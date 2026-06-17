<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Batch;
use App\Models\BatchAssignment;
use App\Notifications\AssignmentSubmissionReviewedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class AssignmentSubmissionController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $batchId = $request->input('batch_id');
        $assignmentId = $request->input('assignment_id');
        $batchAssignmentId = $request->input('batch_assignment_id');
        $status = $request->input('status');

        $submissions = AssignmentSubmission::query()
            ->with([
                'student:id,full_name,email,phone,status',
                'assignment:id,title,assignment_type,max_score',
                'batch:id,program_id,name,start_date,end_date,status',
                'batch.program:id,name',
                'batchAssignment:id,assignment_id,batch_id,available_at,due_at,closed_at,max_score,status,is_active',
                'batchAssignment.assignment:id,title,assignment_type,max_score',
                'reviewedBy:id,name',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('student', function ($studentQuery) use ($search) {
                        $studentQuery
                            ->where('full_name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%')
                            ->orWhere('phone', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('assignment', function ($assignmentQuery) use ($search) {
                        $assignmentQuery->where('title', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('batchAssignment.assignment', function ($assignmentQuery) use ($search) {
                        $assignmentQuery->where('title', 'like', '%' . $search . '%');
                    })
                    ->orWhere('answer_text', 'like', '%' . $search . '%')
                    ->orWhere('answer_url', 'like', '%' . $search . '%')
                    ->orWhere('submitted_file', 'like', '%' . $search . '%');
                });
            })
            ->when($batchId, function ($query) use ($batchId) {
                $query->where('batch_id', $batchId);
            })
            ->when($assignmentId, function ($query) use ($assignmentId) {
                $query->where('assignment_id', $assignmentId);
            })
            ->when($batchAssignmentId, function ($query) use ($batchAssignmentId) {
                $query->where('batch_assignment_id', $batchAssignmentId);
            })
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->orderByRaw('submitted_at IS NULL')
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $batches = Batch::query()
            ->with('program:id,name')
            ->orderByDesc('start_date')
            ->orderBy('name')
            ->get([
                'id',
                'program_id',
                'name',
                'start_date',
                'end_date',
                'status',
            ]);

        $assignments = Assignment::query()
            ->orderBy('title')
            ->get([
                'id',
                'title',
                'assignment_type',
                'max_score',
                'status',
            ]);

        $batchAssignments = BatchAssignment::query()
            ->with([
                'assignment:id,title',
                'batch:id,program_id,name',
                'batch.program:id,name',
            ])
            ->orderByDesc('due_at')
            ->orderByDesc('id')
            ->get([
                'id',
                'assignment_id',
                'batch_id',
                'due_at',
                'status',
            ]);

        $stats = [
            'total' => AssignmentSubmission::count(),
            'draft' => AssignmentSubmission::where('status', 'draft')->count(),
            'submitted' => AssignmentSubmission::where('status', 'submitted')->count(),
            'late' => AssignmentSubmission::where('status', 'late')->count(),
            'reviewed' => AssignmentSubmission::where('status', 'reviewed')->count(),
            'returned' => AssignmentSubmission::where('status', 'returned')->count(),
        ];

        return view('academic.assignment-submissions.index', [
            'submissions' => $submissions,
            'batches' => $batches,
            'assignments' => $assignments,
            'batchAssignments' => $batchAssignments,
            'statuses' => $this->statuses(),
            'stats' => $stats,
        ]);
    }

    public function review(Request $request, AssignmentSubmission $assignmentSubmission): JsonResponse
    {
        try {
            $validated = $request->validate([
                'score' => [
                    'required',
                    'numeric',
                    'min:0',
                ],
                'feedback' => [
                    'required',
                    'string',
                    'max:5000',
                ],
            ], [
                'feedback.required' => 'Notes dari instructor wajib diisi saat submission direview.',
                'feedback.max' => 'Notes dari instructor maksimal 5000 karakter.',
            ]);

            $maxScore = $this->resolveMaxScore($assignmentSubmission);

            if ((float) $validated['score'] > (float) $maxScore) {
                throw ValidationException::withMessages([
                    'score' => ["Score tidak boleh lebih dari max score {$maxScore}."],
                ]);
            }

            DB::transaction(function () use ($assignmentSubmission, $validated) {
                $assignmentSubmission->update([
                    'score' => $validated['score'],
                    'feedback' => $validated['feedback'],
                    'status' => 'reviewed',
                    'reviewed_at' => now(),
                    'reviewed_by' => auth()->id(),
                ]);
            });

            $assignmentSubmission->refresh();

            $notificationSent = $this->sendSubmissionReviewedNotification($assignmentSubmission);

            return response()->json([
                'success' => true,
                'message' => $notificationSent
                    ? 'Submission berhasil direview dan email notifikasi sudah dikirim ke student.'
                    : 'Submission berhasil direview, tetapi email notifikasi belum terkirim. Cek log Laravel untuk detailnya.',
                'data' => [
                    'id' => $assignmentSubmission->id,
                    'status' => 'reviewed',
                    'notification_sent' => $notificationSent,
                ],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->errorResponse('Gagal mereview submission.', $e);
        }
    }

    public function returnRevision(Request $request, AssignmentSubmission $assignmentSubmission): JsonResponse
    {
        try {
            $validated = $request->validate([
                'feedback' => [
                    'required',
                    'string',
                    'max:5000',
                ],
            ], [
                'feedback.required' => 'Feedback wajib diisi saat mengembalikan submission.',
                'feedback.max' => 'Feedback maksimal 5000 karakter.',
            ]);

            DB::transaction(function () use ($assignmentSubmission, $validated) {
                $assignmentSubmission->update([
                    'feedback' => $validated['feedback'],
                    'status' => 'returned',
                    'reviewed_at' => now(),
                    'reviewed_by' => auth()->id(),
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Submission berhasil dikembalikan untuk revisi.',
                'data' => [
                    'id' => $assignmentSubmission->id,
                    'status' => 'returned',
                ],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->errorResponse('Gagal mengembalikan submission.', $e);
        }
    }

    public function markSubmitted(Request $request, AssignmentSubmission $assignmentSubmission): JsonResponse
    {
        try {
            $validated = $request->validate([
                'status' => [
                    'required',
                    Rule::in(['submitted', 'late']),
                ],
            ]);

            DB::transaction(function () use ($assignmentSubmission, $validated) {
                $assignmentSubmission->update([
                    'status' => $validated['status'],
                    'submitted_at' => $assignmentSubmission->submitted_at ?? now(),
                    'score' => null,
                    'feedback' => null,
                    'reviewed_at' => null,
                    'reviewed_by' => null,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Status submission berhasil diperbarui.',
                'data' => [
                    'id' => $assignmentSubmission->id,
                    'status' => $validated['status'],
                ],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->errorResponse('Gagal memperbarui status submission.', $e);
        }
    }

    public function destroy(AssignmentSubmission $assignmentSubmission): JsonResponse
    {
        try {
            DB::transaction(function () use ($assignmentSubmission) {
                $assignmentSubmission->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Submission berhasil dihapus.',
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse('Gagal menghapus submission.', $e);
        }
    }

    private function sendSubmissionReviewedNotification(AssignmentSubmission $assignmentSubmission): bool
    {
        $assignmentSubmission->loadMissing([
            'student:id,full_name,email',
            'assignment:id,title,assignment_type,max_score',
            'batch:id,program_id,name',
            'batch.program:id,name',
            'batchAssignment:id,assignment_id,batch_id,max_score,due_at,status,is_active',
            'batchAssignment.assignment:id,title,assignment_type,max_score',
            'reviewedBy:id,name',
        ]);

        if ($assignmentSubmission->status !== 'reviewed') {
            Log::info('Assignment review notification skipped because submission is not reviewed.', [
                'assignment_submission_id' => $assignmentSubmission->id,
                'status' => $assignmentSubmission->status,
            ]);

            return false;
        }

        if (blank($assignmentSubmission->feedback)) {
            Log::info('Assignment review notification skipped because instructor notes are empty.', [
                'assignment_submission_id' => $assignmentSubmission->id,
            ]);

            return false;
        }

        $student = $assignmentSubmission->student;

        if (! $student || blank($student->email)) {
            Log::warning('Assignment review notification skipped because student email is missing.', [
                'assignment_submission_id' => $assignmentSubmission->id,
                'student_id' => $student->id ?? null,
            ]);

            return false;
        }

        try {
            $student->notify(new AssignmentSubmissionReviewedNotification($assignmentSubmission));

            Log::info('Assignment review notification sent.', [
                'assignment_submission_id' => $assignmentSubmission->id,
                'student_id' => $student->id,
                'student_email' => $student->email,
            ]);

            return true;
        } catch (Throwable $e) {
            Log::warning('Failed to send assignment submission reviewed notification.', [
                'assignment_submission_id' => $assignmentSubmission->id,
                'assignment_id' => $assignmentSubmission->assignment_id,
                'batch_assignment_id' => $assignmentSubmission->batch_assignment_id,
                'student_id' => $student->id ?? null,
                'student_email' => $student->email ?? null,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function resolveMaxScore(AssignmentSubmission $assignmentSubmission): int|float
    {
        $assignmentSubmission->loadMissing([
            'assignment:id,max_score',
            'batchAssignment:id,assignment_id,max_score',
            'batchAssignment.assignment:id,max_score',
        ]);

        return $assignmentSubmission->batchAssignment?->max_score
            ?? $assignmentSubmission->assignment?->max_score
            ?? $assignmentSubmission->batchAssignment?->assignment?->max_score
            ?? 100;
    }

    private function statuses(): array
    {
        return [
            'draft' => 'Draft',
            'submitted' => 'Submitted',
            'late' => 'Late',
            'reviewed' => 'Reviewed',
            'returned' => 'Returned',
        ];
    }

    private function errorResponse(string $message, Throwable $e): JsonResponse
    {
        Log::error($message, [
            'error' => $e->getMessage(),
            'trace' => config('app.debug') ? $e->getTraceAsString() : null,
        ]);

        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => config('app.debug') ? $e->getMessage() : null,
        ], 500);
    }
}