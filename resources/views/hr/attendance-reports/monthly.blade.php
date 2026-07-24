@extends('layouts.app-dashboard')

@section('title', 'Laporan Kehadiran Bulanan')

@section('content')
@php
    $monthlySummary = $summary ?? [];
    $employeeRows = collect($employeeSummaries ?? []);
    $weekRows = collect($weeklyBlocks ?? []);
    $healthChart = $attendanceHealthChart ?? [];
    $leaveChart = $leaveDistributionChart ?? [];

    $selectedMonth = $filters['month']
        ?? now()->format('Y-m');

    try {
        $monthAnchor = \Carbon\Carbon::createFromFormat(
            'Y-m',
            $selectedMonth
        )->startOfMonth();
    } catch (\Throwable) {
        $monthAnchor = now()->startOfMonth();
    }

    $previousMonthUrl = route(
        'hr.dashboard.monthly-report',
        array_filter([
            'month' => $monthAnchor
                ->copy()
                ->subMonthNoOverflow()
                ->format('Y-m'),
            'work_team' => $filters['work_team'] ?? null,
        ])
    );

    $nextMonthUrl = route(
        'hr.dashboard.monthly-report',
        array_filter([
            'month' => $monthAnchor
                ->copy()
                ->addMonthNoOverflow()
                ->format('Y-m'),
            'work_team' => $filters['work_team'] ?? null,
        ])
    );

    $formatNumber = static fn ($value): string =>
        number_format((int) $value);

    $formatPercent = static fn ($value): string =>
        number_format((float) $value, 1) . '%';

    $statusClass = static function (?string $code): string {
        return match ($code) {
            'OT' => 'is-on-time',
            'LT' => 'is-late',
            'EL' => 'is-excused-late',
            'HL' => 'is-half-leave',
            'LV' => 'is-full-leave',
            'AB' => 'is-absent',
            'MS' => 'is-missing',
            'OD' => 'is-off-day',
            'PH' => 'is-holiday',
            'PR' => 'is-present',
            default => 'is-empty',
        };
    };

    $routeExists = static fn (string $name): bool =>
        \Illuminate\Support\Facades\Route::has($name);

    $employeeDetailUrl = static function (
        int|string|null $employeeId
    ) use ($routeExists, $selectedMonth): ?string {
        if (! $employeeId) {
            return null;
        }

        if ($routeExists('hr.dashboard.employee-detail')) {
            return route(
                'hr.dashboard.employee-detail',
                [
                    'employee' => $employeeId,
                    'month' => $selectedMonth,
                ]
            );
        }

        if (
            $routeExists(
                'hr.employees.attendance-detail'
            )
        ) {
            return route(
                'hr.employees.attendance-detail',
                [
                    'employee' => $employeeId,
                    'month' => $selectedMonth,
                ]
            );
        }

        return null;
    };

    $leaveLabels = [
        'sick_leave' => 'Sakit',
        'annual_leave' => 'Cuti Tahunan',
        'unpaid_leave' => 'Cuti Tidak Dibayar',
        'permission' => 'Izin',
        'other' => 'Lainnya',
    ];

    $leaveDurationLabels = [
        'full_day' => 'Hari Penuh',
        'half_day' => 'Setengah Hari',
        'custom_hours' => 'Jam Tertentu',
    ];
@endphp

<div class="container-fluid px-4 py-4 hr-monthly-report-page">
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">
                    HR Attendance Report
                </div>

                <h1 class="page-title mb-2">
                    Laporan Kehadiran Bulanan
                </h1>

                <p class="page-subtitle mb-0">
                    Lihat ringkasan kehadiran setiap employee dan detail status per minggu dalam satu periode bulanan.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <a
                    href="{{ route('hr.dashboard') }}"
                    class="btn btn-light btn-modern"
                >
                    <i class="bi bi-arrow-left me-2"></i>
                    Kembali ke Dashboard
                </a>

                
            </div>
        </div>
    </div>

    <form
        method="GET"
        action="{{ route('hr.dashboard.monthly-report') }}"
        class="content-card mb-4 report-filter-card"
        id="monthlyReportFilterForm"
    >
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">
                    Periode Laporan
                </h5>

                <p class="content-card-subtitle mb-0">
                    Pilih bulan dan tim kerja yang ingin ditampilkan.
                </p>
            </div>

            <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis border border-primary-subtle">
                {{ $period['label'] ?? '-' }}
            </span>
        </div>

        <div class="content-card-body">
            <div class="row g-3 align-items-end">
                <div class="col-xl-4 col-md-6">
                    <label
                        for="month"
                        class="form-label fw-semibold"
                    >
                        Bulan
                    </label>

                    <div class="month-navigation">
                        <a
                            href="{{ $previousMonthUrl }}"
                            class="btn btn-outline-secondary"
                            aria-label="Bulan sebelumnya"
                        >
                            <i class="bi bi-chevron-left"></i>
                        </a>

                        <input
                            type="month"
                            class="form-control monthly-filter-auto-submit"
                            id="month"
                            name="month"
                            value="{{ $selectedMonth }}"
                        >

                        <a
                            href="{{ $nextMonthUrl }}"
                            class="btn btn-outline-secondary"
                            aria-label="Bulan berikutnya"
                        >
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6">
                    <label
                        for="work_team"
                        class="form-label fw-semibold"
                    >
                        Tim Kerja
                    </label>

                    <select
                        class="form-select monthly-filter-auto-submit"
                        id="work_team"
                        name="work_team"
                    >
                        <option value="">
                            Semua Tim
                        </option>

                        @foreach (
                            collect($workTeams ?? [])
                            as $team
                        )
                            <option
                                value="{{ $team }}"
                                @selected(
                                    ($filters['work_team'] ?? null)
                                    === $team
                                )
                            >
                                {{ $team }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-4 col-md-12">
                    <div class="d-flex gap-2">
                        <a
                            href="{{ route(
                                'hr.dashboard.monthly-report'
                            ) }}"
                            class="btn btn-danger btn-modern flex-fill"
                        >
                            <i class="bi bi-arrow-counterclockwise me-2"></i>
                            Reset
                        </a>

                        <button
                            type="submit"
                            class="btn btn-primary btn-modern flex-fill"
                            id="monthlyReportFilterButton"
                        >
                            <span class="default-label">
                                <i class="bi bi-funnel-fill me-2"></i>
                                Terapkan
                            </span>

                            <span class="loading-label d-none">
                                <span class="spinner-border spinner-border-sm me-2"></span>
                                Memuat
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <div class="monthly-report-period mb-4">
        <div>
            <div class="monthly-report-period-eyebrow">
                Periode Terpilih
            </div>

            <div class="monthly-report-period-title">
                {{ $period['label'] ?? '-' }}
            </div>
        </div>

        <div class="monthly-report-period-meta">
            <span>
                <i class="bi bi-people-fill"></i>
                {{ $formatNumber(
                    $monthlySummary['employees_covered'] ?? 0
                ) }}
                employee tercakup
            </span>

            <span>
                <i class="bi bi-calendar-check-fill"></i>
                {{ $formatNumber(
                    $monthlySummary['expected_workdays'] ?? 0
                ) }}
                total hari kerja terjadwal
            </span>
        </div>
    </div>

    <div class="row g-3 mb-4 monthly-kpi-grid">
        <div class="col-xxl-2 col-xl-4 col-md-6">
            <div class="monthly-kpi-card">
                <span class="monthly-kpi-icon">
                    <i class="bi bi-people-fill"></i>
                </span>

                <div class="monthly-kpi-label">
                    Employee Tercakup
                </div>

                <div class="monthly-kpi-value">
                    {{ $formatNumber(
                        $monthlySummary[
                            'employees_covered'
                        ] ?? 0
                    ) }}
                </div>

                <div class="monthly-kpi-help">
                    Employee yang memiliki data pada bulan ini.
                </div>
            </div>
        </div>

        <div class="col-xxl-2 col-xl-4 col-md-6">
            <div class="monthly-kpi-card">
                <span class="monthly-kpi-icon">
                    <i class="bi bi-person-check-fill"></i>
                </span>

                <div class="monthly-kpi-label">
                    Tingkat Kehadiran
                </div>

                <div class="monthly-kpi-value">
                    {{ $formatPercent(
                        $monthlySummary[
                            'presence_rate'
                        ] ?? 0
                    ) }}
                </div>

                <div class="monthly-kpi-help">
                    Hari hadir dari seluruh hari kerja terjadwal.
                </div>
            </div>
        </div>

        <div class="col-xxl-2 col-xl-4 col-md-6">
            <div class="monthly-kpi-card">
                <span class="monthly-kpi-icon">
                    <i class="bi bi-alarm-fill"></i>
                </span>

                <div class="monthly-kpi-label">
                    Ketepatan Waktu
                </div>

                <div class="monthly-kpi-value">
                    {{ $formatPercent(
                        $monthlySummary[
                            'on_time_rate'
                        ] ?? 0
                    ) }}
                </div>

                <div class="monthly-kpi-help">
                    Hari tepat waktu dari seluruh hari hadir.
                </div>
            </div>
        </div>

        <div class="col-xxl-2 col-xl-4 col-md-6">
            <div class="monthly-kpi-card">
                <span class="monthly-kpi-icon">
                    <i class="bi bi-calendar2-minus-fill"></i>
                </span>

                <div class="monthly-kpi-label">
                    Cuti / Izin
                </div>

                <div class="monthly-kpi-value">
                    {{ $formatNumber(
                        $monthlySummary[
                            'approved_leave_days'
                        ] ?? 0
                    ) }}
                </div>

                <div class="monthly-kpi-help">
                    {{ $formatNumber(
                        $monthlySummary[
                            'full_day_leave_days'
                        ] ?? 0
                    ) }}
                    hari penuh ·
                    {{ $formatNumber(
                        $monthlySummary[
                            'half_day_leave_days'
                        ] ?? 0
                    ) }}
                    setengah hari.
                </div>
            </div>
        </div>

        <div class="col-xxl-2 col-xl-4 col-md-6">
            <div class="monthly-kpi-card">
                <span class="monthly-kpi-icon">
                    <i class="bi bi-person-x-fill"></i>
                </span>

                <div class="monthly-kpi-label">
                    Tidak Hadir
                </div>

                <div class="monthly-kpi-value">
                    {{ $formatNumber(
                        $monthlySummary['absent_days'] ?? 0
                    ) }}
                </div>

                <div class="monthly-kpi-help">
                    Tidak hadir tanpa cuti atau izin yang tercatat.
                </div>
            </div>
        </div>

        <div class="col-xxl-2 col-xl-4 col-md-6">
            <div class="monthly-kpi-card">
                <span class="monthly-kpi-icon">
                    <i class="bi bi-box-arrow-right"></i>
                </span>

                <div class="monthly-kpi-label">
                    Clock Out Diisi Otomatis
                </div>

                <div class="monthly-kpi-value">
                    {{ $formatNumber(
                        $monthlySummary[
                            'auto_clock_out_days'
                        ] ?? 0
                    ) }}
                </div>

                <div class="monthly-kpi-help">
                    Jam pulang tidak tercatat dan diisi berdasarkan jadwal kerja.
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-6">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">
                            Ringkasan Status Kehadiran
                        </h5>

                        <p class="content-card-subtitle mb-0">
                            Distribusi status kehadiran selama bulan yang dipilih.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="monthly-chart-shell">
                        <canvas id="monthlyAttendanceHealthChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">
                            Ringkasan Cuti & Izin
                        </h5>

                        <p class="content-card-subtitle mb-0">
                            Jenis cuti dan izin yang tercatat selama bulan ini.
                        </p>
                    </div>

                    <span class="badge rounded-pill bg-light text-dark border">
                        {{ $formatNumber(
                            $leaveChart['total'] ?? 0
                        ) }}
                        catatan
                    </span>
                </div>

                <div class="content-card-body">
                    <div class="monthly-chart-shell">
                        <canvas id="monthlyLeaveDistributionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="content-card mb-4 employee-summary-section">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">
                    Ringkasan per Employee
                </h5>

                <p class="content-card-subtitle mb-0">
                    Bandingkan tingkat kehadiran, ketepatan waktu, cuti, dan catatan lainnya untuk setiap employee.
                </p>
            </div>

            <span class="badge rounded-pill bg-light text-dark border">
                <span id="visibleEmployeeSummaryCount">
                    {{ $formatNumber($employeeRows->count()) }}
                </span>
                employee
            </span>
        </div>

        <div class="content-card-body">
            <div class="employee-summary-toolbar mb-3">
                <div class="employee-search-box">
                    <i class="bi bi-search"></i>

                    <input
                        type="search"
                        class="form-control"
                        id="employeeSummarySearch"
                        placeholder="Cari nama, nomor employee, atau tim..."
                        autocomplete="off"
                    >
                </div>

                
            </div>

            <div
                class="employee-summary-list"
                id="employeeSummaryList"
            >
                @forelse ($employeeRows as $employee)
                    @php
                        $detailUrl = $employeeDetailUrl(
                            $employee['employee_id'] ?? null
                        );

                        $searchText = mb_strtolower(
                            implode(' ', array_filter([
                                $employee['employee_name'] ?? null,
                                $employee['employee_number'] ?? null,
                                $employee['work_team'] ?? null,
                            ]))
                        );
                    @endphp

                    <article
                        class="employee-summary-card"
                        data-employee-summary
                        data-search-text="{{ $searchText }}"
                    >
                        <div class="employee-summary-identity">
                            <span class="employee-summary-avatar">
                                {{ mb_strtoupper(
                                    mb_substr(
                                        $employee['employee_name']
                                            ?? '?',
                                        0,
                                        1
                                    )
                                ) }}
                            </span>

                            <div class="min-w-0">
                                <div class="employee-summary-name">
                                    {{ $employee[
                                        'employee_name'
                                    ] ?? 'Employee belum dikenali' }}
                                </div>

                                <div class="employee-summary-meta">
                                    {{ $employee[
                                        'employee_number'
                                    ] ?? 'Nomor belum tersedia' }}
                                    ·
                                    {{ $employee[
                                        'work_team'
                                    ] ?? 'Tim belum ditentukan' }}
                                </div>
                            </div>
                        </div>

                        <div class="employee-summary-rates">
                            <div class="employee-rate-box is-presence">
                                <span>Tingkat Kehadiran</span>

                                <strong>
                                    {{ $formatPercent(
                                        $employee[
                                            'presence_rate'
                                        ] ?? 0
                                    ) }}
                                </strong>

                                <small>
                                    {{ $formatNumber(
                                        $employee[
                                            'present_days'
                                        ] ?? 0
                                    ) }}
                                    dari
                                    {{ $formatNumber(
                                        $employee[
                                            'expected_workdays'
                                        ] ?? 0
                                    ) }}
                                    hari kerja
                                </small>
                            </div>

                            <div class="employee-rate-box is-punctuality">
                                <span>Ketepatan Waktu</span>

                                <strong>
                                    {{ $formatPercent(
                                        $employee[
                                            'on_time_rate'
                                        ] ?? 0
                                    ) }}
                                </strong>

                                <small>
                                    {{ $formatNumber(
                                        $employee[
                                            'on_time_days'
                                        ] ?? 0
                                    ) }}
                                    hari tepat waktu
                                </small>
                            </div>
                        </div>

                        <div class="employee-summary-stats">
                            <div>
                                <span>Hadir</span>
                                <strong>
                                    {{ $formatNumber(
                                        $employee[
                                            'present_days'
                                        ] ?? 0
                                    ) }}
                                </strong>
                            </div>

                            <div>
                                <span>Terlambat</span>
                                <strong>
                                    {{ $formatNumber(
                                        $employee[
                                            'late_days'
                                        ] ?? 0
                                    ) }}
                                </strong>
                            </div>

                            <div>
                                <span>Cuti / Izin</span>
                                <strong>
                                    {{ $formatNumber(
                                        $employee[
                                            'approved_leave_days'
                                        ] ?? 0
                                    ) }}
                                </strong>
                            </div>

                            <div>
                                <span>Tidak Hadir</span>
                                <strong>
                                    {{ $formatNumber(
                                        $employee[
                                            'absent_days'
                                        ] ?? 0
                                    ) }}
                                </strong>
                            </div>

                            <div>
                                <span>Data Belum Lengkap</span>
                                <strong>
                                    {{ $formatNumber(
                                        $employee[
                                            'missing_days'
                                        ] ?? 0
                                    ) }}
                                </strong>
                            </div>

                            <div>
                                <span>Clock Out Otomatis</span>
                                <strong>
                                    {{ $formatNumber(
                                        $employee[
                                            'auto_clock_out_days'
                                        ] ?? 0
                                    ) }}
                                </strong>
                            </div>
                        </div>

                        @if ($detailUrl)
                            <a
                                href="{{ $detailUrl }}"
                                class="employee-summary-action"
                            >
                                Lihat Detail
                                <i class="bi bi-arrow-right"></i>
                            </a>
                        @endif
                    </article>
                @empty
                    <div class="empty-state-box">
                        <div class="empty-state-icon">
                            <i class="bi bi-people-fill"></i>
                        </div>

                        <h5 class="empty-state-title">
                            Belum ada data employee
                        </h5>

                        <p class="empty-state-text mb-0">
                            Belum ada attendance final untuk bulan dan tim yang dipilih.
                        </p>
                    </div>
                @endforelse
            </div>

            <div
                class="empty-state-box d-none"
                id="employeeSummarySearchEmpty"
            >
                <div class="empty-state-icon">
                    <i class="bi bi-search"></i>
                </div>

                <h5 class="empty-state-title">
                    Employee tidak ditemukan
                </h5>

                <p class="empty-state-text mb-0">
                    Coba gunakan nama, nomor employee, atau tim yang berbeda.
                </p>
            </div>
        </div>
    </div>

    <div class="dashboard-section-label mb-3">
        <div class="dashboard-section-eyebrow">
            Detail Kehadiran
        </div>

        <h4 class="dashboard-section-title mb-1">
            Matriks Kehadiran per Minggu
        </h4>

        <p class="dashboard-section-subtitle mb-0">
            Menampilkan status kehadiran setiap employee pada setiap tanggal dalam bulan yang dipilih. 
        </p>
    </div>

    <div class="monthly-status-legend mb-4">
        @foreach ([
            ['code' => 'OT', 'label' => 'Tepat Waktu'],
            ['code' => 'LT', 'label' => 'Terlambat'],
            ['code' => 'EL', 'label' => 'Terlambat Berizin'],
            ['code' => 'HL', 'label' => 'Cuti/Izin Setengah Hari'],
            ['code' => 'LV', 'label' => 'Cuti/Izin Hari Penuh'],
            ['code' => 'AB', 'label' => 'Tidak Hadir'],
            ['code' => 'MS', 'label' => 'Data Belum Lengkap'],
            ['code' => 'OD', 'label' => 'Hari Libur Kerja'],
            ['code' => 'PH', 'label' => 'Hari Libur'],
            ['code' => 'PR', 'label' => 'Hadir'],
            ['code' => '-', 'label' => 'Belum Ada Data Final'],
        ] as $legend)
            <div class="monthly-status-legend-item">
                <span class="monthly-status-code {{ $statusClass($legend['code']) }}">
                    {{ $legend['code'] }}
                </span>

                <span>{{ $legend['label'] }}</span>
            </div>
        @endforeach

        <div class="monthly-status-legend-item">
            <span class="auto-clock-out-indicator">
                <i class="bi bi-box-arrow-right"></i>
            </span>

            <span>Clock out diisi otomatis</span>
        </div>
    </div>

    <div class="weekly-matrix-list">
        @forelse ($weekRows as $week)
            @php
                $weekDates = collect($week['dates'] ?? []);
                $weekEmployees = collect(
                    $week['employees'] ?? []
                );

                $weekColumnCount = max(
                    1,
                    $weekDates->count()
                );
            @endphp

            <section class="weekly-matrix-card">
                <div class="weekly-matrix-header">
                    <div>
                        <div class="weekly-matrix-eyebrow">
                            Minggu {{ $week['week_number'] ?? '-' }}
                        </div>

                        <h5 class="weekly-matrix-title mb-0">
                            {{ $week['label'] ?? '-' }}
                        </h5>
                    </div>

                    <span class="badge rounded-pill bg-light text-dark border">
                        {{ $formatNumber(
                            $weekEmployees->count()
                        ) }}
                        employee
                    </span>
                </div>

                <div class="weekly-matrix-body">
                    <div
                        class="weekly-matrix-grid weekly-matrix-grid-header"
                        style="--week-columns: {{ $weekColumnCount }};"
                    >
                        <div class="weekly-matrix-employee-heading">
                            Employee
                        </div>

                        @foreach ($weekDates as $date)
                            <div class="weekly-matrix-date-heading">
                                <span>
                                    {{ $date['day'] ?? '-' }}
                                </span>

                                <strong>
                                    {{ $date['day_number'] ?? '-' }}
                                </strong>
                            </div>
                        @endforeach
                    </div>

                    @forelse ($weekEmployees as $employee)
                        @php
                            $detailUrl = $employeeDetailUrl(
                                $employee['employee_id'] ?? null
                            );
                        @endphp

                        <div
                            class="weekly-matrix-grid weekly-matrix-row"
                            style="--week-columns: {{ $weekColumnCount }};"
                        >
                            <div class="weekly-matrix-employee">
                                <div class="weekly-matrix-employee-name">
                                    {{ $employee[
                                        'employee_name'
                                    ] ?? 'Employee belum dikenali' }}
                                </div>

                                <div class="weekly-matrix-employee-meta">
                                    {{ $employee[
                                        'employee_number'
                                    ] ?? 'Nomor belum tersedia' }}
                                    ·
                                    {{ $employee[
                                        'work_team'
                                    ] ?? 'Tim belum ditentukan' }}
                                </div>

                                @if ($detailUrl)
                                    <a href="{{ $detailUrl }}">
                                        Lihat detail
                                    </a>
                                @endif
                            </div>

                            @foreach (
                                collect($employee['cells'] ?? [])
                                as $cell
                            )
                                @php
                                    $payload = [
                                        'employee_name' =>
                                            $employee[
                                                'employee_name'
                                            ] ?? null,
                                        'employee_number' =>
                                            $employee[
                                                'employee_number'
                                            ] ?? null,
                                        'work_team' =>
                                            $employee[
                                                'work_team'
                                            ] ?? null,
                                        'date' =>
                                            $cell['date'] ?? null,
                                        'code' =>
                                            $cell['code'] ?? '-',
                                        'label' =>
                                            $cell['label']
                                                ?? 'Belum Ada Data Final',
                                        'status' =>
                                            $cell['status']
                                                ?? 'no_record',
                                        'clock_in' =>
                                            $cell['clock_in'] ?? null,
                                        'clock_out' =>
                                            $cell['clock_out'] ?? null,
                                        'late_minutes' =>
                                            $cell[
                                                'late_minutes'
                                            ] ?? 0,
                                        'leave_type' =>
                                            $cell[
                                                'leave_type'
                                            ] ?? null,
                                        'leave_duration' =>
                                            $cell[
                                                'leave_duration'
                                            ] ?? null,
                                        'is_excused' =>
                                            $cell[
                                                'is_excused'
                                            ] ?? false,
                                        'is_auto_clock_out' =>
                                            $cell[
                                                'is_auto_clock_out'
                                            ] ?? false,
                                        'remarks' =>
                                            $cell['remarks'] ?? null,
                                    ];

                                    $encodedPayload = base64_encode(
                                        json_encode(
                                            $payload,
                                            JSON_UNESCAPED_UNICODE
                                                | JSON_UNESCAPED_SLASHES
                                        )
                                    );
                                @endphp

                                <button
                                    type="button"
                                    class="weekly-matrix-cell {{ $statusClass($cell['code'] ?? '-') }}"
                                    data-attendance-cell="{{ $encodedPayload }}"
                                    title="{{ $cell['label'] ?? 'Belum Ada Data Final' }}"
                                >
                                    <span class="weekly-matrix-cell-code">
                                        {{ $cell['code'] ?? '-' }}
                                    </span>

                                    @if (
                                        $cell[
                                            'is_auto_clock_out'
                                        ] ?? false
                                    )
                                        <span
                                            class="weekly-matrix-auto-out"
                                            title="Clock out diisi otomatis"
                                        >
                                            <i class="bi bi-box-arrow-right"></i>
                                        </span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    @empty
                        <div class="weekly-matrix-empty">
                            Belum ada employee pada minggu ini.
                        </div>
                    @endforelse
                </div>
            </section>
        @empty
            <div class="content-card">
                <div class="content-card-body">
                    <div class="empty-state-box">
                        <div class="empty-state-icon">
                            <i class="bi bi-calendar3"></i>
                        </div>

                        <h5 class="empty-state-title">
                            Matriks kehadiran belum tersedia
                        </h5>

                        <p class="empty-state-text mb-0">
                            Belum ada attendance final pada bulan yang dipilih.
                        </p>
                    </div>
                </div>
            </div>
        @endforelse
    </div>
</div>

<div
    class="modal fade"
    id="attendanceCellDetailModal"
    tabindex="-1"
    aria-labelledby="attendanceCellDetailModalLabel"
    aria-hidden="true"
>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content monthly-detail-modal">
            <div class="modal-header">
                <div>
                    <div class="modal-eyebrow">
                        Detail Kehadiran
                    </div>

                    <h5
                        class="modal-title"
                        id="attendanceCellDetailModalLabel"
                    >
                        Attendance Employee
                    </h5>
                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Tutup"
                ></button>
            </div>

            <div class="modal-body">
                <div
                    class="monthly-detail-status"
                    id="monthlyDetailStatus"
                >
                    <span
                        class="monthly-status-code"
                        id="monthlyDetailStatusCode"
                    >
                        -
                    </span>

                    <div>
                        <div class="monthly-detail-status-label">
                            Status Kehadiran
                        </div>

                        <strong id="monthlyDetailStatusLabel">
                            Belum Ada Data Final
                        </strong>
                    </div>
                </div>

                <div class="monthly-detail-grid mt-3">
                    <div>
                        <span>Employee</span>
                        <strong id="monthlyDetailEmployee">
                            -
                        </strong>
                    </div>

                    <div>
                        <span>Tim Kerja</span>
                        <strong id="monthlyDetailTeam">
                            -
                        </strong>
                    </div>

                    <div>
                        <span>Tanggal</span>
                        <strong id="monthlyDetailDate">
                            -
                        </strong>
                    </div>

                    <div>
                        <span>Nomor Employee</span>
                        <strong id="monthlyDetailNumber">
                            -
                        </strong>
                    </div>

                    <div>
                        <span>Clock In</span>
                        <strong id="monthlyDetailClockIn">
                            -
                        </strong>
                    </div>

                    <div>
                        <span>Clock Out</span>
                        <strong id="monthlyDetailClockOut">
                            -
                        </strong>
                    </div>

                    <div>
                        <span>Keterlambatan</span>
                        <strong id="monthlyDetailLate">
                            -
                        </strong>
                    </div>

                    <div>
                        <span>Cuti / Izin</span>
                        <strong id="monthlyDetailLeave">
                            -
                        </strong>
                    </div>
                </div>

                <div
                    class="monthly-detail-notice d-none mt-3"
                    id="monthlyDetailAutoClockOut"
                >
                    <i class="bi bi-info-circle-fill"></i>

                    <span>
                        Jam pulang tidak tercatat dan diisi otomatis berdasarkan jadwal kerja.
                    </span>
                </div>

                <div class="monthly-detail-remarks mt-3">
                    <span>Catatan</span>

                    <p
                        class="mb-0"
                        id="monthlyDetailRemarks"
                    >
                        Tidak ada catatan.
                    </p>
                </div>
            </div>

            <div class="modal-footer">
                <button
                    type="button"
                    class="btn btn-sm btn-secondary btn-modern"
                    data-bs-dismiss="modal"
                >
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .hr-monthly-report-page {
        --monthly-purple: #5B3E8E;
        --monthly-purple-dark: #493174;
        --monthly-purple-soft: #f2edf8;
        --monthly-yellow: #FFBE04;
        --monthly-green: #3B8E4D;
        --monthly-red: #c2414b;
        --monthly-orange: #dc762a;
        --monthly-blue: #2f6da5;
        --monthly-text: #2f2938;
        --monthly-muted: #756d80;
        --monthly-border: #e8e3ed;
        --monthly-surface: #ffffff;
        --monthly-surface-soft: #faf9fc;
    }

    .hr-monthly-report-page .min-w-0 {
        min-width: 0;
    }

    .month-navigation {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        gap: .5rem;
    }

    .month-navigation .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 2.75rem;
    }

    .monthly-report-period {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 1.1rem;
        color: #ffffff;
        background: var(--monthly-purple);
        border-radius: 1rem;
        box-shadow: 0 14px 32px rgba(70, 46, 105, .16);
    }

    .monthly-report-period-eyebrow {
        color: rgba(255, 255, 255, .72);
        font-size: .7rem;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .monthly-report-period-title {
        margin-top: .18rem;
        font-size: 1.1rem;
        font-weight: 900;
    }

    .monthly-report-period-meta {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: .65rem;
        flex-wrap: wrap;
    }

    .monthly-report-period-meta span {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .48rem .65rem;
        color: rgba(255, 255, 255, .92);
        background: rgba(255, 255, 255, .12);
        border: 1px solid rgba(255, 255, 255, .16);
        border-radius: 999px;
        font-size: .7rem;
        font-weight: 750;
    }

    .monthly-kpi-card {
        display: flex;
        flex-direction: column;
        min-height: 100%;
        padding: 1rem;
        background: #ffffff;
        border: 1px solid var(--monthly-border);
        border-radius: 1rem;
        box-shadow: 0 8px 24px rgba(48, 34, 68, .045);
    }

    .monthly-kpi-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.3rem;
        height: 2.3rem;
        color: var(--monthly-purple);
        background: var(--monthly-purple-soft);
        border-radius: .75rem;
        font-size: 1rem;
    }

    .monthly-kpi-label {
        margin-top: .75rem;
        color: var(--monthly-muted);
        font-size: .72rem;
        font-weight: 800;
        line-height: 1.3;
    }

    .monthly-kpi-value {
        margin-top: .45rem;
        color: var(--monthly-text);
        font-size: 1.55rem;
        font-weight: 900;
        line-height: 1;
    }

    .monthly-kpi-help {
        margin-top: auto;
        padding-top: .75rem;
        color: var(--monthly-muted);
        font-size: .69rem;
        line-height: 1.45;
    }

    .monthly-chart-shell {
        position: relative;
        height: 300px;
    }

    .employee-summary-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .employee-search-box {
        position: relative;
        flex: 1 1 420px;
        max-width: 520px;
    }

    .employee-search-box i {
        position: absolute;
        top: 50%;
        left: .85rem;
        z-index: 2;
        color: var(--monthly-muted);
        transform: translateY(-50%);
    }

    .employee-search-box .form-control {
        padding-left: 2.35rem;
    }

    .employee-summary-note {
        display: inline-flex;
        align-items: flex-start;
        gap: .45rem;
        max-width: 390px;
        color: var(--monthly-muted);
        font-size: .7rem;
        line-height: 1.4;
    }

    .employee-summary-note i {
        margin-top: .06rem;
        color: var(--monthly-purple);
    }

    .employee-summary-list {
        display: grid;
        gap: .8rem;
    }

    .employee-summary-card {
        display: grid;
        grid-template-columns:
            minmax(190px, 1.15fr)
            minmax(260px, 1.3fr)
            minmax(360px, 1.7fr)
            auto;
        align-items: center;
        gap: .9rem;
        padding: .9rem;
        background: #ffffff;
        border: 1px solid var(--monthly-border);
        border-radius: .9rem;
        transition:
            border-color .18s ease,
            box-shadow .18s ease,
            transform .18s ease;
    }

    .employee-summary-card:hover {
        border-color: #d6c9e5;
        box-shadow: 0 10px 28px rgba(65, 43, 95, .07);
        transform: translateY(-1px);
    }

    .employee-summary-identity {
        display: flex;
        align-items: center;
        gap: .65rem;
        min-width: 0;
    }

    .employee-summary-avatar {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.4rem;
        height: 2.4rem;
        flex: 0 0 2.4rem;
        color: var(--monthly-purple);
        background: var(--monthly-purple-soft);
        border-radius: .8rem;
        font-size: .88rem;
        font-weight: 900;
    }

    .employee-summary-name {
        color: var(--monthly-text);
        font-size: .8rem;
        font-weight: 850;
        overflow-wrap: anywhere;
    }

    .employee-summary-meta {
        margin-top: .12rem;
        color: var(--monthly-muted);
        font-size: .65rem;
        line-height: 1.4;
    }

    .employee-summary-rates {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .55rem;
    }

    .employee-rate-box {
        display: grid;
        gap: .12rem;
        padding: .65rem .7rem;
        background: var(--monthly-surface-soft);
        border: 1px solid var(--monthly-border);
        border-radius: .75rem;
    }

    .employee-rate-box span {
        color: var(--monthly-muted);
        font-size: .61rem;
        font-weight: 750;
    }

    .employee-rate-box strong {
        color: var(--monthly-purple);
        font-size: .93rem;
        font-weight: 900;
    }

    .employee-rate-box small {
        color: var(--monthly-muted);
        font-size: .58rem;
        line-height: 1.35;
    }

    .employee-rate-box.is-presence {
        background: #f2f8f3;
        border-color: #d2e6d6;
    }

    .employee-rate-box.is-presence strong {
        color: var(--monthly-green);
    }

    .employee-summary-stats {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: .4rem;
    }

    .employee-summary-stats div {
        display: grid;
        align-content: center;
        gap: .08rem;
        min-height: 3.5rem;
        padding: .45rem;
        background: var(--monthly-surface-soft);
        border: 1px solid var(--monthly-border);
        border-radius: .65rem;
        text-align: center;
    }

    .employee-summary-stats span {
        color: var(--monthly-muted);
        font-size: .55rem;
        font-weight: 700;
        line-height: 1.25;
    }

    .employee-summary-stats strong {
        color: var(--monthly-text);
        font-size: .78rem;
    }

    .employee-summary-action {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        color: var(--monthly-purple);
        font-size: .68rem;
        font-weight: 850;
        white-space: nowrap;
        text-decoration: none;
    }

    .monthly-status-legend {
        display: flex;
        align-items: center;
        gap: .55rem;
        flex-wrap: wrap;
        padding: .85rem;
        background: #ffffff;
        border: 1px solid var(--monthly-border);
        border-radius: .9rem;
    }

    .monthly-status-legend-item {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        color: var(--monthly-muted);
        font-size: .65rem;
        font-weight: 700;
    }

    .monthly-status-code {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 2rem;
        height: 1.65rem;
        padding: 0 .35rem;
        border: 1px solid transparent;
        border-radius: .55rem;
        font-size: .62rem;
        font-weight: 900;
    }

    .weekly-matrix-list {
        display: grid;
        gap: 1rem;
    }

    .weekly-matrix-card {
        overflow: hidden;
        background: #ffffff;
        border: 1px solid var(--monthly-border);
        border-radius: 1rem;
        box-shadow: 0 8px 24px rgba(48, 34, 68, .04);
    }

    .weekly-matrix-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: .9rem 1rem;
        background: #faf9fc;
        border-bottom: 1px solid var(--monthly-border);
    }

    .weekly-matrix-eyebrow {
        color: var(--monthly-purple);
        font-size: .65rem;
        font-weight: 850;
        letter-spacing: .07em;
        text-transform: uppercase;
    }

    .weekly-matrix-title {
        margin-top: .12rem;
        color: var(--monthly-text);
        font-size: .88rem;
        font-weight: 850;
    }

    .weekly-matrix-body {
        padding: .85rem;
    }

    .weekly-matrix-grid {
        display: grid;
        grid-template-columns:
            minmax(180px, 1.45fr)
            repeat(
                var(--week-columns),
                minmax(62px, 1fr)
            );
        gap: .45rem;
        align-items: stretch;
    }

    .weekly-matrix-grid + .weekly-matrix-grid {
        margin-top: .5rem;
    }

    .weekly-matrix-grid-header {
        padding-bottom: .5rem;
        border-bottom: 1px solid var(--monthly-border);
    }

    .weekly-matrix-employee-heading {
        display: flex;
        align-items: flex-end;
        color: var(--monthly-muted);
        font-size: .65rem;
        font-weight: 800;
    }

    .weekly-matrix-date-heading {
        display: grid;
        place-items: center;
        gap: .1rem;
        min-height: 2.65rem;
        color: var(--monthly-muted);
        background: #faf9fc;
        border: 1px solid var(--monthly-border);
        border-radius: .65rem;
        text-align: center;
    }

    .weekly-matrix-date-heading span {
        font-size: .58rem;
        font-weight: 750;
        text-transform: uppercase;
    }

    .weekly-matrix-date-heading strong {
        color: var(--monthly-text);
        font-size: .8rem;
    }

    .weekly-matrix-row {
        padding-bottom: .5rem;
        border-bottom: 1px solid #f0edf2;
    }

    .weekly-matrix-row:last-child {
        padding-bottom: 0;
        border-bottom: 0;
    }

    .weekly-matrix-employee {
        display: grid;
        align-content: center;
        min-width: 0;
        padding: .55rem .65rem;
        background: #ffffff;
        border: 1px solid var(--monthly-border);
        border-radius: .7rem;
    }

    .weekly-matrix-employee-name {
        color: var(--monthly-text);
        font-size: .72rem;
        font-weight: 850;
        overflow-wrap: anywhere;
    }

    .weekly-matrix-employee-meta {
        margin-top: .12rem;
        color: var(--monthly-muted);
        font-size: .57rem;
        line-height: 1.35;
    }

    .weekly-matrix-employee a {
        width: fit-content;
        margin-top: .3rem;
        color: var(--monthly-purple);
        font-size: .57rem;
        font-weight: 800;
        text-decoration: none;
    }

    .weekly-matrix-cell {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 0;
        min-height: 3.65rem;
        padding: .45rem;
        border: 1px solid transparent;
        border-radius: .7rem;
        cursor: pointer;
        transition:
            transform .16s ease,
            box-shadow .16s ease,
            border-color .16s ease;
    }

    .weekly-matrix-cell:hover {
        z-index: 2;
        transform: translateY(-1px);
        box-shadow: 0 8px 20px rgba(56, 38, 82, .12);
    }

    .weekly-matrix-cell:focus-visible {
        outline: 3px solid rgba(91, 62, 142, .22);
        outline-offset: 2px;
    }

    .weekly-matrix-cell-code {
        font-size: .7rem;
        font-weight: 900;
    }

    .weekly-matrix-auto-out {
        position: absolute;
        right: .28rem;
        bottom: .25rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1rem;
        height: 1rem;
        color: #ffffff;
        background: #5d6670;
        border-radius: 50%;
        font-size: .52rem;
    }

    .auto-clock-out-indicator {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.65rem;
        height: 1.65rem;
        color: #ffffff;
        background: #5d6670;
        border-radius: .55rem;
        font-size: .7rem;
    }

    .weekly-matrix-empty {
        padding: 1rem;
        color: var(--monthly-muted);
        background: #faf9fc;
        border-radius: .75rem;
        font-size: .72rem;
        text-align: center;
    }

    .is-on-time {
        color: #2f7740;
        background: #edf8f0;
        border-color: #cbe3d1;
    }

    .is-late {
        color: #735600;
        background: #fff8df;
        border-color: #efdc99;
    }

    .is-excused-late {
        color: #5b3e8e;
        background: #f2edf8;
        border-color: #d9cdea;
    }

    .is-half-leave {
        color: #6d4a95;
        background: #f5effb;
        border-color: #dfd1ed;
    }

    .is-full-leave {
        color: #56357f;
        background: #eee6f6;
        border-color: #d5c4e6;
    }

    .is-absent {
        color: #a33b44;
        background: #fceff0;
        border-color: #efc9cd;
    }

    .is-missing {
        color: #9d4f14;
        background: #fff2e8;
        border-color: #efcfb6;
    }

    .is-off-day {
        color: #665f6c;
        background: #f1eff3;
        border-color: #ddd9e1;
    }

    .is-holiday {
        color: #245d8e;
        background: #edf5fa;
        border-color: #c9dfec;
    }

    .is-present {
        color: #2f7740;
        background: #f0f8f2;
        border-color: #cbe3d1;
    }

    .is-empty {
        color: #8b8493;
        background: #faf9fb;
        border-color: #e7e2ea;
    }

    .monthly-detail-modal {
        overflow: hidden;
        border: 0;
        border-radius: 1rem;
    }

    .monthly-detail-modal .modal-header {
        padding: 1rem 1.1rem;
        color: #ffffff;
        background: var(--monthly-purple);
        border-bottom: 0;
    }

    .monthly-detail-modal .modal-header .btn-close {
        filter: invert(1);
    }

    .monthly-detail-modal .modal-eyebrow {
        color: rgba(255, 255, 255, .72);
        font-size: .65rem;
        font-weight: 850;
        letter-spacing: .07em;
        text-transform: uppercase;
    }

    .monthly-detail-modal .modal-title {
        margin-top: .15rem;
        font-size: .95rem;
        font-weight: 850;
    }

    .monthly-detail-status {
        display: flex;
        align-items: center;
        gap: .7rem;
        padding: .8rem;
        background: #faf9fc;
        border: 1px solid var(--monthly-border);
        border-radius: .8rem;
    }

    .monthly-detail-status-label {
        color: var(--monthly-muted);
        font-size: .62rem;
        font-weight: 750;
    }

    .monthly-detail-status strong {
        display: block;
        margin-top: .1rem;
        color: var(--monthly-text);
        font-size: .8rem;
    }

    .monthly-detail-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .65rem;
    }

    .monthly-detail-grid div {
        display: grid;
        gap: .12rem;
        padding: .65rem .7rem;
        background: #ffffff;
        border: 1px solid var(--monthly-border);
        border-radius: .7rem;
    }

    .monthly-detail-grid span,
    .monthly-detail-remarks > span {
        color: var(--monthly-muted);
        font-size: .62rem;
        font-weight: 750;
    }

    .monthly-detail-grid strong {
        color: var(--monthly-text);
        font-size: .76rem;
        overflow-wrap: anywhere;
    }

    .monthly-detail-notice {
        display: flex;
        align-items: flex-start;
        gap: .5rem;
        padding: .7rem .75rem;
        color: #4e5862;
        background: #f1f3f5;
        border: 1px solid #d9dde1;
        border-radius: .75rem;
        font-size: .68rem;
        line-height: 1.45;
    }

    .monthly-detail-notice i {
        margin-top: .05rem;
    }

    .monthly-detail-remarks {
        display: grid;
        gap: .3rem;
        padding: .7rem .75rem;
        background: #faf9fc;
        border: 1px solid var(--monthly-border);
        border-radius: .75rem;
    }

    .monthly-detail-remarks p {
        color: var(--monthly-text);
        font-size: .72rem;
        line-height: 1.5;
        overflow-wrap: anywhere;
    }

    @media (max-width: 1399.98px) {
        .employee-summary-card {
            grid-template-columns:
                minmax(190px, 1fr)
                minmax(240px, 1.2fr)
                minmax(0, 1.55fr);
        }

        .employee-summary-action {
            grid-column: 1 / -1;
            justify-self: end;
        }

        .employee-summary-stats {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 1199.98px) {
        .employee-summary-card {
            grid-template-columns:
                minmax(190px, .9fr)
                minmax(0, 1.1fr);
        }

        .employee-summary-stats {
            grid-column: 1 / -1;
            grid-template-columns: repeat(6, minmax(0, 1fr));
        }

        .weekly-matrix-grid {
            grid-template-columns:
                minmax(155px, 1.25fr)
                repeat(
                    var(--week-columns),
                    minmax(54px, 1fr)
                );
        }
    }

    @media (max-width: 991.98px) {
        .monthly-report-period,
        .employee-summary-toolbar {
            align-items: flex-start;
            flex-direction: column;
        }

        .monthly-report-period-meta {
            justify-content: flex-start;
        }

        .employee-search-box {
            width: 100%;
            max-width: none;
        }

        .employee-summary-card {
            grid-template-columns: 1fr;
        }

        .employee-summary-action {
            grid-column: auto;
            justify-self: start;
        }

        .employee-summary-stats {
            grid-column: auto;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .weekly-matrix-grid-header {
            display: none;
        }

        .weekly-matrix-row {
            display: grid;
            grid-template-columns:
                repeat(
                    var(--week-columns),
                    minmax(0, 1fr)
                );
            padding: .75rem;
            background: #faf9fc;
            border: 1px solid var(--monthly-border);
            border-radius: .85rem;
        }

        .weekly-matrix-row + .weekly-matrix-row {
            margin-top: .7rem;
        }

        .weekly-matrix-employee {
            grid-column: 1 / -1;
            margin-bottom: .2rem;
            background: #ffffff;
        }

        .weekly-matrix-cell {
            min-height: 3.2rem;
        }

        .weekly-matrix-cell::before {
            content: attr(title);
            display: none;
        }
    }

    @media (max-width: 767.98px) {
        .hr-monthly-report-page {
            padding-left: 1rem !important;
            padding-right: 1rem !important;
        }

        .employee-summary-rates,
        .monthly-detail-grid {
            grid-template-columns: 1fr;
        }

        .employee-summary-stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .monthly-chart-shell {
            height: 260px;
        }
    }

    @media (max-width: 575.98px) {
        .monthly-report-period-meta {
            display: grid;
            width: 100%;
        }

        .monthly-report-period-meta span {
            justify-content: flex-start;
            border-radius: .7rem;
        }

        .employee-summary-stats {
            grid-template-columns: 1fr 1fr;
        }

        .weekly-matrix-row {
            grid-template-columns:
                repeat(
                    min(
                        var(--week-columns),
                        4
                    ),
                    minmax(0, 1fr)
                );
        }

        .weekly-matrix-cell {
            min-height: 3rem;
        }
    }

    @media print {
        .sidebar,
        .topbar,
        .page-header-actions,
        .report-filter-card,
        .employee-summary-toolbar,
        .employee-summary-action,
        .weekly-matrix-employee a {
            display: none !important;
        }

        .hr-monthly-report-page {
            padding: 0 !important;
        }

        .page-header-card,
        .content-card,
        .weekly-matrix-card,
        .monthly-kpi-card {
            box-shadow: none !important;
            break-inside: avoid;
        }

        .weekly-matrix-card {
            page-break-inside: avoid;
        }

        .weekly-matrix-cell {
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const filterForm = document.getElementById(
        'monthlyReportFilterForm'
    );

    const filterButton = document.getElementById(
        'monthlyReportFilterButton'
    );

    let filterTimer = null;

    function submitMonthlyFilters() {
        if (!filterForm) {
            return;
        }

        if (filterButton) {
            filterButton.disabled = true;

            filterButton
                .querySelector('.default-label')
                ?.classList.add('d-none');

            filterButton
                .querySelector('.loading-label')
                ?.classList.remove('d-none');
        }

        filterForm.submit();
    }

    document
        .querySelectorAll('.monthly-filter-auto-submit')
        .forEach(function (field) {
            field.addEventListener('change', function () {
                window.clearTimeout(filterTimer);

                filterTimer = window.setTimeout(
                    submitMonthlyFilters,
                    180
                );
            });
        });

    filterForm?.addEventListener('submit', function () {
        if (!filterButton) {
            return;
        }

        filterButton.disabled = true;

        filterButton
            .querySelector('.default-label')
            ?.classList.add('d-none');

        filterButton
            .querySelector('.loading-label')
            ?.classList.remove('d-none');
    });

    document
        .getElementById('printMonthlyReportButton')
        ?.addEventListener('click', function () {
            window.print();
        });

    const employeeSearch = document.getElementById(
        'employeeSummarySearch'
    );

    const employeeCards = Array.from(
        document.querySelectorAll(
            '[data-employee-summary]'
        )
    );

    const visibleEmployeeCount = document.getElementById(
        'visibleEmployeeSummaryCount'
    );

    const searchEmpty = document.getElementById(
        'employeeSummarySearchEmpty'
    );

    function filterEmployeeSummaries() {
        const keyword = String(
            employeeSearch?.value || ''
        )
            .trim()
            .toLocaleLowerCase('id-ID');

        let visibleCount = 0;

        employeeCards.forEach(function (card) {
            const searchText = String(
                card.dataset.searchText || ''
            ).toLocaleLowerCase('id-ID');

            const visible = keyword === ''
                || searchText.includes(keyword);

            card.classList.toggle('d-none', !visible);

            if (visible) {
                visibleCount += 1;
            }
        });

        if (visibleEmployeeCount) {
            visibleEmployeeCount.textContent =
                visibleCount.toLocaleString('id-ID');
        }

        searchEmpty?.classList.toggle(
            'd-none',
            visibleCount > 0 || keyword === ''
        );
    }

    employeeSearch?.addEventListener(
        'input',
        filterEmployeeSummaries
    );

    function decodeAttendancePayload(encodedPayload) {
        if (!encodedPayload) {
            return null;
        }

        try {
            const binary = window.atob(encodedPayload);
            const bytes = Uint8Array.from(
                binary,
                function (character) {
                    return character.charCodeAt(0);
                }
            );

            return JSON.parse(
                new TextDecoder('utf-8').decode(bytes)
            );
        } catch (error) {
            console.error(
                'Detail attendance tidak dapat dibaca.',
                error
            );

            return null;
        }
    }

    function formatAttendanceDate(value) {
        if (!value) {
            return '-';
        }

        const date = new Date(
            value + 'T00:00:00'
        );

        if (Number.isNaN(date.getTime())) {
            return value;
        }

        return new Intl.DateTimeFormat(
            'id-ID',
            {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                weekday: 'long'
            }
        ).format(date);
    }

    function attendanceStatusClass(code) {
        return {
            OT: 'is-on-time',
            LT: 'is-late',
            EL: 'is-excused-late',
            HL: 'is-half-leave',
            LV: 'is-full-leave',
            AB: 'is-absent',
            MS: 'is-missing',
            OD: 'is-off-day',
            PH: 'is-holiday',
            PR: 'is-present'
        }[code] || 'is-empty';
    }

    const leaveLabels = @json($leaveLabels);
    const leaveDurationLabels = @json(
        $leaveDurationLabels
    );

    const detailModalElement = document.getElementById(
        'attendanceCellDetailModal'
    );

    const detailModal = (
        detailModalElement
        && window.bootstrap?.Modal
    )
        ? window.bootstrap.Modal.getOrCreateInstance(
            detailModalElement
        )
        : null;

    document.addEventListener('click', function (event) {
        const cell = event.target.closest(
            '[data-attendance-cell]'
        );

        if (!cell) {
            return;
        }

        const payload = decodeAttendancePayload(
            cell.dataset.attendanceCell
        );

        if (!payload) {
            return;
        }

        const codeElement = document.getElementById(
            'monthlyDetailStatusCode'
        );

        if (codeElement) {
            codeElement.className =
                'monthly-status-code '
                + attendanceStatusClass(
                    payload.code
                );

            codeElement.textContent =
                payload.code || '-';
        }

        const values = {
            monthlyDetailStatusLabel:
                payload.label
                || 'Belum Ada Data Final',
            monthlyDetailEmployee:
                payload.employee_name
                || 'Employee belum dikenali',
            monthlyDetailTeam:
                payload.work_team
                || 'Tim belum ditentukan',
            monthlyDetailDate:
                formatAttendanceDate(payload.date),
            monthlyDetailNumber:
                payload.employee_number
                || 'Belum tersedia',
            monthlyDetailClockIn:
                payload.clock_in || '-',
            monthlyDetailClockOut:
                payload.clock_out || '-',
            monthlyDetailLate:
                Number(payload.late_minutes || 0) > 0
                    ? Number(
                        payload.late_minutes
                    ).toLocaleString('id-ID')
                        + ' menit'
                    : '-',
            monthlyDetailLeave:
                payload.leave_type
                    ? (
                        leaveLabels[
                            payload.leave_type
                        ]
                        || payload.leave_type
                    )
                    + (
                        payload.leave_duration
                            ? ' · '
                                + (
                                    leaveDurationLabels[
                                        payload
                                            .leave_duration
                                    ]
                                    || payload
                                        .leave_duration
                                )
                            : ''
                    )
                    : '-',
            monthlyDetailRemarks:
                payload.remarks
                || 'Tidak ada catatan.'
        };

        Object.entries(values).forEach(function (
            [elementId, value]
        ) {
            const element = document.getElementById(
                elementId
            );

            if (element) {
                element.textContent = value;
            }
        });

        document
            .getElementById(
                'monthlyDetailAutoClockOut'
            )
            ?.classList.toggle(
                'd-none',
                !Boolean(
                    payload.is_auto_clock_out
                )
            );

        detailModal?.show();
    });

    if (typeof Chart === 'undefined') {
        return;
    }

    Chart.defaults.font.family = getComputedStyle(
        document.body
    ).fontFamily;

    Chart.defaults.color = '#756d80';

    const healthChart = @json(
        $attendanceHealthChart ?? []
    );

    const leaveChart = @json(
        $leaveDistributionChart ?? []
    );

    const healthCanvas = document.getElementById(
        'monthlyAttendanceHealthChart'
    );

    if (healthCanvas) {
        new Chart(healthCanvas, {
            type: 'doughnut',
            data: {
                labels: healthChart.labels || [],
                datasets: [{
                    data:
                        healthChart
                            .datasets
                            ?.attendance_status
                        || [],
                    backgroundColor: [
                        '#3B8E4D',
                        '#E2A719',
                        '#5B3E8E',
                        '#8B65B6',
                        '#73509D',
                        '#C2414B',
                        '#DC762A'
                    ],
                    borderColor: '#FFFFFF',
                    borderWidth: 3,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '66%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            boxWidth: 8,
                            padding: 16,
                            font: {
                                size: 10,
                                weight: 700
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return ' '
                                    + context.label
                                    + ': '
                                    + Number(
                                        context.raw || 0
                                    ).toLocaleString(
                                        'id-ID'
                                    );
                            }
                        }
                    }
                }
            }
        });
    }

    const leaveCanvas = document.getElementById(
        'monthlyLeaveDistributionChart'
    );

    if (leaveCanvas) {
        new Chart(leaveCanvas, {
            type: 'bar',
            data: {
                labels: leaveChart.labels || [],
                datasets: [{
                    label: 'Catatan',
                    data:
                        leaveChart
                            .datasets
                            ?.leave_type
                        || [],
                    backgroundColor:
                        'rgba(91, 62, 142, .82)',
                    borderColor: '#5B3E8E',
                    borderWidth: 1,
                    borderRadius: 8,
                    maxBarThickness: 42
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return ' '
                                    + Number(
                                        context.raw || 0
                                    ).toLocaleString(
                                        'id-ID'
                                    )
                                    + ' catatan';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            font: {
                                size: 10
                            }
                        },
                        grid: {
                            color:
                                'rgba(117, 109, 128, .09)'
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 10,
                                weight: 700
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush
