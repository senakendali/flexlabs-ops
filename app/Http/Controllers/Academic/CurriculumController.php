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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class CurriculumController extends Controller
{
    private const VIDEO_DISK = 'private';
    private const VIDEO_DIRECTORY = 'learning-videos/sub-topics';
    private const VIDEO_UPLOAD_DIRECTORY = 'learning-videos/sub-topics/uploads';
    private const VIDEO_EXTENSIONS = ['mp4', 'webm', 'mov', 'm4v'];

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
                                                    $subTopicQuery->where(function ($subTopicNameQuery) use ($search) {
                                                        $subTopicNameQuery->where('name', 'like', '%' . $search . '%')
                                                            ->orWhere('description', 'like', '%' . $search . '%')
                                                            ->orWhere('content', 'like', '%' . $search . '%');
                                                    });
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
                                                            $subTopicQuery->where(function ($subTopicNameQuery) use ($search) {
                                                        $subTopicNameQuery->where('name', 'like', '%' . $search . '%')
                                                            ->orWhere('description', 'like', '%' . $search . '%')
                                                            ->orWhere('content', 'like', '%' . $search . '%');
                                                    });
                                                        }
                                                    },
                                                ]);

                                            if ($search !== '') {
                                                $topicQuery->where(function ($q) use ($search) {
                                                    $q->where('name', 'like', '%' . $search . '%')
                                                        ->orWhereHas('subTopics', function ($subTopicQuery) use ($search) {
                                                            $subTopicQuery->where(function ($subTopicNameQuery) use ($search) {
                                                        $subTopicNameQuery->where('name', 'like', '%' . $search . '%')
                                                            ->orWhere('description', 'like', '%' . $search . '%')
                                                            ->orWhere('content', 'like', '%' . $search . '%');
                                                    });
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
                                                        $subTopicQuery->where(function ($subTopicNameQuery) use ($search) {
                                                        $subTopicNameQuery->where('name', 'like', '%' . $search . '%')
                                                            ->orWhere('description', 'like', '%' . $search . '%')
                                                            ->orWhere('content', 'like', '%' . $search . '%');
                                                    });
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
                                                    $subTopicQuery->where(function ($subTopicNameQuery) use ($search) {
                                                        $subTopicNameQuery->where('name', 'like', '%' . $search . '%')
                                                            ->orWhere('description', 'like', '%' . $search . '%')
                                                            ->orWhere('content', 'like', '%' . $search . '%');
                                                    });
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

    public function serverVideos(): JsonResponse
    {
        try {
            $disk = self::VIDEO_DISK;
            $directory = self::VIDEO_DIRECTORY;

            if (! Storage::disk($disk)->exists($directory)) {
                Storage::disk($disk)->makeDirectory($directory);
            }

            $files = collect(Storage::disk($disk)->allFiles($directory))
                ->filter(fn (string $path) => $this->isSupportedVideoPath($path))
                ->map(function (string $path) use ($disk) {
                    $size = Storage::disk($disk)->size($path);
                    $lastModified = Storage::disk($disk)->lastModified($path);

                    return [
                        'name' => basename($path),
                        'path' => $path,
                        'size' => $size,
                        'size_label' => $this->formatFileSize((int) $size),
                        'mime' => $this->guessVideoMime($path),
                        'last_modified' => date('Y-m-d H:i', $lastModified),
                        'last_modified_timestamp' => $lastModified,
                    ];
                })
                ->sortByDesc('last_modified_timestamp')
                ->values()
                ->map(function (array $file) {
                    unset($file['last_modified_timestamp']);

                    return $file;
                })
                ->all();

            return response()->json([
                'success' => true,
                'data' => $files,
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse('Gagal mengambil daftar video dari server.', $e);
        }
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

            return $this->successResponse(
                'Stage berhasil ditambahkan.',
                [
                    'id' => $stage->id,
                    'name' => $stage->name,
                ],
                $this->stageFocusPayload($stage)
            );
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

            $stage->refresh();

            return $this->successResponse(
                'Stage berhasil diperbarui.',
                [
                    'id' => $stage->id,
                    'name' => $stage->name,
                ],
                $this->stageFocusPayload($stage)
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->errorResponse('Gagal memperbarui stage.', $e);
        }
    }


    public function destroyStage(ProgramStage $stage): JsonResponse
    {
        try {
            $focus = [
                'type' => 'program',
                'program_id' => (int) $stage->program_id,
            ];

            DB::transaction(function () use ($stage) {
                $stage->load('modules.topics.subTopics');

                foreach ($stage->modules as $module) {
                    $this->deleteModuleTree($module);
                }

                $stage->delete();
            });

            return $this->successResponse(
                'Stage berhasil dihapus.',
                [],
                $focus
            );
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

            return $this->successResponse(
                'Module berhasil ditambahkan.',
                [
                    'id' => $module->id,
                    'name' => $module->name,
                ],
                $this->moduleFocusPayload($module)
            );
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

            $module->refresh();

            return $this->successResponse(
                'Module berhasil diperbarui.',
                [
                    'id' => $module->id,
                    'name' => $module->name,
                ],
                $this->moduleFocusPayload($module)
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->errorResponse('Gagal memperbarui module.', $e);
        }
    }


    public function destroyModule(Module $module): JsonResponse
    {
        try {
            $focus = [
                'type' => 'stage',
                'stage_id' => (int) $module->program_stage_id,
            ];

            DB::transaction(function () use ($module) {
                $module->load('topics.subTopics');
                $this->deleteModuleTree($module);
            });

            return $this->successResponse(
                'Module berhasil dihapus.',
                [],
                $focus
            );
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

            $topic->load('module:id,program_stage_id');

            return $this->successResponse(
                'Topic berhasil ditambahkan.',
                [
                    'id' => $topic->id,
                    'name' => $topic->name,
                ],
                $this->topicFocusPayload($topic)
            );
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

            $topic->refresh()->load('module:id,program_stage_id');

            return $this->successResponse(
                'Topic berhasil diperbarui.',
                [
                    'id' => $topic->id,
                    'name' => $topic->name,
                ],
                $this->topicFocusPayload($topic)
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->errorResponse('Gagal memperbarui topic.', $e);
        }
    }


    public function destroyTopic(Topic $topic): JsonResponse
    {
        try {
            $topic->load('module:id,program_stage_id');

            $focus = [
                'type' => 'module',
                'stage_id' => (int) ($topic->module->program_stage_id ?? 0),
                'module_id' => (int) $topic->module_id,
                'collapse_id' => 'moduleCollapse' . $topic->module_id,
            ];

            DB::transaction(function () use ($topic) {
                $topic->load('subTopics');
                $this->deleteTopicTree($topic);
            });

            return $this->successResponse(
                'Topic berhasil dihapus.',
                [],
                $focus
            );
        } catch (Throwable $e) {
            return $this->errorResponse('Gagal menghapus topic.', $e);
        }
    }


    public function storeSubTopic(Request $request): JsonResponse
    {
        try {
            $validated = $this->validateSubTopic($request);

            $lessonData = $this->normalizeSubTopicLessonData($request, $validated);

            $subTopic = SubTopic::create([
                'topic_id' => $validated['topic_id'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'content' => $validated['content'] ?? null,
                'content_format' => $validated['content_format'] ?? 'markdown',
                'sort_order' => $validated['sort_order'] ?? 1,
                'is_active' => (bool) $validated['is_active'],

                'lesson_type' => $lessonData['lesson_type'],
                'video_provider' => $lessonData['video_provider'],
                'video_url' => $lessonData['video_url'],
                'video_disk' => $lessonData['video_disk'],
                'video_path' => $lessonData['video_path'],
                'video_mime' => $lessonData['video_mime'],
                'video_size' => $lessonData['video_size'],
                'video_duration_minutes' => $lessonData['video_duration_minutes'],
                'video_duration_seconds' => $lessonData['video_duration_seconds'],
                'thumbnail_url' => $lessonData['thumbnail_url'],
            ]);

            $subTopic->load([
                'topic:id,module_id',
                'topic.module:id,program_stage_id',
            ]);

            return $this->successResponse(
                'Sub topic berhasil ditambahkan.',
                [
                    'id' => $subTopic->id,
                    'name' => $subTopic->name,
                ],
                $this->subTopicFocusPayload($subTopic)
            );
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

            $lessonData = $this->normalizeSubTopicLessonData($request, $validated, $subTopic);

            $subTopic->update([
                'topic_id' => $validated['topic_id'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'content' => $validated['content'] ?? null,
                'content_format' => $validated['content_format'] ?? 'markdown',
                'sort_order' => $validated['sort_order'] ?? $subTopic->sort_order ?? 1,
                'is_active' => (bool) $validated['is_active'],

                'lesson_type' => $lessonData['lesson_type'],
                'video_provider' => $lessonData['video_provider'],
                'video_url' => $lessonData['video_url'],
                'video_disk' => $lessonData['video_disk'],
                'video_path' => $lessonData['video_path'],
                'video_mime' => $lessonData['video_mime'],
                'video_size' => $lessonData['video_size'],
                'video_duration_minutes' => $lessonData['video_duration_minutes'],
                'video_duration_seconds' => $lessonData['video_duration_seconds'],
                'thumbnail_url' => $lessonData['thumbnail_url'],
            ]);

            $subTopic->refresh()->load([
                'topic:id,module_id',
                'topic.module:id,program_stage_id',
            ]);

            return $this->successResponse(
                'Sub topic berhasil diperbarui.',
                [
                    'id' => $subTopic->id,
                    'name' => $subTopic->name,
                ],
                $this->subTopicFocusPayload($subTopic)
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->errorResponse('Gagal memperbarui sub topic.', $e);
        }
    }


    public function destroySubTopic(SubTopic $subTopic): JsonResponse
    {
        try {
            $subTopic->load([
                'topic:id,module_id',
                'topic.module:id,program_stage_id',
            ]);

            $focus = [
                'type' => 'topic',
                'stage_id' => (int) ($subTopic->topic?->module?->program_stage_id ?? 0),
                'module_id' => (int) ($subTopic->topic?->module_id ?? 0),
                'topic_id' => (int) $subTopic->topic_id,
                'collapse_id' => 'moduleCollapse' . ($subTopic->topic?->module_id ?? 0),
            ];

            $this->deleteOwnedSubTopicVideo($subTopic);

            $subTopic->delete();

            return $this->successResponse(
                'Sub topic berhasil dihapus.',
                [],
                $focus
            );
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
            'content' => ['nullable', 'string'],
            'content_format' => ['nullable', 'string', 'in:markdown'],
            'sort_order' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['required', 'boolean'],

            'lesson_type' => ['nullable', 'string', 'in:video,live_session'],

            // Video source support.
            'video_provider' => ['nullable', 'string', 'in:youtube,bunny,self_hosted,server,upload'],
            'video_source' => ['nullable', 'string', 'in:youtube,bunny,self_hosted,server,upload'],
            'video_url' => ['nullable', 'url', 'max:2048'],

            // Get from server support.
            'server_video_path' => ['nullable', 'string', 'max:5000'],
            'video_path' => ['nullable', 'string', 'max:5000'],

            // Direct upload support. 1GB = 1,048,576 KB.
            'video_file' => [
                'nullable',
                'file',
                'mimetypes:video/mp4,video/webm,video/quicktime,video/x-m4v',
                'max:1048576',
            ],
            'clear_video_file' => ['nullable', 'boolean'],

            'video_duration_minutes' => ['nullable', 'integer', 'min:1', 'max:999'],
            'thumbnail_url' => ['nullable', 'url'],
        ]);
    }

    private function normalizeSubTopicLessonData(Request $request, array $validated, ?SubTopic $subTopic = null): array
    {
        $lessonType = $validated['lesson_type'] ?? 'video';
        $durationMinutes = $validated['video_duration_minutes'] ?? null;

        $basePayload = [
            'lesson_type' => $lessonType === 'live_session' ? 'live_session' : 'video',
            'video_provider' => null,
            'video_url' => null,
            'video_disk' => null,
            'video_path' => null,
            'video_mime' => null,
            'video_size' => null,
            'video_duration_minutes' => $durationMinutes,
            'video_duration_seconds' => $durationMinutes ? ((int) $durationMinutes * 60) : null,
            'thumbnail_url' => $validated['thumbnail_url'] ?? null,
        ];

        if ($lessonType === 'live_session') {
            $this->deleteOwnedSubTopicVideo($subTopic);

            return array_merge($basePayload, [
                'video_duration_minutes' => null,
                'video_duration_seconds' => null,
                'thumbnail_url' => null,
            ]);
        }

        if ($request->boolean('clear_video_file')) {
            $this->deleteOwnedSubTopicVideo($subTopic);

            if (! $request->hasFile('video_file') && empty($validated['server_video_path']) && empty($validated['video_path']) && empty($validated['video_url'])) {
                return $basePayload;
            }
        }

        $videoSource = $this->resolveVideoSource($request, $validated, $subTopic);

        if (in_array($videoSource, ['bunny', 'youtube'], true)) {
            $this->deleteOwnedSubTopicVideo($subTopic);

            $videoUrl = $validated['video_url'] ?? null;

            if (! $videoUrl) {
                throw ValidationException::withMessages([
                    'video_url' => [$videoSource === 'bunny'
                        ? 'Bunny Stream URL wajib diisi.'
                        : 'Video URL wajib diisi.'],
                ]);
            }

            return array_merge($basePayload, [
                'video_provider' => $videoSource,
                'video_url' => $videoUrl,
            ]);
        }

        if ($videoSource === 'upload') {
            if ($request->hasFile('video_file')) {
                $this->deleteOwnedSubTopicVideo($subTopic);

                $uploadedVideo = $this->storeUploadedSubTopicVideo($request);

                return array_merge($basePayload, $uploadedVideo);
            }

            if ($subTopic && $subTopic->video_path && ! $request->boolean('clear_video_file')) {
                return array_merge($basePayload, [
                    'video_provider' => 'self_hosted',
                    'video_disk' => $subTopic->video_disk ?: self::VIDEO_DISK,
                    'video_path' => $subTopic->video_path,
                    'video_mime' => $subTopic->video_mime ?: $this->guessVideoMime($subTopic->video_path),
                    'video_size' => $subTopic->video_size,
                ]);
            }

            return $basePayload;
        }

        $serverVideoPath = $validated['server_video_path']
            ?? $validated['video_path']
            ?? null;

        if (! $serverVideoPath && $subTopic && $subTopic->video_path && ! $request->boolean('clear_video_file')) {
            $serverVideoPath = $subTopic->video_path;
        }

        if (! $serverVideoPath) {
            return $basePayload;
        }

        $serverVideoPath = $this->normalizeServerVideoPath($serverVideoPath);

        $this->deleteOwnedSubTopicVideoIfDifferent($subTopic, $serverVideoPath);

        return array_merge($basePayload, [
            'video_provider' => 'self_hosted',
            'video_disk' => self::VIDEO_DISK,
            'video_path' => $serverVideoPath,
            'video_mime' => $this->guessVideoMime($serverVideoPath),
            'video_size' => Storage::disk(self::VIDEO_DISK)->exists($serverVideoPath)
                ? Storage::disk(self::VIDEO_DISK)->size($serverVideoPath)
                : null,
        ]);
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
            $this->deleteOwnedSubTopicVideo($subTopic);
            $subTopic->delete();
        }

        $topic->delete();
    }


    private function resolveVideoSource(Request $request, array $validated, ?SubTopic $subTopic = null): string
    {
        $source = $validated['video_source']
            ?? $validated['video_provider']
            ?? null;

        if ($request->hasFile('video_file')) {
            return 'upload';
        }

        if (in_array($source, ['youtube', 'bunny', 'upload', 'server'], true)) {
            return $source;
        }

        if ($source === 'self_hosted') {
            if (! empty($validated['server_video_path']) || ! empty($validated['video_path'])) {
                return 'server';
            }

            if ($subTopic && $subTopic->video_path) {
                return 'server';
            }

            return 'server';
        }

        if (! empty($validated['server_video_path']) || ! empty($validated['video_path'])) {
            return 'server';
        }

        if (! empty($validated['video_url'])) {
            return $subTopic && $subTopic->video_provider === 'bunny' ? 'bunny' : 'youtube';
        }

        if ($subTopic && $subTopic->video_path) {
            return 'server';
        }

        return 'server';
    }

    private function storeUploadedSubTopicVideo(Request $request): array
    {
        $file = $request->file('video_file');

        if (! $file) {
            return [
                'video_provider' => null,
                'video_disk' => null,
                'video_path' => null,
                'video_mime' => null,
                'video_size' => null,
            ];
        }

        if (! Storage::disk(self::VIDEO_DISK)->exists(self::VIDEO_UPLOAD_DIRECTORY)) {
            Storage::disk(self::VIDEO_DISK)->makeDirectory(self::VIDEO_UPLOAD_DIRECTORY);
        }

        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = Str::slug($originalName) ?: 'video';
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'mp4');

        if (! in_array($extension, self::VIDEO_EXTENSIONS, true)) {
            $extension = 'mp4';
        }

        $filename = $safeName . '-' . Str::uuid()->toString() . '.' . $extension;

        $path = $file->storeAs(
            self::VIDEO_UPLOAD_DIRECTORY,
            $filename,
            self::VIDEO_DISK
        );

        return [
            'video_provider' => 'self_hosted',
            'video_disk' => self::VIDEO_DISK,
            'video_path' => $path,
            'video_mime' => $file->getMimeType() ?: $this->guessVideoMime($path),
            'video_size' => $file->getSize(),
        ];
    }

    private function normalizeServerVideoPath(string $path): string
    {
        $normalizedPath = trim(str_replace('\\', '/', $path));
        $normalizedPath = ltrim($normalizedPath, '/');

        if (
            $normalizedPath === ''
            || str_contains($normalizedPath, '..')
            || ! str_starts_with($normalizedPath, self::VIDEO_DIRECTORY . '/')
            || ! $this->isSupportedVideoPath($normalizedPath)
        ) {
            throw ValidationException::withMessages([
                'server_video_path' => ['File video server tidak valid. Pilih file dari daftar video server.'],
            ]);
        }

        if (! Storage::disk(self::VIDEO_DISK)->exists($normalizedPath)) {
            throw ValidationException::withMessages([
                'server_video_path' => ['File video server tidak ditemukan di storage private.'],
            ]);
        }

        return $normalizedPath;
    }

    private function isSupportedVideoPath(string $path): bool
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($extension, self::VIDEO_EXTENSIONS, true);
    }

    private function guessVideoMime(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'mp4', 'm4v' => 'video/mp4',
            'webm' => 'video/webm',
            'mov' => 'video/quicktime',
            default => 'video/mp4',
        };
    }

    private function formatFileSize(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
        $value = $bytes / (1024 ** $power);

        return number_format($value, $power === 0 ? 0 : 1) . ' ' . $units[$power];
    }

    private function deleteOwnedSubTopicVideoIfDifferent(?SubTopic $subTopic, ?string $newPath): void
    {
        if (! $subTopic || ! $subTopic->video_path || $subTopic->video_path === $newPath) {
            return;
        }

        $this->deleteOwnedSubTopicVideo($subTopic);
    }

    private function deleteOwnedSubTopicVideo(?SubTopic $subTopic): void
    {
        if (! $subTopic || ! $subTopic->video_path) {
            return;
        }

        if (! $this->isOwnedUploadedVideoPath($subTopic->video_path)) {
            return;
        }

        $disk = $subTopic->video_disk ?: self::VIDEO_DISK;

        if (Storage::disk($disk)->exists($subTopic->video_path)) {
            Storage::disk($disk)->delete($subTopic->video_path);
        }
    }

    private function isOwnedUploadedVideoPath(string $path): bool
    {
        return str_starts_with($path, self::VIDEO_UPLOAD_DIRECTORY . '/');
    }


    private function successResponse(string $message, array $data = [], ?array $focus = null): JsonResponse
    {
        $payload = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        if ($focus !== null) {
            $payload['focus'] = $focus;
        }

        return response()->json($payload);
    }

    private function stageFocusPayload(ProgramStage $stage): array
    {
        return [
            'type' => 'stage',
            'program_id' => (int) $stage->program_id,
            'stage_id' => (int) $stage->id,
        ];
    }

    private function moduleFocusPayload(Module $module): array
    {
        return [
            'type' => 'module',
            'stage_id' => (int) $module->program_stage_id,
            'module_id' => (int) $module->id,
            'collapse_id' => 'moduleCollapse' . $module->id,
        ];
    }

    private function topicFocusPayload(Topic $topic): array
    {
        $topic->loadMissing('module:id,program_stage_id');

        return [
            'type' => 'topic',
            'stage_id' => (int) ($topic->module->program_stage_id ?? 0),
            'module_id' => (int) $topic->module_id,
            'topic_id' => (int) $topic->id,
            'collapse_id' => 'moduleCollapse' . $topic->module_id,
        ];
    }

    private function subTopicFocusPayload(SubTopic $subTopic): array
    {
        $subTopic->loadMissing([
            'topic:id,module_id',
            'topic.module:id,program_stage_id',
        ]);

        $topic = $subTopic->topic;
        $module = $topic?->module;

        return [
            'type' => 'sub_topic',
            'stage_id' => (int) ($module?->program_stage_id ?? 0),
            'module_id' => (int) ($topic?->module_id ?? 0),
            'topic_id' => (int) $subTopic->topic_id,
            'sub_topic_id' => (int) $subTopic->id,
            'collapse_id' => 'moduleCollapse' . ($topic?->module_id ?? 0),
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