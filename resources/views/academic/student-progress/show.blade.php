@extends('layouts.app-dashboard')

@section('title', 'Student Progress Detail')

@section('content')
@php
    $backParams = request()->only(['batch_id', 'stage_id']);
    $overallProgress = max(0, min(100, (float) ($studentSummary['overall_progress'] ?? 0)));

    $monitoringBadgeClass = match($studentSummary['monitoring_status_badge'] ?? 'secondary') {
        'success' => 'bg-success-subtle text-success-emphasis border border-success-subtle',
        'primary' => 'bg-primary-subtle text-primary-emphasis border border-primary-subtle',
        'info' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
        'warning' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
        'danger' => 'bg-danger-subtle text-danger-emphasis border border-danger-subtle',
        default => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
    };

    $monitoringIcon = match($studentSummary['monitoring_status'] ?? 'Not Started') {
        'Completed' => 'bi bi-check-circle-fill',
        'On Track' => 'bi bi-rocket-takeoff-fill',
        'In Progress' => 'bi bi-play-circle-fill',
        'At Risk' => 'bi bi-exclamation-circle-fill',
        'Need Follow Up' => 'bi bi-telephone-outbound-fill',
        default => 'bi bi-hourglass-split',
    };

    $programNames = ($studentSummary['program_names'] ?? collect())->isNotEmpty()
        ? $studentSummary['program_names']->implode(', ')
        : '-';

    $batchNames = ($studentSummary['batch_names'] ?? collect())->isNotEmpty()
        ? $studentSummary['batch_names']->implode(', ')
        : '-';

    $liveSessionCount = collect($lessons ?? [])
        ->filter(function ($lesson) {
            $type = strtolower(str_replace('-', '_', (string) ($lesson->lesson_type ?? '')));

            return in_array($type, [
                'live',
                'live_session',
                'online_session',
                'offline_session',
                'mentoring',
            ], true);
        })
        ->count();

    $videoLessonCount = collect($lessons ?? [])
        ->filter(function ($lesson) {
            $type = strtolower(str_replace('-', '_', (string) ($lesson->lesson_type ?? '')));

            return $type === 'video';
        })
        ->count();
@endphp

<div class="container-fluid px-4 py-4">

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Academic Monitoring</div>

                <h1 class="page-title mb-2">
                    Student Progress Detail
                </h1>

                <p class="page-subtitle mb-0">
                    Detail progress materi student berdasarkan program, batch, stage, module, topic,
                    dan sub topic yang tersedia.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <a
                    href="{{ route('academic.student-progress.index', $backParams) }}"
                    class="btn btn-light btn-modern"
                >
                    <i class="bi bi-arrow-left me-2"></i>Back
                </a>
            </div>
        </div>
    </div>

    {{-- Student Data Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-4 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-person-fill"></i>
                    </div>

                    <div class="min-w-0">
                        <div class="stat-title">Student</div>
                        <div class="fw-bold text-dark text-truncate" title="{{ $student->full_name ?? '-' }}">
                            {{ $student->full_name ?? '-' }}
                        </div>
                    </div>
                </div>

                <div class="stat-description text-truncate" title="{{ $student->email ?? '-' }}">
                    {{ $student->email ?? '-' }}
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-telephone-fill"></i>
                    </div>

                    <div class="min-w-0">
                        <div class="stat-title">Contact</div>
                        <div class="fw-bold text-dark text-truncate" title="{{ $student->phone ?? '-' }}">
                            {{ $student->phone ?? '-' }}
                        </div>
                    </div>
                </div>

                <div class="stat-description text-truncate" title="{{ $student->city ?? '-' }}">
                    <i class="bi bi-geo-alt me-1"></i>{{ $student->city ?? '-' }}
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-12">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>

                    <div class="min-w-0">
                        <div class="stat-title">Program & Batch</div>
                        <div class="fw-bold text-dark text-truncate" title="{{ $programNames }}">
                            {{ $programNames }}
                        </div>
                    </div>
                </div>

                <div class="stat-description text-truncate" title="{{ $batchNames }}">
                    <i class="bi bi-collection-fill me-1"></i>{{ $batchNames }}
                </div>
            </div>
        </div>
    </div>

    {{-- Progress Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-xl col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-bar-chart-line-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Progress</div>
                        <div class="stat-value">
                            {{ $studentSummary['overall_progress_label'] ?? '0%' }}
                        </div>
                    </div>
                </div>
                <div class="stat-description">Total progress materi.</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Completed</div>
                        <div class="stat-value">
                            {{ number_format($studentSummary['completed_lessons'] ?? 0) }}
                        </div>
                    </div>
                </div>
                <div class="stat-description">Materi sudah selesai.</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-play-circle-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Opened</div>
                        <div class="stat-value">
                            {{ number_format($studentSummary['opened_lessons'] ?? 0) }}
                        </div>
                    </div>
                </div>
                <div class="stat-description">Materi pernah dibuka.</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-journals"></i>
                    </div>
                    <div>
                        <div class="stat-title">Total Lessons</div>
                        <div class="stat-value">
                            {{ number_format($studentSummary['total_lessons'] ?? 0) }}
                        </div>
                    </div>
                </div>
                <div class="stat-description">
                    {{ number_format($videoLessonCount) }} video • {{ number_format($liveSessionCount) }} live
                </div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="{{ $monitoringIcon }}"></i>
                    </div>
                    <div>
                        <div class="stat-title">Monitoring</div>
                        <div>
                            <span class="badge rounded-pill {{ $monitoringBadgeClass }}">
                                {{ $studentSummary['monitoring_status'] ?? 'Not Started' }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="stat-description">
                    {{ $studentSummary['last_activity_label'] ?? '-' }}
                </div>
            </div>
        </div>
    </div>

    {{-- Overall Progress Bar --}}
    <div class="content-card mb-4">
        <div class="content-card-body">
            <div class="d-flex justify-content-between align-items-center gap-3 mb-2 flex-wrap">
                <div>
                    <div class="small text-muted fw-semibold">Overall Progress</div>
                    <div class="fw-bold text-dark">
                        {{ $studentSummary['overall_progress_label'] ?? '0%' }}
                    </div>
                </div>

                <div class="text-md-end">
                    <div class="small text-muted fw-semibold">Last Activity</div>
                    <div class="fw-bold text-dark">
                        {{ $studentSummary['last_activity_label'] ?? '-' }}
                    </div>
                </div>
            </div>

            <div class="progress" style="height: 12px; border-radius: 999px;">
                <div
                    class="progress-bar"
                    role="progressbar"
                    style="width: {{ $overallProgress }}%; border-radius: 999px;"
                    aria-valuenow="{{ $overallProgress }}"
                    aria-valuemin="0"
                    aria-valuemax="100"
                ></div>
            </div>

            <div class="small text-muted mt-2">
                @if(is_null($studentSummary['inactive_days'] ?? null))
                    Student belum memiliki aktivitas materi.
                @elseif((int) $studentSummary['inactive_days'] === 0)
                    Student aktif hari ini.
                @else
                    Student terakhir aktif {{ (int) $studentSummary['inactive_days'] }} hari lalu.
                @endif
            </div>
        </div>
    </div>

    {{-- Lesson Table --}}
    <div class="content-card">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Lesson Progress Detail</h5>
                <p class="content-card-subtitle mb-0">
                    Video progress dibaca dari posisi tontonan, sedangkan live session selesai ketika student menandai materi sebagai completed.
                </p>
            </div>

            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="small text-muted">Total</span>
                <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis border border-primary-subtle">
                    {{ number_format($lessons->count()) }} lessons
                </span>
            </div>
        </div>

        <div class="content-card-body p-0">
            @if($lessons->count())
                <div class="student-table-responsive dropdown-safe-table">
                    <table class="table table-hover align-middle admin-table student-admin-table mb-0">
                        <thead>
                            <tr>
                                <th class="text-nowrap" style="width: 70px;">No</th>
                                <th class="text-nowrap">Stage</th>
                                <th class="text-nowrap">Module</th>
                                <th class="text-nowrap">Topic</th>
                                <th class="text-nowrap">Sub Topic</th>
                                <th class="text-nowrap">Type</th>
                                <th class="text-nowrap" style="min-width: 230px;">Progress</th>
                                <th class="text-nowrap">Learning Data</th>
                                <th class="text-nowrap">Last Activity</th>
                                <th class="text-nowrap">Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($lessons as $lesson)
                                @php
                                    $lessonType = strtolower(str_replace('-', '_', (string) ($lesson->lesson_type ?? '')));
                                    $isLiveSession = in_array($lessonType, [
                                        'live',
                                        'live_session',
                                        'online_session',
                                        'offline_session',
                                        'mentoring',
                                    ], true);

                                    $isVideoLesson = $lessonType === 'video';

                                    $progressValue = max(0, min(100, (float) ($lesson->progress_percentage ?? 0)));
                                    $isCompleted = (bool) ($lesson->is_completed ?? false) || $progressValue >= 95;

                                    $lessonStatus = (string) ($lesson->lesson_status ?? 'Not Started');

                                    if ($isLiveSession && ! $isCompleted) {
                                        $lessonStatus = 'Awaiting Completion';
                                    }

                                    $lessonBadgeClass = match(true) {
                                        $isCompleted => 'bg-success-subtle text-success-emphasis border border-success-subtle',
                                        $isLiveSession => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
                                        $lessonStatus === 'In Progress' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
                                        default => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
                                    };

                                    $lessonIcon = match(true) {
                                        $isCompleted => 'bi bi-check-circle-fill',
                                        $isLiveSession => 'bi bi-broadcast-pin',
                                        $lessonStatus === 'In Progress' => 'bi bi-play-circle-fill',
                                        default => 'bi bi-hourglass-split',
                                    };

                                    $typeLabel = $lessonType
                                        ? ucwords(str_replace('_', ' ', $lessonType))
                                        : '-';

                                    $typeBadgeClass = match(true) {
                                        $isVideoLesson => 'bg-primary-subtle text-primary-emphasis border border-primary-subtle',
                                        $isLiveSession => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
                                        default => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
                                    };

                                    $progressCaption = $isLiveSession
                                        ? ($isCompleted ? 'Manual completed' : 'Manual completion')
                                        : 'Video progress';

                                    $learningDataTitle = $isLiveSession
                                        ? 'Live Session'
                                        : ($lesson->duration_label ?? '-');

                                    $learningDataSubtitle = $isLiveSession
                                        ? 'No video duration tracking'
                                        : 'Duration';

                                    if ($isLiveSession && ! empty($lesson->duration_label) && $lesson->duration_label !== '-') {
                                        $learningDataSubtitle = 'Estimate: ' . $lesson->duration_label;
                                    }
                                @endphp

                                <tr>
                                    <td class="text-muted">
                                        {{ $loop->iteration }}
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark text-truncate" style="max-width: 180px;" title="{{ $lesson->stage_name ?? '-' }}">
                                            {{ $lesson->stage_name ?? '-' }}
                                        </div>
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark text-truncate" style="max-width: 190px;" title="{{ $lesson->module_name ?? '-' }}">
                                            {{ $lesson->module_name ?? '-' }}
                                        </div>
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark text-truncate" style="max-width: 210px;" title="{{ $lesson->topic_name ?? '-' }}">
                                            {{ $lesson->topic_name ?? '-' }}
                                        </div>
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark text-truncate" style="max-width: 260px;" title="{{ $lesson->name ?? '-' }}">
                                            {{ $lesson->name ?? '-' }}
                                        </div>

                                        @if(!empty($lesson->description))
                                            <div class="small text-muted text-truncate" style="max-width: 260px;" title="{{ $lesson->description }}">
                                                {{ $lesson->description }}
                                            </div>
                                        @endif
                                    </td>

                                    <td>
                                        <span class="badge rounded-pill {{ $typeBadgeClass }}">
                                            @if($isLiveSession)
                                                <i class="bi bi-broadcast-pin me-1"></i>
                                            @elseif($isVideoLesson)
                                                <i class="bi bi-play-circle me-1"></i>
                                            @else
                                                <i class="bi bi-journal-text me-1"></i>
                                            @endif

                                            {{ $typeLabel }}
                                        </span>
                                    </td>

                                    <td>
                                        <div class="d-flex justify-content-between align-items-center gap-3 mb-2">
                                            <div class="fw-semibold text-dark">
                                                {{ $lesson->progress_label ?? '0%' }}
                                            </div>

                                            <div class="small text-muted">
                                                {{ $progressCaption }}
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

                                        @if($isLiveSession)
                                            <div class="mt-2">
                                                <span class="badge rounded-pill {{ $isCompleted ? 'bg-success-subtle text-success-emphasis border border-success-subtle' : 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' }}">
                                                    {{ $isCompleted ? 'Marked completed' : 'Waiting for student action' }}
                                                </span>
                                            </div>
                                        @else
                                            <div class="small text-muted mt-2">
                                                Position: {{ $lesson->last_position_label ?? '-' }}
                                            </div>
                                        @endif
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ $learningDataTitle }}
                                        </div>

                                        <div class="small text-muted">
                                            {{ $learningDataSubtitle }}
                                        </div>
                                    </td>

                                    <td>
                                        @if($isLiveSession && ! $isCompleted && empty($lesson->last_watched_at))
                                            <div class="fw-semibold text-muted">
                                                Not marked yet
                                            </div>

                                            <div class="small text-muted">
                                                Waiting for manual completion
                                            </div>
                                        @else
                                            <div class="fw-semibold text-dark">
                                                {{ $lesson->last_watched_label ?? '-' }}
                                            </div>

                                            @if(!empty($lesson->completed_at))
                                                <div class="small text-muted">
                                                    Completed: {{ $lesson->completed_at_label ?? '-' }}
                                                </div>
                                            @endif
                                        @endif
                                    </td>

                                    <td>
                                        <span class="badge rounded-pill {{ $lessonBadgeClass }}">
                                            <i class="{{ $lessonIcon }} me-1"></i>
                                            {{ $lessonStatus }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5 px-3">
                    <div class="mb-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary" style="width: 64px; height: 64px;">
                            <i class="bi bi-journal-x fs-3"></i>
                        </span>
                    </div>

                    <h5 class="fw-semibold text-dark mb-2">
                        Materi belum ditemukan
                    </h5>

                    <p class="text-muted mb-4">
                        Student ini belum memiliki materi aktif dari program, batch, atau stage yang dipilih.
                    </p>

                    <a href="{{ route('academic.student-progress.index', $backParams) }}" class="btn btn-primary btn-modern">
                        <i class="bi bi-arrow-left me-2"></i>Back to Monitoring
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
