@extends('layouts.app-dashboard')

@section('title', 'Attendance Imports')

@section('content')
@php
    $statusBadgeClass = function ($status) {
        return match($status) {
            'reviewing' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
            'processing' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
            'completed' => 'bg-success-subtle text-success-emphasis border border-success-subtle',
            'failed' => 'bg-danger-subtle text-danger-emphasis border border-danger-subtle',
            'cancelled' => 'bg-dark-subtle text-dark-emphasis border border-dark-subtle',
            default => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
        };
    };

    $formatStatus = function ($status) use ($statusOptions) {
        return $statusOptions[$status] ?? ucfirst((string) $status);
    };
@endphp

<div class="container-fluid px-4 py-4">
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Human Resources</div>
                <h1 class="page-title mb-2">Attendance Imports</h1>
                <p class="page-subtitle mb-0">
                    Upload, review, and finalize employee attendance exported from Evertime.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <a href="{{ route('hr.attendance-imports.create') }}" class="btn btn-light btn-modern">
                    <i class="bi bi-file-earmark-arrow-up me-2"></i>Upload Attendance
                </a>
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

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="content-card h-100">
                <div class="content-card-body">
                    <div class="small text-muted mb-2">Total Imports</div>
                    <div class="fs-3 fw-bold text-dark">{{ number_format($summary['total'] ?? 0) }}</div>
                    <div class="small text-muted mt-2">All uploaded attendance files.</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="content-card h-100">
                <div class="content-card-body">
                    <div class="small text-muted mb-2">Needs Review</div>
                    <div class="fs-3 fw-bold text-warning">{{ number_format($summary['reviewing'] ?? 0) }}</div>
                    <div class="small text-muted mt-2">Imports awaiting HR adjustment.</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="content-card h-100">
                <div class="content-card-body">
                    <div class="small text-muted mb-2">Completed</div>
                    <div class="fs-3 fw-bold text-success">{{ number_format($summary['completed'] ?? 0) }}</div>
                    <div class="small text-muted mt-2">Already stored as final attendance.</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="content-card h-100">
                <div class="content-card-body">
                    <div class="small text-muted mb-2">Failed</div>
                    <div class="fs-3 fw-bold text-danger">{{ number_format($summary['failed'] ?? 0) }}</div>
                    <div class="small text-muted mt-2">Imports requiring recheck.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Import Filters</h5>
                <p class="content-card-subtitle mb-0">
                    Filter import history by file name, status, and upload date.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            <form method="GET" action="{{ route('hr.attendance-imports.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-lg-4">
                        <label for="search" class="form-label">File Name</label>
                        <input
                            type="text"
                            id="search"
                            name="search"
                            value="{{ $filters['search'] ?? '' }}"
                            class="form-control"
                            placeholder="Search attendance file..."
                        >
                    </div>

                    <div class="col-12 col-md-4 col-lg-2">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">All Status</option>
                            @foreach ($statusOptions as $value => $label)
                                <option value="{{ $value }}" {{ ($filters['status'] ?? '') === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-4 col-lg-2">
                        <label for="date_from" class="form-label">Uploaded From</label>
                        <input
                            type="date"
                            id="date_from"
                            name="date_from"
                            value="{{ $filters['date_from'] ?? '' }}"
                            class="form-control"
                        >
                    </div>

                    <div class="col-6 col-md-4 col-lg-2">
                        <label for="date_to" class="form-label">Uploaded To</label>
                        <input
                            type="date"
                            id="date_to"
                            name="date_to"
                            value="{{ $filters['date_to'] ?? '' }}"
                            class="form-control"
                        >
                    </div>

                    <div class="col-12 col-lg-2">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-modern flex-fill">
                                <i class="bi bi-funnel me-2"></i>Filter
                            </button>

                            <a href="{{ route('hr.attendance-imports.index') }}" class="btn btn-outline-secondary btn-modern">
                                Reset
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="content-card">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Import History</h5>
                <p class="content-card-subtitle mb-0">
                    Review file period, generated missing rows, validation status, and finalization progress.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            @if ($imports->count())
                <div class="table-responsive dropdown-safe-table">
                    <table class="table table-hover align-middle admin-table mb-0">
                        <thead>
                            <tr>
                                <th class="text-nowrap" style="width: 80px;">No</th>
                                <th class="text-nowrap">File</th>
                                <th class="text-nowrap">Period</th>
                                <th class="text-nowrap">Rows</th>
                                <th class="text-nowrap">Review</th>
                                <th class="text-nowrap">Status</th>
                                <th class="text-nowrap">Uploaded By</th>
                                <th class="text-nowrap">Uploaded At</th>
                                <th class="text-end text-nowrap" style="width: 170px;">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($imports as $attendanceImport)
                                @php
                                    $canDelete = ! in_array(
                                        $attendanceImport->status,
                                        ['processing', 'completed'],
                                        true
                                    );
                                @endphp

                                <tr>
                                    <td class="text-muted">
                                        {{ ($imports->currentPage() - 1) * $imports->perPage() + $loop->iteration }}
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ $attendanceImport->original_file_name }}
                                        </div>
                                        <div class="small text-muted">
                                            Sheet: {{ $attendanceImport->sheet_name ?: 'Attendance' }}
                                        </div>
                                    </td>

                                    <td class="text-nowrap">
                                        @if ($attendanceImport->date_from && $attendanceImport->date_to)
                                            <div class="fw-semibold text-dark">
                                                {{ $attendanceImport->date_from->format('d M Y') }}
                                            </div>
                                            <div class="small text-muted">
                                                to {{ $attendanceImport->date_to->format('d M Y') }}
                                            </div>
                                        @else
                                            <span class="text-muted">Not detected</span>
                                        @endif
                                    </td>

                                    <td class="text-nowrap">
                                        <div class="fw-semibold text-dark">
                                            {{ number_format($attendanceImport->total_rows) }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ number_format($attendanceImport->generated_rows) }} generated
                                        </div>
                                    </td>

                                    <td class="text-nowrap">
                                        <div class="fw-semibold {{ $attendanceImport->review_rows > 0 ? 'text-warning' : 'text-success' }}">
                                            {{ number_format($attendanceImport->review_rows) }} needs review
                                        </div>
                                        <div class="small text-muted">
                                            {{ number_format($attendanceImport->error_rows) }} error ·
                                            {{ number_format($attendanceImport->duplicate_rows) }} duplicate
                                        </div>
                                    </td>

                                    <td class="text-nowrap">
                                        <span class="badge rounded-pill {{ $statusBadgeClass($attendanceImport->status) }}">
                                            {{ $formatStatus($attendanceImport->status) }}
                                        </span>

                                        @if ($attendanceImport->failure_message)
                                            <div class="small text-danger mt-2">
                                                {{ \Illuminate\Support\Str::limit($attendanceImport->failure_message, 80) }}
                                            </div>
                                        @endif
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ $attendanceImport->uploader?->name ?? 'System' }}
                                        </div>
                                    </td>

                                    <td class="text-nowrap">
                                        <div class="fw-semibold text-dark">
                                            {{ $attendanceImport->created_at?->format('d M Y') }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ $attendanceImport->created_at?->format('H:i') }}
                                        </div>
                                    </td>

                                    <td class="text-end text-nowrap">
                                        <div class="d-inline-flex gap-2">
                                            <a
                                                href="{{ route('hr.attendance-imports.review', $attendanceImport) }}"
                                                class="btn btn-sm btn-outline-secondary px-3"
                                            >
                                                Review
                                            </a>

                                            @if ($canDelete)
                                                <form
                                                    method="POST"
                                                    action="{{ route('hr.attendance-imports.destroy', $attendanceImport) }}"
                                                    onsubmit="return confirm('Delete this attendance import?')"
                                                >
                                                    @csrf
                                                    @method('DELETE')

                                                    <button type="submit" class="btn btn-sm btn-outline-danger px-3">
                                                        Delete
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($imports->hasPages())
                    <div class="mt-3">
                        {{ $imports->links() }}
                    </div>
                @endif
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-file-earmark-spreadsheet"></i>
                    </div>

                    <h5 class="empty-state-title">No attendance import found</h5>
                    <p class="empty-state-text mb-0">
                        Upload the first Evertime attendance file to start reviewing employee attendance.
                    </p>

                    <div class="mt-3">
                        <a href="{{ route('hr.attendance-imports.create') }}" class="btn btn-primary btn-modern">
                            <i class="bi bi-file-earmark-arrow-up me-2"></i>Upload Attendance
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
