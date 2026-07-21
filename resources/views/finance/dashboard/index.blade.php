@extends('layouts.app-dashboard')

@section('title', 'Finance Dashboard')

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

    $financePerformance = $financePerformance ?? [];
    $financeActionCenter = $financeActionCenter ?? [];
    $receivables = $receivables ?? [];
    $scheduleReceivables = $scheduleReceivables ?? [];
    $orderSnapshot = $orderSnapshot ?? ['statuses' => []];
    $orderPeriod = $orderPeriod ?? ['by_type' => []];
    $paymentStatusOverview = $paymentStatusOverview ?? [];
    $revenueChart = $revenueChart ?? ['labels' => [], 'datasets' => [], 'summary' => []];
    $revenueByOrderType = collect($revenueByOrderType ?? []);
    $paymentMethodBreakdown = collect($paymentMethodBreakdown ?? []);
    $gatewayBreakdown = collect($gatewayBreakdown ?? []);
    $overdueSchedules = collect($overdueSchedules ?? []);
    $recentPayments = collect($recentPayments ?? []);
    $largestReceivables = collect($largestReceivables ?? []);
    $financeSummary = $financeSummary ?? [];
    $financeDashboardAiSummaryText = (string) ($financeDashboardAiSummaryText ?? ($financeSummary['summary_text'] ?? ''));

    $formatCurrency = fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
    $formatNumber = fn ($value) => number_format((float) $value, 0, ',', '.');

    $confirmedRevenue = (float) ($financePerformance['confirmed_revenue'] ?? 0);
    $previousRevenue = (float) ($financePerformance['previous_confirmed_revenue'] ?? 0);
    $revenueGrowth = $financePerformance['revenue_growth_percent'] ?? null;
    $revenueIsNew = (bool) ($financePerformance['revenue_growth_is_new'] ?? false);

    $growthBadge = match (true) {
        $revenueIsNew => [
            'class' => 'bg-success-subtle text-success',
            'icon' => 'bi-stars',
            'text' => 'New',
        ],
        $revenueGrowth === null => [
            'class' => 'bg-light text-muted',
            'icon' => 'bi-dash',
            'text' => 'No baseline',
        ],
        (float) $revenueGrowth > 0 => [
            'class' => 'bg-success-subtle text-success',
            'icon' => 'bi-arrow-up-right',
            'text' => '+' . number_format((float) $revenueGrowth, 1) . '%',
        ],
        (float) $revenueGrowth < 0 => [
            'class' => 'bg-danger-subtle text-danger',
            'icon' => 'bi-arrow-down-right',
            'text' => number_format((float) $revenueGrowth, 1) . '%',
        ],
        default => [
            'class' => 'bg-light text-muted',
            'icon' => 'bi-dash',
            'text' => 'No change',
        ],
    };

    $financeMetricCards = [
        [
            'label' => 'Confirmed Revenue',
            'value' => $formatCurrency($confirmedRevenue),
            'icon' => 'bi-cash-stack',
            'description' => 'Payment berstatus paid dalam periode terpilih.',
            'badge' => $growthBadge,
            'comparison' => $formatCurrency($previousRevenue) . ' → ' . $formatCurrency($confirmedRevenue),
        ],
        [
            'label' => 'Outstanding Receivables',
            'value' => $formatCurrency($financePerformance['outstanding_receivables'] ?? 0),
            'icon' => 'bi-receipt-cutoff',
            'description' => number_format((int) ($financePerformance['outstanding_order_count'] ?? 0)) . ' order masih memiliki saldo yang belum dibayar.',
        ],
        [
            'label' => 'Paid Transactions',
            'value' => number_format((int) ($financePerformance['paid_transactions'] ?? 0)),
            'icon' => 'bi-credit-card-2-front-fill',
            'description' => 'Jumlah transaksi payment berstatus paid pada periode terpilih.',
        ],
        [
            'label' => 'Fully Paid Orders',
            'value' => number_format((int) ($financePerformance['fully_paid_orders_snapshot'] ?? 0)),
            'icon' => 'bi-patch-check-fill',
            'description' => 'Total order yang saat ini sudah berstatus paid.',
        ],
        [
            'label' => 'New Order Value',
            'value' => $formatCurrency($financePerformance['new_order_value'] ?? 0),
            'icon' => 'bi-bag-check-fill',
            'description' => number_format((int) ($financePerformance['new_order_count'] ?? 0)) . ' order baru dibuat dalam periode terpilih.',
        ],
        [
            'label' => 'Average Payment Value',
            'value' => $formatCurrency($financePerformance['average_payment_value'] ?? 0),
            'icon' => 'bi-calculator-fill',
            'description' => 'Rata-rata nominal dari setiap transaksi payment berstatus paid.',
        ],
    ];

    $actionCards = [
        [
            'label' => 'Overdue Schedules',
            'count' => (int) data_get($financeActionCenter, 'overdue_schedules.count', 0),
            'total' => (float) data_get($financeActionCenter, 'overdue_schedules.total', 0),
            'icon' => 'bi-exclamation-triangle-fill',
            'icon_class' => 'bg-danger-subtle text-danger',
            'description' => 'Jadwal pembayaran melewati jatuh tempo dan masih memiliki outstanding.',
        ],
        [
            'label' => 'Pending Payments',
            'count' => (int) data_get($financeActionCenter, 'pending_payments.count', 0),
            'total' => (float) data_get($financeActionCenter, 'pending_payments.total', 0),
            'icon' => 'bi-hourglass-split',
            'icon_class' => 'bg-warning-subtle text-warning',
            'description' => 'Payment link atau record pembayaran masih menunggu penyelesaian.',
        ],
        [
            'label' => 'Expired Payments',
            'count' => (int) data_get($financeActionCenter, 'expired_payments.count', 0),
            'total' => (float) data_get($financeActionCenter, 'expired_payments.total', 0),
            'icon' => 'bi-clock-history',
            'icon_class' => 'bg-secondary-subtle text-secondary',
            'description' => 'Payment sudah kedaluwarsa dan mungkin perlu dibuat ulang.',
        ],
        [
            'label' => 'Partial Orders',
            'count' => (int) data_get($financeActionCenter, 'partial_orders.count', 0),
            'total' => (float) data_get($financeActionCenter, 'partial_orders.total', 0),
            'icon' => 'bi-pie-chart-fill',
            'icon_class' => 'bg-primary-subtle text-primary',
            'description' => 'Order sudah memiliki pembayaran tetapi belum lunas sepenuhnya.',
        ],
    ];

    $paymentStatusMeta = [
        'paid' => ['label' => 'Paid', 'icon' => 'bi-check2-circle', 'class' => 'bg-success-subtle text-success'],
        'pending' => ['label' => 'Pending', 'icon' => 'bi-hourglass-split', 'class' => 'bg-warning-subtle text-warning'],
        'failed' => ['label' => 'Failed', 'icon' => 'bi-x-octagon-fill', 'class' => 'bg-danger-subtle text-danger'],
        'expired' => ['label' => 'Expired', 'icon' => 'bi-clock-history', 'class' => 'bg-secondary-subtle text-secondary'],
        'cancelled' => ['label' => 'Cancelled', 'icon' => 'bi-slash-circle', 'class' => 'bg-light text-muted'],
    ];

    $orderStatusMeta = [
        'pending' => ['label' => 'Pending', 'class' => 'bg-warning-subtle text-warning'],
        'partial' => ['label' => 'Partial', 'class' => 'bg-primary-subtle text-primary'],
        'paid' => ['label' => 'Paid', 'class' => 'bg-success-subtle text-success'],
        'cancelled' => ['label' => 'Cancelled', 'class' => 'bg-secondary-subtle text-secondary'],
    ];

    $currentUser = auth()->user();
    $canViewPayments = $currentUser
        && method_exists($currentUser, 'canAccess')
        && $currentUser->canAccess('payments.view')
        && Route::has('payments.index');
@endphp

<div class="container-fluid px-4 py-4 finance-dashboard-page">
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Finance Dashboard</div>
                <h1 class="page-title mb-2">Revenue, Collection & Receivables</h1>
                <p class="page-subtitle mb-0">
                    Pantau confirmed revenue, outstanding receivables, status pembayaran, collection overdue, dan kesehatan order dalam satu dashboard.
                </p>
            </div>

            @if($canViewPayments)
                <div class="page-header-actions d-flex gap-2 flex-wrap">
                    <a href="{{ route('payments.index') }}" class="btn btn-light btn-modern">
                        <i class="bi bi-credit-card-fill"></i>
                        Manage Payments
                    </a>
                </div>
            @endif
        </div>
    </div>

    <div class="content-card mb-4 finance-filter-card">
        <div class="content-card-body">
            <form
                id="financeDashboardFilterForm"
                method="GET"
                action="{{ route('finance.dashboard') }}"
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
                    <a href="{{ route('finance.dashboard') }}" class="btn btn-light btn-modern w-100">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="dashboard-section-label mb-3">
        <div class="dashboard-section-eyebrow">Collection Priority</div>
        <h4 class="dashboard-section-title mb-1">Finance Action Center</h4>
        <p class="dashboard-section-subtitle mb-0">
            Item finansial yang membutuhkan tindak lanjut dari tim Finance saat ini.
        </p>
    </div>

    <div class="row g-3 mb-4">
        @foreach($actionCards as $card)
            <div class="col-xl-3 col-md-6">
                <div class="finance-action-card h-100">
                    <div class="finance-action-card-top">
                        <div class="finance-action-icon {{ $card['icon_class'] }}">
                            <i class="bi {{ $card['icon'] }}"></i>
                        </div>
                        <div>
                            <div class="finance-action-label">{{ $card['label'] }}</div>
                            <div class="finance-action-count">{{ number_format($card['count']) }}</div>
                        </div>
                    </div>
                    <div class="finance-action-total">{{ $formatCurrency($card['total']) }}</div>
                    <div class="finance-action-description">{{ $card['description'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="dashboard-section-label mb-3 mt-1">
        <div class="dashboard-section-eyebrow">Finance Performance</div>
        <h4 class="dashboard-section-title mb-1">Revenue & Financial Position</h4>
        <p class="dashboard-section-subtitle mb-0">
            Ringkasan performa pembayaran dan posisi piutang untuk periode {{ $period['label'] ?? '-' }}.
        </p>
    </div>

    <div class="finance-kpi-scroll mb-4" aria-label="Finance performance metrics">
        @foreach($financeMetricCards as $card)
            <div class="finance-kpi-item">
                <div class="funnel-card h-100 finance-kpi-card">
                    <div class="funnel-card-top">
                        <div class="funnel-icon-wrap">
                            <i class="bi {{ $card['icon'] }}"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="funnel-title">{{ $card['label'] }}</div>
                            <div class="funnel-value finance-kpi-value">{{ $card['value'] }}</div>
                        </div>
                    </div>

                    <div class="funnel-description">
                        {{ $card['description'] }}
                    </div>

                    @if(!empty($card['badge']))
                        <div class="finance-kpi-comparison mt-auto pt-3">
                            <span class="badge rounded-pill {{ $card['badge']['class'] }}">
                                <i class="bi {{ $card['badge']['icon'] }} me-1"></i>
                                {{ $card['badge']['text'] }}
                            </span>
                            <span>{{ $card['comparison'] }}</span>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="content-card mb-4">
        <div class="content-card-header d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h5 class="content-card-title mb-1">Revenue & Order Value Trend</h5>
                <p class="content-card-subtitle mb-0">
                    Perbandingan confirmed revenue dan nilai order baru pada periode terpilih.
                </p>
            </div>

            <div class="revenue-total-box">
                <div class="revenue-total-label">Confirmed Revenue</div>
                <div class="revenue-total-value">{{ $formatCurrency(data_get($revenueChart, 'summary.confirmed_revenue', 0)) }}</div>
            </div>
        </div>

        <div class="content-card-body">
            <div class="finance-chart-wrap">
                <canvas id="financeRevenueChart"></canvas>
            </div>
        </div>
    </div>

    <div class="dashboard-section-label mb-3 mt-1">
        <div class="dashboard-section-eyebrow">Collection & Receivables</div>
        <h4 class="dashboard-section-title mb-1">Receivable Position</h4>
        <p class="dashboard-section-subtitle mb-0">
            Outstanding order dan jadwal pembayaran yang perlu dipantau untuk menjaga arus kas.
        </p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-5">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Collection Timeline</h5>
                        <p class="content-card-subtitle mb-0">Jadwal tagihan berdasarkan kedekatan jatuh tempo.</p>
                    </div>
                </div>
                <div class="content-card-body">
                    <div class="finance-collection-grid">
                        <div class="finance-collection-item is-danger">
                            <span>Overdue</span>
                            <strong>{{ number_format((int) ($scheduleReceivables['overdue_count'] ?? 0)) }}</strong>
                            <small>{{ $formatCurrency($scheduleReceivables['overdue_total'] ?? 0) }}</small>
                        </div>
                        <div class="finance-collection-item is-warning">
                            <span>Due Today</span>
                            <strong>{{ number_format((int) ($scheduleReceivables['due_today_count'] ?? 0)) }}</strong>
                            <small>{{ $formatCurrency($scheduleReceivables['due_today_total'] ?? 0) }}</small>
                        </div>
                        <div class="finance-collection-item is-primary">
                            <span>Next 7 Days</span>
                            <strong>{{ number_format((int) ($scheduleReceivables['due_next_7_days_count'] ?? 0)) }}</strong>
                            <small>{{ $formatCurrency($scheduleReceivables['due_next_7_days_total'] ?? 0) }}</small>
                        </div>
                        <div class="finance-collection-item is-muted">
                            <span>Next 30 Days</span>
                            <strong>{{ number_format((int) ($scheduleReceivables['due_next_30_days_count'] ?? 0)) }}</strong>
                            <small>{{ $formatCurrency($scheduleReceivables['due_next_30_days_total'] ?? 0) }}</small>
                        </div>
                    </div>

                    <div class="finance-receivable-summary mt-3">
                        <div>
                            <span>Total Outstanding</span>
                            <strong>{{ $formatCurrency($receivables['outstanding_total'] ?? 0) }}</strong>
                        </div>
                        <div>
                            <span>Outstanding Orders</span>
                            <strong>{{ number_format((int) ($receivables['outstanding_order_count'] ?? 0)) }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="content-card h-100">
                <div class="content-card-header d-flex justify-content-between align-items-start gap-3 flex-wrap">
                    <div>
                        <h5 class="content-card-title mb-1">Largest Receivables</h5>
                        <p class="content-card-subtitle mb-0">Order dengan outstanding terbesar yang layak diprioritaskan.</p>
                    </div>
                    <span class="badge rounded-pill bg-primary-subtle text-primary">
                        {{ number_format($largestReceivables->count()) }} order
                    </span>
                </div>
                <div class="content-card-body">
                    @if($largestReceivables->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-modern align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Student / Order</th>
                                        <th>Status</th>
                                        <th class="text-end">Order Value</th>
                                        <th class="text-end">Paid</th>
                                        <th class="text-end">Outstanding</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($largestReceivables as $item)
                                        @php
                                            $orderStatus = (string) ($item['order_status'] ?? 'pending');
                                            $statusMeta = $orderStatusMeta[$orderStatus] ?? ['label' => ucfirst($orderStatus), 'class' => 'bg-light text-muted'];
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="fw-semibold text-dark">
                                                    {{ $item['student_name'] ?: ('Student #' . ($item['student_id'] ?? '-')) }}
                                                </div>
                                                <div class="small text-muted">
                                                    Order #{{ $item['order_id'] }} · {{ ucfirst((string) ($item['order_type'] ?? 'unknown')) }}
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge rounded-pill {{ $statusMeta['class'] }}">{{ $statusMeta['label'] }}</span>
                                            </td>
                                            <td class="text-end">{{ $formatCurrency($item['final_price'] ?? 0) }}</td>
                                            <td class="text-end text-success">{{ $formatCurrency($item['confirmed_paid_amount'] ?? 0) }}</td>
                                            <td class="text-end fw-bold text-danger">{{ $formatCurrency($item['outstanding_amount'] ?? 0) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="empty-state-box my-0">
                            <div class="empty-state-icon"><i class="bi bi-check2-circle"></i></div>
                            <h5 class="empty-state-title">Tidak ada outstanding receivable</h5>
                            <p class="empty-state-text mb-0">Semua order aktif sudah tidak memiliki saldo outstanding.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-section-label mb-3 mt-1">
        <div class="dashboard-section-eyebrow">Payment Operations</div>
        <h4 class="dashboard-section-title mb-1">Payment Status & Collection Queue</h4>
        <p class="dashboard-section-subtitle mb-0">
            Status transaksi pembayaran pada periode terpilih dan daftar collection overdue saat ini.
        </p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-4">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Payment Status Overview</h5>
                        <p class="content-card-subtitle mb-0">Jumlah dan nominal payment per status.</p>
                    </div>
                </div>
                <div class="content-card-body">
                    <div class="finance-payment-status-list">
                        @foreach($paymentStatusMeta as $statusKey => $meta)
                            @php
                                $statusData = $paymentStatusOverview[$statusKey] ?? ['count' => 0, 'total' => 0];
                            @endphp
                            <div class="finance-payment-status-item">
                                <div class="d-flex align-items-center gap-3 min-w-0">
                                    <div class="finance-payment-status-icon {{ $meta['class'] }}">
                                        <i class="bi {{ $meta['icon'] }}"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold text-dark">{{ $meta['label'] }}</div>
                                        <div class="small text-muted">{{ $formatCurrency($statusData['total'] ?? 0) }}</div>
                                    </div>
                                </div>
                                <strong>{{ number_format((int) ($statusData['count'] ?? 0)) }}</strong>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="content-card h-100">
                <div class="content-card-header d-flex justify-content-between align-items-start gap-3 flex-wrap">
                    <div>
                        <h5 class="content-card-title mb-1">Overdue Payment Schedules</h5>
                        <p class="content-card-subtitle mb-0">Jadwal pembayaran yang sudah melewati jatuh tempo.</p>
                    </div>
                    <span class="badge rounded-pill bg-danger-subtle text-danger">
                        {{ number_format($overdueSchedules->count()) }} schedule
                    </span>
                </div>
                <div class="content-card-body">
                    @if($overdueSchedules->isNotEmpty())
                        <div class="table-responsive finance-table-scroll">
                            <table class="table table-modern align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Student / Schedule</th>
                                        <th>Due Date</th>
                                        <th class="text-center">Overdue</th>
                                        <th class="text-end">Outstanding</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($overdueSchedules as $schedule)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold text-dark">
                                                    {{ $schedule['student_name'] ?: ('Student #' . ($schedule['student_id'] ?? '-')) }}
                                                </div>
                                                <div class="small text-muted">
                                                    {{ $schedule['title'] ?: ('Schedule #' . $schedule['id']) }} · Order #{{ $schedule['order_id'] }}
                                                </div>
                                            </td>
                                            <td>{{ $schedule['due_date_label'] ?? '-' }}</td>
                                            <td class="text-center">
                                                <span class="badge rounded-pill bg-danger-subtle text-danger">
                                                    {{ number_format((int) ($schedule['days_overdue'] ?? 0)) }} days
                                                </span>
                                            </td>
                                            <td class="text-end fw-bold text-danger">
                                                {{ $formatCurrency($schedule['outstanding_amount'] ?? 0) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="empty-state-box my-0">
                            <div class="empty-state-icon"><i class="bi bi-calendar-check"></i></div>
                            <h5 class="empty-state-title">Tidak ada schedule overdue</h5>
                            <p class="empty-state-text mb-0">Belum ada jadwal pembayaran aktif yang melewati jatuh tempo.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-section-label mb-3 mt-1">
        <div class="dashboard-section-eyebrow">Payment Analysis</div>
        <h4 class="dashboard-section-title mb-1">Revenue Sources & Payment Channels</h4>
        <p class="dashboard-section-subtitle mb-0">
            Komposisi revenue berdasarkan jenis order, metode pembayaran, dan gateway.
        </p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-4">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Revenue by Order Type</h5>
                        <p class="content-card-subtitle mb-0">Kontribusi confirmed revenue berdasarkan tipe order.</p>
                    </div>
                </div>
                <div class="content-card-body">
                    @if($revenueByOrderType->isNotEmpty())
                        <div class="finance-breakdown-list">
                            @foreach($revenueByOrderType as $item)
                                <div class="finance-breakdown-item">
                                    <div>
                                        <div class="fw-semibold text-dark">{{ ucfirst((string) ($item['order_type'] ?? 'unknown')) }}</div>
                                        <div class="small text-muted">
                                            {{ number_format((int) ($item['payment_count'] ?? 0)) }} payments · {{ number_format((int) ($item['order_count'] ?? 0)) }} orders
                                        </div>
                                    </div>
                                    <strong>{{ $formatCurrency($item['revenue'] ?? 0) }}</strong>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="empty-state-box compact-empty-state my-0">
                            <div class="empty-state-icon"><i class="bi bi-pie-chart"></i></div>
                            <h5 class="empty-state-title">Belum ada revenue breakdown</h5>
                            <p class="empty-state-text mb-0">Data payment paid berdasarkan tipe order belum tersedia.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Payment Methods</h5>
                        <p class="content-card-subtitle mb-0">Metode pembayaran yang menghasilkan confirmed revenue.</p>
                    </div>
                </div>
                <div class="content-card-body">
                    @if($paymentMethodBreakdown->isNotEmpty())
                        <div class="finance-breakdown-list">
                            @foreach($paymentMethodBreakdown as $item)
                                <div class="finance-breakdown-item">
                                    <div>
                                        <div class="fw-semibold text-dark">{{ $item['payment_method'] ?? 'Unknown' }}</div>
                                        <div class="small text-muted">{{ number_format((int) ($item['count'] ?? 0)) }} transactions</div>
                                    </div>
                                    <strong>{{ $formatCurrency($item['total'] ?? 0) }}</strong>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="empty-state-box compact-empty-state my-0">
                            <div class="empty-state-icon"><i class="bi bi-credit-card"></i></div>
                            <h5 class="empty-state-title">Belum ada payment method</h5>
                            <p class="empty-state-text mb-0">Metode pembayaran belum tercatat pada payment paid periode ini.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Gateway Performance</h5>
                        <p class="content-card-subtitle mb-0">Success rate transaksi berdasarkan gateway provider.</p>
                    </div>
                </div>
                <div class="content-card-body">
                    @if($gatewayBreakdown->isNotEmpty())
                        <div class="finance-gateway-list">
                            @foreach($gatewayBreakdown as $item)
                                @php
                                    $successRate = (float) ($item['success_rate'] ?? 0);
                                    $rateClass = $successRate >= 80 ? 'bg-success' : ($successRate >= 50 ? 'bg-warning' : 'bg-danger');
                                @endphp
                                <div class="finance-gateway-item">
                                    <div class="d-flex justify-content-between gap-3 mb-2">
                                        <div>
                                            <div class="fw-semibold text-dark">{{ $item['gateway_provider'] ?? 'Manual / Unknown' }}</div>
                                            <div class="small text-muted">
                                                {{ number_format((int) ($item['paid_count'] ?? 0)) }} paid dari {{ number_format((int) ($item['total_count'] ?? 0)) }} transaksi
                                            </div>
                                        </div>
                                        <strong>{{ number_format($successRate, 1) }}%</strong>
                                    </div>
                                    <div class="progress progress-modern finance-gateway-progress">
                                        <div class="progress-bar {{ $rateClass }}" style="width: {{ min(100, max(0, $successRate)) }}%;"></div>
                                    </div>
                                    <div class="small text-muted mt-2">{{ $formatCurrency($item['paid_amount'] ?? 0) }} confirmed</div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="empty-state-box compact-empty-state my-0">
                            <div class="empty-state-icon"><i class="bi bi-cloud-check"></i></div>
                            <h5 class="empty-state-title">Belum ada gateway activity</h5>
                            <p class="empty-state-text mb-0">Data gateway provider belum tersedia pada periode ini.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-section-label mb-3 mt-1">
        <div class="dashboard-section-eyebrow">Transaction History</div>
        <h4 class="dashboard-section-title mb-1">Recent Confirmed Payments</h4>
        <p class="dashboard-section-subtitle mb-0">
            Pembayaran terbaru berstatus paid dalam periode {{ $period['label'] ?? '-' }}.
        </p>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-header d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h5 class="content-card-title mb-1">Recent Payments</h5>
                <p class="content-card-subtitle mb-0">Daftar transaksi paid terbaru yang sudah masuk sebagai confirmed revenue.</p>
            </div>
            <span class="badge rounded-pill bg-success-subtle text-success">
                {{ number_format($recentPayments->count()) }} payments
            </span>
        </div>
        <div class="content-card-body">
            @if($recentPayments->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-modern align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Student / Invoice</th>
                                <th>Date</th>
                                <th>Method</th>
                                <th>Gateway</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentPayments as $payment)
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ $payment['student_name'] ?: ('Student #' . ($payment['student_id'] ?? '-')) }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ $payment['invoice_number'] ?: ('Payment #' . $payment['id']) }}
                                            @if(!empty($payment['order_type']))
                                                · {{ ucfirst((string) $payment['order_type']) }}
                                            @endif
                                        </div>
                                    </td>
                                    <td>{{ $payment['effective_date_label'] ?? '-' }}</td>
                                    <td>{{ $payment['payment_method'] ?: '-' }}</td>
                                    <td>{{ $payment['gateway_provider'] ?: 'Manual / Unknown' }}</td>
                                    <td class="text-end fw-bold text-success">{{ $formatCurrency($payment['amount'] ?? 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-state-box my-0">
                    <div class="empty-state-icon"><i class="bi bi-receipt"></i></div>
                    <h5 class="empty-state-title">Belum ada confirmed payment</h5>
                    <p class="empty-state-text mb-0">Tidak ada payment berstatus paid dalam periode terpilih.</p>
                </div>
            @endif
        </div>
    </div>

    <div class="dashboard-section-label mb-3 mt-1">
        <div class="dashboard-section-eyebrow">Order Overview</div>
        <h4 class="dashboard-section-title mb-1">Order Financial Status</h4>
        <p class="dashboard-section-subtitle mb-0">
            Snapshot seluruh order dan nilai order baru pada periode terpilih.
        </p>
    </div>

    <div class="row g-3 mb-4">
        @foreach($orderStatusMeta as $statusKey => $meta)
            @php
                $statusData = data_get($orderSnapshot, 'statuses.' . $statusKey, ['count' => 0, 'value' => 0]);
            @endphp
            <div class="col-xl-3 col-md-6">
                <div class="stat-card h-100">
                    <div class="stat-card-top">
                        <div class="stat-icon-wrap {{ $meta['class'] }}">
                            <i class="bi bi-bag-fill"></i>
                        </div>
                        <div>
                            <div class="stat-title">{{ $meta['label'] }} Orders</div>
                            <div class="stat-value">{{ number_format((int) ($statusData['count'] ?? 0)) }}</div>
                        </div>
                    </div>
                    <div class="stat-description">Total nilai {{ $formatCurrency($statusData['value'] ?? 0) }}.</div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<x-ai-insight-widget
    title="AI Finance Recommendations"
    :insight="$financeSummary"
    :summary="$financeDashboardAiSummaryText ?: null"
/>
@endsection

@push('styles')
<style>
    .finance-dashboard-page .min-w-0 {
        min-width: 0;
    }

    .finance-filter-card .form-control {
        min-height: 44px;
        border-radius: 12px;
    }

    .finance-action-card {
        background: #ffffff;
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 18px;
        padding: 1rem;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.04);
        display: flex;
        flex-direction: column;
        gap: .75rem;
    }

    .finance-action-card-top {
        display: flex;
        align-items: center;
        gap: .85rem;
    }

    .finance-action-icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        font-size: 1.05rem;
    }

    .finance-action-label {
        color: #64748b;
        font-size: .77rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .finance-action-count {
        color: #0f172a;
        font-size: 1.5rem;
        font-weight: 900;
        line-height: 1;
        margin-top: .2rem;
    }

    .finance-action-total {
        color: #111827;
        font-size: 1rem;
        font-weight: 850;
    }

    .finance-action-description {
        color: #64748b;
        font-size: .82rem;
        line-height: 1.45;
        margin-top: auto;
    }

    .finance-kpi-scroll {
        display: grid;
        grid-auto-flow: column;
        grid-auto-columns: calc((100% - 2rem) / 3);
        gap: 1rem;
        overflow-x: auto;
        overflow-y: hidden;
        scroll-snap-type: x mandatory;
        scroll-behavior: smooth;
        padding: .1rem .05rem .8rem;
        scrollbar-width: thin;
        scrollbar-color: rgba(91, 62, 142, .5) rgba(91, 62, 142, .08);
    }

    .finance-kpi-scroll::-webkit-scrollbar {
        height: 8px;
    }

    .finance-kpi-scroll::-webkit-scrollbar-track {
        background: rgba(91, 62, 142, .08);
        border-radius: 999px;
    }

    .finance-kpi-scroll::-webkit-scrollbar-thumb {
        background: rgba(91, 62, 142, .45);
        border-radius: 999px;
        border: 2px solid rgba(255, 255, 255, .65);
    }

    .finance-kpi-scroll::-webkit-scrollbar-thumb:hover {
        background: rgba(91, 62, 142, .7);
    }

    .finance-kpi-item {
        min-width: 0;
        scroll-snap-align: start;
    }

    .finance-kpi-card {
        display: flex;
        flex-direction: column;
    }

    .finance-kpi-value {
        font-size: clamp(1.25rem, 1.7vw, 1.8rem);
        overflow-wrap: anywhere;
    }

    .finance-kpi-comparison {
        display: flex;
        align-items: center;
        gap: .65rem;
        flex-wrap: wrap;
        color: #64748b;
        font-size: .78rem;
        font-weight: 650;
    }

    .finance-chart-wrap {
        height: 360px;
        position: relative;
    }

    .finance-collection-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .8rem;
    }

    .finance-collection-item {
        border: 1px solid rgba(15, 23, 42, .08);
        border-radius: 16px;
        background: #ffffff;
        padding: .95rem;
        display: flex;
        flex-direction: column;
        gap: .3rem;
    }

    .finance-collection-item span {
        color: #64748b;
        font-size: .78rem;
        font-weight: 800;
    }

    .finance-collection-item strong {
        color: #0f172a;
        font-size: 1.45rem;
        font-weight: 900;
        line-height: 1;
    }

    .finance-collection-item small {
        color: #64748b;
        font-weight: 700;
    }

    .finance-collection-item.is-danger { border-left: 4px solid #dc3545; }
    .finance-collection-item.is-warning { border-left: 4px solid #ffc107; }
    .finance-collection-item.is-primary { border-left: 4px solid #5B3E8E; }
    .finance-collection-item.is-muted { border-left: 4px solid #94a3b8; }

    .finance-receivable-summary {
        border-radius: 18px;
        border: 1px solid rgba(91, 62, 142, .1);
        background: rgba(91, 62, 142, .045);
        padding: 1rem;
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
    }

    .finance-receivable-summary > div {
        display: flex;
        flex-direction: column;
        gap: .25rem;
    }

    .finance-receivable-summary span {
        color: #64748b;
        font-size: .78rem;
        font-weight: 750;
    }

    .finance-receivable-summary strong {
        color: #111827;
        font-size: 1.05rem;
        font-weight: 900;
    }

    .finance-payment-status-list,
    .finance-breakdown-list,
    .finance-gateway-list {
        display: grid;
        gap: .75rem;
    }

    .finance-payment-status-item,
    .finance-breakdown-item,
    .finance-gateway-item {
        border: 1px solid rgba(15, 23, 42, .08);
        border-radius: 16px;
        background: #ffffff;
        padding: .9rem;
    }

    .finance-payment-status-item,
    .finance-breakdown-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .finance-payment-status-item > strong,
    .finance-breakdown-item > strong {
        color: #111827;
        font-size: 1rem;
        font-weight: 900;
        text-align: right;
    }

    .finance-payment-status-icon {
        width: 38px;
        height: 38px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
    }

    .finance-gateway-progress {
        height: 8px;
    }

    .finance-table-scroll {
        max-height: 470px;
        overflow: auto;
    }

    .finance-table-scroll thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: #ffffff;
    }

    .compact-empty-state {
        min-height: 240px;
    }

    @media (max-width: 1199.98px) {
        .finance-kpi-scroll {
            grid-auto-columns: calc((100% - 1rem) / 2);
        }
    }

    @media (max-width: 767.98px) {
        .finance-kpi-scroll {
            grid-auto-columns: 86%;
        }

        .finance-chart-wrap {
            height: 320px;
        }
    }

    @media (max-width: 575.98px) {
        .finance-collection-grid,
        .finance-receivable-summary {
            grid-template-columns: 1fr;
        }

        .finance-dashboard-page {
            padding-left: 1rem !important;
            padding-right: 1rem !important;
        }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const filterForm = document.getElementById('financeDashboardFilterForm');
    const dateFromInput = document.getElementById('date_from');
    const dateToInput = document.getElementById('date_to');
    let submitTimer = null;

    const submitFilter = function () {
        if (!filterForm || !dateFromInput?.value || !dateToInput?.value) {
            return;
        }

        window.clearTimeout(submitTimer);
        submitTimer = window.setTimeout(function () {
            filterForm.submit();
        }, 350);
    };

    dateFromInput?.addEventListener('change', submitFilter);
    dateToInput?.addEventListener('change', submitFilter);

    const revenueCanvas = document.getElementById('financeRevenueChart');

    if (revenueCanvas && typeof Chart !== 'undefined') {
        const labels = @json($revenueChart['labels'] ?? []);
        const confirmedRevenue = @json(data_get($revenueChart, 'datasets.confirmed_revenue', []));
        const newOrderValue = @json(data_get($revenueChart, 'datasets.new_order_value', []));
        const paidTransactions = @json(data_get($revenueChart, 'datasets.paid_transactions', []));

        new Chart(revenueCanvas, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    {
                        type: 'bar',
                        label: 'Confirmed Revenue',
                        data: confirmedRevenue,
                        borderRadius: 8,
                        maxBarThickness: 38,
                        yAxisID: 'y',
                        backgroundColor: 'rgba(91, 62, 142, 0.82)',
                        borderColor: 'rgba(91, 62, 142, 1)',
                        borderWidth: 1,
                    },
                    {
                        type: 'line',
                        label: 'New Order Value',
                        data: newOrderValue,
                        tension: 0.35,
                        borderWidth: 2,
                        pointRadius: 2,
                        pointHoverRadius: 5,
                        yAxisID: 'y',
                        borderColor: '#FFBE04',
                        backgroundColor: 'rgba(255, 190, 4, 0.16)',
                    },
                    {
                        type: 'line',
                        label: 'Paid Transactions',
                        data: paidTransactions,
                        tension: 0.35,
                        borderWidth: 2,
                        borderDash: [6, 5],
                        pointRadius: 2,
                        pointHoverRadius: 5,
                        yAxisID: 'y1',
                        borderColor: '#3B8E4D',
                        backgroundColor: 'rgba(59, 142, 77, 0.14)',
                    }
                ]
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
                                const value = Number(context.raw || 0);

                                if (context.dataset.yAxisID === 'y1') {
                                    return ' ' + context.dataset.label + ': ' + value.toLocaleString('id-ID');
                                }

                                return ' ' + context.dataset.label + ': Rp ' + value.toLocaleString('id-ID');
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
                        position: 'left',
                        ticks: {
                            color: '#64748b',
                            callback: function (value) {
                                return 'Rp ' + Number(value).toLocaleString('id-ID');
                            },
                        },
                        grid: { color: 'rgba(100, 116, 139, 0.08)' },
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        ticks: {
                            precision: 0,
                            color: '#64748b',
                        },
                        grid: { drawOnChartArea: false },
                    },
                },
            },
        });
    }
});
</script>
@endpush
