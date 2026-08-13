<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\AcademicSchedule;
use App\Models\Batch;
use App\Models\Program;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcademicCalendarController extends Controller
{
    public function index(): View
    {
        $today = today();

        $programs = Program::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        $runningBatches = Batch::query()
            ->with('program:id,name')
            ->select(['id', 'program_id', 'name', 'start_date', 'end_date'])
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->orderBy('start_date')
            ->orderBy('name')
            ->get();

        return view('academic.calendar.index', compact('programs', 'runningBatches'));
    }

    public function events(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after_or_equal:start'],
            'program_id' => ['nullable', 'integer', 'exists:programs,id'],
            'batch_id' => ['nullable', 'integer', 'exists:batches,id'],
            'schedule_type' => ['nullable', 'string', 'max:50'],
        ]);

        $startDate = Carbon::parse($validated['start'])->toDateString();
        // FullCalendar sends an exclusive end date for the requested range.
        $endDate = Carbon::parse($validated['end'])->subDay()->toDateString();

        $schedules = AcademicSchedule::query()
            ->with([
                'program:id,name',
                'batch:id,program_id,name',
                'instructor:id,name',
            ])
            ->whereBetween('schedule_date', [$startDate, $endDate])
            ->when($validated['program_id'] ?? null, fn ($query, $programId) =>
                $query->where('program_id', $programId)
            )
            ->when($validated['batch_id'] ?? null, fn ($query, $batchId) =>
                $query->where('batch_id', $batchId)
            )
            ->when($validated['schedule_type'] ?? null, fn ($query, $type) =>
                $query->where('schedule_type', $type)
            )
            ->orderBy('schedule_date')
            ->orderByRaw('CASE WHEN is_all_day = 1 THEN 0 ELSE 1 END')
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $schedules->map(fn (AcademicSchedule $schedule) =>
                $this->toCalendarEvent($schedule)
            )->values(),
        ]);
    }

    private function toCalendarEvent(AcademicSchedule $schedule): array
    {
        $isAllDay = (bool) $schedule->is_all_day;
        $date = $schedule->schedule_date->format('Y-m-d');
        $startTime = $schedule->start_time
            ? Carbon::parse($schedule->start_time)->format('H:i:s')
            : null;
        $endTime = $schedule->end_time
            ? Carbon::parse($schedule->end_time)->format('H:i:s')
            : null;

        return [
            'id' => $schedule->id,
            'title' => $schedule->title,
            'start' => $isAllDay || !$startTime ? $date : "{$date}T{$startTime}",
            'end' => $isAllDay || !$endTime ? null : "{$date}T{$endTime}",
            'allDay' => $isAllDay,
            'backgroundColor' => $this->batchColor($schedule->batch_id),
            'borderColor' => $this->batchColor($schedule->batch_id),
            'textColor' => '#FFFFFF',
            'extendedProps' => [
                'program_id' => $schedule->program_id,
                'program_name' => $schedule->program?->name,
                'batch_id' => $schedule->batch_id,
                'batch_name' => $schedule->batch?->name,
                'schedule_type' => $schedule->schedule_type,
                'instructor_id' => $schedule->instructor_id,
                'instructor_name' => $schedule->instructor?->name,
                'notes' => $schedule->notes,
            ],
        ];
    }

    private function batchColor(int $batchId): string
    {
        $palette = [
            '#5B3E8E',
            '#2563EB',
            '#0F766E',
            '#C2410C',
            '#7C3AED',
            '#BE123C',
            '#0369A1',
            '#3F6212',
        ];

        return $palette[$batchId % count($palette)];
    }
}