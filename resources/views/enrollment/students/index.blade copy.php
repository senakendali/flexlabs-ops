@extends('layouts.app-dashboard')

@section('title', 'Students')

@section('content')
<div class="container-fluid px-4 py-4">

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Enrollment Management</div>
                <h1 class="page-title mb-2">Students</h1>
                <p class="page-subtitle mb-0">
                    Manage confirmed students, enrollment access, and LMS login simulation.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-primary btn-modern" onclick="openCreateModal()">
                    <i class="bi bi-plus-lg me-2"></i>Add Student
                </button>
            </div>
        </div>
    </div>

    <div
        id="toastContainer"
        class="toast-container position-fixed top-0 end-0 p-3"
        style="z-index: 9999;"
    ></div>

    <div class="row g-3 mb-4">
        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Total</div>
                        <div class="stat-value">{{ $stats['total'] ?? $students->total() }}</div>
                    </div>
                </div>
                <div class="stat-description">Total confirmed student data.</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-person-check-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Active</div>
                        <div class="stat-value">{{ $stats['active'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Student yang sudah aktif.</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-person-dash-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Inactive</div>
                        <div class="stat-value">{{ $stats['inactive'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Student yang belum aktif / belum diproses.</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Enrolled</div>
                        <div class="stat-value">{{ $stats['enrolled'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Enrollment aktif untuk LMS.</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-person-badge-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">LMS Account</div>
                        <div class="stat-value">{{ $stats['login_ready'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Student yang sudah punya akun login LMS.</div>
            </div>
        </div>
    </div>

    <div class="content-card students-table-card">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Student List</h5>
                <p class="content-card-subtitle mb-0">
                    Kelola data student yang sudah confirm, lalu enroll ke batch untuk simulasi akses LMS.
                </p>
            </div>

            <form method="GET" class="d-flex align-items-center gap-2 flex-wrap">
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

        <div class="content-card-body">
            @if($students->count())
                <div class="student-table-responsive dropdown-safe-table">
                    <table class="table table-hover align-middle admin-table student-admin-table mb-0">
                        <thead>
                            <tr>
                                <th class="text-nowrap" style="width: 80px;">No</th>
                                <th class="text-nowrap col-student">Student</th>
                                <th class="text-nowrap col-contact">Contact</th>
                                <th class="text-nowrap col-profile">Profile</th>
                                <th class="text-nowrap">NIK</th>
                                <th class="text-nowrap col-emergency">Emergency Contact</th>
                                <th class="text-nowrap col-enrollment">Enrollment</th>
                                <th class="text-nowrap">Status</th>
                                <th class="text-end text-nowrap" style="width: 160px;">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($students as $student)
                                @php
                                    $statusClass = match($student->status) {
                                        'active' => 'bg-success-subtle text-success-emphasis border border-success-subtle',
                                        'inactive' => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
                                        default => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
                                    };

                                    $activeEnrollments = $student->enrollments
                                        ? $student->enrollments->where('status', 'active')->where('access_status', 'active')
                                        : collect();

                                    $studentName = $student->full_name ?? $student->name ?? '-';

                                    $studentNik = $student->nik ?: '-';

                                    $emergencyContactName = $student->emergency_contact_name ?: null;
                                    $emergencyContactPhone = $student->emergency_contact_phone ?: null;
                                    $emergencyContactRelation = $student->emergency_contact_relation ?: null;
                                @endphp

                                <tr>
                                    <td class="text-muted">
                                        {{ ($students->currentPage() - 1) * $students->perPage() + $loop->iteration }}
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ $studentName }}
                                        </div>

                                        @if ($student->goal)
                                            <div
                                                class="small text-muted text-truncate"
                                                style="max-width: 260px;"
                                                title="{{ $student->goal }}"
                                            >
                                                {{ $student->goal }}
                                            </div>
                                        @endif

                                        <div class="mt-2">
                                            @if($student->user)
                                                <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis border border-primary-subtle">
                                                    <i class="bi bi-person-check me-1"></i>Login Ready
                                                </span>
                                            @else
                                                <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle">
                                                    <i class="bi bi-person-x me-1"></i>No Login Account
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ $student->email ?: '-' }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ $student->phone ?: '-' }}
                                        </div>
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ $student->city ?: '-' }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ $student->current_status ?: '-' }}
                                        </div>
                                        <div class="small text-muted">
                                            Source: {{ $student->source ?: '-' }}
                                        </div>
                                    </td>

                                    <td class="text-nowrap">
                                        @if($studentNik !== '-')
                                            <div class="fw-semibold text-dark">
                                                {{ $studentNik }}
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    <td>
                                        @if($emergencyContactName || $emergencyContactPhone || $emergencyContactRelation)
                                            <div class="fw-semibold text-dark">
                                                {{ $emergencyContactName ?: '-' }}
                                            </div>

                                            <div class="small text-muted">
                                                {{ $emergencyContactPhone ?: '-' }}
                                            </div>

                                            @if($emergencyContactRelation)
                                                <div class="small text-muted">
                                                    Relation: {{ $emergencyContactRelation }}
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    <td>
                                        @if($activeEnrollments->count())
                                            <div class="d-flex flex-column gap-1">
                                                @foreach($activeEnrollments->take(2) as $enrollment)
                                                    <div>
                                                        <span class="badge rounded-pill bg-success-subtle text-success-emphasis border border-success-subtle">
                                                            {{ $enrollment->batch->program->name ?? 'Program' }}
                                                            -
                                                            {{ $enrollment->batch->name ?? 'Batch' }}
                                                        </span>
                                                    </div>
                                                @endforeach

                                                @if($activeEnrollments->count() > 2)
                                                    <small class="text-muted">
                                                        +{{ $activeEnrollments->count() - 2 }} more enrollment
                                                    </small>
                                                @endif
                                            </div>
                                        @else
                                            <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle">
                                                Not Enrolled
                                            </span>
                                        @endif
                                    </td>

                                    <td class="text-nowrap">
                                        <span class="badge rounded-pill {{ $statusClass }}">
                                            {{ ucfirst($student->status) }}
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
                                                        onclick="openEnrollModal(
                                                            {{ $student->id }},
                                                            @js($studentName),
                                                            @js($student->email),
                                                            '{{ route('students.enroll', $student->id) }}'
                                                        )"
                                                    >
                                                        <i class="bi bi-mortarboard me-2"></i>Enroll Student
                                                    </button>
                                                </li>

                                                <li>
                                                    <button
                                                        type="button"
                                                        class="dropdown-item"
                                                        onclick="editStudent({{ $student->id }})"
                                                    >
                                                        <i class="bi bi-pencil-square me-2"></i>Edit Student
                                                    </button>
                                                </li>

                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>

                                                <li>
                                                    <button
                                                        type="button"
                                                        class="dropdown-item text-danger"
                                                        onclick="openDeleteModal({{ $student->id }}, @js($studentName))"
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

                @if ($students->hasPages())
                    <div class="mt-3">
                        {{ $students->links() }}
                    </div>
                @endif
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-people"></i>
                    </div>

                    <h5 class="empty-state-title">No students found</h5>
                    <p class="empty-state-text mb-0">
                        Add confirmed student data first, then enroll them to a batch.
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Student Form Modal --}}
<div class="modal fade" id="studentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="studentForm">
            @csrf
            <input type="hidden" id="student_id">

            <div class="modal-content custom-modal">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title" id="studentModalTitle">Add Student</h5>
                        <p class="text-muted mb-0">Manage basic student information.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-4">
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
                            <label for="status" class="form-label">
                                Student Status <span class="text-danger">*</span>
                            </label>
                            <select id="status" class="form-select">
                                <option value="inactive">Inactive</option>
                                <option value="active">Active</option>
                            </select>
                            <div class="invalid-feedback" id="error_status"></div>
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

                        <div class="col-12">
                            <div class="border-top pt-3 mt-1">
                                <div class="fw-semibold text-dark mb-1">Identity & Emergency Contact</div>
                                <div class="small text-muted mb-3">
                                    Optional data untuk identitas student dan kontak darurat.
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="nik" class="form-label">
                                NIK <span class="text-muted">(Optional)</span>
                            </label>
                            <input
                                type="text"
                                id="nik"
                                class="form-control"
                                maxlength="16"
                                inputmode="numeric"
                                pattern="[0-9]{16}"
                                placeholder="16 digit NIK"
                            >
                            <div class="form-text">Isi 16 digit angka jika tersedia.</div>
                            <div class="invalid-feedback" id="error_nik"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="emergency_contact_name" class="form-label">
                                Emergency Contact Name <span class="text-muted">(Optional)</span>
                            </label>
                            <input
                                type="text"
                                id="emergency_contact_name"
                                class="form-control"
                                maxlength="255"
                                placeholder="Nama kontak darurat"
                            >
                            <div class="invalid-feedback" id="error_emergency_contact_name"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="emergency_contact_phone" class="form-label">
                                Emergency Contact Phone <span class="text-muted">(Optional)</span>
                            </label>
                            <input
                                type="text"
                                id="emergency_contact_phone"
                                class="form-control"
                                maxlength="30"
                                placeholder="Nomor kontak darurat"
                            >
                            <div class="invalid-feedback" id="error_emergency_contact_phone"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="emergency_contact_relation" class="form-label">
                                Emergency Contact Relation <span class="text-muted">(Optional)</span>
                            </label>
                            <input
                                type="text"
                                id="emergency_contact_relation"
                                class="form-control"
                                maxlength="100"
                                placeholder="Contoh: Parent, Sibling, Guardian"
                            >
                            <div class="invalid-feedback" id="error_emergency_contact_relation"></div>
                        </div>

                        <div class="col-12">
                            <div class="border-top pt-3 mt-1">
                                <div class="fw-semibold text-dark mb-1">Profile Information</div>
                                <div class="small text-muted mb-3">
                                    Data tambahan untuk segmentasi dan kebutuhan enrollment.
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="city" class="form-label">City</label>
                            <input type="text" id="city" class="form-control">
                            <div class="invalid-feedback" id="error_city"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="current_status" class="form-label">Current Status</label>
                            <input type="text" id="current_status" class="form-control" placeholder="e.g. Student, Employee, Freelancer">
                            <div class="invalid-feedback" id="error_current_status"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="source" class="form-label">Source</label>
                            <input type="text" id="source" class="form-control" placeholder="e.g. Instagram, Referral, Event">
                            <div class="invalid-feedback" id="error_source"></div>
                        </div>

                        <div class="col-12">
                            <label for="goal" class="form-label">Goal</label>
                            <textarea id="goal" rows="4" class="form-control" placeholder="Why does this student want to join the program?"></textarea>
                            <div class="invalid-feedback" id="error_goal"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary btn-modern" id="submitBtn">
                        <span class="default-text">Save</span>
                        <span class="loading-text d-none">Saving...</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Enroll Modal --}}
<div class="modal fade" id="enrollModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="enrollForm">
            @csrf

            <div class="modal-content custom-modal">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title">Enroll Student</h5>
                        <p class="text-muted mb-0" id="enrollStudentSubtitle">Enroll student to selected batch.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-4">
                    <div id="enrollAlert" class="alert alert-danger d-none mb-3"></div>

                    <div class="soft-panel mb-3">
                        <div class="soft-panel-title" id="enrollStudentName">-</div>
                        <div class="soft-panel-subtitle" id="enrollStudentEmail">-</div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="enroll_batch_id" class="form-label">
                                Batch <span class="text-danger">*</span>
                            </label>
                            <select id="enroll_batch_id" class="form-select" required>
                                <option value="">Select Batch</option>
                                @foreach($batches ?? [] as $batch)
                                    <option value="{{ $batch->id }}">
                                        {{ $batch->program->name ?? 'Program' }} - {{ $batch->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="error_enroll_batch_id"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="enroll_status" class="form-label">Enrollment Status</label>
                            <select id="enroll_status" class="form-select">
                                <option value="active">Active</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="on_hold">On Hold</option>
                            </select>
                            <div class="invalid-feedback" id="error_enroll_status"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="enroll_access_status" class="form-label">Access Status</label>
                            <select id="enroll_access_status" class="form-select">
                                <option value="active">Active</option>
                                <option value="suspended">Suspended</option>
                                <option value="expired">Expired</option>
                            </select>
                            <div class="invalid-feedback" id="error_enroll_access_status"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="enroll_enrolled_at" class="form-label">Enrolled At</label>
                            <input type="datetime-local" id="enroll_enrolled_at" class="form-control">
                            <div class="invalid-feedback" id="error_enroll_enrolled_at"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="enroll_access_expires_at" class="form-label">Access Expires At</label>
                            <input type="datetime-local" id="enroll_access_expires_at" class="form-control">
                            <div class="invalid-feedback" id="error_enroll_access_expires_at"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="enroll_create_user_account" class="form-label">LMS Login Account</label>
                            <select id="enroll_create_user_account" class="form-select">
                                <option value="1">Create / Link Student User</option>
                                <option value="0">Enrollment Only</option>
                            </select>
                            <div class="form-text">
                                Untuk simulasi LMS, pilih Create / Link Student User.
                            </div>
                            <div class="invalid-feedback" id="error_enroll_create_user_account"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="enroll_password" class="form-label">Default Password</label>
                            <input type="text" id="enroll_password" class="form-control" value="password">
                            <div class="form-text">
                                Dipakai saat user student baru dibuat.
                            </div>
                            <div class="invalid-feedback" id="error_enroll_password"></div>
                        </div>

                        <div class="col-12">
                            <label for="enroll_notes" class="form-label">Notes</label>
                            <textarea id="enroll_notes" rows="3" class="form-control" placeholder="Catatan enrollment..."></textarea>
                            <div class="invalid-feedback" id="error_enroll_notes"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-success btn-modern" id="enrollSubmitBtn">
                        <span class="default-text">Enroll Student</span>
                        <span class="loading-text d-none">Enrolling...</span>
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
                <div class="delete-confirm-heading">
                    <div class="delete-confirm-icon">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div>
                        <h5 class="modal-title">Delete Student</h5>
                        <p class="text-muted mb-0">Konfirmasi sebelum menghapus student.</p>
                    </div>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pt-4">
                <div class="delete-confirm-message">
                    <div class="delete-confirm-label">Student yang akan dihapus</div>
                    <div class="delete-confirm-name" id="deleteStudentName">-</div>
                </div>

                <div class="delete-confirm-warning mt-3">
                    Student yang sudah dihapus tidak bisa dikembalikan.
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


@push('styles')
<style>
    .students-table-card,
    .students-table-card .content-card-body {
        min-width: 0;
        max-width: 100%;
        overflow: hidden;
    }

    .student-table-responsive {
        display: block;
        width: 100%;
        max-width: 100%;
        overflow-x: auto !important;
        overflow-y: visible;
        -webkit-overflow-scrolling: touch;
        overscroll-behavior-x: contain;
    }

    .student-admin-table {
        width: 100%;
        min-width: 1180px;
        table-layout: auto;
    }

    .student-admin-table th,
    .student-admin-table td {
        vertical-align: top;
    }

    .student-admin-table .col-student {
        min-width: 220px;
    }

    .student-admin-table .col-contact {
        min-width: 220px;
    }

    .student-admin-table .col-profile {
        min-width: 190px;
    }

    .student-admin-table .col-emergency {
        min-width: 220px;
    }

    .student-admin-table .col-enrollment {
        min-width: 220px;
    }

    .student-admin-table .dropdown-menu {
        z-index: 1080;
    }

    @media (max-width: 768px) {
        .container-fluid.px-4 {
            padding-left: 1rem !important;
            padding-right: 1rem !important;
        }

        .content-card-header {
            align-items: flex-start;
            gap: 1rem;
        }

        .student-admin-table {
            min-width: 1080px;
        }
    }
</style>
@endpush

@push('scripts')
<script>
let studentModal;
let deleteModal;
let enrollModal;
let deleteStudentId = null;
let enrollActionUrl = null;

document.addEventListener('DOMContentLoaded', function () {
    studentModal = new bootstrap.Modal(document.getElementById('studentModal'));
    deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    enrollModal = new bootstrap.Modal(document.getElementById('enrollModal'));

    document.getElementById('studentForm').addEventListener('submit', submitStudentForm);
    document.getElementById('confirmDeleteBtn').addEventListener('click', deleteStudent);
    document.getElementById('enrollForm').addEventListener('submit', submitEnrollForm);
});

function csrfToken() {
    return document.querySelector('#studentForm input[name="_token"]')?.value || '{{ csrf_token() }}';
}

function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');

    const toastId = 'toast-' + Date.now();
    const bgClass = type === 'success' ? 'bg-success' : 'bg-danger';

    container.insertAdjacentHTML('beforeend', `
        <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">${message}</div>
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

function resetStudentErrors() {
    document.querySelectorAll('#studentForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
    document.querySelectorAll('#studentForm .invalid-feedback').forEach(el => el.innerText = '');
    document.getElementById('formAlert').classList.add('d-none');
    document.getElementById('formAlert').innerText = '';
}

function resetEnrollErrors() {
    document.querySelectorAll('#enrollForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
    document.querySelectorAll('#enrollForm .invalid-feedback').forEach(el => el.innerText = '');
    document.getElementById('enrollAlert').classList.add('d-none');
    document.getElementById('enrollAlert').innerText = '';
}

function setButtonLoading(button, loading = true) {
    const defaultText = button.querySelector('.default-text');
    const loadingText = button.querySelector('.loading-text');

    button.disabled = loading;

    if (defaultText) defaultText.classList.toggle('d-none', loading);
    if (loadingText) loadingText.classList.toggle('d-none', !loading);
}

function fillValidationErrors(prefix, errors, alertId) {
    const alert = document.getElementById(alertId);
    let firstMessage = 'Please check the form again.';

    Object.keys(errors || {}).forEach(function (field) {
        const messages = errors[field];
        const message = Array.isArray(messages) ? messages[0] : messages;

        firstMessage = message || firstMessage;

        const input = document.getElementById(prefix + field);
        const feedback = document.getElementById('error_' + prefix + field);

        if (input) input.classList.add('is-invalid');
        if (feedback) feedback.innerText = message;
    });

    alert.innerText = firstMessage;
    alert.classList.remove('d-none');
}

function openCreateModal() {
    resetStudentErrors();

    document.getElementById('studentModalTitle').innerText = 'Add Student';
    document.getElementById('student_id').value = '';

    document.getElementById('full_name').value = '';
    document.getElementById('status').value = 'inactive';
    document.getElementById('email').value = '';
    document.getElementById('phone').value = '';
    document.getElementById('nik').value = '';
    document.getElementById('emergency_contact_name').value = '';
    document.getElementById('emergency_contact_phone').value = '';
    document.getElementById('emergency_contact_relation').value = '';
    document.getElementById('city').value = '';
    document.getElementById('current_status').value = '';
    document.getElementById('source').value = '';
    document.getElementById('goal').value = '';

    studentModal.show();
}

async function editStudent(id) {
    resetStudentErrors();

    try {
        const url = `{{ route('students.show', ['student' => '__ID__']) }}`.replace('__ID__', id);

        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        const result = await response.json();

        if (!response.ok) {
            showToast(result.message || 'Failed to load student.', 'error');
            return;
        }

        const student = result.data || result;

        document.getElementById('studentModalTitle').innerText = 'Edit Student';
        document.getElementById('student_id').value = student.id;

        document.getElementById('full_name').value = student.full_name || '';
        document.getElementById('status').value = student.status || 'inactive';
        document.getElementById('email').value = student.email || '';
        document.getElementById('phone').value = student.phone || '';
        document.getElementById('nik').value = student.nik || '';
        document.getElementById('emergency_contact_name').value = student.emergency_contact_name || '';
        document.getElementById('emergency_contact_phone').value = student.emergency_contact_phone || '';
        document.getElementById('emergency_contact_relation').value = student.emergency_contact_relation || '';
        document.getElementById('city').value = student.city || '';
        document.getElementById('current_status').value = student.current_status || '';
        document.getElementById('source').value = student.source || '';
        document.getElementById('goal').value = student.goal || '';

        studentModal.show();
    } catch (error) {
        showToast('Failed to load student.', 'error');
    }
}

async function submitStudentForm(event) {
    event.preventDefault();
    resetStudentErrors();

    const submitBtn = document.getElementById('submitBtn');
    setButtonLoading(submitBtn, true);

    const id = document.getElementById('student_id').value;

    const formData = new FormData();
    formData.append('_token', csrfToken());
    formData.append('full_name', document.getElementById('full_name').value);
    formData.append('status', document.getElementById('status').value);
    formData.append('email', document.getElementById('email').value);
    formData.append('phone', document.getElementById('phone').value);
    formData.append('nik', document.getElementById('nik').value);
    formData.append('emergency_contact_name', document.getElementById('emergency_contact_name').value);
    formData.append('emergency_contact_phone', document.getElementById('emergency_contact_phone').value);
    formData.append('emergency_contact_relation', document.getElementById('emergency_contact_relation').value);
    formData.append('city', document.getElementById('city').value);
    formData.append('current_status', document.getElementById('current_status').value);
    formData.append('source', document.getElementById('source').value);
    formData.append('goal', document.getElementById('goal').value);

    let url = '{{ route('students.store') }}';

    if (id) {
        url = `{{ route('students.update', ['student' => '__ID__']) }}`.replace('__ID__', id);
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
                fillValidationErrors('', result.errors || {}, 'formAlert');
            } else {
                document.getElementById('formAlert').innerText = result.message || 'Failed to save student.';
                document.getElementById('formAlert').classList.remove('d-none');
            }

            setButtonLoading(submitBtn, false);
            return;
        }

        showToast(result.message || 'Student saved successfully.');
        studentModal.hide();

        setTimeout(() => window.location.reload(), 700);
    } catch (error) {
        document.getElementById('formAlert').innerText = 'Failed to connect to server.';
        document.getElementById('formAlert').classList.remove('d-none');
        setButtonLoading(submitBtn, false);
    }
}

function openEnrollModal(studentId, studentName, studentEmail, actionUrl) {
    resetEnrollErrors();

    enrollActionUrl = actionUrl;

    document.getElementById('enrollStudentName').innerText = studentName || '-';
    document.getElementById('enrollStudentEmail').innerText = studentEmail || 'No email set';
    document.getElementById('enrollStudentSubtitle').innerText = `Enroll ${studentName || 'student'} to selected batch.`;

    document.getElementById('enroll_batch_id').value = '';
    document.getElementById('enroll_status').value = 'active';
    document.getElementById('enroll_access_status').value = 'active';
    document.getElementById('enroll_enrolled_at').value = '';
    document.getElementById('enroll_access_expires_at').value = '';
    document.getElementById('enroll_create_user_account').value = '1';
    document.getElementById('enroll_password').value = 'password';
    document.getElementById('enroll_notes').value = '';

    enrollModal.show();
}

async function submitEnrollForm(event) {
    event.preventDefault();
    resetEnrollErrors();

    if (!enrollActionUrl) {
        showToast('Enroll route is missing.', 'error');
        return;
    }

    const submitBtn = document.getElementById('enrollSubmitBtn');
    setButtonLoading(submitBtn, true);

    const formData = new FormData();
    formData.append('_token', csrfToken());
    formData.append('batch_id', document.getElementById('enroll_batch_id').value);
    formData.append('status', document.getElementById('enroll_status').value);
    formData.append('access_status', document.getElementById('enroll_access_status').value);
    formData.append('enrolled_at', document.getElementById('enroll_enrolled_at').value);
    formData.append('access_expires_at', document.getElementById('enroll_access_expires_at').value);
    formData.append('create_user_account', document.getElementById('enroll_create_user_account').value);
    formData.append('password', document.getElementById('enroll_password').value);
    formData.append('notes', document.getElementById('enroll_notes').value);

    try {
        const response = await fetch(enrollActionUrl, {
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
                fillValidationErrors('enroll_', result.errors || {}, 'enrollAlert');
            } else {
                document.getElementById('enrollAlert').innerText = result.message || 'Failed to enroll student.';
                document.getElementById('enrollAlert').classList.remove('d-none');
            }

            setButtonLoading(submitBtn, false);
            return;
        }

        showToast(result.message || 'Student enrolled successfully.');
        enrollModal.hide();

        setTimeout(() => window.location.reload(), 700);
    } catch (error) {
        document.getElementById('enrollAlert').innerText = 'Failed to connect to server.';
        document.getElementById('enrollAlert').classList.remove('d-none');
        setButtonLoading(submitBtn, false);
    }
}

function openDeleteModal(id, name) {
    deleteStudentId = id;
    document.getElementById('deleteStudentName').innerText = name;
    deleteModal.show();
}

async function deleteStudent() {
    if (!deleteStudentId) return;

    const deleteBtn = document.getElementById('confirmDeleteBtn');
    setButtonLoading(deleteBtn, true);

    const formData = new FormData();
    formData.append('_token', csrfToken());
    formData.append('_method', 'DELETE');

    try {
        const url = `{{ route('students.destroy', ['student' => '__ID__']) }}`.replace('__ID__', deleteStudentId);

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
            showToast(result.message || 'Failed to delete student.', 'error');
            setButtonLoading(deleteBtn, false);
            return;
        }

        showToast(result.message || 'Student deleted successfully.');
        deleteModal.hide();

        setTimeout(() => window.location.reload(), 700);
    } catch (error) {
        showToast('Failed to connect to server.', 'error');
        setButtonLoading(deleteBtn, false);
    }
}
</script>
@endpush