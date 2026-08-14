<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\AcademicSchedule;
use App\Models\Batch;
use App\Models\Instructor;
use App\Models\Program;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class AcademicScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'program_id' => ['nullable', 'integer', 'exists:programs,id'],
            'batch_id' => ['nullable', 'integer', 'exists:batches,id'],
            'schedule_type' => ['nullable', 'string', 'max:50'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $schedules = AcademicSchedule::query()
            ->with([
                'program:id,name',
                'batch:id,program_id,name',
                'instructor:id,name',
                'creator:id,name',
            ])
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%");
                });
            })
            ->when($filters['program_id'] ?? null, fn (Builder $query, int $id) =>
                $query->where('program_id', $id)
            )
            ->when($filters['batch_id'] ?? null, fn (Builder $query, int $id) =>
                $query->where('batch_id', $id)
            )
            ->when($filters['schedule_type'] ?? null, fn (Builder $query, string $type) =>
                $query->where('schedule_type', $type)
            )
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) =>
                $query->whereDate('schedule_date', '>=', $date)
            )
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) =>
                $query->whereDate('schedule_date', '<=', $date)
            )
            ->orderByDesc('schedule_date')
            ->orderByDesc('start_time')
            ->paginate(20)
            ->withQueryString();

        return view('academic.schedules.index', [
            'schedules' => $schedules,
            'programs' => $this->programOptions(),
            'batches' => $this->batchOptions(),
            'instructors' => $this->instructorOptions(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('academic.schedules.create', $this->formOptions());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateSchedule($request);

        try {
            $schedule = DB::transaction(function () use ($validated, $request) {
                return AcademicSchedule::create([
                    ...$validated,
                    'created_by' => $request->user()->id,
                ]);
            });

            $schedule->load(['program:id,name', 'batch:id,program_id,name', 'instructor:id,name']);

            return response()->json([
                'success' => true,
                'message' => 'Jadwal akademik berhasil ditambahkan.',
                'data' => $schedule,
                'redirect_url' => route('academic.schedules.index'),
            ], 201);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Jadwal akademik belum berhasil ditambahkan. Silakan coba kembali.',
            ], 500);
        }
    }

    public function show(AcademicSchedule $academicSchedule, Request $request): View|JsonResponse
    {
        $academicSchedule->load([
            'program:id,name',
            'batch:id,program_id,name',
            'instructor:id,name',
            'creator:id,name',
        ]);

        if ($request->expectsJson()) {
            $data = $academicSchedule->toArray();

            // Use the raw database values for date/time form fields. This
            // prevents Laravel's JSON timezone conversion from shifting the
            // schedule date one day backward in browsers using UTC+ offsets.
            $data['schedule_date'] = $academicSchedule->getRawOriginal('schedule_date');
            $data['start_time'] = $academicSchedule->getRawOriginal('start_time');
            $data['end_time'] = $academicSchedule->getRawOriginal('end_time');

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        }

        return view('academic.schedules.show', compact('academicSchedule'));
    }

    public function edit(AcademicSchedule $academicSchedule): View
    {
        return view('academic.schedules.edit', [
            ...$this->formOptions(),
            'academicSchedule' => $academicSchedule,
        ]);
    }

    public function update(
        Request $request,
        AcademicSchedule $academicSchedule
    ): JsonResponse {
        $validated = $this->validateSchedule($request);

        try {
            DB::transaction(function () use ($academicSchedule, $validated) {
                $academicSchedule->update($validated);
            });

            $academicSchedule->refresh()->load([
                'program:id,name',
                'batch:id,program_id,name',
                'instructor:id,name',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Jadwal akademik berhasil diperbarui.',
                'data' => $academicSchedule,
                'redirect_url' => route('academic.schedules.index'),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Jadwal akademik belum berhasil diperbarui. Silakan coba kembali.',
            ], 500);
        }
    }

    public function destroy(AcademicSchedule $academicSchedule): JsonResponse
    {
        try {
            DB::transaction(function () use ($academicSchedule) {
                $academicSchedule->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Jadwal akademik berhasil dihapus.',
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Jadwal akademik belum berhasil dihapus. Silakan coba kembali.',
            ], 500);
        }
    }

    private function validateSchedule(Request $request): array
    {
        $request->merge([
            'is_all_day' => $request->boolean('is_all_day'),
            'notes' => $request->filled('notes') ? $request->notes : null,
            'instructor_id' => $request->filled('instructor_id') ? $request->instructor_id : null,
        ]);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'batch_id' => [
                'required',
                'integer',
                Rule::exists('batches', 'id')->where(fn ($query) =>
                    $query->where('program_id', $request->integer('program_id'))
                ),
            ],
            'schedule_type' => [
                'required',
                Rule::in([
                    'kickoff',
                    'live_session',
                    'assignment_deadline',
                    'quiz_deadline',
                    'mentoring',
                    'replacement_class',
                    'assessment',
                    'final_presentation',
                    'holiday',
                    'other',
                ]),
            ],
            'schedule_date' => ['required', 'date'],
            'is_all_day' => ['required', 'boolean'],
            'start_time' => [
                Rule::requiredIf(!$request->boolean('is_all_day')),
                'nullable',
                'date_format:H:i',
            ],
            'end_time' => [
                Rule::requiredIf(!$request->boolean('is_all_day')),
                'nullable',
                'date_format:H:i',
                'after:start_time',
            ],
            'instructor_id' => ['nullable', 'integer', 'exists:instructors,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        // These legacy columns remain populated for database compatibility,
        // but are intentionally hidden from the simple schedule-marker UI.
        $validated['delivery_mode'] = 'online';
        $validated['meeting_link'] = null;
        $validated['location'] = null;
        $validated['status'] = 'scheduled';

        if ($validated['is_all_day']) {
            $validated['start_time'] = null;
            $validated['end_time'] = null;
        }

        return $validated;
    }

    private function formOptions(): array
    {
        return [
            'programs' => $this->programOptions(),
            'batches' => $this->batchOptions(),
            'instructors' => $this->instructorOptions(),
        ];
    }

    private function instructorOptions()
    {
        return Instructor::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();
    }

    private function programOptions()
    {
        return Program::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();
    }

    private function batchOptions()
    {
        return Batch::query()
            ->select(['id', 'program_id', 'name', 'start_date', 'end_date'])
            ->orderByDesc('start_date')
            ->orderBy('name')
            ->get();
    }
}