@extends('layouts.app-dashboard')

@section('title', 'Payments')

@section('content')
@php
    $statusBadgeClass = function ($status) {
        return match($status) {
            'pending' => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
            'paid' => 'bg-success-subtle text-success-emphasis border border-success-subtle',
            'failed' => 'bg-danger-subtle text-danger-emphasis border border-danger-subtle',
            'expired' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
            'cancelled' => 'bg-dark text-white border border-dark',
            default => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
        };
    };
@endphp

<div class="container-fluid px-4 py-4">
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Finance</div>
                <h1 class="page-title mb-2">Payments</h1>
                <p class="page-subtitle mb-0">
                    Manage payment records, invoice references, payment confirmations, schedules, and transaction status in one place.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-light btn-modern" onclick="openCreateModal()">
                    <i class="bi bi-plus-lg me-2"></i>Add Payment
                </button>
            </div>
        </div>
    </div>

    <div
        id="toastContainer"
        class="toast-container position-fixed top-0 end-0 p-3"
        style="z-index: 9999;"
    ></div>

    <div class="content-card">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Payment List</h5>
                <p class="content-card-subtitle mb-0">
                    Review invoice number, student payment, selected schedule, amount, payment method, date, and current payment status.
                </p>
            </div>

            <form method="GET" class="d-flex align-items-center gap-2 flex-wrap">
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
                <span class="small text-muted">entries</span>
            </form>
        </div>

        <div class="content-card-body">
            @if($payments->count())
                <div class="table-responsive dropdown-safe-table">
                    <table class="table table-hover align-middle admin-table mb-0">
                        <thead>
                            <tr>
                                <th class="text-nowrap" style="width: 80px;">No</th>
                                <th class="text-nowrap">Invoice</th>
                                <th class="text-nowrap">Student</th>
                                <th class="text-nowrap">Program / Batch</th>
                                <th class="text-nowrap">Schedule</th>
                                <th class="text-end text-nowrap">Amount</th>
                                <th class="text-nowrap">Method</th>
                                <th class="text-nowrap">Date</th>
                                <th class="text-nowrap">Status</th>
                                <th class="text-end text-nowrap" style="width: 160px;">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($payments as $payment)
                                <tr>
                                    <td class="text-muted">
                                        {{ ($payments->currentPage() - 1) * $payments->perPage() + $loop->iteration }}
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ $payment->invoice_number ?: '-' }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ $payment->reference_number ?: '-' }}
                                        </div>
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ $payment->order->student->full_name ?? '-' }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ $payment->order->student->email ?? ($payment->order->student->phone ?? '-') }}
                                        </div>
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ $payment->order->batch->program->name ?? '-' }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ $payment->order->batch->name ?? '-' }}
                                        </div>
                                    </td>

                                    <td>
                                        @if ($payment->paymentSchedule)
                                            <div class="fw-semibold text-dark">
                                                {{ $payment->paymentSchedule->title }}
                                            </div>
                                            <div class="small text-muted">
                                                Rp {{ number_format((float) $payment->paymentSchedule->amount, 0, ',', '.') }}
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    <td class="text-end text-nowrap">
                                        <div class="fw-bold text-dark">
                                            Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}
                                        </div>
                                    </td>

                                    <td class="text-nowrap">
                                        {{ $payment->payment_method ?: '-' }}
                                    </td>

                                    <td class="text-nowrap">
                                        {{ $payment->payment_date?->format('d M Y') ?: '-' }}
                                    </td>

                                    <td class="text-nowrap">
                                        <span class="badge rounded-pill {{ $statusBadgeClass($payment->status) }}">
                                            {{ ucfirst($payment->status) }}
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
                                                @if ($payment->invoice_number)
                                                    <li>
                                                        <a
                                                            href="{{ route('payments.invoice', $payment->id) }}"
                                                            class="dropdown-item"
                                                        >
                                                            <i class="bi bi-receipt me-2"></i>View Invoice
                                                        </a>
                                                    </li>

                                                    <li>
                                                        <hr class="dropdown-divider">
                                                    </li>
                                                @endif

                                                <li>
                                                    <button
                                                        type="button"
                                                        class="dropdown-item"
                                                        onclick="editPayment({{ $payment->id }})"
                                                    >
                                                        <i class="bi bi-pencil-square me-2"></i>Edit Payment
                                                    </button>
                                                </li>

                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>

                                                <li>
                                                    <button
                                                        type="button"
                                                        class="dropdown-item text-danger"
                                                        onclick="openDeleteModal({{ $payment->id }}, @js($payment->invoice_number ?: 'Payment #' . $payment->id))"
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

                @if ($payments->hasPages())
                    <div class="mt-3">
                        {{ $payments->links() }}
                    </div>
                @endif
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-cash-stack"></i>
                    </div>

                    <h5 class="empty-state-title">No payments found</h5>
                    <p class="empty-state-text mb-0">
                        Belum ada payment yang tercatat. Tambahkan pembayaran baru untuk mulai mencatat transaksi student.
                    </p>

                    <div class="mt-3">
                        <button type="button" class="btn btn-primary btn-modern" onclick="openCreateModal()">
                            <i class="bi bi-plus-lg me-2"></i>Add Payment
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Form Modal --}}
<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="paymentForm">
            @csrf
            <input type="hidden" id="payment_id">

            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold mb-1" id="paymentModalTitle">Add Payment</h5>
                        <div class="small text-muted">
                            Complete order, schedule, amount, payment method, gateway reference, and payment status.
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div id="formAlert" class="alert alert-danger d-none mb-3"></div>

                    <div class="content-card mb-3">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Order & Schedule</h5>
                                <p class="content-card-subtitle mb-0">
                                    Select the order and optional payment schedule for this payment record.
                                </p>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="order_id" class="form-label">
                                        Order <span class="text-danger">*</span>
                                    </label>
                                    <select id="order_id" class="form-select">
                                        <option value="">Select Order</option>
                                        @foreach ($orders as $order)
                                            <option
                                                value="{{ $order->id }}"
                                                data-student="{{ $order->student->full_name ?? '' }}"
                                                data-program="{{ $order->batch->program->name ?? '' }}"
                                                data-batch="{{ $order->batch->name ?? '' }}"
                                                data-total="{{ (int) round((float) $order->final_price) }}"
                                            >
                                                {{ $order->student->full_name ?? '-' }} -
                                                {{ $order->batch->name ?? '-' }}
                                                @if ($order->batch && $order->batch->program)
                                                    ({{ $order->batch->program->name }})
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback" id="error_order_id"></div>
                                </div>

                                <div class="col-md-6">
                                    <label for="payment_schedule_id" class="form-label">Payment Schedule</label>
                                    <select id="payment_schedule_id" class="form-select">
                                        <option value="">Select Schedule (Optional)</option>
                                        @foreach ($paymentSchedules as $schedule)
                                            <option
                                                value="{{ $schedule->id }}"
                                                data-order-id="{{ $schedule->order_id }}"
                                                data-title="{{ $schedule->title }}"
                                                data-amount="{{ (int) round((float) $schedule->amount) }}"
                                                data-due-date="{{ $schedule->due_date?->format('Y-m-d') ?? '' }}"
                                            >
                                                {{ $schedule->order->student->full_name ?? '-' }} -
                                                {{ $schedule->title }} -
                                                Rp {{ number_format((float) $schedule->amount, 0, ',', '.') }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">
                                        Optional. Choose a schedule if this payment is for a specific installment.
                                    </div>
                                    <div class="invalid-feedback" id="error_payment_schedule_id"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="content-card mb-3">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Order Preview</h5>
                                <p class="content-card-subtitle mb-0">
                                    Review selected student, program, batch, order total, and schedule amount.
                                </p>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="student_name" class="form-label">Student</label>
                                    <input type="text" id="student_name" class="form-control" readonly>
                                </div>

                                <div class="col-md-4">
                                    <label for="program_name" class="form-label">Program</label>
                                    <input type="text" id="program_name" class="form-control" readonly>
                                </div>

                                <div class="col-md-4">
                                    <label for="batch_name" class="form-label">Batch</label>
                                    <input type="text" id="batch_name" class="form-control" readonly>
                                </div>

                                <div class="col-md-4">
                                    <label for="order_total" class="form-label">Order Total</label>
                                    <input type="number" id="order_total" class="form-control" readonly>
                                    <div class="form-text" id="order_total_text">Rp 0</div>
                                </div>

                                <div class="col-md-4">
                                    <label for="schedule_title_preview" class="form-label">Schedule Title</label>
                                    <input type="text" id="schedule_title_preview" class="form-control" readonly>
                                </div>

                                <div class="col-md-4">
                                    <label for="schedule_amount_preview" class="form-label">Schedule Amount</label>
                                    <input type="number" id="schedule_amount_preview" class="form-control" readonly>
                                    <div class="form-text" id="schedule_amount_text">Rp 0</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="content-card mb-3">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Payment Detail</h5>
                                <p class="content-card-subtitle mb-0">
                                    Input payment amount, payment date, payment status, method, and reference number.
                                </p>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="amount" class="form-label">
                                        Payment Amount <span class="text-danger">*</span>
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

                                <div class="col-md-4">
                                    <label for="payment_date" class="form-label">Payment Date</label>
                                    <input type="date" id="payment_date" class="form-control">
                                    <div class="invalid-feedback" id="error_payment_date"></div>
                                </div>

                                <div class="col-md-4">
                                    <label for="status" class="form-label">
                                        Payment Status <span class="text-danger">*</span>
                                    </label>
                                    <select id="status" class="form-select">
                                        <option value="pending">Pending</option>
                                        <option value="paid">Paid</option>
                                        <option value="failed">Failed</option>
                                        <option value="expired">Expired</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                    <div class="invalid-feedback" id="error_status"></div>
                                </div>

                                <div class="col-md-6">
                                    <label for="payment_method" class="form-label">Payment Method</label>
                                    <input
                                        type="text"
                                        id="payment_method"
                                        class="form-control"
                                        placeholder="e.g. Transfer, Cash, QRIS, Virtual Account"
                                    >
                                    <div class="invalid-feedback" id="error_payment_method"></div>
                                </div>

                                <div class="col-md-6">
                                    <label for="reference_number" class="form-label">Reference Number</label>
                                    <input
                                        type="text"
                                        id="reference_number"
                                        class="form-control"
                                        placeholder="e.g. bank ref no / transaction code"
                                    >
                                    <div class="invalid-feedback" id="error_reference_number"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="content-card">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Gateway & Internal Notes</h5>
                                <p class="content-card-subtitle mb-0">
                                    Add gateway information and internal notes for finance reconciliation.
                                </p>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="gateway_provider" class="form-label">Gateway Provider</label>
                                    <input
                                        type="text"
                                        id="gateway_provider"
                                        class="form-control"
                                        placeholder="e.g. Xendit"
                                    >
                                    <div class="invalid-feedback" id="error_gateway_provider"></div>
                                </div>

                                <div class="col-md-6">
                                    <label for="gateway_transaction_id" class="form-label">Gateway Transaction ID</label>
                                    <input
                                        type="text"
                                        id="gateway_transaction_id"
                                        class="form-control"
                                        placeholder="e.g. external transaction id"
                                    >
                                    <div class="invalid-feedback" id="error_gateway_transaction_id"></div>
                                </div>

                                <div class="col-12">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea
                                        id="notes"
                                        rows="4"
                                        class="form-control"
                                        placeholder="Internal notes for this payment"
                                    ></textarea>
                                    <div class="invalid-feedback" id="error_notes"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary btn-modern" id="submitBtn">
                        <span class="default-text">
                            <i class="bi bi-check-circle me-2"></i>Save
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
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold mb-1">Delete Payment</h5>
                    <div class="small text-muted">
                        This action will remove the selected payment record from the system.
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="alert alert-danger mb-0">
                    <div class="d-flex gap-2 align-items-start">
                        <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                        <div>
                            <div class="fw-semibold">Delete this payment?</div>
                            <div class="small mt-1">
                                Are you sure you want to delete
                                <strong id="deletePaymentName"></strong>?
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-2"></i>Cancel
                </button>
                <button type="button" class="btn btn-danger btn-modern" id="confirmDeleteBtn">
                    <span class="default-delete-text">
                        <i class="bi bi-trash me-2"></i>Delete
                    </span>
                    <span class="loading-delete-text d-none">
                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        Deleting...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const paymentModalEl = document.getElementById('paymentModal');
    const paymentModal = new bootstrap.Modal(paymentModalEl);
    const paymentForm = document.getElementById('paymentForm');
    const submitBtn = document.getElementById('submitBtn');
    const modalTitle = document.getElementById('paymentModalTitle');
    const formAlert = document.getElementById('formAlert');

    const deleteModalEl = document.getElementById('deleteModal');
    const deleteModal = new bootstrap.Modal(deleteModalEl);
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const deletePaymentNameEl = document.getElementById('deletePaymentName');

    const fields = {
        id: document.getElementById('payment_id'),
        order_id: document.getElementById('order_id'),
        payment_schedule_id: document.getElementById('payment_schedule_id'),
        student_name: document.getElementById('student_name'),
        program_name: document.getElementById('program_name'),
        batch_name: document.getElementById('batch_name'),
        order_total: document.getElementById('order_total'),
        schedule_title_preview: document.getElementById('schedule_title_preview'),
        schedule_amount_preview: document.getElementById('schedule_amount_preview'),
        amount: document.getElementById('amount'),
        payment_date: document.getElementById('payment_date'),
        payment_method: document.getElementById('payment_method'),
        reference_number: document.getElementById('reference_number'),
        gateway_transaction_id: document.getElementById('gateway_transaction_id'),
        gateway_provider: document.getElementById('gateway_provider'),
        status: document.getElementById('status'),
        notes: document.getElementById('notes'),
    };

    const orderTotalText = document.getElementById('order_total_text');
    const scheduleAmountText = document.getElementById('schedule_amount_text');
    const amountText = document.getElementById('amount_text');

    let deletePaymentId = null;
    let reloadTimeout = null;

    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        const id = 'toast-' + Date.now();

        const bgClass = {
            success: 'bg-success',
            danger: 'bg-danger',
            warning: 'bg-warning text-dark',
            info: 'bg-info text-dark'
        }[type] || 'bg-success';

        const html = `
            <div id="${id}" class="toast align-items-center text-white ${bgClass} border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', html);

        const toastEl = document.getElementById(id);
        const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
        toast.show();

        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    }

    function scheduleReload() {
        if (reloadTimeout) {
            clearTimeout(reloadTimeout);
        }

        reloadTimeout = setTimeout(() => {
            location.reload();
        }, 3000);
    }

    function formatNumber(value) {
        const cleaned = String(value ?? '').replace(/,/g, '').trim();
        const number = parseFloat(cleaned);
        return Number.isNaN(number) ? 0 : number;
    }

    function roundCurrency(value) {
        return Math.round(formatNumber(value));
    }

    function formatRupiah(value) {
        const number = roundCurrency(value);
        return 'Rp ' + number.toLocaleString('id-ID');
    }

    function getSelectedOrderOption() {
        return fields.order_id.options[fields.order_id.selectedIndex] || null;
    }

    function getSelectedScheduleOption() {
        return fields.payment_schedule_id.options[fields.payment_schedule_id.selectedIndex] || null;
    }

    function updateOrderPreview() {
        const selectedOption = getSelectedOrderOption();

        if (!selectedOption || !selectedOption.value) {
            fields.student_name.value = '';
            fields.program_name.value = '';
            fields.batch_name.value = '';
            fields.order_total.value = '';
            orderTotalText.textContent = 'Rp 0';
            return;
        }

        const total = roundCurrency(selectedOption.dataset.total || 0);

        fields.student_name.value = selectedOption.dataset.student || '';
        fields.program_name.value = selectedOption.dataset.program || '';
        fields.batch_name.value = selectedOption.dataset.batch || '';
        fields.order_total.value = total;
        orderTotalText.textContent = formatRupiah(total);
    }

    function updateSchedulePreview() {
        const selectedOption = getSelectedScheduleOption();

        if (!selectedOption || !selectedOption.value) {
            fields.schedule_title_preview.value = '';
            fields.schedule_amount_preview.value = '';
            scheduleAmountText.textContent = 'Rp 0';
            return;
        }

        const scheduleAmount = roundCurrency(selectedOption.dataset.amount || 0);

        fields.schedule_title_preview.value = selectedOption.dataset.title || '';
        fields.schedule_amount_preview.value = scheduleAmount;
        scheduleAmountText.textContent = formatRupiah(scheduleAmount);
    }

    function filterScheduleOptionsByOrder() {
        const selectedOrderId = fields.order_id.value;
        const currentScheduleId = fields.payment_schedule_id.value;

        Array.from(fields.payment_schedule_id.options).forEach((option, index) => {
            if (index === 0) {
                option.hidden = false;
                return;
            }

            option.hidden = selectedOrderId !== '' && option.dataset.orderId !== selectedOrderId;
        });

        if (currentScheduleId) {
            const selectedOption = fields.payment_schedule_id.options[fields.payment_schedule_id.selectedIndex];
            if (!selectedOption || selectedOption.hidden) {
                fields.payment_schedule_id.value = '';
            }
        }
    }

    function autoFillAmountFromSchedule() {
        const selectedOption = getSelectedScheduleOption();

        if (!selectedOption || !selectedOption.value) {
            return;
        }

        const scheduleAmount = roundCurrency(selectedOption.dataset.amount || 0);

        if (!fields.amount.value || roundCurrency(fields.amount.value) === 0) {
            fields.amount.value = scheduleAmount;
            updateAmountPreview();
        }
    }

    function updateAmountPreview() {
        amountText.textContent = formatRupiah(fields.amount.value || 0);
    }

    function resetForm() {
        paymentForm.reset();
        fields.id.value = '';
        fields.order_id.value = '';
        fields.payment_schedule_id.value = '';
        fields.student_name.value = '';
        fields.program_name.value = '';
        fields.batch_name.value = '';
        fields.order_total.value = '';
        fields.schedule_title_preview.value = '';
        fields.schedule_amount_preview.value = '';
        fields.amount.value = '';
        fields.payment_date.value = '';
        fields.payment_method.value = '';
        fields.reference_number.value = '';
        fields.gateway_transaction_id.value = '';
        fields.gateway_provider.value = '';
        fields.status.value = 'pending';
        fields.notes.value = '';

        orderTotalText.textContent = 'Rp 0';
        scheduleAmountText.textContent = 'Rp 0';
        amountText.textContent = 'Rp 0';

        Array.from(fields.payment_schedule_id.options).forEach((option, index) => {
            option.hidden = false;
        });

        formAlert.classList.add('d-none');
        formAlert.innerHTML = '';

        clearValidationErrors();
        setSubmitLoading(false);
    }

    function clearValidationErrors() {
        Object.values(fields).forEach(field => {
            if (field && field.classList) {
                field.classList.remove('is-invalid');
            }
        });

        [
            'order_id',
            'payment_schedule_id',
            'amount',
            'payment_date',
            'payment_method',
            'reference_number',
            'gateway_transaction_id',
            'gateway_provider',
            'status',
            'notes'
        ].forEach(key => {
            const errorEl = document.getElementById(`error_${key}`);
            if (errorEl) {
                errorEl.textContent = '';
            }
        });
    }

    function setValidationErrors(errors = {}) {
        clearValidationErrors();

        Object.keys(errors).forEach(key => {
            const field = fields[key];
            const errorEl = document.getElementById(`error_${key}`);

            if (field && field.classList) {
                field.classList.add('is-invalid');
            }

            if (errorEl) {
                errorEl.textContent = Array.isArray(errors[key]) ? errors[key][0] : errors[key];
            }
        });
    }

    function setSubmitLoading(isLoading) {
        submitBtn.disabled = isLoading;
        submitBtn.querySelector('.default-text').classList.toggle('d-none', isLoading);
        submitBtn.querySelector('.loading-text').classList.toggle('d-none', !isLoading);
    }

    function setDeleteLoading(isLoading) {
        confirmDeleteBtn.disabled = isLoading;
        confirmDeleteBtn.querySelector('.default-delete-text').classList.toggle('d-none', isLoading);
        confirmDeleteBtn.querySelector('.loading-delete-text').classList.toggle('d-none', !isLoading);
    }

    function openCreateModal() {
        resetForm();
        modalTitle.textContent = 'Add Payment';
        updateOrderPreview();
        updateSchedulePreview();
        updateAmountPreview();
        paymentModal.show();
    }

    async function editPayment(id) {
        resetForm();
        modalTitle.textContent = 'Edit Payment';

        try {
            const response = await fetch(`/payments/${id}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to fetch payment data.');
            }

            const data = result.data;

            fields.id.value = data.id ?? '';
            fields.order_id.value = data.order_id ?? '';
            filterScheduleOptionsByOrder();
            fields.payment_schedule_id.value = data.payment_schedule_id ?? '';
            fields.amount.value = roundCurrency(data.amount ?? 0);
            fields.payment_date.value = data.payment_date ?? '';
            fields.payment_method.value = data.payment_method ?? '';
            fields.reference_number.value = data.reference_number ?? '';
            fields.gateway_transaction_id.value = data.gateway_transaction_id ?? '';
            fields.gateway_provider.value = data.gateway_provider ?? '';
            fields.status.value = data.status ?? 'pending';
            fields.notes.value = data.notes ?? '';

            updateOrderPreview();
            updateSchedulePreview();
            updateAmountPreview();

            paymentModal.show();
        } catch (error) {
            formAlert.classList.remove('d-none');
            formAlert.innerHTML = error.message || 'Failed to load payment data.';
            paymentModal.show();
        }
    }

    function openDeleteModal(id, name) {
        deletePaymentId = id;
        deletePaymentNameEl.textContent = name || '-';
        setDeleteLoading(false);
        deleteModal.show();
    }

    fields.order_id.addEventListener('change', function () {
        filterScheduleOptionsByOrder();
        updateOrderPreview();

        if (!fields.payment_schedule_id.value) {
            fields.schedule_title_preview.value = '';
            fields.schedule_amount_preview.value = '';
            scheduleAmountText.textContent = 'Rp 0';
        }
    });

    fields.payment_schedule_id.addEventListener('change', function () {
        updateSchedulePreview();
        autoFillAmountFromSchedule();
    });

    fields.amount.addEventListener('input', function () {
        updateAmountPreview();
    });

    paymentForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        clearValidationErrors();
        formAlert.classList.add('d-none');
        formAlert.innerHTML = '';

        const id = fields.id.value;
        const url = id ? `/payments/${id}` : `/payments`;

        const payload = {
            order_id: fields.order_id.value,
            payment_schedule_id: fields.payment_schedule_id.value || null,
            amount: roundCurrency(fields.amount.value || 0),
            payment_date: fields.payment_date.value || null,
            payment_method: fields.payment_method.value.trim(),
            reference_number: fields.reference_number.value.trim(),
            gateway_transaction_id: fields.gateway_transaction_id.value.trim(),
            gateway_provider: fields.gateway_provider.value.trim(),
            status: fields.status.value,
            notes: fields.notes.value.trim(),
        };

        setSubmitLoading(true);

        try {
            const response = await fetch(url, {
                method: id ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
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
                throw new Error(result.message || 'Failed to save payment.');
            }

            paymentModal.hide();
            showToast(result.message || 'Payment saved successfully', 'success');
            scheduleReload();
        } catch (error) {
            if (error.message !== 'Validation failed.') {
                formAlert.classList.remove('d-none');
                formAlert.innerHTML = error.message || 'Something went wrong.';
            }
        } finally {
            setSubmitLoading(false);
        }
    });

    confirmDeleteBtn.addEventListener('click', async function () {
        if (!deletePaymentId) return;

        setDeleteLoading(true);

        try {
            const response = await fetch(`/payments/${deletePaymentId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to delete payment.');
            }

            deleteModal.hide();
            showToast(result.message || 'Payment deleted successfully', 'danger');
            scheduleReload();
        } catch (error) {
            showToast(error.message || 'Failed to delete payment.', 'danger');
        } finally {
            setDeleteLoading(false);
            deletePaymentId = null;
        }
    });

    paymentModalEl.addEventListener('hidden.bs.modal', function () {
        resetForm();
    });

    deleteModalEl.addEventListener('hidden.bs.modal', function () {
        deletePaymentId = null;
        deletePaymentNameEl.textContent = '';
        setDeleteLoading(false);
    });
</script>
@endpush