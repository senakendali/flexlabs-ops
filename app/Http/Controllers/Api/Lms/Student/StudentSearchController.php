<?php

namespace App\Http\Controllers\Api\Lms\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class StudentSearchController extends Controller
{
    private string $defaultCourseTitle;
    private string $defaultCourseSlug;

    public function __construct()
    {
        $this->defaultCourseTitle = env('LMS_DEFAULT_COURSE_TITLE', 'AI Powered Software Engineering');
        $this->defaultCourseSlug = env('LMS_DEFAULT_COURSE_SLUG', 'ai-powered-software-engineering');
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:30'],
            'debug' => ['nullable'],
        ]);

        $keyword = trim((string) ($validated['q'] ?? ''));
        $limit = (int) ($validated['limit'] ?? 10);
        $debugEnabled = $request->boolean('debug');

        if (mb_strlen($keyword) < 2) {
            return response()->json([
                'success' => true,
                'message' => 'Minimal keyword 2 karakter.',
                'data' => [
                    'query' => $keyword,
                    'results' => [],
                    'total' => 0,
                ],
            ]);
        }

        if (!Schema::hasTable('sub_topics')) {
            return response()->json([
                'success' => false,
                'message' => 'Tabel sub_topics tidak ditemukan.',
            ], 422);
        }

        try {
            $debug = [];

            $student = $this->resolveStudent($request->user());

            $fallbackProgram = $student
                ? $this->resolveFallbackProgram($student)
                : null;

            if (!$fallbackProgram) {
                $fallbackProgram = $this->defaultProgramPayload();
            }

            $debug['student_id'] = $student->id ?? null;
            $debug['fallback_program'] = $fallbackProgram;

            $results = $this->searchLessons(
                keyword: $keyword,
                limit: $limit,
                fallbackProgram: $fallbackProgram,
                debug: $debug
            );

            $data = [
                'query' => $keyword,
                'results' => $results,
                'total' => count($results),
            ];

            if ($debugEnabled) {
                $data['debug'] = $debug;
            }

            return response()->json([
                'success' => true,
                'message' => 'Search result berhasil dimuat.',
                'data' => $data,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Search lesson gagal dimuat.',
                'error' => config('app.debug') ? $exception->getMessage() : null,
            ], 500);
        }
    }

    private function searchLessons(string $keyword, int $limit, ?array $fallbackProgram, array &$debug = []): array
    {
        $lowerKeyword = mb_strtolower($keyword);
        $like = '%' . $lowerKeyword . '%';

        $debug['mode'] = 'sub_topics_base_left_join_with_default_course_fallback';
        $debug['keyword'] = $keyword;
        $debug['raw_sub_topic_count'] = $this->countRawSubTopicMatches($like);

        $query = DB::table('sub_topics');

        $hasTopicsJoin = false;
        $hasModulesJoin = false;
        $hasProgramJoin = false;

        if (
            Schema::hasTable('topics')
            && Schema::hasColumn('sub_topics', 'topic_id')
        ) {
            $query->leftJoin('topics', 'sub_topics.topic_id', '=', 'topics.id');
            $hasTopicsJoin = true;
        }

        if (
            $hasTopicsJoin
            && Schema::hasTable('modules')
            && Schema::hasColumn('topics', 'module_id')
        ) {
            $query->leftJoin('modules', 'topics.module_id', '=', 'modules.id');
            $hasModulesJoin = true;
        }

        if (
            $hasModulesJoin
            && Schema::hasTable('programs')
            && Schema::hasColumn('modules', 'program_id')
        ) {
            $query->leftJoin('programs', 'modules.program_id', '=', 'programs.id');
            $hasProgramJoin = true;
            $debug['program_join_type'] = 'modules.program_id';
        } elseif (
            $hasModulesJoin
            && Schema::hasTable('programs')
            && Schema::hasTable('stages')
            && Schema::hasColumn('modules', 'stage_id')
            && Schema::hasColumn('stages', 'program_id')
        ) {
            $query
                ->leftJoin('stages', 'modules.stage_id', '=', 'stages.id')
                ->leftJoin('programs', 'stages.program_id', '=', 'programs.id');

            $hasProgramJoin = true;
            $debug['program_join_type'] = 'stages.program_id';
        } else {
            $debug['program_join_type'] = null;
        }

        $debug['topics_joined'] = $hasTopicsJoin;
        $debug['modules_joined'] = $hasModulesJoin;
        $debug['programs_joined'] = $hasProgramJoin;

        /**
         * Search lesson dibuat longgar dulu:
         * - tidak filter enrollment
         * - tidak filter program
         * - tidak filter is_active
         *
         * Supaya selama keyword ada di sub_topics/topics/modules, result tetap keluar.
         */
        $query->where(function ($builder) use ($like, $hasTopicsJoin, $hasModulesJoin, $hasProgramJoin) {
            $this->orWhereLowerLikeIfColumnExists($builder, 'sub_topics', [
                'name',
                'title',
                'description',
                'lesson_type',
            ], $like);

            if ($hasTopicsJoin) {
                $this->orWhereLowerLikeIfColumnExists($builder, 'topics', [
                    'name',
                    'title',
                    'description',
                    'practice_brief',
                ], $like);
            }

            if ($hasModulesJoin) {
                $this->orWhereLowerLikeIfColumnExists($builder, 'modules', [
                    'name',
                    'title',
                    'description',
                ], $like);
            }

            if ($hasProgramJoin) {
                $this->orWhereLowerLikeIfColumnExists($builder, 'programs', [
                    'name',
                    'title',
                    'description',
                ], $like);
            }
        });

        $countQuery = clone $query;
        $debug['final_match_count_before_limit'] = $countQuery->count();

        $selects = [
            'sub_topics.id as sub_topic_id',
            $this->selectColumn('sub_topics', ['topic_id'], 'topic_id'),
            $this->selectColumn('sub_topics', ['name', 'title'], 'sub_topic_title'),
            $this->selectColumn('sub_topics', ['description'], 'sub_topic_description'),
            $this->selectColumn('sub_topics', ['slug'], 'sub_topic_slug'),
            $this->selectColumn('sub_topics', ['lesson_type'], 'lesson_type'),
            $this->selectColumn('sub_topics', ['sort_order'], 'sub_topic_sort_order'),
            $this->selectColumn('sub_topics', ['is_active'], 'sub_topic_is_active'),
        ];

        if ($hasTopicsJoin) {
            $selects[] = DB::raw('topics.id as joined_topic_id');
            $selects[] = $this->selectColumn('topics', ['name', 'title'], 'topic_title');
            $selects[] = $this->selectColumn('topics', ['description'], 'topic_description');
            $selects[] = $this->selectColumn('topics', ['slug'], 'topic_slug');
            $selects[] = $this->selectColumn('topics', ['module_id'], 'module_id');
            $selects[] = $this->selectColumn('topics', ['sort_order'], 'topic_sort_order');
            $selects[] = $this->selectColumn('topics', ['is_active'], 'topic_is_active');
        } else {
            $selects[] = DB::raw('NULL as joined_topic_id');
            $selects[] = DB::raw("'' as topic_title");
            $selects[] = DB::raw("'' as topic_description");
            $selects[] = DB::raw("'' as topic_slug");
            $selects[] = DB::raw('NULL as module_id');
            $selects[] = DB::raw('NULL as topic_sort_order');
            $selects[] = DB::raw('NULL as topic_is_active');
        }

        if ($hasModulesJoin) {
            $selects[] = DB::raw('modules.id as joined_module_id');
            $selects[] = $this->selectColumn('modules', ['name', 'title'], 'module_title');
            $selects[] = $this->selectColumn('modules', ['slug'], 'module_slug');
            $selects[] = $this->selectColumn('modules', ['sort_order'], 'module_sort_order');
            $selects[] = $this->selectColumn('modules', ['is_active'], 'module_is_active');
        } else {
            $selects[] = DB::raw('NULL as joined_module_id');
            $selects[] = DB::raw("'' as module_title");
            $selects[] = DB::raw("'' as module_slug");
            $selects[] = DB::raw('NULL as module_sort_order');
            $selects[] = DB::raw('NULL as module_is_active');
        }

        if ($hasProgramJoin) {
            $selects[] = DB::raw('programs.id as program_id');
            $selects[] = $this->selectColumn('programs', ['name', 'title'], 'program_title');
            $selects[] = $this->selectColumn('programs', ['slug'], 'program_slug');
        } else {
            $selects[] = DB::raw('NULL as program_id');
            $selects[] = DB::raw("'' as program_title");
            $selects[] = DB::raw("'' as program_slug");
        }

        $query->select($selects);

        $this->applyOrdering($query, $keyword, $hasTopicsJoin, $hasModulesJoin);

        return $query
            ->limit($limit)
            ->get()
            ->map(fn ($row) => $this->formatLessonResult($row, $keyword, $fallbackProgram))
            ->values()
            ->all();
    }

    private function countRawSubTopicMatches(string $like): int
    {
        $query = DB::table('sub_topics');

        $query->where(function ($builder) use ($like) {
            $this->orWhereLowerLikeIfColumnExists($builder, 'sub_topics', [
                'name',
                'title',
                'description',
                'lesson_type',
            ], $like);
        });

        return $query->count();
    }

    private function formatLessonResult(object $row, string $keyword, ?array $fallbackProgram = null): array
    {
        $subTopicTitle = $row->sub_topic_title ?: 'Untitled Lesson';

        $description = $row->sub_topic_description
            ?: trim(($row->module_title ?: '-') . ' / ' . ($row->topic_title ?: '-'));

        $joinedProgramId = $row->program_id ?? null;
        $joinedProgramTitle = trim((string) ($row->program_title ?? ''));
        $joinedProgramSlug = trim((string) ($row->program_slug ?? ''));

        $programId = $joinedProgramId
            ?: ($fallbackProgram['id'] ?? null);

        $programTitle = $joinedProgramTitle
            ?: ($fallbackProgram['title'] ?? $this->defaultCourseTitle);

        $programSlug = $joinedProgramSlug
            ?: ($fallbackProgram['slug'] ?? $this->defaultCourseSlug);

        if (!$programSlug && $programTitle) {
            $programSlug = Str::slug($programTitle);
        }

        if (!$programSlug) {
            $programSlug = $this->defaultCourseSlug;
        }

        /**
         * Table sub_topics belum punya slug.
         * Jadi slug lesson dibuat dari name/title.
         */
        $lessonSlug = trim((string) ($row->sub_topic_slug ?? ''));

        if (!$lessonSlug) {
            $lessonSlug = Str::slug($subTopicTitle);
        }

        /**
         * Format wajib:
         * /learn/{courseSlug}/{lessonSlug}
         */
        $lessonUrl = "/learn/{$programSlug}/{$lessonSlug}";

        return [
            'id' => $row->sub_topic_id,

            'type' => 'lesson',
            'category' => 'lesson',
            'result_type' => 'lesson',
            'resultType' => 'lesson',

            'title' => $subTopicTitle,
            'name' => $subTopicTitle,
            'description' => $description ?: 'Open this lesson to continue learning.',
            'caption' => $description ?: 'Open this lesson to continue learning.',

            'lesson_type' => $row->lesson_type ?? null,
            'lessonType' => $row->lesson_type ?? null,

            'course_id' => $programId,
            'courseId' => $programId,
            'course_title' => $programTitle,
            'courseTitle' => $programTitle,
            'course_slug' => $programSlug,
            'courseSlug' => $programSlug,

            'module_id' => $row->joined_module_id ?? $row->module_id ?? null,
            'moduleId' => $row->joined_module_id ?? $row->module_id ?? null,
            'module_title' => $row->module_title ?: '-',
            'moduleTitle' => $row->module_title ?: '-',

            'topic_id' => $row->joined_topic_id ?? $row->topic_id ?? null,
            'topicId' => $row->joined_topic_id ?? $row->topic_id ?? null,
            'topic_title' => $row->topic_title ?: '-',
            'topicTitle' => $row->topic_title ?: '-',

            'sub_topic_id' => $row->sub_topic_id,
            'subTopicId' => $row->sub_topic_id,
            'sub_topic_title' => $subTopicTitle,
            'subTopicTitle' => $subTopicTitle,
            'sub_topic_slug' => $lessonSlug,
            'subTopicSlug' => $lessonSlug,

            'is_active' => $row->sub_topic_is_active ?? null,
            'isActive' => $row->sub_topic_is_active ?? null,

            'keyword' => $keyword,

            'to' => $lessonUrl,
            'url' => $lessonUrl,
            'path' => $lessonUrl,
            'learn_url' => $lessonUrl,
            'learnUrl' => $lessonUrl,
        ];
    }

    private function resolveStudent($user): ?object
    {
        if (!$user || !Schema::hasTable('students')) {
            return null;
        }

        if (Schema::hasColumn('students', 'user_id')) {
            $student = DB::table('students')
                ->where('user_id', $user->id)
                ->first();

            if ($student) {
                return $student;
            }
        }

        if (Schema::hasColumn('students', 'email') && filled($user->email ?? null)) {
            $student = DB::table('students')
                ->where('email', $user->email)
                ->first();

            if ($student) {
                return $student;
            }
        }

        return null;
    }

    private function resolveFallbackProgram(object $student): ?array
    {
        if (!Schema::hasTable('programs')) {
            return $this->defaultProgramPayload();
        }

        $programId = $this->resolveStudentProgramId($student);

        if (!$programId) {
            return $this->defaultProgramPayload();
        }

        $program = DB::table('programs')
            ->where('id', $programId)
            ->first();

        if (!$program) {
            return $this->defaultProgramPayload();
        }

        $title = $program->name
            ?? $program->title
            ?? $this->defaultCourseTitle;

        $slug = $program->slug
            ?? Str::slug($title)
            ?: $this->defaultCourseSlug;

        return [
            'id' => $program->id,
            'title' => $title,
            'slug' => $slug,
        ];
    }

    private function resolveStudentProgramId(object $student): ?int
    {
        if (!Schema::hasTable('student_enrollments')) {
            return null;
        }

        $query = DB::table('student_enrollments')
            ->where('student_enrollments.student_id', $student->id);

        $hasBatchesJoin = false;

        if (
            Schema::hasTable('batches')
            && Schema::hasColumn('student_enrollments', 'batch_id')
        ) {
            $query->leftJoin('batches', 'student_enrollments.batch_id', '=', 'batches.id');
            $hasBatchesJoin = true;
        }

        if (Schema::hasColumn('student_enrollments', 'status')) {
            $query->where(function ($builder) {
                $builder
                    ->whereNull('student_enrollments.status')
                    ->orWhereNotIn('student_enrollments.status', [
                        'inactive',
                        'cancelled',
                        'canceled',
                        'rejected',
                        'expired',
                        'dropped',
                        'failed',
                    ]);
            });
        }

        if (Schema::hasColumn('student_enrollments', 'access_status')) {
            $query->where(function ($builder) {
                $builder
                    ->whereNull('student_enrollments.access_status')
                    ->orWhereNotIn('student_enrollments.access_status', [
                        'inactive',
                        'revoked',
                        'closed',
                        'blocked',
                        'expired',
                    ]);
            });
        }

        $selects = [
            'student_enrollments.id',
        ];

        if (Schema::hasColumn('student_enrollments', 'program_id')) {
            $selects[] = 'student_enrollments.program_id as enrollment_program_id';
        } else {
            $selects[] = DB::raw('NULL as enrollment_program_id');
        }

        if ($hasBatchesJoin && Schema::hasColumn('batches', 'program_id')) {
            $selects[] = 'batches.program_id as batch_program_id';
        } else {
            $selects[] = DB::raw('NULL as batch_program_id');
        }

        $row = $query
            ->select($selects)
            ->orderByDesc('student_enrollments.id')
            ->first();

        if (!$row) {
            return null;
        }

        return $row->enrollment_program_id
            ?? $row->batch_program_id
            ?? null;
    }

    private function defaultProgramPayload(): array
    {
        return [
            'id' => null,
            'title' => $this->defaultCourseTitle,
            'slug' => $this->defaultCourseSlug,
        ];
    }

    private function selectColumn(string $table, array $columns, string $alias)
    {
        foreach ($columns as $column) {
            if (Schema::hasColumn($table, $column)) {
                return DB::raw("`{$table}`.`{$column}` as `{$alias}`");
            }
        }

        return DB::raw("'' as `{$alias}`");
    }

    private function orWhereLowerLikeIfColumnExists($builder, string $table, array $columns, string $like): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        foreach ($columns as $column) {
            if (Schema::hasColumn($table, $column)) {
                $builder->orWhereRaw("LOWER(`{$table}`.`{$column}`) LIKE ?", [$like]);
            }
        }
    }

    private function applyOrdering($query, string $keyword, bool $hasTopicsJoin, bool $hasModulesJoin): void
    {
        $lowerKeyword = mb_strtolower($keyword);
        $bindings = [];
        $cases = [];
        $priority = 0;

        foreach ([
            ['sub_topics', 'name', true],
            ['sub_topics', 'title', true],
            ['topics', 'name', $hasTopicsJoin],
            ['topics', 'title', $hasTopicsJoin],
            ['modules', 'name', $hasModulesJoin],
            ['modules', 'title', $hasModulesJoin],
        ] as [$table, $column, $isJoined]) {
            if ($isJoined && Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                $cases[] = "WHEN LOWER(`{$table}`.`{$column}`) LIKE ? THEN {$priority}";
                $bindings[] = $lowerKeyword . '%';
                $priority++;
            }
        }

        if (!empty($cases)) {
            $query->orderByRaw(
                'CASE ' . implode(' ', $cases) . ' ELSE 99 END',
                $bindings
            );
        }

        if ($hasModulesJoin && Schema::hasColumn('modules', 'sort_order')) {
            $query->orderBy('modules.sort_order');
        }

        if ($hasTopicsJoin && Schema::hasColumn('topics', 'sort_order')) {
            $query->orderBy('topics.sort_order');
        }

        if (Schema::hasColumn('sub_topics', 'sort_order')) {
            $query->orderBy('sub_topics.sort_order');
        }

        $query->orderBy('sub_topics.id');
    }
}