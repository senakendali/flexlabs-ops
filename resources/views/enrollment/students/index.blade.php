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

    <div class="content-card">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Student List</h5>
                <p class="content-card-subtitle mb-0">
                    Kelola data student yang sudah confirm, lalu enroll ke batch untuk simulasi akses LMS.
                </p>
            </div>

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

        <div class="content-card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 70px;">No</th>
                            <th>Student</th>
                            <th>Contact</th>
                            <th>Profile</th>
                            <th>Enrollment</th>
                            <th style="width: 120px;">Status</th>
                            <th style="width: 230px;" class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($students as $student)
                            @php
                                $statusClass = match($student->status) {
                                    'active' => 'ui-badge-success',
                                    'inactive' => 'ui-badge-muted',
                                    default => 'ui-badge-muted',
                                };

                                $activeEnrollments = $student->enrollments
                                    ? $student->enrollments->where('status', 'active')->where('access_status', 'active')
                                    : collect();

                                $studentName = $student->full_name ?? $student->name ?? '-';
                            @endphp

                            <tr>
                                <td>
                                    {{ ($students->currentPage() - 1) * $students->perPage() + $loop->iteration }}
                                </td>

                                <td>
                                    <div class="fw-bold text-dark">{{ $studentName }}</div>

                                    @if ($student->goal)
                                        <div class="small text-muted text-truncate" style="max-width: 280px;" title="{{ $student->goal }}">
                                            {{ $student->goal }}
                                        </div>
                                    @endif

                                    @if($student->user)
                                        <div class="mt-1">
                                            <span class="ui-badge ui-badge-primary">
                                                <i class="bi bi-person-check me-1"></i>Login Ready
                                            </span>
                                        </div>
                                    @else
                                        <div class="mt-1">
                                            <span class="ui-badge ui-badge-muted">
                                                <i class="bi bi-person-x me-1"></i>No Login Account
                                            </span>
                                        </div>
                                    @endif
                                </td>

                                <td>
                                    <div>{{ $student->email ?: '-' }}</div>
                                    <div class="small text-muted">{{ $student->phone ?: '-' }}</div>
                                </td>

                                <td>
                                    <div>{{ $student->city ?: '-' }}</div>
                                    <div class="small text-muted">{{ $student->current_status ?: '-' }}</div>
                                    <div class="small text-muted">Source: {{ $student->source ?: '-' }}</div>
                                </td>

                                <td>
                                    @if($activeEnrollments->count())
                                        <div class="d-flex flex-column gap-1">
                                            @foreach($activeEnrollments->take(2) as $enrollment)
                                                <div>
                                                    <span class="ui-badge ui-badge-success">
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
                                        <span class="ui-badge ui-badge-muted">
                                            Not Enrolled
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    <span class="ui-badge {{ $statusClass }}">
                                        {{ ucfirst($student->status) }}
                                    </span>
                                </td>

                                <td class="text-end">
                                    <div class="d-inline-flex gap-2 flex-wrap justify-content-end">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-success"
                                            onclick="openEnrollModal(
                                                {{ $student->id }},
                                                @js($studentName),
                                                @js($student->email),
                                                '{{ route('students.enroll', $student->id) }}'
                                            )"
                                        >
                                            <i class="bi bi-mortarboard me-1"></i>Enroll
                                        </button>

                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            onclick="editStudent({{ $student->id }})"
                                        >
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="openDeleteModal({{ $student->id }}, @js($studentName))"
                                        >
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="empty-state-icon mx-auto">
                                        <i class="bi bi-people"></i>
                                    </div>
                                    <h5 class="empty-state-title mt-3">No students found</h5>
                                    <p class="empty-state-text mb-0">
                                        Add confirmed student data first, then enroll them to a batch.
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($students->hasPages())
                <div class="p-3 border-top">
                    {{ $students->links() }}
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