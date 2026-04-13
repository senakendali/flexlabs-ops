@extends('layouts.app-dashboard')

@section('title', 'Batches')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="mb-1">Batches</h4>
            <small class="text-muted">Manage batch data for each program enrollment</small>
        </div>

        <button type="button" class="btn btn-primary" onclick="openCreateModal()">
            <i class="bi bi-plus-lg me-1"></i> Add Batch
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
                        <th>Batch Name</th>
                        <th>Program</th>
                        <th>Period</th>
                        <th>Quota</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th style="width: 170px;" class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($batches as $batch)
                        <tr>
                            <td>
                                {{ ($batches->currentPage() - 1) * $batches->perPage() + $loop->iteration }}
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $batch->name }}</div>
                                <div class="small text-muted">
                                    <code>{{ $batch->slug }}</code>
                                </div>
                            </td>
                            <td>{{ $batch->program->name ?? '-' }}</td>
                            <td class="text-muted">
                                @if ($batch->start_date || $batch->end_date)
                                    {{ $batch->start_date?->format('d M Y') ?? '-' }}
                                    -
                                    {{ $batch->end_date?->format('d M Y') ?? '-' }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $batch->quota ?? '-' }}</td>
                            <td>
                                Rp {{ number_format((float) $batch->price, 0, ',', '.') }}
                            </td>
                            <td>
                                @php
                                    $statusClass = match($batch->status) {
                                        'draft' => 'bg-secondary',
                                        'open' => 'bg-success',
                                        'closed' => 'bg-danger',
                                        'ongoing' => 'bg-primary',
                                        'completed' => 'bg-dark',
                                        default => 'bg-secondary',
                                    };
                                @endphp
                                <span class="badge {{ $statusClass }}">
                                    {{ ucfirst($batch->status) }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-inline-flex gap-2">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        onclick="editBatch({{ $batch->id }})"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="openDeleteModal({{ $batch->id }}, @js($batch->name))"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                No batches found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($batches->hasPages())
            <div class="card-footer bg-white">
                {{ $batches->links() }}
            </div>
        @endif
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
                    <h5 class="modal-title" id="batchModalTitle">Add Batch</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div id="formAlert" class="alert alert-danger d-none mb-3"></div>

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
                            <input type="number" id="quota" class="form-control" min="1" placeholder="e.g. 20">
                            <div class="invalid-feedback" id="error_quota"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="price" class="form-label">Price</label>
                            <input type="number" id="price" class="form-control" min="0" step="0.01" placeholder="e.g. 2500000">
                            <div class="invalid-feedback" id="error_price"></div>
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

{{-- Delete Modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title">Delete Batch</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <p class="mb-0">
                    Are you sure you want to delete
                    <strong id="deleteBatchName"></strong>?
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

        const html = `
            <div id="${id}" class="toast align-items-center text-white ${bgClass} border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', html);

        const toastEl = document.getElementById(id);
        const toast = new bootstrap.Toast(toastEl, {
            delay: 3000
        });

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
        }, 3000);
    }

    function resetForm() {
        batchForm.reset();
        fields.id.value = '';
        fields.program_id.value = '';
        fields.status.value = 'draft';
        formAlert.classList.add('d-none');
        formAlert.innerHTML = '';
        clearValidationErrors();
        setSubmitLoading(false);
        autoSlug = true;
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

    function slugify(text) {
        return String(text || '')
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');
    }

    function openCreateModal() {
        isEditMode = false;
        resetForm();
        modalTitle.textContent = 'Add Batch';
        batchModal.show();
    }

    async function editBatch(id) {
        isEditMode = true;
        resetForm();
        modalTitle.textContent = 'Edit Batch';

        try {
            const response = await fetch(`/enrollment/batches/${id}`, {
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

            autoSlug = false;
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
        formAlert.classList.add('d-none');
        formAlert.innerHTML = '';

        const id = fields.id.value;
        const url = id ? `/enrollment/batches/${id}` : `/enrollment/batches`;

        const payload = {
            program_id: fields.program_id.value,
            name: fields.name.value.trim(),
            slug: fields.slug.value.trim(),
            start_date: fields.start_date.value || null,
            end_date: fields.end_date.value || null,
            quota: fields.quota.value ? parseInt(fields.quota.value, 10) : null,
            price: fields.price.value ? parseFloat(fields.price.value) : 0,
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
            showToast(result.message || 'Batch saved successfully', 'success');
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
            const response = await fetch(`/enrollment/batches/${deleteBatchId}`, {
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
            showToast(result.message || 'Batch deleted successfully', 'danger');
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