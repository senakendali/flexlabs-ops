@extends('layouts.app-dashboard')

@section('title', 'Attendance Import Review')

@section('content')
@php
    $reviewBadgeClass = function ($status) {
        return match($status) {
            'valid' => 'bg-success-subtle text-success-emphasis border border-success-subtle',
            'needs_review' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
            'resolved' => 'bg-primary-subtle text-primary-emphasis border border-primary-subtle',
            'ignored' => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
            'error' => 'bg-danger-subtle text-danger-emphasis border border-danger-subtle',
            'duplicate' => 'bg-dark-subtle text-dark-emphasis border border-dark-subtle',
            default => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
        };
    };

    $attendanceBadgeClass = function ($status) {
        return match($status) {
            'present' => 'bg-success-subtle text-success-emphasis border border-success-subtle',
            'absent' => 'bg-danger-subtle text-danger-emphasis border border-danger-subtle',
            'missing' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
            'off_day' => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
            'holiday' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
            default => 'bg-dark-subtle text-dark-emphasis border border-dark-subtle',
        };
    };

    $statusDotClass = function ($status) {
        return match($status) {
            'reviewing' => 'bg-warning',
            'processing' => 'bg-info',
            'completed' => 'bg-success',
            'failed' => 'bg-danger',
            'cancelled' => 'bg-dark',
            default => 'bg-secondary',
        };
    };

    $canConfirm = $canEdit
        && (int) $attendanceImport->review_rows === 0
        && (int) $attendanceImport->error_rows === 0
        && (int) $attendanceImport->duplicate_rows === 0;

@endphp

<style>
    .attendance-review-page {
        --attendance-purple: #5B3E8E;
        --attendance-purple-dark: #493173;
        --attendance-yellow: #FFBE04;
        --attendance-border: #e8e3f0;
        --attendance-soft-purple: #f7f4fb;
    }

    /*
    |--------------------------------------------------------------------------
    | Purple Header Compatibility
    |--------------------------------------------------------------------------
    */
    .attendance-review-page .page-header-card .page-eyebrow,
    .attendance-review-page .page-header-card .page-title {
        color: #fff !important;
    }

    .attendance-review-page .page-header-card .page-subtitle,
    .attendance-review-page .page-header-card .header-meta,
    .attendance-review-page .page-header-card .header-meta span {
        color: rgba(255, 255, 255, .78) !important;
    }

    .attendance-review-page .header-status-badge {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .48rem .75rem;
        color: #fff;
        background: rgba(255, 255, 255, .14);
        border: 1px solid rgba(255, 255, 255, .26);
        backdrop-filter: blur(8px);
    }

    .attendance-review-page .header-status-dot {
        width: .55rem;
        height: .55rem;
        display: inline-block;
        border-radius: 50%;
        box-shadow: 0 0 0 3px rgba(255, 255, 255, .16);
    }

    .attendance-review-page .header-btn-back {
        color: var(--attendance-purple) !important;
        background: #fff !important;
        border-color: #fff !important;
    }

    .attendance-review-page .header-btn-back:hover {
        color: var(--attendance-purple-dark) !important;
        background: #f7f5fb !important;
        border-color: #f7f5fb !important;
    }

    .attendance-review-page .header-btn-cancel {
        color: #fff !important;
        background: rgba(255, 255, 255, .08) !important;
        border-color: rgba(255, 255, 255, .55) !important;
    }

    .attendance-review-page .header-btn-cancel:hover {
        color: #fff !important;
        background: rgba(255, 255, 255, .18) !important;
        border-color: #fff !important;
    }

    .attendance-review-page .header-btn-confirm {
        color: #2d2340 !important;
        background: var(--attendance-yellow) !important;
        border-color: var(--attendance-yellow) !important;
    }

    .attendance-review-page .header-btn-confirm:hover {
        color: #21172f !important;
        background: #f3b500 !important;
        border-color: #f3b500 !important;
    }

    .attendance-review-page .header-btn-confirm:disabled {
        color: rgba(255, 255, 255, .65) !important;
        background: rgba(255, 255, 255, .15) !important;
        border-color: rgba(255, 255, 255, .25) !important;
        opacity: 1;
    }

    /*
    |--------------------------------------------------------------------------
    | Filter Layout
    |--------------------------------------------------------------------------
    */
    .attendance-review-page .filter-actions {
        border-top: 1px solid var(--attendance-border);
        padding-top: 1rem;
        margin-top: 1rem;
    }

    /*
    |--------------------------------------------------------------------------
    | Grouped Attendance Table
    |--------------------------------------------------------------------------
    */
    .attendance-review-page .attendance-table-wrap {
        max-height: 72vh;
        overflow: auto;
        border: 1px solid var(--attendance-border);
        border-radius: .9rem;
        scrollbar-width: thin;
        scrollbar-color: #b8a9cf #f4f1f8;
    }

    .attendance-review-page .attendance-table-wrap::-webkit-scrollbar {
        width: 10px;
        height: 10px;
    }

    .attendance-review-page .attendance-table-wrap::-webkit-scrollbar-track {
        background: #f4f1f8;
        border-radius: 999px;
    }

    .attendance-review-page .attendance-table-wrap::-webkit-scrollbar-thumb {
        background: #b8a9cf;
        border: 2px solid #f4f1f8;
        border-radius: 999px;
    }

    .attendance-review-page .attendance-review-table {
        min-width: 1420px;
    }

    .attendance-review-page .attendance-review-table thead {
        position: sticky;
        top: 0;
        z-index: 5;
    }

    .attendance-review-page .employee-group-row td {
        padding: .85rem 1rem !important;
        background: var(--attendance-soft-purple) !important;
        border-top: 1px solid #dcd3e9;
        border-bottom: 1px solid #dcd3e9;
    }

    .attendance-review-page .employee-group-icon {
        width: 38px;
        height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 38px;
        color: var(--attendance-purple);
        background: #ece5f6;
        border-radius: 12px;
    }

    .attendance-review-page .needs-review-row {
        cursor: pointer;
        transition: background-color .16s ease, box-shadow .16s ease;
    }

    .attendance-review-page .needs-review-row:hover td {
        background-color: #fff7d8 !important;
    }

    .attendance-review-page .needs-review-row:focus {
        outline: 3px solid rgba(91, 62, 142, .22);
        outline-offset: -3px;
    }

    .attendance-review-page .review-click-hint {
        color: #8a6500;
        font-size: .75rem;
        font-weight: 600;
        margin-top: .35rem;
        white-space: nowrap;
    }

    /*
    |--------------------------------------------------------------------------
    | Confirmation Dialog
    |--------------------------------------------------------------------------
    */
    .attendance-review-page .confirmation-icon {
        width: 52px;
        height: 52px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 52px;
        border-radius: 16px;
        font-size: 1.35rem;
    }

    .attendance-review-page .confirmation-icon.is-primary {
        color: var(--attendance-purple);
        background: #eee8f7;
    }

    .attendance-review-page .confirmation-icon.is-danger {
        color: #b42318;
        background: #fee4e2;
    }
</style>

<div class="container-fluid px-4 py-4 attendance-review-page">
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                    <div class="page-eyebrow mb-0">Human Resources</div>

                    <span class="badge rounded-pill header-status-badge">
                        <span class="header-status-dot {{ $statusDotClass($attendanceImport->status) }}"></span>
                        {{ ucfirst($attendanceImport->status) }}
                    </span>
                </div>

                <h1 class="page-title mb-2">Attendance Import Review</h1>
                <p class="page-subtitle mb-2">{{ $attendanceImport->original_file_name }}</p>

                <div class="header-meta small d-flex gap-3 flex-wrap">
                    <span>
                        <i class="bi bi-table me-1"></i>
                        {{ $attendanceImport->sheet_name ?: 'Attendance' }}
                    </span>

                    <span>
                        <i class="bi bi-calendar-range me-1"></i>
                        @if ($attendanceImport->date_from && $attendanceImport->date_to)
                            {{ $attendanceImport->date_from->format('d M Y') }}
                            –
                            {{ $attendanceImport->date_to->format('d M Y') }}
                        @else
                            Period not detected
                        @endif
                    </span>

                    <span>
                        <i class="bi bi-person me-1"></i>
                        {{ $attendanceImport->uploader?->name ?? 'System' }}
                    </span>
                </div>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <a
                    href="{{ route('hr.attendance-imports.index') }}"
                    class="btn btn-modern header-btn-back"
                >
                    <i class="bi bi-arrow-left me-2"></i>Back
                </a>

                @if ($canEdit)
                    <form
                        method="POST"
                        action="{{ route('hr.attendance-imports.cancel', $attendanceImport) }}"
                        id="cancelImportForm"
                        class="d-none"
                    >
                        @csrf
                        @method('PATCH')
                    </form>

                    <button
                        type="button"
                        class="btn btn-modern header-btn-cancel"
                        data-confirm-form="cancelImportForm"
                        data-confirm-title="Cancel Attendance Import"
                        data-confirm-message="This import will be cancelled and can no longer be finalized. Continue?"
                        data-confirm-label="Cancel Import"
                        data-confirm-variant="danger"
                    >
                        <i class="bi bi-x-circle me-2"></i>Cancel Import
                    </button>

                    <form
                        method="POST"
                        action="{{ route('hr.attendance-imports.confirm', $attendanceImport) }}"
                        id="confirmImportForm"
                        class="d-none"
                    >
                        @csrf
                    </form>

                    <button
                        type="button"
                        class="btn btn-modern header-btn-confirm"
                        data-confirm-form="confirmImportForm"
                        data-confirm-title="Confirm Attendance Import"
                        data-confirm-message="All reviewed rows will be saved as final employee attendance. Continue?"
                        data-confirm-label="Confirm Import"
                        data-confirm-variant="primary"
                        {{ $canConfirm ? '' : 'disabled' }}
                        title="{{ $canConfirm ? 'Confirm attendance import' : 'Resolve all review, error, and duplicate rows first.' }}"
                    >
                        <i class="bi bi-check-circle me-2"></i>Confirm Import
                    </button>
                @endif
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger border-0 shadow-sm mb-4">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm mb-4">
            <div class="fw-semibold mb-2">Changes could not be saved.</div>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-3 mb-4">
        @foreach ([
            ['label' => 'Total Rows', 'value' => $attendanceImport->total_rows, 'class' => 'text-dark'],
            ['label' => 'Imported', 'value' => $attendanceImport->imported_rows, 'class' => 'text-dark'],
            ['label' => 'Generated', 'value' => $attendanceImport->generated_rows, 'class' => 'text-dark'],
            ['label' => 'Needs Review', 'value' => $attendanceImport->review_rows, 'class' => 'text-warning'],
            ['label' => 'Error', 'value' => $attendanceImport->error_rows, 'class' => 'text-danger'],
            ['label' => 'Duplicate', 'value' => $attendanceImport->duplicate_rows, 'class' => 'text-dark'],
        ] as $stat)
            <div class="col-6 col-md-4 col-xl-2">
                <div class="content-card h-100">
                    <div class="content-card-body">
                        <div class="small text-muted mb-2">{{ $stat['label'] }}</div>
                        <div class="fs-3 fw-bold {{ $stat['class'] }}">
                            {{ number_format($stat['value']) }}
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="content-card mb-4">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Attendance Filters</h5>
                <p class="content-card-subtitle mb-0">
                    Filter rows by employee, attendance type, review status, and date.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            <form
                method="GET"
                action="{{ route('hr.attendance-imports.review', $attendanceImport) }}"
                id="reviewFilterForm"
            >
                <div class="row g-3">
                    <div class="col-12 col-xl-3">
                        <label for="search" class="form-label">Search</label>
                        <input
                            type="text"
                            id="search"
                            name="search"
                            value="{{ $filters['search'] ?? '' }}"
                            class="form-control"
                            placeholder="Employee, number, remarks..."
                        >
                    </div>

                    <div class="col-12 col-sm-6 col-xl-3">
                        <label for="employee_id" class="form-label">Employee</label>
                        <select name="employee_id" id="employee_id" class="form-select filter-auto-submit">
                            <option value="">All Employee</option>
                            @foreach ($employees as $employee)
                                <option
                                    value="{{ $employee->id }}"
                                    {{ (int) ($filters['employee_id'] ?? 0) === $employee->id ? 'selected' : '' }}
                                >
                                    {{ $employee->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-sm-6 col-xl-3">
                        <label for="attendance_type" class="form-label">Attendance</label>
                        <select name="attendance_type" id="attendance_type" class="form-select filter-auto-submit">
                            <option value="">All Type</option>
                            @foreach ($attendanceTypeOptions as $value => $label)
                                <option
                                    value="{{ $value }}"
                                    {{ ($filters['attendance_type'] ?? '') === $value ? 'selected' : '' }}
                                >
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-sm-6 col-xl-3">
                        <label for="review_status" class="form-label">Review</label>
                        <select name="review_status" id="review_status" class="form-select filter-auto-submit">
                            <option value="">All Status</option>
                            @foreach ($reviewStatusOptions as $value => $label)
                                <option
                                    value="{{ $value }}"
                                    {{ ($filters['review_status'] ?? '') === $value ? 'selected' : '' }}
                                >
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-sm-6 col-xl-3">
                        <label for="date_from" class="form-label">Date From</label>
                        <input
                            type="date"
                            id="date_from"
                            name="date_from"
                            value="{{ $filters['date_from'] ?? '' }}"
                            class="form-control filter-auto-submit"
                        >
                    </div>

                    <div class="col-12 col-sm-6 col-xl-3">
                        <label for="date_to" class="form-label">Date To</label>
                        <input
                            type="date"
                            id="date_to"
                            name="date_to"
                            value="{{ $filters['date_to'] ?? '' }}"
                            class="form-control filter-auto-submit"
                        >
                    </div>
                </div>

                <div class="filter-actions d-flex justify-content-end align-items-center gap-2 flex-wrap">
                    <a
                        href="{{ route('hr.attendance-imports.review', $attendanceImport) }}"
                        class="btn btn-outline-secondary btn-modern"
                    >
                        <i class="bi bi-arrow-counterclockwise me-2"></i>Reset
                    </a>

                    <button type="submit" class="btn btn-primary btn-modern">
                        <i class="bi bi-search me-2"></i>Search
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="content-card">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Attendance Review by Employee</h5>
                <p class="content-card-subtitle mb-0">
                    All employees and attendance rows are displayed in one page. Click a Needs Review row to open the adjustment form.
                </p>
            </div>

            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge rounded-pill bg-light text-dark border">
                    {{ number_format($employeeGroups->count()) }} employees
                </span>

                <span class="badge rounded-pill bg-light text-dark border">
                    {{ number_format($employeeGroups->sum('record_count')) }} attendance rows
                </span>
            </div>
        </div>

        <div class="content-card-body">
            <form
                method="POST"
                action="{{ route('hr.attendance-imports.bulk-update', $attendanceImport) }}"
                id="bulkUpdateForm"
            >
                @csrf
                @method('PATCH')

                @if ($employeeGroups->count())
                    <div class="attendance-table-wrap">
                        <table class="table table-hover align-middle admin-table attendance-review-table mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">
                                        @if ($canEdit)
                                            <input type="checkbox" class="form-check-input" id="selectAllRows">
                                        @endif
                                    </th>
                                    <th class="text-nowrap">Date</th>
                                    <th class="text-nowrap">Template / Schedule</th>
                                    <th class="text-nowrap">Clock In</th>
                                    <th class="text-nowrap">Clock Out</th>
                                    <th class="text-nowrap">Attendance</th>
                                    <th class="text-nowrap">Leave / Permission</th>
                                    <th class="text-nowrap">Punctuality</th>
                                    <th class="text-nowrap">Source</th>
                                    <th class="text-nowrap">Review</th>
                                    <th class="text-nowrap">Remarks</th>
                                    <th class="text-end text-nowrap">Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($employeeGroups as $employeeGroup)
                                    @php
                                        $groupEmployeeName = $employeeGroup['employee_name'];
                                        $groupEmployeeNumber = $employeeGroup['employee_number']
                                            ?: 'No employee number';

                                        $groupReviewCount = (int) $employeeGroup['needs_review_count'];
                                        $groupErrorCount = (int) $employeeGroup['error_count'];
                                        $groupDuplicateCount = (int) $employeeGroup['duplicate_count'];

                                        $groupDateFrom = $employeeGroup['date_from'];
                                        $groupDateTo = $employeeGroup['date_to'];
                                    @endphp

                                    <tr class="employee-group-row">
                                        <td colspan="12">
                                            <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                                                <div class="d-flex align-items-center gap-3">
                                                    <span class="employee-group-icon">
                                                        <i class="bi bi-person-badge"></i>
                                                    </span>

                                                    <div>
                                                        <div class="fw-bold text-dark">{{ $groupEmployeeName }}</div>
                                                        <div class="small text-muted">
                                                            {{ $groupEmployeeNumber }}
                                                            · {{ number_format($employeeGroup['record_count']) }} records

                                                            @if ($groupDateFrom && $groupDateTo)
                                                                · {{ $groupDateFrom->format('d M') }}
                                                                – {{ $groupDateTo->format('d M Y') }}
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="d-flex gap-2 flex-wrap">
                                                    @if ($employeeGroup['is_unmatched'])
                                                        <span class="badge rounded-pill bg-danger-subtle text-danger-emphasis border border-danger-subtle">
                                                            Unmatched Employee
                                                        </span>
                                                    @endif

                                                    @if ($groupReviewCount > 0)
                                                        <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                                            {{ $groupReviewCount }} Needs Review
                                                        </span>
                                                    @endif

                                                    @if ($groupErrorCount > 0)
                                                        <span class="badge rounded-pill bg-danger-subtle text-danger-emphasis border border-danger-subtle">
                                                            {{ $groupErrorCount }} Error
                                                        </span>
                                                    @endif

                                                    @if ($groupDuplicateCount > 0)
                                                        <span class="badge rounded-pill bg-dark-subtle text-dark-emphasis border border-dark-subtle">
                                                            {{ $groupDuplicateCount }} Duplicate
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                    </tr>

                                    @foreach ($employeeGroup['rows'] as $row)
                                        @php
                                            $editPayload = [
                                                'employee_id' => $row->employee_id,
                                                'working_hour_template_id' => $row->working_hour_template_id,
                                                'attendance_date' => optional($row->attendance_date)->format('Y-m-d'),
                                                'clock_in' => $row->clock_in,
                                                'clock_out' => $row->clock_out,
                                                'scheduled_start_time' => $row->scheduled_start_time,
                                                'scheduled_end_time' => $row->scheduled_end_time,
                                                'attendance_type' => $row->attendance_type,
                                                'punctuality_status' => $row->punctuality_status,
                                                'arrival_status' => $row->arrival_status,
                                                'departure_status' => $row->departure_status,
                                                'late_minutes' => $row->late_minutes,
                                                'early_leave_minutes' => $row->early_leave_minutes,
                                                'leave_type' => $row->leave_type,
                                                'leave_duration' => $row->leave_duration,
                                                'leave_session' => $row->leave_session,
                                                'leave_start_time' => $row->leave_start_time,
                                                'leave_end_time' => $row->leave_end_time,
                                                'leave_minutes' => $row->leave_minutes,
                                                'is_excused' => (bool) $row->is_excused,
                                                'leave_reason' => $row->leave_reason,
                                                'remarks' => $row->remarks,
                                                'review_status' => $row->review_status,
                                                'update_url' => route(
                                                    'hr.attendance-imports.rows.update',
                                                    [$attendanceImport, $row]
                                                ),
                                            ];

                                            $isNeedsReview = $canEdit
                                                && $row->review_status === 'needs_review';
                                        @endphp

                                        <tr
                                            class="{{ $row->review_status === 'needs_review' ? 'table-warning needs-review-row' : ($row->review_status === 'error' ? 'table-danger' : '') }}"
                                            @if ($isNeedsReview)
                                                role="button"
                                                tabindex="0"
                                                title="Click to review this attendance row"
                                                data-attendance-payload="{{ base64_encode(json_encode(
                                                    $editPayload,
                                                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                                                )) }}"
                                            @endif
                                        >
                                            <td>
                                                @if ($canEdit)
                                                    <input
                                                        type="checkbox"
                                                        class="form-check-input row-checkbox"
                                                        name="row_ids[]"
                                                        value="{{ $row->id }}"
                                                    >
                                                @endif
                                            </td>

                                            <td class="text-nowrap">
                                                <div class="fw-semibold text-dark">
                                                    {{ $row->attendance_date?->format('d M Y') ?? '-' }}
                                                </div>
                                                <div class="small text-muted">
                                                    {{ $row->attendance_date?->format('l') ?? '' }}
                                                </div>
                                            </td>

                                            <td>
                                                <div class="fw-semibold text-dark">
                                                    {{ $row->workingHourTemplate?->name
                                                        ?? $row->working_hours_template_raw
                                                        ?? 'Unknown Template' }}
                                                </div>
                                                <div class="small text-muted">
                                                    {{ $row->scheduled_start_time ?: '-' }}
                                                    –
                                                    {{ $row->scheduled_end_time ?: '-' }}
                                                </div>

                                                @if ($row->schedule_is_inferred)
                                                    <span class="badge rounded-pill bg-light text-dark border mt-2">
                                                        Inferred
                                                    </span>
                                                @endif
                                            </td>

                                            <td class="text-nowrap fw-semibold">{{ $row->clock_in ?: '-' }}</td>
                                            <td class="text-nowrap fw-semibold">{{ $row->clock_out ?: '-' }}</td>

                                            <td class="text-nowrap">
                                                <span class="badge rounded-pill {{ $attendanceBadgeClass($row->attendance_type) }}">
                                                    {{ $attendanceTypeOptions[$row->attendance_type] ?? ucfirst($row->attendance_type) }}
                                                </span>
                                            </td>

                                            <td>
                                                @if ($row->leave_type)
                                                    <div class="fw-semibold text-dark">
                                                        {{ $leaveTypeOptions[$row->leave_type] ?? ucfirst(str_replace('_', ' ', $row->leave_type)) }}
                                                    </div>
                                                    <div class="small text-muted">
                                                        {{ $leaveDurationOptions[$row->leave_duration] ?? ucfirst(str_replace('_', ' ', $row->leave_duration ?? '')) }}
                                                        @if ($row->leave_session)
                                                            · {{ $leaveSessionOptions[$row->leave_session] ?? ucfirst(str_replace('_', ' ', $row->leave_session)) }}
                                                        @endif
                                                    </div>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>

                                            <td>
                                                <div class="fw-semibold text-dark">
                                                    {{ $punctualityOptions[$row->punctuality_status] ?? ucfirst(str_replace('_', ' ', $row->punctuality_status)) }}
                                                </div>

                                                @if ($row->late_minutes)
                                                    <div class="small text-muted">{{ $row->late_minutes }} min late</div>
                                                @elseif ($row->early_leave_minutes)
                                                    <div class="small text-muted">{{ $row->early_leave_minutes }} min early</div>
                                                @endif
                                            </td>

                                            <td class="text-nowrap">
                                                <span class="badge rounded-pill bg-light text-dark border">
                                                    {{ $sourceOptions[$row->source] ?? ucfirst($row->source) }}
                                                </span>
                                            </td>

                                            <td>
                                                <span class="badge rounded-pill {{ $reviewBadgeClass($row->review_status) }}">
                                                    {{ $reviewStatusOptions[$row->review_status] ?? ucfirst($row->review_status) }}
                                                </span>

                                                @if (
                                                    $row->validation_message
                                                    && in_array($row->review_status, ['needs_review', 'error', 'duplicate'], true)
                                                )
                                                    <div class="small text-danger mt-2" style="max-width: 260px;">
                                                        {{ $row->validation_message }}
                                                    </div>
                                                @endif

                                                @if ($isNeedsReview)
                                                    <div class="review-click-hint">
                                                        <i class="bi bi-cursor me-1"></i>Click row to review
                                                    </div>
                                                @endif
                                            </td>

                                            <td style="min-width: 220px;">
                                                {{ $row->remarks ?: '-' }}
                                            </td>

                                            <td class="text-end text-nowrap">
                                                @if ($canEdit)
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-secondary px-3 edit-row-button"
                                                        data-attendance-payload="{{ base64_encode(json_encode(
                                                            $editPayload,
                                                            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                                                        )) }}"
                                                    >
                                                        {{ $row->review_status === 'needs_review' ? 'Review' : 'Edit' }}
                                                    </button>
                                                @else
                                                    <span class="text-muted small">Locked</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($canEdit)
                        <div
                            class="d-flex justify-content-between align-items-center gap-3 flex-wrap mt-3 d-none"
                            id="bulkActionBar"
                        >
                            <div>
                                <div class="fw-semibold text-dark">
                                    <span id="selectedRowCount">0</span> rows selected
                                </div>
                                <div class="small text-muted">
                                    Apply one attendance resolution to multiple selected rows.
                                </div>
                            </div>

                            <button
                                type="button"
                                class="btn btn-primary btn-modern"
                                data-bs-toggle="modal"
                                data-bs-target="#bulkUpdateModal"
                            >
                                Bulk Adjust
                            </button>
                        </div>
                    @endif
                @else
                    <div class="empty-state-box">
                        <div class="empty-state-icon">
                            <i class="bi bi-calendar2-check"></i>
                        </div>

                        <h5 class="empty-state-title">No attendance row found</h5>
                        <p class="empty-state-text mb-0">
                            No attendance row matches the current filters.
                        </p>
                    </div>
                @endif
            </form>

        </div>
    </div>
</div>

{{-- Edit Attendance Modal --}}
<div class="modal fade" id="editAttendanceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <form method="POST" id="editAttendanceForm">
            @csrf
            @method('PATCH')

            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold mb-1">Edit Attendance Row</h5>
                        <div class="small text-muted">
                            Adjust employee, work schedule, attendance status, leave, permission, and review status.
                        </div>
                    </div>

                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="content-card mb-3">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Employee & Date</h5>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <div class="row g-3">
                                <div class="col-12 col-lg-5">
                                    <label class="form-label">Employee</label>
                                    <select name="employee_id" class="form-select" data-field="employee_id">
                                        <option value="">Select Employee</option>
                                        @foreach ($employees as $employee)
                                            <option value="{{ $employee->id }}">
                                                {{ $employee->name }} · {{ $employee->employee_number }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 col-lg-4">
                                    <label class="form-label">Working Template</label>
                                    <select name="working_hour_template_id" class="form-select" data-field="working_hour_template_id">
                                        <option value="">Select Template</option>
                                        @foreach ($workingHourTemplates as $template)
                                            <option value="{{ $template->id }}">{{ $template->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 col-lg-3">
                                    <label class="form-label">Attendance Date</label>
                                    <input type="date" name="attendance_date" class="form-control" data-field="attendance_date">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="content-card mb-3">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Schedule & Actual Time</h5>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <div class="row g-3">
                                <div class="col-6 col-lg-3">
                                    <label class="form-label">Scheduled Start</label>
                                    <input type="time" name="scheduled_start_time" class="form-control" data-field="scheduled_start_time">
                                </div>

                                <div class="col-6 col-lg-3">
                                    <label class="form-label">Scheduled End</label>
                                    <input type="time" name="scheduled_end_time" class="form-control" data-field="scheduled_end_time">
                                </div>

                                <div class="col-6 col-lg-3">
                                    <label class="form-label">Clock In</label>
                                    <input type="time" name="clock_in" class="form-control" data-field="clock_in">
                                </div>

                                <div class="col-6 col-lg-3">
                                    <label class="form-label">Clock Out</label>
                                    <input type="time" name="clock_out" class="form-control" data-field="clock_out">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="content-card mb-3">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Attendance Classification</h5>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <div class="row g-3">
                                <div class="col-12 col-md-4">
                                    <label class="form-label">Attendance Type</label>
                                    <select name="attendance_type" class="form-select" data-field="attendance_type">
                                        @foreach ($attendanceTypeOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label class="form-label">Punctuality</label>
                                    <select name="punctuality_status" class="form-select" data-field="punctuality_status">
                                        @foreach ($punctualityOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label class="form-label">Review Status</label>
                                    <select name="review_status" class="form-select" data-field="review_status">
                                        @foreach ($reviewStatusOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label class="form-label">Arrival Status</label>
                                    <select name="arrival_status" class="form-select" data-field="arrival_status">
                                        @foreach ($arrivalStatusOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label class="form-label">Departure Status</label>
                                    <select name="departure_status" class="form-select" data-field="departure_status">
                                        @foreach ($departureStatusOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-6 col-md-2">
                                    <label class="form-label">Late Minutes</label>
                                    <input type="number" min="0" name="late_minutes" class="form-control" data-field="late_minutes">
                                </div>

                                <div class="col-6 col-md-2">
                                    <label class="form-label">Early Minutes</label>
                                    <input type="number" min="0" name="early_leave_minutes" class="form-control" data-field="early_leave_minutes">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="content-card">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Leave / Permission</h5>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <div class="row g-3">
                                <div class="col-12 col-md-4">
                                    <label class="form-label">Leave Type</label>
                                    <select name="leave_type" class="form-select" data-field="leave_type">
                                        <option value="">No Leave</option>
                                        @foreach ($leaveTypeOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label class="form-label">Duration</label>
                                    <select name="leave_duration" class="form-select" data-field="leave_duration">
                                        <option value="">Select Duration</option>
                                        @foreach ($leaveDurationOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label class="form-label">Session</label>
                                    <select name="leave_session" class="form-select" data-field="leave_session">
                                        <option value="">Select Session</option>
                                        @foreach ($leaveSessionOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-6 col-md-3">
                                    <label class="form-label">Leave Start</label>
                                    <input type="time" name="leave_start_time" class="form-control" data-field="leave_start_time">
                                </div>

                                <div class="col-6 col-md-3">
                                    <label class="form-label">Leave End</label>
                                    <input type="time" name="leave_end_time" class="form-control" data-field="leave_end_time">
                                </div>

                                <div class="col-6 col-md-3">
                                    <label class="form-label">Leave Minutes</label>
                                    <input type="number" min="0" name="leave_minutes" class="form-control" data-field="leave_minutes">
                                </div>

                                <div class="col-6 col-md-3 d-flex align-items-end">
                                    <div class="form-check form-switch mb-2">
                                        <input type="hidden" name="is_excused" value="0">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            name="is_excused"
                                            value="1"
                                            id="editIsExcused"
                                            data-field="is_excused"
                                        >
                                        <label class="form-check-label" for="editIsExcused">Excused</label>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Leave Reason</label>
                                    <textarea name="leave_reason" rows="2" class="form-control" data-field="leave_reason"></textarea>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Remarks</label>
                                    <textarea name="remarks" rows="3" class="form-control" data-field="remarks"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-primary btn-modern">
                        <i class="bi bi-check-circle me-2"></i>Save Changes
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Bulk Adjustment Modal --}}
<div class="modal fade" id="bulkUpdateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold mb-1">Bulk Attendance Adjustment</h5>
                    <div class="small text-muted">
                        Apply the same attendance resolution to all selected rows.
                    </div>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label">Attendance Type</label>
                        <select name="resolution[attendance_type]" class="form-select" form="bulkUpdateForm" required>
                            @foreach ($attendanceTypeOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label">Review Status</label>
                        <select name="resolution[review_status]" class="form-select" form="bulkUpdateForm">
                            <option value="resolved">Resolved</option>
                            <option value="ignored">Ignored</option>
                            <option value="valid">Valid</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label">Leave Type</label>
                        <select name="resolution[leave_type]" class="form-select" form="bulkUpdateForm">
                            <option value="">No Leave</option>
                            @foreach ($leaveTypeOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label">Duration</label>
                        <select name="resolution[leave_duration]" class="form-select" form="bulkUpdateForm">
                            <option value="">Select Duration</option>
                            @foreach ($leaveDurationOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label">Session</label>
                        <select name="resolution[leave_session]" class="form-select" form="bulkUpdateForm">
                            <option value="">Select Session</option>
                            @foreach ($leaveSessionOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input type="hidden" name="resolution[is_excused]" value="0" form="bulkUpdateForm">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="resolution[is_excused]"
                                value="1"
                                id="bulkIsExcused"
                                form="bulkUpdateForm"
                            >
                            <label class="form-check-label" for="bulkIsExcused">
                                Mark as excused
                            </label>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Leave Reason</label>
                        <textarea
                            name="resolution[leave_reason]"
                            rows="2"
                            class="form-control"
                            form="bulkUpdateForm"
                        ></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Remarks</label>
                        <textarea
                            name="resolution[remarks]"
                            rows="3"
                            class="form-control"
                            form="bulkUpdateForm"
                        ></textarea>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                    Cancel
                </button>

                <button type="submit" class="btn btn-primary btn-modern" form="bulkUpdateForm">
                    Apply to Selected Rows
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Reusable Confirmation Dialog --}}
<div class="modal fade" id="actionConfirmationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body pt-2 pb-4 px-4">
                <div class="d-flex gap-3 align-items-start">
                    <div class="confirmation-icon is-primary" id="confirmationIcon">
                        <i class="bi bi-question-lg" id="confirmationIconGlyph"></i>
                    </div>

                    <div>
                        <h5 class="fw-bold mb-2" id="confirmationTitle">Confirm Action</h5>
                        <p class="text-muted mb-0" id="confirmationMessage">
                            Are you sure you want to continue?
                        </p>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-top">
                <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                    Go Back
                </button>

                <button type="button" class="btn btn-primary btn-modern" id="confirmationSubmitButton">
                    Confirm
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let attendanceEditModal = null;
    let attendanceConfirmationModal = null;
    let pendingAttendanceConfirmationForm = null;

    function decodeAttendancePayload(encodedPayload) {
        if (!encodedPayload) {
            return null;
        }

        try {
            const binary = window.atob(encodedPayload);
            const bytes = Uint8Array.from(
                binary,
                character => character.charCodeAt(0)
            );

            const json = new TextDecoder('utf-8').decode(bytes);

            return JSON.parse(json);
        } catch (error) {
            console.error('Attendance payload could not be decoded.', error);

            return null;
        }
    }

    function getAttendanceEditModal() {
        const modalElement = document.getElementById('editAttendanceModal');

        if (!modalElement) {
            console.error('Attendance edit modal element was not found.');

            return null;
        }

        if (!window.bootstrap || !window.bootstrap.Modal) {
            console.error('Bootstrap Modal is not available.');

            return null;
        }

        if (!attendanceEditModal) {
            attendanceEditModal = window.bootstrap.Modal.getOrCreateInstance(
                modalElement
            );
        }

        return attendanceEditModal;
    }

    function populateAttendanceEditForm(row) {
        const form = document.getElementById('editAttendanceForm');

        if (!form) {
            console.error('Attendance edit form was not found.');

            return false;
        }

        form.action = row.update_url || '';

        form.querySelectorAll('[data-field]').forEach(field => {
            const key = field.dataset.field;
            const value = row[key];

            if (field.type === 'checkbox') {
                field.checked = Boolean(value);

                return;
            }

            field.value = value ?? '';
        });

        return true;
    }

    function openAttendanceReviewModal(row) {
        if (!row || typeof row !== 'object') {
            console.error('Invalid attendance row payload.', row);

            return;
        }

        if (!populateAttendanceEditForm(row)) {
            return;
        }

        getAttendanceEditModal()?.show();
    }

    function openAttendanceReviewModalFromElement(element) {
        const row = decodeAttendancePayload(
            element.dataset.attendancePayload
        );

        if (!row) {
            return;
        }

        openAttendanceReviewModal(row);
    }

    function syncAttendanceBulkActionBar() {
        const selectAllRows = document.getElementById('selectAllRows');

        const rowCheckboxes = Array.from(
            document.querySelectorAll('.row-checkbox')
        );

        const bulkActionBar = document.getElementById('bulkActionBar');

        const selectedRowCount = document.getElementById(
            'selectedRowCount'
        );

        const selected = rowCheckboxes.filter(
            checkbox => checkbox.checked
        );

        if (selectedRowCount) {
            selectedRowCount.textContent = selected.length;
        }

        bulkActionBar?.classList.toggle(
            'd-none',
            selected.length === 0
        );

        if (selectAllRows) {
            selectAllRows.checked =
                selected.length > 0
                && selected.length === rowCheckboxes.length;

            selectAllRows.indeterminate =
                selected.length > 0
                && selected.length < rowCheckboxes.length;
        }
    }

    function initializeAttendanceBulkSelection() {
        const selectAllRows = document.getElementById('selectAllRows');

        const rowCheckboxes = Array.from(
            document.querySelectorAll('.row-checkbox')
        );

        selectAllRows?.addEventListener('change', () => {
            rowCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAllRows.checked;
            });

            syncAttendanceBulkActionBar();
        });

        rowCheckboxes.forEach(checkbox => {
            checkbox.addEventListener(
                'change',
                syncAttendanceBulkActionBar
            );
        });
    }

    function initializeAttendanceRowEditors() {
        document.querySelectorAll('.edit-row-button').forEach(button => {
            button.addEventListener('click', event => {
                event.preventDefault();
                event.stopPropagation();

                openAttendanceReviewModalFromElement(button);
            });
        });

        document
            .querySelectorAll(
                '.needs-review-row[data-attendance-payload]'
            )
            .forEach(rowElement => {
                rowElement.addEventListener('click', event => {
                    if (
                        event.target.closest(
                            'input, button, a, select, textarea, label, .dropdown'
                        )
                    ) {
                        return;
                    }

                    openAttendanceReviewModalFromElement(rowElement);
                });

                rowElement.addEventListener('keydown', event => {
                    if (!['Enter', ' '].includes(event.key)) {
                        return;
                    }

                    if (
                        event.target.closest(
                            'input, button, a, select, textarea, label, .dropdown'
                        )
                    ) {
                        return;
                    }

                    event.preventDefault();

                    openAttendanceReviewModalFromElement(rowElement);
                });
            });
    }

    function initializeAttendanceFilters() {
        document
            .querySelectorAll('.filter-auto-submit')
            .forEach(field => {
                field.addEventListener('change', () => {
                    document
                        .getElementById('reviewFilterForm')
                        ?.submit();
                });
            });
    }

    function getAttendanceConfirmationModal() {
        const modalElement = document.getElementById(
            'actionConfirmationModal'
        );

        if (!modalElement) {
            console.error('Confirmation modal element was not found.');

            return null;
        }

        if (!window.bootstrap || !window.bootstrap.Modal) {
            console.error('Bootstrap Modal is not available.');

            return null;
        }

        if (!attendanceConfirmationModal) {
            attendanceConfirmationModal =
                window.bootstrap.Modal.getOrCreateInstance(
                    modalElement
                );
        }

        return attendanceConfirmationModal;
    }

    function initializeAttendanceConfirmationDialog() {
        const modalElement = document.getElementById(
            'actionConfirmationModal'
        );

        const confirmationTitle = document.getElementById(
            'confirmationTitle'
        );

        const confirmationMessage = document.getElementById(
            'confirmationMessage'
        );

        const confirmationIcon = document.getElementById(
            'confirmationIcon'
        );

        const confirmationIconGlyph = document.getElementById(
            'confirmationIconGlyph'
        );

        const confirmationSubmitButton = document.getElementById(
            'confirmationSubmitButton'
        );

        document
            .querySelectorAll('[data-confirm-form]')
            .forEach(trigger => {
                trigger.addEventListener('click', () => {
                    if (trigger.disabled) {
                        return;
                    }

                    pendingAttendanceConfirmationForm =
                        document.getElementById(
                            trigger.dataset.confirmForm
                        );

                    const variant =
                        trigger.dataset.confirmVariant || 'primary';

                    if (confirmationTitle) {
                        confirmationTitle.textContent =
                            trigger.dataset.confirmTitle
                            || 'Confirm Action';
                    }

                    if (confirmationMessage) {
                        confirmationMessage.textContent =
                            trigger.dataset.confirmMessage
                            || 'Are you sure you want to continue?';
                    }

                    if (confirmationSubmitButton) {
                        confirmationSubmitButton.textContent =
                            trigger.dataset.confirmLabel
                            || 'Confirm';

                        confirmationSubmitButton.className =
                            `btn btn-modern ${
                                variant === 'danger'
                                    ? 'btn-danger'
                                    : 'btn-primary'
                            }`;
                    }

                    if (confirmationIcon) {
                        confirmationIcon.className =
                            `confirmation-icon ${
                                variant === 'danger'
                                    ? 'is-danger'
                                    : 'is-primary'
                            }`;
                    }

                    if (confirmationIconGlyph) {
                        confirmationIconGlyph.className =
                            variant === 'danger'
                                ? 'bi bi-exclamation-triangle-fill'
                                : 'bi bi-check-lg';
                    }

                    getAttendanceConfirmationModal()?.show();
                });
            });

        confirmationSubmitButton?.addEventListener('click', () => {
            if (!pendingAttendanceConfirmationForm) {
                return;
            }

            confirmationSubmitButton.disabled = true;

            confirmationSubmitButton.innerHTML = `
                <span
                    class="spinner-border spinner-border-sm me-2"
                    aria-hidden="true"
                ></span>
                Processing...
            `;

            pendingAttendanceConfirmationForm.submit();
        });

        modalElement?.addEventListener(
            'hidden.bs.modal',
            () => {
                pendingAttendanceConfirmationForm = null;

                if (confirmationSubmitButton) {
                    confirmationSubmitButton.disabled = false;
                    confirmationSubmitButton.textContent = 'Confirm';
                    confirmationSubmitButton.className =
                        'btn btn-primary btn-modern';
                }
            }
        );
    }

    document.addEventListener('DOMContentLoaded', () => {
        initializeAttendanceBulkSelection();
        initializeAttendanceRowEditors();
        initializeAttendanceFilters();
        initializeAttendanceConfirmationDialog();
    });
</script>
@endpush
