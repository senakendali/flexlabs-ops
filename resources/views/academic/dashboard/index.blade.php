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

    /*
    |--------------------------------------------------------------------------
    | Academic Trello Work Progress
    |--------------------------------------------------------------------------
    | Data hanya berasal dari source `academic`.
    | Fallback dipertahankan agar Blade aman terhadap payload controller lama.
    */
    $trelloDashboardStats = $trelloDashboardStats ?? [];
    $trelloAcademicStats = $trelloAcademicStats
        ?? ($trelloDashboardStats['academic'] ?? null)
        ?? $trelloDashboardStats;

    $trelloAcademicSummary = $trelloAcademicStats['summary'] ?? [];
    $trelloAcademicStatuses = $trelloAcademicStats['statuses'] ?? [];

    $trelloAcademicTotalOpenCards = max((int) ($trelloAcademicSummary['total_open_cards'] ?? 0), 0);
    $trelloAcademicActiveWork = max((int) ($trelloAcademicSummary['active_work'] ?? 0), 0);
    $trelloAcademicCompleted = max((int) ($trelloAcademicSummary['completed'] ?? 0), 0);
    $trelloAcademicDueToday = max((int) ($trelloAcademicSummary['due_today'] ?? 0), 0);
    $trelloAcademicOverdue = max((int) ($trelloAcademicSummary['overdue'] ?? 0), 0);
    $trelloAcademicUnmapped = max((int) ($trelloAcademicSummary['unmapped'] ?? 0), 0);
    $trelloAcademicCompletionRate = min(max((int) ($trelloAcademicSummary['completion_rate'] ?? 0), 0), 100);
    $trelloAcademicActiveWorkRate = min(max((int) ($trelloAcademicSummary['active_work_rate'] ?? 0), 0), 100);

    $trelloAcademicDueTodayCards = collect($trelloAcademicStats['due_today_cards'] ?? []);
    $trelloAcademicOverdueCards = collect($trelloAcademicStats['overdue_cards'] ?? []);

    $trelloAcademicPriorityCards = $trelloAcademicOverdueCards
        ->merge($trelloAcademicDueTodayCards)
        ->unique(function ($card) {
            return data_get($card, 'trello_card_id')
                ?: data_get($card, 'id')
                ?: data_get($card, 'name')
                ?: data_get($card, 'title')
                ?: uniqid('academic-card-', true);
        })
        ->values();

    $trelloAcademicActiveCards = collect($trelloAcademicStats['active_cards'] ?? []);
    $trelloAcademicRecentCards = collect($trelloAcademicStats['recent_cards'] ?? []);

    $trelloAcademicWebhookStatus = strtolower((string) ($trelloAcademicStats['webhook_status'] ?? 'inactive'));
    $trelloAcademicIsSynced = in_array($trelloAcademicWebhookStatus, ['active', 'synced'], true);

    $trelloAcademicBoardName = filled($trelloAcademicStats['board_name'] ?? null)
        ? (string) $trelloAcademicStats['board_name']
        : 'Academic Trello';

    $trelloAcademicInsight = filled($trelloAcademicStats['insight'] ?? null)
        ? (string) $trelloAcademicStats['insight']
        : 'Academic Trello insight belum tersedia.';

    $trelloAcademicLastSyncedRaw = $trelloAcademicStats['last_synced_at'] ?? null;
    $trelloAcademicLastWebhookRaw = $trelloAcademicStats['last_webhook_at'] ?? null;

    try {
        $trelloAcademicLastSyncedText = $trelloAcademicLastSyncedRaw
            ? \Carbon\Carbon::parse($trelloAcademicLastSyncedRaw)->format('d M Y H:i')
            : '-';
    } catch (\Throwable) {
        $trelloAcademicLastSyncedText = '-';
    }

    try {
        $trelloAcademicLastWebhookText = $trelloAcademicLastWebhookRaw
            ? \Carbon\Carbon::parse($trelloAcademicLastWebhookRaw)->format('d M Y H:i')
            : '-';
    } catch (\Throwable) {
        $trelloAcademicLastWebhookText = '-';
    }

    $trelloAcademicProgressClass = $trelloAcademicCompletionRate >= 80
        ? 'bg-success'
        : ($trelloAcademicCompletionRate >= 50 ? 'bg-warning' : 'bg-danger');

    $trelloAcademicOverdueClass = $trelloAcademicOverdue > 0
        ? 'text-danger'
        : 'text-success';

    $trelloAcademicDueTodayClass = $trelloAcademicDueToday > 0
        ? 'text-warning'
        : 'text-success';

    $trelloStatusLabels = [
        'notes' => 'Notes',
        'todo' => 'To Do',
        'in_progress' => 'Doing',
        'review' => 'Review',
        'scheduled' => 'Scheduled',
        'done' => 'Done',
        'archived' => 'Archived',
        'ignored' => 'Ignored',
        'unmapped' => 'Unmapped',
    ];

    $trelloStatusIcons = [
        'notes' => 'bi-journal-text',
        'todo' => 'bi-list-check',
        'in_progress' => 'bi-lightning-charge-fill',
        'review' => 'bi-eye-fill',
        'scheduled' => 'bi-calendar-event-fill',
        'done' => 'bi-check2-circle',
        'archived' => 'bi-archive-fill',
        'ignored' => 'bi-slash-circle',
        'unmapped' => 'bi-question-circle',
    ];

    $trelloStatusBadgeClasses = [
        'notes' => 'bg-light text-muted',
        'todo' => 'bg-primary-subtle text-primary',
        'in_progress' => 'bg-warning-subtle text-warning',
        'review' => 'bg-info-subtle text-info',
        'scheduled' => 'bg-purple-subtle text-purple',
        'done' => 'bg-success-subtle text-success',
        'archived' => 'bg-secondary-subtle text-secondary',
        'ignored' => 'bg-secondary-subtle text-secondary',
        'unmapped' => 'bg-secondary-subtle text-secondary',
    ];

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

            <a href="{{ url('academic/student-progress') }}" class="btn btn-sm btn-light btn-modern border">
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
        <div class="dashboard-section-eyebrow">Team Overview</div>
        <h4 class="dashboard-section-title mb-1">Academic Work Progress</h4>
        <p class="dashboard-section-subtitle mb-0">
            Pantau progres pekerjaan tim Academic berdasarkan status pengerjaan, prioritas deadline, PIC, dan aktivitas terbaru dari Trello.
        </p>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-header d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h5 class="content-card-title mb-1">Academic Work Progress</h5>
                <p class="content-card-subtitle mb-0">
                    Ringkasan pekerjaan operasional dari board {{ $trelloAcademicBoardName }}.
                </p>
            </div>

            <span class="badge rounded-pill {{ $trelloAcademicIsSynced
                ? 'bg-success-subtle text-success'
                : 'bg-warning-subtle text-warning' }}">
                <i class="bi {{ $trelloAcademicIsSynced ? 'bi-cloud-check-fill' : 'bi-cloud-slash-fill' }} me-1"></i>
                {{ $trelloAcademicIsSynced ? 'Synced' : 'Not Synced' }}
            </span>
        </div>

        <div class="content-card-body">
            <div class="trello-insight-box mb-3">
                <div class="d-flex align-items-start gap-3">
                    <div class="trello-insight-icon">
                        <i class="bi bi-kanban-fill"></i>
                    </div>

                    <div>
                        <div class="fw-semibold text-dark mb-1">Academic Work Insight</div>
                        <p class="text-muted mb-0">{{ $trelloAcademicInsight }}</p>

                        <div class="small text-muted mt-2">
                            Last sync: <strong>{{ $trelloAcademicLastSyncedText }}</strong>
                            <span class="mx-1">•</span>
                            Last webhook: <strong>{{ $trelloAcademicLastWebhookText }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="work-progress-completion-card mb-4">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-3">
                    <div>
                        <div class="work-progress-completion-eyebrow">Academic Progress</div>
                        <div class="work-progress-completion-value">
                            {{ number_format($trelloAcademicCompletionRate) }}%
                        </div>
                        <div class="work-progress-completion-label">
                            {{ number_format($trelloAcademicCompleted) }}
                            dari
                            {{ number_format($trelloAcademicTotalOpenCards) }}
                            card sudah selesai.
                        </div>
                    </div>

                    <div class="work-progress-completion-meta text-lg-end">
                        <div class="small text-muted">Active Work</div>
                        <div class="fw-semibold text-dark">
                            {{ number_format($trelloAcademicActiveWork) }} card berjalan
                        </div>
                    </div>
                </div>

                <div class="progress progress-modern work-progress-completion-track mb-3">
                    <div
                        class="progress-bar {{ $trelloAcademicProgressClass }}"
                        role="progressbar"
                        style="width: {{ $trelloAcademicCompletionRate }}%;"
                        aria-valuenow="{{ $trelloAcademicCompletionRate }}"
                        aria-valuemin="0"
                        aria-valuemax="100"
                    ></div>
                </div>

                <div class="row g-3">
                    <div class="col-xl-4 col-md-4">
                        <div class="work-progress-mini-metric">
                            <span>Due Today</span>
                            <strong class="{{ $trelloAcademicDueTodayClass }}">
                                {{ number_format($trelloAcademicDueToday) }}
                            </strong>
                        </div>
                    </div>

                    <div class="col-xl-4 col-md-4">
                        <div class="work-progress-mini-metric">
                            <span>Overdue</span>
                            <strong class="{{ $trelloAcademicOverdueClass }}">
                                {{ number_format($trelloAcademicOverdue) }}
                            </strong>
                        </div>
                    </div>

                    <div class="col-xl-4 col-md-4">
                        <div class="work-progress-mini-metric">
                            <span>Unmapped</span>
                            <strong>{{ number_format($trelloAcademicUnmapped) }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                @foreach(['todo', 'in_progress', 'review', 'done'] as $statusKey)
                    @php
                        $statusTotal = (int) ($trelloAcademicStatuses[$statusKey] ?? 0);
                        $statusLabel = $trelloStatusLabels[$statusKey] ?? \Illuminate\Support\Str::headline($statusKey);
                        $statusClass = $trelloStatusBadgeClasses[$statusKey] ?? 'bg-light text-muted';
                        $statusIcon = $trelloStatusIcons[$statusKey] ?? 'bi-circle';

                        $statusDescription = match ($statusKey) {
                            'todo' => 'Task yang sudah masuk antrean kerja dan menunggu eksekusi.',
                            'in_progress' => 'Task yang sedang dikerjakan oleh tim Academic.',
                            'review' => 'Task yang sudah dikerjakan dan menunggu pengecekan.',
                            'done' => 'Task yang sudah selesai dan tercatat sebagai completed.',
                            default => 'Status pekerjaan Academic.',
                        };
                    @endphp

                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card h-100 work-progress-stat-card">
                            <div class="stat-card-top">
                                <div class="stat-icon-wrap {{ $statusClass }}">
                                    <i class="bi {{ $statusIcon }}"></i>
                                </div>

                                <div>
                                    <div class="stat-title">{{ $statusLabel }}</div>
                                    <div class="stat-value">{{ number_format($statusTotal) }}</div>
                                </div>
                            </div>

                            <div class="stat-description">
                                {{ $statusDescription }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($trelloAcademicUnmapped > 0)
                <div class="alert alert-warning mb-4">
                    Ada {{ number_format($trelloAcademicUnmapped) }} card yang belum punya status dashboard.
                    Jalankan mapping list sebelum angka dipakai untuk keputusan operasional.
                </div>
            @endif

            <div class="row g-3 trello-table-row">
                <div class="col-12 d-flex flex-column trello-table-column">
                    <div class="trello-table-card flex-fill">
                        <div class="trello-table-header">
                            <div>
                                <div class="fw-semibold text-dark">Priority Cards</div>
                                <div class="small text-muted">
                                    Card dengan deadline hari ini atau sudah melewati deadline.
                                </div>
                            </div>

                            <span class="badge rounded-pill bg-danger-subtle text-danger">
                                {{ number_format($trelloAcademicPriorityCards->count()) }} card
                            </span>
                        </div>

                        @if($trelloAcademicPriorityCards->count())
                            <div class="table-responsive trello-table-scroll">
                                <table class="table table-modern align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Card</th>
                                            <th>PIC</th>
                                            <th>Status</th>
                                            <th>Due</th>
                                            <th class="text-end">Link</th>
                                        </tr>
                                    </thead>

                                    <tbody
                                        class="trello-load-more-list auto-expand-list is-collapsed"
                                        data-initial-visible="4"
                                    >
                                        @foreach($trelloAcademicPriorityCards as $card)
                                            @php
                                                $cardStatus = \Illuminate\Support\Str::of(
                                                    data_get($card, 'normalized_status')
                                                        ?: data_get($card, 'status')
                                                        ?: 'unmapped'
                                                )
                                                    ->lower()
                                                    ->replace([' ', '-'], '_')
                                                    ->toString();

                                                $cardDueAt = data_get($card, 'due_at')
                                                    ?: data_get($card, 'due')
                                                    ?: data_get($card, 'due_date');

                                                try {
                                                    $cardDueText = $cardDueAt
                                                        ? \Carbon\Carbon::parse($cardDueAt)->format('d M H:i')
                                                        : '-';
                                                } catch (\Throwable) {
                                                    $cardDueText = '-';
                                                }

                                                $cardUrl = data_get($card, 'short_url')
                                                    ?: data_get($card, 'url')
                                                    ?: data_get($card, 'card_url');

                                                $cardMembers = collect(data_get($card, 'members', []));

                                                $cardMemberNames = $cardMembers
                                                    ->pluck('name')
                                                    ->filter()
                                                    ->implode(', ');
                                            @endphp

                                            <tr>
                                                <td>
                                                    <div class="fw-semibold text-dark">
                                                        {{ \Illuminate\Support\Str::limit(
                                                            data_get($card, 'name')
                                                                ?: data_get($card, 'title')
                                                                ?: '-',
                                                            48
                                                        ) }}
                                                    </div>
                                                    <div class="small text-muted">
                                                        {{ data_get($card, 'list_name')
                                                            ?: data_get($card, 'trello_list_name')
                                                            ?: '-' }}
                                                    </div>
                                                </td>

                                                <td>
                                                    <div class="work-card-pic">
                                                        <div class="work-card-avatar-stack">
                                                            @forelse($cardMembers->take(3) as $member)
                                                                <div
                                                                    class="work-card-avatar"
                                                                    title="{{ data_get($member, 'name', 'PIC') }}"
                                                                >
                                                                    @if(filled(data_get($member, 'avatar_url')))
                                                                        <img
                                                                            src="{{ data_get($member, 'avatar_url') }}"
                                                                            alt="{{ data_get($member, 'name', 'PIC') }}"
                                                                            loading="lazy"
                                                                            referrerpolicy="no-referrer"
                                                                            onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';"
                                                                        >
                                                                        <span class="work-card-avatar-fallback">
                                                                            {{ data_get($member, 'initials', '?') }}
                                                                        </span>
                                                                    @else
                                                                        <span>{{ data_get($member, 'initials', '?') }}</span>
                                                                    @endif
                                                                </div>
                                                            @empty
                                                                <div class="work-card-avatar is-empty" title="No PIC">
                                                                    <span>?</span>
                                                                </div>
                                                            @endforelse

                                                            @if($cardMembers->count() > 3)
                                                                <div
                                                                    class="work-card-avatar is-more"
                                                                    title="{{ $cardMembers->count() - 3 }} PIC lainnya"
                                                                >
                                                                    <span>+{{ $cardMembers->count() - 3 }}</span>
                                                                </div>
                                                            @endif
                                                        </div>

                                                        <div class="work-card-pic-name">
                                                            {{ $cardMemberNames
                                                                ? \Illuminate\Support\Str::limit($cardMemberNames, 24)
                                                                : 'No PIC' }}
                                                        </div>
                                                    </div>
                                                </td>

                                                <td>
                                                    <span class="badge rounded-pill {{ $trelloStatusBadgeClasses[$cardStatus] ?? 'bg-light text-muted' }}">
                                                        {{ $trelloStatusLabels[$cardStatus]
                                                            ?? \Illuminate\Support\Str::headline($cardStatus) }}
                                                    </span>
                                                </td>

                                                <td>{{ $cardDueText }}</td>

                                                <td class="text-end">
                                                    @if($cardUrl)
                                                        <a
                                                            href="{{ $cardUrl }}"
                                                            target="_blank"
                                                            rel="noopener"
                                                            class="btn btn-sm btn-light"
                                                        >
                                                            Open
                                                        </a>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="empty-state-box my-0">
                                <div class="empty-state-icon">
                                    <i class="bi bi-check2-circle"></i>
                                </div>
                                <h5 class="empty-state-title">Tidak ada priority card</h5>
                                <p class="empty-state-text mb-0">
                                    Belum ada card Academic dengan deadline hari ini atau overdue.
                                </p>
                            </div>
                        @endif
                    </div>

                    @if($trelloAcademicPriorityCards->count() > 4)
                        <div
                            class="auto-expand-trigger trello-auto-expand-trigger"
                            data-auto-expand-key="trello-academic-priority"
                            aria-hidden="true"
                        ></div>
                    @endif
                </div>

                <div class="col-12 d-flex flex-column trello-table-column">
                    <div class="trello-table-card flex-fill">
                        <div class="trello-table-header">
                            <div>
                                <div class="fw-semibold text-dark">Active Work Queue</div>
                                <div class="small text-muted">
                                    Card aktif yang berada di To Do, Doing, Review, atau Scheduled.
                                </div>
                            </div>

                            <span class="badge rounded-pill bg-primary-subtle text-primary">
                                {{ number_format($trelloAcademicActiveCards->count()) }} card
                            </span>
                        </div>

                        @if($trelloAcademicActiveCards->count())
                            <div class="table-responsive trello-table-scroll">
                                <table class="table table-modern align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Card</th>
                                            <th>PIC</th>
                                            <th>Status</th>
                                            <th>Last Activity</th>
                                            <th class="text-end">Link</th>
                                        </tr>
                                    </thead>

                                    <tbody
                                        class="trello-load-more-list auto-expand-list is-collapsed"
                                        data-initial-visible="4"
                                    >
                                        @foreach($trelloAcademicActiveCards as $card)
                                            @php
                                                $cardStatus = \Illuminate\Support\Str::of(
                                                    data_get($card, 'normalized_status')
                                                        ?: data_get($card, 'status')
                                                        ?: 'unmapped'
                                                )
                                                    ->lower()
                                                    ->replace([' ', '-'], '_')
                                                    ->toString();

                                                $cardLastActivity = data_get($card, 'last_activity_at')
                                                    ?: data_get($card, 'date_last_activity')
                                                    ?: data_get($card, 'updated_at');

                                                try {
                                                    $cardLastActivityText = $cardLastActivity
                                                        ? \Carbon\Carbon::parse($cardLastActivity)->format('d M H:i')
                                                        : '-';
                                                } catch (\Throwable) {
                                                    $cardLastActivityText = '-';
                                                }

                                                $cardUrl = data_get($card, 'short_url')
                                                    ?: data_get($card, 'url')
                                                    ?: data_get($card, 'card_url');

                                                $cardMembers = collect(data_get($card, 'members', []));

                                                $cardMemberNames = $cardMembers
                                                    ->pluck('name')
                                                    ->filter()
                                                    ->implode(', ');
                                            @endphp

                                            <tr>
                                                <td>
                                                    <div class="fw-semibold text-dark">
                                                        {{ \Illuminate\Support\Str::limit(
                                                            data_get($card, 'name')
                                                                ?: data_get($card, 'title')
                                                                ?: '-',
                                                            48
                                                        ) }}
                                                    </div>
                                                    <div class="small text-muted">
                                                        {{ data_get($card, 'list_name')
                                                            ?: data_get($card, 'trello_list_name')
                                                            ?: '-' }}
                                                    </div>
                                                </td>

                                                <td>
                                                    <div class="work-card-pic">
                                                        <div class="work-card-avatar-stack">
                                                            @forelse($cardMembers->take(3) as $member)
                                                                <div
                                                                    class="work-card-avatar"
                                                                    title="{{ data_get($member, 'name', 'PIC') }}"
                                                                >
                                                                    @if(filled(data_get($member, 'avatar_url')))
                                                                        <img
                                                                            src="{{ data_get($member, 'avatar_url') }}"
                                                                            alt="{{ data_get($member, 'name', 'PIC') }}"
                                                                            loading="lazy"
                                                                            referrerpolicy="no-referrer"
                                                                            onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';"
                                                                        >
                                                                        <span class="work-card-avatar-fallback">
                                                                            {{ data_get($member, 'initials', '?') }}
                                                                        </span>
                                                                    @else
                                                                        <span>{{ data_get($member, 'initials', '?') }}</span>
                                                                    @endif
                                                                </div>
                                                            @empty
                                                                <div class="work-card-avatar is-empty" title="No PIC">
                                                                    <span>?</span>
                                                                </div>
                                                            @endforelse

                                                            @if($cardMembers->count() > 3)
                                                                <div
                                                                    class="work-card-avatar is-more"
                                                                    title="{{ $cardMembers->count() - 3 }} PIC lainnya"
                                                                >
                                                                    <span>+{{ $cardMembers->count() - 3 }}</span>
                                                                </div>
                                                            @endif
                                                        </div>

                                                        <div class="work-card-pic-name">
                                                            {{ $cardMemberNames
                                                                ? \Illuminate\Support\Str::limit($cardMemberNames, 24)
                                                                : 'No PIC' }}
                                                        </div>
                                                    </div>
                                                </td>

                                                <td>
                                                    <span class="badge rounded-pill {{ $trelloStatusBadgeClasses[$cardStatus] ?? 'bg-light text-muted' }}">
                                                        {{ $trelloStatusLabels[$cardStatus]
                                                            ?? \Illuminate\Support\Str::headline($cardStatus) }}
                                                    </span>
                                                </td>

                                                <td>{{ $cardLastActivityText }}</td>

                                                <td class="text-end">
                                                    @if($cardUrl)
                                                        <a
                                                            href="{{ $cardUrl }}"
                                                            target="_blank"
                                                            rel="noopener"
                                                            class="btn btn-sm btn-light"
                                                        >
                                                            Open
                                                        </a>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="empty-state-box my-0">
                                <div class="empty-state-icon">
                                    <i class="bi bi-kanban"></i>
                                </div>
                                <h5 class="empty-state-title">Tidak ada active work</h5>
                                <p class="empty-state-text mb-0">
                                    Belum ada card Academic di status To Do, Doing, Review, atau Scheduled.
                                </p>
                            </div>
                        @endif
                    </div>

                    @if($trelloAcademicActiveCards->count() > 4)
                        <div
                            class="auto-expand-trigger trello-auto-expand-trigger"
                            data-auto-expand-key="trello-academic-active"
                            aria-hidden="true"
                        ></div>
                    @endif
                </div>
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

@push('styles')
<style>
    .trello-insight-box {
        background: linear-gradient(135deg, rgba(0, 121, 191, 0.08), rgba(91, 62, 142, 0.06));
        border: 1px solid rgba(0, 121, 191, 0.12);
        border-radius: 18px;
        padding: 1rem;
    }

    .trello-insight-icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        background: rgba(0, 121, 191, 0.12);
        color: #0079BF;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 1.15rem;
    }

    .work-progress-stat-card .stat-icon-wrap {
        background: rgba(91, 62, 142, 0.10);
        color: #5B3E8E;
    }

    .work-progress-completion-card {
        border: 1px solid rgba(91, 62, 142, 0.12);
        border-radius: 20px;
        background: linear-gradient(135deg, rgba(91, 62, 142, 0.07), rgba(255, 190, 4, 0.08));
        padding: 1.25rem;
    }

    .work-progress-completion-eyebrow {
        color: #5B3E8E;
        font-size: .75rem;
        font-weight: 800;
        letter-spacing: .04em;
        text-transform: uppercase;
        margin-bottom: .35rem;
    }

    .work-progress-completion-value {
        color: #111827;
        font-size: 2.25rem;
        font-weight: 900;
        line-height: 1;
    }

    .work-progress-completion-label {
        color: #6b7280;
        font-size: .92rem;
        font-weight: 600;
        margin-top: .35rem;
    }

    .work-progress-completion-meta {
        background: rgba(255, 255, 255, 0.72);
        border: 1px solid rgba(15, 23, 42, 0.06);
        border-radius: 16px;
        padding: .75rem 1rem;
    }

    .work-progress-completion-track {
        height: 10px;
        background: rgba(91, 62, 142, 0.10);
    }

    .work-progress-mini-metric {
        background: #ffffff;
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 16px;
        padding: .9rem 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
    }

    .work-progress-mini-metric span {
        color: #6b7280;
        font-size: .85rem;
        font-weight: 700;
    }

    .work-progress-mini-metric strong {
        color: #111827;
        font-size: 1.2rem;
        font-weight: 900;
    }

    .trello-table-row {
        align-items: stretch;
    }

    .trello-table-row > [class*="col-"] {
        display: flex;
        align-items: stretch;
    }

    .trello-table-column {
        gap: .8rem;
    }

    .trello-table-column + .trello-table-column {
        margin-top: 1.35rem;
    }

    .trello-table-card {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 18px;
        background: #ffffff;
        overflow: hidden;
        width: 100%;
        min-height: 0;
        display: flex;
        flex-direction: column;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.04);
    }

    .trello-table-header {
        padding: 1rem 1rem .85rem;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        flex: 0 0 auto;
        min-height: 78px;
        border-bottom: 1px solid rgba(15, 23, 42, 0.06);
        background: linear-gradient(180deg, #ffffff 0%, rgba(248, 250, 252, 0.82) 100%);
    }

    .trello-table-header .badge {
        flex: 0 0 auto;
        white-space: nowrap;
    }

    .trello-table-scroll {
        flex: 0 0 auto;
        overflow-x: visible;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
    }

    .trello-table-scroll table {
        width: 100%;
        min-width: 0;
        table-layout: fixed;
    }

    .trello-table-scroll th,
    .trello-table-scroll td {
        white-space: normal;
        overflow-wrap: anywhere;
    }

    .trello-table-scroll th:nth-child(1),
    .trello-table-scroll td:nth-child(1) {
        width: 38%;
    }

    .trello-table-scroll th:nth-child(2),
    .trello-table-scroll td:nth-child(2) {
        width: 20%;
    }

    .trello-table-scroll th:nth-child(3),
    .trello-table-scroll td:nth-child(3) {
        width: 16%;
    }

    .trello-table-scroll th:nth-child(4),
    .trello-table-scroll td:nth-child(4) {
        width: 16%;
    }

    .trello-table-scroll th:nth-child(5),
    .trello-table-scroll td:nth-child(5) {
        width: 10%;
    }

    .trello-table-scroll thead th {
        position: sticky;
        top: 0;
        z-index: 3;
        background: rgba(255, 255, 255, 0.96);
        backdrop-filter: blur(8px);
        box-shadow: inset 0 -1px 0 rgba(15, 23, 42, 0.08);
    }

    .trello-table-scroll tbody tr {
        transition: background-color .18s ease;
    }

    .trello-table-scroll tbody tr:hover {
        background: rgba(91, 62, 142, 0.035);
    }

    .trello-table-scroll tbody tr:last-child td {
        border-bottom: 0;
    }

    .trello-table-card .empty-state-box {
        flex: 1 1 auto;
        min-height: 310px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .work-card-pic {
        min-width: 112px;
    }

    .work-card-avatar-stack {
        display: flex;
        align-items: center;
        margin-bottom: .35rem;
        min-height: 30px;
    }

    .work-card-avatar {
        width: 30px;
        height: 30px;
        border-radius: 999px;
        overflow: hidden;
        background: rgba(91, 62, 142, 0.12);
        color: #5B3E8E;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        border: 2px solid #ffffff;
        box-shadow: 0 6px 14px rgba(15, 23, 42, 0.10);
        font-size: .72rem;
        font-weight: 900;
        letter-spacing: .02em;
    }

    .work-card-avatar + .work-card-avatar {
        margin-left: -8px;
    }

    .work-card-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .work-card-avatar span {
        width: 100%;
        height: 100%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .work-card-avatar img + .work-card-avatar-fallback {
        display: none;
    }

    .work-card-avatar.is-empty {
        background: rgba(107, 114, 128, 0.12);
        color: #6b7280;
    }

    .work-card-avatar.is-more {
        background: #111827;
        color: #ffffff;
        font-size: .65rem;
    }

    .work-card-pic-name {
        color: #6b7280;
        font-size: .76rem;
        font-weight: 700;
        line-height: 1.2;
        max-width: 132px;
        word-break: break-word;
    }

    .auto-expand-list.is-collapsed tr:nth-child(n+5),
    .trello-load-more-list.is-collapsed tr:nth-child(n+5) {
        display: none;
    }

    .auto-expand-list tr {
        transition: background-color .18s ease;
    }

    .auto-expand-trigger {
        width: 100%;
        height: 1px;
        pointer-events: none;
        opacity: 0;
    }

    .auto-expand-list:not(.is-collapsed) tr:nth-child(n+5) {
        animation: academicTrelloAutoExpandFadeIn .22s ease both;
    }

    @keyframes academicTrelloAutoExpandFadeIn {
        from {
            opacity: 0;
            transform: translateY(-4px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .bg-purple-subtle {
        background-color: rgba(91, 62, 142, 0.12) !important;
    }

    .text-purple {
        color: #5B3E8E !important;
    }

    @media (max-width: 767.98px) {
        .trello-table-header {
            flex-direction: column;
            align-items: flex-start;
            min-height: auto;
        }

        .trello-table-scroll {
            overflow-x: auto;
        }

        .trello-table-scroll table {
            table-layout: auto;
            min-width: 720px;
        }

        .trello-table-scroll th,
        .trello-table-scroll td {
            font-size: .78rem;
        }
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const expandList = function (list) {
        if (!list || !list.classList.contains('is-collapsed')) {
            return;
        }

        list.classList.remove('is-collapsed');
    };

    const expandSectionFromTrigger = function (trigger) {
        const column = trigger.closest('.trello-table-column');
        const list = column ? column.querySelector('.auto-expand-list') : null;

        expandList(list);
    };

    if ('IntersectionObserver' in window) {
        const autoExpandObserver = new IntersectionObserver(function (entries, observer) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) {
                    return;
                }

                expandSectionFromTrigger(entry.target);
                observer.unobserve(entry.target);
            });
        }, {
            root: null,
            threshold: 0.01,
            rootMargin: '0px 0px -8% 0px'
        });

        document.querySelectorAll('.trello-auto-expand-trigger').forEach(function (trigger) {
            autoExpandObserver.observe(trigger);
        });
    } else {
        document.querySelectorAll('.trello-auto-expand-trigger').forEach(expandSectionFromTrigger);
    }
});
</script>
@endpush
