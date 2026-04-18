@extends('layouts.app-dashboard')

@section('title', 'Marketing Campaigns')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="mb-1">Marketing Campaigns</h4>
            <small class="text-muted">Manage campaign execution under each marketing plan.</small>
        </div>

        <button type="button" class="btn btn-primary" onclick="openCreateModal()">
            <i class="bi bi-plus-lg me-1"></i> Add Campaign
        </button>
    </div>

    <div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080;"></div>

    <div class="card shadow-sm border-0">
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('marketing.campaigns.index') }}" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <input
                        type="text"
                        name="search"
                        class="form-control form-control-sm"
                        placeholder="Search campaign name, channel, or objective..."
                        value="{{ request('search') }}"
                    >
                </div>

                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="planned" {{ request('status') === 'planned' ? 'selected' : '' }}>Planned</option>
                        <option value="ongoing" {{ request('status') === 'ongoing' ? 'selected' : '' }}>Ongoing</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <select name="marketing_plan_id" class="form-select form-select-sm">
                        <option value="">All Plans</option>
                        @foreach ($plans as $plan)
                            <option value="{{ $plan->id }}" {{ (string) request('marketing_plan_id') === (string) $plan->id ? 'selected' : '' }}>
                                {{ $plan->title }}
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
                    <a href="{{ route('marketing.campaigns.index') }}" class="btn btn-sm btn-light border">
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
                        <th>Campaign</th>
                        <th style="width: 180px;">Plan</th>
                        <th style="width: 120px;">Channel</th>
                        <th style="width: 120px;">Type</th>
                        <th style="width: 170px;">Date Range</th>
                        <th style="width: 140px;">Budget</th>
                        <th style="width: 120px;">Status</th>
                        <th style="width: 120px;">Active</th>
                        <th style="width: 160px;" class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($campaigns as $campaign)
                        @php
                            $statusClass = match($campaign->status) {
                                'planned' => 'secondary',
                                'ongoing' => 'primary',
                                'completed' => 'success',
                                'cancelled' => 'danger',
                                default => 'secondary',
                            };
                        @endphp

                        <tr>
                            <td>
                                {{ ($campaigns->currentPage() - 1) * $campaigns->perPage() + $loop->iteration }}
                            </td>

                            <td>
                                <div class="fw-semibold">{{ $campaign->name }}</div>
                                <small class="text-muted">
                                    {{ $campaign->description ? \Illuminate\Support\Str::limit($campaign->description, 70) : '-' }}
                                </small>
                            </td>

                            <td>
                                {{ $campaign->plan?->title ?: '-' }}
                            </td>

                            <td>
                                {{ $campaign->channel ?: '-' }}
                            </td>

                            <td>
                                {{ $campaign->type ?: '-' }}
                            </td>

                            <td class="text-muted small">
                                @if ($campaign->start_date && $campaign->end_date)
                                    {{ $campaign->start_date->format('d M Y') }}<br>
                                    <span class="text-muted">to {{ $campaign->end_date->format('d M Y') }}</span>
                                @elseif ($campaign->start_date)
                                    {{ $campaign->start_date->format('d M Y') }}
                                @elseif ($campaign->end_date)
                                    {{ $campaign->end_date->format('d M Y') }}
                                @else
                                    -
                                @endif
                            </td>

                            <td class="fw-semibold">
                                Rp {{ number_format((float) $campaign->budget, 0, ',', '.') }}
                            </td>

                            <td>
                                <span class="badge bg-{{ $statusClass }}">{{ ucfirst($campaign->status) }}</span>
                            </td>

                            <td>
                                @if ($campaign->is_active)
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
                                        data-id="{{ $campaign->id }}"
                                        data-marketing_plan_id="{{ $campaign->marketing_plan_id }}"
                                        data-name="{{ e($campaign->name) }}"
                                        data-channel="{{ e($campaign->channel ?? '') }}"
                                        data-type="{{ e($campaign->type ?? '') }}"
                                        data-start_date="{{ optional($campaign->start_date)->format('Y-m-d') }}"
                                        data-end_date="{{ optional($campaign->end_date)->format('Y-m-d') }}"
                                        data-budget="{{ $campaign->budget }}"
                                        data-target_leads="{{ $campaign->target_leads }}"
                                        data-target_conversions="{{ $campaign->target_conversions }}"
                                        data-actual_leads="{{ $campaign->actual_leads }}"
                                        data-actual_conversions="{{ $campaign->actual_conversions }}"
                                        data-status="{{ e($campaign->status) }}"
                                        data-description="{{ e($campaign->description ?? '') }}"
                                        data-notes="{{ e($campaign->notes ?? '') }}"
                                        data-pic_user_id="{{ $campaign->pic_user_id }}"
                                        data-is_active="{{ $campaign->is_active ? 1 : 0 }}"
                                        onclick="editCampaign(this)"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        data-id="{{ $campaign->id }}"
                                        data-name="{{ e($campaign->name) }}"
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
                                No marketing campaigns found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($campaigns->hasPages())
            <div class="card-footer bg-white">
                {{ $campaigns->links() }}
            </div>
        @endif
    </div>
</div>

{{-- Form Modal --}}
<div class="modal fade" id="campaignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <form id="campaignForm">
            @csrf
            <input type="hidden" id="campaign_id">

            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title" id="campaignModalTitle">Add Campaign</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div id="formAlert" class="alert alert-danger d-none mb-3"></div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="marketing_plan_id" class="form-label">
                                Plan <span class="text-danger">*</span>
                            </label>
                            <select id="marketing_plan_id" class="form-select">
                                <option value="">Select Plan</option>
                                @foreach ($plans as $plan)
                                    <option value="{{ $plan->id }}">{{ $plan->title }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="error_marketing_plan_id"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="name" class="form-label">
                                Campaign Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="name" class="form-control">
                            <div class="invalid-feedback" id="error_name"></div>
                        </div>

                        <div class="col-md-4">
                            <label for="channel" class="form-label">Channel</label>
                            <input type="text" id="channel" class="form-control" placeholder="Instagram / TikTok / Meta Ads / Event">
                            <div class="invalid-feedback" id="error_channel"></div>
                        </div>

                        <div class="col-md-4">
                            <label for="type" class="form-label">Type</label>
                            <input type="text" id="type" class="form-control" placeholder="Awareness / Lead Generation / Promo">
                            <div class="invalid-feedback" id="error_type"></div>
                        </div>

                        <div class="col-md-4">
                            <label for="budget" class="form-label">Budget</label>
                            <input type="number" id="budget" class="form-control" min="0" step="0.01">
                            <div class="invalid-feedback" id="error_budget"></div>
                        </div>

                        <div class="col-md-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" id="start_date" class="form-control">
                            <div class="invalid-feedback" id="error_start_date"></div>
                        </div>

                        <div class="col-md-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" id="end_date" class="form-control">
                            <div class="invalid-feedback" id="error_end_date"></div>
                        </div>

                        <div class="col-md-3">
                            <label for="status" class="form-label">
                                Status <span class="text-danger">*</span>
                            </label>
                            <select id="status" class="form-select">
                                <option value="planned">Planned</option>
                                <option value="ongoing">Ongoing</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <div class="invalid-feedback" id="error_status"></div>
                        </div>

                        <div class="col-md-3 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" checked>
                                <label class="form-check-label" for="is_active">
                                    Active
                                </label>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label for="target_leads" class="form-label">Target Leads</label>
                            <input type="number" id="target_leads" class="form-control" min="0">
                            <div class="invalid-feedback" id="error_target_leads"></div>
                        </div>

                        <div class="col-md-3">
                            <label for="target_conversions" class="form-label">Target Conversions</label>
                            <input type="number" id="target_conversions" class="form-control" min="0">
                            <div class="invalid-feedback" id="error_target_conversions"></div>
                        </div>

                        <div class="col-md-3">
                            <label for="actual_leads" class="form-label">Actual Leads</label>
                            <input type="number" id="actual_leads" class="form-control" min="0">
                            <div class="invalid-feedback" id="error_actual_leads"></div>
                        </div>

                        <div class="col-md-3">
                            <label for="actual_conversions" class="form-label">Actual Conversions</label>
                            <input type="number" id="actual_conversions" class="form-control" min="0">
                            <div class="invalid-feedback" id="error_actual_conversions"></div>
                        </div>

                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea id="description" rows="3" class="form-control"></textarea>
                            <div class="invalid-feedback" id="error_description"></div>
                        </div>

                        <div class="col-12">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea id="notes" rows="3" class="form-control"></textarea>
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
                <h5 class="modal-title">Delete Campaign</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <p class="mb-0">
                    Are you sure you want to delete
                    <strong id="deleteCampaignName"></strong>?
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
    const campaignModalEl = document.getElementById('campaignModal');
    const campaignModal = new bootstrap.Modal(campaignModalEl);
    const campaignForm = document.getElementById('campaignForm');
    const submitBtn = document.getElementById('submitBtn');
    const modalTitle = document.getElementById('campaignModalTitle');
    const formAlert = document.getElementById('formAlert');

    const deleteModalEl = document.getElementById('deleteModal');
    const deleteModal = new bootstrap.Modal(deleteModalEl);
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const deleteCampaignNameEl = document.getElementById('deleteCampaignName');

    const fields = {
        id: document.getElementById('campaign_id'),
        marketing_plan_id: document.getElementById('marketing_plan_id'),
        name: document.getElementById('name'),
        channel: document.getElementById('channel'),
        type: document.getElementById('type'),
        start_date: document.getElementById('start_date'),
        end_date: document.getElementById('end_date'),
        budget: document.getElementById('budget'),
        target_leads: document.getElementById('target_leads'),
        target_conversions: document.getElementById('target_conversions'),
        actual_leads: document.getElementById('actual_leads'),
        actual_conversions: document.getElementById('actual_conversions'),
        status: document.getElementById('status'),
        description: document.getElementById('description'),
        notes: document.getElementById('notes'),
        is_active: document.getElementById('is_active'),
    };

    let isEditMode = false;
    let deleteCampaignId = null;
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
        fields.marketing_plan_id.value = '';
        fields.name.value = '';
        fields.channel.value = '';
        fields.type.value = '';
        fields.start_date.value = '';
        fields.end_date.value = '';
        fields.budget.value = '';
        fields.target_leads.value = '';
        fields.target_conversions.value = '';
        fields.actual_leads.value = '';
        fields.actual_conversions.value = '';
        fields.status.value = 'planned';
        fields.description.value = '';
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
        modalTitle.textContent = 'Add Campaign';
        campaignModal.show();
    }

    function editCampaign(button) {
        isEditMode = true;
        resetForm();

        modalTitle.textContent = 'Edit Campaign';
        fields.id.value = button.dataset.id || '';
        fields.marketing_plan_id.value = button.dataset.marketing_plan_id || '';
        fields.name.value = decodeHtml(button.dataset.name || '');
        fields.channel.value = decodeHtml(button.dataset.channel || '');
        fields.type.value = decodeHtml(button.dataset.type || '');
        fields.start_date.value = button.dataset.start_date || '';
        fields.end_date.value = button.dataset.end_date || '';
        fields.budget.value = button.dataset.budget || '';
        fields.target_leads.value = button.dataset.target_leads || '';
        fields.target_conversions.value = button.dataset.target_conversions || '';
        fields.actual_leads.value = button.dataset.actual_leads || '';
        fields.actual_conversions.value = button.dataset.actual_conversions || '';
        fields.status.value = button.dataset.status || 'planned';
        fields.description.value = decodeHtml(button.dataset.description || '');
        fields.notes.value = decodeHtml(button.dataset.notes || '');
        fields.is_active.checked = button.dataset.is_active === '1';

        campaignModal.show();
    }

    async function submitForm(event) {
        event.preventDefault();
        resetValidation();

        const id = fields.id.value;
        const url = isEditMode
            ? `{{ url('/marketing/campaigns') }}/${id}`
            : `{{ route('marketing.campaigns.store') }}`;

        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');

        if (isEditMode) {
            formData.append('_method', 'PUT');
        }

        formData.append('marketing_plan_id', fields.marketing_plan_id.value);
        formData.append('name', fields.name.value);
        formData.append('channel', fields.channel.value);
        formData.append('type', fields.type.value);
        formData.append('start_date', fields.start_date.value);
        formData.append('end_date', fields.end_date.value);
        formData.append('budget', fields.budget.value);
        formData.append('target_leads', fields.target_leads.value);
        formData.append('target_conversions', fields.target_conversions.value);
        formData.append('actual_leads', fields.actual_leads.value);
        formData.append('actual_conversions', fields.actual_conversions.value);
        formData.append('status', fields.status.value);
        formData.append('description', fields.description.value);
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

                throw new Error(data.message || 'Failed to save campaign.');
            }

            campaignModal.hide();
            showToast(data.message || (isEditMode ? 'Campaign updated successfully.' : 'Campaign created successfully.'));

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
        deleteCampaignId = button.dataset.id;
        deleteCampaignNameEl.textContent = decodeHtml(button.dataset.name || '');
        deleteModal.show();
    }

    async function confirmDelete() {
        if (!deleteCampaignId) return;

        setLoading(confirmDeleteBtn, true, '.default-delete-text', '.loading-delete-text');

        try {
            const response = await fetch(`{{ url('/marketing/campaigns') }}/${deleteCampaignId}`, {
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
                throw new Error(data.message || 'Failed to delete campaign.');
            }

            deleteModal.hide();
            showToast(data.message || 'Campaign deleted successfully.');

            clearTimeout(reloadTimeout);
            reloadTimeout = setTimeout(() => window.location.reload(), 700);
        } catch (error) {
            showToast(error.message || 'Something went wrong.', 'danger');
        } finally {
            setLoading(confirmDeleteBtn, false, '.default-delete-text', '.loading-delete-text');
        }
    }

    campaignForm.addEventListener('submit', submitForm);
    confirmDeleteBtn.addEventListener('click', confirmDelete);
    campaignModalEl.addEventListener('hidden.bs.modal', resetForm);
</script>
@endpush