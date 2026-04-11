@extends('layouts.app-dashboard')

@section('title', 'Trial Participants')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="mb-1">Trial Participants</h4>
            <small class="text-muted">Manage participant data for trial sessions</small>
        </div>

        <button type="button" class="btn btn-primary" onclick="openCreateModal()">
            <i class="bi bi-plus-lg me-1"></i> Add Participant
        </button>
    </div>

    <div
        id="toastContainer"
        class="toast-container position-fixed top-0 end-0 p-3"
        style="z-index: 9999;"
    ></div>

    <div class="card shadow-sm border-0">
        <div class="card-body border-bottom">
            <form method="GET">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label for="search" class="form-label mb-1 small text-muted">Search</label>
                        <input
                            type="text"
                            name="search"
                            id="search"
                            class="form-control form-control-sm"
                            value="{{ request('search') }}"
                            placeholder="Name, email, phone"
                        >
                    </div>

                    <div class="col-md-3">
                        <label for="trial_schedule_id" class="form-label mb-1 small text-muted">Schedule</label>
                        <select
                            name="trial_schedule_id"
                            id="trial_schedule_id"
                            class="form-select form-select-sm"
                        >
                            <option value="">All Schedules</option>
                            @foreach ($trialSchedules as $schedule)
                                <option
                                    value="{{ $schedule->id }}"
                                    {{ (string) request('trial_schedule_id') === (string) $schedule->id ? 'selected' : '' }}
                                >
                                    {{ $schedule->name }}
                                    - {{ \Illuminate\Support\Carbon::parse($schedule->schedule_date)->format('d M Y') }}
                                    ({{ \Illuminate\Support\Carbon::parse($schedule->start_time)->format('H:i') }}
                                    - {{ \Illuminate\Support\Carbon::parse($schedule->end_time)->format('H:i') }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="status" class="form-label mb-1 small text-muted">Status</label>
                        <select
                            name="status"
                            id="status"
                            class="form-select form-select-sm"
                        >
                            <option value="">All Status</option>
                            @foreach ($statusOptions as $value => $label)
                                <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-1">
                        <label for="per_page" class="form-label mb-1 small text-muted">Show</label>
                        <select
                            name="per_page"
                            id="per_page"
                            class="form-select form-select-sm"
                        >
                            @foreach ([10, 25, 50, 100] as $size)
                                <option value="{{ $size }}" {{ (int) request('per_page', 10) === $size ? 'selected' : '' }}>
                                    {{ $size }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-1">
                        <label class="form-label mb-1 small text-muted d-block">&nbsp;</label>
                        <button type="submit" class="btn btn-sm btn-outline-primary w-100 filter-action-btn">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>

                    <div class="col-md-1">
                        <label class="form-label mb-1 small text-muted d-block">&nbsp;</label>
                        <a href="{{ route('trial-participants.index') }}" class="btn btn-sm btn-outline-secondary w-100 filter-action-btn">
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 80px;">No</th>
                        <th>Participant</th>
                        <th>Schedule</th>
                        <th>Theme</th>
                        <th>Contact</th>
                        <th>Domicile</th>
                        <th>Status</th>
                        <th style="width: 140px;" class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($participants as $participant)
                        <tr>
                            <td>
                                {{ ($participants->currentPage() - 1) * $participants->perPage() + $loop->iteration }}
                            </td>

                            <td>
                                <div class="fw-semibold">{{ $participant->full_name }}</div>
                                @if ($participant->current_activity)
                                    <small class="text-muted d-block">
                                        {{ $participant->current_activity }}
                                    </small>
                                @endif
                                @if ($participant->goal)
                                    <small class="text-muted d-block">
                                        {{ \Illuminate\Support\Str::limit($participant->goal, 60) }}
                                    </small>
                                @endif
                            </td>

                            <td>
                                @if ($participant->trialSchedule)
                                    <div class="fw-semibold">{{ $participant->trialSchedule->name }}</div>
                                    <small class="text-muted d-block">
                                        {{ \Illuminate\Support\Carbon::parse($participant->trialSchedule->schedule_date)->format('d M Y') }}
                                    </small>
                                    <small class="text-muted d-block">
                                        {{ \Illuminate\Support\Carbon::parse($participant->trialSchedule->start_time)->format('H:i') }}
                                        - {{ \Illuminate\Support\Carbon::parse($participant->trialSchedule->end_time)->format('H:i') }}
                                    </small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>

                            <td>
                                {{ $participant->trialTheme->name ?? '-' }}
                            </td>

                            <td>
                                <div>{{ $participant->email ?: '-' }}</div>
                                <small class="text-muted d-block">{{ $participant->phone ?: '-' }}</small>
                            </td>

                            <td>{{ $participant->domicile_city ?: '-' }}</td>

                            <td>
                                @php
                                    $statusClass = match($participant->status) {
                                        'registered' => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
                                        'contacted' => 'bg-info-subtle text-info border border-info-subtle',
                                        'confirmed' => 'bg-primary-subtle text-primary border border-primary-subtle',
                                        'attended' => 'bg-success-subtle text-success border border-success-subtle',
                                        'cancelled' => 'bg-danger-subtle text-danger border border-danger-subtle',
                                        'no_show' => 'bg-dark-subtle text-dark border border-dark-subtle',
                                        default => 'bg-secondary-subtle text-secondary border border-secondary-subtle',
                                    };
                                @endphp

                                <span class="badge {{ $statusClass }}">
                                    {{ $statusOptions[$participant->status] ?? ucfirst(str_replace('_', ' ', $participant->status)) }}
                                </span>
                            </td>

                            <td class="text-center">
                                <div class="d-inline-flex gap-2">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        onclick="editParticipant({{ $participant->id }})"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="openDeleteModal({{ $participant->id }}, @js($participant->full_name))"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                No trial participants found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($participants->hasPages())
            <div class="card-footer bg-white">
                {{ $participants->links() }}
            </div>
        @endif
    </div>
</div>

{{-- Participant Form Modal --}}
<div class="modal fade" id="participantModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="participantForm">
            @csrf
            <input type="hidden" id="participant_id">

            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title" id="participantModalTitle">Add Participant</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div id="formAlert" class="alert alert-danger d-none mb-3"></div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="full_name" class="form-label">
                                Full Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="full_name" class="form-control">
                            <div class="invalid-feedback" id="error_full_name"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="trial_schedule_id_form" class="form-label">
                                Trial Schedule <span class="text-danger">*</span>
                            </label>
                            <select id="trial_schedule_id_form" class="form-select">
                                <option value="">Select Schedule</option>
                                @foreach ($trialSchedules as $schedule)
                                    <option
                                        value="{{ $schedule->id }}"
                                        data-theme-id="{{ $schedule->trial_theme_id }}"
                                    >
                                        {{ $schedule->name }}
                                        - {{ \Illuminate\Support\Carbon::parse($schedule->schedule_date)->format('d M Y') }}
                                        ({{ \Illuminate\Support\Carbon::parse($schedule->start_time)->format('H:i') }}
                                        - {{ \Illuminate\Support\Carbon::parse($schedule->end_time)->format('H:i') }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="error_trial_schedule_id"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="trial_theme_id" class="form-label">Trial Theme</label>
                            <select id="trial_theme_id" class="form-select">
                                <option value="">Select Theme</option>
                                @foreach ($trialThemes as $theme)
                                    <option value="{{ $theme->id }}">{{ $theme->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="error_trial_theme_id"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="input_source" class="form-label">Input Source</label>
                            <select id="input_source" class="form-select">
                                @foreach ($inputSourceOptions as $value => $label)
                                    <option value="{{ $value }}" {{ $value === 'admin' ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="error_input_source"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" class="form-control">
                            <div class="invalid-feedback" id="error_email"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" id="phone" class="form-control">
                            <div class="invalid-feedback" id="error_phone"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="domicile_city" class="form-label">Domicile City</label>
                            <input type="text" id="domicile_city" class="form-control">
                            <div class="invalid-feedback" id="error_domicile_city"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="current_activity" class="form-label">Current Activity</label>
                            <input type="text" id="current_activity" class="form-control">
                            <div class="invalid-feedback" id="error_current_activity"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="status_form" class="form-label">Status</label>
                            <select id="status_form" class="form-select">
                                @foreach ($statusOptions as $value => $label)
                                    <option value="{{ $value }}" {{ $value === 'registered' ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="error_status"></div>
                        </div>

                        <div class="col-12">
                            <label for="goal" class="form-label">Goal</label>
                            <textarea id="goal" rows="3" class="form-control"></textarea>
                            <div class="invalid-feedback" id="error_goal"></div>
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
                <h5 class="modal-title">Delete Participant</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <p class="mb-0">
                    Are you sure you want to delete
                    <strong id="deleteParticipantName"></strong>?
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
    const participantModalEl = document.getElementById('participantModal');
    const participantModal = new bootstrap.Modal(participantModalEl);
    const participantForm = document.getElementById('participantForm');
    const submitBtn = document.getElementById('submitBtn');
    const modalTitle = document.getElementById('participantModalTitle');
    const formAlert = document.getElementById('formAlert');

    const deleteModalEl = document.getElementById('deleteModal');
    const deleteModal = new bootstrap.Modal(deleteModalEl);
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const deleteParticipantNameEl = document.getElementById('deleteParticipantName');

    const fields = {
        id: document.getElementById('participant_id'),
        full_name: document.getElementById('full_name'),
        trial_schedule_id: document.getElementById('trial_schedule_id_form'),
        trial_theme_id: document.getElementById('trial_theme_id'),
        input_source: document.getElementById('input_source'),
        email: document.getElementById('email'),
        phone: document.getElementById('phone'),
        domicile_city: document.getElementById('domicile_city'),
        current_activity: document.getElementById('current_activity'),
        status: document.getElementById('status_form'),
        goal: document.getElementById('goal'),
        notes: document.getElementById('notes'),
    };

    let deleteParticipantId = null;
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
        participantForm.reset();
        fields.id.value = '';
        fields.full_name.value = '';
        fields.trial_schedule_id.value = '';
        fields.trial_theme_id.value = '';
        fields.input_source.value = 'admin';
        fields.email.value = '';
        fields.phone.value = '';
        fields.domicile_city.value = '';
        fields.current_activity.value = '';
        fields.status.value = 'registered';
        fields.goal.value = '';
        fields.notes.value = '';
        formAlert.classList.add('d-none');
        formAlert.innerHTML = '';
        clearValidationErrors();
        setSubmitLoading(false);
    }

    function syncThemeFromSchedule() {
        if (!fields.trial_schedule_id) return;

        const selectedOption = fields.trial_schedule_id.options[fields.trial_schedule_id.selectedIndex];

        if (!selectedOption) return;

        const themeId = selectedOption.dataset.themeId || '';

        if (themeId && fields.trial_theme_id) {
            fields.trial_theme_id.value = themeId;
        }
    }

    function openCreateModal() {
        resetForm();
        modalTitle.textContent = 'Add Participant';
        participantModal.show();
    }

    async function editParticipant(id) {
        resetForm();
        modalTitle.textContent = 'Edit Participant';

        try {
            const response = await fetch(`/trial/participants/${id}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to fetch participant data.');
            }

            const data = result.data;

            fields.id.value = data.id;
            fields.full_name.value = data.full_name ?? '';
            fields.trial_schedule_id.value = data.trial_schedule_id ?? '';
            fields.trial_theme_id.value = data.trial_theme_id ?? '';
            fields.input_source.value = data.input_source ?? 'admin';
            fields.email.value = data.email ?? '';
            fields.phone.value = data.phone ?? '';
            fields.domicile_city.value = data.domicile_city ?? '';
            fields.current_activity.value = data.current_activity ?? '';
            fields.status.value = data.status ?? 'registered';
            fields.goal.value = data.goal ?? '';
            fields.notes.value = data.notes ?? '';

            participantModal.show();
        } catch (error) {
            formAlert.classList.remove('d-none');
            formAlert.innerHTML = error.message || 'Failed to load participant data.';
            participantModal.show();
        }
    }

    function openDeleteModal(id, name) {
        deleteParticipantId = id;
        deleteParticipantNameEl.textContent = name || '-';
        setDeleteLoading(false);
        deleteModal.show();
    }

    if (fields.trial_schedule_id) {
        fields.trial_schedule_id.addEventListener('change', syncThemeFromSchedule);
    }

    participantForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        clearValidationErrors();
        formAlert.classList.add('d-none');
        formAlert.innerHTML = '';

        const id = fields.id.value;
        const url = id ? `/trial/participants/${id}` : `/trial/participants`;

        const payload = {
            full_name: fields.full_name.value.trim(),
            trial_schedule_id: fields.trial_schedule_id.value,
            trial_theme_id: fields.trial_theme_id.value || null,
            input_source: fields.input_source.value,
            email: fields.email.value.trim(),
            phone: fields.phone.value.trim(),
            domicile_city: fields.domicile_city.value.trim(),
            current_activity: fields.current_activity.value.trim(),
            status: fields.status.value,
            goal: fields.goal.value.trim(),
            notes: fields.notes.value.trim(),
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
                throw new Error(result.message || 'Failed to save participant.');
            }

            participantModal.hide();
            showToast(result.message || 'Participant saved successfully', 'success');
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
        if (!deleteParticipantId) return;

        setDeleteLoading(true);

        try {
            const response = await fetch(`/trial/participants/${deleteParticipantId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to delete participant.');
            }

            deleteModal.hide();
            showToast(result.message || 'Participant deleted successfully', 'danger');
            scheduleReload();
        } catch (error) {
            showToast(error.message || 'Failed to delete participant.', 'danger');
        } finally {
            setDeleteLoading(false);
            deleteParticipantId = null;
        }
    });

    participantModalEl.addEventListener('hidden.bs.modal', function () {
        resetForm();
    });

    deleteModalEl.addEventListener('hidden.bs.modal', function () {
        deleteParticipantId = null;
        deleteParticipantNameEl.textContent = '';
        setDeleteLoading(false);
    });
</script>
@endpush