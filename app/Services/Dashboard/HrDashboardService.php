<?php

namespace App\Services\Dashboard;

use App\Models\AttendanceImport;
use App\Models\AttendanceImportRow;
use App\Models\CompanyHoliday;
use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\WorkingHourTemplate;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Throwable;

class HrDashboardService
{
    protected ?int $activeEmployeeCountCache = null;

    private const TYPE_PRESENT = 'present';
    private const TYPE_ABSENT = 'absent';
    private const TYPE_MISSING = 'missing';
    private const TYPE_HOLIDAY = 'holiday';
    private const TYPE_OFF_DAY = 'off_day';

    private const ARRIVAL_ON_TIME = 'on_time';
    private const ARRIVAL_LATE = 'late';
    private const ARRIVAL_EXCUSED_LATE = 'excused_late';
    private const ARRIVAL_UNKNOWN = 'unknown';

    private const DEPARTURE_EARLY = 'early_departure';
    private const DEPARTURE_EXCUSED_EARLY = 'excused_early_departure';

    /**
     * Menyiapkan seluruh data HR Dashboard.
     *
     * Source of truth:
     * - EmployeeAttendance: attendance final yang sudah confirmed.
     * - AttendanceImport/AttendanceImportRow: backlog review dan data quality.
     *
     * Filter:
     * - date_from: Y-m-d
     * - date_to: Y-m-d
     * - work_team: optional
     *
     * Default periode mengikuti bulan dari attendance final terakhir.
     */
    public function getData(array $filters = []): array
    {
        [$dateFrom, $dateTo] = $this->resolvePeriod($filters);
        [$previousFrom, $previousTo] = $this->resolvePreviousPeriod($dateFrom, $dateTo);

        $workTeam = $this->optionalString($filters['work_team'] ?? null);

        $currentRows = $this->getAttendanceRows($dateFrom, $dateTo, $workTeam);
        $previousRows = $this->getAttendanceRows($previousFrom, $previousTo, $workTeam);

        $attendanceOverview = $this->getAttendanceOverview($currentRows);
        $previousAttendanceOverview = $this->getAttendanceOverview($previousRows);
        $attendanceChanges = $this->buildAttendanceChanges(
            $attendanceOverview,
            $previousAttendanceOverview
        );

        $dataFreshness = $this->getDataFreshness();
        $reviewAndDataQuality = $this->getReviewAndDataQuality(
            $dateFrom,
            $dateTo,
            $workTeam
        );
        $employeesRequiringAttention = $this->getEmployeesRequiringAttention($currentRows, 8);
        $topEmployeesByAttendance = $this->getTopEmployeesByAttendance($currentRows, 10);
        $attendanceByTeam = $this->getAttendanceByTeam($currentRows);
        $upcomingHolidays = $this->getUpcomingHolidays();
        $masterDataHealth = $this->getMasterDataHealth();
        $latestImport = $this->getLatestImport();

        $hrSummary = $this->buildHrDashboardSummary([
            'period' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'label' => $this->formatPeriodLabel($dateFrom, $dateTo),
            ],
            'data_freshness' => $dataFreshness,
            'attendance_overview' => $attendanceOverview,
            'attendance_changes' => $attendanceChanges,
            'employees_requiring_attention' => $employeesRequiringAttention,
            'review_and_data_quality' => $reviewAndDataQuality,
            'latest_import' => $latestImport,
            'master_data_health' => $masterDataHealth,
            'upcoming_holidays' => $upcomingHolidays,
        ]);

        return [
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'work_team' => $workTeam,
            ],
            'period' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'label' => $this->formatPeriodLabel($dateFrom, $dateTo),
                'previous_date_from' => $previousFrom,
                'previous_date_to' => $previousTo,
                'previous_label' => $this->formatPeriodLabel($previousFrom, $previousTo),
            ],
            'workTeams' => $this->getWorkTeamOptions(),
            'dataFreshness' => $dataFreshness,
            'attendanceOverview' => $attendanceOverview,
            'previousAttendanceOverview' => $previousAttendanceOverview,
            'attendanceChanges' => $attendanceChanges,
            'attendanceHealthChart' => $this->getAttendanceHealthChart($currentRows),
            'weeklyTrendChart' => $this->getWeeklyTrendChart($currentRows, $dateFrom, $dateTo),
            'leaveDistributionChart' => $this->getLeaveDistributionChart($currentRows),
            'employeesRequiringAttention' => $employeesRequiringAttention,
            'topEmployeesByAttendance' => $topEmployeesByAttendance,
            'attendanceByTeam' => $attendanceByTeam,
            'reviewAndDataQuality' => $reviewAndDataQuality,
            'latestImport' => $latestImport,
            'upcomingHolidays' => $upcomingHolidays,
            'masterDataHealth' => $masterDataHealth,
            'hrSummary' => $hrSummary,
            'hrDashboardAiSummaryText' => $hrSummary['summary_text'] ?? '',
        ];
    }

    /**
     * Payload chart untuk endpoint async/JSON.
     */
    public function getChartData(array $filters = []): array
    {
        [$dateFrom, $dateTo] = $this->resolvePeriod($filters);
        $workTeam = $this->optionalString($filters['work_team'] ?? null);
        $rows = $this->getAttendanceRows($dateFrom, $dateTo, $workTeam);

        return [
            'period' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'label' => $this->formatPeriodLabel($dateFrom, $dateTo),
            ],
            'attendance_health' => $this->getAttendanceHealthChart($rows),
            'weekly_trend' => $this->getWeeklyTrendChart($rows, $dateFrom, $dateTo),
            'leave_distribution' => $this->getLeaveDistributionChart($rows),
        ];
    }

    /**
     * Data untuk halaman Monthly Attendance Report.
     */
    public function getMonthlyReportData(array $filters = []): array
    {
        [$dateFrom, $dateTo] = $this->resolveMonthPeriod($filters);
        $workTeam = $this->optionalString($filters['work_team'] ?? null);
        $rows = $this->getAttendanceRows($dateFrom, $dateTo, $workTeam);
        $employeeSummaries = $this->buildEmployeeSummaries($rows);

        return [
            'filters' => [
                'month' => Carbon::parse($dateFrom)->format('Y-m'),
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'work_team' => $workTeam,
            ],
            'period' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'label' => Carbon::parse($dateFrom)->translatedFormat('F Y'),
            ],
            'workTeams' => $this->getWorkTeamOptions(),
            'summary' => $this->getAttendanceOverview($rows),
            'employeeSummaries' => $employeeSummaries,
            'weeklyBlocks' => $this->buildWeeklyMatrixBlocks(
                $rows,
                $dateFrom,
                $dateTo,
                $employeeSummaries
            ),
            'attendanceHealthChart' => $this->getAttendanceHealthChart($rows),
            'leaveDistributionChart' => $this->getLeaveDistributionChart($rows),
        ];
    }

    /**
     * Drilldown satu employee untuk periode bulanan.
     */
    public function getEmployeeDetailData(int $employeeId, array $filters = []): array
    {
        $employee = Employee::query()
            ->with('defaultWorkingHourTemplate')
            ->findOrFail($employeeId);

        [$dateFrom, $dateTo] = $this->resolveMonthPeriod($filters);

        $rows = EmployeeAttendance::query()
            ->with([
                'workingHourTemplate:id,name,start_time,end_time',
                'attendanceImport:id,original_file_name,status',
            ])
            ->where('employee_id', $employee->id)
            ->between($dateFrom, $dateTo)
            ->orderBy('attendance_date')
            ->get();

        return [
            'employee' => $employee,
            'period' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'label' => Carbon::parse($dateFrom)->translatedFormat('F Y'),
            ],
            'overview' => $this->getAttendanceOverview($rows),
            'calendar' => $rows
                ->map(fn (EmployeeAttendance $row) => $this->attendanceCalendarItem($row))
                ->values(),
            'exceptions' => $rows
                ->filter(fn (EmployeeAttendance $row) => $this->isExceptionRow($row))
                ->sortByDesc(fn (EmployeeAttendance $row) => $row->attendance_date?->format('Y-m-d'))
                ->map(fn (EmployeeAttendance $row) => $this->attendanceExceptionItem($row))
                ->values(),
            'monthlyTrend' => $this->getEmployeeMonthlyTrend(
                $employee->id,
                $dateTo,
                6
            ),
        ];
    }

    protected function resolvePeriod(array $filters): array
    {
        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        $latestAttendanceDate = $this->getLatestAttendanceDate();

        if ($dateFrom === '' && $dateTo === '') {
            $anchor = $latestAttendanceDate
                ? Carbon::parse($latestAttendanceDate)
                : now();

            return [
                $anchor->copy()->startOfMonth()->toDateString(),
                ($latestAttendanceDate ? $anchor : now())->copy()->startOfDay()->toDateString(),
            ];
        }

        try {
            $from = $dateFrom !== ''
                ? Carbon::parse($dateFrom)->startOfDay()
                : Carbon::parse($dateTo)->startOfMonth();
        } catch (Throwable) {
            $from = $latestAttendanceDate
                ? Carbon::parse($latestAttendanceDate)->startOfMonth()
                : now()->startOfMonth();
        }

        try {
            $to = $dateTo !== ''
                ? Carbon::parse($dateTo)->startOfDay()
                : $from->copy()->endOfMonth();
        } catch (Throwable) {
            $to = $latestAttendanceDate
                ? Carbon::parse($latestAttendanceDate)->startOfDay()
                : now()->startOfDay();
        }

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from->toDateString(), $to->toDateString()];
    }

    protected function resolveMonthPeriod(array $filters): array
    {
        $month = trim((string) ($filters['month'] ?? ''));

        if ($month !== '') {
            try {
                $anchor = Carbon::createFromFormat('Y-m', $month)->startOfMonth();

                return [
                    $anchor->toDateString(),
                    $anchor->copy()->endOfMonth()->toDateString(),
                ];
            } catch (Throwable) {
                // Fall through to the normal resolver.
            }
        }

        [$dateFrom] = $this->resolvePeriod($filters);
        $anchor = Carbon::parse($dateFrom)->startOfMonth();

        return [
            $anchor->toDateString(),
            $anchor->copy()->endOfMonth()->toDateString(),
        ];
    }

    protected function resolvePreviousPeriod(string $dateFrom, string $dateTo): array
    {
        $from = Carbon::parse($dateFrom)->startOfDay();
        $to = Carbon::parse($dateTo)->startOfDay();
        $periodDays = max(1, $from->diffInDays($to) + 1);
        $previousTo = $from->copy()->subDay();
        $previousFrom = $previousTo->copy()->subDays($periodDays - 1);

        return [$previousFrom->toDateString(), $previousTo->toDateString()];
    }

    protected function getLatestAttendanceDate(): ?string
    {
        if (! Schema::hasTable((new EmployeeAttendance())->getTable())) {
            return null;
        }

        $date = EmployeeAttendance::query()->max('attendance_date');

        return $date ? Carbon::parse($date)->toDateString() : null;
    }

    protected function getAttendanceRows(
        string $dateFrom,
        string $dateTo,
        ?string $workTeam = null
    ): Collection {
        if (! Schema::hasTable((new EmployeeAttendance())->getTable())) {
            return collect();
        }

        return EmployeeAttendance::query()
            ->with([
                'employee:id,employee_number,name,work_team,employee_type,duty_type,is_active',
                'workingHourTemplate:id,name,start_time,end_time',
            ])
            ->whereNotNull('employee_id')
            ->between($dateFrom, $dateTo)
            ->when(
                $workTeam,
                fn (Builder $query) => $query->whereHas(
                    'employee',
                    fn (Builder $employeeQuery) => $employeeQuery->where('work_team', $workTeam)
                )
            )
            ->orderBy('attendance_date')
            ->orderBy('employee_id')
            ->get();
    }

    protected function getAttendanceOverview(Collection $rows): array
    {
        $expectedRows = $rows
            ->reject(fn (EmployeeAttendance $row) => in_array(
                $row->attendance_type,
                [self::TYPE_HOLIDAY, self::TYPE_OFF_DAY],
                true
            ))
            ->values();

        $presentRows = $expectedRows
            ->filter(fn (EmployeeAttendance $row) => $row->attendance_type === self::TYPE_PRESENT);

        $onTimeRows = $presentRows
            ->filter(fn (EmployeeAttendance $row) => $this->isOnTime($row));

        $lateRows = $presentRows
            ->filter(fn (EmployeeAttendance $row) => $this->isLate($row));

        $excusedLateRows = $presentRows
            ->filter(fn (EmployeeAttendance $row) => $this->isExcusedLate($row));

        $approvedLeaveRows = $expectedRows
            ->filter(fn (EmployeeAttendance $row) => $this->isApprovedLeave($row));

        $unexcusedAbsentRows = $expectedRows
            ->filter(fn (EmployeeAttendance $row) => $this->isUnexcusedAbsent($row));

        $missingRows = $expectedRows
            ->where('attendance_type', self::TYPE_MISSING);

        $earlyDepartureRows = $presentRows
            ->where('departure_status', self::DEPARTURE_EARLY);

        $excusedEarlyRows = $presentRows
            ->where('departure_status', self::DEPARTURE_EXCUSED_EARLY);

        $autoClockOutRows = $presentRows
            ->filter(fn (EmployeeAttendance $row) => $this->isAutoClockOut($row));

        $expectedWorkdays = $expectedRows->count();
        $presentDays = $presentRows->count();
        $onTimeDays = $onTimeRows->count();
        $absentDays = $unexcusedAbsentRows->count();
        $approvedLeaveDays = $approvedLeaveRows->count();

        return [
            'employees_covered' => (int) $rows
                ->pluck('employee_id')
                ->filter()
                ->unique()
                ->count(),
            'active_employees' => $this->activeEmployeeCount(),
            'attendance_rows' => $rows->count(),
            'expected_workdays' => $expectedWorkdays,
            'present_days' => $presentDays,
            'on_time_days' => $onTimeDays,
            'late_days' => $lateRows->count(),
            'excused_late_days' => $excusedLateRows->count(),
            'approved_leave_days' => $approvedLeaveDays,
            'full_day_leave_days' => $approvedLeaveRows
                ->where('leave_duration', 'full_day')
                ->count(),
            'half_day_leave_days' => $approvedLeaveRows
                ->where('leave_duration', 'half_day')
                ->count(),
            'absent_days' => $absentDays,
            'missing_days' => $missingRows->count(),
            'early_departure_days' => $earlyDepartureRows->count(),
            'excused_early_departure_days' => $excusedEarlyRows->count(),
            'auto_clock_out_days' => $autoClockOutRows->count(),
            'holiday_days' => $rows
                ->where('attendance_type', self::TYPE_HOLIDAY)
                ->count(),
            'off_day_days' => $rows
                ->where('attendance_type', self::TYPE_OFF_DAY)
                ->count(),
            'total_scheduled_minutes' => (int) $expectedRows
                ->sum(fn (EmployeeAttendance $row) => (int) ($row->scheduled_work_minutes ?? 0)),
            'total_worked_minutes' => (int) $presentRows
                ->sum(fn (EmployeeAttendance $row) => (int) ($row->worked_minutes ?? 0)),
            'average_late_minutes' => $lateRows->isNotEmpty()
                ? round((float) $lateRows->average(
                    fn (EmployeeAttendance $row) => (int) ($row->late_minutes ?? 0)
                ), 1)
                : 0,

            // Present Days / Expected Workdays.
            'presence_rate' => $this->percentage($presentDays, $expectedWorkdays),

            // On-Time Days / Present Days.
            'on_time_rate' => $this->percentage($onTimeDays, $presentDays),

            // Unexcused Absent Days / Expected Workdays.
            'absence_rate' => $this->percentage($absentDays, $expectedWorkdays),

            // Approved Leave Days / Expected Workdays.
            'leave_coverage_rate' => $this->percentage(
                $approvedLeaveDays,
                $expectedWorkdays
            ),
            'auto_clock_out_rate' => $this->percentage(
                $autoClockOutRows->count(),
                $presentDays
            ),
        ];
    }

    protected function buildAttendanceChanges(array $current, array $previous): array
    {
        return [
            'presence_rate' => $this->buildRateChange(
                (float) ($current['presence_rate'] ?? 0),
                (float) ($previous['presence_rate'] ?? 0)
            ),
            'on_time_rate' => $this->buildRateChange(
                (float) ($current['on_time_rate'] ?? 0),
                (float) ($previous['on_time_rate'] ?? 0)
            ),
            'absence_rate' => $this->buildRateChange(
                (float) ($current['absence_rate'] ?? 0),
                (float) ($previous['absence_rate'] ?? 0),
                true
            ),
            'present_days' => $this->buildChange(
                (int) ($current['present_days'] ?? 0),
                (int) ($previous['present_days'] ?? 0)
            ),
            'approved_leave_days' => $this->buildChange(
                (int) ($current['approved_leave_days'] ?? 0),
                (int) ($previous['approved_leave_days'] ?? 0)
            ),
            'absent_days' => $this->buildChange(
                (int) ($current['absent_days'] ?? 0),
                (int) ($previous['absent_days'] ?? 0),
                true
            ),
        ];
    }

    protected function getAttendanceHealthChart(Collection $rows): array
    {
        $expectedRows = $rows->reject(fn (EmployeeAttendance $row) => in_array(
            $row->attendance_type,
            [self::TYPE_HOLIDAY, self::TYPE_OFF_DAY],
            true
        ));

        $counts = [
            'on_time' => $expectedRows
                ->filter(fn (EmployeeAttendance $row) => $row->attendance_type === self::TYPE_PRESENT && $this->isOnTime($row))
                ->count(),
            'late' => $expectedRows
                ->filter(fn (EmployeeAttendance $row) => $row->attendance_type === self::TYPE_PRESENT && $this->isLate($row))
                ->count(),
            'excused_late' => $expectedRows
                ->filter(fn (EmployeeAttendance $row) => $row->attendance_type === self::TYPE_PRESENT && $this->isExcusedLate($row))
                ->count(),
            'half_day_leave' => $expectedRows
                ->filter(fn (EmployeeAttendance $row) => $this->isApprovedLeave($row) && $row->leave_duration === 'half_day')
                ->count(),
            'full_day_leave' => $expectedRows
                ->filter(fn (EmployeeAttendance $row) => $this->isApprovedLeave($row) && $row->leave_duration === 'full_day')
                ->count(),
            'absent' => $expectedRows
                ->filter(fn (EmployeeAttendance $row) => $this->isUnexcusedAbsent($row))
                ->count(),
            'missing' => $expectedRows
                ->where('attendance_type', self::TYPE_MISSING)
                ->count(),
        ];

        return [
            'labels' => [
                'On Time',
                'Late',
                'Excused Late',
                'Half-Day Leave',
                'Full-Day Leave',
                'Absent',
                'Missing',
            ],
            'keys' => array_keys($counts),
            'datasets' => [
                'attendance_status' => array_values($counts),
            ],
            'counts' => $counts,
            'total' => array_sum($counts),
        ];
    }

    protected function getWeeklyTrendChart(
        Collection $rows,
        string $dateFrom,
        string $dateTo
    ): array {
        $from = Carbon::parse($dateFrom)->startOfDay();
        $to = Carbon::parse($dateTo)->startOfDay();
        $weekStart = $from->copy()->startOfWeek(Carbon::MONDAY);

        $labels = [];
        $presenceRates = [];
        $onTimeRates = [];
        $absenceRates = [];
        $presentDays = [];
        $expectedWorkdays = [];

        while ($weekStart->lte($to)) {
            $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);
            $effectiveFrom = $weekStart->lessThan($from) ? $from->copy() : $weekStart->copy();
            $effectiveTo = $weekEnd->greaterThan($to) ? $to->copy() : $weekEnd->copy();

            $weekRows = $rows
                ->filter(fn (EmployeeAttendance $row) => $row->attendance_date
                    && $row->attendance_date->betweenIncluded($effectiveFrom, $effectiveTo))
                ->values();

            $overview = $this->getAttendanceOverview($weekRows);

            $labels[] = $this->formatCompactPeriodLabel($effectiveFrom, $effectiveTo);
            $presenceRates[] = (float) ($overview['presence_rate'] ?? 0);
            $onTimeRates[] = (float) ($overview['on_time_rate'] ?? 0);
            $absenceRates[] = (float) ($overview['absence_rate'] ?? 0);
            $presentDays[] = (int) ($overview['present_days'] ?? 0);
            $expectedWorkdays[] = (int) ($overview['expected_workdays'] ?? 0);

            $weekStart->addWeek();
        }

        return [
            'granularity' => 'weekly',
            'labels' => $labels,
            'datasets' => [
                'presence_rate' => $presenceRates,
                'on_time_rate' => $onTimeRates,
                'absence_rate' => $absenceRates,
                'present_days' => $presentDays,
                'expected_workdays' => $expectedWorkdays,
            ],
        ];
    }

    protected function getLeaveDistributionChart(Collection $rows): array
    {
        $leaveRows = $rows->filter(fn (EmployeeAttendance $row) => $this->isApprovedLeave($row));
        $keys = ['sick_leave', 'annual_leave', 'unpaid_leave', 'permission', 'other'];
        $labels = ['Sick Leave', 'Annual Leave', 'Unpaid Leave', 'Permission', 'Other'];

        $counts = collect($keys)
            ->mapWithKeys(fn (string $key) => [
                $key => $leaveRows->where('leave_type', $key)->count(),
            ])
            ->all();

        return [
            'labels' => $labels,
            'keys' => $keys,
            'datasets' => [
                'leave_type' => array_values($counts),
            ],
            'counts' => $counts,
            'full_day_total' => $leaveRows->where('leave_duration', 'full_day')->count(),
            'half_day_total' => $leaveRows->where('leave_duration', 'half_day')->count(),
            'total' => $leaveRows->count(),
        ];
    }

    protected function getEmployeesRequiringAttention(Collection $rows, int $limit = 8): Collection
    {
        return $rows
            ->whereNotNull('employee_id')
            ->groupBy('employee_id')
            ->map(function (Collection $employeeRows): array {
                /** @var EmployeeAttendance|null $firstRow */
                $firstRow = $employeeRows->first();
                $employee = $firstRow?->employee;

                $lateRows = $employeeRows->filter(fn (EmployeeAttendance $row) => $this->isLate($row));
                $absentRows = $employeeRows->filter(fn (EmployeeAttendance $row) => $this->isUnexcusedAbsent($row));
                $autoClockOutRows = $employeeRows->filter(fn (EmployeeAttendance $row) => $this->isAutoClockOut($row));
                $earlyRows = $employeeRows->where('departure_status', self::DEPARTURE_EARLY);
                $unknownRows = $employeeRows->filter(fn (EmployeeAttendance $row) => $row->attendance_type === self::TYPE_PRESENT
                    && (($row->arrival_status ?: $row->punctuality_status) === self::ARRIVAL_UNKNOWN));

                $score = ($absentRows->count() * 6)
                    + ($lateRows->count() * 2)
                    + ($earlyRows->count() * 2)
                    + ($unknownRows->count() * 2)
                    + $autoClockOutRows->count();

                $lastIssueDate = $employeeRows
                    ->filter(fn (EmployeeAttendance $row) => $this->isExceptionRow($row))
                    ->pluck('attendance_date')
                    ->filter()
                    ->sortByDesc(fn (Carbon $date) => $date->format('Y-m-d'))
                    ->first();

                $counts = [
                    'late_days' => $lateRows->count(),
                    'absent_days' => $absentRows->count(),
                    'auto_clock_out_days' => $autoClockOutRows->count(),
                    'early_departure_days' => $earlyRows->count(),
                    'unknown_punctuality_days' => $unknownRows->count(),
                ];

                return [
                    'employee_id' => $employee?->id ?? $firstRow?->employee_id,
                    'employee_number' => $employee?->employee_number,
                    'employee_name' => $employee?->name ?? 'Unknown Employee',
                    'work_team' => $employee?->work_team ?: 'Unassigned',
                    ...$counts,
                    'score' => $score,
                    'last_issue_date' => $lastIssueDate
                        ? Carbon::parse($lastIssueDate)->toDateString()
                        : null,
                    'reason' => $this->buildAttentionReason($counts),
                ];
            })
            ->filter(fn (array $item) => (int) ($item['score'] ?? 0) > 0)
            ->sortByDesc('score')
            ->take($limit)
            ->values();
    }

    /**
     * Menentukan Top 10 Employee berdasarkan attendance final.
     *
     * Urutan ranking:
     * 1. Presence Rate tertinggi.
     * 2. On-Time Rate tertinggi.
     * 3. Jumlah absent paling sedikit.
     * 4. Jumlah expected workday lebih besar.
     *
     * Tidak memakai skor tersembunyi agar ranking mudah dipahami HR.
     */
    protected function getTopEmployeesByAttendance(
        Collection $rows,
        int $limit = 10
    ): Collection {
        return $this->buildEmployeeSummaries($rows)
            ->filter(
                fn (array $employee): bool =>
                    (int) ($employee['expected_workdays'] ?? 0) > 0
            )
            ->sort(function (
                array $left,
                array $right
            ): int {
                $presenceComparison = (
                    (float) ($right['presence_rate'] ?? 0)
                ) <=> (
                    (float) ($left['presence_rate'] ?? 0)
                );

                if ($presenceComparison !== 0) {
                    return $presenceComparison;
                }

                $onTimeComparison = (
                    (float) ($right['on_time_rate'] ?? 0)
                ) <=> (
                    (float) ($left['on_time_rate'] ?? 0)
                );

                if ($onTimeComparison !== 0) {
                    return $onTimeComparison;
                }

                $absenceComparison = (
                    (int) ($left['absent_days'] ?? 0)
                ) <=> (
                    (int) ($right['absent_days'] ?? 0)
                );

                if ($absenceComparison !== 0) {
                    return $absenceComparison;
                }

                $workdayComparison = (
                    (int) ($right['expected_workdays'] ?? 0)
                ) <=> (
                    (int) ($left['expected_workdays'] ?? 0)
                );

                if ($workdayComparison !== 0) {
                    return $workdayComparison;
                }

                return strcasecmp(
                    (string) ($left['employee_name'] ?? ''),
                    (string) ($right['employee_name'] ?? '')
                );
            })
            ->take($limit)
            ->values()
            ->map(function (
                array $employee,
                int $index
            ): array {
                $employee['rank'] = $index + 1;

                return $employee;
            });
    }

    protected function getAttendanceByTeam(Collection $rows): Collection
    {
        return $rows
            ->whereNotNull('employee_id')
            ->groupBy(fn (EmployeeAttendance $row) => trim((string) ($row->employee?->work_team ?: 'Unassigned')))
            ->map(function (Collection $teamRows, string $team): array {
                $overview = $this->getAttendanceOverview($teamRows);

                return [
                    'team' => $team,
                    'employees' => (int) $teamRows->pluck('employee_id')->filter()->unique()->count(),
                    'expected_workdays' => (int) ($overview['expected_workdays'] ?? 0),
                    'present_days' => (int) ($overview['present_days'] ?? 0),
                    'on_time_days' => (int) ($overview['on_time_days'] ?? 0),
                    'late_days' => (int) ($overview['late_days'] ?? 0),
                    'approved_leave_days' => (int) ($overview['approved_leave_days'] ?? 0),
                    'absent_days' => (int) ($overview['absent_days'] ?? 0),
                    'auto_clock_out_days' => (int) ($overview['auto_clock_out_days'] ?? 0),
                    'presence_rate' => (float) ($overview['presence_rate'] ?? 0),
                    'on_time_rate' => (float) ($overview['on_time_rate'] ?? 0),
                    'absence_rate' => (float) ($overview['absence_rate'] ?? 0),
                ];
            })
            ->sortBy('team')
            ->values();
    }

    protected function getDataFreshness(): array
    {
        $latestCompleted = AttendanceImport::query()
            ->completed()
            ->orderByDesc('confirmed_at')
            ->orderByDesc('id')
            ->with(['uploader:id,name', 'confirmer:id,name'])
            ->first();

        $latestPendingQuery = AttendanceImport::query()
            ->whereIn('status', [
                AttendanceImport::STATUS_UPLOADED,
                AttendanceImport::STATUS_REVIEWING,
                AttendanceImport::STATUS_PROCESSING,
                AttendanceImport::STATUS_FAILED,
            ]);

        /*
        | Jangan biarkan import gagal lama membuat dashboard selamanya berstatus
        | Review Required setelah periode yang lebih baru sudah confirmed.
        */
        if ($latestCompleted) {
            $latestPendingQuery->where('id', '>', $latestCompleted->id);
        }

        $latestPending = $latestPendingQuery
            ->orderByDesc('imported_at')
            ->orderByDesc('id')
            ->with('uploader:id,name')
            ->first();

        $availableThrough = $this->getLatestAttendanceDate()
            ?? $latestCompleted?->date_to?->toDateString();

        $daysSinceAvailable = null;

        if ($availableThrough) {
            $daysSinceAvailable = max(
                0,
                Carbon::parse($availableThrough)
                    ->startOfDay()
                    ->diffInDays(now()->startOfDay(), false)
            );
        }

        $status = match (true) {
            $latestPending && in_array(
                $latestPending->status,
                [AttendanceImport::STATUS_REVIEWING, AttendanceImport::STATUS_FAILED],
                true
            ) => 'review_required',
            ! $availableThrough => 'no_data',
            $daysSinceAvailable <= 8 => 'up_to_date',
            $daysSinceAvailable <= 14 => 'awaiting_weekly_import',
            default => 'import_overdue',
        };

        return [
            'status' => $status,
            'status_label' => match ($status) {
                'up_to_date' => 'Up to Date',
                'awaiting_weekly_import' => 'Awaiting Weekly Import',
                'review_required' => 'Review Required',
                'import_overdue' => 'Import Overdue',
                default => 'No Confirmed Data',
            },
            'status_description' => match ($status) {
                'up_to_date' => 'Attendance final masih berada dalam siklus import mingguan.',
                'awaiting_weekly_import' => 'Data terakhir masih tersedia, tetapi upload periode berikutnya sudah mendekati jadwal.',
                'review_required' => 'Ada attendance import yang belum selesai direview atau mengalami kegagalan.',
                'import_overdue' => 'Data attendance terakhir sudah melewati satu siklus import mingguan.',
                default => 'Belum ada attendance final yang dapat digunakan untuk dashboard.',
            },
            'available_through' => $availableThrough,
            'days_since_available' => $daysSinceAvailable,
            'latest_completed_import' => $this->attendanceImportItem($latestCompleted),
            'latest_pending_import' => $this->attendanceImportItem($latestPending),
        ];
    }

    protected function getReviewAndDataQuality(
        string $dateFrom,
        string $dateTo,
        ?string $workTeam = null
    ): array {
        $openImports = AttendanceImport::query()
            ->whereIn('status', [
                AttendanceImport::STATUS_UPLOADED,
                AttendanceImport::STATUS_REVIEWING,
                AttendanceImport::STATUS_PROCESSING,
                AttendanceImport::STATUS_FAILED,
            ])
            ->where(function (Builder $query) use ($dateFrom, $dateTo): void {
                $query
                    ->whereNull('date_from')
                    ->orWhereNull('date_to')
                    ->orWhere(function (Builder $periodQuery) use ($dateFrom, $dateTo): void {
                        $periodQuery
                            ->whereDate('date_from', '<=', $dateTo)
                            ->whereDate('date_to', '>=', $dateFrom);
                    });
            })
            ->get();

        $importIds = $openImports->pluck('id');

        $rows = $importIds->isEmpty()
            ? collect()
            : AttendanceImportRow::query()
                ->with('employee:id,work_team')
                ->whereIn('attendance_import_id', $importIds)
                ->when(
                    $workTeam,
                    fn (Builder $query) => $query->whereHas(
                        'employee',
                        fn (Builder $employeeQuery) => $employeeQuery->where('work_team', $workTeam)
                    )
                )
                ->get([
                    'id',
                    'attendance_import_id',
                    'employee_id',
                    'attendance_date',
                    'attendance_type',
                    'source',
                    'review_status',
                    'raw_payload',
                    'resolution_metadata',
                ]);

        $blockingRows = $rows->filter(fn (AttendanceImportRow $row) => in_array(
            $row->review_status,
            [
                AttendanceImportRow::REVIEW_NEEDS_REVIEW,
                AttendanceImportRow::REVIEW_ERROR,
                AttendanceImportRow::REVIEW_DUPLICATE,
            ],
            true
        ) || ! $row->employee_id || ! $row->attendance_date);

        return [
            'open_imports' => $openImports->count(),
            'review_backlog' => $blockingRows->pluck('id')->unique()->count(),
            'needs_review_rows' => $rows
                ->where('review_status', AttendanceImportRow::REVIEW_NEEDS_REVIEW)
                ->count(),
            'error_rows' => $rows
                ->where('review_status', AttendanceImportRow::REVIEW_ERROR)
                ->count(),
            'conflicting_duplicate_rows' => $rows
                ->filter(fn (AttendanceImportRow $row) => $row->review_status === AttendanceImportRow::REVIEW_DUPLICATE
                    || data_get($row->raw_payload, '_system.duplicate.type') === 'conflict')
                ->count(),
            'exact_duplicate_auto_resolved' => $rows
                ->filter(fn (AttendanceImportRow $row) => data_get($row->raw_payload, '_system.duplicate.type') === 'exact'
                    && $row->review_status === AttendanceImportRow::REVIEW_IGNORED)
                ->count(),
            'unmatched_employee_rows' => $rows->whereNull('employee_id')->count(),
            'generated_missing_rows' => $rows
                ->filter(fn (AttendanceImportRow $row) => $row->source === AttendanceImportRow::SOURCE_GENERATED_GAP
                    && data_get($row->raw_payload, '_system.generated_type') === 'missing_workday')
                ->count(),
            'generated_holiday_rows' => $rows
                ->filter(fn (AttendanceImportRow $row) => data_get($row->raw_payload, '_system.generated_type') === 'company_holiday')
                ->count(),
            'auto_clock_out_rows' => $rows
                ->filter(fn (AttendanceImportRow $row) => (bool) data_get(
                    $row->raw_payload,
                    '_system.auto_clock_out',
                    false
                ))
                ->count(),
        ];
    }

    protected function getLatestImport(): ?array
    {
        $attendanceImport = AttendanceImport::query()
            ->with(['uploader:id,name', 'confirmer:id,name'])
            ->orderByDesc('imported_at')
            ->orderByDesc('id')
            ->first();

        return $this->attendanceImportItem($attendanceImport);
    }

    protected function getUpcomingHolidays(int $limit = 5): Collection
    {
        if (! Schema::hasTable((new CompanyHoliday())->getTable())) {
            return collect();
        }

        return CompanyHoliday::query()
            ->active()
            ->whereDate('holiday_date', '>=', now()->toDateString())
            ->orderBy('holiday_date')
            ->limit($limit)
            ->get(['id', 'holiday_date', 'name', 'holiday_type', 'notes'])
            ->map(fn (CompanyHoliday $holiday) => [
                'id' => $holiday->id,
                'holiday_date' => $holiday->holiday_date?->toDateString(),
                'name' => $holiday->name,
                'holiday_type' => $holiday->holiday_type,
                'notes' => $holiday->notes,
                'days_remaining' => $holiday->holiday_date
                    ? max(0, now()->startOfDay()->diffInDays(
                        $holiday->holiday_date->copy()->startOfDay(),
                        false
                    ))
                    : null,
            ]);
    }

    protected function getMasterDataHealth(): array
    {
        $activeEmployees = Employee::query()->active()->count();
        $employeesWithoutTemplate = Employee::query()
            ->active()
            ->whereNull('default_working_hour_template_id')
            ->count();
        $employeesWithoutTeam = Employee::query()
            ->active()
            ->where(fn (Builder $query) => $query
                ->whereNull('work_team')
                ->orWhere('work_team', ''))
            ->count();
        $incompleteTemplates = WorkingHourTemplate::query()
            ->active()
            ->where(fn (Builder $query) => $query
                ->whereNull('start_time')
                ->orWhereNull('end_time'))
            ->count();

        return [
            'active_employees' => $activeEmployees,
            'inactive_employees' => Employee::query()->where('is_active', false)->count(),
            'employees_without_template' => $employeesWithoutTemplate,
            'employees_without_work_team' => $employeesWithoutTeam,
            'incomplete_working_templates' => $incompleteTemplates,
            'health_score' => $this->masterDataHealthScore(
                $activeEmployees,
                $employeesWithoutTemplate,
                $employeesWithoutTeam,
                $incompleteTemplates
            ),
        ];
    }

    protected function buildEmployeeSummaries(Collection $rows): Collection
    {
        return $rows
            ->whereNotNull('employee_id')
            ->groupBy('employee_id')
            ->map(function (Collection $employeeRows): array {
                /** @var EmployeeAttendance|null $firstRow */
                $firstRow = $employeeRows->first();
                $employee = $firstRow?->employee;
                $overview = $this->getAttendanceOverview($employeeRows);

                return [
                    'employee_id' => $employee?->id ?? $firstRow?->employee_id,
                    'employee_number' => $employee?->employee_number,
                    'employee_name' => $employee?->name ?? 'Unknown Employee',
                    'work_team' => $employee?->work_team ?: 'Unassigned',
                    'expected_workdays' => (int) ($overview['expected_workdays'] ?? 0),
                    'present_days' => (int) ($overview['present_days'] ?? 0),
                    'on_time_days' => (int) ($overview['on_time_days'] ?? 0),
                    'late_days' => (int) ($overview['late_days'] ?? 0),
                    'approved_leave_days' => (int) ($overview['approved_leave_days'] ?? 0),
                    'absent_days' => (int) ($overview['absent_days'] ?? 0),
                    'missing_days' => (int) ($overview['missing_days'] ?? 0),
                    'auto_clock_out_days' => (int) ($overview['auto_clock_out_days'] ?? 0),
                    'presence_rate' => (float) ($overview['presence_rate'] ?? 0),
                    'on_time_rate' => (float) ($overview['on_time_rate'] ?? 0),
                    'absence_rate' => (float) ($overview['absence_rate'] ?? 0),
                ];
            })
            ->sortBy('employee_name')
            ->values();
    }

    protected function buildWeeklyMatrixBlocks(
        Collection $rows,
        string $dateFrom,
        string $dateTo,
        Collection $employeeSummaries
    ): Collection {
        $dates = collect(CarbonPeriod::create($dateFrom, $dateTo))
            ->map(fn (Carbon $date) => $date->copy());

        return $dates
            ->chunk(7)
            ->values()
            ->map(function (Collection $weekDates, int $index) use ($rows, $employeeSummaries): array {
                $dateKeys = $weekDates
                    ->map(fn (Carbon $date) => $date->toDateString())
                    ->all();

                $rowsByEmployeeDate = $rows
                    ->filter(fn (EmployeeAttendance $row) => $row->attendance_date
                        && in_array($row->attendance_date->toDateString(), $dateKeys, true))
                    ->keyBy(fn (EmployeeAttendance $row) => $row->employee_id
                        . '|'
                        . $row->attendance_date->toDateString());

                return [
                    'week_number' => $index + 1,
                    'date_from' => $weekDates->first()?->toDateString(),
                    'date_to' => $weekDates->last()?->toDateString(),
                    'label' => $this->formatCompactPeriodLabel(
                        $weekDates->first(),
                        $weekDates->last()
                    ),
                    'dates' => $weekDates
                        ->map(fn (Carbon $date) => [
                            'date' => $date->toDateString(),
                            'day' => $date->translatedFormat('D'),
                            'day_number' => $date->format('d'),
                        ])
                        ->values(),
                    'employees' => $employeeSummaries
                        ->map(function (array $employee) use ($weekDates, $rowsByEmployeeDate): array {
                            $employeeId = $employee['employee_id'];

                            return [
                                'employee_id' => $employeeId,
                                'employee_number' => $employee['employee_number'],
                                'employee_name' => $employee['employee_name'],
                                'work_team' => $employee['work_team'],
                                'cells' => $weekDates
                                    ->map(function (Carbon $date) use ($employeeId, $rowsByEmployeeDate): array {
                                        $row = $rowsByEmployeeDate->get(
                                            $employeeId . '|' . $date->toDateString()
                                        );

                                        return $row
                                            ? $this->attendanceMatrixCell($row)
                                            : [
                                                'date' => $date->toDateString(),
                                                'code' => '-',
                                                'label' => 'No Final Record',
                                                'status' => 'no_record',
                                                'is_auto_clock_out' => false,
                                            ];
                                    })
                                    ->values(),
                            ];
                        })
                        ->values(),
                ];
            });
    }

    protected function getEmployeeMonthlyTrend(
        int $employeeId,
        string $periodEnd,
        int $months = 6
    ): array {
        $end = Carbon::parse($periodEnd)->endOfMonth();
        $start = $end->copy()->subMonthsNoOverflow($months - 1)->startOfMonth();

        $rows = EmployeeAttendance::query()
            ->where('employee_id', $employeeId)
            ->between($start->toDateString(), $end->toDateString())
            ->orderBy('attendance_date')
            ->get();

        $labels = [];
        $presenceRates = [];
        $onTimeRates = [];
        $absenceRates = [];
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $monthFrom = $cursor->copy()->startOfMonth();
            $monthTo = $cursor->copy()->endOfMonth();
            $monthRows = $rows->filter(fn (EmployeeAttendance $row) => $row->attendance_date
                && $row->attendance_date->betweenIncluded($monthFrom, $monthTo));
            $overview = $this->getAttendanceOverview($monthRows);

            $labels[] = $cursor->translatedFormat('M Y');
            $presenceRates[] = (float) ($overview['presence_rate'] ?? 0);
            $onTimeRates[] = (float) ($overview['on_time_rate'] ?? 0);
            $absenceRates[] = (float) ($overview['absence_rate'] ?? 0);
            $cursor->addMonth();
        }

        return [
            'labels' => $labels,
            'datasets' => [
                'presence_rate' => $presenceRates,
                'on_time_rate' => $onTimeRates,
                'absence_rate' => $absenceRates,
            ],
        ];
    }

    protected function buildHrDashboardSummary(array $context): array
    {
        $overview = $context['attendance_overview'] ?? [];
        $changes = $context['attendance_changes'] ?? [];
        $freshness = $context['data_freshness'] ?? [];
        $quality = $context['review_and_data_quality'] ?? [];
        $masterData = $context['master_data_health'] ?? [];
        $employeesAttention = collect($context['employees_requiring_attention'] ?? []);
        $upcomingHolidays = collect($context['upcoming_holidays'] ?? []);
        $items = [];

        $freshnessStatus = $freshness['status'] ?? 'no_data';

        if ($freshnessStatus === 'review_required') {
            $items[] = $this->summaryItem(
                'critical',
                'Attendance import perlu diselesaikan',
                'Ada import attendance yang masih reviewing atau gagal. Selesaikan review sebelum menggunakan periode terbaru sebagai laporan final.',
                1000
            );
        } elseif ($freshnessStatus === 'import_overdue') {
            $items[] = $this->summaryItem(
                'warning',
                'Weekly attendance import terlambat',
                'Data attendance terakhir sudah melewati satu siklus upload mingguan.',
                940
            );
        } elseif ($freshnessStatus === 'awaiting_weekly_import') {
            $items[] = $this->summaryItem(
                'action',
                'Periode berikutnya siap di-upload',
                'Data confirmed masih tersedia, tetapi upload attendance mingguan berikutnya sudah mendekati jadwal.',
                800
            );
        } elseif ($freshnessStatus === 'up_to_date') {
            $items[] = $this->summaryItem(
                'good',
                'Attendance data masih up to date',
                'Data final masih berada dalam siklus import mingguan yang wajar.',
                500
            );
        } else {
            $items[] = $this->summaryItem(
                'warning',
                'Attendance final belum tersedia',
                'Belum ada data attendance confirmed yang dapat digunakan sebagai dasar dashboard.',
                970
            );
        }

        $reviewBacklog = (int) ($quality['review_backlog'] ?? 0);

        if ($reviewBacklog > 0) {
            $items[] = $this->summaryItem(
                'critical',
                'Review backlog perlu action HR',
                number_format($reviewBacklog) . ' attendance row masih memblokir proses confirm.',
                980
            );
        }

        $expectedWorkdays = (int) ($overview['expected_workdays'] ?? 0);
        $presenceRate = (float) ($overview['presence_rate'] ?? 0);
        $onTimeRate = (float) ($overview['on_time_rate'] ?? 0);
        $absenceRate = (float) ($overview['absence_rate'] ?? 0);

        if ($expectedWorkdays > 0) {
            if ($presenceRate < 90) {
                $items[] = $this->summaryItem(
                    'warning',
                    'Presence Rate perlu perhatian',
                    'Presence Rate periode terpilih berada di ' . number_format($presenceRate, 1) . '%.',
                    850
                );
            } else {
                $items[] = $this->summaryItem(
                    'good',
                    'Presence Rate sehat',
                    'Presence Rate periode terpilih mencapai ' . number_format($presenceRate, 1) . '%.',
                    450
                );
            }

            if ($onTimeRate < 85) {
                $items[] = $this->summaryItem(
                    'action',
                    'On-Time Rate perlu ditingkatkan',
                    'On-Time Rate saat ini ' . number_format($onTimeRate, 1) . '%. Fokuskan tindak lanjut pada pola keterlambatan yang berulang.',
                    820
                );
            }

            if ($absenceRate > 5) {
                $items[] = $this->summaryItem(
                    'warning',
                    'Absence Rate meningkat',
                    'Unexcused Absence Rate mencapai ' . number_format($absenceRate, 1) . '%.',
                    870
                );
            }
        }

        $presenceChange = data_get($changes, 'presence_rate.percentage_points');

        if (is_numeric($presenceChange) && (float) $presenceChange <= -3) {
            $items[] = $this->summaryItem(
                'warning',
                'Presence Rate menurun',
                'Presence Rate turun ' . number_format(abs((float) $presenceChange), 1) . ' percentage points dibanding periode sebelumnya.',
                830
            );
        }

        if ($employeesAttention->isNotEmpty()) {
            $topEmployee = $employeesAttention->first();

            $items[] = $this->summaryItem(
                'action',
                'Employee attendance perlu follow-up',
                ($topEmployee['employee_name'] ?? 'Employee')
                    . ' menjadi prioritas karena '
                    . ($topEmployee['reason'] ?? 'memiliki attendance exception pada periode ini')
                    . '.',
                790
            );
        }

        if (
            (int) ($masterData['employees_without_template'] ?? 0) > 0
            || (int) ($masterData['incomplete_working_templates'] ?? 0) > 0
        ) {
            $items[] = $this->summaryItem(
                'warning',
                'Master working hours belum lengkap',
                number_format((int) ($masterData['employees_without_template'] ?? 0))
                    . ' employee belum memiliki default template dan '
                    . number_format((int) ($masterData['incomplete_working_templates'] ?? 0))
                    . ' template belum memiliki jam lengkap.',
                760
            );
        }

        if ((int) ($quality['auto_clock_out_rows'] ?? 0) > 0) {
            $items[] = $this->summaryItem(
                'info',
                'Ada clock out yang tidak tercatat',
                number_format((int) ($quality['auto_clock_out_rows'] ?? 0))
                    . ' data attendance memiliki jam pulang yang diisi otomatis berdasarkan jadwal kerja.',
                430
            );
        }

        if ($upcomingHolidays->isNotEmpty()) {
            $holiday = $upcomingHolidays->first();

            $items[] = $this->summaryItem(
                'info',
                'Upcoming company holiday',
                ($holiday['name'] ?? 'Company Holiday')
                    . ' dijadwalkan pada '
                    . Carbon::parse($holiday['holiday_date'])->translatedFormat('d M Y')
                    . '.',
                350
            );
        }

        if (empty($items)) {
            $items[] = $this->summaryItem(
                'info',
                'HR Dashboard siap dipantau',
                'Data attendance tersedia. Pantau presence, punctuality, review backlog, dan kualitas master data secara berkala.',
                300
            );
        }

        usort($items, function (array $left, array $right): int {
            $scoreCompare = ((int) ($right['score'] ?? 0)) <=> ((int) ($left['score'] ?? 0));

            if ($scoreCompare !== 0) {
                return $scoreCompare;
            }

            return $this->severityWeight($right['type'] ?? 'info')
                <=> $this->severityWeight($left['type'] ?? 'info');
        });

        $focus = collect($items)
            ->filter(fn (array $item) => in_array(
                $item['type'] ?? null,
                ['critical', 'warning', 'action'],
                true
            ))
            ->take(4)
            ->map(fn (array $item) => [
                'type' => $item['type'],
                'level' => $item['type'],
                'title' => $item['title'],
                'message' => $item['message'],
                'description' => $item['message'],
            ])
            ->values()
            ->all();

        if (empty($focus)) {
            $focus = collect($items)
                ->take(3)
                ->map(fn (array $item) => [
                    'type' => $item['type'],
                    'level' => $item['type'],
                    'title' => $item['title'],
                    'message' => $item['message'],
                    'description' => $item['message'],
                ])
                ->values()
                ->all();
        }

        return [
            'generated_at' => now()->format('d M Y H:i'),
            'source' => 'local',
            'source_label' => 'Ringkasan HR',
            'mode' => 'local_smart',
            'headline' => $items[0]['title'] ?? 'Ringkasan Kehadiran HR',
            'summary_text' => collect($items)
                ->take(4)
                ->pluck('message')
                ->filter()
                ->unique()
                ->implode("\n\n"),
            'items' => array_slice($items, 0, 8),
            'focus' => $focus,
        ];
    }

    protected function attendanceImportItem(?AttendanceImport $attendanceImport): ?array
    {
        if (! $attendanceImport) {
            return null;
        }

        return [
            'id' => $attendanceImport->id,
            'original_file_name' => $attendanceImport->original_file_name,
            'sheet_name' => $attendanceImport->sheet_name,
            'date_from' => $attendanceImport->date_from?->toDateString(),
            'date_to' => $attendanceImport->date_to?->toDateString(),
            'period_label' => ($attendanceImport->date_from && $attendanceImport->date_to)
                ? $this->formatPeriodLabel(
                    $attendanceImport->date_from->toDateString(),
                    $attendanceImport->date_to->toDateString()
                )
                : null,
            'status' => $attendanceImport->status,
            'total_rows' => (int) $attendanceImport->total_rows,
            'imported_rows' => (int) $attendanceImport->imported_rows,
            'generated_rows' => (int) $attendanceImport->generated_rows,
            'valid_rows' => (int) $attendanceImport->valid_rows,
            'review_rows' => (int) $attendanceImport->review_rows,
            'error_rows' => (int) $attendanceImport->error_rows,
            'duplicate_rows' => (int) $attendanceImport->duplicate_rows,
            'summary' => is_array($attendanceImport->summary)
                ? $attendanceImport->summary
                : [],
            'uploaded_by' => $attendanceImport->uploader?->name,
            'confirmed_by' => $attendanceImport->confirmer?->name,
            'imported_at' => $attendanceImport->imported_at?->toIso8601String(),
            'confirmed_at' => $attendanceImport->confirmed_at?->toIso8601String(),
            'failure_message' => $attendanceImport->failure_message,
        ];
    }

    protected function attendanceMatrixCell(EmployeeAttendance $row): array
    {
        return [
            'id' => $row->id,
            'date' => $row->attendance_date?->toDateString(),
            'code' => $this->attendanceStatusCode($row),
            'label' => $this->attendanceStatusLabel($row),
            'status' => $row->attendance_type,
            'clock_in' => $this->formatTime($row->clock_in),
            'clock_out' => $this->formatTime($row->clock_out),
            'late_minutes' => (int) ($row->late_minutes ?? 0),
            'leave_type' => $row->leave_type,
            'leave_duration' => $row->leave_duration,
            'is_excused' => (bool) $row->is_excused,
            'is_auto_clock_out' => $this->isAutoClockOut($row),
            'remarks' => $row->remarks,
        ];
    }

    protected function attendanceCalendarItem(EmployeeAttendance $row): array
    {
        return array_merge($this->attendanceMatrixCell($row), [
            'day_name' => $row->attendance_date?->translatedFormat('l'),
            'template' => $row->workingHourTemplate?->name
                ?? $row->working_hours_template_raw,
            'scheduled_start_time' => $this->formatTime($row->scheduled_start_time),
            'scheduled_end_time' => $this->formatTime($row->scheduled_end_time),
            'punctuality_status' => $row->punctuality_status,
            'arrival_status' => $row->arrival_status,
            'departure_status' => $row->departure_status,
        ]);
    }

    protected function attendanceExceptionItem(EmployeeAttendance $row): array
    {
        return [
            'id' => $row->id,
            'attendance_date' => $row->attendance_date?->toDateString(),
            'label' => $this->attendanceStatusLabel($row),
            'code' => $this->attendanceStatusCode($row),
            'attendance_type' => $row->attendance_type,
            'arrival_status' => $row->arrival_status,
            'departure_status' => $row->departure_status,
            'late_minutes' => (int) ($row->late_minutes ?? 0),
            'early_leave_minutes' => (int) ($row->early_leave_minutes ?? 0),
            'leave_type' => $row->leave_type,
            'leave_duration' => $row->leave_duration,
            'is_auto_clock_out' => $this->isAutoClockOut($row),
            'remarks' => $row->remarks,
        ];
    }

    protected function attendanceStatusCode(EmployeeAttendance $row): string
    {
        if ($row->attendance_type === self::TYPE_HOLIDAY) {
            return 'PH';
        }

        if ($row->attendance_type === self::TYPE_OFF_DAY) {
            return 'OD';
        }

        if ($this->isUnexcusedAbsent($row)) {
            return 'AB';
        }

        if ($row->attendance_type === self::TYPE_MISSING) {
            return 'MS';
        }

        if ($this->isApprovedLeave($row) && $row->leave_duration === 'full_day') {
            return 'LV';
        }

        if ($this->isApprovedLeave($row) && $row->leave_duration === 'half_day') {
            return 'HL';
        }

        if ($this->isExcusedLate($row)) {
            return 'EL';
        }

        if ($this->isLate($row)) {
            return 'LT';
        }

        if ($this->isOnTime($row)) {
            return 'OT';
        }

        return 'PR';
    }

    protected function attendanceStatusLabel(EmployeeAttendance $row): string
    {
        return match ($this->attendanceStatusCode($row)) {
            'PH' => 'Public Holiday',
            'OD' => 'Off Day',
            'AB' => 'Absent',
            'MS' => 'Missing Attendance',
            'LV' => 'Full-Day Leave',
            'HL' => 'Half-Day Leave',
            'EL' => 'Excused Late',
            'LT' => 'Late',
            'OT' => 'On Time',
            default => 'Present',
        };
    }

    protected function isOnTime(EmployeeAttendance $row): bool
    {
        return ($row->arrival_status ?: $row->punctuality_status) === self::ARRIVAL_ON_TIME;
    }

    protected function isLate(EmployeeAttendance $row): bool
    {
        return ($row->arrival_status ?: $row->punctuality_status) === self::ARRIVAL_LATE;
    }

    protected function isExcusedLate(EmployeeAttendance $row): bool
    {
        return ($row->arrival_status ?: $row->punctuality_status) === self::ARRIVAL_EXCUSED_LATE;
    }

    protected function isApprovedLeave(EmployeeAttendance $row): bool
    {
        return filled($row->leave_type) && (bool) $row->is_excused;
    }

    protected function isUnexcusedAbsent(EmployeeAttendance $row): bool
    {
        return $row->attendance_type === self::TYPE_ABSENT
            && ! $this->isApprovedLeave($row);
    }

    protected function isAutoClockOut(EmployeeAttendance $row): bool
    {
        return (bool) (
            data_get($row->metadata, 'auto_clock_out', false)
            || data_get($row->metadata, 'system.auto_clock_out', false)
            || data_get($row->metadata, 'raw_payload._system.auto_clock_out', false)
        );
    }

    protected function isExceptionRow(EmployeeAttendance $row): bool
    {
        return $this->isLate($row)
            || $this->isExcusedLate($row)
            || $this->isUnexcusedAbsent($row)
            || $row->attendance_type === self::TYPE_MISSING
            || $row->departure_status === self::DEPARTURE_EARLY
            || $this->isAutoClockOut($row);
    }

    protected function buildAttentionReason(array $counts): string
    {
        $parts = [];
        $labels = [
            'absent_days' => 'hari tidak hadir tanpa keterangan',
            'late_days' => 'kali terlambat',
            'early_departure_days' => 'kali pulang lebih awal',
            'auto_clock_out_days' => 'clock out tidak tercatat',
            'unknown_punctuality_days' => 'data waktu datang belum jelas',
        ];

        foreach ($labels as $key => $label) {
            $value = (int) ($counts[$key] ?? 0);

            if ($value > 0) {
                $parts[] = number_format($value) . ' ' . $label;
            }
        }

        return $parts ? implode(' · ', $parts) : 'Ada catatan kehadiran yang perlu diperiksa';
    }

    protected function activeEmployeeCount(): int
    {
        return $this->activeEmployeeCountCache ??=
            Employee::query()->active()->count();
    }

    protected function getWorkTeamOptions(): Collection
    {
        return Employee::query()
            ->active()
            ->whereNotNull('work_team')
            ->where('work_team', '!=', '')
            ->distinct()
            ->orderBy('work_team')
            ->pluck('work_team')
            ->values();
    }

    protected function masterDataHealthScore(
        int $activeEmployees,
        int $employeesWithoutTemplate,
        int $employeesWithoutTeam,
        int $incompleteTemplates
    ): float {
        if ($activeEmployees <= 0) {
            return 0;
        }

        $employeePenalty = ($employeesWithoutTemplate + $employeesWithoutTeam)
            / max($activeEmployees * 2, 1);
        $templatePenalty = min($incompleteTemplates / max($activeEmployees, 1), 1);

        return max(
            0,
            round(100 - (($employeePenalty * 70) + ($templatePenalty * 30)), 1)
        );
    }

    protected function optionalString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    protected function buildChange(
        float|int $current,
        float|int $previous,
        bool $lowerIsBetter = false
    ): array {
        $difference = $current - $previous;
        $direction = match (true) {
            $difference > 0 => 'up',
            $difference < 0 => 'down',
            default => 'flat',
        };
        $performance = match (true) {
            $difference == 0 => 'neutral',
            $lowerIsBetter && $difference < 0 => 'good',
            $lowerIsBetter && $difference > 0 => 'bad',
            ! $lowerIsBetter && $difference > 0 => 'good',
            default => 'bad',
        };

        return [
            'current' => $current,
            'previous' => $previous,
            'difference' => $difference,
            'percentage' => $this->growthPercentage((float) $current, (float) $previous),
            'direction' => $direction,
            'performance' => $performance,
        ];
    }

    protected function buildRateChange(
        float $current,
        float $previous,
        bool $lowerIsBetter = false
    ): array {
        $percentagePoints = round($current - $previous, 1);
        $direction = match (true) {
            $percentagePoints > 0 => 'up',
            $percentagePoints < 0 => 'down',
            default => 'flat',
        };
        $performance = match (true) {
            $percentagePoints == 0.0 => 'neutral',
            $lowerIsBetter && $percentagePoints < 0 => 'good',
            $lowerIsBetter && $percentagePoints > 0 => 'bad',
            ! $lowerIsBetter && $percentagePoints > 0 => 'good',
            default => 'bad',
        };

        return [
            'current' => $current,
            'previous' => $previous,
            'percentage_points' => $percentagePoints,
            'direction' => $direction,
            'performance' => $performance,
        ];
    }

    protected function percentage(
        float|int $numerator,
        float|int $denominator,
        int $precision = 1
    ): float|int {
        if ((float) $denominator <= 0) {
            return 0;
        }

        $value = ($numerator / $denominator) * 100;

        return $precision === 0
            ? (int) round($value)
            : round($value, $precision);
    }

    protected function growthPercentage(float $current, float $previous): float
    {
        if ($previous == 0.0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / abs($previous)) * 100, 1);
    }

    protected function formatPeriodLabel(string $dateFrom, string $dateTo): string
    {
        $from = Carbon::parse($dateFrom);
        $to = Carbon::parse($dateTo);

        if ($from->isSameDay($to)) {
            return $from->translatedFormat('d M Y');
        }

        if ($from->isSameMonth($to)) {
            return $from->translatedFormat('d')
                . '–'
                . $to->translatedFormat('d M Y');
        }

        return $from->translatedFormat('d M Y')
            . ' – '
            . $to->translatedFormat('d M Y');
    }

    protected function formatCompactPeriodLabel(Carbon $dateFrom, Carbon $dateTo): string
    {
        if ($dateFrom->isSameMonth($dateTo)) {
            return $dateFrom->translatedFormat('d')
                . '–'
                . $dateTo->translatedFormat('d M');
        }

        return $dateFrom->translatedFormat('d M')
            . ' – '
            . $dateTo->translatedFormat('d M');
    }

    protected function formatTime(mixed $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        return substr((string) $value, 0, 5);
    }

    protected function summaryItem(
        string $type,
        string $title,
        string $message,
        int $score
    ): array {
        return [
            'type' => $type,
            'level' => $type,
            'title' => $title,
            'message' => $message,
            'description' => $message,
            'score' => $score,
        ];
    }

    protected function severityWeight(string $type): int
    {
        return match ($type) {
            'critical' => 5,
            'warning' => 4,
            'action' => 3,
            'good' => 2,
            'info' => 1,
            default => 0,
        };
    }
}
