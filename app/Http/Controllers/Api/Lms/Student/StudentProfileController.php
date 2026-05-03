<?php

namespace App\Http\Controllers\Api\Lms\Student;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Throwable;

class StudentProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $student = $this->resolveStudent($user);

        $learningProgress = $this->getLearningProgress($student);
        $summary = $this->buildSummary($student, $learningProgress);

        return response()->json([
            'success' => true,
            'message' => 'Profile berhasil dimuat.',
            'data' => [
                'student' => $this->formatStudent($user, $student),
                'profile' => $this->formatProfile($user, $student, $learningProgress),
                'summaries' => $summary,
                'learning_progress' => $learningProgress,
                'account' => $this->formatAccountStatus($user, $student),
                'preferences' => $this->formatPreferences($student),
                'notification_count' => $this->getUnreadNotificationCount($user),
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        $student = $this->resolveStudent($user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'bio' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::beginTransaction();

        try {
            $this->updateUserProfile($user, $validated);

            if ($student) {
                $this->updateStudentProfile($student, $validated);
            }

            DB::commit();

            $freshUser = $user->fresh();
            $freshStudent = $this->resolveStudent($freshUser);
            $learningProgress = $this->getLearningProgress($freshStudent);

            return response()->json([
                'success' => true,
                'message' => 'Profile berhasil diperbarui.',
                'data' => [
                    'student' => $this->formatStudent($freshUser, $freshStudent),
                    'profile' => $this->formatProfile($freshUser, $freshStudent, $learningProgress),
                    'summaries' => $this->buildSummary($freshStudent, $learningProgress),
                    'learning_progress' => $learningProgress,
                    'account' => $this->formatAccountStatus($freshUser, $freshStudent),
                    'preferences' => $this->formatPreferences($freshStudent),
                    'notification_count' => $this->getUnreadNotificationCount($freshUser),
                ],
            ]);
        } catch (Throwable $exception) {
            DB::rollBack();
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Profile gagal diperbarui.',
            ], 500);
        }
    }

    public function updatePhoto(Request $request): JsonResponse
    {
        $user = $request->user();
        $student = $this->resolveStudent($user);

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Data student tidak ditemukan.',
            ], 404);
        }

        $validated = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if (!Schema::hasColumn('students', 'avatar_url')) {
            return response()->json([
                'success' => false,
                'message' => 'Kolom avatar_url belum tersedia di tabel students.',
            ], 422);
        }

        try {
            $path = $validated['photo']->store('students/profile-photos', 'public');
            $url = Storage::url($path);

            DB::table('students')
                ->where('id', $student->id)
                ->update([
                    'avatar_url' => $url,
                    'updated_at' => now(),
                ]);

            $freshUser = $user->fresh();
            $freshStudent = $this->resolveStudent($freshUser);

            return response()->json([
                'success' => true,
                'message' => 'Foto profile berhasil diperbarui.',
                'data' => [
                    'avatar_url' => $url,
                    'avatarUrl' => $url,
                    'student' => $this->formatStudent($freshUser, $freshStudent),
                ],
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Foto profile gagal diperbarui.',
            ], 500);
        }
    }

    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password lama tidak sesuai.',
                'errors' => [
                    'current_password' => ['Password lama tidak sesuai.'],
                ],
            ], 422);
        }

        DB::table('users')
            ->where('id', $user->id)
            ->update([
                'password' => Hash::make($validated['password']),
                'updated_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diperbarui.',
        ]);
    }

    public function updatePreferences(Request $request): JsonResponse
    {
        $user = $request->user();
        $student = $this->resolveStudent($user);

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Data student tidak ditemukan.',
            ], 404);
        }

        if (!Schema::hasTable('student_preferences')) {
            return response()->json([
                'success' => false,
                'message' => 'Tabel student_preferences belum tersedia.',
            ], 422);
        }

        $validated = $request->validate([
            'preferences' => ['required', 'array'],
            'preferences.*.key' => ['required', 'string', 'max:100'],
            'preferences.*.enabled' => ['required', 'boolean'],
        ]);

        $allowedKeys = collect($this->defaultPreferences())->keys();

        DB::beginTransaction();

        try {
            foreach ($validated['preferences'] as $preference) {
                $key = $preference['key'];

                if (!$allowedKeys->contains($key)) {
                    continue;
                }

                DB::table('student_preferences')->updateOrInsert(
                    [
                        'student_id' => $student->id,
                        'preference_key' => $key,
                    ],
                    [
                        'enabled' => (bool) $preference['enabled'],
                        'updated_at' => now(),
                        'created_at' => now(),
                    ],
                );
            }

            DB::commit();

            $freshStudent = $this->resolveStudent($user->fresh());

            return response()->json([
                'success' => true,
                'message' => 'Preferences berhasil diperbarui.',
                'data' => [
                    'preferences' => $this->formatPreferences($freshStudent),
                ],
            ]);
        } catch (Throwable $exception) {
            DB::rollBack();
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Preferences gagal diperbarui.',
            ], 500);
        }
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

        if (Schema::hasColumn('students', 'email') && filled($this->recordValue($user, ['email']))) {
            $student = DB::table('students')
                ->where('email', $this->recordValue($user, ['email']))
                ->first();

            if ($student) {
                return $student;
            }
        }

        $studentId = $this->recordValue($user, ['student_id']);

        if ($studentId && Schema::hasColumn('students', 'id')) {
            return DB::table('students')
                ->where('id', $studentId)
                ->first();
        }

        return null;
    }

    private function updateUserProfile($user, array $validated): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        $payload = [];

        if (Schema::hasColumn('users', 'name')) {
            $payload['name'] = $validated['name'];
        }

        if (Schema::hasColumn('users', 'email')) {
            $payload['email'] = $validated['email'];
        }

        if (!empty($payload)) {
            $payload['updated_at'] = now();

            DB::table('users')
                ->where('id', $user->id)
                ->update($payload);
        }
    }

    private function updateStudentProfile(object $student, array $validated): void
    {
        if (!Schema::hasTable('students')) {
            return;
        }

        $payload = [];

        if (Schema::hasColumn('students', 'full_name')) {
            $payload['full_name'] = $validated['name'];
        }

        if (Schema::hasColumn('students', 'email')) {
            $payload['email'] = $validated['email'];
        }

        if (Schema::hasColumn('students', 'phone')) {
            $payload['phone'] = $validated['phone'];
        }

        if (Schema::hasColumn('students', 'bio')) {
            $payload['bio'] = $validated['bio'];
        }

        if (!empty($payload)) {
            $payload['updated_at'] = now();

            DB::table('students')
                ->where('id', $student->id)
                ->update($payload);
        }
    }

    private function formatStudent($user, ?object $student): array
    {
        $name = $this->firstFilled([
            $this->recordValue($student, ['full_name']),
            $this->recordValue($user, ['name']),
            'Student',
        ]);

        $email = $this->firstFilled([
            $this->recordValue($student, ['email']),
            $this->recordValue($user, ['email']),
        ]);

        $bio = $this->firstFilled([
            $this->recordValue($student, ['bio']),
            'Learning practical tech skills with FlexLabs.',
        ]);

        $avatarUrl = $this->recordValue($student, ['avatar_url']);

        return [
            'id' => $student->id ?? null,
            'user_id' => $user->id ?? null,
            'userId' => $user->id ?? null,

            'name' => $name,
            'full_name' => $name,
            'fullName' => $name,

            'email' => $email,
            'phone' => $this->recordValue($student, ['phone']),

            'role' => 'FlexLabs Student',
            'bio' => $bio,

            'avatar_url' => $avatarUrl,
            'avatarUrl' => $avatarUrl,
        ];
    }

    private function formatProfile($user, ?object $student, array $learningProgress): array
    {
        $studentData = $this->formatStudent($user, $student);
        $currentProgram = $learningProgress[0]['title'] ?? '-';

        return [
            'name' => $studentData['name'],
            'email' => $studentData['email'],
            'phone' => $studentData['phone'],
            'program' => $currentProgram,
            'bio' => $studentData['bio'],
        ];
    }

    private function formatAccountStatus($user, ?object $student): array
    {
        $status = $this->recordValue($student, ['status']) ?: 'active';
        $statusLabel = str($status)->replace('_', ' ')->title()->toString();

        $studentCode = $student?->id
            ? 'FLX-' . now()->format('Y') . '-' . str_pad((string) $student->id, 3, '0', STR_PAD_LEFT)
            : '-';

        return [
            'status' => $status,
            'status_label' => $statusLabel,
            'statusLabel' => $statusLabel,
            'joined_label' => $this->formatMonthYear($this->recordValue($user, ['created_at'])),
            'joinedLabel' => $this->formatMonthYear($this->recordValue($user, ['created_at'])),
            'student_code' => $studentCode,
            'studentCode' => $studentCode,
        ];
    }

    private function defaultPreferences(): array
    {
        return [
            'email_notifications' => [
                'key' => 'email_notifications',
                'label' => 'Email Notifications',
                'caption' => 'Receive schedule and course updates by email.',
                'enabled' => true,
            ],
            'learning_reminders' => [
                'key' => 'learning_reminders',
                'label' => 'Learning Reminders',
                'caption' => 'Remind me when I have upcoming sessions.',
                'enabled' => true,
            ],
            'progress_summary' => [
                'key' => 'progress_summary',
                'label' => 'Progress Summary',
                'caption' => 'Send weekly learning progress summary.',
                'enabled' => false,
            ],
        ];
    }

    private function formatPreferences(?object $student): array
    {
        $defaults = $this->defaultPreferences();

        if (!$student || !Schema::hasTable('student_preferences')) {
            return array_values($defaults);
        }

        $savedPreferences = DB::table('student_preferences')
            ->where('student_id', $student->id)
            ->get()
            ->keyBy('preference_key');

        foreach ($defaults as $key => $preference) {
            if ($savedPreferences->has($key)) {
                $defaults[$key]['enabled'] = (bool) $savedPreferences[$key]->enabled;
            }
        }

        return array_values($defaults);
    }

    private function buildSummary(?object $student, array $learningProgress): array
    {
        $activeCourses = count($learningProgress);

        $overallProgress = $activeCourses > 0
            ? (int) round(collect($learningProgress)->avg('progress'))
            : 0;

        $certificateCount = $this->getCertificateCount($student);
        $achievementCount = collect($learningProgress)->sum('completed_lessons');

        return [
            'active_courses' => $activeCourses,
            'activeCourses' => $activeCourses,
            'overall_progress' => $overallProgress,
            'overallProgress' => $overallProgress,
            'certificates' => $certificateCount,
            'achievements' => $achievementCount,
        ];
    }

    private function getLearningProgress(?object $student): array
    {
        if (!$student || !Schema::hasTable('student_enrollments')) {
            return [];
        }

        $enrollments = $this->getActiveEnrollments($student);

        return $enrollments
            ->map(function ($enrollment) {
                $programId = $enrollment->resolved_program_id ?? null;

                if (!$programId) {
                    return null;
                }

                $program = $this->findProgram((int) $programId);

                if (!$program) {
                    return null;
                }

                $progress = $this->calculateProgramProgress(
                    studentId: (int) $enrollment->student_id,
                    programId: (int) $programId
                );

                return [
                    'id' => $program->id,
                    'slug' => $this->recordValue($program, ['slug']),
                    'title' => $this->recordValue($program, ['name', 'title']) ?: 'Course',
                    'caption' => $progress['current_lesson_title'] ?: ($enrollment->batch_name ?? 'Active learning program'),

                    'batch_id' => $enrollment->batch_id ?? null,
                    'batchId' => $enrollment->batch_id ?? null,
                    'batch_name' => $enrollment->batch_name ?? '-',
                    'batchName' => $enrollment->batch_name ?? '-',

                    'progress' => $progress['progress'],
                    'completed_lessons' => $progress['completed_lessons'],
                    'completedLessons' => $progress['completed_lessons'],
                    'total_lessons' => $progress['total_lessons'],
                    'totalLessons' => $progress['total_lessons'],

                    'continue_url' => $progress['continue_url'] ?: '/my-courses',
                    'continueUrl' => $progress['continue_url'] ?: '/my-courses',

                    'modules' => $progress['modules'],
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function getActiveEnrollments(object $student): Collection
    {
        $query = DB::table('student_enrollments')
            ->leftJoin('batches', 'student_enrollments.batch_id', '=', 'batches.id')
            ->where('student_enrollments.student_id', $student->id);

        if (Schema::hasColumn('student_enrollments', 'status')) {
            $query->whereIn('student_enrollments.status', [
                'active',
                'ongoing',
                'enrolled',
                'approved',
                'paid',
            ]);
        }

        $selects = [
            'student_enrollments.id',
            'student_enrollments.student_id',
            'student_enrollments.batch_id',
        ];

        if (Schema::hasColumn('student_enrollments', 'program_id')) {
            $selects[] = 'student_enrollments.program_id as enrollment_program_id';
        }

        if (Schema::hasColumn('batches', 'program_id')) {
            $selects[] = 'batches.program_id as batch_program_id';
        }

        if (Schema::hasColumn('batches', 'name')) {
            $selects[] = 'batches.name as batch_name';
        }

        $rows = $query
            ->select($selects)
            ->orderByDesc('student_enrollments.id')
            ->get();

        return $rows->map(function ($row) {
            $row->resolved_program_id = $row->enrollment_program_id
                ?? $row->batch_program_id
                ?? null;

            $row->batch_name = $row->batch_name ?? '-';

            return $row;
        });
    }

    private function calculateProgramProgress(int $studentId, int $programId): array
    {
        $modules = $this->buildProgramProgressModules($studentId, $programId);

        $allSubTopics = collect($modules)
            ->flatMap(function ($module) {
                return collect($module['topics'] ?? [])
                    ->flatMap(fn ($topic) => $topic['sub_topics'] ?? []);
            })
            ->values();

        $totalLessons = $allSubTopics->count();
        $completedLessons = $allSubTopics->where('is_completed', true)->count();

        $currentLesson = $allSubTopics
            ->first(fn ($subTopic) => !($subTopic['is_completed'] ?? false));

        $progressPercentage = $totalLessons > 0
            ? (int) round(($completedLessons / $totalLessons) * 100)
            : 0;

        return [
            'progress' => max(0, min(100, $progressPercentage)),
            'completed_lessons' => $completedLessons,
            'total_lessons' => $totalLessons,
            'current_lesson_title' => $currentLesson['title'] ?? ($totalLessons > 0 ? 'All lessons completed' : ''),
            'continue_url' => $currentLesson
                ? $this->buildLessonUrl($programId, (object) $currentLesson)
                : '',
            'modules' => $modules,
        ];
    }

    private function buildProgramProgressModules(int $studentId, int $programId): array
    {
        if (!Schema::hasTable('modules') || !Schema::hasTable('topics') || !Schema::hasTable('sub_topics')) {
            return [];
        }

        $moduleIds = $this->getProgramModuleIds($programId);

        if ($moduleIds->isEmpty()) {
            return [];
        }

        $progressBySubTopic = $this->getProgressBySubTopic($studentId);

        $modules = DB::table('modules')
            ->whereIn('id', $moduleIds)
            ->when(Schema::hasColumn('modules', 'sort_order'), fn ($query) => $query->orderBy('sort_order'))
            ->when(!Schema::hasColumn('modules', 'sort_order'), fn ($query) => $query->orderBy('id'))
            ->get();

        return $modules
            ->map(function ($module) use ($progressBySubTopic) {
                $topics = DB::table('topics')
                    ->where('module_id', $module->id)
                    ->when(Schema::hasColumn('topics', 'sort_order'), fn ($query) => $query->orderBy('sort_order'))
                    ->when(!Schema::hasColumn('topics', 'sort_order'), fn ($query) => $query->orderBy('id'))
                    ->get();

                $normalizedTopics = $topics
                    ->map(function ($topic) use ($progressBySubTopic) {
                        $subTopicsQuery = DB::table('sub_topics')
                            ->where('topic_id', $topic->id);

                        if (Schema::hasColumn('sub_topics', 'is_active')) {
                            $subTopicsQuery->where('is_active', true);
                        }

                        $subTopics = $subTopicsQuery
                            ->when(Schema::hasColumn('sub_topics', 'sort_order'), fn ($query) => $query->orderBy('sort_order'))
                            ->when(!Schema::hasColumn('sub_topics', 'sort_order'), fn ($query) => $query->orderBy('id'))
                            ->get();

                        $normalizedSubTopics = $subTopics
                            ->map(function ($subTopic) use ($progressBySubTopic) {
                                $progress = $progressBySubTopic[$subTopic->id] ?? null;

                                $progressPercentage = $progress
                                    ? (float) ($progress->progress_percentage ?? $progress->progress ?? 0)
                                    : 0;

                                $isCompleted = $progress
                                    ? ((bool) ($progress->is_completed ?? false) || $progressPercentage >= 95)
                                    : false;

                                return [
                                    'id' => $subTopic->id,
                                    'slug' => $this->recordValue($subTopic, ['slug']),
                                    'title' => $this->recordValue($subTopic, ['name', 'title']) ?: 'Sub Topic',
                                    'progress_percentage' => $progressPercentage,
                                    'progressPercentage' => $progressPercentage,
                                    'is_completed' => $isCompleted,
                                    'isCompleted' => $isCompleted,
                                    'status' => $isCompleted ? 'completed' : 'available',
                                ];
                            })
                            ->values();

                        $totalSubTopics = $normalizedSubTopics->count();
                        $completedSubTopics = $normalizedSubTopics->where('is_completed', true)->count();

                        return [
                            'id' => $topic->id,
                            'title' => $this->recordValue($topic, ['name', 'title']) ?: 'Topic',
                            'total_sub_topics' => $totalSubTopics,
                            'totalSubTopics' => $totalSubTopics,
                            'completed_sub_topics' => $completedSubTopics,
                            'completedSubTopics' => $completedSubTopics,
                            'progress' => $totalSubTopics > 0
                                ? (int) round(($completedSubTopics / $totalSubTopics) * 100)
                                : 0,
                            'sub_topics' => $normalizedSubTopics->all(),
                            'subTopics' => $normalizedSubTopics->all(),
                        ];
                    })
                    ->values();

                $totalLessons = $normalizedTopics->sum('total_sub_topics');
                $completedLessons = $normalizedTopics->sum('completed_sub_topics');

                return [
                    'id' => $module->id,
                    'title' => $this->recordValue($module, ['name', 'title']) ?: 'Module',
                    'total_lessons' => $totalLessons,
                    'totalLessons' => $totalLessons,
                    'completed_lessons' => $completedLessons,
                    'completedLessons' => $completedLessons,
                    'progress' => $totalLessons > 0
                        ? (int) round(($completedLessons / $totalLessons) * 100)
                        : 0,
                    'topics' => $normalizedTopics->all(),
                ];
            })
            ->values()
            ->all();
    }

    private function getProgramModuleIds(int $programId): Collection
    {
        if (!Schema::hasTable('modules')) {
            return collect();
        }

        if (Schema::hasColumn('modules', 'program_id')) {
            return DB::table('modules')
                ->where('program_id', $programId)
                ->pluck('id');
        }

        if (
            Schema::hasTable('stages')
            && Schema::hasColumn('modules', 'stage_id')
            && Schema::hasColumn('stages', 'program_id')
        ) {
            return DB::table('modules')
                ->join('stages', 'modules.stage_id', '=', 'stages.id')
                ->where('stages.program_id', $programId)
                ->pluck('modules.id');
        }

        return collect();
    }

    private function getProgressBySubTopic(int $studentId): array
    {
        $table = $this->resolveProgressTable();

        if (!$table) {
            return [];
        }

        $subTopicColumn = $this->firstExistingColumn($table, [
            'sub_topic_id',
            'lesson_id',
        ]);

        if (!$subTopicColumn || !Schema::hasColumn($table, 'student_id')) {
            return [];
        }

        return DB::table($table)
            ->where('student_id', $studentId)
            ->get()
            ->keyBy($subTopicColumn)
            ->all();
    }

    private function resolveProgressTable(): ?string
    {
        foreach ([
            'student_lesson_progresses',
            'student_lesson_progress',
            'student_learning_progresses',
            'learning_progresses',
            'lesson_progresses',
        ] as $table) {
            if (Schema::hasTable($table)) {
                return $table;
            }
        }

        return null;
    }

    private function buildLessonUrl(int $programId, object $subTopic): string
    {
        $program = $this->findProgram($programId);

        $courseSlug = $this->recordValue($program, ['slug']);
        $lessonSlug = $this->recordValue($subTopic, ['slug']);

        if ($courseSlug && $lessonSlug) {
            return "/learn/{$courseSlug}/{$lessonSlug}";
        }

        return '/my-courses';
    }

    private function findProgram(int $programId): ?object
    {
        if (!Schema::hasTable('programs')) {
            return null;
        }

        return DB::table('programs')
            ->where('id', $programId)
            ->first();
    }

    private function getCertificateCount(?object $student): int
    {
        if (!$student || !Schema::hasTable('certificates')) {
            return 0;
        }

        if (!Schema::hasColumn('certificates', 'student_id')) {
            return 0;
        }

        return DB::table('certificates')
            ->where('student_id', $student->id)
            ->count();
    }

    private function getUnreadNotificationCount($user): int
    {
        if (!Schema::hasTable('notifications')) {
            return 0;
        }

        try {
            return DB::table('notifications')
                ->where('notifiable_id', $user->id)
                ->whereNull('read_at')
                ->count();
        } catch (Throwable) {
            return 0;
        }
    }

    private function firstExistingColumn(string $table, array $columns): ?string
    {
        foreach ($columns as $column) {
            if (Schema::hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    private function recordValue(mixed $record, array $keys): string
    {
        if (!$record) {
            return '';
        }

        foreach ($keys as $key) {
            $value = data_get($record, $key);

            if (filled($value)) {
                return trim((string) $value);
            }
        }

        return '';
    }

    private function firstFilled(array $values): string
    {
        foreach ($values as $value) {
            if (filled($value)) {
                return trim((string) $value);
            }
        }

        return '';
    }

    private function formatMonthYear(mixed $value): string
    {
        if (!$value) {
            return '-';
        }

        try {
            return Carbon::parse($value)->format('M Y');
        } catch (Throwable) {
            return '-';
        }
    }
}