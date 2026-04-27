@extends('layouts.app-dashboard')

@section('title', 'Instructor Availability')

@php
    $routePrefix = Route::has('academic.instructor-availability.index')
        ? 'academic.instructor-availability'
        : 'instructor-availability';

    $indexRoute = Route::has($routePrefix . '.index')
        ? route($routePrefix . '.index')
        : url()->current();

    $storeRoute = Route::has($routePrefix . '.store')
        ? route($routePrefix . '.store')
        : '#';

    $showRouteTemplate = Route::has($routePrefix . '.show')
        ? route($routePrefix . '.show', ['instructorAvailabilitySlot' => '__ID__'])
        : '#';

    $updateRouteTemplate = Route::has($routePrefix . '.update')
        ? route($routePrefix . '.update', ['instructorAvailabilitySlot' => '__ID__'])
        : '#';

    $destroyRouteTemplate = Route::has($routePrefix . '.destroy')
        ? route($routePrefix . '.destroy', ['instructorAvailabilitySlot' => '__ID__'])
        : '#';

    $statusOptions = [
        'available' => 'Available',
        'booked' => 'Booked',
        'blocked' => 'Blocked',
    ];

    $statusClassMap = [
        'available' => 'status-published',
        'booked' => 'status-draft',
        'blocked' => 'status-archived',
    ];

    $statusIconMap = [
        'available' => 'bi-check2-circle',
        'booked' => 'bi-person-check-fill',
        'blocked' => 'bi-slash-circle',
    ];

    $pageStats = [
        'total' => $stats['total'] ?? 0,
        'available' => $stats['available'] ?? 0,
        'booked' => $stats['booked'] ?? 0,
        'blocked' => $stats['blocked'] ?? 0,
    ];

    $currentSearch = $search ?? request('search');
    $currentStatus = $status ?? request('status');
    $currentDate = $date ?? request('date');
    $currentInstructorId = $instructorId ?? request('instructor_id');
    $currentPerPage = (int) request('per_page', 10);
@endphp

@section('content')
<div class="container-fluid px-4 py-4">

    <div
        id="toastContainer"
        class="toast-container position-fixed top-0 end-0 p-3"
        style="z-index: 9999;"
    ></div>

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Academic Management</div>

                <h1 class="page-title mb-2">Instructor Availability</h1>

                <p class="page-subtitle mb-0">
                    Kelola slot jadwal instructor untuk kebutuhan <strong>1-on-1 mentoring session</strong> di LMS student.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <button
                    type="button"
                    class="btn btn-primary btn-modern"
                    onclick="openCreateModal()"
                >
                    <i class="bi bi-plus-circle me-2"></i>Add Slot
                </button>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-calendar2-week-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Total Slots</div>
                        <div class="stat-value">{{ $pageStats['total'] }}</div>
                    </div>
                </div>
                <div class="stat-description">Semua slot availability instructor.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div>
                        <div class="stat-title">Available</div>
                        <div class="stat-value">{{ $pageStats['available'] }}</div>
                    </div>
                </div>
                <div class="stat-description">Slot yang bisa dipilih student.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-person-check-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Booked</div>
                        <div class="stat-value">{{ $pageStats['booked'] }}</div>
                    </div>
                </div>
                <div class="stat-description">Slot yang sudah dibooking student.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-slash-circle"></i>
                    </div>
                    <div>
                        <div class="stat-title">Blocked</div>
                        <div class="stat-value">{{ $pageStats['blocked'] }}</div>
                    </div>
                </div>
                <div class="stat-description">Slot yang sengaja ditutup admin.</div>
            </div>
        </div>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Filter Availability</h5>
                <p class="content-card-subtitle mb-0">
                    Cari slot berdasarkan instructor, tanggal, status, atau keyword tertentu.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            <form method="GET" action="{{ $indexRoute }}">
                <div class="row g-3 align-items-end">
                    <div class="col-xl-3 col-md-6">
                        <label for="search" class="form-label">Keyword</label>
                        <input
                            type="text"
                            name="search"
                            id="search"
                            class="form-control"
                            value="{{ $currentSearch }}"
                            placeholder="Cari nama, email, spesialisasi..."
                        >
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <label for="instructor_id" class="form-label">Instructor</label>
                        <select name="instructor_id" id="instructor_id" class="form-select">
                            <option value="">All instructors</option>
                            @foreach($instructors ?? [] as $instructor)
                                <option
                                    value="{{ $instructor->id }}"
                                    {{ (string) $currentInstructorId === (string) $instructor->id ? 'selected' : '' }}
                                >
                                    {{ $instructor->name }}{{ $instructor->specialization ? ' — ' . $instructor->specialization : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-2 col-md-6">
                        <label for="date" class="form-label">Date</label>
                        <input
                            type="date"
                            name="date"
                            id="date"
                            class="form-control"
                            value="{{ $currentDate }}"
                        >
                    </div>

                    <div class="col-xl-2 col-md-6">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">All Statuses</option>
                            @foreach($statusOptions as $value => $label)
                                <option
                                    value="{{ $value }}"
                                    {{ (string) $currentStatus === (string) $value ? 'selected' : '' }}
                                >
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-2 col-md-6">
                        <label for="per_page" class="form-label">Show</label>
                        <select name="per_page" id="per_page" class="form-select">
                            @foreach ([10, 25, 50, 100] as $size)
                                <option value="{{ $size }}" {{ $currentPerPage === $size ? 'selected' : '' }}>
                                    {{ $size }} rows
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12">
                        <div class="d-flex gap-2 justify-content-end flex-wrap">
                            <a href="{{ $indexRoute }}" class="btn btn-outline-secondary btn-modern">
                                <i class="bi bi-arrow-counterclockwise me-2"></i>Reset
                            </a>

                            <button type="submit" class="btn btn-primary btn-modern">
                                <i class="bi bi-funnel me-2"></i>Filter
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="content-card">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Availability Slot List</h5>
                <p class="content-card-subtitle mb-0">
                    Slot available akan tampil sebagai pilihan jadwal saat student book 1-on-1 session.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            @if(($slots ?? collect())->count())
                <div class="assignment-list">
                    @foreach($slots as $slot)
                        @php
                            $slotStatus = $slot->status ?? 'available';
                            $slotStatusLabel = $statusOptions[$slotStatus] ?? \Illuminate\Support\Str::title($slotStatus);
                            $slotStatusClass = $statusClassMap[$slotStatus] ?? 'status-draft';
                            $slotStatusIcon = $statusIconMap[$slotStatus] ?? 'bi-info-circle';

                            $dateLabel = $slot->date
                                ? \Illuminate\Support\Carbon::parse($slot->date)->format('d M Y')
                                : '-';

                            $dateMachine = $slot->date
                                ? \Illuminate\Support\Carbon::parse($slot->date)->format('Y-m-d')
                                : '-';

                            $dayLabel = $slot->date
                                ? \Illuminate\Support\Carbon::parse($slot->date)->translatedFormat('l')
                                : '-';

                            $startTime = $slot->start_time ? substr($slot->start_time, 0, 5) : '-';
                            $endTime = $slot->end_time ? substr($slot->end_time, 0, 5) : '-';

                            $initial = strtoupper(mb_substr($slot->instructor?->name ?? 'I', 0, 1));

                            $photoUrl = null;

                            if (!empty($slot->instructor?->photo)) {
                                $photoUrl = str_starts_with($slot->instructor->photo, 'http')
                                    ? $slot->instructor->photo
                                    : asset('storage/' . $slot->instructor->photo);
                            }

                            $rowNumber = method_exists($slots, 'currentPage')
                                ? (($slots->currentPage() - 1) * $slots->perPage() + $loop->iteration)
                                : $loop->iteration;
                        @endphp

                        <div class="assignment-card">
                            <div class="assignment-main">
                                <div class="assignment-icon overflow-hidden">
                                    @if($photoUrl)
                                        <img
                                            src="{{ $photoUrl }}"
                                            alt="{{ $slot->instructor?->name }}"
                                            class="w-100 h-100 object-fit-cover"
                                        >
                                    @else
                                        {{ $initial }}
                                    @endif
                                </div>

                                <div class="assignment-info">
                                    <div class="assignment-title-row">
                                        <h5 class="assignment-title mb-0">
                                            {{ $slot->instructor?->name ?? 'Instructor not found' }}
                                        </h5>

                                        <div class="assignment-badges">
                                            <span class="assignment-type-badge type-link">
                                                <i class="bi bi-clock me-1"></i>{{ $startTime }} - {{ $endTime }}
                                            </span>

                                            <span class="assignment-status-badge {{ $slotStatusClass }}">
                                                <i class="bi {{ $slotStatusIcon }} me-1"></i>{{ $slotStatusLabel }}
                                            </span>

                                            @if($slot->is_active)
                                                <span class="assignment-required-badge">Active</span>
                                            @else
                                                <span class="assignment-optional-badge">Inactive</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="assignment-meta mt-2">
                                        <span>
                                            <i class="bi bi-hash me-1"></i>No. {{ $rowNumber }}
                                        </span>

                                        <span>
                                            <i class="bi bi-person-badge me-1"></i>{{ $slot->instructor?->specialization ?: 'General Instructor' }}
                                        </span>

                                        <span>
                                            <i class="bi bi-calendar-event me-1"></i>{{ $dayLabel }}, {{ $dateLabel }}
                                        </span>
                                    </div>

                                    <div class="assignment-target mt-3">
                                        <i class="bi bi-calendar-check me-1"></i>
                                        {{ $dateMachine }} · Slot untuk 1-on-1 mentoring session
                                    </div>

                                    <div class="assignment-footer-meta">
                                        <span>
                                            <i class="bi bi-eye me-1"></i>{{ $slot->is_active ? 'Visible to student' : 'Hidden from student' }}
                                        </span>
                                        <span>
                                            <i class="bi bi-shield-check me-1"></i>{{ $slotStatus === 'booked' ? 'Used by booking' : 'Editable slot' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="assignment-actions">
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary btn-sm"
                                    onclick="editSlot({{ $slot->id }})"
                                >
                                    <i class="bi bi-pencil-square me-1"></i>Edit
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-outline-danger btn-sm"
                                    onclick="openDeleteModal(
                                        {{ $slot->id }},
                                        @js(($slot->instructor?->name ?? 'Instructor') . ' - ' . $dateLabel . ' ' . $startTime . '-' . $endTime),
                                        @js($slotStatus)
                                    )"
                                    {{ $slotStatus === 'booked' ? 'disabled' : '' }}
                                >
                                    <i class="bi bi-trash me-1"></i>Delete
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if(method_exists($slots, 'hasPages') && $slots->hasPages())
                    <div class="mt-4">
                        {{ $slots->links() }}
                    </div>
                @endif
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-calendar-x"></i>
                    </div>
                    <h5 class="empty-state-title">Availability slot belum tersedia</h5>
                    <p class="empty-state-text mb-3">
                        Belum ada slot instructor yang sesuai filter. Buat slot pertama supaya student bisa memilih jadwal mentoring 1-on-1.
                    </p>
                    <button type="button" class="btn btn-primary btn-modern" onclick="openCreateModal()">
                        <i class="bi bi-plus-circle me-2"></i>Add First Slot
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Slot Modal --}}
<div class="modal fade" id="slotModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="slotForm">
            @csrf
            <input type="hidden" id="slot_id" name="slot_id">

            <div class="modal-content custom-modal">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title" id="slotModalTitle">Add Availability Slot</h5>
                        <p class="text-muted small mb-0" id="slotModalSubtitle">
                            Slot ini akan jadi pilihan jadwal untuk booking 1-on-1 mentoring session.
                        </p>
                    </div>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-4">
                    <div id="formAlert" class="alert alert-danger d-none mb-3"></div>

                    <div class="entity-card mb-4">
                        <div class="entity-main">
                            <div class="entity-icon">
                                <i class="bi bi-calendar-check-fill"></i>
                            </div>

                            <div class="entity-info">
                                <div class="entity-title-row">
                                    <div>
                                        <h6 class="entity-title mb-1">Slot Availability</h6>
                                        <div class="entity-subtitle">
                                            Pastikan jam tidak bentrok dengan slot instructor lain di tanggal yang sama.
                                        </div>
                                    </div>

                                    <div class="entity-badges">
                                        <span class="ui-badge ui-badge-primary">1-on-1</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="instructor_id_modal" class="form-label">
                                Instructor <span class="text-danger">*</span>
                            </label>

                            <select id="instructor_id_modal" name="instructor_id" class="form-select">
                                <option value="">Select instructor</option>
                                @foreach($instructors ?? [] as $instructor)
                                    <option value="{{ $instructor->id }}">
                                        {{ $instructor->name }}{{ $instructor->specialization ? ' — ' . $instructor->specialization : '' }}
                                    </option>
                                @endforeach
                            </select>

                            <div class="invalid-feedback" id="error_instructor_id"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="date_modal" class="form-label">
                                Date <span class="text-danger">*</span>
                            </label>

                            <input
                                type="date"
                                id="date_modal"
                                name="date"
                                class="form-control"
                            >

                            <div class="invalid-feedback" id="error_date"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="start_time" class="form-label">
                                Start Time <span class="text-danger">*</span>
                            </label>

                            <input
                                type="time"
                                id="start_time"
                                name="start_time"
                                class="form-control"
                            >

                            <div class="invalid-feedback" id="error_start_time"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="end_time" class="form-label">
                                End Time <span class="text-danger">*</span>
                            </label>

                            <input
                                type="time"
                                id="end_time"
                                name="end_time"
                                class="form-control"
                            >

                            <div class="invalid-feedback" id="error_end_time"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="status_modal" class="form-label">Slot Status</label>

                            <select id="status_modal" name="status" class="form-select">
                                <option value="available">Available</option>
                                <option value="blocked">Blocked</option>
                                <option value="booked">Booked</option>
                            </select>

                            <div class="form-text">
                                Gunakan booked hanya kalau slot sudah terpakai booking.
                            </div>

                            <div class="invalid-feedback" id="error_status"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="is_active_modal" class="form-label">Visibility</label>

                            <select id="is_active_modal" name="is_active" class="form-select">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>

                            <div class="form-text">
                                Inactive tidak akan tampil untuk student.
                            </div>

                            <div class="invalid-feedback" id="error_is_active"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-primary btn-modern" id="submitBtn">
                        <span class="default-text">
                            <i class="bi bi-check-circle me-2"></i>Save Slot
                        </span>
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
        <div class="modal-content custom-modal delete-confirm-modal">
            <div class="modal-header border-0 pb-0">
                <div>
                    <div class="modal-icon-danger mb-3">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <h5 class="modal-title">Delete Availability Slot</h5>
                    <p class="text-muted small mb-0">
                        Slot yang dihapus tidak bisa dikembalikan.
                    </p>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pt-4">
                <div class="delete-confirm-message">
                    <div class="delete-confirm-label">Slot yang akan dihapus</div>
                    <div class="delete-confirm-name" id="deleteSlotName">-</div>
                </div>

                <div class="delete-confirm-warning mt-3">
                    Pastikan slot ini belum digunakan oleh student mentoring session.
                </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                    Cancel
                </button>

                <button type="button" class="btn btn-danger btn-modern" id="confirmDeleteBtn">
                    <span class="default-text">
                        <i class="bi bi-trash me-2"></i>Delete
                    </span>
                    <span class="loading-text d-none">Deleting...</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let slotModal;
let deleteModal;
let deleteSlotId = null;

const storeRoute = @js($storeRoute);
const showRouteTemplate = @js($showRouteTemplate);
const updateRouteTemplate = @js($updateRouteTemplate);
const destroyRouteTemplate = @js($destroyRouteTemplate);

document.addEventListener('DOMContentLoaded', function () {
    slotModal = new bootstrap.Modal(document.getElementById('slotModal'));
    deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));

    document.getElementById('slotForm').addEventListener('submit', submitSlotForm);
    document.getElementById('confirmDeleteBtn').addEventListener('click', deleteSlot);
});

function csrfToken() {
    return document.querySelector('#slotForm input[name="_token"]')?.value || '{{ csrf_token() }}';
}

function buildRoute(template, id) {
    return String(template || '#').replace('__ID__', id);
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function normalizeTime(value) {
    if (!value) return '';
    return String(value).substring(0, 5);
}

function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    const toastId = 'toast-' + Date.now();

    const bgClass = type === 'success'
        ? 'bg-success'
        : type === 'warning'
            ? 'bg-warning'
            : type === 'info'
                ? 'bg-primary'
                : 'bg-danger';

    const textClass = type === 'warning' ? 'text-dark' : 'text-white';
    const closeClass = type === 'warning' ? '' : 'btn-close-white';

    container.insertAdjacentHTML('beforeend', `
        <div id="${toastId}" class="toast align-items-center ${textClass} ${bgClass} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body fw-semibold">${escapeHtml(message)}</div>
                <button type="button" class="btn-close ${closeClass} me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `);

    const toastEl = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastEl, { delay: 2600 });

    toast.show();

    toastEl.addEventListener('hidden.bs.toast', function () {
        toastEl.remove();
    });
}

function setButtonLoading(button, loading = true) {
    if (!button) return;

    const defaultText = button.querySelector('.default-text');
    const loadingText = button.querySelector('.loading-text');

    button.disabled = loading;

    if (defaultText) defaultText.classList.toggle('d-none', loading);
    if (loadingText) loadingText.classList.toggle('d-none', !loading);
}

function resetErrors() {
    document.querySelectorAll('#slotForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
    document.querySelectorAll('#slotForm .invalid-feedback').forEach(el => el.innerText = '');

    const alert = document.getElementById('formAlert');
    alert.classList.add('d-none');
    alert.innerText = '';
}

function fillValidationErrors(errors) {
    const alert = document.getElementById('formAlert');
    let firstMessage = 'Please check the form again.';

    Object.keys(errors || {}).forEach(function (field) {
        const messages = errors[field];
        const message = Array.isArray(messages) ? messages[0] : messages;

        firstMessage = message || firstMessage;

        const input = document.querySelector(`#slotForm [name="${field}"]`);
        const feedback = document.getElementById('error_' + field);

        if (input) input.classList.add('is-invalid');
        if (feedback) feedback.innerText = message;
    });

    alert.innerText = firstMessage;
    alert.classList.remove('d-none');
}

function resetSlotForm() {
    resetErrors();

    const form = document.getElementById('slotForm');
    const submitBtn = document.getElementById('submitBtn');

    form.reset();
    document.getElementById('slot_id').value = '';
    document.getElementById('status_modal').value = 'available';
    document.getElementById('is_active_modal').value = '1';

    setButtonLoading(submitBtn, false);
}

function openCreateModal() {
    resetSlotForm();

    document.getElementById('slotModalTitle').innerText = 'Add Availability Slot';
    document.getElementById('slotModalSubtitle').innerText = 'Slot ini akan jadi pilihan jadwal untuk booking 1-on-1 mentoring session.';

    slotModal.show();
}

async function editSlot(id) {
    resetSlotForm();

    try {
        const response = await fetch(buildRoute(showRouteTemplate, id), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        const result = await response.json().catch(() => ({}));

        if (!response.ok || !result.success) {
            showToast(result.message || 'Failed to load availability slot.', 'error');
            return;
        }

        const slot = result.data || result;

        document.getElementById('slotModalTitle').innerText = 'Edit Availability Slot';
        document.getElementById('slotModalSubtitle').innerText = 'Update data slot instructor availability.';
        document.getElementById('slot_id').value = slot.id || '';
        document.getElementById('instructor_id_modal').value = slot.instructor_id || '';
        document.getElementById('date_modal').value = slot.date || '';
        document.getElementById('start_time').value = normalizeTime(slot.start_time);
        document.getElementById('end_time').value = normalizeTime(slot.end_time);
        document.getElementById('status_modal').value = slot.status || 'available';
        document.getElementById('is_active_modal').value = Number(slot.is_active ?? 1).toString();

        slotModal.show();
    } catch (error) {
        showToast('Failed to load availability slot.', 'error');
    }
}

async function submitSlotForm(event) {
    event.preventDefault();
    resetErrors();

    const submitBtn = document.getElementById('submitBtn');
    setButtonLoading(submitBtn, true);

    const id = document.getElementById('slot_id').value;
    const form = document.getElementById('slotForm');
    const formData = new FormData(form);

    let url = storeRoute;

    if (id) {
        url = buildRoute(updateRouteTemplate, id);
        formData.append('_method', 'PUT');
    }

    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: formData,
            credentials: 'same-origin',
        });

        const result = await response.json().catch(() => ({}));

        if (!response.ok) {
            if (response.status === 422) {
                fillValidationErrors(result.errors || {});
            } else {
                const alert = document.getElementById('formAlert');
                alert.innerText = result.message || 'Failed to save availability slot.';
                alert.classList.remove('d-none');
            }

            setButtonLoading(submitBtn, false);
            return;
        }

        showToast(result.message || 'Availability slot saved successfully.');
        slotModal.hide();

        setTimeout(() => window.location.reload(), 700);
    } catch (error) {
        const alert = document.getElementById('formAlert');
        alert.innerText = 'Failed to connect to server.';
        alert.classList.remove('d-none');

        setButtonLoading(submitBtn, false);
    }
}

function openDeleteModal(id, name, status) {
    if (status === 'booked') {
        showToast('Slot yang sudah booked tidak bisa dihapus.', 'warning');
        return;
    }

    deleteSlotId = id;
    document.getElementById('deleteSlotName').innerText = name || '-';
    deleteModal.show();
}

async function deleteSlot() {
    if (!deleteSlotId) return;

    const deleteBtn = document.getElementById('confirmDeleteBtn');
    setButtonLoading(deleteBtn, true);

    const formData = new FormData();
    formData.append('_token', csrfToken());
    formData.append('_method', 'DELETE');

    try {
        const response = await fetch(buildRoute(destroyRouteTemplate, deleteSlotId), {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: formData,
            credentials: 'same-origin',
        });

        const result = await response.json().catch(() => ({}));

        if (!response.ok) {
            showToast(result.message || 'Failed to delete availability slot.', 'error');
            setButtonLoading(deleteBtn, false);
            return;
        }

        showToast(result.message || 'Availability slot deleted successfully.');
        deleteModal.hide();

        setTimeout(() => window.location.reload(), 700);
    } catch (error) {
        showToast('Failed to connect to server.', 'error');
        setButtonLoading(deleteBtn, false);
    }
}
</script>
@endpush
