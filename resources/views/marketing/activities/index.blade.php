@extends('layouts.app-dashboard')

@section('title', 'Marketing Activities')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="mb-1">Marketing Activities</h4>
            <small class="text-muted">Track daily execution for each marketing campaign.</small>
        </div>

        <button type="button" class="btn btn-primary" onclick="openCreateModal()">
            <i class="bi bi-plus-lg me-1"></i> Add Activity
        </button>
    </div>

    <div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080;"></div>

    <div class="card shadow-sm border-0">
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('marketing.activities.index') }}" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <input
                        type="text"
                        name="search"
                        class="form-control form-control-sm"
                        placeholder="Search title, channel, or activity type..."
                        value="{{ request('search') }}"
                    >
                </div>

                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="planned" {{ request('status') === 'planned' ? 'selected' : '' }}>Planned</option>
                        <option value="ongoing" {{ request('status') === 'ongoing' ? 'selected' : '' }}>Ongoing</option>
                        <option value="done" {{ request('status') === 'done' ? 'selected' : '' }}>Done</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <select name="marketing_campaign_id" class="form-select form-select-sm">
                        <option value="">All Campaigns</option>
                        @foreach ($campaigns as $campaign)
                            <option value="{{ $campaign->id }}" {{ (string) request('marketing_campaign_id') === (string) $campaign->id ? 'selected' : '' }}>
                                {{ $campaign->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-auto">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-search me-1"></i> Filter
                    </button>
                </div>

                <div class="col-md-auto">
                    <a href="{{ route('marketing.activities.index') }}" class="btn btn-sm btn-light border">
                        Reset
                    </a>
                </div>

                <div class="col-md-auto ms-md-auto">
                    <div class="d-flex align-items-center gap-2">
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
                    </div>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 80px;">No</th>
                        <th>Activity</th>
                        <th style="width: 180px;">Campaign</th>
                        <th style="width: 120px;">Date</th>
                        <th style="width: 120px;">Channel</th>
                        <th style="width: 140px;">Type</th>
                        <th style="width: 120px;">PIC</th>
                        <th style="width: 120px;">Status</th>
                        <th style="width: 120px;">Active</th>
                        <th style="width: 160px;" class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($activities as $activity)
                        @php
                            $statusClass = match($activity->status) {
                                'planned' => 'secondary',
                                'ongoing' => 'primary',
                                'done' => 'success',
                                'cancelled' => 'danger',
                                default => 'secondary',
                            };
                        @endphp

                        <tr>
                            <td>
                                {{ ($activities->currentPage() - 1) * $activities->perPage() + $loop->iteration }}
                            </td>

                            <td>
                                <div class="fw-semibold">{{ $activity->title }}</div>
                                <small class="text-muted">
                                    {{ $activity->description ? \Illuminate\Support\Str::limit($activity->description, 70) : '-' }}
                                </small>
                            </td>

                            <td>
                                {{ $activity->campaign?->name ?: '-' }}
                            </td>

                            <td>
                                {{ optional($activity->activity_date)->format('d M Y') ?: '-' }}
                            </td>

                            <td>
                                {{ $activity->channel ?: '-' }}
                            </td>

                            <td>
                                {{ $activity->activity_type ?: '-' }}
                            </td>

                            <td>
                                {{ $activity->pic?->name ?: '-' }}
                            </td>

                            <td>
                                <span class="badge bg-{{ $statusClass }}">{{ ucfirst($activity->status) }}</span>
                            </td>

                            <td>
                                @if ($activity->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>

                            <td class="text-center">
                                <div class="d-inline-flex gap-2">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        data-id="{{ $activity->id }}"
                                        data-marketing_campaign_id="{{ $activity->marketing_campaign_id }}"
                                        data-activity_date="{{ optional($activity->activity_date)->format('Y-m-d') }}"
                                        data-channel="{{ e($activity->channel ?? '') }}"
                                        data-activity_type="{{ e($activity->activity_type ?? '') }}"
                                        data-title="{{ e($activity->title) }}"
                                        data-description="{{ e($activity->description ?? '') }}"
                                        data-status="{{ e($activity->status) }}"
                                        data-output_link="{{ e($activity->output_link ?? '') }}"
                                        data-pic_user_id="{{ $activity->pic_user_id }}"
                                        data-notes="{{ e($activity->notes ?? '') }}"
                                        data-is_active="{{ $activity->is_active ? 1 : 0 }}"
                                        onclick="editActivity(this)"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        data-id="{{ $activity->id }}"
                                        data-title="{{ e($activity->title) }}"
                                        onclick="openDeleteModal(this)"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">
                                No marketing activities found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($activities->hasPages())
            <div class="card-footer bg-white">
                {{ $activities->links() }}
            </div>
        @endif
    </div>
</div>

{{-- Form Modal --}}
<div class="modal fade" id="activityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <form id="activityForm">
            @csrf
            <input type="hidden" id="activity_id">

            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title" id="activityModalTitle">Add Activity</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div id="formAlert" class="alert alert-danger d-none mb-3"></div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="marketing_campaign_id" class="form-label">Campaign</label>
                            <select id="marketing_campaign_id" class="form-select">
                                <option value="">Select Campaign</option>
                                @foreach ($campaigns as $campaign)
                                    <option value="{{ $campaign->id }}">{{ $campaign->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="error_marketing_campaign_id"></div>
                        </div>

                        <div class="col-md-3">
                            <label for="activity_date" class="form-label">
                                Activity Date <span class="text-danger">*</span>
                            </label>
                            <input type="date" id="activity_date" class="form-control">
                            <div class="invalid-feedback" id="error_activity_date"></div>
                        </div>

                        <div class="col-md-3">
                            <label for="status" class="form-label">
                                Status <span class="text-danger">*</span>
                            </label>
                            <select id="status" class="form-select">
                                <option value="planned">Planned</option>
                                <option value="ongoing">Ongoing</option>
                                <option value="done">Done</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <div class="invalid-feedback" id="error_status"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="title" class="form-label">
                                Title <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="title" class="form-control">
                            <div class="invalid-feedback" id="error_title"></div>
                        </div>

                        <div class="col-md-3">
                            <label for="channel" class="form-label">Channel</label>
                            <input type="text" id="channel" class="form-control" placeholder="Instagram / TikTok / Event">
                            <div class="invalid-feedback" id="error_channel"></div>
                        </div>

                        <div class="col-md-3">
                            <label for="activity_type" class="form-label">Activity Type</label>
                            <input type="text" id="activity_type" class="form-control" placeholder="Content Post / Design / Meeting">
                            <div class="invalid-feedback" id="error_activity_type"></div>
                        </div>

                        <div class="col-md-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea id="description" rows="3" class="form-control"></textarea>
                            <div class="invalid-feedback" id="error_description"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="output_link" class="form-label">Output Link</label>
                            <input type="url" id="output_link" class="form-control" placeholder="https://...">
                            <div class="invalid-feedback" id="error_output_link"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="pic_user_id" class="form-label">PIC</label>
                            <select id="pic_user_id" class="form-select">
                                <option value="">Select PIC</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="error_pic_user_id"></div>
                        </div>

                        <div class="col-md-9">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea id="notes" rows="3" class="form-control"></textarea>
                            <div class="invalid-feedback" id="error_notes"></div>
                        </div>

                        <div class="col-md-3 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" checked>
                                <label class="form-check-label" for="is_active">
                                    Active
                                </label>
                            </div>
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
                <h5 class="modal-title">Delete Activity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <p class="mb-0">
                    Are you sure you want to delete
                    <strong id="deleteActivityTitle"></strong>?
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
    const activityModalEl = document.getElementById('activityModal');
    const activityModal = new bootstrap.Modal(activityModalEl);
    const activityForm = document.getElementById('activityForm');
    const submitBtn = document.getElementById('submitBtn');
    const modalTitle = document.getElementById('activityModalTitle');
    const formAlert = document.getElementById('formAlert');

    const deleteModalEl = document.getElementById('deleteModal');
    const deleteModal = new bootstrap.Modal(deleteModalEl);
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const deleteActivityTitleEl = document.getElementById('deleteActivityTitle');

    const fields = {
        id: document.getElementById('activity_id'),
        marketing_campaign_id: document.getElementById('marketing_campaign_id'),
        activity_date: document.getElementById('activity_date'),
        channel: document.getElementById('channel'),
        activity_type: document.getElementById('activity_type'),
        title: document.getElementById('title'),
        description: document.getElementById('description'),
        status: document.getElementById('status'),
        output_link: document.getElementById('output_link'),
        pic_user_id: document.getElementById('pic_user_id'),
        notes: document.getElementById('notes'),
        is_active: document.getElementById('is_active'),
    };

    let isEditMode = false;
    let deleteActivityId = null;
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
        const toast = new bootstrap.Toast(toastEl, { delay: 2500 });
        toast.show();

        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    }

    function resetValidation() {
        formAlert.classList.add('d-none');
        formAlert.innerHTML = '';

        Object.keys(fields).forEach((key) => {
            if (fields[key] && fields[key].classList) {
                fields[key].classList.remove('is-invalid');
            }

            const errorEl = document.getElementById(`error_${key}`);
            if (errorEl) {
                errorEl.textContent = '';
            }
        });
    }

    function resetForm() {
        resetValidation();

        fields.id.value = '';
        fields.marketing_campaign_id.value = '';
        fields.activity_date.value = '';
        fields.channel.value = '';
        fields.activity_type.value = '';
        fields.title.value = '';
        fields.description.value = '';
        fields.status.value = 'planned';
        fields.output_link.value = '';
        fields.pic_user_id.value = '';
        fields.notes.value = '';
        fields.is_active.checked = true;
    }

    function setLoading(button, isLoading, defaultSelector, loadingSelector) {
        button.disabled = isLoading;
        button.querySelector(defaultSelector).classList.toggle('d-none', isLoading);
        button.querySelector(loadingSelector).classList.toggle('d-none', !isLoading);
    }

    function decodeHtml(value) {
        const txt = document.createElement('textarea');
        txt.innerHTML = value || '';
        return txt.value;
    }

    function openCreateModal() {
        isEditMode = false;
        resetForm();
        modalTitle.textContent = 'Add Activity';

        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');
        fields.activity_date.value = `${yyyy}-${mm}-${dd}`;

        activityModal.show();
    }

    function editActivity(button) {
        isEditMode = true;
        resetForm();

        modalTitle.textContent = 'Edit Activity';
        fields.id.value = button.dataset.id || '';
        fields.marketing_campaign_id.value = button.dataset.marketing_campaign_id || '';
        fields.activity_date.value = button.dataset.activity_date || '';
        fields.channel.value = decodeHtml(button.dataset.channel || '');
        fields.activity_type.value = decodeHtml(button.dataset.activity_type || '');
        fields.title.value = decodeHtml(button.dataset.title || '');
        fields.description.value = decodeHtml(button.dataset.description || '');
        fields.status.value = button.dataset.status || 'planned';
        fields.output_link.value = decodeHtml(button.dataset.output_link || '');
        fields.pic_user_id.value = button.dataset.pic_user_id || '';
        fields.notes.value = decodeHtml(button.dataset.notes || '');
        fields.is_active.checked = button.dataset.is_active === '1';

        activityModal.show();
    }

    async function submitForm(event) {
        event.preventDefault();
        resetValidation();

        const id = fields.id.value;
        const url = isEditMode
            ? `{{ url('/marketing/activities') }}/${id}`
            : `{{ route('marketing.activities.store') }}`;

        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');

        if (isEditMode) {
            formData.append('_method', 'PUT');
        }

        formData.append('marketing_campaign_id', fields.marketing_campaign_id.value);
        formData.append('activity_date', fields.activity_date.value);
        formData.append('channel', fields.channel.value);
        formData.append('activity_type', fields.activity_type.value);
        formData.append('title', fields.title.value);
        formData.append('description', fields.description.value);
        formData.append('status', fields.status.value);
        formData.append('output_link', fields.output_link.value);
        formData.append('pic_user_id', fields.pic_user_id.value);
        formData.append('notes', fields.notes.value);

        if (fields.is_active.checked) {
            formData.append('is_active', '1');
        }

        setLoading(submitBtn, true, '.default-text', '.loading-text');

        try {
            const response = await fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            const data = await response.json();

            if (!response.ok) {
                if (response.status === 422 && data.errors) {
                    Object.entries(data.errors).forEach(([field, messages]) => {
                        if (fields[field]) {
                            fields[field].classList.add('is-invalid');
                        }

                        const errorEl = document.getElementById(`error_${field}`);
                        if (errorEl) {
                            errorEl.textContent = messages[0];
                        }
                    });

                    formAlert.classList.remove('d-none');
                    formAlert.textContent = data.message || 'Please check the form fields.';
                    return;
                }

                throw new Error(data.message || 'Failed to save activity.');
            }

            activityModal.hide();
            showToast(data.message || (isEditMode ? 'Activity updated successfully.' : 'Activity created successfully.'));

            clearTimeout(reloadTimeout);
            reloadTimeout = setTimeout(() => window.location.reload(), 700);
        } catch (error) {
            formAlert.classList.remove('d-none');
            formAlert.textContent = error.message || 'Something went wrong.';
        } finally {
            setLoading(submitBtn, false, '.default-text', '.loading-text');
        }
    }

    function openDeleteModal(button) {
        deleteActivityId = button.dataset.id;
        deleteActivityTitleEl.textContent = decodeHtml(button.dataset.title || '');
        deleteModal.show();
    }

    async function confirmDelete() {
        if (!deleteActivityId) return;

        setLoading(confirmDeleteBtn, true, '.default-delete-text', '.loading-delete-text');

        try {
            const response = await fetch(`{{ url('/marketing/activities') }}/${deleteActivityId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: new URLSearchParams({
                    _method: 'DELETE',
                }),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Failed to delete activity.');
            }

            deleteModal.hide();
            showToast(data.message || 'Activity deleted successfully.');

            clearTimeout(reloadTimeout);
            reloadTimeout = setTimeout(() => window.location.reload(), 700);
        } catch (error) {
            showToast(error.message || 'Something went wrong.', 'danger');
        } finally {
            setLoading(confirmDeleteBtn, false, '.default-delete-text', '.loading-delete-text');
        }
    }

    activityForm.addEventListener('submit', submitForm);
    confirmDeleteBtn.addEventListener('click', confirmDelete);
    activityModalEl.addEventListener('hidden.bs.modal', resetForm);
</script>
@endpush