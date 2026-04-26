@extends('layouts.app-dashboard')

@section('title', 'Mentoring Sessions')

@php
    $routePrefix = Route::has('academic.mentoring-sessions.index')
        ? 'academic.mentoring-sessions'
        : 'mentoring-sessions';

    $indexRoute = Route::has($routePrefix . '.index')
        ? route($routePrefix . '.index')
        : url()->current();

    $showRouteTemplate = Route::has($routePrefix . '.show')
        ? route($routePrefix . '.show', ['studentMentoringSession' => '__ID__'])
        : '#';

    $approveRouteTemplate = Route::has($routePrefix . '.approve')
        ? route($routePrefix . '.approve', ['studentMentoringSession' => '__ID__'])
        : '#';

    $rejectRouteTemplate = Route::has($routePrefix . '.reject')
        ? route($routePrefix . '.reject', ['studentMentoringSession' => '__ID__'])
        : '#';

    $completeRouteTemplate = Route::has($routePrefix . '.complete')
        ? route($routePrefix . '.complete', ['studentMentoringSession' => '__ID__'])
        : '#';

    $cancelRouteTemplate = Route::has($routePrefix . '.cancel')
        ? route($routePrefix . '.cancel', ['studentMentoringSession' => '__ID__'])
        : '#';

    $meetingUrlRouteTemplate = Route::has($routePrefix . '.meeting-url')
        ? route($routePrefix . '.meeting-url', ['studentMentoringSession' => '__ID__'])
        : '#';

    $statusOptions = [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rescheduled' => 'Rescheduled',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'rejected' => 'Rejected',
    ];

    $statusBadgeMap = [
        'pending' => 'ui-badge-warning',
        'approved' => 'ui-badge-primary',
        'rescheduled' => 'ui-badge-info',
        'completed' => 'ui-badge-success',
        'cancelled' => 'ui-badge-muted',
        'rejected' => 'ui-badge-danger',
    ];

    $topicOptions = [
        'code_review' => 'Code Review',
        'debugging' => 'Debugging',
        'project_consultation' => 'Project Consultation',
        'career_portfolio' => 'Career / Portfolio',
        'lesson_discussion' => 'Lesson Discussion',
        'other' => 'Other',
    ];

    $closedCount = ($stats['completed'] ?? 0) + ($stats['cancelled'] ?? 0);

    $pageStats = [
        'total' => $stats['total'] ?? 0,
        'pending' => $stats['pending'] ?? 0,
        'approved' => $stats['approved'] ?? 0,
        'closed' => $closedCount,
    ];

    $currentSearch = $search ?? request('search');
    $currentStatus = $status ?? request('status');
    $currentDate = $date ?? request('date');
    $currentPerPage = (int) request('per_page', 10);
@endphp

@section('content')
<div class="container-fluid px-4 py-4">
    <div
        id="toastContainer"
        class="toast-container position-fixed top-0 end-0 p-3"
        style="z-index: 9999;"
    ></div>

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Academic Management</div>

                <h1 class="page-title mb-2">Mentoring Sessions</h1>

                <p class="page-subtitle mb-0">
                    Kelola booking 1-on-1 mentoring dari student, mulai dari approve, reject, meeting link, sampai completed.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <a
                    href="{{ $indexRoute }}"
                    class="btn btn-light btn-modern"
                >
                    <i class="bi bi-arrow-counterclockwise me-2"></i>Reset Filter
                </a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-chat-square-text-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Total Sessions</div>
                        <div class="stat-value">{{ $pageStats['total'] }}</div>
                    </div>
                </div>
                <div class="stat-description">Semua booking 1-on-1 mentoring.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div>
                        <div class="stat-title">Pending</div>
                        <div class="stat-value">{{ $pageStats['pending'] }}</div>
                    </div>
                </div>
                <div class="stat-description">Menunggu approval admin/instructor.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-camera-video-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Approved</div>
                        <div class="stat-value">{{ $pageStats['approved'] }}</div>
                    </div>
                </div>
                <div class="stat-description">Session yang sudah siap berjalan.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div>
                        <div class="stat-title">Closed</div>
                        <div class="stat-value">{{ $pageStats['closed'] }}</div>
                    </div>
                </div>
                <div class="stat-description">Completed, cancelled, atau rejected.</div>
            </div>
        </div>
    </div>

    <div class="content-card">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Session List</h5>
                <p class="content-card-subtitle mb-0">
                    Data booking 1-on-1 yang masuk dari LMS student.
                </p>
            </div>

            <form method="GET" action="{{ $indexRoute }}" class="d-flex align-items-center gap-2 flex-wrap">
                <input type="hidden" name="search" value="{{ $currentSearch }}">
                <input type="hidden" name="date" value="{{ $currentDate }}">
                <input type="hidden" name="status" value="{{ $currentStatus }}">

                <label for="per_page" class="form-label mb-0 text-muted">Show</label>

                <select
                    name="per_page"
                    id="per_page"
                    class="form-select form-select-sm"
                    style="width: 90px;"
                    onchange="this.form.submit()"
                >
                    @foreach ([10, 25, 50, 100] as $size)
                        <option value="{{ $size }}" {{ $currentPerPage === $size ? 'selected' : '' }}>
                            {{ $size }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="content-card-body border-bottom">
            <form method="GET" action="{{ $indexRoute }}" class="row g-3 align-items-end">
                <input type="hidden" name="per_page" value="{{ $currentPerPage }}">

                <div class="col-xl-5 col-md-6">
                    <label for="search" class="form-label">Search</label>
                    <input
                        type="text"
                        name="search"
                        id="search"
                        class="form-control"
                        value="{{ $currentSearch }}"
                        placeholder="Student, instructor, topic, notes..."
                    >
                </div>

                <div class="col-xl-2 col-md-6">
                    <label for="date" class="form-label">Session Date</label>
                    <input
                        type="date"
                        name="date"
                        id="date"
                        class="form-control"
                        value="{{ $currentDate }}"
                    >
                </div>

                <div class="col-xl-3 col-md-6">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">All status</option>
                        @foreach($statusOptions as $value => $label)
                            <option
                                value="{{ $value }}"
                                {{ (string) $currentStatus === (string) $value ? 'selected' : '' }}
                            >
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-2 col-md-6">
                    <div class="d-flex gap-2">
                        <a href="{{ $indexRoute }}" class="btn btn-outline-secondary btn-modern w-100">
                            Clear
                        </a>

                        <button type="submit" class="btn btn-primary btn-modern w-100">
                            Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="content-card-body p-0">
            @if(($sessions ?? collect())->count())
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 70px;">No</th>
                                <th>Student</th>
                                <th>Instructor</th>
                                <th>Schedule</th>
                                <th>Topic</th>
                                <th>Status</th>
                                <th>Meeting</th>
                                <th class="text-end" style="width: 270px; min-width: 270px;">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($sessions as $session)
                                @php
                                    $statusValue = $session->status ?? 'pending';
                                    $statusLabel = $statusOptions[$statusValue] ?? \Illuminate\Support\Str::title(str_replace('_', ' ', $statusValue));
                                    $statusBadge = $statusBadgeMap[$statusValue] ?? 'ui-badge-muted';

                                    $topicLabel = $session->topic_type_label
                                        ?? ($topicOptions[$session->topic_type] ?? \Illuminate\Support\Str::title(str_replace('_', ' ', $session->topic_type ?? '-')));

                                    $slot = $session->availabilitySlot;

                                    $dateLabel = $slot?->date
                                        ? \Illuminate\Support\Carbon::parse($slot->date)->format('d M Y')
                                        : '-';

                                    $dateMachine = $slot?->date
                                        ? \Illuminate\Support\Carbon::parse($slot->date)->format('Y-m-d')
                                        : '-';

                                    $startTime = $slot?->start_time ? substr($slot->start_time, 0, 5) : '-';
                                    $endTime = $slot?->end_time ? substr($slot->end_time, 0, 5) : '-';

                                    $studentName = $session->student?->full_name ?? 'Student not found';
                                    $studentInitial = strtoupper(mb_substr($studentName, 0, 1));

                                    $instructorName = $session->instructor?->name ?? 'Instructor not found';
                                    $instructorInitial = strtoupper(mb_substr($instructorName, 0, 1));

                                    $requestedLabel = $session->requested_at
                                        ? $session->requested_at->format('d M Y H:i')
                                        : ($session->created_at ? $session->created_at->format('d M Y H:i') : '-');

                                    $canApprove = $statusValue === 'pending';
                                    $canReject = in_array($statusValue, ['pending', 'approved'], true);
                                    $canCancel = in_array($statusValue, ['pending', 'approved', 'rescheduled'], true);
                                    $canComplete = $statusValue === 'approved';
                                    $canUpdateMeeting = in_array($statusValue, ['pending', 'approved'], true);
                                @endphp

                                <tr>
                                    <td>
                                        @if(method_exists($sessions, 'currentPage'))
                                            {{ ($sessions->currentPage() - 1) * $sessions->perPage() + $loop->iteration }}
                                        @else
                                            {{ $loop->iteration }}
                                        @endif
                                    </td>

                                    <td>
                                        <div class="entity-main">
                                            <div class="entity-icon">
                                                {{ $studentInitial }}
                                            </div>

                                            <div class="entity-info">
                                                <div class="entity-title-row">
                                                    <div>
                                                        <h6 class="entity-title mb-1">{{ $studentName }}</h6>
                                                        <div class="entity-subtitle">
                                                            {{ $session->student?->email ?: '-' }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="entity-main">
                                            <div class="entity-icon">
                                                {{ $instructorInitial }}
                                            </div>

                                            <div class="entity-info">
                                                <div class="entity-title-row">
                                                    <div>
                                                        <h6 class="entity-title mb-1">{{ $instructorName }}</h6>
                                                        <div class="entity-subtitle">
                                                            {{ $session->instructor?->specialization ?: 'General Instructor' }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="fw-semibold">{{ $dateLabel }}</div>
                                        <div class="text-muted small">
                                            {{ $startTime }} - {{ $endTime }}
                                        </div>
                                    </td>

                                    <td>
                                        <span class="ui-badge ui-badge-muted">
                                            {{ $topicLabel }}
                                        </span>

                                        <div class="text-muted small mt-1">
                                            Requested: {{ $requestedLabel }}
                                        </div>
                                    </td>

                                    <td>
                                        <span class="ui-badge {{ $statusBadge }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>

                                    <td>
                                        @if($session->meeting_url)
                                            <a
                                                href="{{ $session->meeting_url }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="btn btn-outline-primary btn-sm btn-modern text-nowrap"
                                            >
                                                <i class="bi bi-camera-video me-1"></i>Open
                                            </a>
                                        @else
                                            <span class="ui-badge ui-badge-muted">No URL</span>
                                        @endif
                                    </td>

                                    <td>
                                        <div class="d-flex justify-content-end gap-2 flex-nowrap">
                                            <button
                                                type="button"
                                                class="btn btn-outline-secondary btn-sm btn-modern text-nowrap"
                                                onclick="openDetailModal({{ $session->id }})"
                                            >
                                                <i class="bi bi-eye me-1"></i>Detail
                                            </button>

                                            <div class="dropdown">
                                                <button
                                                    class="btn btn-outline-primary btn-sm btn-modern dropdown-toggle text-nowrap"
                                                    type="button"
                                                    data-bs-toggle="dropdown"
                                                    aria-expanded="false"
                                                >
                                                    Manage
                                                </button>

                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    @if($canApprove)
                                                        <li>
                                                            <button
                                                                type="button"
                                                                class="dropdown-item"
                                                                onclick="openApproveModal({{ $session->id }}, @js($studentName), @js($instructorName), @js($session->meeting_url))"
                                                            >
                                                                <i class="bi bi-check-circle me-2"></i>Approve
                                                            </button>
                                                        </li>
                                                    @endif

                                                    @if($canUpdateMeeting)
                                                        <li>
                                                            <button
                                                                type="button"
                                                                class="dropdown-item"
                                                                onclick="openMeetingUrlModal({{ $session->id }}, @js($studentName), @js($session->meeting_url))"
                                                            >
                                                                <i class="bi bi-link-45deg me-2"></i>Update Meeting URL
                                                            </button>
                                                        </li>
                                                    @endif

                                                    @if($canComplete)
                                                        <li>
                                                            <button
                                                                type="button"
                                                                class="dropdown-item"
                                                                onclick="completeSession({{ $session->id }}, @js($studentName))"
                                                            >
                                                                <i class="bi bi-check2-square me-2"></i>Mark Completed
                                                            </button>
                                                        </li>
                                                    @endif

                                                    @if($canReject)
                                                        <li>
                                                            <button
                                                                type="button"
                                                                class="dropdown-item text-danger"
                                                                onclick="openReasonModal('reject', {{ $session->id }}, @js($studentName))"
                                                            >
                                                                <i class="bi bi-x-circle me-2"></i>Reject
                                                            </button>
                                                        </li>
                                                    @endif

                                                    @if($canCancel)
                                                        <li>
                                                            <button
                                                                type="button"
                                                                class="dropdown-item text-danger"
                                                                onclick="openReasonModal('cancel', {{ $session->id }}, @js($studentName))"
                                                            >
                                                                <i class="bi bi-slash-circle me-2"></i>Cancel
                                                            </button>
                                                        </li>
                                                    @endif

                                                    @if(!$canApprove && !$canUpdateMeeting && !$canComplete && !$canReject && !$canCancel)
                                                        <li>
                                                            <span class="dropdown-item text-muted">
                                                                No action available
                                                            </span>
                                                        </li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if(method_exists($sessions, 'hasPages') && $sessions->hasPages())
                    <div class="p-3 border-top">
                        {{ $sessions->links() }}
                    </div>
                @endif
            @else
               
                <div class="d-flex align-items-center justify-content-center px-4 py-5" style="min-height: 360px;">
                    <div class="text-center" style="max-width: 440px;">
                        <div
                            class="mx-auto mb-4 d-flex align-items-center justify-content-center rounded-4"
                            style="width: 76px; height: 76px; background: #E8DBFF; color: #5B3E8E;"
                        >
                            <i class="bi bi-chat-square-text fs-2"></i>
                        </div>

                        <h5 class="mb-2 fw-bold text-dark">
                            No mentoring sessions found
                        </h5>

                        <p class="mb-0 text-muted">
                            Booking 1-on-1 dari student akan muncul di sini setelah student memilih instructor dan jadwal mentoring.
                        </p>
                    </div>
                </div>
           
            @endif
        </div>
    </div>
</div>

{{-- Detail Modal --}}
<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title">Mentoring Session Detail</h5>
                    <p class="text-muted small mb-0">
                        Detail booking 1-on-1 session dari student.
                    </p>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pt-4">
                <div id="detailLoading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="mt-3 text-muted">Loading detail...</div>
                </div>

                <div id="detailContent" class="d-none">
                    <div class="entity-card mb-4">
                        <div class="entity-main">
                            <div class="entity-icon">
                                <i class="bi bi-chat-square-text-fill"></i>
                            </div>

                            <div class="entity-info">
                                <div class="entity-title-row">
                                    <div>
                                        <h6 class="entity-title mb-1" id="detailStudentName">-</h6>
                                        <div class="entity-subtitle" id="detailStudentContact">-</div>
                                    </div>

                                    <div class="entity-badges">
                                        <span class="ui-badge ui-badge-primary" id="detailStatus">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="stat-card h-100">
                                <div class="stat-title">Instructor</div>
                                <div class="stat-value fs-5" id="detailInstructorName">-</div>
                                <div class="stat-description" id="detailInstructorMeta">-</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="stat-card h-100">
                                <div class="stat-title">Schedule</div>
                                <div class="stat-value fs-5" id="detailScheduleDate">-</div>
                                <div class="stat-description" id="detailScheduleTime">-</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="stat-card h-100">
                                <div class="stat-title">Topic</div>
                                <div class="stat-value fs-5" id="detailTopic">-</div>
                                <div class="stat-description">Student mentoring topic</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="stat-card h-100">
                                <div class="stat-title">Meeting URL</div>
                                <div class="stat-value fs-6 text-break" id="detailMeetingUrl">-</div>
                                <div class="stat-description">Online session link</div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Student Notes</label>
                            <div class="entity-card">
                                <div class="entity-subtitle white-space-pre-line" id="detailNotes">
                                    -
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="detailError" class="alert alert-danger d-none mb-0"></div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Approve Modal --}}
<div class="modal fade" id="approveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="approveForm">
            @csrf
            <input type="hidden" id="approve_session_id">

            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title">Approve Mentoring Session</h5>
                        <p class="text-muted small mb-0">
                            Approve booking dan isi meeting URL kalau sudah tersedia.
                        </p>
                    </div>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-4">
                    <div id="approveAlert" class="alert alert-danger d-none mb-3"></div>

                    <div class="entity-card mb-4">
                        <div class="entity-main">
                            <div class="entity-icon">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>

                            <div class="entity-info">
                                <div class="entity-title-row">
                                    <div>
                                        <h6 class="entity-title mb-1" id="approveStudentName">-</h6>
                                        <div class="entity-subtitle" id="approveInstructorName">-</div>
                                    </div>

                                    <div class="entity-badges">
                                        <span class="ui-badge ui-badge-warning">Pending</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <label for="approve_meeting_url" class="form-label">Meeting URL</label>
                    <input
                        type="url"
                        id="approve_meeting_url"
                        name="meeting_url"
                        class="form-control"
                        placeholder="https://meet.google.com/..."
                    >
                    <div class="form-text">
                        Optional, bisa diisi sekarang atau update nanti.
                    </div>
                    <div class="invalid-feedback" id="approve_error_meeting_url"></div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-primary btn-modern" id="approveSubmitBtn">
                        <span class="default-text">
                            <i class="bi bi-check-circle me-2"></i>Approve Session
                        </span>
                        <span class="loading-text d-none">Approving...</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Meeting URL Modal --}}
<div class="modal fade" id="meetingUrlModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="meetingUrlForm">
            @csrf
            <input type="hidden" id="meeting_url_session_id">

            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title">Update Meeting URL</h5>
                        <p class="text-muted small mb-0">
                            Update link meeting untuk session ini.
                        </p>
                    </div>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-4">
                    <div id="meetingUrlAlert" class="alert alert-danger d-none mb-3"></div>

                    <div class="entity-card mb-4">
                        <div class="entity-main">
                            <div class="entity-icon">
                                <i class="bi bi-link-45deg"></i>
                            </div>

                            <div class="entity-info">
                                <div class="entity-title-row">
                                    <div>
                                        <h6 class="entity-title mb-1" id="meetingUrlStudentName">-</h6>
                                        <div class="entity-subtitle">Meeting link akan terlihat oleh student setelah session approved.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <label for="meeting_url_input" class="form-label">Meeting URL</label>
                    <input
                        type="url"
                        id="meeting_url_input"
                        name="meeting_url"
                        class="form-control"
                        placeholder="https://meet.google.com/..."
                    >
                    <div class="invalid-feedback" id="meeting_url_error_meeting_url"></div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-primary btn-modern" id="meetingUrlSubmitBtn">
                        <span class="default-text">
                            <i class="bi bi-save me-2"></i>Save URL
                        </span>
                        <span class="loading-text d-none">Saving...</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Reason Modal --}}
<div class="modal fade" id="reasonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="reasonForm">
            @csrf
            <input type="hidden" id="reason_session_id">
            <input type="hidden" id="reason_action">

            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title" id="reasonModalTitle">Update Session</h5>
                        <p class="text-muted small mb-0" id="reasonModalSubtitle">
                            Beri alasan perubahan status session.
                        </p>
                    </div>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-4">
                    <div id="reasonAlert" class="alert alert-danger d-none mb-3"></div>

                    <div class="delete-confirm-message mb-3">
                        <div class="delete-confirm-label">Session student</div>
                        <div class="delete-confirm-name" id="reasonStudentName">-</div>
                    </div>

                    <label for="reason_text" class="form-label">Reason</label>
                    <textarea
                        id="reason_text"
                        name="reason"
                        rows="4"
                        class="form-control"
                        placeholder="Tulis alasan singkat..."
                    ></textarea>
                    <div class="invalid-feedback" id="reason_error_reason"></div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-danger btn-modern" id="reasonSubmitBtn">
                        <span class="default-text" id="reasonSubmitText">
                            Submit
                        </span>
                        <span class="loading-text d-none">Processing...</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
let detailModal;
let approveModal;
let meetingUrlModal;
let reasonModal;

const showRouteTemplate = @js($showRouteTemplate);
const approveRouteTemplate = @js($approveRouteTemplate);
const rejectRouteTemplate = @js($rejectRouteTemplate);
const completeRouteTemplate = @js($completeRouteTemplate);
const cancelRouteTemplate = @js($cancelRouteTemplate);
const meetingUrlRouteTemplate = @js($meetingUrlRouteTemplate);

document.addEventListener('DOMContentLoaded', function () {
    detailModal = new bootstrap.Modal(document.getElementById('detailModal'));
    approveModal = new bootstrap.Modal(document.getElementById('approveModal'));
    meetingUrlModal = new bootstrap.Modal(document.getElementById('meetingUrlModal'));
    reasonModal = new bootstrap.Modal(document.getElementById('reasonModal'));

    document.getElementById('approveForm').addEventListener('submit', submitApproveForm);
    document.getElementById('meetingUrlForm').addEventListener('submit', submitMeetingUrlForm);
    document.getElementById('reasonForm').addEventListener('submit', submitReasonForm);
});

function csrfToken() {
    return document.querySelector('#approveForm input[name="_token"]')?.value || '{{ csrf_token() }}';
}

function buildRoute(template, id) {
    return String(template || '#').replace('__ID__', id);
}

function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    const toastId = 'toast-' + Date.now();

    const bgClass = type === 'success'
        ? 'bg-success'
        : type === 'warning'
            ? 'bg-warning'
            : type === 'info'
                ? 'bg-primary'
                : 'bg-danger';

    container.insertAdjacentHTML('beforeend', `
        <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body fw-semibold">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `);

    const toastEl = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastEl, { delay: 2600 });

    toast.show();

    toastEl.addEventListener('hidden.bs.toast', function () {
        toastEl.remove();
    });
}

function setButtonLoading(button, loading = true) {
    const defaultText = button.querySelector('.default-text');
    const loadingText = button.querySelector('.loading-text');

    button.disabled = loading;

    if (defaultText) defaultText.classList.toggle('d-none', loading);
    if (loadingText) loadingText.classList.toggle('d-none', !loading);
}

function resetFormErrors(formSelector, alertId, errorPrefix = '') {
    document.querySelectorAll(`${formSelector} .is-invalid`).forEach(el => el.classList.remove('is-invalid'));
    document.querySelectorAll(`${formSelector} .invalid-feedback`).forEach(el => el.innerText = '');

    const alert = document.getElementById(alertId);

    if (alert) {
        alert.classList.add('d-none');
        alert.innerText = '';
    }
}

function fillValidationErrors(errors, formSelector, alertId, errorPrefix = '') {
    const alert = document.getElementById(alertId);
    let firstMessage = 'Please check the form again.';

    Object.keys(errors || {}).forEach(function (field) {
        const messages = errors[field];
        const message = Array.isArray(messages) ? messages[0] : messages;

        firstMessage = message || firstMessage;

        const input = document.querySelector(`${formSelector} [name="${field}"]`);
        const feedback = document.getElementById(`${errorPrefix}${field}`);

        if (input) input.classList.add('is-invalid');
        if (feedback) feedback.innerText = message;
    });

    if (alert) {
        alert.innerText = firstMessage;
        alert.classList.remove('d-none');
    }
}

async function submitPatchRequest(url, formData = new FormData()) {
    formData.append('_method', 'PATCH');

    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: formData,
        credentials: 'same-origin',
    });

    const result = await response.json().catch(() => ({}));

    return { response, result };
}

async function openDetailModal(id) {
    document.getElementById('detailLoading').classList.remove('d-none');
    document.getElementById('detailContent').classList.add('d-none');
    document.getElementById('detailError').classList.add('d-none');
    document.getElementById('detailError').innerText = '';

    detailModal.show();

    try {
        const response = await fetch(buildRoute(showRouteTemplate, id), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        const result = await response.json().catch(() => ({}));

        if (!response.ok || !result.success) {
            document.getElementById('detailError').innerText = result.message || 'Failed to load session detail.';
            document.getElementById('detailError').classList.remove('d-none');
            return;
        }

        const session = result.data || {};

        document.getElementById('detailStudentName').innerText = session.student_name || '-';
        document.getElementById('detailStudentContact').innerText = `${session.student_email || '-'} ${session.student_phone ? '• ' + session.student_phone : ''}`;
        document.getElementById('detailStatus').innerText = session.status_label || '-';

        document.getElementById('detailInstructorName').innerText = session.instructor_name || '-';
        document.getElementById('detailInstructorMeta').innerText = session.instructor_specialization || '-';

        document.getElementById('detailScheduleDate').innerText = session.slot_date_label || '-';
        document.getElementById('detailScheduleTime').innerText = session.slot_time_label || '-';

        document.getElementById('detailTopic').innerText = session.topic_type_label || '-';
        document.getElementById('detailMeetingUrl').innerText = session.meeting_url || '-';
        document.getElementById('detailNotes').innerText = session.notes || 'No notes added.';

        document.getElementById('detailContent').classList.remove('d-none');
    } catch (error) {
        document.getElementById('detailError').innerText = 'Failed to connect to server.';
        document.getElementById('detailError').classList.remove('d-none');
    } finally {
        document.getElementById('detailLoading').classList.add('d-none');
    }
}

function openApproveModal(id, studentName, instructorName, meetingUrl = '') {
    resetFormErrors('#approveForm', 'approveAlert', 'approve_error_');

    document.getElementById('approveForm').reset();
    document.getElementById('approve_session_id').value = id;
    document.getElementById('approveStudentName').innerText = studentName || '-';
    document.getElementById('approveInstructorName').innerText = instructorName ? `Instructor: ${instructorName}` : '-';
    document.getElementById('approve_meeting_url').value = meetingUrl || '';

    approveModal.show();
}

async function submitApproveForm(event) {
    event.preventDefault();

    resetFormErrors('#approveForm', 'approveAlert', 'approve_error_');

    const id = document.getElementById('approve_session_id').value;
    const submitBtn = document.getElementById('approveSubmitBtn');
    const formData = new FormData(document.getElementById('approveForm'));

    setButtonLoading(submitBtn, true);

    try {
        const { response, result } = await submitPatchRequest(buildRoute(approveRouteTemplate, id), formData);

        if (!response.ok) {
            if (response.status === 422 && result.errors) {
                fillValidationErrors(result.errors, '#approveForm', 'approveAlert', 'approve_error_');
            } else {
                document.getElementById('approveAlert').innerText = result.message || 'Failed to approve session.';
                document.getElementById('approveAlert').classList.remove('d-none');
            }

            setButtonLoading(submitBtn, false);
            return;
        }

        showToast(result.message || 'Mentoring session approved.');
        approveModal.hide();

        setTimeout(() => window.location.reload(), 700);
    } catch (error) {
        document.getElementById('approveAlert').innerText = 'Failed to connect to server.';
        document.getElementById('approveAlert').classList.remove('d-none');
        setButtonLoading(submitBtn, false);
    }
}

function openMeetingUrlModal(id, studentName, meetingUrl = '') {
    resetFormErrors('#meetingUrlForm', 'meetingUrlAlert', 'meeting_url_error_');

    document.getElementById('meetingUrlForm').reset();
    document.getElementById('meeting_url_session_id').value = id;
    document.getElementById('meetingUrlStudentName').innerText = studentName || '-';
    document.getElementById('meeting_url_input').value = meetingUrl || '';

    meetingUrlModal.show();
}

async function submitMeetingUrlForm(event) {
    event.preventDefault();

    resetFormErrors('#meetingUrlForm', 'meetingUrlAlert', 'meeting_url_error_');

    const id = document.getElementById('meeting_url_session_id').value;
    const submitBtn = document.getElementById('meetingUrlSubmitBtn');
    const formData = new FormData(document.getElementById('meetingUrlForm'));

    setButtonLoading(submitBtn, true);

    try {
        const { response, result } = await submitPatchRequest(buildRoute(meetingUrlRouteTemplate, id), formData);

        if (!response.ok) {
            if (response.status === 422 && result.errors) {
                fillValidationErrors(result.errors, '#meetingUrlForm', 'meetingUrlAlert', 'meeting_url_error_');
            } else {
                document.getElementById('meetingUrlAlert').innerText = result.message || 'Failed to update meeting URL.';
                document.getElementById('meetingUrlAlert').classList.remove('d-none');
            }

            setButtonLoading(submitBtn, false);
            return;
        }

        showToast(result.message || 'Meeting URL updated.');
        meetingUrlModal.hide();

        setTimeout(() => window.location.reload(), 700);
    } catch (error) {
        document.getElementById('meetingUrlAlert').innerText = 'Failed to connect to server.';
        document.getElementById('meetingUrlAlert').classList.remove('d-none');
        setButtonLoading(submitBtn, false);
    }
}

function openReasonModal(action, id, studentName) {
    resetFormErrors('#reasonForm', 'reasonAlert', 'reason_error_');

    document.getElementById('reasonForm').reset();
    document.getElementById('reason_session_id').value = id;
    document.getElementById('reason_action').value = action;
    document.getElementById('reasonStudentName').innerText = studentName || '-';

    if (action === 'reject') {
        document.getElementById('reasonModalTitle').innerText = 'Reject Mentoring Session';
        document.getElementById('reasonModalSubtitle').innerText = 'Tolak booking dan buka kembali slot availability.';
        document.getElementById('reasonSubmitText').innerHTML = '<i class="bi bi-x-circle me-2"></i>Reject Session';
    } else {
        document.getElementById('reasonModalTitle').innerText = 'Cancel Mentoring Session';
        document.getElementById('reasonModalSubtitle').innerText = 'Cancel booking dan buka kembali slot availability.';
        document.getElementById('reasonSubmitText').innerHTML = '<i class="bi bi-slash-circle me-2"></i>Cancel Session';
    }

    reasonModal.show();
}

async function submitReasonForm(event) {
    event.preventDefault();

    resetFormErrors('#reasonForm', 'reasonAlert', 'reason_error_');

    const action = document.getElementById('reason_action').value;
    const id = document.getElementById('reason_session_id').value;
    const submitBtn = document.getElementById('reasonSubmitBtn');
    const formData = new FormData(document.getElementById('reasonForm'));

    const routeTemplate = action === 'reject'
        ? rejectRouteTemplate
        : cancelRouteTemplate;

    setButtonLoading(submitBtn, true);

    try {
        const { response, result } = await submitPatchRequest(buildRoute(routeTemplate, id), formData);

        if (!response.ok) {
            if (response.status === 422 && result.errors) {
                fillValidationErrors(result.errors, '#reasonForm', 'reasonAlert', 'reason_error_');
            } else {
                document.getElementById('reasonAlert').innerText = result.message || 'Failed to update session.';
                document.getElementById('reasonAlert').classList.remove('d-none');
            }

            setButtonLoading(submitBtn, false);
            return;
        }

        showToast(result.message || 'Mentoring session updated.');
        reasonModal.hide();

        setTimeout(() => window.location.reload(), 700);
    } catch (error) {
        document.getElementById('reasonAlert').innerText = 'Failed to connect to server.';
        document.getElementById('reasonAlert').classList.remove('d-none');
        setButtonLoading(submitBtn, false);
    }
}

async function completeSession(id, studentName) {
    const confirmed = confirm(`Mark mentoring session for ${studentName || 'this student'} as completed?`);

    if (!confirmed) return;

    try {
        const { response, result } = await submitPatchRequest(buildRoute(completeRouteTemplate, id));

        if (!response.ok) {
            showToast(result.message || 'Failed to complete session.', 'error');
            return;
        }

        showToast(result.message || 'Mentoring session completed.');
        setTimeout(() => window.location.reload(), 700);
    } catch (error) {
        showToast('Failed to connect to server.', 'error');
    }
}
</script>
@endpush