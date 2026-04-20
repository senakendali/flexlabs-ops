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
                <a href="{{ route('instructor-schedules.index') }}" class="btn btn-light border">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
                <button type="button" id="saveDraftBtn" class="btn btn-save-draft">
                    <i class="bi bi-save me-1"></i> Save Draft
                </button>
                <button type="button" id="submitScheduleBtnTop" class="btn btn-primary">
                    <i class="bi bi-check-circle me-1"></i> {{ $isEdit ? 'Update Schedule' : 'Create Schedule' }}
                </button>
            </div>
        </div>
    </div>

    <div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1090;"></div>

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
                                    <span class="nav-link-subtitle">Judul, program, dan sesi</span>
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
                                    <p class="content-card-subtitle mb-0">Isi informasi dasar sesi yang akan dijadwalkan.</p>
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
                                        <label class="form-label">Session / Sub Topic</label>
                                        <select name="sub_topic_id" class="form-select section-overview">
                                            <option value="">Select Session</option>
                                            @foreach ($subTopics as $subTopic)
                                                <option value="{{ $subTopic->id }}" @selected((string) $subTopicValue === (string) $subTopic->id)>
                                                    {{ $subTopic->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback error-text" data-error-for="sub_topic_id"></div>
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
                                                <option value="{{ $batch->id }}" data-program-id="{{ $batch->program_id }}" @selected((string) $batchValue === (string) $batch->id)>
                                                    {{ $batch->name }}
                                                </option>
                                            @endforeach
                                        </select>
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
                                                @endphp
                                                <option value="{{ $item->id }}" @selected((string) $rescheduledFromValue === (string) $item->id)>
                                                    {{ $item->session_title }} — {{ $scheduleDate }} — {{ $timeLabel }} — {{ $item->batch->name ?? '-' }} — {{ $teachingInstructor ?? '-' }}
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
                    <div class="content-card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div class="footer-note">
                            <div class="footer-note-title">Final Check</div>
                            <div class="footer-note-subtitle">Pastikan jadwal, instruktur, dan batch sudah sesuai sebelum menyimpan data.</div>
                        </div>

                        <div class="d-flex gap-2 flex-wrap">
                            <a href="{{ route('instructor-schedules.index') }}" class="btn btn-light border">Cancel</a>
                            <button type="button" id="saveDraftBtnBottom" class="btn btn-save-draft">
                                <i class="bi bi-save me-1"></i> Save Draft
                            </button>
                            <button type="button" id="submitScheduleBtnBottom" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i> {{ $isEdit ? 'Update Schedule' : 'Create Schedule' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('styles')
<style>
    
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('instructorScheduleForm');
    const saveDraftBtn = document.getElementById('saveDraftBtn');
    const saveDraftBtnBottom = document.getElementById('saveDraftBtnBottom');
    const submitScheduleBtnTop = document.getElementById('submitScheduleBtnTop');
    const submitScheduleBtnBottom = document.getElementById('submitScheduleBtnBottom');
    const toastContainer = document.getElementById('toastContainer');
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

    totalSectionCount.textContent = '4';

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
            <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="${closeBtnClass}" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        const toastEl = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastEl, { delay: 1600 });
        toast.show();
        toastEl.addEventListener('hidden.bs.toast', function () { toastEl.remove(); });
    }

    function clearValidationErrors() {
        form.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
        form.querySelectorAll('.error-text').forEach((el) => el.textContent = '');
    }

    function applyValidationErrors(errors) {
        Object.entries(errors).forEach(([key, messages]) => {
            const message = Array.isArray(messages) ? messages[0] : messages;
            const field = form.querySelector(`[name="${cssEscapeName(key)}"]`);
            if (field) field.classList.add('is-invalid');
            const errorHolder = form.querySelector(`[data-error-for="${key}"]`);
            if (errorHolder) errorHolder.textContent = message;
        });
    }

    function cssEscapeName(name) {
        return name.replaceAll('[', '\\[').replaceAll(']', '\\]');
    }

    function toggleRescheduledField() {
        if (!rescheduledFromWrapper || !isMakeupSession) return;
        rescheduledFromWrapper.style.display = isMakeupSession.checked ? '' : 'none';
    }

    function syncProgramFromBatch() {
        if (!batchSelect || !programSelect) return;
        const selectedOption = batchSelect.options[batchSelect.selectedIndex];
        const programId = selectedOption?.dataset.programId;
        if (programId && !programSelect.value) {
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

    function isFilled(value) {
        return value !== null && value !== undefined && String(value).trim() !== '';
    }

    function checkOverviewState() {
        const sessionTitle = form.querySelector('[name="session_title"]')?.value;
        return isFilled(sessionTitle) ? 'completed' : 'incomplete';
    }

    function checkAssignmentState() {
        const instructor = form.querySelector('[name="instructor_id"]')?.value;
        const batch = form.querySelector('[name="batch_id"]')?.value;
        const needsReschedule = isMakeupSession?.checked;
        const rescheduledFrom = form.querySelector('[name="rescheduled_from_id"]')?.value;

        const isComplete = isFilled(instructor) && isFilled(batch) && (!needsReschedule || isFilled(rescheduledFrom));
        return isComplete ? 'completed' : 'incomplete';
    }

    function checkDeliveryState() {
        const scheduleDate = form.querySelector('[name="schedule_date"]')?.value;
        const startTime = form.querySelector('[name="start_time"]')?.value;
        const endTime = form.querySelector('[name="end_time"]')?.value;
        const deliveryMode = form.querySelector('[name="delivery_mode"]')?.value;

        const isComplete = isFilled(scheduleDate) && isFilled(startTime) && isFilled(endTime) && isFilled(deliveryMode);
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
        completedSectionCount.textContent = String(filledCount);
        totalSectionCount.textContent = String(totalCount);
        sectionProgressBar.style.width = `${percent}%`;
    }

    function refreshAll() {
        toggleRescheduledField();
        updateLiveStatus();
        refreshProgress();
    }

    async function parseResponse(response) {
        const contentType = response.headers.get('content-type') || '';
        if (contentType.includes('application/json')) return await response.json();
        const text = await response.text();
        return { success: false, message: text || 'Unexpected server response.' };
    }

    async function submitForm(forceDraft = false) {
        clearValidationErrors();

        const formData = new FormData(form);
        if (!isMakeupSession?.checked) {
            formData.set('is_makeup_session', '0');
            formData.delete('rescheduled_from_id');
        }

        if (forceDraft) {
            formData.set('status', 'scheduled');
        }

        const submitButtons = [saveDraftBtn, saveDraftBtnBottom, submitScheduleBtnTop, submitScheduleBtnBottom];
        submitButtons.forEach(btn => btn && (btn.disabled = true));

        const activeButtons = forceDraft ? [saveDraftBtn, saveDraftBtnBottom] : [submitScheduleBtnTop, submitScheduleBtnBottom];
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
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: formData,
            });

            const data = await parseResponse(response);

            if (!response.ok) {
                if (response.status === 422 && data.errors) {
                    applyValidationErrors(data.errors);
                }
                showToast(data.message || 'Terjadi kesalahan saat menyimpan data.', 'danger');
                return;
            }

            showToast(data.message || 'Jadwal instruktur berhasil disimpan.', 'success');
            if (data.redirect) {
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 900);
            }
        } catch (error) {
            showToast('Terjadi kesalahan saat mengirim data.', 'danger');
        } finally {
            submitButtons.forEach(btn => btn && (btn.disabled = false));
            activeButtons.forEach((btn) => {
                if (btn && btn.dataset.originalHtml) btn.innerHTML = btn.dataset.originalHtml;
            });
        }
    }

    [saveDraftBtn, saveDraftBtnBottom].forEach((btn) => btn?.addEventListener('click', () => submitForm(true)));
    [submitScheduleBtnTop, submitScheduleBtnBottom].forEach((btn) => btn?.addEventListener('click', () => submitForm(false)));

    isMakeupSession?.addEventListener('change', refreshAll);
    batchSelect?.addEventListener('change', function () {
        syncProgramFromBatch();
        refreshAll();
    });
    statusField?.addEventListener('change', updateLiveStatus);

    form.querySelectorAll('input, textarea, select').forEach((el) => {
        el.addEventListener('input', refreshAll);
        el.addEventListener('change', refreshAll);
    });

    syncProgramFromBatch();
    refreshAll();
});
</script>
@endpush
