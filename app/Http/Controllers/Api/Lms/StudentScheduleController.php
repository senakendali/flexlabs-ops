<?php

namespace App\Http\Controllers\Api\Lms;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class StudentScheduleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $timezone = config('app.timezone', 'Asia/Jakarta');

        $student = $this->resolveStudent($request);
        $batchIds = $this->resolveStudentBatchIds($student);

        $liveSessions = $this->getLiveSessions($batchIds, $timezone);
        $mentoringSessions = $this->getMentoringSessions($student?->id, $batchIds, $timezone);

        $sessions = $liveSessions
            ->merge($mentoringSessions)
            ->sortBy('start_at')
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Student schedules loaded successfully.',
            'student' => [
                'id' => $student?->id,
                'name' => $this->resolveStudentName($student, $request),
                'avatarUrl' => $this->pickValue($student, [
                    'avatar_url',
                    'photo_url',
                    'profile_photo_url',
                    'image_url',
                ]),
            ],
            'summaries' => [
                'total' => $sessions->count(),
                'today' => $sessions->where('date', 'Today')->count(),
                'live_session' => $sessions->where('type', 'live_session')->count(),
                'mentoring_session' => $sessions->where('type', 'mentoring_session')->count(),
            ],
            'sessions' => $sessions,
        ]);
    }

    private function getLiveSessions(array $batchIds, string $timezone): Collection
    {
        $model = $this->firstExistingModel([
            \App\Models\Academic\InstructorSchedule::class,
            \App\Models\InstructorSchedule::class,
        ]);

        if (!$model) {
            return collect();
        }

        $instance = new $model();
        $table = $instance->getTable();

        if (!Schema::hasTable($table)) {
            return collect();
        }

        $dateColumn = $this->firstExistingColumn($table, [
            'schedule_date',
            'session_date',
            'date',
        ]);

        $startColumn = $this->firstExistingColumn($table, [
            'start_time',
            'starts_at',
            'started_at',
        ]);

        if (!$dateColumn && !$startColumn) {
            return collect();
        }

        $query = $model::query();

        $relations = $this->availableRelations($instance, [
            'batch',
            'batch.program',
            'program',
            'instructor',
            'teacher',
            'mentor',
            'module',
            'topic',
            'subTopic',
            'subtopic',
        ]);

        if (!empty($relations)) {
            $query->with($relations);
        }

        if (!empty($batchIds) && Schema::hasColumn($table, 'batch_id')) {
            $query->whereIn('batch_id', $batchIds);
        }

        if ($dateColumn) {
            $query
                ->whereDate($dateColumn, '>=', now($timezone)->toDateString())
                ->whereDate($dateColumn, '<=', now($timezone)->addDays(60)->toDateString())
                ->orderBy($dateColumn);
        }

        if ($startColumn) {
            $query->orderBy($startColumn);
        }

        return $query
            ->get()
            ->map(fn ($schedule) => $this->mapLiveSession($schedule, $timezone))
            ->filter()
            ->values();
    }

    private function getMentoringSessions(?int $studentId, array $batchIds, string $timezone): Collection
    {
        $model = $this->firstExistingModel([
            \App\Models\StudentMentoringSession::class,
            \App\Models\Academic\StudentMentoringSession::class,
            \App\Models\Lms\StudentMentoringSession::class,
        ]);

        if (!$model) {
            return collect();
        }

        $instance = new $model();
        $table = $instance->getTable();

        if (!Schema::hasTable($table)) {
            return collect();
        }

        $query = $model::query();

        $relations = $this->availableRelations($instance, [
            'student',
            'instructor',
            'availabilitySlot',
        ]);

        if (!empty($relations)) {
            $query->with($relations);
        }

        if ($studentId && Schema::hasColumn($table, 'student_id')) {
            $query->where('student_id', $studentId);
        }

        if (Schema::hasColumn($table, 'status')) {
            $query->whereNotIn('status', [
                'completed',
                'cancelled',
                'canceled',
                'rejected',
            ]);
        }

        return $query
            ->latest('created_at')
            ->get()
            ->map(fn ($session) => $this->mapMentoringSession($session, $timezone))
            ->filter(function ($item) use ($timezone) {
                if (!$item || empty($item['start_at'])) {
                    return false;
                }

                return Carbon::parse($item['start_at'])
                    ->timezone($timezone)
                    ->greaterThanOrEqualTo(now($timezone)->startOfDay());
            })
            ->values();
    }

    private function mapLiveSession($schedule, string $timezone): ?array
    {
        $date = $this->pickValue($schedule, [
            'schedule_date',
            'session_date',
            'date',
        ]);

        $startTime = $this->pickValue($schedule, [
            'start_time',
            'starts_at',
            'started_at',
        ]);

        $endTime = $this->pickValue($schedule, [
            'end_time',
            'ends_at',
            'ended_at',
        ]);

        $startAt = $this->buildDateTime($date, $startTime, $timezone);
        $endAt = $this->buildDateTime($date, $endTime, $timezone);

        if (!$startAt) {
            return null;
        }

        $title = $this->pickValue($schedule, [
            'title',
            'session_title',
            'topic_title',
            'name',
        ]);

        $title = $title
            ?: data_get($schedule, 'subTopic.title')
            ?: data_get($schedule, 'subtopic.title')
            ?: data_get($schedule, 'topic.title')
            ?: data_get($schedule, 'module.title')
            ?: data_get($schedule, 'batch.name')
            ?: 'Live Session';

        $description = $this->pickValue($schedule, [
            'description',
            'notes',
            'session_notes',
            'agenda',
        ]);

        $lecturer = data_get($schedule, 'instructor.name')
            ?: data_get($schedule, 'teacher.name')
            ?: data_get($schedule, 'mentor.name')
            ?: $this->pickValue($schedule, [
                'instructor_name',
                'teacher_name',
                'mentor_name',
            ])
            ?: 'FlexLabs Academic Team';

        $meetingUrl = $this->resolveMeetingUrl($schedule);
        $status = $this->resolveStatus($schedule, $startAt, $endAt, $timezone);

        return [
            'id' => 'live-' . $schedule->id,
            'source' => 'instructor_schedule',
            'source_id' => $schedule->id,

            'type' => 'live_session',
            'typeLabel' => 'Live Session',

            'title' => $title,
            'description' => $description ?: 'Scheduled live class with your instructor.',

            'date' => $this->formatDateLabel($startAt, $timezone),
            'raw_date' => $startAt->copy()->timezone($timezone)->toDateString(),
            'time' => $this->formatTimeRange($startAt, $endAt, $timezone),

            'start_at' => $startAt->toIso8601String(),
            'end_at' => $endAt?->toIso8601String(),

            'lecturer' => $lecturer,
            'meeting_url' => $meetingUrl,

            'status' => $status,
            'statusLabel' => $this->statusLabel($status),
        ];
    }

    private function mapMentoringSession($session, string $timezone): ?array
    {
        $slot = data_get($session, 'availabilitySlot');

        if (!$slot) {
            return null;
        }

        $date = $this->pickValue($slot, [
            'date',
            'slot_date',
            'available_date',
            'availability_date',
            'schedule_date',
        ]);

        $startTime = $this->pickValue($slot, [
            'start_time',
            'starts_at',
            'started_at',
        ]);

        $endTime = $this->pickValue($slot, [
            'end_time',
            'ends_at',
            'ended_at',
        ]);

        $startAt = $this->buildDateTime($date, $startTime, $timezone);
        $endAt = $this->buildDateTime($date, $endTime, $timezone);

        if (!$startAt) {
            return null;
        }

        $topicLabel = $session->topic_type_label
            ?? $this->formatMentoringTopicType($session->topic_type);

        $lecturer = data_get($session, 'instructor.name')
            ?: $this->pickValue($session, [
                'instructor_name',
                'mentor_name',
            ])
            ?: 'FlexLabs Mentor';

        $meetingUrl = $this->resolveMeetingUrl($session)
            ?: $this->resolveMeetingUrl($slot);

        $status = strtolower((string) ($session->status ?? 'pending'));

        return [
            'id' => 'mentoring-' . $session->id,
            'source' => 'student_mentoring_session',
            'source_id' => $session->id,

            'type' => 'mentoring_session',
            'typeLabel' => 'Mentoring Session',

            'title' => '1-on-1 Mentoring - ' . $topicLabel,
            'description' => $session->notes ?: 'Private mentoring session with your instructor.',

            'date' => $this->formatDateLabel($startAt, $timezone),
            'raw_date' => $startAt->copy()->timezone($timezone)->toDateString(),
            'time' => $this->formatTimeRange($startAt, $endAt, $timezone),

            'start_at' => $startAt->toIso8601String(),
            'end_at' => $endAt?->toIso8601String(),

            'lecturer' => $lecturer,
            'meeting_url' => $meetingUrl,

            'status' => $status,
            'statusLabel' => $session->status_label ?? $this->mentoringStatusLabel($status),
        ];
    }

    private function formatMentoringTopicType(?string $topicType): string
    {
        return match ($topicType) {
            'code_review' => 'Code Review',
            'debugging' => 'Debugging',
            'project_consultation' => 'Project Consultation',
            'career_portfolio' => 'Career / Portfolio',
            'lesson_discussion' => 'Lesson Discussion',
            'other' => 'Other',
            default => str($topicType)->replace('_', ' ')->title()->toString(),
        };
    }

    private function mentoringStatusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'Pending Approval',
            'approved' => 'Approved',
            'rescheduled' => 'Rescheduled',
            'completed' => 'Completed',
            'cancelled', 'canceled' => 'Cancelled',
            'rejected' => 'Rejected',
            default => str($status)->replace('_', ' ')->title()->toString(),
        };
    }

    private function resolveMeetingUrl($model): ?string
    {
        $directValue = $this->pickValue($model, [
            'meeting_link',
            'meetingLink',

            'meeting_url',
            'meetingUrl',

            'meet_link',
            'meetLink',

            'meet_url',
            'meetUrl',

            'google_meet_link',
            'googleMeetLink',

            'google_meet_url',
            'googleMeetUrl',

            'zoom_link',
            'zoomLink',

            'zoom_url',
            'zoomUrl',

            'join_link',
            'joinLink',

            'join_url',
            'joinUrl',

            'url',
            'link',
        ]);

        if ($this->looksLikeUrl($directValue)) {
            return trim($directValue);
        }

        return null;
    }

    private function looksLikeUrl($value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        $value = trim($value);

        if ($value === '') {
            return false;
        }

        return str_starts_with($value, 'http://')
            || str_starts_with($value, 'https://')
            || str_contains($value, 'meet.google.com')
            || str_contains($value, 'zoom.us')
            || str_contains($value, 'teams.microsoft.com');
    }

    private function resolveStudent(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return null;
        }

        if (method_exists($user, 'student')) {
            $student = $user->student;

            if ($student) {
                return $student;
            }
        }

        $studentModel = $this->firstExistingModel([
            \App\Models\Academic\Student::class,
            \App\Models\Student::class,
        ]);

        if (!$studentModel) {
            return null;
        }

        $instance = new $studentModel();
        $table = $instance->getTable();

        if (!Schema::hasTable($table)) {
            return null;
        }

        if (Schema::hasColumn($table, 'user_id')) {
            $student = $studentModel::query()
                ->where('user_id', $user->id)
                ->first();

            if ($student) {
                return $student;
            }
        }

        if (!empty($user->student_id)) {
            $student = $studentModel::query()->find($user->student_id);

            if ($student) {
                return $student;
            }
        }

        if (Schema::hasColumn($table, 'email') && !empty($user->email)) {
            return $studentModel::query()
                ->where('email', $user->email)
                ->first();
        }

        return null;
    }

    private function resolveStudentName($student, Request $request): string
    {
        $user = $request->user();

        return $this->pickValue($student, [
            'full_name',
            'student_name',
            'first_name',
            'nickname',
            'name',
        ])
            ?: $this->pickValue($user, [
                'full_name',
                'name',
                'email',
            ])
            ?: 'Student';
    }

    private function resolveStudentBatchIds($student): array
    {
        if (!$student) {
            return [];
        }

        $batchIds = collect();

        $directBatchId = $this->pickValue($student, [
            'batch_id',
            'current_batch_id',
        ]);

        if ($directBatchId) {
            $batchIds->push((int) $directBatchId);
        }

        if (method_exists($student, 'batches')) {
            try {
                $student->loadMissing('batches');

                $relationBatchIds = $student->batches
                    ? $student->batches->pluck('id')
                    : collect();

                $batchIds = $batchIds->merge($relationBatchIds);
            } catch (\Throwable) {
                //
            }
        }

        return $batchIds
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function resolveStatus($model, Carbon $startAt, ?Carbon $endAt, string $timezone): string
    {
        $rawStatus = strtolower((string) ($model->status ?? ''));

        if (in_array($rawStatus, ['cancelled', 'canceled'], true)) {
            return 'cancelled';
        }

        if (in_array($rawStatus, ['completed', 'done', 'finished'], true)) {
            return 'completed';
        }

        return 'scheduled';
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'available' => 'Ready to join',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            default => 'Scheduled',
        };
    }

    private function buildDateTime($date, $time, string $timezone): ?Carbon
    {
        if (!$date && !$time) {
            return null;
        }

        try {
            if ($date instanceof \DateTimeInterface) {
                $date = Carbon::parse($date)->toDateString();
            }

            if ($time instanceof \DateTimeInterface) {
                $time = Carbon::parse($time)->format('H:i:s');
            }

            if ($date && !$time) {
                return Carbon::parse($date, $timezone)->startOfDay();
            }

            if (!$date && $time) {
                return Carbon::parse($time, $timezone);
            }

            return Carbon::parse(trim($date . ' ' . $time), $timezone);
        } catch (\Throwable) {
            return null;
        }
    }

    private function formatDateLabel(Carbon $date, string $timezone): string
    {
        $target = $date->copy()->timezone($timezone);
        $today = now($timezone)->startOfDay();
        $tomorrow = now($timezone)->addDay()->startOfDay();

        if ($target->isSameDay($today)) {
            return 'Today';
        }

        if ($target->isSameDay($tomorrow)) {
            return 'Tomorrow';
        }

        return $target->translatedFormat('l, d F Y');
    }

    private function formatTimeRange(Carbon $startAt, ?Carbon $endAt, string $timezone): string
    {
        $start = $startAt->copy()->timezone($timezone)->format('H:i');

        if (!$endAt) {
            return $start . ' WIB';
        }

        $end = $endAt->copy()->timezone($timezone)->format('H:i');

        return $start . ' - ' . $end . ' WIB';
    }

    private function pickValue($model, array $columns): mixed
    {
        if (!$model) {
            return null;
        }

        foreach ($columns as $column) {
            $value = data_get($model, $column);

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function firstExistingModel(array $models): ?string
    {
        foreach ($models as $model) {
            if (class_exists($model)) {
                return $model;
            }
        }

        return null;
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

    private function availableRelations(object $model, array $relations): array
    {
        return collect($relations)
            ->filter(function ($relation) use ($model) {
                $firstRelation = explode('.', $relation)[0];

                return method_exists($model, $firstRelation);
            })
            ->values()
            ->all();
    }
}