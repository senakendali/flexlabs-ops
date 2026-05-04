@extends('layouts.app-dashboard')

@section('title', 'Sales Performance Dashboard')

@section('content')
@php
    $totals = $totals ?? [
        'total_leads' => 0,
        'interacted' => 0,
        'ignored' => 0,
        'closed_lost' => 0,
        'not_related' => 0,
        'warm_leads' => 0,
        'hot_leads' => 0,
        'consultation' => 0,
        'closed_deal' => 0,
        'revenue' => 0,
    ];

    $kpis = $kpis ?? [
        'interaction_rate' => 0,
        'consultation_rate' => 0,
        'hot_lead_rate' => 0,
        'deal_rate' => 0,
        'consultation_to_deal_rate' => 0,
        'average_deal_value' => 0,
    ];

    $filters = $filters ?? [
        'date_from' => now()->subDays(29)->toDateString(),
        'date_to' => now()->toDateString(),
    ];

    $reports = $reports ?? collect();
@endphp

<div class="container-fluid px-4 py-4">
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Sales Performance</div>
                <h1 class="page-title mb-2">Sales Performance Dashboard</h1>
                <p class="page-subtitle mb-0">
                    Pantau performa sales untuk melihat perkembangan leads, kualitas follow up, conversion, closed deal, dan revenue.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                @if(Route::has('sales-daily-reports.index'))
                    <a href="{{ route('sales-daily-reports.index') }}" class="btn btn-light btn-modern">
                        <i class="bi bi-file-earmark-text me-2"></i>Daily Reports
                    </a>
                @endif
            </div>
        </div>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Filter Performance</h5>
                <p class="content-card-subtitle mb-0">
                    Pilih periode laporan untuk membaca performa sales berdasarkan tanggal report.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            <form method="GET" action="{{ route('sales-performance.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-xl-4 col-md-6">
                        <label for="date_from" class="form-label">Date From</label>
                        <input
                            type="date"
                            id="date_from"
                            name="date_from"
                            class="form-control"
                            value="{{ $filters['date_from'] }}"
                        >
                    </div>

                    <div class="col-xl-4 col-md-6">
                        <label for="date_to" class="form-label">Date To</label>
                        <input
                            type="date"
                            id="date_to"
                            name="date_to"
                            class="form-control"
                            value="{{ $filters['date_to'] }}"
                        >
                    </div>

                    <div class="col-xl-4 col-md-12">
                        <div class="d-flex gap-2 justify-content-xl-end flex-wrap">
                            <a href="{{ route('sales-performance.index') }}" class="btn btn-outline-secondary btn-modern">
                                <i class="bi bi-arrow-counterclockwise me-2"></i>Reset
                            </a>

                            <button type="submit" class="btn btn-primary btn-modern">
                                <i class="bi bi-funnel me-2"></i>Apply Filter
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Main Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-people"></i>
                    </div>

                    <div>
                        <div class="stat-title">Total Leads</div>
                        <div class="stat-value">{{ number_format((int) $totals['total_leads']) }}</div>
                    </div>
                </div>

                <div class="stat-description">
                    Akumulasi leads pada periode terpilih.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-chat-left-text"></i>
                    </div>

                    <div>
                        <div class="stat-title">Interacted</div>
                        <div class="stat-value">{{ number_format((int) $totals['interacted']) }}</div>
                    </div>
                </div>

                <div class="stat-description">
                    Interaction rate {{ number_format((float) $kpis['interaction_rate'], 1) }}% dari total leads.
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
                        <div class="stat-title">Closed Deal</div>
                        <div class="stat-value">{{ number_format((int) $totals['closed_deal']) }}</div>
                    </div>
                </div>

                <div class="stat-description">
                    Deal rate {{ number_format((float) $kpis['deal_rate'], 1) }}% dari total leads.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-cash-stack"></i>
                    </div>

                    <div>
                        <div class="stat-title">Total Revenue</div>
                        <div class="stat-value">Rp {{ number_format((float) $totals['revenue'], 0, ',', '.') }}</div>
                    </div>
                </div>

                <div class="stat-description">
                    Akumulasi revenue dari seluruh deal pada periode ini.
                </div>
            </div>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-telephone"></i>
                    </div>

                    <div>
                        <div class="stat-title">Consultation Rate</div>
                        <div class="stat-value">{{ number_format((float) $kpis['consultation_rate'], 1) }}%</div>
                    </div>
                </div>

                <div class="stat-description">
                    Persentase leads yang lanjut ke tahap konsultasi.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-fire"></i>
                    </div>

                    <div>
                        <div class="stat-title">Hot Lead Rate</div>
                        <div class="stat-value">{{ number_format((float) $kpis['hot_lead_rate'], 1) }}%</div>
                    </div>
                </div>

                <div class="stat-description">
                    Persentase leads yang masuk kategori hot leads.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-arrow-left-right"></i>
                    </div>

                    <div>
                        <div class="stat-title">Consultation to Deal</div>
                        <div class="stat-value">{{ number_format((float) $kpis['consultation_to_deal_rate'], 1) }}%</div>
                    </div>
                </div>

                <div class="stat-description">
                    Rasio konsultasi yang berhasil dikonversi menjadi deal.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-wallet2"></i>
                    </div>

                    <div>
                        <div class="stat-title">Average Deal Value</div>
                        <div class="stat-value">Rp {{ number_format((float) $kpis['average_deal_value'], 0, ',', '.') }}</div>
                    </div>
                </div>

                <div class="stat-description">
                    Rata-rata nilai revenue per deal pada periode ini.
                </div>
            </div>
        </div>
    </div>

    {{-- Charts --}}
    <div class="row g-4 mb-4">
        <div class="col-xl-8">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Sales Trend Overview</h5>
                        <p class="content-card-subtitle mb-0">
                            Perkembangan total leads, interaction, consultation, hot leads, dan closed deal dari waktu ke waktu.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div style="position: relative; min-height: 360px;">
                        <canvas id="salesTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Funnel & Outcome Snapshot</h5>
                        <p class="content-card-subtitle mb-0">
                            Ringkasan perpindahan dari leads sampai closed deal.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div style="position: relative; min-height: 360px;">
                        <canvas id="salesFunnelChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Revenue + Quick Summary --}}
    <div class="row g-4 mb-4">
        <div class="col-xl-8">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Revenue Trend</h5>
                        <p class="content-card-subtitle mb-0">
                            Pantau perubahan revenue harian untuk melihat momentum closing pada periode terpilih.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div style="position: relative; min-height: 320px;">
                        <canvas id="revenueTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Quick Summary</h5>
                        <p class="content-card-subtitle mb-0">
                            Gambaran ringkas untuk membaca kondisi funnel dan hasil bisnis pada periode ini.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="d-flex flex-column gap-3">
                        <div class="border rounded-3 p-3">
                            <div class="small text-muted mb-1">Hot Leads</div>
                            <div class="fw-bold fs-5">{{ number_format((int) $totals['hot_leads']) }}</div>
                            <div class="small text-muted">
                                Leads dengan potensi closing lebih tinggi.
                            </div>
                        </div>

                        <div class="border rounded-3 p-3">
                            <div class="small text-muted mb-1">Consultation</div>
                            <div class="fw-bold fs-5">{{ number_format((int) $totals['consultation']) }}</div>
                            <div class="small text-muted">
                                Leads yang masuk tahap konsultasi.
                            </div>
                        </div>

                        <div class="border rounded-3 p-3">
                            <div class="small text-muted mb-1">Ignored</div>
                            <div class="fw-bold fs-5">{{ number_format((int) $totals['ignored']) }}</div>
                            <div class="small text-muted">
                                Leads yang belum berhasil masuk ke interaksi.
                            </div>
                        </div>

                        <div class="border rounded-3 p-3">
                            <div class="small text-muted mb-1">Closed Lost</div>
                            <div class="fw-bold fs-5">{{ number_format((int) $totals['closed_lost']) }}</div>
                            <div class="small text-muted">
                                Leads yang tidak berhasil lanjut ke deal.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Summary Table --}}
    <div class="content-card">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Daily Reporting Summary</h5>
                <p class="content-card-subtitle mb-0">
                    Ringkasan angka harian untuk membaca pola performa reporting sales dan outcome bisnis.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            @if(($reports ?? collect())->count())
                <div class="table-responsive dropdown-safe-table">
                    <table class="table table-hover align-middle admin-table mb-0">
                        <thead>
                            <tr>
                                <th class="text-nowrap">Date</th>
                                <th class="text-end text-nowrap">Total Leads</th>
                                <th class="text-end text-nowrap">Interacted</th>
                                <th class="text-end text-nowrap">Ignored</th>
                                <th class="text-end text-nowrap">Consultation</th>
                                <th class="text-end text-nowrap">Hot Leads</th>
                                <th class="text-end text-nowrap">Closed Deal</th>
                                <th class="text-end text-nowrap">Revenue</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($reports as $report)
                                <tr>
                                    <td class="text-nowrap">
                                        <div class="fw-bold text-dark">
                                            {{ optional($report->report_date)->format('d M Y') ?? '-' }}
                                        </div>
                                    </td>

                                    <td class="text-end text-nowrap">
                                        {{ number_format((int) ($report->total_leads ?? 0)) }}
                                    </td>

                                    <td class="text-end text-nowrap">
                                        {{ number_format((int) ($report->interacted ?? 0)) }}
                                    </td>

                                    <td class="text-end text-nowrap">
                                        {{ number_format((int) ($report->ignored ?? 0)) }}
                                    </td>

                                    <td class="text-end text-nowrap">
                                        {{ number_format((int) ($report->consultation ?? 0)) }}
                                    </td>

                                    <td class="text-end text-nowrap">
                                        {{ number_format((int) ($report->hot_leads ?? 0)) }}
                                    </td>

                                    <td class="text-end text-nowrap">
                                        <div class="fw-bold text-dark">
                                            {{ number_format((int) ($report->closed_deal ?? 0)) }}
                                        </div>
                                    </td>

                                    <td class="text-end text-nowrap">
                                        <div class="fw-bold text-success">
                                            Rp {{ number_format((float) ($report->revenue ?? 0), 0, ',', '.') }}
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-bar-chart-line"></i>
                    </div>

                    <h5 class="empty-state-title">Belum ada data performance</h5>
                    <p class="empty-state-text mb-0">
                        Belum ada sales daily report pada periode yang dipilih.
                    </p>

                    @if(Route::has('sales-daily-reports.create'))
                        <div class="mt-3">
                            <a href="{{ route('sales-daily-reports.create') }}" class="btn btn-primary btn-modern">
                                <i class="bi bi-plus-lg me-2"></i>Add Report
                            </a>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', async function () {
    const url = new URL(@json(route('sales-performance.chart-data')), window.location.origin);
    url.searchParams.set('date_from', @json($filters['date_from']));
    url.searchParams.set('date_to', @json($filters['date_to']));

    const trendCtx = document.getElementById('salesTrendChart');
    const funnelCtx = document.getElementById('salesFunnelChart');
    const revenueTrendCtx = document.getElementById('revenueTrendChart');

    try {
        const response = await fetch(url.toString(), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        });

        if (!response.ok) {
            throw new Error('Failed to load sales performance chart data.');
        }

        const result = await response.json();

        if (trendCtx) {
            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: result.labels || [],
                    datasets: [
                        {
                            label: 'Total Leads',
                            data: result.datasets?.total_leads || [],
                            tension: 0.35,
                            borderWidth: 2
                        },
                        {
                            label: 'Interacted',
                            data: result.datasets?.interacted || [],
                            tension: 0.35,
                            borderWidth: 2
                        },
                        {
                            label: 'Consultation',
                            data: result.datasets?.consultation || [],
                            tension: 0.35,
                            borderWidth: 2
                        },
                        {
                            label: 'Hot Leads',
                            data: result.datasets?.hot_leads || [],
                            tension: 0.35,
                            borderWidth: 2
                        },
                        {
                            label: 'Closed Deal',
                            data: result.datasets?.closed_deal || [],
                            tension: 0.35,
                            borderWidth: 2
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
                                boxWidth: 10
                            }
                        }
                    },
                    scales: {
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

        if (funnelCtx) {
            new Chart(funnelCtx, {
                type: 'bar',
                data: {
                    labels: ['Leads', 'Interacted', 'Consultation', 'Closed Deal'],
                    datasets: [{
                        label: 'Total',
                        data: [
                            Number(result.summary?.total_leads || 0),
                            Number(result.summary?.interacted || 0),
                            Number(result.summary?.consultation || 0),
                            Number(result.summary?.closed_deal || 0)
                        ],
                        borderRadius: 8,
                        maxBarThickness: 42
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
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

        if (revenueTrendCtx) {
            new Chart(revenueTrendCtx, {
                type: 'bar',
                data: {
                    labels: result.labels || [],
                    datasets: [{
                        label: 'Revenue',
                        data: result.datasets?.revenue || [],
                        borderRadius: 8,
                        maxBarThickness: 42
                    }]
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
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const value = Number(context.raw || 0);
                                    return 'Rp ' + value.toLocaleString('id-ID');
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + Number(value).toLocaleString('id-ID');
                                }
                            }
                        }
                    }
                }
            });
        }
    } catch (error) {
        console.error(error);
    }
});
</script>
@endpush