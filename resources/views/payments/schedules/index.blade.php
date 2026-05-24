@extends('layouts.app-dashboard')

@section('title', 'Payment Schedules')

@section('content')
@php
    $statusBadgeClass = function ($status) {
        return match($status) {
            'pending' => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
            'paid' => 'bg-success-subtle text-success-emphasis border border-success-subtle',
            'overdue' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
            'cancelled' => 'bg-danger-subtle text-danger-emphasis border border-danger-subtle',
            default => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
        };
    };

    $typeBadgeClass = function ($type) {
        return match($type) {
            'workshop' => 'bg-primary-subtle text-primary-emphasis border border-primary-subtle',
            'webinar' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
            'program' => 'bg-success-subtle text-success-emphasis border border-success-subtle',
            default => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
        };
    };

    $scheduleCollection = $paymentSchedules->getCollection();

    $totalSchedules = $paymentSchedules->total();
    $pendingSchedules = $scheduleCollection->where('status', 'pending')->count();
    $paidSchedules = $scheduleCollection->where('status', 'paid')->count();
    $workshopSchedules = $scheduleCollection->filter(fn ($schedule) => ($schedule->order?->order_type ?? 'program') === 'workshop')->count();
@endphp

<div class="container-fluid px-4 py-4">
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Finance</div>
                <h1 class="page-title mb-2">Payment Schedules</h1>
                <p class="page-subtitle mb-0">
                    Manage payment plans for program and workshop orders, including installment amount, due date, and schedule status.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-primary btn-modern" onclick="openCreateModal()">
                    <i class="bi bi-plus-lg me-2"></i>Add Schedule
                </button>
            </div>
        </div>
    </div>

    <div
        id="toastContainer"
        class="toast-container position-fixed top-0 end-0 p-3"
        style="z-index: 9999;"
    ></div>

    <div class="row g-3 mb-4">
        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-calendar2-check"></i>
                    </div>
                    <div>
                        <div class="stat-title">Total Schedules</div>
                        <div class="stat-value">{{ $totalSchedules }}</div>
                    </div>
                </div>
                <div class="stat-description">Total jadwal pembayaran tercatat.</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div>
                        <div class="stat-title">Pending</div>
                        <div class="stat-value">{{ $pendingSchedules }}</div>
                    </div>
                </div>
                <div class="stat-description">Schedule yang masih menunggu pembayaran.</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Paid</div>
                        <div class="stat-value">{{ $paidSchedules }}</div>
                    </div>
                </div>
                <div class="stat-description">Schedule yang sudah dibayar.</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-easel2-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Workshop</div>
                        <div class="stat-value">{{ $workshopSchedules }}</div>
                    </div>
                </div>
                <div class="stat-description">Schedule dari order workshop pada halaman ini.</div>
            </div>
        </div>
    </div>

    <div class="content-card schedules-table-card">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Payment Schedule List</h5>
                <p class="content-card-subtitle mb-0">
                    Review customer payment schedule details, source item, amount, due date, and current status.
                </p>
            </div>

            <form method="GET" class="d-flex align-items-center gap-2 flex-wrap">
                <select
                    name="order_type"
                    class="form-select form-select-sm"
                    style="width: 160px;"
                    onchange="this.form.submit()"
                >
                    <option value="">All Types</option>
                    <option value="program" {{ request('order_type') === 'program' ? 'selected' : '' }}>Program</option>
                    <option value="workshop" {{ request('order_type') === 'workshop' ? 'selected' : '' }}>Workshop</option>
                </select>

                <select
                    name="status"
                    class="form-select form-select-sm"
                    style="width: 160px;"
                    onchange="this.form.submit()"
                >
                    <option value="">All Status</option>
                    @foreach (['pending', 'paid', 'overdue', 'cancelled'] as $status)
                        <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>
                            {{ ucfirst($status) }}
                        </option>
                    @endforeach
                </select>

                <div class="input-group input-group-sm" style="width: 260px;">
                    <input
                        type="text"
                        name="keyword"
                        class="form-control"
                        value="{{ request('keyword') }}"
                        placeholder="Search schedule..."
                    >
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>

                <label for="per_page" class="form-label mb-0 small text-muted">Show</label>
                <select
                    name="per_page"
                    id="per_page"
                    class="form-select form-select-sm"
                    style="width: auto;"
                    onchange="this.form.submit()"
                >
                    @foreach ([10, 25, 50, 100] as $size)
                        <option value="{{ $size }}" {{ (int) request('per_page', 10) === $size ? 'selected' : '' }}>
                            {{ $size }}
                        </option>
                    @endforeach
                </select>

                @if(request()->hasAny(['order_type', 'status', 'keyword', 'per_page']))
                    <a href="{{ route('payment-schedules.index') }}" class="btn btn-sm btn-outline-secondary">
                        Reset
                    </a>
                @endif
            </form>
        </div>

        <div class="content-card-body">
            @if($paymentSchedules->count())
                <div class="schedules-table-responsive dropdown-safe-table">
                    <table class="table table-hover align-middle admin-table schedules-admin-table mb-0">
                        <thead>
                            <tr>
                                <th class="text-nowrap" style="width: 70px;">No</th>
                                <th class="text-nowrap">Type</th>
                                <th class="text-nowrap col-customer">Customer</th>
                                <th class="text-nowrap col-source">Source / Item</th>
                                <th class="text-nowrap">Title</th>
                                <th class="text-end text-nowrap">Amount</th>
                                <th class="text-nowrap">Due Date</th>
                                <th class="text-nowrap">Status</th>
                                <th class="text-end text-nowrap" style="width: 150px;">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($paymentSchedules as $schedule)
                                @php
                                    $order = $schedule->order;
                                    $orderType = $order?->order_type ?: 'program';

                                    $customerName = $order?->student?->full_name ?? '-';
                                    $customerContact = $order?->student?->email ?: ($order?->student?->phone ?: '-');

                                    $programName = $order?->batch?->program?->name ?? '-';
                                    $batchName = $order?->batch?->name ?? '-';

                                    $workshopTitle = $order?->workshop?->title
                                        ?? $order?->workshop?->name
                                        ?? '-';

                                    $sourceTitle = $orderType === 'workshop'
                                        ? $workshopTitle
                                        : $programName;

                                    $sourceSubtitle = $orderType === 'workshop'
                                        ? 'Workshop Order'
                                        : $batchName;

                                    $orderTotal = (float) ($order?->final_price ?? 0);
                                    $scheduledTotal = (float) ($order?->paymentSchedules?->sum('amount') ?? 0);
                                    $currentAmount = (float) ($schedule->amount ?? 0);

                                    $editPayload = [
                                        'id' => $schedule->id,
                                        'order_id' => $schedule->order_id,
                                        'order_type' => $orderType,
                                        'student_name' => $customerName,
                                        'source_title' => $sourceTitle,
                                        'source_subtitle' => $sourceSubtitle,
                                        'order_total' => $orderTotal,
                                        'scheduled_total' => $scheduledTotal,
                                        'current_amount' => $currentAmount,
                                        'title' => $schedule->title,
                                        'amount' => $currentAmount,
                                        'due_date' => $schedule->due_date ? \Illuminate\Support\Carbon::parse($schedule->due_date)->format('Y-m-d') : null,
                                        'status' => $schedule->status,
                                        'notes' => $schedule->notes,
                                    ];

                                    $deleteTitle = $customerName . ' - ' . $schedule->title;
                                @endphp

                                <tr>
                                    <td class="text-muted">
                                        {{ ($paymentSchedules->currentPage() - 1) * $paymentSchedules->perPage() + $loop->iteration }}
                                    </td>

                                    <td class="text-nowrap">
                                        <span class="badge rounded-pill {{ $typeBadgeClass($orderType) }}">
                                            {{ ucfirst($orderType) }}
                                        </span>
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ $customerName }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ $customerContact }}
                                        </div>
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ $sourceTitle }}
                                        </div>

                                        <div class="small text-muted">
                                            {{ $sourceSubtitle }}
                                        </div>
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ $schedule->title }}
                                        </div>

                                        <div class="small text-muted">
                                            Order #{{ $schedule->order_id }}
                                        </div>
                                    </td>

                                    <td class="text-end text-nowrap">
                                        <div class="fw-bold text-dark">
                                            Rp {{ number_format((float) $schedule->amount, 0, ',', '.') }}
                                        </div>
                                    </td>

                                    <td class="text-nowrap">
                                        {{ $schedule->due_date ? \Illuminate\Support\Carbon::parse($schedule->due_date)->format('d M Y') : '-' }}
                                    </td>

                                    <td class="text-nowrap">
                                        <span class="badge rounded-pill {{ $statusBadgeClass($schedule->status) }}">
                                            {{ ucfirst($schedule->status) }}
                                        </span>
                                    </td>

                                    <td class="text-end text-nowrap">
                                        <div class="dropdown">
                                            <button
                                                class="btn btn-sm btn-outline-secondary dropdown-toggle px-3"
                                                type="button"
                                                data-bs-toggle="dropdown"
                                                data-bs-boundary="viewport"
                                                aria-expanded="false"
                                            >
                                                Actions
                                            </button>

                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                <li>
                                                    <button
                                                        type="button"
                                                        class="dropdown-item"
                                                        onclick="openEditModal(@js($editPayload))"
                                                    >
                                                        <i class="bi bi-pencil-square me-2"></i>Edit Schedule
                                                    </button>
                                                </li>

                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>

                                                <li>
                                                    <button
                                                        type="button"
                                                        class="dropdown-item text-danger"
                                                        onclick="openDeleteModal({{ $schedule->id }}, @js($deleteTitle))"
                                                    >
                                                        <i class="bi bi-trash me-2"></i>Delete
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($paymentSchedules->hasPages())
                    <div class="mt-3">
                        {{ $paymentSchedules->links() }}
                    </div>
                @endif
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-calendar2-check"></i>
                    </div>

                    <h5 class="empty-state-title">No payment schedules found</h5>
                    <p class="empty-state-text mb-0">
                        Belum ada payment schedule yang tercatat. Buat jadwal pembayaran untuk mulai mengatur installment order.
                    </p>

                    <div class="mt-3">
                        <button type="button" class="btn btn-primary btn-modern" onclick="openCreateModal()">
                            <i class="bi bi-plus-lg me-2"></i>Add Schedule
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Form Modal --}}
<div class="modal fade" id="scheduleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="scheduleForm">
            @csrf
            <input type="hidden" id="schedule_id">

            <div class="modal-content custom-modal">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title" id="scheduleModalTitle">Add Schedule</h5>
                        <p class="text-muted mb-0">
                            Complete order, installment amount, due date, and payment schedule status.
                        </p>
                    </div>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-4">
                    <div id="formAlert" class="alert alert-danger d-none mb-3"></div>
                    <div id="amountWarning" class="alert alert-warning d-none mb-3"></div>

                    <div class="content-card mb-3">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Order Information</h5>
                                <p class="content-card-subtitle mb-0">
                                    Select the order and review customer, source item, and order total before creating the schedule.
                                </p>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label for="order_id" class="form-label">
                                        Order <span class="text-danger">*</span>
                                    </label>

                                    <select id="order_id" class="form-select">
                                        <option value="">Select Order</option>

                                        @foreach ($orders as $order)
                                            @php
                                                $orderType = $order->order_type ?: 'program';

                                                $customerName = $order->student->full_name ?? '-';

                                                $programName = $order->batch?->program?->name ?? '-';
                                                $batchName = $order->batch?->name ?? '-';

                                                $workshopTitle = $order->workshop?->title
                                                    ?? $order->workshop?->name
                                                    ?? '-';

                                                $sourceTitle = $orderType === 'workshop'
                                                    ? $workshopTitle
                                                    : $programName;

                                                $sourceSubtitle = $orderType === 'workshop'
                                                    ? 'Workshop Order'
                                                    : $batchName;

                                                $scheduledAmount = (float) (
                                                    $order->payment_schedules_sum_amount
                                                    ?? $order->paymentSchedules?->sum('amount')
                                                    ?? 0
                                                );

                                                $finalPrice = (float) ($order->final_price ?? 0);
                                            @endphp

                                            <option
                                                value="{{ $order->id }}"
                                                data-type="{{ $orderType }}"
                                                data-student="{{ $customerName }}"
                                                data-source-title="{{ $sourceTitle }}"
                                                data-source-subtitle="{{ $sourceSubtitle }}"
                                                data-total="{{ (int) round($finalPrice) }}"
                                                data-scheduled="{{ (int) round($scheduledAmount) }}"
                                            >
                                                [{{ ucfirst($orderType) }}] {{ $customerName }} - {{ $sourceTitle }}
                                                | Rp {{ number_format($finalPrice, 0, ',', '.') }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <div class="invalid-feedback" id="error_order_id"></div>
                                </div>

                                <div class="col-md-6">
                                    <label for="schedule_order_type" class="form-label">Order Type</label>
                                    <input type="text" id="schedule_order_type" class="form-control" readonly>
                                </div>

                                <div class="col-md-6">
                                    <label for="student_name" class="form-label">Customer</label>
                                    <input type="text" id="student_name" class="form-control" readonly>
                                </div>

                                <div class="col-md-6">
                                    <label for="source_title" class="form-label">Source / Item</label>
                                    <input type="text" id="source_title" class="form-control" readonly>
                                </div>

                                <div class="col-md-6">
                                    <label for="source_subtitle" class="form-label">Source Detail</label>
                                    <input type="text" id="source_subtitle" class="form-control" readonly>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="content-card mb-3">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Balance Preview</h5>
                                <p class="content-card-subtitle mb-0">
                                    Review order total, existing scheduled amount, and remaining balance before saving.
                                </p>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="order_total" class="form-label">Order Total</label>
                                    <input type="number" id="order_total" class="form-control" readonly>
                                    <div class="form-text" id="order_total_text">Rp 0</div>
                                </div>

                                <div class="col-md-4">
                                    <label for="total_scheduled" class="form-label">Total Scheduled</label>
                                    <input type="number" id="total_scheduled" class="form-control" readonly>
                                    <div class="form-text" id="total_scheduled_text">Rp 0</div>
                                </div>

                                <div class="col-md-4">
                                    <label for="remaining_balance" class="form-label">Remaining Balance</label>
                                    <input type="number" id="remaining_balance" class="form-control" readonly>
                                    <div class="form-text fw-semibold" id="remaining_balance_text">Rp 0</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="content-card mb-3">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Schedule Detail</h5>
                                <p class="content-card-subtitle mb-0">
                                    Define installment title, schedule amount, due date, and status.
                                </p>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="title" class="form-label">
                                        Title <span class="text-danger">*</span>
                                    </label>

                                    <input
                                        type="text"
                                        id="title"
                                        class="form-control"
                                        placeholder="e.g. DP, Termin 1, Pelunasan"
                                    >

                                    <div class="invalid-feedback" id="error_title"></div>
                                </div>

                                <div class="col-md-6">
                                    <label for="amount" class="form-label">
                                        Schedule Amount <span class="text-danger">*</span>
                                    </label>

                                    <input
                                        type="number"
                                        id="amount"
                                        class="form-control"
                                        min="0"
                                        step="0.01"
                                        placeholder="e.g. 500000"
                                    >

                                    <div class="form-text" id="amount_text">Rp 0</div>
                                    <div class="invalid-feedback" id="error_amount"></div>
                                </div>

                                <div class="col-md-6">
                                    <label for="due_date" class="form-label">
                                        Due Date <span class="text-danger">*</span>
                                    </label>

                                    <input type="date" id="due_date" class="form-control">

                                    <div class="invalid-feedback" id="error_due_date"></div>
                                </div>

                                <div class="col-md-6">
                                    <label for="status" class="form-label">
                                        Schedule Status <span class="text-danger">*</span>
                                    </label>

                                    <select id="status" class="form-select">
                                        <option value="pending">Pending</option>
                                        <option value="paid">Paid</option>
                                        <option value="overdue">Overdue</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>

                                    <div class="invalid-feedback" id="error_status"></div>
                                </div>

                                <div class="col-md-12">
                                    <label for="notes" class="form-label">Notes</label>

                                    <textarea
                                        id="notes"
                                        rows="4"
                                        class="form-control"
                                        placeholder="Internal payment schedule notes..."
                                    ></textarea>

                                    <div class="invalid-feedback" id="error_notes"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-primary btn-modern" id="submitScheduleBtn">
                        <span class="default-text">
                            <i class="bi bi-save me-2"></i>Save Schedule
                        </span>

                        <span class="loading-text d-none">
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            Saving...
                        </span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Delete Modal --}}
<div class="modal fade" id="deleteScheduleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal delete-confirm-modal">
            <div class="modal-header border-0 pb-0">
                <div class="delete-confirm-heading">
                    <div class="delete-confirm-icon">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>

                    <div>
                        <h5 class="modal-title">Delete Schedule</h5>
                        <p class="text-muted mb-0">Konfirmasi sebelum menghapus payment schedule.</p>
                    </div>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pt-4">
                <div class="delete-confirm-message">
                    <div class="delete-confirm-label">Schedule yang akan dihapus</div>
                    <div class="delete-confirm-name" id="deleteScheduleName">-</div>
                </div>

                <div class="delete-confirm-warning mt-3">
                    Schedule yang sudah memiliki payment paid sebaiknya dicancel, bukan dihapus permanen.
                </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                    Cancel
                </button>

                <button type="button" class="btn btn-danger btn-modern" id="confirmDeleteScheduleBtn">
                    <span class="default-text">
                        <i class="bi bi-trash me-2"></i>Delete
                    </span>

                    <span class="loading-text d-none">
                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        Deleting...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .schedules-table-card,
    .schedules-table-card .content-card-body {
        min-width: 0;
        max-width: 100%;
        overflow: hidden;
    }

    .schedules-table-responsive {
        display: block;
        width: 100%;
        max-width: 100%;
        overflow-x: auto !important;
        overflow-y: visible;
        -webkit-overflow-scrolling: touch;
        overscroll-behavior-x: contain;
        padding-bottom: 96px;
        margin-bottom: -96px;
    }

    .schedules-admin-table {
        width: 100%;
        min-width: 1180px;
        table-layout: auto;
    }

    .schedules-admin-table th,
    .schedules-admin-table td {
        vertical-align: middle;
    }

    .schedules-admin-table .col-customer {
        min-width: 220px;
    }

    .schedules-admin-table .col-source {
        min-width: 260px;
    }

    .admin-table thead th {
        background: #f8fafc;
        color: #475569;
        font-size: 0.78rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        border-bottom: 1px solid #e2e8f0;
    }

    .admin-table tbody td {
        vertical-align: middle;
        border-bottom: 1px solid #eef2f7;
    }

    .delete-confirm-heading {
        display: flex;
        align-items: center;
        gap: 0.9rem;
    }

    .delete-confirm-icon {
        width: 44px;
        height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 1rem;
        background: #fee2e2;
        color: #dc2626;
        font-size: 1.25rem;
    }

    .delete-confirm-message {
        border: 1px solid #fecaca;
        background: #fff1f2;
        border-radius: 1rem;
        padding: 1rem;
    }

    .delete-confirm-label {
        font-size: 0.78rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #9f1239;
        margin-bottom: 0.25rem;
    }

    .delete-confirm-name {
        font-size: 1rem;
        font-weight: 800;
        color: #111827;
    }

    .delete-confirm-warning {
        border-radius: 1rem;
        background: #fff7ed;
        border: 1px solid #fed7aa;
        color: #9a3412;
        padding: 0.9rem 1rem;
        font-weight: 700;
        font-size: 0.9rem;
    }
</style>
@endpush

@push('scripts')
<script>
    let scheduleModal;
    let deleteScheduleModal;
    let isEditMode = false;
    let deleteScheduleId = null;

    const routes = {
        store: @js(route('payment-schedules.store')),
        update: @js(route('payment-schedules.update', ['paymentSchedule' => '__ID__'])),
        destroy: @js(route('payment-schedules.destroy', ['paymentSchedule' => '__ID__'])),
    };

    const csrfToken = @js(csrf_token());

    const fields = {
        schedule_id: document.getElementById('schedule_id'),
        order_id: document.getElementById('order_id'),
        schedule_order_type: document.getElementById('schedule_order_type'),
        student_name: document.getElementById('student_name'),
        source_title: document.getElementById('source_title'),
        source_subtitle: document.getElementById('source_subtitle'),
        order_total: document.getElementById('order_total'),
        total_scheduled: document.getElementById('total_scheduled'),
        remaining_balance: document.getElementById('remaining_balance'),
        title: document.getElementById('title'),
        amount: document.getElementById('amount'),
        due_date: document.getElementById('due_date'),
        status: document.getElementById('status'),
        notes: document.getElementById('notes'),
    };

    document.addEventListener('DOMContentLoaded', function () {
        scheduleModal = new bootstrap.Modal(document.getElementById('scheduleModal'));
        deleteScheduleModal = new bootstrap.Modal(document.getElementById('deleteScheduleModal'));

        document.getElementById('scheduleForm').addEventListener('submit', submitScheduleForm);
        document.getElementById('confirmDeleteScheduleBtn').addEventListener('click', deleteSchedule);

        fields.order_id.addEventListener('change', handleOrderChange);
        fields.amount.addEventListener('input', updateBalancePreview);
    });

    function openCreateModal() {
        isEditMode = false;
        resetForm();

        document.getElementById('scheduleModalTitle').innerText = 'Add Payment Schedule';

        fields.status.value = 'pending';
        fields.due_date.value = @js(now()->toDateString());

        scheduleModal.show();
    }

    function openEditModal(payload) {
        isEditMode = true;
        resetForm();

        document.getElementById('scheduleModalTitle').innerText = 'Edit Payment Schedule';

        fields.schedule_id.value = payload.id || '';
        fields.order_id.value = payload.order_id || '';
        fields.schedule_order_type.value = capitalize(payload.order_type || 'program');
        fields.student_name.value = payload.student_name || '';
        fields.source_title.value = payload.source_title || '';
        fields.source_subtitle.value = payload.source_subtitle || '';
        fields.order_total.value = Math.round(Number(payload.order_total || 0));
        fields.total_scheduled.value = Math.round(Number(payload.scheduled_total || 0) - Number(payload.current_amount || 0));
        fields.remaining_balance.value = 0;
        fields.title.value = payload.title || '';
        fields.amount.value = Math.round(Number(payload.amount || 0));
        fields.due_date.value = payload.due_date || @js(now()->toDateString());
        fields.status.value = payload.status || 'pending';
        fields.notes.value = payload.notes || '';

        updateBalancePreview();

        scheduleModal.show();
    }

    function openDeleteModal(id, name) {
        deleteScheduleId = id;
        document.getElementById('deleteScheduleName').innerText = name || '-';
        resetDeleteButton();
        deleteScheduleModal.show();
    }

    function handleOrderChange() {
        const selectedOption = fields.order_id.options[fields.order_id.selectedIndex];

        if (!selectedOption || !fields.order_id.value) {
            fields.schedule_order_type.value = '';
            fields.student_name.value = '';
            fields.source_title.value = '';
            fields.source_subtitle.value = '';
            fields.order_total.value = 0;
            fields.total_scheduled.value = 0;
            fields.remaining_balance.value = 0;

            updateMoneyLabels();
            return;
        }

        fields.schedule_order_type.value = capitalize(selectedOption.dataset.type || 'program');
        fields.student_name.value = selectedOption.dataset.student || '';
        fields.source_title.value = selectedOption.dataset.sourceTitle || '';
        fields.source_subtitle.value = selectedOption.dataset.sourceSubtitle || '';
        fields.order_total.value = Number(selectedOption.dataset.total || 0);
        fields.total_scheduled.value = Number(selectedOption.dataset.scheduled || 0);

        const orderType = selectedOption.dataset.type || 'program';
        const sourceTitle = selectedOption.dataset.sourceTitle || '';

        if (!fields.title.value) {
            fields.title.value = orderType === 'workshop'
                ? `Pembayaran Workshop: ${sourceTitle}`
                : 'Payment Installment';
        }

        updateBalancePreview();
    }

    function updateBalancePreview() {
        const orderTotal = Number(fields.order_total.value || 0);
        const totalScheduled = Number(fields.total_scheduled.value || 0);
        const currentAmount = Number(fields.amount.value || 0);
        const remaining = Math.max(orderTotal - totalScheduled - currentAmount, 0);

        fields.remaining_balance.value = Math.round(remaining);

        document.getElementById('order_total_text').innerText = formatRupiah(orderTotal);
        document.getElementById('total_scheduled_text').innerText = formatRupiah(totalScheduled);
        document.getElementById('remaining_balance_text').innerText = formatRupiah(remaining);
        document.getElementById('amount_text').innerText = formatRupiah(currentAmount);

        const amountWarning = document.getElementById('amountWarning');

        if (currentAmount > Math.max(orderTotal - totalScheduled, 0) && orderTotal > 0) {
            amountWarning.innerText = 'Schedule amount is greater than remaining balance.';
            amountWarning.classList.remove('d-none');
        } else {
            amountWarning.classList.add('d-none');
            amountWarning.innerText = '';
        }
    }

    async function submitScheduleForm(event) {
        event.preventDefault();

        clearErrors();

        const submitButton = document.getElementById('submitScheduleBtn');
        setButtonLoading(submitButton, true);

        const payload = {
            order_id: fields.order_id.value,
            title: fields.title.value.trim(),
            amount: fields.amount.value || 0,
            due_date: fields.due_date.value,
            status: fields.status.value,
            notes: fields.notes.value.trim(),
        };

        const scheduleId = fields.schedule_id.value;
        const url = isEditMode
            ? routes.update.replace('__ID__', scheduleId)
            : routes.store;

        const method = isEditMode ? 'PUT' : 'POST';

        try {
            const response = await fetch(url, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            });

            const result = await response.json();

            if (response.status === 422) {
                setValidationErrors(result.errors || {});
                throw new Error(result.message || 'Validation failed.');
            }

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to save payment schedule.');
            }

            scheduleModal.hide();
            showToast(result.message || 'Payment schedule saved successfully.', 'success');

            setTimeout(() => {
                window.location.reload();
            }, 700);
        } catch (error) {
            if (error.message !== 'Validation failed.') {
                showFormAlert(error.message || 'Something went wrong.');
                showToast(error.message || 'Something went wrong.', 'danger');
            }
        } finally {
            setButtonLoading(submitButton, false);
        }
    }

    async function deleteSchedule() {
        if (!deleteScheduleId) {
            return;
        }

        const deleteButton = document.getElementById('confirmDeleteScheduleBtn');
        setButtonLoading(deleteButton, true);

        try {
            const response = await fetch(routes.destroy.replace('__ID__', deleteScheduleId), {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to delete payment schedule.');
            }

            deleteScheduleModal.hide();
            showToast(result.message || 'Payment schedule deleted successfully.', 'success');

            setTimeout(() => {
                window.location.reload();
            }, 700);
        } catch (error) {
            showToast(error.message || 'Something went wrong.', 'danger');
        } finally {
            setButtonLoading(deleteButton, false);
        }
    }

    function resetForm() {
        document.getElementById('scheduleForm').reset();

        fields.schedule_id.value = '';
        fields.order_id.value = '';
        fields.schedule_order_type.value = '';
        fields.student_name.value = '';
        fields.source_title.value = '';
        fields.source_subtitle.value = '';
        fields.order_total.value = 0;
        fields.total_scheduled.value = 0;
        fields.remaining_balance.value = 0;
        fields.title.value = '';
        fields.amount.value = '';
        fields.due_date.value = @js(now()->toDateString());
        fields.status.value = 'pending';
        fields.notes.value = '';

        clearErrors();
        updateMoneyLabels();

        document.getElementById('formAlert').classList.add('d-none');
        document.getElementById('formAlert').innerHTML = '';

        document.getElementById('amountWarning').classList.add('d-none');
        document.getElementById('amountWarning').innerHTML = '';
    }

    function updateMoneyLabels() {
        document.getElementById('order_total_text').innerText = formatRupiah(fields.order_total.value || 0);
        document.getElementById('total_scheduled_text').innerText = formatRupiah(fields.total_scheduled.value || 0);
        document.getElementById('remaining_balance_text').innerText = formatRupiah(fields.remaining_balance.value || 0);
        document.getElementById('amount_text').innerText = formatRupiah(fields.amount.value || 0);
    }

    function clearErrors() {
        Object.values(fields).forEach(field => {
            if (!field || !field.classList) {
                return;
            }

            field.classList.remove('is-invalid');
        });

        document.querySelectorAll('#scheduleForm .invalid-feedback').forEach(el => {
            el.innerText = '';
        });
    }

    function setValidationErrors(errors) {
        Object.keys(errors).forEach(key => {
            const field = fields[key];
            const errorEl = document.getElementById(`error_${key}`);

            if (field) {
                field.classList.add('is-invalid');
            }

            if (errorEl) {
                errorEl.innerText = Array.isArray(errors[key]) ? errors[key][0] : errors[key];
            }
        });
    }

    function showFormAlert(message) {
        const alert = document.getElementById('formAlert');
        alert.innerHTML = message;
        alert.classList.remove('d-none');
    }

    function setButtonLoading(button, isLoading) {
        if (!button) {
            return;
        }

        button.disabled = isLoading;

        const defaultText = button.querySelector('.default-text');
        const loadingText = button.querySelector('.loading-text');

        if (defaultText) {
            defaultText.classList.toggle('d-none', isLoading);
        }

        if (loadingText) {
            loadingText.classList.toggle('d-none', !isLoading);
        }
    }

    function resetDeleteButton() {
        const button = document.getElementById('confirmDeleteScheduleBtn');
        setButtonLoading(button, false);
    }

    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');

        const toastId = 'toast-' + Date.now();

        const bgClass = {
            success: 'text-bg-success',
            danger: 'text-bg-danger',
            warning: 'text-bg-warning',
            info: 'text-bg-info',
        }[type] || 'text-bg-success';

        const toastEl = document.createElement('div');
        toastEl.id = toastId;
        toastEl.className = `toast align-items-center ${bgClass} border-0`;
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');

        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body fw-semibold">
                    ${message}
                </div>
                <button
                    type="button"
                    class="btn-close btn-close-white me-2 m-auto"
                    data-bs-dismiss="toast"
                    aria-label="Close"
                ></button>
            </div>
        `;

        container.appendChild(toastEl);

        const toast = new bootstrap.Toast(toastEl, {
            delay: 3000,
        });

        toast.show();

        toastEl.addEventListener('hidden.bs.toast', () => {
            toastEl.remove();
        });
    }

    function formatRupiah(value) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        }).format(Number(value || 0));
    }

    function capitalize(value) {
        if (!value) {
            return '';
        }

        return value.charAt(0).toUpperCase() + value.slice(1);
    }
</script>
@endpush