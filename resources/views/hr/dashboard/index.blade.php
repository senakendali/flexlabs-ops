@extends('layouts.app-dashboard')

@section('title', 'HR Dashboard')

@section('content')
@php
    $overview = $attendanceOverview ?? [];
    $previousOverview = $previousAttendanceOverview ?? [];
    $changes = $attendanceChanges ?? [];
    $freshness = $dataFreshness ?? [];
    $healthChart = $attendanceHealthChart ?? [];
    $trendChart = $weeklyTrendChart ?? [];
    $leaveChart = $leaveDistributionChart ?? [];

    $attentionEmployees = collect(
        $employeesRequiringAttention ?? []
    );

    $topAttendanceEmployees = collect(
        $topEmployeesByAttendance ?? []
    );

    $teamRows = collect($attendanceByTeam ?? []);
    $quality = $reviewAndDataQuality ?? [];
    $holidayRows = collect($upcomingHolidays ?? []);
    $masterHealth = $masterDataHealth ?? [];
    $latestImportData = $latestImport ?? null;

    /*
    |--------------------------------------------------------------------------
    | Robot AI HR Context
    |--------------------------------------------------------------------------
    | Ringkasan disiapkan oleh HrDashboardService dan diteruskan
    | ke shared AI insight widget yang juga dipakai dashboard lain.
    */
    $hrInsight = is_array($hrSummary ?? null)
        ? $hrSummary
        : [];

    $hrDashboardAiSummaryText = trim(
        (string) (
            $hrDashboardAiSummaryText
            ?? ($hrInsight['summary_text'] ?? '')
        )
    );

    $periodLabel = $period['label'] ?? '-';
    $previousPeriodLabel = $period['previous_label'] ?? '-';

    $formatPercent = static function ($value): string {
        return number_format((float) $value, 1) . '%';
    };

    $formatNumber = static function ($value): string {
        return number_format((int) $value);
    };

    $formatDate = static function ($value, string $fallback = '-'): string {
        if (! $value) {
            return $fallback;
        }

        try {
            return \Carbon\Carbon::parse($value)
                ->translatedFormat('d M Y');
        } catch (\Throwable) {
            return $fallback;
        }
    };

    $formatDateTime = static function (
        $value,
        string $fallback = '-'
    ): string {
        if (! $value) {
            return $fallback;
        }

        try {
            return \Carbon\Carbon::parse($value)
                ->translatedFormat('d M Y H:i');
        } catch (\Throwable) {
            return $fallback;
        }
    };

    $freshnessStatus = $freshness['status'] ?? 'no_data';

    $freshnessClass = match ($freshnessStatus) {
        'up_to_date' => 'is-success',
        'awaiting_weekly_import' => 'is-info',
        'review_required' => 'is-warning',
        'import_overdue' => 'is-danger',
        default => 'is-neutral',
    };

    $freshnessIcon = match ($freshnessStatus) {
        'up_to_date' => 'bi-check-circle-fill',
        'awaiting_weekly_import' => 'bi-calendar-week-fill',
        'review_required' => 'bi-exclamation-triangle-fill',
        'import_overdue' => 'bi-clock-history',
        default => 'bi-database-exclamation',
    };

    $changeBadge = static function (
        array $change,
        bool $showPoint = false
    ): array {
        $direction = $change['direction'] ?? 'flat';
        $performance = $change['performance'] ?? 'neutral';

        $value = $showPoint
            ? (float) ($change['percentage_points'] ?? 0)
            : (float) ($change['percentage'] ?? 0);

        $icon = match ($direction) {
            'up' => 'bi-arrow-up-right',
            'down' => 'bi-arrow-down-right',
            default => 'bi-dash',
        };

        $class = match ($performance) {
            'good' => 'is-good',
            'bad' => 'is-bad',
            default => 'is-neutral',
        };

        return [
            'class' => $class,
            'icon' => $icon,
            'text' => ($value > 0 ? '+' : '')
                . number_format($value, 1)
                . ($showPoint ? ' pp' : '%'),
        ];
    };

    $presenceChange = $changeBadge(
        $changes['presence_rate'] ?? [],
        true
    );

    $onTimeChange = $changeBadge(
        $changes['on_time_rate'] ?? [],
        true
    );

    $absenceChange = $changeBadge(
        $changes['absence_rate'] ?? [],
        true
    );

    $routeExists = static fn (string $name): bool =>
        \Illuminate\Support\Facades\Route::has($name);

    $firstExistingRoute = static function (
        array $names,
        array $parameters = []
    ) use ($routeExists): ?string {
        foreach ($names as $name) {
            if ($routeExists($name)) {
                return route($name, $parameters);
            }
        }

        return null;
    };

    $attendanceIndexUrl = $firstExistingRoute([
        'hr.attendances.index',
        'hr.attendance-imports.index',
    ]);

    $attendanceCreateUrl = $firstExistingRoute([
        'hr.attendances.create',
        'hr.attendance-imports.create',
    ]);

    $attendanceReviewUrl = null;

    if (
        $latestImportData
        && ! empty($latestImportData['id'])
        && $routeExists('hr.attendance-imports.review')
    ) {
        $attendanceReviewUrl = route(
            'hr.attendance-imports.review',
            $latestImportData['id']
        );
    }

    $employeeMasterUrl = $firstExistingRoute([
        'hr.employees.index',
    ]);

    $workingHoursUrl = $firstExistingRoute([
        'hr.working-hour-templates.index',
    ]);

    $holidayMasterUrl = $firstExistingRoute([
        'hr.company-holidays.index',
    ]);

    $monthlyReportUrl = $firstExistingRoute([
        'hr.dashboard.monthly-report',
        'hr.attendance-reports.monthly',
    ]);

    $importStatusClass = match ($latestImportData['status'] ?? null) {
        'completed' => 'bg-success-subtle text-success-emphasis',
        'reviewing' => 'bg-warning-subtle text-warning-emphasis',
        'processing' => 'bg-primary-subtle text-primary-emphasis',
        'failed' => 'bg-danger-subtle text-danger-emphasis',
        'cancelled' => 'bg-secondary-subtle text-secondary-emphasis',
        default => 'bg-light text-dark',
    };
@endphp

<div class="container-fluid px-4 py-4 hr-dashboard-page">
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">HR Operations</div>

                <h1 class="page-title mb-2">
                    HR Attendance Dashboard
                </h1>

                <p class="page-subtitle mb-0">
                    Pantau kehadiran employee, data yang perlu diperiksa, dan tindak lanjut HR dalam satu halaman.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                @if ($monthlyReportUrl)
                    <a
                        href="{{ $monthlyReportUrl }}"
                        class="btn btn-light btn-modern"
                    >
                        <i class="bi bi-calendar3 me-2"></i>
                        Laporan Bulanan
                    </a>
                @endif

                @if ($attendanceCreateUrl)
                    <a
                        href="{{ $attendanceCreateUrl }}"
                        class="btn btn-light btn-modern"
                    >
                        <i class="bi bi-cloud-arrow-up-fill me-2"></i>
                        Upload Attendance
                    </a>
                @elseif ($attendanceIndexUrl)
                    <a
                        href="{{ $attendanceIndexUrl }}"
                        class="btn btn-light btn-modern"
                    >
                        <i class="bi bi-cloud-arrow-up-fill me-2"></i>
                        Import Attendance
                    </a>
                @endif
            </div>
        </div>
    </div>

    <div class="hr-freshness-banner {{ $freshnessClass }} mb-4">
        <div class="hr-freshness-main">
            <div class="hr-freshness-icon">
                <i class="bi {{ $freshnessIcon }}"></i>
            </div>

            <div class="min-w-0">
                <div class="hr-freshness-eyebrow">
                    Status Data Attendance
                </div>

                <div class="hr-freshness-title">
                    {{ $freshness['status_label'] ?? 'Belum Ada Data Terkonfirmasi' }}
                </div>

                <div class="hr-freshness-description">
                    {{ $freshness['status_description'] ?? 'Belum ada informasi freshness yang tersedia.' }}
                </div>
            </div>
        </div>

        <div class="hr-freshness-facts">
            <div class="hr-freshness-fact">
                <span>Data tersedia sampai</span>
                <strong>
                    {{ $formatDate($freshness['available_through'] ?? null) }}
                </strong>
            </div>

            <div class="hr-freshness-fact">
                <span>Periode yang dipilih</span>
                <strong>{{ $periodLabel }}</strong>
            </div>

            <div class="hr-freshness-fact">
                <span>Import terakhir dikonfirmasi</span>
                <strong>
                    {{ $formatDateTime(
                        data_get(
                            $freshness,
                            'latest_completed_import.confirmed_at'
                        )
                    ) }}
                </strong>
            </div>
        </div>

        @if (
            data_get(
                $freshness,
                'latest_pending_import.id'
            )
        )
            <div class="hr-freshness-pending">
                <div>
                    <span class="hr-freshness-pending-label">
                        Import yang menunggu proses
                    </span>

                    <strong>
                        {{ data_get(
                            $freshness,
                            'latest_pending_import.period_label',
                            'Periode belum tersedia'
                        ) }}
                    </strong>

                    <span>
                        ·
                        {{ \Illuminate\Support\Str::headline(
                            data_get(
                                $freshness,
                                'latest_pending_import.status',
                                'unknown'
                            )
                        ) }}
                    </span>
                </div>

                @if ($attendanceReviewUrl)
                    <a
                        href="{{ $attendanceReviewUrl }}"
                        class="btn btn-sm btn-warning"
                    >
                        Periksa Import
                        <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                @endif
            </div>
        @endif
    </div>

    <form
        method="GET"
        action="{{ route('hr.dashboard') }}"
        class="content-card mb-4"
        id="hrDashboardFilterForm"
    >
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">
                    Periode Laporan
                </h5>

                <p class="content-card-subtitle mb-0">
                    Pilih periode attendance yang ingin dilihat.
                </p>
            </div>

            <span class="badge rounded-pill bg-light text-dark border">
                Dibandingkan dengan {{ $previousPeriodLabel }}
            </span>
        </div>

        <div class="content-card-body">
            <div class="row g-3 align-items-end">
                <div class="col-xl-3 col-md-6">
                    <label
                        for="date_from"
                        class="form-label fw-semibold"
                    >
                        Mulai Tanggal
                    </label>

                    <input
                        type="date"
                        class="form-control hr-filter-auto-submit"
                        id="date_from"
                        name="date_from"
                        value="{{ $filters['date_from'] ?? '' }}"
                    >
                </div>

                <div class="col-xl-3 col-md-6">
                    <label
                        for="date_to"
                        class="form-label fw-semibold"
                    >
                        Sampai Tanggal
                    </label>

                    <input
                        type="date"
                        class="form-control hr-filter-auto-submit"
                        id="date_to"
                        name="date_to"
                        value="{{ $filters['date_to'] ?? '' }}"
                    >
                </div>

                <div class="col-xl-3 col-md-6">
                    <label
                        for="work_team"
                        class="form-label fw-semibold"
                    >
                        Tim Kerja
                    </label>

                    <select
                        class="form-select hr-filter-auto-submit"
                        id="work_team"
                        name="work_team"
                    >
                        <option value="">
                            Semua Tim
                        </option>

                        @foreach (($workTeams ?? collect()) as $team)
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

                <div class="col-xl-3 col-md-6">
                    <div class="d-flex gap-2">
                        <a
                            href="{{ route('hr.dashboard') }}"
                            class="btn btn-danger btn-modern flex-fill"
                        >
                            <i class="bi bi-arrow-counterclockwise me-2"></i>
                            Reset
                        </a>

                        <button
                            type="submit"
                            class="btn btn-primary btn-modern flex-fill"
                            id="hrDashboardFilterButton"
                        >
                            <span class="default-label">
                                <i class="bi bi-funnel-fill me-2"></i>
                                Terapkan
                            </span>

                            <span class="loading-label d-none">
                                <span class="spinner-border spinner-border-sm me-2"></span>
                                Loading
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <div class="dashboard-section-label mb-3">
        <div class="dashboard-section-eyebrow">
            Ringkasan Kehadiran
        </div>

        <h4 class="dashboard-section-title mb-1">
            Performa Periode
        </h4>

        <p class="dashboard-section-subtitle mb-0">
            Tingkat Kehadiran dan Ketepatan Waktu ditampilkan terpisah agar hasilnya mudah dibaca.
        </p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xxl-2 col-xl-4 col-md-6">
            <div class="hr-kpi-card">
                <div class="hr-kpi-top">
                    <span class="hr-kpi-icon">
                        <i class="bi bi-people-fill"></i>
                    </span>

                    <span class="hr-kpi-label">
                        Employee Tercakup
                    </span>
                </div>

                <div class="hr-kpi-value">
                    {{ $formatNumber(
                        $overview['employees_covered'] ?? 0
                    ) }}
                </div>

                <div class="hr-kpi-help">
                    Dari
                    {{ $formatNumber(
                        $overview['active_employees'] ?? 0
                    ) }}
                    employee aktif.
                </div>
            </div>
        </div>

        <div class="col-xxl-2 col-xl-4 col-md-6">
            <div class="hr-kpi-card">
                <div class="hr-kpi-top">
                    <span class="hr-kpi-icon">
                        <i class="bi bi-person-check-fill"></i>
                    </span>

                    <span class="hr-kpi-label">
                        Tingkat Kehadiran
                    </span>
                </div>

                <div class="hr-kpi-value">
                    {{ $formatPercent(
                        $overview['presence_rate'] ?? 0
                    ) }}
                </div>

                <div class="hr-kpi-change {{ $presenceChange['class'] }}">
                    <i class="bi {{ $presenceChange['icon'] }}"></i>
                    {{ $presenceChange['text'] }}
                    dari periode sebelumnya
                </div>

                <div class="hr-kpi-help">
                    Persentase hari hadir dari hari kerja terjadwal.
                </div>
            </div>
        </div>

        <div class="col-xxl-2 col-xl-4 col-md-6">
            <div class="hr-kpi-card">
                <div class="hr-kpi-top">
                    <span class="hr-kpi-icon">
                        <i class="bi bi-alarm-fill"></i>
                    </span>

                    <span class="hr-kpi-label">
                        Ketepatan Waktu
                    </span>
                </div>

                <div class="hr-kpi-value">
                    {{ $formatPercent(
                        $overview['on_time_rate'] ?? 0
                    ) }}
                </div>

                <div class="hr-kpi-change {{ $onTimeChange['class'] }}">
                    <i class="bi {{ $onTimeChange['icon'] }}"></i>
                    {{ $onTimeChange['text'] }}
                    dari periode sebelumnya
                </div>

                <div class="hr-kpi-help">
                    Persentase hadir tepat waktu dari total hari hadir.
                </div>
            </div>
        </div>

        <div class="col-xxl-2 col-xl-4 col-md-6">
            <div class="hr-kpi-card">
                <div class="hr-kpi-top">
                    <span class="hr-kpi-icon">
                        <i class="bi bi-calendar2-minus-fill"></i>
                    </span>

                    <span class="hr-kpi-label">
                        Cuti / Izin
                    </span>
                </div>

                <div class="hr-kpi-value">
                    {{ $formatNumber(
                        $overview['approved_leave_days'] ?? 0
                    ) }}
                </div>

                <div class="hr-kpi-help">
                    {{ $formatNumber(
                        $overview['full_day_leave_days'] ?? 0
                    ) }}
                    hari penuh ·
                    {{ $formatNumber(
                        $overview['half_day_leave_days'] ?? 0
                    ) }}
                    setengah hari.
                </div>
            </div>
        </div>

        <div class="col-xxl-2 col-xl-4 col-md-6">
            <div class="hr-kpi-card">
                <div class="hr-kpi-top">
                    <span class="hr-kpi-icon">
                        <i class="bi bi-person-x-fill"></i>
                    </span>

                    <span class="hr-kpi-label">
                        Tidak Hadir Tanpa Keterangan
                    </span>
                </div>

                <div class="hr-kpi-value">
                    {{ $formatNumber(
                        $overview['absent_days'] ?? 0
                    ) }}
                </div>

                <div class="hr-kpi-change {{ $absenceChange['class'] }}">
                    <i class="bi {{ $absenceChange['icon'] }}"></i>
                    {{ $absenceChange['text'] }}
                    perubahan
                </div>

                <div class="hr-kpi-help">
                    Data yang belum lengkap ditampilkan secara terpisah.
                </div>
            </div>
        </div>

        <div class="col-xxl-2 col-xl-4 col-md-6">
            <div class="hr-kpi-card is-actionable">
                <div class="hr-kpi-top">
                    <span class="hr-kpi-icon">
                        <i class="bi bi-clipboard2-pulse-fill"></i>
                    </span>

                    <span class="hr-kpi-label">
                        Perlu Tindakan HR
                    </span>
                </div>

                <div class="hr-kpi-value">
                    {{ $formatNumber(
                        $quality['review_backlog'] ?? 0
                    ) }}
                </div>

                <div class="hr-kpi-help">
                    Ada data attendance yang perlu diperiksa sebelum dapat dikonfirmasi.
                </div>

                @if ($attendanceReviewUrl)
                    <a
                        href="{{ $attendanceReviewUrl }}"
                        class="hr-kpi-link"
                    >
                        Buka Review Attendance
                        <i class="bi bi-arrow-right"></i>
                    </a>
                @endif
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-5">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">
                            Ringkasan Status Kehadiran
                        </h5>

                        <p class="content-card-subtitle mb-0">
                            Ringkasan status kehadiran employee pada periode yang dipilih.
                        </p>
                    </div>

                    <span class="badge rounded-pill bg-light text-dark border">
                        {{ $formatNumber(
                            $healthChart['total'] ?? 0
                        ) }}
                        catatan attendance
                    </span>
                </div>

                <div class="content-card-body">
                    <div class="hr-chart-shell is-donut">
                        <canvas id="hrAttendanceHealthChart"></canvas>
                    </div>

                    <div class="hr-health-legend mt-3">
                        @foreach (
                            ($healthChart['counts'] ?? [])
                            as $key => $value
                        )
                            @php
                                $healthLabels = [
                                    'on_time' => 'On Time',
                                    'late' => 'Late',
                                    'excused_late' => 'Excused Late',
                                    'half_day_leave' => 'Setengah Hari Cuti / Izin',
                                    'full_day_leave' => 'Hari Penuh Cuti / Izin',
                                    'absent' => 'Tidak Hadir',
                                    'missing' => 'Missing',
                                ];
                            @endphp

                            <div class="hr-health-legend-item">
                                <span
                                    class="hr-health-dot"
                                    data-health-key="{{ $key }}"
                                ></span>

                                <span>
                                    {{ $healthLabels[$key]
                                        ?? \Illuminate\Support\Str::headline($key) }}
                                </span>

                                <strong>{{ $formatNumber($value) }}</strong>
                            </div>
                        @endforeach
                    </div>

                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">
                            Tren Kehadiran Mingguan
                        </h5>

                        <p class="content-card-subtitle mb-0">
                            Perubahan Tingkat Kehadiran, Ketepatan Waktu, dan tingkat ketidakhadiran per minggu.
                        </p>
                    </div>

                    <span class="badge rounded-pill bg-light text-dark border">
                        {{ $periodLabel }}
                    </span>
                </div>

                <div class="content-card-body">
                    <div class="hr-chart-shell">
                        <canvas id="hrWeeklyTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <div class="row g-4 mb-4">
        <div class="col-xl-7">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">
                            Employee yang Perlu Perhatian
                        </h5>

                        <p class="content-card-subtitle mb-0">
                            Employee dengan catatan tidak hadir, terlambat, pulang lebih awal, atau clock out yang tidak tercatat.
                        </p>
                    </div>

                    <span class="badge rounded-pill bg-light text-dark border">
                        {{ $formatNumber(
                            $attentionEmployees->count()
                        ) }}
                        employee
                    </span>
                </div>

                <div class="content-card-body">
                    @forelse ($attentionEmployees as $employee)
                        @php
                            $employeeDetailUrl = null;

                            if (
                                $routeExists(
                                    'hr.dashboard.employee-detail'
                                )
                            ) {
                                $employeeDetailUrl = route(
                                    'hr.dashboard.employee-detail',
                                    $employee['employee_id']
                                );
                            } elseif (
                                $routeExists(
                                    'hr.employees.attendance-detail'
                                )
                            ) {
                                $employeeDetailUrl = route(
                                    'hr.employees.attendance-detail',
                                    $employee['employee_id']
                                );
                            }
                        @endphp

                        <div class="hr-attention-item">
                            <div class="hr-attention-avatar">
                                {{ mb_strtoupper(
                                    mb_substr(
                                        $employee['employee_name']
                                            ?? '?',
                                        0,
                                        1
                                    )
                                ) }}
                            </div>

                            <div class="hr-attention-main">
                                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                    <div>
                                        <div class="hr-attention-name">
                                            {{ $employee['employee_name']
                                                ?? 'Employee belum dikenali' }}
                                        </div>

                                        <div class="hr-attention-meta">
                                            {{ $employee['employee_number']
                                                ?? 'Nomor employee belum tersedia' }}
                                            ·
                                            {{ $employee['work_team']
                                                ?? 'Tim belum ditentukan' }}
                                        </div>
                                    </div>

                                    <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                        Perlu ditindaklanjuti
                                    </span>
                                </div>

                                <div class="hr-attention-reason">
                                    {{ $employee['reason']
                                        ?? 'Ada catatan kehadiran yang perlu diperiksa' }}
                                </div>

                                <div class="hr-attention-footer">
                                    <span>
                                        Catatan terakhir:
                                        <strong>
                                            {{ $formatDate(
                                                $employee['last_issue_date']
                                                    ?? null
                                            ) }}
                                        </strong>
                                    </span>

                                    @if ($employeeDetailUrl)
                                        <a href="{{ $employeeDetailUrl }}">
                                            Lihat Detail
                                            <i class="bi bi-arrow-right"></i>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state-box">
                            <div class="empty-state-icon">
                                <i class="bi bi-person-check-fill"></i>
                            </div>

                            <h5 class="empty-state-title">
                                Tidak ada catatan yang perlu ditindaklanjuti
                            </h5>

                            <p class="empty-state-text mb-0">
                                Belum ada employee dengan masalah kehadiran pada periode yang dipilih.
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">
                            Top 10 Employee by Attendance
                        </h5>

                        <p class="content-card-subtitle mb-0">
                            Diurutkan berdasarkan tingkat kehadiran, lalu ketepatan waktu pada periode yang dipilih.
                        </p>
                    </div>

                    <span class="badge rounded-pill bg-success-subtle text-success-emphasis border border-success-subtle">
                        {{ $formatNumber(
                            $topAttendanceEmployees->count()
                        ) }}
                        employee
                    </span>
                </div>

                <div class="content-card-body">
                    <div class="hr-top-attendance-list">
                        @forelse (
                            $topAttendanceEmployees
                            as $topEmployee
                        )
                            @php
                                $topEmployeeDetailUrl = null;

                                if (
                                    $routeExists(
                                        'hr.dashboard.employee-detail'
                                    )
                                ) {
                                    $topEmployeeDetailUrl = route(
                                        'hr.dashboard.employee-detail',
                                        $topEmployee['employee_id']
                                    );
                                } elseif (
                                    $routeExists(
                                        'hr.employees.attendance-detail'
                                    )
                                ) {
                                    $topEmployeeDetailUrl = route(
                                        'hr.employees.attendance-detail',
                                        $topEmployee['employee_id']
                                    );
                                }
                            @endphp

                            <div class="hr-top-attendance-item">
                                <span
                                    class="hr-top-attendance-rank {{ ($topEmployee['rank'] ?? 0) <= 3 ? 'is-top-three' : '' }}"
                                >
                                    {{ $topEmployee['rank'] ?? '-' }}
                                </span>

                                <div class="hr-top-attendance-person">
                                    <div class="hr-top-attendance-name">
                                        {{ $topEmployee['employee_name']
                                            ?? 'Employee belum dikenali' }}
                                    </div>

                                    <div class="hr-top-attendance-meta">
                                        {{ $topEmployee['work_team']
                                            ?? 'Tim belum ditentukan' }}
                                        ·
                                        {{ $formatNumber(
                                            $topEmployee[
                                                'present_days'
                                            ] ?? 0
                                        ) }}
                                        hari hadir
                                    </div>
                                </div>

                                <div class="hr-top-attendance-rates">
                                    <div>
                                        <span>Kehadiran</span>
                                        <strong>
                                            {{ $formatPercent(
                                                $topEmployee[
                                                    'presence_rate'
                                                ] ?? 0
                                            ) }}
                                        </strong>
                                    </div>

                                    <div>
                                        <span>Tepat Waktu</span>
                                        <strong>
                                            {{ $formatPercent(
                                                $topEmployee[
                                                    'on_time_rate'
                                                ] ?? 0
                                            ) }}
                                        </strong>
                                    </div>
                                </div>

                                @if ($topEmployeeDetailUrl)
                                    <a
                                        href="{{ $topEmployeeDetailUrl }}"
                                        class="hr-top-attendance-link"
                                        aria-label="Lihat detail {{ $topEmployee['employee_name'] ?? 'employee' }}"
                                    >
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                @endif
                            </div>
                        @empty
                            <div class="empty-state-box">
                                <div class="empty-state-icon">
                                    <i class="bi bi-trophy-fill"></i>
                                </div>

                                <h5 class="empty-state-title">
                                    Ranking belum tersedia
                                </h5>

                                <p class="empty-state-text mb-0">
                                    Ranking akan tampil setelah attendance periode ini sudah dikonfirmasi.
                                </p>
                            </div>
                        @endforelse
                    </div>

                    
                </div>
            </div>
        </div>
    </div>

<div class="row g-4 mb-4">
        <div class="col-xl-7">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">
                            Kehadiran per Tim
                        </h5>

                        <p class="content-card-subtitle mb-0">
                            Perbandingan tingkat kehadiran dan ketepatan waktu setiap tim.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="hr-team-grid">
                        @forelse ($teamRows as $team)
                            <div class="hr-team-card">
                                <div class="hr-team-header">
                                    <div>
                                        <div class="hr-team-name">
                                            {{ $team['team'] ?? 'Tim belum ditentukan' }}
                                        </div>

                                        <div class="hr-team-meta">
                                            {{ $formatNumber(
                                                $team['employees'] ?? 0
                                            ) }}
                                            employee ·
                                            {{ $formatNumber(
                                                $team['expected_workdays'] ?? 0
                                            ) }}
                                            hari kerja
                                        </div>
                                    </div>

                                    <span class="hr-team-rate">
                                        {{ $formatPercent(
                                            $team['presence_rate'] ?? 0
                                        ) }}
                                    </span>
                                </div>

                                <div class="hr-team-progress">
                                    <span
                                        style="width: {{ min(
                                            max(
                                                (float) (
                                                    $team['presence_rate']
                                                    ?? 0
                                                ),
                                                0
                                            ),
                                            100
                                        ) }}%"
                                    ></span>
                                </div>

                                <div class="hr-team-stats">
                                    <div>
                                        <span>Tepat Waktu</span>
                                        <strong>
                                            {{ $formatPercent(
                                                $team['on_time_rate'] ?? 0
                                            ) }}
                                        </strong>
                                    </div>

                                    <div>
                                        <span>Cuti / Izin</span>
                                        <strong>
                                            {{ $formatNumber(
                                                $team[
                                                    'approved_leave_days'
                                                ] ?? 0
                                            ) }}
                                        </strong>
                                    </div>

                                    <div>
                                        <span>Tidak Hadir</span>
                                        <strong>
                                            {{ $formatNumber(
                                                $team['absent_days'] ?? 0
                                            ) }}
                                        </strong>
                                    </div>

                                    <div>
                                        <span>Clock Out Otomatis</span>
                                        <strong>
                                            {{ $formatNumber(
                                                $team[
                                                    'auto_clock_out_days'
                                                ] ?? 0
                                            ) }}
                                        </strong>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="empty-state-box hr-team-empty">
                                <div class="empty-state-icon">
                                    <i class="bi bi-diagram-3-fill"></i>
                                </div>

                                <h5 class="empty-state-title">
                                    Data tim belum tersedia
                                </h5>

                                <p class="empty-state-text mb-0">
                                    Isi Tim Kerja pada Employee Master agar dashboard dapat membandingkan attendance per team.
                                </p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">
                            Ringkasan Cuti & Izin
                        </h5>

                        <p class="content-card-subtitle mb-0">
                            Jenis cuti dan izin yang tercatat pada periode yang dipilih.
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
                    <div class="hr-chart-shell is-compact">
                        <canvas id="hrLeaveDistributionChart"></canvas>
                    </div>

                    <div class="hr-leave-summary mt-3">
                        <div>
                            <span>Hari Penuh</span>
                            <strong>
                                {{ $formatNumber(
                                    $leaveChart[
                                        'full_day_total'
                                    ] ?? 0
                                ) }}
                            </strong>
                        </div>

                        <div>
                            <span>Setengah Hari</span>
                            <strong>
                                {{ $formatNumber(
                                    $leaveChart[
                                        'half_day_total'
                                    ] ?? 0
                                ) }}
                            </strong>
                        </div>
                    </div>
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
                            Review & Kualitas Data
                        </h5>

                        <p class="content-card-subtitle mb-0">
                            Data yang perlu diperiksa sebelum attendance dapat dikonfirmasi.
                        </p>
                    </div>

                    <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                        {{ $formatNumber(
                            $quality['open_imports'] ?? 0
                        ) }}
                        proses import aktif
                    </span>
                </div>

                <div class="content-card-body">
                    <div class="hr-quality-grid">
                        <div class="hr-quality-item is-warning">
                            <span>Perlu Diperiksa</span>
                            <strong>
                                {{ $formatNumber(
                                    $quality['needs_review_rows'] ?? 0
                                ) }}
                            </strong>
                        </div>

                        <div class="hr-quality-item is-danger">
                            <span>Data Bermasalah</span>
                            <strong>
                                {{ $formatNumber(
                                    $quality['error_rows'] ?? 0
                                ) }}
                            </strong>
                        </div>

                        <div class="hr-quality-item is-danger">
                            <span>Data Ganda Berbeda</span>
                            <strong>
                                {{ $formatNumber(
                                    $quality[
                                        'conflicting_duplicate_rows'
                                    ] ?? 0
                                ) }}
                            </strong>
                        </div>

                        <div class="hr-quality-item">
                            <span>Employee Belum Dikenali</span>
                            <strong>
                                {{ $formatNumber(
                                    $quality[
                                        'unmatched_employee_rows'
                                    ] ?? 0
                                ) }}
                            </strong>
                        </div>

                        <div class="hr-quality-item is-info">
                            <span>Hari Kerja Tanpa Data</span>
                            <strong>
                                {{ $formatNumber(
                                    $quality[
                                        'generated_missing_rows'
                                    ] ?? 0
                                ) }}
                            </strong>
                        </div>

                        <div class="hr-quality-item is-info">
                            <span>Clock Out Diisi Otomatis</span>
                            <strong>
                                {{ $formatNumber(
                                    $quality[
                                        'auto_clock_out_rows'
                                    ] ?? 0
                                ) }}
                            </strong>
                        </div>

                        <div class="hr-quality-item is-success">
                            <span>Data Ganda Dirapikan</span>
                            <strong>
                                {{ $formatNumber(
                                    $quality[
                                        'exact_duplicate_auto_resolved'
                                    ] ?? 0
                                ) }}
                            </strong>
                        </div>

                        <div class="hr-quality-item is-success">
                            <span>Hari Libur Ditambahkan</span>
                            <strong>
                                {{ $formatNumber(
                                    $quality[
                                        'generated_holiday_rows'
                                    ] ?? 0
                                ) }}
                            </strong>
                        </div>
                    </div>

                   

                    @if ($attendanceReviewUrl)
                        <a
                            href="{{ $attendanceReviewUrl }}"
                            class="btn btn-primary btn-modern w-100 mt-3"
                        >
                            <i class="bi bi-clipboard2-check-fill me-2"></i>
                            Buka Review Attendance
                        </a>
                    @elseif ($attendanceIndexUrl)
                        <a
                            href="{{ $attendanceIndexUrl }}"
                            class="btn btn-outline-secondary btn-modern w-100 mt-3"
                        >
                            <i class="bi bi-clock-history me-2"></i>
                            Lihat Riwayat Import
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">
                            Import Attendance Terbaru
                        </h5>

                        <p class="content-card-subtitle mb-0">
                            Ringkasan proses import attendance terakhir yang masuk ke sistem.
                        </p>
                    </div>

                    @if ($latestImportData)
                        <span class="badge rounded-pill {{ $importStatusClass }}">
                            {{ \Illuminate\Support\Str::headline(
                                $latestImportData['status']
                                    ?? 'unknown'
                            ) }}
                        </span>
                    @endif
                </div>

                <div class="content-card-body">
                    @if ($latestImportData)
                        <div class="hr-import-file">
                            <span class="hr-import-file-icon">
                                <i class="bi bi-file-earmark-spreadsheet-fill"></i>
                            </span>

                            <div class="min-w-0">
                                <div class="hr-import-file-name">
                                    {{ $latestImportData[
                                        'original_file_name'
                                    ] ?? 'File attendance' }}
                                </div>

                                <div class="hr-import-file-meta">
                                    {{ $latestImportData[
                                        'period_label'
                                    ] ?? 'Periode belum tersedia' }}
                                    · Diunggah oleh
                                    {{ $latestImportData[
                                        'uploaded_by'
                                    ] ?? 'User tidak diketahui' }}
                                </div>
                            </div>
                        </div>

                        <div class="hr-import-stats">
                            <div>
                                <span>Total Data</span>
                                <strong>
                                    {{ $formatNumber(
                                        $latestImportData[
                                            'total_rows'
                                        ] ?? 0
                                    ) }}
                                </strong>
                            </div>

                            <div>
                                <span>Dari Excel</span>
                                <strong>
                                    {{ $formatNumber(
                                        $latestImportData[
                                            'imported_rows'
                                        ] ?? 0
                                    ) }}
                                </strong>
                            </div>

                            <div>
                                <span>Dibuat Sistem</span>
                                <strong>
                                    {{ $formatNumber(
                                        $latestImportData[
                                            'generated_rows'
                                        ] ?? 0
                                    ) }}
                                </strong>
                            </div>

                            <div>
                                <span>Perlu Diperiksa</span>
                                <strong>
                                    {{ $formatNumber(
                                        $latestImportData[
                                            'review_rows'
                                        ] ?? 0
                                    ) }}
                                </strong>
                            </div>
                        </div>

                        <div class="hr-import-footer">
                            <span>
                                Diproses:
                                <strong>
                                    {{ $formatDateTime(
                                        $latestImportData[
                                            'imported_at'
                                        ] ?? null
                                    ) }}
                                </strong>
                            </span>

                            <div class="d-flex gap-2 flex-wrap">
                                @if ($attendanceReviewUrl)
                                    <a
                                        href="{{ $attendanceReviewUrl }}"
                                        class="btn btn-sm btn-outline-secondary"
                                    >
                                        Periksa Data
                                    </a>
                                @endif

                                @if ($attendanceIndexUrl)
                                    <a
                                        href="{{ $attendanceIndexUrl }}"
                                        class="btn btn-sm btn-outline-secondary"
                                    >
                                        Riwayat Import
                                    </a>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="empty-state-box">
                            <div class="empty-state-icon">
                                <i class="bi bi-cloud-arrow-up-fill"></i>
                            </div>

                            <h5 class="empty-state-title">
                                Belum ada import attendance
                            </h5>

                            <p class="empty-state-text mb-3">
                                Upload file attendance untuk memulai proses review dan laporan HR.
                            </p>

                            @if ($attendanceCreateUrl)
                                <a
                                    href="{{ $attendanceCreateUrl }}"
                                    class="btn btn-primary btn-modern"
                                >
                                    Upload Attendance
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>


    <div class="row g-4 mb-4">
        <div class="col-xl-5">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">
                            Hari Libur Mendatang
                        </h5>

                        <p class="content-card-subtitle mb-0">
                            Hari libur perusahaan yang sudah dijadwalkan.
                        </p>
                    </div>

                    @if ($holidayMasterUrl)
                        <a
                            href="{{ $holidayMasterUrl }}"
                            class="btn btn-sm btn-outline-secondary"
                        >
                            Kelola Hari Libur
                        </a>
                    @endif
                </div>

                <div class="content-card-body">
                    @forelse ($holidayRows as $holiday)
                        <div class="hr-holiday-item">
                            <div class="hr-holiday-date">
                                <strong>
                                    {{ $formatDate(
                                        $holiday['holiday_date']
                                            ?? null,
                                        '--'
                                    ) }}
                                </strong>

                                <span>
                                    {{ $holiday['days_remaining'] ?? 0 }}
                                    hari lagi
                                </span>
                            </div>

                            <div class="hr-holiday-main">
                                <div class="hr-holiday-name">
                                    {{ $holiday['name']
                                        ?? 'Hari Libur Perusahaan' }}
                                </div>

                                <div class="hr-holiday-meta">
                                    {{ \Illuminate\Support\Str::headline(
                                        $holiday['holiday_type']
                                            ?? 'company holiday'
                                    ) }}
                                </div>

                                @if (! empty($holiday['notes']))
                                    <div class="hr-holiday-notes">
                                        {{ $holiday['notes'] }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="empty-state-box">
                            <div class="empty-state-icon">
                                <i class="bi bi-calendar2-event-fill"></i>
                            </div>

                            <h5 class="empty-state-title">
                                Belum ada hari libur berikutnya
                            </h5>

                            <p class="empty-state-text mb-0">
                                Tambahkan hari libur perusahaan agar jadwal kerja dan attendance tetap akurat.
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">
                            Kelengkapan Data HR
                        </h5>

                        <p class="content-card-subtitle mb-0">
                            Kelengkapan data employee, tim kerja, dan jadwal kerja yang digunakan dalam attendance.
                        </p>
                    </div>

                    <span class="hr-master-score">
                        {{ $formatPercent(
                            $masterHealth['health_score'] ?? 0
                        ) }}
                    </span>
                </div>

                <div class="content-card-body">
                    <div class="hr-master-health-grid">
                        <div class="hr-master-health-item">
                            <span>Employee Aktif</span>
                            <strong>
                                {{ $formatNumber(
                                    $masterHealth[
                                        'active_employees'
                                    ] ?? 0
                                ) }}
                            </strong>
                        </div>

                        <div class="hr-master-health-item">
                            <span>Belum Punya Jadwal Kerja</span>
                            <strong>
                                {{ $formatNumber(
                                    $masterHealth[
                                        'employees_without_template'
                                    ] ?? 0
                                ) }}
                            </strong>
                        </div>

                        <div class="hr-master-health-item">
                            <span>Belum Punya Tim Kerja</span>
                            <strong>
                                {{ $formatNumber(
                                    $masterHealth[
                                        'employees_without_work_team'
                                    ] ?? 0
                                ) }}
                            </strong>
                        </div>

                        <div class="hr-master-health-item">
                            <span>Jadwal Kerja Belum Lengkap</span>
                            <strong>
                                {{ $formatNumber(
                                    $masterHealth[
                                        'incomplete_working_templates'
                                    ] ?? 0
                                ) }}
                            </strong>
                        </div>
                    </div>

                    <div class="hr-master-progress mt-3">
                        <span
                            style="width: {{ min(
                                max(
                                    (float) (
                                        $masterHealth['health_score']
                                        ?? 0
                                    ),
                                    0
                                ),
                                100
                            ) }}%"
                        ></span>
                    </div>

                    <div class="hr-card-explanation mt-3">
                        <i class="bi bi-info-circle-fill"></i>

                        <span>
                            Lengkapi data yang masih kosong agar hasil attendance dan laporan HR lebih akurat.
                        </span>
                    </div>

                    <div class="hr-quick-actions mt-3">
                        @if ($employeeMasterUrl)
                            <a href="{{ $employeeMasterUrl }}">
                                <i class="bi bi-people-fill"></i>
                                Data Employee
                            </a>
                        @endif

                        @if ($workingHoursUrl)
                            <a href="{{ $workingHoursUrl }}">
                                <i class="bi bi-clock-fill"></i>
                                Jadwal Kerja
                            </a>
                        @endif

                        @if ($holidayMasterUrl)
                            <a href="{{ $holidayMasterUrl }}">
                                <i class="bi bi-calendar2-event-fill"></i>
                                Hari Libur
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<x-ai-insight-widget
    title="AI HR Recommendations"
    :insight="$hrInsight"
    :summary="$hrDashboardAiSummaryText"
/>
@endsection

@push('styles')
<style>
    .hr-dashboard-page {
        --hr-purple: #5B3E8E;
        --hr-purple-dark: #493174;
        --hr-purple-soft: #f2edf8;
        --hr-yellow: #FFBE04;
        --hr-green: #3B8E4D;
        --hr-red: #c2414b;
        --hr-blue: #2f6da5;
        --hr-text: #2f2938;
        --hr-muted: #756d80;
        --hr-border: #e8e3ed;
        --hr-surface: #ffffff;
        --hr-surface-soft: #faf9fc;
    }

    .hr-dashboard-page .min-w-0 {
        min-width: 0;
    }

    .hr-freshness-banner {
        padding: 1.15rem;
        background: #ffffff;
        border: 1px solid var(--hr-border);
        border-left-width: 5px;
        border-radius: 1rem;
        box-shadow: 0 10px 28px rgba(49, 34, 72, .05);
    }

    .hr-freshness-banner.is-success {
        border-left-color: var(--hr-green);
    }

    .hr-freshness-banner.is-info {
        border-left-color: var(--hr-blue);
    }

    .hr-freshness-banner.is-warning {
        border-left-color: #d99a00;
    }

    .hr-freshness-banner.is-danger {
        border-left-color: var(--hr-red);
    }

    .hr-freshness-banner.is-neutral {
        border-left-color: #8b8493;
    }

    .hr-freshness-main {
        display: flex;
        align-items: flex-start;
        gap: .9rem;
    }

    .hr-freshness-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.75rem;
        height: 2.75rem;
        flex: 0 0 2.75rem;
        color: var(--hr-purple);
        background: var(--hr-purple-soft);
        border-radius: .85rem;
        font-size: 1.2rem;
    }

    .hr-freshness-eyebrow,
    .hr-insight-eyebrow {
        margin-bottom: .18rem;
        color: var(--hr-purple);
        font-size: .7rem;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .hr-freshness-title {
        color: var(--hr-text);
        font-size: 1.05rem;
        font-weight: 800;
    }

    .hr-freshness-description {
        margin-top: .2rem;
        color: var(--hr-muted);
        font-size: .84rem;
        line-height: 1.55;
    }

    .hr-freshness-facts {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .7rem;
        margin-top: 1rem;
    }

    .hr-freshness-fact {
        display: grid;
        gap: .2rem;
        padding: .75rem .85rem;
        background: var(--hr-surface-soft);
        border: 1px solid var(--hr-border);
        border-radius: .8rem;
    }

    .hr-freshness-fact span {
        color: var(--hr-muted);
        font-size: .72rem;
        font-weight: 700;
    }

    .hr-freshness-fact strong {
        color: var(--hr-text);
        font-size: .84rem;
        line-height: 1.35;
    }

    .hr-freshness-pending {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-top: .85rem;
        padding: .75rem .85rem;
        color: #735600;
        background: #fff9e8;
        border: 1px solid #f1dc9a;
        border-radius: .8rem;
        font-size: .8rem;
    }

    .hr-freshness-pending-label {
        margin-right: .3rem;
        font-weight: 800;
    }

    .hr-kpi-card {
        display: flex;
        flex-direction: column;
        min-height: 100%;
        padding: 1rem;
        background: #ffffff;
        border: 1px solid var(--hr-border);
        border-radius: 1rem;
        box-shadow: 0 8px 24px rgba(48, 34, 68, .045);
    }

    .hr-kpi-card.is-actionable {
        background: #fffaf0;
        border-color: #f0d999;
    }

    .hr-kpi-top {
        display: flex;
        align-items: center;
        gap: .65rem;
    }

    .hr-kpi-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.2rem;
        height: 2.2rem;
        flex: 0 0 2.2rem;
        color: var(--hr-purple);
        background: var(--hr-purple-soft);
        border-radius: .75rem;
        font-size: 1rem;
    }

    .hr-kpi-label {
        color: var(--hr-muted);
        font-size: .75rem;
        font-weight: 800;
        line-height: 1.25;
    }

    .hr-kpi-value {
        margin-top: .9rem;
        color: var(--hr-text);
        font-size: 1.65rem;
        font-weight: 900;
        line-height: 1;
    }

    .hr-kpi-help {
        margin-top: auto;
        padding-top: .75rem;
        color: var(--hr-muted);
        font-size: .72rem;
        line-height: 1.45;
    }

    .hr-kpi-change {
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        width: fit-content;
        margin-top: .55rem;
        padding: .23rem .45rem;
        border-radius: 999px;
        font-size: .66rem;
        font-weight: 800;
    }

    .hr-kpi-change.is-good {
        color: #2f7740;
        background: #edf8f0;
    }

    .hr-kpi-change.is-bad {
        color: #a33b44;
        background: #fceff0;
    }

    .hr-kpi-change.is-neutral {
        color: #6d6674;
        background: #f1eff3;
    }

    .hr-kpi-link {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        margin-top: .8rem;
        color: #7a5900;
        font-size: .74rem;
        font-weight: 800;
        text-decoration: none;
    }

    .hr-chart-shell {
        position: relative;
        height: 315px;
    }

    .hr-chart-shell.is-donut {
        height: 245px;
    }

    .hr-chart-shell.is-compact {
        height: 270px;
    }

    .hr-health-legend {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .55rem .85rem;
    }

    .hr-health-legend-item {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        align-items: center;
        gap: .45rem;
        color: var(--hr-muted);
        font-size: .74rem;
    }

    .hr-health-legend-item strong {
        color: var(--hr-text);
    }

    .hr-health-dot {
        width: .58rem;
        height: .58rem;
        border-radius: 50%;
        background: #8b8493;
    }

    .hr-health-dot[data-health-key="on_time"] {
        background: #3B8E4D;
    }

    .hr-health-dot[data-health-key="late"] {
        background: #e2a719;
    }

    .hr-health-dot[data-health-key="excused_late"] {
        background: #5B3E8E;
    }

    .hr-health-dot[data-health-key="half_day_leave"] {
        background: #8b65b6;
    }

    .hr-health-dot[data-health-key="full_day_leave"] {
        background: #73509d;
    }

    .hr-health-dot[data-health-key="absent"] {
        background: #c2414b;
    }

    .hr-health-dot[data-health-key="missing"] {
        background: #dc762a;
    }

    .hr-card-explanation {
        display: flex;
        align-items: flex-start;
        gap: .55rem;
        padding: .75rem .85rem;
        color: #655d6d;
        background: #faf9fc;
        border: 1px solid var(--hr-border);
        border-radius: .8rem;
        font-size: .74rem;
        line-height: 1.5;
    }

    .hr-card-explanation i {
        color: var(--hr-purple);
        margin-top: .05rem;
    }

    .hr-attention-item {
        display: flex;
        align-items: flex-start;
        gap: .8rem;
        padding: .9rem 0;
        border-bottom: 1px solid var(--hr-border);
    }

    .hr-attention-item:first-child {
        padding-top: 0;
    }

    .hr-attention-item:last-child {
        padding-bottom: 0;
        border-bottom: 0;
    }

    .hr-attention-avatar {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.55rem;
        height: 2.55rem;
        flex: 0 0 2.55rem;
        color: var(--hr-purple);
        background: var(--hr-purple-soft);
        border-radius: .85rem;
        font-size: .92rem;
        font-weight: 900;
    }

    .hr-attention-main {
        flex: 1 1 auto;
        min-width: 0;
    }

    .hr-attention-name {
        color: var(--hr-text);
        font-size: .86rem;
        font-weight: 800;
    }

    .hr-attention-meta,
    .hr-import-file-meta,
    .hr-holiday-meta {
        margin-top: .12rem;
        color: var(--hr-muted);
        font-size: .7rem;
    }

    .hr-attention-reason {
        margin-top: .55rem;
        color: #5f5767;
        font-size: .76rem;
        line-height: 1.45;
    }

    .hr-attention-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        margin-top: .55rem;
        color: var(--hr-muted);
        font-size: .68rem;
    }

    .hr-attention-footer a {
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        color: var(--hr-purple);
        font-weight: 800;
        text-decoration: none;
    }

    .hr-quality-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .65rem;
    }

    .hr-quality-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        padding: .75rem .8rem;
        background: #faf9fc;
        border: 1px solid var(--hr-border);
        border-radius: .8rem;
    }

    .hr-quality-item span {
        color: var(--hr-muted);
        font-size: .72rem;
        font-weight: 700;
        line-height: 1.3;
    }

    .hr-quality-item strong {
        color: var(--hr-text);
        font-size: .98rem;
    }

    .hr-quality-item.is-warning {
        background: #fff9e9;
        border-color: #efdda5;
    }

    .hr-quality-item.is-danger {
        background: #fdf2f3;
        border-color: #efc9cd;
    }

    .hr-quality-item.is-info {
        background: #f1f7fb;
        border-color: #cfe0ed;
    }

    .hr-quality-item.is-success {
        background: #f0f8f2;
        border-color: #cbe3d1;
    }

    .hr-top-attendance-list {
        display: grid;
    }

    .hr-top-attendance-item {
        display: grid;
        grid-template-columns:
            auto
            minmax(0, 1fr)
            auto
            auto;
        align-items: center;
        gap: .7rem;
        padding: .72rem 0;
        border-bottom: 1px solid var(--hr-border);
    }

    .hr-top-attendance-item:first-child {
        padding-top: 0;
    }

    .hr-top-attendance-item:last-child {
        padding-bottom: 0;
        border-bottom: 0;
    }

    .hr-top-attendance-rank {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2rem;
        height: 2rem;
        flex: 0 0 2rem;
        color: #716878;
        background: #f3f1f5;
        border: 1px solid #e2dde6;
        border-radius: .68rem;
        font-size: .72rem;
        font-weight: 900;
    }

    .hr-top-attendance-rank.is-top-three {
        color: #6f4e00;
        background: #fff6d9;
        border-color: #ead488;
    }

    .hr-top-attendance-person {
        min-width: 0;
    }

    .hr-top-attendance-name {
        color: var(--hr-text);
        font-size: .78rem;
        font-weight: 850;
        overflow-wrap: anywhere;
    }

    .hr-top-attendance-meta {
        margin-top: .12rem;
        color: var(--hr-muted);
        font-size: .65rem;
        line-height: 1.35;
    }

    .hr-top-attendance-rates {
        display: grid;
        grid-template-columns: repeat(2, auto);
        gap: .75rem;
    }

    .hr-top-attendance-rates div {
        display: grid;
        gap: .08rem;
        text-align: right;
    }

    .hr-top-attendance-rates span {
        color: var(--hr-muted);
        font-size: .59rem;
        font-weight: 700;
    }

    .hr-top-attendance-rates strong {
        color: var(--hr-purple);
        font-size: .76rem;
    }

    .hr-top-attendance-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.8rem;
        height: 1.8rem;
        color: var(--hr-purple);
        background: var(--hr-purple-soft);
        border-radius: .6rem;
        text-decoration: none;
    }

    .hr-ranking-note {
        display: flex;
        align-items: flex-start;
        gap: .5rem;
        padding: .7rem .8rem;
        color: #655d6d;
        background: #faf9fc;
        border: 1px solid var(--hr-border);
        border-radius: .8rem;
        font-size: .69rem;
        line-height: 1.45;
    }

    .hr-ranking-note i {
        color: var(--hr-purple);
        margin-top: .05rem;
    }

    .hr-team-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .8rem;
    }

    .hr-team-card {
        padding: .9rem;
        background: #ffffff;
        border: 1px solid var(--hr-border);
        border-radius: .9rem;
    }

    .hr-team-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: .75rem;
    }

    .hr-team-name {
        color: var(--hr-text);
        font-size: .84rem;
        font-weight: 850;
    }

    .hr-team-meta {
        margin-top: .15rem;
        color: var(--hr-muted);
        font-size: .68rem;
    }

    .hr-team-rate {
        color: var(--hr-purple);
        font-size: 1rem;
        font-weight: 900;
    }

    .hr-team-progress,
    .hr-master-progress {
        height: .38rem;
        margin-top: .75rem;
        overflow: hidden;
        background: #eeeaf2;
        border-radius: 999px;
    }

    .hr-team-progress span,
    .hr-master-progress span {
        display: block;
        height: 100%;
        background: var(--hr-purple);
        border-radius: inherit;
    }

    .hr-team-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: .45rem;
        margin-top: .75rem;
    }

    .hr-team-stats div {
        display: grid;
        gap: .1rem;
    }

    .hr-team-stats span {
        color: var(--hr-muted);
        font-size: .62rem;
        font-weight: 700;
    }

    .hr-team-stats strong {
        color: var(--hr-text);
        font-size: .76rem;
    }

    .hr-team-empty {
        grid-column: 1 / -1;
    }

    .hr-leave-summary {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .75rem;
    }

    .hr-leave-summary div {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        padding: .7rem .8rem;
        background: var(--hr-surface-soft);
        border: 1px solid var(--hr-border);
        border-radius: .8rem;
    }

    .hr-leave-summary span {
        color: var(--hr-muted);
        font-size: .72rem;
        font-weight: 700;
    }

    .hr-leave-summary strong {
        color: var(--hr-purple);
        font-size: .95rem;
    }

    .hr-import-file {
        display: flex;
        align-items: flex-start;
        gap: .75rem;
    }

    .hr-import-file-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.6rem;
        height: 2.6rem;
        flex: 0 0 2.6rem;
        color: var(--hr-green);
        background: #eef7f0;
        border-radius: .8rem;
        font-size: 1.1rem;
    }

    .hr-import-file-name {
        max-width: 100%;
        color: var(--hr-text);
        font-size: .86rem;
        font-weight: 850;
        overflow-wrap: anywhere;
    }

    .hr-import-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: .65rem;
        margin-top: 1rem;
    }

    .hr-import-stats div,
    .hr-master-health-item {
        display: grid;
        gap: .15rem;
        padding: .7rem;
        background: var(--hr-surface-soft);
        border: 1px solid var(--hr-border);
        border-radius: .8rem;
    }

    .hr-import-stats span,
    .hr-master-health-item span {
        color: var(--hr-muted);
        font-size: .66rem;
        font-weight: 700;
    }

    .hr-import-stats strong,
    .hr-master-health-item strong {
        color: var(--hr-text);
        font-size: .95rem;
    }

    .hr-import-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-top: 1rem;
        padding-top: .85rem;
        color: var(--hr-muted);
        border-top: 1px solid var(--hr-border);
        font-size: .7rem;
    }

    .hr-master-score {
        color: var(--hr-purple);
        font-size: 1.05rem;
        font-weight: 900;
    }

    .hr-master-health-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .65rem;
    }

    .hr-quick-actions {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .6rem;
    }

    .hr-quick-actions a {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: .4rem;
        min-height: 2.75rem;
        padding: .65rem;
        color: var(--hr-purple);
        background: #ffffff;
        border: 1px solid #dcd2e8;
        border-radius: .75rem;
        font-size: .69rem;
        font-weight: 800;
        text-align: center;
        text-decoration: none;
    }

    .hr-holiday-item {
        display: grid;
        grid-template-columns: minmax(115px, .65fr) minmax(0, 1.35fr);
        gap: .8rem;
        padding: .8rem 0;
        border-bottom: 1px solid var(--hr-border);
    }

    .hr-holiday-item:first-child {
        padding-top: 0;
    }

    .hr-holiday-item:last-child {
        padding-bottom: 0;
        border-bottom: 0;
    }

    .hr-holiday-date {
        display: grid;
        align-content: start;
        gap: .15rem;
    }

    .hr-holiday-date strong {
        color: var(--hr-purple);
        font-size: .82rem;
    }

    .hr-holiday-date span,
    .hr-holiday-notes {
        color: var(--hr-muted);
        font-size: .67rem;
        line-height: 1.4;
    }

    .hr-holiday-name {
        color: var(--hr-text);
        font-size: .82rem;
        font-weight: 800;
    }

    .hr-holiday-notes {
        margin-top: .35rem;
    }















    @media (max-width: 1199.98px) {
        .hr-freshness-facts {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .hr-team-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 767.98px) {
        .hr-dashboard-page {
            padding-left: 1rem !important;
            padding-right: 1rem !important;
        }

        .hr-freshness-facts,
        .hr-quality-grid,
        .hr-master-health-grid,
        .hr-leave-summary {
            grid-template-columns: 1fr;
        }

        .hr-freshness-pending,
        .hr-import-footer {
            align-items: flex-start;
            flex-direction: column;
        }

        .hr-team-stats,
        .hr-import-stats,
        .hr-quick-actions {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .hr-top-attendance-item {
            grid-template-columns: auto minmax(0, 1fr) auto;
        }

        .hr-top-attendance-rates {
            grid-column: 2 / -1;
            justify-content: start;
        }

        .hr-top-attendance-rates div {
            text-align: left;
        }

        .hr-health-legend {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 575.98px) {
        .hr-freshness-main,
        .hr-attention-item {
            gap: .65rem;
        }

        .hr-freshness-facts,
        .hr-team-stats,
        .hr-import-stats,
        .hr-quick-actions {
            grid-template-columns: 1fr;
        }

        .hr-holiday-item {
            grid-template-columns: 1fr;
            gap: .35rem;
        }

        .hr-attention-footer {
            align-items: flex-start;
            flex-direction: column;
        }

        .hr-chart-shell {
            height: 275px;
        }

        .hr-chart-shell.is-donut,
        .hr-chart-shell.is-compact {
            height: 235px;
        }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const filterForm = document.getElementById(
        'hrDashboardFilterForm'
    );

    const filterButton = document.getElementById(
        'hrDashboardFilterButton'
    );

    let filterTimer = null;

    function submitDashboardFilters() {
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
        .querySelectorAll('.hr-filter-auto-submit')
        .forEach(function (field) {
            field.addEventListener('change', function () {
                window.clearTimeout(filterTimer);

                filterTimer = window.setTimeout(
                    submitDashboardFilters,
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

    if (typeof Chart === 'undefined') {
        return;
    }

    Chart.defaults.font.family = getComputedStyle(
        document.body
    ).fontFamily;

    Chart.defaults.color = '#756d80';

    const attendanceHealth = @json($attendanceHealthChart ?? []);
    const weeklyTrend = @json($weeklyTrendChart ?? []);
    const leaveDistribution = @json($leaveDistributionChart ?? []);

    const healthCanvas = document.getElementById(
        'hrAttendanceHealthChart'
    );

    if (healthCanvas) {
        new Chart(healthCanvas, {
            type: 'doughnut',
            data: {
                labels: attendanceHealth.labels || [],
                datasets: [{
                    data:
                        attendanceHealth
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
                cutout: '68%',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                const value = Number(
                                    context.raw || 0
                                );

                                return ' '
                                    + context.label
                                    + ': '
                                    + value.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
    }

    const trendCanvas = document.getElementById(
        'hrWeeklyTrendChart'
    );

    if (trendCanvas) {
        new Chart(trendCanvas, {
            type: 'line',
            data: {
                labels: weeklyTrend.labels || [],
                datasets: [
                    {
                        label: 'Tingkat Kehadiran',
                        data:
                            weeklyTrend
                                .datasets
                                ?.presence_rate
                            || [],
                        borderColor: '#5B3E8E',
                        backgroundColor:
                            'rgba(91, 62, 142, .08)',
                        tension: .35,
                        borderWidth: 2.5,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        fill: false
                    },
                    {
                        label: 'Ketepatan Waktu',
                        data:
                            weeklyTrend
                                .datasets
                                ?.on_time_rate
                            || [],
                        borderColor: '#3B8E4D',
                        backgroundColor:
                            'rgba(59, 142, 77, .08)',
                        tension: .35,
                        borderWidth: 2.5,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        fill: false
                    },
                    {
                        label: 'Tingkat Ketidakhadiran',
                        data:
                            weeklyTrend
                                .datasets
                                ?.absence_rate
                            || [],
                        borderColor: '#C2414B',
                        backgroundColor:
                            'rgba(194, 65, 75, .08)',
                        tension: .35,
                        borderWidth: 2.5,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            boxWidth: 8,
                            padding: 18,
                            font: {
                                size: 11,
                                weight: 700
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return ' '
                                    + context.dataset.label
                                    + ': '
                                    + Number(
                                        context.raw || 0
                                    ).toFixed(1)
                                    + '%';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 10,
                                weight: 700
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        suggestedMax: 100,
                        ticks: {
                            callback: function (value) {
                                return value + '%';
                            },
                            font: {
                                size: 10
                            }
                        },
                        grid: {
                            color:
                                'rgba(117, 109, 128, .09)'
                        }
                    }
                }
            }
        });
    }

    const leaveCanvas = document.getElementById(
        'hrLeaveDistributionChart'
    );

    if (leaveCanvas) {
        new Chart(leaveCanvas, {
            type: 'bar',
            data: {
                labels: leaveDistribution.labels || [],
                datasets: [{
                    label: 'Catatan Cuti / Izin',
                    data:
                        leaveDistribution
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
                                    ).toLocaleString('id-ID')
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
