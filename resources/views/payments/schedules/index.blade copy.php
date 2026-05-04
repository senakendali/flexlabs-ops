@extends('layouts.app-dashboard')

@section('title', 'Payment Schedules')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="mb-1">Payment Schedules</h4>
            <small class="text-muted">Manage payment plans and due dates for each order</small>
        </div>

        <button type="button" class="btn btn-primary" onclick="openCreateModal()">
            <i class="bi bi-plus-lg me-1"></i> Add Schedule
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
                        <th>Student</th>
                        <th>Program / Batch</th>
                        <th>Title</th>
                        <th>Amount</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th style="width: 170px;" class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($paymentSchedules as $schedule)
                        <tr>
                            <td>
                                {{ ($paymentSchedules->currentPage() - 1) * $paymentSchedules->perPage() + $loop->iteration }}
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $schedule->order->student->full_name ?? '-' }}</div>
                                <div class="small text-muted">
                                    {{ $schedule->order->student->email ?: ($schedule->order->student->phone ?: '-') }}
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $schedule->order->batch->program->name ?? '-' }}</div>
                                <div class="small text-muted">{{ $schedule->order->batch->name ?? '-' }}</div>
                            </td>
                            <td>{{ $schedule->title }}</td>
                            <td class="fw-semibold">Rp {{ number_format((float) $schedule->amount, 0, ',', '.') }}</td>
                            <td>{{ $schedule->due_date?->format('d M Y') ?: '-' }}</td>
                            <td>
                                @php
                                    $statusClass = match($schedule->status) {
                                        'pending' => 'bg-secondary',
                                        'paid' => 'bg-success',
                                        'overdue' => 'bg-warning text-dark',
                                        'cancelled' => 'bg-danger',
                                        default => 'bg-secondary',
                                    };
                                @endphp
                                <span class="badge {{ $statusClass }}">
                                    {{ ucfirst($schedule->status) }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-inline-flex gap-2">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        onclick="editSchedule({{ $schedule->id }})"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="openDeleteModal({{ $schedule->id }}, @js(($schedule->order->student->full_name ?? 'Unknown') . ' - ' . $schedule->title))"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                No payment schedules found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($paymentSchedules->hasPages())
            <div class="card-footer bg-white">
                {{ $paymentSchedules->links() }}
            </div>
        @endif
    </div>
</div>

{{-- Form Modal --}}
<div class="modal fade" id="scheduleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="scheduleForm">
            @csrf
            <input type="hidden" id="schedule_id">

            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title" id="scheduleModalTitle">Add Schedule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div id="formAlert" class="alert alert-danger d-none mb-3"></div>
                    <div id="amountWarning" class="alert alert-warning d-none mb-3"></div>

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
                                        data-scheduled="{{ (int) round((float) ($order->payment_schedules_sum_amount ?? 0)) }}"
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
                            <label for="total_scheduled" class="form-label">Total Scheduled</label>
                            <input type="number" id="total_scheduled" class="form-control" readonly>
                            <div class="form-text" id="total_scheduled_text">Rp 0</div>
                        </div>

                        <div class="col-md-4">
                            <label for="remaining_balance" class="form-label">Remaining Balance</label>
                            <input type="number" id="remaining_balance" class="form-control" readonly>
                            <div class="form-text fw-semibold" id="remaining_balance_text">Rp 0</div>
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
                            <label for="due_date" class="form-label">Due Date</label>
                            <input type="date" id="due_date" class="form-control">
                            <div class="invalid-feedback" id="error_due_date"></div>
                        </div>

                        <div class="col-12">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea
                                id="notes"
                                rows="4"
                                class="form-control"
                                placeholder="Internal notes for this payment schedule"
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
                <h5 class="modal-title">Delete Payment Schedule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <p class="mb-0">
                    Are you sure you want to delete
                    <strong id="deleteScheduleName"></strong>?
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
    const scheduleModalEl = document.getElementById('scheduleModal');
    const scheduleModal = new bootstrap.Modal(scheduleModalEl);
    const scheduleForm = document.getElementById('scheduleForm');
    const submitBtn = document.getElementById('submitBtn');
    const modalTitle = document.getElementById('scheduleModalTitle');
    const formAlert = document.getElementById('formAlert');
    const amountWarning = document.getElementById('amountWarning');

    const deleteModalEl = document.getElementById('deleteModal');
    const deleteModal = new bootstrap.Modal(deleteModalEl);
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const deleteScheduleNameEl = document.getElementById('deleteScheduleName');

    const fields = {
        id: document.getElementById('schedule_id'),
        order_id: document.getElementById('order_id'),
        student_name: document.getElementById('student_name'),
        program_name: document.getElementById('program_name'),
        batch_name: document.getElementById('batch_name'),
        order_total: document.getElementById('order_total'),
        total_scheduled: document.getElementById('total_scheduled'),
        remaining_balance: document.getElementById('remaining_balance'),
        title: document.getElementById('title'),
        amount: document.getElementById('amount'),
        due_date: document.getElementById('due_date'),
        status: document.getElementById('status'),
        notes: document.getElementById('notes'),
    };

    const orderTotalText = document.getElementById('order_total_text');
    const totalScheduledText = document.getElementById('total_scheduled_text');
    const remainingBalanceText = document.getElementById('remaining_balance_text');
    const amountText = document.getElementById('amount_text');

    let deleteScheduleId = null;
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

    function getCurrentAmount() {
        return roundCurrency(fields.amount.value || 0);
    }

    function getBaseScheduledAmount() {
        const selectedOption = getSelectedOrderOption();

        if (!selectedOption || !selectedOption.value) {
            return 0;
        }

        const baseScheduled = roundCurrency(selectedOption.dataset.scheduled || 0);

        if (fields.id.value) {
            return Math.max(baseScheduled - getCurrentAmount(), 0);
        }

        return baseScheduled;
    }

    function updateOrderPreview() {
        const selectedOption = getSelectedOrderOption();

        if (!selectedOption || !selectedOption.value) {
            fields.student_name.value = '';
            fields.program_name.value = '';
            fields.batch_name.value = '';
            fields.order_total.value = '';
            fields.total_scheduled.value = '';
            fields.remaining_balance.value = '';
            orderTotalText.textContent = 'Rp 0';
            totalScheduledText.textContent = 'Rp 0';
            remainingBalanceText.textContent = 'Rp 0';
            return;
        }

        const total = roundCurrency(selectedOption.dataset.total || 0);
        const currentAmount = getCurrentAmount();
        const baseScheduled = getBaseScheduledAmount();
        const totalScheduled = baseScheduled + currentAmount;
        const remaining = Math.max(total - totalScheduled, 0);

        fields.student_name.value = selectedOption.dataset.student || '';
        fields.program_name.value = selectedOption.dataset.program || '';
        fields.batch_name.value = selectedOption.dataset.batch || '';
        fields.order_total.value = total;
        fields.total_scheduled.value = totalScheduled;
        fields.remaining_balance.value = remaining;

        orderTotalText.textContent = formatRupiah(total);
        totalScheduledText.textContent = formatRupiah(totalScheduled);
        remainingBalanceText.textContent = formatRupiah(remaining);
    }

    function updateAmountPreview() {
        amountText.textContent = formatRupiah(fields.amount.value || 0);
        updateOrderPreview();
        validateAmountAgainstRemaining();
    }

    function validateAmountAgainstRemaining() {
        amountWarning.classList.add('d-none');
        amountWarning.innerHTML = '';

        const selectedOption = getSelectedOrderOption();
        if (!selectedOption || !selectedOption.value) {
            return true;
        }

        const total = roundCurrency(selectedOption.dataset.total || 0);
        const currentAmount = getCurrentAmount();
        const baseScheduled = getBaseScheduledAmount();

        if ((baseScheduled + currentAmount) > total) {
            const overBy = (baseScheduled + currentAmount) - total;
            amountWarning.classList.remove('d-none');
            amountWarning.innerHTML = `
                Schedule amount exceeds remaining balance by <strong>${formatRupiah(overBy)}</strong>.
            `;
            return false;
        }

        return true;
    }

    function resetForm() {
        scheduleForm.reset();
        fields.id.value = '';
        fields.order_id.value = '';
        fields.student_name.value = '';
        fields.program_name.value = '';
        fields.batch_name.value = '';
        fields.order_total.value = '';
        fields.total_scheduled.value = '';
        fields.remaining_balance.value = '';
        fields.title.value = '';
        fields.amount.value = '';
        fields.due_date.value = '';
        fields.status.value = 'pending';
        fields.notes.value = '';

        orderTotalText.textContent = 'Rp 0';
        totalScheduledText.textContent = 'Rp 0';
        remainingBalanceText.textContent = 'Rp 0';
        amountText.textContent = 'Rp 0';

        formAlert.classList.add('d-none');
        formAlert.innerHTML = '';
        amountWarning.classList.add('d-none');
        amountWarning.innerHTML = '';

        clearValidationErrors();
        setSubmitLoading(false);
    }

    function clearValidationErrors() {
        Object.values(fields).forEach(field => {
            if (field && field.classList) {
                field.classList.remove('is-invalid');
            }
        });

        ['order_id', 'title', 'amount', 'due_date', 'status', 'notes'].forEach(key => {
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
        modalTitle.textContent = 'Add Schedule';
        updateOrderPreview();
        updateAmountPreview();
        scheduleModal.show();
    }

    async function editSchedule(id) {
        resetForm();
        modalTitle.textContent = 'Edit Schedule';

        try {
            const response = await fetch(`/payments/schedules/${id}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to fetch payment schedule data.');
            }

            const data = result.data;

            fields.id.value = data.id ?? '';
            fields.order_id.value = data.order_id ?? '';
            fields.title.value = data.title ?? '';
            fields.amount.value = roundCurrency(data.amount ?? 0);
            fields.due_date.value = data.due_date ?? '';
            fields.status.value = data.status ?? 'pending';
            fields.notes.value = data.notes ?? '';

            updateOrderPreview();
            updateAmountPreview();

            scheduleModal.show();
        } catch (error) {
            formAlert.classList.remove('d-none');
            formAlert.innerHTML = error.message || 'Failed to load payment schedule data.';
            scheduleModal.show();
        }
    }

    function openDeleteModal(id, name) {
        deleteScheduleId = id;
        deleteScheduleNameEl.textContent = name || '-';
        setDeleteLoading(false);
        deleteModal.show();
    }

    fields.order_id.addEventListener('change', function () {
        updateOrderPreview();
        validateAmountAgainstRemaining();
    });

    fields.amount.addEventListener('input', function () {
        updateAmountPreview();
    });

    scheduleForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        clearValidationErrors();
        formAlert.classList.add('d-none');
        formAlert.innerHTML = '';

        if (!validateAmountAgainstRemaining()) {
            return;
        }

        const id = fields.id.value;
        const url = id ? `/payments/schedules/${id}` : `/payments/schedules`;

        const payload = {
            order_id: fields.order_id.value,
            title: fields.title.value.trim(),
            amount: roundCurrency(fields.amount.value || 0),
            due_date: fields.due_date.value || null,
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
                throw new Error(result.message || 'Failed to save payment schedule.');
            }

            scheduleModal.hide();
            showToast(result.message || 'Payment schedule saved successfully', 'success');
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
        if (!deleteScheduleId) return;

        setDeleteLoading(true);

        try {
            const response = await fetch(`/payments/schedules/${deleteScheduleId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to delete payment schedule.');
            }

            deleteModal.hide();
            showToast(result.message || 'Payment schedule deleted successfully', 'danger');
            scheduleReload();
        } catch (error) {
            showToast(error.message || 'Failed to delete payment schedule.', 'danger');
        } finally {
            setDeleteLoading(false);
            deleteScheduleId = null;
        }
    });

    scheduleModalEl.addEventListener('hidden.bs.modal', function () {
        resetForm();
    });

    deleteModalEl.addEventListener('hidden.bs.modal', function () {
        deleteScheduleId = null;
        deleteScheduleNameEl.textContent = '';
        setDeleteLoading(false);
    });
</script>
@endpush