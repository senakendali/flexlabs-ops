@extends('layouts.app-dashboard')

@section('title', 'Learning Quiz Questions')

@section('content')
<div class="container-fluid px-4 py-4">

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Academic Management</div>
                <h1 class="page-title mb-2">Quiz Questions</h1>
                <p class="page-subtitle mb-0">
                    Kelola questions dan answer options untuk quiz:
                    <strong>{{ $learningQuiz->title }}</strong>
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <a href="{{ route('learning-quizzes.index') }}" class="btn btn-light btn-modern">
                    <i class="bi bi-arrow-left me-2"></i>Back to Quizzes
                </a>

                <button
                    type="button"
                    class="btn btn-primary btn-modern"
                    data-bs-toggle="modal"
                    data-bs-target="#questionModal"
                    data-mode="create"
                >
                    <i class="bi bi-plus-circle me-2"></i>Add Question
                </button>
            </div>
        </div>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-body">
            <div class="entity-card">
                <div class="entity-main">
                    <div class="entity-icon">
                        <i class="bi bi-patch-question-fill"></i>
                    </div>

                    <div class="entity-info">
                        <div class="entity-title-row">
                            <div>
                                <h5 class="entity-title">{{ $learningQuiz->title }}</h5>
                                <div class="entity-subtitle">
                                    @if($learningQuiz->subTopic)
                                        Sub Topic: {{ $learningQuiz->subTopic->name }}
                                    @else
                                        Topic: {{ $learningQuiz->topic->name ?? '-' }}
                                    @endif
                                </div>
                            </div>

                            <div class="entity-badges">
                                <span class="ui-badge {{ $learningQuiz->quiz_type === 'graded' ? 'ui-badge-primary' : 'ui-badge-info' }}">
                                    {{ $learningQuiz->quiz_type_label }}
                                </span>

                                <span class="ui-badge {{ $learningQuiz->status === 'published' ? 'ui-badge-success' : 'ui-badge-muted' }}">
                                    {{ $learningQuiz->status_label }}
                                </span>

                                @if($learningQuiz->is_active)
                                    <span class="ui-badge ui-badge-success">Active</span>
                                @else
                                    <span class="ui-badge ui-badge-muted">Inactive</span>
                                @endif
                            </div>
                        </div>

                        <div class="entity-meta">
                            @if($learningQuiz->topic?->module)
                                <span>
                                    <i class="bi bi-folder2-open me-1"></i>{{ $learningQuiz->topic->module->name }}
                                </span>
                            @endif

                            <span>
                                <i class="bi bi-clock me-1"></i>
                                {{ $learningQuiz->duration_minutes ? $learningQuiz->duration_minutes . ' minutes' : 'No duration limit' }}
                            </span>

                            <span>
                                <i class="bi bi-award me-1"></i>Passing {{ $learningQuiz->passing_score }}%
                            </span>

                            <span>
                                <i class="bi bi-arrow-repeat me-1"></i>{{ $learningQuiz->max_attempts }} attempts
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-list-check"></i>
                    </div>
                    <div>
                        <div class="stat-title">Questions</div>
                        <div class="stat-value">{{ $stats['questions'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Total question dalam quiz ini.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-toggle-on"></i>
                    </div>
                    <div>
                        <div class="stat-title">Active</div>
                        <div class="stat-value">{{ $stats['active_questions'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Question aktif yang bisa digunakan.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-ui-checks-grid"></i>
                    </div>
                    <div>
                        <div class="stat-title">Options</div>
                        <div class="stat-value">{{ $stats['options'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Total pilihan jawaban.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-star-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Total Score</div>
                        <div class="stat-value">{{ $stats['total_score'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Akumulasi score dari semua question.</div>
            </div>
        </div>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Filter Questions</h5>
                <p class="content-card-subtitle mb-0">
                    Cari question berdasarkan teks, explanation, option, tipe, atau status.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            <form method="GET" action="{{ route('learning-quizzes.questions.index', $learningQuiz->id) }}">
                <div class="row g-3 align-items-end">
                    <div class="col-xl-4 col-md-6">
                        <label class="form-label">Keyword</label>
                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            value="{{ request('search') }}"
                            placeholder="Cari question / option..."
                        >
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <label class="form-label">Question Type</label>
                        <select name="question_type" class="form-select">
                            <option value="">All Types</option>
                            @foreach($questionTypes ?? [] as $key => $label)
                                <option value="{{ $key }}" {{ request('question_type') === $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>

                    <div class="col-xl-2">
                        <div class="d-flex gap-2 justify-content-xl-end flex-wrap">
                            <a href="{{ route('learning-quizzes.questions.index', $learningQuiz->id) }}" class="btn btn-outline-secondary btn-modern">
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
                <h5 class="content-card-title mb-1">Question List</h5>
                <p class="content-card-subtitle mb-0">
                    Kelola question, score, explanation, dan options untuk quiz ini.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            @if(($questions ?? collect())->count())
                <div class="entity-list">
                    @foreach($questions as $question)
                        @php
                            $questionTypeClass = match($question->question_type) {
                                'single_choice' => 'ui-badge-primary',
                                'multiple_choice' => 'ui-badge-info',
                                'true_false' => 'ui-badge-warning',
                                'short_answer' => 'ui-badge-muted',
                                default => 'ui-badge-muted',
                            };

                            $questionPreview = \Illuminate\Support\Str::limit(strip_tags($question->question_text ?? ''), 240);
                            $explanationPreview = \Illuminate\Support\Str::limit(strip_tags($question->explanation ?? ''), 180);

                            $questionPayload = [
                                'id' => $question->id,
                                'question_text' => $question->question_text,
                                'question_type' => $question->question_type,
                                'explanation' => $question->explanation,
                                'score' => $question->score,
                                'sort_order' => $question->sort_order,
                                'is_required' => (int) $question->is_required,
                                'is_active' => (int) $question->is_active,
                            ];
                        @endphp

                        <script type="application/json" id="question-payload-{{ $question->id }}">
                            @json($questionPayload)
                        </script>

                        <div class="entity-card">
                            <div class="entity-main">
                                <div class="entity-icon">
                                    <i class="bi bi-question-circle-fill"></i>
                                </div>

                                <div class="entity-info">
                                    <div class="entity-title-row">
                                        <div>
                                            <h5 class="entity-title">
                                                Question #{{ $question->sort_order }}
                                            </h5>
                                            <div class="entity-subtitle">
                                                {{ $questionPreview }}
                                            </div>
                                        </div>

                                        <div class="entity-badges">
                                            <span class="ui-badge {{ $questionTypeClass }}">
                                                {{ $question->question_type_label }}
                                            </span>

                                            <span class="ui-badge ui-badge-success">
                                                Score {{ $question->score }}
                                            </span>

                                            @if($question->is_required)
                                                <span class="ui-badge ui-badge-primary">Required</span>
                                            @else
                                                <span class="ui-badge ui-badge-muted">Optional</span>
                                            @endif

                                            @if($question->is_active)
                                                <span class="ui-badge ui-badge-success">Active</span>
                                            @else
                                                <span class="ui-badge ui-badge-muted">Inactive</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="entity-meta">
                                        <span>
                                            <i class="bi bi-ui-checks-grid me-1"></i>{{ $question->options_count ?? 0 }} Options
                                        </span>

                                        <span>
                                            <i class="bi bi-sort-numeric-down me-1"></i>Order {{ $question->sort_order }}
                                        </span>
                                    </div>

                                    @if(!empty($explanationPreview))
                                        <div class="richtext-preview">
                                            <div class="richtext-content">
                                                <strong>Explanation:</strong> {{ $explanationPreview }}
                                            </div>
                                        </div>
                                    @endif

                                    @if($question->question_type !== 'short_answer')
                                        <div class="soft-panel quiz-options-panel mt-3">
                                            <div class="soft-panel-header d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                                <div>
                                                    <div class="soft-panel-title">
                                                        <i class="bi bi-ui-checks-grid me-2"></i>Answer Options
                                                    </div>
                                                    <div class="soft-panel-subtitle">
                                                        Tandai option yang benar sesuai tipe question.
                                                    </div>
                                                </div>

                                                <button
                                                    type="button"
                                                    class="btn btn-outline-primary btn-sm"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#optionModal"
                                                    data-mode="create"
                                                    data-question-id="{{ $question->id }}"
                                                    data-create-url="{{ route('learning-quizzes.questions.options.store', [$learningQuiz->id, $question->id]) }}"
                                                    {{ $question->question_type === 'true_false' ? 'disabled' : '' }}
                                                >
                                                    <i class="bi bi-plus-circle me-1"></i>Add Option
                                                </button>
                                            </div>

                                            @if($question->options->count())
                                                <div class="quiz-option-list">
                                                    @foreach($question->options as $option)
                                                        @php
                                                            $optionPayload = [
                                                                'id' => $option->id,
                                                                'question_id' => $question->id,
                                                                'option_text' => $option->option_text,
                                                                'is_correct' => (int) $option->is_correct,
                                                                'sort_order' => $option->sort_order,
                                                                'is_active' => (int) $option->is_active,
                                                                'update_url' => route('learning-quizzes.questions.options.update', [$learningQuiz->id, $question->id, $option->id]),
                                                            ];
                                                        @endphp

                                                        <script type="application/json" id="option-payload-{{ $option->id }}">
                                                            @json($optionPayload)
                                                        </script>

                                                        <div class="quiz-option-card {{ $option->is_correct ? 'is-correct' : '' }} {{ !$option->is_active ? 'is-inactive' : '' }}">
                                                            <div class="quiz-option-main">
                                                                <div class="quiz-option-marker">
                                                                    @if($option->is_correct)
                                                                        <i class="bi bi-check2-circle"></i>
                                                                    @else
                                                                        <i class="bi bi-circle"></i>
                                                                    @endif
                                                                </div>

                                                                <div class="quiz-option-content">
                                                                    <div class="quiz-option-badges">
                                                                        <span class="quiz-option-pill {{ $option->is_correct ? 'is-correct' : 'is-muted' }}">
                                                                            {{ $option->is_correct ? 'Correct' : 'Incorrect' }}
                                                                        </span>

                                                                        <span class="quiz-option-pill {{ $option->is_active ? 'is-active' : 'is-muted' }}">
                                                                            {{ $option->is_active ? 'Active' : 'Inactive' }}
                                                                        </span>

                                                                        <span class="quiz-option-pill is-muted">
                                                                            Order {{ $option->sort_order }}
                                                                        </span>
                                                                    </div>

                                                                    <div class="quiz-option-text">
                                                                        {{ $option->option_text }}
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="quiz-option-actions">
                                                                <button
                                                                    type="button"
                                                                    class="btn btn-light btn-sm"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#optionModal"
                                                                    data-mode="edit"
                                                                    data-option-json-id="option-payload-{{ $option->id }}"
                                                                >
                                                                    <i class="bi bi-pencil-square me-1"></i>Edit
                                                                </button>

                                                                <button
                                                                    type="button"
                                                                    class="btn btn-outline-danger btn-sm"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#deleteConfirmModal"
                                                                    data-delete-type="Option"
                                                                    data-delete-name="{{ \Illuminate\Support\Str::limit($option->option_text, 80) }}"
                                                                    data-delete-url="{{ route('learning-quizzes.questions.options.destroy', [$learningQuiz->id, $question->id, $option->id]) }}"
                                                                >
                                                                    <i class="bi bi-trash me-1"></i>Delete
                                                                </button>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <div class="empty-inline-state">
                                                    Belum ada option untuk question ini.
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <div class="soft-panel mt-3">
                                            <div class="soft-panel-title">
                                                <i class="bi bi-keyboard me-2"></i>Short Answer
                                            </div>
                                            <div class="soft-panel-subtitle">
                                                Question tipe short answer tidak membutuhkan options. Penilaian bisa dilakukan dari hasil attempt nanti.
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="entity-actions">
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#questionModal"
                                    data-mode="edit"
                                    data-question-json-id="question-payload-{{ $question->id }}"
                                >
                                    <i class="bi bi-pencil-square me-1"></i>Edit
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-outline-danger btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#deleteConfirmModal"
                                    data-delete-type="Question"
                                    data-delete-name="Question #{{ $question->sort_order }}"
                                    data-delete-url="{{ route('learning-quizzes.questions.destroy', [$learningQuiz->id, $question->id]) }}"
                                >
                                    <i class="bi bi-trash me-1"></i>Delete
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4">
                    {{ $questions->links() }}
                </div>
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-question-circle"></i>
                    </div>
                    <h5 class="empty-state-title">Belum ada question</h5>
                    <p class="empty-state-text mb-3">
                        Tambahkan question pertama untuk quiz ini. Setelah itu baru isi answer options.
                    </p>
                    <button
                        type="button"
                        class="btn btn-primary btn-modern"
                        data-bs-toggle="modal"
                        data-bs-target="#questionModal"
                        data-mode="create"
                    >
                        <i class="bi bi-plus-circle me-2"></i>Add First Question
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Question Modal --}}
<div class="modal fade" id="questionModal" tabindex="-1" aria-labelledby="questionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content custom-modal">
            <form
                id="questionForm"
                data-create-url="{{ route('learning-quizzes.questions.store', $learningQuiz->id) }}"
            >
                @csrf
                <input type="hidden" name="_method" value="POST">
                <input type="hidden" name="id" value="">

                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title" id="questionModalLabel">Add Question</h5>
                        <p class="text-muted mb-0" id="questionModalSubtitle">
                            Tambahkan question untuk quiz ini.
                        </p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-4">
                    <div class="alert alert-danger d-none form-alert" role="alert"></div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Question Text</label>
                            <textarea
                                name="question_text"
                                rows="5"
                                class="form-control"
                                placeholder="Tulis pertanyaan..."
                                required
                            ></textarea>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Question Type</label>
                            <select name="question_type" class="form-select" required>
                                @foreach($questionTypes ?? [] as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">
                                True / False otomatis membuat option True dan False.
                            </div>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Score</label>
                            <input
                                type="number"
                                name="score"
                                class="form-control"
                                min="1"
                                max="999"
                                value="1"
                                required
                            >
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Order</label>
                            <input
                                type="number"
                                name="sort_order"
                                class="form-control"
                                min="1"
                                value="1"
                            >
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Required</label>
                            <select name="is_required" class="form-select" required>
                                <option value="1">Required</option>
                                <option value="0">Optional</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="is_active" class="form-select" required>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Explanation</label>
                            <textarea
                                name="explanation"
                                rows="4"
                                class="form-control"
                                placeholder="Penjelasan jawaban benar / pembahasan..."
                            ></textarea>
                            <div class="form-text">
                                Explanation bisa ditampilkan ke student jika quiz mengizinkan show correct answer.
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="help-card soft-panel">
                                <div class="help-icon">
                                    <i class="bi bi-info-circle"></i>
                                </div>
                                <div>
                                    <div class="help-title">Catatan tipe question</div>
                                    <div class="help-text">
                                        Untuk <strong>Single Choice</strong> dan <strong>True / False</strong>, hanya satu option yang boleh benar.
                                        Untuk <strong>Multiple Choice</strong>, beberapa option bisa benar.
                                        Untuk <strong>Short Answer</strong>, option tidak digunakan.
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
                        Save Question
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Option Modal --}}
<div class="modal fade" id="optionModal" tabindex="-1" aria-labelledby="optionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content custom-modal">
            <form id="optionForm">
                @csrf
                <input type="hidden" name="_method" value="POST">
                <input type="hidden" name="id" value="">
                <input type="hidden" name="question_id" value="">

                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title" id="optionModalLabel">Add Option</h5>
                        <p class="text-muted mb-0" id="optionModalSubtitle">
                            Tambahkan pilihan jawaban.
                        </p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-4">
                    <div class="alert alert-danger d-none form-alert" role="alert"></div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Option Text</label>
                            <textarea
                                name="option_text"
                                rows="4"
                                class="form-control"
                                placeholder="Tulis pilihan jawaban..."
                                required
                            ></textarea>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Correct Answer</label>
                            <select name="is_correct" class="form-select" required>
                                <option value="0">Incorrect</option>
                                <option value="1">Correct</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Order</label>
                            <input
                                type="number"
                                name="sort_order"
                                class="form-control"
                                min="1"
                                value="1"
                            >
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="is_active" class="form-select" required>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary btn-modern submit-btn">
                        Save Option
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Delete Modal --}}
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal delete-confirm-modal">
            <div class="modal-header border-0 pb-0">
                <div class="delete-confirm-heading">
                    <div class="delete-confirm-icon">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div>
                        <h5 class="modal-title" id="deleteConfirmModalLabel">Delete Item</h5>
                        <p class="text-muted mb-0" id="deleteConfirmSubtitle">
                            Konfirmasi sebelum menghapus data.
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
                    Data yang sudah dihapus tidak bisa dikembalikan.
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
        document.querySelector('#questionForm input[name="_token"]')?.value
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

    function getJsonFromScript(scriptId) {
        if (!scriptId) return null;

        const script = document.getElementById(scriptId);
        if (!script) return null;

        try {
            return JSON.parse(script.textContent || '{}');
        } catch (error) {
            return null;
        }
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

    async function submitAsyncForm(form, actionUrl, successMessage) {
        const submitBtn = form.querySelector('.submit-btn');
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

                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = submitBtn.dataset.defaultText;
                }

                return false;
            }

            showToast(data.message || successMessage, 'success');

            setTimeout(() => {
                window.location.reload();
            }, 900);

            return true;
        } catch (error) {
            showErrors(form, { general: ['Gagal menghubungi server.'] });
            showToast('Gagal menghubungi server.', 'error');

            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = submitBtn.dataset.defaultText;
            }

            return false;
        }
    }

    function setupQuestionModal() {
        const modalEl = document.getElementById('questionModal');
        const form = document.getElementById('questionForm');

        if (!modalEl || !form) return;

        modalEl.addEventListener('show.bs.modal', function (event) {
            resetFormState(form);

            const button = event.relatedTarget;
            const mode = button?.dataset?.mode || 'create';

            const title = modalEl.querySelector('#questionModalLabel');
            const subtitle = modalEl.querySelector('#questionModalSubtitle');
            const submitBtn = form.querySelector('.submit-btn');

            if (mode === 'edit') {
                const payload = getJsonFromScript(button?.dataset?.questionJsonId) || {};

                title.textContent = 'Edit Question';
                subtitle.textContent = 'Perbarui question quiz.';
                submitBtn.innerHTML = 'Update Question';
                submitBtn.dataset.defaultText = 'Update Question';

                form.querySelector('input[name="id"]').value = payload.id || '';
                form.querySelector('input[name="_method"]').value = 'PUT';
                form.querySelector('textarea[name="question_text"]').value = payload.question_text || '';
                form.querySelector('select[name="question_type"]').value = payload.question_type || 'single_choice';
                form.querySelector('textarea[name="explanation"]').value = payload.explanation || '';
                form.querySelector('input[name="score"]').value = payload.score || 1;
                form.querySelector('input[name="sort_order"]').value = payload.sort_order || 1;
                form.querySelector('select[name="is_required"]').value = String(payload.is_required ?? 1);
                form.querySelector('select[name="is_active"]').value = String(payload.is_active ?? 1);
            } else {
                title.textContent = 'Add Question';
                subtitle.textContent = 'Tambahkan question untuk quiz ini.';
                submitBtn.innerHTML = 'Save Question';
                submitBtn.dataset.defaultText = 'Save Question';

                form.querySelector('input[name="score"]').value = 1;
                form.querySelector('input[name="sort_order"]').value = 1;
                form.querySelector('select[name="question_type"]').value = 'single_choice';
                form.querySelector('select[name="is_required"]').value = '1';
                form.querySelector('select[name="is_active"]').value = '1';
            }
        });

        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            const id = form.querySelector('input[name="id"]')?.value || '';
            const method = form.querySelector('input[name="_method"]')?.value || 'POST';

            const actionUrl = method === 'PUT'
                ? `{{ route('learning-quizzes.questions.update', ['learningQuiz' => $learningQuiz->id, 'question' => '__QUESTION__']) }}`.replace('__QUESTION__', id)
                : form.dataset.createUrl;

            if (method === 'PUT') {
                const methodInput = form.querySelector('input[name="_method"]');
                methodInput.value = 'PUT';
            }

            await submitAsyncForm(form, actionUrl, 'Question berhasil disimpan.');
        });
    }

    function setupOptionModal() {
        const modalEl = document.getElementById('optionModal');
        const form = document.getElementById('optionForm');

        if (!modalEl || !form) return;

        modalEl.addEventListener('show.bs.modal', function (event) {
            resetFormState(form);

            const button = event.relatedTarget;
            const mode = button?.dataset?.mode || 'create';

            const title = modalEl.querySelector('#optionModalLabel');
            const subtitle = modalEl.querySelector('#optionModalSubtitle');
            const submitBtn = form.querySelector('.submit-btn');

            if (mode === 'edit') {
                const payload = getJsonFromScript(button?.dataset?.optionJsonId) || {};

                title.textContent = 'Edit Option';
                subtitle.textContent = 'Perbarui pilihan jawaban.';
                submitBtn.innerHTML = 'Update Option';
                submitBtn.dataset.defaultText = 'Update Option';

                modalEl.dataset.actionUrl = payload.update_url || '';

                form.querySelector('input[name="id"]').value = payload.id || '';
                form.querySelector('input[name="_method"]').value = 'PUT';
                form.querySelector('input[name="question_id"]').value = payload.question_id || '';
                form.querySelector('textarea[name="option_text"]').value = payload.option_text || '';
                form.querySelector('select[name="is_correct"]').value = String(payload.is_correct ?? 0);
                form.querySelector('input[name="sort_order"]').value = payload.sort_order || 1;
                form.querySelector('select[name="is_active"]').value = String(payload.is_active ?? 1);
            } else {
                title.textContent = 'Add Option';
                subtitle.textContent = 'Tambahkan pilihan jawaban.';
                submitBtn.innerHTML = 'Save Option';
                submitBtn.dataset.defaultText = 'Save Option';

                modalEl.dataset.actionUrl = button?.dataset?.createUrl || '';

                form.querySelector('input[name="question_id"]').value = button?.dataset?.questionId || '';
                form.querySelector('input[name="sort_order"]').value = 1;
                form.querySelector('select[name="is_correct"]').value = '0';
                form.querySelector('select[name="is_active"]').value = '1';
            }
        });

        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            const actionUrl = modalEl.dataset.actionUrl;

            if (!actionUrl) {
                showToast('Route option belum tersedia.', 'error');
                return;
            }

            await submitAsyncForm(form, actionUrl, 'Option berhasil disimpan.');
        });
    }

    function setupDeleteConfirmModal() {
        const modalEl = document.getElementById('deleteConfirmModal');
        const confirmBtn = document.getElementById('confirmDeleteBtn');

        if (!modalEl || !confirmBtn) return;

        modalEl.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;

            const deleteType = button?.dataset?.deleteType || 'Item';
            const deleteName = button?.dataset?.deleteName || '-';
            const deleteUrl = button?.dataset?.deleteUrl || '';

            modalEl.dataset.deleteUrl = deleteUrl;

            modalEl.querySelector('#deleteConfirmModalLabel').textContent = `Delete ${deleteType}`;
            modalEl.querySelector('#deleteConfirmSubtitle').textContent = `Konfirmasi sebelum menghapus ${deleteType.toLowerCase()}.`;
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
                    showToast(data.message || 'Gagal menghapus data.', 'error');
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = '<i class="bi bi-trash me-2"></i>Delete';
                    return;
                }

                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) {
                    modalInstance.hide();
                }

                showToast(data.message || 'Data berhasil dihapus.', 'success');

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

    setupQuestionModal();
    setupOptionModal();
    setupDeleteConfirmModal();
});
</script>
@endpush