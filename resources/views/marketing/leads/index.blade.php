@extends('layouts.app-dashboard')

@section('title', 'Lead Sources')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="mb-1">Lead Sources</h4>
            <small class="text-muted">Monitor summary lead performance from ads, events, organic, referral, and other channels.</small>
        </div>

        <button type="button" class="btn btn-primary" onclick="openCreateModal()">
            <i class="bi bi-plus-lg me-1"></i> Add Lead Source Summary
        </button>
    </div>

    <div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080;"></div>

    <div class="card shadow-sm border-0">
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('marketing.leads.index') }}" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <input
                        type="text"
                        name="search"
                        class="form-control form-control-sm"
                        placeholder="Search source, campaign, or event..."
                        value="{{ request('search') }}"
                    >
                </div>

                <div class="col-md-2">
                    <select name="source_type" class="form-select form-select-sm">
                        <option value="">All Source Type</option>
                        <option value="ads" {{ request('source_type') === 'ads' ? 'selected' : '' }}>Ads</option>
                        <option value="event" {{ request('source_type') === 'event' ? 'selected' : '' }}>Event</option>
                        <option value="organic" {{ request('source_type') === 'organic' ? 'selected' : '' }}>Organic</option>
                        <option value="referral" {{ request('source_type') === 'referral' ? 'selected' : '' }}>Referral</option>
                        <option value="direct" {{ request('source_type') === 'direct' ? 'selected' : '' }}>Direct</option>
                        <option value="partnership" {{ request('source_type') === 'partnership' ? 'selected' : '' }}>Partnership</option>
                    </select>
                </div>

                <div class="col-md-auto">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-search me-1"></i> Filter
                    </button>
                </div>

                <div class="col-md-auto">
                    <a href="{{ route('marketing.leads.index') }}" class="btn btn-sm btn-light border">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60px;">No</th>
                        <th>Source</th>
                        <th>Campaign / Event</th>
                        <th>Date</th>
                        <th class="text-end">Leads</th>
                        <th class="text-end">Qualified</th>
                        <th class="text-end">Conversions</th>
                        <th class="text-end">Revenue</th>
                        <th>Status</th>
                        <th class="text-center" style="width: 180px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($leads as $lead)
                        <tr>
                            <td>{{ $leads->firstItem() + $loop->index }}</td>
                            <td>
                                <div class="fw-semibold">{{ $lead->source_name ?: '-' }}</div>
                                <div class="small text-muted">{{ $lead->source_type_label }}</div>
                            </td>
                            <td>
                                <div>{{ $lead->campaign?->name ?? '-' }}</div>
                                @if ($lead->event?->name)
                                    <div class="small text-muted">{{ $lead->event->name }}</div>
                                @endif
                            </td>
                            <td>{{ $lead->date?->format('d M Y') ?? '-' }}</td>
                            <td class="text-end">{{ number_format($lead->leads ?? 0, 0, ',', '.') }}</td>
                            <td class="text-end">
                                <div>{{ number_format($lead->qualified_leads ?? 0, 0, ',', '.') }}</div>
                                <div class="small text-muted">{{ number_format($lead->qualification_rate ?? 0, 2) }}%</div>
                            </td>
                            <td class="text-end">
                                <div>{{ number_format($lead->conversions ?? 0, 0, ',', '.') }}</div>
                                <div class="small text-muted">{{ number_format($lead->conversion_rate ?? 0, 2) }}%</div>
                            </td>
                            <td class="text-end">Rp{{ number_format($lead->revenue ?? 0, 0, ',', '.') }}</td>
                            <td>
                                @if ($lead->is_active)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Inactive</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-inline-flex gap-1">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-secondary"
                                        data-id="{{ $lead->id }}"
                                        onclick="viewLeadSource(this)"
                                        title="View"
                                    >
                                        <i class="bi bi-eye"></i>
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        data-id="{{ $lead->id }}"
                                        onclick="editLeadSource(this)"
                                        title="Edit"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        data-id="{{ $lead->id }}"
                                        data-name="{{ e($lead->source_name ?: $lead->source_type_label) }}"
                                        onclick="openDeleteModal(this)"
                                        title="Delete"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">
                                No lead source summaries found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($leads->hasPages())
            <div class="card-footer bg-white">
                {{ $leads->links() }}
            </div>
        @endif
    </div>
</div>

{{-- Form Modal --}}
<div class="modal fade" id="leadSourceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <form id="leadSourceForm">
            @csrf
            <input type="hidden" id="lead_source_id">

            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title" id="leadSourceModalTitle">Add Lead Source Summary</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div id="formAlert" class="alert alert-danger d-none" role="alert"></div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="source_type" class="form-label">Source Type <span class="text-danger">*</span></label>
                            <select id="source_type" class="form-select">
                                <option value="">Select Source Type</option>
                                <option value="ads">Ads</option>
                                <option value="event">Event</option>
                                <option value="organic">Organic</option>
                                <option value="referral">Referral</option>
                                <option value="direct">Direct</option>
                                <option value="partnership">Partnership</option>
                            </select>
                            <div class="invalid-feedback" id="error_source_type"></div>
                        </div>

                        <div class="col-md-4">
                            <label for="source_name" class="form-label">Source Name</label>
                            <input type="text" id="source_name" class="form-control" placeholder="Example: Meta Ads April / Workshop Intro Web">
                            <div class="invalid-feedback" id="error_source_name"></div>
                        </div>

                        <div class="col-md-4">
                            <label for="date" class="form-label">Date</label>
                            <input type="date" id="date" class="form-control">
                            <div class="invalid-feedback" id="error_date"></div>
                        </div>

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

                        <div class="col-md-6">
                            <label for="marketing_event_id" class="form-label">Event</label>
                            <select id="marketing_event_id" class="form-select">
                                <option value="">Select Event</option>
                                @foreach ($events as $event)
                                    <option value="{{ $event->id }}">{{ $event->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="error_marketing_event_id"></div>
                        </div>

                        <div class="col-md-3">
                            <label for="leads" class="form-label">Leads</label>
                            <input type="number" min="0" id="leads" class="form-control" placeholder="0">
                            <div class="invalid-feedback" id="error_leads"></div>
                        </div>

                        <div class="col-md-3">
                            <label for="qualified_leads" class="form-label">Qualified Leads</label>
                            <input type="number" min="0" id="qualified_leads" class="form-control" placeholder="0">
                            <div class="invalid-feedback" id="error_qualified_leads"></div>
                        </div>

                        <div class="col-md-3">
                            <label for="conversions" class="form-label">Conversions</label>
                            <input type="number" min="0" id="conversions" class="form-control" placeholder="0">
                            <div class="invalid-feedback" id="error_conversions"></div>
                        </div>

                        <div class="col-md-3">
                            <label for="revenue" class="form-label">Revenue</label>
                            <input type="number" min="0" step="0.01" id="revenue" class="form-control" placeholder="0">
                            <div class="invalid-feedback" id="error_revenue"></div>
                        </div>

                        <div class="col-md-12">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea id="notes" class="form-control" rows="4" placeholder="Additional notes..."></textarea>
                            <div class="invalid-feedback" id="error_notes"></div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-check mt-1">
                                <input class="form-check-input" type="checkbox" id="is_active" checked>
                                <label class="form-check-label" for="is_active">
                                    Active
                                </label>
                            </div>
                            <div class="invalid-feedback d-block" id="error_is_active"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <span class="default-text">Save Summary</span>
                        <span class="loading-text d-none">Saving...</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- View Modal --}}
<div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title">Lead Source Summary Detail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div id="viewLoading" class="text-center py-4 d-none">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="small text-muted mt-2">Loading data...</div>
                </div>

                <div id="viewContent" class="d-none">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="small text-muted">Source Type</div>
                            <div class="fw-semibold" id="view_source_type">-</div>
                        </div>

                        <div class="col-md-6">
                            <div class="small text-muted">Source Name</div>
                            <div class="fw-semibold" id="view_source_name">-</div>
                        </div>

                        <div class="col-md-4">
                            <div class="small text-muted">Date</div>
                            <div id="view_date">-</div>
                        </div>

                        <div class="col-md-4">
                            <div class="small text-muted">Campaign</div>
                            <div id="view_campaign_name">-</div>
                        </div>

                        <div class="col-md-4">
                            <div class="small text-muted">Event</div>
                            <div id="view_event_name">-</div>
                        </div>

                        <div class="col-md-3">
                            <div class="small text-muted">Leads</div>
                            <div id="view_leads">-</div>
                        </div>

                        <div class="col-md-3">
                            <div class="small text-muted">Qualified Leads</div>
                            <div id="view_qualified_leads">-</div>
                        </div>

                        <div class="col-md-3">
                            <div class="small text-muted">Conversions</div>
                            <div id="view_conversions">-</div>
                        </div>

                        <div class="col-md-3">
                            <div class="small text-muted">Revenue</div>
                            <div id="view_revenue">-</div>
                        </div>

                        <div class="col-md-6">
                            <div class="small text-muted">Qualification Rate</div>
                            <div id="view_qualification_rate">-</div>
                        </div>

                        <div class="col-md-6">
                            <div class="small text-muted">Conversion Rate</div>
                            <div id="view_conversion_rate">-</div>
                        </div>

                        <div class="col-md-6">
                            <div class="small text-muted">Active</div>
                            <div id="view_is_active">-</div>
                        </div>

                        <div class="col-md-6">
                            <div class="small text-muted">Updated By</div>
                            <div id="view_updated_by_name">-</div>
                        </div>

                        <div class="col-md-12">
                            <div class="small text-muted">Notes</div>
                            <div id="view_notes" class="border rounded p-3 bg-light-subtle">-</div>
                        </div>
                    </div>
                </div>

                <div id="viewError" class="alert alert-danger d-none mb-0"></div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Delete Modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title">Delete Lead Source Summary</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <p class="mb-0">
                    Are you sure you want to delete
                    <strong id="deleteLeadSourceName"></strong>?
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
    const leadSourceModalEl = document.getElementById('leadSourceModal');
    const leadSourceModal = new bootstrap.Modal(leadSourceModalEl);
    const leadSourceForm = document.getElementById('leadSourceForm');
    const submitBtn = document.getElementById('submitBtn');
    const modalTitle = document.getElementById('leadSourceModalTitle');
    const formAlert = document.getElementById('formAlert');

    const viewModalEl = document.getElementById('viewModal');
    const viewModal = new bootstrap.Modal(viewModalEl);

    const deleteModalEl = document.getElementById('deleteModal');
    const deleteModal = new bootstrap.Modal(deleteModalEl);
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const deleteLeadSourceNameEl = document.getElementById('deleteLeadSourceName');

    const fields = {
        id: document.getElementById('lead_source_id'),
        marketing_campaign_id: document.getElementById('marketing_campaign_id'),
        marketing_event_id: document.getElementById('marketing_event_id'),
        date: document.getElementById('date'),
        source_type: document.getElementById('source_type'),
        source_name: document.getElementById('source_name'),
        leads: document.getElementById('leads'),
        qualified_leads: document.getElementById('qualified_leads'),
        conversions: document.getElementById('conversions'),
        revenue: document.getElementById('revenue'),
        notes: document.getElementById('notes'),
        is_active: document.getElementById('is_active'),
    };

    let isEditMode = false;
    let deleteLeadSourceId = null;
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
            <div id="${id}" class="toast align-items-center text-white ${bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
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

    function setLoading(button, isLoading, defaultSelector, loadingSelector) {
        button.disabled = isLoading;
        button.querySelector(defaultSelector)?.classList.toggle('d-none', isLoading);
        button.querySelector(loadingSelector)?.classList.toggle('d-none', !isLoading);
    }

    function resetValidation() {
        formAlert.classList.add('d-none');
        formAlert.textContent = '';

        Object.keys(fields).forEach((key) => {
            if (fields[key]) {
                fields[key].classList.remove('is-invalid');
            }

            const errorEl = document.getElementById(`error_${key}`);
            if (errorEl) {
                errorEl.textContent = '';
            }
        });
    }

    function resetForm() {
        leadSourceForm.reset();
        resetValidation();

        isEditMode = false;
        fields.id.value = '';
        fields.is_active.checked = true;
        modalTitle.textContent = 'Add Lead Source Summary';
    }

    function decodeHtml(value) {
        const txt = document.createElement('textarea');
        txt.innerHTML = value || '';
        return txt.value;
    }

    function openCreateModal() {
        resetForm();
        modalTitle.textContent = 'Add Lead Source Summary';
        leadSourceModal.show();
    }

    async function editLeadSource(button) {
        const id = button.dataset.id;
        resetForm();
        modalTitle.textContent = 'Edit Lead Source Summary';
        isEditMode = true;

        try {
            const response = await fetch(`{{ url('/marketing/leads') }}/${id}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'Failed to load detail.');
            }

            const data = result.data;

            fields.id.value = data.id ?? '';
            fields.marketing_campaign_id.value = data.marketing_campaign_id ?? '';
            fields.marketing_event_id.value = data.marketing_event_id ?? '';
            fields.date.value = data.date ?? '';
            fields.source_type.value = data.source_type ?? '';
            fields.source_name.value = data.source_name ?? '';
            fields.leads.value = data.leads ?? 0;
            fields.qualified_leads.value = data.qualified_leads ?? 0;
            fields.conversions.value = data.conversions ?? 0;
            fields.revenue.value = data.revenue ?? 0;
            fields.notes.value = data.notes ?? '';
            fields.is_active.checked = Boolean(data.is_active);

            leadSourceModal.show();
        } catch (error) {
            showToast(error.message || 'Something went wrong.', 'danger');
        }
    }

    async function viewLeadSource(button) {
        const id = button.dataset.id;

        document.getElementById('viewLoading').classList.remove('d-none');
        document.getElementById('viewContent').classList.add('d-none');
        document.getElementById('viewError').classList.add('d-none');
        document.getElementById('viewError').textContent = '';
        viewModal.show();

        try {
            const response = await fetch(`{{ url('/marketing/leads') }}/${id}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'Failed to load detail.');
            }

            const data = result.data;

            document.getElementById('view_source_type').textContent = data.source_type_label || '-';
            document.getElementById('view_source_name').textContent = data.source_name || '-';
            document.getElementById('view_date').textContent = data.date || '-';
            document.getElementById('view_campaign_name').textContent = data.campaign_name || '-';
            document.getElementById('view_event_name').textContent = data.event_name || '-';
            document.getElementById('view_leads').textContent = formatNumber(data.leads);
            document.getElementById('view_qualified_leads').textContent = formatNumber(data.qualified_leads);
            document.getElementById('view_conversions').textContent = formatNumber(data.conversions);
            document.getElementById('view_revenue').textContent = formatRupiah(data.revenue);
            document.getElementById('view_qualification_rate').textContent = `${formatDecimal(data.qualification_rate)}%`;
            document.getElementById('view_conversion_rate').textContent = `${formatDecimal(data.conversion_rate)}%`;
            document.getElementById('view_is_active').textContent = data.is_active ? 'Yes' : 'No';
            document.getElementById('view_updated_by_name').textContent = data.updated_by_name || '-';
            document.getElementById('view_notes').textContent = data.notes || '-';

            document.getElementById('viewContent').classList.remove('d-none');
        } catch (error) {
            const errorEl = document.getElementById('viewError');
            errorEl.classList.remove('d-none');
            errorEl.textContent = error.message || 'Something went wrong.';
        } finally {
            document.getElementById('viewLoading').classList.add('d-none');
        }
    }

    async function submitForm(event) {
        event.preventDefault();
        resetValidation();

        const id = fields.id.value;
        const url = isEditMode
            ? `{{ url('/marketing/leads') }}/${id}`
            : `{{ route('marketing.leads.store') }}`;

        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');

        if (isEditMode) {
            formData.append('_method', 'PUT');
        }

        formData.append('marketing_campaign_id', fields.marketing_campaign_id.value);
        formData.append('marketing_event_id', fields.marketing_event_id.value);
        formData.append('date', fields.date.value);
        formData.append('source_type', fields.source_type.value);
        formData.append('source_name', fields.source_name.value);
        formData.append('leads', fields.leads.value || 0);
        formData.append('qualified_leads', fields.qualified_leads.value || 0);
        formData.append('conversions', fields.conversions.value || 0);
        formData.append('revenue', fields.revenue.value || 0);
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

                throw new Error(data.message || 'Failed to save data.');
            }

            leadSourceModal.hide();
            showToast(data.message || (isEditMode ? 'Lead source summary updated successfully.' : 'Lead source summary created successfully.'));

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
        deleteLeadSourceId = button.dataset.id;
        deleteLeadSourceNameEl.textContent = decodeHtml(button.dataset.name || '');
        deleteModal.show();
    }

    async function confirmDelete() {
        if (!deleteLeadSourceId) return;

        setLoading(confirmDeleteBtn, true, '.default-delete-text', '.loading-delete-text');

        try {
            const response = await fetch(`{{ url('/marketing/leads') }}/${deleteLeadSourceId}`, {
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
                throw new Error(data.message || 'Failed to delete data.');
            }

            deleteModal.hide();
            showToast(data.message || 'Lead source summary deleted successfully.');

            clearTimeout(reloadTimeout);
            reloadTimeout = setTimeout(() => window.location.reload(), 700);
        } catch (error) {
            showToast(error.message || 'Something went wrong.', 'danger');
        } finally {
            setLoading(confirmDeleteBtn, false, '.default-delete-text', '.loading-delete-text');
        }
    }

    function formatRupiah(value) {
        const number = Number(value || 0);
        return 'Rp' + number.toLocaleString('id-ID');
    }

    function formatNumber(value) {
        const number = Number(value || 0);
        return number.toLocaleString('id-ID');
    }

    function formatDecimal(value) {
        const number = Number(value || 0);
        return number.toLocaleString('id-ID', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    leadSourceForm.addEventListener('submit', submitForm);
    confirmDeleteBtn.addEventListener('click', confirmDelete);
    leadSourceModalEl.addEventListener('hidden.bs.modal', resetForm);
</script>
@endpush