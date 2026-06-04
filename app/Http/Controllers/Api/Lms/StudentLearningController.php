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
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class StudentLearningController extends Controller
{
    public function show(Request $request, string $courseSlug, string $lessonSlug): JsonResponse
    {
        // FAST_LESSON_SHOW_QUERY_V1
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

        /*
         |--------------------------------------------------------------------------
         | Fast path: ambil daftar lesson memakai flat SQL join.
         |--------------------------------------------------------------------------
         |
         | Relasi curriculum FlexLabs sudah jelas:
         | programs -> program_stages -> modules -> topics -> sub_topics.
         |
         | Jadi tidak perlu hydrate Eloquent nested topic.module.stage.program untuk
         | semua lesson. Content lesson juga tidak ikut ditarik untuk semua subtopic;
         | content hanya diambil untuk lesson aktif lewat hydrateCurrentLessonDetail().
         |
         */
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

        $currentSubTopic = $this->hydrateCurrentLessonDetail($currentSubTopic);
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

        $progressRows = $this->getProgressRowsForSubTopics(
            student: $student,
            subTopicIds: $orderedSubTopics->pluck('id')->filter()->unique()->values()
        );

        $currentProgress = $progressRows->get((int) $currentSubTopic->id);
        $currentTopic = $currentSubTopic->topic ?? null;

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

    public function streamLessonVideo(Request $request, string $courseSlug, string $lessonSlug)
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Invalid or expired video link.');
        }

        $subTopic = $this->resolveSubTopicForVideoStream($courseSlug, $lessonSlug);

        if (! $subTopic) {
            abort(404, 'Video lesson not found.');
        }

        if (! $this->hasSelfHostedVideo($subTopic)) {
            abort(404, 'Video file not available.');
        }

        $absolutePath = $this->resolveSelfHostedVideoAbsolutePath($subTopic);

        if (! $absolutePath || ! is_file($absolutePath) || ! is_readable($absolutePath)) {
            abort(404, 'Video file not found on server.');
        }

        $fileSize = filesize($absolutePath);

        if ($fileSize === false || $fileSize <= 0) {
            abort(404, 'Invalid video file.');
        }

        $mimeType = $this->resolveVideoMimeType($subTopic, $absolutePath);
        $fileName = $this->safeDownloadFileName(basename($absolutePath));

        $start = 0;
        $end = $fileSize - 1;
        $status = 200;

        $rangeHeader = $request->headers->get('Range');

        if ($rangeHeader && preg_match('/bytes=(\d*)-(\d*)/', $rangeHeader, $matches)) {
            $status = 206;

            if ($matches[1] === '' && $matches[2] !== '') {
                $suffixLength = (int) $matches[2];
                $start = max($fileSize - $suffixLength, 0);
            } else {
                $start = $matches[1] !== '' ? (int) $matches[1] : 0;
            }

            if ($matches[2] !== '') {
                $end = min((int) $matches[2], $fileSize - 1);
            }

            if ($start > $end || $start >= $fileSize) {
                return response('', 416, [
                    'Content-Range' => 'bytes */' . $fileSize,
                    'Accept-Ranges' => 'bytes',
                ]);
            }
        }

        $length = $end - $start + 1;

        $headers = [
            'Content-Type' => $mimeType,
            'Content-Length' => (string) $length,
            'Accept-Ranges' => 'bytes',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'X-Content-Type-Options' => 'nosniff',
        ];

        if ($status === 206) {
            $headers['Content-Range'] = "bytes {$start}-{$end}/{$fileSize}";
        }

        return response()->stream(function () use ($absolutePath, $start, $end) {
            $handle = fopen($absolutePath, 'rb');

            if ($handle === false) {
                return;
            }

            try {
                fseek($handle, $start);

                $bytesLeft = $end - $start + 1;
                $chunkSize = 1024 * 1024;

                while ($bytesLeft > 0 && ! feof($handle)) {
                    $readLength = min($chunkSize, $bytesLeft);
                    $buffer = fread($handle, $readLength);

                    if ($buffer === false || $buffer === '') {
                        break;
                    }

                    echo $buffer;

                    if (ob_get_level() > 0) {
                        ob_flush();
                    }

                    flush();

                    $bytesLeft -= strlen($buffer);

                    if (connection_aborted()) {
                        break;
                    }
                }
            } finally {
                fclose($handle);
            }
        }, $status, $headers);
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
        ?object $progress = null
    ): array {
        $topic = $subTopic->topic;
        $module = $topic?->module;

        $title = $subTopic->name
            ?? $subTopic->title
            ?? 'Untitled Sub Topic';

        $slug = $this->getSubTopicSlug($subTopic);
        $lessonType = $subTopic->lesson_type ?? 'video';

        $videoProvider = $this->resolveSubTopicVideoProvider($subTopic);
        $videoPlaybackUrl = $this->resolveSubTopicVideoPlaybackUrl($subTopic, $courseSlug, $slug);
        $videoEmbedUrl = $videoProvider === 'self_hosted'
            ? $videoPlaybackUrl
            : $this->normalizeYouTubeEmbedUrl($videoPlaybackUrl);

        $duration = $this->resolveSubTopicDuration($subTopic);
        $durationSeconds = $this->resolveSubTopicDurationSeconds($subTopic, $progress);

        $description = $this->getColumnValue($subTopic, [
            'description',
            'summary',
        ]);

        $content = $this->getColumnValue($subTopic, [
            'content',
        ]);

        $contentFormat = $this->getColumnValue($subTopic, [
            'content_format',
        ]) ?: 'markdown';

        $thumbnailUrl = $this->getColumnValue($subTopic, [
            'thumbnail_url',
            'thumbnail',
            'image_url',
        ]);

        if (! $thumbnailUrl && $lessonType === 'video') {
            $thumbnailUrl = asset('images/video-thumbnail.png');
        }

        if (! $thumbnailUrl && $lessonType === 'live_session') {
            $thumbnailUrl = asset('images/live-session.png');
        }

        $isCompleted = (bool) ($progress?->is_completed ?? false);

        $status = match (true) {
            $isCompleted => 'completed',
            $isActive => 'active',
            default => 'default',
        };

        /**
         * Untuk Bunny Stream, jangan kirim raw URL database lewat video_url.
         * Frontend memakai video_embed_url / bunny_embed_url / protected_video_url.
         */
        $publicVideoUrl = $videoProvider === 'bunny'
            ? null
            : $videoPlaybackUrl;

        return [
            'id' => $subTopic->id,
            'slug' => $slug,

            'title' => $title,
            'name' => $title,

            'description' => $description,

            // Lesson reading material / Markdown content
            'content' => $content,
            'content_format' => $contentFormat,
            'has_content' => ! empty($content),

            'module_id' => $module->id ?? null,
            'module_title' => $module->name ?? $module->title ?? '-',

            'topic_id' => $topic->id ?? null,
            'topic_title' => $topic->name ?? $topic->title ?? '-',

            'lesson_type' => $lessonType,
            'lessonType' => $lessonType,

            'duration' => $duration,
            'duration_label' => $duration,
            'durationLabel' => $duration,

            'video_provider' => $videoProvider,
            'videoProvider' => $videoProvider,
            'has_video' => ! empty($videoPlaybackUrl),
            'hasVideo' => ! empty($videoPlaybackUrl),

            /**
             * Untuk self-hosted video, URL ini adalah temporary signed stream URL.
             * Untuk Bunny Stream, jangan expose URL database/raw lewat video_url.
             * Frontend LearningPage versi rewind membaca urutan:
             * protected_video_url -> bunny_embed_url -> video_embed_url.
             */
            'video_url' => $publicVideoUrl,
            'videoUrl' => $publicVideoUrl,
            'video_embed_url' => $videoEmbedUrl,
            'videoEmbedUrl' => $videoEmbedUrl,
            'bunny_embed_url' => $videoProvider === 'bunny' ? $videoEmbedUrl : null,
            'bunnyEmbedUrl' => $videoProvider === 'bunny' ? $videoEmbedUrl : null,
            'protected_video_url' => $videoProvider === 'bunny' ? $videoEmbedUrl : $videoPlaybackUrl,
            'protectedVideoUrl' => $videoProvider === 'bunny' ? $videoEmbedUrl : $videoPlaybackUrl,

            'thumbnail_url' => $thumbnailUrl,
            'thumbnailUrl' => $thumbnailUrl,
            'image_url' => $thumbnailUrl,
            'imageUrl' => $thumbnailUrl,

            'last_position_seconds' => (int) ($progress?->last_position_seconds ?? 0),
            'lastPositionSeconds' => (int) ($progress?->last_position_seconds ?? 0),
            'duration_seconds' => $durationSeconds,
            'durationSeconds' => $durationSeconds,
            'progress_percentage' => (float) ($progress?->progress_percentage ?? 0),
            'progressPercentage' => (float) ($progress?->progress_percentage ?? 0),

            'status' => $status,
            'status_label' => match ($status) {
                'completed' => 'Completed',
                'active' => 'In Progress',
                default => 'Available',
            },
            'statusLabel' => match ($status) {
                'completed' => 'Completed',
                'active' => 'In Progress',
                default => 'Available',
            },

            'is_completed' => $isCompleted,
            'isCompleted' => $isCompleted,
            'is_current' => $isActive,
            'isCurrent' => $isActive,
            'is_locked' => false,
            'isLocked' => false,

            'url' => '/learn/' . $courseSlug . '/' . $slug,
            'learn_url' => '/learn/' . $courseSlug . '/' . $slug,
            'learnUrl' => '/learn/' . $courseSlug . '/' . $slug,
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
        ?object $progress = null
    ): array {
        $title = $subTopic->name
            ?? $subTopic->title
            ?? 'Untitled Sub Topic';

        $slug = $this->getSubTopicSlug($subTopic);

        $isCompleted = (bool) ($progress?->is_completed ?? false);

        $hasContent = (bool) ($subTopic->has_content ?? false);

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

            'lesson_type' => $subTopic->lesson_type ?? 'video',
            'lessonType' => $subTopic->lesson_type ?? 'video',
            'video_provider' => $this->resolveSubTopicVideoProvider($subTopic),
            'videoProvider' => $this->resolveSubTopicVideoProvider($subTopic),
            'has_video' => $this->hasAnyVideo($subTopic),
            'hasVideo' => $this->hasAnyVideo($subTopic),
            'has_content' => $hasContent,
            'hasContent' => $hasContent,

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
        if (
            ! Schema::hasTable('sub_topics')
            || ! Schema::hasTable('topics')
            || ! Schema::hasTable('modules')
            || ! Schema::hasTable('program_stages')
        ) {
            return collect();
        }

        $selects = [
            'sub_topics.id',
            'sub_topics.topic_id',
            'sub_topics.name',
            'sub_topics.description',
            'sub_topics.sort_order',
            'sub_topics.lesson_type',
            'sub_topics.video_url',
            'sub_topics.video_provider',
            'sub_topics.video_disk',
            'sub_topics.video_path',
            'sub_topics.video_mime',
            'sub_topics.video_duration_minutes',
            'sub_topics.video_duration_seconds',
            'sub_topics.thumbnail_url',
            'topics.id as topic_id',
            'topics.name as topic_name',
            'topics.description as topic_description',
            'topics.sort_order as topic_sort_order',
            'topics.slide_url as topic_slide_url',
            'topics.starter_code_url as topic_starter_code_url',
            'topics.supporting_file_url as topic_supporting_file_url',
            'topics.external_reference_url as topic_external_reference_url',
            'topics.practice_brief as topic_practice_brief',
            'modules.id as module_id',
            'modules.name as module_name',
            'modules.description as module_description',
            'modules.sort_order as module_sort_order',
            'program_stages.id as stage_id',
            'program_stages.name as stage_name',
            'program_stages.sort_order as stage_sort_order',
        ];

        if (Schema::hasColumn('sub_topics', 'slug')) {
            $selects[] = 'sub_topics.slug';
        }

        if (Schema::hasColumn('sub_topics', 'content')) {
            $selects[] = DB::raw("CASE WHEN sub_topics.content IS NULL OR sub_topics.content = '' THEN 0 ELSE 1 END as has_content");
        } else {
            $selects[] = DB::raw('0 as has_content');
        }

        $query = DB::table('sub_topics')
            ->join('topics', 'topics.id', '=', 'sub_topics.topic_id')
            ->join('modules', 'modules.id', '=', 'topics.module_id')
            ->join('program_stages', 'program_stages.id', '=', 'modules.program_stage_id')
            ->where('program_stages.program_id', $programId)
            ->select($selects);

        if (Schema::hasColumn('program_stages', 'is_active')) {
            $query->where('program_stages.is_active', true);
        }

        if (Schema::hasColumn('modules', 'is_active')) {
            $query->where('modules.is_active', true);
        }

        if (Schema::hasColumn('topics', 'is_active')) {
            $query->where('topics.is_active', true);
        }

        if (Schema::hasColumn('sub_topics', 'is_active')) {
            $query->where('sub_topics.is_active', true);
        }

        return $query
            ->orderBy('program_stages.sort_order')
            ->orderBy('modules.sort_order')
            ->orderBy('topics.sort_order')
            ->orderBy('sub_topics.sort_order')
            ->orderBy('sub_topics.id')
            ->get()
            ->map(fn ($row) => $this->hydrateFlatSubTopicRelations($row))
            ->values();
    }

    private function hydrateFlatSubTopicRelations(object $row): object
    {
        $program = (object) [
            'id' => null,
            'name' => null,
        ];

        $stage = (object) [
            'id' => $row->stage_id ?? null,
            'name' => $row->stage_name ?? null,
            'title' => $row->stage_name ?? null,
            'sort_order' => $row->stage_sort_order ?? null,
            'program' => $program,
        ];

        $module = (object) [
            'id' => $row->module_id ?? null,
            'name' => $row->module_name ?? null,
            'title' => $row->module_name ?? null,
            'description' => $row->module_description ?? null,
            'sort_order' => $row->module_sort_order ?? null,
            'stage' => $stage,
        ];

        $topic = (object) [
            'id' => $row->topic_id ?? null,
            'name' => $row->topic_name ?? null,
            'title' => $row->topic_name ?? null,
            'description' => $row->topic_description ?? null,
            'sort_order' => $row->topic_sort_order ?? null,
            'slide_url' => $row->topic_slide_url ?? null,
            'starter_code_url' => $row->topic_starter_code_url ?? null,
            'supporting_file_url' => $row->topic_supporting_file_url ?? null,
            'external_reference_url' => $row->topic_external_reference_url ?? null,
            'practice_brief' => $row->topic_practice_brief ?? null,
            'module' => $module,
        ];

        $row->title = $row->name ?? null;
        $row->topic = $topic;

        return $row;
    }

    private function hydrateCurrentLessonDetail(object $subTopic): object
    {
        $selects = [
            'id',
        ];

        if (Schema::hasColumn('sub_topics', 'content_format')) {
            $selects[] = 'content_format';
        }

        if (Schema::hasColumn('sub_topics', 'content')) {
            $selects[] = 'content';
        }

        if (Schema::hasColumn('sub_topics', 'slug')) {
            $selects[] = 'slug';
        }

        $detail = DB::table('sub_topics')
            ->where('id', $subTopic->id)
            ->select($selects)
            ->first();

        if ($detail) {
            foreach ((array) $detail as $key => $value) {
                $subTopic->{$key} = $value;
            }

            $subTopic->has_content = ! empty($subTopic->content);
        }

        return $subTopic;
    }

    private function getProgressRowsForSubTopics(Student $student, Collection $subTopicIds): Collection
    {
        $subTopicIds = $subTopicIds
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($subTopicIds->isEmpty() || ! Schema::hasTable('student_lesson_progresses')) {
            return collect();
        }

        return DB::table('student_lesson_progresses')
            ->where('student_id', $student->id)
            ->whereIn('sub_topic_id', $subTopicIds->all())
            ->select([
                'id',
                'student_id',
                'sub_topic_id',
                'last_position_seconds',
                'duration_seconds',
                'progress_percentage',
                'is_completed',
                'completed_at',
                'last_watched_at',
                'updated_at',
            ])
            ->get()
            ->keyBy(fn ($progress) => (int) $progress->sub_topic_id);
    }

    

    private function countPendingTasks(Student $student, Collection $batchIds): int
    {
        $batchIds = $batchIds
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($batchIds->isEmpty()) {
            return 0;
        }

        $assignmentCount = 0;

        if (Schema::hasTable('batch_assignments')) {
            $assignmentQuery = BatchAssignment::query()
                ->whereIn('batch_id', $batchIds->all());

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

            if (Schema::hasTable('assignment_submissions')) {
                $assignmentQuery->whereNotExists(function ($submissionQuery) use ($student) {
                    $submissionQuery
                        ->selectRaw('1')
                        ->from('assignment_submissions')
                        ->whereColumn('assignment_submissions.batch_assignment_id', 'batch_assignments.id')
                        ->where('assignment_submissions.student_id', $student->id)
                        ->whereIn('assignment_submissions.status', [
                            'submitted',
                            'late',
                            'reviewed',
                            'returned',
                            'graded',
                            'completed',
                        ]);
                });
            }

            $assignmentCount = $assignmentQuery->count();
        }

        $quizCount = 0;

        if (Schema::hasTable('batch_learning_quizzes')) {
            $quizQuery = BatchLearningQuiz::query()
                ->whereIn('batch_id', $batchIds->all());

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

            if (Schema::hasTable('learning_quiz_attempts')) {
                $quizQuery->whereNotExists(function ($attemptQuery) use ($student) {
                    $attemptQuery
                        ->selectRaw('1')
                        ->from('learning_quiz_attempts')
                        ->whereColumn('learning_quiz_attempts.batch_learning_quiz_id', 'batch_learning_quizzes.id')
                        ->where('learning_quiz_attempts.student_id', $student->id)
                        ->whereIn('learning_quiz_attempts.status', [
                            'submitted',
                            'completed',
                            'finished',
                            'passed',
                            'done',
                            'reviewed',
                            'graded',
                        ]);
                });
            }

            $quizCount = $quizQuery->count();
        }

        return $assignmentCount + $quizCount;
    }

    

    private function resolveSubTopicVideoPlaybackUrl($subTopic, string $courseSlug, string $lessonSlug): ?string
    {
        if ($this->hasSelfHostedVideo($subTopic)) {
            return $this->makeSelfHostedVideoSignedUrl($courseSlug, $lessonSlug);
        }

        $rawVideoUrl = $this->getColumnValue($subTopic, [
            'video_url',
            'video_embed_url',
            'youtube_url',
            'content_url',
        ]);

        if (! $rawVideoUrl) {
            return null;
        }

        $rawVideoUrl = trim((string) $rawVideoUrl);

        if ($this->isBunnyStreamUrl($rawVideoUrl)) {
            return $this->makeProtectedBunnyEmbedUrl($rawVideoUrl);
        }

        return $rawVideoUrl;
    }

    private function makeSelfHostedVideoSignedUrl(string $courseSlug, string $lessonSlug): ?string
    {
        $routeName = $this->resolveVideoStreamRouteName();

        if (! $routeName) {
            return null;
        }

        return URL::temporarySignedRoute(
            $routeName,
            now()->addHours(2),
            [
                'courseSlug' => $courseSlug,
                'lessonSlug' => $lessonSlug,
            ]
        );
    }

    private function resolveVideoStreamRouteName(): ?string
    {
        foreach ([
            'lms.student.learn.video',
            'api.lms.student.learn.video',
            'lms.student.learning.video',
            'api.lms.student.learning.video',
        ] as $routeName) {
            if (Route::has($routeName)) {
                return $routeName;
            }
        }

        return null;
    }

    private function resolveSubTopicForVideoStream(string $courseSlug, string $lessonSlug)
    {
        $normalizedCourseSlug = $this->slugify($courseSlug);
        $normalizedLessonSlug = $this->slugify($lessonSlug);

        $program = DB::table('programs')
            ->where(function ($query) use ($normalizedCourseSlug, $courseSlug) {
                $query->where('slug', $normalizedCourseSlug)
                    ->orWhere('id', $courseSlug);
            })
            ->select(['id', 'name', 'slug'])
            ->first();

        if (! $program) {
            return null;
        }

        return $this->getSubTopicsForProgram((int) $program->id)
            ->first(function ($subTopic) use ($normalizedLessonSlug, $lessonSlug) {
                $subTopicSlug = $this->getSubTopicSlug($subTopic);

                return $subTopicSlug === $normalizedLessonSlug
                    || (string) $subTopic->id === (string) $lessonSlug;
            });
    }

    

    private function resolveSubTopicVideoProvider($subTopic): ?string
    {
        if ($this->hasSelfHostedVideo($subTopic)) {
            return 'self_hosted';
        }

        $videoUrl = $this->getColumnValue($subTopic, [
            'video_url',
            'video_embed_url',
            'youtube_url',
            'content_url',
        ]);

        if ($videoUrl && $this->isBunnyStreamUrl((string) $videoUrl)) {
            return 'bunny';
        }

        $provider = strtolower(trim((string) $this->getColumnValue($subTopic, [
            'video_provider',
        ])));

        if ($provider) {
            return match ($provider) {
                'bunny', 'bunny_stream', 'bunnystream' => 'bunny',
                'youtube', 'yt' => 'youtube',
                'self_hosted', 'self-hosted', 'local' => 'self_hosted',
                default => $provider,
            };
        }

        if ($videoUrl && $this->isYouTubeUrl((string) $videoUrl)) {
            return 'youtube';
        }

        if ($videoUrl) {
            return 'external';
        }

        return null;
    }

    private function hasAnyVideo($subTopic): bool
    {
        if ($this->hasSelfHostedVideo($subTopic)) {
            return true;
        }

        return (bool) $this->getColumnValue($subTopic, [
            'video_url',
            'video_embed_url',
            'youtube_url',
            'content_url',
        ]);
    }

    private function hasSelfHostedVideo($subTopic): bool
    {
        return (bool) $this->getColumnValue($subTopic, [
            'video_path',
        ]);
    }

    private function resolveSubTopicDurationSeconds($subTopic, ?object $progress = null): ?int
    {
        if ($progress?->duration_seconds) {
            return (int) $progress->duration_seconds;
        }

        $durationSeconds = $this->getColumnValue($subTopic, [
            'video_duration_seconds',
            'duration_seconds',
        ]);

        if ($durationSeconds) {
            return (int) $durationSeconds;
        }

        $durationMinutes = $this->getColumnValue($subTopic, [
            'video_duration_minutes',
            'duration_minutes',
        ]);

        if ($durationMinutes) {
            return (int) $durationMinutes * 60;
        }

        return null;
    }

    private function resolveSelfHostedVideoAbsolutePath($subTopic): ?string
    {
        $videoPath = $this->sanitizeVideoPath(
            $this->getColumnValue($subTopic, [
                'video_path',
            ])
        );

        if (! $videoPath) {
            return null;
        }

        $diskName = $this->getColumnValue($subTopic, [
            'video_disk',
        ]) ?: 'private';

        $diskPath = $this->resolveLocalDiskVideoPath((string) $diskName, $videoPath);

        if ($diskPath) {
            return $diskPath;
        }

        foreach ([
            storage_path('app/private/' . $videoPath),
            storage_path('app/' . $videoPath),
            storage_path('app/public/' . $videoPath),
            public_path($videoPath),
        ] as $candidatePath) {
            if (is_file($candidatePath) && is_readable($candidatePath)) {
                return $candidatePath;
            }
        }

        return null;
    }

    private function resolveLocalDiskVideoPath(string $diskName, string $videoPath): ?string
    {
        $diskConfig = config('filesystems.disks.' . $diskName);

        if (! $diskConfig || ($diskConfig['driver'] ?? null) !== 'local') {
            return null;
        }

        try {
            if (! Storage::disk($diskName)->exists($videoPath)) {
                return null;
            }

            $absolutePath = Storage::disk($diskName)->path($videoPath);

            return is_file($absolutePath) && is_readable($absolutePath)
                ? $absolutePath
                : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function sanitizeVideoPath($path): ?string
    {
        $path = trim(str_replace('\\', '/', (string) $path));
        $path = ltrim($path, '/');

        if ($path === '' || str_contains($path, '../') || str_contains($path, '..\\') || str_contains($path, '..')) {
            return null;
        }

        return $path;
    }

    private function resolveVideoMimeType($subTopic, string $absolutePath): string
    {
        $storedMime = $this->getColumnValue($subTopic, [
            'video_mime',
            'mime_type',
        ]);

        if ($storedMime) {
            return (string) $storedMime;
        }

        $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

        return match ($extension) {
            'webm' => 'video/webm',
            'mov' => 'video/quicktime',
            'm4v' => 'video/x-m4v',
            'ogg', 'ogv' => 'video/ogg',
            default => 'video/mp4',
        };
    }

    private function safeDownloadFileName(string $fileName): string
    {
        $fileName = str_replace(["\r", "\n", '"'], '', $fileName);

        return $fileName !== '' ? $fileName : 'lesson-video.mp4';
    }

    private function isBunnyStreamUrl(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return $host !== '' && str_contains($host, 'mediadelivery.net');
    }

    private function makeProtectedBunnyEmbedUrl(string $rawUrl): string
    {
        $bunnyVideo = $this->parseBunnyStreamUrl($rawUrl);

        if (! $bunnyVideo) {
            return $rawUrl;
        }

        $baseUrl = sprintf(
            'https://iframe.mediadelivery.net/embed/%s/%s',
            rawurlencode($bunnyVideo['library_id']),
            rawurlencode($bunnyVideo['video_id'])
        );

        $query = $bunnyVideo['query'];

        /**
         * Selalu buang token/expires lama dari database.
         * Kalau BUNNY_STREAM_SIGN_EMBED_URL=true, token baru digenerate.
         * Kalau false, URL embed keluar bersih tanpa token/expires.
         * Native Bunny controls dibiarkan aktif supaya playback stabil.
         */
        unset(
            $query['token'],
            $query['expires'],
            $query['controls'],
            $query['showControls'],
            $query['show_controls'],
            $query['preload'],
            $query['show_heatmap']
        );

        $query['autoplay'] = 'false';
        $query['muted'] = 'false';

        $tokenSecurityKey = $this->shouldSignBunnyEmbedUrl()
            ? $this->resolveBunnyStreamTokenSecurityKey()
            : null;

        if ($tokenSecurityKey) {
            $expires = now()
                ->addMinutes($this->resolveBunnyStreamTokenExpiresMinutes())
                ->timestamp;

            /**
             * Bunny Stream Embed View Token Authentication formula:
             * SHA256(token_authentication_key + video_id + expires)
             *
             * Key yang dipakai adalah:
             * Stream > Video Library > Security > Token authentication key
             */
            $query['token'] = hash(
                'sha256',
                $tokenSecurityKey . $bunnyVideo['video_id'] . $expires
            );

            $query['expires'] = $expires;
        }

        return $baseUrl . '?' . http_build_query($query);
    }

    private function shouldSignBunnyEmbedUrl(): bool
    {
        /**
         * Default false supaya saat kondisi darurat/student sedang menonton,
         * Bunny Embed View Token Authentication bisa dimatikan hanya dari .env.
         * Jika mau mode secure lagi, set BUNNY_STREAM_SIGN_EMBED_URL=true
         * dan enable Embed view token authentication di Bunny Dashboard.
         */
        return filter_var(
            config('services.bunny_stream.sign_embed_url')
                ?? config('services.bunny.stream_sign_embed_url')
                ?? config('services.bunny.stream.sign_embed_url')
                ?? env('BUNNY_STREAM_SIGN_EMBED_URL', false),
            FILTER_VALIDATE_BOOL
        );
    }

    private function parseBunnyStreamUrl(string $url): ?array
    {
        $parsedUrl = parse_url($url);

        if (! is_array($parsedUrl)) {
            return null;
        }

        $host = strtolower((string) ($parsedUrl['host'] ?? ''));

        if (! str_contains($host, 'mediadelivery.net')) {
            return null;
        }

        $path = trim((string) ($parsedUrl['path'] ?? ''), '/');
        $segments = array_values(array_filter(explode('/', $path)));

        if (count($segments) < 3) {
            return null;
        }

        $mode = strtolower((string) ($segments[0] ?? ''));

        if (! in_array($mode, ['embed', 'play'], true)) {
            return null;
        }

        $libraryId = (string) ($segments[1] ?? '');
        $videoId = (string) ($segments[2] ?? '');

        if ($libraryId === '' || $videoId === '') {
            return null;
        }

        $query = [];

        if (! empty($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $query);
        }

        unset($query['token'], $query['expires']);

        return [
            'library_id' => $libraryId,
            'video_id' => $videoId,
            'query' => $query,
        ];
    }

    private function resolveBunnyStreamTokenSecurityKey(): ?string
    {
        /**
         * Ini harus diisi dengan key dari Bunny Dashboard:
         * Stream > Video Library > Security > Token authentication key
         *
         * Jangan pakai CDN token key, pull zone key, atau key library yang lama.
         */
        $key = config('services.bunny_stream.token_authentication_key')
            ?: config('services.bunny_stream.token_security_key')
            ?: config('services.bunny.stream_token_authentication_key')
            ?: config('services.bunny.stream_token_security_key')
            ?: config('services.bunny.stream.token_authentication_key')
            ?: config('services.bunny.stream.token_security_key')
            ?: config('services.bunny.token_authentication_key')
            ?: config('services.bunny.token_security_key')
            ?: env('BUNNY_STREAM_TOKEN_AUTHENTICATION_KEY')
            ?: env('BUNNY_STREAM_TOKEN_AUTH_KEY')
            ?: env('BUNNY_STREAM_TOKEN_SECURITY_KEY')
            ?: env('BUNNY_STREAM_SECURITY_KEY')
            ?: env('BUNNY_TOKEN_AUTHENTICATION_KEY')
            ?: env('BUNNY_TOKEN_SECURITY_KEY');

        $key = trim((string) $key);

        return $key !== '' ? $key : null;
    }

    private function resolveBunnyStreamTokenExpiresMinutes(): int
    {
        $minutes = config('services.bunny_stream.token_expires_minutes')
            ?: config('services.bunny.stream_token_expires_minutes')
            ?: config('services.bunny.stream.token_expires_minutes')
            ?: config('services.bunny.token_expires_minutes')
            ?: env('BUNNY_STREAM_TOKEN_EXPIRES_MINUTES', 15);

        $minutes = (int) $minutes;

        /**
         * Guardrail:
         * - Minimum 1 menit supaya token tidak langsung expired.
         * - Maksimum 120 menit supaya link tidak terlalu lama reusable kalau dicopy.
         */
        return max(1, min($minutes, 120));
    }

    private function isYouTubeUrl(string $url): bool
    {
        return str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be');
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
            $value = null;
            $exists = false;

            if (is_array($model)) {
                if (array_key_exists($column, $model)) {
                    $exists = true;
                    $value = $model[$column];
                }
            } elseif ($model instanceof \Illuminate\Database\Eloquent\Model) {
                if (array_key_exists($column, $model->getAttributes())) {
                    $exists = true;
                    $value = $model->getAttribute($column);
                }
            } elseif (is_object($model)) {
                if (property_exists($model, $column) || isset($model->{$column})) {
                    $exists = true;
                    $value = $model->{$column} ?? null;
                }
            }

            if ($exists && $value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    

    private function slugify(?string $value): string
    {
        return str($value ?: 'item')->slug()->toString();
    }
}