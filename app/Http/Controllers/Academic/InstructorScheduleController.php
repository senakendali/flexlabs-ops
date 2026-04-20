<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Instructor;
use App\Models\InstructorSchedule;
use App\Models\Program;
use App\Models\SubTopic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InstructorScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->input('per_page', 10);

        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $search = trim((string) $request->input('search'));
        $instructorId = $request->input('instructor_id');
        $replacementInstructorId = $request->input('replacement_instructor_id');
        $batchId = $request->input('batch_id');
        $programId = $request->input('program_id');
        $status = $request->input('status');
        $deliveryMode = $request->input('delivery_mode');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $isMakeupSession = $request->input('is_makeup_session');

        $schedules = InstructorSchedule::query()
            ->with([
                'instructor:id,name',
                'replacementInstructor:id,name',
                'batch:id,name',
                'program:id,name',
                'subTopic:id,name',
                'rescheduledFrom:id,session_title,schedule_date,start_time,end_time',
            ])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('session_title', 'like', '%' . $search . '%')
                        ->orWhere('location', 'like', '%' . $search . '%')
                        ->orWhere('meeting_link', 'like', '%' . $search . '%')
                        ->orWhere('notes', 'like', '%' . $search . '%');
                });
            })
            ->when($instructorId, fn ($query) => $query->where('instructor_id', $instructorId))
            ->when($replacementInstructorId, fn ($query) => $query->where('replacement_instructor_id', $replacementInstructorId))
            ->when($batchId, fn ($query) => $query->where('batch_id', $batchId))
            ->when($programId, fn ($query) => $query->where('program_id', $programId))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($deliveryMode, fn ($query) => $query->where('delivery_mode', $deliveryMode))
            ->when($dateFrom, fn ($query) => $query->whereDate('schedule_date', '>=', $dateFrom))
            ->when($dateTo, fn ($query) => $query->whereDate('schedule_date', '<=', $dateTo))
            ->when($isMakeupSession !== null && $isMakeupSession !== '', function ($query) use ($isMakeupSession) {
                $query->where('is_makeup_session', (bool) $isMakeupSession);
            })
            ->orderBy('schedule_date')
            ->orderBy('start_time')
            ->paginate($perPage)
            ->withQueryString();

        return view('academic.instructor-schedules.index', [
            'schedules' => $schedules,
            'instructors' => Instructor::query()->orderBy('name')->get(['id', 'name']),
            'batches' => Batch::query()->orderBy('name')->get(['id', 'name']),
            'programs' => Program::query()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'search' => $search,
                'instructor_id' => $instructorId,
                'replacement_instructor_id' => $replacementInstructorId,
                'batch_id' => $batchId,
                'program_id' => $programId,
                'status' => $status,
                'delivery_mode' => $deliveryMode,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'is_makeup_session' => $isMakeupSession,
                'per_page' => $perPage,
            ],
            'statusOptions' => $this->statusOptions(),
            'deliveryModeOptions' => $this->deliveryModeOptions(),
        ]);
    }

    public function create(): View
    {
        return view('academic.instructor-schedules.form', [
            'schedule' => new InstructorSchedule([
                'status' => 'scheduled',
                'delivery_mode' => 'online',
                'is_makeup_session' => false,
            ]),
            'instructors' => Instructor::query()->orderBy('name')->get(['id', 'name']),
            'replacementInstructors' => Instructor::query()->orderBy('name')->get(['id', 'name']),
            'batches' => Batch::query()->orderBy('name')->get(['id', 'name', 'program_id']),
            'programs' => Program::query()->orderBy('name')->get(['id', 'name']),
            'subTopics' => SubTopic::query()->orderBy('name')->get(['id', 'name']),
            'reschedulableSchedules' => $this->getReschedulableSchedules(),
            'statusOptions' => $this->statusOptions(),
            'deliveryModeOptions' => $this->deliveryModeOptions(),
            'submitMode' => 'create',
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $this->validateRequest($request);

        $this->ensureNoConflict($validated);

        $schedule = new InstructorSchedule();
        $schedule->fill($this->buildPayload($validated));
        $schedule->save();

        return $this->successResponse(
            $request,
            'Jadwal instruktur berhasil dibuat.',
            route('instructor-schedules.edit', $schedule),
            $schedule
        );
    }

    public function show(InstructorSchedule $instructorSchedule): View
    {
        $instructorSchedule->load([
            'instructor',
            'replacementInstructor',
            'batch',
            'program',
            'subTopic',
            'rescheduledFrom.instructor',
            'rescheduledFrom.batch',
        ]);

        return view('academic.instructor-schedules.show', [
            'schedule' => $instructorSchedule,
        ]);
    }

    public function edit(InstructorSchedule $instructorSchedule): View
    {
        $instructorSchedule->load([
            'instructor',
            'replacementInstructor',
            'batch',
            'program',
            'subTopic',
            'rescheduledFrom',
        ]);

        return view('academic.instructor-schedules.form', [
            'schedule' => $instructorSchedule,
            'instructors' => Instructor::query()->orderBy('name')->get(['id', 'name']),
            'replacementInstructors' => Instructor::query()->orderBy('name')->get(['id', 'name']),
            'batches' => Batch::query()->orderBy('name')->get(['id', 'name', 'program_id']),
            'programs' => Program::query()->orderBy('name')->get(['id', 'name']),
            'subTopics' => SubTopic::query()->orderBy('name')->get(['id', 'name']),
            'reschedulableSchedules' => $this->getReschedulableSchedules($instructorSchedule->id),
            'statusOptions' => $this->statusOptions(),
            'deliveryModeOptions' => $this->deliveryModeOptions(),
            'submitMode' => 'edit',
        ]);
    }

    public function update(Request $request, InstructorSchedule $instructorSchedule): JsonResponse|RedirectResponse
    {
        $validated = $this->validateRequest($request, $instructorSchedule->id);

        $this->ensureNoConflict($validated, $instructorSchedule->id);

        $instructorSchedule->update($this->buildPayload($validated));

        return $this->successResponse(
            $request,
            'Jadwal instruktur berhasil diperbarui.',
            route('instructor-schedules.edit', $instructorSchedule),
            $instructorSchedule
        );
    }

   public function destroy(Request $request, InstructorSchedule $instructorSchedule): JsonResponse|RedirectResponse
    {
        $instructorSchedule->delete();

        return $this->successResponse(
            $request,
            'Jadwal instruktur berhasil dihapus.',
            route('instructor-schedules.index')
        );
    }

    protected function validateRequest(Request $request, ?int $ignoreId = null): array
    {
        $normalizedStartTime = $this->normalizeTimeInput($request->input('start_time'));
        $normalizedEndTime = $this->normalizeTimeInput($request->input('end_time'));

        $request->merge([
            'start_time' => $normalizedStartTime ?? $request->input('start_time'),
            'end_time' => $normalizedEndTime ?? $request->input('end_time'),
        ]);

        $validated = $request->validate([
            'instructor_id' => ['required', 'exists:instructors,id'],
            'replacement_instructor_id' => [
                'nullable',
                'exists:instructors,id',
                'different:instructor_id',
            ],
            'batch_id' => ['required', 'exists:batches,id'],
            'program_id' => ['nullable', 'exists:programs,id'],
            'sub_topic_id' => ['nullable', 'exists:sub_topics,id'],
            'rescheduled_from_id' => ['nullable', 'exists:instructor_schedules,id'],

            'session_title' => ['required', 'string', 'max:255'],
            'schedule_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],

            'delivery_mode' => ['required', Rule::in(array_keys($this->deliveryModeOptions()))],
            'meeting_link' => ['nullable', 'url', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],

            'is_makeup_session' => ['nullable', 'boolean'],

            'status' => ['required', Rule::in(array_keys($this->statusOptions()))],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['is_makeup_session'] = $request->boolean('is_makeup_session');
        $validated['start_time'] = $this->normalizeTimeInput($validated['start_time']) ?? $validated['start_time'];
        $validated['end_time'] = $this->normalizeTimeInput($validated['end_time']) ?? $validated['end_time'];

        if ($ignoreId && !empty($validated['rescheduled_from_id']) && (int) $validated['rescheduled_from_id'] === $ignoreId) {
            abort(response()->json([
                'message' => 'Validasi gagal.',
                'errors' => [
                    'rescheduled_from_id' => ['Sesi asal reschedule tidak boleh memilih jadwal yang sama.'],
                ],
            ], 422));
        }

        return $validated;
    }

    protected function normalizeTimeInput(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = trim((string) $value);

        if (preg_match('/^\d{2}:\d{2}$/', $value)) {
            return $value;
        }

        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $value)) {
            return substr($value, 0, 5);
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('H:i');
        } catch (\Throwable $th) {
            return null;
        }
    }

    protected function buildPayload(array $validated): array
    {
        $batch = Batch::query()->find($validated['batch_id']);

        return [
            'instructor_id' => $validated['instructor_id'],
            'replacement_instructor_id' => $validated['replacement_instructor_id'] ?? null,
            'batch_id' => $validated['batch_id'],
            'program_id' => $validated['program_id'] ?? $batch?->program_id,
            'sub_topic_id' => $validated['sub_topic_id'] ?? null,
            'rescheduled_from_id' => $validated['rescheduled_from_id'] ?? null,
            'session_title' => $validated['session_title'],
            'schedule_date' => $validated['schedule_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'delivery_mode' => $validated['delivery_mode'],
            'meeting_link' => $validated['meeting_link'] ?? null,
            'location' => $validated['location'] ?? null,
            'is_makeup_session' => (bool) ($validated['is_makeup_session'] ?? false),
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ];
    }

    protected function ensureNoConflict(array $validated, ?int $ignoreId = null): void
    {
        $this->ensureTeachingInstructorConflictFree($validated, $ignoreId);
        $this->ensureBatchConflictFree($validated, $ignoreId);
    }

    /**
     * Cek bentrok untuk instruktur yang benar-benar mengajar.
     * Kalau ada replacement instructor, yang dicek replacement-nya.
     * Kalau tidak ada, yang dicek instructor utama.
     */
    protected function ensureTeachingInstructorConflictFree(array $validated, ?int $ignoreId = null): void
    {
        $teachingInstructorId = !empty($validated['replacement_instructor_id'])
            ? (int) $validated['replacement_instructor_id']
            : (int) $validated['instructor_id'];

        $query = InstructorSchedule::query()
            ->whereDate('schedule_date', $validated['schedule_date'])
            ->whereNotIn('status', ['cancelled'])
            ->where(function ($mainQuery) use ($teachingInstructorId) {
                $mainQuery
                    ->where('instructor_id', $teachingInstructorId)
                    ->orWhere('replacement_instructor_id', $teachingInstructorId);
            })
            ->where(function ($subQuery) use ($validated) {
                $subQuery
                    ->where('start_time', '<', $validated['end_time'])
                    ->where('end_time', '>', $validated['start_time']);
            });

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            $field = !empty($validated['replacement_instructor_id'])
                ? 'replacement_instructor_id'
                : 'instructor_id';

            $message = !empty($validated['replacement_instructor_id'])
                ? 'Instruktur pengganti sudah memiliki jadwal lain pada waktu tersebut.'
                : 'Instruktur sudah memiliki jadwal lain pada waktu tersebut.';

            abort(response()->json([
                'message' => 'Validasi gagal.',
                'errors' => [
                    $field => [$message],
                ],
            ], 422));
        }
    }

    protected function ensureBatchConflictFree(array $validated, ?int $ignoreId = null): void
    {
        $query = InstructorSchedule::query()
            ->where('batch_id', $validated['batch_id'])
            ->whereDate('schedule_date', $validated['schedule_date'])
            ->whereNotIn('status', ['cancelled'])
            ->where(function ($subQuery) use ($validated) {
                $subQuery
                    ->where('start_time', '<', $validated['end_time'])
                    ->where('end_time', '>', $validated['start_time']);
            });

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            abort(response()->json([
                'message' => 'Validasi gagal.',
                'errors' => [
                    'batch_id' => ['Batch sudah memiliki jadwal lain pada waktu tersebut.'],
                ],
            ], 422));
        }
    }

    protected function getReschedulableSchedules(?int $ignoreId = null)
    {
        return InstructorSchedule::query()
            ->with([
                'instructor:id,name',
                'replacementInstructor:id,name',
                'batch:id,name',
            ])
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->orderByDesc('schedule_date')
            ->orderByDesc('start_time')
            ->get([
                'id',
                'session_title',
                'schedule_date',
                'start_time',
                'end_time',
                'instructor_id',
                'replacement_instructor_id',
                'batch_id',
            ]);
    }

    protected function successResponse(
        Request $request,
        string $message,
        string $redirect,
        ?InstructorSchedule $schedule = null
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'redirect' => $redirect,
                'data' => $schedule ? [
                    'id' => $schedule->id,
                    'session_title' => $schedule->session_title,
                    'schedule_date' => $schedule->schedule_date,
                    'status' => $schedule->status,
                ] : null,
            ]);
        }

        return redirect($redirect)->with('success', $message);
    }

    protected function statusOptions(): array
    {
        return [
            'scheduled' => 'Scheduled',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'rescheduled' => 'Rescheduled',
        ];
    }

    protected function deliveryModeOptions(): array
    {
        return [
            'online' => 'Online',
            'offline' => 'Offline',
            'hybrid' => 'Hybrid',
        ];
    }
}