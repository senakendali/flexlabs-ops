<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\AcademicSchedule;
use App\Models\Batch;
use App\Models\Program;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AcademicCalendarController extends Controller
{
    /**
     * Curated palette.
     *
     * Urutan warna dibuat supaya batch yang berdekatan
     * mempunyai warna yang cukup berbeda secara visual.
     */
    private const BATCH_PALETTE = [
        '#5B3E8E', // Purple
        '#2563EB', // Blue
        '#059669', // Emerald
        '#EA580C', // Orange
        '#DC2626', // Red
        '#0891B2', // Cyan
        '#CA8A04', // Gold
        '#DB2777', // Pink
        '#4F46E5', // Indigo
        '#65A30D', // Lime
        '#9333EA', // Violet
        '#0F766E', // Teal
        '#C2410C', // Burnt Orange
        '#0369A1', // Sky
        '#BE123C', // Rose
        '#3F6212', // Olive
        '#7C2D12', // Brown
        '#4338CA', // Deep Indigo
        '#047857', // Deep Emerald
        '#A21CAF', // Fuchsia
        '#B45309', // Amber
        '#0E7490', // Deep Cyan
        '#6D28D9', // Deep Violet
        '#15803D', // Green
    ];

    public function index(): View
    {
        $today = today();

        $programs = Program::query()
            ->select([
                'id',
                'name',
            ])
            ->orderBy('name')
            ->get();

        $runningBatches = $this->getRunningBatches($today);

        /*
        |--------------------------------------------------------------------------
        | Batch Color Map
        |--------------------------------------------------------------------------
        |
        | Mapping berdasarkan URUTAN running batch, bukan batch ID.
        |
        | Contoh:
        |
        | running batch pertama  -> palette[0]
        | running batch kedua    -> palette[1]
        | running batch ketiga   -> palette[2]
        |
        */

        $batchColorMap = $this->buildBatchColorMap(
            $runningBatches
        );

        return view(
            'academic.calendar.index',
            compact(
                'programs',
                'runningBatches',
                'batchColorMap'
            )
        );
    }

    public function events(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start' => [
                'required',
                'date',
            ],

            'end' => [
                'required',
                'date',
                'after_or_equal:start',
            ],

            'program_id' => [
                'nullable',
                'integer',
                'exists:programs,id',
            ],

            'batch_id' => [
                'nullable',
                'integer',
                'exists:batches,id',
            ],

            'schedule_type' => [
                'nullable',
                'string',
                'max:50',
            ],
        ]);

        $startDate = Carbon::parse(
            $validated['start']
        )->toDateString();

        /*
        |--------------------------------------------------------------------------
        | FullCalendar End Date
        |--------------------------------------------------------------------------
        |
        | FullCalendar mengirim end date secara exclusive.
        |
        */

        $endDate = Carbon::parse(
            $validated['end']
        )
            ->subDay()
            ->toDateString();

        /*
        |--------------------------------------------------------------------------
        | Running Batch Color Map
        |--------------------------------------------------------------------------
        |
        | Events memakai mapping warna yang sama dengan halaman index.
        |
        */

        $runningBatches = $this->getRunningBatches(
            today()
        );

        $batchColorMap = $this->buildBatchColorMap(
            $runningBatches
        );

        /*
        |--------------------------------------------------------------------------
        | Schedules
        |--------------------------------------------------------------------------
        */

        $schedules = AcademicSchedule::query()
            ->with([
                'program:id,name',
                'batch:id,program_id,name',
                'instructor:id,name',
            ])
            ->whereBetween(
                'schedule_date',
                [
                    $startDate,
                    $endDate,
                ]
            )
            ->when(
                $validated['program_id'] ?? null,
                fn ($query, $programId) =>
                    $query->where(
                        'program_id',
                        $programId
                    )
            )
            ->when(
                $validated['batch_id'] ?? null,
                fn ($query, $batchId) =>
                    $query->where(
                        'batch_id',
                        $batchId
                    )
            )
            ->when(
                $validated['schedule_type'] ?? null,
                fn ($query, $type) =>
                    $query->where(
                        'schedule_type',
                        $type
                    )
            )
            ->orderBy('schedule_date')
            ->orderByRaw(
                'CASE WHEN is_all_day = 1 THEN 0 ELSE 1 END'
            )
            ->orderBy('start_time')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Calendar Events
        |--------------------------------------------------------------------------
        */

        $events = $schedules
            ->map(
                fn (AcademicSchedule $schedule) =>
                    $this->toCalendarEvent(
                        $schedule,
                        $batchColorMap
                    )
            )
            ->values();

        return response()->json([
            'success' => true,
            'data' => $events,
        ]);
    }

    /**
     * Get running batches using exactly the same rule
     * for both page rendering and event colors.
     */
    private function getRunningBatches(
        Carbon $today
    ): Collection {
        return Batch::query()
            ->with('program:id,name')
            ->select([
                'id',
                'program_id',
                'name',
                'status',
                'start_date',
                'end_date',
            ])
            ->whereIn(
                'status',
                [
                    'ongoing',
                    'on_going',
                    'on going',
                ]
            )
            ->where(
                function ($query) use ($today) {
                    $query
                        ->whereNull('end_date')
                        ->orWhereDate(
                            'end_date',
                            '>=',
                            $today
                        );
                }
            )
            ->orderBy('start_date')
            ->orderBy('name')
            ->get();
    }

    /**
     * Build consistent batch -> color mapping.
     */
    private function buildBatchColorMap(
        Collection $runningBatches
    ): array {
        $palette = self::BATCH_PALETTE;

        $colorMap = [];

        foreach (
            $runningBatches->values()
            as $index => $batch
        ) {
            $colorMap[$batch->id] =
                $palette[
                    $index % count($palette)
                ];
        }

        return $colorMap;
    }

    /**
     * Convert schedule into FullCalendar event.
     */
    private function toCalendarEvent(
        AcademicSchedule $schedule,
        array $batchColorMap
    ): array {
        $isAllDay =
            (bool) $schedule->is_all_day;

        $date =
            $schedule
                ->schedule_date
                ->format('Y-m-d');

        $startTime =
            $schedule->start_time
                ? Carbon::parse(
                    $schedule->start_time
                )->format('H:i:s')
                : null;

        $endTime =
            $schedule->end_time
                ? Carbon::parse(
                    $schedule->end_time
                )->format('H:i:s')
                : null;

        /*
        |--------------------------------------------------------------------------
        | Event Color
        |--------------------------------------------------------------------------
        |
        | Kalau batch masih termasuk running batch,
        | ambil warna dari batchColorMap.
        |
        | Fallback digunakan untuk schedule lama / batch non-running.
        |
        */

        $color =
            $batchColorMap[$schedule->batch_id]
            ?? '#6B7280';

        return [
            'id' =>
                $schedule->id,

            'title' =>
                $schedule->title,

            'start' =>
                $isAllDay || !$startTime
                    ? $date
                    : "{$date}T{$startTime}",

            'end' =>
                $isAllDay || !$endTime
                    ? null
                    : "{$date}T{$endTime}",

            'allDay' =>
                $isAllDay,

            'backgroundColor' =>
                $color,

            'borderColor' =>
                $color,

            'textColor' =>
                '#FFFFFF',

            'extendedProps' => [

                'program_id' =>
                    $schedule->program_id,

                'program_name' =>
                    $schedule->program?->name,

                'batch_id' =>
                    $schedule->batch_id,

                'batch_name' =>
                    $schedule->batch?->name,

                'schedule_type' =>
                    $schedule->schedule_type,

                'instructor_id' =>
                    $schedule->instructor_id,

                'instructor_name' =>
                    $schedule->instructor?->name,

                'notes' =>
                    $schedule->notes,
            ],
        ];
    }
}