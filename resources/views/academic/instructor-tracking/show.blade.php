@extends('layouts.app-dashboard')

@section('title', 'Instructor Session Tracking')

@section('content')
@php
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Str;

    $canFill = $tracking->checked_in_at && $tracking->can_edit;
    $isSubmitted = in_array($tracking->status, ['submitted', 'reviewed'], true);

    $statusBadge = match ($tracking->status) {
        'checked_in' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
        'draft' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
        'submitted' => 'bg-primary-subtle text-primary-emphasis border border-primary-subtle',
        'reviewed' => 'bg-success-subtle text-success-emphasis border border-success-subtle',
        'returned' => 'bg-danger-subtle text-danger-emphasis border border-danger-subtle',
        default => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
    };

    $scheduleDateLabel = $schedule->schedule_date
        ? Carbon::parse($schedule->schedule_date)->format('d M Y')
        : '-';

    $scheduleTimeLabel = trim(($schedule->start_time ?? '-') . ' - ' . ($schedule->end_time ?? '-'));

    $teachingInstructorName = data_get($schedule, 'replacementInstructor.name')
        ?: data_get($schedule, 'instructor.name', '-');

    $teachingInstructorEmail = data_get($schedule, 'replacementInstructor.email')
        ?: data_get($schedule, 'instructor.email', 'Teaching session owner');

    $batchName = data_get($schedule, 'batch.name', '-');
    $programName = data_get($schedule, 'program.name')
        ?: data_get($schedule, 'batch.program.name', '-');

    $deliveryModeLabel = $schedule->delivery_mode
        ? Str::headline($schedule->delivery_mode)
        : '-';

    $deliveryDetail = $schedule->meeting_link
        ?: $schedule->location
        ?: 'Belum ada meeting link atau lokasi.';

    $curriculumTree = $curriculumTree ?? [];

    $scheduledMaterialTotal = 0;
    $scheduledTopicTotal = 0;
    $scheduledModuleTotal = 0;

    $coverageStatusCounts = [
        'pending' => 0,
        'delivered' => 0,
        'partial' => 0,
        'not_delivered' => 0,
    ];

    foreach ($curriculumTree as $stage) {
        foreach (($stage['modules'] ?? []) as $module) {
            $hasModuleItem = false;

            foreach (($module['topics'] ?? []) as $topic) {
                $topicItems = $topic['items'] ?? [];

                if (count($topicItems)) {
                    $scheduledTopicTotal++;
                    $hasModuleItem = true;
                }

                foreach ($topicItems as $item) {
                    $scheduledMaterialTotal++;

                    $itemStatus = $item->delivery_status ?: 'pending';

                    if (! array_key_exists($itemStatus, $coverageStatusCounts)) {
                        $itemStatus = 'pending';
                    }

                    $coverageStatusCounts[$itemStatus]++;
                }
            }

            if ($hasModuleItem) {
                $scheduledModuleTotal++;
            }
        }
    }

    $deliveredCount = $coverageStatusCounts['delivered'] ?? 0;
    $partialCount = $coverageStatusCounts['partial'] ?? 0;
    $notDeliveredCount = $coverageStatusCounts['not_delivered'] ?? 0;
    $pendingCount = $coverageStatusCounts['pending'] ?? 0;
@endphp

@push('styles')
<style>
    
</style>
@endpush

<div class="container-fluid px-4 py-4 tracking-page">
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Instructor Tracking</div>
                <h1 class="page-title mb-2">Session Coverage Tracking</h1>
                <p class="page-subtitle mb-0">
                    Check-in sesi, review materi yang sudah dijadwalkan, tandai coverage per sub topic, lalu submit hasil pelaksanaan.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <a href="{{ route('instructor-tracking.index') }}" class="btn btn-light btn-modern">
                    <i class="bi bi-arrow-left me-2"></i>Back
                </a>

                @if(! $tracking->checked_in_at)
                    <button type="button" class="btn btn-light btn-modern" id="btnCheckIn" data-url="{{ route('instructor-tracking.check-in', $schedule) }}">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Check In
                    </button>
                @endif
            </div>
        </div>
    </div>

    <div id="trackingAlert" class="alert d-none mb-4" role="alert"></div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap"><i class="bi bi-person-workspace"></i></div>
                    <div>
                        <div class="stat-title">Instructor</div>
                        <div class="stat-value stat-value-sm">{{ $teachingInstructorName }}</div>
                    </div>
                </div>
                <div class="stat-description">{{ $teachingInstructorEmail }}</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap"><i class="bi bi-calendar-event"></i></div>
                    <div>
                        <div class="stat-title">Schedule</div>
                        <div class="stat-value stat-value-sm">{{ $scheduleDateLabel }}</div>
                    </div>
                </div>
                <div class="stat-description">{{ $scheduleTimeLabel }}</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap"><i class="bi bi-bar-chart-line"></i></div>
                    <div>
                        <div class="stat-title">Coverage</div>
                        <div class="stat-value" id="coveragePercentageText">{{ number_format((float) $tracking->coverage_percentage, 2) }}%</div>
                    </div>
                </div>
                <div class="stat-description">Delivered dihitung penuh, partial dihitung setengah.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap"><i class="bi bi-activity"></i></div>
                    <div>
                        <div class="stat-title">Status</div>
                        <div class="stat-value stat-value-sm">
                            <span class="badge rounded-pill {{ $statusBadge }}">{{ $tracking->status_label }}</span>
                        </div>
                    </div>
                </div>
                <div class="stat-description">
                    @if($tracking->checked_in_at)
                        Check-in: {{ $tracking->checked_in_at->format('d M Y H:i') }}
                    @else
                        Waiting for instructor check-in.
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="content-card tracking-plan-card mb-4">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Scheduled Session Plan</h5>
                <p class="content-card-subtitle mb-0">
                    Data di bawah berasal dari jadwal. Instructor hanya mengisi realisasi pelaksanaan, bukan memilih materi baru.
                </p>
            </div>

            <span class="badge rounded-pill bg-light text-primary border px-3 py-2">
                <i class="bi bi-calendar-check me-1"></i> Schedule Based
            </span>
        </div>

        <div class="content-card-body">
            <div class="row g-3">
                <div class="col-xl-12">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="tracking-info-box">
                                <div class="d-flex gap-3 align-items-start">
                                    <div class="tracking-info-icon">
                                        <i class="bi bi-easel2"></i>
                                    </div>
                                    <div>
                                        <div class="tracking-plan-title">Session Title</div>
                                        <div class="tracking-plan-value">{{ $schedule->session_title ?: '-' }}</div>
                                        <div class="tracking-plan-meta">{{ $programName }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="tracking-info-box">
                                <div class="d-flex gap-3 align-items-start">
                                    <div class="tracking-info-icon">
                                        <i class="bi bi-people"></i>
                                    </div>
                                    <div>
                                        <div class="tracking-plan-title">Batch</div>
                                        <div class="tracking-plan-value">{{ $batchName }}</div>
                                        <div class="tracking-plan-meta">{{ $deliveryModeLabel }} · {{ $scheduleDateLabel }} · {{ $scheduleTimeLabel }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="tracking-info-box">
                                <div class="tracking-plan-title">Meeting / Location</div>
                                <div class="tracking-plan-value text-break">{{ $deliveryDetail }}</div>
                                <div class="tracking-plan-meta">
                                    Gunakan info ini untuk memastikan tracking sesuai sesi yang sedang berjalan.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                
            </div>

            @if(! $tracking->checked_in_at)
                <div class="alert alert-warning border-0 shadow-sm mt-4 mb-0">
                    <div class="d-flex gap-2 align-items-start">
                        <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                        <div>
                            <div class="fw-semibold mb-1">Instructor belum check-in.</div>
                            <div>Coverage materi dan session notes akan aktif setelah instructor melakukan check-in.</div>
                        </div>
                    </div>
                </div>
            @endif

            @if($isSubmitted)
                <div class="alert alert-info border-0 shadow-sm mt-4 mb-0">
                    <div class="d-flex gap-2 align-items-start">
                        <i class="bi bi-info-circle-fill mt-1"></i>
                        <div>
                            <div class="fw-semibold mb-1">Tracking sudah dikirim.</div>
                            <div>Data session ini sudah check-out dan tidak bisa diubah lagi kecuali dikembalikan oleh Academic Team.</div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <form id="trackingForm">
        @csrf

        <div class="content-card mb-4">
            <div class="content-card-header">
                <div>
                    <h5 class="content-card-title mb-1">Scheduled Material Coverage</h5>
                    <p class="content-card-subtitle mb-0">
                        Checklist hanya untuk sub topic yang sudah ditempel pada jadwal ini.
                    </p>
                </div>

                <div class="scheduled-material-badge">
                    <span id="liveScheduledMaterialCount">{{ $scheduledMaterialTotal }}</span> sub topic scheduled
                </div>
            </div>

            <div class="content-card-body">
                <div class="scheduled-material-header mb-4">
                    <div class="row g-3 align-items-center">
                        <div class="col-lg">
                            <div class="d-flex gap-3 align-items-start">
                                <div class="tracking-info-icon">
                                    <i class="bi bi-list-check"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark mb-1">Material plan dari jadwal</div>
                                    <div class="text-muted small">
                                        Instructor tinggal menandai apakah setiap sub topic benar-benar tersampaikan pada sesi ini.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-auto">
                            @if(\Illuminate\Support\Facades\Route::has('instructor-schedules.edit'))
                                <a href="{{ route('instructor-schedules.edit', $schedule) }}" class="btn btn-light border btn-modern">
                                    <i class="bi bi-pencil-square me-2"></i>Update Schedule Materials
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="coverage-summary-grid mb-4">
                    <div class="coverage-summary-tile">
                        <div class="coverage-summary-label">Scheduled</div>
                        <div class="coverage-summary-value" id="summaryScheduledCount">{{ $scheduledMaterialTotal }}</div>
                        <div class="coverage-summary-help">{{ $scheduledTopicTotal }} topic · {{ $scheduledModuleTotal }} module</div>
                    </div>

                    <div class="coverage-summary-tile">
                        <div class="coverage-summary-label">Delivered</div>
                        <div class="coverage-summary-value" id="summaryDeliveredCount">{{ $deliveredCount }}</div>
                        <div class="coverage-summary-help">Materi selesai dibawakan.</div>
                    </div>

                    <div class="coverage-summary-tile">
                        <div class="coverage-summary-label">Partial</div>
                        <div class="coverage-summary-value" id="summaryPartialCount">{{ $partialCount }}</div>
                        <div class="coverage-summary-help">Perlu reason.</div>
                    </div>

                    <div class="coverage-summary-tile">
                        <div class="coverage-summary-label">Not Delivered</div>
                        <div class="coverage-summary-value" id="summaryNotDeliveredCount">{{ $notDeliveredCount }}</div>
                        <div class="coverage-summary-help">Perlu reason.</div>
                    </div>

                    <div class="coverage-summary-tile">
                        <div class="coverage-summary-label">Pending</div>
                        <div class="coverage-summary-value" id="summaryPendingCount">{{ $pendingCount }}</div>
                        <div class="coverage-summary-help">Belum ditandai.</div>
                    </div>
                </div>

                @if(count($curriculumTree))
                    @php $itemIndex = 0; @endphp

                    <div class="accordion tracking-accordion" id="scheduledCoverageAccordion">
                        @foreach($curriculumTree as $stageKey => $stage)
                            @php
                                $stageItemCount = 0;

                                foreach (($stage['modules'] ?? []) as $stageModule) {
                                    foreach (($stageModule['topics'] ?? []) as $stageTopic) {
                                        $stageItemCount += count($stageTopic['items'] ?? []);
                                    }
                                }
                            @endphp

                            <div class="accordion-item mb-3 border rounded-3 overflow-hidden">
                                <h2 class="accordion-header" id="stageHeading{{ $loop->index }}">
                                    <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#stageCollapse{{ $loop->index }}" aria-expanded="{{ $loop->first ? 'true' : 'false' }}">
                                        <span class="d-flex align-items-center justify-content-between gap-3 w-100 pe-2">
                                            <span>
                                                <span class="fw-bold">{{ $stage['name'] }}</span>
                                                <span class="tracking-stage-meta d-block mt-1">{{ $stageItemCount }} scheduled sub topic</span>
                                            </span>
                                        </span>
                                    </button>
                                </h2>

                                <div id="stageCollapse{{ $loop->index }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}" data-bs-parent="#scheduledCoverageAccordion">
                                    <div class="accordion-body bg-light-subtle">
                                        @foreach($stage['modules'] as $module)
                                            @php
                                                $moduleItemCount = 0;

                                                foreach (($module['topics'] ?? []) as $moduleTopic) {
                                                    $moduleItemCount += count($moduleTopic['items'] ?? []);
                                                }
                                            @endphp

                                            @continue($moduleItemCount < 1)

                                            <div class="coverage-module-block mb-4">
                                                <div class="coverage-module-header">
                                                    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                                        <div>
                                                            <div class="fw-bold text-primary">
                                                                <i class="bi bi-folder2-open me-2"></i>{{ $module['name'] }}
                                                            </div>
                                                            <div class="small text-muted mt-1">
                                                                {{ $moduleItemCount }} sub topic dijadwalkan dari module ini.
                                                            </div>
                                                        </div>

                                                        <span class="badge rounded-pill bg-light text-primary border px-3 py-2">
                                                            Module
                                                        </span>
                                                    </div>
                                                </div>

                                                <div class="p-3">
                                                    @foreach($module['topics'] as $topic)
                                                        @php
                                                            $topicItems = $topic['items'] ?? [];
                                                        @endphp

                                                        @continue(count($topicItems) < 1)

                                                        <div class="coverage-topic-card mb-3">
                                                            <div class="coverage-topic-header">
                                                                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                                                    <div>
                                                                        <div class="fw-bold text-dark">
                                                                            <i class="bi bi-list-check me-2"></i>{{ $topic['name'] }}
                                                                        </div>
                                                                        <div class="small text-muted mt-1">
                                                                            {{ count($topicItems) }} scheduled sub topic.
                                                                        </div>
                                                                    </div>

                                                                    <span class="badge rounded-pill bg-white text-dark border px-3 py-2">
                                                                        Topic
                                                                    </span>
                                                                </div>
                                                            </div>

                                                            <div class="coverage-topic-body">
                                                                @foreach($topicItems as $item)
                                                                    @php
                                                                        $subTopic = $item->subTopic;
                                                                        $currentIndex = $itemIndex++;
                                                                        $deliveryStatus = $item->delivery_status ?: 'pending';

                                                                        if (! in_array($deliveryStatus, ['pending', 'delivered', 'partial', 'not_delivered'], true)) {
                                                                            $deliveryStatus = 'pending';
                                                                        }

                                                                        $statusLabel = match ($deliveryStatus) {
                                                                            'delivered' => 'Delivered',
                                                                            'partial' => 'Partial',
                                                                            'not_delivered' => 'Not Delivered',
                                                                            default => 'Pending',
                                                                        };

                                                                        $statusIcon = match ($deliveryStatus) {
                                                                            'delivered' => 'bi-check-circle-fill',
                                                                            'partial' => 'bi-exclamation-circle-fill',
                                                                            'not_delivered' => 'bi-x-circle-fill',
                                                                            default => 'bi-clock-fill',
                                                                        };

                                                                        $reasonRequired = in_array($deliveryStatus, ['partial', 'not_delivered'], true);
                                                                    @endphp

                                                                    <div class="coverage-item status-{{ $deliveryStatus }}" data-coverage-item>
                                                                        <input type="hidden" name="items[{{ $currentIndex }}][id]" value="{{ $item->id }}">
                                                                        <input type="hidden" name="items[{{ $currentIndex }}][sub_topic_id]" value="{{ $item->sub_topic_id }}">

                                                                        <div class="row g-3 align-items-start">
                                                                            <div class="col-xl-5">
                                                                                <div class="coverage-item-title">
                                                                                    {{ $subTopic?->name ?? 'Untitled Sub Topic' }}
                                                                                </div>

                                                                                @if($subTopic?->description)
                                                                                    <div class="coverage-item-description">
                                                                                        {{ Str::limit(strip_tags($subTopic->description), 150) }}
                                                                                    </div>
                                                                                @endif

                                                                                <div class="coverage-item-meta">
                                                                                    <span class="coverage-status-badge {{ $deliveryStatus }}" data-status-badge>
                                                                                        <i class="bi {{ $statusIcon }}"></i>
                                                                                        <span data-status-text>{{ $statusLabel }}</span>
                                                                                    </span>

                                                                                    <span class="badge rounded-pill bg-light text-dark border">
                                                                                        {{ $subTopic?->lesson_type ? Str::headline($subTopic->lesson_type) : 'Lesson' }}
                                                                                    </span>
                                                                                </div>
                                                                            </div>

                                                                            <div class="col-xl-7">
                                                                                <div class="coverage-form-panel">
                                                                                    <div class="row g-3">
                                                                                        <div class="col-md-4">
                                                                                            <label class="form-label small fw-bold">Delivery Status</label>
                                                                                            <select
                                                                                                name="items[{{ $currentIndex }}][delivery_status]"
                                                                                                class="form-select form-select-sm delivery-status"
                                                                                                data-delivery-status
                                                                                                {{ $canFill ? '' : 'disabled' }}
                                                                                            >
                                                                                                <option value="pending" @selected($deliveryStatus === 'pending')>Pending</option>
                                                                                                <option value="delivered" @selected($deliveryStatus === 'delivered')>Delivered</option>
                                                                                                <option value="partial" @selected($deliveryStatus === 'partial')>Partial</option>
                                                                                                <option value="not_delivered" @selected($deliveryStatus === 'not_delivered')>Not Delivered</option>
                                                                                            </select>
                                                                                        </div>

                                                                                        <div class="col-md-4">
                                                                                            <label class="form-label small fw-bold">
                                                                                                Reason
                                                                                                <span class="text-danger reason-required-mark {{ $reasonRequired ? '' : 'd-none' }}">*</span>
                                                                                            </label>
                                                                                            <input
                                                                                                type="text"
                                                                                                name="items[{{ $currentIndex }}][not_delivered_reason]"
                                                                                                value="{{ $item->not_delivered_reason }}"
                                                                                                class="form-control form-control-sm"
                                                                                                data-reason-input
                                                                                                placeholder="Required if partial/not delivered"
                                                                                                {{ $reasonRequired ? 'required' : '' }}
                                                                                                {{ $canFill ? '' : 'disabled' }}
                                                                                            >
                                                                                            <div class="reason-helper {{ $reasonRequired ? 'is-required' : '' }}" data-reason-helper>
                                                                                                {{ $reasonRequired ? 'Reason wajib diisi untuk status ini.' : 'Optional jika materi delivered/pending.' }}
                                                                                            </div>
                                                                                        </div>

                                                                                        <div class="col-md-4">
                                                                                            <label class="form-label small fw-bold">Delivery Notes</label>
                                                                                            <input
                                                                                                type="text"
                                                                                                name="items[{{ $currentIndex }}][delivery_notes]"
                                                                                                value="{{ $item->delivery_notes }}"
                                                                                                class="form-control form-control-sm"
                                                                                                placeholder="Optional notes"
                                                                                                {{ $canFill ? '' : 'disabled' }}
                                                                                            >
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state text-center py-5">
                        <div class="empty-state-icon mb-3"><i class="bi bi-journal-x"></i></div>
                        <h5 class="mb-1">Belum ada scheduled material</h5>
                        <p class="text-muted mb-3">
                            Jadwal ini belum punya sub topic yang ditempel. Tracking baru bisa akurat setelah materi dijadwalkan.
                        </p>

                        @if(\Illuminate\Support\Facades\Route::has('instructor-schedules.edit'))
                            <a href="{{ route('instructor-schedules.edit', $schedule) }}" class="btn btn-primary btn-modern">
                                <i class="bi bi-pencil-square me-2"></i>Update Schedule Materials
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <div class="content-card mb-4">
            <div class="content-card-header">
                <div>
                    <h5 class="content-card-title mb-1">Session Notes</h5>
                    <p class="content-card-subtitle mb-0">
                        Catatan pelaksanaan sesi wajib diisi sebelum check-out.
                    </p>
                </div>
            </div>

            <div class="content-card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Session Notes <span class="text-danger">*</span></label>
                        <textarea
                            name="session_notes"
                            rows="4"
                            class="form-control"
                            placeholder="Ringkasan sesi hari ini, kondisi kelas, dan progress pembelajaran."
                            {{ $canFill ? '' : 'disabled' }}
                        >{{ old('session_notes', $tracking->session_notes) }}</textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Issue Notes</label>
                        <textarea
                            name="issue_notes"
                            rows="3"
                            class="form-control"
                            placeholder="Kendala teknis, siswa telat, kelas kurang kondusif, dll."
                            {{ $canFill ? '' : 'disabled' }}
                        >{{ old('issue_notes', $tracking->issue_notes) }}</textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Follow-up Notes</label>
                        <textarea
                            name="follow_up_notes"
                            rows="3"
                            class="form-control"
                            placeholder="Materi yang perlu diulang, siswa yang perlu difollow-up, atau next action."
                            {{ $canFill ? '' : 'disabled' }}
                        >{{ old('follow_up_notes', $tracking->follow_up_notes) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-card tracking-footer-card">
            <div class="content-card-body">
                <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                    <div>
                        <div class="fw-bold text-dark mb-1">Final Check</div>
                        <div class="small text-muted">
                            @if($tracking->checked_out_at)
                                Checked out at {{ $tracking->checked_out_at->format('d M Y H:i') }} · Duration {{ $tracking->actual_duration_minutes }} minutes
                            @elseif($tracking->checked_in_at)
                                Checked in at {{ $tracking->checked_in_at->format('d M Y H:i') }} · Late {{ $tracking->late_minutes }} minutes
                            @else
                                Instructor must check-in before filling this form.
                            @endif
                        </div>
                    </div>

                    <div class="d-flex gap-2 flex-wrap">
                        <button
                            type="button"
                            class="btn btn-outline-secondary btn-modern"
                            id="btnSaveDraft"
                            data-url="{{ route('instructor-tracking.save-draft', $schedule) }}"
                            {{ $canFill ? '' : 'disabled' }}
                        >
                            <i class="bi bi-save me-2"></i>Save Draft
                        </button>

                        <button
                            type="button"
                            class="btn btn-primary btn-modern"
                            id="btnCheckOut"
                            data-url="{{ route('instructor-tracking.check-out', $schedule) }}"
                            {{ $canFill ? '' : 'disabled' }}
                        >
                            <i class="bi bi-box-arrow-right me-2"></i>Check Out & Submit
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <div class="modal fade" id="checkOutModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-bold">Submit Session Tracking?</h5>
                        <div class="text-muted small">Data akan dikirim ke Academic Team setelah instructor check-out.</div>
                    </div>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-warning mb-3">
                        Pastikan coverage scheduled material dan session notes sudah lengkap. Setelah check-out, data tidak bisa diubah kecuali dikembalikan oleh Academic Team.
                    </div>

                    <div class="border rounded-3 p-3 bg-light-subtle">
                        <div class="small text-muted mb-1">Session</div>
                        <div class="fw-semibold">{{ $batchName }} · {{ $scheduleDateLabel }}</div>
                        <div class="small text-muted mt-1">{{ $scheduleTimeLabel }}</div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="button" class="btn btn-primary btn-modern" id="btnConfirmCheckOut">
                        <i class="bi bi-box-arrow-right me-2"></i>Yes, Check Out
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const alertBox = document.getElementById('trackingAlert');
    const form = document.getElementById('trackingForm');
    const btnCheckIn = document.getElementById('btnCheckIn');
    const btnSaveDraft = document.getElementById('btnSaveDraft');
    const btnCheckOut = document.getElementById('btnCheckOut');
    const btnConfirmCheckOut = document.getElementById('btnConfirmCheckOut');
    const checkOutModalEl = document.getElementById('checkOutModal');
    const checkOutModal = checkOutModalEl && window.bootstrap ? new bootstrap.Modal(checkOutModalEl) : null;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

    const summaryDeliveredCount = document.getElementById('summaryDeliveredCount');
    const summaryPartialCount = document.getElementById('summaryPartialCount');
    const summaryNotDeliveredCount = document.getElementById('summaryNotDeliveredCount');
    const summaryPendingCount = document.getElementById('summaryPendingCount');
    const coveragePercentageText = document.getElementById('coveragePercentageText');

    const statusConfig = {
        pending: {
            label: 'Pending',
            icon: 'bi-clock-fill',
            className: 'pending',
            itemClass: 'status-pending',
        },
        delivered: {
            label: 'Delivered',
            icon: 'bi-check-circle-fill',
            className: 'delivered',
            itemClass: 'status-delivered',
        },
        partial: {
            label: 'Partial',
            icon: 'bi-exclamation-circle-fill',
            className: 'partial',
            itemClass: 'status-partial',
        },
        not_delivered: {
            label: 'Not Delivered',
            icon: 'bi-x-circle-fill',
            className: 'not_delivered',
            itemClass: 'status-not_delivered',
        },
    };

    function showAlert(type, message) {
        if (!alertBox) return;

        alertBox.className = 'alert alert-' + type + ' mb-4';
        alertBox.textContent = message;
        alertBox.classList.remove('d-none');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function hideAlert() {
        if (!alertBox) return;

        alertBox.classList.add('d-none');
        alertBox.textContent = '';
    }

    function setLoading(button, isLoading, loadingText) {
        if (!button) return;

        if (isLoading) {
            button.dataset.originalHtml = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>' + loadingText;
            return;
        }

        button.disabled = false;

        if (button.dataset.originalHtml) {
            button.innerHTML = button.dataset.originalHtml;
        }
    }

    async function submitJson(url, formData = null) {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: formData,
        });

        let payload = null;

        try {
            payload = await response.json();
        } catch (error) {
            payload = { message: 'Response server tidak valid.' };
        }

        if (!response.ok || payload.success === false) {
            const message = payload.message || Object.values(payload.errors || {})?.[0]?.[0] || 'Request gagal diproses.';
            throw new Error(message);
        }

        return payload;
    }

    function updateCoverageItem(item) {
        if (!item) return;

        const select = item.querySelector('[data-delivery-status]');
        const reasonInput = item.querySelector('[data-reason-input]');
        const reasonHelper = item.querySelector('[data-reason-helper]');
        const requiredMark = item.querySelector('.reason-required-mark');
        const badge = item.querySelector('[data-status-badge]');
        const badgeText = item.querySelector('[data-status-text]');

        const status = select?.value || 'pending';
        const config = statusConfig[status] || statusConfig.pending;
        const requiresReason = ['partial', 'not_delivered'].includes(status);

        item.classList.remove(
            'status-pending',
            'status-delivered',
            'status-partial',
            'status-not_delivered'
        );

        item.classList.add(config.itemClass);

        if (badge) {
            badge.className = 'coverage-status-badge ' + config.className;
            badge.innerHTML = `<i class="bi ${config.icon}"></i><span data-status-text>${config.label}</span>`;
        } else if (badgeText) {
            badgeText.textContent = config.label;
        }

        if (reasonInput) {
            reasonInput.required = requiresReason;
        }

        if (requiredMark) {
            requiredMark.classList.toggle('d-none', !requiresReason);
        }

        if (reasonHelper) {
            reasonHelper.classList.toggle('is-required', requiresReason);
            reasonHelper.textContent = requiresReason
                ? 'Reason wajib diisi untuk status ini.'
                : 'Optional jika materi delivered/pending.';
        }
    }

    function refreshCoverageSummary() {
        const counts = {
            pending: 0,
            delivered: 0,
            partial: 0,
            not_delivered: 0,
        };

        const selects = form?.querySelectorAll('[data-delivery-status]') || [];

        selects.forEach((select) => {
            const status = select.value || 'pending';

            if (Object.prototype.hasOwnProperty.call(counts, status)) {
                counts[status]++;
            } else {
                counts.pending++;
            }
        });

        if (summaryDeliveredCount) summaryDeliveredCount.textContent = counts.delivered;
        if (summaryPartialCount) summaryPartialCount.textContent = counts.partial;
        if (summaryNotDeliveredCount) summaryNotDeliveredCount.textContent = counts.not_delivered;
        if (summaryPendingCount) summaryPendingCount.textContent = counts.pending;

        if (coveragePercentageText) {
            const total = selects.length;
            const score = counts.delivered + (counts.partial * 0.5);
            const percentage = total > 0 ? ((score / total) * 100) : 0;

            coveragePercentageText.textContent = percentage.toFixed(2) + '%';
        }
    }

    function refreshCoverageUi() {
        document.querySelectorAll('[data-coverage-item]').forEach(updateCoverageItem);
        refreshCoverageSummary();
    }

    form?.querySelectorAll('[data-delivery-status]').forEach((select) => {
        select.addEventListener('change', function () {
            updateCoverageItem(select.closest('[data-coverage-item]'));
            refreshCoverageSummary();
        });
    });

    btnCheckIn?.addEventListener('click', async function () {
        hideAlert();
        setLoading(btnCheckIn, true, 'Checking in...');

        try {
            const payload = await submitJson(btnCheckIn.dataset.url, new FormData());
            showAlert('success', payload.message || 'Check-in berhasil.');
            setTimeout(() => window.location.reload(), 700);
        } catch (error) {
            showAlert('danger', error.message);
            setLoading(btnCheckIn, false);
        }
    });

    btnSaveDraft?.addEventListener('click', async function () {
        hideAlert();
        setLoading(btnSaveDraft, true, 'Saving...');

        try {
            const payload = await submitJson(btnSaveDraft.dataset.url, new FormData(form));
            showAlert('success', payload.message || 'Draft berhasil disimpan.');
        } catch (error) {
            showAlert('danger', error.message);
        } finally {
            setLoading(btnSaveDraft, false);
        }
    });

    btnCheckOut?.addEventListener('click', function () {
        hideAlert();

        if (checkOutModal) {
            checkOutModal.show();
            return;
        }

        btnConfirmCheckOut?.click();
    });

    btnConfirmCheckOut?.addEventListener('click', async function () {
        hideAlert();
        setLoading(btnConfirmCheckOut, true, 'Submitting...');
        setLoading(btnCheckOut, true, 'Submitting...');

        try {
            const payload = await submitJson(btnCheckOut.dataset.url, new FormData(form));

            if (checkOutModal) {
                checkOutModal.hide();
            }

            showAlert('success', payload.message || 'Check-out berhasil.');

            setTimeout(() => {
                window.location.href = payload.redirect_url || window.location.href;
            }, 800);
        } catch (error) {
            showAlert('danger', error.message);
            setLoading(btnConfirmCheckOut, false);
            setLoading(btnCheckOut, false);
        }
    });

    refreshCoverageUi();
});
</script>
@endpush