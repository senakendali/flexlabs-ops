@extends('layouts.app-dashboard')

@section('title', 'Programs')

@section('content')
@php
    $statusBadgeClass = function ($isActive) {
        return $isActive
            ? 'bg-success-subtle text-success-emphasis border border-success-subtle'
            : 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle';
    };
@endphp

<div class="container-fluid px-4 py-4">
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Academic</div>
                <h1 class="page-title mb-2">Programs</h1>
                <p class="page-subtitle mb-0">
                    Manage master program data, program slug, description, and active status for academic modules.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-light btn-modern" onclick="openCreateModal()">
                    <i class="bi bi-plus-lg me-2"></i>Add Program
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
                <h5 class="content-card-title mb-1">Program List</h5>
                <p class="content-card-subtitle mb-0">
                    Review academic programs, generated slug, description, and program availability status.
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
            @if($programs->count())
                <div class="table-responsive dropdown-safe-table">
                    <table class="table table-hover align-middle admin-table mb-0">
                        <thead>
                            <tr>
                                <th class="text-nowrap" style="width: 80px;">No</th>
                                <th class="text-nowrap">Program</th>
                                <th class="text-nowrap">Slug</th>
                                <th class="text-nowrap">Description</th>
                                <th class="text-nowrap">Status</th>
                                <th class="text-end text-nowrap" style="width: 160px;">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($programs as $program)
                                <tr>
                                    <td class="text-muted">
                                        {{ ($programs->currentPage() - 1) * $programs->perPage() + $loop->iteration }}
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ $program->name }}
                                        </div>
                                        <div class="small text-muted">
                                            Academic program
                                        </div>
                                    </td>

                                    <td class="text-nowrap">
                                        <code>{{ $program->slug }}</code>
                                    </td>

                                    <td class="text-muted">
                                        {{ $program->description ?: '-' }}
                                    </td>

                                    <td class="text-nowrap">
                                        <span class="badge rounded-pill {{ $statusBadgeClass($program->is_active) }}">
                                            {{ $program->is_active ? 'Active' : 'Inactive' }}
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
                                                        onclick="editProgram({{ $program->id }})"
                                                    >
                                                        <i class="bi bi-pencil-square me-2"></i>Edit Program
                                                    </button>
                                                </li>

                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>

                                                <li>
                                                    <button
                                                        type="button"
                                                        class="dropdown-item text-danger"
                                                        onclick="openDeleteModal({{ $program->id }}, @js($program->name))"
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

                @if ($programs->hasPages())
                    <div class="mt-3">
                        {{ $programs->links() }}
                    </div>
                @endif
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-journal-richtext"></i>
                    </div>

                    <h5 class="empty-state-title">No programs found</h5>
                    <p class="empty-state-text mb-0">
                        Belum ada program yang tercatat. Tambahkan program baru untuk mulai mengelola struktur akademik.
                    </p>

                    <div class="mt-3">
                        <button type="button" class="btn btn-primary btn-modern" onclick="openCreateModal()">
                            <i class="bi bi-plus-lg me-2"></i>Add Program
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Form Modal --}}
<div class="modal fade" id="programModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="programForm">
            @csrf
            <input type="hidden" id="program_id">

            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold mb-1" id="programModalTitle">Add Program</h5>
                        <div class="small text-muted">
                            Complete program identity, slug, description, and active status.
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div id="formAlert" class="alert alert-danger d-none mb-3"></div>

                    <div class="content-card">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Program Information</h5>
                                <p class="content-card-subtitle mb-0">
                                    Define the program name and slug used across academic modules.
                                </p>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">
                                        Program Name <span class="text-danger">*</span>
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

                                <div class="col-12">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea id="description" rows="4" class="form-control" placeholder="Short description about this program"></textarea>
                                    <div class="invalid-feedback" id="error_description"></div>
                                </div>

                                <div class="col-12">
                                    <div class="border rounded-3 p-3">
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" id="is_active" checked>
                                            <label class="form-check-label fw-semibold" for="is_active">
                                                Active Program
                                            </label>
                                        </div>
                                        <div class="small text-muted mt-1">
                                            Active programs can be used in related academic and enrollment modules.
                                        </div>
                                        <div class="invalid-feedback d-block" id="error_is_active"></div>
                                    </div>
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
                    <h5 class="modal-title fw-bold mb-1">Delete Program</h5>
                    <div class="small text-muted">
                        This action will remove selected program from the system.
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="alert alert-danger mb-0">
                    <div class="d-flex gap-2 align-items-start">
                        <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                        <div>
                            <div class="fw-semibold">Delete this program?</div>
                            <div class="small mt-1">
                                Are you sure you want to delete
                                <strong id="deleteProgramName"></strong>?
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
    const programBaseUrl = @json(url('/programs'));

    const programModalEl = document.getElementById('programModal');
    const programModal = new bootstrap.Modal(programModalEl);
    const programForm = document.getElementById('programForm');
    const submitBtn = document.getElementById('submitBtn');
    const modalTitle = document.getElementById('programModalTitle');
    const formAlert = document.getElementById('formAlert');

    const deleteModalEl = document.getElementById('deleteModal');
    const deleteModal = new bootstrap.Modal(deleteModalEl);
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const deleteProgramNameEl = document.getElementById('deleteProgramName');

    const fields = {
        id: document.getElementById('program_id'),
        name: document.getElementById('name'),
        slug: document.getElementById('slug'),
        description: document.getElementById('description'),
        is_active: document.getElementById('is_active'),
    };

    let isEditMode = false;
    let autoSlug = true;
    let deleteProgramId = null;
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

        ['name', 'slug', 'description', 'is_active'].forEach(key => {
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
        programForm.reset();

        fields.id.value = '';
        fields.name.value = '';
        fields.slug.value = '';
        fields.description.value = '';
        fields.is_active.checked = true;

        clearValidationErrors();
        setSubmitLoading(false);

        autoSlug = true;
        isEditMode = false;
    }

    function openCreateModal() {
        resetForm();
        modalTitle.textContent = 'Add Program';
        programModal.show();
    }

    async function editProgram(id) {
        resetForm();
        isEditMode = true;
        autoSlug = false;
        modalTitle.textContent = 'Edit Program';

        try {
            const response = await fetch(`${programBaseUrl}/${id}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to fetch program data.');
            }

            const data = result.data;

            fields.id.value = data.id ?? '';
            fields.name.value = data.name ?? '';
            fields.slug.value = data.slug ?? '';
            fields.description.value = data.description ?? '';
            fields.is_active.checked = Boolean(data.is_active);

            programModal.show();
        } catch (error) {
            formAlert.classList.remove('d-none');
            formAlert.innerHTML = error.message || 'Failed to load program data.';
            programModal.show();
        }
    }

    function openDeleteModal(id, name) {
        deleteProgramId = id;
        deleteProgramNameEl.textContent = name || '-';
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

    programForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        clearValidationErrors();

        const id = fields.id.value;
        const url = id ? `${programBaseUrl}/${id}` : programBaseUrl;

        const payload = {
            name: fields.name.value.trim(),
            slug: fields.slug.value.trim(),
            description: fields.description.value.trim(),
            is_active: fields.is_active.checked ? 1 : 0,
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
                throw new Error(result.message || 'Failed to save program.');
            }

            programModal.hide();
            showToast(result.message || 'Program saved successfully.', 'success');
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
        if (!deleteProgramId) return;

        setDeleteLoading(true);

        try {
            const response = await fetch(`${programBaseUrl}/${deleteProgramId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to delete program.');
            }

            deleteModal.hide();
            showToast(result.message || 'Program deleted successfully.', 'danger');
            scheduleReload();
        } catch (error) {
            showToast(error.message || 'Failed to delete program.', 'danger');
        } finally {
            setDeleteLoading(false);
            deleteProgramId = null;
        }
    });

    programModalEl.addEventListener('hidden.bs.modal', function () {
        resetForm();
    });

    deleteModalEl.addEventListener('hidden.bs.modal', function () {
        deleteProgramId = null;
        deleteProgramNameEl.textContent = '';
        setDeleteLoading(false);
    });
</script>
@endpush