@extends('layouts.app-dashboard')

@section('title', 'Employee Master')

@section('content')
@php
    $activeBadgeClass = fn (bool $isActive) => $isActive
        ? 'bg-success-subtle text-success-emphasis border border-success-subtle'
        : 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle';

    $formatTime = fn ($time) => filled($time)
        ? substr((string) $time, 0, 5)
        : '-';
@endphp

<div class="container-fluid px-4 py-4">
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Human Resources</div>
                <h1 class="page-title mb-2">Employee Master</h1>
                <p class="page-subtitle mb-0">
                    Kelola identitas employee, struktur kerja, default working-hours template, dan status aktif.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-light btn-modern" onclick="openEmployeeCreateModal()">
                    <i class="bi bi-person-plus-fill me-2"></i>Add Employee
                </button>
            </div>
        </div>
    </div>

    <div
        id="employeeToastContainer"
        class="toast-container position-fixed top-0 end-0 p-3"
        style="z-index: 9999;"
    ></div>

    <div class="row g-3 mb-4">
        @foreach ([
            [
                'label' => 'Total Employees',
                'value' => $summary['total'],
                'icon' => 'bi bi-people-fill',
                'description' => 'Seluruh employee yang tersimpan pada master HR.',
            ],
            [
                'label' => 'Active',
                'value' => $summary['active'],
                'icon' => 'bi bi-person-check-fill',
                'description' => 'Employee aktif yang dapat digunakan pada proses attendance.',
            ],
            [
                'label' => 'Inactive',
                'value' => $summary['inactive'],
                'icon' => 'bi bi-person-dash-fill',
                'description' => 'Employee nonaktif yang tidak digunakan untuk data baru.',
            ],
            [
                'label' => 'Without Template',
                'value' => $summary['without_template'],
                'icon' => 'bi bi-clock-history',
                'description' => 'Employee yang belum memiliki default working-hours template.',
            ],
        ] as $stat)
            <div class="col-xl col-md-6">
                <div class="stat-card h-100">
                    <div class="stat-card-top">
                        <div class="stat-icon-wrap">
                            <i class="{{ $stat['icon'] }}"></i>
                        </div>

                        <div>
                            <div class="stat-title">{{ $stat['label'] }}</div>
                            <div class="stat-value">{{ number_format($stat['value']) }}</div>
                        </div>
                    </div>

                    <div class="stat-description">{{ $stat['description'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="content-card employee-table-card">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Employee List</h5>
                <p class="content-card-subtitle mb-0">
                    Review employee profile, team, work schedule, source, dan status master.
                </p>
            </div>

            <form
                method="GET"
                action="{{ route('hr.employees.index') }}"
                class="master-filter-form d-flex align-items-center gap-2 flex-wrap"
            >
                <select
                    name="is_active"
                    class="form-select form-select-sm"
                    style="width: 145px;"
                    onchange="this.form.submit()"
                >
                    <option value="">All Status</option>
                    <option value="1" {{ ($filters['is_active'] ?? '') === '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ ($filters['is_active'] ?? '') === '0' ? 'selected' : '' }}>Inactive</option>
                </select>

                <select
                    name="employee_type"
                    class="form-select form-select-sm"
                    style="width: 165px;"
                    onchange="this.form.submit()"
                >
                    <option value="">All Employee Types</option>
                    @foreach ($employeeTypeOptions as $employeeType)
                        <option
                            value="{{ $employeeType }}"
                            {{ ($filters['employee_type'] ?? '') === $employeeType ? 'selected' : '' }}
                        >
                            {{ $employeeType }}
                        </option>
                    @endforeach
                </select>

                <select
                    name="work_team"
                    class="form-select form-select-sm"
                    style="width: 155px;"
                    onchange="this.form.submit()"
                >
                    <option value="">All Teams</option>
                    @foreach ($workTeamOptions as $workTeam)
                        <option
                            value="{{ $workTeam }}"
                            {{ ($filters['work_team'] ?? '') === $workTeam ? 'selected' : '' }}
                        >
                            {{ $workTeam }}
                        </option>
                    @endforeach
                </select>

                <select
                    name="default_working_hour_template_id"
                    class="form-select form-select-sm"
                    style="width: 210px;"
                    onchange="this.form.submit()"
                >
                    <option value="">All Working Templates</option>
                    @foreach ($workingHourTemplates as $template)
                        <option
                            value="{{ $template->id }}"
                            {{ (int) ($filters['default_working_hour_template_id'] ?? 0) === $template->id ? 'selected' : '' }}
                        >
                            {{ $template->name }}
                        </option>
                    @endforeach
                </select>

                <div class="input-group input-group-sm master-search-group">
                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        value="{{ $filters['search'] ?? '' }}"
                        placeholder="Search employee..."
                    >
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>

                @if (collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty())
                    <a href="{{ route('hr.employees.index') }}" class="btn btn-sm btn-outline-secondary">
                        Reset
                    </a>
                @endif
            </form>
        </div>

        <div class="content-card-body">
            @if ($employees->count())
                <div class="master-table-responsive">
                    <table class="table table-hover align-middle admin-table master-admin-table mb-0">
                        <thead>
                            <tr>
                                <th class="text-nowrap" style="width: 65px;">No</th>
                                <th class="text-nowrap col-employee">Employee</th>
                                <th class="text-nowrap">Employment</th>
                                <th class="text-nowrap">Team / Duty</th>
                                <th class="text-nowrap col-template">Default Schedule</th>
                                <th class="text-nowrap">Working Days</th>
                                <th class="text-nowrap">Source</th>
                                <th class="text-nowrap">Last Seen</th>
                                <th class="text-nowrap">Status</th>
                                <th class="text-end text-nowrap" style="width: 130px;">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($employees as $employee)
                                @php
                                    $resolvedWorkingDays = $employee->resolvedWorkingDays();

                                    $employeePayload = [
                                        'id' => $employee->id,
                                        'employee_number' => $employee->employee_number,
                                        'name' => $employee->name,
                                        'email' => $employee->email,
                                        'phone' => $employee->phone,
                                        'employee_type' => $employee->employee_type,
                                        'work_team' => $employee->work_team,
                                        'duty_type' => $employee->duty_type,
                                        'default_working_hour_template_id' => $employee->default_working_hour_template_id,
                                        'default_start_time' => $employee->default_start_time,
                                        'default_end_time' => $employee->default_end_time,
                                        'working_days_override' => $employee->working_days_override ?: [],
                                        'source' => $employee->source,
                                        'is_active' => (bool) $employee->is_active,
                                    ];

                                    $encodedEmployeePayload = base64_encode(json_encode(
                                        $employeePayload,
                                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                                    ));
                                @endphp

                                <tr>
                                    <td class="text-muted">
                                        {{ ($employees->currentPage() - 1) * $employees->perPage() + $loop->iteration }}
                                    </td>

                                    <td>
                                        <div class="fw-bold text-dark">{{ $employee->name }}</div>
                                        <div class="small text-muted">{{ $employee->employee_number }}</div>

                                        @if ($employee->email || $employee->phone)
                                            <div class="small text-muted mt-1">
                                                {{ $employee->email ?: $employee->phone }}
                                            </div>
                                        @endif
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark">{{ $employee->employee_type ?: '-' }}</div>
                                        <div class="small text-muted">{{ $employee->phone ?: 'No phone' }}</div>
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark">{{ $employee->work_team ?: '-' }}</div>
                                        <div class="small text-muted">{{ $employee->duty_type ?: 'No duty type' }}</div>
                                    </td>

                                    <td>
                                        @if ($employee->defaultWorkingHourTemplate)
                                            <div class="fw-semibold text-dark">
                                                {{ $employee->defaultWorkingHourTemplate->name }}
                                            </div>
                                            <div class="small text-muted">
                                                {{ $formatTime($employee->defaultWorkingHourTemplate->start_time) }}
                                                –
                                                {{ $formatTime($employee->defaultWorkingHourTemplate->end_time) }}
                                            </div>
                                        @elseif ($employee->default_start_time || $employee->default_end_time)
                                            <div class="fw-semibold text-dark">Employee Override</div>
                                            <div class="small text-muted">
                                                {{ $formatTime($employee->default_start_time) }}
                                                –
                                                {{ $formatTime($employee->default_end_time) }}
                                            </div>
                                        @else
                                            <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                                Not Configured
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        <div class="working-day-badges">
                                            @foreach ($resolvedWorkingDays as $day)
                                                <span class="badge rounded-pill bg-light text-dark border">
                                                    {{ substr($workingDayOptions[$day] ?? (string) $day, 0, 3) }}
                                                </span>
                                            @endforeach
                                        </div>

                                        @if (! empty($employee->working_days_override))
                                            <div class="small text-primary mt-1">Employee override</div>
                                        @endif
                                    </td>

                                    <td>
                                        <span class="badge rounded-pill bg-light text-dark border">
                                            {{ $employee->source ?: 'manual' }}
                                        </span>
                                    </td>

                                    <td class="text-nowrap">
                                        <div class="fw-semibold text-dark">
                                            {{ $employee->last_seen_at?->format('d M Y') ?? '-' }}
                                        </div>
                                        <div class="small text-muted">
                                            First: {{ $employee->first_seen_at?->format('d M Y') ?? '-' }}
                                        </div>
                                    </td>

                                    <td class="text-nowrap">
                                        <span class="badge rounded-pill {{ $activeBadgeClass((bool) $employee->is_active) }}">
                                            {{ $employee->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>

                                    <td class="text-end text-nowrap">
                                        <div class="dropdown">
                                            <button
                                                class="btn btn-sm btn-outline-secondary dropdown-toggle px-3"
                                                type="button"
                                                data-bs-toggle="dropdown"
                                                data-bs-boundary="viewport"
                                            >
                                                Actions
                                            </button>

                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                <li>
                                                    <button
                                                        type="button"
                                                        class="dropdown-item employee-edit-button"
                                                        data-payload="{{ $encodedEmployeePayload }}"
                                                    >
                                                        <i class="bi bi-pencil-square me-2"></i>Edit Employee
                                                    </button>
                                                </li>

                                                <li><hr class="dropdown-divider"></li>

                                                <li>
                                                    <button
                                                        type="button"
                                                        class="dropdown-item text-danger employee-delete-button"
                                                        data-id="{{ $employee->id }}"
                                                        data-name="{{ $employee->name }}"
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

                @if ($employees->hasPages())
                    <div class="mt-3">
                        {{ $employees->links() }}
                    </div>
                @endif
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <h5 class="empty-state-title">No employees found</h5>
                    <p class="empty-state-text mb-0">
                        Belum ada employee yang sesuai dengan filter. Tambahkan employee baru atau reset filter.
                    </p>
                    <div class="mt-3">
                        <button type="button" class="btn btn-primary btn-modern" onclick="openEmployeeCreateModal()">
                            <i class="bi bi-person-plus-fill me-2"></i>Add Employee
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="modal fade" id="employeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <form id="employeeForm">
            @csrf
            <input type="hidden" id="employee_id">

            <div class="modal-content custom-modal">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title" id="employeeModalTitle">Add Employee</h5>
                        <p class="text-muted mb-0">
                            Lengkapi identitas, struktur kerja, dan default attendance schedule.
                        </p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body pt-4">
                    <div id="employeeFormAlert" class="alert alert-danger d-none mb-3"></div>

                    <div class="content-card mb-3">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Identity & Contact</h5>
                                <p class="content-card-subtitle mb-0">
                                    Employee number menjadi identifier utama saat mencocokkan hasil attendance import.
                                </p>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <div class="row g-3">
                                <div class="col-12 col-md-4">
                                    <label for="employee_number" class="form-label">
                                        Employee Number <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" id="employee_number" class="form-control">
                                    <div class="invalid-feedback" id="error_employee_number"></div>
                                </div>

                                <div class="col-12 col-md-8">
                                    <label for="employee_name" class="form-label">
                                        Employee Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" id="employee_name" class="form-control">
                                    <div class="invalid-feedback" id="error_name"></div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="employee_email" class="form-label">Email</label>
                                    <input type="email" id="employee_email" class="form-control">
                                    <div class="invalid-feedback" id="error_email"></div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="employee_phone" class="form-label">Phone</label>
                                    <input type="text" id="employee_phone" class="form-control">
                                    <div class="invalid-feedback" id="error_phone"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="content-card mb-3">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Employment Information</h5>
                                <p class="content-card-subtitle mb-0">
                                    Catat jenis employee, team kerja, dan duty type untuk kebutuhan HR reporting.
                                </p>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <div class="row g-3">
                                <div class="col-12 col-md-4">
                                    <label for="employee_type" class="form-label">Employee Type</label>
                                    <input
                                        type="text"
                                        id="employee_type"
                                        class="form-control"
                                        list="employeeTypeList"
                                        placeholder="e.g. Full Time"
                                    >
                                    <datalist id="employeeTypeList">
                                        @foreach ($employeeTypeOptions as $option)
                                            <option value="{{ $option }}"></option>
                                        @endforeach
                                    </datalist>
                                    <div class="invalid-feedback" id="error_employee_type"></div>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label for="work_team" class="form-label">Work Team</label>
                                    <input
                                        type="text"
                                        id="work_team"
                                        class="form-control"
                                        list="workTeamList"
                                        placeholder="e.g. Education"
                                    >
                                    <datalist id="workTeamList">
                                        @foreach ($workTeamOptions as $option)
                                            <option value="{{ $option }}"></option>
                                        @endforeach
                                    </datalist>
                                    <div class="invalid-feedback" id="error_work_team"></div>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label for="duty_type" class="form-label">Duty Type</label>
                                    <input
                                        type="text"
                                        id="duty_type"
                                        class="form-control"
                                        list="dutyTypeList"
                                        placeholder="e.g. Regular"
                                    >
                                    <datalist id="dutyTypeList">
                                        @foreach ($dutyTypeOptions as $option)
                                            <option value="{{ $option }}"></option>
                                        @endforeach
                                    </datalist>
                                    <div class="invalid-feedback" id="error_duty_type"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="content-card mb-3">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Default Attendance Schedule</h5>
                                <p class="content-card-subtitle mb-0">
                                    Gunakan working-hours template sebagai jadwal utama. Jam override hanya diisi jika employee memiliki pengecualian.
                                </p>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <div class="row g-3">
                                <div class="col-12 col-lg-6">
                                    <label for="default_working_hour_template_id" class="form-label">
                                        Default Working-Hours Template
                                    </label>
                                    <select id="default_working_hour_template_id" class="form-select">
                                        <option value="">No Default Template</option>
                                        @foreach ($workingHourTemplates as $template)
                                            <option value="{{ $template->id }}">
                                                {{ $template->name }}
                                                @if ($template->start_time && $template->end_time)
                                                    · {{ substr($template->start_time, 0, 5) }}–{{ substr($template->end_time, 0, 5) }}
                                                @endif
                                                {{ $template->is_active ? '' : ' (Inactive)' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback" id="error_default_working_hour_template_id"></div>
                                </div>

                                <div class="col-6 col-lg-3">
                                    <label for="default_start_time" class="form-label">Start Override</label>
                                    <input type="time" step="1" id="default_start_time" class="form-control">
                                    <div class="invalid-feedback" id="error_default_start_time"></div>
                                </div>

                                <div class="col-6 col-lg-3">
                                    <label for="default_end_time" class="form-label">End Override</label>
                                    <input type="time" step="1" id="default_end_time" class="form-control">
                                    <div class="invalid-feedback" id="error_default_end_time"></div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label d-block">Working Days Override</label>
                                    <div class="working-day-selector">
                                        @foreach ($workingDayOptions as $dayNumber => $dayLabel)
                                            <label class="working-day-option">
                                                <input
                                                    type="checkbox"
                                                    class="form-check-input employee-working-day"
                                                    value="{{ $dayNumber }}"
                                                >
                                                <span>{{ substr($dayLabel, 0, 3) }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    <div class="form-text">
                                        Biarkan kosong untuk mengikuti working days dari default template.
                                    </div>
                                    <div class="invalid-feedback d-block" id="error_working_days_override"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="content-card">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Source & Status</h5>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <div class="row g-3 align-items-end">
                                <div class="col-12 col-md-6">
                                    <label for="employee_source" class="form-label">Source</label>
                                    <input type="text" id="employee_source" class="form-control" placeholder="manual">
                                    <div class="invalid-feedback" id="error_source"></div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="form-check form-switch mb-2">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            id="employee_is_active"
                                            checked
                                        >
                                        <label class="form-check-label fw-semibold" for="employee_is_active">
                                            Active Employee
                                        </label>
                                    </div>
                                    <div class="form-text">
                                        Employee aktif dapat digunakan pada proses attendance berikutnya.
                                    </div>
                                    <div class="invalid-feedback d-block" id="error_is_active"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-primary btn-modern" id="submitEmployeeBtn">
                        <span class="default-text">
                            <i class="bi bi-save me-2"></i>Save Employee
                        </span>
                        <span class="loading-text d-none">
                            <span class="spinner-border spinner-border-sm me-2"></span>
                            Saving...
                        </span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="deleteEmployeeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal delete-confirm-modal">
            <div class="modal-header border-0 pb-0">
                <div class="delete-confirm-heading">
                    <div class="delete-confirm-icon">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>

                    <div>
                        <h5 class="modal-title">Delete Employee</h5>
                        <p class="text-muted mb-0">Konfirmasi sebelum menghapus employee.</p>
                    </div>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body pt-4">
                <div class="delete-confirm-message">
                    <div class="delete-confirm-label">Employee yang akan dihapus</div>
                    <div class="delete-confirm-name" id="deleteEmployeeName">-</div>
                </div>

                <div class="delete-confirm-warning mt-3">
                    Employee akan dihapus menggunakan soft delete. Riwayat attendance tetap dipertahankan.
                </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                    Cancel
                </button>

                <button type="button" class="btn btn-danger btn-modern" id="confirmDeleteEmployeeBtn">
                    <span class="default-text">
                        <i class="bi bi-trash me-2"></i>Delete
                    </span>
                    <span class="loading-text d-none">
                        <span class="spinner-border spinner-border-sm me-2"></span>
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
    .employee-table-card,
    .employee-table-card .content-card-body {
        min-width: 0;
        max-width: 100%;
        overflow: hidden;
    }

    .master-filter-form {
        justify-content: flex-end;
    }

    .master-search-group {
        width: 245px;
    }

    .master-table-responsive {
        display: block;
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        overflow-y: visible;
        -webkit-overflow-scrolling: touch;
        padding-bottom: 90px;
        margin-bottom: -90px;
    }

    .master-admin-table {
        min-width: 1320px;
    }

    .master-admin-table .col-employee {
        min-width: 220px;
    }

    .master-admin-table .col-template {
        min-width: 220px;
    }

    .admin-table thead th {
        background: #f8fafc;
        color: #475569;
        font-size: .78rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .04em;
        border-bottom: 1px solid #e2e8f0;
    }

    .admin-table tbody td {
        border-bottom: 1px solid #eef2f7;
    }

    .working-day-badges,
    .working-day-selector {
        display: flex;
        align-items: center;
        gap: .35rem;
        flex-wrap: wrap;
    }

    .working-day-option {
        position: relative;
        cursor: pointer;
        margin: 0;
    }

    .working-day-option input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .working-day-option span {
        min-width: 48px;
        min-height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: .45rem .7rem;
        border: 1px solid #d8dee8;
        border-radius: .75rem;
        background: #fff;
        color: #64748b;
        font-weight: 700;
        font-size: .82rem;
        transition: all .16s ease;
    }

    .working-day-option input:checked + span {
        color: #5B3E8E;
        background: #f1ebf8;
        border-color: #bdaed5;
        box-shadow: 0 0 0 3px rgba(91, 62, 142, .08);
    }

    .delete-confirm-heading {
        display: flex;
        align-items: center;
        gap: .9rem;
    }

    .delete-confirm-icon {
        width: 44px;
        height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 1rem;
        background: #fee2e2;
        color: #dc2626;
        font-size: 1.25rem;
    }

    .delete-confirm-message {
        border: 1px solid #fecaca;
        background: #fff1f2;
        border-radius: 1rem;
        padding: 1rem;
    }

    .delete-confirm-label {
        font-size: .78rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #9f1239;
        margin-bottom: .25rem;
    }

    .delete-confirm-name {
        font-size: 1rem;
        font-weight: 800;
        color: #111827;
    }

    .delete-confirm-warning {
        border-radius: 1rem;
        background: #fff7ed;
        border: 1px solid #fed7aa;
        color: #9a3412;
        padding: .9rem 1rem;
        font-weight: 700;
        font-size: .9rem;
    }

    @media (max-width: 768px) {
        .container-fluid.px-4 {
            padding-left: 1rem !important;
            padding-right: 1rem !important;
        }

        .master-filter-form,
        .master-search-group {
            width: 100% !important;
        }

        .master-filter-form .form-select,
        .master-filter-form .btn {
            flex: 1 1 145px;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    let employeeModal = null;
    let deleteEmployeeModal = null;
    let employeeEditMode = false;
    let deleteEmployeeId = null;

    const employeeRoutes = {
        store: @js(route('hr.employees.store')),
        update: @js(route('hr.employees.update', ['employee' => '__ID__'])),
        destroy: @js(route('hr.employees.destroy', ['employee' => '__ID__'])),
    };

    const employeeCsrfToken = @js(csrf_token());
    const employeeFields = {};

    document.addEventListener('DOMContentLoaded', () => {
        employeeModal = bootstrap.Modal.getOrCreateInstance(
            document.getElementById('employeeModal')
        );

        deleteEmployeeModal = bootstrap.Modal.getOrCreateInstance(
            document.getElementById('deleteEmployeeModal')
        );

        Object.assign(employeeFields, {
            id: document.getElementById('employee_id'),
            employee_number: document.getElementById('employee_number'),
            name: document.getElementById('employee_name'),
            email: document.getElementById('employee_email'),
            phone: document.getElementById('employee_phone'),
            employee_type: document.getElementById('employee_type'),
            work_team: document.getElementById('work_team'),
            duty_type: document.getElementById('duty_type'),
            default_working_hour_template_id: document.getElementById('default_working_hour_template_id'),
            default_start_time: document.getElementById('default_start_time'),
            default_end_time: document.getElementById('default_end_time'),
            source: document.getElementById('employee_source'),
            is_active: document.getElementById('employee_is_active'),
        });

        document.getElementById('employeeForm')
            .addEventListener('submit', submitEmployeeForm);

        document.getElementById('confirmDeleteEmployeeBtn')
            .addEventListener('click', deleteEmployee);

        document.querySelectorAll('.employee-edit-button').forEach(button => {
            button.addEventListener('click', () => {
                const payload = decodeEmployeePayload(button.dataset.payload);

                if (payload) {
                    openEmployeeEditModal(payload);
                }
            });
        });

        document.querySelectorAll('.employee-delete-button').forEach(button => {
            button.addEventListener('click', () => {
                openEmployeeDeleteModal(
                    button.dataset.id,
                    button.dataset.name
                );
            });
        });
    });

    window.openEmployeeCreateModal = function () {
        employeeEditMode = false;
        resetEmployeeForm();

        document.getElementById('employeeModalTitle').textContent = 'Add Employee';
        employeeFields.source.value = 'manual';
        employeeFields.is_active.checked = true;

        employeeModal.show();
    };

    function openEmployeeEditModal(payload) {
        employeeEditMode = true;
        resetEmployeeForm();

        document.getElementById('employeeModalTitle').textContent = 'Edit Employee';

        employeeFields.id.value = payload.id ?? '';
        employeeFields.employee_number.value = payload.employee_number ?? '';
        employeeFields.name.value = payload.name ?? '';
        employeeFields.email.value = payload.email ?? '';
        employeeFields.phone.value = payload.phone ?? '';
        employeeFields.employee_type.value = payload.employee_type ?? '';
        employeeFields.work_team.value = payload.work_team ?? '';
        employeeFields.duty_type.value = payload.duty_type ?? '';
        employeeFields.default_working_hour_template_id.value =
            payload.default_working_hour_template_id ?? '';
        employeeFields.default_start_time.value = normalizeEmployeeTime(payload.default_start_time);
        employeeFields.default_end_time.value = normalizeEmployeeTime(payload.default_end_time);
        employeeFields.source.value = payload.source ?? 'manual';
        employeeFields.is_active.checked = Boolean(payload.is_active);

        const selectedDays = (payload.working_days_override ?? []).map(Number);

        document.querySelectorAll('.employee-working-day').forEach(checkbox => {
            checkbox.checked = selectedDays.includes(Number(checkbox.value));
        });

        employeeModal.show();
    }

    function openEmployeeDeleteModal(id, name) {
        deleteEmployeeId = id;
        document.getElementById('deleteEmployeeName').textContent = name || '-';
        setEmployeeButtonLoading(
            document.getElementById('confirmDeleteEmployeeBtn'),
            false
        );
        deleteEmployeeModal.show();
    }

    async function submitEmployeeForm(event) {
        event.preventDefault();
        clearEmployeeErrors();

        const button = document.getElementById('submitEmployeeBtn');
        setEmployeeButtonLoading(button, true);

        const workingDaysOverride = Array.from(
            document.querySelectorAll('.employee-working-day:checked')
        ).map(checkbox => Number(checkbox.value));

        const payload = {
            employee_number: employeeFields.employee_number.value.trim(),
            name: employeeFields.name.value.trim(),
            email: employeeFields.email.value.trim() || null,
            phone: employeeFields.phone.value.trim() || null,
            employee_type: employeeFields.employee_type.value.trim() || null,
            work_team: employeeFields.work_team.value.trim() || null,
            duty_type: employeeFields.duty_type.value.trim() || null,
            default_working_hour_template_id:
                employeeFields.default_working_hour_template_id.value || null,
            default_start_time: employeeFields.default_start_time.value || null,
            default_end_time: employeeFields.default_end_time.value || null,
            working_days_override: workingDaysOverride.length
                ? workingDaysOverride
                : null,
            source: employeeFields.source.value.trim() || 'manual',
            is_active: employeeFields.is_active.checked,
        };

        const employeeId = employeeFields.id.value;
        const url = employeeEditMode
            ? employeeRoutes.update.replace('__ID__', employeeId)
            : employeeRoutes.store;

        try {
            const response = await fetch(url, {
                method: employeeEditMode ? 'PUT' : 'POST',
                headers: employeeJsonHeaders(),
                body: JSON.stringify(payload),
            });

            const result = await parseEmployeeResponse(response);

            if (response.status === 422) {
                setEmployeeValidationErrors(result.errors || {});
                throw new Error('Validation failed.');
            }

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Employee gagal disimpan.');
            }

            employeeModal.hide();
            showEmployeeToast(result.message || 'Employee berhasil disimpan.', 'success');

            setTimeout(() => window.location.reload(), 650);
        } catch (error) {
            if (error.message !== 'Validation failed.') {
                showEmployeeFormAlert(error.message);
                showEmployeeToast(error.message || 'Terjadi kesalahan.', 'danger');
            }
        } finally {
            setEmployeeButtonLoading(button, false);
        }
    }

    async function deleteEmployee() {
        if (!deleteEmployeeId) {
            return;
        }

        const button = document.getElementById('confirmDeleteEmployeeBtn');
        setEmployeeButtonLoading(button, true);

        try {
            const response = await fetch(
                employeeRoutes.destroy.replace('__ID__', deleteEmployeeId),
                {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': employeeCsrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                }
            );

            const result = await parseEmployeeResponse(response);

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Employee gagal dihapus.');
            }

            deleteEmployeeModal.hide();
            showEmployeeToast(result.message || 'Employee berhasil dihapus.', 'success');

            setTimeout(() => window.location.reload(), 650);
        } catch (error) {
            showEmployeeToast(error.message || 'Employee gagal dihapus.', 'danger');
        } finally {
            setEmployeeButtonLoading(button, false);
        }
    }

    function resetEmployeeForm() {
        document.getElementById('employeeForm').reset();

        document.querySelectorAll('.employee-working-day').forEach(checkbox => {
            checkbox.checked = false;
        });

        employeeFields.id.value = '';
        employeeFields.is_active.checked = true;

        clearEmployeeErrors();
        hideEmployeeFormAlert();
    }

    function clearEmployeeErrors() {
        document.querySelectorAll('#employeeForm .is-invalid').forEach(field => {
            field.classList.remove('is-invalid');
        });

        document.querySelectorAll('#employeeForm .invalid-feedback').forEach(error => {
            error.textContent = '';
        });
    }

    function setEmployeeValidationErrors(errors) {
        Object.entries(errors).forEach(([key, messages]) => {
            const baseKey = key.split('.')[0];
            const field = employeeFields[baseKey];
            const errorElement = document.getElementById(`error_${baseKey}`);
            const message = Array.isArray(messages) ? messages[0] : messages;

            field?.classList.add('is-invalid');

            if (errorElement) {
                errorElement.textContent = message;
            }
        });
    }

    function showEmployeeFormAlert(message) {
        const alert = document.getElementById('employeeFormAlert');
        alert.textContent = message || 'Terjadi kesalahan.';
        alert.classList.remove('d-none');
    }

    function hideEmployeeFormAlert() {
        const alert = document.getElementById('employeeFormAlert');
        alert.textContent = '';
        alert.classList.add('d-none');
    }

    function decodeEmployeePayload(encodedPayload) {
        try {
            const binary = atob(encodedPayload);
            const bytes = Uint8Array.from(
                binary,
                character => character.charCodeAt(0)
            );

            return JSON.parse(new TextDecoder('utf-8').decode(bytes));
        } catch (error) {
            console.error('Employee payload could not be decoded.', error);
            return null;
        }
    }

    function normalizeEmployeeTime(value) {
        return value ? String(value).slice(0, 5) : '';
    }

    function employeeJsonHeaders() {
        return {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': employeeCsrfToken,
            'X-Requested-With': 'XMLHttpRequest',
        };
    }

    async function parseEmployeeResponse(response) {
        const contentType = response.headers.get('content-type') || '';

        if (!contentType.includes('application/json')) {
            throw new Error('Server mengembalikan response yang tidak valid.');
        }

        return response.json();
    }

    function setEmployeeButtonLoading(button, loading) {
        if (!button) {
            return;
        }

        button.disabled = loading;
        button.querySelector('.default-text')?.classList.toggle('d-none', loading);
        button.querySelector('.loading-text')?.classList.toggle('d-none', !loading);
    }

    function showEmployeeToast(message, type = 'success') {
        const container = document.getElementById('employeeToastContainer');
        const toastElement = document.createElement('div');

        const backgroundClass = {
            success: 'text-bg-success',
            danger: 'text-bg-danger',
            warning: 'text-bg-warning',
            info: 'text-bg-info',
        }[type] || 'text-bg-success';

        toastElement.className = `toast align-items-center ${backgroundClass} border-0`;
        toastElement.setAttribute('role', 'alert');

        const wrapper = document.createElement('div');
        wrapper.className = 'd-flex';

        const body = document.createElement('div');
        body.className = 'toast-body fw-semibold';
        body.textContent = message;

        const close = document.createElement('button');
        close.type = 'button';
        close.className = 'btn-close btn-close-white me-2 m-auto';
        close.setAttribute('data-bs-dismiss', 'toast');

        wrapper.append(body, close);
        toastElement.appendChild(wrapper);
        container.appendChild(toastElement);

        const toast = new bootstrap.Toast(toastElement, { delay: 3200 });
        toast.show();

        toastElement.addEventListener('hidden.bs.toast', () => toastElement.remove());
    }
</script>
@endpush
