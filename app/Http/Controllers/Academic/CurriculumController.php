<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\ProgramStage;
use App\Models\Module;
use App\Models\Topic;
use App\Models\SubTopic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class CurriculumController extends Controller
{
    public function index(Request $request): View
    {
        $programId = $request->input('program_id');
        $search = trim((string) $request->input('search'));

        $programs = Program::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $curriculumPrograms = Program::query()
            ->when($programId, function ($query) use ($programId) {
                $query->where('id', $programId);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                        ->orWhereHas('stages', function ($stageQuery) use ($search) {
                            $stageQuery->where('name', 'like', '%' . $search . '%')
                                ->orWhereHas('modules', function ($moduleQuery) use ($search) {
                                    $moduleQuery->where('name', 'like', '%' . $search . '%')
                                        ->orWhereHas('topics', function ($topicQuery) use ($search) {
                                            $topicQuery->where('name', 'like', '%' . $search . '%')
                                                ->orWhereHas('subTopics', function ($subTopicQuery) use ($search) {
                                                    $subTopicQuery->where('name', 'like', '%' . $search . '%');
                                                });
                                        });
                                });
                        });
                });
            })
            ->with([
                'stages' => function ($stageQuery) use ($search) {
                    $stageQuery->orderBy('sort_order')
                        ->orderBy('id')
                        ->with([
                            'modules' => function ($moduleQuery) use ($search) {
                                $moduleQuery->orderBy('sort_order')
                                    ->orderBy('id')
                                    ->with([
                                        'topics' => function ($topicQuery) use ($search) {
                                            $topicQuery->orderBy('sort_order')
                                                ->orderBy('id')
                                                ->with([
                                                    'subTopics' => function ($subTopicQuery) use ($search) {
                                                        $subTopicQuery->orderBy('sort_order')
                                                            ->orderBy('id');

                                                        if ($search !== '') {
                                                            $subTopicQuery->where('name', 'like', '%' . $search . '%');
                                                        }
                                                    },
                                                ]);

                                            if ($search !== '') {
                                                $topicQuery->where(function ($q) use ($search) {
                                                    $q->where('name', 'like', '%' . $search . '%')
                                                        ->orWhereHas('subTopics', function ($subTopicQuery) use ($search) {
                                                            $subTopicQuery->where('name', 'like', '%' . $search . '%');
                                                        });
                                                });
                                            }
                                        },
                                    ]);

                                if ($search !== '') {
                                    $moduleQuery->where(function ($q) use ($search) {
                                        $q->where('name', 'like', '%' . $search . '%')
                                            ->orWhereHas('topics', function ($topicQuery) use ($search) {
                                                $topicQuery->where('name', 'like', '%' . $search . '%')
                                                    ->orWhereHas('subTopics', function ($subTopicQuery) use ($search) {
                                                        $subTopicQuery->where('name', 'like', '%' . $search . '%');
                                                    });
                                            });
                                    });
                                }
                            },
                        ]);

                    if ($search !== '') {
                        $stageQuery->where(function ($q) use ($search) {
                            $q->where('name', 'like', '%' . $search . '%')
                                ->orWhereHas('modules', function ($moduleQuery) use ($search) {
                                    $moduleQuery->where('name', 'like', '%' . $search . '%')
                                        ->orWhereHas('topics', function ($topicQuery) use ($search) {
                                            $topicQuery->where('name', 'like', '%' . $search . '%')
                                                ->orWhereHas('subTopics', function ($subTopicQuery) use ($search) {
                                                    $subTopicQuery->where('name', 'like', '%' . $search . '%');
                                                });
                                        });
                                });
                        });
                    }
                },
            ])
            ->orderBy('name')
            ->get();

        $allStages = ProgramStage::query()
            ->with('program:id,name')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $allModules = Module::query()
            ->with([
                'stage:id,program_id,name',
                'stage.program:id,name',
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $allTopics = Topic::query()
            ->with([
                'module:id,program_stage_id,name',
                'module.stage:id,name',
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $stats = [
            'programs' => Program::count(),
            'stages' => ProgramStage::count(),
            'modules' => Module::count(),
            'topics' => Topic::count(),
        ];

        $curriculumPrograms->each(function ($program) {
            $modulesCount = 0;
            $topicsCount = 0;
            $subTopicsCount = 0;

            foreach ($program->stages as $stage) {
                $stageModulesCount = $stage->modules->count();
                $stage->modules_count = $stageModulesCount;
                $modulesCount += $stageModulesCount;

                foreach ($stage->modules as $module) {
                    $moduleTopicsCount = $module->topics->count();
                    $module->topics_count = $moduleTopicsCount;
                    $topicsCount += $moduleTopicsCount;

                    foreach ($module->topics as $topic) {
                        $topicSubTopicsCount = $topic->subTopics->count();
                        $topic->sub_topics_count = $topicSubTopicsCount;
                        $subTopicsCount += $topicSubTopicsCount;
                    }
                }
            }

            $program->modules_count = $modulesCount;
            $program->topics_count = $topicsCount;
            $program->sub_topics_count = $subTopicsCount;
        });

        return view('academic.curriculum.index', [
            'programs' => $programs,
            'curriculumPrograms' => $curriculumPrograms,
            'allStages' => $allStages,
            'allModules' => $allModules,
            'allTopics' => $allTopics,
            'stats' => $stats,
        ]);
    }

    public function storeStage(Request $request): JsonResponse
    {
        try {
            $validated = $this->validateStage($request);

            $stage = ProgramStage::create([
                'program_id' => $validated['program_id'],
                'name' => $validated['name'],
                'slug' => $this->generateUniqueStageSlug($validated['name']),
                'description' => $validated['description'] ?? null,
                'sort_order' => $validated['sort_order'] ?? 1,
                'is_active' => (bool) $validated['is_active'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Stage berhasil ditambahkan.',
                'data' => [
                    'id' => $stage->id,
                    'name' => $stage->name,
                ],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->errorResponse('Gagal menambahkan stage.', $e);
        }
    }

    public function updateStage(Request $request, ProgramStage $stage): JsonResponse
    {
        try {
            $validated = $this->validateStage($request);

            $stage->update([
                'program_id' => $validated['program_id'],
                'name' => $validated['name'],
                'slug' => $this->generateUniqueStageSlug($validated['name'], $stage->id),
                'description' => $validated['description'] ?? null,
                'sort_order' => $validated['sort_order'] ?? $stage->sort_order ?? 1,
                'is_active' => (bool) $validated['is_active'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Stage berhasil diperbarui.',
                'data' => [
                    'id' => $stage->id,
                    'name' => $stage->name,
                ],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->errorResponse('Gagal memperbarui stage.', $e);
        }
    }

    public function destroyStage(ProgramStage $stage): JsonResponse
    {
        try {
            DB::transaction(function () use ($stage) {
                $stage->load('modules.topics.subTopics');

                foreach ($stage->modules as $module) {
                    $this->deleteModuleTree($module);
                }

                $stage->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Stage berhasil dihapus.',
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse('Gagal menghapus stage.', $e);
        }
    }

    public function storeModule(Request $request): JsonResponse
    {
        try {
            $validated = $this->validateModule($request);

            $module = Module::create([
                'program_stage_id' => $validated['program_stage_id'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'sort_order' => $validated['sort_order'] ?? 1,
                'is_active' => (bool) $validated['is_active'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Module berhasil ditambahkan.',
                'data' => [
                    'id' => $module->id,
                    'name' => $module->name,
                ],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->errorResponse('Gagal menambahkan module.', $e);
        }
    }

    public function updateModule(Request $request, Module $module): JsonResponse
    {
        try {
            $validated = $this->validateModule($request);

            $module->update([
                'program_stage_id' => $validated['program_stage_id'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'sort_order' => $validated['sort_order'] ?? $module->sort_order ?? 1,
                'is_active' => (bool) $validated['is_active'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Module berhasil diperbarui.',
                'data' => [
                    'id' => $module->id,
                    'name' => $module->name,
                ],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->errorResponse('Gagal memperbarui module.', $e);
        }
    }

    public function destroyModule(Module $module): JsonResponse
    {
        try {
            DB::transaction(function () use ($module) {
                $module->load('topics.subTopics');
                $this->deleteModuleTree($module);
            });

            return response()->json([
                'success' => true,
                'message' => 'Module berhasil dihapus.',
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse('Gagal menghapus module.', $e);
        }
    }

    public function storeTopic(Request $request): JsonResponse
    {
        try {
            $validated = $this->validateTopic($request);

            $topic = Topic::create([
                'module_id' => $validated['module_id'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'sort_order' => $validated['sort_order'] ?? 1,
                'is_active' => (bool) $validated['is_active'],

                'slide_url' => $validated['slide_url'] ?? null,
                'starter_code_url' => $validated['starter_code_url'] ?? null,
                'supporting_file_url' => $validated['supporting_file_url'] ?? null,
                'external_reference_url' => $validated['external_reference_url'] ?? null,
                'practice_brief' => $validated['practice_brief'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Topic berhasil ditambahkan.',
                'data' => [
                    'id' => $topic->id,
                    'name' => $topic->name,
                ],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->errorResponse('Gagal menambahkan topic.', $e);
        }
    }

    public function updateTopic(Request $request, Topic $topic): JsonResponse
    {
        try {
            $validated = $this->validateTopic($request);

            $topic->update([
                'module_id' => $validated['module_id'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'sort_order' => $validated['sort_order'] ?? $topic->sort_order ?? 1,
                'is_active' => (bool) $validated['is_active'],

                'slide_url' => $validated['slide_url'] ?? null,
                'starter_code_url' => $validated['starter_code_url'] ?? null,
                'supporting_file_url' => $validated['supporting_file_url'] ?? null,
                'external_reference_url' => $validated['external_reference_url'] ?? null,
                'practice_brief' => $validated['practice_brief'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Topic berhasil diperbarui.',
                'data' => [
                    'id' => $topic->id,
                    'name' => $topic->name,
                ],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->errorResponse('Gagal memperbarui topic.', $e);
        }
    }

    public function destroyTopic(Topic $topic): JsonResponse
    {
        try {
            DB::transaction(function () use ($topic) {
                $topic->load('subTopics');
                $this->deleteTopicTree($topic);
            });

            return response()->json([
                'success' => true,
                'message' => 'Topic berhasil dihapus.',
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse('Gagal menghapus topic.', $e);
        }
    }

    public function storeSubTopic(Request $request): JsonResponse
    {
        try {
            $validated = $this->validateSubTopic($request);

            $lessonData = $this->normalizeSubTopicLessonData($validated);

            $subTopic = SubTopic::create([
                'topic_id' => $validated['topic_id'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'sort_order' => $validated['sort_order'] ?? 1,
                'is_active' => (bool) $validated['is_active'],

                'lesson_type' => $lessonData['lesson_type'],
                'video_url' => $lessonData['video_url'],
                'video_duration_minutes' => $lessonData['video_duration_minutes'],
                'thumbnail_url' => $lessonData['thumbnail_url'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sub topic berhasil ditambahkan.',
                'data' => [
                    'id' => $subTopic->id,
                    'name' => $subTopic->name,
                ],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->errorResponse('Gagal menambahkan sub topic.', $e);
        }
    }

    public function updateSubTopic(Request $request, SubTopic $subTopic): JsonResponse
    {
        try {
            $validated = $this->validateSubTopic($request);

            $lessonData = $this->normalizeSubTopicLessonData($validated);

            $subTopic->update([
                'topic_id' => $validated['topic_id'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'sort_order' => $validated['sort_order'] ?? $subTopic->sort_order ?? 1,
                'is_active' => (bool) $validated['is_active'],

                'lesson_type' => $lessonData['lesson_type'],
                'video_url' => $lessonData['video_url'],
                'video_duration_minutes' => $lessonData['video_duration_minutes'],
                'thumbnail_url' => $lessonData['thumbnail_url'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sub topic berhasil diperbarui.',
                'data' => [
                    'id' => $subTopic->id,
                    'name' => $subTopic->name,
                ],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->errorResponse('Gagal memperbarui sub topic.', $e);
        }
    }

    public function destroySubTopic(SubTopic $subTopic): JsonResponse
    {
        try {
            $subTopic->delete();

            return response()->json([
                'success' => true,
                'message' => 'Sub topic berhasil dihapus.',
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse('Gagal menghapus sub topic.', $e);
        }
    }

    private function validateStage(Request $request): array
    {
        return $request->validate([
            'program_id' => ['required', 'exists:programs,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['required', 'boolean'],
        ]);
    }

    private function validateModule(Request $request): array
    {
        return $request->validate([
            'program_stage_id' => ['required', 'exists:program_stages,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['required', 'boolean'],
        ]);
    }

    private function validateTopic(Request $request): array
    {
        return $request->validate([
            'module_id' => ['required', 'exists:modules,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['required', 'boolean'],

            'slide_url' => ['nullable', 'url'],
            'starter_code_url' => ['nullable', 'url'],
            'supporting_file_url' => ['nullable', 'url'],
            'external_reference_url' => ['nullable', 'url'],
            'practice_brief' => ['nullable', 'string'],
        ]);
    }

    private function validateSubTopic(Request $request): array
    {
        return $request->validate([
            'topic_id' => ['required', 'exists:topics,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['required', 'boolean'],

            'lesson_type' => ['nullable', 'string', 'in:video,live_session'],
            'video_url' => ['nullable', 'url'],
            'video_duration_minutes' => ['nullable', 'integer', 'min:1', 'max:999'],
            'thumbnail_url' => ['nullable', 'url'],
        ]);
    }

    private function normalizeSubTopicLessonData(array $validated): array
    {
        $lessonType = $validated['lesson_type'] ?? 'video';

        if ($lessonType === 'live_session') {
            return [
                'lesson_type' => 'live_session',
                'video_url' => null,
                'video_duration_minutes' => null,
                'thumbnail_url' => null,
            ];
        }

        return [
            'lesson_type' => 'video',
            'video_url' => $validated['video_url'] ?? null,
            'video_duration_minutes' => $validated['video_duration_minutes'] ?? null,
            'thumbnail_url' => $validated['thumbnail_url'] ?? null,
        ];
    }

    private function deleteModuleTree(Module $module): void
    {
        $module->loadMissing('topics.subTopics');

        foreach ($module->topics as $topic) {
            $this->deleteTopicTree($topic);
        }

        $module->delete();
    }

    private function deleteTopicTree(Topic $topic): void
    {
        $topic->loadMissing('subTopics');

        foreach ($topic->subTopics as $subTopic) {
            $subTopic->delete();
        }

        $topic->delete();
    }

    private function errorResponse(string $message, Throwable $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => config('app.debug') ? $e->getMessage() : null,
        ], 500);
    }

    private function generateUniqueStageSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug !== '' ? $baseSlug : 'stage';

        $counter = 1;

        while (
            ProgramStage::query()
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}