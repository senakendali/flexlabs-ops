@extends('layouts.app-dashboard')

@section('title', 'Gear Borrowing')

@section('content')
@php
    $conditionBadgeClass = function ($condition) {
        return match($condition) {
            'good' => 'bg-success-subtle text-success-emphasis border border-success-subtle',
            'minor_damage' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
            'damaged' => 'bg-danger-subtle text-danger-emphasis border border-danger-subtle',
            default => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
        };
    };

    $statusBadgeClass = function ($status) {
        return match($status) {
            'available' => 'bg-success-subtle text-success-emphasis border border-success-subtle',
            'borrowed' => 'bg-primary-subtle text-primary-emphasis border border-primary-subtle',
            'maintenance' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
            default => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
        };
    };

    $formatCondition = function ($condition) {
        return match($condition) {
            'good' => 'Good',
            'minor_damage' => 'Minor Damage',
            'damaged' => 'Damaged',
            default => ucfirst((string) $condition),
        };
    };

    $formatStatus = function ($status) {
        return match($status) {
            'available' => 'Available',
            'borrowed' => 'Borrowed',
            'maintenance' => 'Maintenance',
            default => ucfirst((string) $status),
        };
    };
@endphp

<div class="container-fluid px-4 py-4">
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Operations</div>
                <h1 class="page-title mb-2">Gear Borrowing</h1>
                <p class="page-subtitle mb-0">
                    View available equipment from master data, manage borrowing activity, check active borrowers, and record equipment returns.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-light btn-modern" onclick="openBorrowModal()">
                    <i class="bi bi-plus-lg me-2"></i>Borrow Equipment
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
                <h5 class="content-card-title mb-1">Borrowing Equipment List</h5>
                <p class="content-card-subtitle mb-0">
                    Review equipment condition, current availability, active borrower, and return status.
                </p>
            </div>

            <form method="GET" action="{{ route('borrowings.index') }}" class="d-flex align-items-center gap-2 flex-wrap">
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
            @if($equipments->count())
                <div class="table-responsive dropdown-safe-table">
                    <table class="table table-hover align-middle admin-table mb-0">
                        <thead>
                            <tr>
                                <th class="text-nowrap" style="width: 80px;">No</th>
                                <th class="text-nowrap">Equipment</th>
                                <th class="text-nowrap">Code</th>
                                <th class="text-nowrap">Brand / Model</th>
                                <th class="text-nowrap">Condition</th>
                                <th class="text-nowrap">Status</th>
                                <th class="text-nowrap">Borrower</th>
                                <th class="text-end text-nowrap" style="width: 160px;">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($equipments as $equipment)
                                @php
                                    $activeBorrowing = $equipment->activeBorrowing;
                                    $isBorrowedByCurrentUser = $activeBorrowing
                                        && $activeBorrowing->user_id === auth()->id();
                                @endphp

                                <tr>
                                    <td class="text-muted">
                                        {{ ($equipments->currentPage() - 1) * $equipments->perPage() + $loop->iteration }}
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark">{{ $equipment->name }}</div>
                                        @if ($equipment->description)
                                            <div class="small text-muted">
                                                {{ \Illuminate\Support\Str::limit($equipment->description, 50) }}
                                            </div>
                                        @else
                                            <div class="small text-muted">No description</div>
                                        @endif
                                    </td>

                                    <td class="text-nowrap">
                                        <code>{{ $equipment->code }}</code>
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark">{{ $equipment->brand ?: '-' }}</div>
                                        <div class="small text-muted">{{ $equipment->model ?: '-' }}</div>
                                    </td>

                                    <td class="text-nowrap">
                                        <span class="badge rounded-pill {{ $conditionBadgeClass($equipment->condition) }}">
                                            {{ $formatCondition($equipment->condition) }}
                                        </span>
                                    </td>

                                    <td class="text-nowrap">
                                        <span class="badge rounded-pill {{ $statusBadgeClass($equipment->status) }}">
                                            {{ $formatStatus($equipment->status) }}
                                        </span>
                                    </td>

                                    <td>
                                        @if ($activeBorrowing && $activeBorrowing->user)
                                            <div class="fw-semibold text-dark">
                                                {{ $activeBorrowing->user->name }}

                                                @if ($isBorrowedByCurrentUser)
                                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle ms-1">
                                                        You
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="small text-muted">
                                                {{ $activeBorrowing->borrowed_at?->format('d M Y H:i') }}
                                            </div>
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
                                                @if ($equipment->status === 'available')
                                                    <li>
                                                        <button
                                                            type="button"
                                                            class="dropdown-item text-success"
                                                            onclick="openBorrowModal({{ $equipment->id }})"
                                                        >
                                                            <i class="bi bi-box-arrow-right me-2"></i>Borrow Equipment
                                                        </button>
                                                    </li>
                                                @endif

                                                @if ($equipment->status === 'borrowed' && $activeBorrowing)
                                                    <li>
                                                        <button
                                                            type="button"
                                                            class="dropdown-item"
                                                            onclick="viewBorrowing({{ $activeBorrowing->id }})"
                                                        >
                                                            <i class="bi bi-eye me-2"></i>View Detail
                                                        </button>
                                                    </li>
                                                @endif

                                                @if ($equipment->status === 'borrowed' && $activeBorrowing && $isBorrowedByCurrentUser)
                                                    <li>
                                                        <hr class="dropdown-divider">
                                                    </li>

                                                    <li>
                                                        <button
                                                            type="button"
                                                            class="dropdown-item text-warning"
                                                            onclick="openReturnModal(
                                                                {{ $activeBorrowing->id }},
                                                                @js($equipment->name),
                                                                @js($activeBorrowing->user->name ?? '-')
                                                            )"
                                                        >
                                                            <i class="bi bi-box-arrow-in-left me-2"></i>Return Equipment
                                                        </button>
                                                    </li>
                                                @endif

                                                @if (
                                                    $equipment->status !== 'available'
                                                    && !($equipment->status === 'borrowed' && $activeBorrowing)
                                                )
                                                    <li>
                                                        <span class="dropdown-item text-muted">
                                                            <i class="bi bi-dash-circle me-2"></i>No action available
                                                        </span>
                                                    </li>
                                                @endif
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($equipments->hasPages())
                    <div class="mt-3">
                        {{ $equipments->links() }}
                    </div>
                @endif
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-box-seam"></i>
                    </div>

                    <h5 class="empty-state-title">No equipment found</h5>
                    <p class="empty-state-text mb-0">
                        Belum ada equipment yang tersedia untuk borrowing.
                    </p>

                    <div class="mt-3">
                        <button type="button" class="btn btn-primary btn-modern" onclick="openBorrowModal()">
                            <i class="bi bi-plus-lg me-2"></i>Borrow Equipment
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Borrow Modal --}}
<div class="modal fade" id="borrowModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="borrowForm" class="w-100">
            @csrf

            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold mb-1">Borrow Equipment</h5>
                        <div class="small text-muted">
                            Select available equipment and set borrowing date, expected return date, and borrowing notes.
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div id="borrowFormAlert" class="alert alert-danger d-none mb-3"></div>

                    <div class="content-card">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Borrowing Detail</h5>
                                <p class="content-card-subtitle mb-0">
                                    Complete the borrowing information before saving this request.
                                </p>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <div class="row g-3">
                                <div class="col-12">
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

                                <div class="col-md-6">
                                    <label for="borrowed_at" class="form-label">Borrowed At</label>
                                    <input type="datetime-local" id="borrowed_at" class="form-control">
                                    <div class="invalid-feedback" id="error_borrowed_at"></div>
                                </div>

                                <div class="col-md-6">
                                    <label for="expected_return_at" class="form-label">Expected Return</label>
                                    <input type="datetime-local" id="expected_return_at" class="form-control">
                                    <div class="invalid-feedback" id="error_expected_return_at"></div>
                                </div>

                                <div class="col-12">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea id="notes" rows="4" class="form-control" placeholder="Add borrowing notes if needed"></textarea>
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
                    <button type="submit" class="btn btn-primary btn-modern" id="borrowSubmitBtn">
                        <span class="default-borrow-text">
                            <i class="bi bi-check-circle me-2"></i>Save
                        </span>
                        <span class="loading-borrow-text d-none">
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            Saving...
                        </span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Borrowing Detail Modal --}}
<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold mb-1">Borrowing Detail</h5>
                    <div class="small text-muted">
                        Review active borrowing information, return status, notes, and borrower detail.
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div id="detailAlert" class="alert alert-danger d-none mb-3"></div>

                <div class="content-card">
                    <div class="content-card-header">
                        <div>
                            <h5 class="content-card-title mb-1">Borrowing Information</h5>
                            <p class="content-card-subtitle mb-0">
                                Detail peminjaman equipment yang sedang dipilih.
                            </p>
                        </div>
                    </div>

                    <div class="content-card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="small text-muted mb-1">Equipment</div>
                                <div class="fw-semibold text-dark" id="detail_equipment">-</div>
                            </div>

                            <div class="col-md-6">
                                <div class="small text-muted mb-1">Borrower</div>
                                <div class="fw-semibold text-dark" id="detail_user">-</div>
                            </div>

                            <div class="col-md-6">
                                <div class="small text-muted mb-1">Borrowed At</div>
                                <div class="fw-semibold text-dark" id="detail_borrowed_at">-</div>
                            </div>

                            <div class="col-md-6">
                                <div class="small text-muted mb-1">Expected Return</div>
                                <div class="fw-semibold text-dark" id="detail_expected_return_at">-</div>
                            </div>

                            <div class="col-md-6">
                                <div class="small text-muted mb-1">Returned At</div>
                                <div class="fw-semibold text-dark" id="detail_returned_at">-</div>
                            </div>

                            <div class="col-md-6">
                                <div class="small text-muted mb-1">Status</div>
                                <div id="detail_status">-</div>
                            </div>

                            <div class="col-12">
                                <div class="small text-muted mb-1">Notes</div>
                                <div class="fw-semibold text-dark" id="detail_notes">-</div>
                            </div>

                            <div class="col-12">
                                <div class="small text-muted mb-1">Return Notes</div>
                                <div class="fw-semibold text-dark" id="detail_return_notes">-</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Return Modal --}}
<div class="modal fade" id="returnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="returnForm" class="w-100">
            @csrf
            <input type="hidden" id="return_borrowing_id">

            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold mb-1">Return Equipment</h5>
                        <div class="small text-muted">
                            Record returned time and return notes for the selected borrowing.
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div id="returnFormAlert" class="alert alert-danger d-none mb-3"></div>

                    <div class="content-card">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Return Detail</h5>
                                <p class="content-card-subtitle mb-0">
                                    Confirm returned equipment and complete return information.
                                </p>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="return_equipment_name" class="form-label">Equipment</label>
                                    <input type="text" id="return_equipment_name" class="form-control" readonly>
                                </div>

                                <div class="col-md-6">
                                    <label for="return_borrower_name" class="form-label">Borrower</label>
                                    <input type="text" id="return_borrower_name" class="form-control" readonly>
                                </div>

                                <div class="col-12">
                                    <label for="returned_at" class="form-label">Returned At</label>
                                    <input type="datetime-local" id="returned_at" class="form-control">
                                    <div class="invalid-feedback" id="error_returned_at"></div>
                                </div>

                                <div class="col-12">
                                    <label for="return_notes" class="form-label">Notes</label>
                                    <textarea id="return_notes" rows="4" class="form-control" placeholder="Add return notes if needed"></textarea>
                                    <div class="invalid-feedback" id="error_return_notes"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary btn-modern" id="returnSubmitBtn">
                        <span class="default-return-text">
                            <i class="bi bi-check-circle me-2"></i>Save
                        </span>
                        <span class="loading-return-text d-none">
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            Saving...
                        </span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

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

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function renderStatusBadge(status) {
        const normalizedStatus = status || '-';

        const badgeClass = {
            borrowed: 'bg-primary-subtle text-primary-emphasis border border-primary-subtle',
            returned: 'bg-success-subtle text-success-emphasis border border-success-subtle',
            overdue: 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
            cancelled: 'bg-danger-subtle text-danger-emphasis border border-danger-subtle',
        }[normalizedStatus] || 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle';

        return `<span class="badge rounded-pill ${badgeClass}">${escapeHtml(normalizedStatus)}</span>`;
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

        document.getElementById('equipment_id').value = selectedEquipmentId || '';
        document.getElementById('borrowed_at').value = toDatetimeLocalValue();
        document.getElementById('expected_return_at').value = '';
        document.getElementById('notes').value = '';

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
        document.getElementById('return_notes').value = '';

        returnFormAlert.classList.add('d-none');
        returnFormAlert.innerHTML = '';

        clearValidationErrors();
        setReturnLoading(false);
    }

    function openBorrowModal(selectedEquipmentId = '') {
        resetBorrowForm(selectedEquipmentId);
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
        document.getElementById('detail_status').innerHTML = '-';
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

            document.getElementById('detail_equipment').textContent = data.equipment?.name ?? '-';
            document.getElementById('detail_user').textContent = data.user?.name ?? '-';
            document.getElementById('detail_borrowed_at').textContent = formatDateTime(data.borrowed_at);
            document.getElementById('detail_expected_return_at').textContent = formatDateTime(data.expected_return_at);
            document.getElementById('detail_returned_at').textContent = formatDateTime(data.returned_at);
            document.getElementById('detail_status').innerHTML = renderStatusBadge(data.status);
            document.getElementById('detail_notes').textContent = data.notes || '-';
            document.getElementById('detail_return_notes').textContent = data.return_notes || '-';
        } catch (error) {
            detailAlert.classList.remove('d-none');
            detailAlert.innerHTML = error.message || 'Failed to load borrowing detail.';
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
            borrowed_at: document.getElementById('borrowed_at').value || null,
            expected_return_at: document.getElementById('expected_return_at').value || null,
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
            showToast(result.message || 'Equipment borrowed successfully.', 'success');
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
            returned_at: document.getElementById('returned_at').value || null,
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
            showToast(result.message || 'Equipment returned successfully.', 'success');
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