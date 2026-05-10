@extends('layouts.app-dashboard')

@section('title', 'User Management')

@section('content')
<div class="container-fluid px-4 py-4">

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">System Settings</div>
                <h1 class="page-title mb-2">User Management</h1>
                <p class="page-subtitle mb-0">
                    Manage FlexOps users, roles, account types, and access ownership.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-light btn-modern" onclick="openCreateModal()">
                    <i class="bi bi-plus-lg me-2"></i>Add User
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
                        <div class="stat-title">Total Result</div>
                        <div class="stat-value">{{ $users->total() }}</div>
                    </div>
                </div>
                <div class="stat-description">Total user berdasarkan filter aktif.</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-shield-lock-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Roles</div>
                        <div class="stat-value">{{ count($roles ?? []) }}</div>
                    </div>
                </div>
                <div class="stat-description">Role resmi yang tersedia di FlexOps.</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-person-badge-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">User Types</div>
                        <div class="stat-value">{{ count($userTypes ?? []) }}</div>
                    </div>
                </div>
                <div class="stat-description">Tipe akun: staff, instructor, dan student.</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-person-check-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Current Page</div>
                        <div class="stat-value">{{ $users->count() }}</div>
                    </div>
                </div>
                <div class="stat-description">Jumlah user yang tampil di halaman ini.</div>
            </div>
        </div>
    </div>

    <div class="content-card users-table-card">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">User List</h5>
                <p class="content-card-subtitle mb-0">
                    Kelola akses user berdasarkan role dan tipe akun tanpa reload form.
                </p>
            </div>

            <form method="GET" class="d-flex align-items-center gap-2 flex-wrap">
                <input
                    type="text"
                    name="search"
                    class="form-control form-control-sm"
                    style="width: 220px;"
                    value="{{ $search ?? request('search') }}"
                    placeholder="Search user..."
                >

                <select name="role" class="form-select form-select-sm" style="width: 150px;">
                    <option value="">All Roles</option>
                    @foreach ($roles as $value => $label)
                        <option value="{{ $value }}" {{ ($selectedRole ?? request('role')) === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>

                <select name="user_type" class="form-select form-select-sm" style="width: 150px;">
                    <option value="">All Types</option>
                    @foreach ($userTypes as $value => $label)
                        <option value="{{ $value }}" {{ ($selectedUserType ?? request('user_type')) === $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>

                <select
                    name="per_page"
                    class="form-select form-select-sm"
                    style="width: auto;"
                >
                    @foreach ([10, 15, 25, 50, 100] as $size)
                        <option value="{{ $size }}" {{ (int) request('per_page', $perPage ?? 15) === $size ? 'selected' : '' }}>
                            {{ $size }}
                        </option>
                    @endforeach
                </select>

                <button type="submit" class="btn btn-sm btn-outline-primary btn-modern">
                    <i class="bi bi-search me-1"></i>Filter
                </button>

                <a href="{{ route('settings.users.index') }}" class="btn btn-sm btn-outline-secondary btn-modern">
                    Reset
                </a>
            </form>
        </div>

        <div class="content-card-body">
            @if($users->count())
                <div class="user-table-responsive dropdown-safe-table">
                    <table class="table table-hover align-middle admin-table user-admin-table mb-0">
                        <thead>
                            <tr>
                                <th class="text-nowrap" style="width: 80px;">No</th>
                                <th class="text-nowrap col-user">User</th>
                                <th class="text-nowrap">Role</th>
                                <th class="text-nowrap">User Type</th>
                                <th class="text-nowrap">Email Status</th>
                                <th class="text-nowrap">Created</th>
                                <th class="text-end text-nowrap" style="width: 160px;">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($users as $user)
                                @php
                                    $roleLabel = $roles[$user->role] ?? ucfirst((string) $user->role);
                                    $userTypeLabel = $userTypes[$user->user_type] ?? ucfirst((string) $user->user_type);

                                    $roleClass = match($user->role) {
                                        'admin' => 'bg-danger-subtle text-danger-emphasis border border-danger-subtle',
                                        'academic' => 'bg-primary-subtle text-primary-emphasis border border-primary-subtle',
                                        'marketing' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
                                        'sales' => 'bg-success-subtle text-success-emphasis border border-success-subtle',
                                        'finance' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
                                        'hr' => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
                                        'instructor' => 'bg-dark-subtle text-dark-emphasis border border-dark-subtle',
                                        'student' => 'bg-light text-dark border',
                                        default => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
                                    };

                                    $typeClass = match($user->user_type) {
                                        'staff' => 'bg-primary-subtle text-primary-emphasis border border-primary-subtle',
                                        'instructor' => 'bg-dark-subtle text-dark-emphasis border border-dark-subtle',
                                        'student' => 'bg-success-subtle text-success-emphasis border border-success-subtle',
                                        default => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
                                    };

                                    $isCurrentUser = auth()->id() === $user->id;
                                @endphp

                                <tr>
                                    <td class="text-muted">
                                        {{ ($users->currentPage() - 1) * $users->perPage() + $loop->iteration }}
                                    </td>

                                    <td>
                                        <div class="d-flex align-items-start gap-3">
                                            <div class="user-avatar">
                                                {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                                            </div>

                                            <div class="min-w-0">
                                                <div class="fw-semibold text-dark d-flex align-items-center gap-2 flex-wrap">
                                                    {{ $user->name }}

                                                    @if($isCurrentUser)
                                                        <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis border border-primary-subtle">
                                                            You
                                                        </span>
                                                    @endif
                                                </div>

                                                <div class="small text-muted text-truncate" style="max-width: 320px;">
                                                    {{ $user->email }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="text-nowrap">
                                        <span class="badge rounded-pill {{ $roleClass }}">
                                            {{ $roleLabel }}
                                        </span>
                                    </td>

                                    <td class="text-nowrap">
                                        <span class="badge rounded-pill {{ $typeClass }}">
                                            {{ $userTypeLabel }}
                                        </span>
                                    </td>

                                    <td class="text-nowrap">
                                        @if($user->email_verified_at)
                                            <span class="badge rounded-pill bg-success-subtle text-success-emphasis border border-success-subtle">
                                                <i class="bi bi-check-circle me-1"></i>Verified
                                            </span>
                                        @else
                                            <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle">
                                                <i class="bi bi-clock me-1"></i>Not Verified
                                            </span>
                                        @endif
                                    </td>

                                    <td class="text-nowrap">
                                        <div class="fw-semibold text-dark">
                                            {{ optional($user->created_at)->format('d M Y') }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ optional($user->created_at)->format('H:i') }}
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
                                                        onclick="editUser({{ $user->id }})"
                                                    >
                                                        <i class="bi bi-pencil-square me-2"></i>Edit User
                                                    </button>
                                                </li>

                                                <li>
                                                    <button
                                                        type="button"
                                                        class="dropdown-item"
                                                        onclick="openPasswordModal({{ $user->id }}, @js($user->name), @js($user->email))"
                                                    >
                                                        <i class="bi bi-key me-2"></i>Change Password
                                                    </button>
                                                </li>

                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>

                                                <li>
                                                    <button
                                                        type="button"
                                                        class="dropdown-item text-danger"
                                                        onclick="openDeleteModal({{ $user->id }}, @js($user->name), {{ $isCurrentUser ? 'true' : 'false' }})"
                                                        {{ $isCurrentUser ? 'disabled' : '' }}
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

                @if ($users->hasPages())
                    <div class="mt-3">
                        {{ $users->links() }}
                    </div>
                @endif
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-people"></i>
                    </div>

                    <h5 class="empty-state-title">No users found</h5>
                    <p class="empty-state-text mb-0">
                        Tambahkan user baru untuk mulai mengatur akses FlexOps.
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- User Form Modal --}}
<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="userForm">
            @csrf
            <input type="hidden" id="user_id">

            <div class="modal-content custom-modal">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title" id="userModalTitle">Add User</h5>
                        <p class="text-muted mb-0">Manage user account, role, and access type.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-4">
                    <div id="formAlert" class="alert alert-danger d-none mb-3"></div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="user_name" class="form-label">
                                Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="user_name" class="form-control">
                            <div class="invalid-feedback" id="error_user_name"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="user_email" class="form-label">
                                Email <span class="text-danger">*</span>
                            </label>
                            <input type="email" id="user_email" class="form-control">
                            <div class="invalid-feedback" id="error_user_email"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="user_role" class="form-label">
                                Role <span class="text-danger">*</span>
                            </label>
                            <select id="user_role" class="form-select" onchange="syncUserTypeByRole()">
                                @foreach($roles as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">
                                Role dipakai untuk grouping menu dan akses modul.
                            </div>
                            <div class="invalid-feedback" id="error_user_role"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="user_user_type" class="form-label">
                                User Type <span class="text-danger">*</span>
                            </label>
                            <select id="user_user_type" class="form-select">
                                @foreach($userTypes as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">
                                Akan otomatis disesuaikan dari role.
                            </div>
                            <div class="invalid-feedback" id="error_user_user_type"></div>
                        </div>

                        <div class="col-12">
                            <div class="border-top pt-3 mt-1">
                                <div class="fw-semibold text-dark mb-1">Login Password</div>
                                <div class="small text-muted mb-3" id="passwordSectionCaption">
                                    Password wajib diisi saat membuat user baru.
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 password-create-field">
                            <label for="user_password" class="form-label">
                                Password <span class="text-danger">*</span>
                            </label>
                            <input type="password" id="user_password" class="form-control" autocomplete="new-password">
                            <div class="invalid-feedback" id="error_user_password"></div>
                        </div>

                        <div class="col-md-6 password-create-field">
                            <label for="user_password_confirmation" class="form-label">
                                Confirm Password <span class="text-danger">*</span>
                            </label>
                            <input type="password" id="user_password_confirmation" class="form-control" autocomplete="new-password">
                            <div class="invalid-feedback" id="error_user_password_confirmation"></div>
                        </div>

                        <div class="col-12 edit-password-info d-none">
                            <div class="soft-panel">
                                <div class="soft-panel-title">
                                    Password tidak diubah dari form edit.
                                </div>
                                <div class="soft-panel-subtitle">
                                    Gunakan action <strong>Change Password</strong> pada dropdown user.
                                </div>
                            </div>
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

{{-- Password Modal --}}
<div class="modal fade" id="passwordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="passwordForm">
            @csrf
            <input type="hidden" id="password_user_id">

            <div class="modal-content custom-modal">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title">Change Password</h5>
                        <p class="text-muted mb-0">Update login password for selected user.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-4">
                    <div id="passwordAlert" class="alert alert-danger d-none mb-3"></div>

                    <div class="soft-panel mb-3">
                        <div class="soft-panel-title" id="passwordUserName">-</div>
                        <div class="soft-panel-subtitle" id="passwordUserEmail">-</div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="password_password" class="form-label">
                                New Password <span class="text-danger">*</span>
                            </label>
                            <input type="password" id="password_password" class="form-control" autocomplete="new-password">
                            <div class="invalid-feedback" id="error_password_password"></div>
                        </div>

                        <div class="col-12">
                            <label for="password_password_confirmation" class="form-label">
                                Confirm New Password <span class="text-danger">*</span>
                            </label>
                            <input type="password" id="password_password_confirmation" class="form-control" autocomplete="new-password">
                            <div class="invalid-feedback" id="error_password_password_confirmation"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary btn-modern" id="passwordSubmitBtn">
                        <span class="default-text">Update Password</span>
                        <span class="loading-text d-none">Updating...</span>
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
                        <h5 class="modal-title">Delete User</h5>
                        <p class="text-muted mb-0">Konfirmasi sebelum menghapus user.</p>
                    </div>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pt-4">
                <div class="delete-confirm-message">
                    <div class="delete-confirm-label">User yang akan dihapus</div>
                    <div class="delete-confirm-name" id="deleteUserName">-</div>
                </div>

                <div class="delete-confirm-warning mt-3">
                    User yang sudah dihapus tidak bisa dikembalikan.
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
    .users-table-card,
    .users-table-card .content-card-body {
        min-width: 0;
        max-width: 100%;
        overflow: hidden;
    }

    .user-table-responsive {
        display: block;
        width: 100%;
        max-width: 100%;
        overflow-x: auto !important;
        overflow-y: visible;
        -webkit-overflow-scrolling: touch;
        overscroll-behavior-x: contain;
    }

    .user-admin-table {
        width: 100%;
        min-width: 980px;
        max-width: none;
    }

    .user-admin-table th {
        font-size: .74rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #64748b;
        background: #f8fafc;
    }

    .user-admin-table td {
        vertical-align: middle;
    }

    .user-avatar {
        width: 42px;
        height: 42px;
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(91, 62, 142, .11);
        color: #5B3E8E;
        font-weight: 800;
        flex: 0 0 auto;
        border: 1px solid rgba(91, 62, 142, .14);
    }

    .soft-panel {
        border: 1px solid rgba(91, 62, 142, .12);
        background: rgba(91, 62, 142, .06);
        border-radius: 18px;
        padding: 14px 16px;
    }

    .soft-panel-title {
        font-weight: 700;
        color: #1f2937;
    }

    .soft-panel-subtitle {
        font-size: .875rem;
        color: #64748b;
        margin-top: 2px;
    }

    .custom-modal {
        border: 0;
        border-radius: 24px;
        box-shadow: 0 24px 80px rgba(15, 23, 42, .18);
        overflow: hidden;
    }

    .custom-modal .modal-header,
    .custom-modal .modal-body,
    .custom-modal .modal-footer {
        padding-left: 24px;
        padding-right: 24px;
    }

    .custom-modal .modal-header {
        padding-top: 24px;
    }

    .custom-modal .modal-footer {
        padding-bottom: 24px;
    }

    .delete-confirm-heading {
        display: flex;
        align-items: flex-start;
        gap: 14px;
    }

    .delete-confirm-icon {
        width: 46px;
        height: 46px;
        border-radius: 18px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #fee2e2;
        color: #dc2626;
        font-size: 1.35rem;
        flex: 0 0 auto;
    }

    .delete-confirm-message {
        border: 1px solid #fee2e2;
        background: #fff7f7;
        border-radius: 18px;
        padding: 16px;
    }

    .delete-confirm-label {
        font-size: .78rem;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #991b1b;
        font-weight: 700;
        margin-bottom: 4px;
    }

    .delete-confirm-name {
        font-size: 1.05rem;
        font-weight: 800;
        color: #111827;
    }

    .delete-confirm-warning {
        color: #991b1b;
        font-size: .9rem;
        font-weight: 600;
    }

    .empty-state-box {
        text-align: center;
        padding: 54px 24px;
        border: 1px dashed rgba(91, 62, 142, .25);
        border-radius: 24px;
        background: rgba(91, 62, 142, .035);
    }

    .empty-state-icon {
        width: 64px;
        height: 64px;
        border-radius: 22px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(91, 62, 142, .1);
        color: #5B3E8E;
        font-size: 1.8rem;
        margin-bottom: 16px;
    }

    .empty-state-title {
        font-weight: 800;
        color: #111827;
    }

    .empty-state-text {
        color: #64748b;
    }
</style>
@endpush

@push('scripts')
<script>
const routes = {
    store: @json(route('settings.users.store')),
    show: @json(route('settings.users.show', ['user' => '__ID__'])),
    update: @json(route('settings.users.update', ['user' => '__ID__'])),
    password: @json(route('settings.users.password.update', ['user' => '__ID__'])),
    destroy: @json(route('settings.users.destroy', ['user' => '__ID__'])),
};

let userModal = null;
let passwordModal = null;
let deleteModal = null;
let deleteUserId = null;

document.addEventListener('DOMContentLoaded', function () {
    userModal = new bootstrap.Modal(document.getElementById('userModal'));
    passwordModal = new bootstrap.Modal(document.getElementById('passwordModal'));
    deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));

    document.getElementById('userForm').addEventListener('submit', submitUserForm);
    document.getElementById('passwordForm').addEventListener('submit', submitPasswordForm);
    document.getElementById('confirmDeleteBtn').addEventListener('click', deleteUser);
});

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function buildRoute(template, id) {
    return template.replace('__ID__', id);
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
    const toast = new bootstrap.Toast(toastEl, { delay: 2600 });

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

function resetFormErrors(formSelector, alertId) {
    document.querySelectorAll(`${formSelector} .is-invalid`).forEach(el => el.classList.remove('is-invalid'));
    document.querySelectorAll(`${formSelector} .invalid-feedback`).forEach(el => el.innerText = '');

    const alert = document.getElementById(alertId);
    alert.classList.add('d-none');
    alert.innerText = '';
}

function fillValidationErrors(prefix, errors, alertId) {
    const alert = document.getElementById(alertId);
    let firstMessage = 'Please check the form again.';

    Object.keys(errors || {}).forEach(function (field) {
        const messages = errors[field];
        const message = Array.isArray(messages) ? messages[0] : messages;

        firstMessage = message || firstMessage;

        const input = document.getElementById(`${prefix}_${field}`);
        const feedback = document.getElementById(`error_${prefix}_${field}`);

        if (input) input.classList.add('is-invalid');
        if (feedback) feedback.innerText = message;
    });

    alert.innerText = firstMessage;
    alert.classList.remove('d-none');
}

function syncUserTypeByRole() {
    const role = document.getElementById('user_role').value;
    const userType = document.getElementById('user_user_type');

    if (role === 'student') {
        userType.value = 'student';
        return;
    }

    if (role === 'instructor') {
        userType.value = 'instructor';
        return;
    }

    userType.value = 'staff';
}

function togglePasswordCreateFields(isCreateMode) {
    document.querySelectorAll('.password-create-field').forEach(el => {
        el.classList.toggle('d-none', !isCreateMode);
    });

    document.querySelectorAll('.edit-password-info').forEach(el => {
        el.classList.toggle('d-none', isCreateMode);
    });

    document.getElementById('passwordSectionCaption').innerText = isCreateMode
        ? 'Password wajib diisi saat membuat user baru.'
        : 'Password tidak diubah dari form edit.';
}

function openCreateModal() {
    resetFormErrors('#userForm', 'formAlert');

    document.getElementById('userModalTitle').innerText = 'Add User';
    document.getElementById('user_id').value = '';
    document.getElementById('user_name').value = '';
    document.getElementById('user_email').value = '';
    document.getElementById('user_role').value = 'academic';
    document.getElementById('user_user_type').value = 'staff';
    document.getElementById('user_password').value = '';
    document.getElementById('user_password_confirmation').value = '';

    togglePasswordCreateFields(true);
    syncUserTypeByRole();

    userModal.show();
}

async function editUser(id) {
    resetFormErrors('#userForm', 'formAlert');

    try {
        const response = await fetch(buildRoute(routes.show, id), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        const result = await response.json().catch(() => ({}));

        if (!response.ok) {
            showToast(result.message || 'Failed to load user.', 'error');
            return;
        }

        const user = result.data?.user || result.user || result.data;

        document.getElementById('userModalTitle').innerText = 'Edit User';
        document.getElementById('user_id').value = user.id;
        document.getElementById('user_name').value = user.name || '';
        document.getElementById('user_email').value = user.email || '';
        document.getElementById('user_role').value = user.role || 'academic';
        document.getElementById('user_user_type').value = user.user_type || 'staff';
        document.getElementById('user_password').value = '';
        document.getElementById('user_password_confirmation').value = '';

        togglePasswordCreateFields(false);
        syncUserTypeByRole();

        userModal.show();
    } catch (error) {
        showToast('Failed to load user.', 'error');
    }
}

async function submitUserForm(event) {
    event.preventDefault();
    resetFormErrors('#userForm', 'formAlert');

    const submitBtn = document.getElementById('submitBtn');
    setButtonLoading(submitBtn, true);

    const id = document.getElementById('user_id').value;
    const isEdit = Boolean(id);

    const formData = new FormData();
    formData.append('_token', csrfToken());
    formData.append('name', document.getElementById('user_name').value);
    formData.append('email', document.getElementById('user_email').value);
    formData.append('role', document.getElementById('user_role').value);
    formData.append('user_type', document.getElementById('user_user_type').value);

    let url = routes.store;

    if (isEdit) {
        url = buildRoute(routes.update, id);
        formData.append('_method', 'PUT');
    } else {
        formData.append('password', document.getElementById('user_password').value);
        formData.append('password_confirmation', document.getElementById('user_password_confirmation').value);
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
                fillValidationErrors('user', result.errors || {}, 'formAlert');
            } else {
                document.getElementById('formAlert').innerText = result.message || 'Failed to save user.';
                document.getElementById('formAlert').classList.remove('d-none');
            }

            setButtonLoading(submitBtn, false);
            return;
        }

        showToast(result.message || 'User saved successfully.');
        userModal.hide();

        setTimeout(() => window.location.reload(), 700);
    } catch (error) {
        document.getElementById('formAlert').innerText = 'Failed to connect to server.';
        document.getElementById('formAlert').classList.remove('d-none');
        setButtonLoading(submitBtn, false);
    }
}

function openPasswordModal(userId, userName, userEmail) {
    resetFormErrors('#passwordForm', 'passwordAlert');

    document.getElementById('password_user_id').value = userId;
    document.getElementById('passwordUserName').innerText = userName || '-';
    document.getElementById('passwordUserEmail').innerText = userEmail || '-';
    document.getElementById('password_password').value = '';
    document.getElementById('password_password_confirmation').value = '';

    passwordModal.show();
}

async function submitPasswordForm(event) {
    event.preventDefault();
    resetFormErrors('#passwordForm', 'passwordAlert');

    const submitBtn = document.getElementById('passwordSubmitBtn');
    setButtonLoading(submitBtn, true);

    const id = document.getElementById('password_user_id').value;

    const formData = new FormData();
    formData.append('_token', csrfToken());
    formData.append('_method', 'PATCH');
    formData.append('password', document.getElementById('password_password').value);
    formData.append('password_confirmation', document.getElementById('password_password_confirmation').value);

    try {
        const response = await fetch(buildRoute(routes.password, id), {
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
                fillValidationErrors('password', result.errors || {}, 'passwordAlert');
            } else {
                document.getElementById('passwordAlert').innerText = result.message || 'Failed to update password.';
                document.getElementById('passwordAlert').classList.remove('d-none');
            }

            setButtonLoading(submitBtn, false);
            return;
        }

        showToast(result.message || 'Password updated successfully.');
        passwordModal.hide();

        setButtonLoading(submitBtn, false);
    } catch (error) {
        document.getElementById('passwordAlert').innerText = 'Failed to connect to server.';
        document.getElementById('passwordAlert').classList.remove('d-none');
        setButtonLoading(submitBtn, false);
    }
}

function openDeleteModal(userId, userName, isCurrentUser = false) {
    if (isCurrentUser) {
        showToast('Akun sendiri tidak boleh dihapus.', 'error');
        return;
    }

    deleteUserId = userId;
    document.getElementById('deleteUserName').innerText = userName || '-';

    deleteModal.show();
}

async function deleteUser() {
    if (!deleteUserId) {
        showToast('User tidak ditemukan.', 'error');
        return;
    }

    const button = document.getElementById('confirmDeleteBtn');
    setButtonLoading(button, true);

    const formData = new FormData();
    formData.append('_token', csrfToken());
    formData.append('_method', 'DELETE');

    try {
        const response = await fetch(buildRoute(routes.destroy, deleteUserId), {
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
            showToast(result.message || 'Failed to delete user.', 'error');
            setButtonLoading(button, false);
            return;
        }

        showToast(result.message || 'User deleted successfully.');
        deleteModal.hide();

        setTimeout(() => window.location.reload(), 700);
    } catch (error) {
        showToast('Failed to connect to server.', 'error');
        setButtonLoading(button, false);
    }
}
</script>
@endpush