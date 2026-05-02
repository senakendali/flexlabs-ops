@extends('layouts.app-dashboard')

@section('title', 'Instructor Schedule Detail')

@section('content')
@php
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Str;

    $teachingInstructor = $schedule->replacementInstructor?->name ?: $schedule->instructor?->name;

    $statusLabel = $schedule->status_label
        ?? Str::of($schedule->status ?? 'scheduled')->replace('_', ' ')->title();

    $deliveryModeLabel = $schedule->delivery_mode_label
        ?? Str::of($schedule->delivery_mode ?? 'online')->replace('_', ' ')->title();

    $scheduleDateLabel = $schedule->schedule_date
        ? Carbon::parse($schedule->schedule_date)->format('d M Y')
        : '-';

    $timeRange = $schedule->time_range
        ?? (($schedule->start_time && $schedule->end_time)
            ? Carbon::parse($schedule->start_time)->format('H:i') . ' - ' . Carbon::parse($schedule->end_time)->format('H:i')
            : '-');

    $isMakeup = (bool) ($schedule->is_makeup_session ?? false);

    $scheduledMaterials = collect($schedule->subTopics ?? []);
    $scheduledMaterialsCount = $scheduledMaterials->count();

    $scheduledTopicGroups = $scheduledMaterials
        ->groupBy(fn ($subTopic) => data_get($subTopic, 'topic.id') ?: 'uncategorized');

    $primaryTopicName = $scheduledMaterials
        ->map(fn ($subTopic) => data_get($subTopic, 'topic.name'))
        ->filter()
        ->unique()
        ->values()
        ->implode(', ');

    $programName = $schedule->program->name
        ?? $schedule->batch?->program?->name
        ?? '-';
@endphp

<div class="container-fluid px-4 py-4 instructor-schedules-show-page marketing-report-show-page">
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">
                    <i class="bi bi-calendar-check me-1"></i>
                    Academic
                </div>

                <h1 class="page-title mb-2">Instructor Schedule Detail</h1>

                <p class="page-subtitle mb-0">
                    Lihat detail jadwal mengajar, instruktur, batch, materi live session, dan informasi pelaksanaan sesi.
                </p>
            </div>

            <div class="d-flex gap-2 flex-wrap page-header-actions">
                <a href="{{ route('instructor-schedules.index') }}" class="btn btn-light border btn-modern">
                    <i class="bi bi-list-ul me-1"></i> List
                </a>

                @if(Route::has('instructor-tracking.show') && $scheduledMaterialsCount > 0)
                    <a href="{{ route('instructor-tracking.show', $schedule) }}" class="btn btn-light border btn-modern">
                        <i class="bi bi-clipboard-check me-1"></i> Tracking
                    </a>
                @endif

                <a href="{{ route('instructor-schedules.edit', $schedule) }}" class="btn btn-light btn-modern">
                    <i class="bi bi-pencil-square me-1"></i> Edit Schedule
                </a>

                <button type="button" class="btn btn-danger btn-modern" data-bs-toggle="modal" data-bs-target="#deleteScheduleModal">
                    <i class="bi bi-trash3 me-1"></i> Delete
                </button>
            </div>
        </div>
    </div>

    <div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1090;"></div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card dashboard-stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-calendar-event-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Schedule Date</div>
                        <div class="stat-value">{{ $scheduleDateLabel }}</div>
                    </div>
                </div>
                <div class="stat-description">Tanggal pelaksanaan sesi yang sedang dibuka.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card dashboard-stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-clock-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Time Range</div>
                        <div class="stat-value">{{ $timeRange }}</div>
                    </div>
                </div>
                <div class="stat-description">Rentang waktu sesi untuk aktivitas pengajaran ini.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card dashboard-stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-person-video3"></i>
                    </div>
                    <div>
                        <div class="stat-title">Instructor</div>
                        <div class="stat-value">{{ $teachingInstructor ?: '-' }}</div>
                    </div>
                </div>
                <div class="stat-description">Instruktur yang tercatat mengajar pada sesi ini.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card dashboard-stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-journal-check"></i>
                    </div>
                    <div>
                        <div class="stat-title">Scheduled Materials</div>
                        <div class="stat-value">{{ $scheduledMaterialsCount }}</div>
                    </div>
                </div>
                <div class="stat-description">Jumlah sub topic live session yang dijadwalkan.</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="content-card mb-4">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Session Overview</h5>
                        <p class="content-card-subtitle mb-0">
                            Informasi inti sesi yang dijadwalkan untuk batch dan program terkait.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="info-grid">
                        <div class="info-item info-item-full">
                            <div class="info-label">Session Title</div>
                            <div class="info-value">{{ $schedule->session_title ?: '-' }}</div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Program</div>
                            <div class="info-value">{{ $programName }}</div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Batch</div>
                            <div class="info-value">{{ $schedule->batch->name ?? '-' }}</div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Topic</div>
                            <div class="info-value">{{ $primaryTopicName ?: '-' }}</div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Delivery Mode</div>
                            <div class="info-value">{{ $deliveryModeLabel }}</div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Session Type</div>
                            <div class="info-value">{{ $isMakeup ? 'Makeup Session' : 'Regular Session' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-card mb-4">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Scheduled Materials</h5>
                        <p class="content-card-subtitle mb-0">
                            Materi live session yang akan menjadi checklist coverage saat instruktur melakukan tracking sesi.
                        </p>
                    </div>

                    <span class="badge bg-light text-dark border">
                        {{ $scheduledMaterialsCount }} material{{ $scheduledMaterialsCount === 1 ? '' : 's' }}
                    </span>
                </div>

                <div class="content-card-body">
                    @if($scheduledMaterialsCount > 0)
                        <div class="d-flex flex-column gap-3">
                            @foreach($scheduledTopicGroups as $topicId => $materials)
                                @php
                                    $firstMaterial = $materials->first();
                                    $topicName = data_get($firstMaterial, 'topic.name') ?: 'Uncategorized Topic';
                                    $moduleName = data_get($firstMaterial, 'topic.module.name');
                                    $stageName = data_get($firstMaterial, 'topic.module.stage.name');
                                @endphp

                                <div class="border rounded-3 p-3 bg-light">
                                    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-2">
                                        <div>
                                            <div class="fw-bold text-dark">{{ $topicName }}</div>

                                            @if($stageName || $moduleName)
                                                <div class="small text-muted">
                                                    {{ $stageName ?: 'Stage' }}
                                                    @if($moduleName)
                                                        · {{ $moduleName }}
                                                    @endif
                                                </div>
                                            @endif
                                        </div>

                                        <span class="badge bg-white text-dark border">
                                            {{ $materials->count() }} sub topic{{ $materials->count() === 1 ? '' : 's' }}
                                        </span>
                                    </div>

                                    <div class="list-group list-group-flush border rounded-3 overflow-hidden">
                                        @foreach($materials as $index => $subTopic)
                                            <div class="list-group-item bg-white d-flex align-items-start gap-2">
                                                

                                                <div>
                                                    <div class="fw-semibold">{{ $subTopic->name }}</div>

                                                    @if($subTopic->description)
                                                        <div class="small text-muted mt-1">
                                                            {{ $subTopic->description }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="alert alert-warning mb-0">
                            <div class="fw-bold mb-1">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                Belum ada scheduled materials.
                            </div>
                            <div>
                                Tambahkan topic dan checklist sub topic live session melalui halaman edit schedule agar instructor tracking bisa berjalan sesuai jadwal.
                            </div>

                            <div class="mt-3">
                                <a href="{{ route('instructor-schedules.edit', $schedule) }}" class="btn btn-sm btn-warning">
                                    <i class="bi bi-pencil-square me-1"></i>
                                    Edit Scheduled Materials
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="content-card mb-4">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Assignment Detail</h5>
                        <p class="content-card-subtitle mb-0">
                            Informasi pengajar utama, instruktur pengganti, batch, dan relasi reschedule.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Main Instructor</div>
                            <div class="info-value">{{ $schedule->instructor->name ?? '-' }}</div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Teaching Instructor</div>
                            <div class="info-value">{{ $teachingInstructor ?: '-' }}</div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Replacement Instructor</div>
                            <div class="info-value">{{ $schedule->replacementInstructor->name ?? '-' }}</div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Batch</div>
                            <div class="info-value">{{ $schedule->batch->name ?? '-' }}</div>
                        </div>

                        <div class="info-item info-item-full">
                            <div class="info-label">Rescheduled From</div>
                            <div class="info-value">
                                @if ($schedule->rescheduledFrom)
                                    {{ $schedule->rescheduledFrom->session_title }}

                                    <div class="small text-muted mt-1">
                                        {{ $schedule->rescheduledFrom->schedule_date ? Carbon::parse($schedule->rescheduledFrom->schedule_date)->format('d M Y') : '-' }}
                                        ·
                                        {{ $schedule->rescheduledFrom->start_time ? Carbon::parse($schedule->rescheduledFrom->start_time)->format('H:i') : '-' }}
                                        -
                                        {{ $schedule->rescheduledFrom->end_time ? Carbon::parse($schedule->rescheduledFrom->end_time)->format('H:i') : '-' }}

                                        @if ($schedule->rescheduledFrom->batch?->name)
                                            · {{ $schedule->rescheduledFrom->batch->name }}
                                        @endif
                                    </div>
                                @else
                                    -
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Delivery Information</h5>
                        <p class="content-card-subtitle mb-0">
                            Detail pelaksanaan sesi, meeting link, lokasi, dan catatan tambahan.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="text-block-group">
                        <div class="text-block-item">
                            <div class="text-block-label">Meeting Link</div>
                            <div class="text-block-value">
                                @if ($schedule->meeting_link)
                                    <a href="{{ $schedule->meeting_link }}" target="_blank" rel="noopener noreferrer">
                                        {{ $schedule->meeting_link }}
                                    </a>
                                @else
                                    -
                                @endif
                            </div>
                        </div>

                        <div class="text-block-item">
                            <div class="text-block-label">Location</div>
                            <div class="text-block-value">{{ $schedule->location ?: '-' }}</div>
                        </div>

                        <div class="text-block-item border-0 pb-0">
                            <div class="text-block-label">Notes</div>
                            <div class="text-block-value">{{ $schedule->notes ?: '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="content-card mb-4">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Quick Summary</h5>
                        <p class="content-card-subtitle mb-0">
                            Gambaran ringkas status, mode pelaksanaan, dan waktu pencatatan jadwal.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="simple-list">
                        <div class="simple-list-item">
                            <div class="simple-list-title">Current Status</div>
                            <div class="simple-list-meta">{{ $statusLabel }}</div>
                        </div>

                        <div class="simple-list-item">
                            <div class="simple-list-title">Delivery Mode</div>
                            <div class="simple-list-meta">{{ $deliveryModeLabel }}</div>
                        </div>

                        <div class="simple-list-item">
                            <div class="simple-list-title">Session Type</div>
                            <div class="simple-list-meta">{{ $isMakeup ? 'Makeup Session' : 'Regular Session' }}</div>
                        </div>

                        <div class="simple-list-item">
                            <div class="simple-list-title">Scheduled Materials</div>
                            <div class="simple-list-meta">
                                {{ $scheduledMaterialsCount }} sub topic{{ $scheduledMaterialsCount === 1 ? '' : 's' }}
                            </div>
                        </div>

                        <div class="simple-list-item">
                            <div class="simple-list-title">Schedule Created</div>
                            <div class="simple-list-meta">{{ optional($schedule->created_at)->format('d M Y H:i') ?? '-' }}</div>
                        </div>

                        <div class="simple-list-item border-0 pb-0">
                            <div class="simple-list-title">Last Updated</div>
                            <div class="simple-list-meta">{{ optional($schedule->updated_at)->format('d M Y H:i') ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-card mb-4">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Tracking Readiness</h5>
                        <p class="content-card-subtitle mb-0">
                            Status kesiapan jadwal untuk digunakan pada Instructor Tracking.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    @if($scheduledMaterialsCount > 0)
                        <div class="alert alert-success mb-3">
                            <div class="fw-bold mb-1">
                                <i class="bi bi-check-circle me-1"></i>
                                Ready for Tracking
                            </div>
                            <div>
                                Jadwal ini sudah memiliki materi live session untuk checklist coverage instruktur.
                            </div>
                        </div>

                        @if(Route::has('instructor-tracking.show'))
                            <a href="{{ route('instructor-tracking.show', $schedule) }}" class="btn btn-primary w-100">
                                <i class="bi bi-clipboard-check me-1"></i>
                                Open Instructor Tracking
                            </a>
                        @endif
                    @else
                        <div class="alert alert-warning mb-3">
                            <div class="fw-bold mb-1">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                Not Ready Yet
                            </div>
                            <div>
                                Jadwal belum punya scheduled materials, jadi tracking coverage belum bisa dilakukan dengan benar.
                            </div>
                        </div>

                        <a href="{{ route('instructor-schedules.edit', $schedule) }}" class="btn btn-warning w-100">
                            <i class="bi bi-pencil-square me-1"></i>
                            Add Scheduled Materials
                        </a>
                    @endif
                </div>
            </div>

            
        </div>
    </div>
</div>

{{-- Delete Modal --}}
<div class="modal fade" id="deleteScheduleModal" tabindex="-1" aria-labelledby="deleteScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="deleteScheduleModalLabel">Delete Instructor Schedule</h5>
                    <div class="text-muted small">This action cannot be undone.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="alert alert-danger mb-0">
                    <div class="fw-bold mb-1">Are you sure?</div>
                    <div>
                        Jadwal <strong>{{ $schedule->session_title ?: 'this session' }}</strong> akan dihapus dari sistem.
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>

                <form action="{{ route('instructor-schedules.destroy', $schedule) }}" method="POST" class="m-0">
                    @csrf
                    @method('DELETE')

                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash3 me-1"></i>
                        Delete Schedule
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection