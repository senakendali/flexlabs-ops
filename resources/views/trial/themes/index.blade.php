@extends('layouts.app-dashboard')

@section('title', 'Trial Themes')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Academic</div>
                <h1 class="page-title mb-2">Trial Themes</h1>
                <p class="page-subtitle mb-0">
                    Manage trial class themes by program, display order, description, and active status.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-light btn-modern" onclick="openCreateModal()">
                    <i class="bi bi-plus-lg me-2"></i>Add Theme
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
                <h5 class="content-card-title mb-1">Trial Theme List</h5>
                <p class="content-card-subtitle mb-0">
                    Review theme name, related program, slug, sort order, and publication status.
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
            @if ($themes->count())
                <div class="table-responsive dropdown-safe-table">
                    <table class="table table-hover align-middle admin-table mb-0">
                        <thead>
                            <tr>
                                <th class="text-nowrap" style="width: 80px;">No</th>
                                <th class="text-nowrap">Theme</th>
                                <th class="text-nowrap">Program</th>
                                <th class="text-end text-nowrap">Sort Order</th>
                                <th class="text-nowrap">Status</th>
                                <th class="text-end text-nowrap" style="width: 160px;">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($themes as $theme)
                                <tr>
                                    <td class="text-muted">
                                        {{ ($themes->currentPage() - 1) * $themes->perPage() + $loop->iteration }}
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ $theme->name }}
                                        </div>

                                        <div class="small text-muted">
                                            <code>{{ $theme->slug }}</code>
                                        </div>

                                        @if ($theme->description)
                                            <div class="small text-muted mt-1">
                                                {{ \Illuminate\Support\Str::limit($theme->description, 70) }}
                                            </div>
                                        @endif
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ $theme->program->name ?? '-' }}
                                        </div>
                                        <div class="small text-muted">Academic program</div>
                                    </td>

                                    <td class="text-end text-nowrap">
                                        {{ $theme->sort_order }}
                                    </td>

                                    <td class="text-nowrap">
                                        @if ($theme->is_active)
                                            <span class="badge rounded-pill bg-success-subtle text-success-emphasis border border-success-subtle">
                                                Active
                                            </span>
                                        @else
                                            <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle">
                                                Inactive
                                            </span>
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
                                                        onclick="editTheme({{ $theme->id }})"
                                                    >
                                                        <i class="bi bi-pencil-square me-2"></i>Edit Theme
                                                    </button>
                                                </li>

                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>

                                                <li>
                                                    <button
                                                        type="button"
                                                        class="dropdown-item text-danger"
                                                        onclick="openDeleteModal({{ $theme->id }}, @js($theme->name))"
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

                @if ($themes->hasPages())
                    <div class="mt-3">
                        {{ $themes->links() }}
                    </div>
                @endif
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-palette"></i>
                    </div>

                    <h5 class="empty-state-title">No trial themes found</h5>
                    <p class="empty-state-text mb-0">
                        Belum ada tema trial class yang tercatat. Tambahkan tema baru untuk mengatur pilihan trial berdasarkan program.
                    </p>

                    <div class="mt-3">
                        <button type="button" class="btn btn-primary btn-modern" onclick="openCreateModal()">
                            <i class="bi bi-plus-lg me-2"></i>Add Theme
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Theme Form Modal --}}
<div class="modal fade" id="themeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="themeForm">
            @csrf
            <input type="hidden" id="theme_id">

            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold mb-1" id="themeModalTitle">Add Theme</h5>
                        <div class="small text-muted">
                            Complete trial theme information, related program, display order, and active status.
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div id="formAlert" class="alert alert-danger d-none mb-3"></div>

                    <div class="content-card mb-3">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Theme Information</h5>
                                <p class="content-card-subtitle mb-0">
                                    Define the selected program, theme name, slug, sort order, and publication status.
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
                                    <label for="is_active" class="form-label">Status Active</label>
                                    <select id="is_active" class="form-select">
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                    </select>
                                    <div class="invalid-feedback" id="error_is_active"></div>
                                </div>

                                <div class="col-md-6">
                                    <label for="name" class="form-label">
                                        Theme Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" id="name" class="form-control">
                                    <div class="invalid-feedback" id="error_name"></div>
                                </div>

                                <div class="col-md-6">
                                    <label for="slug" class="form-label">Slug</label>
                                    <input type="text" id="slug" class="form-control">
                                    <div class="form-text">Optional. Will auto-generate from theme name if empty.</div>
                                    <div class="invalid-feedback" id="error_slug"></div>
                                </div>

                                <div class="col-md-6">
                                    <label for="sort_order" class="form-label">Sort Order</label>
                                    <input type="number" min="0" id="sort_order" class="form-control" value="0">
                                    <div class="invalid-feedback" id="error_sort_order"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="content-card">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Description</h5>
                                <p class="content-card-subtitle mb-0">
                                    Add optional notes or context for this trial theme.
                                </p>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <label for="description" class="form-label">Description</label>
                            <textarea
                                id="description"
                                rows="4"
                                class="form-control"
                                placeholder="Short description about this trial theme"
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
                    <h5 class="modal-title fw-bold mb-1">Delete Theme</h5>
                    <div class="small text-muted">
                        This action will remove selected trial theme from the system.
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="alert alert-danger mb-0">
                    <div class="d-flex gap-2 align-items-start">
                        <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                        <div>
                            <div class="fw-semibold">Delete this trial theme?</div>
                            <div class="small mt-1">
                                Are you sure you want to delete
                                <strong id="deleteThemeName"></strong>?
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
    const themeModalEl = document.getElementById('themeModal');
    const themeModal = new bootstrap.Modal(themeModalEl);
    const themeForm = document.getElementById('themeForm');
    const submitBtn = document.getElementById('submitBtn');
    const modalTitle = document.getElementById('themeModalTitle');
    const formAlert = document.getElementById('formAlert');

    const deleteModalEl = document.getElementById('deleteModal');
    const deleteModal = new bootstrap.Modal(deleteModalEl);
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const deleteThemeNameEl = document.getElementById('deleteThemeName');

    const fields = {
        id: document.getElementById('theme_id'),
        program_id: document.getElementById('program_id'),
        name: document.getElementById('name'),
        slug: document.getElementById('slug'),
        sort_order: document.getElementById('sort_order'),
        description: document.getElementById('description'),
        is_active: document.getElementById('is_active'),
    };

    let isEditMode = false;
    let autoSlug = true;
    let deleteThemeId = null;
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

    function setDeleteLoading(isLoading) {
        confirmDeleteBtn.disabled = isLoading;
        confirmDeleteBtn.querySelector('.default-delete-text').classList.toggle('d-none', isLoading);
        confirmDeleteBtn.querySelector('.loading-delete-text').classList.toggle('d-none', !isLoading);
    }

    function resetForm() {
        themeForm.reset();
        fields.id.value = '';
        fields.program_id.value = '';
        fields.name.value = '';
        fields.slug.value = '';
        fields.sort_order.value = 0;
        fields.description.value = '';
        fields.is_active.value = '1';
        formAlert.classList.add('d-none');
        formAlert.innerHTML = '';
        clearValidationErrors();
        setSubmitLoading(false);
        autoSlug = true;
    }

    function openCreateModal() {
        isEditMode = false;
        resetForm();
        modalTitle.textContent = 'Add Theme';
        themeModal.show();
    }

    async function editTheme(id) {
        isEditMode = true;
        resetForm();
        modalTitle.textContent = 'Edit Theme';

        try {
            const response = await fetch(`/trial/themes/${id}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to fetch theme data.');
            }

            const data = result.data;

            fields.id.value = data.id;
            fields.program_id.value = data.program_id ?? '';
            fields.name.value = data.name ?? '';
            fields.slug.value = data.slug ?? '';
            fields.sort_order.value = data.sort_order ?? 0;
            fields.description.value = data.description ?? '';
            fields.is_active.value = data.is_active ? '1' : '0';

            autoSlug = false;
            themeModal.show();
        } catch (error) {
            formAlert.classList.remove('d-none');
            formAlert.innerHTML = error.message || 'Failed to load theme data.';
            themeModal.show();
        }
    }

    function openDeleteModal(id, name) {
        deleteThemeId = id;
        deleteThemeNameEl.textContent = name || '-';
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

    themeForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        clearValidationErrors();
        formAlert.classList.add('d-none');
        formAlert.innerHTML = '';

        const id = fields.id.value;
        const url = id ? `/trial/themes/${id}` : `/trial/themes`;

        const payload = {
            program_id: fields.program_id.value,
            name: fields.name.value.trim(),
            slug: fields.slug.value.trim(),
            sort_order: fields.sort_order.value === '' ? 0 : Number(fields.sort_order.value),
            description: fields.description.value.trim(),
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
                throw new Error(result.message || 'Failed to save theme.');
            }

            themeModal.hide();
            showToast(result.message || 'Theme saved successfully', 'success');
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
        if (!deleteThemeId) return;

        setDeleteLoading(true);

        try {
            const response = await fetch(`/trial/themes/${deleteThemeId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to delete theme.');
            }

            deleteModal.hide();
            showToast(result.message || 'Theme deleted successfully', 'danger');
            scheduleReload();
        } catch (error) {
            showToast(error.message || 'Failed to delete theme.', 'danger');
        } finally {
            setDeleteLoading(false);
            deleteThemeId = null;
        }
    });

    themeModalEl.addEventListener('hidden.bs.modal', function () {
        resetForm();
    });

    deleteModalEl.addEventListener('hidden.bs.modal', function () {
        deleteThemeId = null;
        deleteThemeNameEl.textContent = '';
        setDeleteLoading(false);
    });
</script>
@endpush