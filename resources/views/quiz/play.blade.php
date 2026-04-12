@extends('layouts.public')

@section('title', $quiz->title . ' | Quiz')

@section('content')
<div
    id="toastContainer"
    class="toast-container position-fixed top-0 end-0 p-3"
></div>

@php
    $quizQuestions = $quiz->questions->map(function ($question) {
        return [
            'id' => $question->id,
            'question_text' => $question->question_text,
            'options' => $question->options->values()->map(function ($option, $index) {
                return [
                    'id' => $option->id,
                    'option_text' => $option->option_text,
                    'key' => chr(65 + $index),
                ];
            })->values()->toArray(),
        ];
    })->values()->toArray();
@endphp

<section
    class="content-section quiz-play-section"
    id="quizApp"
    data-quiz-id="{{ $quiz->id }}"
    data-participant-store-url="{{ url('/api/public/quizzes/' . $quiz->id . '/participants') }}"
    data-submit-url="{{ url('/api/public/quizzes/' . $quiz->id . '/submit') }}"
>
    <div class="container">
        <div class="section-card quiz-hero-card mb-4">
            <div class="section-card-body">
                <div class="row g-4 align-items-center">
                    <div class="col-lg-12">
                        <span class="section-label">Quiz Time</span>
                        <h1 class="section-title mt-3 mb-2">{{ $quiz->title }}</h1>
                        <p class="section-subtitle mb-0">
                            {{ $quiz->description ?: 'Lengkapi data peserta, jawab pertanyaan satu per satu, lalu kirim hasil quiz di akhir.' }}
                        </p>
                    </div>

                    
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-lg-4 col-md-6">
                        <div class="quiz-stat-box">
                            <div class="quiz-stat-icon">
                                <i class="bi bi-patch-question"></i>
                            </div>
                            <div class="quiz-stat-content">
                                <div class="quiz-stat-title">Total Questions</div>
                                <div class="quiz-stat-value">{{ $quiz->questions->count() }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <div class="quiz-stat-box">
                            <div class="quiz-stat-icon">
                                <i class="bi bi-people"></i>
                            </div>
                            <div class="quiz-stat-content">
                                <div class="quiz-stat-title">Quota</div>
                                <div class="quiz-stat-value">{{ $quiz->quota ?: 'Unlimited' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-12">
                        <div class="quiz-stat-box">
                            <div class="quiz-stat-icon">
                                <i class="bi bi-activity"></i>
                            </div>
                            <div class="quiz-stat-content">
                                <div class="quiz-stat-title">Status</div>
                                <div class="quiz-stat-value">
                                    @if($isDraft)
                                        Draft
                                    @elseif($isFinished)
                                        Finished
                                    @elseif($isNotOpenedYet)
                                        Not Open Yet
                                    @else
                                        Open
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($isDraft)
            <div class="section-card">
                <div class="section-card-body text-center py-5">
                    <div class="quiz-state-icon mx-auto mb-3">
                        <i class="bi bi-pencil-square"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Quiz masih draft</h4>
                    <p class="text-muted mb-0">Quiz ini belum bisa dimainkan karena statusnya masih draft.</p>
                </div>
            </div>
        @elseif($isFinished)
            <div class="section-card">
                <div class="section-card-body text-center py-5">
                    <div class="quiz-state-icon mx-auto mb-3">
                        <i class="bi bi-flag"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Quiz sudah selesai</h4>
                    <p class="text-muted mb-0">Quiz ini sudah ditandai selesai dan tidak tersedia lagi untuk dimainkan.</p>
                </div>
            </div>
        @elseif($isNotOpenedYet)
            <div class="section-card">
                <div class="section-card-body text-center py-5">
                    <div class="quiz-state-icon mx-auto mb-3">
                        <i class="bi bi-clock"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Quiz belum dibuka</h4>
                    <p class="text-muted mb-0">
                        Quiz ini akan tersedia pada
                        <strong>{{ $quiz->opens_at?->format('d M Y H:i') }}</strong>.
                    </p>
                </div>
            </div>
        @elseif($quiz->questions->isEmpty())
            <div class="section-card">
                <div class="section-card-body text-center py-5">
                    <div class="quiz-state-icon mx-auto mb-3">
                        <i class="bi bi-patch-question"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Belum ada pertanyaan</h4>
                    <p class="text-muted mb-0">Quiz ini belum memiliki pertanyaan yang bisa dimainkan.</p>
                </div>
            </div>
        @else
            <div id="quizIdentityStep" class="section-card quiz-form-card">
                <div class="section-card-header">
                    <span class="section-label">Participant Form</span>
                    <h2 class="section-title mt-3">Isi Data Peserta</h2>
                    <p class="section-subtitle mb-0">
                        Semua jawaban akan dikirim saat quiz selesai. Setelah klik mulai, peserta akan masuk ke sistem lalu pertanyaan tampil satu per satu.
                    </p>
                </div>

                <div class="section-card-body">
                    <form id="participantForm" novalidate>
                        @csrf
                        <div id="identityFormAlert" class="alert alert-danger d-none mb-4"></div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="participant_name" class="form-label">
                                    Nama Lengkap <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="participant_name"
                                    class="form-control form-control-lg"
                                    placeholder="Masukkan nama lengkap"
                                >
                                <div class="invalid-feedback" id="error_participant_name"></div>
                            </div>

                            <div class="col-md-6">
                                <label for="participant_email" class="form-label">
                                    Email <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="email"
                                    id="participant_email"
                                    class="form-control form-control-lg"
                                    placeholder="Masukkan email aktif"
                                >
                                <div class="invalid-feedback" id="error_participant_email"></div>
                            </div>

                            <div class="col-md-6">
                                <label for="participant_phone" class="form-label">
                                    Nomor HP <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="participant_phone"
                                    class="form-control form-control-lg"
                                    placeholder="Masukkan nomor HP aktif"
                                >
                                <div class="invalid-feedback" id="error_participant_phone"></div>
                            </div>

                            
                            <div class="col-12 pt-2">
                                <button type="submit" class="btn btn-brand btn-lg px-4" id="startQuizBtn">
                                    <span class="btn-text">
                                        <i class="bi bi-play-fill me-1"></i>
                                        Mulai Quiz
                                    </span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div id="quizQuestionStep" class="d-none">
                <div class="row g-4">
                    <div class="col-lg-4 col-xl-3">
                        <div class="section-card quiz-sidebar-card">
                            <div class="section-card-body">
                                <div class="quiz-sidebar-top">
                                    <div>
                                        <div class="quiz-sidebar-label">Progress</div>
                                        <h5 class="quiz-sidebar-title mb-1">{{ $quiz->title }}</h5>
                                        <div class="quiz-sidebar-subtitle" id="progressText">
                                            0 dari {{ $quiz->questions->count() }} pertanyaan terjawab
                                        </div>
                                    </div>
                                </div>

                                <div class="quiz-progress mt-3 mb-4">
                                    <div class="quiz-progress-bar" id="progressBar"></div>
                                </div>

                                <div class="quiz-question-list" id="questionNavList"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-8 col-xl-9">
                        <div class="section-card quiz-main-card">
                            <div class="section-card-body">
                                <div class="quiz-question-head">
                                    <div class="quiz-question-badge" id="questionCounter">
                                        Question 1 of {{ $quiz->questions->count() }}
                                    </div>

                                    <h3 class="quiz-question-title" id="questionTitle"></h3>

                                    <p class="quiz-question-helper mb-0">
                                        Pilih satu jawaban yang paling sesuai, lalu lanjut ke pertanyaan berikutnya.
                                    </p>
                                </div>

                                <div class="quiz-options-wrap" id="optionsContainer"></div>

                                <div class="quiz-action-bar">
                                    <button type="button" class="btn btn-soft btn-lg px-4" id="prevBtn">
                                        <i class="bi bi-arrow-left me-1"></i>
                                        Previous
                                    </button>

                                    <div class="quiz-action-right">
                                        <button type="button" class="btn btn-outline-secondary btn-lg px-4" id="nextBtn">
                                            Next
                                            <i class="bi bi-arrow-right ms-1"></i>
                                        </button>

                                        <button type="button" class="btn btn-brand btn-lg px-4 d-none" id="finishBtn">
                                            <i class="bi bi-check-circle me-1"></i>
                                            Finish Quiz
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="quizFinishStep" class="section-card d-none">
                <div class="section-card-body text-center py-5">
                    <div class="success-icon mx-auto mb-3">
                        <i class="bi bi-check-lg"></i>
                    </div>

                    <h4 class="fw-bold mb-2">Quiz selesai</h4>
                    <p class="text-muted mb-4">
                        Jawaban kamu sudah berhasil dikirim! Terima kasih sudah meluangkan waktu buat ikut quiz ini
                    </p>

                    <div class="quiz-summary-wrap mx-auto mb-4">
                        <div class="quiz-summary-item">
                            <span>Nama</span>
                            <strong id="summaryName">-</strong>
                        </div>
                        <div class="quiz-summary-item">
                            <span>Total Soal</span>
                            <strong>{{ $quiz->questions->count() }}</strong>
                        </div>
                        <div class="quiz-summary-item">
                            <span>Jawaban Terisi</span>
                            <strong id="summaryAnswered">0</strong>
                        </div>
                    </div>

                    <a href="{{ route('quiz.play', $quiz->id) }}" class="btn btn-brand">
                        <i class="bi bi-arrow-repeat me-2"></i>
                        Ulangi Quiz
                    </a>
                </div>
            </div>
        @endif
    </div>
</section>
@endsection

@push('scripts')
<script>
    const quizQuestions = @json($quizQuestions);

    let currentQuestionIndex = 0;
    let answers = {};
    let participant = {
        name: '',
        phone: '',
        email: '',
    };
    let participantId = null;
    let isSubmittingParticipant = false;
    let isSubmittingAnswers = false;

    const quizApp = document.getElementById('quizApp');
    const quizId = quizApp?.dataset.quizId || null;
    const participantStoreUrl = quizApp?.dataset.participantStoreUrl || '';
    const submitUrl = quizApp?.dataset.submitUrl || '';

    const participantForm = document.getElementById('participantForm');
    const identityFormAlert = document.getElementById('identityFormAlert');

    const quizIdentityStep = document.getElementById('quizIdentityStep');
    const quizQuestionStep = document.getElementById('quizQuestionStep');
    const quizFinishStep = document.getElementById('quizFinishStep');

    const participantName = document.getElementById('participant_name');
    const participantPhone = document.getElementById('participant_phone');
    const participantEmail = document.getElementById('participant_email');

    const questionCounter = document.getElementById('questionCounter');
    const questionTitle = document.getElementById('questionTitle');
    const optionsContainer = document.getElementById('optionsContainer');
    const questionNavList = document.getElementById('questionNavList');
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');

    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const finishBtn = document.getElementById('finishBtn');
    const startQuizBtn = document.getElementById('startQuizBtn');

    const summaryName = document.getElementById('summaryName');
    const summaryAnswered = document.getElementById('summaryAnswered');

    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        const id = 'toast-' + Date.now();

        const bgClass = {
            success: 'bg-success',
            danger: 'bg-danger',
            warning: 'bg-warning text-dark',
            info: 'bg-info text-dark'
        }[type] || 'bg-success';

        const closeBtnClass = type === 'warning' || type === 'info'
            ? 'btn-close'
            : 'btn-close btn-close-white';

        const html = `
            <div id="${id}" class="toast align-items-center text-white ${bgClass} border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body ${type === 'warning' || type === 'info' ? 'text-dark' : ''}">
                        ${message}
                    </div>
                    <button type="button" class="${closeBtnClass} me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', html);

        const toastEl = document.getElementById(id);
        const toast = new bootstrap.Toast(toastEl, { delay: 2500 });
        toast.show();

        toastEl.addEventListener('hidden.bs.toast', () => {
            toastEl.remove();
        });
    }

    function clearValidationErrors() {
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        document.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');

        if (identityFormAlert) {
            identityFormAlert.classList.add('d-none');
            identityFormAlert.innerHTML = '';
        }
    }

    function setFieldError(fieldId, message) {
        const field = document.getElementById(fieldId);
        const error = document.getElementById(`error_${fieldId}`);

        if (field) field.classList.add('is-invalid');
        if (error) error.textContent = message;
    }

    function validateParticipantForm() {
        clearValidationErrors();

        let isValid = true;

        if (!participantName.value.trim()) {
            setFieldError('participant_name', 'Nama wajib diisi.');
            isValid = false;
        }

        if (!participantPhone.value.trim()) {
            setFieldError('participant_phone', 'Nomor HP wajib diisi.');
            isValid = false;
        }

        const emailValue = participantEmail.value.trim();
        if (!emailValue) {
            setFieldError('participant_email', 'Email wajib diisi.');
            isValid = false;
        } else {
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(emailValue)) {
                setFieldError('participant_email', 'Format email tidak valid.');
                isValid = false;
            }
        }

        if (!isValid && identityFormAlert) {
            identityFormAlert.classList.remove('d-none');
            identityFormAlert.innerHTML = 'Lengkapi data peserta dengan benar sebelum mulai quiz.';
        }

        return isValid;
    }

    function getAnsweredCount() {
        return Object.keys(answers).filter((key) => !!answers[key]).length;
    }

    function updateProgress() {
        const total = quizQuestions.length;
        const answered = getAnsweredCount();
        const percentage = total > 0 ? (answered / total) * 100 : 0;

        if (progressBar) {
            progressBar.style.width = `${percentage}%`;
        }

        if (progressText) {
            progressText.textContent = `${answered} dari ${total} pertanyaan terjawab`;
        }
    }

    function renderQuestionNavigator() {
        if (!questionNavList) return;

        questionNavList.innerHTML = '';

        quizQuestions.forEach((question, index) => {
            const isActive = index === currentQuestionIndex;
            const isAnswered = !!answers[question.id];

            const html = `
                <button type="button"
                        class="quiz-nav-item ${isActive ? 'active' : ''} ${isAnswered ? 'done' : ''}"
                        data-index="${index}">
                    <span class="quiz-nav-label-wrap">
                        <span class="quiz-nav-label">Question ${index + 1}</span>
                        <span class="quiz-nav-sub">${isAnswered ? 'Answered' : 'Not answered yet'}</span>
                    </span>
                    <span class="quiz-nav-status ${isAnswered ? 'done' : ''}">
                        <i class="bi ${isAnswered ? 'bi-check-lg' : 'bi-circle'}"></i>
                    </span>
                </button>
            `;

            questionNavList.insertAdjacentHTML('beforeend', html);
        });

        questionNavList.querySelectorAll('.quiz-nav-item').forEach((btn) => {
            btn.addEventListener('click', function () {
                currentQuestionIndex = parseInt(this.dataset.index, 10);
                renderQuestion();
            });
        });

        updateProgress();
    }

    function renderQuestion() {
        const total = quizQuestions.length;
        const question = quizQuestions[currentQuestionIndex];

        if (!question) return;

        questionCounter.textContent = `Question ${currentQuestionIndex + 1} of ${total}`;
        questionTitle.textContent = question.question_text;

        optionsContainer.innerHTML = '';

        question.options.forEach((option) => {
            const checked = String(answers[question.id] || '') === String(option.id) ? 'checked' : '';

            const html = `
                <div class="quiz-answer-card">
                    <input
                        type="radio"
                        class="quiz-answer-input"
                        name="question_${question.id}"
                        id="option_${option.id}"
                        value="${option.id}"
                        ${checked}
                    >
                    <label class="quiz-answer-label" for="option_${option.id}">
                        <span class="quiz-answer-key">${option.key}</span>
                        <span class="quiz-answer-text">${option.option_text}</span>
                        <span class="quiz-answer-check">
                            <i class="bi bi-check-circle-fill"></i>
                        </span>
                    </label>
                </div>
            `;

            optionsContainer.insertAdjacentHTML('beforeend', html);
        });

        document.querySelectorAll(`input[name="question_${question.id}"]`).forEach((input) => {
            input.addEventListener('change', function () {
                answers[question.id] = this.value;
                renderQuestionNavigator();
                updateActionButtons();
            });
        });

        renderQuestionNavigator();
        updateActionButtons();
    }

    function updateActionButtons() {
        const total = quizQuestions.length;

        if (prevBtn) {
            prevBtn.disabled = currentQuestionIndex === 0;
        }

        if (nextBtn && finishBtn) {
            if (currentQuestionIndex === total - 1) {
                nextBtn.classList.add('d-none');
                finishBtn.classList.remove('d-none');
            } else {
                nextBtn.classList.remove('d-none');
                finishBtn.classList.add('d-none');
            }
        }
    }

    async function createParticipant() {
        const response = await fetch(participantStoreUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                quiz_id: quizId,
                name: participant.name,
                email: participant.email,
                phone: participant.phone,
            }),
        });

        const result = await response.json();

        if (!response.ok) {
            if (response.status === 422 && result.errors) {
                Object.keys(result.errors).forEach((field) => {
                    const firstError = result.errors[field]?.[0] || 'Input tidak valid.';
                    setFieldError(`participant_${field}`, firstError);
                });

                if (identityFormAlert) {
                    identityFormAlert.classList.remove('d-none');
                    identityFormAlert.innerHTML = result.message || 'Data peserta belum valid.';
                }
            }

            throw new Error(result.message || 'Gagal menyimpan data peserta.');
        }

        return result;
    }

    async function submitAnswers() {
        const payloadAnswers = Object.entries(answers).map(([questionId, optionId]) => ({
            question_id: Number(questionId),
            option_id: Number(optionId),
        }));

        const response = await fetch(submitUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                quiz_id: quizId,
                participant_id: participantId,
                answers: payloadAnswers,
            }),
        });

        const result = await response.json();

        if (!response.ok) {
            throw new Error(result.message || 'Gagal mengirim jawaban quiz.');
        }

        return result;
    }

    function startQuizFlow() {
        quizIdentityStep.classList.add('d-none');
        quizFinishStep.classList.add('d-none');
        quizQuestionStep.classList.remove('d-none');

        currentQuestionIndex = 0;
        answers = {};

        renderQuestion();
        updateProgress();
    }

    function finishQuizFlow() {
        quizQuestionStep.classList.add('d-none');
        quizFinishStep.classList.remove('d-none');

        summaryName.textContent = participant.name || '-';
        summaryAnswered.textContent = getAnsweredCount();
    }

    if (participantForm) {
        participantForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            if (isSubmittingParticipant) return;

            if (!validateParticipantForm()) {
                return;
            }

            participant = {
                name: participantName.value.trim(),
                phone: participantPhone.value.trim(),
                email: participantEmail.value.trim(),
            };

            isSubmittingParticipant = true;
            startQuizBtn.disabled = true;
            startQuizBtn.innerHTML = '<span class="btn-text"><span class="spinner-border spinner-border-sm me-2"></span>Menyimpan peserta...</span>';

            try {
                const result = await createParticipant();
                participantId = result.data?.id || null;

                if (!participantId) {
                    throw new Error('Participant ID tidak ditemukan dari response API.');
                }

                startQuizFlow();
                showToast('Data peserta berhasil disimpan.', 'success');
            } catch (error) {
                showToast(error.message || 'Gagal menyimpan data peserta.', 'danger');
            } finally {
                isSubmittingParticipant = false;
                startQuizBtn.disabled = false;
                startQuizBtn.innerHTML = '<span class="btn-text"><i class="bi bi-play-fill me-1"></i>Mulai Quiz</span>';
            }
        });
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', function () {
            if (currentQuestionIndex > 0) {
                currentQuestionIndex -= 1;
                renderQuestion();
            }
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', function () {
            const currentQuestion = quizQuestions[currentQuestionIndex];
            const hasAnswer = !!answers[currentQuestion.id];

            if (!hasAnswer) {
                showToast('Pilih salah satu jawaban dulu ya.', 'warning');
                return;
            }

            if (currentQuestionIndex < quizQuestions.length - 1) {
                currentQuestionIndex += 1;
                renderQuestion();
            }
        });
    }

    if (finishBtn) {
        finishBtn.addEventListener('click', async function () {
            if (isSubmittingAnswers) return;

            const unanswered = quizQuestions.filter((question) => !answers[question.id]);

            if (unanswered.length > 0) {
                showToast('Masih ada pertanyaan yang belum dijawab.', 'warning');
                return;
            }

            isSubmittingAnswers = true;
            finishBtn.disabled = true;
            finishBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Mengirim jawaban...';

            try {
                await submitAnswers();
                finishQuizFlow();
                showToast('Jawaban berhasil dikirim.', 'success');
            } catch (error) {
                showToast(error.message || 'Gagal mengirim jawaban.', 'danger');
            } finally {
                isSubmittingAnswers = false;
                finishBtn.disabled = false;
                finishBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Finish Quiz';
            }
        });
    }
</script>
@endpush