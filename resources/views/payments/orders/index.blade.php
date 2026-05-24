@extends('layouts.app-dashboard')

@section('title', 'Sales Orders')

@section('content')
@php
    $statusBadgeClass = function ($status) {
        return match($status) {
            'pending' => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
            'partial' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
            'paid' => 'bg-success-subtle text-success-emphasis border border-success-subtle',
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

    $ordersCollection = $orders->getCollection();

    $totalOrders = $orders->total();
    $pendingOrders = $ordersCollection->where('status', 'pending')->count();
    $paidOrders = $ordersCollection->where('status', 'paid')->count();
    $workshopOrders = $ordersCollection->where('order_type', 'workshop')->count();

    $availableWorkshops = $workshops ?? collect();
@endphp

<div class="container-fluid px-4 py-4">
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Finance</div>
                <h1 class="page-title mb-2">Sales Orders</h1>
                <p class="page-subtitle mb-0">
                    Manage program and workshop transactions, pricing, discounts, and payment readiness in one place.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-primary btn-modern" onclick="openCreateModal()">
                    <i class="bi bi-plus-lg me-2"></i>Add Order
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
                        <i class="bi bi-receipt-cutoff"></i>
                    </div>
                    <div>
                        <div class="stat-title">Total Orders</div>
                        <div class="stat-value">{{ $totalOrders }}</div>
                    </div>
                </div>
                <div class="stat-description">Total transaksi program dan workshop.</div>
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
                        <div class="stat-value">{{ $pendingOrders }}</div>
                    </div>
                </div>
                <div class="stat-description">Order yang masih menunggu pembayaran.</div>
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
                        <div class="stat-value">{{ $paidOrders }}</div>
                    </div>
                </div>
                <div class="stat-description">Order yang sudah lunas pada halaman ini.</div>
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
                        <div class="stat-value">{{ $workshopOrders }}</div>
                    </div>
                </div>
                <div class="stat-description">Order workshop pada halaman ini.</div>
            </div>
        </div>
    </div>

    <div class="content-card orders-table-card">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Order List</h5>
                <p class="content-card-subtitle mb-0">
                    Review customer order details, source item, final amount, and transaction status.
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
                    @foreach (['pending', 'partial', 'paid', 'cancelled'] as $status)
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
                        placeholder="Search order..."
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
                    <a href="{{ route('orders.index') }}" class="btn btn-sm btn-outline-secondary">
                        Reset
                    </a>
                @endif
            </form>
        </div>

        <div class="content-card-body">
            @if($orders->count())
                <div class="orders-table-responsive dropdown-safe-table">
                    <table class="table table-hover align-middle admin-table orders-admin-table mb-0">
                        <thead>
                            <tr>
                                <th class="text-nowrap" style="width: 70px;">No</th>
                                <th class="text-nowrap">Type</th>
                                <th class="text-nowrap col-customer">Customer</th>
                                <th class="text-nowrap col-source">Source / Item</th>
                                <th class="text-end text-nowrap">Original</th>
                                <th class="text-end text-nowrap">Discount</th>
                                <th class="text-end text-nowrap">Final</th>
                                <th class="text-nowrap">Status</th>
                                <th class="text-nowrap">Schedule</th>
                                <th class="text-end text-nowrap" style="width: 150px;">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($orders as $order)
                                @php
                                    $orderType = $order->order_type ?: 'program';

                                    $customerName = $order->student->full_name ?? '-';
                                    $customerContact = $order->student?->email ?: ($order->student?->phone ?: '-');

                                    $programName = $order->batch?->program?->name ?? '-';
                                    $batchName = $order->batch?->name ?? '-';

                                    $workshopTitle = $order->workshop?->title
                                        ?? $order->workshop?->name
                                        ?? '-';

                                    $sourceTitle = $orderType === 'workshop'
                                        ? $workshopTitle
                                        : $programName;

                                    $sourceSubtitle = $orderType === 'workshop'
                                        ? 'Workshop'
                                        : $batchName;

                                    $schedule = $order->paymentSchedules?->first();

                                    $scheduleStatusClass = match($schedule?->status) {
                                        'paid' => 'bg-success-subtle text-success-emphasis border border-success-subtle',
                                        'pending' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
                                        'overdue' => 'bg-danger-subtle text-danger-emphasis border border-danger-subtle',
                                        'cancelled' => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
                                        default => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
                                    };

                                    $payload = [
                                        'id' => $order->id,
                                        'student_id' => $order->student_id,
                                        'order_type' => $orderType,
                                        'batch_id' => $order->batch_id,
                                        'workshop_id' => $order->workshop_id,
                                        'original_price' => (float) $order->original_price,
                                        'discount' => (float) $order->discount,
                                        'final_price' => (float) $order->final_price,
                                        'status' => $order->status,
                                        'notes' => $order->notes,
                                    ];

                                    $deleteTitle = $customerName . ' - ' . $sourceTitle;
                                @endphp

                                <tr>
                                    <td class="text-muted">
                                        {{ ($orders->currentPage() - 1) * $orders->perPage() + $loop->iteration }}
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

                                        @if($orderType === 'workshop' && $order->workshop)
                                            <div class="mt-1">
                                                <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis border border-primary-subtle">
                                                    <i class="bi bi-easel2 me-1"></i>Paid Workshop
                                                </span>
                                            </div>
                                        @endif
                                    </td>

                                    <td class="text-end text-nowrap">
                                        Rp {{ number_format((float) $order->original_price, 0, ',', '.') }}
                                    </td>

                                    <td class="text-end text-nowrap">
                                        Rp {{ number_format((float) $order->discount, 0, ',', '.') }}
                                    </td>

                                    <td class="text-end text-nowrap">
                                        <div class="fw-bold text-dark">
                                            Rp {{ number_format((float) $order->final_price, 0, ',', '.') }}
                                        </div>
                                    </td>

                                    <td class="text-nowrap">
                                        <span class="badge rounded-pill {{ $statusBadgeClass($order->status) }}">
                                            {{ ucfirst($order->status) }}
                                        </span>
                                    </td>

                                    <td class="text-nowrap">
                                        @if($schedule)
                                            <div class="fw-semibold text-dark">
                                                Rp {{ number_format((float) $schedule->amount, 0, ',', '.') }}
                                            </div>
                                            <div class="small text-muted">
                                                Due: {{ $schedule->due_date ? \Illuminate\Support\Carbon::parse($schedule->due_date)->format('d M Y') : '-' }}
                                            </div>
                                            <span class="badge rounded-pill {{ $scheduleStatusClass }}">
                                                {{ ucfirst($schedule->status) }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
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
                                                        onclick="openEditModal(@js($payload))"
                                                    >
                                                        <i class="bi bi-pencil-square me-2"></i>Edit Order
                                                    </button>
                                                </li>

                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>

                                                <li>
                                                    <button
                                                        type="button"
                                                        class="dropdown-item text-danger"
                                                        onclick="openDeleteModal({{ $order->id }}, @js($deleteTitle))"
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

                @if ($orders->hasPages())
                    <div class="mt-3">
                        {{ $orders->links() }}
                    </div>
                @endif
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-receipt"></i>
                    </div>

                    <h5 class="empty-state-title">No orders found</h5>
                    <p class="empty-state-text mb-0">
                        Belum ada order yang tercatat. Buat order program atau workshop untuk mulai mencatat transaksi.
                    </p>

                    <div class="mt-3">
                        <button type="button" class="btn btn-primary btn-modern" onclick="openCreateModal()">
                            <i class="bi bi-plus-lg me-2"></i>Add Order
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Form Modal --}}
<div class="modal fade" id="orderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="orderForm">
            @csrf
            <input type="hidden" id="order_id">

            <div class="modal-content custom-modal">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title" id="orderModalTitle">Add Order</h5>
                        <p class="text-muted mb-0">
                            Complete order source, pricing, discount, and transaction status.
                        </p>
                    </div>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-4">
                    <div id="formAlert" class="alert alert-danger d-none mb-3"></div>

                    <div class="content-card mb-3">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Customer & Source</h5>
                                <p class="content-card-subtitle mb-0">
                                    Select customer and choose whether this order belongs to a program or workshop.
                                </p>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="student_id" class="form-label">
                                        Customer / Student <span class="text-danger">*</span>
                                    </label>

                                    <select id="student_id" class="form-select">
                                        <option value="">Select Customer</option>
                                        @foreach ($students as $student)
                                            <option value="{{ $student->id }}">
                                                {{ $student->full_name }}
                                                @if ($student->email)
                                                    - {{ $student->email }}
                                                @elseif ($student->phone)
                                                    - {{ $student->phone }}
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>

                                    <div class="invalid-feedback" id="error_student_id"></div>
                                </div>

                                <div class="col-md-6">
                                    <label for="order_type" class="form-label">
                                        Order Type <span class="text-danger">*</span>
                                    </label>

                                    <select id="order_type" class="form-select">
                                        <option value="program">Program</option>
                                        <option value="workshop">Workshop</option>
                                    </select>

                                    <div class="invalid-feedback" id="error_order_type"></div>
                                </div>

                                <div class="col-md-6 source-field source-program">
                                    <label for="batch_id" class="form-label">
                                        Batch <span class="text-danger">*</span>
                                    </label>

                                    <select id="batch_id" class="form-select">
                                        <option value="">Select Batch</option>
                                        @foreach ($batches as $batch)
                                            <option
                                                value="{{ $batch->id }}"
                                                data-price="{{ (int) round((float) $batch->price) }}"
                                                data-program="{{ $batch->program->name ?? '' }}"
                                                data-batch="{{ $batch->name }}"
                                                data-status="{{ $batch->status }}"
                                            >
                                                {{ $batch->name }}
                                                @if ($batch->program)
                                                    ({{ $batch->program->name }})
                                                @endif
                                                - Rp {{ number_format((float) $batch->price, 0, ',', '.') }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <div class="invalid-feedback" id="error_batch_id"></div>
                                </div>

                                <div class="col-md-6 source-field source-workshop d-none">
                                    <label for="workshop_id" class="form-label">
                                        Workshop <span class="text-danger">*</span>
                                    </label>

                                    <select id="workshop_id" class="form-select">
                                        <option value="">Select Workshop</option>
                                        @foreach ($availableWorkshops as $workshop)
                                            <option
                                                value="{{ $workshop->id }}"
                                                data-price="{{ (int) round((float) ($workshop->price ?? $workshop->final_price ?? $workshop->registration_fee ?? 0)) }}"
                                                data-title="{{ $workshop->title ?? $workshop->name ?? 'Workshop #' . $workshop->id }}"
                                            >
                                                {{ $workshop->title ?? $workshop->name ?? 'Workshop #' . $workshop->id }}
                                                - Rp {{ number_format((float) ($workshop->price ?? $workshop->final_price ?? $workshop->registration_fee ?? 0), 0, ',', '.') }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <div class="invalid-feedback" id="error_workshop_id"></div>

                                    @if($availableWorkshops->count() === 0)
                                        <div class="form-text text-warning">
                                            Workshop list belum tersedia dari controller. Pastikan controller mengirim variable <code>$workshops</code>.
                                        </div>
                                    @endif
                                </div>

                                <div class="col-md-6">
                                    <label for="source_name" class="form-label">Selected Source</label>
                                    <input type="text" id="source_name" class="form-control" readonly>
                                </div>

                                <div class="col-md-6">
                                    <label for="status" class="form-label">
                                        Order Status <span class="text-danger">*</span>
                                    </label>

                                    <select id="status" class="form-select">
                                        <option value="pending">Pending</option>
                                        <option value="partial">Partial</option>
                                        <option value="paid">Paid</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>

                                    <div class="invalid-feedback" id="error_status"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="content-card mb-3">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Pricing & Discount</h5>
                                <p class="content-card-subtitle mb-0">
                                    Check original price, discount amount, and final price before saving the order.
                                </p>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="original_price" class="form-label">Original Price</label>
                                    <input type="number" id="original_price" class="form-control" readonly>
                                    <div class="form-text" id="original_price_text">Rp 0</div>
                                    <div class="invalid-feedback" id="error_original_price"></div>
                                </div>

                                <div class="col-md-4">
                                    <label for="discount_type" class="form-label">Discount Type</label>
                                    <select id="discount_type" class="form-select">
                                        <option value="amount">Amount (Rp)</option>
                                        <option value="percentage">Percentage (%)</option>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label for="discount" class="form-label">Discount</label>
                                    <input
                                        type="number"
                                        id="discount"
                                        class="form-control"
                                        min="0"
                                        step="0.01"
                                        value="0"
                                        placeholder="e.g. 100000"
                                    >
                                    <div class="form-text" id="discount_help">Enter discount in rupiah.</div>
                                    <div class="invalid-feedback" id="error_discount"></div>
                                </div>

                                <div class="col-md-6">
                                    <label for="discount_amount_preview" class="form-label">Discount Amount</label>
                                    <input type="number" id="discount_amount_preview" class="form-control" readonly>
                                    <div class="form-text" id="discount_amount_text">Rp 0</div>
                                </div>

                                <div class="col-md-6">
                                    <label for="final_price" class="form-label">Final Price</label>
                                    <input type="number" id="final_price" class="form-control" readonly>
                                    <div class="form-text fw-semibold" id="final_price_text">Rp 0</div>
                                    <div class="invalid-feedback" id="error_final_price"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="content-card">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Internal Notes</h5>
                                <p class="content-card-subtitle mb-0">
                                    Add optional notes for finance, sales, or academic follow-up.
                                </p>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea
                                id="notes"
                                rows="4"
                                class="form-control"
                                placeholder="Catatan internal..."
                            ></textarea>
                            <div class="invalid-feedback" id="error_notes"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-primary btn-modern" id="submitOrderBtn">
                        <span class="default-text">
                            <i class="bi bi-save me-2"></i>Save Order
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
<div class="modal fade" id="deleteOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal delete-confirm-modal">
            <div class="modal-header border-0 pb-0">
                <div class="delete-confirm-heading">
                    <div class="delete-confirm-icon">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>

                    <div>
                        <h5 class="modal-title">Delete Order</h5>
                        <p class="text-muted mb-0">Konfirmasi sebelum menghapus order.</p>
                    </div>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pt-4">
                <div class="delete-confirm-message">
                    <div class="delete-confirm-label">Order yang akan dihapus</div>
                    <div class="delete-confirm-name" id="deleteOrderName">-</div>
                </div>

                <div class="delete-confirm-warning mt-3">
                    Order yang sudah terhubung ke payment perlu dicek ulang sebelum dihapus.
                </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                    Cancel
                </button>

                <button type="button" class="btn btn-danger btn-modern" id="confirmDeleteOrderBtn">
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
    .orders-table-card,
    .orders-table-card .content-card-body {
        min-width: 0;
        max-width: 100%;
        overflow: hidden;
    }

    .orders-table-responsive {
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

    .orders-admin-table {
        width: 100%;
        min-width: 1240px;
        table-layout: auto;
    }

    .orders-admin-table th,
    .orders-admin-table td {
        vertical-align: middle;
    }

    .orders-admin-table .col-customer {
        min-width: 220px;
    }

    .orders-admin-table .col-source {
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

    @media (max-width: 768px) {
        .container-fluid.px-4 {
            padding-left: 1rem !important;
            padding-right: 1rem !important;
        }

        .orders-admin-table {
            min-width: 1120px;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    let orderModal;
    let deleteOrderModal;
    let isEditMode = false;
    let deleteOrderId = null;

    const routes = {
        store: @js(route('orders.store')),
        update: @js(route('orders.update', ['order' => '__ID__'])),
        destroy: @js(route('orders.destroy', ['order' => '__ID__'])),
    };

    const csrfToken = @js(csrf_token());

    const fields = {
        order_id: document.getElementById('order_id'),
        student_id: document.getElementById('student_id'),
        order_type: document.getElementById('order_type'),
        batch_id: document.getElementById('batch_id'),
        workshop_id: document.getElementById('workshop_id'),
        source_name: document.getElementById('source_name'),
        status: document.getElementById('status'),
        original_price: document.getElementById('original_price'),
        discount_type: document.getElementById('discount_type'),
        discount: document.getElementById('discount'),
        discount_amount_preview: document.getElementById('discount_amount_preview'),
        final_price: document.getElementById('final_price'),
        notes: document.getElementById('notes'),
    };

    document.addEventListener('DOMContentLoaded', function () {
        orderModal = new bootstrap.Modal(document.getElementById('orderModal'));
        deleteOrderModal = new bootstrap.Modal(document.getElementById('deleteOrderModal'));

        document.getElementById('orderForm').addEventListener('submit', submitOrderForm);
        document.getElementById('confirmDeleteOrderBtn').addEventListener('click', deleteOrder);

        fields.order_type.addEventListener('change', handleOrderTypeChange);
        fields.batch_id.addEventListener('change', handleSourceChange);
        fields.workshop_id.addEventListener('change', handleSourceChange);
        fields.discount_type.addEventListener('change', handleDiscountTypeChange);
        fields.discount.addEventListener('input', calculateFinalPrice);
    });

    function openCreateModal() {
        isEditMode = false;
        resetForm();

        document.getElementById('orderModalTitle').innerText = 'Add Order';

        fields.order_type.value = 'program';
        fields.status.value = 'pending';

        handleOrderTypeChange();

        orderModal.show();
    }

    function openEditModal(payload) {
        isEditMode = true;
        resetForm();

        document.getElementById('orderModalTitle').innerText = 'Edit Order';

        fields.order_id.value = payload.id || '';
        fields.student_id.value = payload.student_id || '';
        fields.order_type.value = payload.order_type || 'program';
        fields.batch_id.value = payload.batch_id || '';
        fields.workshop_id.value = payload.workshop_id || '';
        fields.original_price.value = Math.round(Number(payload.original_price || 0));
        fields.discount_type.value = 'amount';
        fields.discount.value = Math.round(Number(payload.discount || 0));
        fields.final_price.value = Math.round(Number(payload.final_price || 0));
        fields.status.value = payload.status || 'pending';
        fields.notes.value = payload.notes || '';

        handleOrderTypeChange(false);
        syncSourceName();
        calculateFinalPrice();

        orderModal.show();
    }

    function openDeleteModal(id, name) {
        deleteOrderId = id;
        document.getElementById('deleteOrderName').innerText = name || '-';
        resetDeleteButton();
        deleteOrderModal.show();
    }

    function handleOrderTypeChange(resetSource = true) {
        const type = fields.order_type.value;

        document.querySelectorAll('.source-field').forEach(el => el.classList.add('d-none'));

        if (type === 'workshop') {
            document.querySelectorAll('.source-workshop').forEach(el => el.classList.remove('d-none'));

            if (resetSource) {
                fields.batch_id.value = '';
                fields.workshop_id.value = '';
                fields.original_price.value = 0;
            }
        } else {
            document.querySelectorAll('.source-program').forEach(el => el.classList.remove('d-none'));

            if (resetSource) {
                fields.batch_id.value = '';
                fields.workshop_id.value = '';
                fields.original_price.value = 0;
            }
        }

        syncSourceName();
        calculateFinalPrice();
    }

    function handleSourceChange() {
        const type = fields.order_type.value;
        let selectedOption = null;

        if (type === 'workshop') {
            selectedOption = fields.workshop_id.options[fields.workshop_id.selectedIndex];
        } else {
            selectedOption = fields.batch_id.options[fields.batch_id.selectedIndex];
        }

        const price = selectedOption ? Number(selectedOption.dataset.price || 0) : 0;

        fields.original_price.value = Math.round(price);

        syncSourceName();
        calculateFinalPrice();
    }

    function syncSourceName() {
        const type = fields.order_type.value;
        let selectedOption = null;

        if (type === 'workshop') {
            selectedOption = fields.workshop_id.options[fields.workshop_id.selectedIndex];

            fields.source_name.value = selectedOption && fields.workshop_id.value
                ? (selectedOption.dataset.title || selectedOption.textContent.trim())
                : '';
        } else {
            selectedOption = fields.batch_id.options[fields.batch_id.selectedIndex];

            if (selectedOption && fields.batch_id.value) {
                const program = selectedOption.dataset.program || '';
                const batch = selectedOption.dataset.batch || '';

                fields.source_name.value = [program, batch].filter(Boolean).join(' - ');
            } else {
                fields.source_name.value = '';
            }
        }
    }

    function handleDiscountTypeChange() {
        const type = fields.discount_type.value;
        const help = document.getElementById('discount_help');

        if (type === 'percentage') {
            fields.discount.placeholder = 'e.g. 10';
            help.innerText = 'Enter discount percentage.';
        } else {
            fields.discount.placeholder = 'e.g. 100000';
            help.innerText = 'Enter discount in rupiah.';
        }

        calculateFinalPrice();
    }

    function calculateFinalPrice() {
        const originalPrice = Number(fields.original_price.value || 0);
        const discountInput = Number(fields.discount.value || 0);

        let discountAmount = 0;

        if (fields.discount_type.value === 'percentage') {
            discountAmount = originalPrice * (discountInput / 100);
        } else {
            discountAmount = discountInput;
        }

        discountAmount = Math.max(discountAmount, 0);
        discountAmount = Math.min(discountAmount, originalPrice);

        const finalPrice = Math.max(originalPrice - discountAmount, 0);

        fields.discount_amount_preview.value = Math.round(discountAmount);
        fields.final_price.value = Math.round(finalPrice);

        document.getElementById('original_price_text').innerText = formatRupiah(originalPrice);
        document.getElementById('discount_amount_text').innerText = formatRupiah(discountAmount);
        document.getElementById('final_price_text').innerText = formatRupiah(finalPrice);
    }

    async function submitOrderForm(event) {
        event.preventDefault();

        clearErrors();

        const submitButton = document.getElementById('submitOrderBtn');
        setButtonLoading(submitButton, true);

        calculateFinalPrice();

        const orderType = fields.order_type.value;

        const payload = {
            student_id: fields.student_id.value,
            order_type: orderType,
            batch_id: orderType === 'program' ? fields.batch_id.value : null,
            workshop_id: orderType === 'workshop' ? fields.workshop_id.value : null,
            original_price: fields.original_price.value || 0,
            discount: fields.discount_amount_preview.value || 0,
            final_price: fields.final_price.value || 0,
            status: fields.status.value,
            notes: fields.notes.value.trim(),
        };

        const orderId = fields.order_id.value;
        const url = isEditMode
            ? routes.update.replace('__ID__', orderId)
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
                throw new Error(result.message || 'Failed to save order.');
            }

            orderModal.hide();
            showToast(result.message || 'Order saved successfully.', 'success');

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

    async function deleteOrder() {
        if (!deleteOrderId) {
            return;
        }

        const deleteButton = document.getElementById('confirmDeleteOrderBtn');
        setButtonLoading(deleteButton, true);

        try {
            const response = await fetch(routes.destroy.replace('__ID__', deleteOrderId), {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to delete order.');
            }

            deleteOrderModal.hide();
            showToast(result.message || 'Order deleted successfully.', 'success');

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
        document.getElementById('orderForm').reset();

        fields.order_id.value = '';
        fields.order_type.value = 'program';
        fields.batch_id.value = '';
        fields.workshop_id.value = '';
        fields.source_name.value = '';
        fields.original_price.value = 0;
        fields.discount_type.value = 'amount';
        fields.discount.value = 0;
        fields.discount_amount_preview.value = 0;
        fields.final_price.value = 0;
        fields.status.value = 'pending';
        fields.notes.value = '';

        clearErrors();
        calculateFinalPrice();

        document.getElementById('formAlert').classList.add('d-none');
        document.getElementById('formAlert').innerHTML = '';
    }

    function clearErrors() {
        Object.values(fields).forEach(field => {
            if (!field || !field.classList) {
                return;
            }

            field.classList.remove('is-invalid');
        });

        document.querySelectorAll('#orderForm .invalid-feedback').forEach(el => {
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
        const button = document.getElementById('confirmDeleteOrderBtn');
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
</script>
@endpush