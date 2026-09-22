<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Module;
use App\Models\Program;
use App\Models\ProgramStage;
use App\Models\SubTopic;
use App\Models\Topic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class AssignmentController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Assignment Index
    |--------------------------------------------------------------------------
    |
    | Initial page load hanya membawa:
    |
    | - assignments
    | - programs
    | - stats
    | - static dropdown options
    |
    | Stage, Topic, dan Sub Topic diambil asynchronous setelah user
    | memilih parent dropdown.
    |
    */
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search'));

        $programId = $request->input('program_id');
        $stageId = $request->input('stage_id');
        $topicId = $request->input('topic_id');
        $subTopicId = $request->input('sub_topic_id');

        $type = $request->input('assignment_type');
        $status = $request->input('status');

        $moduleHasProgramId = $this->moduleHasProgramId();

        $assignments = Assignment::query()
            ->with([
                'topic:id,module_id,name',

                'topic.module' => function ($query) use ($moduleHasProgramId) {
                    $columns = [
                        'id',
                        'program_stage_id',
                        'name',
                    ];

                    if ($moduleHasProgramId) {
                        $columns[] = 'program_id';
                    }

                    $query->select($columns);
                },

                'topic.module.stage:id,program_id,name',
                'topic.module.stage.program:id,name',

                'subTopic:id,topic_id,name',

                'createdBy:id,name',
                'updatedBy:id,name',
            ])
            ->withCount([
                'batchAssignments',
                'submissions',
            ])

            /*
            |--------------------------------------------------------------------------
            | Search
            |--------------------------------------------------------------------------
            */
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', '%' . $search . '%')
                        ->orWhere('instruction', 'like', '%' . $search . '%')

                        ->orWhereHas('topic', function ($topicQuery) use ($search) {
                            $topicQuery->where('name', 'like', '%' . $search . '%');
                        })

                        ->orWhereHas('subTopic', function ($subTopicQuery) use ($search) {
                            $subTopicQuery->where('name', 'like', '%' . $search . '%');
                        })

                        ->orWhereHas('topic.module', function ($moduleQuery) use ($search) {
                            $moduleQuery->where('name', 'like', '%' . $search . '%');
                        })

                        ->orWhereHas('topic.module.stage', function ($stageQuery) use ($search) {
                            $stageQuery->where('name', 'like', '%' . $search . '%');
                        })

                        ->orWhereHas('topic.module.stage.program', function ($programQuery) use ($search) {
                            $programQuery->where('name', 'like', '%' . $search . '%');
                        });
                });
            })

            /*
            |--------------------------------------------------------------------------
            | Program Filter
            |--------------------------------------------------------------------------
            |
            | Support:
            |
            | 1. Program → Stage → Module → Topic
            | 2. Program → Module → Topic
            |
            */
            ->when($programId, function ($query) use ($programId, $moduleHasProgramId) {
                $query->whereHas('topic.module', function ($moduleQuery) use (
                    $programId,
                    $moduleHasProgramId
                ) {
                    $moduleQuery->where(function ($q) use (
                        $programId,
                        $moduleHasProgramId
                    ) {
                        /*
                        |--------------------------------------------------------------------------
                        | Curriculum melalui Stage
                        |--------------------------------------------------------------------------
                        */
                        $q->whereHas('stage', function ($stageQuery) use ($programId) {
                            $stageQuery->where('program_id', $programId);
                        });

                        /*
                        |--------------------------------------------------------------------------
                        | Curriculum langsung ke Program
                        |--------------------------------------------------------------------------
                        */
                        if ($moduleHasProgramId) {
                            $q->orWhere(function ($directQuery) use ($programId) {
                                $directQuery
                                    ->whereNull('program_stage_id')
                                    ->where('program_id', $programId);
                            });
                        }
                    });
                });
            })

            /*
            |--------------------------------------------------------------------------
            | Stage Filter
            |--------------------------------------------------------------------------
            */
            ->when($stageId, function ($query) use ($stageId) {
                $query->whereHas('topic.module', function ($moduleQuery) use ($stageId) {
                    $moduleQuery->where('program_stage_id', $stageId);
                });
            })

            /*
            |--------------------------------------------------------------------------
            | Topic Filter
            |--------------------------------------------------------------------------
            */
            ->when($topicId, function ($query) use ($topicId) {
                $query->where('topic_id', $topicId);
            })

            /*
            |--------------------------------------------------------------------------
            | Sub Topic Filter
            |--------------------------------------------------------------------------
            */
            ->when($subTopicId, function ($query) use ($subTopicId) {
                $query->where('sub_topic_id', $subTopicId);
            })

            /*
            |--------------------------------------------------------------------------
            | Type
            |--------------------------------------------------------------------------
            */
            ->when($type, function ($query) use ($type) {
                $query->where('assignment_type', $type);
            })

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })

            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Initial Programs
        |--------------------------------------------------------------------------
        |
        | Hanya Program yang diload di awal.
        |
        */
        $programs = Program::query()
            ->select([
                'id',
                'name',
                'is_active',
            ])
            ->orderBy('name')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Stats
        |--------------------------------------------------------------------------
        */
        $stats = [
            'total' => Assignment::count(),

            'published' => Assignment::query()
                ->where('status', 'published')
                ->count(),

            'draft' => Assignment::query()
                ->where('status', 'draft')
                ->count(),

            'active' => Assignment::query()
                ->where('is_active', true)
                ->count(),
        ];

        return view('academic.assignments.index', [
            'assignments' => $assignments,

            'programs' => $programs,

            'stats' => $stats,

            'assignmentTypes' => $this->assignmentTypes(),
            'statuses' => $this->statuses(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Async: Program Structure / Stages
    |--------------------------------------------------------------------------
    |
    | Dipanggil setelah Program dipilih.
    |
    | Response memberitahu frontend:
    |
    | - apakah program punya Stage
    | - apakah program punya direct curriculum
    | - apakah Stage wajib dipilih
    |
    */
    public function optionsStages(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'program_id' => [
                'required',
                'integer',
                'exists:programs,id',
            ],
        ]);

        $programId = (int) $validated['program_id'];

        $stages = ProgramStage::query()
            ->select([
                'id',
                'program_id',
                'name',
                'sort_order',
                'is_active',
            ])
            ->where('program_id', $programId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Cek direct curriculum
        |--------------------------------------------------------------------------
        |
        | Contoh:
        |
        | AI For Office
        | Program → Module → Topic
        |
        */
        $hasDirectTopics = false;

        if ($this->moduleHasProgramId()) {
            $hasDirectTopics = Topic::query()
                ->whereHas('module', function ($moduleQuery) use ($programId) {
                    $moduleQuery
                        ->whereNull('program_stage_id')
                        ->where('program_id', $programId);
                })
                ->exists();
        }

        $hasStages = $stages->isNotEmpty();

        /*
        |--------------------------------------------------------------------------
        | Mode
        |--------------------------------------------------------------------------
        |
        | staged:
        |   hanya curriculum dengan Stage
        |
        | direct:
        |   tanpa Stage
        |
        | hybrid:
        |   mempunyai keduanya
        |
        | empty:
        |   belum punya curriculum
        |
        */
        $mode = match (true) {
            $hasStages && $hasDirectTopics => 'hybrid',
            $hasStages => 'staged',
            $hasDirectTopics => 'direct',
            default => 'empty',
        };

        return response()->json([
            'success' => true,

            'data' => [
                'program_id' => $programId,

                'mode' => $mode,

                'has_stages' => $hasStages,

                'has_direct_topics' => $hasDirectTopics,

                /*
                |--------------------------------------------------------------------------
                | Stage wajib hanya jika semua Topic memang melalui Stage.
                |--------------------------------------------------------------------------
                */
                'requires_stage' => $hasStages && !$hasDirectTopics,

                'stages' => $stages
                    ->map(function (ProgramStage $stage) {
                        return [
                            'id' => $stage->id,
                            'name' => $stage->name,
                            'is_active' => (bool) $stage->is_active,
                        ];
                    })
                    ->values(),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Async: Topics
    |--------------------------------------------------------------------------
    |
    | Program staged:
    |
    | program_id + stage_id
    |
    | Program direct:
    |
    | program_id saja
    |
    */
    public function optionsTopics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'program_id' => [
                'required',
                'integer',
                'exists:programs,id',
            ],

            'stage_id' => [
                'nullable',
                'integer',
                'exists:program_stages,id',
            ],
        ]);

        $programId = (int) $validated['program_id'];

        $stageId = !empty($validated['stage_id'])
            ? (int) $validated['stage_id']
            : null;

        /*
        |--------------------------------------------------------------------------
        | Kalau Stage dikirim, pastikan Stage milik Program.
        |--------------------------------------------------------------------------
        */
        if ($stageId) {
            $stage = ProgramStage::query()
                ->select([
                    'id',
                    'program_id',
                ])
                ->find($stageId);

            if (!$stage || (int) $stage->program_id !== $programId) {
                throw ValidationException::withMessages([
                    'stage_id' => [
                        'Stage yang dipilih tidak sesuai dengan program.',
                    ],
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Topic melalui Stage
            |--------------------------------------------------------------------------
            */
            $topics = Topic::query()
                ->select([
                    'id',
                    'module_id',
                    'name',
                    'sort_order',
                    'is_active',
                ])
                ->with([
                    'module:id,program_stage_id,name',
                ])
                ->whereHas('module', function ($moduleQuery) use ($stageId) {
                    $moduleQuery->where(
                        'program_stage_id',
                        $stageId
                    );
                })
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,

                'data' => [
                    'program_id' => $programId,
                    'stage_id' => $stageId,

                    'topics' => $topics
                        ->map(function (Topic $topic) {
                            return [
                                'id' => $topic->id,

                                'name' => $topic->name,

                                'module_id' => $topic->module_id,

                                'module_name' => $topic->module?->name,

                                'label' => $topic->module
                                    ? $topic->module->name . ' - ' . $topic->name
                                    : $topic->name,

                                'is_active' => (bool) $topic->is_active,
                            ];
                        })
                        ->values(),
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Tanpa Stage
        |--------------------------------------------------------------------------
        |
        | Hanya valid kalau Module mempunyai direct program_id.
        |
        */
        if (!$this->moduleHasProgramId()) {
            return response()->json([
                'success' => true,

                'data' => [
                    'program_id' => $programId,
                    'stage_id' => null,
                    'topics' => [],
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Cek apakah program sebenarnya membutuhkan Stage.
        |--------------------------------------------------------------------------
        */
        $hasStages = ProgramStage::query()
            ->where('program_id', $programId)
            ->exists();

        $hasDirectTopics = Topic::query()
            ->whereHas('module', function ($moduleQuery) use ($programId) {
                $moduleQuery
                    ->whereNull('program_stage_id')
                    ->where('program_id', $programId);
            })
            ->exists();

        /*
        |--------------------------------------------------------------------------
        | Program staged murni.
        |
        | Jangan diam-diam return seluruh Topic.
        |--------------------------------------------------------------------------
        */
        if ($hasStages && !$hasDirectTopics) {
            throw ValidationException::withMessages([
                'stage_id' => [
                    'Pilih stage terlebih dahulu untuk program ini.',
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Direct Program → Module → Topic
        |--------------------------------------------------------------------------
        */
        $topics = Topic::query()
            ->select([
                'id',
                'module_id',
                'name',
                'sort_order',
                'is_active',
            ])
            ->with([
                'module' => function ($moduleQuery) {
                    $moduleQuery->select([
                        'id',
                        'program_stage_id',
                        'program_id',
                        'name',
                    ]);
                },
            ])
            ->whereHas('module', function ($moduleQuery) use ($programId) {
                $moduleQuery
                    ->whereNull('program_stage_id')
                    ->where('program_id', $programId);
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,

            'data' => [
                'program_id' => $programId,
                'stage_id' => null,

                'topics' => $topics
                    ->map(function (Topic $topic) {
                        return [
                            'id' => $topic->id,

                            'name' => $topic->name,

                            'module_id' => $topic->module_id,

                            'module_name' => $topic->module?->name,

                            'label' => $topic->module
                                ? $topic->module->name . ' - ' . $topic->name
                                : $topic->name,

                            'is_active' => (bool) $topic->is_active,
                        ];
                    })
                    ->values(),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Async: Sub Topics
    |--------------------------------------------------------------------------
    */
    public function optionsSubTopics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'topic_id' => [
                'required',
                'integer',
                'exists:topics,id',
            ],
        ]);

        $topicId = (int) $validated['topic_id'];

        $subTopics = SubTopic::query()
            ->select([
                'id',
                'topic_id',
                'name',
                'sort_order',
                'is_active',
            ])
            ->where('topic_id', $topicId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,

            'data' => [
                'topic_id' => $topicId,

                'sub_topics' => $subTopics
                    ->map(function (SubTopic $subTopic) {
                        return [
                            'id' => $subTopic->id,
                            'name' => $subTopic->name,
                            'is_active' => (bool) $subTopic->is_active,
                        ];
                    })
                    ->values(),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $this->validateAssignment($request);

            $validated = $this->normalizeAssignmentTarget(
                $validated
            );

            $assignment = DB::transaction(function () use ($validated) {
                return Assignment::create([
                    /*
                    |--------------------------------------------------------------------------
                    | Hanya target asli yang disimpan.
                    |--------------------------------------------------------------------------
                    */
                    'topic_id' => $validated['topic_id'],

                    'sub_topic_id' =>
                        $validated['sub_topic_id'] ?? null,

                    'title' => $validated['title'],

                    'slug' =>
                        $this->generateUniqueSlug(
                            $validated['title']
                        ),

                    'assignment_type' =>
                        $validated['assignment_type'],

                    'instruction' =>
                        $this->normalizeInstructionHtml(
                            $validated['instruction'] ?? null
                        ),

                    'attachment_url' =>
                        $validated['attachment_url'] ?? null,

                    'starter_file_url' =>
                        $validated['starter_file_url'] ?? null,

                    'reference_url' =>
                        $validated['reference_url'] ?? null,

                    'estimated_minutes' =>
                        $validated['estimated_minutes'] ?? null,

                    'max_score' =>
                        $validated['max_score'] ?? 100,

                    'is_required' =>
                        (bool) ($validated['is_required'] ?? true),

                    'sort_order' =>
                        $validated['sort_order'] ?? 1,

                    'status' =>
                        $validated['status'] ?? 'draft',

                    'is_active' =>
                        (bool) ($validated['is_active'] ?? true),

                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            });

            return response()->json([
                'success' => true,

                'message' =>
                    'Assignment berhasil ditambahkan.',

                'data' => [
                    'id' => $assignment->id,
                    'title' => $assignment->title,
                ],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->errorResponse(
                'Gagal menambahkan assignment.',
                $e
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */
    public function update(
        Request $request,
        Assignment $assignment
    ): JsonResponse {
        try {
            $validated = $this->validateAssignment($request);

            $validated = $this->normalizeAssignmentTarget(
                $validated
            );

            DB::transaction(function () use (
                $assignment,
                $validated
            ) {
                $assignment->update([
                    'topic_id' =>
                        $validated['topic_id'],

                    'sub_topic_id' =>
                        $validated['sub_topic_id'] ?? null,

                    'title' =>
                        $validated['title'],

                    'slug' =>
                        $this->generateUniqueSlug(
                            $validated['title'],
                            $assignment->id
                        ),

                    'assignment_type' =>
                        $validated['assignment_type'],

                    'instruction' =>
                        $this->normalizeInstructionHtml(
                            $validated['instruction'] ?? null
                        ),

                    'attachment_url' =>
                        $validated['attachment_url'] ?? null,

                    'starter_file_url' =>
                        $validated['starter_file_url'] ?? null,

                    'reference_url' =>
                        $validated['reference_url'] ?? null,

                    'estimated_minutes' =>
                        $validated['estimated_minutes'] ?? null,

                    'max_score' =>
                        $validated['max_score']
                        ?? $assignment->max_score
                        ?? 100,

                    'is_required' =>
                        (bool) ($validated['is_required'] ?? true),

                    'sort_order' =>
                        $validated['sort_order']
                        ?? $assignment->sort_order
                        ?? 1,

                    'status' =>
                        $validated['status']
                        ?? $assignment->status
                        ?? 'draft',

                    'is_active' =>
                        (bool) ($validated['is_active'] ?? true),

                    'updated_by' =>
                        auth()->id(),
                ]);
            });

            return response()->json([
                'success' => true,

                'message' =>
                    'Assignment berhasil diperbarui.',

                'data' => [
                    'id' => $assignment->id,

                    'title' =>
                        $assignment
                            ->fresh()
                            ->title,
                ],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->errorResponse(
                'Gagal memperbarui assignment.',
                $e
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Destroy
    |--------------------------------------------------------------------------
    */
    public function destroy(
        Assignment $assignment
    ): JsonResponse {
        try {
            DB::transaction(function () use ($assignment) {
                $assignment->delete();
            });

            return response()->json([
                'success' => true,

                'message' =>
                    'Assignment berhasil dihapus.',
            ]);
        } catch (Throwable $e) {
            return $this->errorResponse(
                'Gagal menghapus assignment.',
                $e
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    |
    | Stage sengaja nullable.
    |
    | Karena beberapa Program menggunakan:
    |
    | Program → Stage → Module
    |
    | sementara program lain:
    |
    | Program → Module
    |
    */
    private function validateAssignment(
        Request $request
    ): array {
        return $request->validate([
            'program_id' => [
                'required',
                'integer',
                'exists:programs,id',
            ],

            'stage_id' => [
                'nullable',
                'integer',
                'exists:program_stages,id',
            ],

            'topic_id' => [
                'required',
                'integer',
                'exists:topics,id',
            ],

            'sub_topic_id' => [
                'nullable',
                'integer',
                'exists:sub_topics,id',
            ],

            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'assignment_type' => [
                'required',
                Rule::in(
                    array_keys(
                        $this->assignmentTypes()
                    )
                ),
            ],

            'instruction' => [
                'nullable',
                'string',
            ],

            'attachment_url' => [
                'nullable',
                'url',
            ],

            'starter_file_url' => [
                'nullable',
                'url',
            ],

            'reference_url' => [
                'nullable',
                'url',
            ],

            'estimated_minutes' => [
                'nullable',
                'integer',
                'min:1',
                'max:9999',
            ],

            'max_score' => [
                'nullable',
                'integer',
                'min:1',
                'max:999',
            ],

            'is_required' => [
                'required',
                'boolean',
            ],

            'sort_order' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'status' => [
                'required',

                Rule::in(
                    array_keys(
                        $this->statuses()
                    )
                ),
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Normalize / Validate Assignment Target
    |--------------------------------------------------------------------------
    |
    | Backend menentukan hierarchy berdasarkan Module dari Topic.
    |
    | Jika Module punya program_stage_id:
    |
    | Program
    | → Stage
    | → Module
    | → Topic
    |
    | Jika program_stage_id NULL:
    |
    | Program
    | → Module
    | → Topic
    |
    */
    private function normalizeAssignmentTarget(
        array $validated
    ): array {
        $programId =
            (int) $validated['program_id'];

        $stageId =
            !empty($validated['stage_id'])
                ? (int) $validated['stage_id']
                : null;

        $topicId =
            (int) $validated['topic_id'];

        $subTopicId =
            !empty($validated['sub_topic_id'])
                ? (int) $validated['sub_topic_id']
                : null;

        /*
        |--------------------------------------------------------------------------
        | Topic
        |--------------------------------------------------------------------------
        */
        $topic = Topic::query()
            ->select([
                'id',
                'module_id',
            ])
            ->find($topicId);

        if (!$topic) {
            throw ValidationException::withMessages([
                'topic_id' => [
                    'Topic yang dipilih tidak ditemukan.',
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Module
        |--------------------------------------------------------------------------
        */
        $moduleColumns = [
            'id',
            'program_stage_id',
            'name',
        ];

        if ($this->moduleHasProgramId()) {
            $moduleColumns[] = 'program_id';
        }

        $module = Module::query()
            ->select($moduleColumns)
            ->find($topic->module_id);

        if (!$module) {
            throw ValidationException::withMessages([
                'topic_id' => [
                    'Topic yang dipilih belum terhubung ke module.',
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | STAGED CURRICULUM
        |--------------------------------------------------------------------------
        */
        if ($module->program_stage_id) {
            $moduleStageId =
                (int) $module->program_stage_id;

            /*
            |--------------------------------------------------------------------------
            | Stage wajib untuk Topic staged.
            |--------------------------------------------------------------------------
            */
            if (!$stageId) {
                throw ValidationException::withMessages([
                    'stage_id' => [
                        'Pilih stage untuk topic ini.',
                    ],
                ]);
            }

            if ($stageId !== $moduleStageId) {
                throw ValidationException::withMessages([
                    'topic_id' => [
                        'Topic yang dipilih tidak sesuai dengan stage.',
                    ],
                ]);
            }

            $stage = ProgramStage::query()
                ->select([
                    'id',
                    'program_id',
                ])
                ->find($moduleStageId);

            if (!$stage) {
                throw ValidationException::withMessages([
                    'stage_id' => [
                        'Stage dari topic yang dipilih tidak ditemukan.',
                    ],
                ]);
            }

            if ((int) $stage->program_id !== $programId) {
                throw ValidationException::withMessages([
                    'program_id' => [
                        'Topic yang dipilih tidak sesuai dengan program.',
                    ],
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | DIRECT CURRICULUM
        |--------------------------------------------------------------------------
        */
        else {
            /*
            |--------------------------------------------------------------------------
            | Direct curriculum tidak boleh membawa Stage.
            |--------------------------------------------------------------------------
            */
            if ($stageId) {
                throw ValidationException::withMessages([
                    'stage_id' => [
                        'Program ini tidak menggunakan stage untuk topic yang dipilih.',
                    ],
                ]);
            }

            if (!$this->moduleHasProgramId()) {
                throw ValidationException::withMessages([
                    'topic_id' => [
                        'Module dari topic ini belum mempunyai hubungan program yang valid.',
                    ],
                ]);
            }

            if (
                empty($module->program_id)
                || (int) $module->program_id !== $programId
            ) {
                throw ValidationException::withMessages([
                    'topic_id' => [
                        'Topic yang dipilih tidak sesuai dengan program.',
                    ],
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Sub Topic → Topic
        |--------------------------------------------------------------------------
        */
        if ($subTopicId) {
            $subTopic = SubTopic::query()
                ->select([
                    'id',
                    'topic_id',
                ])
                ->find($subTopicId);

            if (!$subTopic) {
                throw ValidationException::withMessages([
                    'sub_topic_id' => [
                        'Sub topic yang dipilih tidak ditemukan.',
                    ],
                ]);
            }

            if (
                (int) $subTopic->topic_id
                !== $topicId
            ) {
                throw ValidationException::withMessages([
                    'sub_topic_id' => [
                        'Sub topic yang dipilih tidak sesuai dengan topic.',
                    ],
                ]);
            }
        }

        $validated['program_id'] =
            $programId;

        $validated['stage_id'] =
            $stageId;

        $validated['topic_id'] =
            $topicId;

        $validated['sub_topic_id'] =
            $subTopicId;

        return $validated;
    }

    /*
    |--------------------------------------------------------------------------
    | Module Schema Detection
    |--------------------------------------------------------------------------
    |
    | Ini dibuat defensif karena struktur project sekarang mendukung
    | Module via ProgramStage dan ada relation Module::program().
    |
    | Kalau kolom program_id memang tersedia, direct curriculum didukung.
    |
    */
    private function moduleHasProgramId(): bool
    {
        static $hasColumn = null;

        if ($hasColumn === null) {
            $hasColumn = Schema::hasColumn(
                'modules',
                'program_id'
            );
        }

        return $hasColumn;
    }

    /*
    |--------------------------------------------------------------------------
    | Normalize Instruction
    |--------------------------------------------------------------------------
    */
    private function normalizeInstructionHtml(
        ?string $instruction
    ): ?string {
        if ($instruction === null) {
            return null;
        }

        $instruction =
            trim($instruction);

        if ($instruction === '') {
            return null;
        }

        $instruction = str_replace(
            ["\r\n", "\r"],
            "\n",
            $instruction
        );

        if (
            $this->looksLikeEscapedHtml(
                $instruction
            )
        ) {
            $instruction =
                html_entity_decode(
                    $instruction,
                    ENT_QUOTES | ENT_HTML5,
                    'UTF-8'
                );

            $instruction =
                trim($instruction);
        }

        if (
            $this->isEmptyQuillHtml(
                $instruction
            )
        ) {
            return null;
        }

        if (
            $this->containsHtmlTag(
                $instruction
            )
        ) {
            $html =
                $this->cleanInstructionHtml(
                    $instruction
                );

            return $this
                ->isEmptyInstructionHtml($html)
                    ? null
                    : $html;
        }

        $html =
            $this->plainTextToInstructionHtml(
                $instruction
            );

        return $this
            ->isEmptyInstructionHtml($html)
                ? null
                : $html;
    }

    private function looksLikeEscapedHtml(
        string $value
    ): bool {
        return (bool) preg_match(
            '/&lt;\s*\/?\s*(p|br|h1|h2|h3|h4|h5|h6|ul|ol|li|strong|b|em|i|u|s|a|blockquote|pre|code|span|div)\b/i',
            $value
        );
    }

    private function containsHtmlTag(
        string $value
    ): bool {
        return (bool) preg_match(
            '/<\s*\/?\s*[a-z][^>]*>/i',
            $value
        );
    }

    private function isEmptyQuillHtml(
        string $html
    ): bool {
        $normalized =
            strtolower(
                preg_replace(
                    '/\s+/',
                    '',
                    str_replace(
                        '&nbsp;',
                        '',
                        $html
                    )
                ) ?? ''
            );

        return in_array(
            $normalized,
            [
                '',
                '<p></p>',
                '<p><br></p>',
                '<p><br/></p>',
                '<div></div>',
                '<div><br></div>',
                '<div><br/></div>',
            ],
            true
        );
    }

    private function isEmptyInstructionHtml(
        ?string $html
    ): bool {
        if ($html === null) {
            return true;
        }

        $html =
            trim($html);

        if (
            $html === ''
            || $this->isEmptyQuillHtml($html)
        ) {
            return true;
        }

        $text =
            trim(
                html_entity_decode(
                    strip_tags(
                        str_replace(
                            '&nbsp;',
                            ' ',
                            $html
                        )
                    ),
                    ENT_QUOTES | ENT_HTML5,
                    'UTF-8'
                )
            );

        $hasMeaningfulTag =
            (bool) preg_match(
                '/<(img|video|audio|iframe|table|ul|ol|li|blockquote|pre|code)\b/i',
                $html
            );

        return $text === ''
            && !$hasMeaningfulTag;
    }

    private function cleanInstructionHtml(
        string $html
    ): string {
        $html =
            trim($html);

        if ($html === '') {
            return '';
        }

        $html =
            preg_replace(
                '/<!--.*?-->/s',
                '',
                $html
            ) ?? $html;

        $html =
            preg_replace(
                '/<\s*(script|style|iframe|object|embed|form|input|button|textarea|select|option|meta|link|base)\b[^>]*>.*?<\s*\/\s*\1\s*>/is',
                '',
                $html
            ) ?? $html;

        $html =
            preg_replace(
                '/<\s*(script|style|iframe|object|embed|form|input|button|textarea|select|option|meta|link|base)\b[^>]*\/?>/is',
                '',
                $html
            ) ?? $html;

        $allowedTags =
            implode('', [
                '<p>',
                '<br>',
                '<strong>',
                '<b>',
                '<em>',
                '<i>',
                '<u>',
                '<s>',
                '<ol>',
                '<ul>',
                '<li>',
                '<a>',
                '<h1>',
                '<h2>',
                '<h3>',
                '<h4>',
                '<h5>',
                '<h6>',
                '<blockquote>',
                '<pre>',
                '<code>',
                '<span>',
                '<div>',
            ]);

        $html =
            strip_tags(
                $html,
                $allowedTags
            );

        $html =
            preg_replace(
                '/\s+on[a-z]+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/is',
                '',
                $html
            ) ?? $html;

        $html =
            preg_replace(
                '/\s+style\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/is',
                '',
                $html
            ) ?? $html;

        $html =
            preg_replace(
                '/\s+src\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/is',
                '',
                $html
            ) ?? $html;

        $html =
            preg_replace_callback(
                '/<a\b([^>]*)>/i',
                function ($matches) {
                    $attributes =
                        $matches[1] ?? '';

                    $href = null;

                    if (
                        preg_match(
                            '/href\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i',
                            $attributes,
                            $hrefMatches
                        )
                    ) {
                        $href =
                            $hrefMatches[2]
                            ?? $hrefMatches[3]
                            ?? $hrefMatches[4]
                            ?? null;
                    }

                    $href =
                        trim(
                            (string) $href
                        );

                    if (
                        $href === ''
                        || !$this
                            ->isSafeInstructionUrl(
                                $href
                            )
                    ) {
                        return '<a>';
                    }

                    return '<a href="'
                        . e($href)
                        . '" target="_blank" rel="noopener noreferrer">';
                },
                $html
            ) ?? $html;

        $html =
            preg_replace_callback(
                '/<(?!\/|a\b|br\b)([a-z0-9]+)\b([^>]*)>/i',
                function ($matches) {
                    $tag =
                        strtolower(
                            $matches[1]
                        );

                    $attributes =
                        $matches[2] ?? '';

                    $classAttr = '';

                    if (
                        preg_match(
                            '/class\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i',
                            $attributes,
                            $classMatches
                        )
                    ) {
                        $classValue =
                            $classMatches[2]
                            ?? $classMatches[3]
                            ?? $classMatches[4]
                            ?? '';

                        $allowedClasses =
                            collect(
                                preg_split(
                                    '/\s+/',
                                    trim($classValue)
                                ) ?: []
                            )
                                ->filter(
                                    fn ($className) =>
                                        preg_match(
                                            '/^ql-[a-z0-9_-]+$/i',
                                            $className
                                        )
                                )
                                ->values()
                                ->all();

                        if (
                            !empty(
                                $allowedClasses
                            )
                        ) {
                            $classAttr =
                                ' class="'
                                . e(
                                    implode(
                                        ' ',
                                        $allowedClasses
                                    )
                                )
                                . '"';
                        }
                    }

                    return '<'
                        . $tag
                        . $classAttr
                        . '>';
                },
                $html
            ) ?? $html;

        $html =
            preg_replace(
                '/>\s+</',
                '><',
                $html
            ) ?? $html;

        return trim($html);
    }

    private function isSafeInstructionUrl(
        string $url
    ): bool {
        $url =
            trim($url);

        if ($url === '') {
            return false;
        }

        $lowerUrl =
            strtolower($url);

        if (
            str_starts_with(
                $lowerUrl,
                'javascript:'
            )
            || str_starts_with(
                $lowerUrl,
                'data:'
            )
            || str_starts_with(
                $lowerUrl,
                'vbscript:'
            )
        ) {
            return false;
        }

        return str_starts_with(
            $lowerUrl,
            'http://'
        )
            || str_starts_with(
                $lowerUrl,
                'https://'
            )
            || str_starts_with(
                $lowerUrl,
                'mailto:'
            )
            || str_starts_with(
                $lowerUrl,
                'tel:'
            )
            || str_starts_with(
                $lowerUrl,
                '#'
            )
            || str_starts_with(
                $lowerUrl,
                '/'
            );
    }

    private function plainTextToInstructionHtml(
        string $text
    ): string {
        $text =
            trim(
                str_replace(
                    ["\r\n", "\r"],
                    "\n",
                    $text
                )
            );

        if ($text === '') {
            return '';
        }

        $paragraphs =
            preg_split(
                "/\n{2,}/",
                $text
            ) ?: [];

        return collect(
            $paragraphs
        )
            ->map(
                function ($paragraph) {
                    $paragraph =
                        trim(
                            (string) $paragraph
                        );

                    if ($paragraph === '') {
                        return null;
                    }

                    $paragraph =
                        e($paragraph);

                    $paragraph =
                        preg_replace(
                            "/\n/",
                            '<br>',
                            $paragraph
                        );

                    return '<p>'
                        . $paragraph
                        . '</p>';
                }
            )
            ->filter()
            ->implode('');
    }

    private function assignmentTypes(): array
    {
        return [
            'text' =>
                'Text Answer',

            'file' =>
                'File Upload',

            'link' =>
                'Link Submission',

            'mixed' =>
                'Mixed Submission',
        ];
    }

    private function statuses(): array
    {
        return [
            'draft' =>
                'Draft',

            'published' =>
                'Published',

            'archived' =>
                'Archived',
        ];
    }

    private function generateUniqueSlug(
        string $title,
        ?int $ignoreId = null
    ): string {
        $baseSlug =
            Str::slug($title);

        $baseSlug =
            $baseSlug !== ''
                ? $baseSlug
                : 'assignment';

        $slug =
            $baseSlug;

        $counter = 1;

        while (
            Assignment::query()
                ->when(
                    $ignoreId,
                    function ($query) use (
                        $ignoreId
                    ) {
                        $query->where(
                            'id',
                            '!=',
                            $ignoreId
                        );
                    }
                )
                ->where(
                    'slug',
                    $slug
                )
                ->exists()
        ) {
            $slug =
                $baseSlug
                . '-'
                . $counter;

            $counter++;
        }

        return $slug;
    }

    private function errorResponse(
        string $message,
        Throwable $e
    ): JsonResponse {
        return response()->json([
            'success' => false,

            'message' =>
                $message,

            'error' =>
                config('app.debug')
                    ? $e->getMessage()
                    : null,
        ], 500);
    }
}