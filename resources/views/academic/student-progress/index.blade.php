@extends('layouts.app-dashboard')

@section('title', 'Student Progress Monitoring')

@section('content')
<div class="container-fluid px-4 py-4">

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Academic Monitoring</div>
                <h1 class="page-title mb-2">Student Progress Monitoring</h1>
                <p class="page-subtitle mb-0">
                    Pantau progress belajar student berdasarkan batch, stage, materi selesai,
                    aktivitas terakhir, dan kebutuhan follow up.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <a href="{{ route('academic.student-progress.index') }}" class="btn btn-light btn-modern">
                    <i class="bi bi-arrow-clockwise me-2"></i>Refresh
                </a>
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
                        <div class="stat-title">Students</div>
                        <div class="stat-value">{{ number_format($summary['active_students'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">Student yang masuk monitoring progress.</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-bar-chart-line-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Average Progress</div>
                        <div class="stat-value">{{ $summary['average_progress_label'] ?? '0%' }}</div>
                    </div>
                </div>
                <div class="stat-description">Rata-rata progress berdasarkan filter saat ini.</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Completed</div>
                        <div class="stat-value">{{ number_format($summary['completed_students'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">Student dengan progress minimal 95%.</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-telephone-outbound-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Need Follow Up</div>
                        <div class="stat-value">{{ number_format($summary['need_follow_up'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">Inactive 7+ hari dan progress belum aman.</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div>
                        <div class="stat-title">Not Started</div>
                        <div class="stat-value">{{ number_format($summary['not_started'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="stat-description">Student yang belum mulai akses materi.</div>
            </div>
        </div>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Progress Filter</h5>
                <p class="content-card-subtitle mb-0">
                    Filter berdasarkan batch, stage, progress, dan aktivitas terakhir.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            <form method="GET" action="{{ route('academic.student-progress.index') }}">
                <div class="d-flex flex-wrap align-items-end gap-3">
                    <div class="flex-grow-1" style="min-width: 220px;">
                        <label for="search" class="form-label small text-muted fw-semibold">Search</label>
                        <input
                            type="text"
                            id="search"
                            name="search"
                            value="{{ $filters['search'] ?? '' }}"
                            class="form-control"
                            placeholder="Nama, email, atau phone"
                        >
                    </div>

                    <div style="min-width: 190px;">
                        <label for="batch_id" class="form-label small text-muted fw-semibold">Batch</label>
                        <select id="batch_id" name="batch_id" class="form-select">
                            <option value="">All Batch</option>
                            @foreach(($batchOptions ?? []) as $batch)
                                <option value="{{ $batch->id }}" @selected((string) ($filters['batch_id'] ?? '') === (string) $batch->id)>
                                    {{ $batch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div style="min-width: 220px;">
                        <label for="stage_id" class="form-label small text-muted fw-semibold">Stage</label>
                        <select id="stage_id" name="stage_id" class="form-select">
                            <option value="">All Stage</option>
                            @foreach(($stageOptions ?? []) as $stage)
                                <option value="{{ $stage->id }}" @selected((string) ($filters['stage_id'] ?? '') === (string) $stage->id)>
                                    {{ $stage->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div style="min-width: 170px;">
                        <label for="progress_range" class="form-label small text-muted fw-semibold">Progress</label>
                        <select id="progress_range" name="progress_range" class="form-select">
                            @foreach(($progressRangeOptions ?? []) as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['progress_range'] ?? '') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div style="min-width: 180px;">
                        <label for="activity" class="form-label small text-muted fw-semibold">Activity</label>
                        <select id="activity" name="activity" class="form-select">
                            @foreach(($activityOptions ?? []) as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['activity'] ?? '') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div style="min-width: 190px;">
                        <label for="sort" class="form-label small text-muted fw-semibold">Sort</label>
                        <select id="sort" name="sort" class="form-select">
                            @foreach(($sortOptions ?? []) as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['sort'] ?? 'last_activity_desc') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-modern">
                            <i class="bi bi-search me-2"></i>Search
                        </button>

                        <a href="{{ route('academic.student-progress.index') }}" class="btn btn-secondary btn-modern">
                            <i class="bi bi-x-circle me-2"></i>Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="content-card">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Student Progress List</h5>
                <p class="content-card-subtitle mb-0">
                    Monitoring progress belajar student, jumlah materi selesai, dan aktivitas terakhir.
                </p>
            </div>

            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="small text-muted">Total</span>
                <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis border border-primary-subtle">
                    {{ number_format($students->total()) }} students
                </span>
            </div>
        </div>

        <div class="content-card-body p-0">
            @if($students->count())
                <div class="student-table-responsive dropdown-safe-table">
                    <table class="table table-hover align-middle admin-table student-admin-table mb-0">
                        <thead>
                            <tr>
                                <th class="text-nowrap" style="width: 80px;">No</th>
                                <th class="text-nowrap">Student</th>
                                <th class="text-nowrap">Batch</th>
                                <th class="text-nowrap">Program</th>
                                <th class="text-nowrap">Lessons</th>
                                <th class="text-nowrap" style="min-width: 220px;">Progress</th>
                                <th class="text-nowrap">Last Activity</th>
                                <th class="text-nowrap">Monitoring</th>
                                <th class="text-end text-nowrap" style="width: 130px;">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($students as $student)
                                @php
                                    $rowNumber = ($students->currentPage() - 1) * $students->perPage() + $loop->iteration;

                                    $studentName = $student->full_name ?? '-';
                                    $studentEmail = $student->email ?: '-';
                                    $studentPhone = $student->phone ?: '-';

                                    $progressValue = max(0, min(100, (float) ($student->overall_progress ?? 0)));

                                    $monitoringBadgeClass = match($student->monitoring_status_badge ?? 'secondary') {
                                        'success' => 'bg-success-subtle text-success-emphasis border border-success-subtle',
                                        'primary' => 'bg-primary-subtle text-primary-emphasis border border-primary-subtle',
                                        'info' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
                                        'warning' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
                                        'danger' => 'bg-danger-subtle text-danger-emphasis border border-danger-subtle',
                                        default => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
                                    };

                                    $monitoringIcon = match($student->monitoring_status ?? 'Not Started') {
                                        'Completed' => 'bi bi-check-circle-fill',
                                        'On Track' => 'bi bi-rocket-takeoff-fill',
                                        'In Progress' => 'bi bi-play-circle-fill',
                                        'At Risk' => 'bi bi-exclamation-circle-fill',
                                        'Need Follow Up' => 'bi bi-telephone-outbound-fill',
                                        default => 'bi bi-hourglass-split',
                                    };

                                    $inactiveDays = $student->inactive_days ?? null;
                                @endphp

                                <tr>
                                    <td class="text-muted">
                                        {{ $rowNumber }}
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ $studentName }}
                                        </div>

                                        <div class="small text-muted">
                                            {{ $studentEmail }}
                                        </div>

                                        <div class="small text-muted">
                                            {{ $studentPhone }}
                                        </div>
                                    </td>

                                    <td>
                                        <div
                                            class="fw-semibold text-dark text-truncate"
                                            style="max-width: 180px;"
                                            title="{{ $student->batch_names_label ?? '-' }}"
                                        >
                                            {{ $student->batch_names_label ?? '-' }}
                                        </div>
                                    </td>

                                    <td>
                                        <div
                                            class="fw-semibold text-dark text-truncate"
                                            style="max-width: 260px;"
                                            title="{{ $student->program_names_label ?? '-' }}"
                                        >
                                            {{ $student->program_names_label ?? '-' }}
                                        </div>

                                        @if(!empty($student->city))
                                            <div class="small text-muted mt-1">
                                                <i class="bi bi-geo-alt me-1"></i>{{ $student->city }}
                                            </div>
                                        @endif
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ number_format($student->completed_lessons ?? 0) }}
                                            /
                                            {{ number_format($student->total_lessons ?? 0) }}
                                            completed
                                        </div>

                                        <div class="small text-muted">
                                            {{ number_format($student->opened_lessons ?? 0) }} opened lessons
                                        </div>
                                    </td>

                                    <td>
                                        <div class="d-flex justify-content-between align-items-center gap-3 mb-2">
                                            <div class="fw-semibold text-dark">
                                                {{ $student->overall_progress_label ?? '0%' }}
                                            </div>
                                            <div class="small text-muted">
                                                Progress
                                            </div>
                                        </div>

                                        <div class="progress" style="height: 9px; border-radius: 999px;">
                                            <div
                                                class="progress-bar"
                                                role="progressbar"
                                                style="width: {{ $progressValue }}%; border-radius: 999px;"
                                                aria-valuenow="{{ $progressValue }}"
                                                aria-valuemin="0"
                                                aria-valuemax="100"
                                            ></div>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ $student->last_activity_label ?? '-' }}
                                        </div>

                                        <div class="small text-muted">
                                            @if(is_null($inactiveDays))
                                                No activity yet
                                            @elseif((int) $inactiveDays === 0)
                                                Active today
                                            @else
                                                Inactive {{ (int) $inactiveDays }} days
                                            @endif
                                        </div>
                                    </td>

                                    <td>
                                        <span class="badge rounded-pill {{ $monitoringBadgeClass }}">
                                            <i class="{{ $monitoringIcon }} me-1"></i>
                                            {{ $student->monitoring_status ?? 'Not Started' }}
                                        </span>
                                    </td>

                                    <td class="text-end">
                                        <a
                                            href="{{ route('academic.student-progress.show', $student->id) }}"
                                            class="btn btn-sm btn-primary btn-modern"
                                        >
                                            <i class="bi bi-eye me-1"></i>Detail
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap p-3 border-top">
                    <div class="small text-muted">
                        Showing
                        <strong>{{ $students->firstItem() }}</strong>
                        to
                        <strong>{{ $students->lastItem() }}</strong>
                        of
                        <strong>{{ $students->total() }}</strong>
                        students
                    </div>

                    <div>
                        {{ $students->links() }}
                    </div>
                </div>
            @else
                <div class="text-center py-5 px-3">
                    <div class="mb-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary" style="width: 64px; height: 64px;">
                            <i class="bi bi-search fs-3"></i>
                        </span>
                    </div>

                    <h5 class="fw-semibold text-dark mb-2">
                        Data progress belum ditemukan
                    </h5>

                    <p class="text-muted mb-4">
                        Belum ada student yang sesuai dengan filter saat ini.
                    </p>

                    <a href="{{ route('academic.student-progress.index') }}" class="btn btn-secondary btn-modern">
                        <i class="bi bi-arrow-clockwise me-2"></i>Reset Filter
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection