@extends('layouts.app-dashboard')

@section('title', 'Workshop Schedules')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Academic</div>
                <h1 class="page-title mb-2">Workshop Schedules</h1>
                <p class="page-subtitle mb-0">
                    Manage workshop schedules by workshop, date, session time, quota, location, pricing, and publication status.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-light btn-modern" onclick="openCreateModal()">
                    <i class="bi bi-plus-lg me-2"></i>Add Schedule
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
                <h5 class="content-card-title mb-1">Workshop Schedule List</h5>
                <p class="content-card-subtitle mb-0">
                    Review schedule name, workshop, date, session time, quota, location type, pricing, and status.
                </p>
            </div>

            <div class="d-flex align-items-center gap-2 flex-wrap">
                <label for="per_page" class="form-label mb-0 small text-muted">Show</label>
                <select
                    id="per_page"
                    class="form-select form-select-sm"
                    style="width: auto;"
                >
                    @foreach ([10, 25, 50, 100] as $size)
                        <option value="{{ $size }}" {{ $size === 10 ? 'selected' : '' }}>
                            {{ $size }}
                        </option>
                    @endforeach
                </select>
                <span class="small text-muted">entries</span>
            </div>
        </div>

        <div class="content-card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label for="filter_search" class="form-label small text-muted">Search</label>
                    <input
                        type="text"
                        id="filter_search"
                        class="form-control"
                        placeholder="Search schedule, workshop, or location"
                    >
                </div>

                <div class="col-md-3">
                    <label for="filter_workshop_id" class="form-label small text-muted">Workshop</label>
                    <select id="filter_workshop_id" class="form-select">
                        <option value="">All Workshops</option>
                        @foreach ($workshops as $workshop)
                            <option value="{{ $workshop->id }}">{{ $workshop->title }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="filter_status" class="form-label small text-muted">Status</label>
                    <select id="filter_status" class="form-select">
                        <option value="">All Status</option>
                        <option value="draft">Draft</option>
                        <option value="open">Open</option>
                        <option value="closed">Closed</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="filter_location_type" class="form-label small text-muted">Location</label>
                    <select id="filter_location_type" class="form-select">
                        <option value="">All Location</option>
                        <option value="online">Online</option>
                        <option value="offline">Offline</option>
                        <option value="hybrid">Hybrid</option>
                    </select>
                </div>

                <div class="col-md-1">
                    <label for="filter_is_active" class="form-label small text-muted">Active</label>
                    <select id="filter_is_active" class="form-select">
                        <option value="">All</option>
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
            </div>

            <div id="tableLoading" class="d-none">
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    </div>

                    <h5 class="empty-state-title">Loading schedules...</h5>
                    <p class="empty-state-text mb-0">
                        Please wait while we load workshop schedule data.
                    </p>
                </div>
            </div>

            <div id="tableWrapper" class="table-responsive dropdown-safe-table">
                <table class="table table-hover align-middle admin-table mb-0">
                    <thead>
                        <tr>
                            <th class="text-nowrap" style="width: 80px;">No</th>
                            <th class="text-nowrap">Schedule</th>
                            <th class="text-nowrap">Workshop</th>
                            <th class="text-nowrap">Date</th>
                            <th class="text-nowrap">Time</th>
                            <th class="text-nowrap">Location</th>
                            <th class="text-end text-nowrap">Quota</th>
                            <th class="text-end text-nowrap">Price</th>
                            <th class="text-nowrap">Status</th>
                            <th class="text-end text-nowrap" style="width: 160px;">Action</th>
                        </tr>
                    </thead>

                    <tbody id="scheduleTableBody"></tbody>
                </table>
            </div>

            <div id="emptyState" class="empty-state-box d-none">
                <div class="empty-state-icon">
                    <i class="bi bi-calendar2-week"></i>
                </div>

                <h5 class="empty-state-title">No workshop schedules found</h5>
                <p class="empty-state-text mb-0">
                    Belum ada jadwal workshop yang tercatat. Tambahkan jadwal baru untuk mulai mengatur sesi workshop.
                </p>

                <div class="mt-3">
                    <button type="button" class="btn btn-primary btn-modern" onclick="openCreateModal()">
                        <i class="bi bi-plus-lg me-2"></i>Add Schedule
                    </button>
                </div>
            </div>

            <div id="paginationWrapper" class="mt-3 d-flex justify-content-between align-items-center gap-3 flex-wrap d-none">
                <div class="small text-muted" id="paginationInfo"></div>
                <nav>
                    <ul class="pagination pagination-sm mb-0" id="paginationLinks"></ul>
                </nav>
            </div>
        </div>
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
                    <div>
                        <h5 class="modal-title fw-bold mb-1" id="scheduleModalTitle">Add Schedule</h5>
                        <div class="small text-muted">
                            Complete workshop schedule information, date, session time, location, quota, and pricing.
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body workshop-schedule-modal-body">
                    <div id="formAlert" class="alert alert-danger d-none mb-3"></div>

                    <div class="content-card mb-3">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Workshop</h5>
                                <p class="content-card-subtitle mb-0">
                                    Choose the workshop. Price fields will be auto-filled from selected workshop.
                                </p>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label for="workshop_id" class="form-label">
                                        Workshop <span class="text-danger">*</span>
                                    </label>
                                    <select id="workshop_id" class="form-select">
                                        <option value="">Select Workshop</option>
                                        @foreach ($workshops as $workshop)
                                            <option
                                                value="{{ $workshop->id }}"
                                                data-price="{{ $workshop->price }}"
                                                data-old-price="{{ $workshop->old_price }}"
                                            >
                                                {{ $workshop->title }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback" id="error_workshop_id"></div>
                                </div>

                                <div class="col-md-4">
                                    <label for="sort_order" class="form-label">Sort Order</label>
                                    <input type="number" min="0" id="sort_order" class="form-control">
                                    <div class="invalid-feedback" id="error_sort_order"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="content-card mb-3">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Schedule Detail</h5>
                                <p class="content-card-subtitle mb-0">
                                    Define the session title, date, start time, end time, and quota.
                                </p>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="title" class="form-label">Schedule Title</label>
                                    <input type="text" id="title" class="form-control" placeholder="Example: Batch 1 - Weekend Class">
                                    <div class="form-text">Optional. If empty, public page can use workshop title.</div>
                                    <div class="invalid-feedback" id="error_title"></div>
                                </div>

                                <div class="col-md-6">
                                    <label for="schedule_date" class="form-label">
                                        Schedule Date <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" id="schedule_date" class="form-control">
                                    <div class="invalid-feedback" id="error_schedule_date"></div>
                                </div>

                                <div class="col-md-6">
                                    <label for="start_time" class="form-label">Start Time</label>
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
                                    <label for="registered_count" class="form-label">Registered Count</label>
                                    <input type="number" min="0" id="registered_count" class="form-control">
                                    <div class="form-text">Usually auto-managed later. Keep 0 for new schedule.</div>
                                    <div class="invalid-feedback" id="error_registered_count"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="content-card mb-3">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Location</h5>
                                <p class="content-card-subtitle mb-0">
                                    Choose whether this workshop is online, offline, or hybrid.
                                </p>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="location_type" class="form-label">
                                        Location Type <span class="text-danger">*</span>
                                    </label>
                                    <select id="location_type" class="form-select">
                                        <option value="online">Online</option>
                                        <option value="offline">Offline</option>
                                        <option value="hybrid">Hybrid</option>
                                    </select>
                                    <div class="invalid-feedback" id="error_location_type"></div>
                                </div>

                                <div class="col-md-8">
                                    <label for="location" class="form-label">Location</label>
                                    <input type="text" id="location" class="form-control" placeholder="Example: Zoom, Google Meet, FlexLabs HQ, Jakarta">
                                    <div class="invalid-feedback" id="error_location"></div>
                                </div>

                                <div class="col-md-12">
                                    <label for="meeting_url" class="form-label">Meeting URL</label>
                                    <input type="url" id="meeting_url" class="form-control" placeholder="https://...">
                                    <div class="invalid-feedback" id="error_meeting_url"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="content-card mb-3">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Pricing & Status</h5>
                                <p class="content-card-subtitle mb-0">
                                    Price defaults from selected workshop, but can be changed for special schedule pricing.
                                </p>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="price" class="form-label">Price</label>
                                    <input type="number" min="0" id="price" class="form-control">
                                    <div class="form-text">Auto-filled from workshop price. Can be changed.</div>
                                    <div class="invalid-feedback" id="error_price"></div>
                                </div>

                                <div class="col-md-6">
                                    <label for="old_price" class="form-label">Old Price</label>
                                    <input type="number" min="0" id="old_price" class="form-control">
                                    <div class="form-text">Auto-filled from workshop old price. Can be changed.</div>
                                    <div class="invalid-feedback" id="error_old_price"></div>
                                </div>

                                <div class="col-md-6">
                                    <label for="status" class="form-label">
                                        Schedule Status <span class="text-danger">*</span>
                                    </label>
                                    <select id="status" class="form-select">
                                        <option value="draft">Draft</option>
                                        <option value="open">Open</option>
                                        <option value="closed">Closed</option>
                                        <option value="completed">Completed</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                    <div class="invalid-feedback" id="error_status"></div>
                                </div>

                                <div class="col-md-6">
                                    <label for="is_active" class="form-label">Status Active</label>
                                    <select id="is_active" class="form-select">
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                    </select>
                                    <div class="invalid-feedback" id="error_is_active"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="content-card">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Notes</h5>
                                <p class="content-card-subtitle mb-0">
                                    Add optional notes or context for this workshop schedule.
                                </p>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea
                                id="notes"
                                rows="4"
                                class="form-control"
                                placeholder="Short notes about this workshop schedule"
                            ></textarea>
                            <div class="invalid-feedback" id="error_notes"></div>
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
                    <h5 class="modal-title fw-bold mb-1">Delete Schedule</h5>
                    <div class="small text-muted">
                        This action will remove selected workshop schedule from the system.
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="alert alert-danger mb-0">
                    <div class="d-flex gap-2 align-items-start">
                        <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                        <div>
                            <div class="fw-semibold">Delete this workshop schedule?</div>
                            <div class="small mt-1">
                                Are you sure you want to delete
                                <strong id="deleteScheduleName"></strong>?
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

@push('styles')
<style>
    /*
    |--------------------------------------------------------------------------
    | Workshop Schedule Modal Scroll
    |--------------------------------------------------------------------------
    | Dibuat supaya gaya modal tetap sama seperti Trial Schedules:
    | - modal-lg
    | - modal-dialog-centered
    | - footer tetap terlihat
    | - body yang scroll saat konten panjang
    |--------------------------------------------------------------------------
    */
    .workshop-schedule-modal-body {
        max-height: calc(100vh - 220px);
        overflow-y: auto;
    }

    @media (max-width: 575.98px) {
        .workshop-schedule-modal-body {
            max-height: calc(100vh - 190px);
        }
    }
</style>
@endpush

@push('scripts')
<script>
    const routes = {
        index: @js(route('academic.workshop-schedules.index')),
        store: @js(route('academic.workshop-schedules.store')),
        showTemplate: @js(route('academic.workshop-schedules.show', ['workshopSchedule' => '__ID__'])),
        updateTemplate: @js(route('academic.workshop-schedules.update', ['workshopSchedule' => '__ID__'])),
        destroyTemplate: @js(route('academic.workshop-schedules.destroy', ['workshopSchedule' => '__ID__'])),
        pricingTemplate: @js(route('academic.workshop-schedules.workshops.pricing', ['workshop' => '__ID__'])),
    };

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

    const tableWrapper = document.getElementById('tableWrapper');
    const tableLoading = document.getElementById('tableLoading');
    const emptyState = document.getElementById('emptyState');
    const scheduleTableBody = document.getElementById('scheduleTableBody');
    const paginationWrapper = document.getElementById('paginationWrapper');
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationLinks = document.getElementById('paginationLinks');

    const filterFields = {
        search: document.getElementById('filter_search'),
        workshop_id: document.getElementById('filter_workshop_id'),
        status: document.getElementById('filter_status'),
        location_type: document.getElementById('filter_location_type'),
        is_active: document.getElementById('filter_is_active'),
        per_page: document.getElementById('per_page'),
    };

    const fields = {
        id: document.getElementById('schedule_id'),
        workshop_id: document.getElementById('workshop_id'),
        title: document.getElementById('title'),
        schedule_date: document.getElementById('schedule_date'),
        start_time: document.getElementById('start_time'),
        end_time: document.getElementById('end_time'),
        location_type: document.getElementById('location_type'),
        location: document.getElementById('location'),
        meeting_url: document.getElementById('meeting_url'),
        quota: document.getElementById('quota'),
        registered_count: document.getElementById('registered_count'),
        price: document.getElementById('price'),
        old_price: document.getElementById('old_price'),
        status: document.getElementById('status'),
        notes: document.getElementById('notes'),
        is_active: document.getElementById('is_active'),
        sort_order: document.getElementById('sort_order'),
    };

    let deleteScheduleId = null;
    let currentPage = 1;
    let searchTimeout = null;
    let isEditing = false;

    function routeFromTemplate(template, id) {
        return template.replace('__ID__', id);
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

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
                        ${escapeHtml(message)}
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

    function setTableLoading(isLoading) {
        tableLoading.classList.toggle('d-none', !isLoading);
        tableWrapper.classList.toggle('d-none', isLoading);
    }

    function statusBadge(status, label) {
        const map = {
            draft: 'bg-secondary-subtle text-secondary-emphasis border-secondary-subtle',
            open: 'bg-success-subtle text-success-emphasis border-success-subtle',
            closed: 'bg-warning-subtle text-warning-emphasis border-warning-subtle',
            completed: 'bg-primary-subtle text-primary-emphasis border-primary-subtle',
            cancelled: 'bg-danger-subtle text-danger-emphasis border-danger-subtle',
        };

        const badgeClass = map[status] || 'bg-secondary-subtle text-secondary-emphasis border-secondary-subtle';

        return `<span class="badge rounded-pill ${badgeClass} border">${escapeHtml(label || status || '-')}</span>`;
    }

    function activeBadge(isActive) {
        if (isActive) {
            return `<span class="badge rounded-pill bg-success-subtle text-success-emphasis border border-success-subtle">Active</span>`;
        }

        return `<span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle">Inactive</span>`;
    }

    function money(value) {
        if (value === null || value === undefined || value === '') {
            return '-';
        }

        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        }).format(Number(value));
    }

    function resetForm() {
        scheduleForm.reset();

        fields.id.value = '';
        fields.workshop_id.value = '';
        fields.title.value = '';
        fields.schedule_date.value = '';
        fields.start_time.value = '';
        fields.end_time.value = '';
        fields.location_type.value = 'online';
        fields.location.value = '';
        fields.meeting_url.value = '';
        fields.quota.value = '';
        fields.registered_count.value = 0;
        fields.price.value = '';
        fields.old_price.value = '';
        fields.status.value = 'open';
        fields.notes.value = '';
        fields.is_active.value = '1';
        fields.sort_order.value = 0;

        formAlert.classList.add('d-none');
        formAlert.innerHTML = '';
        clearValidationErrors();
        setSubmitLoading(false);
        isEditing = false;
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

    function formatTimeForInput(timeValue) {
        if (!timeValue) return '';
        return String(timeValue).substring(0, 5);
    }

    async function loadWorkshopPricing(workshopId) {
        if (!workshopId || isEditing) {
            return;
        }

        try {
            const response = await fetch(routeFromTemplate(routes.pricingTemplate, workshopId), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to fetch workshop pricing.');
            }

            fields.price.value = result.data.price ?? '';
            fields.old_price.value = result.data.old_price ?? '';
        } catch (error) {
            showToast(error.message || 'Failed to load workshop pricing.', 'warning');
        }
    }

    async function fetchSchedules(page = 1) {
        currentPage = page;
        setTableLoading(true);

        const params = new URLSearchParams({
            page: currentPage,
            per_page: filterFields.per_page.value || 10,
        });

        Object.keys(filterFields).forEach(key => {
            if (key === 'per_page') {
                return;
            }

            const value = filterFields[key].value;

            if (value !== '') {
                params.append(key, value);
            }
        });

        try {
            const response = await fetch(`${routes.index}?${params.toString()}`, {
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

            renderTable(result.data);
            renderPagination(result.data);
        } catch (error) {
            showToast(error.message || 'Failed to load schedules.', 'danger');
            renderTable({ data: [] });
            renderPagination(null);
        } finally {
            setTableLoading(false);
        }
    }

    function renderTable(pagination) {
        const rows = pagination.data || [];

        emptyState.classList.toggle('d-none', rows.length > 0);
        tableWrapper.classList.toggle('d-none', rows.length === 0);

        if (!rows.length) {
            scheduleTableBody.innerHTML = '';
            return;
        }

        const from = pagination.from || 1;

        scheduleTableBody.innerHTML = rows.map((schedule, index) => {
            const no = from + index;
            const displayTitle = schedule.display_title || schedule.title || schedule.workshop?.title || '-';
            const notes = schedule.notes
                ? escapeHtml(schedule.notes).substring(0, 80)
                : 'Workshop session';

            const locationText = [
                schedule.location_type_label || '-',
                schedule.location ? escapeHtml(schedule.location) : null,
            ].filter(Boolean).join(' • ');

            const quotaText = schedule.quota
                ? `${Number(schedule.registered_count || 0).toLocaleString('id-ID')} / ${Number(schedule.quota).toLocaleString('id-ID')}`
                : `${Number(schedule.registered_count || 0).toLocaleString('id-ID')} / ∞`;

            return `
                <tr>
                    <td class="text-muted">${no}</td>

                    <td>
                        <div class="fw-semibold text-dark">
                            ${escapeHtml(displayTitle)}
                        </div>
                        <div class="small text-muted mt-1">
                            ${notes}
                        </div>
                    </td>

                    <td>
                        <div class="fw-semibold text-dark">
                            ${escapeHtml(schedule.workshop?.title || '-')}
                        </div>
                        <div class="small text-muted">Workshop</div>
                    </td>

                    <td class="text-nowrap">
                        <div class="fw-semibold text-dark">
                            ${escapeHtml(schedule.schedule_date_label || '-')}
                        </div>
                        <div class="small text-muted">Schedule date</div>
                    </td>

                    <td class="text-nowrap">
                        <div class="fw-semibold text-dark">
                            ${escapeHtml(schedule.time_label || '-')}
                        </div>
                        <div class="small text-muted">Session time</div>
                    </td>

                    <td class="text-nowrap">
                        <div class="fw-semibold text-dark">
                            ${escapeHtml(schedule.location_type_label || '-')}
                        </div>
                        <div class="small text-muted">
                            ${locationText || '-'}
                        </div>
                    </td>

                    <td class="text-end text-nowrap">
                        ${quotaText}
                    </td>

                    <td class="text-end text-nowrap">
                        <div class="fw-semibold text-dark">
                            ${escapeHtml(schedule.formatted_price || money(schedule.effective_price))}
                        </div>
                        ${
                            schedule.effective_old_price
                                ? `<div class="small text-muted text-decoration-line-through">${escapeHtml(schedule.formatted_old_price || money(schedule.effective_old_price))}</div>`
                                : `<div class="small text-muted">Price</div>`
                        }
                    </td>

                    <td class="text-nowrap">
                        <div class="d-flex flex-column gap-1 align-items-start">
                            ${statusBadge(schedule.status, schedule.status_label)}
                            ${activeBadge(schedule.is_active)}
                        </div>
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
                                        onclick="editSchedule(${schedule.id})"
                                    >
                                        <i class="bi bi-pencil-square me-2"></i>Edit Schedule
                                    </button>
                                </li>

                                <li>
                                    <hr class="dropdown-divider">
                                </li>

                                <li>
                                    <button
                                        type="button"
                                        class="dropdown-item text-danger"
                                        onclick="openDeleteModal(${schedule.id}, '${escapeHtml(displayTitle).replaceAll("'", "\\'")}')"
                                    >
                                        <i class="bi bi-trash me-2"></i>Delete
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function renderPagination(pagination) {
        if (!pagination || !pagination.total || pagination.total <= pagination.per_page) {
            paginationWrapper.classList.add('d-none');
            paginationInfo.textContent = '';
            paginationLinks.innerHTML = '';
            return;
        }

        paginationWrapper.classList.remove('d-none');
        paginationInfo.textContent = `Showing ${pagination.from} to ${pagination.to} of ${pagination.total} entries`;

        const current = pagination.current_page;
        const last = pagination.last_page;

        let links = '';

        links += `
            <li class="page-item ${current <= 1 ? 'disabled' : ''}">
                <button class="page-link" type="button" onclick="fetchSchedules(${current - 1})">Previous</button>
            </li>
        `;

        const start = Math.max(1, current - 2);
        const end = Math.min(last, current + 2);

        for (let page = start; page <= end; page++) {
            links += `
                <li class="page-item ${page === current ? 'active' : ''}">
                    <button class="page-link" type="button" onclick="fetchSchedules(${page})">${page}</button>
                </li>
            `;
        }

        links += `
            <li class="page-item ${current >= last ? 'disabled' : ''}">
                <button class="page-link" type="button" onclick="fetchSchedules(${current + 1})">Next</button>
            </li>
        `;

        paginationLinks.innerHTML = links;
    }

    async function editSchedule(id) {
        resetForm();
        modalTitle.textContent = 'Edit Schedule';
        isEditing = true;

        try {
            const response = await fetch(routeFromTemplate(routes.showTemplate, id), {
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
            fields.workshop_id.value = data.workshop_id ?? '';
            fields.title.value = data.title ?? '';
            fields.schedule_date.value = formatDateForInput(data.schedule_date);
            fields.start_time.value = formatTimeForInput(data.start_time);
            fields.end_time.value = formatTimeForInput(data.end_time);
            fields.location_type.value = data.location_type ?? 'online';
            fields.location.value = data.location ?? '';
            fields.meeting_url.value = data.meeting_url ?? '';
            fields.quota.value = data.quota ?? '';
            fields.registered_count.value = data.registered_count ?? 0;
            fields.price.value = data.price ?? '';
            fields.old_price.value = data.old_price ?? '';
            fields.status.value = data.status ?? 'open';
            fields.notes.value = data.notes ?? '';
            fields.is_active.value = data.is_active ? '1' : '0';
            fields.sort_order.value = data.sort_order ?? 0;

            scheduleModal.show();

            window.setTimeout(() => {
                isEditing = false;
            }, 300);
        } catch (error) {
            formAlert.classList.remove('d-none');
            formAlert.innerHTML = error.message || 'Failed to load schedule data.';
            scheduleModal.show();
            isEditing = false;
        }
    }

    function openDeleteModal(id, name) {
        deleteScheduleId = id;
        deleteScheduleNameEl.textContent = name || '-';
        setDeleteLoading(false);
        deleteModal.show();
    }

    fields.workshop_id.addEventListener('change', function () {
        loadWorkshopPricing(this.value);
    });

    Object.values(filterFields).forEach((field) => {
        if (!field) return;

        field.addEventListener('change', function () {
            fetchSchedules(1);
        });
    });

    filterFields.search.addEventListener('input', function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => fetchSchedules(1), 400);
    });

    scheduleForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        clearValidationErrors();
        formAlert.classList.add('d-none');
        formAlert.innerHTML = '';

        const id = fields.id.value;
        const url = id
            ? routeFromTemplate(routes.updateTemplate, id)
            : routes.store;

        const payload = {
            workshop_id: fields.workshop_id.value,
            title: fields.title.value.trim() || null,
            schedule_date: fields.schedule_date.value,
            start_time: fields.start_time.value || null,
            end_time: fields.end_time.value || null,
            location_type: fields.location_type.value,
            location: fields.location.value.trim() || null,
            meeting_url: fields.meeting_url.value.trim() || null,
            quota: fields.quota.value === '' ? null : Number(fields.quota.value),
            registered_count: fields.registered_count.value === '' ? 0 : Number(fields.registered_count.value),
            price: fields.price.value === '' ? null : Number(fields.price.value),
            old_price: fields.old_price.value === '' ? null : Number(fields.old_price.value),
            status: fields.status.value,
            notes: fields.notes.value.trim() || null,
            is_active: fields.is_active.value === '1' ? 1 : 0,
            sort_order: fields.sort_order.value === '' ? 0 : Number(fields.sort_order.value),
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
            fetchSchedules(currentPage);
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
            const response = await fetch(routeFromTemplate(routes.destroyTemplate, deleteScheduleId), {
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
            fetchSchedules(currentPage);
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

    document.addEventListener('DOMContentLoaded', function () {
        fetchSchedules(1);
    });
</script>
@endpush
