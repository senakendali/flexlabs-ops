@extends('layouts.app-dashboard')

@section('title', 'Quiz Attempt Detail')

@section('content')
<div class="container-fluid px-4 py-4">

    @php
        $quiz = $attempt->learningQuiz;
        $student = $attempt->student;
        $batch = $attempt->batch;
        $batchLabel = ($batch?->program?->name ? $batch->program->name . ' - ' : '') . ($batch?->name ?? 'Batch');

        $statusClass = match($attempt->status) {
            'in_progress' => 'ui-badge-warning',
            'submitted' => 'ui-badge-primary',
            'graded' => 'ui-badge-success',
            'expired' => 'ui-badge-danger',
            'cancelled' => 'ui-badge-muted',
            default => 'ui-badge-muted',
        };

        $resultLabel = 'Not graded';
        $resultClass = 'ui-badge-muted';

        if (!is_null($attempt->is_passed)) {
            $resultLabel = $attempt->is_passed ? 'Passed' : 'Failed';
            $resultClass = $attempt->is_passed ? 'ui-badge-success' : 'ui-badge-danger';
        }

        $score = !is_null($attempt->score) ? number_format((float) $attempt->score, 2) : '-';
        $totalScore = !is_null($attempt->total_score) ? number_format((float) $attempt->total_score, 2) : '-';
        $percentage = !is_null($attempt->percentage) ? number_format((float) $attempt->percentage, 2) . '%' : '-';

        $durationLabel = '-';
        if (!is_null($attempt->duration_seconds)) {
            $minutes = floor($attempt->duration_seconds / 60);
            $seconds = $attempt->duration_seconds % 60;
            $durationLabel = $minutes . 'm ' . $seconds . 's';
        }
    @endphp

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Academic Management</div>
                <h1 class="page-title mb-2">Quiz Attempt Detail</h1>
                <p class="page-subtitle mb-0">
                    Detail jawaban student untuk quiz:
                    <strong>{{ $quiz->title ?? 'Untitled Quiz' }}</strong>
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <a href="{{ route('learning-quiz-attempts.index') }}" class="btn btn-light btn-modern">
                    <i class="bi bi-arrow-left me-2"></i>Back to Attempts
                </a>

                <button
                    type="button"
                    class="btn btn-primary btn-modern"
                    data-action-url="{{ route('learning-quiz-attempts.grade', $attempt->id) }}"
                    data-action-message="Hitung ulang score dan tandai attempt ini sebagai graded?"
                >
                    <i class="bi bi-calculator me-2"></i>Recalculate Grade
                </button>
            </div>
        </div>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-body">
            <div class="entity-card">
                <div class="entity-main">
                    <div class="entity-icon">
                        <i class="bi bi-person-check-fill"></i>
                    </div>

                    <div class="entity-info">
                        <div class="entity-title-row">
                            <div>
                                <h5 class="entity-title">{{ $student->name ?? 'Unknown Student' }}</h5>
                                <div class="entity-subtitle">
                                    {{ $student->email ?? '-' }} • {{ $batchLabel }}
                                </div>
                            </div>

                            <div class="entity-badges">
                                <span class="ui-badge {{ $statusClass }}">
                                    {{ $statuses[$attempt->status] ?? $attempt->status_label }}
                                </span>

                                <span class="ui-badge {{ $resultClass }}">
                                    {{ $resultLabel }}
                                </span>

                                <span class="ui-badge ui-badge-muted">
                                    Attempt #{{ $attempt->attempt_number }}
                                </span>
                            </div>
                        </div>

                        <div class="entity-meta">
                            <span>
                                <i class="bi bi-telephone me-1"></i>{{ $student->phone ?? '-' }}
                            </span>

                            <span>
                                <i class="bi bi-person-badge me-1"></i>
                                Graded by: {{ $attempt->gradedBy->name ?? '-' }}
                            </span>

                            <span>
                                <i class="bi bi-calendar-check me-1"></i>
                                Graded at: {{ $attempt->graded_at ? $attempt->graded_at->format('d M Y, H:i') : '-' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Started At</div>
                    <div class="info-value">
                        {{ $attempt->started_at ? $attempt->started_at->format('d M Y, H:i') : '-' }}
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-label">Submitted At</div>
                    <div class="info-value">
                        {{ $attempt->submitted_at ? $attempt->submitted_at->format('d M Y, H:i') : '-' }}
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-label">Duration</div>
                    <div class="info-value">{{ $durationLabel }}</div>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Score</div>
                    <div class="info-value">{{ $score }} / {{ $totalScore }}</div>
                </div>

                <div class="info-item">
                    <div class="info-label">Percentage</div>
                    <div class="info-value">{{ $percentage }}</div>
                </div>

                <div class="info-item">
                    <div class="info-label">Answers</div>
                    <div class="info-value">{{ $attempt->answers->count() }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="content-card">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Student Answers</h5>
                <p class="content-card-subtitle mb-0">
                    Review jawaban per question. Short answer bisa dinilai manual dari halaman ini.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            @if($attempt->answers->count())
                <div class="entity-list">
                    @foreach($attempt->answers as $answer)
                        @php
                            $question = $answer->question;
                            $questionType = $question?->question_type ?? 'unknown';

                            $answerStateLabel = 'Not checked';
                            $answerStateClass = 'ui-badge-muted';

                            if (!is_null($answer->is_correct)) {
                                $answerStateLabel = $answer->is_correct ? 'Correct' : 'Incorrect';
                                $answerStateClass = $answer->is_correct ? 'ui-badge-success' : 'ui-badge-danger';
                            }

                            $answerScore = !is_null($answer->score) ? number_format((float) $answer->score, 2) : '-';
                            $maxQuestionScore = $question?->score ?? 0;

                            $selectedOptionIds = collect($answer->selected_option_ids ?? [])
                                ->map(fn ($id) => (int) $id)
                                ->values()
                                ->all();

                            if ($answer->learning_quiz_option_id) {
                                $selectedOptionIds[] = (int) $answer->learning_quiz_option_id;
                                $selectedOptionIds = array_values(array_unique($selectedOptionIds));
                            }

                            $answerPayload = [
                                'id' => $answer->id,
                                'score' => $answer->score,
                                'max_score' => $maxQuestionScore,
                                'is_correct' => is_null($answer->is_correct) ? '' : (int) $answer->is_correct,
                                'feedback' => $answer->feedback,
                                'grade_url' => route('learning-quiz-attempts.answers.grade', [$attempt->id, $answer->id]),
                                'question_title' => 'Question #' . ($question?->sort_order ?? $loop->iteration),
                            ];
                        @endphp

                        <script type="application/json" id="answer-payload-{{ $answer->id }}">
                            @json($answerPayload)
                        </script>

                        <div class="entity-card">
                            <div class="entity-main">
                                <div class="entity-icon">
                                    <i class="bi bi-chat-square-text-fill"></i>
                                </div>

                                <div class="entity-info">
                                    <div class="entity-title-row">
                                        <div>
                                            <h5 class="entity-title">
                                                Question #{{ $question?->sort_order ?? $loop->iteration }}
                                            </h5>
                                            <div class="entity-subtitle">
                                                {{ $question?->question_text ?? '-' }}
                                            </div>
                                        </div>

                                        <div class="entity-badges">
                                            <span class="ui-badge ui-badge-primary">
                                                {{ $question?->question_type_label ?? ucfirst($questionType) }}
                                            </span>

                                            <span class="ui-badge {{ $answerStateClass }}">
                                                {{ $answerStateLabel }}
                                            </span>

                                            <span class="ui-badge ui-badge-muted">
                                                Score {{ $answerScore }}/{{ $maxQuestionScore }}
                                            </span>
                                        </div>
                                    </div>

                                    @if($question && $question->options->count())
                                        <div class="soft-panel quiz-options-panel mt-3">
                                            <div class="soft-panel-header">
                                                <div class="soft-panel-title">
                                                    <i class="bi bi-ui-checks-grid me-2"></i>Options
                                                </div>
                                                <div class="soft-panel-subtitle">
                                                    Warna hijau menandakan jawaban benar. Marker ungu menandakan pilihan student.
                                                </div>
                                            </div>

                                            <div class="quiz-option-list">
                                                @foreach($question->options as $option)
                                                    @php
                                                        $isSelected = in_array((int) $option->id, $selectedOptionIds, true);
                                                    @endphp

                                                    <div class="quiz-option-card {{ $option->is_correct ? 'is-correct' : '' }} {{ $isSelected ? 'is-selected' : '' }}">
                                                        <div class="quiz-option-main">
                                                            <div class="quiz-option-marker">
                                                                @if($isSelected)
                                                                    <i class="bi bi-check-circle-fill"></i>
                                                                @elseif($option->is_correct)
                                                                    <i class="bi bi-check2-circle"></i>
                                                                @else
                                                                    <i class="bi bi-circle"></i>
                                                                @endif
                                                            </div>

                                                            <div class="quiz-option-content">
                                                                <div class="quiz-option-badges">
                                                                    @if($isSelected)
                                                                        <span class="quiz-option-pill is-active">Selected</span>
                                                                    @endif

                                                                    <span class="quiz-option-pill {{ $option->is_correct ? 'is-correct' : 'is-muted' }}">
                                                                        {{ $option->is_correct ? 'Correct Answer' : 'Option' }}
                                                                    </span>
                                                                </div>

                                                                <div class="quiz-option-text">
                                                                    {{ $option->option_text }}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @else
                                        <div class="soft-panel mt-3">
                                            <div class="soft-panel-title">
                                                <i class="bi bi-keyboard me-2"></i>Answer Text
                                            </div>
                                            <div class="soft-panel-subtitle">
                                                {{ $answer->answer_text ?: 'No answer text.' }}
                                            </div>
                                        </div>
                                    @endif

                                    @if($question?->explanation)
                                        <div class="richtext-preview">
                                            <div class="richtext-content">
                                                <strong>Explanation:</strong> {{ $question->explanation }}
                                            </div>
                                        </div>
                                    @endif

                                    @if($answer->feedback)
                                        <div class="richtext-preview">
                                            <div class="richtext-content">
                                                <strong>Feedback:</strong> {{ $answer->feedback }}
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
                                    data-bs-target="#gradeAnswerModal"
                                    data-answer-json-id="answer-payload-{{ $answer->id }}"
                                >
                                    <i class="bi bi-pencil-square me-1"></i>Grade
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-chat-square-text"></i>
                    </div>
                    <h5 class="empty-state-title">Belum ada jawaban</h5>
                    <p class="empty-state-text mb-0">
                        Jawaban akan muncul setelah student submit quiz.
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Grade Answer Modal --}}
<div class="modal fade" id="gradeAnswerModal" tabindex="-1" aria-labelledby="gradeAnswerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content custom-modal">
            <form id="gradeAnswerForm">
                @csrf

                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title" id="gradeAnswerModalLabel">Grade Answer</h5>
                        <p class="text-muted mb-0" id="gradeAnswerSubtitle">Beri score dan feedback untuk jawaban ini.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-4">
                    <div class="alert alert-danger d-none form-alert" role="alert"></div>

                    <div class="soft-panel mb-3">
                        <div class="soft-panel-title" id="gradeQuestionTitle">-</div>
                        <div class="soft-panel-subtitle" id="gradeMaxScoreText">Max score: -</div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Score</label>
                            <input type="number" name="score" class="form-control" min="0" step="0.01" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Correct Status</label>
                            <select name="is_correct" class="form-select">
                                <option value="">Keep Current</option>
                                <option value="1">Correct</option>
                                <option value="0">Incorrect</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Feedback</label>
                            <textarea
                                name="feedback"
                                rows="5"
                                class="form-control"
                                placeholder="Feedback untuk jawaban student..."
                            ></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary btn-modern submit-btn">
                        Save Grade
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken =
        document.querySelector('#gradeAnswerForm input[name="_token"]')?.value
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
        const alertBox = form.querySelector('.form-alert');
        const submitBtn = form.querySelector('.submit-btn');

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

    function setupGradeAnswerModal() {
        const modalEl = document.getElementById('gradeAnswerModal');
        const form = document.getElementById('gradeAnswerForm');

        if (!modalEl || !form) return;

        modalEl.addEventListener('show.bs.modal', function (event) {
            form.reset();
            resetFormState(form);

            const button = event.relatedTarget;
            const payload = getJsonFromScript(button?.dataset?.answerJsonId) || {};

            modalEl.dataset.gradeUrl = payload.grade_url || '';

            form.querySelector('input[name="score"]').value = payload.score ?? '';
            form.querySelector('input[name="score"]').setAttribute('max', payload.max_score ?? 0);
            form.querySelector('select[name="is_correct"]').value = payload.is_correct === '' ? '' : String(payload.is_correct);
            form.querySelector('textarea[name="feedback"]').value = payload.feedback || '';

            modalEl.querySelector('#gradeQuestionTitle').textContent = payload.question_title || '-';
            modalEl.querySelector('#gradeMaxScoreText').textContent = `Max score: ${payload.max_score ?? 0}`;
        });

        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            const actionUrl = modalEl.dataset.gradeUrl;

            if (!actionUrl) {
                showToast('Route grade answer belum tersedia.', 'error');
                return;
            }

            const submitBtn = form.querySelector('.submit-btn');
            const formData = new FormData(form);

            resetFormState(form);

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
                        showToast(data.message || 'Gagal menyimpan grade.', 'error');
                    }

                    submitBtn.disabled = false;
                    submitBtn.innerHTML = submitBtn.dataset.defaultText;
                    return;
                }

                showToast(data.message || 'Answer berhasil dinilai.', 'success');

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

    function setupActionButtons() {
        document.querySelectorAll('[data-action-url]').forEach(function (button) {
            button.addEventListener('click', async function () {
                const actionUrl = button.dataset.actionUrl;
                const message = button.dataset.actionMessage || 'Lanjutkan aksi ini?';

                if (!actionUrl) {
                    showToast('Route action belum tersedia.', 'error');
                    return;
                }

                if (!confirm(message)) return;

                const defaultText = button.innerHTML;
                button.disabled = true;
                button.innerHTML = 'Processing...';

                const formData = new FormData();
                formData.append('_token', csrfToken);

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
                        showToast(data.message || 'Aksi gagal diproses.', 'error');
                        button.disabled = false;
                        button.innerHTML = defaultText;
                        return;
                    }

                    showToast(data.message || 'Aksi berhasil diproses.', 'success');

                    setTimeout(() => {
                        window.location.reload();
                    }, 900);
                } catch (error) {
                    showToast('Gagal menghubungi server.', 'error');
                    button.disabled = false;
                    button.innerHTML = defaultText;
                }
            });
        });
    }

    setupGradeAnswerModal();
    setupActionButtons();
});
</script>
@endpush