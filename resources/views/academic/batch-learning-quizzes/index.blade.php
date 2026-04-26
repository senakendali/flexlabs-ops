@extends('layouts.app-dashboard')

@section('title', 'Batch Learning Quizzes')

@section('content')
<div class="container-fluid px-4 py-4">

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Academic Management</div>
                <h1 class="page-title mb-2">Batch Learning Quizzes</h1>
                <p class="page-subtitle mb-0">
                    Atur learning quiz yang diberikan ke <strong>Batch</strong>, termasuk deadline, duration, passing score, dan max attempts.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <button
                    type="button"
                    class="btn btn-primary btn-modern"
                    data-bs-toggle="modal"
                    data-bs-target="#batchLearningQuizModal"
                    data-mode="create"
                >
                    <i class="bi bi-plus-circle me-2"></i>Add Batch Learning Quiz
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
                <div class="stat-description">Total quiz yang sudah diberikan ke batch.</div>
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
                <div class="stat-description">Quiz yang sudah bisa muncul untuk student.</div>
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
                <div class="stat-description">Quiz batch yang belum dipublish.</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-lock-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Closed</div>
                        <div class="stat-value">{{ $stats['closed'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Quiz yang sudah ditutup.</div>
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
                <div class="stat-description">Batch quiz aktif di sistem.</div>
            </div>
        </div>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Filter Batch Learning Quizzes</h5>
                <p class="content-card-subtitle mb-0">Cari berdasarkan quiz, batch, program, atau status.</p>
            </div>
        </div>

        <div class="content-card-body">
            <form method="GET" action="{{ route('batch-learning-quizzes.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-xl-3 col-md-6">
                        <label class="form-label">Keyword</label>
                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            value="{{ request('search') }}"
                            placeholder="Cari quiz / batch / program..."
                        >
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <label class="form-label">Batch</label>
                        <select name="batch_id" class="form-select">
                            <option value="">All Batches</option>
                            @foreach($batches ?? [] as $batch)
                                <option value="{{ $batch->id }}" {{ request('batch_id') == $batch->id ? 'selected' : '' }}>
                                    {{ $batch->program->name ?? 'Program' }} - {{ $batch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <label class="form-label">Learning Quiz</label>
                        <select name="learning_quiz_id" class="form-select">
                            <option value="">All Quizzes</option>
                            @foreach($learningQuizzes ?? [] as $quiz)
                                <option value="{{ $quiz->id }}" {{ request('learning_quiz_id') == $quiz->id ? 'selected' : '' }}>
                                    {{ $quiz->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-1 col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            @foreach($statuses ?? [] as $key => $label)
                                <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-2">
                        <div class="d-flex gap-2 justify-content-xl-end flex-wrap">
                            <a href="{{ route('batch-learning-quizzes.index') }}" class="btn btn-outline-secondary btn-modern">
                                <i class="bi bi-arrow-counterclockwise me-2"></i>Reset
                            </a>
                            <button type="submit" class="btn btn-primary btn-modern">
                                <i class="bi bi-funnel me-2"></i>Filter
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="content-card">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Batch Learning Quiz List</h5>
                <p class="content-card-subtitle mb-0">
                    Data ini menentukan quiz mana yang muncul di batch tertentu beserta deadline dan aturan attempt-nya.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            @if(($batchLearningQuizzes ?? collect())->count())
                <div class="entity-list">
                    @foreach($batchLearningQuizzes as $batchLearningQuiz)
                        @php
                            $quiz = $batchLearningQuiz->learningQuiz;
                            $batch = $batchLearningQuiz->batch;

                            $statusClass = match($batchLearningQuiz->status) {
                                'published' => 'ui-badge-success',
                                'closed' => 'ui-badge-info',
                                'archived' => 'ui-badge-danger',
                                default => 'ui-badge-muted',
                            };

                            $quizTypeClass = match($quiz?->quiz_type) {
                                'practice' => 'ui-badge-info',
                                'graded' => 'ui-badge-primary',
                                default => 'ui-badge-muted',
                            };

                            $deadlineState = 'No deadline';
                            $deadlineClass = 'ui-badge-muted';

                            if ($batchLearningQuiz->due_at) {
                                if ($batchLearningQuiz->is_closed) {
                                    $deadlineState = 'Closed';
                                    $deadlineClass = 'ui-badge-info';
                                } elseif ($batchLearningQuiz->is_overdue) {
                                    $deadlineState = 'Overdue';
                                    $deadlineClass = 'ui-badge-danger';
                                } else {
                                    $deadlineState = 'Open';
                                    $deadlineClass = 'ui-badge-primary';
                                }
                            }

                            $targetLabel = $quiz?->subTopic
                                ? 'Sub Topic: ' . $quiz->subTopic->name
                                : 'Topic: ' . ($quiz?->topic->name ?? '-');

                            $effectiveDuration = $batchLearningQuiz->duration_minutes
                                ?? $quiz?->duration_minutes;

                            $effectivePassingScore = $batchLearningQuiz->passing_score
                                ?? $quiz?->passing_score
                                ?? 70;

                            $effectiveMaxAttempts = $batchLearningQuiz->max_attempts
                                ?? $quiz?->max_attempts
                                ?? 1;

                            $availableAtValue = $batchLearningQuiz->available_at?->format('Y-m-d\TH:i');
                            $dueAtValue = $batchLearningQuiz->due_at?->format('Y-m-d\TH:i');
                            $closedAtValue = $batchLearningQuiz->closed_at?->format('Y-m-d\TH:i');
                        @endphp

                        <div class="entity-card">
                            <div class="entity-main">
                                <div class="entity-icon">
                                    <i class="bi bi-patch-question-fill"></i>
                                </div>

                                <div class="entity-info">
                                    <div class="entity-title-row">
                                        <div>
                                            <h5 class="entity-title">
                                                {{ $quiz->title ?? 'Untitled Learning Quiz' }}
                                            </h5>

                                            <div class="entity-subtitle">
                                                {{ $batch->program->name ?? 'Program' }} - {{ $batch->name ?? 'Batch' }}
                                            </div>
                                        </div>

                                        <div class="entity-badges">
                                            <span class="ui-badge {{ $quizTypeClass }}">
                                                {{ $quiz?->quiz_type_label ?? 'Quiz' }}
                                            </span>

                                            <span class="ui-badge {{ $statusClass }}">
                                                {{ $statuses[$batchLearningQuiz->status] ?? ucfirst($batchLearningQuiz->status) }}
                                            </span>

                                            <span class="ui-badge {{ $deadlineClass }}">
                                                {{ $deadlineState }}
                                            </span>

                                            @if($batchLearningQuiz->allow_late_attempt)
                                                <span class="ui-badge ui-badge-warning">
                                                    Late Allowed
                                                </span>
                                            @else
                                                <span class="ui-badge ui-badge-muted">
                                                    No Late
                                                </span>
                                            @endif

                                            @if($batchLearningQuiz->is_active)
                                                <span class="ui-badge ui-badge-success">Active</span>
                                            @else
                                                <span class="ui-badge ui-badge-muted">Inactive</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="entity-meta">
                                        <span>
                                            <i class="bi bi-layers me-1"></i>{{ $targetLabel }}
                                        </span>

                                        @if($quiz?->topic?->module)
                                            <span>
                                                <i class="bi bi-folder2-open me-1"></i>{{ $quiz->topic->module->name }}
                                            </span>
                                        @endif

                                        <span>
                                            <i class="bi bi-activity me-1"></i>{{ $batchLearningQuiz->attempts_count ?? 0 }} Attempts
                                        </span>
                                    </div>

                                    <div class="info-grid">
                                        <div class="info-item">
                                            <div class="info-label">Available At</div>
                                            <div class="info-value">
                                                @if($batchLearningQuiz->available_at)
                                                    {{ $batchLearningQuiz->available_at->format('d M Y, H:i') }}
                                                @else
                                                    <span class="text-muted">Not set</span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="info-item">
                                            <div class="info-label">Deadline</div>
                                            <div class="info-value">
                                                @if($batchLearningQuiz->due_at)
                                                    {{ $batchLearningQuiz->due_at->format('d M Y, H:i') }}
                                                @else
                                                    <span class="text-muted">No deadline</span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="info-item">
                                            <div class="info-label">Closed At</div>
                                            <div class="info-value">
                                                @if($batchLearningQuiz->closed_at)
                                                    {{ $batchLearningQuiz->closed_at->format('d M Y, H:i') }}
                                                @else
                                                    <span class="text-muted">Not set</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div class="info-grid">
                                        <div class="info-item">
                                            <div class="info-label">Duration</div>
                                            <div class="info-value">
                                                @if($effectiveDuration)
                                                    {{ $effectiveDuration }} minutes
                                                @else
                                                    <span class="text-muted">No limit</span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="info-item">
                                            <div class="info-label">Passing Score</div>
                                            <div class="info-value">{{ $effectivePassingScore }}%</div>
                                        </div>

                                        <div class="info-item">
                                            <div class="info-label">Max Attempts</div>
                                            <div class="info-value">{{ $effectiveMaxAttempts }}x</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="entity-actions">
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#batchLearningQuizModal"
                                    data-mode="edit"
                                    data-id="{{ $batchLearningQuiz->id }}"
                                    data-learning-quiz-id="{{ $batchLearningQuiz->learning_quiz_id }}"
                                    data-batch-id="{{ $batchLearningQuiz->batch_id }}"
                                    data-available-at="{{ $availableAtValue }}"
                                    data-due-at="{{ $dueAtValue }}"
                                    data-closed-at="{{ $closedAtValue }}"
                                    data-duration-minutes="{{ $batchLearningQuiz->duration_minutes }}"
                                    data-passing-score="{{ $batchLearningQuiz->passing_score }}"
                                    data-max-attempts="{{ $batchLearningQuiz->max_attempts }}"
                                    data-allow-late-attempt="{{ (int) $batchLearningQuiz->allow_late_attempt }}"
                                    data-status="{{ $batchLearningQuiz->status }}"
                                    data-is-active="{{ (int) $batchLearningQuiz->is_active }}"
                                >
                                    <i class="bi bi-pencil-square me-1"></i>Edit
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-outline-danger btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#deleteConfirmModal"
                                    data-delete-name="{{ ($quiz->title ?? 'Learning Quiz') . ' - ' . ($batch->name ?? 'Batch') }}"
                                    data-delete-url="{{ route('batch-learning-quizzes.destroy', $batchLearningQuiz->id) }}"
                                >
                                    <i class="bi bi-trash me-1"></i>Delete
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4">
                    {{ $batchLearningQuizzes->links() }}
                </div>
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-patch-question"></i>
                    </div>
                    <h5 class="empty-state-title">Batch learning quiz belum tersedia</h5>
                    <p class="empty-state-text mb-3">
                        Belum ada learning quiz yang diberikan ke batch. Mulai dengan memilih quiz dan batch tujuan.
                    </p>
                    <button
                        type="button"
                        class="btn btn-primary btn-modern"
                        data-bs-toggle="modal"
                        data-bs-target="#batchLearningQuizModal"
                        data-mode="create"
                    >
                        <i class="bi bi-plus-circle me-2"></i>Add First Batch Learning Quiz
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="modal fade" id="batchLearningQuizModal" tabindex="-1" aria-labelledby="batchLearningQuizModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content custom-modal">
            <form id="batchLearningQuizForm" data-create-url="{{ route('batch-learning-quizzes.store') }}">
                @csrf
                <input type="hidden" name="_method" value="POST">
                <input type="hidden" name="id" value="">

                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title" id="batchLearningQuizModalLabel">Add Batch Learning Quiz</h5>
                        <p class="text-muted mb-0" id="batchLearningQuizModalSubtitle">
                            Berikan learning quiz ke batch tertentu dan tentukan deadline serta aturan attempt-nya.
                        </p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-4">
                    <div class="alert alert-danger d-none form-alert" role="alert"></div>

                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label">Learning Quiz</label>
                            <select name="learning_quiz_id" class="form-select" required>
                                <option value="">Select Learning Quiz</option>
                                @foreach($learningQuizzes ?? [] as $quiz)
                                    <option
                                        value="{{ $quiz->id }}"
                                        data-duration-minutes="{{ $quiz->duration_minutes }}"
                                        data-passing-score="{{ $quiz->passing_score }}"
                                        data-max-attempts="{{ $quiz->max_attempts }}"
                                    >
                                        {{ $quiz->title }} — {{ $quiz->status }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">
                                Pilih master learning quiz yang akan diberikan ke batch.
                            </div>
                        </div>

                        <div class="col-md-5">
                            <label class="form-label">Batch</label>
                            <select name="batch_id" class="form-select" required>
                                <option value="">Select Batch</option>
                                @foreach($batches ?? [] as $batch)
                                    <option value="{{ $batch->id }}">
                                        {{ $batch->program->name ?? 'Program' }} - {{ $batch->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">
                                Student dalam batch ini akan mendapatkan quiz.
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Available At</label>
                            <input
                                type="datetime-local"
                                name="available_at"
                                class="form-control"
                            >
                            <div class="form-text">
                                Kosongkan kalau quiz langsung tersedia.
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Due At / Deadline</label>
                            <input
                                type="datetime-local"
                                name="due_at"
                                class="form-control"
                            >
                            <div class="form-text">
                                Deadline pengerjaan quiz untuk batch ini.
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Closed At</label>
                            <input
                                type="datetime-local"
                                name="closed_at"
                                class="form-control"
                            >
                            <div class="form-text">
                                Setelah waktu ini, quiz ditutup.
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Duration Override</label>
                            <input
                                type="number"
                                name="duration_minutes"
                                class="form-control"
                                min="1"
                                max="9999"
                                placeholder="Default quiz"
                            >
                            <div class="form-text">
                                Kosongkan jika mengikuti master quiz.
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Passing Score Override</label>
                            <input
                                type="number"
                                name="passing_score"
                                class="form-control"
                                min="0"
                                max="100"
                                placeholder="Default quiz"
                            >
                            <div class="form-text">
                                Kosongkan jika mengikuti master quiz.
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Max Attempts Override</label>
                            <input
                                type="number"
                                name="max_attempts"
                                class="form-control"
                                min="1"
                                max="99"
                                placeholder="Default quiz"
                            >
                            <div class="form-text">
                                Kosongkan jika mengikuti master quiz.
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Allow Late Attempt</label>
                            <select name="allow_late_attempt" class="form-select" required>
                                <option value="0">No</option>
                                <option value="1">Yes</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                @foreach($statuses ?? [] as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Active Status</label>
                            <select name="is_active" class="form-select" required>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <div class="help-card soft-panel">
                                <div class="help-icon">
                                    <i class="bi bi-info-circle"></i>
                                </div>
                                <div>
                                    <div class="help-title">Catatan quiz batch</div>
                                    <div class="help-text">
                                        Quiz akan muncul untuk student jika status <strong>Published</strong>, aktif,
                                        dan sudah melewati <strong>Available At</strong>. Field duration, passing score,
                                        dan max attempts boleh dikosongkan agar mengikuti setting dari master quiz.
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
                        Save Batch Learning Quiz
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
                        <h5 class="modal-title" id="deleteConfirmModalLabel">Delete Batch Learning Quiz</h5>
                        <p class="text-muted mb-0">
                            Konfirmasi sebelum menghapus batch learning quiz.
                        </p>
                    </div>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pt-4">
                <div class="delete-confirm-message">
                    <div class="delete-confirm-label">Data yang akan dihapus</div>
                    <div class="delete-confirm-name" id="deleteConfirmName">-</div>
                </div>

                <div class="delete-confirm-warning mt-3">
                    Batch learning quiz yang sudah dihapus tidak bisa dikembalikan.
                    Pastikan belum ada attempts penting sebelum menghapus data ini.
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken =
        document.querySelector('#batchLearningQuizForm input[name="_token"]')?.value
        || '{{ csrf_token() }}';

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

        if (idInput) idInput.value = '';
        if (methodInput) methodInput.value = 'POST';

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

    function setupQuizDefaultPlaceholders(form) {
        const quizSelect = form.querySelector('select[name="learning_quiz_id"]');
        const durationInput = form.querySelector('input[name="duration_minutes"]');
        const passingInput = form.querySelector('input[name="passing_score"]');
        const attemptsInput = form.querySelector('input[name="max_attempts"]');

        if (!quizSelect) return;

        quizSelect.addEventListener('change', function () {
            const selectedOption = quizSelect.options[quizSelect.selectedIndex];

            if (!selectedOption) return;

            durationInput.placeholder = selectedOption.dataset.durationMinutes
                ? `Default: ${selectedOption.dataset.durationMinutes}`
                : 'Default: No limit';

            passingInput.placeholder = selectedOption.dataset.passingScore
                ? `Default: ${selectedOption.dataset.passingScore}`
                : 'Default quiz';

            attemptsInput.placeholder = selectedOption.dataset.maxAttempts
                ? `Default: ${selectedOption.dataset.maxAttempts}`
                : 'Default quiz';
        });
    }

    function setupBatchLearningQuizModal() {
        const modalEl = document.getElementById('batchLearningQuizModal');
        const form = document.getElementById('batchLearningQuizForm');

        if (!modalEl || !form) return;

        setupQuizDefaultPlaceholders(form);

        modalEl.addEventListener('show.bs.modal', function (event) {
            resetFormState(form);

            const button = event.relatedTarget;
            const mode = button?.dataset?.mode || 'create';

            const title = modalEl.querySelector('#batchLearningQuizModalLabel');
            const subtitle = modalEl.querySelector('#batchLearningQuizModalSubtitle');
            const submitBtn = form.querySelector('.submit-btn');

            if (mode === 'edit') {
                title.textContent = 'Edit Batch Learning Quiz';
                subtitle.textContent = 'Perbarui quiz batch dan aturan attempt-nya.';
                submitBtn.innerHTML = 'Update Batch Learning Quiz';
                submitBtn.dataset.defaultText = 'Update Batch Learning Quiz';

                form.querySelector('input[name="id"]').value = button.dataset.id || '';
                form.querySelector('input[name="_method"]').value = 'PUT';

                form.querySelector('select[name="learning_quiz_id"]').value = button.dataset.learningQuizId || '';
                form.querySelector('select[name="batch_id"]').value = button.dataset.batchId || '';

                form.querySelector('input[name="available_at"]').value = button.dataset.availableAt || '';
                form.querySelector('input[name="due_at"]').value = button.dataset.dueAt || '';
                form.querySelector('input[name="closed_at"]').value = button.dataset.closedAt || '';

                form.querySelector('input[name="duration_minutes"]').value = button.dataset.durationMinutes || '';
                form.querySelector('input[name="passing_score"]').value = button.dataset.passingScore || '';
                form.querySelector('input[name="max_attempts"]').value = button.dataset.maxAttempts || '';

                form.querySelector('select[name="allow_late_attempt"]').value = button.dataset.allowLateAttempt || '0';

                form.querySelector('select[name="status"]').value = button.dataset.status || 'draft';
                form.querySelector('select[name="is_active"]').value = button.dataset.isActive || '1';
            } else {
                title.textContent = 'Add Batch Learning Quiz';
                subtitle.textContent = 'Berikan learning quiz ke batch tertentu dan tentukan deadline serta aturan attempt-nya.';
                submitBtn.innerHTML = 'Save Batch Learning Quiz';
                submitBtn.dataset.defaultText = 'Save Batch Learning Quiz';

                form.querySelector('select[name="allow_late_attempt"]').value = '0';
                form.querySelector('select[name="status"]').value = 'draft';
                form.querySelector('select[name="is_active"]').value = '1';
            }

            form.querySelector('select[name="learning_quiz_id"]').dispatchEvent(new Event('change'));
        });

        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            const submitBtn = form.querySelector('.submit-btn');
            const id = form.querySelector('input[name="id"]')?.value || '';
            const method = form.querySelector('input[name="_method"]')?.value || 'POST';
            const createUrl = form.dataset.createUrl;
            const actionUrl = method === 'PUT'
                ? `{{ route('batch-learning-quizzes.update', ['batchLearningQuiz' => '__ID__']) }}`.replace('__ID__', id)
                : createUrl;

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

                showToast(data.message || 'Batch learning quiz berhasil disimpan.', 'success');

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
                    showToast(data.message || 'Gagal menghapus batch learning quiz.', 'error');
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = '<i class="bi bi-trash me-2"></i>Delete';
                    return;
                }

                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) {
                    modalInstance.hide();
                }

                showToast(data.message || 'Batch learning quiz berhasil dihapus.', 'success');

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

    setupBatchLearningQuizModal();
    setupDeleteConfirmModal();
});
</script>
@endpush