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
    $trialStats = $trialStats ?? [];
    $upcomingTrialSchedules = $upcomingTrialSchedules ?? [];
    $trialParticipantStatusCounts = collect($trialParticipantStatusCounts ?? []);
    $trialFollowUpProgress = $trialFollowUpProgress ?? 0;
    $workshopStats = $workshopStats ?? [];
    $workshopParticipantStatusCounts = collect($workshopParticipantStatusCounts ?? []);
    $workshopFollowUpProgress = $workshopFollowUpProgress ?? 0;
    $upcomingWorkshopSchedules = $upcomingWorkshopSchedules ?? [];
    $mentoringStats = $mentoringStats ?? [];
    $mentoringStatusCounts = collect($mentoringStatusCounts ?? []);
    $upcomingMentoringSessions = $upcomingMentoringSessions ?? [];
    $assignmentSubmissionStats = $assignmentSubmissionStats ?? [];
    $assignmentSubmissionStatusCounts = collect($assignmentSubmissionStatusCounts ?? []);
    $recentAssignmentSubmissions = $recentAssignmentSubmissions ?? [];
    $academicSummary = $academicSummary ?? [];
    $academicAiSummaryText = $academicAiSummaryText ?? ($academicSummary['summary_text'] ?? '');

    $learningProgressCollection = collect($learningProgress ?? []);
    $learningProgressAverage = $learningProgressCollection->count() > 0
        ? round((float) $learningProgressCollection->avg(fn ($row) => (float) ($row['progress'] ?? 0)))
        : 0;
    $learningProgressHighRisk = $learningProgressCollection
        ->filter(fn ($row) => ($row['risk'] ?? null) === 'High')
        ->count();
    $learningProgressTotalStudents = (int) $learningProgressCollection
        ->sum(fn ($row) => (int) ($row['students'] ?? 0));

    $upcomingTrialScheduleCount = is_countable($upcomingTrialSchedules ?? []) ? count($upcomingTrialSchedules) : 0;
    $upcomingWorkshopScheduleCount = is_countable($upcomingWorkshopSchedules ?? []) ? count($upcomingWorkshopSchedules) : 0;
    $upcomingMentoringSessionCount = is_countable($upcomingMentoringSessions ?? []) ? count($upcomingMentoringSessions) : 0;

    $formatDate = function ($value, $format = 'd M Y') {
        if (blank($value)) {
            return '-';
        }

        try {
            return \Carbon\Carbon::parse($value)->format($format);
        } catch (\Throwable) {
            return '-';
        }
    };

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
        <div class="col-xl-12">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Batch Capacity Overview</h5>
                        <p class="content-card-subtitle mb-0">
                            Ringkasan kapasitas batch aktif dan persiapan kelas berjalan.
                        </p>
                    </div>

                    <a href="{{ $routeHref('batches.index') }}" class="btn btn-sm  btn-light btn-modern border">
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


    </div>

    <div class="dashboard-section-label mb-3 mt-1">
        <div class="dashboard-section-eyebrow">Trial / Webinar Overview</div>
        <h4 class="dashboard-section-title mb-1">Trial / Webinar Readiness</h4>
        <p class="dashboard-section-subtitle mb-0">
            Monitoring peserta webinar/trial bulan ini, progress follow-up, status peserta, dan jadwal mendatang.
        </p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-layout-text-window-reverse"></i>
                    </div>
                    <div>
                        <div class="stat-title">Active Themes</div>
                        <div class="stat-value">{{ number_format((int) ($trialStats['themes_active'] ?? 0)) }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Dari total {{ number_format((int) ($trialStats['themes_total'] ?? 0)) }} tema trial/webinar.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-calendar2-week"></i>
                    </div>
                    <div>
                        <div class="stat-title">Schedules This Month</div>
                        <div class="stat-value">{{ number_format((int) ($trialStats['schedules_active_this_month'] ?? $trialStats['schedules_active'] ?? 0)) }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Jadwal aktif pada bulan berjalan.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-people"></i>
                    </div>
                    <div>
                        <div class="stat-title">Participants This Month</div>
                        <div class="stat-value">{{ number_format((int) ($trialStats['participants_this_month'] ?? $trialStats['participants_new_this_month'] ?? 0)) }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Peserta trial/webinar yang masuk bulan ini.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-arrow-repeat"></i>
                    </div>
                    <div>
                        <div class="stat-title">Follow-up Progress</div>
                        <div class="stat-value">{{ number_format($safePercent($trialFollowUpProgress)) }}%</div>
                    </div>
                </div>
                <div class="stat-description">
                    Contacted, confirmed, atau attended bulan ini.
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-5">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Trial / Webinar Follow-up Progress</h5>
                        <p class="content-card-subtitle mb-0">
                            Persentase peserta trial/webinar bulan ini yang sudah masuk proses follow-up.
                        </p>
                    </div>

                    <a href="{{ $routeHref('trial-participants.index') }}" class="btn btn-sm btn-light btn-modern border">
                        View Trial
                    </a>
                </div>

                <div class="content-card-body">
                    <div class="trial-progress-card">
                        <div class="trial-progress-value">{{ number_format($safePercent($trialFollowUpProgress)) }}%</div>
                        <div class="trial-progress-label">Follow-up Progress Bulan Ini</div>

                        <div class="progress progress-modern mt-3 mb-4">
                            <div
                                class="progress-bar"
                                role="progressbar"
                                style="width: {{ $safePercent($trialFollowUpProgress) }}%;"
                                aria-valuenow="{{ $safePercent($trialFollowUpProgress) }}"
                                aria-valuemin="0"
                                aria-valuemax="100"
                            ></div>
                        </div>

                        <div class="trial-status-grid">
                            @foreach (['registered', 'contacted', 'confirmed', 'attended', 'cancelled', 'no_show'] as $statusKey)
                                <div class="trial-status-item">
                                    <span>{{ \Illuminate\Support\Str::headline($statusKey) }}</span>
                                    <strong>{{ number_format((int) ($trialParticipantStatusCounts[$statusKey] ?? 0)) }}</strong>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Upcoming Trial / Webinar Schedules</h5>
                        <p class="content-card-subtitle mb-0">
                            Jadwal trial/webinar terdekat yang aktif di sistem.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    @if($upcomingTrialScheduleCount > 0)
                        <div class="table-responsive">
                            <table class="table table-modern align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Schedule</th>
                                        <th>Program</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th class="text-center">Quota</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($upcomingTrialSchedules as $schedule)
                                        @php
                                            $trialScheduleTitle = data_get($schedule, 'title')
                                                ?: data_get($schedule, 'theme')
                                                ?: data_get($schedule, 'name')
                                                ?: 'Trial Schedule';

                                            $trialScheduleSubtitle = data_get($schedule, 'subtitle')
                                                ?: data_get($schedule, 'schedule_title')
                                                ?: null;

                                            $trialScheduleProgram = data_get($schedule, 'program')
                                                ?: data_get($schedule, 'program.name')
                                                ?: '-';

                                            $trialScheduleDate = data_get($schedule, 'schedule_date')
                                                ?: data_get($schedule, 'date')
                                                ?: data_get($schedule, 'start_date');

                                            $trialStartTime = data_get($schedule, 'start_time');
                                            $trialEndTime = data_get($schedule, 'end_time');
                                        @endphp

                                        <tr>
                                            <td>
                                                <div class="fw-semibold text-dark">{{ $trialScheduleTitle }}</div>
                                                @if($trialScheduleSubtitle && $trialScheduleSubtitle !== $trialScheduleTitle)
                                                    <div class="small text-muted">{{ $trialScheduleSubtitle }}</div>
                                                @endif
                                            </td>
                                            <td>{{ $trialScheduleProgram }}</td>
                                            <td>{{ $formatDate($trialScheduleDate) }}</td>
                                            <td>
                                                {{ $trialStartTime ? \Illuminate\Support\Str::of($trialStartTime)->substr(0, 5) : '-' }}
                                                @if($trialEndTime)
                                                    - {{ \Illuminate\Support\Str::of($trialEndTime)->substr(0, 5) }}
                                                @endif
                                            </td>
                                            <td class="text-center">{{ number_format((int) (data_get($schedule, 'quota') ?? 0)) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="empty-state-box">
                            <div class="empty-state-icon">
                                <i class="bi bi-calendar-x"></i>
                            </div>
                            <h5 class="empty-state-title">Belum ada trial/webinar schedule mendatang</h5>
                            <p class="empty-state-text mb-0">
                                Data jadwal trial/webinar aktif yang akan datang belum tersedia.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-section-label mb-3 mt-1">
        <div class="dashboard-section-eyebrow">Workshop Overview</div>
        <h4 class="dashboard-section-title mb-1">Workshop Readiness</h4>
        <p class="dashboard-section-subtitle mb-0">
            Monitoring jadwal workshop, peserta bulan ini, conversion, attendance, dan status pembayaran.
        </p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-easel2-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Active Workshops</div>
                        <div class="stat-value">{{ number_format((int) ($workshopStats['workshops_active'] ?? 0)) }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Dari total {{ number_format((int) ($workshopStats['workshops_total'] ?? 0)) }} workshop terdaftar.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-calendar2-week"></i>
                    </div>
                    <div>
                        <div class="stat-title">Schedules This Month</div>
                        <div class="stat-value">{{ number_format((int) ($workshopStats['schedules_active_this_month'] ?? $workshopStats['schedules_this_month'] ?? 0)) }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Jadwal workshop aktif bulan ini.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-person-workspace"></i>
                    </div>
                    <div>
                        <div class="stat-title">Participants This Month</div>
                        <div class="stat-value">{{ number_format((int) ($workshopStats['participants_this_month'] ?? 0)) }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Peserta workshop yang masuk bulan berjalan.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-door-open"></i>
                    </div>
                    <div>
                        <div class="stat-title">Attendance</div>
                        <div class="stat-value">{{ number_format($safePercent($workshopStats['attendance_percent'] ?? 0)) }}%</div>
                    </div>
                </div>
                <div class="stat-description">
                    Persentase peserta workshop yang hadir.
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-5">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Workshop Conversion Progress</h5>
                        <p class="content-card-subtitle mb-0">
                            Persentase peserta workshop bulan ini yang sudah confirmed atau attended.
                        </p>
                    </div>

                  
                </div>

                <div class="content-card-body">
                    <div class="trial-progress-card">
                        <div class="trial-progress-value">{{ number_format($safePercent($workshopStats['conversion_percent'] ?? $workshopFollowUpProgress)) }}%</div>
                        <div class="trial-progress-label">Conversion Progress Bulan Ini</div>

                        <div class="progress progress-modern mt-3 mb-4">
                            <div
                                class="progress-bar"
                                role="progressbar"
                                style="width: {{ $safePercent($workshopStats['conversion_percent'] ?? $workshopFollowUpProgress) }}%;"
                                aria-valuenow="{{ $safePercent($workshopStats['conversion_percent'] ?? $workshopFollowUpProgress) }}"
                                aria-valuemin="0"
                                aria-valuemax="100"
                            ></div>
                        </div>

                        <div class="trial-status-grid">
                            @foreach (['registered', 'pending_payment', 'confirmed', 'attended', 'cancelled'] as $statusKey)
                                <div class="trial-status-item">
                                    <span>{{ \Illuminate\Support\Str::headline($statusKey) }}</span>
                                    <strong>{{ number_format((int) ($workshopParticipantStatusCounts[$statusKey] ?? 0)) }}</strong>
                                </div>
                            @endforeach

                            <div class="trial-status-item">
                                <span>Upcoming</span>
                                <strong>{{ number_format($upcomingWorkshopScheduleCount) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Upcoming Workshop Schedules</h5>
                        <p class="content-card-subtitle mb-0">
                            Jadwal workshop terdekat yang aktif di sistem.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    @if($upcomingWorkshopScheduleCount > 0)
                        <div class="table-responsive">
                            <table class="table table-modern align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Workshop</th>
                                        <th>Schedule</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th class="text-center">Seat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($upcomingWorkshopSchedules as $schedule)
                                        @php
                                            $workshopScheduleTitle = data_get($schedule, 'title')
                                                ?: data_get($schedule, 'workshop_title')
                                                ?: 'Workshop Schedule';

                                            $workshopScheduleSubtitle = data_get($schedule, 'subtitle')
                                                ?: data_get($schedule, 'schedule_title')
                                                ?: null;

                                            $workshopScheduleDate = data_get($schedule, 'schedule_date')
                                                ?: data_get($schedule, 'date')
                                                ?: data_get($schedule, 'start_date');

                                            $workshopStartTime = data_get($schedule, 'start_time');
                                            $workshopEndTime = data_get($schedule, 'end_time');

                                            $workshopRegisteredCount = (int) (data_get($schedule, 'registered_count') ?? 0);
                                            $workshopQuota = (int) (data_get($schedule, 'quota') ?? 0);
                                            $workshopLocationType = data_get($schedule, 'location_type');
                                        @endphp

                                        <tr>
                                            <td>
                                                <div class="fw-semibold text-dark">{{ $workshopScheduleTitle }}</div>
                                                @if($workshopLocationType)
                                                    <div class="small text-muted">{{ \Illuminate\Support\Str::headline($workshopLocationType) }}</div>
                                                @endif
                                            </td>
                                            <td>{{ $workshopScheduleSubtitle ?: '-' }}</td>
                                            <td>{{ $formatDate($workshopScheduleDate) }}</td>
                                            <td>
                                                {{ $workshopStartTime ? \Illuminate\Support\Str::of($workshopStartTime)->substr(0, 5) : '-' }}
                                                @if($workshopEndTime)
                                                    - {{ \Illuminate\Support\Str::of($workshopEndTime)->substr(0, 5) }}
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                {{ number_format($workshopRegisteredCount) }}
                                                @if($workshopQuota > 0)
                                                    / {{ number_format($workshopQuota) }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="empty-state-box">
                            <div class="empty-state-icon">
                                <i class="bi bi-calendar-x"></i>
                            </div>
                            <h5 class="empty-state-title">Belum ada workshop schedule mendatang</h5>
                            <p class="empty-state-text mb-0">
                                Data jadwal workshop aktif yang akan datang belum tersedia.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-section-label mb-3 mt-1">
        <div class="dashboard-section-eyebrow">Mentoring Overview</div>
        <h4 class="dashboard-section-title mb-1">Mentoring Sessions</h4>
        <p class="dashboard-section-subtitle mb-0">
            Monitoring request mentoring student, approval instructor, dan sesi yang perlu diproses.
        </p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div>
                        <div class="stat-title">Pending</div>
                        <div class="stat-value">{{ number_format((int) ($mentoringStats['pending'] ?? 0)) }}</div>
                    </div>
                </div>
                <div class="stat-description">Request mentoring yang belum diproses.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div>
                        <div class="stat-title">Approved</div>
                        <div class="stat-value">{{ number_format((int) ($mentoringStats['approved'] ?? 0)) }}</div>
                    </div>
                </div>
                <div class="stat-description">Sesi mentoring yang sudah disetujui.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-patch-check"></i>
                    </div>
                    <div>
                        <div class="stat-title">Completed</div>
                        <div class="stat-value">{{ number_format((int) ($mentoringStats['completed'] ?? 0)) }}</div>
                    </div>
                </div>
                <div class="stat-description">Sesi mentoring yang sudah selesai.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div>
                        <div class="stat-title">This Week</div>
                        <div class="stat-value">{{ number_format((int) ($mentoringStats['this_week'] ?? 0)) }}</div>
                    </div>
                </div>
                <div class="stat-description">Jadwal mentoring dalam minggu ini.</div>
            </div>
        </div>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Mentoring Queue</h5>
                <p class="content-card-subtitle mb-0">
                    Request mentoring aktif dan progress completion.
                </p>
            </div>

            <a href="{{ url('academic/mentoring-sessions') }}" class="btn btn-sm btn-light btn-modern border">
                View Mentoring
            </a>
        </div>

        <div class="content-card-body">
            <div class="trial-progress-card mb-4">
                <div class="trial-progress-value">{{ number_format($safePercent($mentoringStats['completion_rate'] ?? 0)) }}%</div>
                <div class="trial-progress-label">Mentoring Completion</div>

                <div class="progress progress-modern mt-3">
                    <div
                        class="progress-bar"
                        role="progressbar"
                        style="width: {{ $safePercent($mentoringStats['completion_rate'] ?? 0) }}%;"
                        aria-valuenow="{{ $safePercent($mentoringStats['completion_rate'] ?? 0) }}"
                        aria-valuemin="0"
                        aria-valuemax="100"
                    ></div>
                </div>
            </div>

            @if($upcomingMentoringSessionCount > 0)
                <div class="table-responsive">
                    <table class="table table-modern align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Topic</th>
                                <th>Instructor</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($upcomingMentoringSessions as $session)
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ data_get($session, 'student', '-') }}</div>
                                        <div class="small text-muted">{{ $formatDate(data_get($session, 'requested_date')) }}</div>
                                    </td>
                                    <td>{{ data_get($session, 'topic_type', '-') }}</td>
                                    <td>{{ data_get($session, 'instructor', '-') }}</td>
                                    <td>
                                        <span class="badge rounded-pill {{ $statusBadgeClass(data_get($session, 'status', '-')) }}">
                                            {{ data_get($session, 'status', '-') }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-person-video3"></i>
                    </div>
                    <h5 class="empty-state-title">Belum ada request mentoring aktif</h5>
                    <p class="empty-state-text mb-0">
                        Request mentoring student yang perlu diproses belum tersedia.
                    </p>
                </div>
            @endif
        </div>
    </div>

    <div class="dashboard-section-label mb-3 mt-1">
        <div class="dashboard-section-eyebrow">Assessment Overview</div>
        <h4 class="dashboard-section-title mb-1">Assignment Review Queue</h4>
        <p class="dashboard-section-subtitle mb-0">
            Monitoring submission student yang masuk, status review, dan feedback yang perlu diselesaikan.
        </p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-inbox"></i>
                    </div>
                    <div>
                        <div class="stat-title">Pending Review</div>
                        <div class="stat-value">{{ number_format((int) ($assignmentSubmissionStats['pending_review'] ?? 0)) }}</div>
                    </div>
                </div>
                <div class="stat-description">Submission yang belum direview.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-calendar3"></i>
                    </div>
                    <div>
                        <div class="stat-title">This Month</div>
                        <div class="stat-value">{{ number_format((int) ($assignmentSubmissionStats['this_month'] ?? 0)) }}</div>
                    </div>
                </div>
                <div class="stat-description">Submission masuk bulan berjalan.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-check2-square"></i>
                    </div>
                    <div>
                        <div class="stat-title">Reviewed</div>
                        <div class="stat-value">{{ number_format((int) ($assignmentSubmissionStats['reviewed_this_month'] ?? 0)) }}</div>
                    </div>
                </div>
                <div class="stat-description">Submission yang selesai direview bulan ini.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-star"></i>
                    </div>
                    <div>
                        <div class="stat-title">Avg Score</div>
                        <div class="stat-value">{{ $assignmentSubmissionStats['avg_score'] ?? '-' }}</div>
                    </div>
                </div>
                <div class="stat-description">Rata-rata skor submission yang sudah dinilai.</div>
            </div>
        </div>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Recent Assignment Submissions</h5>
                <p class="content-card-subtitle mb-0">
                    Submission terbaru yang masuk ke dashboard akademik.
                </p>
            </div>

            <a href="{{ $routeHref('assignment-submissions.index') }}" class="btn btn-sm btn-light btn-modern border">
                View Reviews
            </a>
        </div>

        <div class="content-card-body">
            <div class="trial-status-grid mb-4">
                @foreach (['submitted', 'late', 'reviewed', 'returned'] as $statusKey)
                    <div class="trial-status-item">
                        <span>{{ \Illuminate\Support\Str::headline($statusKey) }}</span>
                        <strong>{{ number_format((int) ($assignmentSubmissionStatusCounts[$statusKey] ?? 0)) }}</strong>
                    </div>
                @endforeach
            </div>

            @if(is_countable($recentAssignmentSubmissions) && count($recentAssignmentSubmissions) > 0)
                <div class="table-responsive">
                    <table class="table table-modern align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Assignment</th>
                                <th>Status</th>
                                <th>Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentAssignmentSubmissions as $submission)
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ data_get($submission, 'student', '-') }}</div>
                                        <div class="small text-muted">{{ $formatDate(data_get($submission, 'submitted_date')) }}</div>
                                    </td>
                                    <td>{{ data_get($submission, 'assignment', '-') }}</td>
                                    <td>
                                        <span class="badge rounded-pill {{ $statusBadgeClass(data_get($submission, 'status', '-')) }}">
                                            {{ data_get($submission, 'status', '-') }}
                                        </span>
                                    </td>
                                    <td>{{ data_get($submission, 'score', '-') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-journal-check"></i>
                    </div>
                    <h5 class="empty-state-title">Belum ada submission assignment terbaru</h5>
                    <p class="empty-state-text mb-0">
                        Submission student terbaru belum tersedia di dashboard akademik.
                    </p>
                </div>
            @endif
        </div>
    </div>

    <div class="dashboard-section-label mb-3 mt-1">
        <div class="dashboard-section-eyebrow">Learning Overview</div>
        <h4 class="dashboard-section-title mb-1">Learning Progress by Batch</h4>
        <p class="dashboard-section-subtitle mb-0">
            Monitoring progress belajar siswa berdasarkan batch aktif dan risiko akademik.
        </p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-collection"></i>
                    </div>
                    <div>
                        <div class="stat-title">Tracked Batches</div>
                        <div class="stat-value">{{ number_format($learningProgressCollection->count()) }}</div>
                    </div>
                </div>
                <div class="stat-description">Batch yang masuk pemantauan progress.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Tracked Students</div>
                        <div class="stat-value">{{ number_format($learningProgressTotalStudents) }}</div>
                    </div>
                </div>
                <div class="stat-description">Total student dari batch yang dimonitor.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-speedometer2"></i>
                    </div>
                    <div>
                        <div class="stat-title">Avg Progress</div>
                        <div class="stat-value">{{ number_format($learningProgressAverage) }}%</div>
                    </div>
                </div>
                <div class="stat-description">Rata-rata progress belajar batch aktif.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div>
                        <div class="stat-title">High Risk</div>
                        <div class="stat-value">{{ number_format($learningProgressHighRisk) }}</div>
                    </div>
                </div>
                <div class="stat-description">Batch dengan risiko akademik tinggi.</div>
            </div>
        </div>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Batch Learning Progress</h5>
                <p class="content-card-subtitle mb-0">
                    Progress belajar, jumlah student, dan risk level per batch.
                </p>
            </div>

            <a href="{{ $routeHref('academic/student-progress') }}" class="btn btn-sm btn-light btn-modern border">
                View Progress
            </a>
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

                        <div class="progress progress-modern">
                            <div
                                class="progress-bar"
                                role="progressbar"
                                style="width: {{ $progressPercent }}%;"
                                aria-valuenow="{{ $progressPercent }}"
                                aria-valuemin="0"
                                aria-valuemax="100"
                            >
                                {{ number_format($progressPercent) }}%
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="empty-state-box">
                        <div class="empty-state-icon">
                            <i class="bi bi-bar-chart-line"></i>
                        </div>
                        <h5 class="empty-state-title">Belum ada data progress belajar per batch</h5>
                        <p class="empty-state-text mb-0">
                            Data progress belajar per batch aktif belum tersedia.
                        </p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="dashboard-section-label mb-3 mt-1">
        <div class="dashboard-section-eyebrow">Academic Workload</div>
        <h4 class="dashboard-section-title mb-1">Academic Workload</h4>
        <p class="dashboard-section-subtitle mb-0">
            Ringkasan pekerjaan akademik yang butuh follow-up.
        </p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-pencil-square"></i>
                    </div>
                    <div>
                        <div class="stat-title">Pending Assessment</div>
                        <div class="stat-value">{{ number_format((int) ($workloadStats['pending_assessments'] ?? 0)) }}</div>
                    </div>
                </div>
                <div class="stat-description">Submission belum dinilai.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>
                    <div>
                        <div class="stat-title">Report Cards</div>
                        <div class="stat-value">{{ number_format((int) ($workloadStats['report_cards'] ?? 0)) }}</div>
                    </div>
                </div>
                <div class="stat-description">Report card belum publish/final.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-award"></i>
                    </div>
                    <div>
                        <div class="stat-title">Certificates</div>
                        <div class="stat-value">{{ number_format((int) ($workloadStats['certificates'] ?? 0)) }}</div>
                    </div>
                </div>
                <div class="stat-description">Sertifikat yang menunggu finalisasi.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-person-video3"></i>
                    </div>
                    <div>
                        <div class="stat-title">Mentoring</div>
                        <div class="stat-value">{{ number_format((int) ($workloadStats['mentoring'] ?? 0)) }}</div>
                    </div>
                </div>
                <div class="stat-description">Jadwal mentoring minggu ini.</div>
            </div>
        </div>
    </div>

   



    <x-ai-insight-widget
        title="AI Academic Insight"
        :insight="$academicSummary ?? []"
        :summary="$academicAiSummaryText ?? null"
    />

</div>
@endsection