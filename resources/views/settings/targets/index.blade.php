@extends('layouts.app-dashboard')

@section('title', 'Monthly Targets')

@section('content')
@php
    $formatTargetValue = static function ($value, ?string $unit): string {
        $numericValue = (float) $value;

        return match ($unit) {
            'currency' => 'Rp ' . number_format($numericValue, 0, ',', '.'),
            'percentage' => number_format($numericValue, 2, ',', '.') . '%',
            'decimal' => rtrim(rtrim(number_format($numericValue, 4, ',', '.'), '0'), ','),
            default => number_format($numericValue, 0, ',', '.'),
        };
    };

    $statusClasses = [
        'draft' => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
        'active' => 'bg-success-subtle text-success-emphasis border border-success-subtle',
        'locked' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
    ];

    $statusIcons = [
        'draft' => 'bi-file-earmark-text',
        'active' => 'bi-check-circle',
        'locked' => 'bi-lock',
    ];
@endphp

<div class="container-fluid px-4 py-4">
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Business Planning</div>
                <h1 class="page-title mb-2">Monthly Targets</h1>
                <p class="page-subtitle mb-0">
                    Maintain target KPI bulanan sebagai dasar scorecard dan Executive Center FlexOps.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <button
                    type="button"
                    class="btn btn-light btn-modern"
                    onclick="openCopyModal()"
                >
                    <i class="bi bi-copy me-2"></i>Copy Previous Month
                </button>

                <button
                    type="button"
                    class="btn btn-light btn-modern"
                    onclick="openCreateModal()"
                >
                    <i class="bi bi-plus-lg me-2"></i>Add Target
                </button>
            </div>
        </div>
    </div>

    <div
        id="toastContainer"
        class="toast-container position-fixed top-0 end-0 p-3"
        style="z-index: 9999;"
    ></div>

    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-start gap-3">
                <i class="bi bi-exclamation-circle-fill fs-5"></i>
                <div>
                    <div class="fw-semibold mb-1">Data belum dapat disimpan.</div>
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <div class="row g-3 mb-4" id="targetsSummary">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-bullseye"></i>
                    </div>
                    <div>
                        <div class="stat-title">Total Targets</div>
                        <div class="stat-value">{{ $summary['total'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Target pada {{ $periodMonth->translatedFormat('F Y') }} sesuai filter aktif.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap stat-icon-secondary">
                        <i class="bi bi-file-earmark-text-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Draft</div>
                        <div class="stat-value">{{ $summary['draft'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Target masih dapat ditinjau dan disesuaikan.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap stat-icon-success">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Active</div>
                        <div class="stat-value">{{ $summary['active'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Target aktif untuk monitoring periode berjalan.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap stat-icon-warning">
                        <i class="bi bi-lock-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Locked</div>
                        <div class="stat-value">{{ $summary['locked'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Target dikunci dan tidak dapat diedit atau dihapus.
                </div>
            </div>
        </div>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Target Filters</h5>
                <p class="content-card-subtitle mb-0">
                    Pilih periode dan filter target yang ingin ditampilkan.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            <form method="GET" action="{{ route('settings.targets.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-xl-2 col-md-4">
                        <label for="period" class="form-label">Period</label>
                        <input
                            type="month"
                            name="period"
                            id="period"
                            class="form-control"
                            value="{{ $period }}"
                        >
                    </div>

                    <div class="col-xl-2 col-md-4">
                        <label for="division_filter" class="form-label">Division</label>
                        <select name="division" id="division_filter" class="form-select">
                            <option value="">All Divisions</option>
                            @foreach ($divisions as $division)
                                <option
                                    value="{{ $division }}"
                                    {{ ($filters['division'] ?? null) === $division ? 'selected' : '' }}
                                >
                                    {{ ucfirst($division) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-2 col-md-4">
                        <label for="kpi_definition_filter" class="form-label">KPI</label>
                        <select
                            name="kpi_definition_id"
                            id="kpi_definition_filter"
                            class="form-select"
                        >
                            <option value="">All KPI</option>
                            @foreach ($kpiDefinitions as $kpiDefinition)
                                <option
                                    value="{{ $kpiDefinition->id }}"
                                    {{ (string) ($filters['kpi_definition_id'] ?? '') === (string) $kpiDefinition->id ? 'selected' : '' }}
                                >
                                    {{ $kpiDefinition->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-2 col-md-4">
                        <label for="scope_type_filter" class="form-label">Scope</label>
                        <select name="scope_type" id="scope_type_filter" class="form-select">
                            <option value="">All Scopes</option>
                            @foreach ($scopeOptions as $scopeValue => $scopeLabel)
                                <option
                                    value="{{ $scopeValue }}"
                                    {{ ($filters['scope_type'] ?? null) === $scopeValue ? 'selected' : '' }}
                                >
                                    {{ $scopeLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-2 col-md-4">
                        <label for="status_filter" class="form-label">Status</label>
                        <select name="status" id="status_filter" class="form-select">
                            <option value="">All Statuses</option>
                            @foreach ($statusOptions as $statusValue => $statusLabel)
                                <option
                                    value="{{ $statusValue }}"
                                    {{ ($filters['status'] ?? null) === $statusValue ? 'selected' : '' }}
                                >
                                    {{ $statusLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-2 col-md-8">
                        <label for="search_filter" class="form-label">Search</label>
                        <input
                            type="search"
                            name="search"
                            id="search_filter"
                            class="form-control"
                            value="{{ $filters['search'] ?? '' }}"
                            placeholder="KPI, scope, notes..."
                        >
                    </div>

                    <div class="col-12">
                        <div class="d-flex justify-content-end gap-2 flex-wrap">
                            <a
                                href="{{ route('settings.targets.index', ['period' => $period]) }}"
                                class="btn btn-outline-secondary btn-modern"
                            >
                                <i class="bi bi-arrow-counterclockwise me-2"></i>Reset Filters
                            </a>

                            <button type="submit" class="btn btn-primary btn-modern">
                                <i class="bi bi-funnel me-2"></i>Apply Filters
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="content-card targets-table-card" id="targetsTableCard">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">
                    Target Planning - {{ $periodMonth->translatedFormat('F Y') }}
                </h5>
                <p class="content-card-subtitle mb-0">
                    Actual KPI akan dihitung otomatis dari sumber data FlexOps pada tahap berikutnya.
                </p>
            </div>

            <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis border border-primary-subtle px-3 py-2">
                {{ $targets->count() }} target
            </span>
        </div>

        <div class="content-card-body">
            @if ($targets->isNotEmpty())
                <div class="target-table-responsive dropdown-safe-table">
                    <table class="table table-hover align-middle admin-table target-admin-table mb-0">
                        <thead>
                            <tr>
                                <th class="text-nowrap" style="width: 70px;">No</th>
                                <th class="text-nowrap col-kpi">KPI</th>
                                <th class="text-nowrap">Division</th>
                                <th class="text-nowrap col-scope">Scope</th>
                                <th class="text-nowrap col-target">Target</th>
                                <th class="text-nowrap">Status</th>
                                <th class="text-end text-nowrap" style="width: 140px;">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($targets as $target)
                                @php
                                    $definition = $target->kpiDefinition;
                                    $targetStatusClass = $statusClasses[$target->status]
                                        ?? $statusClasses['draft'];
                                    $targetStatusIcon = $statusIcons[$target->status]
                                        ?? $statusIcons['draft'];
                                    $scopeTitle = $target->scope_label
                                        ?: ($scopeOptions[$target->scope_type] ?? ucfirst($target->scope_type));
                                    $scopeDetail = $target->scope_type === 'company'
                                        ? 'Company-wide'
                                        : $target->scope_identifier;
                                    $editPayload = [
                                        'id' => $target->id,
                                        'kpi_definition_id' => $target->kpi_definition_id,
                                        'period_month' => $target->period_month?->format('Y-m'),
                                        'target_value' => $target->target_value,
                                        'status' => $target->status,
                                        'notes' => $target->notes,
                                        'is_locked' => $target->isLocked(),
                                        'kpi_name' => $definition?->name ?? 'Deleted KPI',
                                    ];
                                @endphp

                                <tr>
                                    <td class="text-muted">{{ $loop->iteration }}</td>

                                    <td>
                                        <div class="d-flex align-items-start gap-3">
                                            <div class="table-icon-wrap">
                                                <i class="bi bi-graph-up-arrow"></i>
                                            </div>
                                            <div>
                                                <div class="fw-semibold text-dark">
                                                    {{ $definition?->name ?? 'Deleted KPI' }}
                                                </div>
                                                <div class="small text-muted">
                                                    {{ $definition?->code ?? '-' }}
                                                </div>
                                                @if ($definition?->description)
                                                    <div
                                                        class="small text-muted text-truncate mt-1"
                                                        style="max-width: 280px;"
                                                        title="{{ $definition->description }}"
                                                    >
                                                        {{ $definition->description }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <span class="division-badge">
                                            {{ ucfirst($definition?->division ?? 'company') }}
                                        </span>
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark">{{ $scopeTitle }}</div>
                                        <div class="small text-muted">
                                            {{ $scopeOptions[$target->scope_type] ?? ucfirst($target->scope_type) }}
                                            · {{ $scopeDetail }}
                                        </div>
                                        @if ($target->sourceTarget)
                                            <div class="small text-muted mt-1">
                                                <i class="bi bi-copy me-1"></i>
                                                Copied from {{ $target->sourceTarget->period_month?->format('M Y') }}
                                            </div>
                                        @endif
                                    </td>

                                    <td>
                                        <div class="target-value">
                                            {{ $formatTargetValue($target->target_value, $definition?->unit) }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ ucfirst($definition?->unit ?? 'number') }}
                                            · {{ ucfirst($definition?->direction ?? 'higher') }} is better
                                        </div>
                                    </td>

                                    <td class="text-nowrap">
                                        <span class="badge rounded-pill {{ $targetStatusClass }}">
                                            <i class="bi {{ $targetStatusIcon }} me-1"></i>
                                            {{ $statusOptions[$target->status] ?? ucfirst($target->status) }}
                                        </span>

                                        @if ($target->isLocked() && $target->locked_at)
                                            <div class="small text-muted mt-1">
                                                {{ $target->locked_at->format('d M Y, H:i') }}
                                            </div>
                                        @endif
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
                                                        class="dropdown-item {{ $target->isLocked() ? 'disabled' : '' }}"
                                                        {{ $target->isLocked() ? 'disabled' : '' }}
                                                        onclick="openEditModal(@js($editPayload))"
                                                    >
                                                        <i class="bi bi-pencil-square me-2"></i>Edit Target
                                                    </button>
                                                </li>

                                                <li>
                                                    <button
                                                        type="button"
                                                        class="dropdown-item"
                                                        onclick="openStatusModal(@js($editPayload))"
                                                    >
                                                        <i class="bi bi-arrow-repeat me-2"></i>Change Status
                                                    </button>
                                                </li>

                                                <li>
                                                    <button
                                                        type="button"
                                                        class="dropdown-item"
                                                        onclick="openHistoryModal(
                                                            {{ $target->id }},
                                                            @js($definition?->name ?? 'Deleted KPI')
                                                        )"
                                                    >
                                                        <i class="bi bi-clock-history me-2"></i>View History
                                                    </button>
                                                </li>

                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button
                                                        type="button"
                                                        class="dropdown-item text-danger {{ $target->isLocked() ? 'disabled' : '' }}"
                                                        {{ $target->isLocked() ? 'disabled' : '' }}
                                                        onclick="openDeleteModal(
                                                            {{ $target->id }},
                                                            @js($definition?->name ?? 'Deleted KPI')
                                                        )"
                                                    >
                                                        <i class="bi bi-trash me-2"></i>Delete Target
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
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-bullseye"></i>
                    </div>

                    <h5 class="empty-state-title">No targets found</h5>
                    <p class="empty-state-text mb-3">
                        Belum ada target yang sesuai dengan periode dan filter yang dipilih.
                    </p>

                    <button
                        type="button"
                        class="btn btn-primary btn-modern"
                        onclick="openCreateModal()"
                    >
                        <i class="bi bi-plus-lg me-2"></i>Add First Target
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Create / Edit Target Modal --}}
<div class="modal fade" id="targetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable target-modal-dialog">
        <form
            id="targetForm"
            method="POST"
            action="{{ route('settings.targets.store') }}"
        >
            @csrf
            <input type="hidden" name="_method" id="targetFormMethod" value="PATCH" disabled>

            <div class="modal-content custom-modal">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title" id="targetModalTitle">Add Monthly Target</h5>
                        <p class="text-muted mb-0" id="targetModalSubtitle">
                            Set target KPI bulanan. Scope ditentukan otomatis dari KPI.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                    ></button>
                </div>

                <div class="modal-body pt-4">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label for="target_kpi_definition_id" class="form-label">
                                KPI <span class="text-danger">*</span>
                            </label>
                            <select
                                name="kpi_definition_id"
                                id="target_kpi_definition_id"
                                class="form-select"
                                required
                            >
                                <option value="">Select KPI</option>
                                @foreach ($kpiDefinitions as $kpiDefinition)
                                    @php
                                        $resolvedScope = $kpiDefinition->resolveTargetScope();
                                    @endphp
                                    <option
                                        value="{{ $kpiDefinition->id }}"
                                        data-unit="{{ $kpiDefinition->unit }}"
                                        data-division="{{ $kpiDefinition->division }}"
                                        data-scope-type="{{ $resolvedScope['scope_type'] }}"
                                        data-scope-identifier="{{ $resolvedScope['scope_identifier'] }}"
                                        data-scope-label="{{ $resolvedScope['scope_label'] }}"
                                    >
                                        {{ $kpiDefinition->name }}
                                        · {{ ucfirst($kpiDefinition->division ?? 'company') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-5">
                            <label for="target_period_month" class="form-label">
                                Period <span class="text-danger">*</span>
                            </label>
                            <input
                                type="month"
                                name="period_month"
                                id="target_period_month"
                                class="form-control"
                                value="{{ $period }}"
                                required
                            >
                        </div>

                        <div class="col-12">
                            <div class="target-scope-preview">
                                <div class="target-scope-preview-header">
                                    <div class="target-scope-preview-icon">
                                        <i class="bi bi-diagram-3"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold text-dark">Target Scope</div>
                                        <div class="small text-muted">
                                            Automatically determined from the selected KPI.
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-3 mt-0">
                                    <div class="col-md-4">
                                        <label for="target_scope_type_display" class="form-label">
                                            Scope Type
                                        </label>
                                        <input
                                            type="text"
                                            id="target_scope_type_display"
                                            class="form-control scope-readonly-input"
                                            value="-"
                                            readonly
                                        >
                                    </div>

                                    <div class="col-md-4">
                                        <label for="target_scope_identifier_display" class="form-label">
                                            Scope Identifier
                                        </label>
                                        <input
                                            type="text"
                                            id="target_scope_identifier_display"
                                            class="form-control scope-readonly-input"
                                            value="-"
                                            readonly
                                        >
                                    </div>

                                    <div class="col-md-4">
                                        <label for="target_scope_label_display" class="form-label">
                                            Scope Label
                                        </label>
                                        <input
                                            type="text"
                                            id="target_scope_label_display"
                                            class="form-control scope-readonly-input"
                                            value="-"
                                            readonly
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="target_target_value" class="form-label">
                                Target Value <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text" id="targetUnitLabel">Value</span>
                                <input
                                    type="number"
                                    name="target_value"
                                    id="target_target_value"
                                    class="form-control"
                                    min="0"
                                    step="0.0001"
                                    placeholder="0"
                                    required
                                >
                            </div>
                            <div class="form-text">
                                Gunakan angka tanpa pemisah ribuan. Contoh: 100000000.
                            </div>
                        </div>

                        <div class="col-md-6" id="createStatusField">
                            <label for="target_status" class="form-label">
                                Initial Status <span class="text-danger">*</span>
                            </label>
                            <select name="status" id="target_status" class="form-select">
                                @foreach ($statusOptions as $statusValue => $statusLabel)
                                    <option value="{{ $statusValue }}">{{ $statusLabel }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <label for="target_notes" class="form-label">
                                Target Notes <span class="text-muted">(Optional)</span>
                            </label>
                            <textarea
                                name="notes"
                                id="target_notes"
                                rows="3"
                                class="form-control"
                                placeholder="Tambahkan konteks atau asumsi penyusunan target."
                            ></textarea>
                        </div>

                        <div class="col-12">
                            <label for="target_history_notes" class="form-label">
                                Change Reason <span class="text-muted">(Optional)</span>
                            </label>
                            <textarea
                                name="history_notes"
                                id="target_history_notes"
                                rows="2"
                                class="form-control"
                                placeholder="Alasan penambahan atau perubahan untuk audit history."
                            ></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button
                        type="button"
                        class="btn btn-outline-secondary btn-modern"
                        data-bs-dismiss="modal"
                    >
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-primary btn-modern">
                        <i class="bi bi-check-lg me-2"></i>
                        <span id="targetSubmitLabel">Save Target</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Copy Previous Month Modal --}}
<div class="modal fade" id="copyTargetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable target-modal-dialog">
        <form
            id="copyTargetForm"
            method="POST"
            action="{{ route('settings.targets.copy-previous-month') }}"
        >
            @csrf

            <div class="modal-content custom-modal">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title">Copy Previous Month</h5>
                        <p class="text-muted mb-0">
                            Salin target bulan sebelumnya sebagai draft.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                    ></button>
                </div>

                <div class="modal-body pt-4">
                    <div class="copy-info-box mb-3">
                        <div class="copy-info-icon">
                            <i class="bi bi-info-circle"></i>
                        </div>
                        <div>
                            Target yang sudah tersedia pada periode tujuan akan dilewati.
                            Target yang pernah dihapus akan direstore jika kombinasinya sama.
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="copy_period_month" class="form-label">
                                Destination Period <span class="text-danger">*</span>
                            </label>
                            <input
                                type="month"
                                name="period_month"
                                id="copy_period_month"
                                class="form-control"
                                value="{{ $period }}"
                                required
                            >
                        </div>

                        <div class="col-12">
                            <label for="copy_history_notes" class="form-label">
                                Copy Notes <span class="text-muted">(Optional)</span>
                            </label>
                            <textarea
                                name="history_notes"
                                id="copy_history_notes"
                                rows="3"
                                class="form-control"
                                placeholder="Contoh: Baseline target dari bulan sebelumnya."
                            ></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button
                        type="button"
                        class="btn btn-outline-secondary btn-modern"
                        data-bs-dismiss="modal"
                    >
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary btn-modern">
                        <i class="bi bi-copy me-2"></i>Copy Targets
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Change Status Modal --}}
<div class="modal fade" id="statusTargetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable target-modal-dialog">
        <form id="statusTargetForm" method="POST">
            @csrf
            @method('PATCH')

            <div class="modal-content custom-modal">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title">Change Target Status</h5>
                        <p class="text-muted mb-0" id="statusTargetSubtitle">
                            Select the new target status.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                    ></button>
                </div>

                <div class="modal-body pt-4">
                    <div class="status-warning-box mb-3">
                        <i class="bi bi-lock-fill"></i>
                        <div>
                            Status <strong>Locked</strong> mencegah target diedit atau dihapus.
                            Ubah kembali ke Draft atau Active untuk membuka target.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="status_new_value" class="form-label">
                            New Status <span class="text-danger">*</span>
                        </label>
                        <select
                            name="status"
                            id="status_new_value"
                            class="form-select"
                            required
                        >
                            @foreach ($statusOptions as $statusValue => $statusLabel)
                                <option value="{{ $statusValue }}">{{ $statusLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="status_history_notes" class="form-label">
                            Change Reason <span class="text-muted">(Optional)</span>
                        </label>
                        <textarea
                            name="history_notes"
                            id="status_history_notes"
                            rows="3"
                            class="form-control"
                            placeholder="Alasan perubahan status untuk audit history."
                        ></textarea>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button
                        type="button"
                        class="btn btn-outline-secondary btn-modern"
                        data-bs-dismiss="modal"
                    >
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary btn-modern">
                        <i class="bi bi-arrow-repeat me-2"></i>Update Status
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Target History Modal --}}
<div class="modal fade" id="historyTargetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable target-modal-dialog">
        <div class="modal-content custom-modal">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title">Target Change History</h5>
                    <p class="text-muted mb-0" id="historyTargetSubtitle">
                        Audit trail perubahan target.
                    </p>
                </div>
                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close"
                ></button>
            </div>

            <div class="modal-body pt-4">
                <div id="historyLoading" class="history-loading">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="text-muted mt-3">Loading target history...</div>
                </div>

                <div id="historyError" class="alert alert-danger d-none mb-0"></div>
                <div id="historyTimeline" class="history-timeline d-none"></div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button
                    type="button"
                    class="btn btn-outline-secondary btn-modern"
                    data-bs-dismiss="modal"
                >
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Delete Target Modal --}}
<div class="modal fade" id="deleteTargetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable target-modal-dialog">
        <form id="deleteTargetForm" method="POST">
            @csrf
            @method('DELETE')

            <input
                type="hidden"
                name="history_notes"
                value="Target dihapus melalui halaman Monthly Targets."
            >

            <div class="modal-content custom-modal delete-confirm-modal">
                <div class="modal-header border-0 pb-0">
                    <div class="d-flex align-items-center gap-3">
                        <div class="delete-confirm-icon">
                            <i class="bi bi-trash"></i>
                        </div>
                        <div>
                            <h5 class="modal-title">Delete Target</h5>
                            <p class="text-muted mb-0">Confirm target deletion.</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                    ></button>
                </div>

                <div class="modal-body pt-4">
                    <p class="mb-2">
                        Are you sure you want to delete target
                        <strong id="deleteTargetName">-</strong>?
                    </p>

                    <div class="delete-confirm-warning mt-3">
                        Target akan dihapus secara soft delete dan riwayat perubahan tetap tercatat.
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button
                        type="button"
                        class="btn btn-outline-secondary btn-modern"
                        data-bs-dismiss="modal"
                    >
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-danger btn-modern">
                        <i class="bi bi-trash me-2"></i>Delete Target
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
<style>
    .targets-table-card,
    .targets-table-card .content-card-body {
        min-width: 0;
        max-width: 100%;
        overflow: hidden;
    }

    .target-table-responsive {
        display: block;
        width: 100%;
        max-width: 100%;
        overflow-x: auto !important;
        overflow-y: visible;
        -webkit-overflow-scrolling: touch;
        overscroll-behavior-x: contain;
    }

    .target-admin-table {
        width: 100%;
        min-width: 1040px;
        table-layout: auto;
    }

    .target-admin-table th,
    .target-admin-table td {
        vertical-align: middle;
    }

    .target-admin-table .col-kpi {
        min-width: 300px;
    }

    .target-admin-table .col-scope {
        min-width: 200px;
    }

    .target-admin-table .col-target {
        min-width: 190px;
    }

    .target-admin-table .dropdown-menu {
        z-index: 1080;
    }

    .stat-icon-secondary {
        color: #667085 !important;
        background: #f2f4f7 !important;
    }

    .stat-icon-success {
        color: #198754 !important;
        background: #eaf7ef !important;
    }

    .stat-icon-warning {
        color: #b54708 !important;
        background: #fff4e5 !important;
    }

    .table-icon-wrap {
        width: 40px;
        height: 40px;
        min-width: 40px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        color: #5b3e8e;
        background: #f1ecf8;
        font-size: 1rem;
    }

    .division-badge {
        display: inline-flex;
        align-items: center;
        padding: .4rem .7rem;
        border-radius: 999px;
        color: #475467;
        background: #f2f4f7;
        border: 1px solid #eaecf0;
        font-size: .78rem;
        font-weight: 600;
    }

    .target-value {
        color: #5b3e8e;
        font-size: 1rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .target-modal-dialog {
        max-height: calc(100vh - 48px);
        max-height: calc(100dvh - 48px);
        margin-top: 24px;
        margin-bottom: 24px;
    }

    /*
     * Most action modals wrap .modal-content inside a form. Bootstrap's
     * modal-dialog-scrollable styles expect .modal-content to be a direct
     * child, so the height and flex rules are repeated here for that markup.
     */
    .target-modal-dialog > form {
        display: flex;
        width: 100%;
        min-height: 0;
        max-height: calc(100vh - 48px);
        max-height: calc(100dvh - 48px);
    }

    .target-modal-dialog .modal-content {
        display: flex;
        flex-direction: column;
        width: 100%;
        min-height: 0;
        max-height: calc(100vh - 48px);
        max-height: calc(100dvh - 48px);
        overflow: hidden;
    }

    .target-modal-dialog .modal-header,
    .target-modal-dialog .modal-footer {
        flex: 0 0 auto;
    }

    .target-modal-dialog .modal-body {
        min-height: 0;
        overflow-x: hidden;
        overflow-y: auto;
        overscroll-behavior: contain;
        scrollbar-gutter: stable;
        -webkit-overflow-scrolling: touch;
    }

    .target-scope-preview {
        padding: 1rem;
        border: 1px solid #e7ddf3;
        border-radius: 14px;
        background: #fbf9fe;
    }

    .target-scope-preview-header {
        display: flex;
        align-items: center;
        gap: .75rem;
        margin-bottom: .25rem;
    }

    .target-scope-preview-icon {
        width: 38px;
        height: 38px;
        min-width: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 11px;
        color: #5b3e8e;
        background: #f1ecf8;
    }

    .scope-readonly-input[readonly] {
        color: #344054;
        background: #fff;
        border-color: #e4e7ec;
        cursor: default;
    }

    .copy-info-box,
    .status-warning-box {
        display: flex;
        align-items: flex-start;
        gap: .75rem;
        padding: 1rem;
        border-radius: 12px;
        font-size: .9rem;
        line-height: 1.55;
    }

    .copy-info-box {
        color: #344054;
        background: #f8f5fc;
        border: 1px solid #e7ddf3;
    }

    .copy-info-icon {
        color: #5b3e8e;
        font-size: 1rem;
    }

    .status-warning-box {
        color: #7a2e0e;
        background: #fff8eb;
        border: 1px solid #fedf89;
    }

    .history-loading {
        min-height: 220px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .history-timeline {
        position: relative;
    }

    .history-item {
        position: relative;
        display: grid;
        grid-template-columns: 42px minmax(0, 1fr);
        gap: 1rem;
        padding-bottom: 1.25rem;
    }

    .history-item:not(:last-child)::before {
        content: "";
        position: absolute;
        top: 42px;
        bottom: 0;
        left: 20px;
        width: 2px;
        background: #eaecf0;
    }

    .history-item-icon {
        position: relative;
        z-index: 1;
        width: 42px;
        height: 42px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        color: #5b3e8e;
        background: #f1ecf8;
        border: 4px solid #fff;
        box-shadow: 0 0 0 1px #e7ddf3;
    }

    .history-item-card {
        padding: 1rem;
        border: 1px solid #eaecf0;
        border-radius: 14px;
        background: #fff;
    }

    .history-action {
        font-weight: 700;
        color: #101828;
    }

    .history-meta {
        color: #667085;
        font-size: .8rem;
    }

    .history-notes {
        margin-top: .65rem;
        padding: .7rem .8rem;
        color: #475467;
        background: #f9fafb;
        border-radius: 10px;
        font-size: .85rem;
    }

    .history-change-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .75rem;
        margin-top: .75rem;
    }

    .history-change-box {
        min-width: 0;
        padding: .75rem;
        border-radius: 10px;
        background: #f9fafb;
        border: 1px solid #f2f4f7;
    }

    .history-change-label {
        margin-bottom: .4rem;
        color: #667085;
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .history-json {
        margin: 0;
        color: #344054;
        font-family: inherit;
        font-size: .8rem;
        line-height: 1.5;
        white-space: pre-wrap;
        word-break: break-word;
    }

    @media (max-width: 768px) {
        .container-fluid.px-4 {
            padding-left: 1rem !important;
            padding-right: 1rem !important;
        }

        .target-modal-dialog {
            max-height: calc(100vh - 24px);
            max-height: calc(100dvh - 24px);
            margin: 12px;
        }

        .target-modal-dialog > form,
        .target-modal-dialog .modal-content {
            max-height: calc(100vh - 24px);
            max-height: calc(100dvh - 24px);
        }

        .content-card-header {
            align-items: flex-start;
            gap: 1rem;
        }

        .target-admin-table {
            min-width: 980px;
        }

        .history-change-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@push('scripts')
<script>
let targetModal;
let copyTargetModal;
let statusTargetModal;
let historyTargetModal;
let deleteTargetModal;

const targetStoreUrl = @js(route('settings.targets.store'));
const targetUpdateUrlTemplate = @js(route('settings.targets.patch', ['target' => '__TARGET__']));
const targetStatusUrlTemplate = @js(route('settings.targets.status.update', ['target' => '__TARGET__']));
const targetHistoryUrlTemplate = @js(route('settings.targets.history', ['target' => '__TARGET__']));
const targetDeleteUrlTemplate = @js(route('settings.targets.destroy', ['target' => '__TARGET__']));
let currentTargetPeriod = @js($period);

document.addEventListener('DOMContentLoaded', function () {
    targetModal = new bootstrap.Modal(document.getElementById('targetModal'));
    copyTargetModal = new bootstrap.Modal(document.getElementById('copyTargetModal'));
    statusTargetModal = new bootstrap.Modal(document.getElementById('statusTargetModal'));
    historyTargetModal = new bootstrap.Modal(document.getElementById('historyTargetModal'));
    deleteTargetModal = new bootstrap.Modal(document.getElementById('deleteTargetModal'));

    document
        .getElementById('target_kpi_definition_id')
        .addEventListener('change', updateTargetPresentation);

    document
        .getElementById('targetForm')
        .addEventListener('submit', function (event) {
            submitTargetForm(event, targetModal);
        });

    document
        .getElementById('copyTargetForm')
        .addEventListener('submit', function (event) {
            submitTargetForm(event, copyTargetModal);
        });

    document
        .getElementById('statusTargetForm')
        .addEventListener('submit', function (event) {
            submitTargetForm(event, statusTargetModal);
        });

    document
        .getElementById('deleteTargetForm')
        .addEventListener('submit', function (event) {
            submitTargetForm(event, deleteTargetModal);
        });

    @if (session('success'))
        showToast(@js(session('success')));
    @endif
});

function buildTargetUrl(template, targetId) {
    return template.replace('__TARGET__', String(targetId));
}

function openCreateModal() {
    const form = document.getElementById('targetForm');
    const methodInput = document.getElementById('targetFormMethod');
    const statusField = document.getElementById('createStatusField');
    const statusInput = document.getElementById('target_status');

    form.reset();
    form.action = targetStoreUrl;
    methodInput.disabled = true;

    document.getElementById('targetModalTitle').innerText = 'Add Monthly Target';
    document.getElementById('targetModalSubtitle').innerText =
        'Set target KPI bulanan. Scope ditentukan otomatis dari KPI.';
    document.getElementById('targetSubmitLabel').innerText = 'Save Target';
    document.getElementById('target_period_month').value = currentTargetPeriod;
    document.getElementById('target_status').value = 'draft';

    statusField.classList.remove('d-none');
    statusInput.disabled = false;

    updateTargetPresentation();
    targetModal.show();
}

function openEditModal(target) {
    if (target.is_locked) {
        showToast('Target locked tidak dapat diedit.', 'error');
        return;
    }

    const form = document.getElementById('targetForm');
    const methodInput = document.getElementById('targetFormMethod');
    const statusField = document.getElementById('createStatusField');
    const statusInput = document.getElementById('target_status');

    form.reset();
    form.action = buildTargetUrl(targetUpdateUrlTemplate, target.id);
    methodInput.disabled = false;
    methodInput.value = 'PATCH';

    document.getElementById('targetModalTitle').innerText = 'Edit Monthly Target';
    document.getElementById('targetModalSubtitle').innerText =
        `Update ${target.kpi_name || 'selected KPI'} target.`;
    document.getElementById('targetSubmitLabel').innerText = 'Update Target';
    document.getElementById('target_kpi_definition_id').value =
        target.kpi_definition_id ?? '';
    document.getElementById('target_period_month').value =
        target.period_month ?? currentTargetPeriod;
    document.getElementById('target_target_value').value =
        target.target_value ?? '';
    document.getElementById('target_notes').value =
        target.notes ?? '';
    document.getElementById('target_history_notes').value = '';

    statusField.classList.add('d-none');
    statusInput.disabled = true;

    updateTargetPresentation();
    targetModal.show();
}

function updateTargetPresentation() {
    const select = document.getElementById('target_kpi_definition_id');
    const selectedOption = select.options[select.selectedIndex];
    const unit = selectedOption?.dataset?.unit || 'value';
    const scopeType = selectedOption?.dataset?.scopeType || '';
    const scopeIdentifier = selectedOption?.dataset?.scopeIdentifier || '';
    const scopeLabel = selectedOption?.dataset?.scopeLabel || '';
    const unitLabels = {
        currency: 'Rp',
        number: 'Number',
        percentage: '%',
        decimal: 'Decimal',
    };
    const scopeTypeLabels = {
        company: 'Company',
        division: 'Division',
    };

    document.getElementById('targetUnitLabel').innerText =
        unitLabels[unit] || 'Value';
    document.getElementById('target_scope_type_display').value =
        scopeTypeLabels[scopeType] || '-';
    document.getElementById('target_scope_identifier_display').value =
        scopeIdentifier || '-';
    document.getElementById('target_scope_label_display').value =
        scopeLabel || '-';
}

function openCopyModal() {
    document.getElementById('copy_period_month').value = currentTargetPeriod;
    document.getElementById('copy_history_notes').value = '';
    copyTargetModal.show();
}

function openStatusModal(target) {
    const form = document.getElementById('statusTargetForm');

    form.action = buildTargetUrl(targetStatusUrlTemplate, target.id);
    document.getElementById('statusTargetSubtitle').innerText =
        `${target.kpi_name || 'Selected KPI'} is currently ${target.status}.`;
    document.getElementById('status_new_value').value = target.status;
    document.getElementById('status_history_notes').value = '';

    statusTargetModal.show();
}

async function openHistoryModal(targetId, targetName) {
    const loading = document.getElementById('historyLoading');
    const errorBox = document.getElementById('historyError');
    const timeline = document.getElementById('historyTimeline');

    document.getElementById('historyTargetSubtitle').innerText =
        `Audit trail for ${targetName}.`;
    loading.classList.remove('d-none');
    errorBox.classList.add('d-none');
    timeline.classList.add('d-none');
    timeline.innerHTML = '';
    historyTargetModal.show();

    try {
        const response = await fetch(
            buildTargetUrl(targetHistoryUrlTemplate, targetId),
            {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            }
        );

        const result = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(result.message || 'Failed to load target history.');
        }

        renderHistory(result.histories || []);
        loading.classList.add('d-none');
        timeline.classList.remove('d-none');
    } catch (error) {
        loading.classList.add('d-none');
        errorBox.innerText = error.message || 'Failed to load target history.';
        errorBox.classList.remove('d-none');
    }
}

function renderHistory(histories) {
    const timeline = document.getElementById('historyTimeline');

    if (!histories.length) {
        timeline.innerHTML = `
            <div class="empty-state-box py-4">
                <div class="empty-state-icon">
                    <i class="bi bi-clock-history"></i>
                </div>
                <h5 class="empty-state-title">No history found</h5>
                <p class="empty-state-text mb-0">
                    Belum ada riwayat perubahan untuk target ini.
                </p>
            </div>
        `;
        return;
    }

    timeline.innerHTML = histories.map(function (history) {
        const changedBy = history.changed_by;
        const actor = changedBy && typeof changedBy === 'object'
            ? (
                changedBy.name
                || changedBy.email
                || `User #${changedBy.id ?? '-'}`
            )
            : (changedBy ? `User #${changedBy}` : 'System');
        const action = formatAction(history.action);
        const date = formatDateTime(history.created_at);
        const notes = history.notes
            ? `<div class="history-notes">${escapeHtml(history.notes)}</div>`
            : '';
        const changes = renderHistoryChanges(
            history.old_values,
            history.new_values
        );

        return `
            <div class="history-item">
                <div class="history-item-icon">
                    <i class="bi ${historyActionIcon(history.action)}"></i>
                </div>
                <div class="history-item-card">
                    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                        <div class="history-action">${escapeHtml(action)}</div>
                        <div class="history-meta">${escapeHtml(date)}</div>
                    </div>
                    <div class="history-meta mt-1">
                        Changed by ${escapeHtml(actor)}
                    </div>
                    ${notes}
                    ${changes}
                </div>
            </div>
        `;
    }).join('');
}

function renderHistoryChanges(oldValues, newValues) {
    if (!oldValues && !newValues) {
        return '';
    }

    const oldBox = oldValues
        ? `
            <div class="history-change-box">
                <div class="history-change-label">Before</div>
                <pre class="history-json">${escapeHtml(formatJson(oldValues))}</pre>
            </div>
        `
        : '';

    const newBox = newValues
        ? `
            <div class="history-change-box">
                <div class="history-change-label">After</div>
                <pre class="history-json">${escapeHtml(formatJson(newValues))}</pre>
            </div>
        `
        : '';

    return `<div class="history-change-grid">${oldBox}${newBox}</div>`;
}

function formatJson(value) {
    if (typeof value === 'string') {
        try {
            return JSON.stringify(JSON.parse(value), null, 2);
        } catch (error) {
            return value;
        }
    }

    return JSON.stringify(value, null, 2);
}

function formatAction(action) {
    return String(action || 'updated')
        .replaceAll('_', ' ')
        .replace(/\b\w/g, character => character.toUpperCase());
}

function historyActionIcon(action) {
    const icons = {
        created: 'bi-plus-lg',
        updated: 'bi-pencil',
        copied: 'bi-copy',
        status_changed: 'bi-arrow-repeat',
        locked: 'bi-lock',
        unlocked: 'bi-unlock',
        deleted: 'bi-trash',
        restored: 'bi-arrow-counterclockwise',
    };

    return icons[action] || 'bi-clock-history';
}

function formatDateTime(value) {
    if (!value) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

function openDeleteModal(targetId, targetName) {
    const form = document.getElementById('deleteTargetForm');

    form.action = buildTargetUrl(targetDeleteUrlTemplate, targetId);
    document.getElementById('deleteTargetName').innerText = targetName;
    deleteTargetModal.show();
}

async function submitTargetForm(event, modalInstance) {
    event.preventDefault();

    const form = event.currentTarget;

    if (!form.reportValidity()) {
        return;
    }

    clearFormErrors(form);

    const submitButton = form.querySelector('button[type="submit"]');
    const formData = new FormData(form);

    normalizeMonthFormValue(formData, 'period_month');
    setSubmitButtonLoading(submitButton, true);

    try {
        const response = await fetch(form.action, {
            method: (form.method || 'POST').toUpperCase(),
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: formData,
            credentials: 'same-origin',
        });

        const result = await parseJsonResponse(response);

        if (!response.ok) {
            if (response.status === 422 && result.errors) {
                displayFormErrors(form, result.errors);
            }

            throw new Error(
                firstResponseError(result)
                || result.message
                || 'Data target belum dapat disimpan.'
            );
        }

        modalInstance?.hide();
        showToast(result.message || 'Data target berhasil disimpan.');

        const responsePeriod = resolveResponsePeriod(result);

        try {
            await refreshTargetsContent(responsePeriod);
        } catch (refreshError) {
            showToast(
                'Data berhasil disimpan, tetapi tampilan belum dapat diperbarui. Silakan refresh halaman.',
                'error'
            );
        }
    } catch (error) {
        showToast(
            error.message || 'Terjadi kesalahan saat menyimpan target.',
            'error'
        );
    } finally {
        setSubmitButtonLoading(submitButton, false);
    }
}

function normalizeMonthFormValue(formData, fieldName) {
    const value = formData.get(fieldName);

    if (typeof value === 'string' && /^\d{4}-\d{2}$/.test(value)) {
        formData.set(fieldName, `${value}-01`);
    }
}

async function parseJsonResponse(response) {
    const contentType = response.headers.get('content-type') || '';

    if (!contentType.includes('application/json')) {
        return {};
    }

    return response.json().catch(() => ({}));
}

function firstResponseError(result) {
    const errors = result?.errors;

    if (!errors || typeof errors !== 'object') {
        return null;
    }

    const firstError = Object.values(errors).flat()[0];

    return firstError ? String(firstError) : null;
}

function clearFormErrors(form) {
    form.querySelectorAll('.is-invalid').forEach(function (field) {
        field.classList.remove('is-invalid');
    });

    form.querySelectorAll('.async-field-error').forEach(function (feedback) {
        feedback.remove();
    });
}

function displayFormErrors(form, errors) {
    Object.entries(errors).forEach(function ([fieldName, messages]) {
        const normalizedName = fieldName.replace(/\.\d+(\.|$)/g, '[]$1');
        const field = Array.from(form.elements).find(function (element) {
            return element.name === fieldName || element.name === normalizedName;
        });

        if (!field) {
            return;
        }

        field.classList.add('is-invalid');

        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback d-block async-field-error';
        feedback.textContent = Array.isArray(messages)
            ? messages[0]
            : String(messages);

        const fieldContainer = field.closest('.input-group') || field;
        fieldContainer.insertAdjacentElement('afterend', feedback);
    });
}

function setSubmitButtonLoading(button, isLoading) {
    if (!button) {
        return;
    }

    if (isLoading) {
        button.dataset.originalHtml = button.innerHTML;
        button.disabled = true;
        button.innerHTML = `
            <span
                class="spinner-border spinner-border-sm me-2"
                role="status"
                aria-hidden="true"
            ></span>
            Processing...
        `;
        return;
    }

    button.disabled = false;

    if (button.dataset.originalHtml) {
        button.innerHTML = button.dataset.originalHtml;
        delete button.dataset.originalHtml;
    }
}

function resolveResponsePeriod(result) {
    const rawPeriod =
        result?.target?.period_month
        || result?.copy_result?.target_period
        || currentTargetPeriod;
    const match = String(rawPeriod || '').match(/^(\d{4}-\d{2})/);

    return match ? match[1] : currentTargetPeriod;
}

async function refreshTargetsContent(period) {
    const url = new URL(window.location.href);

    if (period) {
        currentTargetPeriod = period;
        url.searchParams.set('period', period);
    }

    const response = await fetch(url.toString(), {
        method: 'GET',
        headers: {
            'Accept': 'text/html',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        throw new Error('Failed to refresh target content.');
    }

    const html = await response.text();
    const nextDocument = new DOMParser().parseFromString(html, 'text/html');
    const currentSummary = document.getElementById('targetsSummary');
    const currentTable = document.getElementById('targetsTableCard');
    const nextSummary = nextDocument.getElementById('targetsSummary');
    const nextTable = nextDocument.getElementById('targetsTableCard');

    if (!currentSummary || !currentTable || !nextSummary || !nextTable) {
        throw new Error('Target content is incomplete.');
    }

    currentSummary.replaceWith(nextSummary);
    currentTable.replaceWith(nextTable);

    const periodFilter = document.getElementById('period');

    if (periodFilter) {
        periodFilter.value = currentTargetPeriod;
    }

    window.history.replaceState({}, '', url.toString());
}

function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    const toastElement = document.createElement('div');
    const body = document.createElement('div');
    const closeButton = document.createElement('button');

    toastElement.className =
        `toast align-items-center text-white ${type === 'success' ? 'bg-success' : 'bg-danger'} border-0`;
    toastElement.setAttribute('role', 'alert');

    body.className = 'd-flex';
    body.innerHTML = '<div class="toast-body"></div>';
    body.querySelector('.toast-body').textContent = message;

    closeButton.type = 'button';
    closeButton.className = 'btn-close btn-close-white me-2 m-auto';
    closeButton.setAttribute('data-bs-dismiss', 'toast');
    closeButton.setAttribute('aria-label', 'Close');

    body.appendChild(closeButton);
    toastElement.appendChild(body);
    container.appendChild(toastElement);

    const toast = new bootstrap.Toast(toastElement, { delay: 3000 });
    toast.show();

    toastElement.addEventListener('hidden.bs.toast', function () {
        toastElement.remove();
    });
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}
</script>
@endpush
