<?php

namespace App\Http\Controllers\Api\Lms;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AssignmentSubmission;
use App\Models\BatchAssignment;
use App\Models\BatchLearningQuiz;
use App\Models\LearningQuizAttempt;
use App\Models\Student;
use App\Models\StudentLessonProgress;
use App\Models\SubTopic;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class StudentDashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $user->loadMissing([
            'student.enrollments.program',
            'student.enrollments.batch.program',
        ]);

        if (!$this->isStudentUser($user) || !$user->student) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized student account.',
            ], 403);
        }

        $student = $user->student;
        $activeEnrollments = $this->getEligibleEnrollments($student);

        if ($activeEnrollments->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Student belum memiliki enrollment aktif.',
            ], 403);
        }

        $batchIds = $this->resolveBatchIds($activeEnrollments);
        $programIds = $this->resolveProgramIds($activeEnrollments);

        /*
         * Struktur curriculum FlexLabs sekarang sudah jelas:
         * programs -> program_stages -> modules -> topics -> sub_topics.
         * Jadi dashboard tidak perlu lagi mencari sub topic lewat banyak fallback path.
         */
        $subTopics = $this->getSubTopicsForPrograms($programIds);
        $progressRows = $this->getProgressRows($student, $subTopics);

        if ($subTopics->isEmpty()) {
            $subTopics = $this->getFallbackSubTopics($programIds, $batchIds);
            $progressRows = $this->getProgressRows($student, $subTopics);
        }

        $courses = collect($activeEnrollments->all())
            ->map(fn ($enrollment) => $this->formatCourse(
                enrollment: $enrollment,
                student: $student,
                allSubTopics: $subTopics,
                allProgressRows: $progressRows,
            ))
            ->filter()
            ->unique(fn (array $course) => $course['id'] ?? null)
            ->values();

        $pendingTasks = $this->getPendingTasks($student, $batchIds);
        $summary = $this->formatSummary($courses, $pendingTasks);

        $currentLesson = $this->resolveCurrentLesson(
            subTopics: $subTopics,
            progressRows: $progressRows
        );

        if (!$currentLesson) {
            $currentLesson = $this->resolveCurrentLessonFallback(
                student: $student,
                programIds: $programIds,
                batchIds: $batchIds
            );
        }

        return response()->json([
            'success' => true,
            'data' => [
                'student' => $this->formatStudent($student),
                'notification_count' => $pendingTasks->count(),

                'summary' => $summary,
                'stats' => $this->formatStats($summary, $courses),

                'weekly_summary' => $this->formatWeeklySummary(
                    student: $student,
                    progressRows: $progressRows
                ),

                'pending_tasks' => $pendingTasks->values()->toArray(),
                'current_lesson' => $currentLesson,

                'courses' => $courses->toArray(),

                /*
                 * Upcoming sessions sengaja tidak diambil dari DashboardController.
                 * Sumber jadwal student sekarang satu pintu:
                 * GET /api/lms/student/schedules
                 */
                'announcements' => $this->getDashboardAnnouncements(
                    programIds: $programIds,
                    batchIds: $batchIds
                )->toArray(),
            ],
        ]);
    }

    private function isStudentUser(User $user): bool
    {
        return ($user->user_type ?? null) === 'student'
            || ($user->role ?? null) === 'student'
            || (method_exists($user, 'student') && $user->student);
    }

    private function resolveBatchIds(Collection $enrollments): Collection
    {
        return $enrollments
            ->pluck('batch_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    private function resolveProgramIds(Collection $enrollments): Collection
    {
        return $enrollments
            ->map(fn ($enrollment) => $enrollment->program?->id
                ?? $enrollment->program_id
                ?? $enrollment->batch?->program?->id
                ?? $enrollment->batch?->program_id)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    private function getEligibleEnrollments(Student $student): Collection
    {
        return $student->enrollments
            ->filter(function ($enrollment) {
                $status = strtolower((string) ($enrollment->status ?? ''));
                $accessStatus = strtolower((string) ($enrollment->access_status ?? ''));

                $validStatuses = [
                    'active',
                    'enrolled',
                    'ongoing',
                    'paid',
                    'confirmed',
                    'approved',
                ];

                $validAccessStatuses = [
                    '',
                    'active',
                    'enabled',
                    'open',
                    'allowed',
                ];

                return in_array($status, $validStatuses, true)
                    && in_array($accessStatus, $validAccessStatuses, true);
            })
            ->values();
    }

    private function formatStudent(Student $student): array
    {
        return [
            'id' => $student->id,
            'name' => $student->full_name ?? $student->name ?? 'Student',
            'full_name' => $student->full_name ?? $student->name ?? 'Student',
            'email' => $student->email,
            'phone' => $student->phone,
            'city' => $student->city,
            'current_status' => $student->current_status,
            'goal' => $student->goal,
            'source' => $student->source,
            'status' => $student->status,
            'role' => 'FlexLabs Student',
            'avatar_url' => $this->getColumnValue($student, ['avatar_url', 'photo_url', 'profile_photo_url']),
        ];
    }

    private function formatSummary(Collection $courses, Collection $pendingTasks): array
    {
        $totalSubTopics = (int) $courses->sum('total_sub_topics');
        $completedSubTopics = (int) $courses->sum('completed_sub_topics');

        $progress = $totalSubTopics > 0
            ? $this->clampPercent(($completedSubTopics / $totalSubTopics) * 100)
            : 0;

        return [
            'progress_percentage' => $progress,
            'progressPercentage' => $progress,

            'completed_lessons' => $completedSubTopics,
            'completedLessons' => $completedSubTopics,
            'completed_sub_topics' => $completedSubTopics,
            'completedSubTopics' => $completedSubTopics,

            'total_lessons' => $totalSubTopics,
            'totalLessons' => $totalSubTopics,
            'total_sub_topics' => $totalSubTopics,
            'totalSubTopics' => $totalSubTopics,

            'pending_tasks_count' => $pendingTasks->count(),
            'pendingTasksCount' => $pendingTasks->count(),
        ];
    }

    private function formatStats(array $summary, Collection $courses): array
    {
        return [
            [
                'key' => 'active_courses',
                'label' => 'Active Courses',
                'value' => $courses->count(),
                'caption' => 'active enrollment',
                'type' => 'primary',
            ],
            [
                'key' => 'progress',
                'label' => 'Progress',
                'value' => ($summary['progress_percentage'] ?? 0) . '%',
                'caption' => 'overall course progress',
                'type' => 'warning',
            ],
            [
                'key' => 'completed_lessons',
                'label' => 'Completed',
                'value' => $summary['completed_sub_topics'] ?? 0,
                'caption' => 'of ' . ($summary['total_sub_topics'] ?? 0) . ' sub topics',
                'type' => 'success',
            ],
            [
                'key' => 'certificates',
                'label' => 'Certificates',
                'value' => 0,
                'caption' => 'not available yet',
                'type' => 'primary',
            ],
        ];
    }

    private function formatWeeklySummary(Student $student, Collection $progressRows): array
    {
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();

        $weeklyProgress = $progressRows
            ->filter(function ($progress) use ($startOfWeek, $endOfWeek) {
                $activityAt = $this->resolveProgressActivityAt($progress);

                return $activityAt
                    ? Carbon::parse($activityAt)->between($startOfWeek, $endOfWeek)
                    : false;
            })
            ->values();

        $watchSeconds = (int) $weeklyProgress->sum(function ($progress) {
            return $this->resolveWatchSeconds($progress);
        });

        $completedSubTopics = $progressRows
            ->filter(function ($progress) use ($startOfWeek, $endOfWeek) {
                if (!$this->isProgressCompleted($progress)) {
                    return false;
                }

                $completedAt = $this->getColumnValue($progress, ['completed_at'])
                    ?? $this->resolveProgressActivityAt($progress);

                return $completedAt
                    ? Carbon::parse($completedAt)->between($startOfWeek, $endOfWeek)
                    : false;
            })
            ->pluck('sub_topic_id')
            ->unique()
            ->count();

        $tasksDone = $this->countTasksDoneThisWeek($student, $startOfWeek, $endOfWeek);

        $latestActivity = $progressRows
            ->filter(fn ($progress) => $this->resolveProgressActivityAt($progress))
            ->sortByDesc(fn ($progress) => Carbon::parse($this->resolveProgressActivityAt($progress))->timestamp)
            ->first();

        $periodLabel = $startOfWeek->format('d M') . ' - ' . $endOfWeek->format('d M Y');
        $watchTime = $this->formatWatchTime($watchSeconds);
        $streak = $this->calculateCurrentStreak($student);
        $lastActiveLabel = $this->formatLastActiveLabel($latestActivity ? $this->resolveProgressActivityAt($latestActivity) : null);

        return [
            'period_label' => $periodLabel,
            'periodLabel' => $periodLabel,

            'total_watch_seconds' => $watchSeconds,
            'totalWatchSeconds' => $watchSeconds,
            'total_watch_minutes' => (int) round($watchSeconds / 60),
            'totalWatchMinutes' => (int) round($watchSeconds / 60),
            'total_watch_time_label' => $watchTime,
            'totalWatchTimeLabel' => $watchTime,
            'total_watch_time' => $watchTime,
            'totalWatchTime' => $watchTime,

            'completed_sub_topics' => $completedSubTopics,
            'completedSubTopics' => $completedSubTopics,
            'tasks_done' => $tasksDone,
            'tasksDone' => $tasksDone,

            'current_streak' => $streak,
            'currentStreak' => $streak,
            'last_active_label' => $lastActiveLabel,
            'lastActiveLabel' => $lastActiveLabel,
        ];
    }

    private function countTasksDoneThisWeek(Student $student, Carbon $startOfWeek, Carbon $endOfWeek): int
    {
        $assignmentCount = 0;
        $quizCount = 0;

        if (Schema::hasTable('assignment_submissions')) {
            $assignmentQuery = AssignmentSubmission::query()
                ->where('student_id', $student->id)
                ->whereBetween('updated_at', [$startOfWeek, $endOfWeek]);

            if (Schema::hasColumn('assignment_submissions', 'status')) {
                $assignmentQuery->whereIn('status', [
                    'submitted',
                    'late',
                    'reviewed',
                    'returned',
                    'graded',
                    'completed',
                ]);
            }

            $assignmentCount = $assignmentQuery->count();
        }

        if (Schema::hasTable('learning_quiz_attempts')) {
            $quizQuery = LearningQuizAttempt::query()
                ->where('student_id', $student->id)
                ->whereBetween('updated_at', [$startOfWeek, $endOfWeek]);

            if (Schema::hasColumn('learning_quiz_attempts', 'status')) {
                $quizQuery->whereIn('status', [
                    'submitted',
                    'completed',
                    'finished',
                    'passed',
                    'done',
                    'reviewed',
                    'graded',
                ]);
            }

            $quizCount = $quizQuery->count();
        }

        return $assignmentCount + $quizCount;
    }

    private function formatWatchTime(int|float $seconds): string
    {
        $seconds = (int) round($seconds);

        if ($seconds <= 0) {
            return '0m';
        }

        $hours = intdiv($seconds, 3600);
        $minutes = (int) floor(($seconds % 3600) / 60);

        if ($hours > 0) {
            return $minutes > 0
                ? "{$hours}h {$minutes}m"
                : "{$hours}h";
        }

        return max(1, $minutes) . 'm';
    }

    private function calculateCurrentStreak(Student $student): int
    {
        $progressTable = $this->studentLessonProgressTable();

        if (!$progressTable) {
            return 0;
        }

        $dateColumn = Schema::hasColumn($progressTable, 'last_watched_at')
            ? 'last_watched_at'
            : 'updated_at';

        $dates = DB::table($progressTable)
            ->where('student_id', $student->id)
            ->whereNotNull($dateColumn)
            ->whereDate($dateColumn, '>=', now()->subDays(60)->toDateString())
            ->orderByDesc($dateColumn)
            ->pluck($dateColumn)
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->unique()
            ->values();

        if ($dates->isEmpty()) {
            return 0;
        }

        $streak = 0;
        $cursor = now()->toDateString();

        foreach ($dates as $date) {
            if ($date === $cursor) {
                $streak++;
                $cursor = Carbon::parse($cursor)->subDay()->toDateString();
                continue;
            }

            if ($streak === 0 && $date === Carbon::parse($cursor)->subDay()->toDateString()) {
                $streak++;
                $cursor = Carbon::parse($date)->subDay()->toDateString();
                continue;
            }

            break;
        }

        return $streak;
    }

    private function formatLastActiveLabel($date): string
    {
        if (!$date) {
            return 'No activity yet';
        }

        return 'Last active ' . Carbon::parse($date)->diffForHumans();
    }


    private function canUseProgramStageCurriculumPath(): bool
    {
        return Schema::hasTable('sub_topics')
            && Schema::hasTable('topics')
            && Schema::hasTable('modules')
            && Schema::hasTable('program_stages')
            && Schema::hasColumn('sub_topics', 'topic_id')
            && Schema::hasColumn('topics', 'module_id')
            && Schema::hasColumn('modules', 'program_stage_id')
            && Schema::hasColumn('program_stages', 'program_id');
    }

    private function subTopicDashboardColumns(): array
    {
        $candidateColumns = [
            'id',
            'topic_id',
            'name',
            'description',
            'sort_order',
            'is_active',
            'status',
            'slug',
            'lesson_type',
            'video_url',
            'video_duration_minutes',
            'thumbnail_url',
            'video_provider',
            'video_disk',
            'video_path',
            'video_mime',
            'video_size',
            'video_duration_seconds',
            'duration_minutes',
            'duration_seconds',
            'created_at',
            'updated_at',
        ];

        return collect($candidateColumns)
            ->filter(fn ($column) => Schema::hasColumn('sub_topics', $column))
            ->map(fn ($column) => 'sub_topics.' . $column)
            ->values()
            ->all();
    }

    private function applyCurriculumVisibilityFilters($query): void
    {
        $this->applySubTopicVisibilityFilters($query);

        foreach ([
            ['topics', 'topics'],
            ['modules', 'modules'],
            ['program_stages', 'program_stages'],
        ] as [$table, $alias]) {
            if (Schema::hasColumn($table, 'is_active')) {
                $query->where(function ($activeQuery) use ($alias) {
                    $activeQuery->where($alias . '.is_active', true)
                        ->orWhereNull($alias . '.is_active');
                });
            }

            if (Schema::hasColumn($table, 'status')) {
                $query->where(function ($statusQuery) use ($alias) {
                    $statusQuery->whereNull($alias . '.status')
                        ->orWhereNotIn($alias . '.status', ['inactive', 'archived', 'deleted']);
                });
            }
        }
    }

    private function applySubTopicVisibilityFilters($query): void
    {
        if (Schema::hasColumn('sub_topics', 'is_active')) {
            $query->where(function ($activeQuery) {
                $activeQuery->where('sub_topics.is_active', true)
                    ->orWhereNull('sub_topics.is_active');
            });
        }

        if (Schema::hasColumn('sub_topics', 'status')) {
            $query->where(function ($statusQuery) {
                $statusQuery->whereNull('sub_topics.status')
                    ->orWhereNotIn('sub_topics.status', ['inactive', 'archived', 'deleted']);
            });
        }
    }

    private function subTopicBelongsToProgram($subTopic, int $programId): bool
    {
        $topic = $subTopic->topic ?? null;
        $module = $topic?->module ?? null;
        $stage = $module?->stage ?? null;

        return (int) ($stage?->program_id ?? $stage?->program?->id ?? 0) === $programId;
    }

    private function getSubTopicsForPrograms(Collection $programIds): Collection
    {
        if (!$this->canUseProgramStageCurriculumPath()) {
            return collect();
        }

        $programIds = $programIds
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($programIds->isEmpty()) {
            return collect();
        }

        $query = SubTopic::query()
            ->select($this->subTopicDashboardColumns())
            ->with($this->resolveSubTopicRelations())
            ->join('topics', 'topics.id', '=', 'sub_topics.topic_id')
            ->join('modules', 'modules.id', '=', 'topics.module_id')
            ->join('program_stages', 'program_stages.id', '=', 'modules.program_stage_id')
            ->whereIn('program_stages.program_id', $programIds->all());

        $this->applyCurriculumVisibilityFilters($query);

        return $query
            ->orderBy('program_stages.sort_order')
            ->orderBy('program_stages.name')
            ->orderBy('modules.sort_order')
            ->orderBy('modules.name')
            ->orderBy('topics.sort_order')
            ->orderBy('topics.name')
            ->orderBy('sub_topics.sort_order')
            ->orderBy('sub_topics.name')
            ->orderBy('sub_topics.id')
            ->get()
            ->unique(fn ($subTopic) => (int) $subTopic->id)
            ->values();
    }


    private function querySubTopicsByIds(Collection $subTopicIds, bool $applyVisibilityFilters = true)
    {
        $subTopicIds = $subTopicIds
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $query = SubTopic::query()
            ->select($this->subTopicDashboardColumns())
            ->with($this->resolveSubTopicRelations())
            ->whereIn('sub_topics.id', $subTopicIds->all());

        if ($applyVisibilityFilters) {
            $this->applySubTopicVisibilityFilters($query);
        }

        return $query;
    }


    private function resolveSubTopicIdsByProgram(Collection $programIds): Collection
    {
        if (!$this->canUseProgramStageCurriculumPath()) {
            return collect();
        }

        $programIds = $programIds
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($programIds->isEmpty()) {
            return collect();
        }

        return DB::table('sub_topics')
            ->join('topics', 'topics.id', '=', 'sub_topics.topic_id')
            ->join('modules', 'modules.id', '=', 'topics.module_id')
            ->join('program_stages', 'program_stages.id', '=', 'modules.program_stage_id')
            ->whereIn('program_stages.program_id', $programIds->all())
            ->pluck('sub_topics.id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }


    private function candidateStageTables(): array
    {
        $tables = [];

        foreach ([
            \App\Models\Stage::class,
            \App\Models\ProgramStage::class,
            \App\Models\CurriculumStage::class,
        ] as $modelClass) {
            if (!class_exists($modelClass)) {
                continue;
            }

            try {
                $tables[] = (new $modelClass())->getTable();
            } catch (\Throwable) {
                // ignore and use fallback names below
            }
        }

        return collect($tables)
            ->merge([
                'stages',
                'program_stages',
                'curriculum_stages',
                'learning_stages',
            ])
            ->filter()
            ->unique()
            ->values()
            ->all();
    }


    private function resolveSubTopicRelations(): array
    {
        if (!method_exists(SubTopic::class, 'topic')) {
            return [];
        }

        return [
            'topic:id,module_id,name,description,sort_order,is_active',
            'topic.module:id,program_stage_id,name,description,sort_order,is_active',
            'topic.module.stage:id,program_id,name,slug,description,sort_order,is_active',
            'topic.module.stage.program:id,name,slug,description,is_active',
        ];
    }

    private function canUseStageProgramPath(): bool
    {
        if (
            !method_exists(SubTopic::class, 'topic')
            || !class_exists(\App\Models\Topic::class)
            || !class_exists(\App\Models\Module::class)
            || !method_exists(\App\Models\Topic::class, 'module')
            || !method_exists(\App\Models\Module::class, 'stage')
            || !Schema::hasTable('modules')
        ) {
            return false;
        }

        $hasStageForeignKey = collect([
            'stage_id',
            'program_stage_id',
            'curriculum_stage_id',
        ])->contains(fn ($column) => Schema::hasColumn('modules', $column));

        if (!$hasStageForeignKey) {
            return false;
        }

        foreach ($this->candidateStageTables() as $stageTable) {
            if (
                Schema::hasTable($stageTable)
                && Schema::hasColumn($stageTable, 'program_id')
            ) {
                return true;
            }
        }

        return false;
    }


    private function canUseModuleProgramPath(): bool
    {
        return method_exists(SubTopic::class, 'topic')
            && class_exists(\App\Models\Topic::class)
            && class_exists(\App\Models\Module::class)
            && method_exists(\App\Models\Topic::class, 'module')
            && method_exists(\App\Models\Module::class, 'program')
            && Schema::hasTable('modules')
            && Schema::hasColumn('modules', 'program_id');
    }

    private function formatSubTopicSortKey($subTopic): string
    {
        $topic = $subTopic->topic ?? null;
        $module = $topic?->module ?? null;
        $stage = $module?->stage ?? null;

        return sprintf(
            '%06d-%06d-%06d-%06d-%06d',
            (int) ($stage?->sort_order ?? 999999),
            (int) ($module?->sort_order ?? 999999),
            (int) ($topic?->sort_order ?? 999999),
            (int) ($subTopic->sort_order ?? 999999),
            (int) ($subTopic->id ?? 999999)
        );
    }

    private function getProgressRows(Student $student, Collection $subTopics): Collection
    {
        $progressTable = $this->studentLessonProgressTable();

        if (!$progressTable || $subTopics->isEmpty()) {
            return collect();
        }

        if (!Schema::hasColumn($progressTable, 'student_id') || !Schema::hasColumn($progressTable, 'sub_topic_id')) {
            return collect();
        }

        $subTopicIds = $subTopics
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($subTopicIds->isEmpty()) {
            return collect();
        }

        $query = DB::table($progressTable)
            ->where('student_id', $student->id)
            ->whereIn('sub_topic_id', $subTopicIds->all());

        foreach (['last_watched_at', 'completed_at', 'updated_at', 'created_at'] as $column) {
            if (Schema::hasColumn($progressTable, $column)) {
                $query->orderByDesc($column);
                break;
            }
        }

        return $query->get();
    }

    private function formatCourse($enrollment, Student $student, ?Collection $allSubTopics = null, ?Collection $allProgressRows = null): ?array
    {
        $program = $enrollment->program ?? $enrollment->batch?->program;

        if (!$program) {
            return null;
        }

        $programId = (int) $program->id;

        $subTopics = $allSubTopics
            ? $allSubTopics
                ->filter(fn ($subTopic) => $this->subTopicBelongsToProgram($subTopic, $programId))
                ->values()
            : $this->getSubTopicsForPrograms(collect([$programId]));

        $subTopicIds = $subTopics
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $progressRows = $allProgressRows
            ? $allProgressRows
                ->filter(fn ($progress) => $subTopicIds->contains((int) $this->getColumnValue($progress, ['sub_topic_id'])))
                ->values()
            : $this->getProgressRows($student, $subTopics);

        $totalSubTopics = $subTopics->count();

        $completedSubTopics = $progressRows
            ->filter(fn ($progress) => $this->isProgressCompleted($progress))
            ->pluck('sub_topic_id')
            ->unique()
            ->count();

        $progress = $totalSubTopics > 0
            ? $this->clampPercent(($completedSubTopics / $totalSubTopics) * 100)
            : 0;

        $nextLesson = $this->resolveNextLessonTitle($subTopics, $progressRows);
        $programSlug = $this->resolveProgramSlug($program);

        return [
            'id' => $program->id,
            'title' => $program->name ?? $program->title ?? 'Untitled Course',
            'name' => $program->name ?? $program->title ?? 'Untitled Course',
            'slug' => $programSlug,

            'batch_id' => $enrollment->batch_id,
            'batch_name' => $enrollment->batch?->name,

            'next_lesson' => $nextLesson,
            'nextLesson' => $nextLesson,
            'next_sub_topic' => $nextLesson,
            'nextSubTopic' => $nextLesson,

            'progress' => $progress,
            'progress_percentage' => $progress,
            'progressPercentage' => $progress,

            'completed_sub_topics' => $completedSubTopics,
            'completedSubTopics' => $completedSubTopics,
            'total_sub_topics' => $totalSubTopics,
            'totalSubTopics' => $totalSubTopics,

            'completed_lessons' => $completedSubTopics,
            'completedLessons' => $completedSubTopics,
            'total_lessons' => $totalSubTopics,
            'totalLessons' => $totalSubTopics,

            'course_url' => '/my-courses/' . $programSlug,
            'courseUrl' => '/my-courses/' . $programSlug,
        ];
    }

    private function studentLessonProgressTable(): ?string
    {
        try {
            $modelTable = (new StudentLessonProgress())->getTable();

            if ($modelTable && Schema::hasTable($modelTable)) {
                return $modelTable;
            }
        } catch (\Throwable) {
            // ignore and use fallback names below
        }

        foreach (['student_lesson_progresses', 'student_lesson_progress'] as $table) {
            if (Schema::hasTable($table)) {
                return $table;
            }
        }

        return null;
    }

    private function mergeProgressRows(Collection $primaryRows, Collection $extraRows): Collection
    {
        return $primaryRows
            ->merge($extraRows)
            ->filter(fn ($progress) => $this->getColumnValue($progress, ['sub_topic_id']) !== null)
            ->sortByDesc(function ($progress) {
                $activityAt = $this->resolveProgressActivityAt($progress);

                if ($activityAt) {
                    return Carbon::parse($activityAt)->timestamp;
                }

                $updatedAt = $this->getColumnValue($progress, ['updated_at']);

                return $updatedAt
                    ? Carbon::parse($updatedAt)->timestamp
                    : 0;
            })
            ->unique(function ($progress) {
                $id = $this->getColumnValue($progress, ['id']);

                return $id !== null
                    ? 'id:' . $id
                    : 'sub_topic:' . (int) $this->getColumnValue($progress, ['sub_topic_id']);
            })
            ->values();
    }

    private function mergeIncompleteProgressSubTopics(Collection $subTopics, Collection $progressRows): Collection
    {
        $existingIds = $subTopics
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        $missingIncompleteSubTopicIds = $progressRows
            ->filter(fn ($progress) => !$this->isProgressCompleted($progress))
            ->map(fn ($progress) => (int) $this->getColumnValue($progress, ['sub_topic_id']))
            ->filter()
            ->reject(fn ($id) => $existingIds->contains((int) $id))
            ->unique()
            ->values();

        if ($missingIncompleteSubTopicIds->isEmpty()) {
            return $subTopics
                ->sortBy(fn ($subTopic) => $this->formatSubTopicSortKey($subTopic))
                ->values();
        }

        return $subTopics
            ->merge($this->getSubTopicsByIds($missingIncompleteSubTopicIds))
            ->unique(fn ($subTopic) => (int) $subTopic->id)
            ->sortBy(fn ($subTopic) => $this->formatSubTopicSortKey($subTopic))
            ->values();
    }

    private function resolveNextLessonTitle(Collection $subTopics, Collection $progressRows): string
    {
        if ($subTopics->isEmpty()) {
            return 'No next lesson';
        }

        $progressBySubTopic = $this->mapLatestProgressBySubTopic($progressRows);

        $nextSubTopic = $subTopics
            ->first(function ($subTopic) use ($progressBySubTopic) {
                $progress = $progressBySubTopic->get((int) $subTopic->id);

                return !$this->isProgressCompleted($progress);
            });

        if (!$nextSubTopic) {
            return 'All lessons completed';
        }

        return $this->getColumnValue($nextSubTopic, ['name', 'title']) ?: 'Untitled Lesson';
    }

    private function resolveCurrentLesson(Collection $subTopics, Collection $progressRows, bool $allowCompletedFallback = false): ?array
    {
        if ($subTopics->isEmpty()) {
            return null;
        }

        $progressBySubTopic = $this->mapLatestProgressBySubTopic($progressRows);

        $currentSubTopic = $subTopics
            ->filter(function ($subTopic) use ($progressBySubTopic) {
                $progress = $progressBySubTopic->get((int) $subTopic->id);

                return $progress
                    && !$this->isProgressCompleted($progress)
                    && $this->resolveProgressActivityAt($progress);
            })
            ->sortByDesc(function ($subTopic) use ($progressBySubTopic) {
                $progress = $progressBySubTopic->get((int) $subTopic->id);
                $activityAt = $this->resolveProgressActivityAt($progress);

                return $activityAt
                    ? Carbon::parse($activityAt)->timestamp
                    : 0;
            })
            ->first();

        if (!$currentSubTopic) {
            $currentSubTopic = $subTopics->first(function ($subTopic) use ($progressBySubTopic) {
                $progress = $progressBySubTopic->get((int) $subTopic->id);

                return !$this->isProgressCompleted($progress);
            });
        }

        if (!$currentSubTopic && $allowCompletedFallback) {
            $currentSubTopic = $subTopics->last();
        }

        if (!$currentSubTopic) {
            return null;
        }

        return $this->formatCurrentLessonPayload(
            currentSubTopic: $currentSubTopic,
            progress: $progressBySubTopic->get((int) $currentSubTopic->id)
        );
    }

    private function mapLatestProgressBySubTopic(Collection $progressRows): Collection
    {
        return $progressRows
            ->filter(fn ($progress) => $this->getColumnValue($progress, ['sub_topic_id']) !== null)
            ->sortByDesc(function ($progress) {
                $activityAt = $this->resolveProgressActivityAt($progress);

                if ($activityAt) {
                    return Carbon::parse($activityAt)->timestamp;
                }

                $updatedAt = $this->getColumnValue($progress, ['updated_at']);

                return $updatedAt
                    ? Carbon::parse($updatedAt)->timestamp
                    : 0;
            })
            ->unique(fn ($progress) => (int) $this->getColumnValue($progress, ['sub_topic_id']))
            ->keyBy(fn ($progress) => (int) $this->getColumnValue($progress, ['sub_topic_id']));
    }

    private function formatCurrentLessonPayload($currentSubTopic, $progress = null): array
    {
        $topic = $currentSubTopic->topic ?? null;
        $module = $topic?->module ?? null;
        $stage = $module?->stage ?? null;

        $program = $stage?->program
            ?? $module?->program
            ?? $this->resolveProgramFromDatabase($module);

        $lessonType = $this->resolveSubTopicLessonType($currentSubTopic);
        $isLiveSession = $this->isLiveSessionLessonType($lessonType);

        $progressPercentage = $this->resolveProgressPercentage($progress, $currentSubTopic);
        $isCompleted = $this->isProgressCompleted($progress);

        if ($isCompleted) {
            $progressPercentage = 100;
        }

        $courseSlug = $this->resolveProgramSlug($program);
        $lessonSlug = $this->resolveSubTopicSlug($currentSubTopic);
        $learnUrl = '/learn/' . $courseSlug . '/' . $lessonSlug;

        /*
        |--------------------------------------------------------------------------
        | Live session bukan video.
        |--------------------------------------------------------------------------
        | Dashboard perlu tahu ini supaya frontend tidak menampilkan icon play,
        | watched progress, last watched / video position, atau durasi video.
        */
        $videoUrl = $isLiveSession
            ? null
            : $this->resolveSubTopicVideoUrl($currentSubTopic);

        $thumbnailUrl = $this->resolveSubTopicThumbnailUrl($currentSubTopic, $videoUrl);

        $durationMinutes = $isLiveSession
            ? null
            : $this->resolveDurationMinutes($currentSubTopic, $progress);

        $durationSeconds = $isLiveSession
            ? 0
            : $this->resolveDurationSeconds($currentSubTopic, $progress);

        $lastPositionSeconds = $isLiveSession
            ? 0
            : $this->resolveLastPositionSeconds($progress);

        $hasVideo = !$isLiveSession && (
            $lessonType === 'video'
            || !blank($videoUrl)
            || $durationSeconds > 0
        );

        $typeLabel = $isLiveSession
            ? 'Live Session'
            : ($hasVideo ? 'Video Lesson' : 'Learning Material');

        $heroLabel = $isLiveSession
            ? 'Live Session'
            : 'Current Lesson';

        $actionLabel = $isLiveSession
            ? 'Open Session'
            : 'Continue Learning';

        $statusLabel = $isCompleted
            ? 'Completed'
            : ($isLiveSession ? 'Ready' : 'In Progress');

        return [
            'id' => $currentSubTopic->id,
            'sub_topic_id' => $currentSubTopic->id,
            'subTopicId' => $currentSubTopic->id,

            'title' => $this->getColumnValue($currentSubTopic, ['name', 'title']) ?: 'Untitled Lesson',
            'description' => $this->getColumnValue($currentSubTopic, ['description', 'summary']) ?: 'Continue your current learning activity.',

            /*
            |--------------------------------------------------------------------------
            | Lesson type flags for frontend dashboard.
            |--------------------------------------------------------------------------
            */
            'lesson_type' => $lessonType,
            'lessonType' => $lessonType,
            'type' => $lessonType,
            'session_type' => $lessonType,
            'sessionType' => $lessonType,

            'type_label' => $typeLabel,
            'typeLabel' => $typeLabel,

            'hero_label' => $heroLabel,
            'heroLabel' => $heroLabel,

            'action_label' => $actionLabel,
            'actionLabel' => $actionLabel,

            'is_live_session' => $isLiveSession,
            'isLiveSession' => $isLiveSession,

            'has_video' => $hasVideo,
            'hasVideo' => $hasVideo,

            /*
            |--------------------------------------------------------------------------
            | Duration / media.
            |--------------------------------------------------------------------------
            */
            'duration_minutes' => $durationMinutes,
            'durationMinutes' => $durationMinutes,
            'duration_seconds' => $durationSeconds,
            'durationSeconds' => $durationSeconds,

            'thumbnail_url' => $thumbnailUrl,
            'thumbnailUrl' => $thumbnailUrl,
            'image_url' => $thumbnailUrl,
            'imageUrl' => $thumbnailUrl,

            'video_url' => $videoUrl,
            'videoUrl' => $videoUrl,

            /*
            |--------------------------------------------------------------------------
            | Curriculum info.
            |--------------------------------------------------------------------------
            */
            'module' => $this->getColumnValue($module, ['name', 'title']) ?: '-',
            'module_title' => $this->getColumnValue($module, ['name', 'title']) ?: '-',
            'moduleTitle' => $this->getColumnValue($module, ['name', 'title']) ?: '-',

            'topic' => $this->getColumnValue($topic, ['name', 'title']) ?: '-',
            'topic_title' => $this->getColumnValue($topic, ['name', 'title']) ?: '-',
            'topicTitle' => $this->getColumnValue($topic, ['name', 'title']) ?: '-',

            /*
            |--------------------------------------------------------------------------
            | Status.
            |--------------------------------------------------------------------------
            */
            'status' => $statusLabel,
            'status_label' => $statusLabel,
            'statusLabel' => $statusLabel,
            'is_completed' => $isCompleted,
            'isCompleted' => $isCompleted,

            /*
            |--------------------------------------------------------------------------
            | Progress.
            |--------------------------------------------------------------------------
            | Untuk live session, progress umum tetap bisa 100 kalau student sudah
            | manual mark completed. Video/watched progress dikirim 0 supaya card
            | dashboard tidak menampilkan watched UI.
            */
            'progress_percentage' => $progressPercentage,
            'progressPercentage' => $progressPercentage,

            'video_progress_percentage' => $isLiveSession ? 0 : $progressPercentage,
            'videoProgressPercentage' => $isLiveSession ? 0 : $progressPercentage,
            'watched_percentage' => $isLiveSession ? 0 : $progressPercentage,
            'watchedPercentage' => $isLiveSession ? 0 : $progressPercentage,

            'last_position_seconds' => $lastPositionSeconds,
            'lastPositionSeconds' => $lastPositionSeconds,

            /*
            |--------------------------------------------------------------------------
            | URLs.
            |--------------------------------------------------------------------------
            */
            'course_slug' => $courseSlug,
            'courseSlug' => $courseSlug,
            'lesson_slug' => $lessonSlug,
            'lessonSlug' => $lessonSlug,

            'learn_url' => $learnUrl,
            'learnUrl' => $learnUrl,
        ];
    }


    private function resolveCurrentLessonFallback(Student $student, Collection $programIds, Collection $batchIds): ?array
    {
        $progressRows = $this->getAllStudentProgressRows($student);

        if ($progressRows->isNotEmpty()) {
            $subTopicIds = $progressRows
                ->map(fn ($progress) => (int) $this->getColumnValue($progress, ['sub_topic_id']))
                ->filter()
                ->unique()
                ->values();

            $subTopics = $this->getSubTopicsByIds($subTopicIds);
            $subTopics = $this->mergeIncompleteProgressSubTopics($subTopics, $progressRows);
            $currentLesson = $this->resolveCurrentLesson($subTopics, $progressRows);

            if ($currentLesson) {
                return $currentLesson;
            }
        }

        $fallbackSubTopics = $this->getFallbackSubTopics($programIds, $batchIds);
        $currentLesson = $this->resolveCurrentLesson($fallbackSubTopics, $progressRows);

        if ($currentLesson) {
            return $currentLesson;
        }

        return null;
    }

    private function getAllStudentProgressRows(Student $student): Collection
    {
        $progressTable = $this->studentLessonProgressTable();

        if (!$progressTable) {
            return collect();
        }

        if (!Schema::hasColumn($progressTable, 'student_id')) {
            return collect();
        }

        $query = DB::table($progressTable)
            ->where('student_id', $student->id);

        if (Schema::hasColumn($progressTable, 'sub_topic_id')) {
            $query->whereNotNull('sub_topic_id');
        }

        foreach (['last_watched_at', 'completed_at', 'updated_at', 'created_at'] as $column) {
            if (Schema::hasColumn($progressTable, $column)) {
                $query->orderByDesc($column);
                break;
            }
        }

        return $query
            ->limit(500)
            ->get();
    }

    private function getSubTopicsByIds(Collection $subTopicIds): Collection
    {
        if ($subTopicIds->isEmpty() || !Schema::hasTable('sub_topics')) {
            return collect();
        }

        return $this->querySubTopicsByIds($subTopicIds, false)
            ->get()
            ->sortBy(fn ($subTopic) => $this->formatSubTopicSortKey($subTopic))
            ->values();
    }

    private function getFallbackSubTopics(Collection $programIds, Collection $batchIds): Collection
    {
        $subTopics = $this->getSubTopicsForPrograms($programIds);

        if ($subTopics->isNotEmpty()) {
            return $subTopics;
        }

        if (!Schema::hasTable('sub_topics')) {
            return collect();
        }

        /*
         * Fallback hanya boleh berdasarkan batch.
         * Jangan return semua sub_topics global karena timeline student bisa tercampur program lain.
         */
        if (!Schema::hasColumn('sub_topics', 'batch_id') || $batchIds->isEmpty()) {
            return collect();
        }

        $query = SubTopic::query();
        $relations = $this->resolveSubTopicRelations();

        if (!empty($relations)) {
            $query->with($relations);
        }

        $query->whereIn('sub_topics.batch_id', $batchIds);

        if (Schema::hasColumn('sub_topics', 'is_active')) {
            $query->orderByRaw('CASE WHEN sub_topics.is_active = 1 THEN 0 ELSE 1 END');
        }

        foreach (['sort_order', 'order', 'position', 'id'] as $column) {
            if (Schema::hasColumn('sub_topics', $column)) {
                $query->orderBy('sub_topics.' . $column);
            }
        }

        return $query
            ->get()
            ->sortBy(fn ($subTopic) => $this->formatSubTopicSortKey($subTopic))
            ->values();
    }


    private function resolveProgramFromDatabase($module)
    {
        if (!$module || !isset($module->id) || !Schema::hasTable('programs')) {
            return null;
        }

        if (
            Schema::hasTable('program_stages')
            && Schema::hasTable('modules')
            && Schema::hasColumn('modules', 'program_stage_id')
            && Schema::hasColumn('program_stages', 'program_id')
        ) {
            return DB::table('modules')
                ->join('program_stages', 'program_stages.id', '=', 'modules.program_stage_id')
                ->join('programs', 'programs.id', '=', 'program_stages.program_id')
                ->where('modules.id', $module->id)
                ->select('programs.*')
                ->first();
        }

        return null;
    }


    private function resolveProgramSlug($program): string
    {
        if (!$program) {
            return 'course';
        }

        $slug = $program->slug ?? null;

        if (!blank($slug)) {
            return Str::slug((string) $slug);
        }

        $name = $program->name ?? $program->title ?? null;

        if (!blank($name)) {
            return Str::slug((string) $name);
        }

        return isset($program->id)
            ? (string) $program->id
            : 'course';
    }

    private function resolveSubTopicSlug($subTopic): string
    {
        if (!$subTopic) {
            return 'lesson';
        }

        $slug = $this->getColumnValue($subTopic, ['slug']);

        if ($slug) {
            return Str::slug((string) $slug);
        }

        $title = $this->getColumnValue($subTopic, ['name', 'title']);

        if ($title) {
            return Str::slug((string) $title);
        }

        return isset($subTopic->id)
            ? (string) $subTopic->id
            : 'lesson';
    }

    private function getPendingTasks(Student $student, Collection $batchIds): Collection
    {
        $assignments = collect($this->getPendingAssignments($student, $batchIds)->all());
        $quizzes = collect($this->getPendingQuizzes($student, $batchIds)->all());

        return $assignments
            ->merge($quizzes)
            ->sortBy(fn (array $task) => $task['sort_deadline'] ?? now()->addYears(10)->timestamp)
            ->take(5)
            ->values()
            ->map(function (array $task) {
                unset($task['sort_deadline']);

                return $task;
            });
    }

    private function getPendingAssignments(Student $student, Collection $batchIds): Collection
    {
        if ($batchIds->isEmpty() || !Schema::hasTable('batch_assignments')) {
            return collect();
        }

        $assignments = BatchAssignment::query()
            ->with($this->resolveBatchAssignmentRelations())
            ->whereIn('batch_id', $batchIds)
            ->when(Schema::hasColumn('batch_assignments', 'is_active'), fn ($query) => $query->where('is_active', true))
            ->get();

        $submittedRows = $this->getSubmittedAssignmentRows($student, $assignments);

        return collect(
            $assignments
                ->reject(fn (BatchAssignment $assignment) => $this->assignmentHasSubmittedRow($assignment, $submittedRows))
                ->map(fn (BatchAssignment $assignment) => $this->formatAssignmentTask($assignment))
                ->all()
        )->values();
    }

    private function resolveBatchAssignmentRelations(): array
    {
        $relations = [];

        if (method_exists(BatchAssignment::class, 'assignment')) {
            $relations[] = 'assignment';
        }

        if (method_exists(BatchAssignment::class, 'batch')) {
            $relations[] = 'batch.program';
        }

        return $relations;
    }


    private function getSubmittedAssignmentRows(Student $student, Collection $assignments): Collection
    {
        if ($assignments->isEmpty() || !Schema::hasTable('assignment_submissions')) {
            return collect();
        }

        $query = AssignmentSubmission::query()
            ->where('student_id', $student->id);

        if (Schema::hasColumn('assignment_submissions', 'status')) {
            $query->whereIn('status', [
                'submitted',
                'late',
                'reviewed',
                'returned',
                'graded',
                'completed',
            ]);
        }

        $query->where(function ($submissionQuery) use ($assignments) {
            $hasCondition = false;

            if (Schema::hasColumn('assignment_submissions', 'batch_assignment_id')) {
                $ids = $assignments->pluck('id')->filter()->unique()->values();

                if ($ids->isNotEmpty()) {
                    $submissionQuery->whereIn('batch_assignment_id', $ids->all());
                    $hasCondition = true;
                }
            }

            if (Schema::hasColumn('assignment_submissions', 'assignment_id')) {
                $ids = $assignments->pluck('assignment_id')->filter()->unique()->values();

                if ($ids->isNotEmpty()) {
                    $hasCondition
                        ? $submissionQuery->orWhereIn('assignment_id', $ids->all())
                        : $submissionQuery->whereIn('assignment_id', $ids->all());

                    $hasCondition = true;
                }
            }

            if (Schema::hasColumn('assignment_submissions', 'batch_id')) {
                $ids = $assignments->pluck('batch_id')->filter()->unique()->values();

                if ($ids->isNotEmpty()) {
                    $hasCondition
                        ? $submissionQuery->orWhereIn('batch_id', $ids->all())
                        : $submissionQuery->whereIn('batch_id', $ids->all());

                    $hasCondition = true;
                }
            }

            if (!$hasCondition) {
                $submissionQuery->whereRaw('1 = 0');
            }
        });

        return $query->get();
    }

    private function assignmentHasSubmittedRow(BatchAssignment $assignment, Collection $submittedRows): bool
    {
        return $submittedRows->contains(function ($row) use ($assignment) {
            $hasCondition = false;
            $matches = true;

            if (Schema::hasColumn('assignment_submissions', 'batch_assignment_id')) {
                $hasCondition = true;
                $matches = $matches && (int) $this->getColumnValue($row, ['batch_assignment_id']) === (int) $assignment->id;
            }

            if (Schema::hasColumn('assignment_submissions', 'assignment_id') && isset($assignment->assignment_id)) {
                $hasCondition = true;
                $matches = $matches && (int) $this->getColumnValue($row, ['assignment_id']) === (int) $assignment->assignment_id;
            }

            if (Schema::hasColumn('assignment_submissions', 'batch_id') && $assignment->batch_id) {
                $hasCondition = true;
                $matches = $matches && (int) $this->getColumnValue($row, ['batch_id']) === (int) $assignment->batch_id;
            }

            return $hasCondition && $matches;
        });
    }

    private function hasSubmittedAssignment(Student $student, BatchAssignment $assignment): bool
    {
        if (!Schema::hasTable('assignment_submissions')) {
            return false;
        }

        $query = AssignmentSubmission::query()
            ->where('student_id', $student->id);

        if (Schema::hasColumn('assignment_submissions', 'batch_assignment_id')) {
            $query->where('batch_assignment_id', $assignment->id);
        }

        if (Schema::hasColumn('assignment_submissions', 'assignment_id') && isset($assignment->assignment_id)) {
            $query->where('assignment_id', $assignment->assignment_id);
        }

        if (Schema::hasColumn('assignment_submissions', 'batch_id') && $assignment->batch_id) {
            $query->where('batch_id', $assignment->batch_id);
        }

        if (Schema::hasColumn('assignment_submissions', 'status')) {
            $query->whereIn('status', [
                'submitted',
                'late',
                'reviewed',
                'returned',
                'graded',
                'completed',
            ]);
        }

        return $query->exists();
    }

    private function formatAssignmentTask(BatchAssignment $assignment): array
    {
        $relatedAssignment = method_exists(BatchAssignment::class, 'assignment')
            ? $assignment->assignment
            : null;

        $deadline = $this->resolveDeadline($assignment);
        $priority = $this->resolvePriority($deadline);

        $title = $this->getColumnValue($relatedAssignment, ['title', 'name'])
            ?: $this->getColumnValue($assignment, ['title', 'name'])
            ?: 'Assignment';

        $courseName = $assignment->batch?->program?->name
            ?? $assignment->batch?->name
            ?? 'Course Assignment';

        return [
            'id' => $assignment->id,
            'type' => 'assignment',

            'title' => $title,
            'course' => $courseName,
            'course_name' => $courseName,
            'program_name' => $courseName,

            'deadline' => $this->formatDeadlineLabel($deadline),
            'deadline_label' => $this->formatDeadlineLabel($deadline),
            'due_date_label' => $this->formatDeadlineLabel($deadline),

            'remaining' => $this->formatRemainingTime($deadline),
            'remaining_label' => $this->formatRemainingTime($deadline),

            'priority' => $priority,
            'priority_label' => $this->formatPriorityLabel($priority),
            'priorityLabel' => $this->formatPriorityLabel($priority),

            'detail_url' => '/assignments/' . $assignment->id,
            'detailUrl' => '/assignments/' . $assignment->id,

            'submit_url' => '/assignments/' . $assignment->id . '/submit',
            'submitUrl' => '/assignments/' . $assignment->id . '/submit',

            'sort_deadline' => $deadline?->timestamp ?? now()->addYears(10)->timestamp,
        ];
    }

    private function getPendingQuizzes(Student $student, Collection $batchIds): Collection
    {
        if ($batchIds->isEmpty() || !Schema::hasTable('batch_learning_quizzes')) {
            return collect();
        }

        $query = BatchLearningQuiz::query()
            ->whereIn('batch_id', $batchIds)
            ->when(
                Schema::hasColumn('batch_learning_quizzes', 'is_active'),
                fn ($query) => $query->where('is_active', true)
            );

        if (Schema::hasColumn('batch_learning_quizzes', 'status')) {
            $query->whereNotIn('status', [
                'inactive',
                'archived',
                'cancelled',
                'canceled',
            ]);
        }

        if (method_exists(BatchLearningQuiz::class, 'batch')) {
            $query->with('batch.program');
        }

        if (method_exists(BatchLearningQuiz::class, 'learningQuiz')) {
            $query->with('learningQuiz');
        }

        $quizzes = $query->get();
        $completedRows = $this->getCompletedQuizAttemptRows($student, $quizzes);

        return collect(
            $quizzes
                ->reject(fn ($quiz) => $this->quizHasCompletedAttemptRow($quiz, $completedRows))
                ->map(fn ($quiz) => $this->formatQuizTask($quiz))
                ->all()
        )->values();
    }


    private function getCompletedQuizAttemptRows(Student $student, Collection $quizzes): Collection
    {
        if ($quizzes->isEmpty() || !Schema::hasTable('learning_quiz_attempts')) {
            return collect();
        }

        $hasIdentifierColumn = Schema::hasColumn('learning_quiz_attempts', 'batch_learning_quiz_id')
            || Schema::hasColumn('learning_quiz_attempts', 'learning_quiz_id')
            || Schema::hasColumn('learning_quiz_attempts', 'quiz_id');

        if (!$hasIdentifierColumn) {
            return collect();
        }

        $query = LearningQuizAttempt::query()
            ->where('student_id', $student->id)
            ->where(function ($attemptQuery) use ($quizzes) {
                $hasCondition = false;

                if (Schema::hasColumn('learning_quiz_attempts', 'batch_learning_quiz_id')) {
                    $ids = $quizzes->pluck('id')->filter()->unique()->values();

                    if ($ids->isNotEmpty()) {
                        $attemptQuery->whereIn('batch_learning_quiz_id', $ids->all());
                        $hasCondition = true;
                    }
                }

                if (Schema::hasColumn('learning_quiz_attempts', 'learning_quiz_id')) {
                    $ids = $quizzes
                        ->map(fn ($quiz) => $this->getColumnValue($quiz, ['learning_quiz_id'])
                            ?? data_get($quiz, 'learningQuiz.id')
                            ?? data_get($quiz, 'quiz.id'))
                        ->filter()
                        ->map(fn ($id) => (int) $id)
                        ->unique()
                        ->values();

                    if ($ids->isNotEmpty()) {
                        $hasCondition
                            ? $attemptQuery->orWhereIn('learning_quiz_id', $ids->all())
                            : $attemptQuery->whereIn('learning_quiz_id', $ids->all());

                        $hasCondition = true;
                    }
                }

                if (Schema::hasColumn('learning_quiz_attempts', 'quiz_id')) {
                    $ids = $quizzes
                        ->flatMap(function ($quiz) {
                            return [
                                $this->getColumnValue($quiz, ['learning_quiz_id'])
                                    ?? data_get($quiz, 'learningQuiz.id')
                                    ?? data_get($quiz, 'quiz.id'),
                                $this->getColumnValue($quiz, ['id']),
                            ];
                        })
                        ->filter()
                        ->map(fn ($id) => (int) $id)
                        ->unique()
                        ->values();

                    if ($ids->isNotEmpty()) {
                        $hasCondition
                            ? $attemptQuery->orWhereIn('quiz_id', $ids->all())
                            : $attemptQuery->whereIn('quiz_id', $ids->all());

                        $hasCondition = true;
                    }
                }

                if (!$hasCondition) {
                    $attemptQuery->whereRaw('1 = 0');
                }
            });

        $this->applyCompletedQuizAttemptFilters($query);

        return $query->get();
    }

    private function applyCompletedQuizAttemptFilters($query): void
    {
        $hasCompletionIndicator = Schema::hasColumn('learning_quiz_attempts', 'status')
            || Schema::hasColumn('learning_quiz_attempts', 'submitted_at')
            || Schema::hasColumn('learning_quiz_attempts', 'completed_at')
            || Schema::hasColumn('learning_quiz_attempts', 'finished_at')
            || Schema::hasColumn('learning_quiz_attempts', 'ended_at')
            || Schema::hasColumn('learning_quiz_attempts', 'is_submitted')
            || Schema::hasColumn('learning_quiz_attempts', 'is_completed');

        if (!$hasCompletionIndicator) {
            return;
        }

        $query->where(function ($completedQuery) {
            $hasCondition = false;

            if (Schema::hasColumn('learning_quiz_attempts', 'status')) {
                $completedQuery->whereIn('status', [
                    'submitted',
                    'completed',
                    'finished',
                    'passed',
                    'done',
                    'reviewed',
                    'graded',
                ]);

                $hasCondition = true;
            }

            foreach (['submitted_at', 'completed_at', 'finished_at', 'ended_at'] as $column) {
                if (!Schema::hasColumn('learning_quiz_attempts', $column)) {
                    continue;
                }

                $hasCondition
                    ? $completedQuery->orWhereNotNull($column)
                    : $completedQuery->whereNotNull($column);

                $hasCondition = true;
            }

            foreach (['is_submitted', 'is_completed'] as $column) {
                if (!Schema::hasColumn('learning_quiz_attempts', $column)) {
                    continue;
                }

                $hasCondition
                    ? $completedQuery->orWhere($column, true)
                    : $completedQuery->where($column, true);

                $hasCondition = true;
            }

            if (!$hasCondition) {
                $completedQuery->whereRaw('1 = 0');
            }
        });
    }

    private function quizHasCompletedAttemptRow($quiz, Collection $completedRows): bool
    {
        $batchLearningQuizId = $this->getColumnValue($quiz, ['id']);

        $learningQuizId = $this->getColumnValue($quiz, ['learning_quiz_id'])
            ?? data_get($quiz, 'learningQuiz.id')
            ?? data_get($quiz, 'quiz.id');

        $possibleQuizIds = collect([
            $learningQuizId,
            $batchLearningQuizId,
        ])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        return $completedRows->contains(function ($row) use ($batchLearningQuizId, $learningQuizId, $possibleQuizIds) {
            if (
                Schema::hasColumn('learning_quiz_attempts', 'batch_learning_quiz_id')
                && $batchLearningQuizId
                && (int) $this->getColumnValue($row, ['batch_learning_quiz_id']) === (int) $batchLearningQuizId
            ) {
                return true;
            }

            if (
                Schema::hasColumn('learning_quiz_attempts', 'learning_quiz_id')
                && $learningQuizId
                && (int) $this->getColumnValue($row, ['learning_quiz_id']) === (int) $learningQuizId
            ) {
                return true;
            }

            if (
                Schema::hasColumn('learning_quiz_attempts', 'quiz_id')
                && $possibleQuizIds->isNotEmpty()
                && $possibleQuizIds->contains((int) $this->getColumnValue($row, ['quiz_id']))
            ) {
                return true;
            }

            return false;
        });
    }

    private function hasCompletedQuiz(Student $student, $quiz): bool
    {
        if (!Schema::hasTable('learning_quiz_attempts')) {
            return false;
        }

        $batchLearningQuizId = $this->getColumnValue($quiz, ['id']);

        $learningQuizId = $this->getColumnValue($quiz, ['learning_quiz_id'])
            ?? data_get($quiz, 'learningQuiz.id')
            ?? data_get($quiz, 'quiz.id');

        $possibleQuizIds = collect([
            $learningQuizId,
            $batchLearningQuizId,
        ])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $hasIdentifierColumn = Schema::hasColumn('learning_quiz_attempts', 'batch_learning_quiz_id')
            || Schema::hasColumn('learning_quiz_attempts', 'learning_quiz_id')
            || Schema::hasColumn('learning_quiz_attempts', 'quiz_id');

        if (!$hasIdentifierColumn) {
            return false;
        }

        $query = LearningQuizAttempt::query()
            ->where('student_id', $student->id)
            ->where(function ($attemptQuery) use ($batchLearningQuizId, $learningQuizId, $possibleQuizIds) {
                $hasCondition = false;

                if (
                    Schema::hasColumn('learning_quiz_attempts', 'batch_learning_quiz_id')
                    && $batchLearningQuizId
                ) {
                    $attemptQuery->where('batch_learning_quiz_id', $batchLearningQuizId);
                    $hasCondition = true;
                }

                if (
                    Schema::hasColumn('learning_quiz_attempts', 'learning_quiz_id')
                    && $learningQuizId
                ) {
                    $hasCondition
                        ? $attemptQuery->orWhere('learning_quiz_id', $learningQuizId)
                        : $attemptQuery->where('learning_quiz_id', $learningQuizId);

                    $hasCondition = true;
                }

                if (
                    Schema::hasColumn('learning_quiz_attempts', 'quiz_id')
                    && $possibleQuizIds->isNotEmpty()
                ) {
                    $hasCondition
                        ? $attemptQuery->orWhereIn('quiz_id', $possibleQuizIds->all())
                        : $attemptQuery->whereIn('quiz_id', $possibleQuizIds->all());

                    $hasCondition = true;
                }

                if (!$hasCondition) {
                    $attemptQuery->whereRaw('1 = 0');
                }
            });

        $hasCompletionIndicator = Schema::hasColumn('learning_quiz_attempts', 'status')
            || Schema::hasColumn('learning_quiz_attempts', 'submitted_at')
            || Schema::hasColumn('learning_quiz_attempts', 'completed_at')
            || Schema::hasColumn('learning_quiz_attempts', 'finished_at')
            || Schema::hasColumn('learning_quiz_attempts', 'ended_at')
            || Schema::hasColumn('learning_quiz_attempts', 'is_submitted')
            || Schema::hasColumn('learning_quiz_attempts', 'is_completed');

        if ($hasCompletionIndicator) {
            $query->where(function ($completedQuery) {
                $hasCondition = false;

                if (Schema::hasColumn('learning_quiz_attempts', 'status')) {
                    $completedQuery->whereIn('status', [
                        'submitted',
                        'completed',
                        'finished',
                        'passed',
                        'done',
                        'reviewed',
                        'graded',
                    ]);

                    $hasCondition = true;
                }

                foreach (['submitted_at', 'completed_at', 'finished_at', 'ended_at'] as $column) {
                    if (!Schema::hasColumn('learning_quiz_attempts', $column)) {
                        continue;
                    }

                    $hasCondition
                        ? $completedQuery->orWhereNotNull($column)
                        : $completedQuery->whereNotNull($column);

                    $hasCondition = true;
                }

                foreach (['is_submitted', 'is_completed'] as $column) {
                    if (!Schema::hasColumn('learning_quiz_attempts', $column)) {
                        continue;
                    }

                    $hasCondition
                        ? $completedQuery->orWhere($column, true)
                        : $completedQuery->where($column, true);

                    $hasCondition = true;
                }

                if (!$hasCondition) {
                    $completedQuery->whereRaw('1 = 0');
                }
            });
        }

        return $query->exists();
    }

    private function formatQuizTask($quiz): array
    {
        $deadline = $this->resolveDeadline($quiz);
        $priority = $this->resolvePriority($deadline);
        $relatedQuiz = $quiz->learningQuiz ?? null;

        $title = $this->getColumnValue($quiz, ['title', 'name', 'quiz_title'])
            ?: $this->getColumnValue($relatedQuiz, ['title', 'name', 'quiz_title'])
            ?: 'Quiz';

        $courseName = $quiz->batch?->program?->name
            ?? $quiz->batch?->name
            ?? $this->getColumnValue($quiz, ['batch_name'])
            ?? 'Learning Quiz';

        $quizId = $quiz->id;

        return [
            'id' => $quizId,
            'quiz_id' => $quiz->learning_quiz_id ?? $relatedQuiz?->id ?? null,
            'batch_learning_quiz_id' => $quiz->id,
            'type' => 'quiz',

            'title' => $title,
            'course' => $courseName,
            'course_name' => $courseName,
            'program_name' => $courseName,

            'deadline' => $this->formatDeadlineLabel($deadline),
            'deadline_label' => $this->formatDeadlineLabel($deadline),
            'due_date_label' => $this->formatDeadlineLabel($deadline),

            'remaining' => $this->formatRemainingTime($deadline),
            'remaining_label' => $this->formatRemainingTime($deadline),

            'priority' => $priority,
            'priority_label' => $this->formatPriorityLabel($priority),
            'priorityLabel' => $this->formatPriorityLabel($priority),

            'detail_url' => '/quizzes/' . $quizId,
            'detailUrl' => '/quizzes/' . $quizId,

            'submit_url' => '/quizzes/' . $quizId . '/start',
            'submitUrl' => '/quizzes/' . $quizId . '/start',

            'sort_deadline' => $deadline?->timestamp ?? now()->addYears(10)->timestamp,
        ];
    }

    private function resolveDeadline($model): ?Carbon
    {
        foreach ([
            'due_at',
            'deadline_at',
            'deadline',
            'due_date',
            'closed_at',
            'available_until',
            'end_at',
            'end_date',
        ] as $column) {
            $value = $this->getColumnValue($model, [$column]);

            if ($value) {
                return Carbon::parse($value);
            }
        }

        return null;
    }

    private function formatDeadlineLabel(?Carbon $deadline): string
    {
        if (!$deadline) {
            return 'No deadline';
        }

        return $deadline->format('d M Y H:i');
    }

    private function formatRemainingTime(?Carbon $deadline): string
    {
        if (!$deadline) {
            return 'No deadline';
        }

        $seconds = (int) now()->diffInSeconds($deadline, false);

        if ($seconds < 0) {
            $daysLate = (int) ceil(abs($seconds) / 86400);

            return $daysLate <= 1
                ? 'Overdue'
                : $daysLate . ' days overdue';
        }

        if ($seconds <= 0) {
            return 'Due today';
        }

        $daysLeft = (int) ceil($seconds / 86400);

        if ($daysLeft <= 1) {
            return '1 day left';
        }

        return $daysLeft . ' days left';
    }

    private function resolvePriority(?Carbon $deadline): string
    {
        if (!$deadline) {
            return 'normal';
        }

        $seconds = (int) now()->diffInSeconds($deadline, false);

        if ($seconds < 0 || $seconds <= 86400) {
            return 'high';
        }

        if ($seconds <= 259200) {
            return 'medium';
        }

        return 'normal';
    }

    private function formatPriorityLabel(string $priority): string
    {
        return match ($priority) {
            'high' => 'Urgent',
            'medium' => 'Soon',
            default => 'Task',
        };
    }

    private function getDashboardAnnouncements(Collection $programIds, Collection $batchIds): Collection
    {
        if (!Schema::hasTable('announcements') || !class_exists(Announcement::class)) {
            return collect();
        }

        $query = Announcement::query();

        if (method_exists(Announcement::class, 'program')) {
            $query->with('program');
        }

        if (method_exists(Announcement::class, 'batch')) {
            $query->with('batch.program');
        }

        if (method_exists(Announcement::class, 'scopeVisibleNow')) {
            $query->visibleNow();
        } else {
            if (Schema::hasColumn('announcements', 'publish_at')) {
                $query->where(function ($publishQuery) {
                    $publishQuery->whereNull('publish_at')
                        ->orWhere('publish_at', '<=', now());
                });
            }

            if (Schema::hasColumn('announcements', 'expired_at')) {
                $query->where(function ($expiredQuery) {
                    $expiredQuery->whereNull('expired_at')
                        ->orWhere('expired_at', '>=', now());
                });
            }

            if (Schema::hasColumn('announcements', 'is_active')) {
                $query->where('is_active', true);
            }
        }

        if (method_exists(Announcement::class, 'scopeForProgramsAndBatches')) {
            $query->forProgramsAndBatches($programIds, $batchIds);
        } else {
            $query->where(function ($targetQuery) use ($programIds, $batchIds) {
                if (Schema::hasColumn('announcements', 'program_id') && $programIds->isNotEmpty()) {
                    $targetQuery->orWhereIn('program_id', $programIds);
                }

                if (Schema::hasColumn('announcements', 'batch_id') && $batchIds->isNotEmpty()) {
                    $targetQuery->orWhereIn('batch_id', $batchIds);
                }

                if (Schema::hasColumn('announcements', 'program_id')) {
                    $targetQuery->orWhereNull('program_id');
                }

                if (Schema::hasColumn('announcements', 'batch_id')) {
                    $targetQuery->orWhereNull('batch_id');
                }
            });
        }

        if (Schema::hasColumn('announcements', 'is_pinned')) {
            $query->orderByDesc('is_pinned');
        }

        if (Schema::hasColumn('announcements', 'publish_at')) {
            $query->latest('publish_at');
        }

        return $query
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(function (Announcement $announcement) {
                $content = (string) ($announcement->content ?? $announcement->description ?? '');
                $slug = $announcement->slug ?? $announcement->id;

                return [
                    'id' => $announcement->id,
                    'title' => $announcement->title ?? 'Announcement',
                    'slug' => $slug,
                    'excerpt' => Str::limit(strip_tags($content), 120),
                    'content_preview' => Str::limit(strip_tags($content), 120),
                    'is_pinned' => (bool) ($announcement->is_pinned ?? false),
                    'publish_at_label' => $announcement->publish_at
                        ? Carbon::parse($announcement->publish_at)->format('d M Y H:i')
                        : '',
                    'date_label' => $announcement->publish_at
                        ? Carbon::parse($announcement->publish_at)->format('d M Y H:i')
                        : '',
                    'url' => '/announcements/' . $slug,
                ];
            })
            ->values();
    }

    private function resolveProgressActivityAt($progress)
    {
        if (!$progress) {
            return null;
        }

        return $this->getColumnValue($progress, [
            'last_watched_at',
            'watched_at',
            'completed_at',
            'updated_at',
            'created_at',
        ]);
    }

    private function resolveWatchSeconds($progress): int
    {
        if (!$progress) {
            return 0;
        }

        foreach (['watch_seconds', 'watched_seconds', 'total_watch_seconds'] as $column) {
            $value = $this->getColumnValue($progress, [$column]);

            if (is_numeric($value) && (int) $value > 0) {
                return (int) $value;
            }
        }

        $lastPosition = $this->resolveLastPositionSeconds($progress);
        $duration = (int) ($this->getColumnValue($progress, ['duration_seconds', 'video_duration_seconds']) ?? 0);

        if ($duration > 0 && $lastPosition > 0) {
            return min($lastPosition, $duration);
        }

        return max(0, $lastPosition);
    }

    private function resolveLastPositionSeconds($progress): int
    {
        if (!$progress) {
            return 0;
        }

        foreach ([
            'last_position_seconds',
            'current_position_seconds',
            'position_seconds',
            'watched_seconds',
        ] as $column) {
            $value = $this->getColumnValue($progress, [$column]);

            if (is_numeric($value)) {
                return max(0, (int) $value);
            }
        }

        return 0;
    }

    private function resolveProgressPercentage($progress, $subTopic = null): int
    {
        if ($this->isProgressCompleted($progress)) {
            return 100;
        }

        foreach ([
            'progress_percentage',
            'video_progress_percentage',
            'watched_percentage',
            'percentage',
        ] as $column) {
            $value = $this->getColumnValue($progress, [$column]);

            if (is_numeric($value) && (float) $value > 0) {
                return $this->clampPercent((float) $value);
            }
        }

        $lastPosition = $this->resolveLastPositionSeconds($progress);
        $durationSeconds = $this->resolveDurationSeconds($subTopic, $progress);

        if ($durationSeconds > 0 && $lastPosition > 0) {
            return $this->clampPercent(($lastPosition / $durationSeconds) * 100);
        }

        return 0;
    }

    private function resolveDurationSeconds($subTopic = null, $progress = null): int
    {
        foreach ([
            [$progress, ['duration_seconds', 'video_duration_seconds']],
            [$subTopic, ['duration_seconds', 'video_duration_seconds']],
        ] as [$model, $columns]) {
            foreach ($columns as $column) {
                $value = $this->getColumnValue($model, [$column]);

                if (is_numeric($value) && (int) $value > 0) {
                    return (int) $value;
                }
            }
        }

        foreach ([
            [$progress, ['duration_minutes', 'video_duration_minutes']],
            [$subTopic, ['duration_minutes', 'video_duration_minutes']],
        ] as [$model, $columns]) {
            foreach ($columns as $column) {
                $value = $this->getColumnValue($model, [$column]);

                if (is_numeric($value) && (float) $value > 0) {
                    return (int) round(((float) $value) * 60);
                }
            }
        }

        $duration = $this->getColumnValue($subTopic, ['duration'])
            ?? $this->getColumnValue($progress, ['duration']);

        if (is_numeric($duration) && (float) $duration > 0) {
            $duration = (float) $duration;

            return $duration <= 600
                ? (int) round($duration * 60)
                : (int) round($duration);
        }

        return 0;
    }

    private function resolveDurationMinutes($subTopic = null, $progress = null): ?int
    {
        $seconds = $this->resolveDurationSeconds($subTopic, $progress);

        if ($seconds <= 0) {
            return null;
        }

        return max(1, (int) ceil($seconds / 60));
    }

    private function isProgressCompleted($progress): bool
    {
        if (!$progress) {
            return false;
        }

        $isCompleted = $this->getColumnValue($progress, ['is_completed']);

        if ($isCompleted !== null) {
            return $this->toBoolean($isCompleted);
        }

        $completedAt = $this->getColumnValue($progress, ['completed_at']);

        if ($completedAt) {
            return true;
        }

        $status = strtolower((string) ($this->getColumnValue($progress, ['status']) ?? ''));

        return in_array($status, ['completed', 'finished', 'done', 'passed'], true);
    }

    private function toBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        return in_array(strtolower((string) $value), [
            '1',
            'true',
            'yes',
            'y',
            'completed',
            'done',
        ], true);
    }

    private function clampPercent($value): int
    {
        return max(0, min(100, (int) round((float) $value)));
    }

    private function resolveSubTopicThumbnailUrl($subTopic, ?string $videoUrl = null): string
    {
        $lessonType = $this->resolveSubTopicLessonType($subTopic);

        if ($this->isLiveSessionLessonType($lessonType)) {
            return $this->defaultLiveSessionThumbnailUrl();
        }

        $thumbnailUrl = $this->getColumnValue($subTopic, [
            'thumbnail_url',
            'thumbnailUrl',
            'image_url',
            'imageUrl',
        ]);

        if ($thumbnailUrl) {
            return (string) $thumbnailUrl;
        }

        return $this->defaultVideoThumbnailUrl();
    }

    private function resolveSubTopicLessonType($subTopic): string
    {
        $lessonType = strtolower(trim((string) $this->getColumnValue($subTopic, [
            'lesson_type',
            'lessonType',
            'type',
            'session_type',
            'sessionType',
            'content_type',
            'contentType',
        ])));

        if ($lessonType === '') {
            return 'video';
        }

        $lessonType = str_replace([' ', '-'], '_', $lessonType);

        return match ($lessonType) {
            'live',
            'live_session',
            'live_class',
            'online',
            'online_session',
            'webinar',
            'meet',
            'google_meet',
            'zoom' => 'live_session',

            'video_lesson',
            'recorded_video',
            'recorded',
            'vod' => 'video',

            default => $lessonType,
        };
    }

    private function isLiveSessionLessonType(?string $lessonType): bool
    {
        $normalized = strtolower(trim((string) $lessonType));
        $normalized = str_replace([' ', '-'], '_', $normalized);

        return in_array($normalized, [
            'live',
            'live_session',
            'live_class',
            'online',
            'online_session',
            'webinar',
            'meet',
            'google_meet',
            'zoom',
        ], true);
    }


    private function defaultVideoThumbnailUrl(): string
    {
        return asset('images/video-thumbnail.png');
    }

    private function defaultLiveSessionThumbnailUrl(): string
    {
        return asset('images/live-session.png');
    }

    private function resolveSubTopicVideoUrl($subTopic): ?string
    {
        $lessonType = $this->resolveSubTopicLessonType($subTopic);

        if ($this->isLiveSessionLessonType($lessonType)) {
            return null;
        }

        $videoUrl = $this->getColumnValue($subTopic, [
            'video_url',
            'youtube_url',
            'youtube_link',
            'video_link',
            'url',
            'link',
        ]);

        return $videoUrl ? (string) $videoUrl : null;
    }


    private function extractYoutubeVideoId(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        $url = trim($url);

        if ($url === '') {
            return null;
        }

        $patterns = [
            '/youtu\.be\/([a-zA-Z0-9_-]{6,})/',
            '/youtube\.com\/watch\?v=([a-zA-Z0-9_-]{6,})/',
            '/youtube\.com\/watch.*[?&]v=([a-zA-Z0-9_-]{6,})/',
            '/youtube\.com\/embed\/([a-zA-Z0-9_-]{6,})/',
            '/youtube\.com\/shorts\/([a-zA-Z0-9_-]{6,})/',
            '/youtube\.com\/live\/([a-zA-Z0-9_-]{6,})/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1] ?? null;
            }
        }

        return null;
    }

    private function getColumnValue($model, array $columns)
    {
        if (!$model) {
            return null;
        }

        if (method_exists($model, 'getTable')) {
            $table = $model->getTable();

            foreach ($columns as $column) {
                if (!Schema::hasColumn($table, $column)) {
                    continue;
                }

                if (!blank($model->{$column})) {
                    return $model->{$column};
                }

                if (isset($model->{$column}) && $model->{$column} === 0) {
                    return 0;
                }
            }

            return null;
        }

        foreach ($columns as $column) {
            if (isset($model->{$column}) && !blank($model->{$column})) {
                return $model->{$column};
            }

            if (isset($model->{$column}) && $model->{$column} === 0) {
                return 0;
            }
        }

        return null;
    }

    public function learningTimeline(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $user->loadMissing([
            'student.enrollments.program',
            'student.enrollments.batch.program',
        ]);

        if (!$this->isStudentUser($user) || !$user->student) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized student account.',
            ], 403);
        }

        $student = $user->student;
        $activeEnrollments = $this->getEligibleEnrollments($student);

        if ($activeEnrollments->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'timeline' => [],
                    'summary' => [
                        'total' => 0,
                        'completed' => 0,
                        'in_progress' => 0,
                        'not_started' => 0,
                        'progress_percentage' => 0,
                    ],
                ],
            ]);
        }

        $batchIds = $this->resolveBatchIds($activeEnrollments);
        $programIds = $this->resolveProgramIds($activeEnrollments);

        $subTopics = $this->getSubTopicsForPrograms($programIds);
        $progressRows = $this->getProgressRows($student, $subTopics);

        if ($subTopics->isEmpty()) {
            $subTopics = $this->getFallbackSubTopics($programIds, $batchIds);
            $progressRows = $this->getProgressRows($student, $subTopics);
        }

        if ($subTopics->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'timeline' => [],
                    'summary' => [
                        'total' => 0,
                        'completed' => 0,
                        'in_progress' => 0,
                        'not_started' => 0,
                        'progress_percentage' => 0,
                    ],
                ],
            ]);
        }

        $progressBySubTopic = $this->mapLatestProgressBySubTopic($progressRows);
        $currentSubTopicId = $this->resolveCurrentTimelineSubTopicId(
            subTopics: $subTopics,
            progressBySubTopic: $progressBySubTopic
        );

        $timeline = $subTopics
            ->values()
            ->map(function ($subTopic, int $index) use ($progressBySubTopic, $currentSubTopicId) {
                $progress = $progressBySubTopic->get((int) $subTopic->id);

                return $this->formatLearningTimelineItem(
                    subTopic: $subTopic,
                    progress: $progress,
                    index: $index,
                    currentSubTopicId: $currentSubTopicId
                );
            })
            ->values();

        $totalCount = $timeline->count();
        $completedCount = $timeline->where('status', 'done')->count();
        $inProgressCount = $timeline->where('status', 'in_progress')->count();
        $notStartedCount = $timeline->where('status', 'not_started')->count();

        $progressPercentage = $totalCount > 0
            ? $this->clampPercent(($completedCount / $totalCount) * 100)
            : 0;

        $responseData = [
            'timeline' => $timeline->toArray(),
            'summary' => [
                'total' => $totalCount,
                'completed' => $completedCount,
                'in_progress' => $inProgressCount,
                'not_started' => $notStartedCount,
                'progress_percentage' => $progressPercentage,
                'progressPercentage' => $progressPercentage,
            ],
        ];

        if ($request->boolean('debug')) {
            $responseData['debug'] = [
                'student_id' => $student->id,
                'batch_ids' => $batchIds->values()->all(),
                'program_ids' => $programIds->values()->all(),
                'sub_topics_count' => $subTopics->count(),
                'sub_topic_ids' => $subTopics->pluck('id')->values()->all(),
                'progress_rows_count' => $progressRows->count(),
                'progress_sub_topic_ids' => $progressRows
                    ->map(fn ($progress) => (int) $this->getColumnValue($progress, ['sub_topic_id']))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all(),
                'timeline_count' => $timeline->count(),
                'current_sub_topic_id' => $currentSubTopicId,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $responseData,
        ]);
    }

    private function resolveCurrentTimelineSubTopicId(Collection $subTopics, Collection $progressBySubTopic): ?int
    {
        if ($subTopics->isEmpty()) {
            return null;
        }

        $activeSubTopic = $subTopics
            ->filter(function ($subTopic) use ($progressBySubTopic) {
                $progress = $progressBySubTopic->get((int) $subTopic->id);

                return $progress
                    && !$this->isProgressCompleted($progress)
                    && $this->resolveProgressActivityAt($progress);
            })
            ->sortByDesc(function ($subTopic) use ($progressBySubTopic) {
                $progress = $progressBySubTopic->get((int) $subTopic->id);
                $activityAt = $this->resolveProgressActivityAt($progress);

                return $activityAt
                    ? Carbon::parse($activityAt)->timestamp
                    : 0;
            })
            ->first();

        if ($activeSubTopic) {
            return (int) $activeSubTopic->id;
        }

        $firstIncompleteSubTopic = $subTopics
            ->first(function ($subTopic) use ($progressBySubTopic) {
                $progress = $progressBySubTopic->get((int) $subTopic->id);

                return !$this->isProgressCompleted($progress);
            });

        return $firstIncompleteSubTopic
            ? (int) $firstIncompleteSubTopic->id
            : null;
    }

    private function formatLearningTimelineItem($subTopic, $progress, int $index, ?int $currentSubTopicId): array
    {
        $progressPercentage = $this->resolveProgressPercentage($progress, $subTopic);
        $isCompleted = $this->isProgressCompleted($progress);

        if ($isCompleted) {
            $progressPercentage = 100;
            $status = 'done';
        } elseif ($currentSubTopicId && (int) $subTopic->id === $currentSubTopicId) {
            $status = 'in_progress';
        } elseif ($progressPercentage > 0) {
            $status = 'in_progress';
        } else {
            $status = 'not_started';
        }

        $topic = $subTopic->topic ?? null;
        $module = $topic?->module ?? null;

        $moduleName = $this->getColumnValue($module, ['name', 'title']);
        $topicName = $this->getColumnValue($topic, ['name', 'title']);

        $title = $this->getColumnValue($subTopic, ['name', 'title']) ?: 'Untitled Material';
        $type = $this->resolveSubTopicLessonType($subTopic);
        $isLiveSession = $this->isLiveSessionLessonType($type);

        $videoUrl = $isLiveSession
            ? null
            : $this->resolveSubTopicVideoUrl($subTopic);

        $hasVideo = !$isLiveSession && (
            $type === 'video'
            || !blank($videoUrl)
        );

        $thumbnailUrl = $this->resolveSubTopicThumbnailUrl($subTopic, $videoUrl);
        $url = $this->resolveLearningTimelineUrl($subTopic);

        $typeLabel = $isLiveSession
            ? 'Live Session'
            : ($hasVideo ? 'Video Lesson' : 'Learning Material');

        return [
            'id' => $subTopic->id,
            'number' => $index + 1,

            'title' => $title,
            'name' => $title,

            'subtitle' => $moduleName ?: $topicName ?: '',
            'module' => $moduleName ?: '',
            'module_title' => $moduleName ?: '',
            'moduleTitle' => $moduleName ?: '',
            'topic' => $topicName ?: '',
            'topic_title' => $topicName ?: '',
            'topicTitle' => $topicName ?: '',

            'type' => $type,
            'lesson_type' => $type,
            'lessonType' => $type,

            'type_label' => $typeLabel,
            'typeLabel' => $typeLabel,

            'is_live_session' => $isLiveSession,
            'isLiveSession' => $isLiveSession,
            'has_video' => $hasVideo,
            'hasVideo' => $hasVideo,

            'thumbnail_url' => $thumbnailUrl,
            'thumbnailUrl' => $thumbnailUrl,
            'image_url' => $thumbnailUrl,
            'imageUrl' => $thumbnailUrl,

            'video_url' => $videoUrl,
            'videoUrl' => $videoUrl,

            'status' => $status,
            'progress_status' => $status,
            'progressStatus' => $status,

            'progress' => $progressPercentage,
            'progress_percentage' => $progressPercentage,
            'progressPercentage' => $progressPercentage,

            'is_completed' => $isCompleted,
            'isCompleted' => $isCompleted,
            'is_current' => $status === 'in_progress',
            'isCurrent' => $status === 'in_progress',
            'is_locked' => false,
            'isLocked' => false,

            'url' => $url,
            'learn_url' => $url,
            'learnUrl' => $url,
        ];
    }


    private function resolveLearningTimelineUrl($subTopic): string
    {
        $topic = $subTopic->topic ?? null;
        $module = $topic?->module ?? null;
        $stage = $module?->stage ?? null;

        $program = $stage?->program
            ?? $module?->program
            ?? $this->resolveProgramFromDatabase($module);

        $courseSlug = $this->resolveProgramSlug($program);
        $lessonSlug = $this->resolveSubTopicSlug($subTopic);

        if ($courseSlug !== 'course' && $lessonSlug !== 'lesson') {
            return '/learn/' . $courseSlug . '/' . $lessonSlug;
        }

        return '/learn/sub-topics/' . $subTopic->id;
    }
}
