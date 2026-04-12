@extends('layouts.app-dashboard')

@section('title', 'Gear Borrowing')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="mb-1">Gear Borrowing</h4>
            <small class="text-muted">View available equipment from master data and manage your borrowing activity</small>
        </div>

        <button type="button" class="btn btn-primary" onclick="openBorrowModal()">
            <i class="bi bi-plus-lg me-1"></i> Borrow Equipment
        </button>
    </div>

    <div
        id="toastContainer"
        class="toast-container position-fixed top-0 end-0 p-3"
        style="z-index: 9999;"
    ></div>

    <div class="card shadow-sm border-0">
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('borrowings.index') }}" class="d-flex align-items-center gap-2">
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
                        <th>Name</th>
                        <th>Code</th>
                        <th>Brand / Model</th>
                        <th>Condition</th>
                        <th>Status</th>
                        <th>Borrower</th>
                        <th style="width: 170px;" class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($equipments as $equipment)
                        @php
                            $activeBorrowing = $equipment->activeBorrowing;
                            $isBorrowedByCurrentUser = $activeBorrowing
                                && $activeBorrowing->user_id === auth()->id();
                        @endphp
                        <tr>
                            <td>
                                {{ ($equipments->currentPage() - 1) * $equipments->perPage() + $loop->iteration }}
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $equipment->name }}</div>
                                @if ($equipment->description)
                                    <small class="text-muted">{{ \Illuminate\Support\Str::limit($equipment->description, 50) }}</small>
                                @endif
                            </td>
                            <td><code>{{ $equipment->code }}</code></td>
                            <td>
                                <div>{{ $equipment->brand ?: '-' }}</div>
                                <small class="text-muted">{{ $equipment->model ?: '-' }}</small>
                            </td>
                            <td>
                                @if ($equipment->condition === 'good')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                        Good
                                    </span>
                                @elseif ($equipment->condition === 'minor_damage')
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                        Minor Damage
                                    </span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                        Damaged
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if ($equipment->status === 'available')
                                    <span class="badge bg-success">Available</span>
                                @elseif ($equipment->status === 'borrowed')
                                    <span class="badge bg-primary">Borrowed</span>
                                @else
                                    <span class="badge bg-secondary">Maintenance</span>
                                @endif
                            </td>
                            <td>
                                @if ($activeBorrowing && $activeBorrowing->user)
                                    <div class="fw-semibold">
                                        {{ $activeBorrowing->user->name }}
                                        @if ($isBorrowedByCurrentUser)
                                            <span class="badge bg-info text-dark ms-1">You</span>
                                        @endif
                                    </div>
                                    <small class="text-muted">
                                        {{ $activeBorrowing->borrowed_at?->format('d M Y H:i') }}
                                    </small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-inline-flex gap-2">
                                    @if ($equipment->status === 'available')
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-success"
                                            onclick="openBorrowModal({{ $equipment->id }})"
                                        >
                                            <i class="bi bi-box-arrow-right"></i>
                                        </button>
                                    @endif

                                    @if ($equipment->status === 'borrowed' && $activeBorrowing)
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            onclick="viewBorrowing({{ $activeBorrowing->id }})"
                                        >
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    @endif

                                    @if ($equipment->status === 'borrowed' && $activeBorrowing && $isBorrowedByCurrentUser)
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-warning"
                                            onclick="openReturnModal(
                                                {{ $activeBorrowing->id }},
                                                @js($equipment->name),
                                                @js($activeBorrowing->user->name ?? '-')
                                            )"
                                        >
                                            <i class="bi bi-box-arrow-in-left"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                No equipment found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($equipments->hasPages())
            <div class="card-footer bg-white">
                {{ $equipments->links() }}
            </div>
        @endif
    </div>
</div>

{{-- Borrow Modal --}}
<div class="modal fade gear-modal" id="borrowModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <form id="borrowForm" class="w-100">
            @csrf

            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title">Borrow Equipment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div id="borrowFormAlert" class="alert alert-danger d-none mb-3"></div>

                    <div class="mb-3">
                        <label for="equipment_id" class="form-label">
                            Equipment <span class="text-danger">*</span>
                        </label>
                        <select id="equipment_id" class="form-select">
                            <option value="">Select Equipment</option>
                            @foreach ($equipments->getCollection()->where('status', 'available')->where('is_active', true) as $equipment)
                                <option value="{{ $equipment->id }}">
                                    {{ $equipment->name }} ({{ $equipment->code }})
                                    @if ($equipment->brand || $equipment->model)
                                        - {{ trim(($equipment->brand ?? '') . ' ' . ($equipment->model ?? '')) }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="error_equipment_id"></div>
                    </div>

                    <div class="mb-3">
                        <label for="borrowed_at" class="form-label">Borrowed At</label>
                        <input type="datetime-local" id="borrowed_at" class="form-control">
                        <div class="invalid-feedback" id="error_borrowed_at"></div>
                    </div>

                    <div class="mb-3">
                        <label for="expected_return_at" class="form-label">Expected Return</label>
                        <input type="datetime-local" id="expected_return_at" class="form-control">
                        <div class="invalid-feedback" id="error_expected_return_at"></div>
                    </div>

                    <div class="mb-0">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea id="notes" rows="4" class="form-control"></textarea>
                        <div class="invalid-feedback" id="error_notes"></div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" id="borrowSubmitBtn">
                        <span class="default-borrow-text">Save</span>
                        <span class="loading-borrow-text d-none">Saving...</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Borrowing Detail Modal --}}
<div class="modal fade gear-modal" id="detailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title">Borrowing Detail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div id="detailAlert" class="alert alert-danger d-none mb-3"></div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted">Equipment</label>
                        <div class="fw-semibold" id="detail_equipment">-</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-muted">Borrower</label>
                        <div class="fw-semibold" id="detail_user">-</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-muted">Borrowed At</label>
                        <div id="detail_borrowed_at">-</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-muted">Expected Return</label>
                        <div id="detail_expected_return_at">-</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-muted">Returned At</label>
                        <div id="detail_returned_at">-</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-muted">Status</label>
                        <div id="detail_status">-</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label text-muted">Notes</label>
                        <div id="detail_notes">-</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label text-muted">Return Notes</label>
                        <div id="detail_return_notes">-</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Return Modal --}}
<div class="modal fade gear-modal" id="returnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <form id="returnForm" class="w-100">
            @csrf
            <input type="hidden" id="return_borrowing_id">

            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title">Return Equipment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div id="returnFormAlert" class="alert alert-danger d-none mb-3"></div>

                    <div class="mb-3">
                        <label for="return_equipment_name" class="form-label">Equipment</label>
                        <input type="text" id="return_equipment_name" class="form-control" readonly>
                    </div>

                    <div class="mb-3">
                        <label for="return_borrower_name" class="form-label">Borrower</label>
                        <input type="text" id="return_borrower_name" class="form-control" readonly>
                    </div>

                    <div class="mb-3">
                        <label for="returned_at" class="form-label">Returned At</label>
                        <input type="datetime-local" id="returned_at" class="form-control">
                        <div class="invalid-feedback" id="error_returned_at"></div>
                    </div>

                    <div class="mb-0">
                        <label for="return_notes" class="form-label">Notes</label>
                        <textarea id="return_notes" rows="4" class="form-control"></textarea>
                        <div class="invalid-feedback" id="error_return_notes"></div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" id="returnSubmitBtn">
                        <span class="default-return-text">Save</span>
                        <span class="loading-return-text d-none">Saving...</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
<style>
    .gear-modal {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }

    .gear-modal.show {
        display: flex !important;
        align-items: center;
        justify-content: center;
    }

    .gear-modal .modal-dialog {
        margin: 0 auto !important;
        width: min(1140px, calc(100vw - 2rem));
        max-width: min(1140px, calc(100vw - 2rem));
        position: relative;
        left: auto !important;
        right: auto !important;
        transform: none !important;
    }

    .gear-modal .modal-content {
        width: 100%;
    }

    body.modal-open {
        overflow: hidden;
    }

    @media (max-width: 767.98px) {
        .gear-modal .modal-dialog {
            width: calc(100vw - 1rem);
            max-width: calc(100vw - 1rem);
        }
    }
</style>
@endpush

@push('scripts')
<script>
    const borrowModalEl = document.getElementById('borrowModal');
    const borrowModal = new bootstrap.Modal(borrowModalEl);
    const borrowForm = document.getElementById('borrowForm');
    const borrowFormAlert = document.getElementById('borrowFormAlert');
    const borrowSubmitBtn = document.getElementById('borrowSubmitBtn');

    const detailModalEl = document.getElementById('detailModal');
    const detailModal = new bootstrap.Modal(detailModalEl);
    const detailAlert = document.getElementById('detailAlert');

    const returnModalEl = document.getElementById('returnModal');
    const returnModal = new bootstrap.Modal(returnModalEl);
    const returnForm = document.getElementById('returnForm');
    const returnFormAlert = document.getElementById('returnFormAlert');
    const returnSubmitBtn = document.getElementById('returnSubmitBtn');

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

        const closeBtnClass = type === 'warning' || type === 'info'
            ? 'btn-close'
            : 'btn-close btn-close-white';

        const html = `
            <div id="${id}" class="toast align-items-center text-white ${bgClass} border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="${closeBtnClass} me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', html);

        const toastEl = document.getElementById(id);
        const toast = new bootstrap.Toast(toastEl, { delay: 3000 });

        toast.show();

        toastEl.addEventListener('hidden.bs.toast', () => {
            toastEl.remove();
        });
    }

    function scheduleReload() {
        if (reloadTimeout) {
            clearTimeout(reloadTimeout);
        }

        reloadTimeout = setTimeout(() => {
            location.reload();
        }, 1500);
    }

    function toDatetimeLocalValue(date = new Date()) {
        const offset = date.getTimezoneOffset();
        const localDate = new Date(date.getTime() - (offset * 60000));
        return localDate.toISOString().slice(0, 16);
    }

    function formatDateTime(value) {
        if (!value) return '-';

        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return value;

        return new Intl.DateTimeFormat('en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        }).format(date);
    }

    function clearValidationErrors() {
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        document.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
    }

    function setValidationErrors(errors = {}) {
        clearValidationErrors();

        Object.keys(errors).forEach(key => {
            const field = document.getElementById(key);
            const errorEl = document.getElementById(`error_${key}`);

            if (field && field.classList) {
                field.classList.add('is-invalid');
            }

            if (errorEl) {
                errorEl.textContent = Array.isArray(errors[key]) ? errors[key][0] : errors[key];
            }
        });
    }

    function setBorrowLoading(isLoading) {
        borrowSubmitBtn.disabled = isLoading;
        borrowSubmitBtn.querySelector('.default-borrow-text').classList.toggle('d-none', isLoading);
        borrowSubmitBtn.querySelector('.loading-borrow-text').classList.toggle('d-none', !isLoading);
    }

    function setReturnLoading(isLoading) {
        returnSubmitBtn.disabled = isLoading;
        returnSubmitBtn.querySelector('.default-return-text').classList.toggle('d-none', isLoading);
        returnSubmitBtn.querySelector('.loading-return-text').classList.toggle('d-none', !isLoading);
    }

    function resetBorrowForm(selectedEquipmentId = '') {
        borrowForm.reset();
        document.getElementById('equipment_id').value = selectedEquipmentId;
        document.getElementById('borrowed_at').value = toDatetimeLocalValue();
        borrowFormAlert.classList.add('d-none');
        borrowFormAlert.innerHTML = '';
        clearValidationErrors();
        setBorrowLoading(false);
    }

    function resetReturnForm() {
        returnForm.reset();
        document.getElementById('return_borrowing_id').value = '';
        document.getElementById('return_equipment_name').value = '';
        document.getElementById('return_borrower_name').value = '';
        document.getElementById('returned_at').value = toDatetimeLocalValue();
        returnFormAlert.classList.add('d-none');
        returnFormAlert.innerHTML = '';
        clearValidationErrors();
        setReturnLoading(false);
    }

    function openBorrowModal(equipmentId = '') {
        resetBorrowForm(String(equipmentId));
        borrowModal.show();
    }

    async function viewBorrowing(id) {
        detailAlert.classList.add('d-none');
        detailAlert.innerHTML = '';

        document.getElementById('detail_equipment').textContent = '-';
        document.getElementById('detail_user').textContent = '-';
        document.getElementById('detail_borrowed_at').textContent = '-';
        document.getElementById('detail_expected_return_at').textContent = '-';
        document.getElementById('detail_returned_at').textContent = '-';
        document.getElementById('detail_status').textContent = '-';
        document.getElementById('detail_notes').textContent = '-';
        document.getElementById('detail_return_notes').textContent = '-';

        detailModal.show();

        try {
            const response = await fetch(`/borrowings/${id}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to fetch borrowing detail.');
            }

            const data = result.data;

            document.getElementById('detail_equipment').textContent =
                data.equipment ? `${data.equipment.name} (${data.equipment.code ?? '-'})` : '-';

            document.getElementById('detail_user').textContent =
                data.user ? `${data.user.name}${data.user.email ? ' - ' + data.user.email : ''}` : '-';

            document.getElementById('detail_borrowed_at').textContent = formatDateTime(data.borrowed_at);
            document.getElementById('detail_expected_return_at').textContent = formatDateTime(data.expected_return_at);
            document.getElementById('detail_returned_at').textContent = formatDateTime(data.returned_at);
            document.getElementById('detail_status').textContent = data.status ?? '-';
            document.getElementById('detail_notes').textContent = data.notes ?? '-';
            document.getElementById('detail_return_notes').textContent = data.return_notes ?? '-';
        } catch (error) {
            detailAlert.classList.remove('d-none');
            detailAlert.innerHTML = error.message || 'Failed to load detail.';
        }
    }

    function openReturnModal(id, equipmentName, borrowerName) {
        resetReturnForm();
        document.getElementById('return_borrowing_id').value = id;
        document.getElementById('return_equipment_name').value = equipmentName || '-';
        document.getElementById('return_borrower_name').value = borrowerName || '-';
        returnModal.show();
    }

    borrowForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        clearValidationErrors();
        borrowFormAlert.classList.add('d-none');
        borrowFormAlert.innerHTML = '';

        const payload = {
            equipment_id: document.getElementById('equipment_id').value,
            borrowed_at: document.getElementById('borrowed_at').value,
            expected_return_at: document.getElementById('expected_return_at').value,
            notes: document.getElementById('notes').value.trim(),
        };

        setBorrowLoading(true);

        try {
            const response = await fetch(`/borrowings`, {
                method: 'POST',
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
                throw new Error(result.message || 'Failed to borrow equipment.');
            }

            borrowModal.hide();
            showToast(result.message || 'Equipment borrowed successfully', 'success');
            scheduleReload();
        } catch (error) {
            if (error.message !== 'Validation failed.') {
                borrowFormAlert.classList.remove('d-none');
                borrowFormAlert.innerHTML = error.message || 'Something went wrong.';
            }
        } finally {
            setBorrowLoading(false);
        }
    });

    returnForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        clearValidationErrors();
        returnFormAlert.classList.add('d-none');
        returnFormAlert.innerHTML = '';

        const borrowingId = document.getElementById('return_borrowing_id').value;

        const payload = {
            returned_at: document.getElementById('returned_at').value,
            return_notes: document.getElementById('return_notes').value.trim(),
        };

        setReturnLoading(true);

        try {
            const response = await fetch(`/borrowings/${borrowingId}/return`, {
                method: 'POST',
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
                throw new Error(result.message || 'Failed to return equipment.');
            }

            returnModal.hide();
            showToast(result.message || 'Equipment returned successfully', 'success');
            scheduleReload();
        } catch (error) {
            if (error.message !== 'Validation failed.') {
                returnFormAlert.classList.remove('d-none');
                returnFormAlert.innerHTML = error.message || 'Something went wrong.';
            }
        } finally {
            setReturnLoading(false);
        }
    });

    borrowModalEl.addEventListener('hidden.bs.modal', function () {
        resetBorrowForm();
    });

    returnModalEl.addEventListener('hidden.bs.modal', function () {
        resetReturnForm();
    });
</script>
@endpush