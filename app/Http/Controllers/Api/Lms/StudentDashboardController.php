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

        $user->load([
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

        $batchIds = $activeEnrollments
            ->pluck('batch_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $programIds = $activeEnrollments
            ->map(fn ($enrollment) => $enrollment->program?->id
                ?? $enrollment->program_id
                ?? $enrollment->batch?->program?->id
                ?? $enrollment->batch?->program_id)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $subTopics = $this->getSubTopicsForPrograms($programIds);
        $progressRows = $this->getProgressRows($student, $subTopics);

        $courses = $activeEnrollments
            ->map(fn ($enrollment) => $this->formatCourse($enrollment, $student))
            ->filter()
            ->unique('id')
            ->values();

        $pendingTasks = $this->getPendingTasks($student, $batchIds);

        $currentLesson = $this->resolveCurrentLesson(
            subTopics: $subTopics,
            progressRows: $progressRows
        );

        $summary = $this->formatSummary($courses, $pendingTasks);

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
            || ($user->role ?? null) === 'student';
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
                ];

                $validAccessStatuses = [
                    '',
                    'active',
                    'enabled',
                    'open',
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
            'name' => $student->full_name,
            'full_name' => $student->full_name,
            'email' => $student->email,
            'phone' => $student->phone,
            'city' => $student->city,
            'current_status' => $student->current_status,
            'goal' => $student->goal,
            'source' => $student->source,
            'status' => $student->status,
            'role' => 'FlexLabs Student',
            'avatar_url' => null,
        ];
    }

    private function formatSummary(Collection $courses, Collection $pendingTasks): array
    {
        $totalSubTopics = (int) $courses->sum('total_sub_topics');
        $completedSubTopics = (int) $courses->sum('completed_sub_topics');

        $progress = $totalSubTopics > 0
            ? (int) round(($completedSubTopics / $totalSubTopics) * 100)
            : 0;

        return [
            'progress_percentage' => $progress,
            'completed_lessons' => $completedSubTopics,
            'completed_sub_topics' => $completedSubTopics,
            'total_lessons' => $totalSubTopics,
            'total_sub_topics' => $totalSubTopics,
            'pending_tasks_count' => $pendingTasks->count(),
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
                if (!$progress->last_watched_at) {
                    return false;
                }

                return Carbon::parse($progress->last_watched_at)->between($startOfWeek, $endOfWeek);
            })
            ->values();

        $watchSeconds = $weeklyProgress->sum(function ($progress) {
            $lastPosition = (int) ($progress->last_position_seconds ?? 0);
            $duration = (int) ($progress->duration_seconds ?? 0);

            if ($duration > 0) {
                return min($lastPosition, $duration);
            }

            return $lastPosition;
        });

        $completedSubTopics = $progressRows
            ->filter(function ($progress) use ($startOfWeek, $endOfWeek) {
                if (!$progress->is_completed) {
                    return false;
                }

                if ($progress->completed_at) {
                    return Carbon::parse($progress->completed_at)->between($startOfWeek, $endOfWeek);
                }

                return $progress->updated_at
                    ? Carbon::parse($progress->updated_at)->between($startOfWeek, $endOfWeek)
                    : false;
            })
            ->count();

        $tasksDone = $this->countTasksDoneThisWeek($student, $startOfWeek, $endOfWeek);

        $latestActivity = $progressRows
            ->filter(fn ($progress) => $progress->last_watched_at)
            ->sortByDesc(fn ($progress) => Carbon::parse($progress->last_watched_at)->timestamp)
            ->first();

        return [
            'period_label' => $startOfWeek->format('d M') . ' - ' . $endOfWeek->format('d M Y'),

            'total_watch_seconds' => $watchSeconds,
            'total_watch_minutes' => (int) round($watchSeconds / 60),
            'total_watch_time_label' => $this->formatWatchTime($watchSeconds),

            'completed_sub_topics' => $completedSubTopics,
            'tasks_done' => $tasksDone,

            'current_streak' => $this->calculateCurrentStreak($student),
            'last_active_label' => $this->formatLastActiveLabel($latestActivity?->last_watched_at),
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
        if (!Schema::hasTable('student_lesson_progress')) {
            return 0;
        }

        $dateColumn = Schema::hasColumn('student_lesson_progress', 'last_watched_at')
            ? 'last_watched_at'
            : 'updated_at';

        $dates = StudentLessonProgress::query()
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

        $query = SubTopic::query();

        if (method_exists(SubTopic::class, 'topic')) {
            $query
                ->with([
                    'topic.module.program',
                ])
                ->whereHas('topic.module', function ($moduleQuery) use ($programIds) {
                    if (Schema::hasColumn('modules', 'program_id')) {
                        $moduleQuery->whereIn('program_id', $programIds);
                    }
                });
        } elseif (
            Schema::hasTable('topics')
            && Schema::hasTable('modules')
            && Schema::hasColumn('sub_topics', 'topic_id')
            && Schema::hasColumn('topics', 'module_id')
            && Schema::hasColumn('modules', 'program_id')
        ) {
            $subTopicIds = DB::table('sub_topics')
                ->join('topics', 'topics.id', '=', 'sub_topics.topic_id')
                ->join('modules', 'modules.id', '=', 'topics.module_id')
                ->whereIn('modules.program_id', $programIds)
                ->pluck('sub_topics.id');

            $query->whereIn('id', $subTopicIds);
        } else {
            return collect();
        }

        if (Schema::hasColumn('sub_topics', 'is_active')) {
            $query->where('is_active', true);
        }

        if (Schema::hasColumn('sub_topics', 'sort_order')) {
            $query->orderBy('sort_order');
        }

        return $query
            ->orderBy('id')
            ->get();
    }

    private function getProgressRows(Student $student, Collection $subTopics): Collection
    {
        if (!Schema::hasTable('student_lesson_progress') || $subTopics->isEmpty()) {
            return collect();
        }

        return StudentLessonProgress::query()
            ->where('student_id', $student->id)
            ->whereIn('sub_topic_id', $subTopics->pluck('id'))
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
            ->where('is_completed', true)
            ->pluck('sub_topic_id')
            ->unique()
            ->count();

        $progress = $totalSubTopics > 0
            ? (int) round(($completedSubTopics / $totalSubTopics) * 100)
            : 0;

        $nextLesson = $this->resolveNextLessonTitle($subTopics, $progressRows);

        return [
            'id' => $program->id,
            'title' => $program->name ?? $program->title ?? 'Untitled Course',
            'name' => $program->name ?? $program->title ?? 'Untitled Course',
            'slug' => $program->slug ?? $program->id,

            'batch_id' => $enrollment->batch_id,
            'batch_name' => $enrollment->batch?->name,

            'next_lesson' => $nextLesson,
            'next_sub_topic' => $nextLesson,

            'progress' => $progress,
            'progress_percentage' => $progress,

            'completed_sub_topics' => $completedSubTopics,
            'total_sub_topics' => $totalSubTopics,

            'completed_lessons' => $completedSubTopics,
            'total_lessons' => $totalSubTopics,

            'course_url' => '/my-courses/' . ($program->slug ?? $program->id),
        ];
    }

    private function resolveNextLessonTitle(Collection $subTopics, Collection $progressRows): string
    {
        if ($subTopics->isEmpty()) {
            return 'No next lesson';
        }

        $completedSubTopicIds = $progressRows
            ->where('is_completed', true)
            ->pluck('sub_topic_id')
            ->unique()
            ->values();

        $nextSubTopic = $subTopics
            ->first(fn ($subTopic) => !$completedSubTopicIds->contains($subTopic->id));

        if (!$nextSubTopic) {
            return 'All lessons completed';
        }

        return $this->getColumnValue($nextSubTopic, [
            'name',
            'title',
        ]) ?: 'Untitled Lesson';
    }

    private function resolveCurrentLesson(Collection $subTopics, Collection $progressRows): ?array
    {
        if ($subTopics->isEmpty()) {
            return null;
        }

        $progressBySubTopic = $progressRows->keyBy('sub_topic_id');

        $currentSubTopic = $subTopics->first(function ($subTopic) use ($progressBySubTopic) {
            $progress = $progressBySubTopic->get($subTopic->id);

            return !$progress || !$progress->is_completed;
        });

        if (!$currentSubTopic) {
            $currentSubTopic = $subTopics->last();
        }

        if (!$currentSubTopic) {
            return null;
        }

        $progress = $progressBySubTopic->get($currentSubTopic->id);

        $program = $currentSubTopic->topic?->module?->program ?? null;
        $module = $currentSubTopic->topic?->module ?? null;
        $topic = $currentSubTopic->topic ?? null;

        $progressPercentage = (int) ($progress->progress_percentage ?? 0);

        if (!$progressPercentage) {
            $duration = (int) ($progress->duration_seconds ?? 0);
            $lastPosition = (int) ($progress->last_position_seconds ?? 0);

            $progressPercentage = $duration > 0
                ? (int) min(100, round(($lastPosition / $duration) * 100))
                : 0;
        }

        $isCompleted = (bool) ($progress->is_completed ?? false);

        $courseSlug = $program?->slug ?? $program?->id ?? 'course';
        $lessonSlug = $currentSubTopic->slug ?? $currentSubTopic->id;

        return [
            'id' => $currentSubTopic->id,
            'title' => $this->getColumnValue($currentSubTopic, [
                'name',
                'title',
            ]) ?: 'Untitled Lesson',

            'description' => $this->getColumnValue($currentSubTopic, [
                'description',
                'summary',
            ]) ?: 'Continue your current learning activity.',

            'duration_minutes' => $this->getColumnValue($currentSubTopic, [
                'duration_minutes',
                'duration',
            ]),

            'thumbnail_url' => $this->getColumnValue($currentSubTopic, [
                'thumbnail_url',
                'image',
                'thumbnail',
            ]),

            'module' => $this->getColumnValue($module, ['name', 'title']) ?: '-',
            'module_title' => $this->getColumnValue($module, ['name', 'title']) ?: '-',

            'topic' => $this->getColumnValue($topic, ['name', 'title']) ?: '-',
            'topic_title' => $this->getColumnValue($topic, ['name', 'title']) ?: '-',

            'status' => $isCompleted ? 'Completed' : 'In Progress',
            'status_label' => $isCompleted ? 'Completed' : 'In Progress',
            'is_completed' => $isCompleted,

            'progress_percentage' => max(0, min(100, $progressPercentage)),
            'video_progress_percentage' => max(0, min(100, $progressPercentage)),

            'last_position_seconds' => (int) ($progress->last_position_seconds ?? 0),

            'learn_url' => '/learn/' . $courseSlug . '/' . $lessonSlug,
        ];
    }

    private function getPendingTasks(Student $student, Collection $batchIds): Collection
    {
        return $this->getPendingAssignments($student, $batchIds)
            ->merge($this->getPendingQuizzes($student, $batchIds))
            ->sortBy('sort_deadline')
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

        return $assignments
            ->reject(fn (BatchAssignment $assignment) => $this->hasSubmittedAssignment($student, $assignment))
            ->map(fn (BatchAssignment $assignment) => $this->formatAssignmentTask($assignment))
            ->values();
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

        $title = $this->getColumnValue($relatedAssignment, [
            'title',
            'name',
        ]) ?: $this->getColumnValue($assignment, [
            'title',
            'name',
        ]) ?: 'Assignment';

        $courseName = $assignment->batch?->program?->name
            ?? $assignment->batch?->name
            ?? 'Course Assignment';

        return [
            'id' => $assignment->id,
            'type' => 'assignment',

            'title' => $title,
            'course' => $courseName,
            'course_name' => $courseName,

            'deadline' => $this->formatDeadlineLabel($deadline),
            'deadline_label' => $this->formatDeadlineLabel($deadline),

            'remaining' => $this->formatRemainingTime($deadline),
            'remaining_label' => $this->formatRemainingTime($deadline),

            'priority' => $priority,
            'priority_label' => $this->formatPriorityLabel($priority),

            'detail_url' => '/assignments/' . $assignment->id,
            'submit_url' => '/assignments/' . $assignment->id . '/submit',

            'sort_deadline' => $deadline?->timestamp ?? now()->addYears(10)->timestamp,
        ];
    }

    private function getPendingQuizzes(Student $student, Collection $batchIds): Collection
    {
        if ($batchIds->isEmpty() || !Schema::hasTable('batch_learning_quizzes')) {
            return collect();
        }

        $quizzes = BatchLearningQuiz::query()
            ->whereIn('batch_id', $batchIds)
            ->when(Schema::hasColumn('batch_learning_quizzes', 'is_active'), fn ($query) => $query->where('is_active', true))
            ->get();

        return $quizzes
            ->reject(fn ($quiz) => $this->hasCompletedQuiz($student, $quiz))
            ->map(fn ($quiz) => $this->formatQuizTask($quiz))
            ->values();
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

        $title = $this->getColumnValue($quiz, [
            'title',
            'name',
            'quiz_title',
        ]) ?: 'Quiz';

        return [
            'id' => $quiz->id,
            'type' => 'quiz',

            'title' => $title,
            'course' => 'Learning Quiz',
            'course_name' => 'Learning Quiz',

            'deadline' => $this->formatDeadlineLabel($deadline),
            'deadline_label' => $this->formatDeadlineLabel($deadline),

            'remaining' => $this->formatRemainingTime($deadline),
            'remaining_label' => $this->formatRemainingTime($deadline),

            'priority' => $priority,
            'priority_label' => $this->formatPriorityLabel($priority),

            'detail_url' => '/my-courses',
            'submit_url' => '/my-courses',

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

        return Announcement::query()
            ->with(['program', 'batch.program'])
            ->visibleNow()
            ->forProgramsAndBatches($programIds, $batchIds)
            ->orderByDesc('is_pinned')
            ->latest('publish_at')
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(function (Announcement $announcement) {
                $content = (string) ($announcement->content ?? '');

                return [
                    'id' => $announcement->id,
                    'title' => $announcement->title,
                    'slug' => $announcement->slug,
                    'excerpt' => Str::limit(strip_tags($content), 120),
                    'is_pinned' => (bool) $announcement->is_pinned,
                    'publish_at_label' => $announcement->publish_at?->format('d M Y H:i'),
                    'url' => '/announcements/' . $announcement->slug,
                ];
            })
            ->values();
    }

    private function getDashboardUpcomingSessions(Student $student, Collection $programIds, Collection $batchIds): Collection
    {
        $mentoringSessions = collect();

        if (Schema::hasTable('student_mentoring_sessions')) {
            $mentoringSessions = StudentMentoringSession::query()
                ->with(['instructor', 'availabilitySlot'])
                ->where('student_id', $student->id)
                ->whereIn('status', ['pending', 'approved', 'rescheduled'])
                ->whereHas('availabilitySlot', function ($query) {
                    $query->whereDate('date', '>=', now()->toDateString());
                })
                ->get()
                ->map(function (StudentMentoringSession $session) {
                    $slot = $session->availabilitySlot;

                    $date = $slot?->date;
                    $startTime = $slot?->start_time ? substr($slot->start_time, 0, 5) : null;
                    $endTime = $slot?->end_time ? substr($slot->end_time, 0, 5) : null;

                    $dateLabel = $date ? Carbon::parse($date)->format('d M Y') : '-';
                    $timeLabel = $startTime && $endTime ? "{$startTime} - {$endTime}" : '-';

                    return [
                        'id' => $session->id,
                        'type' => 'mentoring',
                        'title' => '1-on-1 with ' . ($session->instructor?->name ?? 'Instructor'),
                        'subtitle' => $session->topic_type_label,
                        'time' => "{$dateLabel}, {$timeLabel}",
                        'status' => $session->status,
                        'badge_label' => $session->status_label,
                        'join_url' => $session->status === 'approved' ? $session->meeting_url : null,
                        'meeting_url' => $session->status === 'approved' ? $session->meeting_url : null,
                        'sort_datetime' => trim(($date?->format('Y-m-d') ?? '') . ' ' . ($startTime ?: '00:00')),
                    ];
                });
        }

        $liveSessions = $this->getDashboardLiveSessions($programIds, $batchIds);

        return $mentoringSessions
            ->merge($liveSessions)
            ->sortBy('sort_datetime')
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

            if (
                $programIds->isNotEmpty()
                && Schema::hasColumn('instructor_schedules', 'program_id')
            ) {
                $targetQuery->orWhereIn('instructor_schedules.program_id', $programIds);
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
                    'sort_datetime' => trim(($date ?: '') . ' ' . ($startTime ?: '00:00')),
                ];
            })
            ->values();
    }

    private function getColumnValue($model, array $columns)
    {
        if (!$model || !method_exists($model, 'getTable')) {
            return null;
        }

        foreach ($columns as $column) {
            if (!Schema::hasColumn($model->getTable(), $column)) {
                continue;
            }

            if (!blank($model->{$column})) {
                return $model->{$column};
            }
        }

        return null;
    }
}