@extends('layouts.app-dashboard')

@section('title', 'Marketing Events')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="mb-1">Marketing Events</h4>
            <small class="text-muted">Manage marketing event activities, participation, and event performance summary.</small>
        </div>

        <button type="button" class="btn btn-primary" onclick="openCreateModal()">
            <i class="bi bi-plus-lg me-1"></i> Add Event
        </button>
    </div>

    <div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080;"></div>

    <div class="card shadow-sm border-0">
        <div class="card-body border-bottom">
            <form method="GET" action="{{ route('marketing.events.index') }}" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <input
                        type="text"
                        name="search"
                        class="form-control form-control-sm"
                        placeholder="Search event, location, audience, or campaign..."
                        value="{{ request('search') }}"
                    >
                </div>

                <div class="col-md-2">
                    <select name="event_type" class="form-select form-select-sm">
                        <option value="">All Type</option>
                        <option value="workshop" {{ request('event_type') === 'workshop' ? 'selected' : '' }}>Workshop</option>
                        <option value="webinar" {{ request('event_type') === 'webinar' ? 'selected' : '' }}>Webinar</option>
                        <option value="expo" {{ request('event_type') === 'expo' ? 'selected' : '' }}>Expo</option>
                        <option value="school_visit" {{ request('event_type') === 'school_visit' ? 'selected' : '' }}>School Visit</option>
                        <option value="booth" {{ request('event_type') === 'booth' ? 'selected' : '' }}>Booth</option>
                        <option value="community_event" {{ request('event_type') === 'community_event' ? 'selected' : '' }}>Community Event</option>
                        <option value="internal_event" {{ request('event_type') === 'internal_event' ? 'selected' : '' }}>Internal Event</option>
                        <option value="other" {{ request('event_type') === 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="planned" {{ request('status') === 'planned' ? 'selected' : '' }}>Planned</option>
                        <option value="ongoing" {{ request('status') === 'ongoing' ? 'selected' : '' }}>Ongoing</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>

                <div class="col-md-auto">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-search me-1"></i> Filter
                    </button>
                </div>

                <div class="col-md-auto">
                    <a href="{{ route('marketing.events.index') }}" class="btn btn-sm btn-light border">
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
                        <th>Event</th>
                        <th>Campaign</th>
                        <th>Date</th>
                        <th>Audience</th>
                        <th class="text-end">Participants</th>
                        <th class="text-end">Leads</th>
                        <th>Status</th>
                        <th>PIC</th>
                        <th class="text-center" style="width: 180px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($events as $event)
                        <tr>
                            <td>{{ $events->firstItem() + $loop->index }}</td>
                            <td>
                                <div class="fw-semibold">{{ $event->name }}</div>
                                <div class="small text-muted">
                                    {{ $event->event_type_label }}{{ $event->location ? ' · ' . $event->location : '' }}
                                </div>
                            </td>
                            <td>
                                <div>{{ $event->campaign?->name ?? '-' }}</div>
                            </td>
                            <td>
                                <div>{{ $event->event_date?->format('d M Y') ?? '-' }}</div>
                            </td>
                            <td>
                                <div>{{ $event->target_audience ?: '-' }}</div>
                            </td>
                            <td class="text-end">
                                <div>{{ number_format($event->attendees ?? 0, 0, ',', '.') }} / {{ number_format($event->target_participants ?? 0, 0, ',', '.') }}</div>
                                <div class="small text-muted">
                                    Reg {{ number_format($event->registrants ?? 0, 0, ',', '.') }}
                                    · {{ number_format($event->attendance_rate ?? 0, 2) }}%
                                </div>
                            </td>
                            <td class="text-end">
                                <div>{{ number_format($event->leads_generated ?? 0, 0, ',', '.') }}</div>
                                <div class="small text-muted">
                                    Conv {{ number_format($event->conversions ?? 0, 0, ',', '.') }}
                                </div>
                            </td>
                            <td>
                                @php
                                    $statusClass = match ($event->status) {
                                        'planned' => 'bg-primary-subtle text-primary border border-primary-subtle',
                                        'ongoing' => 'bg-warning-subtle text-warning border border-warning-subtle',
                                        'completed' => 'bg-success-subtle text-success border border-success-subtle',
                                        'cancelled' => 'bg-danger-subtle text-danger border border-danger-subtle',
                                        default => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
                                    };
                                @endphp

                                <span class="badge {{ $statusClass }}">
                                    {{ ucfirst($event->status) }}
                                </span>

                                @if (! $event->is_active)
                                    <div class="small text-muted mt-1">Inactive</div>
                                @endif
                            </td>
                            <td>{{ $event->pic?->name ?? '-' }}</td>
                            <td class="text-center">
                                <div class="d-inline-flex gap-1">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-secondary"
                                        data-id="{{ $event->id }}"
                                        onclick="viewEvent(this)"
                                        title="View"
                                    >
                                        <i class="bi bi-eye"></i>
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        data-id="{{ $event->id }}"
                                        onclick="editEvent(this)"
                                        title="Edit"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        data-id="{{ $event->id }}"
                                        data-name="{{ e($event->name) }}"
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
                                No marketing events found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($events->hasPages())
            <div class="card-footer bg-white">
                {{ $events->links() }}
            </div>
        @endif
    </div>
</div>

{{-- Form Modal --}}
<div class="modal fade" id="eventModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <form id="eventForm">
            @csrf
            <input type="hidden" id="event_id">

            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventModalTitle">Add Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div id="formAlert" class="alert alert-danger d-none" role="alert"></div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="marketing_campaign_id" class="form-label">Campaign</label>
                            <select id="marketing_campaign_id" class="form-select">
                                <option value="">Select Campaign</option>
                                @foreach ($campaigns as $campaign)
                                    <option value="{{ $campaign->id }}">
                                        {{ $campaign->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="error_marketing_campaign_id"></div>
                        </div>

                        <div class="col-md-4">
                            <label for="name" class="form-label">Event Name <span class="text-danger">*</span></label>
                            <input type="text" id="name" class="form-control" placeholder="Example: Workshop Intro to Web Development">
                            <div class="invalid-feedback" id="error_name"></div>
                        </div>

                        <div class="col-md-2">
                            <label for="event_date" class="form-label">Event Date</label>
                            <input type="date" id="event_date" class="form-control">
                            <div class="invalid-feedback" id="error_event_date"></div>
                        </div>

                        <div class="col-md-3">
                            <label for="event_type" class="form-label">Event Type <span class="text-danger">*</span></label>
                            <select id="event_type" class="form-select">
                                <option value="">Select Type</option>
                                <option value="workshop">Workshop</option>
                                <option value="webinar">Webinar</option>
                                <option value="expo">Expo</option>
                                <option value="school_visit">School Visit</option>
                                <option value="booth">Booth</option>
                                <option value="community_event">Community Event</option>
                                <option value="internal_event">Internal Event</option>
                                <option value="other">Other</option>
                            </select>
                            <div class="invalid-feedback" id="error_event_type"></div>
                        </div>

                        <div class="col-md-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select id="status" class="form-select">
                                <option value="draft">Draft</option>
                                <option value="planned">Planned</option>
                                <option value="ongoing">Ongoing</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <div class="invalid-feedback" id="error_status"></div>
                        </div>

                        <div class="col-md-3">
                            <label for="target_audience" class="form-label">Target Audience</label>
                            <input type="text" id="target_audience" class="form-control" placeholder="Students, fresh graduates, public, etc">
                            <div class="invalid-feedback" id="error_target_audience"></div>
                        </div>

                        <div class="col-md-3">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" id="location" class="form-control" placeholder="Location / Venue">
                            <div class="invalid-feedback" id="error_location"></div>
                        </div>

                        <div class="col-md-3">
                            <label for="pic_user_id" class="form-label">PIC</label>
                            <select id="pic_user_id" class="form-select">
                                <option value="">Select PIC</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}">
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="error_pic_user_id"></div>
                        </div>

                        <div class="col-md-3">
                            <label for="budget" class="form-label">Budget</label>
                            <input type="number" min="0" step="0.01" id="budget" class="form-control" placeholder="0">
                            <div class="invalid-feedback" id="error_budget"></div>
                        </div>

                        <div class="col-md-2">
                            <label for="target_participants" class="form-label">Target Participants</label>
                            <input type="number" min="0" id="target_participants" class="form-control" placeholder="0">
                            <div class="invalid-feedback" id="error_target_participants"></div>
                        </div>

                        <div class="col-md-2">
                            <label for="registrants" class="form-label">Registrants</label>
                            <input type="number" min="0" id="registrants" class="form-control" placeholder="0">
                            <div class="invalid-feedback" id="error_registrants"></div>
                        </div>

                        <div class="col-md-2">
                            <label for="attendees" class="form-label">Attendees</label>
                            <input type="number" min="0" id="attendees" class="form-control" placeholder="0">
                            <div class="invalid-feedback" id="error_attendees"></div>
                        </div>

                        <div class="col-md-2">
                            <label for="leads_generated" class="form-label">Leads Generated</label>
                            <input type="number" min="0" id="leads_generated" class="form-control" placeholder="0">
                            <div class="invalid-feedback" id="error_leads_generated"></div>
                        </div>

                        <div class="col-md-2">
                            <label for="conversions" class="form-label">Conversions</label>
                            <input type="number" min="0" id="conversions" class="form-control" placeholder="0">
                            <div class="invalid-feedback" id="error_conversions"></div>
                        </div>

                        <div class="col-md-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea id="description" class="form-control" rows="4" placeholder="Describe the event..."></textarea>
                            <div class="invalid-feedback" id="error_description"></div>
                        </div>

                        <div class="col-md-12">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea id="notes" class="form-control" rows="3" placeholder="Additional notes..."></textarea>
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
                        <span class="default-text">Save Event</span>
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
                <h5 class="modal-title">Event Detail</h5>
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
                            <div class="small text-muted">Campaign</div>
                            <div class="fw-semibold" id="view_campaign_name">-</div>
                        </div>

                        <div class="col-md-6">
                            <div class="small text-muted">Event Name</div>
                            <div class="fw-semibold" id="view_name">-</div>
                        </div>

                        <div class="col-md-4">
                            <div class="small text-muted">Event Type</div>
                            <div id="view_event_type">-</div>
                        </div>

                        <div class="col-md-4">
                            <div class="small text-muted">Status</div>
                            <div id="view_status">-</div>
                        </div>

                        <div class="col-md-4">
                            <div class="small text-muted">PIC</div>
                            <div id="view_pic_name">-</div>
                        </div>

                        <div class="col-md-6">
                            <div class="small text-muted">Event Date</div>
                            <div id="view_event_date">-</div>
                        </div>

                        <div class="col-md-6">
                            <div class="small text-muted">Location</div>
                            <div id="view_location">-</div>
                        </div>

                        <div class="col-md-4">
                            <div class="small text-muted">Target Audience</div>
                            <div id="view_target_audience">-</div>
                        </div>

                        <div class="col-md-4">
                            <div class="small text-muted">Budget</div>
                            <div id="view_budget">-</div>
                        </div>

                        <div class="col-md-4">
                            <div class="small text-muted">Active</div>
                            <div id="view_is_active">-</div>
                        </div>

                        <div class="col-md-3">
                            <div class="small text-muted">Target Participants</div>
                            <div id="view_target_participants">-</div>
                        </div>

                        <div class="col-md-3">
                            <div class="small text-muted">Registrants</div>
                            <div id="view_registrants">-</div>
                        </div>

                        <div class="col-md-3">
                            <div class="small text-muted">Attendees</div>
                            <div id="view_attendees">-</div>
                        </div>

                        <div class="col-md-3">
                            <div class="small text-muted">Attendance Rate</div>
                            <div id="view_attendance_rate">-</div>
                        </div>

                        <div class="col-md-3">
                            <div class="small text-muted">Leads Generated</div>
                            <div id="view_leads_generated">-</div>
                        </div>

                        <div class="col-md-3">
                            <div class="small text-muted">Conversions</div>
                            <div id="view_conversions">-</div>
                        </div>

                        <div class="col-md-3">
                            <div class="small text-muted">Registration Rate</div>
                            <div id="view_registration_rate">-</div>
                        </div>

                        <div class="col-md-3">
                            <div class="small text-muted">Conversion Rate</div>
                            <div id="view_conversion_rate">-</div>
                        </div>

                        <div class="col-md-12">
                            <div class="small text-muted">Description</div>
                            <div id="view_description" class="border rounded p-3 bg-light-subtle">-</div>
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
                <h5 class="modal-title">Delete Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <p class="mb-0">
                    Are you sure you want to delete
                    <strong id="deleteEventName"></strong>?
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
    const eventModalEl = document.getElementById('eventModal');
    const eventModal = new bootstrap.Modal(eventModalEl);
    const eventForm = document.getElementById('eventForm');
    const submitBtn = document.getElementById('submitBtn');
    const modalTitle = document.getElementById('eventModalTitle');
    const formAlert = document.getElementById('formAlert');

    const viewModalEl = document.getElementById('viewModal');
    const viewModal = new bootstrap.Modal(viewModalEl);

    const deleteModalEl = document.getElementById('deleteModal');
    const deleteModal = new bootstrap.Modal(deleteModalEl);
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const deleteEventNameEl = document.getElementById('deleteEventName');

    const fields = {
        id: document.getElementById('event_id'),
        marketing_campaign_id: document.getElementById('marketing_campaign_id'),
        name: document.getElementById('name'),
        event_type: document.getElementById('event_type'),
        event_date: document.getElementById('event_date'),
        location: document.getElementById('location'),
        target_audience: document.getElementById('target_audience'),
        target_participants: document.getElementById('target_participants'),
        registrants: document.getElementById('registrants'),
        attendees: document.getElementById('attendees'),
        leads_generated: document.getElementById('leads_generated'),
        conversions: document.getElementById('conversions'),
        budget: document.getElementById('budget'),
        status: document.getElementById('status'),
        description: document.getElementById('description'),
        notes: document.getElementById('notes'),
        is_active: document.getElementById('is_active'),
        pic_user_id: document.getElementById('pic_user_id'),
    };

    let isEditMode = false;
    let deleteEventId = null;
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
        eventForm.reset();
        resetValidation();

        isEditMode = false;
        fields.id.value = '';
        fields.status.value = 'draft';
        fields.is_active.checked = true;
        modalTitle.textContent = 'Add Event';
    }

    function decodeHtml(value) {
        const txt = document.createElement('textarea');
        txt.innerHTML = value || '';
        return txt.value;
    }

    function openCreateModal() {
        resetForm();
        modalTitle.textContent = 'Add Event';
        eventModal.show();
    }

    async function editEvent(button) {
        const id = button.dataset.id;
        resetForm();
        modalTitle.textContent = 'Edit Event';
        isEditMode = true;

        try {
            const response = await fetch(`{{ url('/marketing/events') }}/${id}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'Failed to load event detail.');
            }

            const data = result.data;

            fields.id.value = data.id ?? '';
            fields.marketing_campaign_id.value = data.marketing_campaign_id ?? '';
            fields.name.value = data.name ?? '';
            fields.event_type.value = data.event_type ?? '';
            fields.event_date.value = data.event_date ?? '';
            fields.location.value = data.location ?? '';
            fields.target_audience.value = data.target_audience ?? '';
            fields.target_participants.value = data.target_participants ?? 0;
            fields.registrants.value = data.registrants ?? 0;
            fields.attendees.value = data.attendees ?? 0;
            fields.leads_generated.value = data.leads_generated ?? 0;
            fields.conversions.value = data.conversions ?? 0;
            fields.budget.value = data.budget ?? 0;
            fields.status.value = data.status ?? 'draft';
            fields.description.value = data.description ?? '';
            fields.notes.value = data.notes ?? '';
            fields.pic_user_id.value = data.pic_user_id ?? '';
            fields.is_active.checked = Boolean(data.is_active);

            eventModal.show();
        } catch (error) {
            showToast(error.message || 'Something went wrong.', 'danger');
        }
    }

    async function viewEvent(button) {
        const id = button.dataset.id;

        document.getElementById('viewLoading').classList.remove('d-none');
        document.getElementById('viewContent').classList.add('d-none');
        document.getElementById('viewError').classList.add('d-none');
        document.getElementById('viewError').textContent = '';
        viewModal.show();

        try {
            const response = await fetch(`{{ url('/marketing/events') }}/${id}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'Failed to load event detail.');
            }

            const data = result.data;

            document.getElementById('view_campaign_name').textContent = data.campaign_name || '-';
            document.getElementById('view_name').textContent = data.name || '-';
            document.getElementById('view_event_type').textContent = data.event_type_label || '-';
            document.getElementById('view_status').textContent = data.status || '-';
            document.getElementById('view_pic_name').textContent = data.pic_name || '-';
            document.getElementById('view_event_date').textContent = data.event_date || '-';
            document.getElementById('view_location').textContent = data.location || '-';
            document.getElementById('view_target_audience').textContent = data.target_audience || '-';
            document.getElementById('view_budget').textContent = formatRupiah(data.budget);
            document.getElementById('view_is_active').textContent = data.is_active ? 'Yes' : 'No';
            document.getElementById('view_target_participants').textContent = formatNumber(data.target_participants);
            document.getElementById('view_registrants').textContent = formatNumber(data.registrants);
            document.getElementById('view_attendees').textContent = formatNumber(data.attendees);
            document.getElementById('view_attendance_rate').textContent = `${formatDecimal(data.attendance_rate)}%`;
            document.getElementById('view_leads_generated').textContent = formatNumber(data.leads_generated);
            document.getElementById('view_conversions').textContent = formatNumber(data.conversions);
            document.getElementById('view_registration_rate').textContent = `${formatDecimal(data.registration_rate)}%`;
            document.getElementById('view_conversion_rate').textContent = `${formatDecimal(data.conversion_rate)}%`;
            document.getElementById('view_description').textContent = data.description || '-';
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
            ? `{{ url('/marketing/events') }}/${id}`
            : `{{ route('marketing.events.store') }}`;

        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');

        if (isEditMode) {
            formData.append('_method', 'PUT');
        }

        formData.append('marketing_campaign_id', fields.marketing_campaign_id.value);
        formData.append('name', fields.name.value);
        formData.append('event_type', fields.event_type.value);
        formData.append('event_date', fields.event_date.value);
        formData.append('location', fields.location.value);
        formData.append('target_audience', fields.target_audience.value);
        formData.append('target_participants', fields.target_participants.value || 0);
        formData.append('registrants', fields.registrants.value || 0);
        formData.append('attendees', fields.attendees.value || 0);
        formData.append('leads_generated', fields.leads_generated.value || 0);
        formData.append('conversions', fields.conversions.value || 0);
        formData.append('budget', fields.budget.value || 0);
        formData.append('status', fields.status.value);
        formData.append('description', fields.description.value);
        formData.append('notes', fields.notes.value);
        formData.append('pic_user_id', fields.pic_user_id.value);

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

                throw new Error(data.message || 'Failed to save event.');
            }

            eventModal.hide();
            showToast(data.message || (isEditMode ? 'Event updated successfully.' : 'Event created successfully.'));

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
        deleteEventId = button.dataset.id;
        deleteEventNameEl.textContent = decodeHtml(button.dataset.name || '');
        deleteModal.show();
    }

    async function confirmDelete() {
        if (!deleteEventId) return;

        setLoading(confirmDeleteBtn, true, '.default-delete-text', '.loading-delete-text');

        try {
            const response = await fetch(`{{ url('/marketing/events') }}/${deleteEventId}`, {
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
                throw new Error(data.message || 'Failed to delete event.');
            }

            deleteModal.hide();
            showToast(data.message || 'Event deleted successfully.');

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

    eventForm.addEventListener('submit', submitForm);
    confirmDeleteBtn.addEventListener('click', confirmDelete);
    eventModalEl.addEventListener('hidden.bs.modal', resetForm);
</script>
@endpush