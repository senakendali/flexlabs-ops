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


    $kommoTodayLeadInsight = $kommoTodayLeadInsight ?? [];
    $kommoAvailable = (bool) ($kommoTodayLeadInsight['is_available'] ?? false);
    $kommoTotalLeads = (int) ($kommoTodayLeadInsight['total_leads'] ?? 0);

    // Raw Kommo pipeline states.
    // Incoming Leads / Lead masuk = lead mentah yang belum disentuh sales.
    // Initial Contact dan New Leads = sudah disentuh sales, jadi masuk Sudah Follow-up.
    $kommoIncomingLeads = (int) ($kommoTodayLeadInsight['incoming_leads'] ?? $kommoTodayLeadInsight['lead_masuk'] ?? 0);
    $kommoInitialContact = (int) ($kommoTodayLeadInsight['initial_contact'] ?? 0);
    $kommoNewLeads = (int) ($kommoTodayLeadInsight['new_leads'] ?? 0);

    $kommoInteracted = (int) ($kommoTodayLeadInsight['interacted'] ?? 0);
    $kommoIgnored = (int) ($kommoTodayLeadInsight['ignored'] ?? 0);
    $kommoClosedLost = (int) ($kommoTodayLeadInsight['closed_lost'] ?? 0);
    $kommoNotRelated = (int) ($kommoTodayLeadInsight['not_related'] ?? 0);
    $kommoWarmLeads = (int) ($kommoTodayLeadInsight['warm_leads'] ?? 0);
    $kommoHotLeads = (int) ($kommoTodayLeadInsight['hot_leads'] ?? 0);
    $kommoConsultation = (int) ($kommoTodayLeadInsight['consultation'] ?? 0);

    $kommoFollowedUp = (int) ($kommoTodayLeadInsight['followed_up'] ?? max($kommoTotalLeads - $kommoIncomingLeads, 0));
    $kommoNotFollowedUp = (int) ($kommoTodayLeadInsight['not_followed_up'] ?? $kommoIncomingLeads);
    $kommoNeedAction = (int) ($kommoTodayLeadInsight['need_action'] ?? $kommoTodayLeadInsight['needs_attention'] ?? $kommoNotFollowedUp);
    $kommoFollowUpRate = (int) ($kommoTodayLeadInsight['follow_up_rate'] ?? ($kommoTotalLeads > 0 ? round(($kommoFollowedUp / $kommoTotalLeads) * 100) : 0));

    $kommoProgressClass = $kommoFollowUpRate >= 80
        ? 'bg-success'
        : ($kommoFollowUpRate >= 50 ? 'bg-warning' : 'bg-danger');
    $kommoAttentionClass = $kommoNeedAction > 0 ? 'text-warning' : 'text-success';
    $kommoStatusBadgeClass = $kommoAvailable ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning';
    $kommoStatusBadgeText = $kommoAvailable ? 'Synced' : 'Not Synced';

    $dashboardAiSummaryText = (string) ($dashboardAiSummaryText ?? ($managementSummary['summary_text'] ?? ''));

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
        <div class="dashboard-section-eyebrow">Kommo Leads Overview</div>
        <h4 class="dashboard-section-title mb-1">Lead Hari Ini dari Kommo</h4>
        <p class="dashboard-section-subtitle mb-0">
            Monitoring lead yang masuk hari ini dari Kommo: incoming leads yang perlu screening, state follow-up sales,
            dan progress follow-up agar lead baru tidak dingin.
        </p>
    </div>

    {{-- Kommo Leads Today Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="funnel-card h-100">
                <div class="funnel-card-top">
                    <div class="funnel-icon-wrap">
                        <i class="bi bi-inboxes-fill"></i>
                    </div>
                    <div>
                        <div class="funnel-title">Lead Hari Ini</div>
                        <div class="funnel-value">{{ number_format($kommoTotalLeads) }}</div>
                    </div>
                </div>
                <div class="funnel-description">
                    Total lead baru yang masuk dari Kommo hari ini.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="funnel-card h-100">
                <div class="funnel-card-top">
                    <div class="funnel-icon-wrap">
                        <i class="bi bi-chat-dots-fill"></i>
                    </div>
                    <div>
                        <div class="funnel-title">Sudah Follow-up</div>
                        <div class="funnel-value text-success">{{ number_format($kommoFollowedUp) }}</div>
                    </div>
                </div>
                <div class="funnel-description">
                    Lead yang sudah masuk status proses atau interaksi sales.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="funnel-card h-100">
                <div class="funnel-card-top">
                    <div class="funnel-icon-wrap">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                    </div>
                    <div>
                        <div class="funnel-title">Belum Follow-up</div>
                        <div class="funnel-value {{ $kommoAttentionClass }}">{{ number_format($kommoNotFollowedUp) }}</div>
                    </div>
                </div>
                <div class="funnel-description">
                    Lead yang belum terlihat masuk status follow-up di Kommo.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="funnel-card h-100">
                <div class="funnel-card-top">
                    <div class="funnel-icon-wrap">
                        <i class="bi bi-speedometer2"></i>
                    </div>
                    <div>
                        <div class="funnel-title">Follow-up Rate</div>
                        <div class="funnel-value">{{ number_format($kommoFollowUpRate) }}%</div>
                    </div>
                </div>
                <div class="funnel-description">
                    Persentase lead yang sudah masuk status follow-up.
                </div>
            </div>
        </div>
    </div>

    {{-- Kommo Leads Today Progress --}}
    <div class="content-card mb-4">
        <div class="content-card-header d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h5 class="content-card-title mb-1">Kommo Follow-up Progress</h5>
                <p class="content-card-subtitle mb-0">
                    Progress follow-up dihitung dari lead yang sudah masuk status proses atau interaksi sales.
                </p>
            </div>

            <span class="badge rounded-pill {{ $kommoStatusBadgeClass }}">
                {{ $kommoStatusBadgeText }}
            </span>
        </div>

        <div class="content-card-body">
            <div class="trial-progress-card kommo-progress-row-card">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-3">
                    <div>
                        <div class="trial-progress-value">{{ number_format($kommoFollowUpRate) }}%</div>
                        <div class="trial-progress-label">Follow-up Progress Hari Ini</div>
                    </div>

                    <div class="text-lg-end">
                        <div class="small text-muted">Update terakhir</div>
                        <div class="fw-semibold text-dark">
                            {{ $kommoTodayLeadInsight['last_synced_at'] ?? '-' }}
                        </div>
                    </div>
                </div>

                <div class="progress progress-modern mb-4">
                    <div
                        class="progress-bar {{ $kommoProgressClass }}"
                        role="progressbar"
                        style="width: {{ min($kommoFollowUpRate, 100) }}%;"
                        aria-valuenow="{{ $kommoFollowUpRate }}"
                        aria-valuemin="0"
                        aria-valuemax="100"
                    ></div>
                </div>

                <div class="row g-3">
                    <div class="col-xl-3 col-md-6">
                        <div class="kommo-progress-metric h-100">
                            <div class="kommo-progress-metric-left">
                                <div class="kommo-progress-metric-icon bg-primary-subtle text-primary">
                                    <i class="bi bi-people"></i>
                                </div>
                                <span>Total Lead</span>
                            </div>
                            <strong>{{ number_format($kommoTotalLeads) }}</strong>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="kommo-progress-metric h-100">
                            <div class="kommo-progress-metric-left">
                                <div class="kommo-progress-metric-icon bg-success-subtle text-success">
                                    <i class="bi bi-check2-circle"></i>
                                </div>
                                <span>Followed Up</span>
                            </div>
                            <strong class="text-success">{{ number_format($kommoFollowedUp) }}</strong>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="kommo-progress-metric h-100">
                            <div class="kommo-progress-metric-left">
                                <div class="kommo-progress-metric-icon bg-warning-subtle text-warning">
                                    <i class="bi bi-exclamation-triangle"></i>
                                </div>
                                <span>Need Action</span>
                            </div>
                            <strong class="{{ $kommoAttentionClass }}">{{ number_format($kommoNeedAction) }}</strong>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="kommo-progress-metric h-100">
                            <div class="kommo-progress-metric-left">
                                <div class="kommo-progress-metric-icon {{ $kommoAvailable ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">
                                    <i class="bi {{ $kommoAvailable ? 'bi-cloud-check' : 'bi-cloud-slash' }}"></i>
                                </div>
                                <span>Sync Status</span>
                            </div>
                            <strong class="kommo-sync-value {{ $kommoAvailable ? 'text-success' : 'text-warning' }}">
                                {{ $kommoAvailable ? 'Synced' : 'Not Synced' }}
                            </strong>
                        </div>
                    </div>
                </div>

                @if(! $kommoAvailable && ! empty($kommoTodayLeadInsight['error_message']))
                    <div class="alert alert-warning mt-3 mb-0">
                        {{ $kommoTodayLeadInsight['error_message'] }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Kommo Leads Today Breakdown --}}
    <div class="content-card mb-4">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Kommo Lead Status Breakdown</h5>
                <p class="content-card-subtitle mb-0">
                    Ringkasan status lead hari ini untuk membantu tim sales menentukan prioritas follow-up.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            <div class="kommo-insight-box mb-3">
                <div class="d-flex align-items-start gap-3">
                    <div class="kommo-insight-icon">
                        <i class="bi bi-robot"></i>
                    </div>
                    <div>
                        <div class="fw-semibold text-dark mb-1">Insight Lead Kommo</div>
                        <p class="text-muted mb-0">
                            {{ $kommoTodayLeadInsight['summary_text'] ?? 'Data lead Kommo hari ini belum tersedia.' }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-modern align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th class="text-center">Total</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="fw-semibold text-dark">Interacted</td>
                            <td class="text-center">{{ number_format($kommoInteracted) }}</td>
                            <td class="text-muted">Lead sudah ada interaksi awal.</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-dark">Warm Leads</td>
                            <td class="text-center">{{ number_format($kommoWarmLeads) }}</td>
                            <td class="text-muted">Lead mulai menunjukkan minat.</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-dark">Hot Leads</td>
                            <td class="text-center">{{ number_format($kommoHotLeads) }}</td>
                            <td class="text-muted">Lead prioritas tinggi untuk dikejar closing.</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-dark">Consultation</td>
                            <td class="text-center">{{ number_format($kommoConsultation) }}</td>
                            <td class="text-muted">Lead sudah masuk tahap konsultasi.</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-dark">Ignored</td>
                            <td class="text-center">{{ number_format($kommoIgnored) }}</td>
                            <td class="text-muted">Lead tidak merespons atau belum lanjut.</td>
                        </tr>
                        <tr>
                            <td class="fw-semibold text-dark">Closed Lost / Not Related</td>
                            <td class="text-center">{{ number_format($kommoClosedLost + $kommoNotRelated) }}</td>
                            <td class="text-muted">Lead sudah diproses tapi tidak lanjut.</td>
                        </tr>
                    </tbody>
                </table>
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

<x-ai-insight-widget
    title="AI Dashboard Summary"
    :insight="$managementSummary ?? []"
    :summary="$dashboardAiSummaryText ?? null"
/>
@endsection

@push('styles')
<style>
    .kommo-insight-box {
        border: 1px solid rgba(91, 62, 142, 0.12);
        border-radius: 18px;
        background: linear-gradient(135deg, rgba(91, 62, 142, 0.06), rgba(255, 190, 4, 0.08));
        padding: 16px;
    }

    .kommo-insight-icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        color: #5B3E8E;
        background: rgba(91, 62, 142, 0.12);
        font-size: 1.15rem;
    }
</style>
@endpush


@push('styles')
<style>
    .kommo-progress-row-card {
        background: linear-gradient(135deg, rgba(91, 62, 142, 0.08), rgba(255, 190, 4, 0.08));
        border: 1px solid rgba(91, 62, 142, 0.10);
        border-radius: 20px;
        padding: 1.25rem;
    }

    .kommo-progress-metric {
        background: #ffffff;
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 16px;
        padding: 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
    }

    .kommo-progress-metric-left {
        display: flex;
        align-items: center;
        gap: .75rem;
        min-width: 0;
    }

    .kommo-progress-metric-icon {
        width: 36px;
        height: 36px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        font-size: 1rem;
    }

    .kommo-progress-metric span {
        color: #6b7280;
        font-size: .85rem;
        font-weight: 600;
        line-height: 1.2;
    }

    .kommo-progress-metric strong {
        color: #111827;
        font-size: 1.2rem;
        font-weight: 800;
        white-space: nowrap;
    }

    .kommo-progress-metric .kommo-sync-value {
        font-size: .95rem;
        font-weight: 700;
    }

    .kommo-insight-box {
        background: rgba(91, 62, 142, 0.06);
        border: 1px solid rgba(91, 62, 142, 0.10);
        border-radius: 18px;
        padding: 1rem;
    }

    .kommo-insight-icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        background: rgba(91, 62, 142, 0.12);
        color: #5B3E8E;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 1.15rem;
    }
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