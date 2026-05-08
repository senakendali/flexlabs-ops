@extends('layouts.app-dashboard')

@section('title', 'Trial Participants')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Academic</div>
                <h1 class="page-title mb-2">Trial Participants</h1>
                <p class="page-subtitle mb-0">
                    Manage participant registrations, schedule selection, trial theme, contact details, and attendance status.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-light btn-modern" onclick="openCreateModal()">
                    <i class="bi bi-plus-lg me-2"></i>Add Participant
                </button>
            </div>
        </div>
    </div>

    <div
        id="toastContainer"
        class="toast-container position-fixed top-0 end-0 p-3"
        style="z-index: 9999;"
    ></div>

    <div class="content-card mb-4">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Filter Participants</h5>
                <p class="content-card-subtitle mb-0">
                    Search participant records by name, contact, schedule, or registration status.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            <form method="GET">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Search</label>
                        <input
                            type="text"
                            name="search"
                            id="search"
                            class="form-control"
                            value="{{ request('search') }}"
                            placeholder="Name, email, phone"
                        >
                    </div>

                    <div class="col-md-3">
                        <label for="trial_schedule_id" class="form-label">Schedule</label>
                        <select
                            name="trial_schedule_id"
                            id="trial_schedule_id"
                            class="form-select"
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
                        <label for="status" class="form-label">Status</label>
                        <select
                            name="status"
                            id="status"
                            class="form-select"
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
                        <label for="per_page" class="form-label">Show</label>
                        <select
                            name="per_page"
                            id="per_page"
                            class="form-select"
                        >
                            @foreach ([10, 25, 50, 100] as $size)
                                <option value="{{ $size }}" {{ (int) request('per_page', 10) === $size ? 'selected' : '' }}>
                                    {{ $size }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-modern flex-fill">
                                <i class="bi bi-search me-2"></i>Filter
                            </button>

                            <a href="{{ route('trial-participants.index') }}" class="btn btn-outline-secondary btn-modern">
                                Reset
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="content-card">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Participant List</h5>
                <p class="content-card-subtitle mb-0">
                    Review participant profile, selected schedule, contact details, domicile, and current progress status.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            @if ($participants->count())
                <div class="table-responsive dropdown-safe-table">
                    <table class="table table-hover align-middle admin-table mb-0">
                        <thead>
                            <tr>
                                <th class="text-nowrap" style="width: 80px;">No</th>
                                <th class="text-nowrap">Registered At</th>
                                <th class="text-nowrap">Participant</th>
                                <th class="text-nowrap">Schedule</th>
                                <th class="text-nowrap">Theme</th>
                                <th class="text-nowrap">Contact</th>
                                <th class="text-nowrap">Domicile</th>
                                <th class="text-nowrap">Status</th>
                                <th class="text-end text-nowrap" style="width: 160px;">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($participants as $participant)
                                <tr>
                                    <td class="text-muted">
                                        {{ ($participants->currentPage() - 1) * $participants->perPage() + $loop->iteration }}
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ $participant->submitted_date_label ?? '-' }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ $participant->submitted_time_label ?? '-' }}
                                        </div>
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ $participant->full_name }}
                                        </div>

                                        @if ($participant->current_activity)
                                            <div class="small text-muted mt-1">
                                                {{ $participant->current_activity }}
                                            </div>
                                        @endif

                                        @if ($participant->goal)
                                            <div class="small text-muted mt-1">
                                                {{ \Illuminate\Support\Str::limit($participant->goal, 60) }}
                                            </div>
                                        @endif
                                    </td>

                                    <td>
                                        @if ($participant->trialSchedule)
                                            <div class="fw-semibold text-dark">
                                                {{ $participant->trialSchedule->name }}
                                            </div>

                                            <div class="small text-muted mt-1">
                                                {{ \Illuminate\Support\Carbon::parse($participant->trialSchedule->schedule_date)->format('d M Y') }}
                                            </div>

                                            <div class="small text-muted">
                                                {{ \Illuminate\Support\Carbon::parse($participant->trialSchedule->start_time)->format('H:i') }}
                                                - {{ \Illuminate\Support\Carbon::parse($participant->trialSchedule->end_time)->format('H:i') }}
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ $participant->trialTheme->name ?? '-' }}
                                        </div>
                                        <div class="small text-muted">Trial theme</div>
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ $participant->email ?: '-' }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ $participant->phone ?: '-' }}
                                        </div>
                                    </td>

                                    <td class="text-nowrap">
                                        {{ $participant->domicile_city ?: '-' }}
                                    </td>

                                    <td class="text-nowrap">
                                        @php
                                            $statusClass = match($participant->status) {
                                                'registered' => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
                                                'contacted' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
                                                'confirmed' => 'bg-primary-subtle text-primary-emphasis border border-primary-subtle',
                                                'attended' => 'bg-success-subtle text-success-emphasis border border-success-subtle',
                                                'cancelled' => 'bg-danger-subtle text-danger-emphasis border border-danger-subtle',
                                                'no_show' => 'bg-dark-subtle text-dark-emphasis border border-dark-subtle',
                                                default => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
                                            };
                                        @endphp

                                        <span class="badge rounded-pill {{ $statusClass }}">
                                            {{ $statusOptions[$participant->status] ?? ucfirst(str_replace('_', ' ', $participant->status)) }}
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
                                                        onclick="editParticipant({{ $participant->id }})"
                                                    >
                                                        <i class="bi bi-pencil-square me-2"></i>Edit Participant
                                                    </button>
                                                </li>

                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>

                                                <li>
                                                    <button
                                                        type="button"
                                                        class="dropdown-item text-danger"
                                                        onclick="openDeleteModal({{ $participant->id }}, @js($participant->full_name))"
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

                @if ($participants->hasPages())
                    <div class="mt-3">
                        {{ $participants->links() }}
                    </div>
                @endif
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-people"></i>
                    </div>

                    <h5 class="empty-state-title">No trial participants found</h5>
                    <p class="empty-state-text mb-0">
                        Belum ada peserta trial yang tercatat. Tambahkan participant baru atau ubah filter pencarian untuk melihat data lainnya.
                    </p>

                    <div class="mt-3">
                        <button type="button" class="btn btn-primary btn-modern" onclick="openCreateModal()">
                            <i class="bi bi-plus-lg me-2"></i>Add Participant
                        </button>
                    </div>
                </div>
            @endif
        </div>
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
                    <div>
                        <h5 class="modal-title fw-bold mb-1" id="participantModalTitle">Add Participant</h5>
                        <div class="small text-muted">
                            Complete participant profile, selected schedule, contact details, and follow-up status.
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div id="formAlert" class="alert alert-danger d-none mb-3"></div>

                    <div class="content-card mb-3">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Participant Information</h5>
                                <p class="content-card-subtitle mb-0">
                                    Fill in the participant name, contact, domicile, and current activity.
                                </p>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="full_name" class="form-label">
                                        Full Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" id="full_name" class="form-control">
                                    <div class="invalid-feedback" id="error_full_name"></div>
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
                            </div>
                        </div>
                    </div>

                    <div class="content-card mb-3">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Trial Session</h5>
                                <p class="content-card-subtitle mb-0">
                                    Choose trial schedule, related theme, and registration progress status.
                                </p>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <div class="row g-3">
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
                                    <div class="form-text">Theme will be auto-selected when the selected schedule has a theme.</div>
                                    <div class="invalid-feedback" id="error_trial_theme_id"></div>
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
                            </div>
                        </div>
                    </div>

                    <div class="content-card">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Goal & Notes</h5>
                                <p class="content-card-subtitle mb-0">
                                    Add participant learning goals and internal follow-up notes.
                                </p>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <div class="row g-3">
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
                    <h5 class="modal-title fw-bold mb-1">Delete Participant</h5>
                    <div class="small text-muted">
                        This action will remove selected trial participant from the system.
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="alert alert-danger mb-0">
                    <div class="d-flex gap-2 align-items-start">
                        <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                        <div>
                            <div class="fw-semibold">Delete this participant?</div>
                            <div class="small mt-1">
                                Are you sure you want to delete
                                <strong id="deleteParticipantName"></strong>?
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

    function clearValidationErrors() {
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        document.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
    }

    function setValidationErrors(errors = {}) {
        clearValidationErrors();

        Object.keys(errors).forEach(key => {
            const field = fields[key] || document.getElementById(key);
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