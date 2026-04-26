<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\LearningQuiz;
use App\Models\SubTopic;
use App\Models\Topic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class LearningQuizController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $topicId = $request->input('topic_id');
        $subTopicId = $request->input('sub_topic_id');
        $quizType = $request->input('quiz_type');
        $status = $request->input('status');

        $learningQuizzes = LearningQuiz::query()
            ->with([
                'topic:id,module_id,name',
                'topic.module:id,program_stage_id,name',
                'topic.module.stage:id,program_id,name',
                'topic.module.stage.program:id,name',
                'subTopic:id,topic_id,name',
                'createdBy:id,name',
                'updatedBy:id,name',
            ])
            ->withCount([
                'questions',
                'batchLearningQuizzes',
                'attempts',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', '%' . $search . '%')
                        ->orWhere('instruction', 'like', '%' . $search . '%')
                        ->orWhereHas('topic', function ($topicQuery) use ($search) {
                            $topicQuery->where('name', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('subTopic', function ($subTopicQuery) use ($search) {
                            $subTopicQuery->where('name', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when($topicId, function ($query) use ($topicId) {
                $query->where('topic_id', $topicId);
            })
            ->when($subTopicId, function ($query) use ($subTopicId) {
                $query->where('sub_topic_id', $subTopicId);
            })
            ->when($quizType, function ($query) use ($quizType) {
                $query->where('quiz_type', $quizType);
            })
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $topics = Topic::query()
            ->with([
                'module:id,program_stage_id,name',
                'module.stage:id,program_id,name',
                'module.stage.program:id,name',
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $subTopics = SubTopic::query()
            ->with([
                'topic:id,module_id,name',
                'topic.module:id,program_stage_id,name',
                'topic.module.stage:id,program_id,name',
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $stats = [
            'total' => LearningQuiz::count(),
            'published' => LearningQuiz::where('status', 'published')->count(),
            'draft' => LearningQuiz::where('status', 'draft')->count(),
            'archived' => LearningQuiz::where('status', 'archived')->count(),
            'active' => LearningQuiz::where('is_active', true)->count(),
        ];

        return view('academic.learning-quizzes.index', [
            'learningQuizzes' => $learningQuizzes,
            'topics' => $topics,
            'subTopics' => $subTopics,
            'quizTypes' => $this->quizTypes(),
            'statuses' => $this->statuses(),
            'stats' => $stats,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $this->validateLearningQuiz($request);
            $validated = $this->normalizeLearningQuizTarget($validated);

            $learningQuiz = DB::transaction(function () use ($validated) {
                return LearningQuiz::create([
                    'topic_id' => $validated['topic_id'],
                    'sub_topic_id' => $validated['sub_topic_id'] ?? null,

                    'title' => $validated['title'],
                    'slug' => $this->generateUniqueSlug($validated['title']),

                    'instruction' => $validated['instruction'] ?? null,
                    'quiz_type' => $validated['quiz_type'] ?? 'graded',

                    'duration_minutes' => $validated['duration_minutes'] ?? null,
                    'passing_score' => $validated['passing_score'] ?? 70,
                    'max_attempts' => $validated['max_attempts'] ?? 1,

                    'randomize_questions' => (bool) ($validated['randomize_questions'] ?? false),
                    'randomize_options' => (bool) ($validated['randomize_options'] ?? false),

                    'show_result_after_submit' => (bool) ($validated['show_result_after_submit'] ?? true),
                    'show_correct_answer_after_submit' => (bool) ($validated['show_correct_answer_after_submit'] ?? false),

                    'status' => $validated['status'] ?? 'draft',
                    'is_active' => (bool) ($validated['is_active'] ?? true),

                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Learning quiz berhasil ditambahkan.',
                'data' => [
                    'id' => $learningQuiz->id,
                    'title' => $learningQuiz->title,
                ],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->errorResponse('Gagal menambahkan learning quiz.', $e);
        }
    }

    public function update(Request $request, LearningQuiz $learningQuiz): JsonResponse
    {
        try {
            $validated = $this->validateLearningQuiz($request);
            $validated = $this->normalizeLearningQuizTarget($validated);

            DB::transaction(function () use ($learningQuiz, $validated) {
                $learningQuiz->update([
                    'topic_id' => $validated['topic_id'],
                    'sub_topic_id' => $validated['sub_topic_id'] ?? null,

                    'title' => $validated['title'],
                    'slug' => $this->generateUniqueSlug($validated['title'], $learningQuiz->id),

                    'instruction' => $validated['instruction'] ?? null,
                    'quiz_type' => $validated['quiz_type'] ?? $learningQuiz->quiz_type ?? 'graded',

                    'duration_minutes' => $validated['duration_minutes'] ?? null,
                    'passing_score' => $validated['passing_score'] ?? $learningQuiz->passing_score ?? 70,
                    'max_attempts' => $validated['max_attempts'] ?? $learningQuiz->max_attempts ?? 1,

                    'randomize_questions' => (bool) ($validated['randomize_questions'] ?? false),
                    'randomize_options' => (bool) ($validated['randomize_options'] ?? false),

                    'show_result_after_submit' => (bool) ($validated['show_result_after_submit'] ?? true),
                    'show_correct_answer_after_submit' => (bool) ($validated['show_correct_answer_after_submit'] ?? false),

                    'status' => $validated['status'] ?? $learningQuiz->status ?? 'draft',
                    'is_active' => (bool) ($validated['is_active'] ?? true),

                    'updated_by' => auth()->id(),
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Learning quiz berhasil diperbarui.',
                'data' => [
                    'id' => $learningQuiz->id,
                    'title' => $learningQuiz->fresh()->title,
                ],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->errorResponse('Gagal memperbarui learning quiz.', $e);
        }
    }

    public function destroy(LearningQuiz $learningQuiz): JsonResponse
    {
        try {
            DB::transaction(function () use ($learningQuiz) {
                $learningQuiz->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Learning quiz berhasil dihapus.',
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse('Gagal menghapus learning quiz.', $e);
        }
    }

    private function validateLearningQuiz(Request $request): array
    {
        return $request->validate([
            'topic_id' => [
                'nullable',
                'exists:topics,id',
            ],

            'sub_topic_id' => [
                'nullable',
                'exists:sub_topics,id',
            ],

            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'instruction' => [
                'nullable',
                'string',
            ],

            'quiz_type' => [
                'required',
                Rule::in(array_keys($this->quizTypes())),
            ],

            'duration_minutes' => [
                'nullable',
                'integer',
                'min:1',
                'max:9999',
            ],

            'passing_score' => [
                'required',
                'integer',
                'min:0',
                'max:100',
            ],

            'max_attempts' => [
                'required',
                'integer',
                'min:1',
                'max:99',
            ],

            'randomize_questions' => [
                'required',
                'boolean',
            ],

            'randomize_options' => [
                'required',
                'boolean',
            ],

            'show_result_after_submit' => [
                'required',
                'boolean',
            ],

            'show_correct_answer_after_submit' => [
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
        ]);
    }

    private function normalizeLearningQuizTarget(array $validated): array
    {
        $topicId = $validated['topic_id'] ?? null;
        $subTopicId = $validated['sub_topic_id'] ?? null;

        if (!$topicId && !$subTopicId) {
            throw ValidationException::withMessages([
                'topic_id' => ['Pilih topic atau sub topic untuk learning quiz ini.'],
            ]);
        }

        if ($subTopicId) {
            $subTopic = SubTopic::query()
                ->select(['id', 'topic_id'])
                ->findOrFail($subTopicId);

            if ($topicId && (int) $topicId !== (int) $subTopic->topic_id) {
                throw ValidationException::withMessages([
                    'sub_topic_id' => ['Sub topic yang dipilih tidak sesuai dengan topic.'],
                ]);
            }

            $validated['topic_id'] = $subTopic->topic_id;
        }

        return $validated;
    }

    private function quizTypes(): array
    {
        return [
            'practice' => 'Practice',
            'graded' => 'Graded',
        ];
    }

    private function statuses(): array
    {
        return [
            'draft' => 'Draft',
            'published' => 'Published',
            'archived' => 'Archived',
        ];
    }

    private function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'learning-quiz';

        $slug = $baseSlug;
        $counter = 1;

        while (
            LearningQuiz::query()
                ->when($ignoreId, function ($query) use ($ignoreId) {
                    $query->where('id', '!=', $ignoreId);
                })
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
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