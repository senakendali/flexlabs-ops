<?php

namespace App\Http\Controllers\Api\Lms;

use App\Http\Controllers\Controller;
use App\Models\Instructor;
use App\Models\InstructorAvailabilitySlot;
use App\Models\Student;
use App\Models\StudentMentoringSession;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class StudentMentoringController extends Controller
{
    public function instructors(Request $request): JsonResponse
    {
        $student = $this->resolveStudent($request->user());
        [$programIds, $batchIds] = $this->resolveEnrollmentTargets($student);

        $candidateInstructorIds = $this->resolveCandidateInstructorIds($programIds, $batchIds);

        $instructors = Instructor::query()
            ->when(Schema::hasColumn('instructors', 'is_active'), fn ($query) => $query->where('is_active', true))
            ->when($candidateInstructorIds->isNotEmpty(), fn ($query) => $query->whereIn('id', $candidateInstructorIds))
            ->whereHas('availabilitySlots', function ($slotQuery) {
                $slotQuery
                    ->where('status', 'available')
                    ->where('is_active', true)
                    ->whereDate('date', '>=', now()->toDateString());
            })
            ->orderBy('name')
            ->get()
            ->map(fn (Instructor $instructor) => $this->formatInstructor($instructor))
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'instructors' => $instructors,
            ],
        ]);
    }

    public function slots(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'instructor_id' => ['required', 'integer', Rule::exists('instructors', 'id')],
        ]);

        $student = $this->resolveStudent($request->user());
        [$programIds, $batchIds] = $this->resolveEnrollmentTargets($student);

        $candidateInstructorIds = $this->resolveCandidateInstructorIds($programIds, $batchIds);

        if (
            $candidateInstructorIds->isNotEmpty()
            && !$candidateInstructorIds->contains((int) $validated['instructor_id'])
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Instructor tidak tersedia untuk program atau batch kamu.',
            ], 403);
        }

        $slots = InstructorAvailabilitySlot::query()
            ->where('instructor_id', $validated['instructor_id'])
            ->where('status', 'available')
            ->where('is_active', true)
            ->whereDate('date', '>=', now()->toDateString())
            ->whereDoesntHave('mentoringSession', function ($sessionQuery) {
                $sessionQuery->whereIn('status', ['pending', 'approved', 'rescheduled']);
            })
            ->orderBy('date')
            ->orderBy('start_time')
            ->limit(20)
            ->get()
            ->map(fn (InstructorAvailabilitySlot $slot) => $this->formatSlot($slot))
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'slots' => $slots,
            ],
        ]);
    }

    public function book(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'topic_type' => [
                'required',
                Rule::in([
                    'code_review',
                    'debugging',
                    'project_consultation',
                    'career_portfolio',
                    'lesson_discussion',
                    'other',
                ]),
            ],
            'instructor_id' => ['required', 'integer', Rule::exists('instructors', 'id')],
            'availability_slot_id' => ['required', 'integer', Rule::exists('instructor_availability_slots', 'id')],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $student = $this->resolveStudent($request->user());
        [$programIds, $batchIds] = $this->resolveEnrollmentTargets($student);

        $candidateInstructorIds = $this->resolveCandidateInstructorIds($programIds, $batchIds);

        if (
            $candidateInstructorIds->isNotEmpty()
            && !$candidateInstructorIds->contains((int) $validated['instructor_id'])
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Instructor tidak tersedia untuk program atau batch kamu.',
            ], 403);
        }

        return DB::transaction(function () use ($validated, $student) {
            $slot = InstructorAvailabilitySlot::query()
                ->whereKey($validated['availability_slot_id'])
                ->lockForUpdate()
                ->first();

            if (!$slot) {
                return response()->json([
                    'success' => false,
                    'message' => 'Slot mentoring tidak ditemukan.',
                ], 404);
            }

            if ((int) $slot->instructor_id !== (int) $validated['instructor_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Slot tidak sesuai dengan instructor yang dipilih.',
                ], 422);
            }

            if ($slot->status !== 'available' || !$slot->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Slot mentoring sudah tidak tersedia.',
                ], 422);
            }

            if ($slot->date && Carbon::parse($slot->date)->lt(now()->startOfDay())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Slot mentoring sudah lewat.',
                ], 422);
            }

            $alreadyBooked = StudentMentoringSession::query()
                ->where('availability_slot_id', $slot->id)
                ->whereIn('status', ['pending', 'approved', 'rescheduled'])
                ->exists();

            if ($alreadyBooked) {
                return response()->json([
                    'success' => false,
                    'message' => 'Slot mentoring sudah dibooking student lain.',
                ], 422);
            }

            $studentHasActiveSessionOnSlot = StudentMentoringSession::query()
                ->where('student_id', $student->id)
                ->where('availability_slot_id', $slot->id)
                ->whereIn('status', ['pending', 'approved', 'rescheduled'])
                ->exists();

            if ($studentHasActiveSessionOnSlot) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kamu sudah booking slot ini.',
                ], 422);
            }

            $session = StudentMentoringSession::create([
                'student_id' => $student->id,
                'instructor_id' => $validated['instructor_id'],
                'availability_slot_id' => $slot->id,
                'topic_type' => $validated['topic_type'],
                'notes' => $validated['notes'] ?? null,
                'meeting_url' => null,
                'status' => 'pending',
                'requested_at' => now(),
                'approved_at' => null,
                'cancelled_at' => null,
            ]);

            /*
             * Kita lock slot dari sisi student booking supaya tidak bisa dipilih user lain.
             * Kalau admin reject/cancel, slot dibuka lagi oleh controller FlexOps.
             */
            $slot->update([
                'status' => 'booked',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Booking mentoring berhasil dibuat. Tunggu approval dari admin/instructor.',
                'data' => [
                    'session' => $this->formatMentoringSession(
                        $session->fresh()->load(['instructor', 'availabilitySlot'])
                    ),
                ],
            ], 201);
        });
    }

    private function resolveCandidateInstructorIds(Collection $programIds, Collection $batchIds): Collection
    {
        $ids = collect();

        if (Schema::hasTable('instructor_teaching_scopes')) {
            $scopeIds = DB::table('instructor_teaching_scopes')
                ->where('status', 'active')
                ->whereIn('program_id', $programIds)
                ->where(function ($query) use ($batchIds) {
                    $query->whereNull('batch_id');

                    if ($batchIds->isNotEmpty()) {
                        $query->orWhereIn('batch_id', $batchIds);
                    }
                })
                ->pluck('instructor_id');

            $ids = $ids->merge($scopeIds);
        }

        if ($ids->isEmpty() && Schema::hasTable('instructor_schedules')) {
            $scheduleQuery = DB::table('instructor_schedules');

            $scheduleQuery->where(function ($query) use ($programIds, $batchIds) {
                if ($batchIds->isNotEmpty() && Schema::hasColumn('instructor_schedules', 'batch_id')) {
                    $query->orWhereIn('batch_id', $batchIds);
                }

                if ($programIds->isNotEmpty() && Schema::hasColumn('instructor_schedules', 'program_id')) {
                    $query->orWhereIn('program_id', $programIds);
                }
            });

            $ids = $ids->merge($scheduleQuery->pluck('instructor_id'));
        }

        return $ids
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    private function formatInstructor(Instructor $instructor): array
    {
        return [
            'id' => $instructor->id,
            'name' => $instructor->name,
            'email' => $instructor->email,
            'phone' => $instructor->phone,
            'specialization' => $instructor->specialization,
            'photo_url' => $instructor->photo
                ? (str_starts_with($instructor->photo, 'http')
                    ? $instructor->photo
                    : asset('storage/' . $instructor->photo))
                : null,
        ];
    }

    private function formatSlot(InstructorAvailabilitySlot $slot): array
    {
        $startTime = $slot->start_time ? substr($slot->start_time, 0, 5) : null;
        $endTime = $slot->end_time ? substr($slot->end_time, 0, 5) : null;

        return [
            'id' => $slot->id,
            'instructor_id' => $slot->instructor_id,

            'date' => $slot->date?->format('Y-m-d'),
            'date_label' => $slot->date?->format('d M Y'),

            'start_time' => $startTime,
            'end_time' => $endTime,
            'time_label' => $startTime && $endTime ? "{$startTime} - {$endTime}" : '-',

            'status' => $slot->status,
        ];
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
        ];
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