@extends('layouts.app-dashboard')

@section('title', 'Management Dashboard')

@section('content')
@php
    $managementDashboard = is_array($managementDashboard ?? null)
        ? $managementDashboard
        : [];

    $executiveKpis = collect(
        $executiveKpis
        ?? data_get($managementDashboard, 'executive_kpis', [])
    );

    $managementPriorities = collect(
        $managementPriorities
        ?? data_get($managementDashboard, 'management_priorities', [])
    )->take(5);

    $businessFunnel = is_array($businessFunnel ?? null)
        ? $businessFunnel
        : data_get($managementDashboard, 'business_funnel', []);

    $funnelStages = collect(
        data_get($businessFunnel, 'stages', [])
    );

    $businessTrends = is_array($businessTrends ?? null)
        ? $businessTrends
        : data_get($managementDashboard, 'business_trends', []);

    $revenueTrend = data_get(
        $businessTrends,
        'revenue',
        [
            'labels' => [],
            'data' => [],
            'year' => now()->year,
            'total' => 0,
        ]
    );

    $salesConversionTrend = data_get(
        $businessTrends,
        'sales_conversion',
        []
    );

    $divisionHealth = collect(
        $divisionHealth
        ?? data_get($managementDashboard, 'division_health', [])
    );

    $upcomingCommitments = collect(
        $upcomingCommitments
        ?? data_get($managementDashboard, 'upcoming_commitments', [])
    );

    $dataFreshness = is_array($dataFreshness ?? null)
        ? $dataFreshness
        : data_get($managementDashboard, 'data_freshness', []);

    $freshnessItems = collect(
        data_get($dataFreshness, 'items', [])
    );

    $sourcePeriods = collect(
        $sourcePeriods
        ?? data_get($managementDashboard, 'source_periods', [])
    );

    $managementSummary = is_array($managementSummary ?? null)
        ? $managementSummary
        : data_get($managementDashboard, 'summary', []);

    $dashboardAiSummaryText = trim(
        (string) (
            $dashboardAiSummaryText
            ?? data_get($managementDashboard, 'ai_summary_text')
            ?? data_get($managementSummary, 'summary_text')
            ?? ''
        )
    );

    if ($dashboardAiSummaryText === '') {
        $dashboardAiSummaryText =
            'Ringkasan management belum tersedia karena data utama masih kosong.';
    }

    $period = data_get(
        $managementDashboard,
        'period',
        [
            'label' => '-',
            'note' => null,
        ]
    );

    $selectedMonth = (string) (
        $selectedMonth
        ?? data_get(
            $managementDashboard,
            'selected_month'
        )
        ?? now()->format('Y-m')
    );

    try {
        $selectedMonthDate = \Carbon\Carbon::createFromFormat(
            'Y-m',
            $selectedMonth
        )->startOfMonth();
    } catch (\Throwable) {
        $selectedMonthDate = now()->startOfMonth();
        $selectedMonth = $selectedMonthDate->format('Y-m');
    }

    $monthOptions = collect(
        range(1, now()->month)
    )->map(function (int $monthNumber) {
        $monthDate = now()
            ->startOfYear()
            ->month($monthNumber);

        return [
            'value' => $monthDate->format('Y-m'),
            'label' => $monthDate->translatedFormat('M'),
            'full_label' => $monthDate->translatedFormat('F Y'),
        ];
    });

    $generatedAt = data_get(
        $managementDashboard,
        'generated_at'
    );

    $formatDate = static function (
        mixed $value,
        string $fallback = '-'
    ): string {
        if (blank($value)) {
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
        mixed $value,
        string $fallback = '-'
    ): string {
        if (blank($value)) {
            return $fallback;
        }

        try {
            return \Carbon\Carbon::parse($value)
                ->translatedFormat('d M Y H:i');
        } catch (\Throwable) {
            return $fallback;
        }
    };

    $formatClock = static function (
        mixed $value,
        string $fallback = ''
    ): string {
        if (blank($value)) {
            return $fallback;
        }

        try {
            return \Carbon\Carbon::parse($value)
                ->format('H:i');
        } catch (\Throwable) {
            return substr((string) $value, 0, 5);
        }
    };

    $formatDivisionKpi = static function (
        array $kpi
    ): string {
        $value = $kpi['value'] ?? 0;
        $format = $kpi['format'] ?? null;

        return match ($format) {
            'currency' => 'Rp '
                . number_format(
                    (float) $value,
                    0,
                    ',',
                    '.'
                ),
            'percent' => number_format(
                (float) $value,
                1
            ) . '%',
            default => number_format(
                (float) $value,
                is_float($value) ? 1 : 0,
                ',',
                '.'
            ),
        };
    };

    $statusBadgeClass = static function (
        ?string $status
    ): string {
        return match ($status) {
            'healthy', 'good', 'available' =>
                'bg-success-subtle text-success',
            'attention', 'action' =>
                'bg-warning-subtle text-warning',
            'critical' =>
                'bg-danger-subtle text-danger',
            default =>
                'bg-secondary-subtle text-secondary',
        };
    };

    $statusText = static function (
        ?string $status
    ): string {
        return match ($status) {
            'healthy', 'good' => 'Sehat',
            'attention' => 'Perlu Perhatian',
            'action' => 'Perlu Tindakan',
            'critical' => 'Kritis',
            'available' => 'Tersedia',
            'unavailable' => 'Belum Tersedia',
            default => 'Belum Diketahui',
        };
    };

    $statusIcon = static function (
        ?string $status
    ): string {
        return match ($status) {
            'healthy', 'good', 'available' =>
                'bi-check-circle-fill',
            'attention', 'action' =>
                'bi-exclamation-triangle-fill',
            'critical' =>
                'bi-exclamation-octagon-fill',
            default =>
                'bi-info-circle-fill',
        };
    };

    $priorityIcon = static function (
        ?string $type
    ): string {
        return match ($type) {
            'critical' => 'bi-exclamation-octagon-fill',
            'action' => 'bi-lightning-charge-fill',
            'warning' => 'bi-exclamation-triangle-fill',
            'good', 'healthy' => 'bi-check-circle-fill',
            default => 'bi-info-circle-fill',
        };
    };

    $kpiIcon = static function (
        ?string $key
    ): string {
        return match ($key) {
            'cash_collected' => 'bi-cash-stack',
            'paid_conversion' => 'bi-graph-up-arrow',
            'qualified_pipeline' => 'bi-person-check-fill',
            'outstanding_revenue' => 'bi-wallet2',
            'seat_utilization' => 'bi-people-fill',
            'delivery_risk' => 'bi-exclamation-diamond-fill',
            default => 'bi-bar-chart-fill',
        };
    };

    $funnelIcon = static function (
        ?string $key
    ): string {
        return match ($key) {
            'leads' => 'bi-person-lines-fill',
            'interacted' => 'bi-chat-left-text-fill',
            'consultation' => 'bi-calendar2-check-fill',
            'hot_leads' => 'bi-fire',
            'orders_created' => 'bi-receipt',
            'paid' => 'bi-cash-coin',
            default => 'bi-circle-fill',
        };
    };

    $divisionRoute = static function (
        ?string $divisionKey
    ): ?string {
        $routeName = match ($divisionKey) {
            'sales' => 'sales.dashboard',
            'marketing' => 'marketing.dashboard',
            'finance' => 'finance.dashboard',
            'academic' => 'academic.dashboard',
            'hr' => 'hr.dashboard',
            default => null,
        };

        if (
            ! $routeName
            || ! \Illuminate\Support\Facades\Route::has(
                $routeName
            )
        ) {
            return null;
        }

        return route($routeName);
    };

    $divisionIcon = static function (
        ?string $divisionKey
    ): string {
        return match ($divisionKey) {
            'sales' => 'bi-graph-up-arrow',
            'marketing' => 'bi-megaphone-fill',
            'finance' => 'bi-cash-stack',
            'academic' => 'bi-mortarboard-fill',
            'hr' => 'bi-people-fill',
            'operations' => 'bi-kanban-fill',
            default => 'bi-grid-fill',
        };
    };

    $priorityDivisionKey = static function (
        ?string $division
    ): ?string {
        return match (
            mb_strtolower(
                trim((string) $division)
            )
        ) {
            'sales' => 'sales',
            'marketing' => 'marketing',
            'finance' => 'finance',
            'academic' => 'academic',
            'hr' => 'hr',
            default => null,
        };
    };

    $freshnessStatus = data_get(
        $dataFreshness,
        'status',
        'critical'
    );

@endphp

<div class="container-fluid px-4 py-4 management-dashboard-page">
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div class="flex-grow-1">
                <div class="page-eyebrow">
                    Executive Overview
                </div>

                <h1 class="page-title mb-2">
                    Executive Business Overview
                </h1>

                <p class="page-subtitle mb-0">
                    Ringkasan performa bisnis, keuangan, delivery program,
                    dan prioritas operasional untuk membantu management
                    menentukan tindakan berikutnya.
                </p>

                <div
                    class="management-month-filter mt-3"
                    role="navigation"
                    aria-label="Pilih bulan dashboard"
                >
                    @foreach ($monthOptions as $monthOption)
                        <a
                            href="{{ request()->fullUrlWithQuery([
                                'month' => $monthOption['value'],
                            ]) }}"
                            class="btn btn-sm btn-modern {{ $selectedMonth === $monthOption['value'] ? 'btn-warning' : 'btn-light' }}"
                            title="{{ $monthOption['full_label'] }}"
                        >
                            {{ $monthOption['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <span class="badge rounded-pill bg-light text-dark border">
                    <i class="bi bi-calendar3 me-1"></i>
                    {{ data_get($period, 'label', '-') }}
                </span>

                <span class="badge rounded-pill bg-light text-dark border">
                    <i class="bi bi-clock-history me-1"></i>
                    Diperbarui {{ $formatDateTime($generatedAt) }}
                </span>
            </div>

            <div class="w-100 d-flex justify-content-end mt-2">
                <a
                    href="{{ route('executive-center.dashboard') }}"
                    class="btn btn-sm btn-light btn-modern border"
                    title="Buka Executive Center"
                >
                    <i class="bi bi-bar-chart-line-fill me-1"></i>
                    Executive Center
                </a>
            </div>
        </div>
    </div>

    <div class="dashboard-section-label mb-3">
        <div class="dashboard-section-eyebrow">
            Executive KPI
        </div>

        <h4 class="dashboard-section-title mb-1">
            Kondisi Bisnis Utama
        </h4>

        <p class="dashboard-section-subtitle mb-0">
            Enam indikator utama untuk membaca cashflow, konversi,
            pipeline, kapasitas, dan risiko delivery.
        </p>
    </div>

    <div class="sales-kpi-scroll-shell mb-4">
        

        <div
            class="sales-kpi-scroll"
            role="region"
            aria-label="Executive KPI"
            tabindex="0"
        >
            @forelse ($executiveKpis as $kpi)
                <div class="sales-kpi-scroll-item">
                    <div class="stat-card h-100 management-kpi-card">
                        <div class="stat-card-top">
                            <div class="stat-icon-wrap {{ $statusBadgeClass($kpi['status'] ?? null) }}">
                                <i class="bi {{ $kpiIcon($kpi['key'] ?? null) }}"></i>
                            </div>

                            <div>
                                <div class="stat-title">
                                    {{ $kpi['label'] ?? 'KPI' }}
                                </div>

                                <div class="stat-value {{ ($kpi['key'] ?? null) === 'cash_collected' || ($kpi['key'] ?? null) === 'outstanding_revenue' ? 'stat-value-currency' : '' }}">
                                    {{ $kpi['value_label'] ?? '-' }}
                                </div>
                            </div>
                        </div>

                        <div class="stat-description">
                            {{ $kpi['detail_label']
                                ?? $kpi['comparison_label']
                                ?? '-' }}
                        </div>

                        <div class="d-flex justify-content-between align-items-center gap-2 mt-3">
                            <span class="badge rounded-pill {{ $statusBadgeClass($kpi['status'] ?? null) }}">
                                {{ $statusText($kpi['status'] ?? null) }}
                            </span>

                            <span class="small text-muted">
                                {{ $kpi['period_label'] ?? '-' }}
                            </span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="w-100">
                    <div class="empty-state-box">
                        <div class="empty-state-icon">
                            <i class="bi bi-bar-chart-fill"></i>
                        </div>

                        <h5 class="empty-state-title">
                            KPI executive belum tersedia
                        </h5>

                        <p class="empty-state-text mb-0">
                            Data akan tampil setelah sumber bisnis utama tersedia.
                        </p>
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-8">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">
                            Prioritas Management
                        </h5>

                        <p class="content-card-subtitle mb-0">
                            Masalah dengan dampak dan urgensi tertinggi
                            yang perlu ditindaklanjuti terlebih dahulu.
                        </p>
                    </div>

                    <span class="badge rounded-pill bg-light text-dark border">
                        {{ number_format(
                            $managementPriorities->count()
                        ) }}
                        prioritas
                    </span>
                </div>

                <div class="content-card-body">
                    @forelse ($managementPriorities as $priority)
                        @php
                            $priorityRoute = $divisionRoute(
                                $priorityDivisionKey(
                                    $priority['division'] ?? null
                                )
                            );
                        @endphp

                        <div class="management-priority-item d-flex align-items-start gap-3">
                            <div class="stat-icon-wrap management-priority-icon {{ $statusBadgeClass($priority['type'] ?? null) }}">
                                <i class="bi {{ $priorityIcon($priority['type'] ?? null) }}"></i>
                            </div>

                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                    <div>
                                        <div class="fw-semibold text-dark">
                                            {{ $priority['title']
                                                ?? 'Prioritas Management' }}
                                        </div>

                                        <div class="small text-muted mt-1">
                                            {{ $priority['message'] ?? '-' }}
                                        </div>
                                    </div>

                                    <span class="badge rounded-pill {{ $statusBadgeClass($priority['type'] ?? null) }}">
                                        {{ $priority['division']
                                            ?? 'Management' }}
                                    </span>
                                </div>

                                @if ($priorityRoute)
                                    <a
                                        href="{{ $priorityRoute }}"
                                        class="btn btn-sm btn-primary btn-modern mt-3"
                                    >
                                        Buka Dashboard
                                        <i class="bi bi-arrow-right ms-1"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="empty-state-box">
                            <div class="empty-state-icon">
                                <i class="bi bi-check2-circle"></i>
                            </div>

                            <h5 class="empty-state-title">
                                Tidak ada prioritas kritis
                            </h5>

                            <p class="empty-state-text mb-0">
                                Belum ada masalah utama yang memerlukan
                                tindakan management pada snapshot terbaru.
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">
                            Status Pembaruan Data
                        </h5>

                        <p class="content-card-subtitle mb-0">
                            Ketersediaan data dari integrasi dan modul utama.
                        </p>
                    </div>

                    <span class="badge rounded-pill {{ $statusBadgeClass($freshnessStatus) }}">
                        {{ $statusText($freshnessStatus) }}
                    </span>
                </div>

                <div class="content-card-body">
                    @forelse ($freshnessItems as $item)
                        <div class="management-freshness-item d-flex justify-content-between align-items-start gap-3">
                            <div>
                                <div class="fw-semibold text-dark">
                                    {{ $item['label'] ?? '-' }}
                                </div>

                                <div class="small text-muted mt-1">
                                    {{ $item['period_label'] ?? '-' }}
                                </div>

                                <div class="small text-muted">
                                    Sinkronisasi:
                                    {{ $formatDateTime(
                                        $item['last_synced_at']
                                            ?? null
                                    ) }}
                                </div>
                            </div>

                            <span class="badge rounded-pill {{ $statusBadgeClass($item['status'] ?? null) }}">
                                {{ $statusText(
                                    $item['status'] ?? null
                                ) }}
                            </span>
                        </div>
                    @empty
                        <div class="empty-state-box">
                            <div class="empty-state-icon">
                                <i class="bi bi-cloud-slash"></i>
                            </div>

                            <h5 class="empty-state-title">
                                Status data belum tersedia
                            </h5>

                            <p class="empty-state-text mb-0">
                                Belum ada informasi pembaruan sumber data.
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-section-label mb-3">
        <div class="dashboard-section-eyebrow">
            Business Funnel
        </div>

        <h4 class="dashboard-section-title mb-1">
            Perjalanan Lead hingga Pembayaran
        </h4>

        <p class="dashboard-section-subtitle mb-0">
            Menunjukkan perkembangan lead dari tahap awal,
            interaksi, konsultasi, order, hingga pembayaran.
        </p>
    </div>

    <div class="row g-3 mb-4">
        @forelse ($funnelStages as $stage)
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="funnel-card h-100">
                    <div class="funnel-card-top">
                        <div class="funnel-icon-wrap">
                            <i class="bi {{ $funnelIcon($stage['key'] ?? null) }}"></i>
                        </div>

                        <div>
                            <div class="funnel-title">
                                {{ $stage['label'] ?? '-' }}
                            </div>

                            <div class="funnel-value">
                                {{ number_format(
                                    (float) (
                                        $stage['value'] ?? 0
                                    ),
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </div>
                        </div>
                    </div>

                    <div class="funnel-description">
                        @if (array_key_exists('rate', $stage))
                            {{ number_format(
                                (float) $stage['rate'],
                                1
                            ) }}%
                            dari total leads.
                        @else
                            Titik awal funnel pada periode ini.
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-funnel-fill"></i>
                    </div>

                    <h5 class="empty-state-title">
                        Funnel bisnis belum tersedia
                    </h5>

                    <p class="empty-state-text mb-0">
                        Data funnel akan tampil setelah laporan sales,
                        order, dan pembayaran tersedia.
                    </p>
                </div>
            </div>
        @endforelse
    </div>


    <div class="dashboard-section-label mb-3">
        <div class="dashboard-section-eyebrow">
            Business Trend
        </div>

        <h4 class="dashboard-section-title mb-1">
            Revenue dan Konversi
        </h4>

        <p class="dashboard-section-subtitle mb-0">
            Membandingkan perkembangan pendapatan dengan posisi
            funnel sales pada periode berjalan.
        </p>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-7">
            <div class="content-card h-100">
                <div class="content-card-header d-flex justify-content-between align-items-start gap-3 flex-wrap">
                    <div>
                        <h5 class="content-card-title mb-1">
                            Revenue Trend
                        </h5>

                        <p class="content-card-subtitle mb-0">
                            Pendapatan terkonfirmasi per bulan selama
                            tahun {{ data_get($revenueTrend, 'year', now()->year) }}.
                        </p>
                    </div>

                    <div class="revenue-total-box">
                        <div class="revenue-total-label">
                            Total Tahun Ini
                        </div>

                        <div class="revenue-total-value">
                            Rp {{ number_format(
                                (float) data_get(
                                    $revenueTrend,
                                    'total',
                                    0
                                ),
                                0,
                                ',',
                                '.'
                            ) }}
                        </div>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="chart-wrap">
                        <canvas
                            id="managementRevenueChart"
                            height="110"
                        ></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">
                            Lead-to-Paid Snapshot
                        </h5>

                        <p class="content-card-subtitle mb-0">
                            Perbandingan jumlah lead pada setiap tahap
                            funnel bulan berjalan.
                        </p>
                    </div>

                    <span class="badge rounded-pill bg-light text-dark border">
                        {{ data_get(
                            $salesConversionTrend,
                            'period_label',
                            'Bulan berjalan'
                        ) }}
                    </span>
                </div>

                <div class="content-card-body">
                    <div class="chart-wrap">
                        <canvas
                            id="managementSalesConversionChart"
                            height="110"
                        ></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-section-label mb-3">
        <div class="dashboard-section-eyebrow">
            Division Health
        </div>

        <h4 class="dashboard-section-title mb-1">
            Kondisi Setiap Divisi
        </h4>

        <p class="dashboard-section-subtitle mb-0">
            Ringkasan performa dan masalah utama dari setiap
            dashboard divisi.
        </p>
    </div>

    <div class="row g-3 mb-4">
        @forelse ($divisionHealth as $division)
            @php
                $divisionUrl = $divisionRoute(
                    $division['key'] ?? null
                );
            @endphp

            <div class="col-xl-4 col-md-6">
                <div class="content-card h-100 d-flex flex-column management-division-card">
                    <div class="content-card-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon-wrap management-division-icon">
                                <i class="bi {{ $divisionIcon($division['key'] ?? null) }}"></i>
                            </div>

                            <div class="min-w-0">
                                <h5 class="content-card-title mb-1">
                                    {{ $division['label'] ?? '-' }}
                                </h5>

                                <span class="badge rounded-pill {{ $statusBadgeClass($division['status'] ?? null) }}">
                                    {{ $statusText(
                                        $division['status'] ?? null
                                    ) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="content-card-body d-flex flex-column flex-grow-1">
                        <div class="row g-2 mb-3">
                            @foreach (
                                collect(
                                    $division['kpis'] ?? []
                                )
                                as $divisionKpi
                            )
                                <div class="col-4">
                                    <div class="stat-card h-100 management-division-kpi-card">
                                        <div class="stat-title">
                                            {{ $divisionKpi['label']
                                                ?? '-' }}
                                        </div>

                                        <div class="stat-value mt-2">
                                            {{ $formatDivisionKpi(
                                                $divisionKpi
                                            ) }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="management-division-issue mb-3">
                            <div class="small text-muted mb-1">
                                Hal yang perlu diperhatikan
                            </div>

                            <div class="fw-semibold text-dark">
                                {{ $division['main_issue'] ?? '-' }}
                            </div>
                        </div>

                        @if ($divisionUrl)
                            <a
                                href="{{ $divisionUrl }}"
                                class="btn btn-primary btn-modern w-100 mt-auto"
                            >
                                Buka Dashboard
                                <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-grid-fill"></i>
                    </div>

                    <h5 class="empty-state-title">
                        Ringkasan divisi belum tersedia
                    </h5>

                    <p class="empty-state-text mb-0">
                        Division Health akan tampil setelah data
                        dashboard divisi berhasil dimuat.
                    </p>
                </div>
            </div>
        @endforelse
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-7">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">
                            Upcoming Commitments
                        </h5>

                        <p class="content-card-subtitle mb-0">
                            Jadwal batch, trial, workshop, dan hari libur
                            yang perlu diperhatikan management.
                        </p>
                    </div>

                    <span class="badge rounded-pill bg-light text-dark border">
                        {{ number_format(
                            $upcomingCommitments->count()
                        ) }}
                        agenda
                    </span>
                </div>

                <div class="content-card-body">
                    @forelse ($upcomingCommitments as $commitment)
                        <div class="management-commitment-item d-flex align-items-start gap-3">
                            <div class="stat-icon-wrap management-commitment-icon">
                                <i class="bi bi-calendar-event-fill"></i>
                            </div>

                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                    <div>
                                        <div class="fw-semibold text-dark">
                                            {{ $commitment['title'] ?? '-' }}
                                        </div>

                                        <div class="small text-muted mt-1">
                                            {{ $commitment['description']
                                                ?? '-' }}
                                        </div>
                                    </div>

                                    <div class="small text-muted">
                                        <i class="bi bi-calendar3 me-1"></i>
                                        {{ $formatDate(
                                            $commitment['date'] ?? null
                                        ) }}

                                        @if (
                                            filled(
                                                $commitment['time'] ?? null
                                            )
                                        )
                                            <span class="mx-1">·</span>
                                            <i class="bi bi-clock me-1"></i>
                                            {{ $formatClock(
                                                $commitment['time']
                                            ) }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state-box">
                            <div class="empty-state-icon">
                                <i class="bi bi-calendar-check-fill"></i>
                            </div>

                            <h5 class="empty-state-title">
                                Belum ada agenda mendatang
                            </h5>

                            <p class="empty-state-text mb-0">
                                Jadwal batch, trial, workshop, atau
                                hari libur belum tersedia.
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
                            Periode Sumber Data
                        </h5>

                        <p class="content-card-subtitle mb-0">
                            Setiap modul dapat memakai periode dan waktu
                            pembaruan yang berbeda.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    @forelse ($sourcePeriods as $source)
                        <div class="management-source-period-item">
                            <div class="fw-semibold text-dark">
                                {{ $source['label'] ?? '-' }}
                            </div>

                            <div class="small text-muted mt-1">
                                {{ $formatDate(
                                    $source['date_from'] ?? null
                                ) }}
                                –
                                {{ $formatDate(
                                    $source['date_to'] ?? null
                                ) }}
                            </div>

                            <div class="small text-muted mt-1">
                                {{ $source['description'] ?? '-' }}
                            </div>
                        </div>
                    @empty
                        <div class="empty-state-box">
                            <div class="empty-state-icon">
                                <i class="bi bi-calendar-range-fill"></i>
                            </div>

                            <h5 class="empty-state-title">
                                Periode sumber belum tersedia
                            </h5>

                            <p class="empty-state-text mb-0">
                                Informasi periode akan tampil setelah
                                sumber data berhasil dimuat.
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<x-ai-insight-widget
    title="AI Management Recommendations"
    :insight="$managementSummary"
    :summary="$dashboardAiSummaryText"
/>
@endsection


@push('styles')
<style>
    .management-dashboard-page .management-month-filter {
        display: flex;
        flex-wrap: nowrap;
        gap: .5rem;
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        overflow-y: hidden;
        padding: .1rem .1rem .65rem;
        scroll-snap-type: x proximity;
        scrollbar-width: thin;
        scrollbar-color:
            rgba(91, 62, 142, .62)
            rgba(91, 62, 142, .08);
        -webkit-overflow-scrolling: touch;
    }

    .management-dashboard-page
    .management-month-filter .btn {
        flex: 0 0 auto;
        min-width: 52px;
        scroll-snap-align: start;
    }

    .management-dashboard-page
    .management-month-filter::-webkit-scrollbar {
        height: 8px;
    }

    .management-dashboard-page
    .management-month-filter::-webkit-scrollbar-track {
        background: rgba(91, 62, 142, .07);
        border-radius: 999px;
    }

    .management-dashboard-page
    .management-month-filter::-webkit-scrollbar-thumb {
        background:
            linear-gradient(
                90deg,
                rgba(91, 62, 142, .72),
                rgba(91, 62, 142, .42)
            );
        border: 2px solid rgba(248, 250, 252, .95);
        border-radius: 999px;
    }

    .management-dashboard-page
    .management-month-filter::-webkit-scrollbar-thumb:hover {
        background:
            linear-gradient(
                90deg,
                rgba(91, 62, 142, .92),
                rgba(91, 62, 142, .62)
            );
    }

    .management-dashboard-page .sales-kpi-scroll-shell {
        position: relative;
        border-radius: 22px;
    }

    .management-dashboard-page .sales-kpi-scroll-hint {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: .45rem;
        margin: 0 .2rem .55rem;
        color: #64748b;
        font-size: .74rem;
        font-weight: 700;
    }

    .management-dashboard-page .sales-kpi-scroll-hint i {
        color: #5B3E8E;
    }

    .management-dashboard-page .sales-kpi-scroll {
        display: flex;
        flex-wrap: nowrap;
        gap: 1rem;
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        overflow-y: hidden;
        padding: .15rem .15rem .9rem;
        scroll-snap-type: x mandatory;
        scroll-padding-inline: .15rem;
        scrollbar-width: thin;
        scrollbar-color:
            rgba(91, 62, 142, .62)
            rgba(91, 62, 142, .08);
        -webkit-overflow-scrolling: touch;
    }

    .management-dashboard-page
    .sales-kpi-scroll:focus-visible {
        outline: 3px solid rgba(91, 62, 142, .18);
        outline-offset: 4px;
        border-radius: 18px;
    }

    .management-dashboard-page
    .sales-kpi-scroll::-webkit-scrollbar {
        height: 10px;
    }

    .management-dashboard-page
    .sales-kpi-scroll::-webkit-scrollbar-track {
        background: rgba(91, 62, 142, .07);
        border-radius: 999px;
        margin-inline: .2rem;
    }

    .management-dashboard-page
    .sales-kpi-scroll::-webkit-scrollbar-thumb {
        background:
            linear-gradient(
                90deg,
                rgba(91, 62, 142, .72),
                rgba(91, 62, 142, .42)
            );
        border: 2px solid rgba(248, 250, 252, .95);
        border-radius: 999px;
    }

    .management-dashboard-page
    .sales-kpi-scroll::-webkit-scrollbar-thumb:hover {
        background:
            linear-gradient(
                90deg,
                rgba(91, 62, 142, .92),
                rgba(91, 62, 142, .62)
            );
    }

    .management-dashboard-page .sales-kpi-scroll-item {
        flex: 0 0 calc((100% - 2rem) / 3);
        min-width: 290px;
        scroll-snap-align: start;
        scroll-snap-stop: always;
    }

    .management-dashboard-page .management-kpi-card {
        display: flex;
        flex-direction: column;
        min-height: 190px;
    }

    .management-dashboard-page
    .management-kpi-card .stat-description {
        flex: 1 1 auto;
    }

    .management-dashboard-page .management-priority-item,
    .management-dashboard-page .management-freshness-item,
    .management-dashboard-page .management-commitment-item,
    .management-dashboard-page .management-source-period-item {
        padding: 1rem 0;
        border-bottom: 1px solid rgba(15, 23, 42, .07);
    }

    .management-dashboard-page
    .management-priority-item:first-child,
    .management-dashboard-page
    .management-freshness-item:first-child,
    .management-dashboard-page
    .management-commitment-item:first-child,
    .management-dashboard-page
    .management-source-period-item:first-child {
        padding-top: 0;
    }

    .management-dashboard-page
    .management-priority-item:last-child,
    .management-dashboard-page
    .management-freshness-item:last-child,
    .management-dashboard-page
    .management-commitment-item:last-child,
    .management-dashboard-page
    .management-source-period-item:last-child {
        padding-bottom: 0;
        border-bottom: 0;
    }

    .management-dashboard-page .management-priority-icon,
    .management-dashboard-page .management-commitment-icon {
        width: 44px;
        height: 44px;
        flex: 0 0 44px;
        border-radius: 14px;
        font-size: 1rem;
    }

    .management-dashboard-page .management-commitment-icon {
        color: #5B3E8E;
        background: rgba(91, 62, 142, .11);
    }

    .management-dashboard-page .min-w-0 {
        min-width: 0;
    }

    .management-dashboard-page .management-division-card {
        overflow: hidden;
        background: #ffffff;
        border-color: rgba(15, 23, 42, .08);
        box-shadow: 0 12px 28px rgba(15, 23, 42, .045);
        transition:
            transform .18s ease,
            border-color .18s ease,
            box-shadow .18s ease;
    }

    .management-dashboard-page
    .management-division-card:hover {
        transform: translateY(-2px);
        border-color: rgba(91, 62, 142, .20);
        box-shadow: 0 16px 34px rgba(15, 23, 42, .075);
    }

    .management-dashboard-page
    .management-division-card > .content-card-header {
        min-height: 92px;
        padding: 1.1rem 1.15rem;
        background: #ffffff;
        border-bottom: 1px solid rgba(15, 23, 42, .07);
    }

    .management-dashboard-page
    .management-division-card
    .content-card-title {
        color: #111827;
        font-size: 1rem;
        font-weight: 800;
        line-height: 1.25;
    }

    .management-dashboard-page
    .management-division-icon {
        width: 48px;
        height: 48px;
        flex: 0 0 48px;
        color: #5B3E8E;
        background: rgba(91, 62, 142, .11);
        border-radius: 15px;
        font-size: 1.05rem;
    }

    .management-dashboard-page
    .management-division-card > .content-card-body {
        padding: 1.15rem;
    }

    .management-dashboard-page
    .management-division-kpi-card {
        min-height: 112px;
        padding: .85rem;
        background: #f8fafc;
        border-color: rgba(15, 23, 42, .07);
        border-radius: 16px;
        box-shadow: none;
    }

    .management-dashboard-page
    .management-division-kpi-card .stat-title {
        min-height: 32px;
        color: #64748b;
        font-size: .7rem;
        font-weight: 700;
        line-height: 1.3;
    }

    .management-dashboard-page
    .management-division-kpi-card .stat-value {
        margin-top: .45rem !important;
        color: #111827;
        font-size: 1.1rem;
        font-weight: 850;
        line-height: 1.25;
        overflow-wrap: anywhere;
    }

    .management-dashboard-page .management-division-issue {
        padding: .9rem 1rem;
        color: #5f5767;
        background: rgba(91, 62, 142, .045);
        border: 1px solid rgba(91, 62, 142, .10);
        border-radius: 16px;
    }

    .management-dashboard-page
    .management-division-card .btn-modern {
        min-height: 44px;
        padding: .65rem 1rem;
        font-size: .875rem;
        font-weight: 700;
        line-height: 1.25;
    }

    .management-dashboard-page .chart-wrap {
        min-height: 340px;
    }

    @media (max-width: 1199.98px) {
        .management-dashboard-page
        .sales-kpi-scroll-item {
            flex-basis: calc((100% - 1rem) / 2);
            min-width: 280px;
        }
    }

    @media (max-width: 767.98px) {
        .management-dashboard-page
        .sales-kpi-scroll-item {
            flex-basis: min(84vw, 340px);
            min-width: 270px;
        }

        .management-dashboard-page
        .sales-kpi-scroll-hint {
            justify-content: flex-start;
        }

        .management-dashboard-page
        .management-month-filter {
            padding-bottom: .55rem;
        }

        .management-dashboard-page .chart-wrap {
            min-height: 280px;
        }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') {
        return;
    }

    const revenueLabels = @json(
        data_get($revenueTrend, 'labels', [])
    );

    const revenueValues = @json(
        data_get($revenueTrend, 'data', [])
    );

    const revenueCanvas = document.getElementById(
        'managementRevenueChart'
    );

    if (revenueCanvas) {
        new Chart(revenueCanvas, {
            type: 'bar',
            data: {
                labels: revenueLabels,
                datasets: [{
                    label: 'Pendapatan',
                    data: revenueValues,
                    borderRadius: 8,
                    maxBarThickness: 42,
                    backgroundColor: 'rgba(91, 62, 142, 0.82)',
                    borderColor: '#5B3E8E',
                    borderWidth: 1
                }]
            },
            options: {
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
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

                                return ' Rp '
                                    + value.toLocaleString(
                                        'id-ID'
                                    );
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                return 'Rp '
                                    + Number(value)
                                        .toLocaleString(
                                            'id-ID'
                                        );
                            }
                        }
                    }
                }
            }
        });
    }

    const salesConversionLabels = [
        'Leads',
        'Interacted',
        'Consultation',
        'Hot Leads',
        'Paid'
    ];

    const salesConversionValues = [
        Number(@json(
            data_get(
                $salesConversionTrend,
                'leads',
                0
            )
        )),
        Number(@json(
            data_get(
                $salesConversionTrend,
                'interacted',
                0
            )
        )),
        Number(@json(
            data_get(
                $salesConversionTrend,
                'consultation',
                0
            )
        )),
        Number(@json(
            data_get(
                $salesConversionTrend,
                'hot_leads',
                0
            )
        )),
        Number(@json(
            data_get(
                $salesConversionTrend,
                'paid',
                0
            )
        ))
    ];

    const salesConversionCanvas = document.getElementById(
        'managementSalesConversionChart'
    );

    if (salesConversionCanvas) {
        new Chart(salesConversionCanvas, {
            type: 'line',
            data: {
                labels: salesConversionLabels,
                datasets: [{
                    label: 'Jumlah',
                    data: salesConversionValues,
                    tension: 0.35,
                    borderWidth: 2.5,
                    borderColor: '#5B3E8E',
                    backgroundColor: 'rgba(91, 62, 142, 0.10)',
                    pointBackgroundColor: '#5B3E8E',
                    pointBorderColor: '#5B3E8E',
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true
                }]
            },
            options: {
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush
