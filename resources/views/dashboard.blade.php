@extends('layouts.app-dashboard')

@section('title', 'Management Dashboard')

@section('content')
<div class="container-fluid px-4 py-4">

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Overview Dashboard</div>
                <h1 class="page-title mb-2">Business & Academic Overview</h1>
                <p class="page-subtitle mb-0">
                    Pantau performa bisnis dan operasional dari sisi sales funnel, academic, kapasitas batch,
                    trial performance, serta pendapatan dalam satu dashboard.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <a href="{{ route('curriculum.index') }}" class="btn btn-light btn-modern">
                    <i class="bi bi-gear-fill"></i> Manage Curriculum
                </a>
            </div>
        </div>
    </div>


    <div class="dashboard-section-label mb-3">
        <div class="dashboard-section-eyebrow">Sales Overview</div>
        <h4 class="dashboard-section-title mb-1">Demand & Conversion Funnel</h4>
        <p class="dashboard-section-subtitle mb-0">Monitoring demand dan performa conversion funnel. Mulai dari leads, trial, dan join program.</p>
    </div>

    {{-- Sales Funnel --}}
    
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="funnel-card">
                <div class="funnel-card-top">
                    <div class="funnel-icon-wrap">
                        <i class="bi bi-person-lines-fill"></i>
                    </div>
                    <div>
                        <div class="funnel-title">Leads</div>
                        <div class="funnel-value">{{ number_format($salesInsight['leads'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="funnel-description">Total calon student yang masuk ke funnel.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="funnel-card">
                <div class="funnel-card-top">
                    <div class="funnel-icon-wrap">
                        <i class="bi bi-calendar2-check"></i>
                    </div>
                    <div>
                        <div class="funnel-title">Trial</div>
                        <div class="funnel-value">{{ number_format($salesInsight['trial'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="funnel-description">
                    Conversion dari leads ke trial:
                    <strong>{{ number_format($salesInsight['conversion_trial'] ?? 0) }}%</strong>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="funnel-card">
                <div class="funnel-card-top">
                    <div class="funnel-icon-wrap">
                        <i class="bi bi-mortarboard"></i>
                    </div>
                    <div>
                        <div class="funnel-title">Join Program</div>
                        <div class="funnel-value">{{ number_format($salesInsight['join'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="funnel-description">
                    Conversion dari trial ke join:
                    <strong>{{ number_format($salesInsight['conversion_join'] ?? 0) }}%</strong>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="funnel-card">
                <div class="funnel-card-top">
                    <div class="funnel-icon-wrap">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div>
                        <div class="funnel-title">Paid</div>
                        <div class="funnel-value">{{ number_format($salesInsight['paid'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="funnel-description">
                    Conversion dari join ke paid:
                    <strong>{{ number_format($salesInsight['conversion_paid'] ?? 0) }}%</strong>
                </div>
            </div>
        </div>
    </div>

    {{-- Sales Performance Chart --}}
    <div class="content-card mb-4">
        <div class="content-card-header d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h5 class="content-card-title mb-1">Sales Performance Overview</h5>
                <p class="content-card-subtitle mb-0">
                    Perkembangan leads, interaction, consultation, hot leads, dan closed deal untuk membaca performa sales secara cepat.
                </p>
            </div>

            <div class="revenue-total-box sales-chart-summary-box">
                <div class="revenue-total-label">Closed Deal</div>
                <div class="revenue-total-value" id="salesPerformanceClosedDealValue">{{ number_format($salesInsight['paid'] ?? 0) }}</div>
            </div>
        </div>

        <div class="content-card-body">
            <div class="chart-wrap" style="height: 360px;">
                <canvas id="salesPerformanceChart"></canvas>
            </div>
        </div>
    </div>

    <div class="dashboard-section-label mb-3 mt-1">
        <div class="dashboard-section-eyebrow">Academic Overview</div>
        <h4 class="dashboard-section-title mb-1">Capacity, Delivery & Readiness</h4>
        <p class="dashboard-section-subtitle mb-0">Evaluasi kapasitas dan kesiapan delivery program yang terdiri dari kapasitas, delivery, dan readiness.</p>
    </div>

    {{-- Academic Main Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-journal-richtext"></i>
                    </div>
                    <div>
                        <div class="stat-title">Programs</div>
                        <div class="stat-value">{{ number_format($academicStats['programs'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">Total program akademik yang terdaftar.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-collection-play"></i>
                    </div>
                    <div>
                        <div class="stat-title">Active Batches</div>
                        <div class="stat-value">{{ number_format($academicStats['active_batches'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">Batch aktif yang sedang berjalan atau dibuka.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-people"></i>
                    </div>
                    <div>
                        <div class="stat-title">Filled Seats</div>
                        <div class="stat-value">{{ number_format($academicStats['filled_seats'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">Total seat yang sudah terisi di seluruh batch.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-calendar-event"></i>
                    </div>
                    <div>
                        <div class="stat-title">Upcoming Batches</div>
                        <div class="stat-value">{{ number_format($academicStats['upcoming_batches'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">Batch yang akan dimulai dalam waktu dekat.</div>
            </div>
        </div>
    </div>

    {{-- Batch Capacity Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-4 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-grid-1x2-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Total Kapasitas Batch</div>
                        <div class="stat-value">{{ number_format($batchCapacity['total_capacity'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">Akumulasi seluruh seat dari batch aktif.</div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-person-check-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Sudah Terisi</div>
                        <div class="stat-value">{{ number_format($batchCapacity['filled_seats'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Utilisasi {{ number_format($batchCapacity['utilization_percent'] ?? 0) }}% dari kapasitas aktif.
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-person-plus-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Sisa Seat</div>
                        <div class="stat-value">{{ number_format($batchCapacity['remaining_seats'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">Seat yang masih tersedia untuk diisi.</div>
            </div>
        </div>
    </div>

    {{-- Trial Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-brush"></i>
                    </div>
                    <div>
                        <div class="stat-title">Trial Themes</div>
                        <div class="stat-value">{{ number_format($trialStats['themes_total'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">Theme trial yang terdaftar di sistem.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div>
                        <div class="stat-title">Trial Schedules</div>
                        <div class="stat-value">{{ number_format($trialStats['schedules_active'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">Jadwal trial aktif yang siap dijalankan.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Trial Participants</div>
                        <div class="stat-value">{{ number_format($trialStats['participants_total'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">Total peserta trial yang sudah masuk.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-person-plus"></i>
                    </div>
                    <div>
                        <div class="stat-title">New Trial This Month</div>
                        <div class="stat-value">{{ number_format($trialStats['participants_new_this_month'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">Peserta trial baru di bulan ini.</div>
            </div>
        </div>
    </div>

    <div class="dashboard-section-label mb-3 mt-1">
        <div class="dashboard-section-eyebrow">Finance Overview</div>
        <h4 class="dashboard-section-title mb-1">Revenue & Business Result</h4>
        <p class="dashboard-section-subtitle mb-0">Analisis hasil finansial dari aktivitas operasional.</p>
    </div>

    {{-- Monthly Revenue Chart --}}
    <div class="content-card mb-4">
        <div class="content-card-header d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h5 class="content-card-title mb-1">Revenue Overview</h5>
                <p class="content-card-subtitle mb-0">
                    Total pendapatan pembayaran student selama tahun {{ $revenueChart['year'] ?? now()->year }}.
                </p>
            </div>

            <div class="revenue-total-box">
                <div class="revenue-total-label">Total Tahun Ini</div>
                <div class="revenue-total-value">Rp {{ number_format($revenueChart['total'] ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="content-card-body">
            <div class="chart-wrap">
                <canvas id="monthlyRevenueChart" height="110"></canvas>
            </div>
        </div>
    </div>

    {{-- Trial Progress + Upcoming Trial Schedule --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-5">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Trial Follow Up Progress</h5>
                        <p class="content-card-subtitle mb-0">
                            Persentase peserta trial yang sudah masuk tahap contacted, confirmed, atau attended.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="trial-progress-card">
                        <div class="trial-progress-value">{{ $trialFollowUpProgress ?? 0 }}%</div>
                        <div class="trial-progress-label">Follow Up Progress</div>

                        <div class="progress progress-modern mt-3 mb-4">
                            <div
                                class="progress-bar"
                                role="progressbar"
                                style="width: {{ $trialFollowUpProgress ?? 0 }}%;"
                                aria-valuenow="{{ $trialFollowUpProgress ?? 0 }}"
                                aria-valuemin="0"
                                aria-valuemax="100"
                            ></div>
                        </div>

                        <div class="trial-status-grid">
                            <div class="trial-status-item">
                                <span>Registered</span>
                                <strong>{{ number_format($trialParticipantStatusCounts['registered'] ?? 0) }}</strong>
                            </div>
                            <div class="trial-status-item">
                                <span>Contacted</span>
                                <strong>{{ number_format($trialParticipantStatusCounts['contacted'] ?? 0) }}</strong>
                            </div>
                            <div class="trial-status-item">
                                <span>Confirmed</span>
                                <strong>{{ number_format($trialParticipantStatusCounts['confirmed'] ?? 0) }}</strong>
                            </div>
                            <div class="trial-status-item">
                                <span>Attended</span>
                                <strong>{{ number_format($trialParticipantStatusCounts['attended'] ?? 0) }}</strong>
                            </div>
                            <div class="trial-status-item">
                                <span>Cancelled</span>
                                <strong>{{ number_format($trialParticipantStatusCounts['cancelled'] ?? 0) }}</strong>
                            </div>
                            <div class="trial-status-item">
                                <span>No Show</span>
                                <strong>{{ number_format($trialParticipantStatusCounts['no_show'] ?? 0) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Upcoming Trial Schedules</h5>
                        <p class="content-card-subtitle mb-0">
                            Jadwal trial terdekat yang aktif di sistem.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    @if(($upcomingTrialSchedules ?? collect())->count())
                        <div class="table-responsive">
                            <table class="table table-modern align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Schedule</th>
                                        <th>Program</th>
                                        <th>Theme</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($upcomingTrialSchedules as $schedule)
                                        <tr>
                                            <td class="fw-semibold text-dark">{{ $schedule->name }}</td>
                                            <td>{{ $schedule->program->name ?? '-' }}</td>
                                            <td>{{ $schedule->trialTheme->name ?? '-' }}</td>
                                            <td>{{ \Carbon\Carbon::parse($schedule->schedule_date)->format('d M Y') }}</td>
                                            <td>
                                                {{ $schedule->start_time ? \Carbon\Carbon::parse($schedule->start_time)->format('H:i') : '-' }}
                                                @if(!empty($schedule->end_time))
                                                    - {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="empty-state-box">
                            <div class="empty-state-icon">
                                <i class="bi bi-calendar-x"></i>
                            </div>
                            <h5 class="empty-state-title">Belum ada trial schedule mendatang</h5>
                            <p class="empty-state-text mb-0">
                                Data jadwal trial aktif yang akan datang belum tersedia.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Upcoming Batches --}}
    <div class="content-card">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Upcoming Batches</h5>
                <p class="content-card-subtitle mb-0">
                    Batch mendatang lengkap dengan nama program, kapasitas, seat terisi, dan sisa seat.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            @if(($upcomingBatches ?? collect())->count())
                <div class="table-responsive">
                    <table class="table table-modern align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Program / Batch</th>
                                <th>Start Date</th>
                                <th class="text-center">Capacity</th>
                                <th class="text-center">Filled</th>
                                <th class="text-center">Remaining</th>
                                <th width="220">Progress</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($upcomingBatches as $batch)
                                @php
                                    $capacity = (int) ($batch->capacity ?? 0);
                                    $filled = (int) ($batch->filled_seats ?? 0);
                                    $remaining = (int) ($batch->remaining_seats ?? 0);
                                    $percent = $capacity > 0 ? min(100, round(($filled / $capacity) * 100)) : 0;
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $batch->name }}</div>
                                        <div class="small text-muted">{{ $batch->program_name ?? '-' }}</div>
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($batch->start_date)->format('d M Y') }}</td>
                                    <td class="text-center">{{ number_format($capacity) }}</td>
                                    <td class="text-center">{{ number_format($filled) }}</td>
                                    <td class="text-center">{{ number_format($remaining) }}</td>
                                    <td>
                                        <div class="progress progress-modern">
                                            <div
                                                class="progress-bar"
                                                role="progressbar"
                                                style="width: {{ $percent }}%;"
                                                aria-valuenow="{{ $percent }}"
                                                aria-valuemin="0"
                                                aria-valuemax="100"
                                            >
                                                {{ $percent }}%
                                            </div>
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
                        <i class="bi bi-calendar-x"></i>
                    </div>
                    <h5 class="empty-state-title">Belum ada batch mendatang</h5>
                    <p class="empty-state-text mb-0">
                        Data batch yang akan datang belum tersedia atau tabel batch belum terisi.
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>



</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', async function () {
    const monthlyRevenueCtx = document.getElementById('monthlyRevenueChart');
    const salesPerformanceCtx = document.getElementById('salesPerformanceChart');

    if (monthlyRevenueCtx) {
        const labels = @json($revenueChart['labels'] ?? []);
        const values = @json($revenueChart['data'] ?? []);

        new Chart(monthlyRevenueCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Pendapatan',
                    data: values,
                    borderRadius: 8,
                    maxBarThickness: 42,
                    backgroundColor: 'rgba(91, 62, 142, 0.82)',
                    borderColor: 'rgba(91, 62, 142, 1)',
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
                            label: function(context) {
                                const value = Number(context.raw || 0);
                                return ' Rp ' + value.toLocaleString('id-ID');
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
                            color: '#6b7280'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#6b7280',
                            callback: function(value) {
                                return 'Rp ' + Number(value).toLocaleString('id-ID');
                            }
                        },
                        grid: {
                            color: 'rgba(107, 114, 128, 0.08)'
                        }
                    }
                }
            }
        });
    }

    if (salesPerformanceCtx) {
        try {
            const response = await fetch(@json(route('sales-performance.chart-data')), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load sales performance data.');
            }

            const result = await response.json();

            const summaryValue = document.getElementById('salesPerformanceClosedDealValue');
            if (summaryValue && result?.summary?.closed_deal !== undefined) {
                summaryValue.textContent = Number(result.summary.closed_deal || 0).toLocaleString('id-ID');
            }

            new Chart(salesPerformanceCtx, {
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
                                boxWidth: 10,
                                color: '#6b7280'
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#6b7280'
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                                color: '#6b7280'
                            },
                            grid: {
                                color: 'rgba(107, 114, 128, 0.08)'
                            }
                        }
                    }
                }
            });
        } catch (error) {
            console.error(error);
        }
    }
});
</script>
@endpush