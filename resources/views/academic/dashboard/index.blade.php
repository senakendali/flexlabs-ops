@extends('layouts.app-dashboard')

@section('title', 'Academic Dashboard')

@section('content')
@php
    $stats = $stats ?? [];
    $capacityRows = $capacityRows ?? [];
    $todaySessions = $todaySessions ?? [];
    $trackingRows = $trackingRows ?? [];
    $curriculumReadiness = $curriculumReadiness ?? [];
    $learningProgress = $learningProgress ?? [];
    $alerts = $alerts ?? [];
    $workloadStats = $workloadStats ?? [
        'pending_assessments' => 0,
        'report_cards' => 0,
        'certificates' => 0,
        'mentoring' => 0,
    ];
    $suggestedFocus = $suggestedFocus ?? 'Pantau jadwal instructor hari ini dan pastikan semua sesi punya scheduled material.';

    $statusBadgeClass = function ($status) {
        return match ($status) {
            'Running', 'Active', 'Ongoing', 'Submitted', 'Checked In', 'Reviewed', 'Completed', 'Success' => 'bg-success-subtle text-success-emphasis border border-success-subtle',
            'Preparing', 'Draft', 'Scheduled', 'Pending', 'Open' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
            'Waiting Tracking', 'Returned', 'Cancelled', 'Failed' => 'bg-danger-subtle text-danger-emphasis border border-danger-subtle',
            default => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
        };
    };

    $riskBadgeClass = function ($risk) {
        return match ($risk) {
            'Low' => 'bg-success-subtle text-success-emphasis border border-success-subtle',
            'Medium' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
            'High' => 'bg-danger-subtle text-danger-emphasis border border-danger-subtle',
            default => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
        };
    };

    $alertClass = function ($type) {
        return match ($type) {
            'success' => 'alert-success',
            'warning' => 'alert-warning',
            'danger' => 'alert-danger',
            'info' => 'alert-info',
            default => 'alert-secondary',
        };
    };

    $routeHref = function ($name) {
        return \Illuminate\Support\Facades\Route::has($name) ? route($name) : '#';
    };

    $safePercent = function ($value) {
        return max(0, min(100, (float) $value));
    };
@endphp

<div class="container-fluid px-4 py-4">
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Academic</div>
                <h1 class="page-title mb-2">Academic Dashboard</h1>
                <p class="page-subtitle mb-0">
                    Monitor kesiapan akademik, kapasitas batch, aktivitas instructor, progress belajar, dan kebutuhan follow-up dalam satu tempat.
                </p>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ $routeHref('instructor-schedules.index') }}" class="btn btn-light btn-modern border">
                    <i class="bi bi-calendar2-week me-2"></i>Instructor Schedules
                </a>

                <a href="{{ $routeHref('instructor-tracking.index') }}" class="btn btn-light btn-modern border">
                    <i class="bi bi-clipboard-check me-2"></i>Instructor Tracking
                </a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @forelse ($stats as $stat)
            <div class="col-xl-3 col-md-6">
                <div class="stat-card h-100">
                    <div class="stat-card-top">
                        <div class="stat-icon-wrap">
                            <i class="bi {{ $stat['icon'] ?? 'bi-activity' }}"></i>
                        </div>

                        <div>
                            <div class="stat-title">{{ $stat['title'] ?? '-' }}</div>
                            <div class="stat-value">{{ $stat['value'] ?? '0' }}</div>
                        </div>
                    </div>

                    <div class="stat-description">{{ $stat['description'] ?? '-' }}</div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="content-card">
                    <div class="content-card-body text-center text-muted py-4">
                        Belum ada data summary akademik.
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-8">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Batch Capacity Overview</h5>
                        <p class="content-card-subtitle mb-0">
                            Ringkasan kapasitas batch aktif dan persiapan kelas berjalan.
                        </p>
                    </div>

                    <a href="{{ $routeHref('batches.index') }}" class="btn btn-sm btn-light border">
                        View Batches
                    </a>
                </div>

                <div class="content-card-body">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Program</th>
                                    <th>Batch</th>
                                    <th>Capacity</th>
                                    <th>Filled</th>
                                    <th>Utilization</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($capacityRows as $row)
                                    @php
                                        $capacity = (int) ($row['capacity'] ?? 0);
                                        $filled = (int) ($row['filled'] ?? 0);
                                        $utilization = $capacity > 0 ? round(($filled / $capacity) * 100) : 0;
                                        $utilization = $safePercent($utilization);
                                    @endphp

                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-dark">{{ $row['program'] ?? '-' }}</div>
                                        </td>
                                        <td>{{ $row['batch'] ?? '-' }}</td>
                                        <td>{{ number_format($capacity) }}</td>
                                        <td>{{ number_format($filled) }}</td>
                                        <td style="min-width: 160px;">
                                            <div class="d-flex justify-content-between small mb-1">
                                                <span>{{ number_format($utilization) }}%</span>
                                                <span class="text-muted">{{ number_format($filled) }}/{{ number_format($capacity) }}</span>
                                            </div>

                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar" style="width: {{ $utilization }}%;"></div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill {{ $statusBadgeClass($row['status'] ?? '-') }}">
                                                {{ $row['status'] ?? '-' }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            Belum ada data kapasitas batch.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Academic Alerts</h5>
                        <p class="content-card-subtitle mb-0">
                            Hal yang perlu diperhatikan academic team.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="d-flex flex-column gap-3">
                        @forelse ($alerts as $alert)
                            <div class="alert {{ $alertClass($alert['type'] ?? 'secondary') }} mb-0">
                                <div class="d-flex gap-2 align-items-start">
                                    <i class="bi {{ $alert['icon'] ?? 'bi-info-circle-fill' }} mt-1"></i>

                                    <div>
                                        <div class="fw-semibold">{{ $alert['title'] ?? '-' }}</div>
                                        <div class="small mt-1">{{ $alert['description'] ?? '-' }}</div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="alert alert-success mb-0">
                                <div class="d-flex gap-2 align-items-start">
                                    <i class="bi bi-check-circle-fill mt-1"></i>

                                    <div>
                                        <div class="fw-semibold">Academic operation terlihat aman</div>
                                        <div class="small mt-1">
                                            Belum ada alert prioritas tinggi dari data dashboard saat ini.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-6">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Today Instructor Sessions</h5>
                        <p class="content-card-subtitle mb-0">
                            Jadwal instructor hari ini dan status pelaksanaannya.
                        </p>
                    </div>

                    <a href="{{ $routeHref('instructor-schedules.index') }}" class="btn btn-sm btn-light border">
                        View Schedule
                    </a>
                </div>

                <div class="content-card-body">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Session</th>
                                    <th>Instructor</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($todaySessions as $session)
                                    <tr>
                                        <td class="text-nowrap">{{ $session['time'] ?? '-' }}</td>
                                        <td>
                                            <div class="fw-semibold text-dark">{{ $session['title'] ?? '-' }}</div>
                                            <div class="small text-muted">{{ $session['batch'] ?? '-' }}</div>
                                        </td>
                                        <td>{{ $session['instructor'] ?? '-' }}</td>
                                        <td>
                                            <span class="badge rounded-pill {{ $statusBadgeClass($session['status'] ?? '-') }}">
                                                {{ $session['status'] ?? '-' }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            Belum ada jadwal instructor hari ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Instructor Tracking Snapshot</h5>
                        <p class="content-card-subtitle mb-0">
                            Ringkasan coverage dari sesi instructor terbaru.
                        </p>
                    </div>

                    <a href="{{ $routeHref('instructor-tracking.index') }}" class="btn btn-sm btn-light border">
                        View Tracking
                    </a>
                </div>

                <div class="content-card-body">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Instructor</th>
                                    <th>Session</th>
                                    <th>Coverage</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($trackingRows as $trackingRow)
                                    @php
                                        $coverage = $safePercent($trackingRow['coverage'] ?? 0);
                                    @endphp

                                    <tr>
                                        <td>{{ $trackingRow['instructor'] ?? '-' }}</td>
                                        <td>
                                            <div class="fw-semibold text-dark">{{ $trackingRow['session'] ?? '-' }}</div>
                                            <div class="small text-muted">{{ $trackingRow['batch'] ?? '-' }}</div>
                                        </td>
                                        <td style="min-width: 130px;">
                                            <div class="d-flex justify-content-between small mb-1">
                                                <span>{{ number_format($coverage, $coverage == round($coverage) ? 0 : 2) }}%</span>
                                            </div>

                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar" style="width: {{ $coverage }}%;"></div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill {{ $statusBadgeClass($trackingRow['status'] ?? '-') }}">
                                                {{ $trackingRow['status'] ?? '-' }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            Belum ada data instructor tracking.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-7">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Learning Progress by Batch</h5>
                        <p class="content-card-subtitle mb-0">
                            Progress belajar siswa berdasarkan batch aktif.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="d-flex flex-column gap-3">
                        @forelse ($learningProgress as $progress)
                            @php
                                $progressPercent = $safePercent($progress['progress'] ?? 0);
                            @endphp

                            <div class="border rounded-3 p-3">
                                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-2">
                                    <div>
                                        <div class="fw-bold text-dark">{{ $progress['batch'] ?? '-' }}</div>
                                        <div class="small text-muted">
                                            {{ $progress['program'] ?? '-' }} · {{ number_format((int) ($progress['students'] ?? 0)) }} students
                                        </div>
                                    </div>

                                    <span class="badge rounded-pill {{ $riskBadgeClass($progress['risk'] ?? '-') }}">
                                        {{ $progress['risk'] ?? '-' }} Risk
                                    </span>
                                </div>

                                <div class="d-flex justify-content-between small mb-1">
                                    <span>Learning Progress</span>
                                    <span class="fw-semibold">{{ number_format($progressPercent) }}%</span>
                                </div>

                                <div class="progress" style="height: 9px;">
                                    <div class="progress-bar" style="width: {{ $progressPercent }}%;"></div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-muted py-4">
                                Belum ada data progress belajar per batch.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Academic Workload</h5>
                        <p class="content-card-subtitle mb-0">
                            Ringkasan pekerjaan akademik yang butuh follow-up.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="small text-muted fw-semibold mb-1">Pending Assessment</div>
                                <div class="h3 fw-bold mb-1">{{ number_format((int) ($workloadStats['pending_assessments'] ?? 0)) }}</div>
                                <div class="small text-muted">Submission belum dinilai.</div>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="small text-muted fw-semibold mb-1">Report Cards</div>
                                <div class="h3 fw-bold mb-1">{{ number_format((int) ($workloadStats['report_cards'] ?? 0)) }}</div>
                                <div class="small text-muted">Belum publish/final.</div>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="small text-muted fw-semibold mb-1">Certificates</div>
                                <div class="h3 fw-bold mb-1">{{ number_format((int) ($workloadStats['certificates'] ?? 0)) }}</div>
                                <div class="small text-muted">Menunggu finalisasi.</div>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="small text-muted fw-semibold mb-1">Mentoring</div>
                                <div class="h3 fw-bold mb-1">{{ number_format((int) ($workloadStats['mentoring'] ?? 0)) }}</div>
                                <div class="small text-muted">Jadwal minggu ini.</div>
                            </div>
                        </div>
                    </div>

                    <div class="border rounded-3 bg-light-subtle p-3 mt-3">
                        <div class="fw-bold text-dark mb-1">Suggested Focus</div>
                        <div class="small text-muted">
                            {{ $suggestedFocus }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="content-card">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Curriculum Readiness</h5>
                <p class="content-card-subtitle mb-0">
                    Monitoring kesiapan video, live session material, topic, dan sub topic per program.
                </p>
            </div>

            <a href="{{ $routeHref('curriculum.index') }}" class="btn btn-sm btn-light border">
                View Curriculum
            </a>
        </div>

        <div class="content-card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Program</th>
                            <th>Modules</th>
                            <th>Topics</th>
                            <th>Sub Topics</th>
                            <th>Video Readiness</th>
                            <th>Live Session Readiness</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($curriculumReadiness as $row)
                            @php
                                $videoReady = $safePercent($row['video_ready'] ?? 0);
                                $liveReady = $safePercent($row['live_ready'] ?? 0);
                            @endphp

                            <tr>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $row['program'] ?? '-' }}</div>
                                </td>
                                <td>{{ number_format((int) ($row['modules'] ?? 0)) }}</td>
                                <td>{{ number_format((int) ($row['topics'] ?? 0)) }}</td>
                                <td>{{ number_format((int) ($row['sub_topics'] ?? 0)) }}</td>
                                <td style="min-width: 180px;">
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span>{{ number_format($videoReady) }}%</span>
                                    </div>

                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar" style="width: {{ $videoReady }}%;"></div>
                                    </div>
                                </td>
                                <td style="min-width: 180px;">
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span>{{ number_format($liveReady) }}%</span>
                                    </div>

                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar" style="width: {{ $liveReady }}%;"></div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    Belum ada data curriculum readiness.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection