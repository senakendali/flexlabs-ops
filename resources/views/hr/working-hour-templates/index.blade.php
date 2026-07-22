@extends('layouts.app-dashboard')

@section('title', 'Working Hours Templates')

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
                <h1 class="page-title mb-2">Working Hours Templates</h1>
                <p class="page-subtitle mb-0">
                    Kelola jam kerja, break, half-day boundary, working days, dan toleransi keterlambatan.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-light btn-modern" onclick="openWorkingTemplateCreateModal()">
                    <i class="bi bi-plus-lg me-2"></i>Add Template
                </button>
            </div>
        </div>
    </div>

    <div
        id="workingTemplateToastContainer"
        class="toast-container position-fixed top-0 end-0 p-3"
        style="z-index: 9999;"
    ></div>

    <div class="row g-3 mb-4">
        @foreach ([
            [
                'label' => 'Total Templates',
                'value' => $summary['total'],
                'icon' => 'bi bi-clock-history',
                'description' => 'Seluruh template jam kerja yang tersimpan.',
            ],
            [
                'label' => 'Active',
                'value' => $summary['active'],
                'icon' => 'bi bi-check-circle-fill',
                'description' => 'Template aktif yang tersedia untuk employee dan attendance.',
            ],
            [
                'label' => 'Inactive',
                'value' => $summary['inactive'],
                'icon' => 'bi bi-pause-circle-fill',
                'description' => 'Template nonaktif yang dipertahankan untuk histori.',
            ],
            [
                'label' => 'Incomplete',
                'value' => $summary['incomplete'],
                'icon' => 'bi bi-exclamation-triangle-fill',
                'description' => 'Template yang belum memiliki start atau end time lengkap.',
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

    <div class="content-card working-template-table-card">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Template List</h5>
                <p class="content-card-subtitle mb-0">
                    Review schedule configuration dan penggunaan template pada employee serta attendance.
                </p>
            </div>

            <form
                method="GET"
                action="{{ route('hr.working-hour-templates.index') }}"
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
                    name="source"
                    class="form-select form-select-sm"
                    style="width: 155px;"
                    onchange="this.form.submit()"
                >
                    <option value="">All Sources</option>
                    @foreach ($sourceOptions as $source)
                        <option
                            value="{{ $source }}"
                            {{ ($filters['source'] ?? '') === $source ? 'selected' : '' }}
                        >
                            {{ $source }}
                        </option>
                    @endforeach
                </select>

                <div class="input-group input-group-sm master-search-group">
                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        value="{{ $filters['search'] ?? '' }}"
                        placeholder="Search template..."
                    >
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>

                @if (collect($filters)->filter(fn ($value) => filled($value))->isNotEmpty())
                    <a href="{{ route('hr.working-hour-templates.index') }}" class="btn btn-sm btn-outline-secondary">
                        Reset
                    </a>
                @endif
            </form>
        </div>

        <div class="content-card-body">
            @if ($workingHourTemplates->count())
                <div class="master-table-responsive">
                    <table class="table table-hover align-middle admin-table master-admin-table mb-0">
                        <thead>
                            <tr>
                                <th class="text-nowrap" style="width: 65px;">No</th>
                                <th class="text-nowrap col-template">Template</th>
                                <th class="text-nowrap">Schedule</th>
                                <th class="text-nowrap">Break</th>
                                <th class="text-nowrap">Half-Day Boundary</th>
                                <th class="text-nowrap">Working Days</th>
                                <th class="text-nowrap">Tolerance</th>
                                <th class="text-nowrap">Work Minutes</th>
                                <th class="text-nowrap">Usage</th>
                                <th class="text-nowrap">Status</th>
                                <th class="text-end text-nowrap" style="width: 130px;">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($workingHourTemplates as $template)
                                @php
                                    $templatePayload = [
                                        'id' => $template->id,
                                        'code' => $template->code,
                                        'name' => $template->name,
                                        'start_time' => $template->start_time,
                                        'end_time' => $template->end_time,
                                        'break_start_time' => $template->break_start_time,
                                        'break_end_time' => $template->break_end_time,
                                        'first_half_end_time' => $template->first_half_end_time,
                                        'second_half_start_time' => $template->second_half_start_time,
                                        'working_days' => $template->working_days ?: [],
                                        'late_tolerance_minutes' => $template->late_tolerance_minutes,
                                        'source' => $template->source,
                                        'is_active' => (bool) $template->is_active,
                                    ];

                                    $encodedTemplatePayload = base64_encode(json_encode(
                                        $templatePayload,
                                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                                    ));

                                    $templateIncomplete = blank($template->start_time)
                                        || blank($template->end_time);

                                    $usageCount = (int) $template->employees_count
                                        + (int) $template->import_rows_count
                                        + (int) $template->attendances_count;
                                @endphp

                                <tr class="{{ $templateIncomplete ? 'table-warning' : '' }}">
                                    <td class="text-muted">
                                        {{ ($workingHourTemplates->currentPage() - 1) * $workingHourTemplates->perPage() + $loop->iteration }}
                                    </td>

                                    <td>
                                        <div class="fw-bold text-dark">{{ $template->name }}</div>
                                        <div class="small text-muted">
                                            {{ $template->code ?: 'No code' }}
                                            · {{ $template->source ?: 'manual' }}
                                        </div>

                                        @if ($templateIncomplete)
                                            <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis border border-warning-subtle mt-2">
                                                Incomplete Schedule
                                            </span>
                                        @endif
                                    </td>

                                    <td class="text-nowrap">
                                        <div class="fw-semibold text-dark">
                                            {{ $formatTime($template->start_time) }}
                                            –
                                            {{ $formatTime($template->end_time) }}
                                        </div>
                                    </td>

                                    <td class="text-nowrap">
                                        @if ($template->break_start_time && $template->break_end_time)
                                            <div class="fw-semibold text-dark">
                                                {{ $formatTime($template->break_start_time) }}
                                                –
                                                {{ $formatTime($template->break_end_time) }}
                                            </div>
                                        @else
                                            <span class="text-muted">No break</span>
                                        @endif
                                    </td>

                                    <td class="text-nowrap">
                                        <div class="fw-semibold text-dark">
                                            1st: {{ $formatTime($template->first_half_end_time) }}
                                        </div>
                                        <div class="small text-muted">
                                            2nd: {{ $formatTime($template->second_half_start_time) }}
                                        </div>
                                    </td>

                                    <td>
                                        <div class="working-day-badges">
                                            @foreach (($template->working_days ?: [1, 2, 3, 4, 5]) as $day)
                                                <span class="badge rounded-pill bg-light text-dark border">
                                                    {{ substr($workingDayOptions[$day] ?? (string) $day, 0, 3) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>

                                    <td class="text-nowrap">
                                        <div class="fw-semibold text-dark">
                                            {{ number_format((int) $template->late_tolerance_minutes) }} min
                                        </div>
                                    </td>

                                    <td class="text-nowrap">
                                        <div class="fw-semibold text-dark">
                                            {{ number_format((int) $template->scheduled_work_minutes) }} min
                                        </div>
                                        <div class="small text-muted">
                                            {{ number_format(((int) $template->scheduled_work_minutes) / 60, 1) }} hours
                                        </div>
                                    </td>

                                    <td class="text-nowrap">
                                        <div class="small">
                                            <strong>{{ number_format($template->employees_count) }}</strong> employees
                                        </div>
                                        <div class="small text-muted">
                                            {{ number_format($template->attendances_count) }} attendance
                                        </div>
                                    </td>

                                    <td class="text-nowrap">
                                        <span class="badge rounded-pill {{ $activeBadgeClass((bool) $template->is_active) }}">
                                            {{ $template->is_active ? 'Active' : 'Inactive' }}
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
                                                        class="dropdown-item working-template-edit-button"
                                                        data-payload="{{ $encodedTemplatePayload }}"
                                                    >
                                                        <i class="bi bi-pencil-square me-2"></i>Edit Template
                                                    </button>
                                                </li>

                                                <li><hr class="dropdown-divider"></li>

                                                <li>
                                                    <button
                                                        type="button"
                                                        class="dropdown-item text-danger working-template-delete-button"
                                                        data-id="{{ $template->id }}"
                                                        data-name="{{ $template->name }}"
                                                        data-usage="{{ $usageCount }}"
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

                @if ($workingHourTemplates->hasPages())
                    <div class="mt-3">
                        {{ $workingHourTemplates->links() }}
                    </div>
                @endif
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <h5 class="empty-state-title">No working-hours templates found</h5>
                    <p class="empty-state-text mb-0">
                        Tambahkan template agar attendance dapat menentukan schedule dan punctuality otomatis.
                    </p>
                    <div class="mt-3">
                        <button type="button" class="btn btn-primary btn-modern" onclick="openWorkingTemplateCreateModal()">
                            <i class="bi bi-plus-lg me-2"></i>Add Template
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="modal fade" id="workingTemplateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <form id="workingTemplateForm">
            @csrf
            <input type="hidden" id="working_template_id">

            <div class="modal-content custom-modal">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title" id="workingTemplateModalTitle">Add Working-Hours Template</h5>
                        <p class="text-muted mb-0">
                            Lengkapi schedule agar sistem dapat menentukan workday, punctuality, dan durasi kerja.
                        </p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body pt-4">
                    <div id="workingTemplateFormAlert" class="alert alert-danger d-none mb-3"></div>

                    <div class="content-card mb-3">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Template Identity</h5>
                                <p class="content-card-subtitle mb-0">
                                    Nama template sebaiknya sama dengan template yang diterima dari sumber attendance.
                                </p>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <div class="row g-3">
                                <div class="col-12 col-md-4">
                                    <label for="working_template_code" class="form-label">Template Code</label>
                                    <input type="text" id="working_template_code" class="form-control" placeholder="e.g. REG-01">
                                    <div class="invalid-feedback" id="error_code"></div>
                                </div>

                                <div class="col-12 col-md-8">
                                    <label for="working_template_name" class="form-label">
                                        Template Name <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" id="working_template_name" class="form-control" placeholder="e.g. Regular working hours">
                                    <div class="invalid-feedback" id="error_name"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="content-card mb-3">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Main Schedule</h5>
                                <p class="content-card-subtitle mb-0">
                                    Start dan end time wajib diisi. Shift melewati tengah malam tetap didukung.
                                </p>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <div class="row g-3">
                                <div class="col-6 col-md-3">
                                    <label for="working_template_start_time" class="form-label">
                                        Start Time <span class="text-danger">*</span>
                                    </label>
                                    <input type="time" step="1" id="working_template_start_time" class="form-control">
                                    <div class="invalid-feedback" id="error_start_time"></div>
                                </div>

                                <div class="col-6 col-md-3">
                                    <label for="working_template_end_time" class="form-label">
                                        End Time <span class="text-danger">*</span>
                                    </label>
                                    <input type="time" step="1" id="working_template_end_time" class="form-control">
                                    <div class="invalid-feedback" id="error_end_time"></div>
                                </div>

                                <div class="col-6 col-md-3">
                                    <label for="working_template_break_start" class="form-label">Break Start</label>
                                    <input type="time" step="1" id="working_template_break_start" class="form-control">
                                    <div class="invalid-feedback" id="error_break_start_time"></div>
                                </div>

                                <div class="col-6 col-md-3">
                                    <label for="working_template_break_end" class="form-label">Break End</label>
                                    <input type="time" step="1" id="working_template_break_end" class="form-control">
                                    <div class="invalid-feedback" id="error_break_end_time"></div>
                                </div>

                                <div class="col-6 col-md-3">
                                    <label for="working_template_first_half_end" class="form-label">First Half End</label>
                                    <input type="time" step="1" id="working_template_first_half_end" class="form-control">
                                    <div class="invalid-feedback" id="error_first_half_end_time"></div>
                                </div>

                                <div class="col-6 col-md-3">
                                    <label for="working_template_second_half_start" class="form-label">Second Half Start</label>
                                    <input type="time" step="1" id="working_template_second_half_start" class="form-control">
                                    <div class="invalid-feedback" id="error_second_half_start_time"></div>
                                </div>

                                <div class="col-6 col-md-3">
                                    <label for="working_template_late_tolerance" class="form-label">Late Tolerance</label>
                                    <div class="input-group">
                                        <input
                                            type="number"
                                            min="0"
                                            max="240"
                                            id="working_template_late_tolerance"
                                            class="form-control"
                                            value="0"
                                        >
                                        <span class="input-group-text">minutes</span>
                                    </div>
                                    <div class="invalid-feedback d-block" id="error_late_tolerance_minutes"></div>
                                </div>

                                <div class="col-6 col-md-3">
                                    <label for="working_template_source" class="form-label">Source</label>
                                    <input type="text" id="working_template_source" class="form-control" placeholder="manual">
                                    <div class="invalid-feedback" id="error_source"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="content-card">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Working Days & Status</h5>
                                <p class="content-card-subtitle mb-0">
                                    Working days menentukan tanggal yang wajib memiliki attendance.
                                </p>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <div class="row g-3 align-items-end">
                                <div class="col-12 col-lg-8">
                                    <label class="form-label d-block">
                                        Working Days <span class="text-danger">*</span>
                                    </label>

                                    <div class="working-day-selector">
                                        @foreach ($workingDayOptions as $dayNumber => $dayLabel)
                                            <label class="working-day-option">
                                                <input
                                                    type="checkbox"
                                                    class="form-check-input working-template-day"
                                                    value="{{ $dayNumber }}"
                                                >
                                                <span>{{ substr($dayLabel, 0, 3) }}</span>
                                            </label>
                                        @endforeach
                                    </div>

                                    <div class="invalid-feedback d-block" id="error_working_days"></div>
                                </div>

                                <div class="col-12 col-lg-4">
                                    <div class="form-check form-switch mb-2">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            id="working_template_is_active"
                                            checked
                                        >
                                        <label class="form-check-label fw-semibold" for="working_template_is_active">
                                            Active Template
                                        </label>
                                    </div>
                                    <div class="form-text">
                                        Nonaktifkan template yang tidak boleh digunakan untuk data baru.
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

                    <button type="submit" class="btn btn-primary btn-modern" id="submitWorkingTemplateBtn">
                        <span class="default-text">
                            <i class="bi bi-save me-2"></i>Save Template
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

<div class="modal fade" id="deleteWorkingTemplateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal delete-confirm-modal">
            <div class="modal-header border-0 pb-0">
                <div class="delete-confirm-heading">
                    <div class="delete-confirm-icon">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>

                    <div>
                        <h5 class="modal-title">Delete Working-Hours Template</h5>
                        <p class="text-muted mb-0">Template yang sudah digunakan tidak dapat dihapus.</p>
                    </div>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body pt-4">
                <div class="delete-confirm-message">
                    <div class="delete-confirm-label">Template yang akan dihapus</div>
                    <div class="delete-confirm-name" id="deleteWorkingTemplateName">-</div>
                </div>

                <div class="delete-confirm-warning mt-3" id="deleteWorkingTemplateWarning">
                    Template akan dihapus permanen dari master.
                </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                    Cancel
                </button>

                <button type="button" class="btn btn-danger btn-modern" id="confirmDeleteWorkingTemplateBtn">
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
    .working-template-table-card,
    .working-template-table-card .content-card-body {
        min-width: 0;
        max-width: 100%;
        overflow: hidden;
    }

    .master-filter-form {
        justify-content: flex-end;
    }

    .master-search-group {
        width: 250px;
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
        min-width: 1360px;
    }

    .master-admin-table .col-template {
        min-width: 230px;
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
    let workingTemplateModal = null;
    let deleteWorkingTemplateModal = null;
    let workingTemplateEditMode = false;
    let deleteWorkingTemplateId = null;

    const workingTemplateRoutes = {
        store: @js(route('hr.working-hour-templates.store')),
        update: @js(route('hr.working-hour-templates.update', ['workingHourTemplate' => '__ID__'])),
        destroy: @js(route('hr.working-hour-templates.destroy', ['workingHourTemplate' => '__ID__'])),
    };

    const workingTemplateCsrfToken = @js(csrf_token());
    const workingTemplateFields = {};

    document.addEventListener('DOMContentLoaded', () => {
        workingTemplateModal = bootstrap.Modal.getOrCreateInstance(
            document.getElementById('workingTemplateModal')
        );

        deleteWorkingTemplateModal = bootstrap.Modal.getOrCreateInstance(
            document.getElementById('deleteWorkingTemplateModal')
        );

        Object.assign(workingTemplateFields, {
            id: document.getElementById('working_template_id'),
            code: document.getElementById('working_template_code'),
            name: document.getElementById('working_template_name'),
            start_time: document.getElementById('working_template_start_time'),
            end_time: document.getElementById('working_template_end_time'),
            break_start_time: document.getElementById('working_template_break_start'),
            break_end_time: document.getElementById('working_template_break_end'),
            first_half_end_time: document.getElementById('working_template_first_half_end'),
            second_half_start_time: document.getElementById('working_template_second_half_start'),
            late_tolerance_minutes: document.getElementById('working_template_late_tolerance'),
            source: document.getElementById('working_template_source'),
            is_active: document.getElementById('working_template_is_active'),
        });

        document.getElementById('workingTemplateForm')
            .addEventListener('submit', submitWorkingTemplateForm);

        document.getElementById('confirmDeleteWorkingTemplateBtn')
            .addEventListener('click', deleteWorkingTemplate);

        document.querySelectorAll('.working-template-edit-button').forEach(button => {
            button.addEventListener('click', () => {
                const payload = decodeTemplatePayload(button.dataset.payload);

                if (payload) {
                    openWorkingTemplateEditModal(payload);
                }
            });
        });

        document.querySelectorAll('.working-template-delete-button').forEach(button => {
            button.addEventListener('click', () => {
                openWorkingTemplateDeleteModal(
                    button.dataset.id,
                    button.dataset.name,
                    Number(button.dataset.usage || 0)
                );
            });
        });
    });

    window.openWorkingTemplateCreateModal = function () {
        workingTemplateEditMode = false;
        resetWorkingTemplateForm();

        document.getElementById('workingTemplateModalTitle').textContent =
            'Add Working-Hours Template';

        workingTemplateFields.late_tolerance_minutes.value = 0;
        workingTemplateFields.source.value = 'manual';
        workingTemplateFields.is_active.checked = true;

        document.querySelectorAll('.working-template-day').forEach(checkbox => {
            checkbox.checked = [1, 2, 3, 4, 5].includes(Number(checkbox.value));
        });

        workingTemplateModal.show();
    };

    function openWorkingTemplateEditModal(payload) {
        workingTemplateEditMode = true;
        resetWorkingTemplateForm();

        document.getElementById('workingTemplateModalTitle').textContent =
            'Edit Working-Hours Template';

        workingTemplateFields.id.value = payload.id ?? '';
        workingTemplateFields.code.value = payload.code ?? '';
        workingTemplateFields.name.value = payload.name ?? '';
        workingTemplateFields.start_time.value = normalizeTemplateTime(payload.start_time);
        workingTemplateFields.end_time.value = normalizeTemplateTime(payload.end_time);
        workingTemplateFields.break_start_time.value = normalizeTemplateTime(payload.break_start_time);
        workingTemplateFields.break_end_time.value = normalizeTemplateTime(payload.break_end_time);
        workingTemplateFields.first_half_end_time.value = normalizeTemplateTime(payload.first_half_end_time);
        workingTemplateFields.second_half_start_time.value = normalizeTemplateTime(payload.second_half_start_time);
        workingTemplateFields.late_tolerance_minutes.value =
            payload.late_tolerance_minutes ?? 0;
        workingTemplateFields.source.value = payload.source ?? 'manual';
        workingTemplateFields.is_active.checked = Boolean(payload.is_active);

        const selectedDays = (payload.working_days ?? []).map(Number);

        document.querySelectorAll('.working-template-day').forEach(checkbox => {
            checkbox.checked = selectedDays.includes(Number(checkbox.value));
        });

        workingTemplateModal.show();
    }

    function openWorkingTemplateDeleteModal(id, name, usage) {
        deleteWorkingTemplateId = id;

        document.getElementById('deleteWorkingTemplateName').textContent =
            name || '-';

        document.getElementById('deleteWorkingTemplateWarning').textContent =
            usage > 0
                ? `Template sedang digunakan oleh ${usage} record. Server akan menolak penghapusan; nonaktifkan template jika sudah tidak digunakan.`
                : 'Template belum digunakan dan dapat dihapus dari master.';

        setTemplateButtonLoading(
            document.getElementById('confirmDeleteWorkingTemplateBtn'),
            false
        );

        deleteWorkingTemplateModal.show();
    }

    async function submitWorkingTemplateForm(event) {
        event.preventDefault();
        clearWorkingTemplateErrors();

        const button = document.getElementById('submitWorkingTemplateBtn');
        setTemplateButtonLoading(button, true);

        const workingDays = Array.from(
            document.querySelectorAll('.working-template-day:checked')
        ).map(checkbox => Number(checkbox.value));

        const payload = {
            code: workingTemplateFields.code.value.trim() || null,
            name: workingTemplateFields.name.value.trim(),
            start_time: workingTemplateFields.start_time.value,
            end_time: workingTemplateFields.end_time.value,
            break_start_time: workingTemplateFields.break_start_time.value || null,
            break_end_time: workingTemplateFields.break_end_time.value || null,
            first_half_end_time: workingTemplateFields.first_half_end_time.value || null,
            second_half_start_time: workingTemplateFields.second_half_start_time.value || null,
            working_days: workingDays,
            late_tolerance_minutes: Number(
                workingTemplateFields.late_tolerance_minutes.value || 0
            ),
            source: workingTemplateFields.source.value.trim() || 'manual',
            is_active: workingTemplateFields.is_active.checked,
        };

        const templateId = workingTemplateFields.id.value;
        const url = workingTemplateEditMode
            ? workingTemplateRoutes.update.replace('__ID__', templateId)
            : workingTemplateRoutes.store;

        try {
            const response = await fetch(url, {
                method: workingTemplateEditMode ? 'PUT' : 'POST',
                headers: workingTemplateJsonHeaders(),
                body: JSON.stringify(payload),
            });

            const result = await parseWorkingTemplateResponse(response);

            if (response.status === 422) {
                setWorkingTemplateValidationErrors(result.errors || {});
                throw new Error('Validation failed.');
            }

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Template gagal disimpan.');
            }

            workingTemplateModal.hide();
            showWorkingTemplateToast(
                result.message || 'Template berhasil disimpan.',
                'success'
            );

            setTimeout(() => window.location.reload(), 650);
        } catch (error) {
            if (error.message !== 'Validation failed.') {
                showWorkingTemplateFormAlert(error.message);
                showWorkingTemplateToast(error.message || 'Terjadi kesalahan.', 'danger');
            }
        } finally {
            setTemplateButtonLoading(button, false);
        }
    }

    async function deleteWorkingTemplate() {
        if (!deleteWorkingTemplateId) {
            return;
        }

        const button = document.getElementById('confirmDeleteWorkingTemplateBtn');
        setTemplateButtonLoading(button, true);

        try {
            const response = await fetch(
                workingTemplateRoutes.destroy.replace('__ID__', deleteWorkingTemplateId),
                {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': workingTemplateCsrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                }
            );

            const result = await parseWorkingTemplateResponse(response);

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Template gagal dihapus.');
            }

            deleteWorkingTemplateModal.hide();
            showWorkingTemplateToast(
                result.message || 'Template berhasil dihapus.',
                'success'
            );

            setTimeout(() => window.location.reload(), 650);
        } catch (error) {
            showWorkingTemplateToast(
                error.message || 'Template gagal dihapus.',
                'danger'
            );
        } finally {
            setTemplateButtonLoading(button, false);
        }
    }

    function resetWorkingTemplateForm() {
        document.getElementById('workingTemplateForm').reset();

        document.querySelectorAll('.working-template-day').forEach(checkbox => {
            checkbox.checked = false;
        });

        workingTemplateFields.id.value = '';
        workingTemplateFields.is_active.checked = true;

        clearWorkingTemplateErrors();
        hideWorkingTemplateFormAlert();
    }

    function clearWorkingTemplateErrors() {
        document.querySelectorAll('#workingTemplateForm .is-invalid').forEach(field => {
            field.classList.remove('is-invalid');
        });

        document.querySelectorAll('#workingTemplateForm .invalid-feedback').forEach(error => {
            error.textContent = '';
        });
    }

    function setWorkingTemplateValidationErrors(errors) {
        Object.entries(errors).forEach(([key, messages]) => {
            const baseKey = key.split('.')[0];
            const field = workingTemplateFields[baseKey];
            const errorElement = document.getElementById(`error_${baseKey}`);
            const message = Array.isArray(messages) ? messages[0] : messages;

            field?.classList.add('is-invalid');

            if (errorElement) {
                errorElement.textContent = message;
            }
        });
    }

    function showWorkingTemplateFormAlert(message) {
        const alert = document.getElementById('workingTemplateFormAlert');
        alert.textContent = message || 'Terjadi kesalahan.';
        alert.classList.remove('d-none');
    }

    function hideWorkingTemplateFormAlert() {
        const alert = document.getElementById('workingTemplateFormAlert');
        alert.textContent = '';
        alert.classList.add('d-none');
    }

    function decodeTemplatePayload(encodedPayload) {
        try {
            const binary = atob(encodedPayload);
            const bytes = Uint8Array.from(
                binary,
                character => character.charCodeAt(0)
            );

            return JSON.parse(new TextDecoder('utf-8').decode(bytes));
        } catch (error) {
            console.error('Template payload could not be decoded.', error);
            return null;
        }
    }

    function normalizeTemplateTime(value) {
        return value ? String(value).slice(0, 5) : '';
    }

    function workingTemplateJsonHeaders() {
        return {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': workingTemplateCsrfToken,
            'X-Requested-With': 'XMLHttpRequest',
        };
    }

    async function parseWorkingTemplateResponse(response) {
        const contentType = response.headers.get('content-type') || '';

        if (!contentType.includes('application/json')) {
            throw new Error('Server mengembalikan response yang tidak valid.');
        }

        return response.json();
    }

    function setTemplateButtonLoading(button, loading) {
        if (!button) {
            return;
        }

        button.disabled = loading;
        button.querySelector('.default-text')?.classList.toggle('d-none', loading);
        button.querySelector('.loading-text')?.classList.toggle('d-none', !loading);
    }

    function showWorkingTemplateToast(message, type = 'success') {
        const container = document.getElementById('workingTemplateToastContainer');
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
