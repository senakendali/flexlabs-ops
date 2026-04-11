@extends('layouts.app-dashboard')

@section('title', 'Trial Schedules')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="mb-1">Trial Schedules</h4>
            <small class="text-muted">Manage trial schedules by program and theme</small>
        </div>

        <button type="button" class="btn btn-primary" onclick="openCreateModal()">
            <i class="bi bi-plus-lg me-1"></i> Add Schedule
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
                        <th>Schedule</th>
                        <th>Program</th>
                        <th>Theme</th>
                        <th>Date</th>
                        <th>Day</th>
                        <th>Time</th>
                        <th>Quota</th>
                        <th>Status</th>
                        <th style="width: 140px;" class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($schedules as $schedule)
                        @php
                            $scheduleDate = $schedule->schedule_date
                                ? \Carbon\Carbon::parse($schedule->schedule_date)
                                : null;
                        @endphp
                        <tr>
                            <td>
                                {{ ($schedules->currentPage() - 1) * $schedules->perPage() + $loop->iteration }}
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $schedule->name }}</div>
                                @if ($schedule->description)
                                    <small class="text-muted">
                                        {{ \Illuminate\Support\Str::limit($schedule->description, 60) }}
                                    </small>
                                @endif
                            </td>
                            <td>{{ $schedule->program->name ?? '-' }}</td>
                            <td>{{ $schedule->trialTheme->name ?? '-' }}</td>
                            <td>{{ $scheduleDate ? $scheduleDate->format('d M Y') : '-' }}</td>
                            <td>{{ $scheduleDate ? $scheduleDate->format('l') : '-' }}</td>
                            <td>
                                {{ $schedule->start_time ? \Carbon\Carbon::createFromFormat('H:i:s', $schedule->start_time)->format('H:i') : '-' }}
                                @if ($schedule->end_time)
                                    - {{ \Carbon\Carbon::createFromFormat('H:i:s', $schedule->end_time)->format('H:i') }}
                                @endif
                            </td>
                            <td>{{ $schedule->quota ?? '-' }}</td>
                            <td>
                                @if ($schedule->is_active)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                        Active
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-inline-flex gap-2">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        onclick="editSchedule({{ $schedule->id }})"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="openDeleteModal({{ $schedule->id }}, @js($schedule->name))"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">
                                No trial schedules found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($schedules->hasPages())
            <div class="card-footer bg-white">
                {{ $schedules->links() }}
            </div>
        @endif
    </div>
</div>

{{-- Schedule Form Modal --}}
<div class="modal fade" id="scheduleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="scheduleForm">
            @csrf
            <input type="hidden" id="schedule_id">

            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title" id="scheduleModalTitle">Add Schedule</h5>
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
                            <label for="trial_theme_id" class="form-label">Theme</label>
                            <select id="trial_theme_id" class="form-select">
                                <option value="">Select Theme</option>
                            </select>
                            <div class="form-text">Optional. Themes will follow selected program.</div>
                            <div class="invalid-feedback" id="error_trial_theme_id"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="name" class="form-label">
                                Schedule Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="name" class="form-control">
                            <div class="form-text">Example: Intro Web Dev - 15 Apr 2026</div>
                            <div class="invalid-feedback" id="error_name"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="schedule_date" class="form-label">
                                Schedule Date <span class="text-danger">*</span>
                            </label>
                            <input type="date" id="schedule_date" class="form-control">
                            <div class="invalid-feedback" id="error_schedule_date"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="start_time" class="form-label">
                                Start Time <span class="text-danger">*</span>
                            </label>
                            <input type="time" id="start_time" class="form-control">
                            <div class="invalid-feedback" id="error_start_time"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="end_time" class="form-label">End Time</label>
                            <input type="time" id="end_time" class="form-control">
                            <div class="invalid-feedback" id="error_end_time"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="quota" class="form-label">Quota</label>
                            <input type="number" min="1" id="quota" class="form-control">
                            <div class="invalid-feedback" id="error_quota"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="is_active" class="form-label">Status Active</label>
                            <select id="is_active" class="form-select">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                            <div class="invalid-feedback" id="error_is_active"></div>
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
                <h5 class="modal-title">Delete Schedule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <p class="mb-0">
                    Are you sure you want to delete
                    <strong id="deleteScheduleName"></strong>?
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
    const scheduleModalEl = document.getElementById('scheduleModal');
    const scheduleModal = new bootstrap.Modal(scheduleModalEl);
    const scheduleForm = document.getElementById('scheduleForm');
    const submitBtn = document.getElementById('submitBtn');
    const modalTitle = document.getElementById('scheduleModalTitle');
    const formAlert = document.getElementById('formAlert');

    const deleteModalEl = document.getElementById('deleteModal');
    const deleteModal = new bootstrap.Modal(deleteModalEl);
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const deleteScheduleNameEl = document.getElementById('deleteScheduleName');

    const allThemes = @json($themes);
    const fields = {
        id: document.getElementById('schedule_id'),
        program_id: document.getElementById('program_id'),
        trial_theme_id: document.getElementById('trial_theme_id'),
        name: document.getElementById('name'),
        schedule_date: document.getElementById('schedule_date'),
        start_time: document.getElementById('start_time'),
        end_time: document.getElementById('end_time'),
        quota: document.getElementById('quota'),
        description: document.getElementById('description'),
        is_active: document.getElementById('is_active'),
    };

    let deleteScheduleId = null;
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

    function renderThemeOptions(programId, selectedThemeId = '') {
        const themeSelect = fields.trial_theme_id;
        const currentValue = selectedThemeId ? String(selectedThemeId) : '';

        let options = '<option value="">Select Theme</option>';

        if (programId) {
            const filteredThemes = allThemes.filter(theme => String(theme.program_id) === String(programId));

            filteredThemes.forEach(theme => {
                const selected = String(theme.id) === currentValue ? 'selected' : '';
                options += `<option value="${theme.id}" ${selected}>${theme.name}</option>`;
            });
        }

        themeSelect.innerHTML = options;
    }

    function resetForm() {
        scheduleForm.reset();
        fields.id.value = '';
        fields.program_id.value = '';
        fields.name.value = '';
        fields.schedule_date.value = '';
        fields.start_time.value = '';
        fields.end_time.value = '';
        fields.quota.value = '';
        fields.description.value = '';
        fields.is_active.value = '1';
        renderThemeOptions('', '');
        formAlert.classList.add('d-none');
        formAlert.innerHTML = '';
        clearValidationErrors();
        setSubmitLoading(false);
    }

    function openCreateModal() {
        resetForm();
        modalTitle.textContent = 'Add Schedule';
        scheduleModal.show();
    }

    function formatDateForInput(dateValue) {
        if (!dateValue) return '';
        return String(dateValue).substring(0, 10);
    }

    async function editSchedule(id) {
        resetForm();
        modalTitle.textContent = 'Edit Schedule';

        try {
            const response = await fetch(`/trial/schedules/${id}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to fetch schedule data.');
            }

            const data = result.data;

            fields.id.value = data.id;
            fields.program_id.value = data.program_id ?? '';
            renderThemeOptions(data.program_id ?? '', data.trial_theme_id ?? '');
            fields.name.value = data.name ?? '';
            fields.schedule_date.value = formatDateForInput(data.schedule_date);
            fields.start_time.value = formatTimeForInput(data.start_time);
            fields.end_time.value = formatTimeForInput(data.end_time);
            fields.quota.value = data.quota ?? '';
            fields.description.value = data.description ?? '';
            fields.is_active.value = data.is_active ? '1' : '0';

            scheduleModal.show();
        } catch (error) {
            formAlert.classList.remove('d-none');
            formAlert.innerHTML = error.message || 'Failed to load schedule data.';
            scheduleModal.show();
        }
    }

    function openDeleteModal(id, name) {
        deleteScheduleId = id;
        deleteScheduleNameEl.textContent = name || '-';
        setDeleteLoading(false);
        deleteModal.show();
    }

    function formatTimeForInput(timeValue) {
        if (!timeValue) return '';
        return String(timeValue).substring(0, 5);
    }

    fields.program_id.addEventListener('change', function () {
        renderThemeOptions(this.value, '');
    });

    scheduleForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        clearValidationErrors();
        formAlert.classList.add('d-none');
        formAlert.innerHTML = '';

        const id = fields.id.value;
        const url = id ? `/trial/schedules/${id}` : `/trial/schedules`;

        const payload = {
            program_id: fields.program_id.value,
            trial_theme_id: fields.trial_theme_id.value || null,
            name: fields.name.value.trim(),
            schedule_date: fields.schedule_date.value,
            start_time: fields.start_time.value,
            end_time: fields.end_time.value || null,
            quota: fields.quota.value === '' ? null : Number(fields.quota.value),
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
                throw new Error(result.message || 'Failed to save schedule.');
            }

            scheduleModal.hide();
            showToast(result.message || 'Schedule saved successfully', 'success');
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
        if (!deleteScheduleId) return;

        setDeleteLoading(true);

        try {
            const response = await fetch(`/trial/schedules/${deleteScheduleId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to delete schedule.');
            }

            deleteModal.hide();
            showToast(result.message || 'Schedule deleted successfully', 'danger');
            scheduleReload();
        } catch (error) {
            showToast(error.message || 'Failed to delete schedule.', 'danger');
        } finally {
            setDeleteLoading(false);
            deleteScheduleId = null;
        }
    });

    scheduleModalEl.addEventListener('hidden.bs.modal', function () {
        resetForm();
    });

    deleteModalEl.addEventListener('hidden.bs.modal', function () {
        deleteScheduleId = null;
        deleteScheduleNameEl.textContent = '';
        setDeleteLoading(false);
    });
</script>
@endpush