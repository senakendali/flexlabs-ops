@extends('layouts.app-dashboard')

@section('title', 'Learning Quizzes')

@section('content')
<div class="container-fluid px-4 py-4">

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Academic Management</div>
                <h1 class="page-title mb-2">Learning Quizzes</h1>
                <p class="page-subtitle mb-0">
                    Kelola quiz pembelajaran yang terhubung ke <strong>Topic</strong> atau <strong>Sub Topic</strong>.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <button
                    type="button"
                    class="btn btn-primary btn-modern"
                    data-bs-toggle="modal"
                    data-bs-target="#learningQuizModal"
                    data-mode="create"
                >
                    <i class="bi bi-plus-circle me-2"></i>Add Learning Quiz
                </button>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-patch-question-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Total</div>
                        <div class="stat-value">{{ $stats['total'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Total learning quiz di sistem.</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-send-check"></i>
                    </div>
                    <div>
                        <div class="stat-title">Published</div>
                        <div class="stat-value">{{ $stats['published'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Quiz yang siap digunakan.</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-pencil-square"></i>
                    </div>
                    <div>
                        <div class="stat-title">Draft</div>
                        <div class="stat-value">{{ $stats['draft'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Quiz yang masih disiapkan.</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-archive-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Archived</div>
                        <div class="stat-value">{{ $stats['archived'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Quiz yang sudah diarsipkan.</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-toggle-on"></i>
                    </div>
                    <div>
                        <div class="stat-title">Active</div>
                        <div class="stat-value">{{ $stats['active'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Quiz aktif di sistem.</div>
            </div>
        </div>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Filter Learning Quizzes</h5>
                <p class="content-card-subtitle mb-0">
                    Cari quiz berdasarkan topic, sub topic, tipe quiz, atau status.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            <form method="GET" action="{{ route('learning-quizzes.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-xl-3 col-md-6">
                        <label class="form-label">Keyword</label>
                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            value="{{ request('search') }}"
                            placeholder="Cari title / instruction..."
                        >
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <label class="form-label">Topic</label>
                        <select name="topic_id" class="form-select">
                            <option value="">All Topics</option>
                            @foreach($topics ?? [] as $topic)
                                <option value="{{ $topic->id }}" {{ request('topic_id') == $topic->id ? 'selected' : '' }}>
                                    {{ $topic->module->stage->program->name ?? 'Program' }} -
                                    {{ $topic->module->name ?? 'Module' }} -
                                    {{ $topic->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-2 col-md-6">
                        <label class="form-label">Quiz Type</label>
                        <select name="quiz_type" class="form-select">
                            <option value="">All Types</option>
                            @foreach($quizTypes ?? [] as $key => $label)
                                <option value="{{ $key }}" {{ request('quiz_type') === $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-2 col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            @foreach($statuses ?? [] as $key => $label)
                                <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-2">
                        <div class="d-flex gap-2 justify-content-xl-end flex-wrap">
                            <a href="{{ route('learning-quizzes.index') }}" class="btn btn-outline-secondary btn-modern">
                                <i class="bi bi-arrow-counterclockwise me-2"></i>Reset
                            </a>
                            <button type="submit" class="btn btn-primary btn-modern">
                                <i class="bi bi-funnel me-2"></i>Filter
                            </button>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Sub Topic</label>
                        <select name="sub_topic_id" class="form-select">
                            <option value="">All Sub Topics</option>
                            @foreach($subTopics ?? [] as $subTopic)
                                <option value="{{ $subTopic->id }}" {{ request('sub_topic_id') == $subTopic->id ? 'selected' : '' }}>
                                    {{ $subTopic->topic->module->stage->program->name ?? 'Program' }} -
                                    {{ $subTopic->topic->name ?? 'Topic' }} -
                                    {{ $subTopic->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="content-card">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Learning Quiz List</h5>
                <p class="content-card-subtitle mb-0">
                    Quiz ini masih berupa master/template. Nanti bisa diberikan ke batch melalui Batch Learning Quiz.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            @if(($learningQuizzes ?? collect())->count())
                <div class="entity-list">
                    @foreach($learningQuizzes as $quiz)
                        @php
                            $statusClass = match($quiz->status) {
                                'published' => 'ui-badge-success',
                                'archived' => 'ui-badge-danger',
                                default => 'ui-badge-muted',
                            };

                            $typeClass = match($quiz->quiz_type) {
                                'practice' => 'ui-badge-info',
                                'graded' => 'ui-badge-primary',
                                default => 'ui-badge-muted',
                            };

                            $targetLabel = $quiz->subTopic
                                ? 'Sub Topic: ' . $quiz->subTopic->name
                                : 'Topic: ' . ($quiz->topic->name ?? '-');

                            $instructionHtml = $quiz->instruction ?? '';
                            $hasInstruction = trim(strip_tags($instructionHtml)) !== '';
                        @endphp

                        <script type="application/json" id="learning-quiz-instruction-{{ $quiz->id }}">
                            @json($quiz->instruction)
                        </script>

                        <div class="entity-card">
                            <div class="entity-main">
                                <div class="entity-icon">
                                    <i class="bi bi-patch-question-fill"></i>
                                </div>

                                <div class="entity-info">
                                    <div class="entity-title-row">
                                        <div>
                                            <h5 class="entity-title">{{ $quiz->title }}</h5>
                                            <div class="entity-subtitle">
                                                {{ $targetLabel }}
                                            </div>
                                        </div>

                                        <div class="entity-badges">
                                            <span class="ui-badge {{ $typeClass }}">
                                                {{ $quiz->quiz_type_label }}
                                            </span>

                                            <span class="ui-badge {{ $statusClass }}">
                                                {{ $quiz->status_label }}
                                            </span>

                                            @if($quiz->is_active)
                                                <span class="ui-badge ui-badge-success">Active</span>
                                            @else
                                                <span class="ui-badge ui-badge-muted">Inactive</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="entity-meta">
                                        @if($quiz->topic?->module)
                                            <span>
                                                <i class="bi bi-folder2-open me-1"></i>{{ $quiz->topic->module->name }}
                                            </span>
                                        @endif

                                        <span>
                                            <i class="bi bi-list-check me-1"></i>{{ $quiz->questions_count ?? 0 }} Questions
                                        </span>

                                        <span>
                                            <i class="bi bi-people me-1"></i>{{ $quiz->batch_learning_quizzes_count ?? 0 }} Batch Quizzes
                                        </span>

                                        <span>
                                            <i class="bi bi-activity me-1"></i>{{ $quiz->attempts_count ?? 0 }} Attempts
                                        </span>
                                    </div>

                                    <div class="info-grid">
                                        <div class="info-item">
                                            <div class="info-label">Duration</div>
                                            <div class="info-value">
                                                @if($quiz->duration_minutes)
                                                    {{ $quiz->duration_minutes }} minutes
                                                @else
                                                    <span class="text-muted">No limit</span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="info-item">
                                            <div class="info-label">Passing Score</div>
                                            <div class="info-value">{{ $quiz->passing_score }}%</div>
                                        </div>

                                        <div class="info-item">
                                            <div class="info-label">Max Attempts</div>
                                            <div class="info-value">{{ $quiz->max_attempts }}x</div>
                                        </div>
                                    </div>

                                    @if($hasInstruction)
                                        <div class="richtext-preview">
                                            <div class="richtext-content ql-editor">
                                                {!! $instructionHtml !!}
                                            </div>
                                        </div>
                                    @endif

                                    <div class="entity-meta">
                                        <span>
                                            <i class="bi bi-shuffle me-1"></i>
                                            Questions: {{ $quiz->randomize_questions ? 'Random' : 'Ordered' }}
                                        </span>

                                        <span>
                                            <i class="bi bi-ui-checks-grid me-1"></i>
                                            Options: {{ $quiz->randomize_options ? 'Random' : 'Ordered' }}
                                        </span>

                                        <span>
                                            <i class="bi bi-eye me-1"></i>
                                            Result: {{ $quiz->show_result_after_submit ? 'Shown' : 'Hidden' }}
                                        </span>

                                        <span>
                                            <i class="bi bi-check-circle me-1"></i>
                                            Correct Answer: {{ $quiz->show_correct_answer_after_submit ? 'Shown' : 'Hidden' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="entity-actions">
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#learningQuizModal"
                                    data-mode="edit"
                                    data-id="{{ $quiz->id }}"
                                    data-topic-id="{{ $quiz->topic_id }}"
                                    data-sub-topic-id="{{ $quiz->sub_topic_id }}"
                                    data-title="{{ $quiz->title }}"
                                    data-instruction-json-id="learning-quiz-instruction-{{ $quiz->id }}"
                                    data-quiz-type="{{ $quiz->quiz_type }}"
                                    data-duration-minutes="{{ $quiz->duration_minutes }}"
                                    data-passing-score="{{ $quiz->passing_score }}"
                                    data-max-attempts="{{ $quiz->max_attempts }}"
                                    data-randomize-questions="{{ (int) $quiz->randomize_questions }}"
                                    data-randomize-options="{{ (int) $quiz->randomize_options }}"
                                    data-show-result-after-submit="{{ (int) $quiz->show_result_after_submit }}"
                                    data-show-correct-answer-after-submit="{{ (int) $quiz->show_correct_answer_after_submit }}"
                                    data-status="{{ $quiz->status }}"
                                    data-is-active="{{ (int) $quiz->is_active }}"
                                >
                                    <i class="bi bi-pencil-square me-1"></i>Edit
                                </button>

                                <a
                                    href="{{ route('learning-quizzes.questions.index', $quiz->id) }}"
                                    class="btn btn-outline-primary btn-sm"
                                >
                                    <i class="bi bi-list-check me-1"></i>Questions
                                </a>

                                <button
                                    type="button"
                                    class="btn btn-outline-danger btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#deleteConfirmModal"
                                    data-delete-name="{{ $quiz->title }}"
                                    data-delete-url="{{ route('learning-quizzes.destroy', $quiz->id) }}"
                                >
                                    <i class="bi bi-trash me-1"></i>Delete
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4">
                    {{ $learningQuizzes->links() }}
                </div>
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-patch-question"></i>
                    </div>
                    <h5 class="empty-state-title">Learning quiz belum tersedia</h5>
                    <p class="empty-state-text mb-3">
                        Belum ada learning quiz yang dibuat. Mulai dengan membuat quiz untuk topic atau sub topic tertentu.
                    </p>
                    <button
                        type="button"
                        class="btn btn-primary btn-modern"
                        data-bs-toggle="modal"
                        data-bs-target="#learningQuizModal"
                        data-mode="create"
                    >
                        <i class="bi bi-plus-circle me-2"></i>Add First Learning Quiz
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="modal fade" id="learningQuizModal" tabindex="-1" aria-labelledby="learningQuizModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content custom-modal">
            <form id="learningQuizForm" data-create-url="{{ route('learning-quizzes.store') }}">
                @csrf
                <input type="hidden" name="_method" value="POST">
                <input type="hidden" name="id" value="">
                <input type="hidden" name="instruction" value="">

                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title" id="learningQuizModalLabel">Add Learning Quiz</h5>
                        <p class="text-muted mb-0" id="learningQuizModalSubtitle">
                            Buat quiz pembelajaran yang terhubung ke topic atau sub topic.
                        </p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-4">
                    <div class="alert alert-danger d-none form-alert" role="alert"></div>

                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label">Topic</label>
                            <select name="topic_id" class="form-select learning-quiz-topic-select">
                                <option value="">Select Topic</option>
                                @foreach($topics ?? [] as $topic)
                                    <option value="{{ $topic->id }}">
                                        {{ $topic->module->stage->program->name ?? 'Program' }} -
                                        {{ $topic->module->name ?? 'Module' }} -
                                        {{ $topic->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">
                                Pilih topic jika quiz berlaku untuk satu topic penuh.
                            </div>
                        </div>

                        <div class="col-md-5">
                            <label class="form-label">Sub Topic</label>
                            <select name="sub_topic_id" class="form-select learning-quiz-sub-topic-select">
                                <option value="">No Sub Topic</option>
                                @foreach($subTopics ?? [] as $subTopic)
                                    <option
                                        value="{{ $subTopic->id }}"
                                        data-topic-id="{{ $subTopic->topic_id }}"
                                    >
                                        {{ $subTopic->topic->name ?? 'Topic' }} - {{ $subTopic->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">
                                Optional. Kalau dipilih, quiz akan lebih spesifik ke sub topic.
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Quiz Title</label>
                            <input
                                type="text"
                                name="title"
                                class="form-control"
                                placeholder="Contoh: Quiz Product Flow Review"
                                required
                            >
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Quiz Type</label>
                            <select name="quiz_type" class="form-select" required>
                                @foreach($quizTypes ?? [] as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Duration Minutes</label>
                            <input
                                type="number"
                                name="duration_minutes"
                                class="form-control"
                                min="1"
                                max="9999"
                                placeholder="Kosongkan jika tanpa batas waktu"
                            >
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Passing Score (%)</label>
                            <input
                                type="number"
                                name="passing_score"
                                class="form-control"
                                min="0"
                                max="100"
                                value="70"
                                required
                            >
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Max Attempts</label>
                            <input
                                type="number"
                                name="max_attempts"
                                class="form-control"
                                min="1"
                                max="99"
                                value="1"
                                required
                            >
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                @foreach($statuses ?? [] as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Active Status</label>
                            <select name="is_active" class="form-select" required>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <div class="quill-block">
                                <label class="form-label">Instruction</label>

                                <div class="quill-shell">
                                    <div id="learningQuizInstructionEditor" class="quill-editor-fixed"></div>
                                </div>

                                <div class="form-text mt-2">
                                    Tulis instruksi quiz untuk student. Bisa berisi aturan pengerjaan, durasi, dan ketentuan nilai.
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="soft-panel">
                                <div class="soft-panel-header">
                                    <div class="soft-panel-title">
                                        <i class="bi bi-gear-fill me-2"></i>Quiz Behavior
                                    </div>
                                    <div class="soft-panel-subtitle">
                                        Atur bagaimana quiz ditampilkan dan hasilnya diperlihatkan ke student.
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Randomize Questions</label>
                                        <select name="randomize_questions" class="form-select" required>
                                            <option value="0">No</option>
                                            <option value="1">Yes</option>
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">Randomize Options</label>
                                        <select name="randomize_options" class="form-select" required>
                                            <option value="0">No</option>
                                            <option value="1">Yes</option>
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">Show Result</label>
                                        <select name="show_result_after_submit" class="form-select" required>
                                            <option value="1">Yes</option>
                                            <option value="0">No</option>
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">Show Correct Answer</label>
                                        <select name="show_correct_answer_after_submit" class="form-select" required>
                                            <option value="0">No</option>
                                            <option value="1">Yes</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="help-card soft-panel">
                                <div class="help-icon">
                                    <i class="bi bi-info-circle"></i>
                                </div>
                                <div>
                                    <div class="help-title">Catatan</div>
                                    <div class="help-text">
                                        Learning Quiz ini masih master quiz. Setelah quiz dibuat, step berikutnya adalah mengelola
                                        questions/options, lalu memberikan quiz ke batch melalui Batch Learning Quiz.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary btn-modern submit-btn">
                        Save Learning Quiz
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal delete-confirm-modal">
            <div class="modal-header border-0 pb-0">
                <div class="delete-confirm-heading">
                    <div class="delete-confirm-icon">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div>
                        <h5 class="modal-title" id="deleteConfirmModalLabel">Delete Learning Quiz</h5>
                        <p class="text-muted mb-0">
                            Konfirmasi sebelum menghapus learning quiz.
                        </p>
                    </div>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pt-4">
                <div class="delete-confirm-message">
                    <div class="delete-confirm-label">Quiz yang akan dihapus</div>
                    <div class="delete-confirm-name" id="deleteConfirmName">-</div>
                </div>

                <div class="delete-confirm-warning mt-3">
                    Learning quiz yang sudah dihapus tidak bisa dikembalikan.
                    Questions, options, batch quizzes, attempts, dan answers yang terkait juga bisa ikut terhapus sesuai relasi database.
                </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                    Cancel
                </button>

                <button type="button" class="btn btn-danger btn-modern" id="confirmDeleteBtn">
                    <i class="bi bi-trash me-2"></i>Delete
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken =
        document.querySelector('#learningQuizForm input[name="_token"]')?.value
        || '{{ csrf_token() }}';

    let instructionQuill = null;

    function showToast(message, type = 'success') {
        const toastEl = document.getElementById('appToast');
        if (!toastEl) return;

        const toastBody = toastEl.querySelector('.toast-body');
        toastBody.innerHTML = message;

        toastEl.classList.remove('bg-success', 'bg-danger', 'text-white');

        if (type === 'success') {
            toastEl.classList.add('bg-success', 'text-white');
        } else {
            toastEl.classList.add('bg-danger', 'text-white');
        }

        const toast = bootstrap.Toast.getOrCreateInstance(toastEl, {
            delay: 2500
        });

        toast.show();
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function resetFormState(form) {
        form.reset();

        const idInput = form.querySelector('input[name="id"]');
        const methodInput = form.querySelector('input[name="_method"]');
        const alertBox = form.querySelector('.form-alert');
        const submitBtn = form.querySelector('.submit-btn');
        const instructionInput = form.querySelector('input[name="instruction"]');

        if (idInput) idInput.value = '';
        if (methodInput) methodInput.value = 'POST';
        if (instructionInput) instructionInput.value = '';

        if (instructionQuill) {
            instructionQuill.setText('');
        }

        if (alertBox) {
            alertBox.classList.add('d-none');
            alertBox.innerHTML = '';
        }

        if (submitBtn) {
            if (!submitBtn.dataset.defaultText) {
                submitBtn.dataset.defaultText = submitBtn.innerHTML;
            }
            submitBtn.disabled = false;
            submitBtn.innerHTML = submitBtn.dataset.defaultText;
        }
    }

    function showErrors(form, errors) {
        const alertBox = form.querySelector('.form-alert');
        if (!alertBox) return;

        let messages = [];

        if (errors && typeof errors === 'object') {
            Object.keys(errors).forEach(function (key) {
                const fieldErrors = errors[key];

                if (Array.isArray(fieldErrors)) {
                    fieldErrors.forEach(function (message) {
                        messages.push(`<div>${escapeHtml(message)}</div>`);
                    });
                } else if (fieldErrors) {
                    messages.push(`<div>${escapeHtml(fieldErrors)}</div>`);
                }
            });
        }

        if (!messages.length) {
            messages = ['<div>Terjadi kesalahan. Silakan coba lagi.</div>'];
        }

        alertBox.innerHTML = messages.join('');
        alertBox.classList.remove('d-none');
    }

    function initQuill() {
        const editorEl = document.getElementById('learningQuizInstructionEditor');

        if (!editorEl || typeof Quill === 'undefined') return;

        instructionQuill = new Quill(editorEl, {
            theme: 'snow',
            placeholder: 'Tulis instruksi quiz untuk student...',
            modules: {
                toolbar: [
                    [{ header: [2, 3, false] }],
                    ['bold', 'italic', 'underline'],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    ['link'],
                    ['clean']
                ]
            }
        });
    }

    function getInstructionHtml() {
        if (!instructionQuill) return '';

        const html = instructionQuill.root.innerHTML.trim();

        if (html === '<p><br></p>') {
            return '';
        }

        return html;
    }

    function setInstructionHtml(html) {
        if (!instructionQuill) return;

        instructionQuill.clipboard.dangerouslyPasteHTML(html || '');
    }

    function getInstructionFromJsonScript(scriptId) {
        if (!scriptId) return '';

        const script = document.getElementById(scriptId);
        if (!script) return '';

        try {
            return JSON.parse(script.textContent || '""') || '';
        } catch (error) {
            return '';
        }
    }

    function filterSubTopics(form) {
        const topicSelect = form.querySelector('select[name="topic_id"]');
        const subTopicSelect = form.querySelector('select[name="sub_topic_id"]');

        if (!topicSelect || !subTopicSelect) return;

        const selectedTopicId = topicSelect.value;

        [...subTopicSelect.options].forEach(function (option) {
            if (!option.value) {
                option.hidden = false;
                return;
            }

            const optionTopicId = option.dataset.topicId;

            option.hidden = selectedTopicId && optionTopicId !== selectedTopicId;
        });

        const selectedOption = subTopicSelect.options[subTopicSelect.selectedIndex];

        if (selectedOption && selectedOption.hidden) {
            subTopicSelect.value = '';
        }
    }

    function setupLearningQuizModal() {
        const modalEl = document.getElementById('learningQuizModal');
        const form = document.getElementById('learningQuizForm');

        if (!modalEl || !form) return;

        const topicSelect = form.querySelector('select[name="topic_id"]');
        const subTopicSelect = form.querySelector('select[name="sub_topic_id"]');

        if (topicSelect) {
            topicSelect.addEventListener('change', function () {
                filterSubTopics(form);
            });
        }

        if (subTopicSelect) {
            subTopicSelect.addEventListener('change', function () {
                const selectedOption = subTopicSelect.options[subTopicSelect.selectedIndex];

                if (selectedOption?.dataset?.topicId) {
                    topicSelect.value = selectedOption.dataset.topicId;
                    filterSubTopics(form);
                }
            });
        }

        modalEl.addEventListener('show.bs.modal', function (event) {
            resetFormState(form);

            const button = event.relatedTarget;
            const mode = button?.dataset?.mode || 'create';

            const title = modalEl.querySelector('#learningQuizModalLabel');
            const subtitle = modalEl.querySelector('#learningQuizModalSubtitle');
            const submitBtn = form.querySelector('.submit-btn');

            if (mode === 'edit') {
                title.textContent = 'Edit Learning Quiz';
                subtitle.textContent = 'Perbarui data learning quiz.';
                submitBtn.innerHTML = 'Update Learning Quiz';
                submitBtn.dataset.defaultText = 'Update Learning Quiz';

                form.querySelector('input[name="id"]').value = button.dataset.id || '';
                form.querySelector('input[name="_method"]').value = 'PUT';

                form.querySelector('select[name="topic_id"]').value = button.dataset.topicId || '';
                form.querySelector('select[name="sub_topic_id"]').value = button.dataset.subTopicId || '';
                form.querySelector('input[name="title"]').value = button.dataset.title || '';

                const instructionHtml = getInstructionFromJsonScript(button.dataset.instructionJsonId);
                setInstructionHtml(instructionHtml);
                form.querySelector('input[name="instruction"]').value = instructionHtml;

                form.querySelector('select[name="quiz_type"]').value = button.dataset.quizType || 'graded';
                form.querySelector('input[name="duration_minutes"]').value = button.dataset.durationMinutes || '';
                form.querySelector('input[name="passing_score"]').value = button.dataset.passingScore || 70;
                form.querySelector('input[name="max_attempts"]').value = button.dataset.maxAttempts || 1;

                form.querySelector('select[name="randomize_questions"]').value = button.dataset.randomizeQuestions || '0';
                form.querySelector('select[name="randomize_options"]').value = button.dataset.randomizeOptions || '0';
                form.querySelector('select[name="show_result_after_submit"]').value = button.dataset.showResultAfterSubmit || '1';
                form.querySelector('select[name="show_correct_answer_after_submit"]').value = button.dataset.showCorrectAnswerAfterSubmit || '0';

                form.querySelector('select[name="status"]').value = button.dataset.status || 'draft';
                form.querySelector('select[name="is_active"]').value = button.dataset.isActive || '1';
            } else {
                title.textContent = 'Add Learning Quiz';
                subtitle.textContent = 'Buat quiz pembelajaran yang terhubung ke topic atau sub topic.';
                submitBtn.innerHTML = 'Save Learning Quiz';
                submitBtn.dataset.defaultText = 'Save Learning Quiz';

                form.querySelector('select[name="quiz_type"]').value = 'graded';
                form.querySelector('input[name="duration_minutes"]').value = '';
                form.querySelector('input[name="passing_score"]').value = 70;
                form.querySelector('input[name="max_attempts"]').value = 1;

                form.querySelector('select[name="randomize_questions"]').value = '0';
                form.querySelector('select[name="randomize_options"]').value = '0';
                form.querySelector('select[name="show_result_after_submit"]').value = '1';
                form.querySelector('select[name="show_correct_answer_after_submit"]').value = '0';

                form.querySelector('select[name="status"]').value = 'draft';
                form.querySelector('select[name="is_active"]').value = '1';

                setInstructionHtml('');
            }

            filterSubTopics(form);
        });

        modalEl.addEventListener('shown.bs.modal', function () {
            if (instructionQuill) {
                instructionQuill.focus();
            }
        });

        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            const submitBtn = form.querySelector('.submit-btn');
            const id = form.querySelector('input[name="id"]')?.value || '';
            const method = form.querySelector('input[name="_method"]')?.value || 'POST';
            const createUrl = form.dataset.createUrl;
            const actionUrl = method === 'PUT'
                ? `{{ route('learning-quizzes.update', ['learningQuiz' => '__ID__']) }}`.replace('__ID__', id)
                : createUrl;

            const instructionHtml = getInstructionHtml();
            form.querySelector('input[name="instruction"]').value = instructionHtml;

            const formData = new FormData(form);

            const alertBox = form.querySelector('.form-alert');
            if (alertBox) {
                alertBox.classList.add('d-none');
                alertBox.innerHTML = '';
            }

            if (submitBtn) {
                if (!submitBtn.dataset.defaultText) {
                    submitBtn.dataset.defaultText = submitBtn.innerHTML;
                }
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'Saving...';
            }

            if (method === 'PUT') {
                formData.set('_method', 'PUT');
            }

            try {
                const response = await fetch(actionUrl, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: formData,
                    credentials: 'same-origin',
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    if (response.status === 422) {
                        showErrors(form, data.errors || {});
                        showToast('Mohon cek kembali input yang wajib diisi.', 'error');
                    } else {
                        showErrors(form, { general: [data.message || 'Terjadi kesalahan pada server.'] });
                        showToast(data.message || 'Gagal menyimpan data.', 'error');
                    }

                    submitBtn.disabled = false;
                    submitBtn.innerHTML = submitBtn.dataset.defaultText;
                    return;
                }

                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) {
                    modalInstance.hide();
                }

                showToast(data.message || 'Learning quiz berhasil disimpan.', 'success');

                setTimeout(() => {
                    window.location.reload();
                }, 900);
            } catch (error) {
                showErrors(form, { general: ['Gagal menghubungi server.'] });
                showToast('Gagal menghubungi server.', 'error');

                submitBtn.disabled = false;
                submitBtn.innerHTML = submitBtn.dataset.defaultText;
            }
        });
    }

    function setupDeleteConfirmModal() {
        const modalEl = document.getElementById('deleteConfirmModal');
        const confirmBtn = document.getElementById('confirmDeleteBtn');

        if (!modalEl || !confirmBtn) return;

        modalEl.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;

            const deleteName = button?.dataset?.deleteName || '-';
            const deleteUrl = button?.dataset?.deleteUrl || '';

            modalEl.dataset.deleteUrl = deleteUrl;

            modalEl.querySelector('#deleteConfirmName').textContent = deleteName;

            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<i class="bi bi-trash me-2"></i>Delete';
        });

        confirmBtn.addEventListener('click', async function () {
            const deleteUrl = modalEl.dataset.deleteUrl;

            if (!deleteUrl) {
                showToast('Route delete belum tersedia.', 'error');
                return;
            }

            confirmBtn.disabled = true;
            confirmBtn.innerHTML = 'Deleting...';

            const formData = new FormData();
            formData.append('_token', csrfToken);
            formData.append('_method', 'DELETE');

            try {
                const response = await fetch(deleteUrl, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: formData,
                    credentials: 'same-origin',
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    showToast(data.message || 'Gagal menghapus learning quiz.', 'error');
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = '<i class="bi bi-trash me-2"></i>Delete';
                    return;
                }

                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) {
                    modalInstance.hide();
                }

                showToast(data.message || 'Learning quiz berhasil dihapus.', 'success');

                setTimeout(() => {
                    window.location.reload();
                }, 900);
            } catch (error) {
                showToast('Gagal menghubungi server.', 'error');
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = '<i class="bi bi-trash me-2"></i>Delete';
            }
        });
    }

    initQuill();
    setupLearningQuizModal();
    setupDeleteConfirmModal();
});
</script>
@endpush