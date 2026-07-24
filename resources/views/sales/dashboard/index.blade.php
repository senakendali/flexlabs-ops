@extends('layouts.app-dashboard')

@section('title', 'Sales Dashboard')

@section('content')
@php
    $filters = $filters ?? [
        'date_from' => now()->subDays(29)->toDateString(),
        'date_to' => now()->toDateString(),
    ];

    $period = $period ?? [
        'label' => '-',
        'previous_label' => '-',
    ];

    $salesInsight = $salesInsight ?? [];
    $salesPerformanceChart = $salesPerformanceChart ?? [
        'labels' => [],
        'datasets' => [],
        'summary' => [],
    ];

    $kommoTodayLeadInsight = $kommoTodayLeadInsight ?? [];
    $trialStats = $trialStats ?? [];
    $trialParticipantStatusCounts = collect($trialParticipantStatusCounts ?? []);
    $trialFollowUpProgress = (int) ($trialFollowUpProgress ?? 0);
    $upcomingTrialSchedules = collect($upcomingTrialSchedules ?? []);

    $workshopStats = $workshopStats ?? [];
    $workshopParticipantStatusCounts = collect($workshopParticipantStatusCounts ?? []);
    $workshopFollowUpProgress = (int) ($workshopFollowUpProgress ?? 0);
    $upcomingWorkshopSchedules = collect($upcomingWorkshopSchedules ?? []);

    $financeInsight = $financeInsight ?? [];
    $orderInsight = $orderInsight ?? [];
    $revenueChart = $revenueChart ?? [
        'labels' => [],
        'datasets' => [],
    ];

    $batchCapacity = $batchCapacity ?? [];
    $upcomingBatches = collect($upcomingBatches ?? []);
    $salesSummary = $salesSummary ?? [];
    $salesDashboardAiSummaryText = (string) ($salesDashboardAiSummaryText ?? ($salesSummary['summary_text'] ?? ''));

    $formatCurrency = fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');

    $changeMeta = function (
        array $change,
        bool $positiveIsGood = true,
        bool $isCurrency = false
    ) use ($formatCurrency): array {
        $current = (float) ($change['current'] ?? 0);
        $previous = (float) ($change['previous'] ?? 0);
        $direction = (string) ($change['direction'] ?? 'flat');
        $percentage = abs((float) ($change['percentage'] ?? 0));

        $formatValue = fn (float $value) => $isCurrency
            ? $formatCurrency($value)
            : number_format($value, 0, ',', '.');

        if ($previous <= 0 && $current > 0) {
            return [
                'class' => $positiveIsGood
                    ? 'bg-success-subtle text-success'
                    : 'bg-danger-subtle text-danger',
                'icon' => 'bi-stars',
                'text' => 'New',
                'comparison' => $formatValue($previous) . ' → ' . $formatValue($current),
            ];
        }

        if ($direction === 'flat') {
            return [
                'class' => 'bg-light text-muted',
                'icon' => 'bi-dash',
                'text' => 'No change',
                'comparison' => $formatValue($previous) . ' → ' . $formatValue($current),
            ];
        }

        $isPositive = $direction === 'up';
        $class = $isPositive
            ? ($positiveIsGood ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger')
            : ($positiveIsGood ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success');

        return [
            'class' => $class,
            'icon' => $isPositive ? 'bi-arrow-up-right' : 'bi-arrow-down-right',
            'text' => ($isPositive ? '+' : '-') . number_format($percentage, 1) . '%',
            'comparison' => $formatValue($previous) . ' → ' . $formatValue($current),
        ];
    };

    /*
    |--------------------------------------------------------------------------
    | KPI order
    |--------------------------------------------------------------------------
    | Tiga card pertama harus menjawab hasil bisnis lebih dulu:
    | Leads → Closed Deal → Confirmed Revenue.
    | Card operasional berikutnya baru Interacted → Consultation → Paid Orders.
    */
    $salesMetricCards = [
        [
            'key' => 'leads',
            'label' => 'Total Leads',
            'value' => (int) ($salesInsight['leads'] ?? 0),
            'icon' => 'bi-person-lines-fill',
            'description' => 'Seluruh lead yang tercatat pada Sales Daily Report dalam periode terpilih.',
            'change' => $salesInsight['changes']['leads'] ?? [],
        ],
        [
            'key' => 'closed_deal',
            'label' => 'Closed Deal',
            'value' => (int) ($salesInsight['closed_deal'] ?? 0),
            'icon' => 'bi-check2-circle',
            'description' => 'Closing rate ' . number_format((float) ($salesInsight['closing_rate'] ?? 0), 1) . '% dari total leads.',
            'change' => $salesInsight['changes']['closed_deal'] ?? [],
        ],
        [
            'key' => 'confirmed_revenue',
            'label' => 'Confirmed Revenue',
            'value' => $formatCurrency($salesInsight['confirmed_revenue'] ?? 0),
            'icon' => 'bi-cash-stack',
            'description' => 'Revenue dari payment berstatus terkonfirmasi pada periode terpilih.',
            'change' => $salesInsight['changes']['confirmed_revenue'] ?? [],
            'is_currency' => true,
        ],
        [
            'key' => 'interacted',
            'label' => 'Interacted',
            'value' => (int) ($salesInsight['interacted'] ?? 0),
            'icon' => 'bi-chat-left-text-fill',
            'description' => 'Interaction rate ' . number_format((float) ($salesInsight['interaction_rate'] ?? 0), 1) . '% dari total leads.',
            'change' => $salesInsight['changes']['interacted'] ?? [],
        ],
        [
            'key' => 'consultation',
            'label' => 'Consultation',
            'value' => (int) ($salesInsight['consultation'] ?? 0),
            'icon' => 'bi-headset',
            'description' => 'Consultation rate ' . number_format((float) ($salesInsight['consultation_rate'] ?? 0), 1) . '% dari total leads.',
            'change' => [],
        ],
        [
            'key' => 'paid',
            'label' => 'Paid Orders',
            'value' => (int) ($salesInsight['paid'] ?? 0),
            'icon' => 'bi-wallet2',
            'description' => 'Payment-to-deal ratio ' . number_format((float) ($salesInsight['paid_rate'] ?? 0), 1) . '% pada periode yang sama; bukan cohort conversion.',
            'change' => $salesInsight['changes']['paid'] ?? [],
        ],
    ];

    $kommoAvailable = (bool) ($kommoTodayLeadInsight['is_available'] ?? false);
    $kommoTotalLeads = (int) ($kommoTodayLeadInsight['total_leads'] ?? 0);
    $kommoFollowedUp = (int) ($kommoTodayLeadInsight['followed_up'] ?? 0);
    $kommoNeedAction = (int) ($kommoTodayLeadInsight['need_action'] ?? 0);
    $kommoFollowUpRate = (int) ($kommoTodayLeadInsight['follow_up_rate'] ?? 0);
    $kommoStatusBreakdown = collect($kommoTodayLeadInsight['status_breakdown'] ?? []);

    $kommoProgressClass = $kommoFollowUpRate >= 80
        ? 'bg-success'
        : ($kommoFollowUpRate >= 50 ? 'bg-warning' : 'bg-danger');

    $trialProgressClass = $trialFollowUpProgress >= 80
        ? 'bg-success'
        : ($trialFollowUpProgress >= 50 ? 'bg-warning' : 'bg-danger');

    $workshopProgressClass = $workshopFollowUpProgress >= 80
        ? 'bg-success'
        : ($workshopFollowUpProgress >= 50 ? 'bg-warning' : 'bg-danger');

    $currentUser = auth()->user();
    $studentActionRouteName = Route::has('students.create')
        ? 'students.create'
        : (Route::has('students.index') ? 'students.index' : null);

    $studentActionLabel = $studentActionRouteName === 'students.create'
        ? 'Add Student'
        : 'Manage Students';

    $studentActionIcon = $studentActionRouteName === 'students.create'
        ? 'bi-person-plus-fill'
        : 'bi-people-fill';

    $canAddStudent = $currentUser
        && method_exists($currentUser, 'canAccess')
        && $currentUser->canAccess('students.view')
        && $currentUser->canAccess('students.create')
        && filled($studentActionRouteName);
@endphp

<div class="container-fluid px-4 py-4 sales-dashboard-page">
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Sales Dashboard</div>
                <h1 class="page-title mb-2">Sales Performance & Conversion</h1>
                <p class="page-subtitle mb-0">
                    Pantau funnel sales, follow-up Kommo, trial dan workshop conversion, payment, revenue, serta ketersediaan seat dalam satu dashboard.
                </p>
            </div>

            @if($canAddStudent)
                <div class="page-header-actions d-flex gap-2 flex-wrap">
                    <a
                        href="{{ route($studentActionRouteName) }}"
                        class="btn btn-light btn-modern sales-add-student-btn"
                        title="{{ $studentActionLabel }}"
                    >
                        <i class="bi {{ $studentActionIcon }}"></i>
                        {{ $studentActionLabel }}
                    </a>
                </div>
            @endif
        </div>
    </div>

    <div class="content-card mb-4 sales-filter-card">
        <div class="content-card-body">
            <form
                id="salesDashboardFilterForm"
                method="GET"
                action="{{ route('sales.dashboard') }}"
                class="row g-3 align-items-end"
                data-auto-submit="true"
            >
                <div class="col-xl-5 col-md-5">
                    <label for="date_from" class="form-label fw-semibold">Date From</label>
                    <input
                        type="date"
                        id="date_from"
                        name="date_from"
                        class="form-control"
                        value="{{ $filters['date_from'] ?? '' }}"
                        max="{{ now()->toDateString() }}"
                    >
                </div>

                <div class="col-xl-5 col-md-5">
                    <label for="date_to" class="form-label fw-semibold">Date To</label>
                    <input
                        type="date"
                        id="date_to"
                        name="date_to"
                        class="form-control"
                        value="{{ $filters['date_to'] ?? '' }}"
                        max="{{ now()->toDateString() }}"
                    >
                </div>

                <div class="col-xl-2 col-md-2">
                    <a
                        href="{{ route('sales.dashboard') }}"
                        class="btn btn-danger btn-modern w-100 sales-filter-reset-btn"
                        title="Reset filter"
                    >
                        <i class="bi bi-arrow-counterclockwise me-1"></i>
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="dashboard-section-label mb-3">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="dashboard-section-eyebrow">Today’s Action Center</div>
                <h4 class="dashboard-section-title mb-1">What Sales Needs to Act On</h4>
                <p class="dashboard-section-subtitle mb-0">
                    Prioritas operasional yang perlu dicek lebih dulu sebelum membaca laporan performa.
                </p>
            </div>
            <span class="sales-scope-badge sales-scope-live">
                <i class="bi bi-broadcast-pin"></i> Live & Current Queue
            </span>
        </div>
    </div>

    <div class="row g-3 mb-4 sales-action-center">
        <div class="col-xl-3 col-md-6">
            <div class="sales-action-card {{ $kommoNeedAction > 0 ? 'is-warning' : 'is-clear' }} h-100">
                <div class="sales-action-card-top">
                    <div class="sales-action-icon"><i class="bi bi-inbox-fill"></i></div>
                    <span class="sales-action-scope">Live Today</span>
                </div>
                <div class="sales-action-label">Incoming Leads</div>
                <div class="sales-action-value">{{ number_format($kommoNeedAction) }}</div>
                <div class="sales-action-help">Lead Kommo yang masih menunggu pengecekan sales.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="sales-action-card {{ ($salesInsight['hot_leads'] ?? 0) > 0 ? 'is-priority' : 'is-clear' }} h-100">
                <div class="sales-action-card-top">
                    <div class="sales-action-icon"><i class="bi bi-fire"></i></div>
                    <span class="sales-action-scope">Selected Period</span>
                </div>
                <div class="sales-action-label">Hot Leads</div>
                <div class="sales-action-value">{{ number_format($salesInsight['hot_leads'] ?? 0) }}</div>
                <div class="sales-action-help">Prospek prioritas yang paling dekat dengan keputusan.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="sales-action-card {{ ($financeInsight['pending_payment_count'] ?? 0) > 0 ? 'is-warning' : 'is-clear' }} h-100">
                <div class="sales-action-card-top">
                    <div class="sales-action-icon"><i class="bi bi-hourglass-split"></i></div>
                    <span class="sales-action-scope">All Open Payments</span>
                </div>
                <div class="sales-action-label">Pending Payments</div>
                <div class="sales-action-value">{{ number_format($financeInsight['pending_payment_count'] ?? 0) }}</div>
                <div class="sales-action-help">Nilai pending {{ $formatCurrency($financeInsight['pending_payment_total'] ?? 0) }}.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="sales-action-card {{ ($financeInsight['overdue_schedule_count'] ?? 0) > 0 ? 'is-critical' : 'is-clear' }} h-100">
                <div class="sales-action-card-top">
                    <div class="sales-action-icon"><i class="bi bi-calendar2-x-fill"></i></div>
                    <span class="sales-action-scope">All Open Schedules</span>
                </div>
                <div class="sales-action-label">Overdue Payments</div>
                <div class="sales-action-value">{{ number_format($financeInsight['overdue_schedule_count'] ?? 0) }}</div>
                <div class="sales-action-help">Nilai overdue {{ $formatCurrency($financeInsight['overdue_schedule_total'] ?? 0) }}.</div>
            </div>
        </div>
    </div>

    <div class="dashboard-section-label mb-3">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="dashboard-section-eyebrow">Sales Overview</div>
                <h4 class="dashboard-section-title mb-1">Sales Funnel Performance</h4>
                <p class="dashboard-section-subtitle mb-0">
                    Ringkasan performa sales dari leads, interaksi, konsultasi, closed deal, payment, hingga revenue terkonfirmasi.
                </p>
            </div>
            <span class="sales-scope-badge">
                <i class="bi bi-calendar-range"></i> {{ $period['label'] ?? 'Selected Period' }}
            </span>
        </div>
    </div>

    <div class="sales-kpi-scroll-shell mb-4">
        <div class="sales-kpi-scroll" role="region" aria-label="Sales funnel KPI cards" tabindex="0">
            @foreach($salesMetricCards as $metric)
                @php
                    $change = $changeMeta($metric['change'] ?? [], true, !empty($metric['is_currency']));
                @endphp
                <div class="sales-kpi-scroll-item">
                    <div class="funnel-card h-100 sales-kpi-card">
                        <div class="funnel-card-top">
                            <div class="funnel-icon-wrap">
                                <i class="bi {{ $metric['icon'] }}"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                    <div class="funnel-title">{{ $metric['label'] }}</div>

                                    @if(!empty($metric['change']))
                                        <span
                                            class="badge rounded-pill {{ $change['class'] }} sales-change-badge"
                                            title="Perubahan dibanding periode sebelumnya: {{ $change['comparison'] }}"
                                        >
                                            <i class="bi {{ $change['icon'] }}"></i>
                                            {{ $change['text'] }}
                                        </span>
                                    @endif
                                </div>

                                <div class="funnel-value {{ !empty($metric['is_currency']) ? 'sales-currency-value' : '' }}">
                                    {{ !empty($metric['is_currency']) ? $metric['value'] : number_format($metric['value']) }}
                                </div>
                            </div>
                        </div>
                        <div class="funnel-description">{{ $metric['description'] }}</div>

                        @if(!empty($metric['change']))
                            <div class="sales-kpi-comparison">
                                <span>Previous → Current</span>
                                <strong>{{ $change['comparison'] }}</strong>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-8">
            <div class="content-card h-100">
                <div class="content-card-header d-flex justify-content-between align-items-start gap-3 flex-wrap">
                    <div>
                        <h5 class="content-card-title mb-1">Sales Performance Trend</h5>
                        <p class="content-card-subtitle mb-0">
                            Perkembangan leads, interacted, consultation, hot leads, dan closed deal pada periode terpilih.
                        </p>
                    </div>

                    <div class="revenue-total-box sales-chart-summary-box">
                        <div class="revenue-total-label">Closed Deal</div>
                        <div class="revenue-total-value">
                            {{ number_format($salesPerformanceChart['summary']['closed_deal'] ?? 0) }}
                        </div>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="chart-wrap sales-main-chart-wrap">
                        <canvas id="salesPerformanceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Funnel Health</h5>
                        <p class="content-card-subtitle mb-0">
                            Indikator kualitas pipeline dan peluang yang perlu diprioritaskan.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="sales-health-list">
                        <div class="sales-health-item">
                            <div class="sales-health-item-left">
                                <div class="sales-health-icon bg-warning-subtle text-warning">
                                    <i class="bi bi-thermometer-half"></i>
                                </div>
                                <div>
                                    <div class="sales-health-label">Warm Leads</div>
                                    <div class="sales-health-help">Mulai menunjukkan minat</div>
                                </div>
                            </div>
                            <strong>{{ number_format($salesInsight['warm_leads'] ?? 0) }}</strong>
                        </div>

                        <div class="sales-health-item">
                            <div class="sales-health-item-left">
                                <div class="sales-health-icon bg-danger-subtle text-danger">
                                    <i class="bi bi-fire"></i>
                                </div>
                                <div>
                                    <div class="sales-health-label">Hot Leads</div>
                                    <div class="sales-health-help">Prioritas closing</div>
                                </div>
                            </div>
                            <strong>{{ number_format($salesInsight['hot_leads'] ?? 0) }}</strong>
                        </div>

                        <div class="sales-health-item">
                            <div class="sales-health-item-left">
                                <div class="sales-health-icon bg-secondary-subtle text-secondary">
                                    <i class="bi bi-funnel"></i>
                                </div>
                                <div>
                                    <div class="sales-health-label">Filtered Leads</div>
                                    <div class="sales-health-help">Ignored, lost, atau tidak relevan</div>
                                </div>
                            </div>
                            <strong>{{ number_format($salesInsight['bad_lead_count'] ?? 0) }}</strong>
                        </div>

                        <div class="sales-health-item">
                            <div class="sales-health-item-left">
                                <div class="sales-health-icon bg-primary-subtle text-primary">
                                    <i class="bi bi-percent"></i>
                                </div>
                                <div>
                                    <div class="sales-health-label">Lead-to-Paid Rate</div>
                                    <div class="sales-health-help">Payment dibanding total leads</div>
                                </div>
                            </div>
                            <strong>{{ number_format((float) ($salesInsight['lead_to_paid_rate'] ?? 0), 1) }}%</strong>
                        </div>
                    </div>

                    <div class="sales-report-freshness mt-3">
                        <div>
                            <span>Latest Sales Report</span>
                            <strong>
                                {{ !empty($salesInsight['latest_report_date'])
                                    ? \Carbon\Carbon::parse($salesInsight['latest_report_date'])->format('d M Y')
                                    : '-' }}
                            </strong>
                        </div>

                        @if(($salesInsight['days_since_latest_report'] ?? null) !== null)
                            <span class="badge rounded-pill {{ ($salesInsight['days_since_latest_report'] ?? 0) >= 2 ? 'bg-warning-subtle text-warning' : 'bg-success-subtle text-success' }}">
                                {{ number_format($salesInsight['days_since_latest_report']) }} hari lalu
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-section-label mb-3 mt-1">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="dashboard-section-eyebrow">Kommo Leads Overview</div>
                <h4 class="dashboard-section-title mb-1">Today’s Lead Follow-up</h4>
                <p class="dashboard-section-subtitle mb-0">
                    Monitoring lead Kommo hari ini berdasarkan lead masuk, follow-up, incoming queue, dan status pipeline.
                </p>
            </div>
            <span class="sales-scope-badge sales-scope-live"><i class="bi bi-broadcast-pin"></i> Live Today</span>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="funnel-card h-100">
                <div class="funnel-card-top">
                    <div class="funnel-icon-wrap"><i class="bi bi-inboxes-fill"></i></div>
                    <div>
                        <div class="funnel-title">Today’s Leads</div>
                        <div class="funnel-value">{{ number_format($kommoTotalLeads) }}</div>
                    </div>
                </div>
                <div class="funnel-description">Total lead baru yang masuk ke Kommo hari ini.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="funnel-card h-100">
                <div class="funnel-card-top">
                    <div class="funnel-icon-wrap"><i class="bi bi-chat-dots-fill"></i></div>
                    <div>
                        <div class="funnel-title">Followed Up</div>
                        <div class="funnel-value text-success">{{ number_format($kommoFollowedUp) }}</div>
                    </div>
                </div>
                <div class="funnel-description">Lead yang sudah keluar dari Incoming Leads.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="funnel-card h-100">
                <div class="funnel-card-top">
                    <div class="funnel-icon-wrap"><i class="bi bi-exclamation-triangle-fill"></i></div>
                    <div>
                        <div class="funnel-title">Need Action</div>
                        <div class="funnel-value {{ $kommoNeedAction > 0 ? 'text-warning' : 'text-success' }}">
                            {{ number_format($kommoNeedAction) }}
                        </div>
                    </div>
                </div>
                <div class="funnel-description">Incoming lead yang masih harus dicek sales.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="funnel-card h-100">
                <div class="funnel-card-top">
                    <div class="funnel-icon-wrap"><i class="bi bi-speedometer2"></i></div>
                    <div>
                        <div class="funnel-title">Follow-up Rate</div>
                        <div class="funnel-value">{{ number_format($kommoFollowUpRate) }}%</div>
                    </div>
                </div>
                <div class="funnel-description">Persentase lead hari ini yang sudah diproses.</div>
            </div>
        </div>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-header d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h5 class="content-card-title mb-1">Kommo Follow-up Progress</h5>
                <p class="content-card-subtitle mb-0">
                    Incoming Leads adalah satu-satunya status yang dihitung belum di-follow-up.
                </p>
            </div>

            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge rounded-pill {{ $kommoAvailable ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">
                    <i class="bi {{ $kommoAvailable ? 'bi-cloud-check-fill' : 'bi-cloud-slash-fill' }} me-1"></i>
                    {{ $kommoAvailable ? 'Synced' : 'Not Synced' }}
                </span>

                <span class="badge rounded-pill bg-primary-subtle text-primary">
                    <i class="bi bi-clock-history me-1"></i>
                    {{ $kommoTodayLeadInsight['last_synced_at'] ?? '-' }}
                </span>
            </div>
        </div>

        <div class="content-card-body">
            <div class="kommo-progress-row-card">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-3">
                    <div>
                        <div class="trial-progress-value">{{ number_format($kommoFollowUpRate) }}%</div>
                        <div class="trial-progress-label">Follow-up Progress Today</div>
                    </div>

                    <div class="sales-kommo-summary text-lg-end">
                        {{ $kommoTodayLeadInsight['summary_text'] ?? 'Kommo lead insight belum tersedia.' }}
                    </div>
                </div>

                <div class="progress progress-modern mb-4">
                    <div
                        class="progress-bar {{ $kommoProgressClass }}"
                        role="progressbar"
                        style="width: {{ $kommoFollowUpRate }}%;"
                        aria-valuenow="{{ $kommoFollowUpRate }}"
                        aria-valuemin="0"
                        aria-valuemax="100"
                    ></div>
                </div>

                <div class="row g-3">
                    <div class="col-xl-3 col-md-6">
                        <div class="kommo-progress-metric h-100">
                            <div class="kommo-progress-metric-left">
                                <div class="kommo-progress-metric-icon bg-primary-subtle text-primary"><i class="bi bi-diagram-3"></i></div>
                                <span>Total Lead</span>
                            </div>
                            <strong>{{ number_format($kommoTotalLeads) }}</strong>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="kommo-progress-metric h-100">
                            <div class="kommo-progress-metric-left">
                                <div class="kommo-progress-metric-icon bg-success-subtle text-success"><i class="bi bi-check2-circle"></i></div>
                                <span>Followed Up</span>
                            </div>
                            <strong class="text-success">{{ number_format($kommoFollowedUp) }}</strong>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="kommo-progress-metric h-100">
                            <div class="kommo-progress-metric-left">
                                <div class="kommo-progress-metric-icon bg-warning-subtle text-warning"><i class="bi bi-lightning-charge"></i></div>
                                <span>Need Action</span>
                            </div>
                            <strong class="{{ $kommoNeedAction > 0 ? 'text-warning' : 'text-success' }}">{{ number_format($kommoNeedAction) }}</strong>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="kommo-progress-metric h-100">
                            <div class="kommo-progress-metric-left">
                                <div class="kommo-progress-metric-icon bg-secondary-subtle text-secondary"><i class="bi bi-funnel-fill"></i></div>
                                <span>Filtered Leads</span>
                            </div>
                            <strong>{{ number_format($kommoTodayLeadInsight['filtered_out'] ?? 0) }}</strong>
                        </div>
                    </div>
                </div>

                @if(!$kommoAvailable && !empty($kommoTodayLeadInsight['error_message']))
                    <div class="alert alert-warning mt-3 mb-0">
                        {{ $kommoTodayLeadInsight['error_message'] }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Kommo Lead Status Breakdown</h5>
                <p class="content-card-subtitle mb-0">
                    Detail posisi lead hari ini berdasarkan status pipeline Kommo.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            <div class="table-responsive">
                <table class="table table-modern align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th class="text-center">Total</th>
                            <th>Category</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($kommoStatusBreakdown as $status)
                            <tr>
                                <td class="fw-semibold text-dark">{{ $status['status'] ?? '-' }}</td>
                                <td class="text-center">{{ number_format($status['total'] ?? 0) }}</td>
                                <td>
                                    <span class="badge rounded-pill {{ $status['badge_class'] ?? 'bg-light text-muted' }}">
                                        {{ $status['category'] ?? 'Info' }}
                                    </span>
                                </td>
                                <td class="text-muted">{{ $status['description'] ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">Belum ada breakdown status Kommo hari ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="dashboard-section-label mb-3 mt-1">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="dashboard-section-eyebrow">Trial Status</div>
                <h4 class="dashboard-section-title mb-1">Trial Status & Follow-up</h4>
                <p class="dashboard-section-subtitle mb-0">
                    Distribusi status peserta trial pada periode terpilih dan jadwal trial yang akan datang.
                </p>
            </div>
            <span class="sales-scope-badge"><i class="bi bi-calendar-range"></i> {{ $period['label'] ?? 'Selected Period' }}</span>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap"><i class="bi bi-people-fill"></i></div>
                    <div>
                        <div class="stat-title">Trial Participants</div>
                        <div class="stat-value">{{ number_format($trialStats['participants_total'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">Peserta trial baru pada periode terpilih.</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap"><i class="bi bi-telephone-forward-fill"></i></div>
                    <div>
                        <div class="stat-title">Contacted</div>
                        <div class="stat-value">{{ number_format($trialParticipantStatusCounts['contacted'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">Peserta trial yang sudah dihubungi sales.</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap"><i class="bi bi-person-check-fill"></i></div>
                    <div>
                        <div class="stat-title">Confirmed</div>
                        <div class="stat-value">{{ number_format($trialParticipantStatusCounts['confirmed'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">Peserta yang sudah mengonfirmasi kehadiran.</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap"><i class="bi bi-calendar2-check-fill"></i></div>
                    <div>
                        <div class="stat-title">Attended</div>
                        <div class="stat-value">{{ number_format($trialParticipantStatusCounts['attended'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">Peserta yang benar-benar hadir pada trial.</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-5">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Trial Follow-up Coverage</h5>
                        <p class="content-card-subtitle mb-0">Contacted, confirmed, dan attended dihitung sebagai sudah di-follow-up.</p>
                    </div>
                </div>
                <div class="content-card-body">
                    <div class="trial-progress-card">
                        <div class="trial-progress-value">{{ number_format($trialFollowUpProgress) }}%</div>
                        <div class="trial-progress-label">Follow-up Coverage</div>

                        <div class="progress progress-modern mt-3 mb-4">
                            <div class="progress-bar {{ $trialProgressClass }}" style="width: {{ $trialFollowUpProgress }}%;"></div>
                        </div>

                        <div class="trial-status-grid">
                            @foreach([
                                'registered' => 'Registered',
                                'contacted' => 'Contacted',
                                'confirmed' => 'Confirmed',
                                'attended' => 'Attended',
                                'cancelled' => 'Cancelled',
                                'no_show' => 'No Show',
                            ] as $key => $label)
                                <div class="trial-status-item">
                                    <span>{{ $label }}</span>
                                    <strong>{{ number_format($trialParticipantStatusCounts[$key] ?? 0) }}</strong>
                                </div>
                            @endforeach
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="content-card h-100">
                <div class="content-card-header d-flex justify-content-between align-items-start gap-3 flex-wrap">
                    <div>
                        <h5 class="content-card-title mb-1">Upcoming Trial Schedules</h5>
                        <p class="content-card-subtitle mb-0">Jadwal trial aktif yang paling dekat.</p>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="sales-scope-badge sales-scope-upcoming"><i class="bi bi-calendar-event"></i> Upcoming</span>
                        <span class="badge rounded-pill bg-primary-subtle text-primary">
                            {{ number_format($upcomingTrialSchedules->count()) }} schedule
                        </span>
                    </div>
                </div>
                <div class="content-card-body">
                    @if($upcomingTrialSchedules->isNotEmpty())
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
                                            <td class="fw-semibold text-dark">{{ $schedule->name ?? 'Trial Schedule' }}</td>
                                            <td>{{ $schedule->program->name ?? '-' }}</td>
                                            <td>{{ $schedule->trialTheme->name ?? '-' }}</td>
                                            <td>{{ !empty($schedule->schedule_date) ? \Carbon\Carbon::parse($schedule->schedule_date)->format('d M Y') : '-' }}</td>
                                            <td>
                                                {{ !empty($schedule->start_time) ? \Carbon\Carbon::parse($schedule->start_time)->format('H:i') : '-' }}
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
                            <div class="empty-state-icon"><i class="bi bi-calendar-x"></i></div>
                            <h5 class="empty-state-title">Belum ada trial schedule mendatang</h5>
                            <p class="empty-state-text mb-0">Jadwal trial aktif yang akan datang belum tersedia.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-section-label mb-3 mt-1">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="dashboard-section-eyebrow">Workshop Sales</div>
                <h4 class="dashboard-section-title mb-1">Workshop Registration & Payment Performance</h4>
                <p class="dashboard-section-subtitle mb-0">
                    Monitoring peserta, status konfirmasi, payment, revenue, dan jadwal workshop mendatang.
                </p>
            </div>
            <span class="sales-scope-badge"><i class="bi bi-calendar-range"></i> {{ $period['label'] ?? 'Selected Period' }}</span>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap"><i class="bi bi-person-workspace"></i></div>
                    <div>
                        <div class="stat-title">Participants</div>
                        <div class="stat-value">{{ number_format($workshopStats['participants_total'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    @if(!empty($workshopStats['top_source']))
                        Top source: <strong>{{ $workshopStats['top_source'] }}</strong> ({{ number_format($workshopStats['top_source_total'] ?? 0) }}).
                    @else
                        Peserta workshop pada periode terpilih.
                    @endif
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap"><i class="bi bi-hourglass-split"></i></div>
                    <div>
                        <div class="stat-title">Pending Payment</div>
                        <div class="stat-value">{{ number_format($workshopStats['pending_payment'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">Peserta workshop yang belum menyelesaikan pembayaran.</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap"><i class="bi bi-check2-square"></i></div>
                    <div>
                        <div class="stat-title">Confirmed / Attended</div>
                        <div class="stat-value">{{ number_format(($workshopStats['confirmed'] ?? 0) + ($workshopStats['attended'] ?? 0)) }}</div>
                    </div>
                </div>
                <div class="stat-description">Status confirmation rate {{ number_format($workshopStats['conversion_percent'] ?? 0) }}%; bukan paid conversion.</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap"><i class="bi bi-cash-coin"></i></div>
                    <div>
                        <div class="stat-title">Workshop Revenue</div>
                        <div class="stat-value stat-value-currency">{{ $formatCurrency($workshopStats['revenue'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">Dari {{ number_format($workshopStats['paid_count'] ?? 0) }} payment workshop terkonfirmasi.</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-5">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Workshop Confirmation Progress</h5>
                        <p class="content-card-subtitle mb-0">Confirmed dan attended dihitung sebagai participant yang sudah terkonfirmasi.</p>
                    </div>
                </div>
                <div class="content-card-body">
                    <div class="trial-progress-card">
                        <div class="trial-progress-value">{{ number_format($workshopFollowUpProgress) }}%</div>
                        <div class="trial-progress-label">Confirmation Progress</div>

                        <div class="progress progress-modern mt-3 mb-4">
                            <div class="progress-bar {{ $workshopProgressClass }}" style="width: {{ $workshopFollowUpProgress }}%;"></div>
                        </div>

                        <div class="trial-status-grid">
                            @foreach([
                                'registered' => 'Registered',
                                'pending_payment' => 'Pending Payment',
                                'confirmed' => 'Confirmed',
                                'attended' => 'Attended',
                                'cancelled' => 'Cancelled',
                            ] as $key => $label)
                                <div class="trial-status-item">
                                    <span>{{ $label }}</span>
                                    <strong>{{ number_format($workshopParticipantStatusCounts[$key] ?? 0) }}</strong>
                                </div>
                            @endforeach

                            <div class="trial-status-item">
                                <span>Workshop Orders</span>
                                <strong>{{ number_format($orderInsight['workshop_orders_selected_period'] ?? 0) }}</strong>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="content-card h-100">
                <div class="content-card-header d-flex justify-content-between align-items-start gap-3 flex-wrap">
                    <div>
                        <h5 class="content-card-title mb-1">Upcoming Workshop Schedules</h5>
                        <p class="content-card-subtitle mb-0">Jadwal workshop aktif yang paling dekat.</p>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="sales-scope-badge sales-scope-upcoming"><i class="bi bi-calendar-event"></i> Upcoming</span>
                        <span class="badge rounded-pill bg-primary-subtle text-primary">
                            {{ number_format($upcomingWorkshopSchedules->count()) }} schedule
                        </span>
                    </div>
                </div>
                <div class="content-card-body">
                    @if($upcomingWorkshopSchedules->isNotEmpty())
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
                                                {{ number_format($schedule->registered_count ?? 0) }}
                                                @if(($schedule->quota ?? 0) > 0)
                                                    / {{ number_format($schedule->quota) }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="empty-state-box">
                            <div class="empty-state-icon"><i class="bi bi-calendar-x"></i></div>
                            <h5 class="empty-state-title">Belum ada workshop schedule mendatang</h5>
                            <p class="empty-state-text mb-0">Jadwal workshop aktif yang akan datang belum tersedia.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-section-label mb-3 mt-1">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="dashboard-section-eyebrow">Revenue & Payment</div>
                <h4 class="dashboard-section-title mb-1">Sales Revenue Performance</h4>
                <p class="dashboard-section-subtitle mb-0">
                    Perbandingan revenue terkonfirmasi, reported revenue, order aktif, serta payment yang perlu ditindaklanjuti.
                </p>
            </div>
            <span class="sales-scope-badge sales-scope-current"><i class="bi bi-layers"></i> Mixed Scope</span>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap"><i class="bi bi-cash-stack"></i></div>
                    <div>
                        <div class="stat-title">Confirmed Revenue</div>
                        <div class="stat-value stat-value-currency">{{ $formatCurrency($financeInsight['revenue_selected_period'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    <span class="sales-inline-scope">Selected Period</span>
                    Growth {{ number_format((float) ($financeInsight['revenue_period_growth_percent'] ?? 0), 1) }}% dibanding periode sebelumnya.
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap"><i class="bi bi-receipt-cutoff"></i></div>
                    <div>
                        <div class="stat-title">Potential Revenue</div>
                        <div class="stat-value stat-value-currency">{{ $formatCurrency($orderInsight['potential_revenue'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    <span class="sales-inline-scope">Current Open Orders</span>
                    Akumulasi nilai pending dan partial order.
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap"><i class="bi bi-hourglass-bottom"></i></div>
                    <div>
                        <div class="stat-title">Pending Payments</div>
                        <div class="stat-value">{{ number_format($financeInsight['pending_payment_count'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    <span class="sales-inline-scope">All Open Payments</span>
                    Nilai pending {{ $formatCurrency($financeInsight['pending_payment_total'] ?? 0) }}.
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap"><i class="bi bi-calendar-x-fill"></i></div>
                    <div>
                        <div class="stat-title">Overdue Schedules</div>
                        <div class="stat-value {{ ($financeInsight['overdue_schedule_count'] ?? 0) > 0 ? 'text-danger' : '' }}">
                            {{ number_format($financeInsight['overdue_schedule_count'] ?? 0) }}
                        </div>
                    </div>
                </div>
                <div class="stat-description">
                    <span class="sales-inline-scope">All Open Schedules</span>
                    Nilai overdue {{ $formatCurrency($financeInsight['overdue_schedule_total'] ?? 0) }}.
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-8">
            <div class="content-card h-100">
                <div class="content-card-header d-flex justify-content-between align-items-start gap-3 flex-wrap">
                    <div>
                        <h5 class="content-card-title mb-1">Revenue Trend</h5>
                        <p class="content-card-subtitle mb-0">
                            Perbandingan confirmed revenue dari payments dan reported revenue dari Sales Daily Report.
                        </p>
                    </div>

                    <div class="revenue-total-box">
                        <div class="revenue-total-label">Confirmed Revenue</div>
                        <div class="revenue-total-value">{{ $formatCurrency($revenueChart['total_confirmed_revenue'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="content-card-body">
                    <div class="chart-wrap sales-revenue-chart-wrap">
                        <canvas id="salesRevenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="content-card h-100">
                <div class="content-card-header d-flex justify-content-between align-items-start gap-3 flex-wrap">
                    <div>
                        <h5 class="content-card-title mb-1">Payment & Order Snapshot</h5>
                        <p class="content-card-subtitle mb-0">Ringkasan transaksi yang perlu dipantau sales.</p>
                    </div>
                    <span class="sales-scope-badge sales-scope-current"><i class="bi bi-layers"></i> Current / All Open</span>
                </div>
                <div class="content-card-body">
                    <div class="sales-health-list">
                        <div class="sales-health-item">
                            <div class="sales-health-item-left">
                                <div class="sales-health-icon bg-success-subtle text-success"><i class="bi bi-check-circle-fill"></i></div>
                                <div>
                                    <div class="sales-health-label">Paid Orders</div>
                                    <div class="sales-health-help">Current order snapshot</div>
                                </div>
                            </div>
                            <strong>{{ number_format($orderInsight['paid_orders'] ?? 0) }}</strong>
                        </div>
                        <div class="sales-health-item">
                            <div class="sales-health-item-left">
                                <div class="sales-health-icon bg-warning-subtle text-warning"><i class="bi bi-circle-half"></i></div>
                                <div>
                                    <div class="sales-health-label">Partial Orders</div>
                                    <div class="sales-health-help">Current open orders</div>
                                </div>
                            </div>
                            <strong>{{ number_format($orderInsight['partial_orders'] ?? 0) }}</strong>
                        </div>
                        <div class="sales-health-item">
                            <div class="sales-health-item-left">
                                <div class="sales-health-icon bg-primary-subtle text-primary"><i class="bi bi-bag-fill"></i></div>
                                <div>
                                    <div class="sales-health-label">Orders in Period</div>
                                    <div class="sales-health-help">Order baru periode ini</div>
                                </div>
                            </div>
                            <strong>{{ number_format($orderInsight['orders_selected_period'] ?? 0) }}</strong>
                        </div>
                        <div class="sales-health-item">
                            <div class="sales-health-item-left">
                                <div class="sales-health-icon bg-secondary-subtle text-secondary"><i class="bi bi-clock-history"></i></div>
                                <div>
                                    <div class="sales-health-label">Last Payment</div>
                                    <div class="sales-health-help">
                                        {{ !empty($financeInsight['last_payment_date'])
                                            ? \Carbon\Carbon::parse($financeInsight['last_payment_date'])->format('d M Y H:i')
                                            : 'Belum tersedia' }}
                                    </div>
                                </div>
                            </div>
                            <strong class="sales-small-strong">{{ $formatCurrency($financeInsight['last_payment_amount'] ?? 0) }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-section-label mb-3 mt-1">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="dashboard-section-eyebrow">Program Availability</div>
                <h4 class="dashboard-section-title mb-1">Available Programs & Seats</h4>
                <p class="dashboard-section-subtitle mb-0">
                    Ketersediaan seat pada batch aktif dan program mendatang yang masih dapat ditawarkan sales.
                </p>
            </div>
            <span class="sales-scope-badge sales-scope-current"><i class="bi bi-arrow-repeat"></i> Current Snapshot</span>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap"><i class="bi bi-grid-1x2-fill"></i></div>
                    <div>
                        <div class="stat-title">Total Capacity</div>
                        <div class="stat-value">{{ number_format($batchCapacity['total_capacity'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">Akumulasi kapasitas seluruh batch aktif.</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap"><i class="bi bi-person-check-fill"></i></div>
                    <div>
                        <div class="stat-title">Filled Seats</div>
                        <div class="stat-value">{{ number_format($batchCapacity['filled_seats'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">Seat aktif yang sudah terisi student.</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap"><i class="bi bi-person-plus-fill"></i></div>
                    <div>
                        <div class="stat-title">Remaining Seats</div>
                        <div class="stat-value">{{ number_format($batchCapacity['remaining_seats'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">Seat yang masih tersedia untuk ditawarkan.</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap"><i class="bi bi-pie-chart-fill"></i></div>
                    <div>
                        <div class="stat-title">Utilization</div>
                        <div class="stat-value">{{ number_format($batchCapacity['utilization_percent'] ?? 0) }}%</div>
                    </div>
                </div>
                <div class="stat-description">Persentase seat aktif yang sudah terisi.</div>
            </div>
        </div>
    </div>

    <div class="content-card">
        <div class="content-card-header d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h5 class="content-card-title mb-1">Upcoming Batches</h5>
                <p class="content-card-subtitle mb-0">Batch mendatang beserta kapasitas dan sisa seat yang masih dapat dijual.</p>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="sales-scope-badge sales-scope-upcoming"><i class="bi bi-calendar-event"></i> Upcoming</span>
                <span class="badge rounded-pill bg-primary-subtle text-primary">
                    {{ number_format($upcomingBatches->count()) }} batch
                </span>
            </div>
        </div>

        <div class="content-card-body">
            @if($upcomingBatches->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-modern align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Program / Batch</th>
                                <th>Start Date</th>
                                <th class="text-center">Capacity</th>
                                <th class="text-center">Filled</th>
                                <th class="text-center">Remaining</th>
                                <th width="220">Utilization</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($upcomingBatches as $batch)
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $batch->name ?? '-' }}</div>
                                        <div class="small text-muted">{{ $batch->program_name ?? '-' }}</div>
                                    </td>
                                    <td>{{ !empty($batch->start_date) ? \Carbon\Carbon::parse($batch->start_date)->format('d M Y') : '-' }}</td>
                                    <td class="text-center">{{ number_format($batch->capacity ?? 0) }}</td>
                                    <td class="text-center">{{ number_format($batch->filled_seats ?? 0) }}</td>
                                    <td class="text-center">
                                        <span class="badge rounded-pill {{ ($batch->remaining_seats ?? 0) > 0 ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                            {{ number_format($batch->remaining_seats ?? 0) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="progress progress-modern">
                                            <div
                                                class="progress-bar"
                                                role="progressbar"
                                                style="width: {{ $batch->utilization_percent ?? 0 }}%;"
                                                aria-valuenow="{{ $batch->utilization_percent ?? 0 }}"
                                                aria-valuemin="0"
                                                aria-valuemax="100"
                                            >
                                                {{ number_format($batch->utilization_percent ?? 0) }}%
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
                    <div class="empty-state-icon"><i class="bi bi-calendar-x"></i></div>
                    <h5 class="empty-state-title">Belum ada upcoming batch</h5>
                    <p class="empty-state-text mb-0">Batch mendatang yang masih aktif belum tersedia.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<x-ai-insight-widget
    title="AI Sales Recommendations"
    :insight="$salesSummary"
    :summary="$salesDashboardAiSummaryText"
/>
@endsection

@push('styles')
<style>
    .sales-dashboard-page .min-w-0 {
        min-width: 0;
    }

    .sales-scope-badge {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .48rem .7rem;
        border-radius: 999px;
        border: 1px solid rgba(91, 62, 142, .12);
        background: rgba(91, 62, 142, .07);
        color: #5B3E8E;
        font-size: .72rem;
        font-weight: 800;
        line-height: 1;
        white-space: nowrap;
    }

    .sales-scope-live {
        border-color: rgba(220, 38, 38, .13);
        background: rgba(220, 38, 38, .07);
        color: #b91c1c;
    }

    .sales-scope-current {
        border-color: rgba(2, 132, 199, .13);
        background: rgba(2, 132, 199, .07);
        color: #0369a1;
    }

    .sales-scope-upcoming {
        border-color: rgba(22, 163, 74, .13);
        background: rgba(22, 163, 74, .07);
        color: #15803d;
    }

    .sales-action-center > [class*="col-"] {
        display: flex;
    }

    .sales-action-card {
        width: 100%;
        border: 1px solid rgba(15, 23, 42, .08);
        border-radius: 18px;
        background: #ffffff;
        padding: 1rem;
        box-shadow: 0 12px 28px rgba(15, 23, 42, .04);
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }

    .sales-action-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 18px 36px rgba(15, 23, 42, .07);
    }

    .sales-action-card.is-warning {
        border-color: rgba(245, 158, 11, .26);
        background: #ffffff;
    }

    .sales-action-card.is-critical {
        border-color: rgba(220, 38, 38, .24);
        background: #ffffff;
    }

    .sales-action-card.is-priority {
        border-color: rgba(91, 62, 142, .22);
        background: #ffffff;
    }

    .sales-action-card.is-clear {
        border-color: rgba(22, 163, 74, .15);
        background: #ffffff;
    }

    .sales-action-card-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        margin-bottom: .9rem;
    }

    .sales-action-icon {
        width: 38px;
        height: 38px;
        border-radius: 13px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(91, 62, 142, .10);
        color: #5B3E8E;
        font-size: 1rem;
    }

    .sales-action-scope {
        color: #64748b;
        font-size: .65rem;
        font-weight: 800;
        letter-spacing: .03em;
        text-transform: uppercase;
    }

    .sales-action-label {
        color: #64748b;
        font-size: .78rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .sales-action-value {
        color: #0f172a;
        font-size: 2rem;
        font-weight: 950;
        line-height: 1.05;
        margin-top: .25rem;
    }

    .sales-action-help {
        color: #64748b;
        font-size: .78rem;
        line-height: 1.45;
        margin-top: .65rem;
    }

    .sales-kpi-comparison {
        margin-top: auto;
        padding-top: .8rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        color: #94a3b8;
        font-size: .69rem;
        font-weight: 700;
    }

    .sales-kpi-comparison strong {
        color: #475569;
        font-size: .72rem;
        text-align: right;
    }


    .sales-inline-scope {
        display: inline-flex;
        align-items: center;
        width: fit-content;
        margin: 0 .35rem .35rem 0;
        padding: .24rem .45rem;
        border-radius: 999px;
        background: rgba(91, 62, 142, .07);
        color: #5B3E8E;
        font-size: .62rem;
        font-weight: 850;
        letter-spacing: .025em;
        text-transform: uppercase;
    }

    .sales-add-student-btn {
        min-height: 44px;
        padding-inline: 1rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .45rem;
        white-space: nowrap;
    }

    .sales-filter-card .form-control {
        min-height: 44px;
        border-radius: 12px;
        border-color: rgba(15, 23, 42, .1);
    }

    .sales-filter-card .form-control:focus {
        border-color: rgba(91, 62, 142, .42);
        box-shadow: 0 0 0 .2rem rgba(91, 62, 142, .10);
    }

    .sales-filter-reset-btn {
        min-height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    #salesDashboardFilterForm.is-loading {
        opacity: .68;
        pointer-events: none;
    }

    #salesDashboardFilterForm.is-loading .form-control {
        cursor: progress;
    }


    .sales-kpi-scroll-shell {
        position: relative;
        border-radius: 22px;
    }


    .sales-kpi-scroll {
        display: flex;
        flex-wrap: nowrap;
        gap: 1rem;
        width: 100%;
        overflow-x: auto;
        overflow-y: hidden;
        padding: .15rem .15rem .9rem;
        scroll-snap-type: x mandatory;
        scroll-padding-inline: .15rem;
        scrollbar-width: thin;
        scrollbar-color: rgba(91, 62, 142, .62) rgba(91, 62, 142, .08);
        -webkit-overflow-scrolling: touch;
    }

    .sales-kpi-scroll:focus-visible {
        outline: 3px solid rgba(91, 62, 142, .18);
        outline-offset: 4px;
        border-radius: 18px;
    }

    .sales-kpi-scroll::-webkit-scrollbar {
        height: 10px;
    }

    .sales-kpi-scroll::-webkit-scrollbar-track {
        background: rgba(91, 62, 142, .07);
        border-radius: 999px;
        margin-inline: .2rem;
    }

    .sales-kpi-scroll::-webkit-scrollbar-thumb {
        background: linear-gradient(90deg, rgba(91, 62, 142, .72), rgba(91, 62, 142, .42));
        border: 2px solid rgba(248, 250, 252, .95);
        border-radius: 999px;
    }

    .sales-kpi-scroll::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(90deg, rgba(91, 62, 142, .92), rgba(91, 62, 142, .62));
    }

    .sales-kpi-scroll-item {
        flex: 0 0 calc((100% - 2rem) / 3);
        min-width: 290px;
        scroll-snap-align: start;
        scroll-snap-stop: always;
    }

    .sales-kpi-card {
        min-height: 196px;
        display: flex;
        flex-direction: column;
    }

    .sales-change-badge {
        font-size: .66rem;
        font-weight: 800;
        padding: .35rem .5rem;
    }

    .sales-currency-value {
        font-size: 1.45rem;
        overflow-wrap: anywhere;
    }

    .sales-main-chart-wrap,
    .sales-revenue-chart-wrap {
        height: 360px;
    }

    .sales-health-list {
        display: grid;
        gap: .8rem;
    }

    .sales-health-item {
        border: 1px solid rgba(15, 23, 42, .08);
        border-radius: 16px;
        background: #ffffff;
        padding: .9rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        box-shadow: 0 10px 24px rgba(15, 23, 42, .035);
    }

    .sales-health-item-left {
        display: flex;
        align-items: center;
        gap: .75rem;
        min-width: 0;
    }

    .sales-health-icon {
        width: 38px;
        height: 38px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
    }

    .sales-health-label {
        color: #0f172a;
        font-size: .88rem;
        font-weight: 800;
    }

    .sales-health-help {
        color: #64748b;
        font-size: .74rem;
        margin-top: .1rem;
    }

    .sales-health-item > strong {
        color: #111827;
        font-size: 1.18rem;
        font-weight: 900;
        white-space: nowrap;
    }

    .sales-health-item > strong.sales-small-strong {
        font-size: .86rem;
    }

    .sales-report-freshness {
        border-radius: 16px;
        background: rgba(91, 62, 142, .06);
        border: 1px solid rgba(91, 62, 142, .1);
        padding: .9rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
    }

    .sales-report-freshness div {
        display: grid;
        gap: .15rem;
    }

    .sales-report-freshness span:not(.badge) {
        color: #64748b;
        font-size: .74rem;
        font-weight: 700;
    }

    .sales-report-freshness strong {
        color: #111827;
        font-size: .9rem;
    }

    .kommo-progress-row-card {
        background: linear-gradient(135deg, rgba(91, 62, 142, .08), rgba(255, 190, 4, .08));
        border: 1px solid rgba(91, 62, 142, .10);
        border-radius: 20px;
        padding: 1.25rem;
    }

    .sales-kommo-summary {
        max-width: 580px;
        color: #64748b;
        font-size: .85rem;
        line-height: 1.55;
    }

    .kommo-progress-metric {
        background: #ffffff;
        border: 1px solid rgba(15, 23, 42, .08);
        border-radius: 16px;
        padding: 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        box-shadow: 0 10px 24px rgba(15, 23, 42, .04);
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
    }

    .kommo-progress-metric span {
        color: #64748b;
        font-size: .82rem;
        font-weight: 700;
    }

    .kommo-progress-metric strong {
        color: #111827;
        font-size: 1.15rem;
        font-weight: 900;
        white-space: nowrap;
    }

    .trial-status-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .75rem;
    }

    .trial-status-item {
        border: 1px solid rgba(15, 23, 42, .08);
        border-radius: 14px;
        background: #ffffff;
        padding: .8rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
    }

    .trial-status-item span {
        color: #64748b;
        font-size: .78rem;
        font-weight: 700;
    }

    .trial-status-item strong {
        color: #111827;
        font-size: 1rem;
        font-weight: 900;
    }

    .stat-value-currency {
        font-size: 1.42rem;
        overflow-wrap: anywhere;
    }

    @media (max-width: 1199.98px) {
        .sales-kpi-scroll-item {
            flex-basis: calc((100% - 1rem) / 2);
            min-width: 280px;
        }
    }

    @media (max-width: 767.98px) {
        .sales-scope-badge {
            white-space: normal;
            line-height: 1.25;
        }
        .sales-kpi-scroll-item {
            flex-basis: min(84vw, 340px);
            min-width: 270px;
        }

    }

    @media (max-width: 575.98px) {
        .trial-status-grid {
            grid-template-columns: 1fr;
        }

        .sales-report-freshness,
        .sales-health-item {
            align-items: flex-start;
        }

        .sales-report-freshness {
            flex-direction: column;
        }

        .sales-main-chart-wrap,
        .sales-revenue-chart-wrap {
            height: 310px;
        }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const filterForm = document.getElementById('salesDashboardFilterForm');
    const dateFromInput = document.getElementById('date_from');
    const dateToInput = document.getElementById('date_to');
    let filterSubmitTimer = null;

    const submitDateFilter = function () {
        if (!filterForm || !dateFromInput?.value || !dateToInput?.value) {
            return;
        }

        window.clearTimeout(filterSubmitTimer);

        filterSubmitTimer = window.setTimeout(function () {
            filterForm.classList.add('is-loading');
            filterForm.submit();
        }, 250);
    };

    [dateFromInput, dateToInput].forEach(function (input) {
        input?.addEventListener('change', submitDateFilter);
    });

    const salesPerformanceCanvas = document.getElementById('salesPerformanceChart');
    const salesRevenueCanvas = document.getElementById('salesRevenueChart');

    const salesPerformance = @json($salesPerformanceChart);
    const revenueChart = @json($revenueChart);

    if (salesPerformanceCanvas) {
        new Chart(salesPerformanceCanvas, {
            type: 'line',
            data: {
                labels: salesPerformance.labels || [],
                datasets: [
                    {
                        label: 'Total Leads',
                        data: salesPerformance.datasets?.total_leads || [],
                        borderColor: '#5B3E8E',
                        backgroundColor: 'rgba(91, 62, 142, .10)',
                        tension: .35,
                        borderWidth: 2,
                        pointRadius: 3,
                        fill: false,
                    },
                    {
                        label: 'Interacted',
                        data: salesPerformance.datasets?.interacted || [],
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, .10)',
                        tension: .35,
                        borderWidth: 2,
                        pointRadius: 3,
                        fill: false,
                    },
                    {
                        label: 'Consultation',
                        data: salesPerformance.datasets?.consultation || [],
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245, 158, 11, .10)',
                        tension: .35,
                        borderWidth: 2,
                        pointRadius: 3,
                        fill: false,
                    },
                    {
                        label: 'Hot Leads',
                        data: salesPerformance.datasets?.hot_leads || [],
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, .10)',
                        tension: .35,
                        borderWidth: 2,
                        pointRadius: 3,
                        fill: false,
                    },
                    {
                        label: 'Closed Deal',
                        data: salesPerformance.datasets?.closed_deal || [],
                        borderColor: '#16a34a',
                        backgroundColor: 'rgba(22, 163, 74, .10)',
                        tension: .35,
                        borderWidth: 2,
                        pointRadius: 3,
                        fill: false,
                    },
                ],
            },
            options: {
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            boxWidth: 10,
                            color: '#64748b',
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#64748b' },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            color: '#64748b',
                        },
                        grid: { color: 'rgba(100, 116, 139, .09)' },
                    },
                },
            },
        });
    }

    if (salesRevenueCanvas) {
        new Chart(salesRevenueCanvas, {
            type: 'bar',
            data: {
                labels: revenueChart.labels || [],
                datasets: [
                    {
                        label: 'Confirmed Revenue',
                        data: revenueChart.datasets?.confirmed_revenue || [],
                        backgroundColor: 'rgba(91, 62, 142, .82)',
                        borderColor: '#5B3E8E',
                        borderWidth: 1,
                        borderRadius: 8,
                        maxBarThickness: 36,
                    },
                    {
                        label: 'Reported Revenue',
                        data: revenueChart.datasets?.reported_revenue || [],
                        backgroundColor: 'rgba(255, 190, 4, .68)',
                        borderColor: '#FFBE04',
                        borderWidth: 1,
                        borderRadius: 8,
                        maxBarThickness: 36,
                    },
                ],
            },
            options: {
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            boxWidth: 10,
                            color: '#64748b',
                        },
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return ' ' + context.dataset.label + ': Rp ' + Number(context.raw || 0).toLocaleString('id-ID');
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#64748b' },
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#64748b',
                            callback: function (value) {
                                return 'Rp ' + Number(value).toLocaleString('id-ID');
                            },
                        },
                        grid: { color: 'rgba(100, 116, 139, .09)' },
                    },
                },
            },
        });
    }
});
</script>
@endpush
