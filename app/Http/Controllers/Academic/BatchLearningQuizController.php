<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\BatchLearningQuiz;
use App\Models\LearningQuiz;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class BatchLearningQuizController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $batchId = $request->input('batch_id');
        $learningQuizId = $request->input('learning_quiz_id');
        $status = $request->input('status');

        $batchLearningQuizzes = BatchLearningQuiz::query()
            ->with([
                'learningQuiz:id,topic_id,sub_topic_id,title,quiz_type,duration_minutes,passing_score,max_attempts,status,is_active',
                'learningQuiz.topic:id,module_id,name',
                'learningQuiz.topic.module:id,program_stage_id,name',
                'learningQuiz.topic.module.stage:id,program_id,name',
                'learningQuiz.topic.module.stage.program:id,name',
                'learningQuiz.subTopic:id,topic_id,name',
                'batch:id,program_id,name,start_date,end_date,status',
                'batch.program:id,name',
                'createdBy:id,name',
                'updatedBy:id,name',
            ])
            ->withCount('attempts')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('learningQuiz', function ($quizQuery) use ($search) {
                        $quizQuery->where('title', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('batch', function ($batchQuery) use ($search) {
                        $batchQuery->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('batch.program', function ($programQuery) use ($search) {
                        $programQuery->where('name', 'like', '%' . $search . '%');
                    });
                });
            })
            ->when($batchId, function ($query) use ($batchId) {
                $query->where('batch_id', $batchId);
            })
            ->when($learningQuizId, function ($query) use ($learningQuizId) {
                $query->where('learning_quiz_id', $learningQuizId);
            })
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->orderByRaw('due_at IS NULL')
            ->orderBy('due_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $learningQuizzes = LearningQuiz::query()
            ->where('is_active', true)
            ->orderBy('title')
            ->get([
                'id',
                'title',
                'quiz_type',
                'duration_minutes',
                'passing_score',
                'max_attempts',
                'status',
                'is_active',
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
            'total' => BatchLearningQuiz::count(),
            'published' => BatchLearningQuiz::where('status', 'published')->count(),
            'draft' => BatchLearningQuiz::where('status', 'draft')->count(),
            'closed' => BatchLearningQuiz::where('status', 'closed')->count(),
            'active' => BatchLearningQuiz::where('is_active', true)->count(),
        ];

        return view('academic.batch-learning-quizzes.index', [
            'batchLearningQuizzes' => $batchLearningQuizzes,
            'learningQuizzes' => $learningQuizzes,
            'batches' => $batches,
            'statuses' => $this->statuses(),
            'stats' => $stats,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $this->validateBatchLearningQuiz($request);

            $batchLearningQuiz = DB::transaction(function () use ($validated) {
                return BatchLearningQuiz::create([
                    'learning_quiz_id' => $validated['learning_quiz_id'],
                    'batch_id' => $validated['batch_id'],

                    'available_at' => $validated['available_at'] ?? null,
                    'due_at' => $validated['due_at'] ?? null,
                    'closed_at' => $validated['closed_at'] ?? null,

                    'duration_minutes' => $validated['duration_minutes'] ?? null,
                    'passing_score' => $validated['passing_score'] ?? null,
                    'max_attempts' => $validated['max_attempts'] ?? null,

                    'allow_late_attempt' => (bool) ($validated['allow_late_attempt'] ?? false),

                    'status' => $validated['status'] ?? 'draft',
                    'is_active' => (bool) ($validated['is_active'] ?? true),

                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Batch learning quiz berhasil ditambahkan.',
                'data' => [
                    'id' => $batchLearningQuiz->id,
                ],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->errorResponse('Gagal menambahkan batch learning quiz.', $e);
        }
    }

    public function update(Request $request, BatchLearningQuiz $batchLearningQuiz): JsonResponse
    {
        try {
            $validated = $this->validateBatchLearningQuiz($request, $batchLearningQuiz);

            DB::transaction(function () use ($batchLearningQuiz, $validated) {
                $batchLearningQuiz->update([
                    'learning_quiz_id' => $validated['learning_quiz_id'],
                    'batch_id' => $validated['batch_id'],

                    'available_at' => $validated['available_at'] ?? null,
                    'due_at' => $validated['due_at'] ?? null,
                    'closed_at' => $validated['closed_at'] ?? null,

                    'duration_minutes' => $validated['duration_minutes'] ?? null,
                    'passing_score' => $validated['passing_score'] ?? null,
                    'max_attempts' => $validated['max_attempts'] ?? null,

                    'allow_late_attempt' => (bool) ($validated['allow_late_attempt'] ?? false),

                    'status' => $validated['status'] ?? $batchLearningQuiz->status ?? 'draft',
                    'is_active' => (bool) ($validated['is_active'] ?? true),

                    'updated_by' => auth()->id(),
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Batch learning quiz berhasil diperbarui.',
                'data' => [
                    'id' => $batchLearningQuiz->id,
                ],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->errorResponse('Gagal memperbarui batch learning quiz.', $e);
        }
    }

    public function destroy(BatchLearningQuiz $batchLearningQuiz): JsonResponse
    {
        try {
            DB::transaction(function () use ($batchLearningQuiz) {
                $batchLearningQuiz->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Batch learning quiz berhasil dihapus.',
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse('Gagal menghapus batch learning quiz.', $e);
        }
    }

    private function validateBatchLearningQuiz(Request $request, ?BatchLearningQuiz $batchLearningQuiz = null): array
    {
        $batchLearningQuizId = $batchLearningQuiz?->id;

        $validated = $request->validate([
            'learning_quiz_id' => [
                'required',
                'exists:learning_quizzes,id',
                Rule::unique('batch_learning_quizzes', 'learning_quiz_id')
                    ->where(fn ($query) => $query->where('batch_id', $request->input('batch_id')))
                    ->ignore($batchLearningQuizId),
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
            ],

            'closed_at' => [
                'nullable',
                'date',
            ],

            'duration_minutes' => [
                'nullable',
                'integer',
                'min:1',
                'max:9999',
            ],

            'passing_score' => [
                'nullable',
                'integer',
                'min:0',
                'max:100',
            ],

            'max_attempts' => [
                'nullable',
                'integer',
                'min:1',
                'max:99',
            ],

            'allow_late_attempt' => [
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
            'learning_quiz_id.unique' => 'Learning quiz ini sudah diberikan ke batch yang dipilih.',
        ]);

        $this->validateDateOrder($validated);

        return $validated;
    }

    private function validateDateOrder(array $validated): void
    {
        $availableAt = !empty($validated['available_at'])
            ? Carbon::parse($validated['available_at'])
            : null;

        $dueAt = !empty($validated['due_at'])
            ? Carbon::parse($validated['due_at'])
            : null;

        $closedAt = !empty($validated['closed_at'])
            ? Carbon::parse($validated['closed_at'])
            : null;

        if ($availableAt && $dueAt && $dueAt->lt($availableAt)) {
            throw ValidationException::withMessages([
                'due_at' => ['Deadline tidak boleh lebih awal dari Available At.'],
            ]);
        }

        if ($dueAt && $closedAt && $closedAt->lt($dueAt)) {
            throw ValidationException::withMessages([
                'closed_at' => ['Closed At tidak boleh lebih awal dari Deadline.'],
            ]);
        }

        if (!$dueAt && $availableAt && $closedAt && $closedAt->lt($availableAt)) {
            throw ValidationException::withMessages([
                'closed_at' => ['Closed At tidak boleh lebih awal dari Available At.'],
            ]);
        }
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