@extends('layouts.app-dashboard')

@section('title', 'Assignments')

@section('content')
<div class="container-fluid px-4 py-4">

    {{-- ============================================================
        PAGE HEADER
    ============================================================ --}}
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Academic Management</div>

                <h1 class="page-title mb-2">
                    Assignments
                </h1>

                <p class="page-subtitle mb-0">
                    Kelola tugas pembelajaran yang terhubung ke
                    <strong>Topic</strong> atau
                    <strong>Sub Topic</strong>.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <button
                    type="button"
                    class="btn btn-light btn-modern"
                    data-bs-toggle="modal"
                    data-bs-target="#assignmentModal"
                    data-mode="create"
                >
                    <i class="bi bi-plus-circle me-2"></i>
                    Add Assignment
                </button>
            </div>
        </div>
    </div>

    {{-- ============================================================
        STATS
    ============================================================ --}}
    <div class="row g-3 mb-4">

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-journal-check"></i>
                    </div>

                    <div>
                        <div class="stat-title">
                            Total Assignments
                        </div>

                        <div class="stat-value">
                            {{ $stats['total'] ?? 0 }}
                        </div>
                    </div>
                </div>

                <div class="stat-description">
                    Total master assignment yang tersedia.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-send-check"></i>
                    </div>

                    <div>
                        <div class="stat-title">
                            Published
                        </div>

                        <div class="stat-value">
                            {{ $stats['published'] ?? 0 }}
                        </div>
                    </div>
                </div>

                <div class="stat-description">
                    Assignment yang siap digunakan.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-pencil-square"></i>
                    </div>

                    <div>
                        <div class="stat-title">
                            Draft
                        </div>

                        <div class="stat-value">
                            {{ $stats['draft'] ?? 0 }}
                        </div>
                    </div>
                </div>

                <div class="stat-description">
                    Assignment yang masih disiapkan.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-toggle-on"></i>
                    </div>

                    <div>
                        <div class="stat-title">
                            Active
                        </div>

                        <div class="stat-value">
                            {{ $stats['active'] ?? 0 }}
                        </div>
                    </div>
                </div>

                <div class="stat-description">
                    Assignment aktif di sistem.
                </div>
            </div>
        </div>

    </div>

    {{-- ============================================================
        FILTER
    ============================================================ --}}
    <div class="content-card mb-4">

        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">
                    Filter Assignments
                </h5>

                <p class="content-card-subtitle mb-0">
                    Cari assignment berdasarkan program, stage, topic,
                    sub topic, tipe, atau status.
                </p>
            </div>
        </div>

        <div class="content-card-body">

            <form
                method="GET"
                action="{{ route('assignments.index') }}"
                id="assignmentFilterForm"
                class="assignment-hierarchy-container"

                data-options-base-url="{{ rtrim(route('assignments.index'), '/') }}/options"

                data-initial-program-id="{{ request('program_id') }}"
                data-initial-stage-id="{{ request('stage_id') }}"
                data-initial-topic-id="{{ request('topic_id') }}"
                data-initial-sub-topic-id="{{ request('sub_topic_id') }}"
            >

                {{-- =================================================
                    ROW 1
                ================================================= --}}
                <div class="row g-3">

                    {{-- Keyword --}}
                    <div class="col-xl-4 col-lg-6">
                        <label class="form-label">
                            Keyword
                        </label>

                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            value="{{ request('search') }}"
                            placeholder="Cari judul atau instruction..."
                        >
                    </div>

                    {{-- Program --}}
                    <div class="col-xl-4 col-lg-6">
                        <label class="form-label">
                            Program
                        </label>

                        <select
                            name="program_id"
                            class="form-select"
                        >
                            <option value="">
                                All Programs
                            </option>

                            @foreach($programs ?? [] as $program)
                                <option
                                    value="{{ $program->id }}"
                                    {{ (string) request('program_id') === (string) $program->id ? 'selected' : '' }}
                                >
                                    {{ $program->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Stage --}}
                    <div
                        class="col-xl-4 col-lg-6 assignment-stage-field"
                    >
                        <label class="form-label">
                            Stage
                        </label>

                        <select
                            name="stage_id"
                            class="form-select"
                            data-placeholder="All Stages"
                            disabled
                        >
                            <option value="">
                                Select program first
                            </option>
                        </select>
                    </div>

                </div>

                {{-- =================================================
                    ROW 2
                ================================================= --}}
                <div class="row g-3 mt-1">

                    {{-- Topic --}}
                    <div class="col-xl-4 col-lg-6">
                        <label class="form-label">
                            Topic
                        </label>

                        <select
                            name="topic_id"
                            class="form-select"
                            data-placeholder="All Topics"
                            disabled
                        >
                            <option value="">
                                Select program first
                            </option>
                        </select>
                    </div>

                    {{-- Sub Topic --}}
                    <div class="col-xl-4 col-lg-6">
                        <label class="form-label">
                            Sub Topic
                        </label>

                        <select
                            name="sub_topic_id"
                            class="form-select"
                            data-placeholder="All Sub Topics"
                            disabled
                        >
                            <option value="">
                                Select topic first
                            </option>
                        </select>
                    </div>

                    {{-- Type --}}
                    <div class="col-xl-2 col-lg-6">
                        <label class="form-label">
                            Type
                        </label>

                        <select
                            name="assignment_type"
                            class="form-select"
                        >
                            <option value="">
                                All Types
                            </option>

                            @foreach($assignmentTypes ?? [] as $key => $label)
                                <option
                                    value="{{ $key }}"
                                    {{ request('assignment_type') === $key ? 'selected' : '' }}
                                >
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Status --}}
                    <div class="col-xl-2 col-lg-6">
                        <label class="form-label">
                            Status
                        </label>

                        <select
                            name="status"
                            class="form-select"
                        >
                            <option value="">
                                All Statuses
                            </option>

                            @foreach($statuses ?? [] as $key => $label)
                                <option
                                    value="{{ $key }}"
                                    {{ request('status') === $key ? 'selected' : '' }}
                                >
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                </div>

                {{-- =================================================
                    ROW 3 - ACTIONS
                ================================================= --}}
                <div class="assignment-filter-actions-row mt-4 pt-3">

                    <div class="d-flex justify-content-end gap-2 flex-wrap">

                        <a
                            href="{{ route('assignments.index') }}"
                            class="btn btn-outline-secondary btn-modern"
                        >
                            <i class="bi bi-arrow-counterclockwise me-2"></i>
                            Reset
                        </a>

                        <button
                            type="submit"
                            class="btn btn-primary btn-modern"
                        >
                            <i class="bi bi-funnel me-2"></i>
                            Filter
                        </button>

                    </div>

                </div>

            </form>

        </div>
    </div>

    {{-- ============================================================
        ASSIGNMENT LIST
    ============================================================ --}}
    <div class="content-card">

        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">
                    Assignment List
                </h5>

                <p class="content-card-subtitle mb-0">
                    Assignment ini masih berupa master/template.
                    Deadline per batch akan diatur di Batch Assignment.
                </p>
            </div>
        </div>

        <div class="content-card-body">

            @if(($assignments ?? collect())->count())

                <div class="assignment-list">

                    @foreach($assignments as $assignment)

                        @php
                            $statusClass = match($assignment->status) {
                                'published' => 'status-published',
                                'archived' => 'status-archived',
                                default => 'status-draft',
                            };

                            $typeClass = match($assignment->assignment_type) {
                                'text' => 'type-text',
                                'file' => 'type-file',
                                'link' => 'type-link',
                                default => 'type-mixed',
                            };

                            $targetLabel = $assignment->subTopic
                                ? 'Sub Topic: ' . $assignment->subTopic->name
                                : 'Topic: ' . ($assignment->topic->name ?? '-');

                            $materialCount = collect([
                                $assignment->attachment_url,
                                $assignment->starter_file_url,
                                $assignment->reference_url,
                            ])->filter()->count();

                            $instructionHtml = $assignment->instruction ?? '';

                            $hasInstruction =
                                trim(strip_tags($instructionHtml)) !== '';

                            $assignmentStageId =
                                $assignment->topic?->module?->program_stage_id;

                            $assignmentProgramId =
                                $assignment->topic?->module?->stage?->program_id
                                ?? $assignment->topic?->module?->program_id
                                ?? null;
                        @endphp

                        <script
                            type="application/json"
                            id="assignment-instruction-{{ $assignment->id }}"
                        >
                            @json($assignment->instruction)
                        </script>

                        <div class="assignment-card">

                            <div class="assignment-main">

                                <div class="assignment-icon">
                                    <i class="bi bi-journal-check"></i>
                                </div>

                                <div class="assignment-info">

                                    <div class="assignment-title-row">

                                        <h5 class="assignment-title mb-0">
                                            {{ $assignment->title }}
                                        </h5>

                                        <div class="assignment-badges">

                                            <span class="assignment-type-badge {{ $typeClass }}">
                                                {{ $assignment->type_label }}
                                            </span>

                                            <span class="assignment-status-badge {{ $statusClass }}">
                                                {{ $assignment->status_label }}
                                            </span>

                                            @if($assignment->is_required)
                                                <span class="assignment-required-badge">
                                                    Required
                                                </span>
                                            @else
                                                <span class="assignment-optional-badge">
                                                    Optional
                                                </span>
                                            @endif

                                        </div>

                                    </div>

                                    <div class="assignment-meta">

                                        <span>
                                            <i class="bi bi-layers me-1"></i>
                                            {{ $targetLabel }}
                                        </span>

                                        @if($assignment->topic?->module)
                                            <span>
                                                <i class="bi bi-folder2-open me-1"></i>
                                                {{ $assignment->topic->module->name }}
                                            </span>
                                        @endif

                                        @if($assignment->topic?->module?->stage)
                                            <span>
                                                <i class="bi bi-diagram-3 me-1"></i>
                                                {{ $assignment->topic->module->stage->name }}
                                            </span>
                                        @endif

                                        <span>
                                            <i class="bi bi-star me-1"></i>
                                            Max Score {{ $assignment->max_score }}
                                        </span>

                                    </div>

                                    @if($hasInstruction)

                                        <div class="assignment-instruction-preview">
                                            <div class="assignment-instruction-content ql-editor">
                                                {!! $instructionHtml !!}
                                            </div>
                                        </div>

                                    @endif

                                    <div class="assignment-footer-meta">

                                        <span>
                                            <i class="bi bi-paperclip me-1"></i>
                                            {{ $materialCount }} resources
                                        </span>

                                        <span>
                                            <i class="bi bi-people me-1"></i>
                                            {{ $assignment->batch_assignments_count ?? 0 }}
                                            batch assignments
                                        </span>

                                        <span>
                                            <i class="bi bi-inbox me-1"></i>
                                            {{ $assignment->submissions_count ?? 0 }}
                                            submissions
                                        </span>

                                    </div>

                                </div>

                            </div>

                            <div class="assignment-actions">

                                <button
                                    type="button"
                                    class="btn btn-outline-secondary btn-sm"

                                    data-bs-toggle="modal"
                                    data-bs-target="#assignmentModal"

                                    data-mode="edit"

                                    data-id="{{ $assignment->id }}"

                                    data-program-id="{{ $assignmentProgramId }}"
                                    data-stage-id="{{ $assignmentStageId }}"
                                    data-topic-id="{{ $assignment->topic_id }}"
                                    data-sub-topic-id="{{ $assignment->sub_topic_id }}"

                                    data-title="{{ $assignment->title }}"
                                    data-assignment-type="{{ $assignment->assignment_type }}"

                                    data-instruction-json-id="assignment-instruction-{{ $assignment->id }}"

                                    data-attachment-url="{{ $assignment->attachment_url }}"
                                    data-starter-file-url="{{ $assignment->starter_file_url }}"
                                    data-reference-url="{{ $assignment->reference_url }}"

                                    data-max-score="{{ $assignment->max_score }}"
                                    data-is-required="{{ (int) $assignment->is_required }}"
                                    data-sort-order="{{ $assignment->sort_order }}"
                                    data-status="{{ $assignment->status }}"
                                    data-is-active="{{ (int) $assignment->is_active }}"
                                >
                                    <i class="bi bi-pencil-square me-1"></i>
                                    Edit
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-outline-danger btn-sm"

                                    data-bs-toggle="modal"
                                    data-bs-target="#deleteConfirmModal"

                                    data-delete-name="{{ $assignment->title }}"
                                    data-delete-url="{{ route('assignments.destroy', $assignment->id) }}"
                                >
                                    <i class="bi bi-trash me-1"></i>
                                    Delete
                                </button>

                            </div>

                        </div>

                    @endforeach

                </div>

                <div class="mt-4">
                    {{ $assignments->links() }}
                </div>

            @else

                <div class="empty-state-box">

                    <div class="empty-state-icon">
                        <i class="bi bi-journal-x"></i>
                    </div>

                    <h5 class="empty-state-title">
                        Assignment belum tersedia
                    </h5>

                    <p class="empty-state-text mb-3">
                        Belum ada assignment yang sesuai dengan filter
                        atau belum ada assignment yang dibuat.
                    </p>

                    <button
                        type="button"
                        class="btn btn-primary btn-modern"

                        data-bs-toggle="modal"
                        data-bs-target="#assignmentModal"

                        data-mode="create"
                    >
                        <i class="bi bi-plus-circle me-2"></i>
                        Add Assignment
                    </button>

                </div>

            @endif

        </div>

    </div>

</div>

{{-- ================================================================
    ASSIGNMENT MODAL
================================================================ --}}
<div
    class="modal fade"
    id="assignmentModal"
    tabindex="-1"
    aria-labelledby="assignmentModalLabel"
    aria-hidden="true"
>
    <div class="modal-dialog modal-xl modal-dialog-centered">

        <div class="modal-content custom-modal">

            <form
                id="assignmentForm"
                class="assignment-hierarchy-container"

                data-create-url="{{ route('assignments.store') }}"
                data-options-base-url="{{ rtrim(route('assignments.index'), '/') }}/options"
            >

                @csrf

                <input
                    type="hidden"
                    name="_method"
                    value="POST"
                >

                <input
                    type="hidden"
                    name="id"
                    value=""
                >

                <textarea
                    name="instruction"
                    class="d-none"
                    hidden
                ></textarea>

                <div class="modal-header border-0 pb-0">

                    <div>
                        <h5
                            class="modal-title"
                            id="assignmentModalLabel"
                        >
                            Add Assignment
                        </h5>

                        <p
                            class="text-muted mb-0"
                            id="assignmentModalSubtitle"
                        >
                            Tentukan program dan topic untuk assignment.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                    ></button>

                </div>

                <div class="modal-body pt-4">

                    <div
                        class="alert alert-danger d-none form-alert"
                        role="alert"
                    ></div>

                    <div class="row g-3">

                        {{-- Program --}}
                        <div class="col-xl-3 col-md-6">

                            <label class="form-label">
                                Program
                                <span class="text-danger">*</span>
                            </label>

                            <select
                                name="program_id"
                                class="form-select"
                                required
                            >
                                <option value="">
                                    Select Program
                                </option>

                                @foreach($programs ?? [] as $program)
                                    <option value="{{ $program->id }}">
                                        {{ $program->name }}
                                    </option>
                                @endforeach
                            </select>

                            <div class="form-text">
                                Pilih program assignment.
                            </div>

                        </div>

                        {{-- Stage --}}
                        <div
                            class="col-xl-3 col-md-6 assignment-stage-field"
                        >

                            <label class="form-label">
                                Stage
                                <span class="text-danger assignment-stage-required-mark d-none">*</span>
                            </label>

                            <select
                                name="stage_id"
                                class="form-select"
                                data-placeholder="Select Stage"
                                disabled
                            >
                                <option value="">
                                    Select program first
                                </option>
                            </select>

                            <div class="form-text assignment-stage-help">
                                Stage akan muncul jika digunakan oleh program.
                            </div>

                        </div>

                        {{-- Topic --}}
                        <div class="col-xl-3 col-md-6">

                            <label class="form-label">
                                Topic
                                <span class="text-danger">*</span>
                            </label>

                            <select
                                name="topic_id"
                                class="form-select"
                                data-placeholder="Select Topic"
                                disabled
                                required
                            >
                                <option value="">
                                    Select program first
                                </option>
                            </select>

                            <div class="form-text">
                                Topic dimuat sesuai struktur curriculum.
                            </div>

                        </div>

                        {{-- Sub Topic --}}
                        <div class="col-xl-3 col-md-6">

                            <label class="form-label">
                                Sub Topic
                            </label>

                            <select
                                name="sub_topic_id"
                                class="form-select"
                                data-placeholder="No Sub Topic"
                                disabled
                            >
                                <option value="">
                                    Select topic first
                                </option>
                            </select>

                            <div class="form-text">
                                Optional. Pilih jika assignment khusus sub topic.
                            </div>

                        </div>

                        {{-- Title --}}
                        <div class="col-12">

                            <label class="form-label">
                                Assignment Title
                            </label>

                            <input
                                type="text"
                                name="title"
                                class="form-control"
                                placeholder="Contoh: Buat REST API sederhana dengan Laravel"
                                required
                            >

                        </div>

                        {{-- Type --}}
                        <div class="col-md-4">

                            <label class="form-label">
                                Assignment Type
                            </label>

                            <select
                                name="assignment_type"
                                class="form-select"
                                required
                            >
                                @foreach($assignmentTypes ?? [] as $key => $label)
                                    <option value="{{ $key }}">
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>

                        </div>

                        {{-- Max Score --}}
                        <div class="col-md-4">

                            <label class="form-label">
                                Max Score
                            </label>

                            <input
                                type="number"
                                name="max_score"
                                class="form-control"
                                min="1"
                                max="999"
                                value="100"
                            >

                        </div>

                        {{-- Required --}}
                        <div class="col-md-4">

                            <label class="form-label">
                                Required
                            </label>

                            <select
                                name="is_required"
                                class="form-select"
                            >
                                <option value="1">
                                    Required
                                </option>

                                <option value="0">
                                    Optional
                                </option>
                            </select>

                        </div>

                        {{-- Order --}}
                        <div class="col-md-4">

                            <label class="form-label">
                                Order
                            </label>

                            <input
                                type="number"
                                name="sort_order"
                                class="form-control"
                                min="1"
                                value="1"
                            >

                        </div>

                        {{-- Status --}}
                        <div class="col-md-4">

                            <label class="form-label">
                                Status
                            </label>

                            <select
                                name="status"
                                class="form-select"
                            >
                                @foreach($statuses ?? [] as $key => $label)
                                    <option value="{{ $key }}">
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>

                        </div>

                        {{-- Active --}}
                        <div class="col-md-4">

                            <label class="form-label">
                                Active Status
                            </label>

                            <select
                                name="is_active"
                                class="form-select"
                            >
                                <option value="1">
                                    Active
                                </option>

                                <option value="0">
                                    Inactive
                                </option>
                            </select>

                        </div>

                        {{-- Instruction --}}
                        <div class="col-12">

                            <div class="assignment-quill-block">

                                <label class="form-label">
                                    Instruction
                                </label>

                                <div class="assignment-quill-shell">
                                    <div
                                        id="assignmentInstructionEditor"
                                        class="assignment-quill-editor"
                                    ></div>
                                </div>

                                <div class="form-text mt-2">
                                    Tulis instruksi assignment secara jelas.
                                    Bisa menggunakan heading, list, bold, dan link.
                                </div>

                            </div>

                        </div>

                        {{-- Resources --}}
                        <div class="col-12">

                            <div class="assignment-resource-card">

                                <div class="assignment-resource-header">

                                    <div>
                                        <div class="assignment-resource-title">
                                            <i class="bi bi-paperclip me-2"></i>
                                            Assignment Resources
                                        </div>

                                        <div class="assignment-resource-subtitle">
                                            Link pendukung untuk tugas, starter file,
                                            atau referensi eksternal.
                                        </div>
                                    </div>

                                </div>

                                <div class="row g-3">

                                    <div class="col-md-4">

                                        <label class="form-label">
                                            Attachment URL
                                        </label>

                                        <input
                                            type="url"
                                            name="attachment_url"
                                            class="form-control"
                                            placeholder="https://drive.google.com/..."
                                        >

                                    </div>

                                    <div class="col-md-4">

                                        <label class="form-label">
                                            Starter File URL
                                        </label>

                                        <input
                                            type="url"
                                            name="starter_file_url"
                                            class="form-control"
                                            placeholder="https://github.com/... atau link file"
                                        >

                                    </div>

                                    <div class="col-md-4">

                                        <label class="form-label">
                                            Reference URL
                                        </label>

                                        <input
                                            type="url"
                                            name="reference_url"
                                            class="form-control"
                                            placeholder="https://..."
                                        >

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

                <div class="modal-footer border-0 pt-0">

                    <button
                        type="button"
                        class="btn btn-outline-secondary btn-modern"
                        data-bs-dismiss="modal"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="btn btn-primary btn-modern submit-btn"
                    >
                        Save Assignment
                    </button>

                </div>

            </form>

        </div>

    </div>
</div>

{{-- ================================================================
    DELETE MODAL
================================================================ --}}
<div
    class="modal fade"
    id="deleteConfirmModal"
    tabindex="-1"
    aria-labelledby="deleteConfirmModalLabel"
    aria-hidden="true"
>
    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content custom-modal delete-confirm-modal">

            <div class="modal-header border-0 pb-0">

                <div class="delete-confirm-heading">

                    <div class="delete-confirm-icon">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>

                    <div>
                        <h5
                            class="modal-title"
                            id="deleteConfirmModalLabel"
                        >
                            Delete Assignment
                        </h5>

                        <p class="text-muted mb-0">
                            Konfirmasi sebelum menghapus assignment.
                        </p>
                    </div>

                </div>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                    aria-label="Close"
                ></button>

            </div>

            <div class="modal-body pt-4">

                <div class="delete-confirm-message">

                    <div class="delete-confirm-label">
                        Assignment yang akan dihapus
                    </div>

                    <div
                        class="delete-confirm-name"
                        id="deleteConfirmName"
                    >
                        -
                    </div>

                </div>

                <div class="delete-confirm-warning mt-3">
                    Assignment yang sudah dihapus tidak bisa dikembalikan.
                    Jika sudah digunakan di batch/submission,
                    pastikan data memang aman untuk dihapus.
                </div>

            </div>

            <div class="modal-footer border-0 pt-0">

                <button
                    type="button"
                    class="btn btn-outline-secondary btn-modern"
                    data-bs-dismiss="modal"
                >
                    Cancel
                </button>

                <button
                    type="button"
                    class="btn btn-danger btn-modern"
                    id="confirmDeleteBtn"
                >
                    <i class="bi bi-trash me-2"></i>
                    Delete
                </button>

            </div>

        </div>

    </div>
</div>

@endsection

@push('styles')

<link
    href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css"
    rel="stylesheet"
>

<style>
    .assignment-filter-actions-row {
        border-top: 1px solid #eef0f3;
    }

    .assignment-stage-field.is-hidden {
        display: none !important;
    }

    .assignment-select-loading {
        cursor: wait;
    }

    @media (max-width: 575.98px) {
        .assignment-filter-actions-row .d-flex {
            width: 100%;
        }

        .assignment-filter-actions-row .btn-modern {
            flex: 1 1 0;
        }
    }
</style>

@endpush

@push('scripts')

<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const csrfToken =
        document.querySelector('#assignmentForm input[name="_token"]')?.value
        || '{{ csrf_token() }}';

    let instructionQuill = null;

    /*
    |--------------------------------------------------------------------------
    | Toast
    |--------------------------------------------------------------------------
    */
    function showToast(message, type = 'success') {
        const toastEl = document.getElementById('appToast');

        if (!toastEl) {
            return;
        }

        const toastBody = toastEl.querySelector('.toast-body');

        if (toastBody) {
            toastBody.textContent = message;
        }

        toastEl.classList.remove(
            'bg-success',
            'bg-danger',
            'text-white'
        );

        if (type === 'success') {
            toastEl.classList.add(
                'bg-success',
                'text-white'
            );
        } else {
            toastEl.classList.add(
                'bg-danger',
                'text-white'
            );
        }

        bootstrap.Toast
            .getOrCreateInstance(
                toastEl,
                {
                    delay: 2500
                }
            )
            .show();
    }

    /*
    |--------------------------------------------------------------------------
    | Escape
    |--------------------------------------------------------------------------
    */
    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /*
    |--------------------------------------------------------------------------
    | Select helpers
    |--------------------------------------------------------------------------
    */
    function setSelectMessage(
        select,
        message,
        disabled = true
    ) {
        if (!select) {
            return;
        }

        select.innerHTML = '';

        const option =
            document.createElement('option');

        option.value = '';
        option.textContent = message;

        select.appendChild(option);

        select.value = '';
        select.disabled = disabled;
    }

    function populateSelect(
        select,
        items,
        placeholder,
        selectedValue = '',
        labelResolver = null
    ) {
        if (!select) {
            return;
        }

        select.innerHTML = '';

        const placeholderOption =
            document.createElement('option');

        placeholderOption.value = '';
        placeholderOption.textContent = placeholder;

        select.appendChild(
            placeholderOption
        );

        items.forEach(function (item) {
            const option =
                document.createElement('option');

            option.value =
                String(item.id);

            option.textContent =
                labelResolver
                    ? labelResolver(item)
                    : (
                        item.label
                        || item.name
                        || `#${item.id}`
                    );

            select.appendChild(option);
        });

        select.disabled = false;

        if (selectedValue !== null && selectedValue !== undefined) {
            select.value =
                String(selectedValue);
        }
    }

    function setSelectLoading(
        select,
        label = 'Loading...'
    ) {
        if (!select) {
            return;
        }

        select.classList.add(
            'assignment-select-loading'
        );

        setSelectMessage(
            select,
            label,
            true
        );
    }

    function clearSelectLoading(select) {
        select?.classList.remove(
            'assignment-select-loading'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Fetch JSON
    |--------------------------------------------------------------------------
    */
    async function fetchJson(
        url,
        signal = undefined
    ) {
        const response =
            await fetch(
                url,
                {
                    method: 'GET',

                    headers: {
                        'Accept':
                            'application/json',

                        'X-Requested-With':
                            'XMLHttpRequest',
                    },

                    credentials:
                        'same-origin',

                    signal,
                }
            );

        const data =
            await response
                .json()
                .catch(
                    () => ({})
                );

        if (!response.ok) {
            const validationMessage =
                data?.errors
                    ? Object.values(data.errors)
                        .flat()
                        .filter(Boolean)
                        .join(' ')
                    : null;

            throw new Error(
                validationMessage
                || data.message
                || 'Gagal mengambil data.'
            );
        }

        return data;
    }

    /*
    |--------------------------------------------------------------------------
    | Hierarchy refs
    |--------------------------------------------------------------------------
    */
    function hierarchyRefs(container) {
        return {
            program:
                container.querySelector(
                    'select[name="program_id"]'
                ),

            stage:
                container.querySelector(
                    'select[name="stage_id"]'
                ),

            topic:
                container.querySelector(
                    'select[name="topic_id"]'
                ),

            subTopic:
                container.querySelector(
                    'select[name="sub_topic_id"]'
                ),

            stageField:
                container.querySelector(
                    '.assignment-stage-field'
                ),

            stageRequiredMark:
                container.querySelector(
                    '.assignment-stage-required-mark'
                ),

            optionsBaseUrl:
                String(
                    container.dataset.optionsBaseUrl
                    || ''
                ).replace(/\/$/, ''),
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Abort previous hierarchy request
    |--------------------------------------------------------------------------
    */
    function beginHierarchyRequest(container) {
        if (
            container._hierarchyAbortController
        ) {
            container
                ._hierarchyAbortController
                .abort();
        }

        const controller =
            new AbortController();

        container._hierarchyAbortController =
            controller;

        return controller;
    }

    /*
    |--------------------------------------------------------------------------
    | Stage visibility
    |--------------------------------------------------------------------------
    */
    function setStageVisibility(
        refs,
        visible,
        required = false
    ) {
        if (refs.stageField) {
            refs.stageField.classList.toggle(
                'is-hidden',
                !visible
            );
        }

        if (refs.stage) {
            refs.stage.required =
                visible && required;
        }

        refs.stageRequiredMark
            ?.classList
            .toggle(
                'd-none',
                !(visible && required)
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Load Program Structure
    |--------------------------------------------------------------------------
    */
    async function loadProgramStructure(
        container,
        {
            selectedStageId = '',
            selectedTopicId = '',
            selectedSubTopicId = ''
        } = {}
    ) {
        const refs =
            hierarchyRefs(container);

        const programId =
            refs.program?.value || '';

        if (!programId) {
            setStageVisibility(
                refs,
                true,
                false
            );

            setSelectMessage(
                refs.stage,
                'Select program first'
            );

            setSelectMessage(
                refs.topic,
                'Select program first'
            );

            setSelectMessage(
                refs.subTopic,
                'Select topic first'
            );

            container.dataset.programMode = '';

            return;
        }

        const controller =
            beginHierarchyRequest(container);

        setStageVisibility(
            refs,
            true,
            false
        );

        setSelectLoading(
            refs.stage,
            'Loading stages...'
        );

        setSelectMessage(
            refs.topic,
            'Waiting for program structure'
        );

        setSelectMessage(
            refs.subTopic,
            'Select topic first'
        );

        try {
            const params =
                new URLSearchParams({
                    program_id:
                        programId
                });

            const response =
                await fetchJson(
                    `${refs.optionsBaseUrl}/stages?${params.toString()}`,
                    controller.signal
                );

            const structure =
                response?.data || {};

            const mode =
                structure.mode || 'empty';

            const stages =
                Array.isArray(
                    structure.stages
                )
                    ? structure.stages
                    : [];

            container.dataset.programMode =
                mode;

            clearSelectLoading(
                refs.stage
            );

            /*
            |--------------------------------------------------------------------------
            | DIRECT PROGRAM
            |--------------------------------------------------------------------------
            */
            if (
                mode === 'direct'
                || (
                    !structure.has_stages
                    && structure.has_direct_topics
                )
            ) {
                refs.stage.value = '';

                setStageVisibility(
                    refs,
                    false,
                    false
                );

                await loadTopics(
                    container,
                    {
                        selectedTopicId,
                        selectedSubTopicId,
                        signal:
                            controller.signal
                    }
                );

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | EMPTY PROGRAM
            |--------------------------------------------------------------------------
            */
            if (mode === 'empty') {
                setStageVisibility(
                    refs,
                    false,
                    false
                );

                setSelectMessage(
                    refs.topic,
                    'No topics available'
                );

                setSelectMessage(
                    refs.subTopic,
                    'Select topic first'
                );

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | STAGED / HYBRID
            |--------------------------------------------------------------------------
            */
            setStageVisibility(
                refs,
                true,
                Boolean(
                    structure.requires_stage
                )
            );

            let stagePlaceholder =
                refs.stage?.dataset.placeholder
                || 'Select Stage';

            if (mode === 'hybrid') {
                stagePlaceholder =
                    'No Stage / Direct Topics';
            }

            populateSelect(
                refs.stage,
                stages,
                stagePlaceholder,
                selectedStageId
            );

            /*
            |--------------------------------------------------------------------------
            | Existing selected Stage
            |--------------------------------------------------------------------------
            */
            if (selectedStageId) {
                refs.stage.value =
                    String(selectedStageId);

                await loadTopics(
                    container,
                    {
                        selectedTopicId,
                        selectedSubTopicId,
                        signal:
                            controller.signal
                    }
                );

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Hybrid:
            | empty Stage = direct curriculum
            |--------------------------------------------------------------------------
            */
            if (mode === 'hybrid') {
                await loadTopics(
                    container,
                    {
                        selectedTopicId,
                        selectedSubTopicId,
                        signal:
                            controller.signal
                    }
                );

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Staged pure:
            | tunggu Stage dipilih
            |--------------------------------------------------------------------------
            */
            setSelectMessage(
                refs.topic,
                'Select stage first'
            );

            setSelectMessage(
                refs.subTopic,
                'Select topic first'
            );

        } catch (error) {
            if (
                error.name === 'AbortError'
            ) {
                return;
            }

            clearSelectLoading(
                refs.stage
            );

            setSelectMessage(
                refs.stage,
                'Unable to load stages'
            );

            setSelectMessage(
                refs.topic,
                'Unable to load topics'
            );

            showToast(
                error.message
                || 'Gagal mengambil struktur program.',
                'error'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Load Topics
    |--------------------------------------------------------------------------
    */
    async function loadTopics(
        container,
        {
            selectedTopicId = '',
            selectedSubTopicId = '',
            signal = undefined
        } = {}
    ) {
        const refs =
            hierarchyRefs(container);

        const programId =
            refs.program?.value || '';

        const stageId =
            refs.stage?.value || '';

        const mode =
            container.dataset.programMode
            || '';

        if (!programId) {
            setSelectMessage(
                refs.topic,
                'Select program first'
            );

            return;
        }

        if (
            mode === 'staged'
            && !stageId
        ) {
            setSelectMessage(
                refs.topic,
                'Select stage first'
            );

            setSelectMessage(
                refs.subTopic,
                'Select topic first'
            );

            return;
        }

        setSelectLoading(
            refs.topic,
            'Loading topics...'
        );

        setSelectMessage(
            refs.subTopic,
            'Select topic first'
        );

        try {
            const params =
                new URLSearchParams({
                    program_id:
                        programId
                });

            if (stageId) {
                params.set(
                    'stage_id',
                    stageId
                );
            }

            const response =
                await fetchJson(
                    `${refs.optionsBaseUrl}/topics?${params.toString()}`,
                    signal
                );

            const topics =
                Array.isArray(
                    response?.data?.topics
                )
                    ? response.data.topics
                    : [];

            clearSelectLoading(
                refs.topic
            );

            populateSelect(
                refs.topic,
                topics,
                refs.topic?.dataset.placeholder
                    || 'Select Topic',
                selectedTopicId,
                function (item) {
                    return item.label
                        || item.name;
                }
            );

            if (!topics.length) {
                setSelectMessage(
                    refs.topic,
                    'No topics available'
                );

                return;
            }

            if (selectedTopicId) {
                refs.topic.value =
                    String(selectedTopicId);

                await loadSubTopics(
                    container,
                    {
                        selectedSubTopicId,
                        signal
                    }
                );
            }

        } catch (error) {
            if (
                error.name === 'AbortError'
            ) {
                return;
            }

            clearSelectLoading(
                refs.topic
            );

            setSelectMessage(
                refs.topic,
                'Unable to load topics'
            );

            showToast(
                error.message
                || 'Gagal mengambil topic.',
                'error'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Load Sub Topics
    |--------------------------------------------------------------------------
    */
    async function loadSubTopics(
        container,
        {
            selectedSubTopicId = '',
            signal = undefined
        } = {}
    ) {
        const refs =
            hierarchyRefs(container);

        const topicId =
            refs.topic?.value || '';

        if (!topicId) {
            setSelectMessage(
                refs.subTopic,
                'Select topic first'
            );

            return;
        }

        setSelectLoading(
            refs.subTopic,
            'Loading sub topics...'
        );

        try {
            const params =
                new URLSearchParams({
                    topic_id:
                        topicId
                });

            const response =
                await fetchJson(
                    `${refs.optionsBaseUrl}/sub-topics?${params.toString()}`,
                    signal
                );

            const subTopics =
                Array.isArray(
                    response
                        ?.data
                        ?.sub_topics
                )
                    ? response.data.sub_topics
                    : [];

            clearSelectLoading(
                refs.subTopic
            );

            populateSelect(
                refs.subTopic,
                subTopics,
                refs.subTopic?.dataset.placeholder
                    || 'No Sub Topic',
                selectedSubTopicId
            );

            if (selectedSubTopicId) {
                refs.subTopic.value =
                    String(
                        selectedSubTopicId
                    );
            }

        } catch (error) {
            if (
                error.name === 'AbortError'
            ) {
                return;
            }

            clearSelectLoading(
                refs.subTopic
            );

            setSelectMessage(
                refs.subTopic,
                'Unable to load sub topics'
            );

            showToast(
                error.message
                || 'Gagal mengambil sub topic.',
                'error'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Hierarchy Events
    |--------------------------------------------------------------------------
    */
    function bindHierarchyEvents(
        container
    ) {
        const refs =
            hierarchyRefs(container);

        if (
            !refs.program
            || !refs.stage
            || !refs.topic
            || !refs.subTopic
        ) {
            return;
        }

        refs.program.addEventListener(
            'change',
            async function () {

                refs.stage.value = '';
                refs.topic.value = '';
                refs.subTopic.value = '';

                await loadProgramStructure(
                    container
                );
            }
        );

        refs.stage.addEventListener(
            'change',
            async function () {

                refs.topic.value = '';
                refs.subTopic.value = '';

                const controller =
                    beginHierarchyRequest(
                        container
                    );

                await loadTopics(
                    container,
                    {
                        signal:
                            controller.signal
                    }
                );
            }
        );

        refs.topic.addEventListener(
            'change',
            async function () {

                refs.subTopic.value = '';

                const controller =
                    beginHierarchyRequest(
                        container
                    );

                await loadSubTopics(
                    container,
                    {
                        signal:
                            controller.signal
                    }
                );
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Restore Filter
    |--------------------------------------------------------------------------
    */
    async function initializeFilterHierarchy() {
        const form =
            document.getElementById(
                'assignmentFilterForm'
            );

        if (!form) {
            return;
        }

        bindHierarchyEvents(form);

        const refs =
            hierarchyRefs(form);

        const programId =
            form.dataset.initialProgramId
            || '';

        if (!programId) {
            setSelectMessage(
                refs.stage,
                'Select program first'
            );

            setSelectMessage(
                refs.topic,
                'Select program first'
            );

            setSelectMessage(
                refs.subTopic,
                'Select topic first'
            );

            return;
        }

        refs.program.value =
            String(programId);

        await loadProgramStructure(
            form,
            {
                selectedStageId:
                    form.dataset.initialStageId
                    || '',

                selectedTopicId:
                    form.dataset.initialTopicId
                    || '',

                selectedSubTopicId:
                    form.dataset.initialSubTopicId
                    || ''
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Quill
    |--------------------------------------------------------------------------
    */
    function initQuill() {
        const editorEl =
            document.getElementById(
                'assignmentInstructionEditor'
            );

        if (
            !editorEl
            || typeof Quill === 'undefined'
        ) {
            return;
        }

        instructionQuill =
            new Quill(
                editorEl,
                {
                    theme:
                        'snow',

                    placeholder:
                        'Tulis instruksi assignment untuk student...',

                    modules: {
                        toolbar: [
                            [
                                {
                                    header:
                                        [2, 3, false]
                                }
                            ],

                            [
                                'bold',
                                'italic',
                                'underline'
                            ],

                            [
                                {
                                    list:
                                        'ordered'
                                },

                                {
                                    list:
                                        'bullet'
                                }
                            ],

                            [
                                'link'
                            ],

                            [
                                'clean'
                            ]
                        ]
                    }
                }
            );
    }

    function getInstructionHtml() {
        if (!instructionQuill) {
            return '';
        }

        let html = '';

        if (
            typeof instructionQuill
                .getSemanticHTML
            === 'function'
        ) {
            html =
                instructionQuill
                    .getSemanticHTML();
        } else {
            html =
                instructionQuill
                    .root
                    .innerHTML;
        }

        html =
            String(html || '')
                .trim();

        const normalized =
            html
                .replace(/\s+/g, '')
                .toLowerCase();

        if (
            normalized === ''
            || normalized === '<p></p>'
            || normalized === '<p><br></p>'
            || normalized === '<p><br/></p>'
        ) {
            return '';
        }

        return html;
    }

    function setInstructionHtml(html) {
        if (!instructionQuill) {
            return;
        }

        instructionQuill
            .setContents([]);

        html =
            String(html || '')
                .trim();

        if (!html) {
            return;
        }

        instructionQuill
            .clipboard
            .dangerouslyPasteHTML(
                0,
                html,
                'silent'
            );
    }

    function syncInstructionInput(form) {
        const input =
            form.querySelector(
                '[name="instruction"]'
            );

        if (!input) {
            return;
        }

        input.value =
            getInstructionHtml();
    }

    function getInstructionFromJsonScript(
        scriptId
    ) {
        if (!scriptId) {
            return '';
        }

        const script =
            document.getElementById(
                scriptId
            );

        if (!script) {
            return '';
        }

        try {
            return JSON.parse(
                script.textContent
                || '""'
            ) || '';
        } catch (error) {
            return '';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Errors
    |--------------------------------------------------------------------------
    */
    function showErrors(
        form,
        errors
    ) {
        const alertBox =
            form.querySelector(
                '.form-alert'
            );

        if (!alertBox) {
            return;
        }

        let messages = [];

        if (
            errors
            && typeof errors
                === 'object'
        ) {
            Object
                .keys(errors)
                .forEach(
                    function (key) {
                        const fieldErrors =
                            errors[key];

                        if (
                            Array.isArray(
                                fieldErrors
                            )
                        ) {
                            fieldErrors
                                .forEach(
                                    function (message) {
                                        messages.push(
                                            `<div>${escapeHtml(message)}</div>`
                                        );
                                    }
                                );
                        } else if (
                            fieldErrors
                        ) {
                            messages.push(
                                `<div>${escapeHtml(fieldErrors)}</div>`
                            );
                        }
                    }
                );
        }

        if (!messages.length) {
            messages = [
                '<div>Terjadi kesalahan. Silakan coba lagi.</div>'
            ];
        }

        alertBox.innerHTML =
            messages.join('');

        alertBox.classList.remove(
            'd-none'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Reset Modal
    |--------------------------------------------------------------------------
    */
    function resetAssignmentForm(
        form
    ) {
        form.reset();

        const refs =
            hierarchyRefs(form);

        form.querySelector(
            'input[name="id"]'
        ).value = '';

        form.querySelector(
            'input[name="_method"]'
        ).value = 'POST';

        form.querySelector(
            '[name="instruction"]'
        ).value = '';

        const alertBox =
            form.querySelector(
                '.form-alert'
            );

        if (alertBox) {
            alertBox.classList.add(
                'd-none'
            );

            alertBox.innerHTML = '';
        }

        setInstructionHtml('');

        setStageVisibility(
            refs,
            true,
            false
        );

        setSelectMessage(
            refs.stage,
            'Select program first'
        );

        setSelectMessage(
            refs.topic,
            'Select program first'
        );

        setSelectMessage(
            refs.subTopic,
            'Select topic first'
        );

        form.dataset.programMode = '';

        form.querySelector(
            'select[name="assignment_type"]'
        ).value = 'mixed';

        form.querySelector(
            'input[name="max_score"]'
        ).value = 100;

        form.querySelector(
            'select[name="is_required"]'
        ).value = '1';

        form.querySelector(
            'input[name="sort_order"]'
        ).value = 1;

        form.querySelector(
            'select[name="status"]'
        ).value = 'draft';

        form.querySelector(
            'select[name="is_active"]'
        ).value = '1';
    }

    /*
    |--------------------------------------------------------------------------
    | Assignment Modal
    |--------------------------------------------------------------------------
    */
    function setupAssignmentModal() {
        const modalEl =
            document.getElementById(
                'assignmentModal'
            );

        const form =
            document.getElementById(
                'assignmentForm'
            );

        if (
            !modalEl
            || !form
        ) {
            return;
        }

        bindHierarchyEvents(form);

        modalEl.addEventListener(
            'show.bs.modal',
            function (event) {

                resetAssignmentForm(
                    form
                );

                const button =
                    event.relatedTarget;

                const mode =
                    button?.dataset?.mode
                    || 'create';

                const title =
                    modalEl.querySelector(
                        '#assignmentModalLabel'
                    );

                const subtitle =
                    modalEl.querySelector(
                        '#assignmentModalSubtitle'
                    );

                const submitBtn =
                    form.querySelector(
                        '.submit-btn'
                    );

                if (mode === 'edit') {

                    title.textContent =
                        'Edit Assignment';

                    subtitle.textContent =
                        'Perbarui data assignment.';

                    submitBtn.textContent =
                        'Update Assignment';

                    form.querySelector(
                        'input[name="id"]'
                    ).value =
                        button.dataset.id
                        || '';

                    form.querySelector(
                        'input[name="_method"]'
                    ).value =
                        'PUT';

                    form.querySelector(
                        'input[name="title"]'
                    ).value =
                        button.dataset.title
                        || '';

                    form.querySelector(
                        'select[name="assignment_type"]'
                    ).value =
                        button.dataset.assignmentType
                        || 'mixed';

                    form.querySelector(
                        'input[name="attachment_url"]'
                    ).value =
                        button.dataset.attachmentUrl
                        || '';

                    form.querySelector(
                        'input[name="starter_file_url"]'
                    ).value =
                        button.dataset.starterFileUrl
                        || '';

                    form.querySelector(
                        'input[name="reference_url"]'
                    ).value =
                        button.dataset.referenceUrl
                        || '';

                    form.querySelector(
                        'input[name="max_score"]'
                    ).value =
                        button.dataset.maxScore
                        || 100;

                    form.querySelector(
                        'select[name="is_required"]'
                    ).value =
                        button.dataset.isRequired
                        || '1';

                    form.querySelector(
                        'input[name="sort_order"]'
                    ).value =
                        button.dataset.sortOrder
                        || 1;

                    form.querySelector(
                        'select[name="status"]'
                    ).value =
                        button.dataset.status
                        || 'draft';

                    form.querySelector(
                        'select[name="is_active"]'
                    ).value =
                        button.dataset.isActive
                        || '1';

                    const instructionHtml =
                        getInstructionFromJsonScript(
                            button.dataset.instructionJsonId
                        );

                    setInstructionHtml(
                        instructionHtml
                    );

                    form.querySelector(
                        '[name="instruction"]'
                    ).value =
                        instructionHtml;

                    const refs =
                        hierarchyRefs(form);

                    refs.program.value =
                        button.dataset.programId
                        || '';

                    loadProgramStructure(
                        form,
                        {
                            selectedStageId:
                                button.dataset.stageId
                                || '',

                            selectedTopicId:
                                button.dataset.topicId
                                || '',

                            selectedSubTopicId:
                                button.dataset.subTopicId
                                || ''
                        }
                    );

                    return;
                }

                title.textContent =
                    'Add Assignment';

                subtitle.textContent =
                    'Tentukan program dan topic untuk assignment.';

                submitBtn.textContent =
                    'Save Assignment';
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Submit
        |--------------------------------------------------------------------------
        */
        form.addEventListener(
            'submit',
            async function (event) {

                event.preventDefault();

                const refs =
                    hierarchyRefs(form);

                const mode =
                    form.querySelector(
                        'input[name="_method"]'
                    )?.value
                    || 'POST';

                /*
                |--------------------------------------------------------------------------
                | Required hierarchy
                |--------------------------------------------------------------------------
                */
                if (!refs.program.value) {
                    showErrors(
                        form,
                        {
                            program_id: [
                                'Pilih program terlebih dahulu.'
                            ]
                        }
                    );

                    return;
                }

                if (
                    refs.stage.required
                    && !refs.stage.value
                ) {
                    showErrors(
                        form,
                        {
                            stage_id: [
                                'Pilih stage terlebih dahulu.'
                            ]
                        }
                    );

                    return;
                }

                if (!refs.topic.value) {
                    showErrors(
                        form,
                        {
                            topic_id: [
                                'Pilih topic untuk assignment ini.'
                            ]
                        }
                    );

                    return;
                }

                syncInstructionInput(
                    form
                );

                const submitBtn =
                    form.querySelector(
                        '.submit-btn'
                    );

                const id =
                    form.querySelector(
                        'input[name="id"]'
                    )?.value
                    || '';

                const createUrl =
                    form.dataset.createUrl;

                const actionUrl =
                    mode === 'PUT'
                        ? `{{ route('assignments.update', ['assignment' => '__ID__']) }}`.replace('__ID__', id)
                        : createUrl;

                const formData =
                    new FormData(form);

                if (mode === 'PUT') {
                    formData.set(
                        '_method',
                        'PUT'
                    );
                }

                const alertBox =
                    form.querySelector(
                        '.form-alert'
                    );

                if (alertBox) {
                    alertBox.classList.add(
                        'd-none'
                    );

                    alertBox.innerHTML = '';
                }

                submitBtn.disabled =
                    true;

                submitBtn.textContent =
                    'Saving...';

                try {

                    const response =
                        await fetch(
                            actionUrl,
                            {
                                method: 'POST',

                                headers: {
                                    'Accept':
                                        'application/json',

                                    'X-Requested-With':
                                        'XMLHttpRequest',
                                },

                                body:
                                    formData,

                                credentials:
                                    'same-origin',
                            }
                        );

                    const data =
                        await response
                            .json()
                            .catch(
                                () => ({})
                            );

                    if (!response.ok) {

                        if (
                            response.status
                            === 422
                        ) {
                            showErrors(
                                form,
                                data.errors
                                || {}
                            );

                            showToast(
                                'Mohon cek kembali input yang wajib diisi.',
                                'error'
                            );
                        } else {
                            showErrors(
                                form,
                                {
                                    general: [
                                        data.message
                                        || 'Terjadi kesalahan pada server.'
                                    ]
                                }
                            );

                            showToast(
                                data.message
                                || 'Gagal menyimpan assignment.',
                                'error'
                            );
                        }

                        submitBtn.disabled =
                            false;

                        submitBtn.textContent =
                            mode === 'PUT'
                                ? 'Update Assignment'
                                : 'Save Assignment';

                        return;
                    }

                    bootstrap.Modal
                        .getInstance(
                            modalEl
                        )
                        ?.hide();

                    showToast(
                        data.message
                        || 'Assignment berhasil disimpan.',
                        'success'
                    );

                    setTimeout(
                        function () {
                            window.location.reload();
                        },
                        800
                    );

                } catch (error) {

                    showErrors(
                        form,
                        {
                            general: [
                                'Gagal menghubungi server.'
                            ]
                        }
                    );

                    showToast(
                        'Gagal menghubungi server.',
                        'error'
                    );

                    submitBtn.disabled =
                        false;

                    submitBtn.textContent =
                        mode === 'PUT'
                            ? 'Update Assignment'
                            : 'Save Assignment';
                }
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Delete
    |--------------------------------------------------------------------------
    */
    function setupDeleteConfirmModal() {
        const modalEl =
            document.getElementById(
                'deleteConfirmModal'
            );

        const confirmBtn =
            document.getElementById(
                'confirmDeleteBtn'
            );

        if (
            !modalEl
            || !confirmBtn
        ) {
            return;
        }

        modalEl.addEventListener(
            'show.bs.modal',
            function (event) {

                const button =
                    event.relatedTarget;

                modalEl.dataset.deleteUrl =
                    button?.dataset?.deleteUrl
                    || '';

                modalEl.querySelector(
                    '#deleteConfirmName'
                ).textContent =
                    button?.dataset?.deleteName
                    || '-';

                confirmBtn.disabled =
                    false;

                confirmBtn.innerHTML =
                    '<i class="bi bi-trash me-2"></i>Delete';
            }
        );

        confirmBtn.addEventListener(
            'click',
            async function () {

                const deleteUrl =
                    modalEl.dataset.deleteUrl;

                if (!deleteUrl) {
                    return;
                }

                confirmBtn.disabled =
                    true;

                confirmBtn.textContent =
                    'Deleting...';

                const formData =
                    new FormData();

                formData.append(
                    '_token',
                    csrfToken
                );

                formData.append(
                    '_method',
                    'DELETE'
                );

                try {

                    const response =
                        await fetch(
                            deleteUrl,
                            {
                                method:
                                    'POST',

                                headers: {
                                    'Accept':
                                        'application/json',

                                    'X-Requested-With':
                                        'XMLHttpRequest',
                                },

                                body:
                                    formData,

                                credentials:
                                    'same-origin',
                            }
                        );

                    const data =
                        await response
                            .json()
                            .catch(
                                () => ({})
                            );

                    if (!response.ok) {

                        showToast(
                            data.message
                            || 'Gagal menghapus assignment.',
                            'error'
                        );

                        confirmBtn.disabled =
                            false;

                        confirmBtn.innerHTML =
                            '<i class="bi bi-trash me-2"></i>Delete';

                        return;
                    }

                    bootstrap.Modal
                        .getInstance(
                            modalEl
                        )
                        ?.hide();

                    showToast(
                        data.message
                        || 'Assignment berhasil dihapus.',
                        'success'
                    );

                    setTimeout(
                        function () {
                            window.location.reload();
                        },
                        800
                    );

                } catch (error) {

                    showToast(
                        'Gagal menghubungi server.',
                        'error'
                    );

                    confirmBtn.disabled =
                        false;

                    confirmBtn.innerHTML =
                        '<i class="bi bi-trash me-2"></i>Delete';
                }
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Init
    |--------------------------------------------------------------------------
    */
    initQuill();

    initializeFilterHierarchy();

    setupAssignmentModal();

    setupDeleteConfirmModal();

});
</script>

@endpush