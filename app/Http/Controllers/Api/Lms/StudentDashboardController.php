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
use App\Models\StudentMentoringSession;
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

        $subTopics = $this->getSubTopicsForPrograms($programIds);
        $progressRows = $this->getProgressRows($student, $subTopics);

        /*
         * Penting:
         * Tabel progress di beberapa project bisa plural (`student_lesson_progresses`)
         * atau singular (`student_lesson_progress`). Selain itu, kadang query curriculum
         * belum memasukkan sub topic yang sudah punya progress. Jadi progress student
         * kita merge dulu, lalu sub topic dari progress yang belum completed ikut
         * dimasukkan sebagai kandidat current lesson.
         */
        $progressRows = $this->mergeProgressRows(
            primaryRows: $progressRows,
            extraRows: $this->getAllStudentProgressRows($student)
        );

        $subTopics = $this->mergeIncompleteProgressSubTopics(
            subTopics: $subTopics,
            progressRows: $progressRows
        );

        $courses = collect($activeEnrollments->all())
            ->map(fn ($enrollment) => $this->formatCourse($enrollment, $student))
            ->filter()
            ->unique(fn (array $course) => $course['id'] ?? null)
            ->values();

        $pendingTasks = $this->getPendingTasks($student, $batchIds);
        $summary = $this->formatSummary($courses, $pendingTasks);

        $currentLesson = $this->resolveCurrentLesson(
            subTopics: $subTopics,
            progressRows: $progressRows
        );

        /*
         * Fallback penting:
         * Kalau curriculum path program -> stages -> modules -> topics -> sub_topics belum kebaca,
         * frontend akan dapat current_lesson = null. Jadi kita cari lagi dari progress student,
         * lalu fallback terakhir ke first available sub topic dari program aktif.
         */
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

                'upcoming_sessions' => $this->getDashboardUpcomingSessions(
                    student: $student,
                    programIds: $programIds,
                    batchIds: $batchIds
                )->toArray(),

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

        return [
            'period_label' => $startOfWeek->format('d M') . ' - ' . $endOfWeek->format('d M Y'),
            'periodLabel' => $startOfWeek->format('d M') . ' - ' . $endOfWeek->format('d M Y'),

            'total_watch_seconds' => $watchSeconds,
            'totalWatchSeconds' => $watchSeconds,
            'total_watch_minutes' => (int) round($watchSeconds / 60),
            'totalWatchMinutes' => (int) round($watchSeconds / 60),
            'total_watch_time_label' => $this->formatWatchTime($watchSeconds),
            'totalWatchTimeLabel' => $this->formatWatchTime($watchSeconds),
            'total_watch_time' => $this->formatWatchTime($watchSeconds),
            'totalWatchTime' => $this->formatWatchTime($watchSeconds),

            'completed_sub_topics' => $completedSubTopics,
            'completedSubTopics' => $completedSubTopics,
            'tasks_done' => $tasksDone,
            'tasksDone' => $tasksDone,

            'current_streak' => $this->calculateCurrentStreak($student),
            'currentStreak' => $this->calculateCurrentStreak($student),
            'last_active_label' => $this->formatLastActiveLabel($latestActivity ? $this->resolveProgressActivityAt($latestActivity) : null),
            'lastActiveLabel' => $this->formatLastActiveLabel($latestActivity ? $this->resolveProgressActivityAt($latestActivity) : null),
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

    private function getSubTopicsForPrograms(Collection $programIds): Collection
    {
        if ($programIds->isEmpty() || !Schema::hasTable('sub_topics')) {
            return collect();
        }

        $subTopicIds = $this->resolveSubTopicIdsByProgram($programIds);

        if ($subTopicIds->isEmpty()) {
            return collect();
        }

        $subTopics = $this->querySubTopicsByIds($subTopicIds, true)->get();

        /*
         * Jangan sampai current lesson hilang cuma karena is_active/status belum rapi.
         * Kalau filter visibility bikin kosong, fallback ambil unfiltered curriculum dulu.
         */
        if ($subTopics->isEmpty()) {
            $subTopics = $this->querySubTopicsByIds($subTopicIds, false)->get();
        }

        return $subTopics
            ->sortBy(fn ($subTopic) => $this->formatSubTopicSortKey($subTopic))
            ->values();
    }

    private function querySubTopicsByIds(Collection $subTopicIds, bool $applyVisibilityFilters = true)
    {
        $query = SubTopic::query();
        $relations = $this->resolveSubTopicRelations();

        if (!empty($relations)) {
            $query->with($relations);
        }

        $query->whereIn('id', $subTopicIds);

        if ($applyVisibilityFilters && Schema::hasColumn('sub_topics', 'is_active')) {
            $query->where(function ($activeQuery) {
                $activeQuery->where('is_active', true)
                    ->orWhereNull('is_active');
            });
        }

        if ($applyVisibilityFilters && Schema::hasColumn('sub_topics', 'status')) {
            $query->where(function ($statusQuery) {
                $statusQuery->whereNull('status')
                    ->orWhereNotIn('status', ['inactive', 'archived', 'deleted']);
            });
        }

        return $query;
    }

    private function resolveSubTopicIdsByProgram(Collection $programIds): Collection
    {
        if (!Schema::hasTable('sub_topics')) {
            return collect();
        }

        $ids = collect();

        if (Schema::hasColumn('sub_topics', 'program_id')) {
            $ids = $ids->merge(
                DB::table('sub_topics')
                    ->whereIn('program_id', $programIds)
                    ->pluck('id')
            );
        }

        if (
            Schema::hasTable('topics')
            && Schema::hasColumn('sub_topics', 'topic_id')
            && Schema::hasColumn('topics', 'program_id')
        ) {
            $ids = $ids->merge(
                DB::table('sub_topics')
                    ->join('topics', 'topics.id', '=', 'sub_topics.topic_id')
                    ->whereIn('topics.program_id', $programIds)
                    ->pluck('sub_topics.id')
            );
        }

        if (
            Schema::hasTable('topics')
            && Schema::hasTable('modules')
            && Schema::hasColumn('sub_topics', 'topic_id')
            && Schema::hasColumn('topics', 'module_id')
            && Schema::hasColumn('modules', 'program_id')
        ) {
            $ids = $ids->merge(
                DB::table('sub_topics')
                    ->join('topics', 'topics.id', '=', 'sub_topics.topic_id')
                    ->join('modules', 'modules.id', '=', 'topics.module_id')
                    ->whereIn('modules.program_id', $programIds)
                    ->pluck('sub_topics.id')
            );
        }

        if (
            Schema::hasTable('modules')
            && Schema::hasColumn('sub_topics', 'module_id')
            && Schema::hasColumn('modules', 'program_id')
        ) {
            $ids = $ids->merge(
                DB::table('sub_topics')
                    ->join('modules', 'modules.id', '=', 'sub_topics.module_id')
                    ->whereIn('modules.program_id', $programIds)
                    ->pluck('sub_topics.id')
            );
        }

        foreach ($this->candidateStageTables() as $stageTable) {
            if (!Schema::hasTable($stageTable) || !Schema::hasColumn($stageTable, 'program_id')) {
                continue;
            }

            if (
                Schema::hasTable('topics')
                && Schema::hasTable('modules')
                && Schema::hasColumn('sub_topics', 'topic_id')
                && Schema::hasColumn('topics', 'module_id')
                && Schema::hasColumn('modules', 'stage_id')
            ) {
                $ids = $ids->merge(
                    DB::table('sub_topics')
                        ->join('topics', 'topics.id', '=', 'sub_topics.topic_id')
                        ->join('modules', 'modules.id', '=', 'topics.module_id')
                        ->join($stageTable, $stageTable . '.id', '=', 'modules.stage_id')
                        ->whereIn($stageTable . '.program_id', $programIds)
                        ->pluck('sub_topics.id')
                );
            }

            if (
                Schema::hasTable('modules')
                && Schema::hasColumn('sub_topics', 'module_id')
                && Schema::hasColumn('modules', 'stage_id')
            ) {
                $ids = $ids->merge(
                    DB::table('sub_topics')
                        ->join('modules', 'modules.id', '=', 'sub_topics.module_id')
                        ->join($stageTable, $stageTable . '.id', '=', 'modules.stage_id')
                        ->whereIn($stageTable . '.program_id', $programIds)
                        ->pluck('sub_topics.id')
                );
            }

            if (Schema::hasColumn('sub_topics', 'stage_id')) {
                $ids = $ids->merge(
                    DB::table('sub_topics')
                        ->join($stageTable, $stageTable . '.id', '=', 'sub_topics.stage_id')
                        ->whereIn($stageTable . '.program_id', $programIds)
                        ->pluck('sub_topics.id')
                );
            }
        }

        return $ids
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    private function candidateStageTables(): array
    {
        $tables = [];

        if (class_exists(\App\Models\Stage::class)) {
            try {
                $tables[] = (new \App\Models\Stage())->getTable();
            } catch (\Throwable $exception) {
                // ignore and use fallback names below
            }
        }

        return collect($tables)
            ->merge(['stages', 'program_stages', 'curriculum_stages'])
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

        if ($this->canUseStageProgramPath()) {
            return ['topic.module.stage.program'];
        }

        if ($this->canUseModuleProgramPath()) {
            return ['topic.module.program'];
        }

        return ['topic.module'];
    }

    private function canUseStageProgramPath(): bool
    {
        return method_exists(SubTopic::class, 'topic')
            && class_exists(\App\Models\Topic::class)
            && class_exists(\App\Models\Module::class)
            && class_exists(\App\Models\Stage::class)
            && method_exists(\App\Models\Topic::class, 'module')
            && method_exists(\App\Models\Module::class, 'stage')
            && method_exists(\App\Models\Stage::class, 'program')
            && Schema::hasTable('modules')
            && Schema::hasTable('stages')
            && Schema::hasColumn('modules', 'stage_id')
            && Schema::hasColumn('stages', 'program_id');
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

        return DB::table($progressTable)
            ->where('student_id', $student->id)
            ->whereIn('sub_topic_id', $subTopics->pluck('id')->filter()->values())
            ->get();
    }

    private function formatCourse($enrollment, Student $student): ?array
    {
        $program = $enrollment->program ?? $enrollment->batch?->program;

        if (!$program) {
            return null;
        }

        $subTopics = $this->getSubTopicsForPrograms(collect([(int) $program->id]));
        $progressRows = $this->getProgressRows($student, $subTopics);

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
        } catch (\Throwable $exception) {
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
                $progress = $progressBySubTopic->get($subTopic->id);

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

        /*
         * Urutan current lesson yang benar:
         * 1. Lesson yang sudah pernah dibuka tapi belum completed.
         * 2. Lesson pertama di curriculum yang belum completed / belum punya progress.
         * 3. Completed fallback hanya boleh dipakai kalau caller memang mengizinkan.
         *
         * Ini penting supaya dashboard tidak balik lagi ke lesson pertama yang sudah completed.
         */
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

        $progressPercentage = $this->resolveProgressPercentage($progress, $currentSubTopic);
        $isCompleted = $this->isProgressCompleted($progress);

        if ($isCompleted) {
            $progressPercentage = 100;
        }

        $courseSlug = $this->resolveProgramSlug($program);
        $lessonSlug = $this->resolveSubTopicSlug($currentSubTopic);
        $learnUrl = '/learn/' . $courseSlug . '/' . $lessonSlug;

        return [
            'id' => $currentSubTopic->id,
            'sub_topic_id' => $currentSubTopic->id,
            'subTopicId' => $currentSubTopic->id,

            'title' => $this->getColumnValue($currentSubTopic, ['name', 'title']) ?: 'Untitled Lesson',
            'description' => $this->getColumnValue($currentSubTopic, ['description', 'summary']) ?: 'Continue your current learning activity.',

            'duration_minutes' => $this->resolveDurationMinutes($currentSubTopic, $progress),
            'durationMinutes' => $this->resolveDurationMinutes($currentSubTopic, $progress),
            'duration_seconds' => $this->resolveDurationSeconds($currentSubTopic, $progress),
            'durationSeconds' => $this->resolveDurationSeconds($currentSubTopic, $progress),

            'thumbnail_url' => $this->getColumnValue($currentSubTopic, ['thumbnail_url', 'image', 'thumbnail'])
                ?: 'https://img.youtube.com/vi/Ke90Tje7VS0/maxresdefault.jpg',
            'thumbnailUrl' => $this->getColumnValue($currentSubTopic, ['thumbnail_url', 'image', 'thumbnail'])
                ?: 'https://img.youtube.com/vi/Ke90Tje7VS0/maxresdefault.jpg',

            'module' => $this->getColumnValue($module, ['name', 'title']) ?: '-',
            'module_title' => $this->getColumnValue($module, ['name', 'title']) ?: '-',
            'moduleTitle' => $this->getColumnValue($module, ['name', 'title']) ?: '-',

            'topic' => $this->getColumnValue($topic, ['name', 'title']) ?: '-',
            'topic_title' => $this->getColumnValue($topic, ['name', 'title']) ?: '-',
            'topicTitle' => $this->getColumnValue($topic, ['name', 'title']) ?: '-',

            'status' => $isCompleted ? 'Completed' : 'In Progress',
            'status_label' => $isCompleted ? 'Completed' : 'In Progress',
            'statusLabel' => $isCompleted ? 'Completed' : 'In Progress',
            'is_completed' => $isCompleted,
            'isCompleted' => $isCompleted,

            'progress_percentage' => $progressPercentage,
            'progressPercentage' => $progressPercentage,
            'video_progress_percentage' => $progressPercentage,
            'videoProgressPercentage' => $progressPercentage,
            'watched_percentage' => $progressPercentage,
            'watchedPercentage' => $progressPercentage,

            'last_position_seconds' => $this->resolveLastPositionSeconds($progress),
            'lastPositionSeconds' => $this->resolveLastPositionSeconds($progress),

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

        return $query
            ->get()
            ->sortByDesc(function ($progress) {
                $activityAt = $this->resolveProgressActivityAt($progress);

                return $activityAt
                    ? Carbon::parse($activityAt)->timestamp
                    : 0;
            })
            ->values();
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

        $query = SubTopic::query();
        $relations = $this->resolveSubTopicRelations();

        if (!empty($relations)) {
            $query->with($relations);
        }

        if (Schema::hasColumn('sub_topics', 'batch_id') && $batchIds->isNotEmpty()) {
            $query->whereIn('batch_id', $batchIds);
        }

        if (Schema::hasColumn('sub_topics', 'is_active')) {
            $query->orderByRaw('CASE WHEN is_active = 1 THEN 0 ELSE 1 END');
        }

        foreach (['sort_order', 'order', 'position', 'id'] as $column) {
            if (Schema::hasColumn('sub_topics', $column)) {
                $query->orderBy($column);
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
            Schema::hasTable('stages')
            && Schema::hasColumn('modules', 'stage_id')
            && Schema::hasColumn('stages', 'program_id')
        ) {
            return DB::table('modules')
                ->join('stages', 'stages.id', '=', 'modules.stage_id')
                ->join('programs', 'programs.id', '=', 'stages.program_id')
                ->where('modules.id', $module->id)
                ->select('programs.*')
                ->first();
        }

        if (Schema::hasColumn('modules', 'program_id')) {
            return DB::table('modules')
                ->join('programs', 'programs.id', '=', 'modules.program_id')
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

        return collect(
            $assignments
                ->reject(fn (BatchAssignment $assignment) => $this->hasSubmittedAssignment($student, $assignment))
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
            ->when(Schema::hasColumn('batch_learning_quizzes', 'is_active'), fn ($query) => $query->where('is_active', true));

        if (Schema::hasColumn('batch_learning_quizzes', 'status')) {
            $query->whereNotIn('status', ['inactive', 'archived', 'cancelled', 'canceled']);
        }

        if (method_exists(BatchLearningQuiz::class, 'batch')) {
            $query->with('batch.program');
        }

        if (method_exists(BatchLearningQuiz::class, 'learningQuiz')) {
            $query->with('learningQuiz');
        }

        $quizzes = $query->get();

        return collect(
            $quizzes
                ->reject(fn ($quiz) => $this->hasCompletedQuiz($student, $quiz))
                ->map(fn ($quiz) => $this->formatQuizTask($quiz))
                ->all()
        )->values();
    }

    private function hasCompletedQuiz(Student $student, $quiz): bool
    {
        if (!Schema::hasTable('learning_quiz_attempts')) {
            return false;
        }

        $query = LearningQuizAttempt::query()
            ->where('student_id', $student->id);

        if (Schema::hasColumn('learning_quiz_attempts', 'batch_learning_quiz_id')) {
            $query->where('batch_learning_quiz_id', $quiz->id);
        } elseif (Schema::hasColumn('learning_quiz_attempts', 'learning_quiz_id') && isset($quiz->learning_quiz_id)) {
            $query->where('learning_quiz_id', $quiz->learning_quiz_id);
        }

        if (Schema::hasColumn('learning_quiz_attempts', 'status')) {
            $query->whereIn('status', [
                'submitted',
                'completed',
                'finished',
                'passed',
            ]);
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

    private function getDashboardUpcomingSessions(Student $student, Collection $programIds, Collection $batchIds): Collection
    {
        $mentoringSessions = collect();

        if (Schema::hasTable('student_mentoring_sessions')) {
            $query = StudentMentoringSession::query()
                ->where('student_id', $student->id)
                ->whereIn('status', ['pending', 'approved', 'rescheduled']);

            if (method_exists(StudentMentoringSession::class, 'instructor')) {
                $query->with('instructor');
            }

            if (method_exists(StudentMentoringSession::class, 'availabilitySlot')) {
                $query->with('availabilitySlot')
                    ->whereHas('availabilitySlot', function ($slotQuery) {
                        $slotQuery->whereDate('date', '>=', now()->toDateString());
                    });
            }

            $mentoringSessions = $query
                ->get()
                ->map(function (StudentMentoringSession $session) {
                    $slot = $session->availabilitySlot ?? null;
                    $date = $slot?->date;
                    $startTime = $slot?->start_time ? substr($slot->start_time, 0, 5) : null;
                    $endTime = $slot?->end_time ? substr($slot->end_time, 0, 5) : null;

                    $dateLabel = $date ? Carbon::parse($date)->format('d M Y') : '-';
                    $sortDate = $date ? Carbon::parse($date)->format('Y-m-d') : now()->addYears(10)->format('Y-m-d');
                    $timeLabel = $startTime && $endTime ? "{$startTime} - {$endTime}" : '-';

                    return [
                        'id' => $session->id,
                        'type' => 'mentoring',
                        'title' => '1-on-1 with ' . ($session->instructor?->name ?? 'Instructor'),
                        'subtitle' => $session->topic_type_label ?? Str::headline((string) ($session->topic_type ?? 'Mentoring')),
                        'time' => "{$dateLabel}, {$timeLabel}",
                        'status' => $session->status,
                        'badge_label' => $session->status_label ?? Str::headline((string) $session->status),
                        'join_url' => $session->status === 'approved' ? $session->meeting_url : null,
                        'meeting_url' => $session->status === 'approved' ? $session->meeting_url : null,
                        'sort_datetime' => trim($sortDate . ' ' . ($startTime ?: '00:00')),
                    ];
                });
        }

        $liveSessions = $this->getDashboardLiveSessions($programIds, $batchIds);

        return collect($mentoringSessions->all())
            ->merge(collect($liveSessions->all()))
            ->sortBy(fn (array $session) => $session['sort_datetime'] ?? now()->addYears(10)->toDateTimeString())
            ->take(5)
            ->values()
            ->map(function (array $session) {
                unset($session['sort_datetime']);

                return $session;
            });
    }

    private function getDashboardLiveSessions(Collection $programIds, Collection $batchIds): Collection
    {
        if (!Schema::hasTable('instructor_schedules')) {
            return collect();
        }

        if ($programIds->isEmpty() && $batchIds->isEmpty()) {
            return collect();
        }

        $query = DB::table('instructor_schedules')
            ->leftJoin('instructors', 'instructors.id', '=', 'instructor_schedules.instructor_id')
            ->leftJoin('batches', 'batches.id', '=', 'instructor_schedules.batch_id')
            ->leftJoin('programs', 'programs.id', '=', 'batches.program_id')
            ->whereDate('instructor_schedules.schedule_date', '>=', now()->toDateString());

        $query->where(function ($targetQuery) use ($programIds, $batchIds) {
            if ($batchIds->isNotEmpty() && Schema::hasColumn('instructor_schedules', 'batch_id')) {
                $targetQuery->orWhereIn('instructor_schedules.batch_id', $batchIds);
            }

            if ($programIds->isNotEmpty()) {
                if (Schema::hasColumn('instructor_schedules', 'program_id')) {
                    $targetQuery->orWhereIn('instructor_schedules.program_id', $programIds);
                }

                if (Schema::hasColumn('batches', 'program_id')) {
                    $targetQuery->orWhereIn('batches.program_id', $programIds);
                }
            }
        });

        if (Schema::hasColumn('instructor_schedules', 'status')) {
            $query->whereNotIn('instructor_schedules.status', [
                'cancelled',
                'canceled',
                'inactive',
            ]);
        }

        $selects = [
            'instructor_schedules.id',
            'instructor_schedules.schedule_date',
            'instructor_schedules.start_time',
            'instructor_schedules.end_time',
            'instructors.name as instructor_name',
            'batches.name as batch_name',
            'programs.name as program_name',
        ];

        if (Schema::hasColumn('instructor_schedules', 'meeting_url')) {
            $selects[] = 'instructor_schedules.meeting_url';
        }

        if (Schema::hasColumn('instructor_schedules', 'title')) {
            $selects[] = 'instructor_schedules.title';
        }

        if (Schema::hasColumn('instructor_schedules', 'topic')) {
            $selects[] = 'instructor_schedules.topic';
        }

        return $query
            ->select($selects)
            ->orderBy('instructor_schedules.schedule_date')
            ->orderBy('instructor_schedules.start_time')
            ->limit(5)
            ->get()
            ->map(function ($schedule) {
                $date = $schedule->schedule_date ?? null;
                $startTime = isset($schedule->start_time) ? substr($schedule->start_time, 0, 5) : null;
                $endTime = isset($schedule->end_time) ? substr($schedule->end_time, 0, 5) : null;

                $dateLabel = $date ? Carbon::parse($date)->format('d M Y') : '-';
                $sortDate = $date ? Carbon::parse($date)->format('Y-m-d') : now()->addYears(10)->format('Y-m-d');
                $timeLabel = $startTime && $endTime ? "{$startTime} - {$endTime}" : '-';

                $title = $schedule->title
                    ?? $schedule->topic
                    ?? 'Live Class';

                $subtitle = collect([
                    $schedule->program_name ?? null,
                    $schedule->batch_name ?? null,
                    $schedule->instructor_name ?? null,
                ])->filter()->implode(' • ');

                return [
                    'id' => $schedule->id,
                    'type' => 'live',
                    'title' => $title,
                    'subtitle' => $subtitle,
                    'time' => "{$dateLabel}, {$timeLabel}",
                    'status' => 'scheduled',
                    'badge_label' => 'Live Class',
                    'join_url' => $schedule->meeting_url ?? null,
                    'meeting_url' => $schedule->meeting_url ?? null,
                    'sort_datetime' => trim($sortDate . ' ' . ($startTime ?: '00:00')),
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
}
