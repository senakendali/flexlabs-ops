<?php

namespace App\Http\Controllers\Api\Lms;

use App\Http\Controllers\Controller;
use App\Models\Instructor;
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
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StudentCourseController extends Controller
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
            ->filter(fn ($enrollment) => $enrollment->is_accessible)
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

        $courses = $activeEnrollments
            ->map(fn ($enrollment) => $this->formatCourse($enrollment, $student))
            ->unique('id')
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'student' => $this->formatStudent($student),
                'notification_count' => $this->countPendingTasks($student, $batchIds),
                'summaries' => $this->formatSummaries($courses),
                'courses' => $courses->toArray(),
            ],
        ]);
    }

    public function instructor(Request $request, string $slug): JsonResponse
    {
        $student = $this->resolveStudentFromRequest($request);

        if (! $student) {
            return response()->json([
                'message' => 'Student profile tidak ditemukan.',
            ], 422);
        }

        $course = $this->resolveCourseRecord($slug);

        if (! $course) {
            return response()->json([
                'message' => 'Course tidak ditemukan.',
            ], 404);
        }

        $enrollment = $this->resolveStudentEnrollment($student, $course);
        $instructor = $this->resolveCourseInstructor($course, $enrollment);

        if (! $instructor) {
            return response()->json([
                'message' => 'Instructor belum terhubung ke course ini.',
            ], 404);
        }

        return response()->json([
            'message' => 'Instructor detail berhasil dimuat.',
            'data' => [
                'instructor' => $this->formatInstructorForLms($instructor),
            ],
        ]);
    }

    private function resolveStudentFromRequest(Request $request): ?Student
    {
        $user = $request->user();

        if (! $user) {
            return null;
        }

        if (isset($user->student_id) && $user->student_id) {
            return Student::query()->find($user->student_id);
        }

        if (method_exists($user, 'student')) {
            $student = $user->student;

            if ($student?->id) {
                return $student;
            }
        }

        $studentTable = (new Student())->getTable();

        $query = Student::query();

        $query->where(function ($q) use ($user, $studentTable) {
            if (Schema::hasColumn($studentTable, 'user_id')) {
                $q->orWhere('user_id', $user->id);
            }

            if (($user->email ?? null) && Schema::hasColumn($studentTable, 'email')) {
                $q->orWhere('email', $user->email);
            }
        });

        return $query->first();
    }

    private function resolveCourseRecord(string $slug): ?object
    {
        foreach (['programs', 'courses'] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'slug')) {
                continue;
            }

            $record = DB::table($table)
                ->where('slug', $slug)
                ->first();

            if ($record) {
                $record->_source_table = $table;

                return $record;
            }
        }

        return null;
    }

    private function resolveStudentEnrollment(Student $student, object $course): ?object
    {
        if (! Schema::hasTable('student_enrollments')) {
            return null;
        }

        $query = DB::table('student_enrollments')
            ->where('student_enrollments.student_id', $student->id);

        $courseId = $course->id ?? null;
        $courseTable = $course->_source_table ?? null;

        if ($courseId && Schema::hasColumn('student_enrollments', 'program_id')) {
            $query->where('student_enrollments.program_id', $courseId);
        } elseif (
            $courseId
            && $courseTable === 'programs'
            && Schema::hasColumn('student_enrollments', 'batch_id')
            && Schema::hasTable('batches')
            && Schema::hasColumn('batches', 'program_id')
        ) {
            $query
                ->join('batches', 'student_enrollments.batch_id', '=', 'batches.id')
                ->where('batches.program_id', $courseId)
                ->select('student_enrollments.*');
        }

        if (Schema::hasColumn('student_enrollments', 'status')) {
            $query->whereIn('student_enrollments.status', [
                'active',
                'ongoing',
                'enrolled',
                'approved',
                'paid',
                'completed',
            ]);
        }

        return $query
            ->orderByDesc('student_enrollments.id')
            ->first();
    }

    private function resolveCourseInstructor(object $course, ?object $enrollment = null): ?Instructor
    {
        $instructorTable = (new Instructor())->getTable();

        if (! Schema::hasTable($instructorTable)) {
            return null;
        }

        $instructorId = null;
        $courseTable = $course->_source_table ?? null;

        /*
        |--------------------------------------------------------------------------
        | 1. Direct instructor_id dari programs/courses
        |--------------------------------------------------------------------------
        */
        if (
            $courseTable
            && Schema::hasTable($courseTable)
            && Schema::hasColumn($courseTable, 'instructor_id')
            && ! empty($course->instructor_id)
        ) {
            $instructorId = $course->instructor_id;
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Instructor dari batch student
        |--------------------------------------------------------------------------
        */
        if (
            ! $instructorId
            && $enrollment
            && ! empty($enrollment->batch_id)
            && Schema::hasTable('batches')
            && Schema::hasColumn('batches', 'instructor_id')
        ) {
            $batch = DB::table('batches')
                ->where('id', $enrollment->batch_id)
                ->first();

            if ($batch?->instructor_id) {
                $instructorId = $batch->instructor_id;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Instructor dari instructor_schedules
        |--------------------------------------------------------------------------
        */
        if (! $instructorId && Schema::hasTable('instructor_schedules')) {
            $scheduleQuery = DB::table('instructor_schedules')
                ->whereNotNull('instructor_id');

            $hasScheduleFilter = false;

            if (
                $enrollment
                && ! empty($enrollment->batch_id)
                && Schema::hasColumn('instructor_schedules', 'batch_id')
            ) {
                $scheduleQuery->where('batch_id', $enrollment->batch_id);
                $hasScheduleFilter = true;
            }

            if (
                ! $hasScheduleFilter
                && ! empty($course->id)
                && Schema::hasColumn('instructor_schedules', 'program_id')
            ) {
                $scheduleQuery->where('program_id', $course->id);
                $hasScheduleFilter = true;
            }

            if (
                ! $hasScheduleFilter
                && ! empty($course->id)
                && Schema::hasColumn('instructor_schedules', 'course_id')
            ) {
                $scheduleQuery->where('course_id', $course->id);
                $hasScheduleFilter = true;
            }

            if ($hasScheduleFilter) {
                foreach (['schedule_date', 'session_date', 'date', 'start_at', 'created_at', 'id'] as $column) {
                    if (Schema::hasColumn('instructor_schedules', $column)) {
                        $scheduleQuery->orderByDesc($column);
                        break;
                    }
                }

                $schedule = $scheduleQuery->first();

                if ($schedule?->instructor_id) {
                    $instructorId = $schedule->instructor_id;
                }
            }
        }

        if (! $instructorId) {
            return null;
        }

        $query = Instructor::query()
            ->whereKey($instructorId);

        if (Schema::hasColumn($instructorTable, 'is_active')) {
            $query->where('is_active', true);
        }

        return $query->first();
    }

    private function formatInstructorForLms(Instructor $instructor): array
    {
        $photoUrl = $this->resolveInstructorPhotoUrl($instructor);

        return [
            'id' => $instructor->id,

            'name' => $instructor->name,
            'full_name' => $instructor->name,
            'fullName' => $instructor->name,

            'slug' => $instructor->slug,

            'role' => $this->formatEmploymentTypeLabel($instructor->employment_type),
            'position' => $this->formatEmploymentTypeLabel($instructor->employment_type),

            'email' => $instructor->email,
            'phone' => $instructor->phone,

            'specialization' => $instructor->specialization,
            'bio' => $instructor->bio,

            'employment_type' => $instructor->employment_type,
            'employmentType' => $instructor->employment_type,
            'employment_type_label' => $this->formatEmploymentTypeLabel($instructor->employment_type),
            'employmentTypeLabel' => $this->formatEmploymentTypeLabel($instructor->employment_type),

            'photo' => $photoUrl,
            'photo_url' => $photoUrl,
            'photoUrl' => $photoUrl,
            'avatar_url' => $photoUrl,
            'avatarUrl' => $photoUrl,

            'is_active' => (bool) $instructor->is_active,
            'isActive' => (bool) $instructor->is_active,
        ];
    }

    private function resolveInstructorPhotoUrl(Instructor $instructor): string
    {
        $photo = $instructor->photo;

        if (! $photo) {
            return 'https://ui-avatars.com/api/?name='.urlencode($instructor->name ?: 'Instructor').'&background=5B3E8E&color=ffffff';
        }

        if (Str::startsWith($photo, ['http://', 'https://'])) {
            return $photo;
        }

        $path = Str::of($photo)
            ->replace('\\', '/')
            ->replaceStart('public/', '')
            ->replaceStart('/storage/', '')
            ->replaceStart('storage/', '')
            ->ltrim('/')
            ->toString();

        return Storage::disk('public')->url($path);
    }

    private function formatEmploymentTypeLabel(?string $employmentType): string
    {
        return match ($employmentType) {
            'full_time' => 'Full-time Instructor',
            'part_time' => 'Part-time Instructor',
            default => 'Instructor',
        };
    }

    public function show(Request $request, string $slug): JsonResponse
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

        $enrollment = $this->findEnrollmentByCourseSlug($activeEnrollments, $slug);

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

        $batchIds = $activeEnrollments
            ->pluck('batch_id')
            ->filter()
            ->unique()
            ->values();

        $courseSlug = $this->getProgramSlug($program);
        $subTopics = $this->getSubTopicsForProgram((int) $program->id);

        $progressRows = $this->getProgressRows($student, $subTopics);

        $modules = $this->formatModules(
            subTopics: $subTopics,
            courseSlug: $courseSlug,
            progressRows: $progressRows
        );

        $totalSubTopics = (int) $modules->sum('total_lessons');
        $completedSubTopics = (int) $modules->sum('completed_lessons');

        $progress = $this->calculateLessonProgress(
            enrollment: $enrollment,
            completedLessons: $completedSubTopics,
            totalLessons: $totalSubTopics
        );

        $nextSubTopic = $this->getNextSubTopicFromModules($modules);

        return response()->json([
            'success' => true,
            'data' => [
                'student' => $this->formatStudent($student),
                'notification_count' => $this->countPendingTasks($student, $batchIds),

                'course' => $this->formatCourseDetail(
                    enrollment: $enrollment,
                    totalSubTopics: $totalSubTopics,
                    completedSubTopics: $completedSubTopics,
                    progress: $progress,
                    nextSubTopic: $nextSubTopic
                ),

                'modules' => $modules->values()->toArray(),

                'instructor' => $this->formatInstructor($enrollment),
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

    private function findEnrollmentByCourseSlug(Collection $activeEnrollments, string $slug)
    {
        $normalizedSlug = $this->slugify($slug);

        return $activeEnrollments->first(function ($enrollment) use ($normalizedSlug, $slug) {
            $program = $enrollment->program ?? $enrollment->batch?->program;

            if (!$program) {
                return false;
            }

            $programSlug = $this->getProgramSlug($program);

            return $programSlug === $normalizedSlug
                || (string) $program->id === (string) $slug
                || (string) $enrollment->id === (string) $slug;
        });
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

        $progress = $this->calculateLessonProgress(
            enrollment: $enrollment,
            completedLessons: $completedSubTopics,
            totalLessons: $totalSubTopics
        );

        $nextSubTopic = $this->getNextSubTopicForProgram(
            subTopics: $subTopics,
            courseSlug: $courseSlug,
            progressRows: $progressRows
        );

        $thumbnailUrl = $this->getColumnValue($program, [
            'thumbnail_url',
            'thumbnail',
            'image_url',
            'image',
        ]);

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

            'instructor' => 'FlexLabs Team',

            'description' => $this->getColumnValue($program, [
                'description',
                'summary',
                'short_description',
            ]) ?: 'Continue your learning progress with FlexLabs.',

            'thumbnail_url' => $thumbnailUrl,

            'status' => $this->resolveCourseStatus($enrollment, $progress),
            'status_label' => $this->resolveCourseStatusLabel($enrollment, $progress),

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

            'duration' => $this->resolveDuration($program, $batch),

            'level' => $this->getColumnValue($program, [
                'level',
                'difficulty',
            ]) ?: '-',

            'course_url' => '/courses/' . $courseSlug,

            'batch_id' => $batch->id ?? null,
            'batch_name' => $batch->name ?? null,

            'enrollment_id' => $enrollment->id,
            'enrollment_status' => $enrollment->status,
            'access_status' => $enrollment->access_status,
        ];
    }

    private function formatCourseDetail(
        $enrollment,
        int $totalSubTopics,
        int $completedSubTopics,
        int $progress,
        ?array $nextSubTopic
    ): array {
        $program = $enrollment->program ?? $enrollment->batch?->program;
        $batch = $enrollment->batch;

        $programName = $program->name ?? 'Untitled Course';
        $courseSlug = $program ? $this->getProgramSlug($program) : $this->slugify($programName);

        $thumbnailUrl = $this->getColumnValue($program, [
            'thumbnail_url',
            'thumbnail',
            'image_url',
            'image',
        ]);

        $videoUrl = $this->getColumnValue($program, [
            'video_url',
            'overview_video_url',
            'video_embed_url',
            'youtube_url',
        ]);

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
            ]) ?: 'Continue your learning progress with FlexLabs.',

            'thumbnail_url' => $thumbnailUrl,

            'video_url' => $videoUrl,
            'video_embed_url' => $this->normalizeYouTubeEmbedUrl($videoUrl),

            'status' => $this->resolveCourseStatus($enrollment, $progress),
            'status_label' => $this->resolveCourseStatusLabel($enrollment, $progress),

            'progress' => $progress,
            'progress_percentage' => $progress,

            'completed_lessons' => $completedSubTopics,
            'total_lessons' => $totalSubTopics,

            'completed_sub_topics' => $completedSubTopics,
            'total_sub_topics' => $totalSubTopics,

            'level' => $this->getColumnValue($program, [
                'level',
                'difficulty',
            ]) ?: '-',

            'duration' => $this->resolveDuration($program, $batch),

            'batch_id' => $batch->id ?? null,
            'batch_name' => $batch->name ?? null,

            'continue_url' => $nextSubTopic['url'] ?? '/my-courses',

            'next_lesson' => $nextSubTopic['title'] ?? 'No next sub topic',
            'next_lesson_url' => $nextSubTopic['url'] ?? null,

            'next_sub_topic' => $nextSubTopic['title'] ?? 'No next sub topic',
            'next_sub_topic_url' => $nextSubTopic['url'] ?? null,

            'course_url' => '/courses/' . $courseSlug,

            'enrollment_id' => $enrollment->id,
            'enrollment_status' => $enrollment->status,
            'access_status' => $enrollment->access_status,
        ];
    }

    private function formatInstructor($enrollment): array
    {
        return [
            'name' => 'FlexLabs Academic Team',
            'role' => 'Software Engineering Instructor',
            'email' => 'academic@flexlabs.co.id',
            'specialization' => 'Laravel, Web Development, Software Engineering',
            'experience' => '5+ years teaching & building software products',
            'bio' => 'Focused on helping students understand software engineering concepts through practical projects, structured thinking, and AI-assisted workflows.',
            'photo' => 'https://i.pravatar.cc/200?img=12',
        ];
    }

    private function formatModules(Collection $subTopics, string $courseSlug, Collection $progressRows): Collection
    {
        return $subTopics
            ->groupBy(function ($subTopic) {
                return $subTopic->topic?->module?->id ?? 'module-unknown';
            })
            ->map(function (Collection $moduleSubTopics, $moduleKey) use ($courseSlug, $progressRows) {
                $firstSubTopic = $moduleSubTopics->first();
                $module = $firstSubTopic?->topic?->module;

                $topics = $this->formatTopics(
                    moduleSubTopics: $moduleSubTopics,
                    courseSlug: $courseSlug,
                    progressRows: $progressRows
                );

                $totalSubTopics = (int) $topics->sum('total_sub_topics');
                $completedSubTopics = (int) $topics->sum('completed_sub_topics');

                return [
                    'id' => $module->id ?? $moduleKey,
                    'title' => $module->name ?? $module->title ?? 'Untitled Module',
                    'name' => $module->name ?? $module->title ?? 'Untitled Module',

                    'description' => $this->getColumnValue($module, [
                        'description',
                        'summary',
                    ]) ?: 'Module learning content',

                    'total_lessons' => $totalSubTopics,
                    'completed_lessons' => $completedSubTopics,

                    'total_sub_topics' => $totalSubTopics,
                    'completed_sub_topics' => $completedSubTopics,

                    'progress' => $this->calculateProgressPercentage($completedSubTopics, $totalSubTopics),

                    'topics' => $topics->values()->toArray(),
                ];
            })
            ->values();
    }

    private function formatTopics(Collection $moduleSubTopics, string $courseSlug, Collection $progressRows): Collection
    {
        return $moduleSubTopics
            ->groupBy(function ($subTopic) {
                return $subTopic->topic?->id ?? 'topic-unknown';
            })
            ->map(function (Collection $topicSubTopics, $topicKey) use ($courseSlug, $progressRows) {
                $firstSubTopic = $topicSubTopics->first();
                $topic = $firstSubTopic?->topic;

                $formattedSubTopics = $topicSubTopics
                    ->map(fn ($subTopic) => $this->formatSubTopic(
                        subTopic: $subTopic,
                        courseSlug: $courseSlug,
                        progress: $progressRows->get($subTopic->id)
                    ))
                    ->values();

                $totalSubTopics = $formattedSubTopics->count();

                $completedSubTopics = $formattedSubTopics
                    ->where('is_completed', true)
                    ->count();

                return [
                    'id' => $topic->id ?? $topicKey,
                    'title' => $topic->name ?? $topic->title ?? 'Untitled Topic',
                    'name' => $topic->name ?? $topic->title ?? 'Untitled Topic',

                    'description' => $this->getColumnValue($topic, [
                        'description',
                        'summary',
                    ]),

                    'total_sub_topics' => $totalSubTopics,
                    'completed_sub_topics' => $completedSubTopics,

                    'total_lessons' => $totalSubTopics,
                    'completed_lessons' => $completedSubTopics,

                    'progress' => $this->calculateProgressPercentage($completedSubTopics, $totalSubTopics),

                    'sub_topics' => $formattedSubTopics->toArray(),
                ];
            })
            ->values();
    }

    private function formatSubTopic($subTopic, string $courseSlug, ?StudentLessonProgress $progress = null): array
    {
        $title = $subTopic->name
            ?? $subTopic->title
            ?? 'Untitled Sub Topic';

        $slug = $this->getSubTopicSlug($subTopic);

        $duration = $this->resolveSubTopicDuration($subTopic);

        $description = $this->getColumnValue($subTopic, [
            'description',
            'summary',
        ]);

        $progressPercentage = (float) ($progress?->progress_percentage ?? 0);
        $isCompleted = (bool) ($progress?->is_completed ?? false);

        if (!$isCompleted && $progressPercentage >= 95) {
            $isCompleted = true;
        }

        $status = $isCompleted ? 'completed' : 'available';

        return [
            'id' => $subTopic->id,

            'title' => $title,
            'name' => $title,
            'slug' => $slug,

            'description' => $description,

            'duration' => $duration,
            'duration_label' => $duration,

            'status' => $status,
            'status_label' => $isCompleted ? 'Completed' : 'Available',

            'progress_percentage' => round($progressPercentage, 2),
            'last_position_seconds' => (int) ($progress?->last_position_seconds ?? 0),
            'duration_seconds' => $progress?->duration_seconds
                ? (int) $progress->duration_seconds
                : null,

            'is_completed' => $isCompleted,
            'is_current' => false,
            'is_locked' => false,

            'completed_at' => $progress?->completed_at,
            'last_watched_at' => $progress?->last_watched_at,

            'url' => '/learn/' . $courseSlug . '/' . $slug,
            'learn_url' => '/learn/' . $courseSlug . '/' . $slug,
        ];
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

    private function getNextSubTopicForProgram(Collection $subTopics, string $courseSlug, Collection $progressRows): ?array
    {
        foreach ($subTopics as $subTopic) {
            $progress = $progressRows->get($subTopic->id);

            if (!($progress?->is_completed ?? false)) {
                return $this->formatNextSubTopic($subTopic, $courseSlug);
            }
        }

        return null;
    }

    private function getNextSubTopicFromModules(Collection $modules): ?array
    {
        foreach ($modules as $module) {
            foreach (($module['topics'] ?? []) as $topic) {
                foreach (($topic['sub_topics'] ?? []) as $subTopic) {
                    if (!($subTopic['is_completed'] ?? false) && !($subTopic['is_locked'] ?? false)) {
                        return [
                            'id' => $subTopic['id'] ?? null,
                            'title' => $subTopic['title'] ?? 'Untitled Sub Topic',
                            'url' => $subTopic['url'] ?? '/my-courses',
                        ];
                    }
                }
            }
        }

        return null;
    }

    private function formatNextSubTopic($subTopic, string $courseSlug): array
    {
        $title = $subTopic->name
            ?? $subTopic->title
            ?? 'Untitled Sub Topic';

        $slug = $this->getSubTopicSlug($subTopic);

        return [
            'id' => $subTopic->id,
            'title' => $title,
            'slug' => $slug,
            'url' => '/learn/' . $courseSlug . '/' . $slug,
        ];
    }

    private function resolveCourseStatus($enrollment, int $progress = 0): string
    {
        if (($enrollment->status ?? null) === 'completed' || $progress >= 100) {
            return 'completed';
        }

        if (($enrollment->access_status ?? null) !== 'active') {
            return 'locked';
        }

        if (!$enrollment->is_accessible) {
            return 'locked';
        }

        return 'in_progress';
    }

    private function resolveCourseStatusLabel($enrollment, int $progress = 0): string
    {
        return match ($this->resolveCourseStatus($enrollment, $progress)) {
            'completed' => 'Completed',
            'locked' => 'Locked',
            default => 'In Progress',
        };
    }

    private function calculateLessonProgress($enrollment, int $completedLessons, int $totalLessons): int
    {
        if (($enrollment->status ?? null) === 'completed') {
            return 100;
        }

        return $this->calculateProgressPercentage($completedLessons, $totalLessons);
    }

    private function calculateProgressPercentage(int $completed, int $total): int
    {
        if ($total <= 0) {
            return 0;
        }

        return (int) round(($completed / $total) * 100);
    }

    private function formatSummaries(Collection $courses): array
    {
        $totalCourses = $courses->count();

        $inProgressCourses = $courses
            ->where('status', 'in_progress')
            ->count();

        $completedCourses = $courses
            ->where('status', 'completed')
            ->count();

        $averageProgress = $totalCourses > 0
            ? (int) round($courses->avg('progress'))
            : 0;

        return [
            [
                'key' => 'total_courses',
                'label' => 'Total Courses',
                'value' => $totalCourses,
                'caption' => 'All learning programs',
                'type' => 'primary',
            ],
            [
                'key' => 'in_progress',
                'label' => 'In Progress',
                'value' => $inProgressCourses,
                'caption' => 'Currently learning',
                'type' => 'warning',
            ],
            [
                'key' => 'completed',
                'label' => 'Completed',
                'value' => $completedCourses,
                'caption' => 'Finished courses',
                'type' => 'success',
            ],
            [
                'key' => 'avg_progress',
                'label' => 'Avg Progress',
                'value' => $averageProgress . '%',
                'caption' => 'Overall completion',
                'type' => 'purple',
            ],
        ];
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

    private function resolveDuration($program, $batch): string
    {
        $duration = $this->getColumnValue($program, [
            'duration',
            'duration_label',
            'total_duration',
        ]);

        if ($duration) {
            return (string) $duration;
        }

        $startDate = $batch->start_date ?? null;
        $endDate = $batch->end_date ?? null;

        if ($startDate && $endDate) {
            return Carbon::parse($startDate)->format('d M Y')
                . ' - '
                . Carbon::parse($endDate)->format('d M Y');
        }

        return '-';
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