<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\LearningQuiz;
use App\Models\LearningQuizOption;
use App\Models\LearningQuizQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class LearningQuizQuestionController extends Controller
{
    public function index(Request $request, LearningQuiz $learningQuiz): View
    {
        $search = trim((string) $request->input('search'));
        $questionType = $request->input('question_type');
        $status = $request->input('status');

        $learningQuiz->load([
            'topic:id,module_id,name',
            'topic.module:id,program_stage_id,name',
            'topic.module.stage:id,program_id,name',
            'topic.module.stage.program:id,name',
            'subTopic:id,topic_id,name',
        ]);

        $questions = LearningQuizQuestion::query()
            ->where('learning_quiz_id', $learningQuiz->id)
            ->with([
                'options' => function ($query) {
                    $query->orderBy('sort_order')->orderBy('id');
                },
            ])
            ->withCount('options')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('question_text', 'like', '%' . $search . '%')
                        ->orWhere('explanation', 'like', '%' . $search . '%')
                        ->orWhereHas('options', function ($optionQuery) use ($search) {
                            $optionQuery->where('option_text', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when($questionType, function ($query) use ($questionType) {
                $query->where('question_type', $questionType);
            })
            ->when($status !== null && $status !== '', function ($query) use ($status) {
                $query->where('is_active', (bool) $status);
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'questions' => LearningQuizQuestion::where('learning_quiz_id', $learningQuiz->id)->count(),
            'active_questions' => LearningQuizQuestion::where('learning_quiz_id', $learningQuiz->id)->where('is_active', true)->count(),
            'options' => LearningQuizOption::whereHas('question', function ($query) use ($learningQuiz) {
                $query->where('learning_quiz_id', $learningQuiz->id);
            })->count(),
            'total_score' => LearningQuizQuestion::where('learning_quiz_id', $learningQuiz->id)->sum('score'),
        ];

        return view('academic.learning-quizzes.questions.index', [
            'learningQuiz' => $learningQuiz,
            'questions' => $questions,
            'questionTypes' => $this->questionTypes(),
            'stats' => $stats,
        ]);
    }

    public function storeQuestion(Request $request, LearningQuiz $learningQuiz): JsonResponse
    {
        try {
            $validated = $this->validateQuestion($request);

            $question = DB::transaction(function () use ($learningQuiz, $validated) {
                $question = LearningQuizQuestion::create([
                    'learning_quiz_id' => $learningQuiz->id,
                    'question_text' => $validated['question_text'],
                    'question_type' => $validated['question_type'],
                    'explanation' => $validated['explanation'] ?? null,
                    'score' => $validated['score'] ?? 1,
                    'sort_order' => $validated['sort_order'] ?? 1,
                    'is_required' => (bool) ($validated['is_required'] ?? true),
                    'is_active' => (bool) ($validated['is_active'] ?? true),
                ]);

                if ($question->question_type === 'true_false') {
                    $this->syncTrueFalseOptions($question);
                }

                return $question;
            });

            return response()->json([
                'success' => true,
                'message' => 'Question berhasil ditambahkan.',
                'data' => [
                    'id' => $question->id,
                ],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->errorResponse('Gagal menambahkan question.', $e);
        }
    }

    public function updateQuestion(
        Request $request,
        LearningQuiz $learningQuiz,
        LearningQuizQuestion $question
    ): JsonResponse {
        $this->ensureQuestionBelongsToQuiz($learningQuiz, $question);

        try {
            $validated = $this->validateQuestion($request);

            DB::transaction(function () use ($question, $validated) {
                $oldType = $question->question_type;

                $question->update([
                    'question_text' => $validated['question_text'],
                    'question_type' => $validated['question_type'],
                    'explanation' => $validated['explanation'] ?? null,
                    'score' => $validated['score'] ?? $question->score ?? 1,
                    'sort_order' => $validated['sort_order'] ?? $question->sort_order ?? 1,
                    'is_required' => (bool) ($validated['is_required'] ?? true),
                    'is_active' => (bool) ($validated['is_active'] ?? true),
                ]);

                if ($question->question_type === 'true_false') {
                    $this->syncTrueFalseOptions($question);
                }

                if ($oldType !== 'short_answer' && $question->question_type === 'short_answer') {
                    $question->options()->delete();
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Question berhasil diperbarui.',
                'data' => [
                    'id' => $question->id,
                ],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->errorResponse('Gagal memperbarui question.', $e);
        }
    }

    public function destroyQuestion(
        LearningQuiz $learningQuiz,
        LearningQuizQuestion $question
    ): JsonResponse {
        $this->ensureQuestionBelongsToQuiz($learningQuiz, $question);

        try {
            DB::transaction(function () use ($question) {
                $question->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Question berhasil dihapus.',
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse('Gagal menghapus question.', $e);
        }
    }

    public function storeOption(
        Request $request,
        LearningQuiz $learningQuiz,
        LearningQuizQuestion $question
    ): JsonResponse {
        $this->ensureQuestionBelongsToQuiz($learningQuiz, $question);

        try {
            $this->ensureQuestionCanHaveOptions($question);

            $validated = $this->validateOption($request);

            $option = DB::transaction(function () use ($question, $validated) {
                if ($this->mustHaveSingleCorrectOption($question) && (bool) $validated['is_correct']) {
                    $question->options()->update([
                        'is_correct' => false,
                    ]);
                }

                return LearningQuizOption::create([
                    'learning_quiz_question_id' => $question->id,
                    'option_text' => $validated['option_text'],
                    'is_correct' => (bool) ($validated['is_correct'] ?? false),
                    'sort_order' => $validated['sort_order'] ?? 1,
                    'is_active' => (bool) ($validated['is_active'] ?? true),
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Option berhasil ditambahkan.',
                'data' => [
                    'id' => $option->id,
                ],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->errorResponse('Gagal menambahkan option.', $e);
        }
    }

    public function updateOption(
        Request $request,
        LearningQuiz $learningQuiz,
        LearningQuizQuestion $question,
        LearningQuizOption $option
    ): JsonResponse {
        $this->ensureQuestionBelongsToQuiz($learningQuiz, $question);
        $this->ensureOptionBelongsToQuestion($question, $option);

        try {
            $this->ensureQuestionCanHaveOptions($question);

            $validated = $this->validateOption($request);

            DB::transaction(function () use ($question, $option, $validated) {
                if ($this->mustHaveSingleCorrectOption($question) && (bool) $validated['is_correct']) {
                    $question->options()
                        ->where('id', '!=', $option->id)
                        ->update([
                            'is_correct' => false,
                        ]);
                }

                $option->update([
                    'option_text' => $validated['option_text'],
                    'is_correct' => (bool) ($validated['is_correct'] ?? false),
                    'sort_order' => $validated['sort_order'] ?? $option->sort_order ?? 1,
                    'is_active' => (bool) ($validated['is_active'] ?? true),
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Option berhasil diperbarui.',
                'data' => [
                    'id' => $option->id,
                ],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->errorResponse('Gagal memperbarui option.', $e);
        }
    }

    public function destroyOption(
        LearningQuiz $learningQuiz,
        LearningQuizQuestion $question,
        LearningQuizOption $option
    ): JsonResponse {
        $this->ensureQuestionBelongsToQuiz($learningQuiz, $question);
        $this->ensureOptionBelongsToQuestion($question, $option);

        try {
            DB::transaction(function () use ($option) {
                $option->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Option berhasil dihapus.',
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse('Gagal menghapus option.', $e);
        }
    }

    private function validateQuestion(Request $request): array
    {
        return $request->validate([
            'question_text' => [
                'required',
                'string',
            ],

            'question_type' => [
                'required',
                Rule::in(array_keys($this->questionTypes())),
            ],

            'explanation' => [
                'nullable',
                'string',
            ],

            'score' => [
                'required',
                'integer',
                'min:1',
                'max:999',
            ],

            'sort_order' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'is_required' => [
                'required',
                'boolean',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ]);
    }

    private function validateOption(Request $request): array
    {
        return $request->validate([
            'option_text' => [
                'required',
                'string',
            ],

            'is_correct' => [
                'required',
                'boolean',
            ],

            'sort_order' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ]);
    }

    private function questionTypes(): array
    {
        return [
            'single_choice' => 'Single Choice',
            'multiple_choice' => 'Multiple Choice',
            'true_false' => 'True / False',
            'short_answer' => 'Short Answer',
        ];
    }

    private function ensureQuestionBelongsToQuiz(LearningQuiz $learningQuiz, LearningQuizQuestion $question): void
    {
        abort_unless((int) $question->learning_quiz_id === (int) $learningQuiz->id, 404);
    }

    private function ensureOptionBelongsToQuestion(LearningQuizQuestion $question, LearningQuizOption $option): void
    {
        abort_unless((int) $option->learning_quiz_question_id === (int) $question->id, 404);
    }

    private function ensureQuestionCanHaveOptions(LearningQuizQuestion $question): void
    {
        if ($question->question_type === 'short_answer') {
            throw ValidationException::withMessages([
                'option_text' => ['Short answer tidak membutuhkan options.'],
            ]);
        }
    }

    private function mustHaveSingleCorrectOption(LearningQuizQuestion $question): bool
    {
        return in_array($question->question_type, ['single_choice', 'true_false'], true);
    }

    private function syncTrueFalseOptions(LearningQuizQuestion $question): void
    {
        $existingOptions = $question->options()->count();

        if ($existingOptions > 0) {
            return;
        }

        $question->options()->createMany([
            [
                'option_text' => 'True',
                'is_correct' => true,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'option_text' => 'False',
                'is_correct' => false,
                'sort_order' => 2,
                'is_active' => true,
            ],
        ]);
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