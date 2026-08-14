<?php

namespace App\Http\Controllers\Api\Lms;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentMentoringSession;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StudentUpcomingSessionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $student = $this->resolveStudent($request->user());
        [$programIds, $batchIds] = $this->resolveEnrollmentTargets($student);

        $mentoringSessions = $this->getMentoringSessions($student);
        $liveSessions = $this->getLiveSessions($programIds, $batchIds);

        $sessions = $mentoringSessions
            ->merge($liveSessions)
            ->sortBy('sort_datetime')
            ->values()
            ->map(function (array $session) {
                unset($session['sort_datetime']);

                return $session;
            });

        return response()->json([
            'success' => true,
            'data' => [
                'upcoming_sessions' => $sessions,
                'mentoring_sessions' => $mentoringSessions->map(function (array $session) {
                    unset($session['sort_datetime']);

                    return $session;
                })->values(),
                'live_sessions' => $liveSessions->map(function (array $session) {
                    unset($session['sort_datetime']);

                    return $session;
                })->values(),
            ],
        ]);
    }

    private function getMentoringSessions(Student $student): Collection
    {
        return StudentMentoringSession::query()
            ->with(['instructor', 'availabilitySlot'])
            ->where('student_id', $student->id)
            ->whereIn('status', ['pending', 'approved', 'rescheduled'])
            ->whereHas('availabilitySlot', function ($query) {
                $query->whereDate('date', '>=', now()->toDateString());
            })
            ->get()
            ->map(fn (StudentMentoringSession $session) => $this->formatMentoringSession($session))
            ->values();
    }

    private function getLiveSessions(Collection $programIds, Collection $batchIds): Collection
    {
        if (!Schema::hasTable('instructor_schedules')) {
            return collect();
        }

        $query = DB::table('instructor_schedules')
            ->leftJoin('instructors', 'instructors.id', '=', 'instructor_schedules.instructor_id')
            ->leftJoin('batches', 'batches.id', '=', 'instructor_schedules.batch_id')
            ->leftJoin('programs', 'programs.id', '=', 'instructor_schedules.program_id')
            ->whereDate('instructor_schedules.schedule_date', '>=', now()->toDateString());

        $query->where(function ($targetQuery) use ($programIds, $batchIds) {
            if ($batchIds->isNotEmpty() && Schema::hasColumn('instructor_schedules', 'batch_id')) {
                $targetQuery->orWhereIn('instructor_schedules.batch_id', $batchIds);
            }

            if ($programIds->isNotEmpty() && Schema::hasColumn('instructor_schedules', 'program_id')) {
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
            'instructor_schedules.instructor_id',
            'instructor_schedules.batch_id',
            'instructor_schedules.program_id',
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

        if (Schema::hasColumn('instructor_schedules', 'status')) {
            $selects[] = 'instructor_schedules.status';
        }

        return $query
            ->select($selects)
            ->orderBy('instructor_schedules.schedule_date')
            ->orderBy('instructor_schedules.start_time')
            ->limit(10)
            ->get()
            ->map(fn ($schedule) => $this->formatLiveSession($schedule))
            ->values();
    }

    private function formatMentoringSession(StudentMentoringSession $session): array
    {
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
            'date' => $date?->format('Y-m-d'),
            'date_label' => $dateLabel,
            'time_label' => $timeLabel,
            'status' => $session->status,
            'badge_label' => $session->status_label,
            'join_url' => $session->status === 'approved' ? $session->meeting_url : null,
            'meeting_url' => $session->status === 'approved' ? $session->meeting_url : null,
            'instructor_name' => $session->instructor?->name,
            'sort_datetime' => $this->makeSortDateTime($date?->format('Y-m-d'), $startTime),
        ];
    }

    private function formatLiveSession(object $schedule): array
    {
        $date = $schedule->schedule_date ?? null;
        $startTime = isset($schedule->start_time) ? substr($schedule->start_time, 0, 5) : null;
        $endTime = isset($schedule->end_time) ? substr($schedule->end_time, 0, 5) : null;

        $dateLabel = $date ? Carbon::parse($date)->format('d M Y') : '-';
        $timeLabel = $startTime && $endTime ? "{$startTime} - {$endTime}" : '-';

        $title = $schedule->title
            ?? $schedule->topic
            ?? 'Live Class';

        $subtitleParts = collect([
            $schedule->program_name ?? null,
            $schedule->batch_name ?? null,
            $schedule->instructor_name ?? null,
        ])->filter()->values();

        return [
            'id' => $schedule->id,
            'type' => 'live',
            'title' => $title,
            'subtitle' => $subtitleParts->implode(' • '),
            'time' => "{$dateLabel}, {$timeLabel}",
            'date' => $date,
            'date_label' => $dateLabel,
            'time_label' => $timeLabel,
            'status' => $schedule->status ?? 'scheduled',
            'badge_label' => 'Live Class',
            'join_url' => $schedule->meeting_url ?? null,
            'meeting_url' => $schedule->meeting_url ?? null,
            'program_name' => $schedule->program_name ?? null,
            'batch_name' => $schedule->batch_name ?? null,
            'instructor_name' => $schedule->instructor_name ?? null,
            'sort_datetime' => $this->makeSortDateTime($date, $startTime),
        ];
    }

    private function makeSortDateTime(?string $date, ?string $time): string
    {
        if (!$date) {
            return now()->addYears(10)->toDateTimeString();
        }

        return trim($date . ' ' . ($time ?: '00:00'));
    }

    private function resolveStudent(?User $user): Student
    {
        if (!$user || !$this->isStudentUser($user)) {
            abort(response()->json([
                'success' => false,
                'message' => 'Unauthorized student account.',
            ], 403));
        }

        $user->loadMissing([
            'student.activeEnrollments.program',
            'student.activeEnrollments.batch.program',
        ]);

        if (!$user->student) {
            abort(response()->json([
                'success' => false,
                'message' => 'Student profile tidak ditemukan.',
            ], 403));
        }

        return $user->student;
    }

    private function isStudentUser(User $user): bool
    {
        return ($user->user_type ?? null) === 'student'
            || ($user->role ?? null) === 'student';
    }

    private function resolveEnrollmentTargets(Student $student): array
    {
        $activeEnrollments = $student->activeEnrollments
            ->filter(fn ($enrollment) => ($enrollment->is_accessible ?? true))
            ->values();

        $programIds = $activeEnrollments
            ->map(fn ($enrollment) => $enrollment->program?->id ?? $enrollment->batch?->program?->id)
            ->filter()
            ->unique()
            ->values();

        $batchIds = $activeEnrollments
            ->pluck('batch_id')
            ->filter()
            ->unique()
            ->values();

        return [$programIds, $batchIds];
    }
}