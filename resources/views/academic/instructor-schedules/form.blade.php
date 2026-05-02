@extends('layouts.app-dashboard')

@section('title', ($submitMode === 'edit' ? 'Edit' : 'Create') . ' Instructor Schedule')

@section('content')
@php
    use Illuminate\Support\Carbon;

    $isEdit = $submitMode === 'edit';
    $formAction = $isEdit
        ? route('instructor-schedules.update', $schedule)
        : route('instructor-schedules.store');

    $statusValue = old('status', $schedule->status ?? 'scheduled');
    $deliveryValue = old('delivery_mode', $schedule->delivery_mode ?? 'online');
    $makeupValue = old('is_makeup_session', $schedule->is_makeup_session ?? false);
    $programValue = old('program_id', $schedule->program_id);
    $batchValue = old('batch_id', $schedule->batch_id);
    $instructorValue = old('instructor_id', $schedule->instructor_id);
    $replacementInstructorValue = old('replacement_instructor_id', $schedule->replacement_instructor_id);
    $subTopicValue = old('sub_topic_id', $schedule->sub_topic_id);
    $rescheduledFromValue = old('rescheduled_from_id', $schedule->rescheduled_from_id);

    $selectedSubTopicIds = collect(old('sub_topic_ids', $selectedSubTopicIds ?? []))
        ->filter(fn ($id) => filled($id))
        ->map(fn ($id) => (string) $id)
        ->values();

    if (
        $selectedSubTopicIds->isEmpty()
        && isset($schedule)
        && method_exists($schedule, 'relationLoaded')
        && $schedule->relationLoaded('subTopics')
    ) {
        $selectedSubTopicIds = $schedule->subTopics
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->values();
    }

    if ($selectedSubTopicIds->isEmpty() && filled($subTopicValue)) {
        $selectedSubTopicIds = collect([(string) $subTopicValue]);
    }

    /*
    |--------------------------------------------------------------------------
    | Material Topics Payload
    |--------------------------------------------------------------------------
    */
    $materialTopicsPayload = collect($materialTopicsPayload ?? []);

    if ($materialTopicsPayload->isEmpty()) {
        $materialTopicsPayload = collect($materialTopics ?? [])
            ->map(function ($topic) {
                $subTopics = collect(data_get($topic, 'sub_topics', data_get($topic, 'subTopics', [])))
                    ->filter(function ($subTopic) {
                        return (data_get($subTopic, 'lesson_type') ?: 'live_session') === 'live_session';
                    })
                    ->map(function ($subTopic) {
                        return [
                            'id' => (string) data_get($subTopic, 'id'),
                            'name' => data_get($subTopic, 'name'),
                            'description' => data_get($subTopic, 'description'),
                            'lesson_type' => data_get($subTopic, 'lesson_type'),
                            'sort_order' => (int) (data_get($subTopic, 'sort_order') ?? 0),
                        ];
                    })
                    ->filter(fn ($subTopic) => filled($subTopic['id']) && filled($subTopic['name']))
                    ->sortBy('sort_order')
                    ->values();

                return [
                    'id' => (string) data_get($topic, 'id'),
                    'name' => data_get($topic, 'name', 'Untitled Topic'),
                    'module' => data_get($topic, 'module.name'),
                    'stage' => data_get($topic, 'module.stage.name'),
                    'program_id' => (string) (
                        data_get($topic, 'module.stage.program_id')
                        ?? data_get($topic, 'program_id')
                        ?? ''
                    ),
                    'sub_topics' => $subTopics,
                ];
            })
            ->filter(fn ($topic) => filled($topic['id']) && collect($topic['sub_topics'])->isNotEmpty())
            ->values();
    }

    if ($materialTopicsPayload->isEmpty()) {
        $materialTopicsPayload = collect($subTopics ?? [])
            ->filter(function ($subTopic) {
                return (data_get($subTopic, 'lesson_type') ?: 'live_session') === 'live_session';
            })
            ->groupBy(function ($subTopic) {
                return data_get($subTopic, 'topic_id') ?: data_get($subTopic, 'topic.id') ?: 'no-topic';
            })
            ->map(function ($items, $topicId) {
                $first = $items->first();
                $topic = data_get($first, 'topic');

                return [
                    'id' => (string) $topicId,
                    'name' => data_get($topic, 'name', $topicId === 'no-topic' ? 'Uncategorized Topic' : 'Topic #' . $topicId),
                    'module' => data_get($topic, 'module.name'),
                    'stage' => data_get($topic, 'module.stage.name'),
                    'program_id' => (string) (data_get($topic, 'module.stage.program_id') ?? ''),
                    'sub_topics' => $items
                        ->map(function ($subTopic) {
                            return [
                                'id' => (string) data_get($subTopic, 'id'),
                                'name' => data_get($subTopic, 'name'),
                                'description' => data_get($subTopic, 'description'),
                                'lesson_type' => data_get($subTopic, 'lesson_type'),
                                'sort_order' => (int) (data_get($subTopic, 'sort_order') ?? 0),
                            ];
                        })
                        ->filter(fn ($subTopic) => filled($subTopic['id']) && filled($subTopic['name']))
                        ->sortBy('sort_order')
                        ->values(),
                ];
            })
            ->filter(fn ($topic) => collect($topic['sub_topics'])->isNotEmpty())
            ->values();
    }
@endphp

<div class="container-fluid px-4 py-4 instructor-schedules-form-page">
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Academic</div>
                <h1 class="page-title mb-2">{{ $isEdit ? 'Edit Instructor Schedule' : 'Create Instructor Schedule' }}</h1>
                <p class="page-subtitle mb-0">
                    Atur jadwal sesi instruktur dengan informasi pengajar, batch, waktu pelaksanaan, dan kebutuhan reschedule dalam satu form yang rapi.
                </p>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('instructor-schedules.index') }}" class="btn btn-light border btn-modern">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>

                <button type="button" id="saveDraftBtn" class="btn btn-light btn-modern">
                    <i class="bi bi-save me-1"></i> Save Draft
                </button>

                <button type="button" id="submitScheduleBtnTop" class="btn btn-light btn-modern">
                    <i class="bi bi-check-circle me-1"></i> {{ $isEdit ? 'Update Schedule' : 'Create Schedule' }}
                </button>
            </div>
        </div>
    </div>

    <div
        id="toastContainer"
        aria-live="polite"
        aria-atomic="true"
        style="position: fixed; top: 88px; right: 20px; z-index: 2147483647; width: min(420px, calc(100vw - 40px)); pointer-events: none;"
    ></div>

    <div class="content-card mb-4">
        <div class="content-card-body">
            <div class="row g-3 align-items-stretch schedule-summary-row">
                <div class="col-lg-8 d-flex">
                    <div class="progress-summary-wrap w-100">
                        <div class="progress-summary-top d-flex justify-content-between align-items-center gap-3 mb-2">
                            <div>
                                <div class="progress-summary-label">Form Progress</div>
                                <div class="progress-summary-subtitle">
                                    Lengkapi informasi utama jadwal, assignment, pelaksanaan sesi, dan status agar data siap disimpan.
                                </div>
                            </div>

                            <div class="progress-summary-count">
                                <span id="completedSectionCount">0</span> dari <span id="totalSectionCount">4</span> bagian sudah siap
                            </div>
                        </div>

                        <div class="progress progress-modern" role="progressbar" aria-label="Schedule form progress">
                            <div id="sectionProgressBar" class="progress-bar" style="width: 0%"></div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 d-flex">
                    <div class="status-pill-card w-100">
                        <div class="status-pill-label">Session Status</div>
                        <div class="status-pill-value" id="liveStatusText">
                            {{ $statusOptions[$statusValue] ?? ucfirst($statusValue) }}
                        </div>
                        <div class="status-pill-help" id="liveSummaryText">
                            {{ $makeupValue ? 'Sesi ini ditandai sebagai makeup session.' : 'Sesi ini disiapkan sebagai jadwal reguler.' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form id="instructorScheduleForm" action="{{ $formAction }}" method="POST" novalidate>
        @csrf

        @if ($isEdit)
            @method('PUT')
        @endif

        <div class="row g-4">
            <div class="col-xl-3">
                <div class="section-nav-card sticky-top" style="top: 92px;">
                    <div class="section-nav-header">
                        <div class="section-nav-title">Form Sections</div>
                        <div class="section-nav-subtitle">Lengkapi seluruh bagian jadwal instruktur.</div>
                    </div>

                    <div class="nav flex-column nav-pills custom-section-nav" id="schedule-section-tab" role="tablist" aria-orientation="vertical">
                        <button class="nav-link active" id="tab-overview-btn" data-bs-toggle="pill" data-bs-target="#tab-overview" type="button" role="tab">
                            <span class="nav-link-content">
                                <span class="nav-link-icon"><i class="bi bi-grid-1x2-fill"></i></span>
                                <span class="nav-link-text">
                                    <span class="nav-link-title">Session Overview</span>
                                    <span class="nav-link-subtitle">Judul, program, dan materi</span>
                                </span>
                            </span>
                            <span class="section-check" data-section-indicator="overview"><i class="bi bi-circle"></i></span>
                        </button>

                        <button class="nav-link" id="tab-assignment-btn" data-bs-toggle="pill" data-bs-target="#tab-assignment" type="button" role="tab">
                            <span class="nav-link-content">
                                <span class="nav-link-icon"><i class="bi bi-people-fill"></i></span>
                                <span class="nav-link-text">
                                    <span class="nav-link-title">Assignment</span>
                                    <span class="nav-link-subtitle">Instruktur, batch, dan reschedule</span>
                                </span>
                            </span>
                            <span class="section-check" data-section-indicator="assignment"><i class="bi bi-circle"></i></span>
                        </button>

                        <button class="nav-link" id="tab-delivery-btn" data-bs-toggle="pill" data-bs-target="#tab-delivery" type="button" role="tab">
                            <span class="nav-link-content">
                                <span class="nav-link-icon"><i class="bi bi-calendar2-week-fill"></i></span>
                                <span class="nav-link-text">
                                    <span class="nav-link-title">Schedule & Delivery</span>
                                    <span class="nav-link-subtitle">Tanggal, jam, dan mode pelaksanaan</span>
                                </span>
                            </span>
                            <span class="section-check" data-section-indicator="delivery"><i class="bi bi-circle"></i></span>
                        </button>

                        <button class="nav-link" id="tab-status-btn" data-bs-toggle="pill" data-bs-target="#tab-status" type="button" role="tab">
                            <span class="nav-link-content">
                                <span class="nav-link-icon"><i class="bi bi-clipboard-check-fill"></i></span>
                                <span class="nav-link-text">
                                    <span class="nav-link-title">Status & Notes</span>
                                    <span class="nav-link-subtitle">Status sesi dan catatan pendukung</span>
                                </span>
                            </span>
                            <span class="section-check" data-section-indicator="status"><i class="bi bi-circle"></i></span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-xl-9">
                <div class="tab-content" id="schedule-section-tabContent">
                    <div class="tab-pane fade show active" id="tab-overview" role="tabpanel" aria-labelledby="tab-overview-btn">
                        <div class="content-card section-card mb-4">
                            <div class="content-card-header section-card-header">
                                <div>
                                    <h5 class="content-card-title mb-1">Session Overview</h5>
                                    <p class="content-card-subtitle mb-0">Isi informasi dasar sesi dan materi live session yang akan dibawakan.</p>
                                </div>

                                <div class="section-status-badge" data-section-badge="overview">
                                    <i class="bi bi-hourglass-split me-1"></i> Need Input
                                </div>
                            </div>

                            <div class="content-card-body">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label">Session Title <span class="text-danger">*</span></label>
                                        <input type="text" name="session_title" class="form-control section-overview" value="{{ old('session_title', $schedule->session_title) }}" placeholder="Contoh: Laravel CRUD & API Integration">
                                        <div class="invalid-feedback error-text" data-error-for="session_title"></div>
                                    </div>

                                    <div class="col-lg-6">
                                        <label class="form-label">Program</label>
                                        <select name="program_id" id="program_id" class="form-select section-overview">
                                            <option value="">Select Program</option>
                                            @foreach ($programs as $program)
                                                <option value="{{ $program->id }}" @selected((string) $programValue === (string) $program->id)>
                                                    {{ $program->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback error-text" data-error-for="program_id"></div>
                                    </div>

                                    <div class="col-lg-6">
                                        <label class="form-label">Topic <span class="text-danger">*</span></label>
                                        <select id="materialTopicSelect" class="form-select section-overview">
                                            <option value="">Select Topic</option>
                                        </select>

                                        <input type="hidden" name="sub_topic_id" id="sub_topic_id" value="{{ $subTopicValue }}">

                                        <div class="form-text">Pilih topic terlebih dahulu, lalu checklist sub topic live session di bawah.</div>
                                        <div class="invalid-feedback error-text" data-error-for="sub_topic_id"></div>
                                    </div>

                                    <div class="col-12">
                                        <div class="border rounded-3 bg-light p-3 mt-2">
                                            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
                                                <div>
                                                    <h6 class="fw-bold mb-1">Scheduled Materials</h6>
                                                    <p class="text-muted small mb-0">
                                                        Pilih topic terlebih dahulu, lalu checklist sub topic live session yang akan dibawakan pada jadwal ini.
                                                    </p>
                                                </div>

                                                <span class="badge text-bg-light border text-primary px-3 py-2">
                                                    <span id="selectedMaterialCount">0</span> selected
                                                </span>
                                            </div>

                                            <div class="row g-3">
                                                <div class="col-12">
                                                    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-2">
                                                        <label class="form-label mb-0">Sub Topics <span class="text-danger">*</span></label>

                                                        <div class="d-flex gap-2 flex-wrap">
                                                            <button type="button" id="checkAllSubTopicsBtn" class="btn btn-sm btn-light border">
                                                                <i class="bi bi-check2-square me-1"></i> Check All
                                                            </button>

                                                            <button type="button" id="uncheckAllSubTopicsBtn" class="btn btn-sm btn-light border">
                                                                <i class="bi bi-square me-1"></i> Uncheck All
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <div id="materialSubTopicList" class="app-checklist-scroll border rounded-3 bg-white p-2">
                                                        <div class="p-4 text-center text-muted bg-white border rounded-3 small">
                                                            Pilih topic untuk menampilkan sub topic live session.
                                                        </div>
                                                    </div>

                                                    <div class="invalid-feedback error-text d-block" data-error-for="sub_topic_ids"></div>
                                                    <div class="invalid-feedback error-text d-block" data-error-for="sub_topic_ids.0"></div>
                                                </div>
                                            </div>

                                            <div class="border-top pt-3 mt-3">
                                                <div class="fw-bold small mb-2">Selected Materials</div>
                                                <div id="selectedMaterialsList" class="d-flex flex-wrap gap-2">
                                                    <span class="text-muted small">Belum ada sub topic yang dipilih.</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-assignment" role="tabpanel" aria-labelledby="tab-assignment-btn">
                        <div class="content-card section-card mb-4">
                            <div class="content-card-header section-card-header">
                                <div>
                                    <h5 class="content-card-title mb-1">Assignment</h5>
                                    <p class="content-card-subtitle mb-0">Tentukan instruktur utama, pengganti jika diperlukan, dan batch yang mengikuti sesi.</p>
                                </div>

                                <div class="section-status-badge" data-section-badge="assignment">
                                    <i class="bi bi-hourglass-split me-1"></i> Need Input
                                </div>
                            </div>

                            <div class="content-card-body">
                                <div class="row g-3">
                                    <div class="col-lg-6">
                                        <label class="form-label">Instructor <span class="text-danger">*</span></label>
                                        <select name="instructor_id" class="form-select section-assignment">
                                            <option value="">Select Instructor</option>
                                            @foreach ($instructors as $instructor)
                                                <option value="{{ $instructor->id }}" @selected((string) $instructorValue === (string) $instructor->id)>
                                                    {{ $instructor->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback error-text" data-error-for="instructor_id"></div>
                                    </div>

                                    <div class="col-lg-6">
                                        <label class="form-label">Replacement Instructor</label>
                                        <select name="replacement_instructor_id" class="form-select section-assignment">
                                            <option value="">No Replacement Instructor</option>
                                            @foreach ($replacementInstructors as $replacementInstructor)
                                                <option value="{{ $replacementInstructor->id }}" @selected((string) $replacementInstructorValue === (string) $replacementInstructor->id)>
                                                    {{ $replacementInstructor->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback error-text" data-error-for="replacement_instructor_id"></div>
                                    </div>

                                    <div class="col-lg-6">
                                        <label class="form-label">Batch <span class="text-danger">*</span></label>
                                        <select name="batch_id" id="batch_id" class="form-select section-assignment">
                                            <option value="">Select Batch</option>
                                            @foreach ($batches as $batch)
                                                @php
                                                    $batchProgramName = $batch->program->name ?? 'No Program';
                                                @endphp

                                                <option
                                                    value="{{ $batch->id }}"
                                                    data-program-id="{{ $batch->program_id }}"
                                                    data-program-name="{{ $batchProgramName }}"
                                                    @selected((string) $batchValue === (string) $batch->id)
                                                >
                                                    {{ $batch->name }} — {{ $batchProgramName }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="form-text">Program akan otomatis mengikuti batch yang dipilih.</div>
                                        <div class="invalid-feedback error-text" data-error-for="batch_id"></div>
                                    </div>

                                    <div class="col-lg-6 d-flex align-items-end">
                                        <div class="form-switch-card w-100">
                                            <div>
                                                <div class="form-switch-title">Makeup Session</div>
                                                <div class="form-switch-subtitle">Aktifkan jika sesi ini merupakan sesi pengganti atau susulan.</div>
                                            </div>

                                            <div class="form-check form-switch m-0">
                                                <input class="form-check-input section-assignment" type="checkbox" role="switch" id="is_makeup_session" name="is_makeup_session" value="1" @checked($makeupValue)>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12" id="rescheduledFromWrapper" style="display: none;">
                                        <label class="form-label">Rescheduled From</label>
                                        <select name="rescheduled_from_id" class="form-select section-assignment">
                                            <option value="">Select Previous Session</option>
                                            @foreach ($reschedulableSchedules as $item)
                                                @php
                                                    $teachingInstructor = $item->replacementInstructor?->name ?: $item->instructor?->name;
                                                    $scheduleDate = $item->schedule_date ? Carbon::parse($item->schedule_date)->format('d M Y') : '-';
                                                    $timeLabel = trim(($item->start_time ?? '-') . ' - ' . ($item->end_time ?? '-'));
                                                    $itemBatchName = $item->batch->name ?? '-';
                                                    $itemProgramName = $item->batch?->program?->name;
                                                @endphp
                                                <option value="{{ $item->id }}" @selected((string) $rescheduledFromValue === (string) $item->id)>
                                                    {{ $item->session_title }} — {{ $scheduleDate }} — {{ $timeLabel }} — {{ $itemBatchName }}{{ $itemProgramName ? ' / ' . $itemProgramName : '' }} — {{ $teachingInstructor ?? '-' }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback error-text" data-error-for="rescheduled_from_id"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-delivery" role="tabpanel" aria-labelledby="tab-delivery-btn">
                        <div class="content-card section-card mb-4">
                            <div class="content-card-header section-card-header">
                                <div>
                                    <h5 class="content-card-title mb-1">Schedule & Delivery</h5>
                                    <p class="content-card-subtitle mb-0">Atur tanggal, jam, mode pelaksanaan, dan detail pendukung sesi.</p>
                                </div>

                                <div class="section-status-badge" data-section-badge="delivery">
                                    <i class="bi bi-hourglass-split me-1"></i> Need Input
                                </div>
                            </div>

                            <div class="content-card-body">
                                <div class="row g-3">
                                    <div class="col-lg-4">
                                        <label class="form-label">Schedule Date <span class="text-danger">*</span></label>
                                        <input type="date" name="schedule_date" class="form-control section-delivery" value="{{ old('schedule_date', optional($schedule->schedule_date)->format('Y-m-d')) }}">
                                        <div class="invalid-feedback error-text" data-error-for="schedule_date"></div>
                                    </div>

                                    <div class="col-lg-4">
                                        <label class="form-label">Start Time <span class="text-danger">*</span></label>
                                        <input type="time" name="start_time" class="form-control section-delivery" value="{{ old('start_time', $schedule->start_time) }}">
                                        <div class="invalid-feedback error-text" data-error-for="start_time"></div>
                                    </div>

                                    <div class="col-lg-4">
                                        <label class="form-label">End Time <span class="text-danger">*</span></label>
                                        <input type="time" name="end_time" class="form-control section-delivery" value="{{ old('end_time', $schedule->end_time) }}">
                                        <div class="invalid-feedback error-text" data-error-for="end_time"></div>
                                    </div>

                                    <div class="col-lg-4">
                                        <label class="form-label">Delivery Mode <span class="text-danger">*</span></label>
                                        <select name="delivery_mode" id="delivery_mode" class="form-select section-delivery">
                                            @foreach ($deliveryModeOptions as $value => $label)
                                                <option value="{{ $value }}" @selected($deliveryValue === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback error-text" data-error-for="delivery_mode"></div>
                                    </div>

                                    <div class="col-lg-4">
                                        <label class="form-label">Meeting Link</label>
                                        <input type="url" name="meeting_link" class="form-control section-delivery" value="{{ old('meeting_link', $schedule->meeting_link) }}" placeholder="https://...">
                                        <div class="invalid-feedback error-text" data-error-for="meeting_link"></div>
                                    </div>

                                    <div class="col-lg-4">
                                        <label class="form-label">Location</label>
                                        <input type="text" name="location" class="form-control section-delivery" value="{{ old('location', $schedule->location) }}" placeholder="Room / Campus / Address">
                                        <div class="invalid-feedback error-text" data-error-for="location"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-status" role="tabpanel" aria-labelledby="tab-status-btn">
                        <div class="content-card section-card mb-4">
                            <div class="content-card-header section-card-header">
                                <div>
                                    <h5 class="content-card-title mb-1">Status & Notes</h5>
                                    <p class="content-card-subtitle mb-0">Pilih status sesi dan tambahkan catatan yang membantu tim akademik memahami kondisi jadwal.</p>
                                </div>

                                <div class="section-status-badge" data-section-badge="status">
                                    <i class="bi bi-hourglass-split me-1"></i> Need Input
                                </div>
                            </div>

                            <div class="content-card-body">
                                <div class="row g-3">
                                    <div class="col-lg-4">
                                        <label class="form-label">Status <span class="text-danger">*</span></label>
                                        <select name="status" id="statusField" class="form-select section-status">
                                            @foreach ($statusOptions as $value => $label)
                                                <option value="{{ $value }}" @selected($statusValue === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback error-text" data-error-for="status"></div>
                                    </div>

                                    <div class="col-lg-8">
                                        <label class="form-label">Notes</label>
                                        <textarea name="notes" class="form-control section-status" rows="6" placeholder="Tambahkan catatan bila ada perubahan jadwal, kebutuhan khusus, atau informasi penting lainnya.">{{ old('notes', $schedule->notes) }}</textarea>
                                        <div class="invalid-feedback error-text" data-error-for="notes"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if ($isEdit)
                            <div class="content-card mb-4">
                                <div class="content-card-header section-card-header">
                                    <div>
                                        <h5 class="content-card-title mb-1">Quick Info</h5>
                                        <p class="content-card-subtitle mb-0">Informasi singkat dari jadwal yang sedang dibuka.</p>
                                    </div>

                                    <div class="section-status-badge optional">
                                        <i class="bi bi-dash-circle me-1"></i> Optional
                                    </div>
                                </div>

                                <div class="content-card-body">
                                    <div class="simple-list">
                                        <div class="simple-list-item">
                                            <div class="simple-list-title">Created At</div>
                                            <div class="simple-list-meta">{{ optional($schedule->created_at)->format('d M Y H:i') ?? '-' }}</div>
                                        </div>

                                        <div class="simple-list-item">
                                            <div class="simple-list-title">Updated At</div>
                                            <div class="simple-list-meta">{{ optional($schedule->updated_at)->format('d M Y H:i') ?? '-' }}</div>
                                        </div>

                                        @if ($schedule->rescheduledFrom)
                                            <div class="simple-list-item">
                                                <div class="simple-list-title">Previous Session</div>
                                                <div class="simple-list-meta">{{ $schedule->rescheduledFrom->session_title }}</div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="content-card">
                    <div class="content-card-body">
                        <div class="row align-items-center g-3">
                            <div class="col-12 col-lg">
                                <div class="footer-note">
                                    <div class="footer-note-title">Final Check</div>
                                    <div class="footer-note-subtitle">
                                        Pastikan jadwal, instruktur, batch, dan materi live session sudah sesuai sebelum menyimpan data.
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-lg-auto">
                                <div class="d-flex justify-content-end gap-2 flex-wrap">
                                    <a href="{{ route('instructor-schedules.index') }}" class="btn btn-light border">
                                        Cancel
                                    </a>

                                    <button type="button" id="saveDraftBtnBottom" class="btn btn-light btn-modern">
                                        <i class="bi bi-save me-1"></i> Save Draft
                                    </button>

                                    <button type="button" id="submitScheduleBtnBottom" class="btn btn-primary btn-modern">
                                        <i class="bi bi-check-circle me-1"></i> {{ $isEdit ? 'Update Schedule' : 'Create Schedule' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('instructorScheduleForm');
    const saveDraftBtn = document.getElementById('saveDraftBtn');
    const saveDraftBtnBottom = document.getElementById('saveDraftBtnBottom');
    const submitScheduleBtnTop = document.getElementById('submitScheduleBtnTop');
    const submitScheduleBtnBottom = document.getElementById('submitScheduleBtnBottom');

    const isMakeupSession = document.getElementById('is_makeup_session');
    const rescheduledFromWrapper = document.getElementById('rescheduledFromWrapper');

    const batchSelect = document.getElementById('batch_id');
    const programSelect = document.getElementById('program_id');
    const statusField = document.getElementById('statusField');

    const liveStatusText = document.getElementById('liveStatusText');
    const liveSummaryText = document.getElementById('liveSummaryText');
    const completedSectionCount = document.getElementById('completedSectionCount');
    const totalSectionCount = document.getElementById('totalSectionCount');
    const sectionProgressBar = document.getElementById('sectionProgressBar');

    const primarySubTopicInput = document.getElementById('sub_topic_id');
    const materialTopicSelect = document.getElementById('materialTopicSelect');
    const materialSubTopicList = document.getElementById('materialSubTopicList');
    const selectedMaterialsList = document.getElementById('selectedMaterialsList');
    const selectedMaterialCount = document.getElementById('selectedMaterialCount');
    const checkAllSubTopicsBtn = document.getElementById('checkAllSubTopicsBtn');
    const uncheckAllSubTopicsBtn = document.getElementById('uncheckAllSubTopicsBtn');

    let materialTopics = @json($materialTopicsPayload);
    const materialTopicsUrl = @json(route('instructor-schedules.material-topics'));

    const initialSelectedMaterialIds = @json($selectedSubTopicIds->values());
    const selectedMaterialIds = new Set(initialSelectedMaterialIds.map((id) => String(id)));

    const materialLookup = new Map();

    hydrateMaterialLookup(materialTopics);

    if (totalSectionCount) {
        totalSectionCount.textContent = '4';
    }

    function hydrateMaterialLookup(topics) {
        (topics || []).forEach((topic) => {
            (topic.sub_topics || []).forEach((subTopic) => {
                materialLookup.set(String(subTopic.id), {
                    ...subTopic,
                    topic_name: topic.name,
                    module_name: topic.module,
                    stage_name: topic.stage,
                });
            });
        });
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function ensureToastContainer() {
        let container = document.getElementById('toastContainer');

        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.setAttribute('aria-live', 'polite');
            container.setAttribute('aria-atomic', 'true');
            document.body.appendChild(container);
        }

        container.style.position = 'fixed';
        container.style.top = '88px';
        container.style.right = '20px';
        container.style.zIndex = '2147483647';
        container.style.width = 'min(420px, calc(100vw - 40px))';
        container.style.pointerEvents = 'none';

        return container;
    }

    function rememberToast(message, type = 'success') {
        const payload = JSON.stringify({ message, type });

        try {
            sessionStorage.setItem('instructorScheduleToast', payload);
            localStorage.setItem('instructorScheduleToast', payload);
        } catch (error) {
            // Ignore storage error.
        }
    }

    function consumeRememberedToast() {
        try {
            const rawToast =
                sessionStorage.getItem('instructorScheduleToast')
                || localStorage.getItem('instructorScheduleToast');

            if (!rawToast) {
                return;
            }

            sessionStorage.removeItem('instructorScheduleToast');
            localStorage.removeItem('instructorScheduleToast');

            const parsedToast = JSON.parse(rawToast);

            if (parsedToast?.message) {
                setTimeout(() => {
                    showToast(parsedToast.message, parsedToast.type || 'success');
                }, 350);
            }
        } catch (error) {
            sessionStorage.removeItem('instructorScheduleToast');
            localStorage.removeItem('instructorScheduleToast');
        }
    }

    function showToast(message, type = 'success') {
        const container = ensureToastContainer();
        const toastId = 'app-toast-' + Date.now();

        const typeMap = {
            success: {
                icon: 'bi-check-circle-fill',
                title: 'Success',
                border: '#16a34a',
                bg: '#ecfdf5',
                text: '#166534',
            },
            danger: {
                icon: 'bi-x-circle-fill',
                title: 'Failed',
                border: '#dc2626',
                bg: '#fef2f2',
                text: '#991b1b',
            },
            error: {
                icon: 'bi-x-circle-fill',
                title: 'Failed',
                border: '#dc2626',
                bg: '#fef2f2',
                text: '#991b1b',
            },
            warning: {
                icon: 'bi-exclamation-triangle-fill',
                title: 'Warning',
                border: '#f59e0b',
                bg: '#fffbeb',
                text: '#92400e',
            },
            info: {
                icon: 'bi-info-circle-fill',
                title: 'Info',
                border: '#2563eb',
                bg: '#eff6ff',
                text: '#1d4ed8',
            },
        };

        const config = typeMap[type] || typeMap.success;

        const toast = document.createElement('div');
        toast.id = toastId;
        toast.style.pointerEvents = 'auto';
        toast.style.background = '#ffffff';
        toast.style.border = '1px solid rgba(15, 23, 42, .08)';
        toast.style.borderLeft = `5px solid ${config.border}`;
        toast.style.borderRadius = '16px';
        toast.style.boxShadow = '0 18px 45px rgba(15, 23, 42, .18)';
        toast.style.padding = '14px 14px';
        toast.style.marginBottom = '12px';
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-10px)';
        toast.style.transition = 'all .22s ease';
        toast.style.overflow = 'hidden';

        toast.innerHTML = `
            <div style="display:flex; gap:12px; align-items:flex-start;">
                <div style="width:34px; height:34px; border-radius:12px; display:flex; align-items:center; justify-content:center; background:${config.bg}; color:${config.text}; flex:0 0 auto;">
                    <i class="bi ${config.icon}"></i>
                </div>

                <div style="flex:1; min-width:0;">
                    <div style="font-weight:700; color:#111827; line-height:1.2; margin-bottom:3px;">
                        ${escapeHtml(config.title)}
                    </div>
                    <div style="font-size:13px; color:#4b5563; line-height:1.45;">
                        ${escapeHtml(message)}
                    </div>
                </div>

                <button type="button" aria-label="Close" style="border:0; background:transparent; color:#64748b; font-size:18px; line-height:1; padding:0 2px; cursor:pointer;">
                    &times;
                </button>
            </div>
        `;

        const closeToast = () => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-10px)';

            setTimeout(() => {
                toast.remove();
            }, 220);
        };

        toast.querySelector('button')?.addEventListener('click', closeToast);

        container.appendChild(toast);

        requestAnimationFrame(() => {
            toast.style.opacity = '1';
            toast.style.transform = 'translateY(0)';
        });

        setTimeout(closeToast, 3600);
    }

    window.showInstructorScheduleToast = showToast;

    consumeRememberedToast();

    @if (session('success'))
        showToast(@json(session('success')), 'success');
    @endif

    @if (session('error'))
        showToast(@json(session('error')), 'danger');
    @endif

    @if ($errors->any())
        showToast('Ada beberapa input yang perlu diperbaiki.', 'danger');
    @endif

    function clearValidationErrors() {
        if (!form) return;

        form.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
        form.querySelectorAll('.error-text').forEach((el) => el.textContent = '');
    }

    function cssEscapeName(name) {
        return String(name)
            .replaceAll('[', '\\[')
            .replaceAll(']', '\\]');
    }

    function applyValidationErrors(errors) {
        if (!form || !errors) return;

        Object.entries(errors).forEach(([key, messages]) => {
            const message = Array.isArray(messages) ? messages[0] : messages;
            const field = form.querySelector(`[name="${cssEscapeName(key)}"]`);

            if (field) {
                field.classList.add('is-invalid');
            }

            const errorHolder = form.querySelector(`[data-error-for="${key}"]`);
            if (errorHolder) {
                errorHolder.textContent = message;
            }
        });
    }

    function isFilled(value) {
        return value !== null && value !== undefined && String(value).trim() !== '';
    }

    function toggleRescheduledField() {
        if (!rescheduledFromWrapper || !isMakeupSession) return;

        rescheduledFromWrapper.style.display = isMakeupSession.checked ? '' : 'none';
    }

    function syncProgramFromBatch(force = true) {
        if (!batchSelect || !programSelect) return;

        const selectedOption = batchSelect.options[batchSelect.selectedIndex];
        const programId = selectedOption?.dataset.programId;

        if (programId && (force || !programSelect.value)) {
            programSelect.value = programId;
        }
    }

    function updateLiveStatus() {
        if (!statusField || !liveStatusText || !liveSummaryText) return;

        liveStatusText.textContent = statusField.options[statusField.selectedIndex]?.text || 'Scheduled';
        liveSummaryText.textContent = isMakeupSession?.checked
            ? 'Sesi ini ditandai sebagai makeup session.'
            : 'Sesi ini disiapkan sebagai jadwal reguler.';
    }

    function setTopicLoadingState(message = 'Loading topics...') {
        if (materialTopicSelect) {
            materialTopicSelect.disabled = true;
            materialTopicSelect.innerHTML = `<option value="">${escapeHtml(message)}</option>`;
        }

        if (materialSubTopicList) {
            materialSubTopicList.innerHTML = `
                <div class="p-4 text-center text-muted bg-white border rounded-3 small">
                    <span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
                    Memuat topic dan sub topic live session...
                </div>
            `;
        }

        updateBulkButtons();
    }

    function setTopicEmptyState(message = 'Belum ada topic live session untuk program ini.') {
        if (materialTopicSelect) {
            materialTopicSelect.disabled = false;
            materialTopicSelect.innerHTML = '<option value="">Select Topic</option>';
        }

        if (materialSubTopicList) {
            materialSubTopicList.innerHTML = `
                <div class="p-4 text-center text-muted bg-white border rounded-3 small">
                    ${escapeHtml(message)}
                </div>
            `;
        }

        updateBulkButtons();
    }

    async function fetchMaterialTopicsByProgram(programId, options = {}) {
        const keepSelection = options.keepSelection ?? true;
        const pruneUnavailable = options.pruneUnavailable ?? false;
        const keepTopicId = options.keepTopicId ?? null;
        const showSuccessToast = options.showSuccessToast ?? false;

        if (!materialTopicsUrl) {
            renderTopicOptions(keepTopicId);
            return;
        }

        if (!programId) {
            materialTopics = [];

            if (!keepSelection) {
                selectedMaterialIds.clear();
            }

            renderTopicOptions(null);
            renderSelectedMaterials();
            syncPrimarySubTopicFromMaterials();
            refreshProgress();

            return;
        }

        setTopicLoadingState();

        try {
            const url = new URL(materialTopicsUrl, window.location.origin);
            url.searchParams.set('program_id', programId);

            const response = await fetch(url.toString(), {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok || data.success === false) {
                throw new Error(data.message || 'Gagal memuat topic live session.');
            }

            materialTopics = Array.isArray(data.topics) ? data.topics : [];
            hydrateMaterialLookup(materialTopics);

            const availableSubTopicIds = new Set();

            materialTopics.forEach((topic) => {
                (topic.sub_topics || []).forEach((subTopic) => {
                    availableSubTopicIds.add(String(subTopic.id));
                });
            });

            if (!keepSelection) {
                selectedMaterialIds.clear();
            } else if (pruneUnavailable) {
                Array.from(selectedMaterialIds).forEach((id) => {
                    if (!availableSubTopicIds.has(String(id))) {
                        selectedMaterialIds.delete(String(id));
                    }
                });
            }

            const selectedTopicId = keepTopicId || findTopicIdBySelectedMaterials();

            renderTopicOptions(selectedTopicId);
            renderSelectedMaterials();
            syncPrimarySubTopicFromMaterials();
            refreshProgress();

            if (!materialTopics.length) {
                setTopicEmptyState('Belum ada topic live session untuk program ini.');
                showToast('Belum ada topic live session untuk program ini.', 'warning');
                return;
            }

            if (showSuccessToast) {
                showToast('Topic live session berhasil dimuat.', 'success');
            }
        } catch (error) {
            materialTopics = [];

            if (!keepSelection) {
                selectedMaterialIds.clear();
            }

            setTopicEmptyState('Topic gagal dimuat. Coba pilih program ulang.');
            renderSelectedMaterials();
            syncPrimarySubTopicFromMaterials();
            refreshProgress();

            showToast(error.message || 'Topic gagal dimuat.', 'danger');
        }
    }

    function getVisibleTopics() {
        const programId = programSelect?.value ? String(programSelect.value) : '';

        const topics = materialTopics.filter((topic) => {
            return !programId || !topic.program_id || String(topic.program_id) === programId;
        });

        return topics.length ? topics : materialTopics;
    }

    function findTopicIdBySelectedMaterials() {
        for (const id of selectedMaterialIds) {
            for (const topic of materialTopics) {
                const found = (topic.sub_topics || []).some((subTopic) => String(subTopic.id) === String(id));

                if (found) {
                    return String(topic.id);
                }
            }
        }

        return null;
    }

    function findSubTopicById(id) {
        const targetId = String(id);

        for (const topic of materialTopics) {
            const subTopic = (topic.sub_topics || []).find((item) => String(item.id) === targetId);

            if (subTopic) {
                return {
                    ...subTopic,
                    topic_name: topic.name,
                    module_name: topic.module,
                    stage_name: topic.stage,
                };
            }
        }

        if (materialLookup.has(targetId)) {
            return materialLookup.get(targetId);
        }

        return null;
    }

    function renderTopicOptions(keepTopicId = null) {
        if (!materialTopicSelect) return;

        const visibleTopics = getVisibleTopics();
        const selectedTopicId = keepTopicId || materialTopicSelect.value || findTopicIdBySelectedMaterials();

        materialTopicSelect.disabled = false;
        materialTopicSelect.innerHTML = '<option value="">Select Topic</option>';

        visibleTopics.forEach((topic) => {
            const option = document.createElement('option');

            option.value = String(topic.id);
            option.textContent = topic.module ? `${topic.module} / ${topic.name}` : topic.name;

            materialTopicSelect.appendChild(option);
        });

        if (selectedTopicId && visibleTopics.some((topic) => String(topic.id) === String(selectedTopicId))) {
            materialTopicSelect.value = String(selectedTopicId);
        } else if (visibleTopics.length) {
            materialTopicSelect.value = String(visibleTopics[0].id);
        } else {
            materialTopicSelect.value = '';
        }

        renderSubTopics();
    }

    function getCurrentMaterialTopic() {
        if (!materialTopicSelect) return null;

        return materialTopics.find((item) => String(item.id) === String(materialTopicSelect.value));
    }

    function updateBulkButtons() {
        const topic = getCurrentMaterialTopic();
        const hasSubTopics = !!(topic && (topic.sub_topics || []).length);

        [checkAllSubTopicsBtn, uncheckAllSubTopicsBtn].forEach((btn) => {
            if (btn) {
                btn.disabled = !hasSubTopics;
            }
        });
    }

    function renderSubTopics() {
        if (!materialTopicSelect || !materialSubTopicList) return;

        const topic = getCurrentMaterialTopic();

        if (!topic || !(topic.sub_topics || []).length) {
            materialSubTopicList.innerHTML = `
                <div class="p-4 text-center text-muted bg-white border rounded-3 small">
                    Pilih topic untuk menampilkan sub topic live session.
                </div>
            `;

            updateBulkButtons();
            return;
        }

        materialSubTopicList.innerHTML = '';

        topic.sub_topics.forEach((subTopic) => {
            const id = String(subTopic.id);
            const checked = selectedMaterialIds.has(id) ? 'checked' : '';

            const description = subTopic.description
                ? `<span class="text-muted d-block small mt-1">${escapeHtml(subTopic.description)}</span>`
                : '';

            const label = document.createElement('label');

            label.className = 'd-flex gap-2 align-items-start p-2 rounded-3 border bg-white mb-2';
            label.innerHTML = `
                <input type="checkbox" class="form-check-input scheduled-material-checkbox section-overview" name="sub_topic_ids[]" value="${escapeHtml(id)}" ${checked}>
                <span>
                    <span class="fw-semibold text-dark d-block small">${escapeHtml(subTopic.name)}</span>
                    ${description}
                </span>
            `;

            const checkbox = label.querySelector('input');

            checkbox.addEventListener('change', function () {
                if (checkbox.checked) {
                    selectedMaterialIds.add(id);
                } else {
                    selectedMaterialIds.delete(id);
                }

                syncPrimarySubTopicFromMaterials();
                renderSelectedMaterials();
                refreshProgress();
            });

            materialSubTopicList.appendChild(label);
        });

        updateBulkButtons();
    }

    function setCurrentTopicMaterials(isChecked) {
        const topic = getCurrentMaterialTopic();

        if (!topic || !(topic.sub_topics || []).length) return;

        topic.sub_topics.forEach((subTopic) => {
            const id = String(subTopic.id);

            if (isChecked) {
                selectedMaterialIds.add(id);
            } else {
                selectedMaterialIds.delete(id);
            }
        });

        renderSubTopics();
        syncPrimarySubTopicFromMaterials();
        renderSelectedMaterials();
        refreshProgress();
    }

    function renderSelectedMaterials() {
        if (!selectedMaterialsList || !selectedMaterialCount) return;

        selectedMaterialCount.textContent = String(selectedMaterialIds.size);
        selectedMaterialsList.innerHTML = '';

        if (!selectedMaterialIds.size) {
            selectedMaterialsList.innerHTML = '<span class="text-muted small">Belum ada sub topic yang dipilih.</span>';
            return;
        }

        selectedMaterialIds.forEach((id) => {
            const subTopic = findSubTopicById(id);

            if (!subTopic) return;

            const topicLabel = subTopic.topic_name
                ? `<span class="text-muted small ms-1">· ${escapeHtml(subTopic.topic_name)}</span>`
                : '';

            const pill = document.createElement('span');

            pill.className = 'badge rounded-pill text-bg-light border text-dark d-inline-flex align-items-center gap-1 py-2 px-2';
            pill.innerHTML = `
                <span>${escapeHtml(subTopic.name)}${topicLabel}</span>
                <button type="button" class="btn btn-sm btn-light border rounded-circle p-0 d-inline-flex align-items-center justify-content-center app-pill-remove" aria-label="Remove material" data-remove-material="${escapeHtml(id)}">
                    <i class="bi bi-x"></i>
                </button>
            `;

            pill.querySelector('[data-remove-material]').addEventListener('click', function () {
                selectedMaterialIds.delete(id);

                renderSubTopics();
                syncPrimarySubTopicFromMaterials();
                renderSelectedMaterials();
                refreshProgress();
            });

            selectedMaterialsList.appendChild(pill);
        });
    }

    function syncPrimarySubTopicFromMaterials() {
        if (!primarySubTopicInput) return;

        primarySubTopicInput.value = selectedMaterialIds.size
            ? Array.from(selectedMaterialIds)[0]
            : '';
    }

    function checkOverviewState() {
        const sessionTitle = form.querySelector('[name="session_title"]')?.value;

        return isFilled(sessionTitle) && selectedMaterialIds.size > 0
            ? 'completed'
            : 'incomplete';
    }

    function checkAssignmentState() {
        const instructor = form.querySelector('[name="instructor_id"]')?.value;
        const batch = form.querySelector('[name="batch_id"]')?.value;
        const needsReschedule = isMakeupSession?.checked;
        const rescheduledFrom = form.querySelector('[name="rescheduled_from_id"]')?.value;

        const isComplete = isFilled(instructor)
            && isFilled(batch)
            && (!needsReschedule || isFilled(rescheduledFrom));

        return isComplete ? 'completed' : 'incomplete';
    }

    function checkDeliveryState() {
        const scheduleDate = form.querySelector('[name="schedule_date"]')?.value;
        const startTime = form.querySelector('[name="start_time"]')?.value;
        const endTime = form.querySelector('[name="end_time"]')?.value;
        const deliveryMode = form.querySelector('[name="delivery_mode"]')?.value;

        const isComplete = isFilled(scheduleDate)
            && isFilled(startTime)
            && isFilled(endTime)
            && isFilled(deliveryMode);

        return isComplete ? 'completed' : 'incomplete';
    }

    function checkStatusState() {
        const status = form.querySelector('[name="status"]')?.value;

        return isFilled(status) ? 'completed' : 'incomplete';
    }

    function updateSectionUI(sectionKey, state) {
        const indicator = document.querySelector(`[data-section-indicator="${sectionKey}"]`);
        const badge = document.querySelector(`[data-section-badge="${sectionKey}"]`);

        if (indicator) {
            indicator.classList.remove('completed', 'optional');

            if (state === 'completed') {
                indicator.classList.add('completed');
                indicator.innerHTML = '<i class="bi bi-check-circle-fill"></i>';
            } else {
                indicator.innerHTML = '<i class="bi bi-circle"></i>';
            }
        }

        if (badge) {
            badge.classList.remove('completed', 'optional');

            if (state === 'completed') {
                badge.classList.add('completed');
                badge.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Ready';
            } else {
                badge.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Need Input';
            }
        }
    }

    function refreshProgress() {
        const sections = {
            overview: checkOverviewState(),
            assignment: checkAssignmentState(),
            delivery: checkDeliveryState(),
            status: checkStatusState(),
        };

        const filledCount = Object.values(sections).filter((state) => state === 'completed').length;
        const totalCount = Object.keys(sections).length;
        const percent = totalCount > 0 ? Math.round((filledCount / totalCount) * 100) : 0;

        Object.entries(sections).forEach(([key, state]) => updateSectionUI(key, state));

        if (completedSectionCount) {
            completedSectionCount.textContent = String(filledCount);
        }

        if (totalSectionCount) {
            totalSectionCount.textContent = String(totalCount);
        }

        if (sectionProgressBar) {
            sectionProgressBar.style.width = `${percent}%`;
        }
    }

    function refreshAll() {
        toggleRescheduledField();
        updateLiveStatus();
        refreshProgress();
    }

    async function parseResponse(response) {
        const contentType = response.headers.get('content-type') || '';

        if (contentType.includes('application/json')) {
            return await response.json();
        }

        const text = await response.text();

        return {
            success: false,
            message: text || 'Unexpected server response.',
        };
    }

    async function submitForm(forceDraft = false) {
        clearValidationErrors();

        const formData = new FormData(form);

        formData.delete('sub_topic_ids[]');

        selectedMaterialIds.forEach((id) => {
            formData.append('sub_topic_ids[]', id);
        });

        syncPrimarySubTopicFromMaterials();

        if (primarySubTopicInput) {
            formData.set('sub_topic_id', primarySubTopicInput.value || '');
        }

        if (!isMakeupSession?.checked) {
            formData.set('is_makeup_session', '0');
            formData.delete('rescheduled_from_id');
        }

        if (forceDraft) {
            formData.set('status', 'scheduled');
        }

        const submitButtons = [
            saveDraftBtn,
            saveDraftBtnBottom,
            submitScheduleBtnTop,
            submitScheduleBtnBottom,
        ];

        submitButtons.forEach((btn) => {
            if (btn) {
                btn.disabled = true;
            }
        });

        const activeButtons = forceDraft
            ? [saveDraftBtn, saveDraftBtnBottom]
            : [submitScheduleBtnTop, submitScheduleBtnBottom];

        activeButtons.forEach((btn) => {
            if (btn) {
                btn.dataset.originalHtml = btn.innerHTML;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Saving...';
            }
        });

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': @json(csrf_token()),
                },
                body: formData,
                credentials: 'same-origin',
            });

            const data = await parseResponse(response);

            if (!response.ok || data.success === false) {
                if (response.status === 422 && data.errors) {
                    applyValidationErrors(data.errors);
                    showToast(data.message || 'Ada beberapa input yang perlu diperbaiki.', 'danger');
                    return;
                }

                showToast(data.message || 'Terjadi kesalahan saat menyimpan data.', 'danger');
                return;
            }

            const successMessage = data.message || 'Jadwal instruktur berhasil disimpan.';
            const redirectUrl = data.redirect_url || data.redirect || null;

            showToast(successMessage, 'success');

            if (redirectUrl) {
                rememberToast(successMessage, 'success');

                setTimeout(() => {
                    window.location.href = redirectUrl;
                }, 1200);
            }
        } catch (error) {
            showToast(error.message || 'Terjadi kesalahan saat mengirim data.', 'danger');
        } finally {
            submitButtons.forEach((btn) => {
                if (btn) {
                    btn.disabled = false;
                }
            });

            activeButtons.forEach((btn) => {
                if (btn && btn.dataset.originalHtml) {
                    btn.innerHTML = btn.dataset.originalHtml;
                }
            });
        }
    }

    [saveDraftBtn, saveDraftBtnBottom].forEach((btn) => {
        btn?.addEventListener('click', () => submitForm(true));
    });

    [submitScheduleBtnTop, submitScheduleBtnBottom].forEach((btn) => {
        btn?.addEventListener('click', () => submitForm(false));
    });

    isMakeupSession?.addEventListener('change', refreshAll);
    statusField?.addEventListener('change', updateLiveStatus);

    checkAllSubTopicsBtn?.addEventListener('click', function () {
        setCurrentTopicMaterials(true);
    });

    uncheckAllSubTopicsBtn?.addEventListener('click', function () {
        setCurrentTopicMaterials(false);
    });

    materialTopicSelect?.addEventListener('change', function () {
        renderSubTopics();
        refreshProgress();
    });

    batchSelect?.addEventListener('change', async function () {
        syncProgramFromBatch(true);

        selectedMaterialIds.clear();
        syncPrimarySubTopicFromMaterials();
        renderSelectedMaterials();

        await fetchMaterialTopicsByProgram(programSelect?.value || '', {
            keepSelection: false,
            pruneUnavailable: false,
            showSuccessToast: true,
        });

        refreshAll();
    });

    programSelect?.addEventListener('change', async function () {
        selectedMaterialIds.clear();
        syncPrimarySubTopicFromMaterials();
        renderSelectedMaterials();

        await fetchMaterialTopicsByProgram(programSelect.value || '', {
            keepSelection: false,
            pruneUnavailable: false,
            showSuccessToast: true,
        });

        refreshAll();
    });

    form?.querySelectorAll('input, textarea, select').forEach((el) => {
        el.addEventListener('input', refreshAll);
        el.addEventListener('change', refreshAll);
    });

    syncProgramFromBatch(false);

    if (programSelect?.value) {
        fetchMaterialTopicsByProgram(programSelect.value, {
            keepSelection: true,
            pruneUnavailable: false,
            keepTopicId: findTopicIdBySelectedMaterials(),
            showSuccessToast: false,
        }).then(() => {
            renderSelectedMaterials();
            syncPrimarySubTopicFromMaterials();
            refreshAll();
        });
    } else {
        renderTopicOptions(findTopicIdBySelectedMaterials());
        renderSelectedMaterials();
        syncPrimarySubTopicFromMaterials();
        refreshAll();
    }
});
</script>
@endpush