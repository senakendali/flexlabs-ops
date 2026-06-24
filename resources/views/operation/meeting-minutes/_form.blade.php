@php
    $isEdit = ($submitMode ?? 'create') === 'edit';

    $formAction = $isEdit
        ? route('operation.meeting-minutes.update', $meetingMinute)
        : route('operation.meeting-minutes.store');

    $participants = old('participants', $isEdit ? $meetingMinute->participants->toArray() : []);
    $agendas = old('agendas', $isEdit ? $meetingMinute->agendas->toArray() : []);
    $actionItems = old('action_items', $isEdit ? $meetingMinute->actionItems->toArray() : []);

    if (empty($participants)) {
        $participants = [];
    }

    if (empty($agendas)) {
        $agendas = [];
    }

    if (empty($actionItems)) {
        $actionItems = [];
    }

    $userOptions = $users->map(function ($user) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    })->values();

    $meetingTypeOptions = [
        'internal' => 'Internal',
        'client' => 'Client',
        'vendor' => 'Vendor',
        'academic' => 'Academic',
        'marketing' => 'Marketing',
        'finance' => 'Finance',
        'operation' => 'Operation',
        'other' => 'Other',
    ];

    $departmentOptions = [
        'operation' => 'Operation',
        'academic' => 'Academic',
        'sales' => 'Sales',
        'marketing' => 'Marketing',
        'finance' => 'Finance',
        'management' => 'Management',
        'general_affair' => 'General Affair',
        'other' => 'Other',
    ];

    $statusOptions = [
        'draft' => 'Draft',
        'published' => 'Published',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    $participantRoleOptions = [
        'organizer' => 'Organizer',
        'notulen' => 'Notulen',
        'participant' => 'Participant',
        'guest' => 'Guest',
    ];

    $attendanceOptions = [
        'present' => 'Present',
        'absent' => 'Absent',
        'late' => 'Late',
        'excused' => 'Excused',
    ];

    $priorityOptions = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'urgent' => 'Urgent',
    ];

    $actionStatusOptions = [
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'done' => 'Done',
        'blocked' => 'Blocked',
        'cancelled' => 'Cancelled',
    ];

    $formatDateValue = function ($value) {
        if (blank($value)) {
            return '';
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return '';
        }
    };

    $formatTimeValue = function ($value) {
        if (blank($value)) {
            return '';
        }

        if (is_string($value)) {
            return substr($value, 0, 5);
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('H:i');
        } catch (\Throwable $e) {
            return '';
        }
    };
@endphp

<div class="container-fluid px-4 py-4 meeting-minute-form-page workshops-form-page">

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Operations / Meeting Minutes</div>
                <h1 class="page-title mb-2">
                    {{ $isEdit ? 'Edit MOM' : 'Create MOM' }}
                </h1>
                <p class="page-subtitle mb-0">
                    Lengkapi informasi meeting, peserta, agenda, dan action item agar follow-up operasional lebih rapi.
                </p>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('operation.meeting-minutes.index') }}" class="btn btn-light border btn-modern">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>

                @if($isEdit)
                    <a href="{{ route('operation.meeting-minutes.show', $meetingMinute) }}" class="btn btn-light btn-modern border">
                        <i class="bi bi-eye me-1"></i> View
                    </a>
                @endif

                <button type="button" id="saveDraftBtn" class="btn btn-light btn-modern">
                    <i class="bi bi-save me-1"></i> Save Draft
                </button>

                <button type="button" id="submitMomBtn" class="btn btn-primary">
                    <i class="bi bi-check-circle me-1"></i> {{ $isEdit ? 'Update MOM' : 'Create MOM' }}
                </button>
            </div>
        </div>
    </div>

    <div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1090;"></div>

    <div class="content-card mb-4">
        <div class="content-card-body">
            <div class="row g-3 align-items-stretch report-summary-row">
                <div class="col-lg-8 d-flex">
                    <div class="progress-summary-wrap w-100">
                        <div class="progress-summary-top d-flex justify-content-between align-items-center gap-3 mb-2">
                            <div>
                                <div class="progress-summary-label">MOM Section Progress</div>
                                <div class="progress-summary-subtitle">
                                    Pantau kesiapan komponen MOM. Action item optional, tapi sangat direkomendasikan.
                                </div>
                            </div>

                            <div class="progress-summary-count">
                                <span id="completedSectionCount">0</span> dari <span id="totalSectionCount">5</span> section sudah siap
                            </div>
                        </div>

                        <div class="progress progress-modern" role="progressbar" aria-label="MOM completion progress">
                            <div id="sectionProgressBar" class="progress-bar" style="width: 0%"></div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 d-flex">
                    <div class="status-pill-card w-100">
                        <div class="status-pill-label">MOM Status</div>
                        <div class="status-pill-value" id="liveStatusText">
                            {{ ucfirst(old('status', $meetingMinute->status ?? 'draft')) }}
                        </div>
                        <div class="status-pill-help">
                            Draft untuk catatan awal. Completed untuk MOM yang sudah final dan siap dibaca management.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form id="meetingMinuteForm" action="{{ $formAction }}" method="POST" data-submit-mode="{{ $submitMode ?? 'create' }}">
        @csrf

        @if($isEdit)
            @method('PUT')
        @endif

        <div class="content-card section-card voice-assistant-card mb-4" id="meetingVoiceAssistantCard">
            <div class="content-card-header section-card-header align-items-start">
                <div>
                    <div class="voice-eyebrow">
                        <i class="bi bi-mic-fill me-1"></i> Meeting Voice Assistant
                    </div>
                    <h5 class="content-card-title mb-1">Ambil Suara Meeting</h5>
                    <p class="content-card-subtitle mb-0">
                        Klik start saat meeting berjalan. Setelah selesai, kirim transcript ke AI untuk dibuat Summary &amp; Notes yang lebih rapi.
                    </p>
                </div>

                <div class="voice-status-pill" id="voiceStatusPill">
                    <span class="voice-status-dot"></span>
                    <span id="voiceStatusText">Ready</span>
                </div>
            </div>

            <div class="content-card-body">
                <div class="voice-control-panel">
                    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
                        <div>
                            <div class="voice-panel-title">Live Transcript</div>
                            <div class="voice-panel-subtitle">
                                Support terbaik di Chrome/Edge. Browser akan minta izin microphone saat tombol start ditekan.
                            </div>
                        </div>

                        <div class="d-flex gap-2 flex-wrap">
                            <button type="button" class="btn btn-primary btn-modern" id="startVoiceBtn">
                                <i class="bi bi-mic-fill me-1"></i> Start Listening
                            </button>
                            <button type="button" class="btn btn-light border btn-modern" id="stopVoiceBtn" disabled>
                                <i class="bi bi-stop-circle me-1"></i> Stop
                            </button>
                        </div>
                    </div>

                    <textarea id="voiceTranscriptField" class="form-control voice-transcript-field" rows="8" placeholder="Transcript meeting akan muncul di sini..."></textarea>

                    <div class="voice-interim-box mt-3" id="voiceInterimBox">
                        <span class="voice-interim-label">Live capture:</span>
                        <span id="voiceInterimText">Belum ada suara yang tertangkap.</span>
                    </div>

                    <div class="voice-bottom-row mt-3">
                        <div class="voice-helper-note mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            Untuk hasil AI lebih rapi, ulangi poin penting seperti: keputusan, PIC, deadline, dan next action.
                        </div>

                        <div class="voice-bottom-actions">
                            <button type="button" class="btn btn-light border btn-modern" id="clearVoiceTranscriptBtn">
                                <i class="bi bi-eraser me-1"></i> Clear Transcript
                            </button>
                            <button type="button" class="btn btn-success btn-modern" id="applyVoiceToMomBtn">
                                <i class="bi bi-stars me-1"></i> Generate AI Summary &amp; Notes
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-xl-3">
                <div class="section-nav-card sticky-top" style="top: 92px;">
                    <div class="section-nav-header">
                        <div class="section-nav-title">Form Sections</div>
                        <div class="section-nav-subtitle">Lengkapi bagian utama MOM.</div>
                    </div>

                    <div class="nav flex-column nav-pills custom-section-nav" id="mom-section-tab" role="tablist" aria-orientation="vertical">
                        <button class="nav-link active" id="tab-overview-btn" data-bs-toggle="pill" data-bs-target="#tab-overview" type="button" role="tab">
                            <span class="nav-link-content">
                                <span class="nav-link-icon"><i class="bi bi-grid-1x2-fill"></i></span>
                                <span class="nav-link-text">
                                    <span class="nav-link-title">Overview</span>
                                    <span class="nav-link-subtitle">Informasi utama meeting</span>
                                </span>
                            </span>
                            <span class="section-check" data-section-indicator="overview"><i class="bi bi-circle"></i></span>
                        </button>

                        <button class="nav-link" id="tab-participants-btn" data-bs-toggle="pill" data-bs-target="#tab-participants" type="button" role="tab">
                            <span class="nav-link-content">
                                <span class="nav-link-icon"><i class="bi bi-people-fill"></i></span>
                                <span class="nav-link-text">
                                    <span class="nav-link-title">Participants</span>
                                    <span class="nav-link-subtitle">Peserta meeting</span>
                                </span>
                            </span>
                            <span class="section-check" data-section-indicator="participants"><i class="bi bi-circle"></i></span>
                        </button>

                        <button class="nav-link" id="tab-agenda-btn" data-bs-toggle="pill" data-bs-target="#tab-agenda" type="button" role="tab">
                            <span class="nav-link-content">
                                <span class="nav-link-icon"><i class="bi bi-list-ul"></i></span>
                                <span class="nav-link-text">
                                    <span class="nav-link-title">Agenda</span>
                                    <span class="nav-link-subtitle">Topik pembahasan</span>
                                </span>
                            </span>
                            <span class="section-check" data-section-indicator="agenda"><i class="bi bi-circle"></i></span>
                        </button>

                        <button class="nav-link" id="tab-action-btn" data-bs-toggle="pill" data-bs-target="#tab-action" type="button" role="tab">
                            <span class="nav-link-content">
                                <span class="nav-link-icon"><i class="bi bi-list-task"></i></span>
                                <span class="nav-link-text">
                                    <span class="nav-link-title">Action Items</span>
                                    <span class="nav-link-subtitle">PIC dan deadline</span>
                                </span>
                            </span>
                            <span class="section-check" data-section-indicator="action"><i class="bi bi-circle"></i></span>
                        </button>

                        <button class="nav-link" id="tab-summary-btn" data-bs-toggle="pill" data-bs-target="#tab-summary" type="button" role="tab">
                            <span class="nav-link-content">
                                <span class="nav-link-icon"><i class="bi bi-journal-text"></i></span>
                                <span class="nav-link-text">
                                    <span class="nav-link-title">Summary & Notes</span>
                                    <span class="nav-link-subtitle">Ringkasan hasil meeting</span>
                                </span>
                            </span>
                            <span class="section-check" data-section-indicator="summary"><i class="bi bi-circle"></i></span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-xl-9">
                <div class="tab-content" id="mom-section-tabContent">
                    <div class="tab-pane fade show active" id="tab-overview" role="tabpanel" aria-labelledby="tab-overview-btn">
                        <div class="content-card section-card mb-4">
                            <div class="content-card-header section-card-header">
                                <div>
                                    <h5 class="content-card-title mb-1">Overview</h5>
                                    <p class="content-card-subtitle mb-0">
                                        Isi informasi utama meeting sebagai dasar dokumen MOM.
                                    </p>
                                </div>

                                <div class="section-status-badge" data-section-badge="overview">
                                    <i class="bi bi-hourglass-split me-1"></i> Need Input
                                </div>
                            </div>

                            <div class="content-card-body">
                                <div class="row g-3">
                                    <div class="col-lg-8">
                                        <label class="form-label">Meeting Title <span class="text-danger">*</span></label>
                                        <input type="text" name="title" class="form-control section-overview" value="{{ old('title', $meetingMinute->title) }}" placeholder="Contoh: Weekly Operation Meeting">
                                        <div class="invalid-feedback error-text" data-error-for="title"></div>
                                    </div>

                                    <div class="col-lg-4">
                                        <label class="form-label">MOM No</label>
                                        <input type="text" class="form-control bg-light" value="{{ $meetingMinute->meeting_no ?? 'Akan digenerate otomatis' }}" readonly>
                                    </div>

                                    <div class="col-lg-4">
                                        <label class="form-label">Meeting Type</label>
                                        <select name="meeting_type" class="form-select section-overview">
                                            @foreach($meetingTypeOptions as $value => $label)
                                                <option value="{{ $value }}" @selected(old('meeting_type', $meetingMinute->meeting_type ?? 'internal') === $value)>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback error-text" data-error-for="meeting_type"></div>
                                    </div>

                                    <div class="col-lg-4">
                                        <label class="form-label">Department</label>
                                        <select name="department" class="form-select section-overview">
                                            <option value="">Select Department</option>
                                            @foreach($departmentOptions as $value => $label)
                                                <option value="{{ $value }}" @selected(old('department', $meetingMinute->department ?? 'operation') === $value)>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback error-text" data-error-for="department"></div>
                                    </div>

                                    <div class="col-lg-4">
                                        <label class="form-label">Status</label>
                                        <select name="status" id="statusField" class="form-select section-overview">
                                            @foreach($statusOptions as $value => $label)
                                                <option value="{{ $value }}" @selected(old('status', $meetingMinute->status ?? 'draft') === $value)>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback error-text" data-error-for="status"></div>
                                    </div>

                                    <div class="col-lg-4">
                                        <label class="form-label">Meeting Date <span class="text-danger">*</span></label>
                                        <input type="date" name="meeting_date" class="form-control section-overview" value="{{ old('meeting_date', $formatDateValue($meetingMinute->meeting_date ?? now())) }}">
                                        <div class="invalid-feedback error-text" data-error-for="meeting_date"></div>
                                    </div>

                                    <div class="col-lg-4">
                                        <label class="form-label">Start Time</label>
                                        <input type="time" name="start_time" class="form-control" value="{{ old('start_time', $formatTimeValue($meetingMinute->start_time ?? null)) }}">
                                        <div class="invalid-feedback error-text" data-error-for="start_time"></div>
                                    </div>

                                    <div class="col-lg-4">
                                        <label class="form-label">End Time</label>
                                        <input type="time" name="end_time" class="form-control" value="{{ old('end_time', $formatTimeValue($meetingMinute->end_time ?? null)) }}">
                                        <div class="invalid-feedback error-text" data-error-for="end_time"></div>
                                    </div>

                                    <div class="col-lg-6">
                                        <label class="form-label">Location</label>
                                        <input type="text" name="location" class="form-control" value="{{ old('location', $meetingMinute->location) }}" placeholder="Meeting room / office">
                                        <div class="invalid-feedback error-text" data-error-for="location"></div>
                                    </div>

                                    <div class="col-lg-6">
                                        <label class="form-label">Platform</label>
                                        <input type="text" name="platform" class="form-control" value="{{ old('platform', $meetingMinute->platform) }}" placeholder="Zoom / Google Meet / Offline">
                                        <div class="invalid-feedback error-text" data-error-for="platform"></div>
                                    </div>

                                    <div class="col-lg-6">
                                        <label class="form-label">Related Project / Module</label>
                                        <input type="text" name="related_project" class="form-control" value="{{ old('related_project', $meetingMinute->related_project) }}" placeholder="Contoh: FlexOps">
                                        <div class="invalid-feedback error-text" data-error-for="related_project"></div>
                                    </div>

                                    <div class="col-lg-6">
                                        <label class="form-label">Organizer</label>
                                        <select name="organizer_id" class="form-select">
                                            <option value="">Select Organizer</option>
                                            @foreach($users as $user)
                                                <option value="{{ $user->id }}" @selected((string) old('organizer_id', $meetingMinute->organizer_id) === (string) $user->id)>
                                                    {{ $user->name }} — {{ $user->email }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback error-text" data-error-for="organizer_id"></div>
                                    </div>

                                    <div class="col-lg-4">
                                        <label class="form-label">Active</label>
                                        <div class="form-switch-card">
                                            <div>
                                                <div class="form-switch-title">MOM Active</div>
                                                <div class="form-switch-subtitle">Aktifkan agar tetap muncul di daftar utama.</div>
                                            </div>
                                            <div class="form-check form-switch m-0">
                                                <input type="hidden" name="is_active" value="0">
                                                <input class="form-check-input" type="checkbox" role="switch" name="is_active" value="1" @checked(old('is_active', $meetingMinute->is_active ?? true))>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-participants" role="tabpanel" aria-labelledby="tab-participants-btn">
                        <div class="content-card section-card mb-4">
                            <div class="content-card-header section-card-header">
                                <div>
                                    <h5 class="content-card-title mb-1">Participants</h5>
                                    <p class="content-card-subtitle mb-0">
                                        Tambahkan peserta meeting. Internal user bisa dipilih dari data user, external bisa diinput manual.
                                    </p>
                                </div>

                                <div class="d-flex gap-2 align-items-center">
                                    <div class="section-status-badge" data-section-badge="participants">
                                        <i class="bi bi-dash-circle me-1"></i> Optional
                                    </div>

                                    <button type="button" class="btn btn-primary btn-sm" id="addParticipantBtn">
                                        <i class="bi bi-plus-circle me-1"></i> Add Participant
                                    </button>
                                </div>
                            </div>

                            <div class="content-card-body">
                                <div id="participantsList" class="dynamic-section-list"></div>

                                <div id="participantsEmptyState" class="empty-state-box">
                                    <div class="empty-state-icon"><i class="bi bi-people"></i></div>
                                    <div class="empty-state-title">Belum ada participant</div>
                                    <div class="empty-state-subtitle">Tambahkan minimal peserta utama agar MOM lebih jelas.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-agenda" role="tabpanel" aria-labelledby="tab-agenda-btn">
                        <div class="content-card section-card mb-4">
                            <div class="content-card-header section-card-header">
                                <div>
                                    <h5 class="content-card-title mb-1">Agenda</h5>
                                    <p class="content-card-subtitle mb-0">
                                        Susun daftar topik yang dibahas dalam meeting.
                                    </p>
                                </div>

                                <div class="d-flex gap-2 align-items-center">
                                    <div class="section-status-badge" data-section-badge="agenda">
                                        <i class="bi bi-dash-circle me-1"></i> Optional
                                    </div>

                                    <button type="button" class="btn btn-primary btn-sm" id="addAgendaBtn">
                                        <i class="bi bi-plus-circle me-1"></i> Add Agenda
                                    </button>
                                </div>
                            </div>

                            <div class="content-card-body">
                                <div id="agendaList" class="dynamic-section-list"></div>

                                <div id="agendaEmptyState" class="empty-state-box">
                                    <div class="empty-state-icon"><i class="bi bi-list-ul"></i></div>
                                    <div class="empty-state-title">Belum ada agenda</div>
                                    <div class="empty-state-subtitle">Tambahkan agenda untuk membuat MOM lebih terstruktur.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-action" role="tabpanel" aria-labelledby="tab-action-btn">
                        <div class="content-card section-card mb-4">
                            <div class="content-card-header section-card-header">
                                <div>
                                    <h5 class="content-card-title mb-1">Action Items</h5>
                                    <p class="content-card-subtitle mb-0">
                                        Catat follow-up task, PIC, deadline, priority, dan status.
                                    </p>
                                </div>

                                <div class="d-flex gap-2 align-items-center">
                                    <div class="section-status-badge" data-section-badge="action">
                                        <i class="bi bi-dash-circle me-1"></i> Optional
                                    </div>

                                    <button type="button" class="btn btn-primary btn-sm" id="addActionItemBtn">
                                        <i class="bi bi-plus-circle me-1"></i> Add Action
                                    </button>
                                </div>
                            </div>

                            <div class="content-card-body">
                                <div id="actionItemList" class="dynamic-section-list"></div>

                                <div id="actionItemEmptyState" class="empty-state-box">
                                    <div class="empty-state-icon"><i class="bi bi-list-task"></i></div>
                                    <div class="empty-state-title">Belum ada action item</div>
                                    <div class="empty-state-subtitle">Action item membuat MOM lebih berguna untuk monitoring follow-up.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-summary" role="tabpanel" aria-labelledby="tab-summary-btn">
                        <div class="content-card section-card mb-4">
                            <div class="content-card-header section-card-header">
                                <div>
                                    <h5 class="content-card-title mb-1">Summary & Notes</h5>
                                    <p class="content-card-subtitle mb-0">
                                        Tulis ringkasan hasil meeting dan catatan tambahan.
                                    </p>
                                </div>

                                <div class="section-status-badge" data-section-badge="summary">
                                    <i class="bi bi-hourglass-split me-1"></i> Need Input
                                </div>
                            </div>

                            <div class="content-card-body">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label">Summary</label>
                                        <textarea name="summary" rows="5" class="form-control section-summary" placeholder="Ringkasan hasil meeting">{{ old('summary', $meetingMinute->summary) }}</textarea>
                                        <div class="invalid-feedback error-text" data-error-for="summary"></div>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">Notes</label>
                                        <textarea name="notes" rows="5" class="form-control section-summary" placeholder="Catatan tambahan jika diperlukan">{{ old('notes', $meetingMinute->notes) }}</textarea>
                                        <div class="invalid-feedback error-text" data-error-for="notes"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="content-card">
                    <div class="content-card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div class="footer-note">
                            <div class="footer-note-title">Final Check</div>
                            <div class="footer-note-subtitle">
                                Pastikan informasi utama, summary, dan action item penting sudah terisi.
                            </div>
                        </div>

                        <div class="d-flex gap-2 flex-wrap">
                            <a href="{{ route('operation.meeting-minutes.index') }}" class="btn btn-light border">Cancel</a>

                            <button type="button" id="saveDraftBtnBottom" class="btn btn-save-draft">
                                <i class="bi bi-save me-1"></i> Save Draft
                            </button>

                            <button type="button" id="submitMomBtnBottom" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i> {{ $isEdit ? 'Update MOM' : 'Create MOM' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<template id="participantRowTemplate">
    <div class="dynamic-item-card participant-item-card" data-type="participants">
        <div class="dynamic-item-header">
            <div>
                <div class="dynamic-item-title">Participant Item</div>
                <div class="dynamic-item-subtitle">Pilih user internal atau isi peserta external secara manual.</div>
            </div>

            <button type="button" class="btn btn-sm btn-light border remove-item-btn">
                <i class="bi bi-trash3"></i>
            </button>
        </div>

        <div class="dynamic-item-body">
            <input type="hidden" class="field-id" data-field="id">

            <div class="row g-3">
                <div class="col-lg-6">
                    <label class="form-label">Internal User</label>
                    <select class="form-select field-input participant-watch" data-field="user_id">
                        <option value="">External / Manual Input</option>
                    </select>
                </div>

                <div class="col-lg-3">
                    <label class="form-label">External Name</label>
                    <input type="text" class="form-control field-input" data-field="name" placeholder="Nama external">
                </div>

                <div class="col-lg-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control field-input" data-field="email" placeholder="email@example.com">
                </div>

                <div class="col-lg-4">
                    <label class="form-label">Role</label>
                    <select class="form-select field-input participant-watch" data-field="role">
                        @foreach($participantRoleOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-4">
                    <label class="form-label">Attendance</label>
                    <select class="form-select field-input participant-watch" data-field="attendance_status">
                        @foreach($attendanceOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-4">
                    <label class="form-label">Notes</label>
                    <input type="text" class="form-control field-input" data-field="notes" placeholder="Catatan kehadiran">
                </div>
            </div>
        </div>
    </div>
</template>

<template id="agendaRowTemplate">
    <div class="dynamic-item-card agenda-item-card" data-type="agendas">
        <div class="dynamic-item-header">
            <div>
                <div class="dynamic-item-title">Agenda Item</div>
                <div class="dynamic-item-subtitle">Isi topik pembahasan meeting.</div>
            </div>

            <button type="button" class="btn btn-sm btn-light border remove-item-btn">
                <i class="bi bi-trash3"></i>
            </button>
        </div>

        <div class="dynamic-item-body">
            <input type="hidden" class="field-id" data-field="id">

            <div class="row g-3">
                <div class="col-lg-8">
                    <label class="form-label">Topic</label>
                    <input type="text" class="form-control field-input agenda-watch" data-field="topic" placeholder="Contoh: Review progress FlexOps">
                </div>

                <div class="col-lg-4">
                    <label class="form-label">Sort Order</label>
                    <input type="number" min="0" class="form-control field-input" data-field="sort_order" value="0">
                </div>

                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea class="form-control field-input" rows="3" data-field="description" placeholder="Detail pembahasan agenda"></textarea>
                </div>
            </div>
        </div>
    </div>
</template>

<template id="actionItemRowTemplate">
    <div class="dynamic-item-card action-item-card" data-type="action_items">
        <div class="dynamic-item-header">
            <div>
                <div class="dynamic-item-title">Action Item</div>
                <div class="dynamic-item-subtitle">Isi task, PIC, deadline, priority, dan status follow-up.</div>
            </div>

            <button type="button" class="btn btn-sm btn-light border remove-item-btn">
                <i class="bi bi-trash3"></i>
            </button>
        </div>

        <div class="dynamic-item-body">
            <input type="hidden" class="field-id" data-field="id">

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Task Title</label>
                    <input type="text" class="form-control field-input action-watch" data-field="title" placeholder="Contoh: Buat halaman show MOM">
                </div>

                <div class="col-lg-6">
                    <label class="form-label">PIC User</label>
                    <select class="form-select field-input" data-field="pic_user_id">
                        <option value="">Manual / External PIC</option>
                    </select>
                </div>

                <div class="col-lg-6">
                    <label class="form-label">PIC Name</label>
                    <input type="text" class="form-control field-input" data-field="pic_name" placeholder="Isi jika PIC external/manual">
                </div>

                <div class="col-lg-4">
                    <label class="form-label">Priority</label>
                    <select class="form-select field-input" data-field="priority">
                        @foreach($priorityOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-4">
                    <label class="form-label">Due Date</label>
                    <input type="date" class="form-control field-input" data-field="due_date">
                </div>

                <div class="col-lg-4">
                    <label class="form-label">Status</label>
                    <select class="form-select field-input" data-field="status">
                        @foreach($actionStatusOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea class="form-control field-input" rows="3" data-field="description" placeholder="Detail pekerjaan"></textarea>
                </div>

                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea class="form-control field-input" rows="2" data-field="notes" placeholder="Catatan follow-up"></textarea>
                </div>
            </div>
        </div>
    </div>
</template>

@push('styles')
<style>

    .voice-assistant-card {
        overflow: hidden;
        border: 1px solid rgba(91, 62, 142, .18);
        background: linear-gradient(135deg, rgba(91, 62, 142, .08), rgba(255, 190, 4, .08) 55%, #ffffff 100%);
    }

    .voice-eyebrow {
        display: inline-flex;
        align-items: center;
        padding: .35rem .65rem;
        border-radius: 999px;
        background: rgba(91, 62, 142, .12);
        color: #5B3E8E;
        font-size: .76rem;
        font-weight: 800;
        letter-spacing: .04em;
        text-transform: uppercase;
        margin-bottom: .55rem;
    }

    .voice-status-pill {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .55rem .85rem;
        border-radius: 999px;
        background: #ffffff;
        border: 1px solid rgba(15, 23, 42, .08);
        color: #64748b;
        font-size: .82rem;
        font-weight: 800;
        box-shadow: 0 10px 24px rgba(15, 23, 42, .06);
    }

    .voice-status-pill.is-listening {
        color: #b42318;
        border-color: rgba(180, 35, 24, .22);
        background: #fff7f6;
    }

    .voice-status-pill.is-ready {
        color: #3B8E4D;
        border-color: rgba(59, 142, 77, .22);
        background: #f5fff7;
    }

    .voice-status-pill.is-warning {
        color: #946200;
        border-color: rgba(255, 190, 4, .35);
        background: #fffbeb;
    }

    .voice-status-dot {
        width: .65rem;
        height: .65rem;
        border-radius: 999px;
        background: currentColor;
        box-shadow: 0 0 0 4px rgba(100, 116, 139, .12);
    }

    .voice-status-pill.is-listening .voice-status-dot {
        animation: voicePulse 1.15s infinite;
        box-shadow: 0 0 0 4px rgba(180, 35, 24, .14);
    }

    @keyframes voicePulse {
        0% { transform: scale(1); opacity: 1; }
        70% { transform: scale(1.25); opacity: .55; }
        100% { transform: scale(1); opacity: 1; }
    }

    .voice-control-panel {
        border-radius: 22px;
        background: rgba(255, 255, 255, .88);
        border: 1px solid rgba(15, 23, 42, .08);
        padding: 1rem;
        box-shadow: 0 14px 34px rgba(15, 23, 42, .06);
    }

    .voice-panel-title {
        color: #0f172a;
        font-weight: 900;
        font-size: .98rem;
    }

    .voice-panel-subtitle,
    .voice-helper-note {
        color: #64748b;
        font-size: .84rem;
        line-height: 1.55;
    }

    .voice-transcript-field {
        min-height: 210px;
        resize: vertical;
        border-radius: 18px;
        border-color: rgba(91, 62, 142, .22);
        background: #fff;
        line-height: 1.65;
    }

    .voice-transcript-field:focus {
        border-color: rgba(91, 62, 142, .55);
        box-shadow: 0 0 0 .2rem rgba(91, 62, 142, .12);
    }

    .voice-interim-box {
        min-height: 45px;
        padding: .75rem .9rem;
        border-radius: 16px;
        background: #f8fafc;
        border: 1px dashed rgba(100, 116, 139, .35);
        color: #334155;
        font-size: .86rem;
        line-height: 1.55;
    }

    .voice-interim-label {
        color: #5B3E8E;
        font-weight: 900;
        margin-right: .3rem;
    }


    .voice-bottom-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .voice-bottom-actions {
        display: flex;
        justify-content: flex-end;
        gap: .5rem;
        flex-wrap: wrap;
        margin-left: auto;
    }

    @media (max-width: 767.98px) {
        .voice-bottom-row {
            align-items: stretch;
        }

        .voice-helper-note,
        .voice-bottom-actions,
        .voice-bottom-actions .btn {
            width: 100%;
        }
    }

    .voice-helper-note {
        padding: .75rem .85rem;
        border-radius: 16px;
        background: rgba(255, 190, 4, .13);
        border: 1px solid rgba(255, 190, 4, .25);
        color: #7a5300;
    }

</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('meetingMinuteForm');

    const participantsList = document.getElementById('participantsList');
    const agendaList = document.getElementById('agendaList');
    const actionItemList = document.getElementById('actionItemList');

    const participantsEmptyState = document.getElementById('participantsEmptyState');
    const agendaEmptyState = document.getElementById('agendaEmptyState');
    const actionItemEmptyState = document.getElementById('actionItemEmptyState');

    const addParticipantBtn = document.getElementById('addParticipantBtn');
    const addAgendaBtn = document.getElementById('addAgendaBtn');
    const addActionItemBtn = document.getElementById('addActionItemBtn');

    const saveDraftBtn = document.getElementById('saveDraftBtn');
    const saveDraftBtnBottom = document.getElementById('saveDraftBtnBottom');
    const submitMomBtn = document.getElementById('submitMomBtn');
    const submitMomBtnBottom = document.getElementById('submitMomBtnBottom');

    const statusField = document.getElementById('statusField');
    const liveStatusText = document.getElementById('liveStatusText');
    const completedSectionCount = document.getElementById('completedSectionCount');
    const totalSectionCount = document.getElementById('totalSectionCount');
    const sectionProgressBar = document.getElementById('sectionProgressBar');
    const toastContainer = document.getElementById('toastContainer');

    const voiceStatusPill = document.getElementById('voiceStatusPill');
    const voiceStatusText = document.getElementById('voiceStatusText');
    const startVoiceBtn = document.getElementById('startVoiceBtn');
    const stopVoiceBtn = document.getElementById('stopVoiceBtn');
    const applyVoiceToMomBtn = document.getElementById('applyVoiceToMomBtn');
    const clearVoiceTranscriptBtn = document.getElementById('clearVoiceTranscriptBtn');
    const voiceTranscriptField = document.getElementById('voiceTranscriptField');
    const voiceInterimText = document.getElementById('voiceInterimText');

    const csrfToken = @json(csrf_token());
    const aiSummaryUrl = @json(route('operation.meeting-minutes.ai-summary'));
    const userOptions = @json($userOptions);
    const participantRows = @json($participants);
    const agendaRows = @json($agendas);
    const actionRows = @json($actionItems);

    let participantIndex = 0;
    let agendaIndex = 0;
    let actionIndex = 0;
    let redirectTimeout = null;

    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    let recognition = null;
    let isListening = false;
    let shouldKeepListening = false;
    let finalTranscriptBuffer = '';

    totalSectionCount.textContent = '5';


    function setVoiceStatus(status, text) {
        if (!voiceStatusPill || !voiceStatusText) return;

        voiceStatusPill.classList.remove('is-listening', 'is-ready', 'is-warning');

        if (status) {
            voiceStatusPill.classList.add(`is-${status}`);
        }

        voiceStatusText.textContent = text || 'Ready';
    }

    function setVoiceButtons(listening) {
        if (startVoiceBtn) startVoiceBtn.disabled = listening;
        if (stopVoiceBtn) stopVoiceBtn.disabled = !listening;
    }

    function appendTranscript(text) {
        if (!voiceTranscriptField || !isFilled(text)) return;

        const current = voiceTranscriptField.value.trim();
        voiceTranscriptField.value = current ? `${current} ${text.trim()}` : text.trim();
        voiceTranscriptField.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function splitTranscriptToChunks(transcript) {
        const cleanTranscript = String(transcript || '')
            .replace(/\s+/g, ' ')
            .trim();

        if (!cleanTranscript) return [];

        const sentenceChunks = cleanTranscript
            .split(/(?<=[.!?])\s+|\n+/)
            .map(function (item) { return item.trim(); })
            .filter(Boolean);

        if (sentenceChunks.length > 1) {
            return sentenceChunks;
        }

        const words = cleanTranscript.split(' ');
        const chunks = [];
        let buffer = [];

        words.forEach(function (word) {
            buffer.push(word);

            if (buffer.join(' ').length >= 170) {
                chunks.push(buffer.join(' '));
                buffer = [];
            }
        });

        if (buffer.length > 0) {
            chunks.push(buffer.join(' '));
        }

        return chunks;
    }

    function uniqueLimited(items, limit = 5) {
        const seen = new Set();
        const results = [];

        items.forEach(function (item) {
            const normalized = item.toLowerCase().replace(/[^a-z0-9\s]/gi, '').trim();
            if (!normalized || seen.has(normalized)) return;

            seen.add(normalized);
            results.push(item);
        });

        return results.slice(0, limit);
    }

    function findImportantChunks(chunks, keywords, limit = 5) {
        return uniqueLimited(chunks.filter(function (chunk) {
            const lower = chunk.toLowerCase();
            return keywords.some(function (keyword) {
                return lower.includes(keyword);
            });
        }), limit);
    }

    function buildMomContentFromTranscript(transcript) {
        const chunks = splitTranscriptToChunks(transcript);
        const summaryChunks = uniqueLimited(chunks, 5);

        const decisionChunks = findImportantChunks(chunks, [
            'sepakat', 'disepakati', 'setuju', 'keputusan', 'diputuskan', 'jadi', 'fix', 'final', 'confirmed', 'approve', 'approved'
        ], 5);

        const actionChunks = findImportantChunks(chunks, [
            'action', 'follow up', 'follow-up', 'todo', 'to do', 'pic', 'deadline', 'due date', 'lanjut', 'cek', 'update', 'buat', 'bikin', 'kirim', 'submit', 'perbaiki', 'revisi', 'siapkan', 'prepare', 'kerjakan', 'handle'
        ], 7);

        const issueChunks = findImportantChunks(chunks, [
            'kendala', 'masalah', 'blocking', 'blocked', 'error', 'belum jalan', 'belum selesai', 'kurang', 'issue'
        ], 5);

        const summaryLines = [];
        summaryLines.push('Ringkasan Meeting');

        if (summaryChunks.length > 0) {
            summaryChunks.forEach(function (chunk) {
                summaryLines.push(`- ${chunk}`);
            });
        } else {
            summaryLines.push('- Belum ada transcript yang cukup untuk diringkas.');
        }

        if (decisionChunks.length > 0) {
            summaryLines.push('', 'Keputusan Utama');
            decisionChunks.forEach(function (chunk) {
                summaryLines.push(`- ${chunk}`);
            });
        }

        if (actionChunks.length > 0) {
            summaryLines.push('', 'Action / Follow-up');
            actionChunks.forEach(function (chunk) {
                summaryLines.push(`- ${chunk}`);
            });
        }

        const notesLines = [];
        notesLines.push('Voice Transcript');
        notesLines.push(transcript.trim());

        if (issueChunks.length > 0) {
            notesLines.push('', 'Potential Issues / Notes');
            issueChunks.forEach(function (chunk) {
                notesLines.push(`- ${chunk}`);
            });
        }

        return {
            summary: summaryLines.join('\n'),
            notes: notesLines.join('\n')
        };
    }

    function appendToTextarea(textarea, content, separatorTitle = '') {
        if (!textarea || !isFilled(content)) return;

        const current = textarea.value.trim();
        const separator = separatorTitle ? `\n\n--- ${separatorTitle} ---\n` : '\n\n';

        textarea.value = current ? `${current}${separator}${content.trim()}` : content.trim();
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
        textarea.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function moveToSummaryTab() {
        const summaryTabTrigger = document.getElementById('tab-summary-btn');

        if (summaryTabTrigger && typeof bootstrap !== 'undefined') {
            bootstrap.Tab.getOrCreateInstance(summaryTabTrigger).show();
        }
    }

    function normalizeAiText(value) {
        if (value === null || value === undefined) return '';

        if (Array.isArray(value)) {
            return value
                .map(function (item) {
                    return normalizeAiText(item);
                })
                .filter(Boolean)
                .map(function (item) {
                    return item.startsWith('-') ? item : `- ${item}`;
                })
                .join('
');
        }

        if (typeof value === 'object') {
            return Object.entries(value)
                .map(function ([key, item]) {
                    const normalizedItem = normalizeAiText(item);
                    return normalizedItem ? `${key}: ${normalizedItem}` : '';
                })
                .filter(Boolean)
                .join('
');
        }

        return String(value).trim();
    }

    function getAiPayload(data) {
        if (data && typeof data === 'object' && data.data && typeof data.data === 'object') {
            return data.data;
        }

        return data || {};
    }

    function buildAiNotesPayload(data, transcript) {
        const notesBlocks = [];
        const notes = normalizeAiText(data.notes);
        const decisions = normalizeAiText(data.decisions);
        const actionItems = normalizeAiText(data.action_items || data.actionItems);
        const rawTranscript = normalizeAiText(transcript);

        if (isFilled(notes)) {
            notesBlocks.push(notes);
        }

        if (isFilled(decisions)) {
            notesBlocks.push(`Decisions:
${decisions}`);
        }

        if (isFilled(actionItems)) {
            notesBlocks.push(`Action Items:
${actionItems}`);
        }

        if (isFilled(rawTranscript)) {
            notesBlocks.push(`Raw Voice Transcript:
${rawTranscript}`);
        }

        return notesBlocks.join('

');
    }

    function setAiButtonLoading(isLoading) {
        if (!applyVoiceToMomBtn) return;

        if (isLoading) {
            applyVoiceToMomBtn.disabled = true;
            applyVoiceToMomBtn.dataset.originalHtml = applyVoiceToMomBtn.innerHTML;
            applyVoiceToMomBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Generating AI Summary...';
            return;
        }

        applyVoiceToMomBtn.disabled = false;

        if (applyVoiceToMomBtn.dataset.originalHtml) {
            applyVoiceToMomBtn.innerHTML = applyVoiceToMomBtn.dataset.originalHtml;
        }
    }

    async function applyVoiceTranscriptToMom() {
        const transcript = voiceTranscriptField?.value?.trim() || '';

        if (!transcript) {
            showToast('Transcript masih kosong. Klik Start Listening dulu atau isi transcript manual.', 'warning');
            return;
        }

        if (transcript.length < 20) {
            showToast('Transcript terlalu pendek untuk diolah AI. Tambahkan konteks meeting dulu.', 'warning');
            return;
        }

        if (isListening) {
            stopVoiceRecognition();
        }

        const summaryField = form.querySelector('[name="summary"]');
        const notesField = form.querySelector('[name="notes"]');

        setAiButtonLoading(true);
        setVoiceStatus('warning', 'AI Processing');

        if (voiceInterimText) {
            voiceInterimText.textContent = 'Transcript sedang dikirim ke AI untuk dibuat Summary & Notes...';
        }

        try {
            const response = await fetch(aiSummaryUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    transcript: transcript,
                }),
            });

            const data = await parseResponse(response);
            const aiPayload = getAiPayload(data);

            if (!response.ok) {
                throw new Error(data.message || 'Gagal membuat summary AI.');
            }

            const generatedSummary = normalizeAiText(aiPayload.summary);
            const generatedNotes = buildAiNotesPayload(aiPayload, transcript);

            if (!isFilled(generatedSummary) && !isFilled(generatedNotes)) {
                throw new Error('AI tidak mengembalikan hasil summary yang bisa dipakai.');
            }

            if (!summaryField || !notesField) {
                throw new Error('Field Summary atau Notes tidak ditemukan di form MOM.');
            }

            appendToTextarea(summaryField, generatedSummary, 'AI Generated Summary');
            appendToTextarea(notesField, generatedNotes, 'AI Generated Notes');

            refreshAll();
            setVoiceStatus('ready', 'AI Summary Ready');

            if (voiceInterimText) {
                voiceInterimText.textContent = 'AI summary berhasil dibuat. Cek tab Summary & Notes sebelum submit MOM.';
            }

            showToast('AI Summary berhasil masuk ke Summary & Notes.', 'success');
            moveToSummaryTab();
        } catch (error) {
            setVoiceStatus('warning', 'AI Failed');

            if (voiceInterimText) {
                voiceInterimText.textContent = error.message || 'Gagal memproses transcript dengan AI.';
            }

            showToast(error.message || 'Gagal memproses transcript dengan AI.', 'danger');
        } finally {
            setAiButtonLoading(false);
        }
    }

    function clearVoiceTranscript() {
        shouldKeepListening = false;

        if (isListening && recognition) {
            recognition.stop();
        }

        finalTranscriptBuffer = '';

        if (voiceTranscriptField) {
            voiceTranscriptField.value = '';
            voiceTranscriptField.dispatchEvent(new Event('input', { bubbles: true }));
        }

        if (voiceInterimText) {
            voiceInterimText.textContent = 'Belum ada suara yang tertangkap.';
        }

        setVoiceStatus('ready', 'Ready');
        setVoiceButtons(false);
        showToast('Transcript voice sudah dibersihkan.', 'info');
    }

    function initVoiceRecognition() {
        if (!startVoiceBtn || !stopVoiceBtn || !voiceTranscriptField) return;

        if (!SpeechRecognition) {
            setVoiceStatus('warning', 'Not Supported');
            startVoiceBtn.disabled = true;
            stopVoiceBtn.disabled = true;

            if (voiceInterimText) {
                voiceInterimText.textContent = 'Browser ini belum support speech recognition. Coba pakai Chrome atau Edge.';
            }

            return;
        }

        recognition = new SpeechRecognition();
        recognition.lang = 'id-ID';
        recognition.continuous = true;
        recognition.interimResults = true;
        recognition.maxAlternatives = 1;

        recognition.onstart = function () {
            isListening = true;
            setVoiceStatus('listening', 'Listening');
            setVoiceButtons(true);

            if (voiceInterimText) {
                voiceInterimText.textContent = 'Mendengarkan suara meeting...';
            }
        };

        recognition.onresult = function (event) {
            let interimTranscript = '';
            let finalTranscript = '';

            for (let i = event.resultIndex; i < event.results.length; i++) {
                const transcript = event.results[i][0].transcript;

                if (event.results[i].isFinal) {
                    finalTranscript += transcript + ' ';
                } else {
                    interimTranscript += transcript;
                }
            }

            if (finalTranscript.trim()) {
                finalTranscriptBuffer += finalTranscript.trim() + ' ';
                appendTranscript(finalTranscript);
            }

            if (voiceInterimText) {
                voiceInterimText.textContent = interimTranscript.trim() || 'Mendengarkan suara meeting...';
            }
        };

        recognition.onerror = function (event) {
            const errorMessage = {
                'not-allowed': 'Microphone ditolak. Izinkan akses microphone di browser.',
                'no-speech': 'Belum ada suara yang tertangkap.',
                'audio-capture': 'Microphone tidak terdeteksi.',
                'network': 'Koneksi browser untuk speech recognition bermasalah.'
            }[event.error] || `Voice recognition error: ${event.error}`;

            setVoiceStatus('warning', 'Need Check');
            setVoiceButtons(false);
            showToast(errorMessage, 'warning');
        };

        recognition.onend = function () {
            isListening = false;

            if (shouldKeepListening) {
                setVoiceStatus('listening', 'Listening');
                setVoiceButtons(true);

                setTimeout(function () {
                    try {
                        recognition.start();
                    } catch (error) {
                        shouldKeepListening = false;
                        setVoiceButtons(false);
                        setVoiceStatus('warning', 'Need Check');
                    }
                }, 350);

                return;
            }

            setVoiceButtons(false);

            if (voiceTranscriptField.value.trim()) {
                setVoiceStatus('ready', 'Transcript Ready');

                if (voiceInterimText) {
                    voiceInterimText.textContent = 'Recording berhenti. Transcript siap dikirim ke Summary & Notes.';
                }
            } else {
                setVoiceStatus('ready', 'Ready');

                if (voiceInterimText) {
                    voiceInterimText.textContent = 'Recording berhenti. Belum ada transcript final.';
                }
            }
        };
    }

    function startVoiceRecognition() {
        if (!recognition) {
            showToast('Speech recognition belum tersedia di browser ini.', 'warning');
            return;
        }

        shouldKeepListening = true;

        try {
            recognition.start();
        } catch (error) {
            showToast('Voice assistant sudah aktif atau browser belum siap.', 'warning');
        }
    }

    function stopVoiceRecognition() {
        shouldKeepListening = false;
        if (!recognition || !isListening) return;
        recognition.stop();
    }

    function showToast(message, type = 'success') {
        if (!toastContainer || typeof bootstrap === 'undefined') return;

        const toastId = 'toast-' + Date.now();
        const bgClass = {
            success: 'bg-success',
            danger: 'bg-danger',
            warning: 'bg-warning text-dark',
            info: 'bg-info text-dark'
        }[type] || 'bg-success';

        const closeBtnClass = (type === 'warning' || type === 'info')
            ? 'btn-close me-2 m-auto'
            : 'btn-close btn-close-white me-2 m-auto';

        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0 mb-2" role="alert">
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="${closeBtnClass}" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

        toastContainer.insertAdjacentHTML('beforeend', toastHtml);

        const toastEl = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastEl, { delay: 1800 });
        toast.show();
        toastEl.addEventListener('hidden.bs.toast', function () {
            toastEl.remove();
        });
    }

    function scheduleRedirect(url) {
        if (!url) return;
        if (redirectTimeout) clearTimeout(redirectTimeout);
        redirectTimeout = setTimeout(function () {
            window.location.href = url;
        }, 700);
    }

    function normalizeDate(value) {
        if (!value) return '';
        if (typeof value === 'string' && value.length >= 10) return value.substring(0, 10);
        return value;
    }

    function isFilled(value) {
        return value !== null && value !== undefined && String(value).trim() !== '';
    }

    function setField(card, field, value) {
        const el = card.querySelector(`[data-field="${field}"]`);
        if (!el) return;

        if (el.tagName === 'SELECT') {
            el.value = value ?? '';
            return;
        }

        el.value = value ?? '';
    }

    function applyNames(card, index, prefix) {
        const fields = card.querySelectorAll('.field-input, .field-id');

        fields.forEach(function (field) {
            const fieldName = field.dataset.field;
            if (!fieldName) return;
            field.name = `${prefix}[${index}][${fieldName}]`;
        });
    }

    function fillUserSelect(select, includeManual = true) {
        if (!select) return;

        const firstOption = includeManual
            ? '<option value="">Manual / External</option>'
            : '<option value="">Select User</option>';

        const options = userOptions.map(function (user) {
            return `<option value="${user.id}">${user.name} — ${user.email || '-'}</option>`;
        }).join('');

        select.innerHTML = firstOption + options;
    }

    function bindRemoveButton(card, listEl, emptyEl, callback) {
        const btn = card.querySelector('.remove-item-btn');
        if (!btn) return;

        btn.addEventListener('click', function () {
            card.remove();
            toggleEmptyState(listEl, emptyEl);
            callback();
        });
    }

    function bindInputs(card) {
        card.querySelectorAll('input, textarea, select').forEach(function (el) {
            el.addEventListener('input', refreshAll);
            el.addEventListener('change', refreshAll);
        });
    }

    function toggleEmptyState(listEl, emptyEl) {
        if (!listEl || !emptyEl) return;
        emptyEl.style.display = listEl.children.length > 0 ? 'none' : 'block';
    }

    function renderParticipantRow(data = {}) {
        const template = document.getElementById('participantRowTemplate');
        const clone = template.content.cloneNode(true);
        const card = clone.querySelector('.dynamic-item-card');

        const rowIndex = participantIndex++;
        card.dataset.index = rowIndex;

        fillUserSelect(card.querySelector('[data-field="user_id"]'), true);

        setField(card, 'id', data.id ?? '');
        setField(card, 'user_id', data.user_id ?? '');
        setField(card, 'name', data.name ?? '');
        setField(card, 'email', data.email ?? '');
        setField(card, 'role', data.role ?? 'participant');
        setField(card, 'attendance_status', data.attendance_status ?? 'present');
        setField(card, 'notes', data.notes ?? '');

        applyNames(card, rowIndex, 'participants');
        bindRemoveButton(card, participantsList, participantsEmptyState, refreshAll);
        bindInputs(card);

        participantsList.appendChild(card);
        toggleEmptyState(participantsList, participantsEmptyState);
        refreshAll();
    }

    function renderAgendaRow(data = {}) {
        const template = document.getElementById('agendaRowTemplate');
        const clone = template.content.cloneNode(true);
        const card = clone.querySelector('.dynamic-item-card');

        const rowIndex = agendaIndex++;
        card.dataset.index = rowIndex;

        setField(card, 'id', data.id ?? '');
        setField(card, 'topic', data.topic ?? '');
        setField(card, 'description', data.description ?? '');
        setField(card, 'sort_order', data.sort_order ?? rowIndex + 1);

        applyNames(card, rowIndex, 'agendas');
        bindRemoveButton(card, agendaList, agendaEmptyState, refreshAll);
        bindInputs(card);

        agendaList.appendChild(card);
        toggleEmptyState(agendaList, agendaEmptyState);
        refreshAll();
    }

    function renderActionRow(data = {}) {
        const template = document.getElementById('actionItemRowTemplate');
        const clone = template.content.cloneNode(true);
        const card = clone.querySelector('.dynamic-item-card');

        const rowIndex = actionIndex++;
        card.dataset.index = rowIndex;

        fillUserSelect(card.querySelector('[data-field="pic_user_id"]'), true);

        setField(card, 'id', data.id ?? '');
        setField(card, 'title', data.title ?? '');
        setField(card, 'description', data.description ?? '');
        setField(card, 'pic_user_id', data.pic_user_id ?? '');
        setField(card, 'pic_name', data.pic_name ?? '');
        setField(card, 'priority', data.priority ?? 'medium');
        setField(card, 'due_date', normalizeDate(data.due_date));
        setField(card, 'status', data.status ?? 'pending');
        setField(card, 'notes', data.notes ?? '');

        applyNames(card, rowIndex, 'action_items');
        bindRemoveButton(card, actionItemList, actionItemEmptyState, refreshAll);
        bindInputs(card);

        actionItemList.appendChild(card);
        toggleEmptyState(actionItemList, actionItemEmptyState);
        refreshAll();
    }

    function getSectionStateRequired(isComplete) {
        return isComplete ? 'completed' : 'incomplete';
    }

    function getSectionStateOptional(itemsLength, isCompleteWhenHasData) {
        if (itemsLength === 0) return 'optional';
        return isCompleteWhenHasData ? 'completed' : 'incomplete';
    }

    function checkOverviewState() {
        const title = form.querySelector('[name="title"]')?.value;
        const meetingType = form.querySelector('[name="meeting_type"]')?.value;
        const status = form.querySelector('[name="status"]')?.value;
        const meetingDate = form.querySelector('[name="meeting_date"]')?.value;

        return getSectionStateRequired(
            isFilled(title) && isFilled(meetingType) && isFilled(status) && isFilled(meetingDate)
        );
    }

    function checkParticipantsState() {
        const items = participantsList.querySelectorAll('.participant-item-card');
        const allValid = Array.from(items).every(function (item) {
            const userId = item.querySelector('[data-field="user_id"]')?.value;
            const name = item.querySelector('[data-field="name"]')?.value;
            const email = item.querySelector('[data-field="email"]')?.value;

            return isFilled(userId) || isFilled(name) || isFilled(email);
        });

        return getSectionStateOptional(items.length, allValid);
    }

    function checkAgendaState() {
        const items = agendaList.querySelectorAll('.agenda-item-card');
        const allValid = Array.from(items).every(function (item) {
            return isFilled(item.querySelector('[data-field="topic"]')?.value);
        });

        return getSectionStateOptional(items.length, allValid);
    }

    function checkActionState() {
        const items = actionItemList.querySelectorAll('.action-item-card');
        const allValid = Array.from(items).every(function (item) {
            return isFilled(item.querySelector('[data-field="title"]')?.value);
        });

        return getSectionStateOptional(items.length, allValid);
    }

    function checkSummaryState() {
        const summary = form.querySelector('[name="summary"]')?.value;
        const notes = form.querySelector('[name="notes"]')?.value;

        return getSectionStateRequired(isFilled(summary) || isFilled(notes));
    }

    function updateSectionUI(sectionKey, state) {
        const indicator = document.querySelector(`[data-section-indicator="${sectionKey}"]`);
        const badge = document.querySelector(`[data-section-badge="${sectionKey}"]`);

        if (indicator) {
            indicator.classList.remove('completed', 'optional');

            if (state === 'completed') {
                indicator.classList.add('completed');
                indicator.innerHTML = '<i class="bi bi-check-circle-fill"></i>';
            } else if (state === 'optional') {
                indicator.classList.add('optional');
                indicator.innerHTML = '<i class="bi bi-dash-circle-fill"></i>';
            } else {
                indicator.innerHTML = '<i class="bi bi-circle"></i>';
            }
        }

        if (badge) {
            badge.classList.remove('completed', 'optional');

            if (state === 'completed') {
                badge.classList.add('completed');
                badge.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Ready';
            } else if (state === 'optional') {
                badge.classList.add('optional');
                badge.innerHTML = '<i class="bi bi-dash-circle me-1"></i> Optional';
            } else {
                badge.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Need Input';
            }
        }
    }

    function refreshProgress() {
        const sections = {
            overview: checkOverviewState(),
            participants: checkParticipantsState(),
            agenda: checkAgendaState(),
            action: checkActionState(),
            summary: checkSummaryState(),
        };

        const filledCount = Object.values(sections).filter(function (state) {
            return state === 'completed';
        }).length;

        const totalCount = Object.keys(sections).length;
        const percent = totalCount > 0 ? Math.round((filledCount / totalCount) * 100) : 0;

        Object.entries(sections).forEach(function ([key, state]) {
            updateSectionUI(key, state);
        });

        completedSectionCount.textContent = String(filledCount);
        totalSectionCount.textContent = String(totalCount);
        sectionProgressBar.style.width = `${percent}%`;
    }

    function refreshAll() {
        refreshProgress();

        if (statusField && liveStatusText) {
            liveStatusText.textContent = statusField.options[statusField.selectedIndex]?.text || 'Draft';
        }
    }

    function clearValidationErrors() {
        form.querySelectorAll('.is-invalid').forEach(function (el) {
            el.classList.remove('is-invalid');
        });

        form.querySelectorAll('.error-text').forEach(function (el) {
            el.textContent = '';
        });
    }

    function cssEscapeName(name) {
        return name.replaceAll('[', '\\[').replaceAll(']', '\\]');
    }

    function applyValidationErrors(errors) {
        Object.entries(errors).forEach(function ([key, messages]) {
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

    async function parseResponse(response) {
        const contentType = response.headers.get('content-type') || '';

        if (contentType.includes('application/json')) {
            return await response.json();
        }

        const text = await response.text();

        return {
            success: false,
            message: text || 'Unexpected server response.'
        };
    }

    async function submitForm(forceDraft = false) {
        clearValidationErrors();

        if (forceDraft && statusField) {
            statusField.value = 'draft';
            liveStatusText.textContent = 'Draft';
        }

        const formData = new FormData(form);
        const isEdit = '{{ $isEdit ? '1' : '0' }}' === '1';

        if (isEdit) {
            formData.append('_method', 'PUT');
        }

        const submitButtons = [saveDraftBtn, saveDraftBtnBottom, submitMomBtn, submitMomBtnBottom];
        submitButtons.forEach(function (btn) {
            if (btn) btn.disabled = true;
        });

        const activeButtons = forceDraft ? [saveDraftBtn, saveDraftBtnBottom] : [submitMomBtn, submitMomBtnBottom];

        activeButtons.forEach(function (btn) {
            if (!btn) return;
            btn.dataset.originalHtml = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Saving...';
        });

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: formData,
            });

            const data = await parseResponse(response);

            if (!response.ok || !data.success) {
                if (response.status === 422 && data.errors) {
                    applyValidationErrors(data.errors);
                }

                showToast(data.message || 'Terjadi kesalahan saat menyimpan MOM.', 'danger');
                return;
            }

            showToast(data.message || (forceDraft ? 'Draft MOM berhasil disimpan.' : 'MOM berhasil disimpan.'), 'success');

            if (data.data?.redirect_url) {
                scheduleRedirect(data.data.redirect_url);
            }
        } catch (error) {
            showToast('Terjadi kesalahan saat mengirim data.', 'danger');
        } finally {
            submitButtons.forEach(function (btn) {
                if (btn) btn.disabled = false;
            });

            activeButtons.forEach(function (btn) {
                if (btn && btn.dataset.originalHtml) {
                    btn.innerHTML = btn.dataset.originalHtml;
                }
            });
        }
    }


    initVoiceRecognition();

    if (startVoiceBtn) {
        startVoiceBtn.addEventListener('click', startVoiceRecognition);
    }

    if (stopVoiceBtn) {
        stopVoiceBtn.addEventListener('click', stopVoiceRecognition);
    }

    if (applyVoiceToMomBtn) {
        applyVoiceToMomBtn.addEventListener('click', applyVoiceTranscriptToMom);
    }

    if (clearVoiceTranscriptBtn) {
        clearVoiceTranscriptBtn.addEventListener('click', clearVoiceTranscript);
    }

    if (voiceTranscriptField) {
        voiceTranscriptField.addEventListener('input', function () {
            if (voiceTranscriptField.value.trim()) {
                setVoiceStatus(isListening ? 'listening' : 'ready', isListening ? 'Listening' : 'Transcript Ready');
            } else if (!isListening) {
                setVoiceStatus('ready', 'Ready');
            }
        });
    }

    addParticipantBtn.addEventListener('click', function () {
        renderParticipantRow();
    });

    addAgendaBtn.addEventListener('click', function () {
        renderAgendaRow();
    });

    addActionItemBtn.addEventListener('click', function () {
        renderActionRow();
    });

    saveDraftBtn.addEventListener('click', function () {
        submitForm(true);
    });

    saveDraftBtnBottom.addEventListener('click', function () {
        submitForm(true);
    });

    submitMomBtn.addEventListener('click', function () {
        submitForm(false);
    });

    submitMomBtnBottom.addEventListener('click', function () {
        submitForm(false);
    });

    form.querySelectorAll('input, textarea, select').forEach(function (el) {
        el.addEventListener('input', refreshAll);
        el.addEventListener('change', refreshAll);
    });

    participantRows.forEach(function (row) {
        renderParticipantRow(row);
    });

    agendaRows.forEach(function (row) {
        renderAgendaRow(row);
    });

    actionRows.forEach(function (row) {
        renderActionRow(row);
    });

    toggleEmptyState(participantsList, participantsEmptyState);
    toggleEmptyState(agendaList, agendaEmptyState);
    toggleEmptyState(actionItemList, actionItemEmptyState);

    refreshAll();
});
</script>
@endpush