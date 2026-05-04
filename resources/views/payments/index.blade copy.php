@extends('layouts.app-dashboard')

@section('title', 'Payments')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="mb-1">Payments</h4>
            <small class="text-muted">Manage payment records and payment confirmations</small>
        </div>

        <button type="button" class="btn btn-primary" onclick="openCreateModal()">
            <i class="bi bi-plus-lg me-1"></i> Add Payment
        </button>
    </div>

    <div
        id="toastContainer"
        class="toast-container position-fixed top-0 end-0 p-3"
        style="z-index: 9999;"
    ></div>

    <div class="card shadow-sm border-0">
        <div class="card-body border-bottom">
            <form method="GET" class="d-flex align-items-center gap-2">
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

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 80px;">No</th>
                        <th>Invoice</th>
                        <th>Student</th>
                        <th>Program / Batch</th>
                        <th>Schedule</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th style="width: 220px;" class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr>
                            <td>
                                {{ ($payments->currentPage() - 1) * $payments->perPage() + $loop->iteration }}
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $payment->invoice_number }}</div>
                                <div class="small text-muted">
                                    {{ $payment->reference_number ?: '-' }}
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $payment->order->student->full_name ?? '-' }}</div>
                                <div class="small text-muted">
                                    {{ $payment->order->student->email ?: ($payment->order->student->phone ?: '-') }}
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $payment->order->batch->program->name ?? '-' }}</div>
                                <div class="small text-muted">{{ $payment->order->batch->name ?? '-' }}</div>
                            </td>
                            <td>
                                @if ($payment->paymentSchedule)
                                    <div class="fw-semibold">{{ $payment->paymentSchedule->title }}</div>
                                    <div class="small text-muted">
                                        Rp {{ number_format((float) $payment->paymentSchedule->amount, 0, ',', '.') }}
                                    </div>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="fw-semibold">
                                Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}
                            </td>
                            <td>{{ $payment->payment_method ?: '-' }}</td>
                            <td>{{ $payment->payment_date?->format('d M Y') ?: '-' }}</td>
                            <td>
                                @php
                                    $statusClass = match($payment->status) {
                                        'pending' => 'bg-secondary',
                                        'paid' => 'bg-success',
                                        'failed' => 'bg-danger',
                                        'expired' => 'bg-warning text-dark',
                                        'cancelled' => 'bg-dark',
                                        default => 'bg-secondary',
                                    };
                                @endphp
                                <span class="badge {{ $statusClass }}">
                                    {{ ucfirst($payment->status) }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-inline-flex gap-2 flex-wrap justify-content-center">
                                    @if ($payment->invoice_number)
                                        <a
                                            href="{{ route('payments.invoice', $payment->id) }}"
                                            class="btn btn-sm btn-outline-secondary"
                                            title="View Invoice"
                                        >
                                            <i class="bi bi-receipt me-1"></i> Invoice
                                        </a>
                                    @endif

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        onclick="editPayment({{ $payment->id }})"
                                        title="Edit Payment"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="openDeleteModal({{ $payment->id }}, @js($payment->invoice_number))"
                                        title="Delete Payment"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">
                                No payments found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($payments->hasPages())
            <div class="card-footer bg-white">
                {{ $payments->links() }}
            </div>
        @endif
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
                    <h5 class="modal-title" id="paymentModalTitle">Add Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div id="formAlert" class="alert alert-danger d-none mb-3"></div>

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
                            <div class="form-text">Optional. Choose a schedule if this payment is for a specific installment.</div>
                            <div class="invalid-feedback" id="error_payment_schedule_id"></div>
                        </div>

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

                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <span class="default-text">Save</span>
                        <span class="loading-text d-none">Saving...</span>
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
                <h5 class="modal-title">Delete Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <p class="mb-0">
                    Are you sure you want to delete
                    <strong id="deletePaymentName"></strong>?
                </p>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                    Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <span class="default-delete-text">Delete</span>
                    <span class="loading-delete-text d-none">Deleting...</span>
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