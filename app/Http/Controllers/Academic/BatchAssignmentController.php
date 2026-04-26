<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Batch;
use App\Models\BatchAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class BatchAssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $batchId = $request->input('batch_id');
        $assignmentId = $request->input('assignment_id');
        $status = $request->input('status');

        $batchAssignments = BatchAssignment::query()
            ->with([
                'assignment:id,topic_id,sub_topic_id,title,assignment_type,max_score,status,is_active',
                'assignment.topic:id,module_id,name',
                'assignment.topic.module:id,program_stage_id,name',
                'assignment.topic.module.stage:id,program_id,name',
                'assignment.topic.module.stage.program:id,name',
                'assignment.subTopic:id,topic_id,name',
                'batch:id,program_id,name,start_date,end_date,status',
                'batch.program:id,name',
                'createdBy:id,name',
                'updatedBy:id,name',
            ])
            ->withCount('submissions')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('assignment', function ($assignmentQuery) use ($search) {
                        $assignmentQuery->where('title', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('batch', function ($batchQuery) use ($search) {
                        $batchQuery->where('name', 'like', '%' . $search . '%');
                    });
                });
            })
            ->when($batchId, function ($query) use ($batchId) {
                $query->where('batch_id', $batchId);
            })
            ->when($assignmentId, function ($query) use ($assignmentId) {
                $query->where('assignment_id', $assignmentId);
            })
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->orderByRaw('due_at IS NULL')
            ->orderBy('due_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $assignments = Assignment::query()
            ->where('is_active', true)
            ->orderBy('title')
            ->get([
                'id',
                'title',
                'assignment_type',
                'max_score',
                'status',
            ]);

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

        $stats = [
            'total' => BatchAssignment::count(),
            'published' => BatchAssignment::where('status', 'published')->count(),
            'draft' => BatchAssignment::where('status', 'draft')->count(),
            'closed' => BatchAssignment::where('status', 'closed')->count(),
        ];

        return view('academic.batch-assignments.index', [
            'batchAssignments' => $batchAssignments,
            'assignments' => $assignments,
            'batches' => $batches,
            'statuses' => $this->statuses(),
            'stats' => $stats,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $this->validateBatchAssignment($request);

            $batchAssignment = DB::transaction(function () use ($validated) {
                return BatchAssignment::create([
                    'assignment_id' => $validated['assignment_id'],
                    'batch_id' => $validated['batch_id'],

                    'available_at' => $validated['available_at'] ?? null,
                    'due_at' => $validated['due_at'] ?? null,
                    'closed_at' => $validated['closed_at'] ?? null,

                    'max_score' => $validated['max_score'] ?? null,
                    'allow_late_submission' => (bool) ($validated['allow_late_submission'] ?? false),

                    'status' => $validated['status'] ?? 'draft',
                    'is_active' => (bool) ($validated['is_active'] ?? true),

                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Batch assignment berhasil ditambahkan.',
                'data' => [
                    'id' => $batchAssignment->id,
                ],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->errorResponse('Gagal menambahkan batch assignment.', $e);
        }
    }

    public function update(Request $request, BatchAssignment $batchAssignment): JsonResponse
    {
        try {
            $validated = $this->validateBatchAssignment($request, $batchAssignment);

            DB::transaction(function () use ($batchAssignment, $validated) {
                $batchAssignment->update([
                    'assignment_id' => $validated['assignment_id'],
                    'batch_id' => $validated['batch_id'],

                    'available_at' => $validated['available_at'] ?? null,
                    'due_at' => $validated['due_at'] ?? null,
                    'closed_at' => $validated['closed_at'] ?? null,

                    'max_score' => $validated['max_score'] ?? null,
                    'allow_late_submission' => (bool) ($validated['allow_late_submission'] ?? false),

                    'status' => $validated['status'] ?? $batchAssignment->status ?? 'draft',
                    'is_active' => (bool) ($validated['is_active'] ?? true),

                    'updated_by' => auth()->id(),
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Batch assignment berhasil diperbarui.',
                'data' => [
                    'id' => $batchAssignment->id,
                ],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->errorResponse('Gagal memperbarui batch assignment.', $e);
        }
    }

    public function destroy(BatchAssignment $batchAssignment): JsonResponse
    {
        try {
            DB::transaction(function () use ($batchAssignment) {
                $batchAssignment->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Batch assignment berhasil dihapus.',
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse('Gagal menghapus batch assignment.', $e);
        }
    }

    private function validateBatchAssignment(Request $request, ?BatchAssignment $batchAssignment = null): array
    {
        $batchAssignmentId = $batchAssignment?->id;

        return $request->validate([
            'assignment_id' => [
                'required',
                'exists:assignments,id',
                Rule::unique('batch_assignments', 'assignment_id')
                    ->where(fn ($query) => $query->where('batch_id', $request->input('batch_id')))
                    ->ignore($batchAssignmentId),
            ],

            'batch_id' => [
                'required',
                'exists:batches,id',
            ],

            'available_at' => [
                'nullable',
                'date',
            ],

            'due_at' => [
                'nullable',
                'date',
                'after_or_equal:available_at',
            ],

            'closed_at' => [
                'nullable',
                'date',
                'after_or_equal:due_at',
            ],

            'max_score' => [
                'nullable',
                'integer',
                'min:1',
                'max:999',
            ],

            'allow_late_submission' => [
                'required',
                'boolean',
            ],

            'status' => [
                'required',
                Rule::in(array_keys($this->statuses())),
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ], [
            'assignment_id.unique' => 'Assignment ini sudah diberikan ke batch yang dipilih.',
            'due_at.after_or_equal' => 'Deadline tidak boleh lebih awal dari Available At.',
            'closed_at.after_or_equal' => 'Closed At tidak boleh lebih awal dari Deadline.',
        ]);
    }

    private function statuses(): array
    {
        return [
            'draft' => 'Draft',
            'published' => 'Published',
            'closed' => 'Closed',
            'archived' => 'Archived',
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