@extends('layouts.app-dashboard')

@section('title', 'Sales Performance Dashboard')

@section('content')
<div class="dashboard-content">
    <div class="container-fluid py-4">
        <div class="mb-4">
            <h3 class="fw-bold mb-1">Sales Performance Dashboard</h3>
            <p class="text-muted mb-0">
                Pantau performa reporting sales untuk melihat tren leads, kualitas interaksi, dan peluang konsultasi secara lebih jelas.
            </p>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('sales-performance.index') }}" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="date_from" class="form-label mb-1">Date From</label>
                        <input
                            type="date"
                            id="date_from"
                            name="date_from"
                            class="form-control"
                            value="{{ $filters['date_from'] }}"
                        >
                    </div>

                    <div class="col-md-4">
                        <label for="date_to" class="form-label mb-1">Date To</label>
                        <input
                            type="date"
                            id="date_to"
                            name="date_to"
                            class="form-control"
                            value="{{ $filters['date_to'] }}"
                        >
                    </div>

                    <div class="col-md-4">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-funnel me-1"></i> Apply Filter
                            </button>
                            <a href="{{ route('sales-performance.index') }}" class="btn btn-outline-secondary">
                                Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card dashboard-stat-card h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-start gap-3">
                            <div class="stat-icon">
                                <i class="bi bi-people"></i>
                            </div>
                            <div>
                                <div class="text-muted small">Total Leads</div>
                                <h4 class="fw-bold mb-0">{{ $totals['total_leads'] }}</h4>
                            </div>
                        </div>

                        <div class="mt-auto pt-2">
                            <small class="text-secondary-emphasis">
                                <i class="bi bi-bar-chart-line"></i> Akumulasi leads pada periode terpilih
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card dashboard-stat-card h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-start gap-3">
                            <div class="stat-icon">
                                <i class="bi bi-chat-left-text"></i>
                            </div>
                            <div>
                                <div class="text-muted small">Interaction Rate</div>
                                <h4 class="fw-bold mb-0">{{ $kpis['interaction_rate'] }}%</h4>
                            </div>
                        </div>

                        <div class="mt-auto pt-2">
                            <small class="text-info-emphasis">
                                <i class="bi bi-arrow-up-right-circle"></i> Persentase leads yang berhasil direspons
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card dashboard-stat-card h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-start gap-3">
                            <div class="stat-icon">
                                <i class="bi bi-telephone"></i>
                            </div>
                            <div>
                                <div class="text-muted small">Consultation Rate</div>
                                <h4 class="fw-bold mb-0">{{ $kpis['consultation_rate'] }}%</h4>
                            </div>
                        </div>

                        <div class="mt-auto pt-2">
                            <small class="text-primary-emphasis">
                                <i class="bi bi-headset"></i> Persentase leads yang lanjut ke tahap konsultasi
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card dashboard-stat-card h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-start gap-3">
                            <div class="stat-icon">
                                <i class="bi bi-fire"></i>
                            </div>
                            <div>
                                <div class="text-muted small">Hot Lead Rate</div>
                                <h4 class="fw-bold mb-0">{{ $kpis['hot_lead_rate'] }}%</h4>
                            </div>
                        </div>

                        <div class="mt-auto pt-2">
                            <small class="text-warning-emphasis">
                                <i class="bi bi-stars"></i> Proporsi leads berpotensi tinggi dalam periode ini
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Charts --}}
        <div class="row g-3 mb-4">
            <div class="col-lg-8">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="fw-bold mb-1">Lead Trend Overview</h5>
                            <p class="text-muted small mb-0">
                                Lihat perkembangan total leads, interaction, consultation, dan hot leads dari waktu ke waktu.
                            </p>
                        </div>
                    </div>

                    <div class="card-body">
                        <div style="position: relative; min-height: 360px;">
                            <canvas id="salesTrendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="fw-bold mb-1">Lead Funnel Snapshot</h5>
                            <p class="text-muted small mb-0">
                                Ringkasan funnel sederhana untuk membaca perpindahan dari leads ke konsultasi.
                            </p>
                        </div>
                    </div>

                    <div class="card-body">
                        <div style="position: relative; min-height: 360px;">
                            <canvas id="salesFunnelChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Summary Table --}}
        <div class="row g-3">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header">
                        <h5 class="fw-bold mb-1">Daily Reporting Summary</h5>
                        <p class="text-muted small mb-0">
                            Ringkasan angka harian untuk membantu membaca pola performa reporting sales.
                        </p>
                    </div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Total Leads</th>
                                        <th>Interacted</th>
                                        <th>Ignored</th>
                                        <th>Consultation</th>
                                        <th>Hot Leads</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($reports as $report)
                                        <tr>
                                            <td>{{ optional($report->report_date)->format('d M Y') }}</td>
                                            <td>{{ $report->total_leads }}</td>
                                            <td>{{ $report->interacted }}</td>
                                            <td>{{ $report->ignored }}</td>
                                            <td>{{ $report->consultation }}</td>
                                            <td>{{ $report->hot_leads }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                Belum ada data report pada periode ini.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
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

    const response = await fetch(url.toString(), {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        }
    });

    const result = await response.json();

    const trendCtx = document.getElementById('salesTrendChart');
    const funnelCtx = document.getElementById('salesFunnelChart');

    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: result.labels,
            datasets: [
                {
                    label: 'Total Leads',
                    data: result.datasets.total_leads,
                    tension: 0.35
                },
                {
                    label: 'Interacted',
                    data: result.datasets.interacted,
                    tension: 0.35
                },
                {
                    label: 'Consultation',
                    data: result.datasets.consultation,
                    tension: 0.35
                },
                {
                    label: 'Hot Leads',
                    data: result.datasets.hot_leads,
                    tension: 0.35
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    new Chart(funnelCtx, {
        type: 'bar',
        data: {
            labels: ['Leads', 'Interacted', 'Consultation', 'Hot Leads'],
            datasets: [{
                label: 'Total',
                data: [
                    result.summary.total_leads,
                    result.summary.interacted,
                    result.summary.consultation,
                    result.summary.hot_leads
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
});
</script>
@endpush