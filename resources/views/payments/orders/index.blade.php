@extends('layouts.app-dashboard')

@section('title', 'Orders')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="mb-1">Orders</h4>
            <small class="text-muted">Manage student orders and enrollment transactions</small>
        </div>

        <button type="button" class="btn btn-primary" onclick="openCreateModal()">
            <i class="bi bi-plus-lg me-1"></i> Add Order
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
                        <th>Original Price</th>
                        <th>Discount</th>
                        <th>Final Price</th>
                        <th>Status</th>
                        <th style="width: 170px;" class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td>
                                {{ ($orders->currentPage() - 1) * $orders->perPage() + $loop->iteration }}
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $order->student->full_name ?? '-' }}</div>
                                <div class="small text-muted">
                                    {{ $order->student->email ?: ($order->student->phone ?: '-') }}
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $order->batch->program->name ?? '-' }}</div>
                                <div class="small text-muted">{{ $order->batch->name ?? '-' }}</div>
                            </td>
                            <td>Rp {{ number_format((float) $order->original_price, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format((float) $order->discount, 0, ',', '.') }}</td>
                            <td class="fw-semibold">Rp {{ number_format((float) $order->final_price, 0, ',', '.') }}</td>
                            <td>
                                @php
                                    $statusClass = match($order->status) {
                                        'pending' => 'bg-secondary',
                                        'partial' => 'bg-warning text-dark',
                                        'paid' => 'bg-success',
                                        'cancelled' => 'bg-danger',
                                        default => 'bg-secondary',
                                    };
                                @endphp
                                <span class="badge {{ $statusClass }}">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-inline-flex gap-2">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        onclick="editOrder({{ $order->id }})"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="openDeleteModal({{ $order->id }}, @js(($order->student->full_name ?? 'Unknown') . ' - ' . ($order->batch->name ?? '-')))"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                No orders found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($orders->hasPages())
            <div class="card-footer bg-white">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</div>

{{-- Form Modal --}}
<div class="modal fade" id="orderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="orderForm">
            @csrf
            <input type="hidden" id="order_id">

            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title" id="orderModalTitle">Add Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div id="formAlert" class="alert alert-danger d-none mb-3"></div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="student_id" class="form-label">
                                Student <span class="text-danger">*</span>
                            </label>
                            <select id="student_id" class="form-select">
                                <option value="">Select Student</option>
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

                        <div class="col-md-6">
                            <label for="program_name" class="form-label">Program</label>
                            <input type="text" id="program_name" class="form-control" readonly>
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
                                placeholder="e.g. 100000 (Rp)"
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

                        <div class="col-12">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea id="notes" rows="4" class="form-control" placeholder="Internal notes for this order"></textarea>
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
                <h5 class="modal-title">Delete Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <p class="mb-0">
                    Are you sure you want to delete
                    <strong id="deleteOrderName"></strong>?
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
    const orderModalEl = document.getElementById('orderModal');
    const orderModal = new bootstrap.Modal(orderModalEl);
    const orderForm = document.getElementById('orderForm');
    const submitBtn = document.getElementById('submitBtn');
    const modalTitle = document.getElementById('orderModalTitle');
    const formAlert = document.getElementById('formAlert');

    const deleteModalEl = document.getElementById('deleteModal');
    const deleteModal = new bootstrap.Modal(deleteModalEl);
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const deleteOrderNameEl = document.getElementById('deleteOrderName');

    const fields = {
        id: document.getElementById('order_id'),
        student_id: document.getElementById('student_id'),
        batch_id: document.getElementById('batch_id'),
        program_name: document.getElementById('program_name'),
        original_price: document.getElementById('original_price'),
        discount_type: document.getElementById('discount_type'),
        discount: document.getElementById('discount'),
        discount_amount_preview: document.getElementById('discount_amount_preview'),
        final_price: document.getElementById('final_price'),
        status: document.getElementById('status'),
        notes: document.getElementById('notes'),
    };

    const originalPriceText = document.getElementById('original_price_text');
    const discountAmountText = document.getElementById('discount_amount_text');
    const finalPriceText = document.getElementById('final_price_text');
    const discountHelp = document.getElementById('discount_help');

    let deleteOrderId = null;
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

    function formatRupiah(value) {
        const number = Math.round(formatNumber(value));
        return 'Rp ' + number.toLocaleString('id-ID');
    }

    function roundCurrency(value) {
        return Math.round(formatNumber(value));
    }

    function getSelectedBatchPrice() {
        const selectedOption = fields.batch_id.options[fields.batch_id.selectedIndex];
        if (!selectedOption || !selectedOption.value) {
            return 0;
        }

        return roundCurrency(selectedOption.dataset.price || 0);
    }

    function getSelectedProgramName() {
        const selectedOption = fields.batch_id.options[fields.batch_id.selectedIndex];
        if (!selectedOption || !selectedOption.value) {
            return '';
        }

        return selectedOption.dataset.program || '';
    }

    function updateDiscountPlaceholder() {
        if (fields.discount_type.value === 'percentage') {
            fields.discount.placeholder = 'e.g. 10 (%)';
            discountHelp.textContent = 'Enter discount in percentage. Example: 10 = 10%.';
        } else {
            fields.discount.placeholder = 'e.g. 100000 (Rp)';
            discountHelp.textContent = 'Enter discount in rupiah.';
        }
    }

    function calculateDiscountAmount(price, discountValue, discountType) {
        let discountAmount = 0;

        if (discountType === 'percentage') {
            discountAmount = Math.round(price * (discountValue / 100));
        } else {
            discountAmount = roundCurrency(discountValue);
        }

        if (discountAmount < 0) {
            discountAmount = 0;
        }

        if (discountAmount > price) {
            discountAmount = price;
        }

        return discountAmount;
    }

    function updatePricePreview() {
        const price = getSelectedBatchPrice();
        const program = getSelectedProgramName();
        const discountValue = formatNumber(fields.discount.value);
        const discountType = fields.discount_type.value;

        const discountAmount = calculateDiscountAmount(price, discountValue, discountType);
        const finalPrice = Math.max(price - discountAmount, 0);

        fields.program_name.value = program;
        fields.original_price.value = price;
        fields.discount_amount_preview.value = discountAmount;
        fields.final_price.value = finalPrice;

        originalPriceText.textContent = formatRupiah(price);
        discountAmountText.textContent = formatRupiah(discountAmount);
        finalPriceText.textContent = formatRupiah(finalPrice);
    }

    function resetForm() {
        orderForm.reset();
        fields.id.value = '';
        fields.student_id.value = '';
        fields.batch_id.value = '';
        fields.program_name.value = '';
        fields.original_price.value = '';
        fields.discount_type.value = 'amount';
        fields.discount.value = 0;
        fields.discount_amount_preview.value = '';
        fields.final_price.value = '';
        fields.status.value = 'pending';
        fields.notes.value = '';
        formAlert.classList.add('d-none');
        formAlert.innerHTML = '';
        originalPriceText.textContent = 'Rp 0';
        discountAmountText.textContent = 'Rp 0';
        finalPriceText.textContent = 'Rp 0';
        updateDiscountPlaceholder();
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
            'student_id',
            'batch_id',
            'original_price',
            'discount',
            'final_price',
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
        modalTitle.textContent = 'Add Order';
        updatePricePreview();
        orderModal.show();
    }

    async function editOrder(id) {
        resetForm();
        modalTitle.textContent = 'Edit Order';

        try {
            const response = await fetch(`/payments/orders/${id}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to fetch order data.');
            }

            const data = result.data;

            fields.id.value = data.id ?? '';
            fields.student_id.value = data.student_id ?? '';
            fields.batch_id.value = data.batch_id ?? '';
            fields.status.value = data.status ?? 'pending';
            fields.notes.value = data.notes ?? '';

            const originalPrice = roundCurrency(data.original_price ?? 0);
            const discountAmount = roundCurrency(data.discount ?? 0);
            const finalPrice = roundCurrency(data.final_price ?? 0);

            fields.discount_type.value = 'amount';
            fields.discount.value = discountAmount;

            updateDiscountPlaceholder();
            updatePricePreview();

            fields.original_price.value = originalPrice;
            fields.discount_amount_preview.value = discountAmount;
            fields.final_price.value = finalPrice;

            originalPriceText.textContent = formatRupiah(originalPrice);
            discountAmountText.textContent = formatRupiah(discountAmount);
            finalPriceText.textContent = formatRupiah(finalPrice);

            if (data.batch && data.batch.program) {
                fields.program_name.value = data.batch.program.name ?? '';
            }

            orderModal.show();
        } catch (error) {
            formAlert.classList.remove('d-none');
            formAlert.innerHTML = error.message || 'Failed to load order data.';
            orderModal.show();
        }
    }

    function openDeleteModal(id, name) {
        deleteOrderId = id;
        deleteOrderNameEl.textContent = name || '-';
        setDeleteLoading(false);
        deleteModal.show();
    }

    fields.batch_id.addEventListener('change', function () {
        updatePricePreview();
    });

    fields.discount_type.addEventListener('change', function () {
        updateDiscountPlaceholder();
        updatePricePreview();
    });

    fields.discount.addEventListener('input', function () {
        updatePricePreview();
    });

    orderForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        clearValidationErrors();
        formAlert.classList.add('d-none');
        formAlert.innerHTML = '';

        const id = fields.id.value;
        const url = id ? `/payments/orders/${id}` : `/payments/orders`;

        const originalPrice = getSelectedBatchPrice();
        const discountValue = formatNumber(fields.discount.value);
        const discountType = fields.discount_type.value;
        const discountAmount = calculateDiscountAmount(originalPrice, discountValue, discountType);

        const payload = {
            student_id: fields.student_id.value,
            batch_id: fields.batch_id.value,
            discount: discountAmount,
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
                throw new Error(result.message || 'Failed to save order.');
            }

            orderModal.hide();
            showToast(result.message || 'Order saved successfully', 'success');
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
        if (!deleteOrderId) return;

        setDeleteLoading(true);

        try {
            const response = await fetch(`/payments/orders/${deleteOrderId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to delete order.');
            }

            deleteModal.hide();
            showToast(result.message || 'Order deleted successfully', 'danger');
            scheduleReload();
        } catch (error) {
            showToast(error.message || 'Failed to delete order.', 'danger');
        } finally {
            setDeleteLoading(false);
            deleteOrderId = null;
        }
    });

    orderModalEl.addEventListener('hidden.bs.modal', function () {
        resetForm();
    });

    deleteModalEl.addEventListener('hidden.bs.modal', function () {
        deleteOrderId = null;
        deleteOrderNameEl.textContent = '';
        setDeleteLoading(false);
    });
</script>
@endpush