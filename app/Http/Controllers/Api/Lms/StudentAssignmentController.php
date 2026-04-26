<?php

namespace App\Http\Controllers\Api\Lms;

use App\Http\Controllers\Controller;
use App\Models\AssignmentSubmission;
use App\Models\BatchAssignment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StudentAssignmentController extends Controller
{
    public function show(Request $request, BatchAssignment $batchAssignment): JsonResponse
    {
        $student = $this->resolveStudent($request->user());
        $batchIds = $this->resolveStudentBatchIds($student);

        if (!$this->studentCanAccessAssignment($batchAssignment, $batchIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment tidak ditemukan atau kamu tidak punya akses ke assignment ini.',
            ], 404);
        }

        $this->loadBatchAssignmentRelations($batchAssignment);

        $submission = $this->getLatestSubmission($student, $batchAssignment);

        return response()->json([
            'success' => true,
            'data' => [
                'assignment' => $this->formatAssignment($batchAssignment, $submission),
            ],
        ]);
    }

    public function submit(Request $request, BatchAssignment $batchAssignment): JsonResponse
    {
        $student = $this->resolveStudent($request->user());
        $batchIds = $this->resolveStudentBatchIds($student);

        if (!$this->studentCanAccessAssignment($batchAssignment, $batchIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment tidak ditemukan atau kamu tidak punya akses ke assignment ini.',
            ], 404);
        }

        $this->loadBatchAssignmentRelations($batchAssignment);

        $assignmentType = $this->resolveAssignmentType($batchAssignment);

        $validated = $request->validate([
            'file' => ['nullable', 'file', 'max:20480'],
            'answer_text' => ['nullable', 'string', 'max:20000'],
            'submission_url' => ['nullable', 'url', 'max:1500'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->validateSubmissionByType(
            request: $request,
            assignmentType: $assignmentType,
            answerText: $validated['answer_text'] ?? null,
            submissionUrl: $validated['submission_url'] ?? null,
            notes: $validated['notes'] ?? null
        );

        $deadline = $this->resolveDeadline($batchAssignment);

        $status = $deadline && now()->greaterThan($deadline)
            ? 'late'
            : 'submitted';

        $submission = $this->getLatestSubmission($student, $batchAssignment);

        $payload = $this->buildSubmissionPayload(
            request: $request,
            student: $student,
            batchAssignment: $batchAssignment,
            status: $status,
            notes: $validated['notes'] ?? null,
            answerText: $validated['answer_text'] ?? null,
            submissionUrl: $validated['submission_url'] ?? null,
            currentSubmission: $submission
        );

        if ($submission) {
            $submission->update($payload);
        } else {
            $submission = AssignmentSubmission::create($payload);
        }

        $freshAssignment = $batchAssignment->fresh();

        if ($freshAssignment) {
            $this->loadBatchAssignmentRelations($freshAssignment);
        }

        $freshSubmission = $submission->fresh();

        return response()->json([
            'success' => true,
            'message' => $status === 'late'
                ? 'Assignment berhasil dikumpulkan, tapi tercatat late karena melewati deadline.'
                : 'Assignment berhasil dikumpulkan.',
            'data' => [
                'assignment' => $this->formatAssignment(
                    $freshAssignment ?: $batchAssignment,
                    $freshSubmission
                ),
                'submission' => $this->formatSubmission($freshSubmission),
            ],
        ]);
    }

    private function validateSubmissionByType(
        Request $request,
        string $assignmentType,
        ?string $answerText,
        ?string $submissionUrl,
        ?string $notes
    ): void {
        $hasFile = $request->hasFile('file');
        $hasAnswer = filled($answerText);
        $hasUrl = filled($submissionUrl);
        $hasNotes = filled($notes);

        if ($assignmentType === 'text' && !$hasAnswer) {
            throw ValidationException::withMessages([
                'answer_text' => ['Text answer wajib diisi.'],
            ]);
        }

        if ($assignmentType === 'file' && !$hasFile) {
            throw ValidationException::withMessages([
                'file' => ['File wajib diupload.'],
            ]);
        }

        if ($assignmentType === 'link' && !$hasUrl) {
            throw ValidationException::withMessages([
                'submission_url' => ['Submission link wajib diisi.'],
            ]);
        }

        if ($assignmentType === 'mixed' && !$hasFile && !$hasAnswer && !$hasUrl && !$hasNotes) {
            throw ValidationException::withMessages([
                'submission' => ['Isi minimal salah satu: file, text answer, submission link, atau notes.'],
            ]);
        }
    }

    private function resolveStudent(?User $user): Student
    {
        if (!$user || !$this->isStudentUser($user)) {
            abort(response()->json([
                'success' => false,
                'message' => 'Unauthorized student account.',
            ], 403));
        }

        $user->loadMissing([
            'student.enrollments.program',
            'student.enrollments.batch.program',
        ]);

        if (!$user->student) {
            abort(response()->json([
                'success' => false,
                'message' => 'Student profile tidak ditemukan.',
            ], 403));
        }

        return $user->student;
    }

    private function isStudentUser(User $user): bool
    {
        return ($user->user_type ?? null) === 'student'
            || ($user->role ?? null) === 'student';
    }

    private function resolveStudentBatchIds(Student $student): Collection
    {
        return $student->enrollments
            ->filter(function ($enrollment) {
                $status = strtolower((string) ($enrollment->status ?? ''));
                $accessStatus = strtolower((string) ($enrollment->access_status ?? ''));

                return in_array($status, [
                    'active',
                    'enrolled',
                    'ongoing',
                    'paid',
                    'confirmed',
                ], true)
                && in_array($accessStatus, [
                    '',
                    'active',
                    'enabled',
                    'open',
                ], true);
            })
            ->pluck('batch_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    private function studentCanAccessAssignment(BatchAssignment $batchAssignment, Collection $batchIds): bool
    {
        if ($batchIds->isEmpty()) {
            return false;
        }

        if (!Schema::hasColumn('batch_assignments', 'batch_id')) {
            return false;
        }

        return $batchIds->contains((int) $batchAssignment->batch_id);
    }

    private function loadBatchAssignmentRelations(BatchAssignment $batchAssignment): void
    {
        $relations = [];

        if (method_exists($batchAssignment, 'assignment')) {
            $relations[] = 'assignment';
        }

        if (method_exists($batchAssignment, 'batch')) {
            $relations[] = 'batch.program';
        }

        if (!empty($relations)) {
            $batchAssignment->loadMissing($relations);
        }
    }

    private function getLatestSubmission(Student $student, BatchAssignment $batchAssignment): ?AssignmentSubmission
    {
        $query = AssignmentSubmission::query()
            ->where('student_id', $student->id);

        if (Schema::hasColumn('assignment_submissions', 'batch_assignment_id')) {
            $query->where('batch_assignment_id', $batchAssignment->id);
        }

        if (
            Schema::hasColumn('assignment_submissions', 'assignment_id')
            && $this->getAssignmentId($batchAssignment)
        ) {
            $query->where('assignment_id', $this->getAssignmentId($batchAssignment));
        }

        if (Schema::hasColumn('assignment_submissions', 'batch_id') && $batchAssignment->batch_id) {
            $query->where('batch_id', $batchAssignment->batch_id);
        }

        return $query
            ->latest('updated_at')
            ->latest('id')
            ->first();
    }

    private function buildSubmissionPayload(
        Request $request,
        Student $student,
        BatchAssignment $batchAssignment,
        string $status,
        ?string $notes,
        ?string $answerText,
        ?string $submissionUrl,
        ?AssignmentSubmission $currentSubmission = null
    ): array {
        $payload = [];

        $this->setPayloadIfColumnExists($payload, 'student_id', $student->id);
        $this->setPayloadIfColumnExists($payload, 'batch_assignment_id', $batchAssignment->id);
        $this->setPayloadIfColumnExists($payload, 'assignment_id', $this->getAssignmentId($batchAssignment));
        $this->setPayloadIfColumnExists($payload, 'batch_id', $batchAssignment->batch_id);
        $this->setPayloadIfColumnExists($payload, 'status', $status);

        if (Schema::hasColumn('assignment_submissions', 'submitted_at')) {
            $payload['submitted_at'] = now();
        }

        $this->setNotesPayload($payload, $notes);
        $this->setAnswerTextPayload($payload, $answerText);
        $this->setSubmissionUrlPayload($payload, $submissionUrl);

        if ($request->hasFile('file')) {
            $this->setFilePayload(
                payload: $payload,
                request: $request,
                student: $student,
                currentSubmission: $currentSubmission
            );
        }

        return $payload;
    }

    private function setPayloadIfColumnExists(array &$payload, string $column, mixed $value): void
    {
        if (Schema::hasColumn('assignment_submissions', $column)) {
            $payload[$column] = $value;
        }
    }

    private function setNotesPayload(array &$payload, ?string $notes): void
    {
        foreach (['notes', 'comment'] as $column) {
            if (Schema::hasColumn('assignment_submissions', $column)) {
                $payload[$column] = $notes;
                return;
            }
        }

        /*
         * Kalau table belum punya notes/comment, jangan pakai submission_text
         * karena sekarang submission_text dipakai sebagai fallback answer_text.
         */
    }

    private function setAnswerTextPayload(array &$payload, ?string $answerText): void
    {
        foreach (['answer_text', 'text_answer', 'submission_answer', 'answer'] as $column) {
            if (Schema::hasColumn('assignment_submissions', $column)) {
                $payload[$column] = $answerText;
                return;
            }
        }

        if (Schema::hasColumn('assignment_submissions', 'submission_text')) {
            $payload['submission_text'] = $answerText;
        }
    }

    private function setSubmissionUrlPayload(array &$payload, ?string $submissionUrl): void
    {
        foreach (['submission_url', 'submission_link', 'link_url', 'url'] as $column) {
            if (Schema::hasColumn('assignment_submissions', $column)) {
                $payload[$column] = $submissionUrl;
                return;
            }
        }
    }

    private function setFilePayload(
        array &$payload,
        Request $request,
        Student $student,
        ?AssignmentSubmission $currentSubmission = null
    ): void {
        $file = $request->file('file');

        if (!$file) {
            return;
        }

        $path = $file->store(
            'assignment-submissions/' . $student->id,
            'public'
        );

        if ($currentSubmission) {
            $oldPath = $this->getSubmissionFilePath($currentSubmission);

            if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        foreach ([
            'file_path',
            'attachment_path',
            'submission_file',
            'document',
            'file',
        ] as $column) {
            if (Schema::hasColumn('assignment_submissions', $column)) {
                $payload[$column] = $path;
                break;
            }
        }

        if (Schema::hasColumn('assignment_submissions', 'file_url')) {
            $payload['file_url'] = Storage::disk('public')->url($path);
        }

        if (Schema::hasColumn('assignment_submissions', 'original_filename')) {
            $payload['original_filename'] = $file->getClientOriginalName();
        } elseif (Schema::hasColumn('assignment_submissions', 'file_name')) {
            $payload['file_name'] = $file->getClientOriginalName();
        }

        if (Schema::hasColumn('assignment_submissions', 'mime_type')) {
            $payload['mime_type'] = $file->getMimeType();
        }

        if (Schema::hasColumn('assignment_submissions', 'file_size')) {
            $payload['file_size'] = $file->getSize();
        }
    }

    private function formatAssignment(BatchAssignment $batchAssignment, ?AssignmentSubmission $submission = null): array
    {
        $this->loadBatchAssignmentRelations($batchAssignment);

        $assignment = method_exists($batchAssignment, 'assignment')
            ? $batchAssignment->assignment
            : null;

        $batch = method_exists($batchAssignment, 'batch')
            ? $batchAssignment->batch
            : null;

        $deadline = $this->resolveDeadline($batchAssignment);
        $assignmentType = $this->resolveAssignmentType($batchAssignment);

        $title = $this->getColumnValue($assignment, [
            'title',
            'name',
        ]) ?: $this->getColumnValue($batchAssignment, [
            'title',
            'name',
        ]) ?: 'Untitled Assignment';

        $instructions = $this->getColumnValue($assignment, [
            'instruction',
            'instructions',
            'description',
            'content',
            'brief',
            'requirements',
        ]) ?: $this->getColumnValue($batchAssignment, [
            'instruction',
            'instructions',
            'description',
            'content',
            'brief',
            'requirements',
        ]);

        $courseName = $batch?->program?->name
            ?? $this->getColumnValue($batchAssignment, ['program_name'])
            ?? $this->getColumnValue($batch, ['name'])
            ?? 'Course';

        $submissionStatus = $submission
            ? ($submission->status ?? 'submitted')
            : 'not_submitted';

        $status = $this->resolveAssignmentStatus($deadline, $submission);

        return [
            'id' => $batchAssignment->id,
            'batch_assignment_id' => $batchAssignment->id,
            'assignment_id' => $this->getAssignmentId($batchAssignment),

            'title' => $title,
            'course_name' => $courseName,
            'program_name' => $batch?->program?->name,
            'batch_name' => $batch?->name,

            'assignment_type' => $assignmentType,
            'type_label' => $this->formatAssignmentTypeLabel($assignmentType),

            'instructions' => $instructions,

            'deadline' => $deadline?->format('Y-m-d H:i:s'),
            'deadline_label' => $this->formatDeadlineLabel($deadline),
            'remaining' => $this->formatRemainingTime($deadline),
            'remaining_label' => $this->formatRemainingTime($deadline),

            'status' => $status,
            'status_label' => $this->formatStatusLabel($status),

            'allowed_file_types_label' => 'PDF, ZIP, image, document',
            'max_file_size' => 20 * 1024 * 1024,

            'can_submit' => true,

            'attachments' => $this->formatAttachments($batchAssignment, $assignment),

            'submission_status' => $submissionStatus,
            'submission_status_label' => $this->formatStatusLabel($submissionStatus),
            'submission' => $this->formatSubmission($submission),
        ];
    }

    private function formatSubmission(?AssignmentSubmission $submission): array
    {
        if (!$submission) {
            return [
                'id' => null,
                'status' => 'not_submitted',
                'status_label' => 'Not Submitted',
                'submitted_at' => null,
                'submitted_at_label' => '-',
                'file_url' => null,
                'submission_url' => null,
                'answer_text' => '',
                'notes' => '',
                'message' => 'Belum ada submission untuk assignment ini.',
            ];
        }

        $fileUrl = $this->getSubmissionFileUrl($submission);
        $submissionUrl = $this->getSubmissionUrl($submission);
        $answerText = $this->getSubmissionAnswerText($submission);
        $notes = $this->getSubmissionNotes($submission);

        $submittedAt = $this->getColumnValue($submission, [
            'submitted_at',
            'created_at',
        ]);

        $submittedAtCarbon = $submittedAt
            ? Carbon::parse($submittedAt)
            : null;

        return [
            'id' => $submission->id,
            'status' => $submission->status ?? 'submitted',
            'status_label' => $this->formatStatusLabel($submission->status ?? 'submitted'),

            'submitted_at' => $submittedAtCarbon?->format('Y-m-d H:i:s'),
            'submitted_at_label' => $submittedAtCarbon?->format('d M Y H:i') ?? '-',

            'file_url' => $fileUrl,
            'submission_url' => $submissionUrl,
            'answer_text' => $answerText,
            'notes' => $notes,

            'message' => 'Assignment sudah dikumpulkan.',
        ];
    }

    private function formatAttachments(BatchAssignment $batchAssignment, $assignment = null): array
    {
        $attachments = collect();

        foreach ([$assignment, $batchAssignment] as $model) {
            if (!$model || !method_exists($model, 'getTable')) {
                continue;
            }

            $resourceColumns = [
                'attachment_url' => 'Attachment',
                'starter_file_url' => 'Starter File',
                'reference_url' => 'Reference',

                'starter_code_url' => 'Starter Code',
                'supporting_file_url' => 'Supporting File',
                'external_reference_url' => 'External Reference',
                'resource_url' => 'Resource',
                'file_url' => 'File',
                'document_url' => 'Document',
            ];

            foreach ($resourceColumns as $column => $label) {
                if (!Schema::hasColumn($model->getTable(), $column)) {
                    continue;
                }

                $url = $model->{$column};

                if (blank($url)) {
                    continue;
                }

                $url = trim((string) $url);

                $attachments->push([
                    'name' => $label,
                    'url' => $this->normalizeFileUrl($url),
                    'type_label' => $this->resolveResourceTypeLabel($url),
                    'source_table' => $model->getTable(),
                    'source_column' => $column,
                ]);
            }
        }

        return $attachments
            ->filter(fn ($item) => !blank($item['url'] ?? null))
            ->unique(fn ($item) => ($item['source_column'] ?? '') . '|' . ($item['url'] ?? ''))
            ->values()
            ->toArray();
    }

    private function resolveAssignmentType(BatchAssignment $batchAssignment): string
    {
        $assignment = method_exists($batchAssignment, 'assignment')
            ? $batchAssignment->assignment
            : null;

        $type = $this->getColumnValue($assignment, ['assignment_type'])
            ?: $this->getColumnValue($batchAssignment, ['assignment_type'])
            ?: 'file';

        $type = strtolower((string) $type);

        return in_array($type, ['text', 'file', 'link', 'mixed'], true)
            ? $type
            : 'file';
    }

    private function formatAssignmentTypeLabel(?string $type): string
    {
        return match ($type) {
            'text' => 'Text Answer',
            'file' => 'File Upload',
            'link' => 'Link Submission',
            'mixed' => 'Mixed Submission',
            default => 'Assignment',
        };
    }

    private function resolveResourceTypeLabel(string $url): string
    {
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            if (str_contains($url, 'drive.google.com')) {
                return 'Google Drive';
            }

            if (str_contains($url, 'github.com')) {
                return 'GitHub';
            }

            if (str_contains($url, 'figma.com')) {
                return 'Figma';
            }

            return 'External Link';
        }

        return 'File';
    }

    private function resolveAssignmentStatus(?Carbon $deadline, ?AssignmentSubmission $submission): string
    {
        if ($submission) {
            return $submission->status ?? 'submitted';
        }

        if ($deadline && now()->greaterThan($deadline)) {
            return 'overdue';
        }

        return 'pending';
    }

    private function resolveDeadline(BatchAssignment $batchAssignment): ?Carbon
    {
        foreach ([
            'due_at',
            'deadline_at',
            'deadline',
            'due_date',
            'closed_at',
            'available_until',
        ] as $column) {
            $value = $this->getColumnValue($batchAssignment, [$column]);

            if ($value) {
                return Carbon::parse($value);
            }
        }

        return null;
    }

    private function formatDeadlineLabel(?Carbon $deadline): string
    {
        if (!$deadline) {
            return 'No deadline';
        }

        return $deadline->format('d M Y H:i');
    }

    private function formatRemainingTime(?Carbon $deadline): string
    {
        if (!$deadline) {
            return 'No deadline';
        }

        $seconds = (int) now()->diffInSeconds($deadline, false);

        if ($seconds < 0) {
            $daysLate = (int) ceil(abs($seconds) / 86400);

            return $daysLate <= 1
                ? 'Overdue'
                : $daysLate . ' days overdue';
        }

        if ($seconds <= 0) {
            return 'Due today';
        }

        $daysLeft = (int) ceil($seconds / 86400);

        if ($daysLeft <= 1) {
            return '1 day left';
        }

        return $daysLeft . ' days left';
    }

    private function formatStatusLabel(?string $status): string
    {
        return match ($status) {
            'not_submitted' => 'Not Submitted',
            'pending' => 'Pending',
            'submitted' => 'Submitted',
            'late' => 'Submitted Late',
            'reviewed' => 'Reviewed',
            'returned' => 'Returned',
            'graded' => 'Graded',
            'completed' => 'Completed',
            'overdue' => 'Overdue',
            default => Str::of($status ?: '-')->replace('_', ' ')->title()->toString(),
        };
    }

    private function getAssignmentId(BatchAssignment $batchAssignment): ?int
    {
        if (Schema::hasColumn('batch_assignments', 'assignment_id') && $batchAssignment->assignment_id) {
            return (int) $batchAssignment->assignment_id;
        }

        $assignment = method_exists($batchAssignment, 'assignment')
            ? $batchAssignment->assignment
            : null;

        return $assignment?->id ? (int) $assignment->id : null;
    }

    private function getSubmissionFilePath(AssignmentSubmission $submission): ?string
    {
        foreach ([
            'file_path',
            'attachment_path',
            'submission_file',
            'document',
            'file',
        ] as $column) {
            $value = $this->getColumnValue($submission, [$column]);

            if ($value) {
                return $value;
            }
        }

        return null;
    }

    private function getSubmissionFileUrl(AssignmentSubmission $submission): ?string
    {
        $fileUrl = $this->getColumnValue($submission, ['file_url']);

        if ($fileUrl) {
            return $this->normalizeFileUrl($fileUrl);
        }

        $path = $this->getSubmissionFilePath($submission);

        if (!$path) {
            return null;
        }

        return $this->normalizeFileUrl($path);
    }

    private function getSubmissionUrl(AssignmentSubmission $submission): ?string
    {
        return $this->getColumnValue($submission, [
            'submission_url',
            'submission_link',
            'link_url',
            'url',
        ]);
    }

    private function getSubmissionAnswerText(AssignmentSubmission $submission): ?string
    {
        return $this->getColumnValue($submission, [
            'answer_text',
            'text_answer',
            'submission_answer',
            'answer',
            'submission_text',
        ]);
    }

    private function getSubmissionNotes(AssignmentSubmission $submission): ?string
    {
        return $this->getColumnValue($submission, [
            'notes',
            'comment',
        ]);
    }

    private function normalizeFileUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, '/storage/')) {
            return asset($path);
        }

        if (str_starts_with($path, 'storage/')) {
            return asset($path);
        }

        return asset('storage/' . ltrim($path, '/'));
    }

    private function getColumnValue($model, array $columns)
    {
        if (!$model || !method_exists($model, 'getTable')) {
            return null;
        }

        foreach ($columns as $column) {
            if (!Schema::hasColumn($model->getTable(), $column)) {
                continue;
            }

            if (!blank($model->{$column})) {
                return $model->{$column};
            }
        }

        return null;
    }
}