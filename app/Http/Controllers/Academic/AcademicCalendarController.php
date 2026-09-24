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
     * Palette warna batch.
     *
     * Dibuat cukup kontras secara visual supaya batch yang berbeda
     * mudah dibedakan pada calendar.
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

    /**
     * Academic Calendar page.
     */
    public function index(): View
    {
        $today = today();

        /*
        |--------------------------------------------------------------------------
        | Programs
        |--------------------------------------------------------------------------
        */

        $programs = Program::query()
            ->select([
                'id',
                'name',
            ])
            ->orderBy('name')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Running Batches
        |--------------------------------------------------------------------------
        |
        | Ini hanya digunakan untuk:
        |
        | - batch tabs
        | - running batch legend
        |
        | TIDAK digunakan untuk menentukan apakah suatu batch boleh
        | memiliki warna atau tidak.
        |
        */

        $runningBatches = $this->getRunningBatches(
            $today
        );

        /*
        |--------------------------------------------------------------------------
        | Global Batch Color Map
        |--------------------------------------------------------------------------
        |
        | Warna dibuat berdasarkan SELURUH batch.
        |
        | Jadi batch upcoming yang sudah memiliki schedule tetap mendapatkan
        | warna walaupun statusnya belum ongoing.
        |
        */

        $batchColorMap = $this->getBatchColorMap();

        return view(
            'academic.calendar.index',
            compact(
                'programs',
                'runningBatches',
                'batchColorMap'
            )
        );
    }

    /**
     * FullCalendar events endpoint.
     */
    public function events(Request $request): JsonResponse
    {
        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | Calendar Range
        |--------------------------------------------------------------------------
        */

        $startDate = Carbon::parse(
            $validated['start']
        )->toDateString();

        /*
         * FullCalendar mengirim end date secara exclusive.
         *
         * Contoh:
         *
         * start = 2026-09-01
         * end   = 2026-10-01
         *
         * Maka tanggal terakhir yang benar adalah 2026-09-30.
         */

        $endDate = Carbon::parse(
            $validated['end']
        )
            ->subDay()
            ->toDateString();

        /*
        |--------------------------------------------------------------------------
        | Global Batch Color Map
        |--------------------------------------------------------------------------
        |
        | Penting:
        |
        | Jangan mengambil warna hanya dari running batch.
        |
        | Upcoming batch yang memiliki academic schedule juga harus
        | mempunyai warna unik.
        |
        */

        $batchColorMap = $this->getBatchColorMap();

        /*
        |--------------------------------------------------------------------------
        | Academic Schedules
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
        | Transform To FullCalendar Events
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
     * Get batches currently considered running.
     *
     * Digunakan hanya untuk tab/filter running batch.
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

            /*
            |--------------------------------------------------------------------------
            | Current + Upcoming Batches
            |--------------------------------------------------------------------------
            |
            | Hanya batch yang masih berjalan atau akan berjalan.
            | Batch yang end_date-nya sudah lewat tidak ditampilkan.
            |
            */

            ->where(function ($query) use ($today) {
                $query
                    ->whereNull('end_date')
                    ->orWhereDate(
                        'end_date',
                        '>=',
                        $today
                    );
            })

            /*
            |--------------------------------------------------------------------------
            | Exclude Finished / Cancelled
            |--------------------------------------------------------------------------
            */

            ->whereNotIn('status', [
                'completed',
                'cancelled',
                'canceled',
            ])

            /*
            |--------------------------------------------------------------------------
            | Must Have Academic Schedule
            |--------------------------------------------------------------------------
            |
            | Batch hanya ditampilkan di tab jika minimal sudah memiliki
            | satu AcademicSchedule.
            |
            */

            ->whereExists(function ($query) {
                $query
                    ->selectRaw('1')
                    ->from('academic_schedules')
                    ->whereColumn(
                        'academic_schedules.batch_id',
                        'batches.id'
                    );
            })

            /*
            |--------------------------------------------------------------------------
            | Order
            |--------------------------------------------------------------------------
            */

            ->orderBy('start_date')
            ->orderBy('name')
            ->get();
    }

    private function getRunningBatches_(
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
     * Build global batch color mapping.
     *
     * Warna ditentukan dari SEMUA batch, bukan hanya running batch.
     *
     * Contoh:
     *
     * [
     *     10 => '#5B3E8E',
     *     11 => '#2563EB',
     *     12 => '#059669',
     * ]
     *
     * Dengan begitu batch upcoming tetap mendapatkan warna.
     */
    private function getBatchColorMap(): array
    {
        /*
        |--------------------------------------------------------------------------
        | Ambil Semua Batch ID
        |--------------------------------------------------------------------------
        |
        | Gunakan urutan ID supaya mapping stabil.
        |
        | Batch yang sudah ada tidak akan berubah posisi hanya karena
        | statusnya berubah dari upcoming menjadi ongoing.
        |
        */

        $batchIds = Batch::query()
            ->orderBy('id')
            ->pluck('id')
            ->values();

        $palette = self::BATCH_PALETTE;

        $colorMap = [];

        foreach ($batchIds as $index => $batchId) {
            $colorMap[$batchId] =
                $palette[
                    $index % count($palette)
                ];
        }

        return $colorMap;
    }

    /**
     * Convert AcademicSchedule into FullCalendar event.
     */
    private function toCalendarEvent(
        AcademicSchedule $schedule,
        array $batchColorMap
    ): array {
        /*
        |--------------------------------------------------------------------------
        | All Day
        |--------------------------------------------------------------------------
        */

        $isAllDay =
            (bool) $schedule->is_all_day;

        /*
        |--------------------------------------------------------------------------
        | Schedule Date
        |--------------------------------------------------------------------------
        */

        $date = $schedule
            ->schedule_date
            ->format('Y-m-d');

        /*
        |--------------------------------------------------------------------------
        | Start Time
        |--------------------------------------------------------------------------
        */

        $startTime =
            $schedule->start_time
                ? Carbon::parse(
                    $schedule->start_time
                )->format('H:i:s')
                : null;

        /*
        |--------------------------------------------------------------------------
        | End Time
        |--------------------------------------------------------------------------
        */

        $endTime =
            $schedule->end_time
                ? Carbon::parse(
                    $schedule->end_time
                )->format('H:i:s')
                : null;

        /*
        |--------------------------------------------------------------------------
        | Batch Color
        |--------------------------------------------------------------------------
        |
        | Karena batchColorMap berasal dari seluruh batch,
        | upcoming batch juga akan memiliki warna.
        |
        | Grey hanya menjadi fallback untuk schedule tanpa batch.
        |
        */

        $color =
            $schedule->batch_id
                ? (
                    $batchColorMap[$schedule->batch_id]
                    ?? '#6B7280'
                )
                : '#6B7280';

        /*
        |--------------------------------------------------------------------------
        | FullCalendar Event
        |--------------------------------------------------------------------------
        */

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

            /*
            |--------------------------------------------------------------------------
            | Color
            |--------------------------------------------------------------------------
            */

            'backgroundColor' =>
                $color,

            'borderColor' =>
                $color,

            'textColor' =>
                '#FFFFFF',

            /*
            |--------------------------------------------------------------------------
            | Extended Properties
            |--------------------------------------------------------------------------
            */

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