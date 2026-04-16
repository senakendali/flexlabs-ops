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
                                                    }
                                                ]);

                                            if ($search !== '') {
                                                $topicQuery->where(function ($q) use ($search) {
                                                    $q->where('name', 'like', '%' . $search . '%')
                                                        ->orWhereHas('subTopics', function ($subTopicQuery) use ($search) {
                                                            $subTopicQuery->where('name', 'like', '%' . $search . '%');
                                                        });
                                                });
                                            }
                                        }
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
                            }
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
                }
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
            'sub_topics' => SubTopic::count(),
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
            $validated = $request->validate([
                'program_id' => ['required', 'exists:programs,id'],
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'sort_order' => ['nullable', 'integer', 'min:1'],
                'is_active' => ['required', 'boolean'],
            ]);

            $stage = ProgramStage::create([
                'program_id' => $validated['program_id'],
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']),
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
            return response()->json([
                'message' => 'Gagal menambahkan stage.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateStage(Request $request, ProgramStage $stage): JsonResponse
    {
        try {
            $validated = $request->validate([
                'program_id' => ['required', 'exists:programs,id'],
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'sort_order' => ['nullable', 'integer', 'min:1'],
                'is_active' => ['required', 'boolean'],
            ]);

            $stage->update([
                'program_id' => $validated['program_id'],
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']),
                'description' => $validated['description'] ?? null,
                'sort_order' => $validated['sort_order'] ?? $stage->sort_order,
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
            return response()->json([
                'message' => 'Gagal memperbarui stage.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroyStage(ProgramStage $stage): JsonResponse
    {
        try {
            $stage->delete();

            return response()->json([
                'success' => true,
                'message' => 'Stage berhasil dihapus.',
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Gagal menghapus stage.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function storeModule(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'program_stage_id' => ['required', 'exists:program_stages,id'],
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'sort_order' => ['nullable', 'integer', 'min:1'],
                'is_active' => ['required', 'boolean'],
            ]);

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
            return response()->json([
                'message' => 'Gagal menambahkan module.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateModule(Request $request, Module $module): JsonResponse
    {
        try {
            $validated = $request->validate([
                'program_stage_id' => ['required', 'exists:program_stages,id'],
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'sort_order' => ['nullable', 'integer', 'min:1'],
                'is_active' => ['required', 'boolean'],
            ]);

            $module->update([
                'program_stage_id' => $validated['program_stage_id'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'sort_order' => $validated['sort_order'] ?? $module->sort_order,
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
            return response()->json([
                'message' => 'Gagal memperbarui module.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroyModule(Module $module): JsonResponse
    {
        try {
            $module->delete();

            return response()->json([
                'success' => true,
                'message' => 'Module berhasil dihapus.',
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Gagal menghapus module.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function storeTopic(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'module_id' => ['required', 'exists:modules,id'],
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'sort_order' => ['nullable', 'integer', 'min:1'],
                'is_active' => ['required', 'boolean'],
            ]);

            $topic = Topic::create([
                'module_id' => $validated['module_id'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'sort_order' => $validated['sort_order'] ?? 1,
                'is_active' => (bool) $validated['is_active'],
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
            return response()->json([
                'message' => 'Gagal menambahkan topic.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateTopic(Request $request, Topic $topic): JsonResponse
    {
        try {
            $validated = $request->validate([
                'module_id' => ['required', 'exists:modules,id'],
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'sort_order' => ['nullable', 'integer', 'min:1'],
                'is_active' => ['required', 'boolean'],
            ]);

            $topic->update([
                'module_id' => $validated['module_id'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'sort_order' => $validated['sort_order'] ?? $topic->sort_order,
                'is_active' => (bool) $validated['is_active'],
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
            return response()->json([
                'message' => 'Gagal memperbarui topic.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroyTopic(Topic $topic): JsonResponse
    {
        try {
            $topic->delete();

            return response()->json([
                'success' => true,
                'message' => 'Topic berhasil dihapus.',
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Gagal menghapus topic.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function storeSubTopic(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'topic_id' => ['required', 'exists:topics,id'],
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'sort_order' => ['nullable', 'integer', 'min:1'],
                'is_active' => ['required', 'boolean'],
            ]);

            $subTopic = SubTopic::create([
                'topic_id' => $validated['topic_id'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'sort_order' => $validated['sort_order'] ?? 1,
                'is_active' => (bool) $validated['is_active'],
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
            return response()->json([
                'message' => 'Gagal menambahkan sub topic.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateSubTopic(Request $request, SubTopic $subTopic): JsonResponse
    {
        try {
            $validated = $request->validate([
                'topic_id' => ['required', 'exists:topics,id'],
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'sort_order' => ['nullable', 'integer', 'min:1'],
                'is_active' => ['required', 'boolean'],
            ]);

            $subTopic->update([
                'topic_id' => $validated['topic_id'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'sort_order' => $validated['sort_order'] ?? $subTopic->sort_order,
                'is_active' => (bool) $validated['is_active'],
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
            return response()->json([
                'message' => 'Gagal memperbarui sub topic.',
                'error' => $e->getMessage(),
            ], 500);
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
            return response()->json([
                'message' => 'Gagal menghapus sub topic.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}