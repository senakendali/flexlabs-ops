@extends('layouts.app-dashboard')

@section('title', 'Instructors')

@php
    $routePrefix = Route::has('academic.instructors.index') ? 'academic.instructors' : 'instructors';

    $indexRoute = Route::has($routePrefix . '.index')
        ? route($routePrefix . '.index')
        : url()->current();

    $storeRoute = Route::has($routePrefix . '.store')
        ? route($routePrefix . '.store')
        : '#';

    $showRouteTemplate = Route::has($routePrefix . '.show')
        ? route($routePrefix . '.show', ['instructor' => '__ID__'])
        : '#';

    $updateRouteTemplate = Route::has($routePrefix . '.update')
        ? route($routePrefix . '.update', ['instructor' => '__ID__'])
        : '#';

    $destroyRouteTemplate = Route::has($routePrefix . '.destroy')
        ? route($routePrefix . '.destroy', ['instructor' => '__ID__'])
        : '#';

    $assignRouteTemplate = Route::has($routePrefix . '.assign-teaching-scope')
        ? route($routePrefix . '.assign-teaching-scope', ['instructor' => '__ID__'])
        : '#';

    $assignablePrograms = collect($programs ?? $courses ?? []);
    $assignableBatches = collect($batches ?? []);

    $instructorCollection = method_exists($instructors, 'getCollection')
        ? $instructors->getCollection()
        : collect($instructors ?? []);

    $pageStats = [
        'total' => $stats['total'] ?? (method_exists($instructors, 'total') ? $instructors->total() : $instructorCollection->count()),
        'active' => $stats['active'] ?? $instructorCollection->where('is_active', true)->count(),
        'full_time' => $stats['full_time'] ?? $instructorCollection->where('employment_type', 'full_time')->count(),
        'part_time' => $stats['part_time'] ?? $instructorCollection->where('employment_type', 'part_time')->count(),
    ];
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

                <h1 class="page-title mb-2">Instructors</h1>

                <p class="page-subtitle mb-0">
                    Kelola master instructor untuk kelas, mentoring, dan kebutuhan academic operation FlexOps.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                

                <button
                    type="button"
                    class="btn btn-primary btn-modern"
                    onclick="openCreateModal()"
                >
                    <i class="bi bi-plus-circle me-2"></i>Add Instructor
                </button>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Total Instructors</div>
                        <div class="stat-value">{{ $pageStats['total'] }}</div>
                    </div>
                </div>
                <div class="stat-description">Total instructor yang terdaftar.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-toggle-on"></i>
                    </div>
                    <div>
                        <div class="stat-title">Active</div>
                        <div class="stat-value">{{ $pageStats['active'] }}</div>
                    </div>
                </div>
                <div class="stat-description">Instructor aktif di sistem.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-briefcase-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Full Time</div>
                        <div class="stat-value">{{ $pageStats['full_time'] }}</div>
                    </div>
                </div>
                <div class="stat-description">Instructor dengan status full time.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div>
                        <div class="stat-title">Part Time</div>
                        <div class="stat-value">{{ $pageStats['part_time'] }}</div>
                    </div>
                </div>
                <div class="stat-description">Instructor dengan status part time.</div>
            </div>
        </div>
    </div>

    <div class="content-card">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Instructor List</h5>
                <p class="content-card-subtitle mb-0">
                    Data instructor ini akan dipakai untuk jadwal kelas, availability, dan 1-on-1 mentoring session.
                </p>
            </div>

            <form method="GET" action="{{ $indexRoute }}" class="d-flex align-items-center gap-2 flex-wrap">
                <label for="per_page" class="form-label mb-0 text-muted">Show</label>

                <select
                    name="per_page"
                    id="per_page"
                    class="form-select form-select-sm"
                    style="width: 90px;"
                    onchange="this.form.submit()"
                >
                    @foreach ([10, 25, 50, 100] as $size)
                        <option value="{{ $size }}" {{ (int) request('per_page', 10) === $size ? 'selected' : '' }}>
                            {{ $size }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="content-card-body p-0">
            @if(($instructors ?? collect())->count())
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 70px;">No</th>
                                <th>Instructor</th>
                                <th>Contact</th>
                                <th>Specialization</th>
                                <th>Employment</th>
                                <th>Slug</th>
                                <th>Status</th>
                                <th class="text-end" style="width: 390px; min-width: 390px;">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($instructors as $instructor)
                                @php
                                    $photoUrl = null;

                                    if (!empty($instructor->photo)) {
                                        $photoUrl = str_starts_with($instructor->photo, 'http')
                                            ? $instructor->photo
                                            : asset('storage/' . $instructor->photo);
                                    }

                                    $initial = strtoupper(mb_substr($instructor->name ?? 'I', 0, 1));

                                    $employmentLabel = match($instructor->employment_type) {
                                        'full_time' => 'Full Time',
                                        'part_time' => 'Part Time',
                                        default => '-',
                                    };

                                    $employmentBadge = match($instructor->employment_type) {
                                        'full_time' => 'ui-badge-primary',
                                        'part_time' => 'ui-badge-info',
                                        default => 'ui-badge-muted',
                                    };
                                @endphp

                                <tr>
                                    <td>
                                        @if(method_exists($instructors, 'currentPage'))
                                            {{ ($instructors->currentPage() - 1) * $instructors->perPage() + $loop->iteration }}
                                        @else
                                            {{ $loop->iteration }}
                                        @endif
                                    </td>

                                    <td>
                                        <div class="entity-main">
                                            <div class="entity-icon overflow-hidden">
                                                @if($photoUrl)
                                                    <img
                                                        src="{{ $photoUrl }}"
                                                        alt="{{ $instructor->name }}"
                                                        class="w-100 h-100 object-fit-cover"
                                                    >
                                                @else
                                                    {{ $initial }}
                                                @endif
                                            </div>

                                            <div class="entity-info">
                                                <div class="entity-title-row">
                                                    <div>
                                                        <h6 class="entity-title mb-1">{{ $instructor->name }}</h6>
                                                        <div class="entity-subtitle">
                                                            {{ $instructor->bio ? \Illuminate\Support\Str::limit($instructor->bio, 70) : 'No bio added yet' }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="fw-semibold">{{ $instructor->email ?: '-' }}</div>
                                        <div class="text-muted small">{{ $instructor->phone ?: '-' }}</div>
                                    </td>

                                    <td>
                                        <span class="ui-badge ui-badge-muted">
                                            <i class="bi bi-stars me-1"></i>
                                            {{ $instructor->specialization ?: 'General' }}
                                        </span>
                                    </td>

                                    <td>
                                        <span class="ui-badge {{ $employmentBadge }}">
                                            {{ $employmentLabel }}
                                        </span>
                                    </td>

                                    <td>
                                        <code>{{ $instructor->slug }}</code>
                                    </td>

                                    <td>
                                        @if($instructor->is_active)
                                            <span class="ui-badge ui-badge-success">Active</span>
                                        @else
                                            <span class="ui-badge ui-badge-muted">Inactive</span>
                                        @endif
                                    </td>

                                    <td>
                                        <div class="d-flex justify-content-end gap-2 flex-nowrap">
                                            <button
                                                type="button"
                                                class="btn btn-outline-primary btn-sm btn-modern text-nowrap"
                                                onclick="openAssignModal({{ $instructor->id }}, @js($instructor->name))"
                                            >
                                                <i class="bi bi-diagram-3 me-1"></i>Assign
                                            </button>

                                            <button
                                                type="button"
                                                class="btn btn-outline-secondary btn-sm btn-modern text-nowrap"
                                                onclick="editInstructor({{ $instructor->id }})"
                                            >
                                                <i class="bi bi-pencil-square me-1"></i>Edit
                                            </button>

                                            <button
                                                type="button"
                                                class="btn btn-outline-danger btn-sm btn-modern text-nowrap"
                                                onclick="openDeleteModal({{ $instructor->id }}, @js($instructor->name))"
                                            >
                                                <i class="bi bi-trash me-1"></i>Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if(method_exists($instructors, 'hasPages') && $instructors->hasPages())
                    <div class="p-3 border-top">
                        {{ $instructors->links() }}
                    </div>
                @endif
            @else
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="bi bi-person-workspace"></i>
                    </div>

                    <h5 class="mt-3 mb-1 fw-bold">No instructors found</h5>
                    <p class="text-muted mb-3">
                        Tambahkan instructor pertama untuk mulai setup jadwal dan mentoring session.
                    </p>

                    <button type="button" class="btn btn-primary btn-modern" onclick="openCreateModal()">
                        <i class="bi bi-plus-circle me-2"></i>Add Instructor
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Instructor Modal --}}
<div class="modal fade" id="instructorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="instructorForm" enctype="multipart/form-data">
            @csrf
            <input type="hidden" id="instructor_id" name="instructor_id">

            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title" id="instructorModalTitle">Add Instructor</h5>
                        <p class="text-muted small mb-0">
                            Lengkapi data instructor untuk kebutuhan academic management.
                        </p>
                    </div>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-4">
                    <div id="formAlert" class="alert alert-danger d-none mb-3"></div>

                    {{-- Photo Row --}}
                    <div class="entity-card mb-4">
                        <div class="entity-main align-items-center">
                            <div
                                id="photoPreview"
                                class="entity-icon overflow-hidden"
                                style="width: 92px; height: 92px; font-size: 2rem;"
                            >
                                <i class="bi bi-person-fill"></i>
                            </div>

                            <div class="entity-info">
                                <div class="entity-title-row align-items-start">
                                    <div class="w-100">
                                        <h6 class="entity-title mb-1">Instructor Photo</h6>
                                        <div class="entity-subtitle mb-3">
                                            Upload foto instructor. Optional, gunakan JPG/PNG/WebP dengan ukuran rapi.
                                        </div>

                                        <input
                                            type="file"
                                            id="photo"
                                            name="photo"
                                            class="form-control"
                                            accept="image/*"
                                        >

                                        <div class="invalid-feedback" id="error_photo"></div>
                                    </div>

                                    <div class="entity-badges">
                                        <span class="ui-badge ui-badge-muted">Optional</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Input Row: 2 columns --}}
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">
                                Name <span class="text-danger">*</span>
                            </label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                class="form-control"
                                placeholder="Instructor name"
                            >
                            <div class="invalid-feedback" id="error_name"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="slug" class="form-label">Slug</label>
                            <input
                                type="text"
                                id="slug"
                                name="slug"
                                class="form-control"
                                placeholder="auto from name"
                            >
                            <div class="form-text">Kosongkan kalau mau auto generate.</div>
                            <div class="invalid-feedback" id="error_slug"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-control"
                                placeholder="name@email.com"
                            >
                            <div class="form-text">Kalau diisi, user login instructor akan disiapkan.</div>
                            <div class="invalid-feedback" id="error_email"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone</label>
                            <input
                                type="text"
                                id="phone"
                                name="phone"
                                class="form-control"
                                placeholder="+62..."
                            >
                            <div class="invalid-feedback" id="error_phone"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="specialization" class="form-label">Specialization</label>
                            <input
                                type="text"
                                id="specialization"
                                name="specialization"
                                class="form-control"
                                placeholder="Laravel, UI/UX, Database"
                            >
                            <div class="invalid-feedback" id="error_specialization"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="employment_type" class="form-label">Employment Type</label>
                            <select id="employment_type" name="employment_type" class="form-select">
                                <option value="full_time">Full Time</option>
                                <option value="part_time">Part Time</option>
                            </select>
                            <div class="invalid-feedback" id="error_employment_type"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="is_active" class="form-label">Status</label>
                            <select id="is_active" name="is_active" class="form-select">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                            <div class="invalid-feedback" id="error_is_active"></div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Default Password</label>
                            <input
                                type="text"
                                class="form-control"
                                value="password"
                                disabled
                            >
                            <div class="form-text">
                                Dipakai hanya saat user instructor baru dibuat.
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="bio" class="form-label">Bio</label>
                            <textarea
                                id="bio"
                                name="bio"
                                rows="4"
                                class="form-control"
                                placeholder="Short instructor profile..."
                            ></textarea>
                            <div class="invalid-feedback" id="error_bio"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-primary btn-modern" id="submitBtn">
                        <span class="default-text">
                            <i class="bi bi-check-circle me-2"></i>Save Instructor
                        </span>
                        <span class="loading-text d-none">Saving...</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Assign Teaching Scope Modal --}}
<div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="assignForm">
            @csrf
            <input type="hidden" id="assign_instructor_id" name="instructor_id">

            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title">Assign Teaching Scope</h5>
                        <p class="text-muted small mb-0">
                            Tentukan instructor ini handle program/course dan batch mana.
                        </p>
                    </div>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-4">
                    <div id="assignAlert" class="alert alert-danger d-none mb-3"></div>

                    <div class="entity-card mb-4">
                        <div class="entity-main">
                            <div class="entity-icon">
                                <i class="bi bi-person-check-fill"></i>
                            </div>

                            <div class="entity-info">
                                <div class="entity-title-row">
                                    <div>
                                        <h6 class="entity-title mb-1" id="assignInstructorName">Instructor</h6>
                                        <div class="entity-subtitle">
                                            Teaching scope dipakai untuk mapping instructor ke program/course, batch, dan role pengajaran.
                                        </div>
                                    </div>

                                    <div class="entity-badges">
                                        <span class="ui-badge ui-badge-primary">Teaching Scope</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="assign_program_id" class="form-label">
                                Program / Course <span class="text-danger">*</span>
                            </label>
                            <select id="assign_program_id" name="program_id" class="form-select">
                                <option value="">Select program/course</option>
                                @forelse($assignablePrograms as $program)
                                    <option value="{{ $program->id }}">
                                        {{ $program->name ?? $program->title ?? 'Untitled Program' }}
                                    </option>
                                @empty
                                    <option value="" disabled>No program/course data passed</option>
                                @endforelse
                            </select>
                            <div class="invalid-feedback" id="assign_error_program_id"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="assign_batch_id" class="form-label">Batch</label>
                            <select id="assign_batch_id" name="batch_id" class="form-select">
                                <option value="">All batches / Not specific</option>
                                @forelse($assignableBatches as $batch)
                                    @php
                                        $batchProgramId = $batch->program_id ?? $batch->program?->id ?? null;
                                        $batchProgramName = $batch->program?->name
                                            ?? $batch->program_name
                                            ?? 'Program not set';
                                    @endphp

                                    <option
                                        value="{{ $batch->id }}"
                                        data-program-id="{{ $batchProgramId }}"
                                    >
                                        {{ $batch->name ?? 'Untitled Batch' }} — {{ $batchProgramName }}
                                    </option>
                                @empty
                                    <option value="" disabled>No batch data passed</option>
                                @endforelse
                            </select>
                            <div class="form-text">
                                Pilih Program/Course dulu, lalu batch akan difilter sesuai program tersebut.
                            </div>
                            <div class="invalid-feedback" id="assign_error_batch_id"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="assignment_role" class="form-label">Role</label>
                            <select id="assignment_role" name="assignment_role" class="form-select">
                                <option value="primary_instructor">Primary Instructor</option>
                                <option value="assistant_instructor">Assistant Instructor</option>
                                <option value="mentor">Mentor</option>
                                <option value="reviewer">Project Reviewer</option>
                            </select>
                            <div class="invalid-feedback" id="assign_error_assignment_role"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="assignment_status" class="form-label">Status</label>
                            <select id="assignment_status" name="status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <div class="invalid-feedback" id="assign_error_status"></div>
                        </div>

                        <div class="col-12">
                            <label for="assignment_notes" class="form-label">Notes</label>
                            <textarea
                                id="assignment_notes"
                                name="notes"
                                rows="4"
                                class="form-control"
                                placeholder="Example: Handle Laravel module, project review, and weekly mentoring."
                            ></textarea>
                            <div class="invalid-feedback" id="assign_error_notes"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-primary btn-modern" id="assignSubmitBtn">
                        <span class="default-text">
                            <i class="bi bi-diagram-3 me-2"></i>Save Assignment
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
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title">Delete Instructor</h5>
                    <p class="text-muted small mb-0">
                        Instructor yang dihapus tidak bisa dikembalikan.
                    </p>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pt-4">
                <div class="delete-confirm-message">
                    <div class="delete-confirm-label">Instructor yang akan dihapus</div>
                    <div class="delete-confirm-name" id="deleteInstructorName">-</div>
                </div>

                <div class="delete-confirm-warning mt-3">
                    Pastikan instructor ini belum dipakai di schedule, availability, atau mentoring session.
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
let instructorModal;
let assignModal;
let deleteModal;
let deleteInstructorId = null;

const storeRoute = @js($storeRoute);
const showRouteTemplate = @js($showRouteTemplate);
const updateRouteTemplate = @js($updateRouteTemplate);
const destroyRouteTemplate = @js($destroyRouteTemplate);
const assignRouteTemplate = @js($assignRouteTemplate);

document.addEventListener('DOMContentLoaded', function () {
    instructorModal = new bootstrap.Modal(document.getElementById('instructorModal'));
    assignModal = new bootstrap.Modal(document.getElementById('assignModal'));
    deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));

    document.getElementById('instructorForm').addEventListener('submit', submitInstructorForm);
    document.getElementById('assignForm').addEventListener('submit', submitAssignForm);
    document.getElementById('confirmDeleteBtn').addEventListener('click', deleteInstructor);

    document.getElementById('photo')?.addEventListener('change', previewPhoto);
});

function csrfToken() {
    return document.querySelector('#instructorForm input[name="_token"]')?.value || '{{ csrf_token() }}';
}

function buildRoute(template, id) {
    return String(template || '#').replace('__ID__', id);
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

    container.insertAdjacentHTML('beforeend', `
        <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body fw-semibold">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `);

    const toastEl = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastEl, { delay: 2500 });

    toast.show();

    toastEl.addEventListener('hidden.bs.toast', function () {
        toastEl.remove();
    });
}

function setButtonLoading(button, loading = true) {
    const defaultText = button.querySelector('.default-text');
    const loadingText = button.querySelector('.loading-text');

    button.disabled = loading;

    if (defaultText) defaultText.classList.toggle('d-none', loading);
    if (loadingText) loadingText.classList.toggle('d-none', !loading);
}

function resetErrors() {
    document.querySelectorAll('#instructorForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
    document.querySelectorAll('#instructorForm .invalid-feedback').forEach(el => el.innerText = '');

    const alert = document.getElementById('formAlert');
    alert.classList.add('d-none');
    alert.innerText = '';
}

function resetAssignErrors() {
    document.querySelectorAll('#assignForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
    document.querySelectorAll('#assignForm .invalid-feedback').forEach(el => el.innerText = '');

    const alert = document.getElementById('assignAlert');
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

        const input = document.getElementById(field);
        const feedback = document.getElementById('error_' + field);

        if (input) input.classList.add('is-invalid');
        if (feedback) feedback.innerText = message;
    });

    alert.innerText = firstMessage;
    alert.classList.remove('d-none');
}

function fillAssignValidationErrors(errors) {
    const alert = document.getElementById('assignAlert');
    let firstMessage = 'Please check the assignment form again.';

    Object.keys(errors || {}).forEach(function (field) {
        const messages = errors[field];
        const message = Array.isArray(messages) ? messages[0] : messages;

        firstMessage = message || firstMessage;

        const input = document.querySelector(`#assignForm [name="${field}"]`);
        const feedback = document.getElementById('assign_error_' + field);

        if (input) input.classList.add('is-invalid');
        if (feedback) feedback.innerText = message;
    });

    alert.innerText = firstMessage;
    alert.classList.remove('d-none');
}

function resetPhotoPreview(initial = null, photoUrl = null) {
    const preview = document.getElementById('photoPreview');

    if (photoUrl) {
        preview.innerHTML = `<img src="${photoUrl}" alt="Instructor Photo" class="w-100 h-100 object-fit-cover">`;
        return;
    }

    if (initial) {
        preview.innerText = String(initial).slice(0, 1).toUpperCase();
        return;
    }

    preview.innerHTML = '<i class="bi bi-person-fill"></i>';
}

function previewPhoto(event) {
    const file = event.target.files?.[0];

    if (!file) {
        resetPhotoPreview(document.getElementById('name').value || null);
        return;
    }

    const reader = new FileReader();

    reader.onload = function (readerEvent) {
        resetPhotoPreview(null, readerEvent.target.result);
    };

    reader.readAsDataURL(file);
}

function resetInstructorForm() {
    resetErrors();

    document.getElementById('instructorForm').reset();
    document.getElementById('instructor_id').value = '';
    document.getElementById('employment_type').value = 'full_time';
    document.getElementById('is_active').value = '1';

    resetPhotoPreview();
}

function openCreateModal() {
    resetInstructorForm();

    document.getElementById('instructorModalTitle').innerText = 'Add Instructor';

    instructorModal.show();
}

async function editInstructor(id) {
    resetInstructorForm();

    try {
        const response = await fetch(buildRoute(showRouteTemplate, id), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        const result = await response.json().catch(() => ({}));

        if (!response.ok) {
            showToast(result.message || 'Failed to load instructor.', 'error');
            return;
        }

        const instructor = result.data || result;

        document.getElementById('instructorModalTitle').innerText = 'Edit Instructor';
        document.getElementById('instructor_id').value = instructor.id || '';
        document.getElementById('name').value = instructor.name || '';
        document.getElementById('slug').value = instructor.slug || '';
        document.getElementById('email').value = instructor.email || '';
        document.getElementById('phone').value = instructor.phone || '';
        document.getElementById('specialization').value = instructor.specialization || '';
        document.getElementById('employment_type').value = instructor.employment_type || 'full_time';
        document.getElementById('is_active').value = Number(instructor.is_active ?? 1).toString();
        document.getElementById('bio').value = instructor.bio || '';

        resetPhotoPreview(instructor.name || null, instructor.photo_url || instructor.photo || null);

        instructorModal.show();
    } catch (error) {
        showToast('Failed to load instructor.', 'error');
    }
}

async function submitInstructorForm(event) {
    event.preventDefault();
    resetErrors();

    const submitBtn = document.getElementById('submitBtn');
    setButtonLoading(submitBtn, true);

    const id = document.getElementById('instructor_id').value;
    const form = document.getElementById('instructorForm');
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
                alert.innerText = result.message || 'Failed to save instructor.';
                alert.classList.remove('d-none');
            }

            setButtonLoading(submitBtn, false);
            return;
        }

        showToast(result.message || 'Instructor saved successfully.');
        instructorModal.hide();

        setTimeout(() => window.location.reload(), 700);
    } catch (error) {
        const alert = document.getElementById('formAlert');
        alert.innerText = 'Failed to connect to server.';
        alert.classList.remove('d-none');

        setButtonLoading(submitBtn, false);
    }
}

function openAssignModal(instructorId, instructorName) {
    resetAssignErrors();

    document.getElementById('assignForm').reset();
    document.getElementById('assign_instructor_id').value = instructorId;
    document.getElementById('assignInstructorName').innerText = instructorName || 'Instructor';

    assignModal.show();
}

async function submitAssignForm(event) {
    event.preventDefault();
    resetAssignErrors();

    const instructorId = document.getElementById('assign_instructor_id').value;
    const submitBtn = document.getElementById('assignSubmitBtn');

    if (!instructorId) {
        showToast('Instructor tidak ditemukan.', 'error');
        return;
    }

    if (!assignRouteTemplate || assignRouteTemplate === '#') {
        showToast('Route assign teaching scope belum tersedia.', 'error');
        return;
    }

    const form = document.getElementById('assignForm');
    const formData = new FormData(form);

    setButtonLoading(submitBtn, true);

    try {
        const response = await fetch(buildRoute(assignRouteTemplate, instructorId), {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: formData,
            credentials: 'same-origin',
        });

        const result = await response.json().catch(() => ({}));

        if (!response.ok) {
            if (response.status === 422 && result.errors) {
                fillAssignValidationErrors(result.errors);
            } else {
                const alert = document.getElementById('assignAlert');
                alert.innerText = result.message || 'Failed to save teaching scope.';
                alert.classList.remove('d-none');
            }

            setButtonLoading(submitBtn, false);
            return;
        }

        showToast(result.message || 'Teaching scope berhasil disimpan.');
        assignModal.hide();

        setTimeout(() => window.location.reload(), 700);
    } catch (error) {
        const alert = document.getElementById('assignAlert');
        alert.innerText = 'Failed to connect to server.';
        alert.classList.remove('d-none');

        setButtonLoading(submitBtn, false);
    }
}

function openTeachingScopeInfo() {
    showToast('Teaching Scope dipakai untuk mapping instructor ke program/course, batch, dan role.', 'info');
}

function openDeleteModal(id, name) {
    deleteInstructorId = id;
    document.getElementById('deleteInstructorName').innerText = name || '-';
    deleteModal.show();
}

async function deleteInstructor() {
    if (!deleteInstructorId) return;

    const deleteBtn = document.getElementById('confirmDeleteBtn');
    setButtonLoading(deleteBtn, true);

    const formData = new FormData();
    formData.append('_token', csrfToken());
    formData.append('_method', 'DELETE');

    try {
        const response = await fetch(buildRoute(destroyRouteTemplate, deleteInstructorId), {
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
            showToast(result.message || 'Failed to delete instructor.', 'error');
            setButtonLoading(deleteBtn, false);
            return;
        }

        showToast(result.message || 'Instructor deleted successfully.');
        deleteModal.hide();

        setTimeout(() => window.location.reload(), 700);
    } catch (error) {
        showToast('Failed to connect to server.', 'error');
        setButtonLoading(deleteBtn, false);
    }
}
</script>
@endpush