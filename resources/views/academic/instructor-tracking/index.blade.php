@extends('layouts.app-dashboard')

@section('title', 'Instructor Tracking')

@section('content')
<div class="container-fluid px-4 py-4">
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Academic Monitoring</div>
                <h1 class="page-title mb-2">Instructor Tracking</h1>
                <p class="page-subtitle mb-0">
                    Monitor check-in, material coverage, session notes, and check-out for each teaching session.
                </p>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap"><i class="bi bi-calendar-check"></i></div>
                    <div>
                        <div class="stat-title">Sessions</div>
                        <div class="stat-value">{{ $stats['total'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Total session sesuai filter.</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap"><i class="bi bi-box-arrow-in-right"></i></div>
                    <div>
                        <div class="stat-title">Checked In</div>
                        <div class="stat-value">{{ $stats['checked_in'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Session yang sedang berjalan.</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap"><i class="bi bi-send-check"></i></div>
                    <div>
                        <div class="stat-title">Submitted</div>
                        <div class="stat-value">{{ $stats['submitted'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Sudah check-out dan terkirim.</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap"><i class="bi bi-patch-check"></i></div>
                    <div>
                        <div class="stat-title">Reviewed</div>
                        <div class="stat-value">{{ $stats['reviewed'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Sudah direview Academic Team.</div>
            </div>
        </div>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Filter Tracking</h5>
                <p class="content-card-subtitle mb-0">Pilih batch, instructor, tanggal, atau status tracking.</p>
            </div>
        </div>
        <div class="content-card-body">
            <form method="GET" action="{{ route('instructor-tracking.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-xl-3 col-md-6">
                        <label class="form-label">Batch</label>
                        <select name="batch_id" class="form-select">
                            <option value="">All Batch</option>
                            @foreach($batches as $batch)
                                <option value="{{ $batch->id }}" {{ (string) request('batch_id') === (string) $batch->id ? 'selected' : '' }}>
                                    {{ $batch->name }}{{ $batch->program ? ' - '.$batch->program->name : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <label class="form-label">Instructor</label>
                        <select name="instructor_id" class="form-select">
                            <option value="">All Instructor</option>
                            @foreach($instructors as $instructor)
                                <option value="{{ $instructor->id }}" {{ (string) request('instructor_id') === (string) $instructor->id ? 'selected' : '' }}>
                                    {{ $instructor->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-2 col-md-6">
                        <label class="form-label">Date From</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                    </div>
                    <div class="col-xl-2 col-md-6">
                        <label class="form-label">Date To</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                    </div>
                    <div class="col-xl-2 col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            @foreach(['pending' => 'Pending', 'checked_in' => 'Checked In', 'draft' => 'Draft', 'submitted' => 'Submitted', 'reviewed' => 'Reviewed', 'returned' => 'Returned'] as $value => $label)
                                <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <div class="d-flex gap-2 justify-content-end flex-wrap">
                            <a href="{{ route('instructor-tracking.index') }}" class="btn btn-outline-secondary btn-modern">
                                <i class="bi bi-arrow-counterclockwise me-2"></i>Reset
                            </a>
                            <button type="submit" class="btn btn-primary btn-modern">
                                <i class="bi bi-funnel me-2"></i>Apply Filter
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="content-card">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Teaching Sessions</h5>
                <p class="content-card-subtitle mb-0">Buka sesi untuk check-in, checklist materi, session notes, dan check-out.</p>
            </div>
        </div>
        <div class="content-card-body">
            @if($schedules->count())
                <div class="table-responsive dropdown-safe-table">
                    <table class="table table-hover align-middle admin-table mb-0">
                        <thead>
                            <tr>
                                <th>Session</th>
                                <th>Instructor</th>
                                <th>Batch / Program</th>
                                <th class="text-nowrap">Schedule</th>
                                <th class="text-end">Coverage</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($schedules as $schedule)
                                @php
                                    $tracking = $trackingMap->get($schedule->id);
                                    $status = $tracking?->status ?? 'pending';
                                    $statusLabel = $tracking?->status_label ?? 'Pending';
                                    $badgeClass = match ($status) {
                                        'checked_in' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
                                        'draft' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
                                        'submitted' => 'bg-primary-subtle text-primary-emphasis border border-primary-subtle',
                                        'reviewed' => 'bg-success-subtle text-success-emphasis border border-success-subtle',
                                        'returned' => 'bg-danger-subtle text-danger-emphasis border border-danger-subtle',
                                        default => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
                                    };
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $schedule->title ?? $schedule->topic ?? 'Teaching Session' }}</div>
                                        <div class="small text-muted">ID: #{{ $schedule->id }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ data_get($schedule, 'instructor.name', '-') }}</div>
                                        <div class="small text-muted">{{ data_get($schedule, 'instructor.email') }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ data_get($schedule, 'batch.name', '-') }}</div>
                                        <div class="small text-muted">{{ data_get($schedule, 'batch.program.name', '-') }}</div>
                                    </td>
                                    <td class="text-nowrap">
                                        <div class="fw-semibold">{{ $schedule->schedule_date ? \Illuminate\Support\Carbon::parse($schedule->schedule_date)->format('d M Y') : '-' }}</div>
                                        <div class="small text-muted">{{ $schedule->start_time ?? '-' }} - {{ $schedule->end_time ?? '-' }}</div>
                                    </td>
                                    <td class="text-end">
                                        <div class="fw-bold text-primary">{{ $tracking ? number_format((float) $tracking->coverage_percentage, 2) : '0.00' }}%</div>
                                        <div class="small text-muted">material coverage</div>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill {{ $badgeClass }}">{{ $statusLabel }}</span>
                                        @if($tracking?->late_minutes)
                                            <div class="small text-danger mt-1">Late {{ $tracking->late_minutes }} min</div>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('instructor-tracking.show', $schedule) }}" class="btn btn-sm btn-primary btn-modern text-nowrap">
                                            <i class="bi bi-clipboard-check me-1"></i>Open Tracking
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $schedules->links() }}
                </div>
            @else
                <div class="empty-state text-center py-5">
                    <div class="empty-state-icon mb-3"><i class="bi bi-clipboard-data"></i></div>
                    <h5 class="mb-1">No session found</h5>
                    <p class="text-muted mb-0">Belum ada instructor schedule yang sesuai dengan filter.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
