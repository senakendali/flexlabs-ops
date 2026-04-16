<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\TrialParticipant;
use App\Models\TrialSchedule;
use App\Models\TrialTheme;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $academicStats = $this->getAcademicStats();
        $batchCapacity = $this->getBatchCapacitySummary();
        $revenueChart = $this->getMonthlyRevenueChart();
        $upcomingBatches = $this->getUpcomingBatches();
        $salesInsight = $this->getSalesInsight();

        $trialStats = $this->getTrialStats();
        $upcomingTrialSchedules = $this->getUpcomingTrialSchedules();
        $trialParticipantStatusCounts = $this->getTrialParticipantStatusCounts();
        $trialFollowUpProgress = $this->getTrialFollowUpProgress();

        return view('dashboard', [
            'academicStats' => $academicStats,
            'batchCapacity' => $batchCapacity,
            'revenueChart' => $revenueChart,
            'upcomingBatches' => $upcomingBatches,

            'trialStats' => $trialStats,
            'upcomingTrialSchedules' => $upcomingTrialSchedules,
            'trialParticipantStatusCounts' => $trialParticipantStatusCounts,
            'trialFollowUpProgress' => $trialFollowUpProgress,
            'salesInsight' => $salesInsight,
        ]);
    }

    protected function getAcademicStats(): array
    {
        $programs = $this->safeCount('programs');

        $batchesTable = $this->findExistingTable(['batches']);
        $batchActiveColumn = $this->findExistingColumn($batchesTable, ['is_active', 'status']);

        $activeBatches = 0;

        if ($batchesTable) {
            $query = DB::table($batchesTable);

            if ($batchActiveColumn === 'is_active') {
                $query->where('is_active', 1);
            } elseif ($batchActiveColumn === 'status') {
                $query->whereIn('status', ['active', 'open', 'running']);
            }

            $activeBatches = (int) $query->count();
        }

        $filledSeats = $this->getFilledSeatCount();

        $upcomingBatches = 0;
        if ($batchesTable) {
            $startDateColumn = $this->findExistingColumn($batchesTable, ['start_date', 'start_at', 'batch_start_date']);

            if ($startDateColumn) {
                $upcomingBatches = (int) DB::table($batchesTable)
                    ->whereDate($startDateColumn, '>=', now()->toDateString())
                    ->count();
            }
        }

        return [
            'programs' => $programs,
            'active_batches' => $activeBatches,
            'filled_seats' => $filledSeats,
            'upcoming_batches' => $upcomingBatches,
        ];
    }

    protected function getBatchCapacitySummary(): array
    {
        $batchesTable = $this->findExistingTable(['batches']);
        if (! $batchesTable) {
            return [
                'total_capacity' => 0,
                'filled_seats' => 0,
                'remaining_seats' => 0,
                'utilization_percent' => 0,
            ];
        }

        $capacityColumn = $this->findExistingColumn($batchesTable, ['capacity', 'quota', 'max_seats', 'seat_capacity']);
        $activeColumn = $this->findExistingColumn($batchesTable, ['is_active', 'status']);

        $batchQuery = DB::table($batchesTable);

        if ($activeColumn === 'is_active') {
            $batchQuery->where('is_active', 1);
        } elseif ($activeColumn === 'status') {
            $batchQuery->whereIn('status', ['active', 'open', 'running']);
        }

        $totalCapacity = 0;
        if ($capacityColumn) {
            $totalCapacity = (int) $batchQuery->sum($capacityColumn);
        }

        $filledSeats = $this->getFilledSeatCount(true);

        $remainingSeats = max($totalCapacity - $filledSeats, 0);
        $utilizationPercent = $totalCapacity > 0
            ? (int) round(($filledSeats / $totalCapacity) * 100)
            : 0;

        return [
            'total_capacity' => $totalCapacity,
            'filled_seats' => $filledSeats,
            'remaining_seats' => $remainingSeats,
            'utilization_percent' => $utilizationPercent,
        ];
    }

    protected function getMonthlyRevenueChart(): array
    {
        $year = now()->year;

        $labels = [];
        $data = [];

        for ($month = 1; $month <= 12; $month++) {
            $labels[] = Carbon::create($year, $month, 1)->translatedFormat('M');
            $data[] = 0;
        }

        $paymentsTable = $this->findExistingTable(['payments']);
        if (! $paymentsTable) {
            return [
                'year' => $year,
                'labels' => $labels,
                'data' => $data,
                'total' => 0,
            ];
        }

        $amountColumn = $this->findExistingColumn($paymentsTable, ['amount', 'paid_amount', 'total_amount']);
        $dateColumn = $this->findExistingColumn($paymentsTable, ['paid_at', 'payment_date', 'created_at']);
        $statusColumn = $this->findExistingColumn($paymentsTable, ['status', 'payment_status']);

        if (! $amountColumn || ! $dateColumn) {
            return [
                'year' => $year,
                'labels' => $labels,
                'data' => $data,
                'total' => 0,
            ];
        }

        $query = DB::table($paymentsTable)
            ->selectRaw('MONTH(' . $dateColumn . ') as month_number, SUM(' . $amountColumn . ') as total_amount')
            ->whereYear($dateColumn, $year);

        if ($statusColumn === 'status') {
            $query->whereIn('status', ['paid', 'success', 'settled']);
        } elseif ($statusColumn === 'payment_status') {
            $query->whereIn('payment_status', ['paid', 'success', 'settled']);
        }

        $rows = $query
            ->groupByRaw('MONTH(' . $dateColumn . ')')
            ->orderByRaw('MONTH(' . $dateColumn . ')')
            ->get();

        foreach ($rows as $row) {
            $index = ((int) $row->month_number) - 1;
            if ($index >= 0 && $index < 12) {
                $data[$index] = (float) $row->total_amount;
            }
        }

        return [
            'year' => $year,
            'labels' => $labels,
            'data' => $data,
            'total' => array_sum($data),
        ];
    }

    protected function getSalesInsight(): array
    {
        // fallback kalau belum ada CRM integration
        $totalLeads = DB::table('trial_participants')->count();

        $trialParticipants = DB::table('trial_participants')->count();

        // asumsi peserta yang join program
        $joins = $this->getFilledSeatCount();

        // asumsi paid dari payments
        $paymentsTable = $this->findExistingTable(['payments']);
        $paid = 0;

        if ($paymentsTable) {
            $statusColumn = $this->findExistingColumn($paymentsTable, ['status', 'payment_status']);
            $query = DB::table($paymentsTable);

            if ($statusColumn === 'status') {
                $query->whereIn('status', ['paid', 'success', 'settled']);
            } elseif ($statusColumn === 'payment_status') {
                $query->whereIn('payment_status', ['paid', 'success', 'settled']);
            }

            $paid = $query->count();
        }

        return [
            'leads' => $totalLeads,
            'trial' => $trialParticipants,
            'join' => $joins,
            'paid' => $paid,
            'conversion_trial' => $totalLeads > 0 ? round(($trialParticipants / $totalLeads) * 100) : 0,
            'conversion_join' => $trialParticipants > 0 ? round(($joins / $trialParticipants) * 100) : 0,
            'conversion_paid' => $joins > 0 ? round(($paid / $joins) * 100) : 0,
        ];
    }

   protected function getUpcomingBatches()
    {
        $batchesTable = $this->findExistingTable(['batches']);
        if (! $batchesTable) {
            return collect();
        }

        $nameColumn = $this->findExistingColumn($batchesTable, ['name', 'title']);
        $startDateColumn = $this->findExistingColumn($batchesTable, ['start_date', 'start_at', 'batch_start_date']);
        $capacityColumn = $this->findExistingColumn($batchesTable, ['capacity', 'quota', 'max_seats', 'seat_capacity']);
        $activeColumn = $this->findExistingColumn($batchesTable, ['is_active', 'status']);
        $programIdColumn = $this->findExistingColumn($batchesTable, ['program_id']);

        if (! $nameColumn || ! $startDateColumn) {
            return collect();
        }

        $query = DB::table($batchesTable)
            ->select([
                $batchesTable . '.id',
                DB::raw($batchesTable . '.' . $nameColumn . ' as name'),
                DB::raw($batchesTable . '.' . $startDateColumn . ' as start_date'),
                DB::raw(($capacityColumn ? $batchesTable . '.' . $capacityColumn : '0') . ' as capacity'),
            ])
            ->whereDate($batchesTable . '.' . $startDateColumn, '>=', now()->toDateString());

        if ($programIdColumn && Schema::hasTable('programs')) {
            $query->leftJoin('programs', 'programs.id', '=', $batchesTable . '.' . $programIdColumn);

            $programNameColumn = $this->findExistingColumn('programs', ['name', 'title']);
            if ($programNameColumn) {
                $query->addSelect(DB::raw('programs.' . $programNameColumn . ' as program_name'));
            }
        }

        if ($activeColumn === 'is_active') {
            $query->where($batchesTable . '.is_active', 1);
        } elseif ($activeColumn === 'status') {
            $query->whereIn($batchesTable . '.status', ['active', 'open', 'running']);
        }

        return $query
            ->orderBy($batchesTable . '.' . $startDateColumn)
            ->limit(5)
            ->get()
            ->map(function ($batch) {
                $batch->filled_seats = $this->getFilledSeatCountForBatch((int) $batch->id);
                $batch->remaining_seats = max(((int) $batch->capacity) - ((int) $batch->filled_seats), 0);
                return $batch;
            });
    }

    protected function getTrialStats(): array
    {
        return [
            'themes_total' => class_exists(TrialTheme::class) ? TrialTheme::count() : 0,
            'themes_active' => class_exists(TrialTheme::class)
                ? TrialTheme::query()
                    ->when(
                        $this->hasColumn((new TrialTheme())->getTable(), 'is_active'),
                        fn ($query) => $query->where('is_active', true)
                    )
                    ->count()
                : 0,

            'schedules_total' => class_exists(TrialSchedule::class) ? TrialSchedule::count() : 0,
            'schedules_active' => class_exists(TrialSchedule::class)
                ? TrialSchedule::query()
                    ->when(
                        $this->hasColumn((new TrialSchedule())->getTable(), 'is_active'),
                        fn ($query) => $query->where('is_active', true)
                    )
                    ->count()
                : 0,

            'participants_total' => class_exists(TrialParticipant::class) ? TrialParticipant::count() : 0,
            'participants_new_this_month' => class_exists(TrialParticipant::class)
                ? TrialParticipant::query()
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count()
                : 0,
        ];
    }

    protected function getUpcomingTrialSchedules()
    {
        if (! class_exists(TrialSchedule::class)) {
            return collect();
        }

        $today = Carbon::today();

        return TrialSchedule::query()
            ->with([
                'trialTheme:id,name',
                'program:id,name',
            ])
            ->when(
                $this->hasColumn((new TrialSchedule())->getTable(), 'is_active'),
                fn ($query) => $query->where('is_active', true)
            )
            ->whereDate('schedule_date', '>=', $today)
            ->orderBy('schedule_date')
            ->orderBy('start_time')
            ->take(5)
            ->get();
    }

    protected function getTrialParticipantStatusCounts()
    {
        if (! class_exists(TrialParticipant::class)) {
            return collect([
                'registered' => 0,
                'contacted' => 0,
                'confirmed' => 0,
                'attended' => 0,
                'cancelled' => 0,
                'no_show' => 0,
            ]);
        }

        return collect([
            'registered' => 0,
            'contacted' => 0,
            'confirmed' => 0,
            'attended' => 0,
            'cancelled' => 0,
            'no_show' => 0,
        ])->merge(
            TrialParticipant::query()
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
        );
    }

    protected function getTrialFollowUpProgress(): int
    {
        if (! class_exists(TrialParticipant::class)) {
            return 0;
        }

        $followUpStatuses = ['contacted', 'confirmed', 'attended'];
        $followedUpCount = TrialParticipant::query()
            ->whereIn('status', $followUpStatuses)
            ->count();

        $totalParticipants = max((int) TrialParticipant::count(), 1);

        return (int) round(($followedUpCount / $totalParticipants) * 100);
    }

    protected function getFilledSeatCount(bool $activeBatchOnly = false): int
    {
        $pivotTable = $this->findExistingTable([
            'batch_students',
            'student_batches',
            'enrollments',
            'batch_enrollments',
        ]);

        if (! $pivotTable) {
            return 0;
        }

        $batchIdColumn = $this->findExistingColumn($pivotTable, ['batch_id']);
        if (! $batchIdColumn) {
            return 0;
        }

        $query = DB::table($pivotTable);

        if ($activeBatchOnly) {
            $batchesTable = $this->findExistingTable(['batches']);
            $activeColumn = $this->findExistingColumn($batchesTable, ['is_active', 'status']);

            if ($batchesTable) {
                $query->join($batchesTable, $batchesTable . '.id', '=', $pivotTable . '.' . $batchIdColumn);

                if ($activeColumn === 'is_active') {
                    $query->where($batchesTable . '.is_active', 1);
                } elseif ($activeColumn === 'status') {
                    $query->whereIn($batchesTable . '.status', ['active', 'open', 'running']);
                }
            }
        }

        $studentColumn = $this->findExistingColumn($pivotTable, [
            'student_id',
            'user_id',
            'participant_id',
        ]);

        if ($studentColumn) {
            return (int) $query->distinct()->count($studentColumn);
        }

        return (int) $query->count();
    }

    protected function getFilledSeatCountForBatch(int $batchId): int
    {
        $pivotTable = $this->findExistingTable([
            'batch_students',
            'student_batches',
            'enrollments',
            'batch_enrollments',
        ]);

        if (! $pivotTable) {
            return 0;
        }

        $batchIdColumn = $this->findExistingColumn($pivotTable, ['batch_id']);
        if (! $batchIdColumn) {
            return 0;
        }

        $query = DB::table($pivotTable)->where($batchIdColumn, $batchId);

        $studentColumn = $this->findExistingColumn($pivotTable, [
            'student_id',
            'user_id',
            'participant_id',
        ]);

        if ($studentColumn) {
            return (int) $query->distinct()->count($studentColumn);
        }

        return (int) $query->count();
    }

    protected function safeCount(string $table): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        return (int) DB::table($table)->count();
    }

    protected function findExistingTable(array $tables): ?string
    {
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                return $table;
            }
        }

        return null;
    }

    protected function findExistingColumn(?string $table, array $columns): ?string
    {
        if (! $table || ! Schema::hasTable($table)) {
            return null;
        }

        foreach ($columns as $column) {
            if (Schema::hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    protected function hasColumn(string $table, string $column): bool
    {
        return Schema::hasColumn($table, $column);
    }
}