@extends('layouts.app-dashboard')

@section('title', 'Profile Settings')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="mb-1">Profile Settings</h4>
            <small class="text-muted">
                Kelola informasi akun dan keamanan agar akses tetap aman dan data akun tetap up to date.
            </small>
        </div>

        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <div
        id="toastContainer"
        class="toast-container position-fixed top-0 end-0 p-3"
        style="z-index: 9999;"
    ></div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form
                id="profileForm"
                action="{{ route('profile.update') }}"
                method="POST"
            >
                @csrf
                @method('PATCH')

                <div id="profileFormAlert" class="alert alert-danger d-none mb-4"></div>

                <div class="mb-4">
                    <h5 class="fw-bold mb-1">Account Information</h5>
                    <p class="text-muted small mb-0">
                        Update nama dan email yang digunakan untuk akun dashboard ini.
                    </p>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">
                            Name <span class="text-danger">*</span>
                        </label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name', $user->name) }}"
                        >
                        <div class="invalid-feedback" id="error_name">
                            @error('name') {{ $message }} @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label for="email" class="form-label">
                            Email <span class="text-danger">*</span>
                        </label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email', $user->email) }}"
                        >
                        <div class="invalid-feedback" id="error_email">
                            @error('email') {{ $message }} @enderror
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <button type="submit" class="btn btn-primary" id="profileSubmitBtn">
                        <span class="default-text">
                            <i class="bi bi-check-circle me-1"></i>
                            Update Profile
                        </span>
                        <span class="loading-text d-none">
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            Saving...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form
                id="passwordForm"
                action="{{ route('password.update') }}"
                method="POST"
            >
                @csrf
                @method('PUT')

                <div id="passwordFormAlert" class="alert alert-danger d-none mb-4"></div>

                <div class="mb-4">
                    <h5 class="fw-bold mb-1">Security Settings</h5>
                    <p class="text-muted small mb-0">
                        Perbarui password akun secara berkala untuk menjaga keamanan akses dashboard.
                    </p>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="current_password" class="form-label">
                            Current Password <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input
                                type="password"
                                id="current_password"
                                name="current_password"
                                class="form-control @error('current_password', 'updatePassword') is-invalid @enderror"
                            >
                            <button
                                class="btn btn-outline-secondary toggle-password"
                                type="button"
                                data-target="current_password"
                                aria-label="Show or hide current password"
                            >
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback d-block" id="error_current_password">
                            @error('current_password', 'updatePassword') {{ $message }} @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label for="password" class="form-label">
                            New Password <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-control @error('password', 'updatePassword') is-invalid @enderror"
                            >
                            <button
                                class="btn btn-outline-secondary toggle-password"
                                type="button"
                                data-target="password"
                                aria-label="Show or hide new password"
                            >
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback d-block" id="error_password">
                            @error('password', 'updatePassword') {{ $message }} @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label for="password_confirmation" class="form-label">
                            Confirm Password <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input
                                type="password"
                                id="password_confirmation"
                                name="password_confirmation"
                                class="form-control"
                            >
                            <button
                                class="btn btn-outline-secondary toggle-password"
                                type="button"
                                data-target="password_confirmation"
                                aria-label="Show or hide password confirmation"
                            >
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback d-block" id="error_password_confirmation"></div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <button type="submit" class="btn btn-primary" id="passwordSubmitBtn">
                        <span class="default-text">
                            <i class="bi bi-shield-lock me-1"></i>
                            Update Password
                        </span>
                        <span class="loading-text d-none">
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            Saving...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const profileForm = document.getElementById('profileForm');
    const passwordForm = document.getElementById('passwordForm');

    const profileSubmitBtn = document.getElementById('profileSubmitBtn');
    const passwordSubmitBtn = document.getElementById('passwordSubmitBtn');

    const profileFormAlert = document.getElementById('profileFormAlert');
    const passwordFormAlert = document.getElementById('passwordFormAlert');

    const profileFields = {
        name: document.getElementById('name'),
        email: document.getElementById('email'),
    };

    const passwordFields = {
        current_password: document.getElementById('current_password'),
        password: document.getElementById('password'),
        password_confirmation: document.getElementById('password_confirmation'),
    };

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
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', html);

        const toastEl = document.getElementById(id);
        const toast = new bootstrap.Toast(toastEl, { delay: 2500 });
        toast.show();

        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    }

    function setSubmitLoading(button, isLoading) {
        button.disabled = isLoading;
        button.querySelector('.default-text').classList.toggle('d-none', isLoading);
        button.querySelector('.loading-text').classList.toggle('d-none', !isLoading);
    }

    function clearValidationErrors(fields, alertEl) {
        Object.values(fields).forEach(field => {
            field.classList.remove('is-invalid');
        });

        Object.keys(fields).forEach(key => {
            const errorEl = document.getElementById(`error_${key}`);
            if (errorEl) {
                errorEl.textContent = '';
            }
        });

        alertEl.classList.add('d-none');
        alertEl.innerHTML = '';
    }

    function setValidationErrors(fields, errors = {}) {
        Object.keys(errors).forEach(key => {
            const field = fields[key];
            const errorEl = document.getElementById(`error_${key}`);

            if (field) {
                field.classList.add('is-invalid');
            }

            if (errorEl) {
                errorEl.textContent = Array.isArray(errors[key]) ? errors[key][0] : errors[key];
            }
        });
    }

    async function submitAsyncForm({
        form,
        method,
        fields,
        alertEl,
        submitBtn,
        successMessage,
        onSuccess = null,
    }) {
        clearValidationErrors(fields, alertEl);
        setSubmitLoading(submitBtn, true);

        const payload = {};
        Object.keys(fields).forEach(key => {
            payload[key] = fields[key].value;
        });

        try {
            const response = await fetch(form.getAttribute('action'), {
                method: method,
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
                setValidationErrors(fields, result.errors || {});
                showToast(result.message || 'Validation failed.', 'danger');
                return;
            }

            if (!response.ok || result.success === false) {
                throw new Error(result.message || 'Something went wrong.');
            }

            showToast(result.message || successMessage, 'success');

            if (typeof onSuccess === 'function') {
                onSuccess(result);
            }
        } catch (error) {
            alertEl.classList.remove('d-none');
            alertEl.innerHTML = error.message || 'Something went wrong.';
            showToast(error.message || 'Something went wrong.', 'danger');
        } finally {
            setSubmitLoading(submitBtn, false);
        }
    }

    profileForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        await submitAsyncForm({
            form: profileForm,
            method: 'PATCH',
            fields: profileFields,
            alertEl: profileFormAlert,
            submitBtn: profileSubmitBtn,
            successMessage: 'Profile berhasil diperbarui.',
        });
    });

    passwordForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        await submitAsyncForm({
            form: passwordForm,
            method: 'PUT',
            fields: passwordFields,
            alertEl: passwordFormAlert,
            submitBtn: passwordSubmitBtn,
            successMessage: 'Password berhasil diperbarui.',
            onSuccess: function () {
                passwordFields.current_password.value = '';
                passwordFields.password.value = '';
                passwordFields.password_confirmation.value = '';
            }
        });
    });

    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function () {
            const target = document.getElementById(this.dataset.target);
            const icon = this.querySelector('i');

            if (target.type === 'password') {
                target.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                target.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });
    });
</script>
@endpush