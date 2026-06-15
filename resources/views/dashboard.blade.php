@extends('layouts.app-dashboard')

@section('title', 'Management Dashboard')

@section('content')

@php
    $salesInsight = $salesInsight ?? [];
    $academicStats = $academicStats ?? [];
    $batchCapacity = $batchCapacity ?? [];
    $trialStats = $trialStats ?? [];
    $revenueChart = $revenueChart ?? [
        'labels' => [],
        'data' => [],
        'year' => now()->year,
        'total' => 0,
    ];

    $trialParticipantStatusCounts = collect($trialParticipantStatusCounts ?? [
        'registered' => 0,
        'contacted' => 0,
        'confirmed' => 0,
        'attended' => 0,
        'cancelled' => 0,
        'no_show' => 0,
    ]);
    $trialFollowUpProgress = (int) ($trialFollowUpProgress ?? 0);
    $upcomingTrialSchedules = $upcomingTrialSchedules ?? collect();

    $workshopInsight = $workshopInsight ?? [];
    $workshopStats = $workshopStats ?? [];
    $workshopParticipantStatusCounts = collect($workshopParticipantStatusCounts ?? [
        'registered' => 0,
        'pending_payment' => 0,
        'confirmed' => 0,
        'attended' => 0,
        'cancelled' => 0,
    ]);
    $workshopFollowUpProgress = (int) ($workshopFollowUpProgress ?? 0);
    $upcomingWorkshopSchedules = $upcomingWorkshopSchedules ?? collect();

    $financeInsight = $financeInsight ?? [];
    $orderInsight = $orderInsight ?? [];
    $managementSummary = $managementSummary ?? [];
    $upcomingBatches = $upcomingBatches ?? collect();

    $salesLeads = (int) ($salesInsight['leads'] ?? 0);
    $salesInteracted = (int) ($salesInsight['interacted'] ?? $salesInsight['trial'] ?? 0);
    $salesClosing = (int) ($salesInsight['closing'] ?? $salesInsight['closed_deal'] ?? $salesInsight['join'] ?? 0);
    $salesPaid = (int) ($salesInsight['paid'] ?? 0);

    $salesInteractionRate = (float) ($salesInsight['interaction_rate'] ?? $salesInsight['conversion_trial'] ?? 0);
    $salesClosingRate = (float) ($salesInsight['closing_rate'] ?? $salesInsight['deal_rate'] ?? $salesInsight['conversion_join'] ?? 0);
    $salesPaidRate = (float) ($salesInsight['paid_rate'] ?? $salesInsight['conversion_paid'] ?? 0);

    $dashboardAiHeadline = (string) ($managementSummary['headline'] ?? 'Management Summary');
    $dashboardAiSummaryText = (string) ($dashboardAiSummaryText ?? ($managementSummary['summary_text'] ?? ''));
    $dashboardAiGeneratedAt = (string) ($managementSummary['generated_at'] ?? now()->format('d M Y H:i'));
    $dashboardAiFocusItems = collect($managementSummary['focus'] ?? ($managementSummary['items'] ?? []))
        ->take(3)
        ->values();

    if (blank($dashboardAiSummaryText)) {
        $dashboardAiSummaryText = 'Summary dashboard belum tersedia karena data utama masih kosong.';
    }

    $currentUser = auth()->user();

    $canManageCurriculum = $currentUser
        && method_exists($currentUser, 'canAccess')
        && $currentUser->canAccess('curriculum.view')
        && Route::has('curriculum.index');
@endphp

<div class="container-fluid px-4 py-4">

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Overview Dashboard</div>
                <h1 class="page-title mb-2">Business & Academic Overview</h1>
                <p class="page-subtitle mb-0">
                    Pantau performa bisnis dan operasional dari sisi sales performance, academic, kapasitas batch,
                    trial performance, serta pendapatan dalam satu dashboard.
                </p>
            </div>

            @if($canManageCurriculum)
                <div class="page-header-actions d-flex gap-2 flex-wrap">
                    <a href="{{ route('curriculum.index') }}" class="btn btn-light btn-modern">
                        <i class="bi bi-gear-fill"></i> Manage Curriculum
                    </a>
                </div>
            @endif
        </div>
    </div>


    <div class="dashboard-section-label mb-3">
        <div class="dashboard-section-eyebrow">Sales Overview</div>
        <h4 class="dashboard-section-title mb-1">Sales Performance Summary</h4>
        <p class="dashboard-section-subtitle mb-0">
            Monitoring performa sales berdasarkan total leads, interaksi, closing, dan pembayaran yang sudah berhasil dikonfirmasi.
        </p>
    </div>

    {{-- Sales Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="funnel-card">
                <div class="funnel-card-top">
                    <div class="funnel-icon-wrap">
                        <i class="bi bi-person-lines-fill"></i>
                    </div>
                    <div>
                        <div class="funnel-title">Leads</div>
                        <div class="funnel-value">{{ number_format($salesLeads) }}</div>
                    </div>
                </div>
                <div class="funnel-description">
                    Total leads yang tercatat dari sales daily report.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="funnel-card">
                <div class="funnel-card-top">
                    <div class="funnel-icon-wrap">
                        <i class="bi bi-chat-left-text"></i>
                    </div>
                    <div>
                        <div class="funnel-title">Interacted</div>
                        <div class="funnel-value">{{ number_format($salesInteracted) }}</div>
                    </div>
                </div>
                <div class="funnel-description">
                    Interaction rate:
                    <strong>{{ number_format($salesInteractionRate, 1) }}%</strong>
                    dari total leads.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="funnel-card">
                <div class="funnel-card-top">
                    <div class="funnel-icon-wrap">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div>
                        <div class="funnel-title">Closing</div>
                        <div class="funnel-value">{{ number_format($salesClosing) }}</div>
                    </div>
                </div>
                <div class="funnel-description">
                    Closing rate:
                    <strong>{{ number_format($salesClosingRate, 1) }}%</strong>
                    dari total leads.
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
                        <div class="funnel-value">{{ number_format($salesPaid) }}</div>
                    </div>
                </div>
                <div class="funnel-description">
                    Paid rate:
                    <strong>{{ number_format($salesPaidRate, 1) }}%</strong>
                    dari total closing.
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
                <div class="revenue-total-value" id="salesPerformanceClosedDealValue">{{ number_format($salesClosing) }}</div>
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

    <div class="dashboard-section-label mb-3 mt-1">
        <div class="dashboard-section-eyebrow">Trial Overview</div>
        <h4 class="dashboard-section-title mb-1">Trial Performance This Month</h4>
        <p class="dashboard-section-subtitle mb-0">
            Monitoring performa trial bulan berjalan berdasarkan jadwal, peserta baru, progress follow-up, dan status kehadiran.
        </p>
    </div>

    {{-- Trial Stats This Month --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-brush"></i>
                    </div>
                    <div>
                        <div class="stat-title">Active Trial Themes</div>
                        <div class="stat-value">{{ number_format($trialStats['themes_active'] ?? $trialStats['themes_total'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">Theme trial aktif yang tersedia di sistem.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div>
                        <div class="stat-title">Trial Schedules This Month</div>
                        <div class="stat-value">{{ number_format($trialStats['schedules_active_this_month'] ?? $trialStats['schedules_active'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">Jadwal trial aktif untuk bulan berjalan.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Trial Participants This Month</div>
                        <div class="stat-value">{{ number_format($trialStats['participants_this_month'] ?? $trialStats['participants_total'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">Peserta trial yang masuk pada bulan berjalan.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-person-plus"></i>
                    </div>
                    <div>
                        <div class="stat-title">Trial Participants All Time</div>
                        <div class="stat-value">{{ number_format($trialStats['participants_all_time'] ?? $trialStats['participants_total'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">Total peserta trial yang tercatat sejak awal.</div>
            </div>
        </div>
    </div>


    {{-- Trial Progress + Upcoming Trial Schedule --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-5">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Trial Follow Up Progress This Month</h5>
                        <p class="content-card-subtitle mb-0">
                            Persentase peserta trial bulan ini yang sudah masuk tahap contacted, confirmed, atau attended.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="trial-progress-card">
                        <div class="trial-progress-value">{{ $trialFollowUpProgress ?? 0 }}%</div>
                        <div class="trial-progress-label">Follow Up Progress Bulan Ini</div>

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

    <div class="dashboard-section-label mb-3 mt-1">
        <div class="dashboard-section-eyebrow">Workshop Overview</div>
        <h4 class="dashboard-section-title mb-1">Workshop Performance This Month</h4>
        <p class="dashboard-section-subtitle mb-0">
            Monitoring performa workshop berdasarkan jadwal, peserta, status pembayaran, attendance, dan revenue bulan berjalan.
        </p>
    </div>

    {{-- Workshop Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-easel2-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Active Workshops</div>
                        <div class="stat-value">{{ number_format($workshopStats['workshops_active'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Dari total {{ number_format($workshopStats['workshops_total'] ?? 0) }} workshop yang terdaftar.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-calendar2-week"></i>
                    </div>
                    <div>
                        <div class="stat-title">Schedules This Month</div>
                        <div class="stat-value">{{ number_format($workshopStats['schedules_active_this_month'] ?? $workshopStats['schedules_this_month'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Jadwal workshop aktif pada bulan berjalan.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-person-workspace"></i>
                    </div>
                    <div>
                        <div class="stat-title">Participants This Month</div>
                        <div class="stat-value">{{ number_format($workshopStats['participants_this_month'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    @if(!empty($workshopStats['top_source']))
                        Top source: <strong>{{ $workshopStats['top_source'] }}</strong>
                        ({{ number_format($workshopStats['top_source_total'] ?? 0) }} peserta).
                    @else
                        Peserta workshop yang masuk pada bulan berjalan.
                    @endif
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
                        <div class="stat-title">Workshop Revenue</div>
                        <div class="stat-value stat-value-currency">Rp {{ number_format($workshopStats['revenue_this_month'] ?? 0, 0, ',', '.') }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Dari {{ number_format($workshopStats['paid_count_this_month'] ?? 0) }} pembayaran workshop bulan ini.
                </div>
            </div>
        </div>
    </div>

    {{-- Workshop Progress + Upcoming Workshop Schedule --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-5">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Workshop Conversion Progress This Month</h5>
                        <p class="content-card-subtitle mb-0">
                            Persentase peserta workshop bulan ini yang sudah confirmed atau attended.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="trial-progress-card">
                        <div class="trial-progress-value">{{ $workshopFollowUpProgress ?? 0 }}%</div>
                        <div class="trial-progress-label">Conversion Progress Bulan Ini</div>

                        <div class="progress progress-modern mt-3 mb-4">
                            <div
                                class="progress-bar"
                                role="progressbar"
                                style="width: {{ $workshopFollowUpProgress ?? 0 }}%;"
                                aria-valuenow="{{ $workshopFollowUpProgress ?? 0 }}"
                                aria-valuemin="0"
                                aria-valuemax="100"
                            ></div>
                        </div>

                        <div class="trial-status-grid">
                            <div class="trial-status-item">
                                <span>Registered</span>
                                <strong>{{ number_format($workshopParticipantStatusCounts['registered'] ?? 0) }}</strong>
                            </div>
                            <div class="trial-status-item">
                                <span>Pending Payment</span>
                                <strong>{{ number_format($workshopParticipantStatusCounts['pending_payment'] ?? 0) }}</strong>
                            </div>
                            <div class="trial-status-item">
                                <span>Confirmed</span>
                                <strong>{{ number_format($workshopParticipantStatusCounts['confirmed'] ?? 0) }}</strong>
                            </div>
                            <div class="trial-status-item">
                                <span>Attended</span>
                                <strong>{{ number_format($workshopParticipantStatusCounts['attended'] ?? 0) }}</strong>
                            </div>
                            <div class="trial-status-item">
                                <span>Cancelled</span>
                                <strong>{{ number_format($workshopParticipantStatusCounts['cancelled'] ?? 0) }}</strong>
                            </div>
                            <div class="trial-status-item">
                                <span>Workshop Orders</span>
                                <strong>{{ number_format($orderInsight['workshop_orders_this_month'] ?? 0) }}</strong>
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
                        <h5 class="content-card-title mb-1">Upcoming Workshop Schedules</h5>
                        <p class="content-card-subtitle mb-0">
                            Jadwal workshop terdekat yang aktif di sistem.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    @if(($upcomingWorkshopSchedules ?? collect())->count())
                        <div class="table-responsive">
                            <table class="table table-modern align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Schedule</th>
                                        <th>Workshop</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th class="text-center">Seat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($upcomingWorkshopSchedules as $schedule)
                                        @php
                                            $workshopScheduleQuota = (int) ($schedule->quota ?? 0);
                                            $workshopScheduleRegistered = (int) ($schedule->registered_count ?? 0);
                                        @endphp
                                        <tr>
                                            <td class="fw-semibold text-dark">{{ $schedule->title ?? 'Workshop Schedule' }}</td>
                                            <td>{{ $schedule->workshop_title ?? '-' }}</td>
                                            <td>{{ !empty($schedule->schedule_date) ? \Carbon\Carbon::parse($schedule->schedule_date)->format('d M Y') : '-' }}</td>
                                            <td>
                                                {{ !empty($schedule->start_time) ? \Carbon\Carbon::parse($schedule->start_time)->format('H:i') : '-' }}
                                                @if(!empty($schedule->end_time))
                                                    - {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                {{ number_format($workshopScheduleRegistered) }}
                                                @if($workshopScheduleQuota > 0)
                                                    / {{ number_format($workshopScheduleQuota) }}
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
                            <h5 class="empty-state-title">Belum ada workshop schedule mendatang</h5>
                            <p class="empty-state-text mb-0">
                                Data jadwal workshop aktif yang akan datang belum tersedia.
                            </p>
                        </div>
                    @endif
                </div>
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

<div class="ai-dashboard-assistant" id="aiDashboardAssistant">
    <div class="ai-dashboard-bubble" id="aiDashboardBubble">
        <button type="button" class="ai-dashboard-close" id="aiDashboardClose" aria-label="Close summary">
            <i class="bi bi-x"></i>
        </button>

        <div class="ai-dashboard-bubble-header">
            <div class="ai-dashboard-mini-icon">
                <i class="bi bi-stars"></i>
            </div>
            <div>
                <div class="ai-dashboard-label">Insight</div>
                <div class="ai-dashboard-headline">{{ $dashboardAiHeadline }}</div>
            </div>
        </div>

        <div class="ai-dashboard-text">
            {{ $dashboardAiSummaryText }}
        </div>

        @if($dashboardAiFocusItems->count())
            <div class="ai-dashboard-focus-list">
                @foreach($dashboardAiFocusItems as $item)
                    @php
                        $itemType = (string) ($item['type'] ?? 'info');
                        $itemTitle = (string) ($item['title'] ?? 'Insight');
                        $itemMessage = (string) ($item['message'] ?? $item['description'] ?? $item['text'] ?? '');
                    @endphp
                    <div class="ai-dashboard-focus-item ai-type-{{ $itemType }}">
                        <span class="ai-dashboard-focus-dot"></span>
                        <div class="ai-dashboard-focus-content">
                            <strong>{{ $itemTitle }}</strong>
                            @if(filled($itemMessage))
                                <span>{{ \Illuminate\Support\Str::limit($itemMessage, 180) }}</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="ai-dashboard-footer">
            <span>
                <i class="bi bi-clock-history"></i>
                Updated {{ $dashboardAiGeneratedAt }}
            </span>
            <span class="ai-dashboard-mode">Local Summary</span>
        </div>
    </div>

    <button type="button" class="ai-dashboard-robot" id="aiDashboardRobot" aria-label="Toggle AI summary">
        <span class="ai-dashboard-robot-glow"></span>
        <img src="{{ asset('images/ai.png') }}" alt="AI Assistant">
    </button>
</div>
@endsection

@push('styles')
<style>
.ai-dashboard-assistant {
    position: fixed;
    right: 24px;
    bottom: 24px;
    z-index: 1050;
    display: flex;
    align-items: flex-end;
    gap: 14px;
    pointer-events: none;
}

.ai-dashboard-assistant > * {
    pointer-events: auto;
}

.ai-dashboard-robot {
    width: 86px;
    height: 86px;
    border: 0;
    border-radius: 28px;
    background: linear-gradient(135deg, #5B3E8E 0%, #7C5AC7 100%);
    box-shadow: 0 20px 44px rgba(91, 62, 142, 0.32);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 10px;
    cursor: pointer;
    position: relative;
    overflow: visible;
    transition: transform 0.25s ease, box-shadow 0.25s ease;
    animation: aiRiseUp 0.9s cubic-bezier(0.22, 1, 0.36, 1) both;
    transform-origin: bottom center;
}

.ai-dashboard-robot:hover {
    transform: translateY(-5px) scale(1.035);
    box-shadow: 0 26px 54px rgba(91, 62, 142, 0.42);
}

.ai-dashboard-robot::after {
    content: '';
    position: absolute;
    inset: -8px;
    border-radius: 34px;
    border: 1px solid rgba(91, 62, 142, 0.16);
    animation: aiPulseRing 2.4s ease-in-out infinite;
}

.ai-dashboard-robot-glow {
    position: absolute;
    inset: 10px;
    border-radius: 24px;
    background: radial-gradient(circle, rgba(255, 190, 4, 0.35) 0%, rgba(255, 190, 4, 0) 62%);
    pointer-events: none;
}

.ai-dashboard-robot img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    display: block;
    position: relative;
    z-index: 1;
    filter: drop-shadow(0 8px 12px rgba(15, 23, 42, 0.18));
}

.ai-dashboard-bubble {
    width: min(430px, calc(100vw - 150px));
    max-height: min(72vh, 620px);
    overflow-y: auto;
    background: rgba(255, 255, 255, 0.98);
    border: 1px solid rgba(91, 62, 142, 0.14);
    border-radius: 24px 24px 8px 24px;
    padding: 17px 18px 15px;
    box-shadow: 0 22px 58px rgba(15, 23, 42, 0.18);
    position: relative;
    animation: aiBubbleIn 0.65s ease 0.18s both;
    backdrop-filter: blur(10px);
}

.ai-dashboard-bubble.is-reopening {
    animation: aiBubbleIn 0.32s ease both;
}

.ai-dashboard-bubble::after {
    content: '';
    position: absolute;
    right: -8px;
    bottom: 22px;
    width: 16px;
    height: 16px;
    background: #ffffff;
    border-right: 1px solid rgba(91, 62, 142, 0.14);
    border-bottom: 1px solid rgba(91, 62, 142, 0.14);
    transform: rotate(-45deg);
}

.ai-dashboard-bubble-header {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding-right: 32px;
    margin-bottom: 10px;
}

.ai-dashboard-mini-icon {
    width: 36px;
    height: 36px;
    border-radius: 14px;
    background: #ede9fe;
    color: #5B3E8E;
    display: flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
}

.ai-dashboard-label {
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #7C5AC7;
    line-height: 1.2;
    margin-bottom: 3px;
}

.ai-dashboard-headline {
    font-size: 15px;
    font-weight: 850;
    color: #111827;
    line-height: 1.35;
}

.ai-dashboard-text {
    font-size: 14px;
    line-height: 1.65;
    color: #374151;
    padding-right: 4px;
}

.ai-dashboard-focus-list {
    display: grid;
    gap: 8px;
    margin-top: 13px;
}

.ai-dashboard-focus-item {
    display: flex;
    align-items: flex-start;
    gap: 9px;
    padding: 10px 11px;
    border-radius: 16px;
    background: #f9fafb;
    border: 1px solid rgba(229, 231, 235, 0.9);
}

.ai-dashboard-focus-dot {
    width: 9px;
    height: 9px;
    border-radius: 999px;
    background: #64748b;
    flex: 0 0 auto;
    margin-top: 5px;
    box-shadow: 0 0 0 4px rgba(100, 116, 139, 0.10);
}

.ai-dashboard-focus-content {
    display: grid;
    gap: 2px;
}

.ai-dashboard-focus-content strong {
    font-size: 12.5px;
    line-height: 1.35;
    color: #111827;
}

.ai-dashboard-focus-content span {
    font-size: 12px;
    line-height: 1.45;
    color: #6b7280;
}

.ai-dashboard-focus-item.ai-type-critical .ai-dashboard-focus-dot {
    background: #ef4444;
    box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.11);
}

.ai-dashboard-focus-item.ai-type-warning .ai-dashboard-focus-dot {
    background: #f59e0b;
    box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.13);
}

.ai-dashboard-focus-item.ai-type-action .ai-dashboard-focus-dot {
    background: #5B3E8E;
    box-shadow: 0 0 0 4px rgba(91, 62, 142, 0.12);
}

.ai-dashboard-focus-item.ai-type-good .ai-dashboard-focus-dot {
    background: #22c55e;
    box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.12);
}

.ai-dashboard-focus-item.ai-type-info .ai-dashboard-focus-dot {
    background: #3b82f6;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12);
}

.ai-dashboard-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-top: 13px;
    padding-top: 11px;
    border-top: 1px solid #f1f5f9;
    color: #9ca3af;
    font-size: 11.5px;
}

.ai-dashboard-footer span {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.ai-dashboard-mode {
    background: #f5f3ff;
    color: #5B3E8E;
    border-radius: 999px;
    padding: 4px 8px;
    font-weight: 700;
    white-space: nowrap;
}

.ai-dashboard-close {
    position: absolute;
    top: 11px;
    right: 11px;
    width: 28px;
    height: 28px;
    border: 0;
    border-radius: 999px;
    background: #f3f4f6;
    color: #6b7280;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease;
    z-index: 2;
}

.ai-dashboard-close:hover {
    background: #ede9fe;
    color: #5B3E8E;
    transform: rotate(90deg);
}

.ai-dashboard-assistant.is-collapsed .ai-dashboard-bubble {
    display: none;
}

@keyframes aiRiseUp {
    0% {
        opacity: 0;
        transform: translateY(110px) scale(0.82);
    }
    58% {
        opacity: 1;
        transform: translateY(-10px) scale(1.035);
    }
    100% {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@keyframes aiBubbleIn {
    0% {
        opacity: 0;
        transform: translateY(18px) scale(0.96);
    }
    100% {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@keyframes aiPulseRing {
    0%, 100% {
        opacity: 0.3;
        transform: scale(1);
    }
    50% {
        opacity: 0.9;
        transform: scale(1.05);
    }
}

@media (max-width: 768px) {
    .ai-dashboard-assistant {
        right: 16px;
        bottom: 16px;
        gap: 8px;
    }

    .ai-dashboard-robot {
        width: 68px;
        height: 68px;
        border-radius: 22px;
    }

    .ai-dashboard-bubble {
        width: calc(100vw - 108px);
        padding: 14px 15px;
        border-radius: 20px 20px 8px 20px;
    }

    .ai-dashboard-headline {
        font-size: 14px;
    }

    .ai-dashboard-text {
        font-size: 13px;
        line-height: 1.55;
    }

    .ai-dashboard-focus-content span {
        display: block;
        font-size: 11.5px;
        line-height: 1.4;
    }
}

@media (max-width: 576px) {
    .ai-dashboard-assistant {
        align-items: flex-end;
    }

    .ai-dashboard-bubble {
        width: calc(100vw - 104px);
        max-width: 300px;
    }

    .ai-dashboard-footer {
        align-items: flex-start;
        flex-direction: column;
        gap: 7px;
    }
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', async function () {
    const monthlyRevenueCtx = document.getElementById('monthlyRevenueChart');
    const salesPerformanceCtx = document.getElementById('salesPerformanceChart');
    const aiAssistant = document.getElementById('aiDashboardAssistant');
    const aiRobot = document.getElementById('aiDashboardRobot');
    const aiClose = document.getElementById('aiDashboardClose');

    if (aiAssistant && aiRobot) {
        aiRobot.addEventListener('click', function () {
            aiAssistant.classList.toggle('is-collapsed');
        });
    }

    if (aiAssistant && aiClose) {
        aiClose.addEventListener('click', function (event) {
            event.stopPropagation();
            aiAssistant.classList.add('is-collapsed');
        });
    }

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