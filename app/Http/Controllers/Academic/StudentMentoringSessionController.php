<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\InstructorAvailabilitySlot;
use App\Models\StudentMentoringSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentMentoringSessionController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->get('per_page', 10);

        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $search = $request->get('search');
        $status = $request->get('status');
        $date = $request->get('date');

        $sessions = StudentMentoringSession::query()
            ->with([
                'student',
                'instructor',
                'availabilitySlot',
            ])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery
                        ->whereHas('student', function ($studentQuery) use ($search) {
                            $studentQuery
                                ->where('full_name', 'like', '%' . $search . '%')
                                ->orWhere('email', 'like', '%' . $search . '%')
                                ->orWhere('phone', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('instructor', function ($instructorQuery) use ($search) {
                            $instructorQuery
                                ->where('name', 'like', '%' . $search . '%')
                                ->orWhere('email', 'like', '%' . $search . '%')
                                ->orWhere('specialization', 'like', '%' . $search . '%');
                        })
                        ->orWhere('topic_type', 'like', '%' . $search . '%')
                        ->orWhere('notes', 'like', '%' . $search . '%');
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($date, function ($query) use ($date) {
                $query->whereHas('availabilitySlot', function ($slotQuery) use ($date) {
                    $slotQuery->whereDate('date', $date);
                });
            })
            ->latest('requested_at')
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        $stats = [
            'total' => StudentMentoringSession::count(),
            'pending' => StudentMentoringSession::where('status', 'pending')->count(),
            'approved' => StudentMentoringSession::where('status', 'approved')->count(),
            'completed' => StudentMentoringSession::where('status', 'completed')->count(),
            'cancelled' => StudentMentoringSession::whereIn('status', ['cancelled', 'rejected'])->count(),
        ];

        return view('academic.mentoring-sessions.index', compact(
            'sessions',
            'stats',
            'search',
            'status',
            'date'
        ));
    }

    public function show(StudentMentoringSession $studentMentoringSession): JsonResponse
    {
        $studentMentoringSession->load([
            'student',
            'instructor',
            'availabilitySlot',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mentoring session berhasil diambil.',
            'data' => $this->formatSessionResponse($studentMentoringSession),
        ]);
    }

    public function approve(Request $request, StudentMentoringSession $studentMentoringSession): JsonResponse
    {
        $validated = $request->validate([
            'meeting_url' => ['nullable', 'string', 'max:2000'],
        ]);

        if (!$studentMentoringSession->is_pending) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya mentoring session dengan status pending yang bisa di-approve.',
            ], 422);
        }

        return DB::transaction(function () use ($studentMentoringSession, $validated) {
            $studentMentoringSession->load('availabilitySlot');

            $slot = $studentMentoringSession->availabilitySlot;

            if (!$slot) {
                return response()->json([
                    'success' => false,
                    'message' => 'Availability slot tidak ditemukan.',
                ], 422);
            }

            /**
             * Saat approve, slot dikunci sebagai booked.
             */
            $slot->update([
                'status' => 'booked',
            ]);

            $studentMentoringSession->update([
                'status' => 'approved',
                'meeting_url' => $validated['meeting_url'] ?? $studentMentoringSession->meeting_url,
                'approved_at' => now(),
                'cancelled_at' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mentoring session berhasil di-approve.',
                'data' => $this->formatSessionResponse(
                    $studentMentoringSession->fresh()->load([
                        'student',
                        'instructor',
                        'availabilitySlot',
                    ])
                ),
            ]);
        });
    }

    public function reject(Request $request, StudentMentoringSession $studentMentoringSession): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        if (!in_array($studentMentoringSession->status, ['pending', 'approved'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Mentoring session ini tidak bisa direject.',
            ], 422);
        }

        return DB::transaction(function () use ($studentMentoringSession, $validated) {
            $studentMentoringSession->load('availabilitySlot');

            /**
             * Kalau booking ditolak, slot dibuka lagi.
             */
            $studentMentoringSession->availabilitySlot?->update([
                'status' => 'available',
            ]);

            $notes = $studentMentoringSession->notes;

            if (!empty($validated['reason'])) {
                $notes = trim(($notes ? $notes . "\n\n" : '') . 'Reject reason: ' . $validated['reason']);
            }

            $studentMentoringSession->update([
                'status' => 'rejected',
                'notes' => $notes,
                'cancelled_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mentoring session berhasil direject.',
                'data' => $this->formatSessionResponse(
                    $studentMentoringSession->fresh()->load([
                        'student',
                        'instructor',
                        'availabilitySlot',
                    ])
                ),
            ]);
        });
    }

    public function complete(StudentMentoringSession $studentMentoringSession): JsonResponse
    {
        if ($studentMentoringSession->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya mentoring session approved yang bisa ditandai completed.',
            ], 422);
        }

        $studentMentoringSession->update([
            'status' => 'completed',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mentoring session berhasil ditandai completed.',
            'data' => $this->formatSessionResponse(
                $studentMentoringSession->fresh()->load([
                    'student',
                    'instructor',
                    'availabilitySlot',
                ])
            ),
        ]);
    }

    public function cancel(Request $request, StudentMentoringSession $studentMentoringSession): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        if (in_array($studentMentoringSession->status, ['completed', 'cancelled', 'rejected'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Mentoring session ini tidak bisa dicancel.',
            ], 422);
        }

        return DB::transaction(function () use ($studentMentoringSession, $validated) {
            $studentMentoringSession->load('availabilitySlot');

            /**
             * Kalau dicancel sebelum selesai, slot dibuka lagi.
             */
            $studentMentoringSession->availabilitySlot?->update([
                'status' => 'available',
            ]);

            $notes = $studentMentoringSession->notes;

            if (!empty($validated['reason'])) {
                $notes = trim(($notes ? $notes . "\n\n" : '') . 'Cancel reason: ' . $validated['reason']);
            }

            $studentMentoringSession->update([
                'status' => 'cancelled',
                'notes' => $notes,
                'cancelled_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mentoring session berhasil dicancel.',
                'data' => $this->formatSessionResponse(
                    $studentMentoringSession->fresh()->load([
                        'student',
                        'instructor',
                        'availabilitySlot',
                    ])
                ),
            ]);
        });
    }

    public function updateMeetingUrl(Request $request, StudentMentoringSession $studentMentoringSession): JsonResponse
    {
        $validated = $request->validate([
            'meeting_url' => ['nullable', 'string', 'max:2000'],
        ]);

        if (!in_array($studentMentoringSession->status, ['pending', 'approved'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Meeting URL hanya bisa diupdate untuk session pending atau approved.',
            ], 422);
        }

        $studentMentoringSession->update([
            'meeting_url' => $validated['meeting_url'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Meeting URL berhasil diupdate.',
            'data' => $this->formatSessionResponse(
                $studentMentoringSession->fresh()->load([
                    'student',
                    'instructor',
                    'availabilitySlot',
                ])
            ),
        ]);
    }

    public function updateStatus(Request $request, StudentMentoringSession $studentMentoringSession): JsonResponse
    {
        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in([
                    'pending',
                    'approved',
                    'rescheduled',
                    'completed',
                    'cancelled',
                    'rejected',
                ]),
            ],
            'meeting_url' => ['nullable', 'string', 'max:2000'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        return match ($validated['status']) {
            'approved' => $this->approve($request, $studentMentoringSession),
            'completed' => $this->complete($studentMentoringSession),
            'cancelled' => $this->cancel($request, $studentMentoringSession),
            'rejected' => $this->reject($request, $studentMentoringSession),
            default => $this->genericStatusUpdate($studentMentoringSession, $validated),
        };
    }

    private function genericStatusUpdate(StudentMentoringSession $session, array $validated): JsonResponse
    {
        $payload = [
            'status' => $validated['status'],
        ];

        if (array_key_exists('meeting_url', $validated)) {
            $payload['meeting_url'] = $validated['meeting_url'];
        }

        if ($validated['status'] === 'pending') {
            $payload['approved_at'] = null;
            $payload['cancelled_at'] = null;
        }

        $session->update($payload);

        return response()->json([
            'success' => true,
            'message' => 'Mentoring session berhasil diupdate.',
            'data' => $this->formatSessionResponse(
                $session->fresh()->load([
                    'student',
                    'instructor',
                    'availabilitySlot',
                ])
            ),
        ]);
    }

    private function formatSessionResponse(StudentMentoringSession $session): array
    {
        $slot = $session->availabilitySlot;

        $slotDate = $slot?->date;
        $slotStartTime = $slot?->start_time ? substr($slot->start_time, 0, 5) : null;
        $slotEndTime = $slot?->end_time ? substr($slot->end_time, 0, 5) : null;

        return [
            'id' => $session->id,

            'student_id' => $session->student_id,
            'student_name' => $session->student?->full_name,
            'student_email' => $session->student?->email,
            'student_phone' => $session->student?->phone,

            'instructor_id' => $session->instructor_id,
            'instructor_name' => $session->instructor?->name,
            'instructor_email' => $session->instructor?->email,
            'instructor_specialization' => $session->instructor?->specialization,

            'availability_slot_id' => $session->availability_slot_id,

            'slot_date' => $slotDate?->format('Y-m-d'),
            'slot_date_label' => $slotDate?->format('d M Y'),
            'slot_start_time' => $slotStartTime,
            'slot_end_time' => $slotEndTime,
            'slot_time_label' => $slotStartTime && $slotEndTime
                ? $slotStartTime . ' - ' . $slotEndTime
                : '-',

            'topic_type' => $session->topic_type,
            'topic_type_label' => $session->topic_type_label,

            'notes' => $session->notes,
            'meeting_url' => $session->meeting_url,

            'status' => $session->status,
            'status_label' => $session->status_label,

            'requested_at' => $session->requested_at?->format('Y-m-d H:i:s'),
            'requested_at_label' => $session->requested_at?->format('d M Y H:i'),

            'approved_at' => $session->approved_at?->format('Y-m-d H:i:s'),
            'approved_at_label' => $session->approved_at?->format('d M Y H:i'),

            'cancelled_at' => $session->cancelled_at?->format('Y-m-d H:i:s'),
            'cancelled_at_label' => $session->cancelled_at?->format('d M Y H:i'),

            'created_at' => $session->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $session->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}