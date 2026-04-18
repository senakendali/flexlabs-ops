@extends('layouts.app-dashboard')

@section('title', 'Marketing Events')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="mb-1">Marketing Events</h4>
            <small class="text-muted">
                Manage marketing event activities, participation, and event performance summary.
            </small>
        </div>

        <a href="{{ route('marketing.events.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Add Event
        </a>
    </div>

    <div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080;"></div>

    <div class="card shadow-sm border-0">
        <div class="card-body border-bottom bg-white">
            <form method="GET" action="{{ route('marketing.events.index') }}" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <input
                        type="text"
                        name="search"
                        class="form-control form-control-sm"
                        placeholder="Search event, location, audience, or PIC..."
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
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 70px;">No</th>
                        <th>Event</th>
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
                                <div class="fw-semibold text-dark">{{ $event->name }}</div>
                                <div class="small text-muted">
                                    {{ $event->event_type_label ?? ucfirst(str_replace('_', ' ', $event->event_type)) }}
                                    @if ($event->location)
                                        · {{ $event->location }}
                                    @endif
                                </div>
                            </td>

                            <td>
                                <div class="text-dark">
                                    {{ $event->event_date?->format('d M Y') ?? '-' }}
                                </div>
                            </td>

                            <td>
                                <div class="text-dark">{{ $event->target_audience ?: '-' }}</div>
                            </td>

                            <td class="text-end">
                                <div class="fw-medium">
                                    {{ number_format($event->attendees ?? 0, 0, ',', '.') }}
                                    /
                                    {{ number_format($event->target_participants ?? 0, 0, ',', '.') }}
                                </div>
                                <div class="small text-muted">
                                    Reg {{ number_format($event->registrants ?? 0, 0, ',', '.') }}
                                    · {{ number_format($event->attendance_rate ?? 0, 2) }}%
                                </div>
                            </td>

                            <td class="text-end">
                                <div class="fw-medium">
                                    {{ number_format($event->leads_generated ?? 0, 0, ',', '.') }}
                                </div>
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

                            <td>
                                <div class="text-dark">{{ $event->pic?->name ?? '-' }}</div>
                            </td>

                            <td class="text-center">
                                <div class="d-inline-flex gap-1">
                                    <a
                                        href="{{ route('marketing.events.show', $event) }}"
                                        class="btn btn-sm btn-outline-secondary"
                                        title="View"
                                    >
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    <a
                                        href="{{ route('marketing.events.edit', $event) }}"
                                        class="btn btn-sm btn-outline-primary"
                                        title="Edit"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                    </a>

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
                            <td colspan="9" class="text-center py-5 text-muted">
                                <div class="d-flex flex-column align-items-center gap-2">
                                    <i class="bi bi-calendar-event fs-1 text-secondary"></i>
                                    <div class="fw-semibold">No marketing events found.</div>
                                    <small>Try changing the filter or add a new event.</small>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($events->hasPages())
            <div class="card-footer bg-white py-3">
                {{ $events->links() }}
            </div>
        @endif
    </div>
</div>

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
    const deleteModalEl = document.getElementById('deleteModal');
    const deleteModal = new bootstrap.Modal(deleteModalEl);
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const deleteEventNameEl = document.getElementById('deleteEventName');

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
                    <button type="button" class="btn-close ${type === 'warning' || type === 'info' ? '' : 'btn-close-white'} me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', html);

        const toastEl = document.getElementById(id);
        const toast = new bootstrap.Toast(toastEl, { delay: 3000 });

        toast.show();
        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    }

    function decodeHtml(value) {
        const txt = document.createElement('textarea');
        txt.innerHTML = value || '';
        return txt.value;
    }

    function setLoading(button, isLoading, defaultSelector, loadingSelector) {
        button.disabled = isLoading;
        button.querySelector(defaultSelector)?.classList.toggle('d-none', isLoading);
        button.querySelector(loadingSelector)?.classList.toggle('d-none', !isLoading);
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

    confirmDeleteBtn.addEventListener('click', confirmDelete);

    @if (session('success'))
        showToast(@json(session('success')), 'success');
    @endif

    @if (session('error'))
        showToast(@json(session('error')), 'danger');
    @endif
</script>
@endpush