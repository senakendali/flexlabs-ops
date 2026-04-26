<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Instructor;
use App\Models\InstructorAvailabilitySlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InstructorAvailabilitySlotController extends Controller
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
        $instructorId = $request->get('instructor_id');

        $slots = InstructorAvailabilitySlot::query()
            ->with('instructor')
            ->when($search, function ($query) use ($search) {
                $query->whereHas('instructor', function ($instructorQuery) use ($search) {
                    $instructorQuery
                        ->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('specialization', 'like', '%' . $search . '%');
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($date, fn ($query) => $query->whereDate('date', $date))
            ->when($instructorId, fn ($query) => $query->where('instructor_id', $instructorId))
            ->latest('date')
            ->latest('start_time')
            ->paginate($perPage)
            ->withQueryString();

        $instructors = Instructor::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $stats = [
            'total' => InstructorAvailabilitySlot::count(),
            'available' => InstructorAvailabilitySlot::where('status', 'available')->count(),
            'booked' => InstructorAvailabilitySlot::where('status', 'booked')->count(),
            'blocked' => InstructorAvailabilitySlot::where('status', 'blocked')->count(),
        ];

        return view('academic.instructor-availability.index', compact(
            'slots',
            'instructors',
            'stats',
            'search',
            'status',
            'date',
            'instructorId'
        ));
    }

    public function show(InstructorAvailabilitySlot $instructorAvailabilitySlot): JsonResponse
    {
        $instructorAvailabilitySlot->load('instructor');

        return response()->json([
            'success' => true,
            'message' => 'Availability slot berhasil diambil.',
            'data' => $this->formatSlotResponse($instructorAvailabilitySlot),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateRequest($request);

        $this->ensureNoOverlappingSlot(
            instructorId: (int) $validated['instructor_id'],
            date: $validated['date'],
            startTime: $validated['start_time'],
            endTime: $validated['end_time']
        );

        $slot = InstructorAvailabilitySlot::create([
            'instructor_id' => $validated['instructor_id'],
            'date' => $validated['date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'status' => $validated['status'] ?? 'available',
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Availability slot berhasil ditambahkan.',
            'data' => $this->formatSlotResponse($slot->load('instructor')),
        ], 201);
    }

    public function update(Request $request, InstructorAvailabilitySlot $instructorAvailabilitySlot): JsonResponse
    {
        $validated = $this->validateRequest($request, $instructorAvailabilitySlot->id);

        $this->ensureNoOverlappingSlot(
            instructorId: (int) $validated['instructor_id'],
            date: $validated['date'],
            startTime: $validated['start_time'],
            endTime: $validated['end_time'],
            ignoreId: $instructorAvailabilitySlot->id
        );

        $instructorAvailabilitySlot->update([
            'instructor_id' => $validated['instructor_id'],
            'date' => $validated['date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'status' => $validated['status'] ?? 'available',
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Availability slot berhasil diupdate.',
            'data' => $this->formatSlotResponse($instructorAvailabilitySlot->fresh()->load('instructor')),
        ]);
    }

    public function destroy(InstructorAvailabilitySlot $instructorAvailabilitySlot): JsonResponse
    {
        if ($instructorAvailabilitySlot->status === 'booked') {
            return response()->json([
                'success' => false,
                'message' => 'Slot yang sudah booked tidak bisa dihapus.',
            ], 422);
        }

        $instructorAvailabilitySlot->delete();

        return response()->json([
            'success' => true,
            'message' => 'Availability slot berhasil dihapus.',
        ]);
    }

    private function validateRequest(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'instructor_id' => [
                'required',
                'integer',
                Rule::exists('instructors', 'id'),
            ],
            'date' => [
                'required',
                'date',
            ],
            'start_time' => [
                'required',
                'date_format:H:i',
            ],
            'end_time' => [
                'required',
                'date_format:H:i',
                'after:start_time',
            ],
            'status' => [
                'required',
                Rule::in([
                    'available',
                    'booked',
                    'blocked',
                ]),
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);
    }

    private function ensureNoOverlappingSlot(
        int $instructorId,
        string $date,
        string $startTime,
        string $endTime,
        ?int $ignoreId = null
    ): void {
        $exists = InstructorAvailabilitySlot::query()
            ->where('instructor_id', $instructorId)
            ->whereDate('date', $date)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where(function ($query) use ($startTime, $endTime) {
                $query
                    ->where(function ($overlapQuery) use ($startTime, $endTime) {
                        $overlapQuery
                            ->where('start_time', '<', $endTime)
                            ->where('end_time', '>', $startTime);
                    });
            })
            ->exists();

        if ($exists) {
            abort(response()->json([
                'success' => false,
                'message' => 'Instructor sudah punya slot di jam tersebut.',
                'errors' => [
                    'start_time' => ['Instructor sudah punya slot di jam tersebut.'],
                    'end_time' => ['Instructor sudah punya slot di jam tersebut.'],
                ],
            ], 422));
        }
    }

    private function formatSlotResponse(InstructorAvailabilitySlot $slot): array
    {
        return [
            'id' => $slot->id,
            'instructor_id' => $slot->instructor_id,
            'instructor_name' => $slot->instructor?->name,
            'instructor_email' => $slot->instructor?->email,
            'instructor_specialization' => $slot->instructor?->specialization,
            'date' => $slot->date?->format('Y-m-d'),
            'date_label' => $slot->date?->format('d M Y'),
            'start_time' => $slot->start_time ? substr($slot->start_time, 0, 5) : null,
            'end_time' => $slot->end_time ? substr($slot->end_time, 0, 5) : null,
            'time_label' => $slot->start_time && $slot->end_time
                ? substr($slot->start_time, 0, 5) . ' - ' . substr($slot->end_time, 0, 5)
                : '-',
            'status' => $slot->status,
            'status_label' => str($slot->status)->replace('_', ' ')->title()->toString(),
            'is_active' => (bool) $slot->is_active,
        ];
    }
}