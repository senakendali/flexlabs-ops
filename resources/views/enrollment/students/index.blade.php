@extends('layouts.app-dashboard')

@section('title', 'Students')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="mb-1">Students</h4>
            <small class="text-muted">Manage student master data for enrollment and payments</small>
        </div>

        <button type="button" class="btn btn-primary" onclick="openCreateModal()">
            <i class="bi bi-plus-lg me-1"></i> Add Student
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
                        <th>Full Name</th>
                        <th>Contact</th>
                        <th>City</th>
                        <th>Current Status</th>
                        <th>Source</th>
                        <th style="width: 120px;">Student Status</th>
                        <th style="width: 170px;" class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($students as $student)
                        <tr>
                            <td>
                                {{ ($students->currentPage() - 1) * $students->perPage() + $loop->iteration }}
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $student->full_name }}</div>
                                @if ($student->goal)
                                    <div class="small text-muted text-truncate" style="max-width: 240px;" title="{{ $student->goal }}">
                                        {{ $student->goal }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div>{{ $student->email ?: '-' }}</div>
                                <div class="small text-muted">{{ $student->phone ?: '-' }}</div>
                            </td>
                            <td>{{ $student->city ?: '-' }}</td>
                            <td>{{ $student->current_status ?: '-' }}</td>
                            <td>{{ $student->source ?: '-' }}</td>
                            <td>
                                @php
                                    $statusClass = match($student->status) {
                                        'lead' => 'bg-secondary',
                                        'trial' => 'bg-warning text-dark',
                                        'active' => 'bg-success',
                                        'inactive' => 'bg-danger',
                                        default => 'bg-secondary',
                                    };
                                @endphp
                                <span class="badge {{ $statusClass }}">
                                    {{ ucfirst($student->status) }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-inline-flex gap-2">
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
                                        onclick="openDeleteModal({{ $student->id }}, @js($student->full_name))"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                No students found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($students->hasPages())
            <div class="card-footer bg-white">
                {{ $students->links() }}
            </div>
        @endif
    </div>
</div>

{{-- Form Modal --}}
<div class="modal fade" id="studentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="studentForm">
            @csrf
            <input type="hidden" id="student_id">

            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title" id="studentModalTitle">Add Student</h5>
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
                            <label for="status" class="form-label">
                                Student Status <span class="text-danger">*</span>
                            </label>
                            <select id="status" class="form-select">
                                <option value="lead">Lead</option>
                                <option value="trial">Trial</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
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
                <h5 class="modal-title">Delete Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <p class="mb-0">
                    Are you sure you want to delete
                    <strong id="deleteStudentName"></strong>?
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
    const studentModalEl = document.getElementById('studentModal');
    const studentModal = new bootstrap.Modal(studentModalEl);
    const studentForm = document.getElementById('studentForm');
    const submitBtn = document.getElementById('submitBtn');
    const modalTitle = document.getElementById('studentModalTitle');
    const formAlert = document.getElementById('formAlert');

    const deleteModalEl = document.getElementById('deleteModal');
    const deleteModal = new bootstrap.Modal(deleteModalEl);
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const deleteStudentNameEl = document.getElementById('deleteStudentName');

    const fields = {
        id: document.getElementById('student_id'),
        full_name: document.getElementById('full_name'),
        email: document.getElementById('email'),
        phone: document.getElementById('phone'),
        city: document.getElementById('city'),
        current_status: document.getElementById('current_status'),
        goal: document.getElementById('goal'),
        source: document.getElementById('source'),
        status: document.getElementById('status'),
    };

    let deleteStudentId = null;
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
            <div id="${id}" class="toast align-items-center text-white ${bgClass} border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
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
        }, 3000);
    }

    function resetForm() {
        studentForm.reset();
        fields.id.value = '';
        fields.status.value = 'lead';
        formAlert.classList.add('d-none');
        formAlert.innerHTML = '';
        clearValidationErrors();
        setSubmitLoading(false);
    }

    function clearValidationErrors() {
        Object.values(fields).forEach(field => {
            if (field && field.classList) {
                field.classList.remove('is-invalid');
            }
        });

        [
            'full_name',
            'email',
            'phone',
            'city',
            'current_status',
            'goal',
            'source',
            'status'
        ].forEach(key => {
            const errorEl = document.getElementById(`error_${key}`);
            if (errorEl) {
                errorEl.textContent = '';
            }
        });
    }

    function setValidationErrors(errors = {}) {
        clearValidationErrors();

        Object.keys(errors).forEach(key => {
            const field = fields[key];
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

    function openCreateModal() {
        resetForm();
        modalTitle.textContent = 'Add Student';
        studentModal.show();
    }

    async function editStudent(id) {
        resetForm();
        modalTitle.textContent = 'Edit Student';

        try {
            const response = await fetch(`/enrollment/students/${id}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to fetch student data.');
            }

            const data = result.data;

            fields.id.value = data.id ?? '';
            fields.full_name.value = data.full_name ?? '';
            fields.email.value = data.email ?? '';
            fields.phone.value = data.phone ?? '';
            fields.city.value = data.city ?? '';
            fields.current_status.value = data.current_status ?? '';
            fields.goal.value = data.goal ?? '';
            fields.source.value = data.source ?? '';
            fields.status.value = data.status ?? 'lead';

            studentModal.show();
        } catch (error) {
            formAlert.classList.remove('d-none');
            formAlert.innerHTML = error.message || 'Failed to load student data.';
            studentModal.show();
        }
    }

    function openDeleteModal(id, name) {
        deleteStudentId = id;
        deleteStudentNameEl.textContent = name || '-';
        setDeleteLoading(false);
        deleteModal.show();
    }

    studentForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        clearValidationErrors();
        formAlert.classList.add('d-none');
        formAlert.innerHTML = '';

        const id = fields.id.value;
        const url = id ? `/enrollment/students/${id}` : `/enrollment/students`;

        const payload = {
            full_name: fields.full_name.value.trim(),
            email: fields.email.value.trim(),
            phone: fields.phone.value.trim(),
            city: fields.city.value.trim(),
            current_status: fields.current_status.value.trim(),
            goal: fields.goal.value.trim(),
            source: fields.source.value.trim(),
            status: fields.status.value,
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
                throw new Error(result.message || 'Failed to save student.');
            }

            studentModal.hide();
            showToast(result.message || 'Student saved successfully', 'success');
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
        if (!deleteStudentId) return;

        setDeleteLoading(true);

        try {
            const response = await fetch(`/enrollment/students/${deleteStudentId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to delete student.');
            }

            deleteModal.hide();
            showToast(result.message || 'Student deleted successfully', 'danger');
            scheduleReload();
        } catch (error) {
            showToast(error.message || 'Failed to delete student.', 'danger');
        } finally {
            setDeleteLoading(false);
            deleteStudentId = null;
        }
    });

    studentModalEl.addEventListener('hidden.bs.modal', function () {
        resetForm();
    });

    deleteModalEl.addEventListener('hidden.bs.modal', function () {
        deleteStudentId = null;
        deleteStudentNameEl.textContent = '';
        setDeleteLoading(false);
    });
</script>
@endpush