@extends('layouts.app-dashboard')

@section('title', 'Student Attendance')

@section('content')
@php
    $getScheduleValue = function ($schedule, ?string $column) {
        return $column ? ($schedule->{$column} ?? null) : null;
    };

    $getBatchLabel = function ($batch) {
        if (!$batch) {
            return '-';
        }

        $programName = $batch->program->name ?? null;
        $batchName = $batch->name
            ?? $batch->batch_name
            ?? $batch->code
            ?? ('Batch #' . $batch->id);

        return $programName ? $programName . ' - ' . $batchName : $batchName;
    };

    $getScheduleTitle = function ($schedule) {
        return $schedule->title
            ?? $schedule->topic
            ?? $schedule->session_title
            ?? $schedule->name
            ?? ('Live Session #' . $schedule->id);
    };

    $summaryCount = function ($scheduleId, ?string $status = null, ?string $mode = null) use ($attendanceSummaries) {
        $items = $attendanceSummaries->get($scheduleId, collect());

        return (int) $items
            ->filter(function ($item) use ($status, $mode) {
                if ($status !== null && $item->status !== $status) {
                    return false;
                }

                if ($mode !== null && $item->attendance_mode !== $mode) {
                    return false;
                }

                return true;
            })
            ->sum('total');
    };
@endphp

<div class="container-fluid px-4 py-4">

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Academic Management</div>
                <h1 class="page-title mb-2">Student Attendance</h1>
                <p class="page-subtitle mb-0">
                    Catat kehadiran student berdasarkan live session atau instructor schedule,
                    termasuk mode hadir online dan offline.
                </p>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="content-card mb-4">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Filter Schedule</h5>
                <p class="content-card-subtitle mb-0">
                    Pilih batch atau tanggal untuk mencari jadwal yang ingin dicatat attendance-nya.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            <form method="GET" action="{{ route('academic.attendances.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-xl-5 col-md-6">
                        <label class="form-label">Batch</label>
                        <select name="batch_id" class="form-select">
                            <option value="">All Batches</option>
                            @foreach($batches as $batch)
                                <option value="{{ $batch->id }}" {{ request('batch_id') == $batch->id ? 'selected' : '' }}>
                                    {{ $getBatchLabel($batch) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <label class="form-label">Date</label>
                        <input
                            type="date"
                            name="date"
                            class="form-control"
                            value="{{ request('date') }}"
                        >
                    </div>

                    <div class="col-xl-4">
                        <div class="d-flex gap-2 justify-content-xl-end flex-wrap">
                            <a href="{{ route('academic.attendances.index') }}" class="btn btn-outline-secondary btn-modern">
                                <i class="bi bi-arrow-counterclockwise me-2"></i>Reset
                            </a>

                            <button type="submit" class="btn btn-primary btn-modern">
                                <i class="bi bi-funnel me-2"></i>Filter
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
                <h5 class="content-card-title mb-1">Schedule List</h5>
                <p class="content-card-subtitle mb-0">
                    Buka jadwal untuk mencatat attendance student.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            @if($schedules->count())
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Schedule</th>
                                <th>Batch</th>
                                <th class="text-nowrap">Present</th>
                                <th class="text-nowrap">Late</th>
                                <th class="text-nowrap">Excused</th>
                                <th class="text-nowrap">Absent</th>
                                <th class="text-nowrap">Online</th>
                                <th class="text-nowrap">Offline</th>
                                <th class="text-end text-nowrap">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($schedules as $schedule)
                                @php
                                    $batch = $schedule->batch ?? $batches->firstWhere('id', $schedule->batch_id);

                                    $dateValue = $getScheduleValue($schedule, $dateColumn);
                                    $startValue = $getScheduleValue($schedule, $startTimeColumn);
                                    $endValue = $getScheduleValue($schedule, $endTimeColumn);

                                    $scheduleDate = $dateValue
                                        ? \Illuminate\Support\Carbon::parse($dateValue)->format('d M Y')
                                        : '-';

                                    $startLabel = $startValue
                                        ? \Illuminate\Support\Carbon::parse($startValue)->format('H:i')
                                        : null;

                                    $endLabel = $endValue
                                        ? \Illuminate\Support\Carbon::parse($endValue)->format('H:i')
                                        : null;

                                    $timeRange = trim(($startLabel ?: '') . ' - ' . ($endLabel ?: ''), ' -');

                                    $presentCount = $summaryCount($schedule->id, 'present');
                                    $lateCount = $summaryCount($schedule->id, 'late');
                                    $excusedCount = $summaryCount($schedule->id, 'excused');
                                    $absentCount = $summaryCount($schedule->id, 'absent');
                                    $onlineCount = $summaryCount($schedule->id, null, 'online');
                                    $offlineCount = $summaryCount($schedule->id, null, 'offline');
                                @endphp

                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $getScheduleTitle($schedule) }}</div>
                                        <div class="text-muted small">
                                            {{ $scheduleDate }}
                                            @if($timeRange)
                                                · {{ $timeRange }}
                                            @endif
                                        </div>
                                    </td>

                                    <td>
                                        <div class="text-nowrap">
                                            {{ $getBatchLabel($batch) }}
                                        </div>
                                    </td>

                                    <td class="text-nowrap">
                                        <span class="badge rounded-pill bg-success-subtle text-success">
                                            {{ $presentCount }}
                                        </span>
                                    </td>

                                    <td class="text-nowrap">
                                        <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis">
                                            {{ $lateCount }}
                                        </span>
                                    </td>

                                    <td class="text-nowrap">
                                        <span class="badge rounded-pill bg-info-subtle text-info">
                                            {{ $excusedCount }}
                                        </span>
                                    </td>

                                    <td class="text-nowrap">
                                        <span class="badge rounded-pill bg-danger-subtle text-danger">
                                            {{ $absentCount }}
                                        </span>
                                    </td>

                                    <td class="text-nowrap">
                                        <span class="badge rounded-pill bg-primary-subtle text-primary">
                                            {{ $onlineCount }}
                                        </span>
                                    </td>

                                    <td class="text-nowrap">
                                        <span class="badge rounded-pill bg-secondary-subtle text-secondary">
                                            {{ $offlineCount }}
                                        </span>
                                    </td>

                                    <td class="text-end text-nowrap">
                                        <a
                                            href="{{ route('academic.attendances.record', $schedule) }}"
                                            class="btn btn-sm btn-primary px-3"
                                        >
                                            <i class="bi bi-check2-square me-1"></i>Record Attendance
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $schedules->links() }}
                </div>
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-calendar-x"></i>
                    </div>

                    <h5 class="empty-state-title">Schedule belum tersedia</h5>
                    <p class="empty-state-text mb-0">
                        Belum ada instructor schedule yang bisa dicatat attendance-nya.
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection