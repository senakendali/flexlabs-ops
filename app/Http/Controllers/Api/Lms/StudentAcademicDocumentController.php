<?php

namespace App\Http\Controllers\Api\Lms;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\ReportCard;
use App\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StudentAcademicDocumentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $student = $this->resolveStudent($request);

        if (! $student) {
            return response()->json([
                'message' => 'Student profile tidak ditemukan.',
            ], 422);
        }

        $reportCards = $this->getReportCards($student);
        $certificates = $this->getCertificates($student);

        return response()->json([
            'message' => 'Academic documents berhasil dimuat.',
            'data' => [
                'student' => $this->formatStudent($student),
                'notification_count' => $this->resolveNotificationCount($request),
                'report_cards' => $reportCards,
                'reportCards' => $reportCards,
                'certificates' => $certificates,
                'documents' => collect($reportCards)
                    ->merge($certificates)
                    ->sortByDesc(fn ($item) => $item['sort_date'] ?? $item['sortDate'] ?? null)
                    ->values()
                    ->all(),
                'next_milestone' => $this->resolveNextMilestone($reportCards, $certificates),
                'nextMilestone' => $this->resolveNextMilestone($reportCards, $certificates),
            ],
        ]);
    }

    private function getReportCards(Student $student): array
    {
        if (! class_exists(ReportCard::class)) {
            return [];
        }

        $model = new ReportCard();
        $table = $model->getTable();

        if (! Schema::hasTable($table)) {
            return [];
        }

        $query = ReportCard::query();

        if ($this->hasColumn($table, 'student_id')) {
            $query->where('student_id', $student->id);
        } else {
            return [];
        }

        if ($this->hasColumn($table, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        $this->applyLatestOrdering($query, $table, [
            'published_at',
            'generated_at',
            'created_at',
            'updated_at',
        ]);

        return $query
            ->limit(50)
            ->get()
            ->map(fn (ReportCard $reportCard) => $this->formatReportCard($reportCard))
            ->values()
            ->all();
    }

    private function getCertificates(Student $student): array
    {
        if (! class_exists(Certificate::class)) {
            return [];
        }

        $model = new Certificate();
        $table = $model->getTable();

        if (! Schema::hasTable($table)) {
            return [];
        }

        $query = Certificate::query();

        if ($this->hasColumn($table, 'student_id')) {
            $query->where('student_id', $student->id);
        } else {
            return [];
        }

        if ($this->hasColumn($table, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        $this->applyLatestOrdering($query, $table, [
            'issued_date',
            'completed_date',
            'created_at',
            'updated_at',
        ]);

        return $query
            ->limit(50)
            ->get()
            ->map(fn (Certificate $certificate) => $this->formatCertificate($certificate))
            ->values()
            ->all();
    }

    private function formatReportCard(ReportCard $reportCard): array
    {
        $programTitle = $this->resolveProgramTitle($reportCard);
        $batchName = $this->resolveBatchName($reportCard);

        $title = $this->firstFilled([
            $this->value($reportCard, 'title'),
            $this->value($reportCard, 'report_title'),
            $programTitle ? "Report Card - {$programTitle}" : null,
            'Report Card',
        ]);

        $status = $this->normalizeReportCardStatus($reportCard);

        $publishedAt = $this->firstFilled([
            $this->value($reportCard, 'published_at'),
            $this->value($reportCard, 'generated_at'),
            $this->value($reportCard, 'created_at'),
        ]);

        $pdfUrl = $this->resolveDocumentUrl($reportCard, [
            'download_url',
            'pdf_url',
            'pdf_path_url',
            'file_url',
            'document_url',
            'pdf_path',
            'file_path',
            'document_path',
        ]);

        $finalScore = $this->firstFilled([
            $this->value($reportCard, 'final_score'),
            $this->value($reportCard, 'score'),
            $this->value($reportCard, 'total_score'),
        ]);

        $grade = $this->firstFilled([
            $this->value($reportCard, 'grade'),
            $this->value($reportCard, 'final_grade'),
        ]);

        return [
            'uid' => 'report-card-'.$reportCard->getKey(),
            'id' => $reportCard->getKey(),
            'type' => 'report_card',
            'document_type' => 'report_card',
            'documentType' => 'report_card',
            'type_label' => 'Report Card',
            'typeLabel' => 'Report Card',

            'title' => $title,
            'description' => $this->firstFilled([
                $this->value($reportCard, 'description'),
                $this->value($reportCard, 'summary'),
                'Academic report card containing learning result, score, and grade.',
            ]),

            'program_id' => $this->value($reportCard, 'program_id'),
            'programId' => $this->value($reportCard, 'program_id'),
            'program_name' => $programTitle,
            'programName' => $programTitle,
            'program_title' => $programTitle,
            'programTitle' => $programTitle,

            'batch_id' => $this->value($reportCard, 'batch_id'),
            'batchId' => $this->value($reportCard, 'batch_id'),
            'batch_name' => $batchName,
            'batchName' => $batchName,

            'report_no' => $this->firstFilled([
                $this->value($reportCard, 'report_no'),
                $this->value($reportCard, 'report_number'),
                $this->value($reportCard, 'number'),
            ]),
            'reportNo' => $this->firstFilled([
                $this->value($reportCard, 'report_no'),
                $this->value($reportCard, 'report_number'),
                $this->value($reportCard, 'number'),
            ]),

            'status' => $status,
            'raw_status' => $this->value($reportCard, 'status'),
            'rawStatus' => $this->value($reportCard, 'status'),

            'final_score' => $finalScore,
            'finalScore' => $finalScore,
            'grade' => $grade,

            'progress' => $status === 'published' || $status === 'ready' ? 100 : $this->resolveProgress($reportCard),

            'pdf_url' => $pdfUrl,
            'pdfUrl' => $pdfUrl,
            'download_url' => $pdfUrl,
            'downloadUrl' => $pdfUrl,

            'published_at' => $this->formatDateTime($publishedAt),
            'publishedAt' => $this->formatDateTime($publishedAt),
            'generated_at' => $this->formatDateTime($this->value($reportCard, 'generated_at')),
            'generatedAt' => $this->formatDateTime($this->value($reportCard, 'generated_at')),

            'sort_date' => $this->formatDateTime($publishedAt),
            'sortDate' => $this->formatDateTime($publishedAt),
        ];
    }

    private function formatCertificate(Certificate $certificate): array
    {
        $programTitle = $this->resolveProgramTitle($certificate);
        $batchName = $this->resolveBatchName($certificate);

        $title = $this->firstFilled([
            $this->value($certificate, 'title'),
            $this->value($certificate, 'certificate_title'),
            'Certificate of Achievement',
        ]);

        $status = $this->normalizeCertificateStatus($certificate);

        $issuedDate = $this->firstFilled([
            $this->value($certificate, 'issued_date'),
            $this->value($certificate, 'completed_date'),
            $this->value($certificate, 'created_at'),
        ]);

        $pdfUrl = $this->resolveDocumentUrl($certificate, [
            'download_url',
            'pdf_url',
            'pdf_path_url',
            'file_url',
            'document_url',
            'pdf_path',
            'file_path',
            'document_path',
        ]);

        $verificationUrl = $this->firstFilled([
            $this->value($certificate, 'verification_url'),
            $this->value($certificate, 'verify_url'),
        ]);

        if (! $verificationUrl) {
            $publicToken = $this->firstFilled([
                $this->value($certificate, 'public_token'),
                $this->value($certificate, 'token'),
            ]);

            if ($publicToken) {
                $verificationUrl = url("/certificates/verify/{$publicToken}");
            }
        }

        $finalScore = $this->firstFilled([
            $this->value($certificate, 'final_score'),
            $this->value($certificate, 'score'),
            $this->value($certificate, 'total_score'),
        ]);

        return [
            'uid' => 'certificate-'.$certificate->getKey(),
            'id' => $certificate->getKey(),
            'type' => 'certificate',
            'document_type' => 'certificate',
            'documentType' => 'certificate',
            'type_label' => 'Certificate',
            'typeLabel' => 'Certificate',

            'title' => $title,
            'description' => $this->firstFilled([
                $this->value($certificate, 'description'),
                'Certificate issued for completing the required academic milestone.',
            ]),

            'program_id' => $this->value($certificate, 'program_id'),
            'programId' => $this->value($certificate, 'program_id'),
            'program_name' => $programTitle,
            'programName' => $programTitle,
            'program_title' => $programTitle,
            'programTitle' => $programTitle,

            'batch_id' => $this->value($certificate, 'batch_id'),
            'batchId' => $this->value($certificate, 'batch_id'),
            'batch_name' => $batchName,
            'batchName' => $batchName,

            'certificate_no' => $this->value($certificate, 'certificate_no'),
            'certificateNo' => $this->value($certificate, 'certificate_no'),

            'status' => $status,
            'raw_status' => $this->value($certificate, 'status'),
            'rawStatus' => $this->value($certificate, 'status'),

            'final_score' => $finalScore,
            'finalScore' => $finalScore,
            'grade' => $this->value($certificate, 'grade'),

            'progress' => $status === 'issued' || $status === 'ready' ? 100 : $this->resolveProgress($certificate),

            'pdf_url' => $pdfUrl,
            'pdfUrl' => $pdfUrl,
            'download_url' => $pdfUrl,
            'downloadUrl' => $pdfUrl,

            'verification_url' => $verificationUrl,
            'verificationUrl' => $verificationUrl,

            'issued_date' => $this->formatDate($this->value($certificate, 'issued_date')),
            'issuedDate' => $this->formatDate($this->value($certificate, 'issued_date')),
            'completed_date' => $this->formatDate($this->value($certificate, 'completed_date')),
            'completedDate' => $this->formatDate($this->value($certificate, 'completed_date')),

            'sort_date' => $this->formatDateTime($issuedDate),
            'sortDate' => $this->formatDateTime($issuedDate),
        ];
    }

    private function resolveStudent(Request $request): ?Student
    {
        $user = $request->user();

        if (! $user) {
            return null;
        }

        if (isset($user->student_id) && $user->student_id) {
            return Student::query()->find($user->student_id);
        }

        if (method_exists($user, 'student')) {
            $student = $user->student;

            if ($student?->id) {
                return $student;
            }
        }

        $query = Student::query();

        if (Schema::hasColumn((new Student())->getTable(), 'user_id')) {
            $query->orWhere('user_id', $user->id);
        }

        if (($user->email ?? null) && Schema::hasColumn((new Student())->getTable(), 'email')) {
            $query->orWhere('email', $user->email);
        }

        return $query->first();
    }

    private function formatStudent(Student $student): array
    {
        return [
            'id' => $student->id,
            'name' => $this->firstFilled([
                $this->value($student, 'full_name'),
                $this->value($student, 'name'),
                $this->value($student, 'student_name'),
                'Student',
            ]),
            'full_name' => $this->firstFilled([
                $this->value($student, 'full_name'),
                $this->value($student, 'name'),
                $this->value($student, 'student_name'),
                'Student',
            ]),
            'email' => $this->value($student, 'email'),
            'avatar_url' => $this->firstFilled([
                $this->value($student, 'avatar_url'),
                $this->value($student, 'photo_url'),
                $this->value($student, 'profile_photo_url'),
            ]),
            'avatarUrl' => $this->firstFilled([
                $this->value($student, 'avatar_url'),
                $this->value($student, 'photo_url'),
                $this->value($student, 'profile_photo_url'),
            ]),
        ];
    }

    private function resolveNextMilestone(array $reportCards, array $certificates): ?array
    {
        $pendingReport = collect($reportCards)
            ->first(fn ($item) => ! in_array($item['status'] ?? null, ['published', 'ready'], true));

        if ($pendingReport) {
            return [
                'title' => $pendingReport['title'] ?? 'Report Card',
                'description' => 'Report card kamu sedang diproses oleh academic team.',
                'progress' => (int) ($pendingReport['progress'] ?? 0),
            ];
        }

        $pendingCertificate = collect($certificates)
            ->first(fn ($item) => ! in_array($item['status'] ?? null, ['issued', 'ready'], true));

        if ($pendingCertificate) {
            return [
                'title' => $pendingCertificate['title'] ?? 'Certificate',
                'description' => 'Certificate akan tersedia setelah proses academic verification selesai.',
                'progress' => (int) ($pendingCertificate['progress'] ?? 0),
            ];
        }

        return null;
    }

    private function resolveNotificationCount(Request $request): int
    {
        $user = $request->user();

        if (! $user) {
            return 0;
        }

        if (! Schema::hasTable('notifications')) {
            return 0;
        }

        if (! method_exists($user, 'unreadNotifications')) {
            return 0;
        }

        try {
            return (int) $user->unreadNotifications()->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    private function normalizeReportCardStatus(Model $model): string
    {
        $status = Str::lower((string) $this->value($model, 'status'));
        $pdfUrl = $this->resolveDocumentUrl($model, [
            'download_url',
            'pdf_url',
            'pdf_path_url',
            'file_url',
            'document_url',
            'pdf_path',
            'file_path',
            'document_path',
        ]);

        if ($pdfUrl || in_array($status, ['published', 'final', 'finalized', 'ready', 'completed'], true)) {
            return 'published';
        }

        if (in_array($status, ['pending', 'draft', 'generated'], true)) {
            return 'pending';
        }

        if ($status === 'locked') {
            return 'locked';
        }

        return 'in_progress';
    }

    private function normalizeCertificateStatus(Model $model): string
    {
        $status = Str::lower((string) $this->value($model, 'status'));
        $pdfUrl = $this->resolveDocumentUrl($model, [
            'download_url',
            'pdf_url',
            'pdf_path_url',
            'file_url',
            'document_url',
            'pdf_path',
            'file_path',
            'document_path',
        ]);

        $issuedDate = $this->value($model, 'issued_date');

        if ($pdfUrl || $issuedDate || in_array($status, ['issued', 'published', 'ready', 'earned', 'completed'], true)) {
            return 'issued';
        }

        if (in_array($status, ['pending', 'draft', 'generated'], true)) {
            return 'pending';
        }

        if ($status === 'locked') {
            return 'locked';
        }

        return 'in_progress';
    }

    private function resolveProgress(Model $model): int
    {
        $progress = $this->firstFilled([
            $this->value($model, 'progress'),
            $this->value($model, 'progress_percentage'),
            $this->value($model, 'completion_percentage'),
        ]);

        return max(0, min(100, (int) $progress));
    }

    private function resolveProgramTitle(Model $model): ?string
    {
        foreach (['program', 'course'] as $relation) {
            if (method_exists($model, $relation)) {
                $related = $model->{$relation};

                if ($related) {
                    return $this->firstFilled([
                        $this->value($related, 'title'),
                        $this->value($related, 'name'),
                        $this->value($related, 'program_name'),
                    ]);
                }
            }
        }

        return $this->firstFilled([
            $this->value($model, 'program_title'),
            $this->value($model, 'program_name'),
            $this->value($model, 'course_title'),
            $this->value($model, 'course_name'),
        ]);
    }

    private function resolveBatchName(Model $model): ?string
    {
        if (method_exists($model, 'batch')) {
            $batch = $model->batch;

            if ($batch) {
                return $this->firstFilled([
                    $this->value($batch, 'name'),
                    $this->value($batch, 'title'),
                    $this->value($batch, 'batch_name'),
                ]);
            }
        }

        return $this->firstFilled([
            $this->value($model, 'batch_name'),
            $this->value($model, 'batch_title'),
        ]);
    }

    private function resolveDocumentUrl(Model $model, array $columns): ?string
    {
        foreach ($columns as $column) {
            $value = $this->value($model, $column);

            if (! $value) {
                continue;
            }

            $value = (string) $value;

            if (Str::startsWith($value, ['http://', 'https://'])) {
                return $value;
            }

            $path = Str::of($value)
                ->replace('\\', '/')
                ->replaceStart('public/', '')
                ->replaceStart('/storage/', '')
                ->replaceStart('storage/', '')
                ->ltrim('/')
                ->toString();

            return Storage::disk('public')->url($path);
        }

        return null;
    }

    private function applyLatestOrdering($query, string $table, array $columns): void
    {
        foreach ($columns as $column) {
            if ($this->hasColumn($table, $column)) {
                $query->orderByDesc($column);

                return;
            }
        }

        $query->latest();
    }

    private function value(Model $model, string $column): mixed
    {
        return $model->getAttribute($column);
    }

    private function hasColumn(string $table, string $column): bool
    {
        return Schema::hasColumn($table, $column);
    }

    private function firstFilled(array $values): mixed
    {
        foreach ($values as $value) {
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function formatDate($value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function formatDateTime($value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value)->toISOString();
        } catch (\Throwable) {
            return null;
        }
    }
}