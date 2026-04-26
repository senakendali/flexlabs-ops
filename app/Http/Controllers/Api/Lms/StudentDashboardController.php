<?php

namespace App\Http\Controllers\Api\Lms;

use App\Http\Controllers\Controller;
use App\Models\AssignmentSubmission;
use App\Models\BatchAssignment;
use App\Models\BatchLearningQuiz;
use App\Models\LearningQuizAttempt;
use App\Models\Student;
use App\Models\StudentLessonProgress;
use App\Models\SubTopic;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class StudentDashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->load([
            'student.activeEnrollments.program',
            'student.activeEnrollments.batch.program',
        ]);

        if (!$this->isStudentUser($user) || !$user->student) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized student account.',
            ], 403);
        }

        $student = $user->student;

        $activeEnrollments = $student->activeEnrollments
            ->filter(fn ($enrollment) => ($enrollment->is_accessible ?? true))
            ->values();

        if ($activeEnrollments->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Student belum memiliki enrollment aktif.',
            ], 403);
        }

        $batchIds = $activeEnrollments
            ->pluck('batch_id')
            ->filter()
            ->unique()
            ->values();

        $programIds = $activeEnrollments
            ->map(fn ($enrollment) => $enrollment->program?->id ?? $enrollment->batch?->program?->id)
            ->filter()
            ->unique()
            ->values();

        $subTopics = $this->getSubTopicsForPrograms($programIds);
        $progressRows = $this->getProgressRows($student, $subTopics);

        $courses = $activeEnrollments
            ->map(fn ($enrollment) => $this->formatCourse($enrollment, $student))
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

                'upcoming_sessions' => [],
                'announcements' => [],
            ],
        ]);
    }

    private function isStudentUser(User $user): bool
    {
        return ($user->user_type ?? null) === 'student'
            || ($user->role ?? null) === 'student';
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

                return $progress->last_watched_at->between($startOfWeek, $endOfWeek);
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
                    return $progress->completed_at->between($startOfWeek, $endOfWeek);
                }

                return $progress->updated_at?->between($startOfWeek, $endOfWeek) ?? false;
            })
            ->count();

        $tasksDone = $this->countTasksDoneThisWeek($student, $startOfWeek, $endOfWeek);

        $latestActivity = $progressRows
            ->filter(fn ($progress) => $progress->last_watched_at)
            ->sortByDesc(fn ($progress) => $progress->last_watched_at?->timestamp ?? 0)
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
        $assignmentCount = AssignmentSubmission::query()
            ->where('student_id', $student->id)
            ->whereIn('status', [
                'submitted',
                'late',
                'reviewed',
                'returned',
                'graded',
                'completed',
            ])
            ->whereBetween('updated_at', [$startOfWeek, $endOfWeek])
            ->count();

        $quizCount = LearningQuizAttempt::query()
            ->where('student_id', $student->id)
            ->whereIn('status', [
                'submitted',
                'graded',
                'completed',
                'passed',
            ])
            ->whereBetween('updated_at', [$startOfWeek, $endOfWeek])
            ->count();

        return $assignmentCount + $quizCount;
    }

    private function calculateCurrentStreak(Student $student): int
    {
        $activeDates = StudentLessonProgress::query()
            ->where('student_id', $student->id)
            ->whereNotNull('last_watched_at')
            ->where('last_watched_at', '>=', now()->subDays(30)->startOfDay())
            ->pluck('last_watched_at')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->unique()
            ->values();

        if ($activeDates->isEmpty()) {
            return 0;
        }

        $streak = 0;
        $cursor = now()->startOfDay();

        while ($activeDates->contains($cursor->toDateString())) {
            $streak++;
            $cursor->subDay();
        }

        return $streak;
    }

    private function formatWatchTime(int $seconds): string
    {
        if ($seconds <= 0) {
            return '0m';
        }

        $minutes = (int) round($seconds / 60);

        if ($minutes < 60) {
            return $minutes . 'm';
        }

        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        if ($remainingMinutes <= 0) {
            return $hours . 'h';
        }

        return $hours . 'h ' . $remainingMinutes . 'm';
    }

    private function formatLastActiveLabel(?Carbon $lastActiveAt): string
    {
        if (!$lastActiveAt) {
            return 'No activity yet';
        }

        if ($lastActiveAt->isToday()) {
            return 'Active today';
        }

        if ($lastActiveAt->isYesterday()) {
            return 'Active yesterday';
        }

        $days = (int) floor($lastActiveAt->diffInDays(now()));

        if ($days <= 1) {
            return 'Recently active';
        }

        return 'Active ' . $days . ' days ago';
    }

    private function formatCourse($enrollment, Student $student): array
    {
        $program = $enrollment->program ?? $enrollment->batch?->program;
        $batch = $enrollment->batch;

        $programId = $program->id ?? null;
        $programName = $program->name ?? 'Untitled Course';
        $courseSlug = $program ? $this->getProgramSlug($program) : $this->slugify($programName);

        $subTopics = $programId
            ? $this->getSubTopicsForProgram((int) $programId)
            : collect();

        $progressRows = $this->getProgressRows($student, $subTopics);

        $totalSubTopics = $subTopics->count();

        $completedSubTopics = $subTopics
            ->filter(function ($subTopic) use ($progressRows) {
                $progress = $progressRows->get($subTopic->id);

                return (bool) ($progress?->is_completed ?? false);
            })
            ->count();

        $progress = $totalSubTopics > 0
            ? (int) round(($completedSubTopics / $totalSubTopics) * 100)
            : 0;

        $nextSubTopic = $this->getNextSubTopicForProgram(
            subTopics: $subTopics,
            courseSlug: $courseSlug,
            progressRows: $progressRows
        );

        return [
            'id' => $programId ?? $enrollment->id,
            'slug' => $courseSlug,

            'title' => $programName,
            'name' => $programName,

            'category' => $this->getColumnValue($program, [
                'category',
                'program_type',
                'type',
            ]) ?: 'Learning Program',

            'description' => $this->getColumnValue($program, [
                'description',
                'summary',
                'short_description',
            ]) ?: 'Continue your learning progress with FlexLabs.',

            'status' => $progress >= 100 ? 'completed' : 'in_progress',
            'status_label' => $progress >= 100 ? 'Completed' : 'In Progress',

            'progress' => $progress,
            'progress_percentage' => $progress,

            'completed_lessons' => $completedSubTopics,
            'total_lessons' => $totalSubTopics,
            'completed_sub_topics' => $completedSubTopics,
            'total_sub_topics' => $totalSubTopics,

            'next_lesson' => $nextSubTopic['title'] ?? 'No next sub topic',
            'next_lesson_url' => $nextSubTopic['url'] ?? null,
            'next_sub_topic' => $nextSubTopic['title'] ?? 'No next sub topic',
            'next_sub_topic_url' => $nextSubTopic['url'] ?? null,

            'course_url' => '/courses/' . $courseSlug,

            'batch_id' => $batch->id ?? null,
            'batch_name' => $batch->name ?? null,

            'enrollment_id' => $enrollment->id,
            'enrollment_status' => $enrollment->status,
            'access_status' => $enrollment->access_status,
        ];
    }

    private function getPendingTasks(Student $student, Collection $batchIds): Collection
    {
        if ($batchIds->isEmpty()) {
            return collect();
        }

        $assignments = $this->getPendingAssignments($student, $batchIds);
        $quizzes = $this->getPendingQuizzes($student, $batchIds);

        return $assignments
            ->merge($quizzes)
            ->sortBy(function (array $task) {
                return $task['sort_deadline'] ?? now()->addYears(10)->timestamp;
            })
            ->values()
            ->take(8)
            ->map(function (array $task) {
                unset($task['sort_deadline']);

                return $task;
            });
    }

    private function getPendingAssignments(Student $student, Collection $batchIds): Collection
    {
        $submittedBatchAssignmentIds = AssignmentSubmission::query()
            ->where('student_id', $student->id)
            ->whereNotNull('batch_assignment_id')
            ->whereIn('status', [
                'submitted',
                'late',
                'reviewed',
                'returned',
                'graded',
                'completed',
            ])
            ->pluck('batch_assignment_id')
            ->filter()
            ->unique()
            ->values();

        $query = BatchAssignment::query()
            ->whereIn('batch_id', $batchIds)
            ->whereNotIn('id', $submittedBatchAssignmentIds);

        if (method_exists(BatchAssignment::class, 'batch')) {
            $query->with('batch');
        }

        $this->applyActiveFilter($query, 'batch_assignments');
        $this->applyPublishedFilter($query, 'batch_assignments');

        return $query
            ->get()
            ->map(fn ($assignment) => $this->formatAssignmentTask($assignment))
            ->values();
    }

    private function getPendingQuizzes(Student $student, Collection $batchIds): Collection
    {
        $completedBatchQuizIds = LearningQuizAttempt::query()
            ->where('student_id', $student->id)
            ->whereNotNull('batch_learning_quiz_id')
            ->whereIn('status', [
                'submitted',
                'graded',
                'completed',
                'passed',
            ])
            ->pluck('batch_learning_quiz_id')
            ->filter()
            ->unique()
            ->values();

        $query = BatchLearningQuiz::query()
            ->whereIn('batch_id', $batchIds)
            ->whereNotIn('id', $completedBatchQuizIds);

        if (method_exists(BatchLearningQuiz::class, 'batch')) {
            $query->with('batch');
        }

        if (method_exists(BatchLearningQuiz::class, 'learningQuiz')) {
            $query->with('learningQuiz');
        }

        $this->applyActiveFilter($query, 'batch_learning_quizzes');
        $this->applyPublishedFilter($query, 'batch_learning_quizzes');

        return $query
            ->get()
            ->map(fn ($quiz) => $this->formatQuizTask($quiz))
            ->values();
    }

    private function formatAssignmentTask($assignment): array
    {
        $deadline = $this->resolveDeadline($assignment, [
            'due_date',
            'deadline',
            'due_at',
            'closed_at',
        ]);

        $priority = $this->resolvePriority($deadline);

        return [
            'id' => $assignment->id,
            'type' => 'assignment',

            'title' => $this->getColumnValue($assignment, [
                'title',
                'name',
            ]) ?: 'Assignment',

            'course' => $assignment->batch?->name
                ?? $this->getColumnValue($assignment, ['batch_name'])
                ?? 'Course Assignment',

            'deadline' => $this->formatDeadlineLabel($deadline),
            'remaining' => $this->formatRemainingTime($deadline),

            'priority' => $priority,
            'priority_label' => $this->formatPriorityLabel($priority),

            'detail_url' => '/my-courses',
            'submit_url' => '/my-courses',

            'sort_deadline' => $deadline?->timestamp ?? now()->addYears(10)->timestamp,
        ];
    }

    private function formatQuizTask($quiz): array
    {
        $deadline = $this->resolveDeadline($quiz, [
            'due_date',
            'deadline',
            'closed_at',
            'end_at',
            'available_until',
        ]);

        $priority = $this->resolvePriority($deadline);

        $quizTitle = $this->getColumnValue($quiz, [
            'title',
            'name',
        ]);

        if (!$quizTitle && isset($quiz->learningQuiz)) {
            $quizTitle = $this->getColumnValue($quiz->learningQuiz, [
                'title',
                'name',
            ]);
        }

        return [
            'id' => $quiz->id,
            'type' => 'quiz',

            'title' => $quizTitle ?: 'Quiz',

            'course' => $quiz->batch?->name
                ?? $this->getColumnValue($quiz, ['batch_name'])
                ?? 'Course Quiz',

            'deadline' => $this->formatDeadlineLabel($deadline),
            'remaining' => $this->formatRemainingTime($deadline),

            'priority' => $priority,
            'priority_label' => $this->formatPriorityLabel($priority),

            'detail_url' => '/my-courses',
            'submit_url' => '/my-courses',

            'sort_deadline' => $deadline?->timestamp ?? now()->addYears(10)->timestamp,
        ];
    }

    private function resolveCurrentLesson(Collection $subTopics, Collection $progressRows): ?array
    {
        if ($subTopics->isEmpty()) {
            return null;
        }

        $watchedProgress = $progressRows
            ->filter(function ($progress) {
                return !$progress->is_completed
                    && (int) $progress->last_position_seconds > 0;
            })
            ->sortByDesc(function ($progress) {
                return $progress->last_watched_at?->timestamp ?? 0;
            })
            ->first();

        if ($watchedProgress) {
            $subTopic = $subTopics->firstWhere('id', $watchedProgress->sub_topic_id);

            if ($subTopic) {
                return $this->formatCurrentLesson($subTopic, $watchedProgress);
            }
        }

        $firstNotCompleted = $subTopics->first(function ($subTopic) use ($progressRows) {
            $progress = $progressRows->get($subTopic->id);

            return !($progress?->is_completed ?? false);
        });

        if ($firstNotCompleted) {
            return $this->formatCurrentLesson(
                $firstNotCompleted,
                $progressRows->get($firstNotCompleted->id)
            );
        }

        $latestCompleted = $progressRows
            ->filter(fn ($progress) => (bool) $progress->is_completed)
            ->sortByDesc(function ($progress) {
                return $progress->completed_at?->timestamp
                    ?? $progress->updated_at?->timestamp
                    ?? 0;
            })
            ->first();

        if ($latestCompleted) {
            $subTopic = $subTopics->firstWhere('id', $latestCompleted->sub_topic_id);

            if ($subTopic) {
                return $this->formatCurrentLesson($subTopic, $latestCompleted);
            }
        }

        return null;
    }

    private function formatCurrentLesson($subTopic, ?StudentLessonProgress $progress = null): array
    {
        $topic = $subTopic->topic;
        $module = $topic?->module;
        $program = $module?->stage?->program;

        $title = $subTopic->name
            ?? $subTopic->title
            ?? 'Untitled Lesson';

        $courseSlug = $program
            ? $this->getProgramSlug($program)
            : 'course';

        $subTopicSlug = $this->getSubTopicSlug($subTopic);

        $progressPercentage = (float) ($progress?->progress_percentage ?? 0);
        $isCompleted = (bool) ($progress?->is_completed ?? false);

        if ($isCompleted && $progressPercentage < 100) {
            $progressPercentage = 100;
        }

        return [
            'id' => $subTopic->id,

            'title' => $title,

            'description' => $this->getColumnValue($subTopic, [
                'description',
                'summary',
                'content',
            ]) ?: 'Continue your current learning activity.',

            'duration_minutes' => $this->resolveDurationMinutes($subTopic),

            'thumbnail_url' => $this->resolveThumbnailUrl($subTopic, $program),

            'module' => $module->name ?? $module->title ?? '-',
            'module_title' => $module->name ?? $module->title ?? '-',

            'topic' => $topic->name ?? $topic->title ?? '-',
            'topic_title' => $topic->name ?? $topic->title ?? '-',

            'status' => $isCompleted ? 'Completed' : 'In Progress',
            'status_label' => $isCompleted ? 'Completed' : 'In Progress',

            'is_completed' => $isCompleted,
            'progress_percentage' => round($progressPercentage, 2),
            'last_position_seconds' => (int) ($progress?->last_position_seconds ?? 0),

            'learn_url' => '/learn/' . $courseSlug . '/' . $subTopicSlug,
        ];
    }

    private function getSubTopicsForPrograms(Collection $programIds): Collection
    {
        if ($programIds->isEmpty()) {
            return collect();
        }

        $query = SubTopic::query()
            ->with([
                'topic.module.stage.program',
            ])
            ->whereHas('topic.module.stage', function ($stageQuery) use ($programIds) {
                $stageQuery->whereIn('program_id', $programIds);
            });

        if (Schema::hasColumn('sub_topics', 'is_active')) {
            $query->where('is_active', true);
        }

        return $this->sortSubTopics($query->get());
    }

    private function getSubTopicsForProgram(int $programId): Collection
    {
        $query = SubTopic::query()
            ->with([
                'topic.module.stage.program',
            ])
            ->whereHas('topic.module.stage', function ($stageQuery) use ($programId) {
                $stageQuery->where('program_id', $programId);
            });

        if (Schema::hasColumn('sub_topics', 'is_active')) {
            $query->where('is_active', true);
        }

        return $this->sortSubTopics($query->get());
    }

    private function sortSubTopics(Collection $subTopics): Collection
    {
        return $subTopics
            ->sortBy(function ($subTopic) {
                return sprintf(
                    '%06d-%06d-%06d-%06d-%06d',
                    $subTopic->topic?->module?->stage?->sort_order ?? 999999,
                    $subTopic->topic?->module?->sort_order ?? 999999,
                    $subTopic->topic?->sort_order ?? 999999,
                    $subTopic->sort_order ?? 999999,
                    $subTopic->id ?? 999999
                );
            })
            ->values();
    }

    private function getProgressRows(Student $student, Collection $subTopics): Collection
    {
        $subTopicIds = $subTopics
            ->pluck('id')
            ->filter()
            ->unique()
            ->values();

        if ($subTopicIds->isEmpty()) {
            return collect();
        }

        return StudentLessonProgress::query()
            ->where('student_id', $student->id)
            ->whereIn('sub_topic_id', $subTopicIds)
            ->get()
            ->keyBy('sub_topic_id');
    }

    private function getNextSubTopicForProgram(Collection $subTopics, string $courseSlug, Collection $progressRows): ?array
    {
        foreach ($subTopics as $subTopic) {
            $progress = $progressRows->get($subTopic->id);

            if (!($progress?->is_completed ?? false)) {
                $title = $subTopic->name
                    ?? $subTopic->title
                    ?? 'Untitled Sub Topic';

                $slug = $this->getSubTopicSlug($subTopic);

                return [
                    'id' => $subTopic->id,
                    'title' => $title,
                    'url' => '/learn/' . $courseSlug . '/' . $slug,
                ];
            }
        }

        return null;
    }

    private function applyActiveFilter(Builder $query, string $table): void
    {
        if (Schema::hasColumn($table, 'is_active')) {
            $query->where('is_active', true);
        }
    }

    private function applyPublishedFilter(Builder $query, string $table): void
    {
        if (Schema::hasColumn($table, 'status')) {
            $query->whereIn('status', [
                'published',
                'active',
                'open',
            ]);
        }
    }

    private function resolveDeadline($model, array $columns): ?Carbon
    {
        foreach ($columns as $column) {
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

        return $deadline->format('d M Y');
    }

    private function formatRemainingTime(?Carbon $deadline): string
    {
        if (!$deadline) {
            return 'No deadline';
        }

        $seconds = now()->diffInSeconds($deadline, false);

        if ($seconds < 0) {
            $daysLate = (int) ceil(abs($seconds) / 86400);

            return $daysLate <= 1
                ? 'Overdue'
                : $daysLate . ' days overdue';
        }

        $daysLeft = (int) ceil($seconds / 86400);

        if ($daysLeft <= 0) {
            return 'Due today';
        }

        if ($daysLeft === 1) {
            return '1 day left';
        }

        return $daysLeft . ' days left';
    }

    private function resolvePriority(?Carbon $deadline): string
    {
        if (!$deadline) {
            return 'normal';
        }

        $seconds = now()->diffInSeconds($deadline, false);

        if ($seconds < 0) {
            return 'high';
        }

        $daysLeft = (int) ceil($seconds / 86400);

        if ($daysLeft <= 2) {
            return 'high';
        }

        if ($daysLeft <= 7) {
            return 'medium';
        }

        return 'normal';
    }

    private function formatPriorityLabel(string $priority): string
    {
        return match ($priority) {
            'high' => 'High',
            'medium' => 'Medium',
            default => 'Normal',
        };
    }

    private function resolveDurationMinutes($subTopic): ?int
    {
        $durationMinutes = $this->getColumnValue($subTopic, [
            'video_duration_minutes',
            'duration_minutes',
        ]);

        return $durationMinutes
            ? (int) $durationMinutes
            : null;
    }

    private function resolveThumbnailUrl($subTopic, $program = null): ?string
    {
        return $this->getColumnValue($subTopic, [
            'thumbnail_url',
            'thumbnail',
            'image_url',
            'image',
        ]) ?: $this->getColumnValue($program, [
            'thumbnail_url',
            'thumbnail',
            'image_url',
            'image',
        ]);
    }

    private function getProgramSlug($program): string
    {
        $slug = $this->getColumnValue($program, ['slug']);

        if ($slug) {
            return $this->slugify($slug);
        }

        return $this->slugify($program->name ?? 'course');
    }

    private function getSubTopicSlug($subTopic): string
    {
        $slug = $this->getColumnValue($subTopic, ['slug']);

        if ($slug) {
            return $this->slugify($slug);
        }

        return $this->slugify($subTopic->name ?? $subTopic->title ?? 'sub-topic');
    }

    private function getColumnValue($model, array $columns)
    {
        if (!$model) {
            return null;
        }

        foreach ($columns as $column) {
            if (!Schema::hasColumn($model->getTable(), $column)) {
                continue;
            }

            if (!empty($model->{$column})) {
                return $model->{$column};
            }
        }

        return null;
    }

    private function slugify(?string $value): string
    {
        return str($value ?: 'item')->slug()->toString();
    }
}