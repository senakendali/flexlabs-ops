@extends('layouts.app-dashboard')

@section('title', 'Batches')

@section('content')
@php
    $statusBadgeClass = function ($status) {
        return match($status) {
            'draft' => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
            'open' => 'bg-success-subtle text-success-emphasis border border-success-subtle',
            'closed' => 'bg-danger-subtle text-danger-emphasis border border-danger-subtle',
            'ongoing' => 'bg-primary-subtle text-primary-emphasis border border-primary-subtle',
            'completed' => 'bg-dark text-white border border-dark',
            default => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
        };
    };
@endphp

<div class="container-fluid px-4 py-4">
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Academic</div>
                <h1 class="page-title mb-2">Batches</h1>
                <p class="page-subtitle mb-0">
                    Manage batch data, enrollment period, quota, pricing, and batch status for each academic program.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-light btn-modern" onclick="openCreateModal()">
                    <i class="bi bi-plus-lg me-2"></i>Add Batch
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
                <h5 class="content-card-title mb-1">Batch List</h5>
                <p class="content-card-subtitle mb-0">
                    Review batch name, program, active period, quota, price, and current enrollment status.
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
            @if($batches->count())
                <div class="table-responsive dropdown-safe-table">
                    <table class="table table-hover align-middle admin-table mb-0">
                        <thead>
                            <tr>
                                <th class="text-nowrap" style="width: 80px;">No</th>
                                <th class="text-nowrap">Batch</th>
                                <th class="text-nowrap">Program</th>
                                <th class="text-nowrap">Period</th>
                                <th class="text-end text-nowrap">Quota</th>
                                <th class="text-end text-nowrap">Price</th>
                                <th class="text-nowrap">Status</th>
                                <th class="text-end text-nowrap" style="width: 160px;">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($batches as $batch)
                                <tr>
                                    <td class="text-muted">
                                        {{ ($batches->currentPage() - 1) * $batches->perPage() + $loop->iteration }}
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ $batch->name }}
                                        </div>
                                        <div class="small text-muted">
                                            <code>{{ $batch->slug }}</code>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ $batch->program->name ?? '-' }}
                                        </div>
                                        <div class="small text-muted">Academic program</div>
                                    </td>

                                    <td class="text-nowrap">
                                        @if ($batch->start_date || $batch->end_date)
                                            <div class="fw-semibold text-dark">
                                                {{ $batch->start_date?->format('d M Y') ?? '-' }}
                                            </div>
                                            <div class="small text-muted">
                                                until {{ $batch->end_date?->format('d M Y') ?? '-' }}
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    <td class="text-end text-nowrap">
                                        {{ $batch->quota ? number_format((int) $batch->quota) : '-' }}
                                    </td>

                                    <td class="text-end text-nowrap">
                                        <div class="fw-bold text-dark">
                                            Rp {{ number_format((float) $batch->price, 0, ',', '.') }}
                                        </div>
                                    </td>

                                    <td class="text-nowrap">
                                        <span class="badge rounded-pill {{ $statusBadgeClass($batch->status) }}">
                                            {{ ucfirst($batch->status) }}
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
                                                        onclick="editBatch({{ $batch->id }})"
                                                    >
                                                        <i class="bi bi-pencil-square me-2"></i>Edit Batch
                                                    </button>
                                                </li>

                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>

                                                <li>
                                                    <button
                                                        type="button"
                                                        class="dropdown-item text-danger"
                                                        onclick="openDeleteModal({{ $batch->id }}, @js($batch->name))"
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

                @if ($batches->hasPages())
                    <div class="mt-3">
                        {{ $batches->links() }}
                    </div>
                @endif
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-collection-play"></i>
                    </div>

                    <h5 class="empty-state-title">No batches found</h5>
                    <p class="empty-state-text mb-0">
                        Belum ada batch yang tercatat. Tambahkan batch baru untuk mulai mengatur enrollment program.
                    </p>

                    <div class="mt-3">
                        <button type="button" class="btn btn-primary btn-modern" onclick="openCreateModal()">
                            <i class="bi bi-plus-lg me-2"></i>Add Batch
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Form Modal --}}
<div class="modal fade" id="batchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="batchForm">
            @csrf
            <input type="hidden" id="batch_id">

            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold mb-1" id="batchModalTitle">Add Batch</h5>
                        <div class="small text-muted">
                            Complete batch identity, program, enrollment period, quota, pricing, and batch status.
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div id="formAlert" class="alert alert-danger d-none mb-3"></div>

                    <div class="content-card mb-3">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Batch Information</h5>
                                <p class="content-card-subtitle mb-0">
                                    Define the selected program, batch name, slug, and current status.
                                </p>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="program_id" class="form-label">
                                        Program <span class="text-danger">*</span>
                                    </label>
                                    <select id="program_id" class="form-select">
                                        <option value="">Select Program</option>
                                        @foreach ($programs as $program)
                                            <option value="{{ $program->id }}">{{ $program->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback" id="error_program_id"></div>
                                </div>

                                <div class="col-md-6">
                                    <label for="status" class="form-label">
                                        Status <span class="text-danger">*</span>
                                    </label>
                                    <select id="status" class="form-select">
                                        <option value="draft">Draft</option>
                                        <option value="open">Open</option>
                                        <option value="closed">Closed</option>
                                        <option value="ongoing">Ongoing</option>
                                        <option value="completed">Completed</option>
                                    </select>
                                    <div class="invalid-feedback" id="error_status"></div>
                                </div>

                                <div class="col-md-6">
                                    <label for="name" class="form-label">
                                        Batch Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" id="name" class="form-control">
                                    <div class="invalid-feedback" id="error_name"></div>
                                </div>

                                <div class="col-md-6">
                                    <label for="slug" class="form-label">Slug</label>
                                    <input type="text" id="slug" class="form-control">
                                    <div class="form-text">Optional. Will auto-generate from batch name if empty.</div>
                                    <div class="invalid-feedback" id="error_slug"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="content-card mb-3">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Schedule & Commercial</h5>
                                <p class="content-card-subtitle mb-0">
                                    Set batch period, seat quota, and program price for enrollment/payment references.
                                </p>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="date" id="start_date" class="form-control">
                                    <div class="invalid-feedback" id="error_start_date"></div>
                                </div>

                                <div class="col-md-6">
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input type="date" id="end_date" class="form-control">
                                    <div class="invalid-feedback" id="error_end_date"></div>
                                </div>

                                <div class="col-md-6">
                                    <label for="quota" class="form-label">Quota</label>
                                    <input
                                        type="number"
                                        id="quota"
                                        class="form-control"
                                        min="1"
                                        placeholder="e.g. 20"
                                    >
                                    <div class="invalid-feedback" id="error_quota"></div>
                                </div>

                                <div class="col-md-6">
                                    <label for="price" class="form-label">Price</label>
                                    <input
                                        type="number"
                                        id="price"
                                        class="form-control"
                                        min="0"
                                        step="0.01"
                                        placeholder="e.g. 2500000"
                                    >
                                    <div class="invalid-feedback" id="error_price"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="content-card">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Description</h5>
                                <p class="content-card-subtitle mb-0">
                                    Add optional notes or context for this batch.
                                </p>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <label for="description" class="form-label">Description</label>
                            <textarea
                                id="description"
                                rows="4"
                                class="form-control"
                                placeholder="Short description about this batch"
                            ></textarea>
                            <div class="invalid-feedback" id="error_description"></div>
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
                    <h5 class="modal-title fw-bold mb-1">Delete Batch</h5>
                    <div class="small text-muted">
                        This action will remove selected batch from the system.
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="alert alert-danger mb-0">
                    <div class="d-flex gap-2 align-items-start">
                        <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                        <div>
                            <div class="fw-semibold">Delete this batch?</div>
                            <div class="small mt-1">
                                Are you sure you want to delete
                                <strong id="deleteBatchName"></strong>?
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
    const batchBaseUrl = @json(url('/batches'));

    const batchModalEl = document.getElementById('batchModal');
    const batchModal = new bootstrap.Modal(batchModalEl);
    const batchForm = document.getElementById('batchForm');
    const submitBtn = document.getElementById('submitBtn');
    const modalTitle = document.getElementById('batchModalTitle');
    const formAlert = document.getElementById('formAlert');

    const deleteModalEl = document.getElementById('deleteModal');
    const deleteModal = new bootstrap.Modal(deleteModalEl);
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const deleteBatchNameEl = document.getElementById('deleteBatchName');

    const fields = {
        id: document.getElementById('batch_id'),
        program_id: document.getElementById('program_id'),
        name: document.getElementById('name'),
        slug: document.getElementById('slug'),
        start_date: document.getElementById('start_date'),
        end_date: document.getElementById('end_date'),
        quota: document.getElementById('quota'),
        price: document.getElementById('price'),
        status: document.getElementById('status'),
        description: document.getElementById('description'),
    };

    let isEditMode = false;
    let autoSlug = true;
    let deleteBatchId = null;
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
                    <div class="toast-body">${message}</div>
                    <button type="button" class="${closeBtnClass} me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
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

    function clearValidationErrors() {
        Object.values(fields).forEach(field => {
            if (field && field.classList) {
                field.classList.remove('is-invalid');
            }
        });

        [
            'program_id',
            'name',
            'slug',
            'start_date',
            'end_date',
            'quota',
            'price',
            'status',
            'description'
        ].forEach(key => {
            const errorEl = document.getElementById(`error_${key}`);

            if (errorEl) {
                errorEl.textContent = '';
            }
        });

        formAlert.classList.add('d-none');
        formAlert.innerHTML = '';
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

    function resetForm() {
        batchForm.reset();

        fields.id.value = '';
        fields.program_id.value = '';
        fields.name.value = '';
        fields.slug.value = '';
        fields.start_date.value = '';
        fields.end_date.value = '';
        fields.quota.value = '';
        fields.price.value = '';
        fields.status.value = 'draft';
        fields.description.value = '';

        clearValidationErrors();
        setSubmitLoading(false);

        autoSlug = true;
        isEditMode = false;
    }

    function openCreateModal() {
        resetForm();
        modalTitle.textContent = 'Add Batch';
        batchModal.show();
    }

    async function editBatch(id) {
        resetForm();
        isEditMode = true;
        autoSlug = false;
        modalTitle.textContent = 'Edit Batch';

        try {
            const response = await fetch(`${batchBaseUrl}/${id}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to fetch batch data.');
            }

            const data = result.data;

            fields.id.value = data.id ?? '';
            fields.program_id.value = data.program_id ?? '';
            fields.name.value = data.name ?? '';
            fields.slug.value = data.slug ?? '';
            fields.start_date.value = data.start_date ?? '';
            fields.end_date.value = data.end_date ?? '';
            fields.quota.value = data.quota ?? '';
            fields.price.value = data.price ?? '';
            fields.status.value = data.status ?? 'draft';
            fields.description.value = data.description ?? '';

            batchModal.show();
        } catch (error) {
            formAlert.classList.remove('d-none');
            formAlert.innerHTML = error.message || 'Failed to load batch data.';
            batchModal.show();
        }
    }

    function openDeleteModal(id, name) {
        deleteBatchId = id;
        deleteBatchNameEl.textContent = name || '-';
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

    batchForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        clearValidationErrors();

        const id = fields.id.value;
        const url = id ? `${batchBaseUrl}/${id}` : batchBaseUrl;

        const payload = {
            program_id: fields.program_id.value,
            name: fields.name.value.trim(),
            slug: fields.slug.value.trim(),
            start_date: fields.start_date.value || null,
            end_date: fields.end_date.value || null,
            quota: fields.quota.value || null,
            price: fields.price.value || 0,
            status: fields.status.value,
            description: fields.description.value.trim(),
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
                throw new Error(result.message || 'Failed to save batch.');
            }

            batchModal.hide();
            showToast(result.message || 'Batch saved successfully.', 'success');
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
        if (!deleteBatchId) return;

        setDeleteLoading(true);

        try {
            const response = await fetch(`${batchBaseUrl}/${deleteBatchId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to delete batch.');
            }

            deleteModal.hide();
            showToast(result.message || 'Batch deleted successfully.', 'danger');
            scheduleReload();
        } catch (error) {
            showToast(error.message || 'Failed to delete batch.', 'danger');
        } finally {
            setDeleteLoading(false);
            deleteBatchId = null;
        }
    });

    batchModalEl.addEventListener('hidden.bs.modal', function () {
        resetForm();
    });

    deleteModalEl.addEventListener('hidden.bs.modal', function () {
        deleteBatchId = null;
        deleteBatchNameEl.textContent = '';
        setDeleteLoading(false);
    });
</script>
@endpush