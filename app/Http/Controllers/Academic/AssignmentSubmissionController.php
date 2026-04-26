<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Batch;
use App\Models\BatchAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                'student:id,name,email,phone',
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
                            ->where('name', 'like', '%' . $search . '%')
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
                    'max:999',
                ],

                'feedback' => [
                    'nullable',
                    'string',
                ],
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
                    'feedback' => $validated['feedback'] ?? null,
                    'status' => 'reviewed',
                    'reviewed_at' => now(),
                    'reviewed_by' => auth()->id(),
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Submission berhasil direview.',
                'data' => [
                    'id' => $assignmentSubmission->id,
                    'status' => 'reviewed',
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
                ],
            ], [
                'feedback.required' => 'Feedback wajib diisi saat mengembalikan submission.',
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
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => config('app.debug') ? $e->getMessage() : null,
        ], 500);
    }
}