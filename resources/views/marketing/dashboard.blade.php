@extends('layouts.app-dashboard')

@section('title', 'Marketing Dashboard')

@section('content')
@php
    $statusBadgeMap = [
        'draft' => 'bg-warning-subtle text-warning-emphasis',
        'published' => 'bg-success-subtle text-success-emphasis',
        'archived' => 'bg-secondary-subtle text-secondary-emphasis',
        'planned' => 'bg-warning-subtle text-warning-emphasis',
        'on_progress' => 'bg-primary-subtle text-primary-emphasis',
        'review' => 'bg-secondary-subtle text-secondary-emphasis',
        'done' => 'bg-success-subtle text-success-emphasis',
        'active' => 'bg-success-subtle text-success-emphasis',
        'paused' => 'bg-secondary-subtle text-secondary-emphasis',
        'scheduled' => 'bg-primary-subtle text-primary-emphasis',
        'open_registration' => 'bg-success-subtle text-success-emphasis',
        'confirmed' => 'bg-primary-subtle text-primary-emphasis',
        'cancelled' => 'bg-danger-subtle text-danger-emphasis',
    ];

    $monthlyStatusClass = $monthlyReport
        ? ($statusBadgeMap[$monthlyReport->status] ?? 'bg-light text-dark')
        : 'bg-light text-dark';
@endphp

<div class="container-fluid px-4 py-4 marketing-dashboard-page">

    <div class="page-header-card mb-4">
        <div class="page-header-content dashboard-header-content">
            <div class="dashboard-header-top">
                <div class="dashboard-header-intro">
                    <div class="page-eyebrow">Marketing Dashboard</div>
                    <h1 class="page-title mb-2">Marketing Performance Overview</h1>
                    <p class="page-subtitle mb-0">
                        Ringkasan performa marketing untuk melihat hasil utama, aktivitas yang tercatat, dan tren per periode.
                    </p>
                </div>

                <div class="dashboard-header-action">
                    <a href="{{ route('marketing.reports.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i> Add Report
                    </a>
                </div>
            </div>

            <div class="dashboard-header-filter-row">
                <div class="dashboard-filter-card dashboard-filter-card-soft">
                    <div class="dashboard-filter-label">Month Filter</div>
                    <div class="dashboard-filter-help">
                        Pilih bulan untuk menampilkan weekly report dan monthly summary pada periode yang sama.
                    </div>

                    <div class="month-button-group mt-3" role="group" aria-label="Month filter">
                        @foreach ($monthOptions as $option)
                            <a href="{{ route('marketing.dashboard', ['month' => $option['value'], 'week' => 'all']) }}"
                               class="btn {{ $selectedMonth === $option['value'] ? 'active' : '' }}">
                                {{ $option['short_label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-body">
            <div class="row g-3 align-items-center">
                <div class="col-lg-7">
                    <div class="dashboard-period-box">
                        <div class="dashboard-period-label">Reporting Scope</div>
                        <div class="dashboard-period-value">{{ $scopeTitle }}</div>
                        <div class="dashboard-period-help">{{ $scopeLabel }}</div>
                    </div>
                </div>

                <div class="col-lg-5">
                    @if ($monthlyReport)
                        <div class="dashboard-monthly-chip">
                            <div class="dashboard-monthly-chip-label">Monthly Report</div>
                            <div class="dashboard-monthly-chip-title">{{ $monthlyReport->title }}</div>
                            <div class="dashboard-monthly-chip-meta">
                                {{ $monthlyReport->report_no ?: '-' }}
                                · {{ optional($monthlyReport->start_date)->format('d M Y') ?? '-' }}
                                - {{ optional($monthlyReport->end_date)->format('d M Y') ?? '-' }}
                            </div>
                            <div class="mt-2">
                                <span class="badge rounded-pill {{ $monthlyStatusClass }}">
                                    {{ ucfirst($monthlyReport->status ?? 'draft') }}
                                </span>
                            </div>
                        </div>
                    @else
                        <div class="dashboard-monthly-chip empty">
                            <div class="dashboard-monthly-chip-label">Monthly Report</div>
                            <div class="dashboard-monthly-chip-title">Belum ada report bulanan</div>
                            <div class="dashboard-monthly-chip-meta">
                                Data bulanan untuk {{ $monthLabel }} belum tersedia.
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="week-filter-wrap mt-4">
                <div class="week-filter-header">
                    <div class="week-filter-title">Week Filter</div>
                    <div class="week-filter-subtitle">
                        Tampilkan seluruh minggu dalam bulan ini atau pilih satu minggu untuk melihat detailnya.
                    </div>
                </div>

                <div class="week-filter-grid mt-3">
                    <a href="{{ route('marketing.dashboard', ['month' => $selectedMonth, 'week' => 'all']) }}"
                       class="week-filter-card {{ $selectedWeek === 'all' ? 'active' : '' }}">
                        <div class="week-filter-icon">
                            <i class="bi bi-grid-1x2-fill"></i>
                        </div>
                        <div class="week-filter-body">
                            <div class="week-filter-name">All Weeks</div>
                            <div class="week-filter-meta">{{ $monthLabel }}</div>
                            <div class="week-filter-mini">
                                {{ $weeklyReports->count() }} weekly report
                            </div>
                        </div>
                    </a>

                    @forelse ($weekCards as $card)
                        <a href="{{ route('marketing.dashboard', ['month' => $selectedMonth, 'week' => $card['id']]) }}"
                           class="week-filter-card {{ (string) $selectedWeek === (string) $card['id'] ? 'active' : '' }}">
                            <div class="week-filter-icon">
                                <i class="bi bi-calendar-week-fill"></i>
                            </div>
                            <div class="week-filter-body">
                                <div class="week-filter-name">{{ $card['title'] }}</div>
                                <div class="week-filter-meta">{{ $card['date_label'] }}</div>
                                <div class="week-filter-mini">
                                    Leads {{ number_format($card['leads']) }}
                                    · Conversions {{ number_format($card['conversions']) }}
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="week-filter-empty">
                            <div class="empty-state-box small-empty-state">
                                <div class="empty-state-icon"><i class="bi bi-calendar-x"></i></div>
                                <div class="empty-state-title">No weekly report</div>
                                <div class="empty-state-subtitle">
                                    Belum ada weekly report pada bulan {{ $monthLabel }}.
                                </div>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-section-label mb-3">
        <div class="dashboard-section-eyebrow">Budget Position</div>
        <h4 class="dashboard-section-title mb-1">Ringkasan anggaran pada {{ $scopeTitle }}</h4>
        <p class="dashboard-section-subtitle mb-0">
            Menampilkan total budget, aktual penggunaan, dan sisa budget pada periode yang sedang dipilih.
        </p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-4 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div>
                        <div class="stat-title">Total Budget</div>
                        <div class="stat-value">Rp{{ number_format($summary['total_budget'], 0, ',', '.') }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Total alokasi budget yang tercatat pada periode ini.
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <div>
                        <div class="stat-title">Actual Spend</div>
                        <div class="stat-value">Rp{{ number_format($summary['actual_spend'], 0, ',', '.') }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Total pengeluaran yang sudah tercatat sampai update terakhir.
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-piggy-bank-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Remaining</div>
                        <div class="stat-value">Rp{{ number_format($summary['remaining_budget'], 0, ',', '.') }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Sisa budget yang masih tersedia. Utilization {{ number_format($summary['budget_utilization'], 1) }}%.
                </div>
            </div>
        </div>
    </div>

    @if ($showChart && $chartPayload)
        <div class="dashboard-section-label mb-3">
            <div class="dashboard-section-eyebrow">Performance Trend</div>
            <h4 class="dashboard-section-title mb-1">Perbandingan metrik utama</h4>
            <p class="dashboard-section-subtitle mb-0">
                Grafik menampilkan leads, conversions, revenue, dan spend sesuai scope yang sedang aktif.
            </p>
        </div>

        <div class="content-card mb-4">
            <div class="content-card-body">
                <div class="chart-wrap compact-chart-wrap">
                    <canvas id="weeklyMarketingChart" height="120"></canvas>
                </div>
            </div>
        </div>
    @endif

    <div class="dashboard-section-label mb-3">
        <div class="dashboard-section-eyebrow">Performance Snapshot</div>
        <h4 class="dashboard-section-title mb-1">Ringkasan hasil pada periode aktif</h4>
        <p class="dashboard-section-subtitle mb-0">
            Menampilkan angka utama untuk leads, conversions, revenue, dan jumlah event yang tercatat.
        </p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Leads</div>
                        <div class="stat-value">{{ number_format($summary['leads']) }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Total leads yang tercatat pada periode ini.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div>
                        <div class="stat-title">Conversions</div>
                        <div class="stat-value">{{ number_format($summary['conversions']) }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Total conversions yang berhasil dicatat.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-cash-coin"></i>
                    </div>
                    <div>
                        <div class="stat-title">Revenue</div>
                        <div class="stat-value">Rp{{ number_format($summary['revenue'], 0, ',', '.') }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Total revenue yang tercatat pada periode ini.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-calendar-event-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Events</div>
                        <div class="stat-value">{{ number_format($summary['events']) }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Jumlah event yang masuk ke dalam periode aktif.
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-section-label mb-3">
        <div class="dashboard-section-eyebrow">Insight Summary</div>
        <h4 class="dashboard-section-title mb-1">Insight & Notes</h4>
        <p class="dashboard-section-subtitle mb-0">
            Menampilkan ringkasan, insight, tindak lanjut, dan catatan dari report yang terbaca.
        </p>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-body">
            @if($insightCards->count())
                <div class="insight-card-grid">
                    @foreach($insightCards as $card)
                        <div class="insight-card">
                            <div class="insight-card-header">
                                <div>
                                    <div class="insight-card-title">{{ $card['title'] }}</div>
                                    <div class="insight-card-period">{{ $card['period'] }}</div>
                                </div>
                                <span class="insight-card-icon">
                                    <i class="bi bi-lightbulb-fill"></i>
                                </span>
                            </div>

                            <div class="insight-mini-grid">
                                <div class="insight-mini-box">
                                    <div class="insight-mini-label">Summary</div>
                                    <div class="insight-mini-value">{{ $card['summary'] ?: '-' }}</div>
                                </div>

                                <div class="insight-mini-box">
                                    <div class="insight-mini-label">Insight</div>
                                    <div class="insight-mini-value">{{ $card['key_insight'] ?: '-' }}</div>
                                </div>

                                <div class="insight-mini-box">
                                    <div class="insight-mini-label">Next Action</div>
                                    <div class="insight-mini-value">{{ $card['next_action'] ?: '-' }}</div>
                                </div>

                                <div class="insight-mini-box">
                                    <div class="insight-mini-label">Notes</div>
                                    <div class="insight-mini-value">{{ $card['notes'] ?: '-' }}</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty-state-box small-empty-state">
                    <div class="empty-state-icon"><i class="bi bi-lightbulb"></i></div>
                    <div class="empty-state-title">Belum ada insight</div>
                    <div class="empty-state-subtitle">
                        Belum ada insight dan notes pada periode yang sedang ditampilkan.
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-6">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Monthly Summary</h5>
                        <p class="content-card-subtitle mb-0">
                            Ringkasan bulanan untuk melihat posisi bulan ini secara keseluruhan.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    @if ($monthlyReport)
                        <div class="monthly-summary-card">
                            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                <div>
                                    <div class="monthly-summary-title">{{ $monthlyReport->title }}</div>
                                    <div class="monthly-summary-meta">
                                        {{ $monthlyReport->report_no ?: '-' }}
                                        · {{ optional($monthlyReport->start_date)->format('d M Y') ?? '-' }}
                                        - {{ optional($monthlyReport->end_date)->format('d M Y') ?? '-' }}
                                    </div>
                                </div>

                                <span class="badge rounded-pill {{ $monthlyStatusClass }}">
                                    {{ ucfirst($monthlyReport->status ?? 'draft') }}
                                </span>
                            </div>

                            <div class="monthly-summary-mini-grid">
                                <div class="monthly-summary-mini">
                                    <span class="mini-label">Leads</span>
                                    <span class="mini-value">{{ number_format((int) $monthlyReport->total_leads) }}</span>
                                </div>
                                <div class="monthly-summary-mini">
                                    <span class="mini-label">Conversions</span>
                                    <span class="mini-value">{{ number_format((int) $monthlyReport->total_conversions) }}</span>
                                </div>
                                <div class="monthly-summary-mini">
                                    <span class="mini-label">Revenue</span>
                                    <span class="mini-value">Rp{{ number_format((float) $monthlyReport->total_revenue, 0, ',', '.') }}</span>
                                </div>
                                <div class="monthly-summary-mini">
                                    <span class="mini-label">Budget</span>
                                    <span class="mini-value">Rp{{ number_format((float) $monthlyReport->total_budget, 0, ',', '.') }}</span>
                                </div>
                            </div>

                            <div class="monthly-summary-block">
                                <div class="monthly-summary-label">Key Insight</div>
                                <div class="monthly-summary-value">{{ $monthlyReport->key_insight ?: '-' }}</div>
                            </div>
                        </div>
                    @else
                        <div class="empty-state-box small-empty-state">
                            <div class="empty-state-icon"><i class="bi bi-journal-x"></i></div>
                            <div class="empty-state-title">No monthly report</div>
                            <div class="empty-state-subtitle">
                                Belum ada monthly report untuk {{ $monthLabel }}.
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Execution Overview</h5>
                        <p class="content-card-subtitle mb-0">
                            Ringkasan jumlah campaign, ads, events, dan utilization pada periode aktif.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="snapshot-compact-grid">
                        <div class="snapshot-compact-item">
                            <div class="snapshot-compact-label">Campaigns</div>
                            <div class="snapshot-compact-value">{{ number_format($summary['campaigns']) }}</div>
                        </div>

                        <div class="snapshot-compact-item">
                            <div class="snapshot-compact-label">Ads</div>
                            <div class="snapshot-compact-value">{{ number_format($summary['ads']) }}</div>
                        </div>

                        <div class="snapshot-compact-item">
                            <div class="snapshot-compact-label">Events</div>
                            <div class="snapshot-compact-value">{{ number_format($summary['events']) }}</div>
                        </div>

                        <div class="snapshot-compact-item">
                            <div class="snapshot-compact-label">Spend Utilization</div>
                            <div class="snapshot-compact-value">{{ number_format($summary['budget_utilization'], 1) }}%</div>
                        </div>
                    </div>

                    <div class="insight-box mt-3">
                        <p class="mb-0">
                            Gunakan bagian ini untuk melihat beban aktivitas yang tercatat pada periode yang sedang dipilih.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-section-label mb-3">
        <div class="dashboard-section-eyebrow">Execution Visibility</div>
        <h4 class="dashboard-section-title mb-1">Campaign, ads, dan events pada {{ $scopeTitle }}</h4>
        <p class="dashboard-section-subtitle mb-0">
            Menampilkan daftar aktivitas yang masuk ke dalam scope dashboard saat ini.
        </p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-4">
            <div class="content-card h-100">
                <div class="content-card-header d-flex justify-content-between align-items-center gap-2">
                    <div>
                        <h5 class="content-card-title mb-1">Campaigns</h5>
                        <p class="content-card-subtitle mb-0">
                            Daftar campaign pada periode aktif.
                        </p>
                    </div>
                    <span class="badge rounded-pill bg-light text-dark border">
                        {{ $campaignRows->count() }}
                    </span>
                </div>

                <div class="content-card-body">
                    @if($campaignRows->count())
                        <div class="simple-list">
                            @foreach ($campaignRows as $item)
                                @php
                                    $campaignStatusClass = $statusBadgeMap[$item->status] ?? 'bg-light text-dark';
                                @endphp
                                <div class="simple-list-item">
                                    <div class="simple-list-title">{{ $item->name ?: '-' }}</div>
                                    <div class="simple-list-meta">
                                        {{ $item->objective ?: '-' }}
                                    </div>
                                    <div class="simple-list-meta">
                                        @if($item->owner_name)
                                            {{ $item->owner_name }} ·
                                        @endif
                                        <span class="badge rounded-pill {{ $campaignStatusClass }}">
                                            {{ ucfirst(str_replace('_', ' ', $item->status ?? '-')) }}
                                        </span>
                                    </div>
                                    <div class="simple-list-meta">
                                        Budget Rp{{ number_format($item->budget, 0, ',', '.') }}
                                        · Used Rp{{ number_format($item->actual_spend, 0, ',', '.') }}
                                    </div>
                                    <div class="simple-list-tag">{{ $item->report_period_label }}</div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="empty-state-box small-empty-state">
                            <div class="empty-state-icon"><i class="bi bi-megaphone"></i></div>
                            <div class="empty-state-title">No campaign</div>
                            <div class="empty-state-subtitle">
                                Tidak ada campaign pada periode ini.
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="content-card h-100">
                <div class="content-card-header d-flex justify-content-between align-items-center gap-2">
                    <div>
                        <h5 class="content-card-title mb-1">Ads</h5>
                        <p class="content-card-subtitle mb-0">
                            Daftar ads pada periode aktif.
                        </p>
                    </div>
                    <span class="badge rounded-pill bg-light text-dark border">
                        {{ $adRows->count() }}
                    </span>
                </div>

                <div class="content-card-body">
                    @if($adRows->count())
                        <div class="simple-list">
                            @foreach ($adRows as $item)
                                @php
                                    $adStatusClass = $statusBadgeMap[$item->status] ?? 'bg-light text-dark';
                                @endphp
                                <div class="simple-list-item">
                                    <div class="simple-list-title">{{ $item->ad_name ?: '-' }}</div>
                                    <div class="simple-list-meta">
                                        {{ $item->platform ?: '-' }}
                                        @if($item->objective)
                                            · {{ $item->objective }}
                                        @endif
                                    </div>
                                    <div class="simple-list-meta">
                                        <span class="badge rounded-pill {{ $adStatusClass }}">
                                            {{ ucfirst(str_replace('_', ' ', $item->status ?? '-')) }}
                                        </span>
                                    </div>
                                    <div class="simple-list-meta">
                                        Budget Rp{{ number_format($item->budget, 0, ',', '.') }}
                                        · Used Rp{{ number_format($item->actual_spend, 0, ',', '.') }}
                                    </div>
                                    <div class="simple-list-tag">{{ $item->report_period_label }}</div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="empty-state-box small-empty-state">
                            <div class="empty-state-icon"><i class="bi bi-badge-ad"></i></div>
                            <div class="empty-state-title">No ads</div>
                            <div class="empty-state-subtitle">
                                Tidak ada ads pada periode ini.
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="content-card h-100">
                <div class="content-card-header d-flex justify-content-between align-items-center gap-2">
                    <div>
                        <h5 class="content-card-title mb-1">Events</h5>
                        <p class="content-card-subtitle mb-0">
                            Daftar event pada periode aktif.
                        </p>
                    </div>
                    <span class="badge rounded-pill bg-light text-dark border">
                        {{ $eventRows->count() }}
                    </span>
                </div>

                <div class="content-card-body">
                    @if($eventRows->count())
                        <div class="simple-list">
                            @foreach ($eventRows as $item)
                                @php
                                    $eventStatusClass = $statusBadgeMap[$item->status] ?? 'bg-light text-dark';
                                @endphp
                                <div class="simple-list-item">
                                    <div class="simple-list-title">{{ $item->name ?: '-' }}</div>
                                    <div class="simple-list-meta">
                                        {{ ucfirst(str_replace('_', ' ', $item->event_type ?? '-')) }}
                                        @if($item->location)
                                            · {{ $item->location }}
                                        @endif
                                    </div>
                                    <div class="simple-list-meta">
                                        {{ $item->event_date ? \Illuminate\Support\Carbon::parse($item->event_date)->format('d M Y') : '-' }}
                                        · <span class="badge rounded-pill {{ $eventStatusClass }}">
                                            {{ ucfirst(str_replace('_', ' ', $item->status ?? '-')) }}
                                        </span>
                                    </div>
                                    <div class="simple-list-meta">
                                        Target {{ number_format($item->target_participants) }}
                                        · Budget Rp{{ number_format($item->budget, 0, ',', '.') }}
                                    </div>
                                    <div class="simple-list-tag">{{ $item->report_period_label }}</div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="empty-state-box small-empty-state">
                            <div class="empty-state-icon"><i class="bi bi-calendar-event"></i></div>
                            <div class="empty-state-title">No event</div>
                            <div class="empty-state-subtitle">
                                Tidak ada event pada periode ini.
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const chartPayload = @json($chartPayload);

    if (!chartPayload || !document.getElementById('weeklyMarketingChart') || typeof Chart === 'undefined') {
        return;
    }

    const ctx = document.getElementById('weeklyMarketingChart').getContext('2d');

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartPayload.labels,
            datasets: [
                {
                    label: 'Leads',
                    data: chartPayload.leads,
                    yAxisID: 'y',
                    borderRadius: 10,
                    backgroundColor: 'rgba(91, 62, 142, 0.78)',
                    categoryPercentage: 0.56,
                    barPercentage: 0.82,
                    maxBarThickness: 42,
                },
                {
                    label: 'Conversions',
                    data: chartPayload.conversions,
                    yAxisID: 'y',
                    borderRadius: 10,
                    backgroundColor: 'rgba(255, 190, 4, 0.82)',
                    categoryPercentage: 0.56,
                    barPercentage: 0.82,
                    maxBarThickness: 42,
                },
                {
                    label: 'Revenue (Jt)',
                    data: chartPayload.revenue_million,
                    yAxisID: 'y1',
                    borderRadius: 10,
                    backgroundColor: 'rgba(59, 142, 77, 0.78)',
                    categoryPercentage: 0.56,
                    barPercentage: 0.82,
                    maxBarThickness: 42,
                },
                {
                    label: 'Spend (Jt)',
                    data: chartPayload.actual_spend_million,
                    yAxisID: 'y1',
                    borderRadius: 10,
                    backgroundColor: 'rgba(239, 68, 68, 0.78)',
                    categoryPercentage: 0.56,
                    barPercentage: 0.82,
                    maxBarThickness: 42,
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
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        boxWidth: 10,
                        padding: 16
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.dataset.label || '';
                            const value = context.raw ?? 0;

                            if (label.includes('(Jt)')) {
                                return `${label}: ${value} jt`;
                            }

                            return `${label}: ${value}`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    stacked: false,
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: '#5b6472',
                        font: {
                            size: 12,
                            weight: '600'
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Leads / Conversions'
                    },
                    grid: {
                        color: 'rgba(148, 163, 184, 0.12)'
                    }
                },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Revenue / Spend (Jt)'
                    },
                    grid: {
                        drawOnChartArea: false
                    }
                }
            }
        }
    });
});
</script>
@endpush
