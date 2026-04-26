<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\BatchLearningQuiz;
use App\Models\LearningQuiz;
use App\Models\LearningQuizAnswer;
use App\Models\LearningQuizAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class LearningQuizAttemptController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $batchId = $request->input('batch_id');
        $learningQuizId = $request->input('learning_quiz_id');
        $batchLearningQuizId = $request->input('batch_learning_quiz_id');
        $status = $request->input('status');
        $passed = $request->input('passed');

        $attempts = LearningQuizAttempt::query()
            ->with([
                'student:id,name,email,phone',
                'learningQuiz:id,title,quiz_type,passing_score,max_attempts,status',
                'batch:id,program_id,name,start_date,end_date,status',
                'batch.program:id,name',
                'batchLearningQuiz:id,learning_quiz_id,batch_id,due_at,closed_at,passing_score,max_attempts,status',
                'gradedBy:id,name',
            ])
            ->withCount('answers')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('student', function ($studentQuery) use ($search) {
                        $studentQuery
                            ->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%')
                            ->orWhere('phone', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('learningQuiz', function ($quizQuery) use ($search) {
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
            ->when($batchLearningQuizId, function ($query) use ($batchLearningQuizId) {
                $query->where('batch_learning_quiz_id', $batchLearningQuizId);
            })
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($passed !== null && $passed !== '', function ($query) use ($passed) {
                $query->where('is_passed', (bool) $passed);
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

        $learningQuizzes = LearningQuiz::query()
            ->orderBy('title')
            ->get([
                'id',
                'title',
                'quiz_type',
                'passing_score',
                'max_attempts',
                'status',
            ]);

        $batchLearningQuizzes = BatchLearningQuiz::query()
            ->with([
                'learningQuiz:id,title',
                'batch:id,program_id,name',
                'batch.program:id,name',
            ])
            ->orderByDesc('due_at')
            ->orderByDesc('id')
            ->get([
                'id',
                'learning_quiz_id',
                'batch_id',
                'due_at',
                'status',
            ]);

        $stats = [
            'total' => LearningQuizAttempt::count(),
            'in_progress' => LearningQuizAttempt::where('status', 'in_progress')->count(),
            'submitted' => LearningQuizAttempt::where('status', 'submitted')->count(),
            'graded' => LearningQuizAttempt::where('status', 'graded')->count(),
            'passed' => LearningQuizAttempt::where('is_passed', true)->count(),
            'failed' => LearningQuizAttempt::where('is_passed', false)->count(),
        ];

        return view('academic.learning-quiz-attempts.index', [
            'attempts' => $attempts,
            'batches' => $batches,
            'learningQuizzes' => $learningQuizzes,
            'batchLearningQuizzes' => $batchLearningQuizzes,
            'statuses' => $this->statuses(),
            'stats' => $stats,
        ]);
    }

    public function show(LearningQuizAttempt $attempt): View
    {
        $attempt->load([
            'student:id,name,email,phone',
            'learningQuiz:id,title,quiz_type,passing_score,max_attempts,status',
            'batch:id,program_id,name,start_date,end_date,status',
            'batch.program:id,name',
            'batchLearningQuiz:id,learning_quiz_id,batch_id,due_at,closed_at,passing_score,max_attempts,status',
            'gradedBy:id,name',
            'answers' => function ($query) {
                $query->orderBy('id');
            },
            'answers.question:id,learning_quiz_id,question_text,question_type,explanation,score,sort_order,is_required,is_active',
            'answers.question.options:id,learning_quiz_question_id,option_text,is_correct,sort_order,is_active',
            'answers.option:id,learning_quiz_question_id,option_text,is_correct',
        ]);

        $attempt->answers = $attempt->answers->sortBy(function ($answer) {
            return $answer->question?->sort_order ?? 999999;
        })->values();

        return view('academic.learning-quiz-attempts.show', [
            'attempt' => $attempt,
            'statuses' => $this->statuses(),
        ]);
    }

    public function gradeAnswer(
        Request $request,
        LearningQuizAttempt $attempt,
        LearningQuizAnswer $answer
    ): JsonResponse {
        $this->ensureAnswerBelongsToAttempt($attempt, $answer);

        try {
            $answer->loadMissing('question:id,score,question_type');

            $maxScore = (float) ($answer->question?->score ?? 0);

            $validated = $request->validate([
                'score' => [
                    'required',
                    'numeric',
                    'min:0',
                    'max:' . $maxScore,
                ],
                'is_correct' => [
                    'nullable',
                    'boolean',
                ],
                'feedback' => [
                    'nullable',
                    'string',
                ],
            ], [
                'score.max' => "Score jawaban tidak boleh lebih dari {$maxScore}.",
            ]);

            DB::transaction(function () use ($attempt, $answer, $validated) {
                $answer->update([
                    'score' => $validated['score'],
                    'is_correct' => array_key_exists('is_correct', $validated)
                        ? (bool) $validated['is_correct']
                        : $answer->is_correct,
                    'feedback' => $validated['feedback'] ?? null,
                ]);

                $this->recalculateAttemptScore($attempt);
            });

            return response()->json([
                'success' => true,
                'message' => 'Answer berhasil dinilai.',
                'data' => [
                    'id' => $answer->id,
                ],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->errorResponse('Gagal menilai answer.', $e);
        }
    }

    public function gradeAttempt(LearningQuizAttempt $attempt): JsonResponse
    {
        try {
            DB::transaction(function () use ($attempt) {
                $this->recalculateAttemptScore($attempt);
            });

            return response()->json([
                'success' => true,
                'message' => 'Attempt berhasil dihitung ulang dan ditandai graded.',
                'data' => [
                    'id' => $attempt->id,
                ],
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse('Gagal menghitung ulang attempt.', $e);
        }
    }

    public function updateStatus(Request $request, LearningQuizAttempt $attempt): JsonResponse
    {
        try {
            $validated = $request->validate([
                'status' => [
                    'required',
                    Rule::in(array_keys($this->statuses())),
                ],
            ]);

            DB::transaction(function () use ($attempt, $validated) {
                $attempt->update([
                    'status' => $validated['status'],
                ]);

                if ($validated['status'] === 'graded') {
                    $this->recalculateAttemptScore($attempt);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Status attempt berhasil diperbarui.',
                'data' => [
                    'id' => $attempt->id,
                    'status' => $validated['status'],
                ],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->errorResponse('Gagal memperbarui status attempt.', $e);
        }
    }

    public function destroy(LearningQuizAttempt $attempt): JsonResponse
    {
        try {
            DB::transaction(function () use ($attempt) {
                $attempt->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Quiz attempt berhasil dihapus.',
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse('Gagal menghapus quiz attempt.', $e);
        }
    }

    private function recalculateAttemptScore(LearningQuizAttempt $attempt): void
    {
        $attempt->loadMissing([
            'answers.question:id,score',
            'batchLearningQuiz:id,passing_score',
            'learningQuiz:id,passing_score',
        ]);

        $totalScore = (float) $attempt->answers->sum(function ($answer) {
            return (float) ($answer->question?->score ?? 0);
        });

        $score = (float) $attempt->answers->sum(function ($answer) {
            return (float) ($answer->score ?? 0);
        });

        $percentage = $totalScore > 0
            ? round(($score / $totalScore) * 100, 2)
            : 0;

        $passingScore = $attempt->batchLearningQuiz?->passing_score
            ?? $attempt->learningQuiz?->passing_score
            ?? 70;

        $attempt->update([
            'score' => $score,
            'total_score' => $totalScore,
            'percentage' => $percentage,
            'is_passed' => $percentage >= $passingScore,
            'status' => 'graded',
            'graded_by' => auth()->id(),
            'graded_at' => now(),
        ]);
    }

    private function ensureAnswerBelongsToAttempt(
        LearningQuizAttempt $attempt,
        LearningQuizAnswer $answer
    ): void {
        abort_unless(
            (int) $answer->learning_quiz_attempt_id === (int) $attempt->id,
            404
        );
    }

    private function statuses(): array
    {
        return [
            'in_progress' => 'In Progress',
            'submitted' => 'Submitted',
            'graded' => 'Graded',
            'expired' => 'Expired',
            'cancelled' => 'Cancelled',
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