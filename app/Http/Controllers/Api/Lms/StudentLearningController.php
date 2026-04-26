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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class StudentLearningController extends Controller
{
    public function show(Request $request, string $courseSlug, string $lessonSlug): JsonResponse
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
            ->filter(fn ($enrollment) => $enrollment->is_accessible)
            ->values();

        if ($activeEnrollments->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Student belum memiliki enrollment aktif.',
            ], 403);
        }

        $enrollment = $this->findEnrollmentByCourseSlug($activeEnrollments, $courseSlug);

        if (!$enrollment) {
            return response()->json([
                'success' => false,
                'message' => 'Course tidak ditemukan atau student belum memiliki akses ke course ini.',
            ], 404);
        }

        $program = $enrollment->program ?? $enrollment->batch?->program;

        if (!$program) {
            return response()->json([
                'success' => false,
                'message' => 'Data program untuk course ini tidak ditemukan.',
            ], 404);
        }

        $resolvedCourseSlug = $this->getProgramSlug($program);

        $subTopics = $this->getSubTopicsForProgram((int) $program->id);

        if ($subTopics->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Course ini belum memiliki sub topic.',
            ], 404);
        }

        $currentSubTopic = $this->findSubTopicBySlug($subTopics, $lessonSlug);

        if (!$currentSubTopic) {
            return response()->json([
                'success' => false,
                'message' => 'Sub topic tidak ditemukan di course ini.',
            ], 404);
        }

        $orderedSubTopics = $subTopics->values();

        $currentIndex = $orderedSubTopics->search(
            fn ($item) => (int) $item->id === (int) $currentSubTopic->id
        );

        $previousSubTopic = $currentIndex !== false && $currentIndex > 0
            ? $orderedSubTopics->get($currentIndex - 1)
            : null;

        $nextSubTopic = $currentIndex !== false && $currentIndex < ($orderedSubTopics->count() - 1)
            ? $orderedSubTopics->get($currentIndex + 1)
            : null;

        $progressRows = StudentLessonProgress::query()
            ->where('student_id', $student->id)
            ->whereIn('sub_topic_id', $orderedSubTopics->pluck('id'))
            ->get()
            ->keyBy('sub_topic_id');

        $currentProgress = $progressRows->get($currentSubTopic->id);
        $currentTopic = $currentSubTopic->topic;

        $batchIds = $activeEnrollments
            ->pluck('batch_id')
            ->filter()
            ->unique()
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'student' => $this->formatStudent($student),

                'notification_count' => $this->countPendingTasks($student, $batchIds),

                'course' => $this->formatCourse($enrollment, $resolvedCourseSlug),

                'lesson' => $this->formatLesson(
                    $currentSubTopic,
                    $resolvedCourseSlug,
                    true,
                    $currentProgress
                ),

                /**
                 * Topic-level resources.
                 * Ini yang dipakai sidebar LearningPage.
                 */
                'topic' => $this->formatTopic($currentTopic),
                'topic_resources' => $this->formatTopicResources($currentTopic),

                'learning_path' => $this->formatLearningPath(
                    $orderedSubTopics,
                    $resolvedCourseSlug,
                    (int) $currentSubTopic->id,
                    $progressRows
                ),

                'navigation' => [
                    'course_url' => '/courses/' . $resolvedCourseSlug,
                    'previous' => $previousSubTopic
                        ? $this->formatNavigationItem($previousSubTopic, $resolvedCourseSlug)
                        : null,
                    'next' => $nextSubTopic
                        ? $this->formatNavigationItem($nextSubTopic, $resolvedCourseSlug)
                        : null,
                ],
            ],
        ]);
    }

    public function saveProgress(Request $request, string $courseSlug, string $lessonSlug): JsonResponse
    {
        $validated = $request->validate([
            'last_position_seconds' => ['nullable', 'integer', 'min:0'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'progress_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_completed' => ['nullable', 'boolean'],
        ]);

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
            ->filter(fn ($enrollment) => $enrollment->is_accessible)
            ->values();

        if ($activeEnrollments->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Student belum memiliki enrollment aktif.',
            ], 403);
        }

        $enrollment = $this->findEnrollmentByCourseSlug($activeEnrollments, $courseSlug);

        if (!$enrollment) {
            return response()->json([
                'success' => false,
                'message' => 'Course tidak ditemukan atau student belum memiliki akses ke course ini.',
            ], 404);
        }

        $program = $enrollment->program ?? $enrollment->batch?->program;

        if (!$program) {
            return response()->json([
                'success' => false,
                'message' => 'Data program untuk course ini tidak ditemukan.',
            ], 404);
        }

        $subTopics = $this->getSubTopicsForProgram((int) $program->id);
        $subTopic = $this->findSubTopicBySlug($subTopics, $lessonSlug);

        if (!$subTopic) {
            return response()->json([
                'success' => false,
                'message' => 'Sub topic tidak ditemukan di course ini.',
            ], 404);
        }

        $lastPositionSeconds = (int) ($validated['last_position_seconds'] ?? 0);

        $durationSeconds = isset($validated['duration_seconds'])
            ? (int) $validated['duration_seconds']
            : null;

        $progressPercentage = $validated['progress_percentage'] ?? null;

        if ($progressPercentage === null && $durationSeconds && $durationSeconds > 0) {
            $progressPercentage = round(($lastPositionSeconds / $durationSeconds) * 100, 2);
        }

        $progressPercentage = (float) ($progressPercentage ?? 0);
        $progressPercentage = max(0, min(100, $progressPercentage));

        $requestCompleted = (bool) ($validated['is_completed'] ?? false);

        $autoCompleted = $durationSeconds
            && $durationSeconds > 0
            && $progressPercentage >= 95;

        $isCompleted = $requestCompleted || $autoCompleted;

        $progress = StudentLessonProgress::query()->firstOrNew([
            'student_id' => $student->id,
            'sub_topic_id' => $subTopic->id,
        ]);

        $wasAlreadyCompleted = (bool) $progress->is_completed;
        $finalIsCompleted = $wasAlreadyCompleted || $isCompleted;

        /**
         * Kalau sudah completed, jangan sampai auto-save berikutnya nurunin progress jadi 30%, 40%, dll.
         */
        $finalProgressPercentage = $finalIsCompleted
            ? 100
            : $progressPercentage;

        $progress->fill([
            'last_position_seconds' => $lastPositionSeconds,
            'duration_seconds' => $durationSeconds,
            'progress_percentage' => $finalProgressPercentage,
            'is_completed' => $finalIsCompleted,
            'last_watched_at' => now(),
        ]);

        if ($finalIsCompleted && !$progress->completed_at) {
            $progress->completed_at = now();
        }

        $progress->save();

        return response()->json([
            'success' => true,
            'message' => $progress->is_completed
                ? 'Lesson marked as completed.'
                : 'Learning progress saved.',
            'data' => [
                'progress' => [
                    'student_id' => $progress->student_id,
                    'sub_topic_id' => $progress->sub_topic_id,
                    'last_position_seconds' => (int) $progress->last_position_seconds,
                    'duration_seconds' => $progress->duration_seconds
                        ? (int) $progress->duration_seconds
                        : null,
                    'progress_percentage' => (float) $progress->progress_percentage,
                    'is_completed' => (bool) $progress->is_completed,
                    'completed_at' => $progress->completed_at,
                    'last_watched_at' => $progress->last_watched_at,
                ],
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

    private function findEnrollmentByCourseSlug(Collection $activeEnrollments, string $courseSlug)
    {
        $normalizedSlug = $this->slugify($courseSlug);

        return $activeEnrollments->first(function ($enrollment) use ($normalizedSlug, $courseSlug) {
            $program = $enrollment->program ?? $enrollment->batch?->program;

            if (!$program) {
                return false;
            }

            $programSlug = $this->getProgramSlug($program);

            return $programSlug === $normalizedSlug
                || (string) $program->id === (string) $courseSlug
                || (string) $enrollment->id === (string) $courseSlug;
        });
    }

    private function findSubTopicBySlug(Collection $subTopics, string $lessonSlug)
    {
        $normalizedSlug = $this->slugify($lessonSlug);

        return $subTopics->first(function ($subTopic) use ($normalizedSlug, $lessonSlug) {
            $title = $subTopic->name
                ?? $subTopic->title
                ?? 'untitled-sub-topic';

            $subTopicSlug = $this->getSubTopicSlug($subTopic);

            return $subTopicSlug === $normalizedSlug
                || $this->slugify($title) === $normalizedSlug
                || (string) $subTopic->id === (string) $lessonSlug;
        });
    }

    private function formatCourse($enrollment, string $courseSlug): array
    {
        $program = $enrollment->program ?? $enrollment->batch?->program;
        $batch = $enrollment->batch;

        $programName = $program->name ?? 'Untitled Course';

        return [
            'id' => $program->id ?? $enrollment->id,
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
            ]),

            'course_url' => '/courses/' . $courseSlug,

            'batch_id' => $batch->id ?? null,
            'batch_name' => $batch->name ?? null,

            'enrollment_id' => $enrollment->id,
            'enrollment_status' => $enrollment->status,
            'access_status' => $enrollment->access_status,
        ];
    }

    private function formatLesson(
        $subTopic,
        string $courseSlug,
        bool $isActive = false,
        ?StudentLessonProgress $progress = null
    ): array {
        $topic = $subTopic->topic;
        $module = $topic?->module;

        $title = $subTopic->name
            ?? $subTopic->title
            ?? 'Untitled Sub Topic';

        $slug = $this->getSubTopicSlug($subTopic);

        $videoUrl = $this->getColumnValue($subTopic, [
            'video_url',
            'video_embed_url',
            'youtube_url',
            'content_url',
        ]);

        $duration = $this->resolveSubTopicDuration($subTopic);

        $description = $this->getColumnValue($subTopic, [
            'description',
            'summary',
            'content',
        ]);

        $isCompleted = (bool) ($progress?->is_completed ?? false);

        $status = match (true) {
            $isCompleted => 'completed',
            $isActive => 'active',
            default => 'default',
        };

        return [
            'id' => $subTopic->id,
            'slug' => $slug,

            'title' => $title,
            'name' => $title,

            'description' => $description,

            'module_id' => $module->id ?? null,
            'module_title' => $module->name ?? $module->title ?? '-',

            'topic_id' => $topic->id ?? null,
            'topic_title' => $topic->name ?? $topic->title ?? '-',

            'duration' => $duration,
            'duration_label' => $duration,

            'video_url' => $videoUrl,
            'video_embed_url' => $this->normalizeYouTubeEmbedUrl($videoUrl),

            'last_position_seconds' => (int) ($progress?->last_position_seconds ?? 0),
            'duration_seconds' => $progress?->duration_seconds
                ? (int) $progress->duration_seconds
                : null,
            'progress_percentage' => (float) ($progress?->progress_percentage ?? 0),

            'status' => $status,
            'status_label' => match ($status) {
                'completed' => 'Completed',
                'active' => 'In Progress',
                default => 'Available',
            },

            'is_completed' => $isCompleted,
            'is_current' => $isActive,
            'is_locked' => false,

            'url' => '/learn/' . $courseSlug . '/' . $slug,
            'learn_url' => '/learn/' . $courseSlug . '/' . $slug,
        ];
    }

    private function formatTopic($topic): ?array
    {
        if (!$topic) {
            return null;
        }

        return [
            'id' => $topic->id,
            'name' => $topic->name ?? $topic->title ?? 'Untitled Topic',
            'title' => $topic->name ?? $topic->title ?? 'Untitled Topic',

            'description' => $this->getColumnValue($topic, [
                'description',
                'summary',
            ]),

            'slide_url' => $this->getColumnValue($topic, ['slide_url']),
            'starter_code_url' => $this->getColumnValue($topic, ['starter_code_url']),
            'supporting_file_url' => $this->getColumnValue($topic, ['supporting_file_url']),
            'external_reference_url' => $this->getColumnValue($topic, ['external_reference_url']),
            'practice_brief' => $this->getColumnValue($topic, ['practice_brief']),

            'resources' => $this->formatTopicResources($topic),
        ];
    }

    private function formatTopicResources($topic): array
    {
        if (!$topic) {
            return [];
        }

        $resources = [];

        $slideUrl = $this->getColumnValue($topic, ['slide_url']);

        if ($slideUrl) {
            $resources[] = [
                'key' => 'slide',
                'type' => 'slide',
                'title' => 'Slide Material',
                'description' => 'Presentation atau materi utama topic ini.',
                'url' => $slideUrl,
            ];
        }

        $starterCodeUrl = $this->getColumnValue($topic, ['starter_code_url']);

        if ($starterCodeUrl) {
            $resources[] = [
                'key' => 'starter_code',
                'type' => 'starter_code',
                'title' => 'Starter Code',
                'description' => 'Kode awal untuk mulai praktik.',
                'url' => $starterCodeUrl,
            ];
        }

        $supportingFileUrl = $this->getColumnValue($topic, ['supporting_file_url']);

        if ($supportingFileUrl) {
            $resources[] = [
                'key' => 'supporting_file',
                'type' => 'supporting_file',
                'title' => 'Supporting File',
                'description' => 'File pendukung untuk latihan atau referensi.',
                'url' => $supportingFileUrl,
            ];
        }

        $externalReferenceUrl = $this->getColumnValue($topic, ['external_reference_url']);

        if ($externalReferenceUrl) {
            $resources[] = [
                'key' => 'external_reference',
                'type' => 'external_reference',
                'title' => 'External Reference',
                'description' => 'Referensi tambahan dari luar materi.',
                'url' => $externalReferenceUrl,
            ];
        }

        $practiceBrief = $this->getColumnValue($topic, ['practice_brief']);

        if ($practiceBrief) {
            $resources[] = [
                'key' => 'practice_brief',
                'type' => 'practice_brief',
                'title' => 'Practice Brief',
                'description' => 'Instruksi praktik untuk topic ini.',
                'content' => $practiceBrief,
            ];
        }

        return $resources;
    }

    private function formatNavigationItem($subTopic, string $courseSlug): array
    {
        $title = $subTopic->name
            ?? $subTopic->title
            ?? 'Untitled Sub Topic';

        $slug = $this->getSubTopicSlug($subTopic);

        return [
            'id' => $subTopic->id,
            'title' => $title,
            'name' => $title,
            'slug' => $slug,
            'url' => '/learn/' . $courseSlug . '/' . $slug,
            'to' => '/learn/' . $courseSlug . '/' . $slug,
        ];
    }

    private function formatLearningPath(
        Collection $subTopics,
        string $courseSlug,
        int $activeSubTopicId,
        Collection $progressRows
    ): array {
        return $subTopics
            ->groupBy(function ($subTopic) {
                return $subTopic->topic?->module?->id ?? 'module-unknown';
            })
            ->map(function (Collection $moduleSubTopics, $moduleKey) use ($courseSlug, $activeSubTopicId, $progressRows) {
                $firstSubTopic = $moduleSubTopics->first();
                $module = $firstSubTopic?->topic?->module;

                $topics = $moduleSubTopics
                    ->groupBy(function ($subTopic) {
                        return $subTopic->topic?->id ?? 'topic-unknown';
                    })
                    ->map(function (Collection $topicSubTopics, $topicKey) use ($courseSlug, $activeSubTopicId, $progressRows) {
                        $firstTopicSubTopic = $topicSubTopics->first();
                        $topic = $firstTopicSubTopic?->topic;

                        return [
                            'id' => $topic->id ?? $topicKey,
                            'title' => $topic->name ?? $topic->title ?? 'Untitled Topic',
                            'name' => $topic->name ?? $topic->title ?? 'Untitled Topic',
                            'sub_topics' => $topicSubTopics
                                ->map(fn ($subTopic) => $this->formatLearningPathSubTopic(
                                    $subTopic,
                                    $courseSlug,
                                    $activeSubTopicId,
                                    $progressRows->get($subTopic->id)
                                ))
                                ->values()
                                ->toArray(),
                        ];
                    })
                    ->values()
                    ->toArray();

                return [
                    'id' => $module->id ?? $moduleKey,
                    'order' => $module->sort_order ?? null,
                    'sort_order' => $module->sort_order ?? null,
                    'title' => $module->name ?? $module->title ?? 'Untitled Module',
                    'name' => $module->name ?? $module->title ?? 'Untitled Module',
                    'topics' => $topics,
                ];
            })
            ->values()
            ->map(function (array $module, int $index) {
                $module['order'] = $module['order'] ?: $index + 1;

                return $module;
            })
            ->toArray();
    }

    private function formatLearningPathSubTopic(
        $subTopic,
        string $courseSlug,
        int $activeSubTopicId,
        ?StudentLessonProgress $progress = null
    ): array {
        $title = $subTopic->name
            ?? $subTopic->title
            ?? 'Untitled Sub Topic';

        $slug = $this->getSubTopicSlug($subTopic);

        $isCompleted = (bool) ($progress?->is_completed ?? false);

        $status = match (true) {
            (int) $subTopic->id === $activeSubTopicId => 'active',
            $isCompleted => 'completed',
            default => 'default',
        };

        return [
            'id' => $subTopic->id,
            'title' => $title,
            'name' => $title,
            'slug' => $slug,
            'status' => $status,

            'progress_percentage' => (float) ($progress?->progress_percentage ?? 0),
            'last_position_seconds' => (int) ($progress?->last_position_seconds ?? 0),
            'is_completed' => $isCompleted,

            'to' => '/learn/' . $courseSlug . '/' . $slug,
            'url' => '/learn/' . $courseSlug . '/' . $slug,
            'learn_url' => '/learn/' . $courseSlug . '/' . $slug,
        ];
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

        $subTopics = $query->get();

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

    private function countPendingTasks(Student $student, Collection $batchIds): int
    {
        if ($batchIds->isEmpty()) {
            return 0;
        }

        $submittedBatchAssignmentIds = AssignmentSubmission::query()
            ->where('student_id', $student->id)
            ->whereNotNull('batch_assignment_id')
            ->whereIn('status', [
                'submitted',
                'late',
                'reviewed',
                'returned',
            ])
            ->pluck('batch_assignment_id')
            ->filter()
            ->unique()
            ->values();

        $assignmentQuery = BatchAssignment::query()
            ->whereIn('batch_id', $batchIds)
            ->whereNotIn('id', $submittedBatchAssignmentIds);

        if (Schema::hasColumn('batch_assignments', 'is_active')) {
            $assignmentQuery->where('is_active', true);
        }

        if (Schema::hasColumn('batch_assignments', 'status')) {
            $assignmentQuery->whereIn('status', [
                'published',
                'active',
                'open',
            ]);
        }

        $assignmentCount = $assignmentQuery->count();

        $completedBatchQuizIds = LearningQuizAttempt::query()
            ->where('student_id', $student->id)
            ->whereNotNull('batch_learning_quiz_id')
            ->whereIn('status', [
                'submitted',
                'graded',
            ])
            ->pluck('batch_learning_quiz_id')
            ->filter()
            ->unique()
            ->values();

        $quizQuery = BatchLearningQuiz::query()
            ->whereIn('batch_id', $batchIds)
            ->whereNotIn('id', $completedBatchQuizIds);

        if (Schema::hasColumn('batch_learning_quizzes', 'is_active')) {
            $quizQuery->where('is_active', true);
        }

        if (Schema::hasColumn('batch_learning_quizzes', 'status')) {
            $quizQuery->whereIn('status', [
                'published',
                'active',
                'open',
            ]);
        }

        if (Schema::hasColumn('batch_learning_quizzes', 'available_at')) {
            $quizQuery->where(function ($availableQuery) {
                $availableQuery
                    ->whereNull('available_at')
                    ->orWhere('available_at', '<=', now());
            });
        }

        if (Schema::hasColumn('batch_learning_quizzes', 'closed_at')) {
            $quizQuery->where(function ($closedQuery) {
                $closedQuery
                    ->whereNull('closed_at')
                    ->orWhere('closed_at', '>=', now());
            });
        }

        $quizCount = $quizQuery->count();

        return $assignmentCount + $quizCount;
    }

    private function resolveSubTopicDuration($subTopic): ?string
    {
        $durationLabel = $this->getColumnValue($subTopic, [
            'duration_label',
            'video_duration_label',
        ]);

        if ($durationLabel) {
            return (string) $durationLabel;
        }

        $durationMinutes = $this->getColumnValue($subTopic, [
            'video_duration_minutes',
            'duration_minutes',
        ]);

        if ($durationMinutes) {
            return $durationMinutes . ' min';
        }

        return null;
    }

    private function normalizeYouTubeEmbedUrl(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        if (str_contains($url, '/embed/')) {
            return $url;
        }

        if (str_contains($url, 'youtu.be/')) {
            $id = str($url)->after('youtu.be/')->before('?')->toString();

            return $id ? 'https://www.youtube.com/embed/' . $id : $url;
        }

        if (str_contains($url, 'youtube.com/watch')) {
            parse_str(parse_url($url, PHP_URL_QUERY) ?: '', $query);

            return !empty($query['v'])
                ? 'https://www.youtube.com/embed/' . $query['v']
                : $url;
        }

        return $url;
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