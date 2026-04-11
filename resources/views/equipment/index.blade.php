@extends('layouts.app-dashboard')

@section('title', 'Equipment')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="mb-1">Equipment</h4>
            <small class="text-muted">Manage equipment master data and borrowings</small>
        </div>

        <button type="button" class="btn btn-primary" onclick="openCreateModal()">
            <i class="bi bi-plus-lg me-1"></i> Add Equipment
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
                        <th>Name</th>
                        <th>Code</th>
                        <th>Serial Number</th>
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
                            <td>{{ $equipment->serial_number ?: '-' }}</td>
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
                                    <div class="fw-semibold">{{ $activeBorrowing->user->name }}</div>
                                    <small class="text-muted">
                                        {{ $activeBorrowing->borrowed_at?->format('d M Y H:i') }}
                                    </small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-inline-flex gap-2">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        onclick="editEquipment({{ $equipment->id }})"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    @if ($equipment->status === 'available')
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-success"
                                            onclick="openBorrowModal({{ $equipment->id }}, @js($equipment->name))"
                                        >
                                            <i class="bi bi-box-arrow-right"></i>
                                        </button>
                                    @endif

                                    @if ($equipment->status === 'borrowed' && $activeBorrowing)
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-warning"
                                            onclick="openReturnModal({{ $activeBorrowing->id }}, @js($equipment->name), @js($equipment->condition))"
                                        >
                                            <i class="bi bi-box-arrow-in-left"></i>
                                        </button>
                                    @endif

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="openDeleteModal({{ $equipment->id }}, @js($equipment->name))"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
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

{{-- Equipment Form Modal --}}
<div class="modal fade" id="equipmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="equipmentForm">
            @csrf
            <input type="hidden" id="equipment_id">

            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title" id="equipmentModalTitle">Add Equipment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div id="formAlert" class="alert alert-danger d-none mb-3"></div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">
                                Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="name" class="form-control">
                            <div class="invalid-feedback" id="error_name"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="slug" class="form-label">Slug</label>
                            <input type="text" id="slug" class="form-control">
                            <div class="form-text">Optional. Will auto-generate from name if empty.</div>
                            <div class="invalid-feedback" id="error_slug"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="code_preview" class="form-label">Code</label>
                            <input type="text" id="code_preview" class="form-control" readonly>
                            <div class="form-text">Auto-generated by system.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="serial_number" class="form-label">Serial Number</label>
                            <input type="text" id="serial_number" class="form-control">
                            <div class="invalid-feedback" id="error_serial_number"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="brand" class="form-label">Brand</label>
                            <input type="text" id="brand" class="form-control">
                            <div class="invalid-feedback" id="error_brand"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="model" class="form-label">Model</label>
                            <input type="text" id="model" class="form-control">
                            <div class="invalid-feedback" id="error_model"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="condition" class="form-label">Condition</label>
                            <select id="condition" class="form-select">
                                <option value="good">Good</option>
                                <option value="minor_damage">Minor Damage</option>
                                <option value="damaged">Damaged</option>
                            </select>
                            <div class="invalid-feedback" id="error_condition"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="status" class="form-label">Status</label>
                            <select id="status" class="form-select">
                                <option value="available">Available</option>
                                <option value="borrowed">Borrowed</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                            <div class="invalid-feedback" id="error_status"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="is_active" class="form-label">Status Active</label>
                            <select id="is_active" class="form-select">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                            <div class="invalid-feedback" id="error_is_active"></div>
                        </div>

                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea id="description" rows="4" class="form-control"></textarea>
                            <div class="invalid-feedback" id="error_description"></div>
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

{{-- Borrow Modal --}}
<div class="modal fade" id="borrowModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="borrowForm">
            @csrf
            <input type="hidden" id="borrow_equipment_id">

            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title">Borrow Equipment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div id="borrowFormAlert" class="alert alert-danger d-none mb-3"></div>

                    <div class="mb-3">
                        <label for="borrow_equipment_name" class="form-label">Equipment</label>
                        <input type="text" id="borrow_equipment_name" class="form-control" readonly>
                    </div>

                    <div class="mb-3">
                        <label for="borrow_user_id" class="form-label">
                            Borrower <span class="text-danger">*</span>
                        </label>
                        <select id="borrow_user_id" class="form-select">
                            <option value="">Select User</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">
                                    {{ $user->name }}{{ $user->email ? ' - ' . $user->email : '' }}
                                </option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="error_borrow_user_id"></div>
                    </div>

                    <div class="mb-3">
                        <label for="borrowed_at" class="form-label">
                            Borrowed At <span class="text-danger">*</span>
                        </label>
                        <input type="datetime-local" id="borrowed_at" class="form-control">
                        <div class="invalid-feedback" id="error_borrowed_at"></div>
                    </div>

                    <div class="mb-3">
                        <label for="due_at" class="form-label">Due At</label>
                        <input type="datetime-local" id="due_at" class="form-control">
                        <div class="invalid-feedback" id="error_due_at"></div>
                    </div>

                    <div class="mb-0">
                        <label for="borrow_notes" class="form-label">Notes</label>
                        <textarea id="borrow_notes" rows="3" class="form-control"></textarea>
                        <div class="invalid-feedback" id="error_borrow_notes"></div>
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

{{-- Return Modal --}}
<div class="modal fade" id="returnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="returnForm">
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
                        <label for="returned_at" class="form-label">
                            Returned At <span class="text-danger">*</span>
                        </label>
                        <input type="datetime-local" id="returned_at" class="form-control">
                        <div class="invalid-feedback" id="error_returned_at"></div>
                    </div>

                    <div class="mb-3">
                        <label for="return_condition" class="form-label">Condition After Return</label>
                        <select id="return_condition" class="form-select">
                            <option value="good">Good</option>
                            <option value="minor_damage">Minor Damage</option>
                            <option value="damaged">Damaged</option>
                        </select>
                        <div class="invalid-feedback" id="error_return_condition"></div>
                    </div>

                    <div class="mb-0">
                        <label for="return_notes" class="form-label">Notes</label>
                        <textarea id="return_notes" rows="3" class="form-control"></textarea>
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

{{-- Delete Modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title">Delete Equipment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <p class="mb-0">
                    Are you sure you want to delete
                    <strong id="deleteEquipmentName"></strong>?
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
    const equipmentModalEl = document.getElementById('equipmentModal');
    const equipmentModal = new bootstrap.Modal(equipmentModalEl);
    const equipmentForm = document.getElementById('equipmentForm');
    const submitBtn = document.getElementById('submitBtn');
    const modalTitle = document.getElementById('equipmentModalTitle');
    const formAlert = document.getElementById('formAlert');

    const borrowModalEl = document.getElementById('borrowModal');
    const borrowModal = new bootstrap.Modal(borrowModalEl);
    const borrowForm = document.getElementById('borrowForm');
    const borrowFormAlert = document.getElementById('borrowFormAlert');
    const borrowSubmitBtn = document.getElementById('borrowSubmitBtn');

    const returnModalEl = document.getElementById('returnModal');
    const returnModal = new bootstrap.Modal(returnModalEl);
    const returnForm = document.getElementById('returnForm');
    const returnFormAlert = document.getElementById('returnFormAlert');
    const returnSubmitBtn = document.getElementById('returnSubmitBtn');

    const deleteModalEl = document.getElementById('deleteModal');
    const deleteModal = new bootstrap.Modal(deleteModalEl);
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const deleteEquipmentNameEl = document.getElementById('deleteEquipmentName');

    const fields = {
        id: document.getElementById('equipment_id'),
        name: document.getElementById('name'),
        slug: document.getElementById('slug'),
        code_preview: document.getElementById('code_preview'),
        serial_number: document.getElementById('serial_number'),
        brand: document.getElementById('brand'),
        model: document.getElementById('model'),
        description: document.getElementById('description'),
        condition: document.getElementById('condition'),
        status: document.getElementById('status'),
        is_active: document.getElementById('is_active'),
    };

    let isEditMode = false;
    let autoSlug = true;
    let deleteEquipmentId = null;
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

    function slugify(text) {
        return String(text || '')
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');
    }

    function toDatetimeLocalValue(date = new Date()) {
        const offset = date.getTimezoneOffset();
        const localDate = new Date(date.getTime() - (offset * 60000));
        return localDate.toISOString().slice(0, 16);
    }

    function clearValidationErrors() {
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        document.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
    }

    function setValidationErrors(errors = {}) {
        clearValidationErrors();

        Object.keys(errors).forEach(key => {
            const field = document.getElementById(key) || fields[key];
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

    function setDeleteLoading(isLoading) {
        confirmDeleteBtn.disabled = isLoading;
        confirmDeleteBtn.querySelector('.default-delete-text').classList.toggle('d-none', isLoading);
        confirmDeleteBtn.querySelector('.loading-delete-text').classList.toggle('d-none', !isLoading);
    }

    function resetForm() {
        equipmentForm.reset();
        fields.id.value = '';
        fields.code_preview.value = 'Auto generated';
        fields.condition.value = 'good';
        fields.status.value = 'available';
        fields.is_active.value = '1';
        formAlert.classList.add('d-none');
        formAlert.innerHTML = '';
        clearValidationErrors();
        setSubmitLoading(false);
        autoSlug = true;
    }

    function resetBorrowForm() {
        borrowForm.reset();
        document.getElementById('borrow_equipment_id').value = '';
        document.getElementById('borrow_equipment_name').value = '';
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
        document.getElementById('returned_at').value = toDatetimeLocalValue();
        document.getElementById('return_condition').value = 'good';
        returnFormAlert.classList.add('d-none');
        returnFormAlert.innerHTML = '';
        clearValidationErrors();
        setReturnLoading(false);
    }

    function openCreateModal() {
        isEditMode = false;
        resetForm();
        modalTitle.textContent = 'Add Equipment';
        equipmentModal.show();
    }

    async function editEquipment(id) {
        isEditMode = true;
        resetForm();
        modalTitle.textContent = 'Edit Equipment';

        try {
            const response = await fetch(`/equipment/${id}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to fetch equipment data.');
            }

            const data = result.data;

            fields.id.value = data.id;
            fields.name.value = data.name ?? '';
            fields.slug.value = data.slug ?? '';
            fields.code_preview.value = data.code ?? '-';
            fields.serial_number.value = data.serial_number ?? '';
            fields.brand.value = data.brand ?? '';
            fields.model.value = data.model ?? '';
            fields.description.value = data.description ?? '';
            fields.condition.value = data.condition ?? 'good';
            fields.status.value = data.status ?? 'available';
            fields.is_active.value = data.is_active ? '1' : '0';

            autoSlug = false;
            equipmentModal.show();
        } catch (error) {
            formAlert.classList.remove('d-none');
            formAlert.innerHTML = error.message || 'Failed to load equipment data.';
            equipmentModal.show();
        }
    }

    function openBorrowModal(id, name) {
        resetBorrowForm();
        document.getElementById('borrow_equipment_id').value = id;
        document.getElementById('borrow_equipment_name').value = name || '-';
        borrowModal.show();
    }

    function openReturnModal(id, name, condition) {
        resetReturnForm();
        document.getElementById('return_borrowing_id').value = id;
        document.getElementById('return_equipment_name').value = name || '-';
        document.getElementById('return_condition').value = condition || 'good';
        returnModal.show();
    }

    function openDeleteModal(id, name) {
        deleteEquipmentId = id;
        deleteEquipmentNameEl.textContent = name || '-';
        setDeleteLoading(false);
        deleteModal.show();
    }

    fields.name.addEventListener('input', function () {
        if (!isEditMode && autoSlug) {
            fields.slug.value = slugify(this.value);
        }
    });

    fields.slug.addEventListener('input', function () {
        autoSlug = this.value.trim() === '';
    });

    equipmentForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        clearValidationErrors();
        formAlert.classList.add('d-none');
        formAlert.innerHTML = '';

        const id = fields.id.value;
        const url = id ? `/equipment/${id}` : `/equipment`;

        const payload = {
            name: fields.name.value.trim(),
            slug: fields.slug.value.trim(),
            serial_number: fields.serial_number.value.trim(),
            brand: fields.brand.value.trim(),
            model: fields.model.value.trim(),
            description: fields.description.value.trim(),
            condition: fields.condition.value,
            status: fields.status.value,
            is_active: fields.is_active.value === '1' ? 1 : 0,
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
                throw new Error(result.message || 'Failed to save equipment.');
            }

            equipmentModal.hide();
            showToast(result.message || 'Equipment saved successfully', 'success');
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

    borrowForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        clearValidationErrors();
        borrowFormAlert.classList.add('d-none');
        borrowFormAlert.innerHTML = '';

        const equipmentId = document.getElementById('borrow_equipment_id').value;

        const payload = {
            user_id: document.getElementById('borrow_user_id').value,
            borrowed_at: document.getElementById('borrowed_at').value,
            due_at: document.getElementById('due_at').value,
            notes: document.getElementById('borrow_notes').value.trim(),
        };

        setBorrowLoading(true);

        try {
            const response = await fetch(`/equipment/${equipmentId}/borrow`, {
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
            condition: document.getElementById('return_condition').value,
            notes: document.getElementById('return_notes').value.trim(),
        };

        setReturnLoading(true);

        try {
            const response = await fetch(`/equipment/borrowings/${borrowingId}/return`, {
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

    confirmDeleteBtn.addEventListener('click', async function () {
        if (!deleteEquipmentId) return;

        setDeleteLoading(true);

        try {
            const response = await fetch(`/equipment/${deleteEquipmentId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to delete equipment.');
            }

            deleteModal.hide();
            showToast(result.message || 'Equipment deleted successfully', 'danger');
            scheduleReload();
        } catch (error) {
            showToast(error.message || 'Failed to delete equipment.', 'danger');
        } finally {
            setDeleteLoading(false);
            deleteEquipmentId = null;
        }
    });

    equipmentModalEl.addEventListener('hidden.bs.modal', function () {
        resetForm();
    });

    borrowModalEl.addEventListener('hidden.bs.modal', function () {
        resetBorrowForm();
    });

    returnModalEl.addEventListener('hidden.bs.modal', function () {
        resetReturnForm();
    });

    deleteModalEl.addEventListener('hidden.bs.modal', function () {
        deleteEquipmentId = null;
        deleteEquipmentNameEl.textContent = '';
        setDeleteLoading(false);
    });
</script>
@endpush