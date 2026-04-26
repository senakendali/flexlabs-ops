@extends('layouts.app-dashboard')

@section('title', 'Learning Quiz Attempts')

@section('content')
<div class="container-fluid px-4 py-4">

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Academic Management</div>
                <h1 class="page-title mb-2">Learning Quiz Attempts</h1>
                <p class="page-subtitle mb-0">
                    Pantau hasil pengerjaan quiz student, status attempt, score, percentage, dan hasil kelulusan.
                </p>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-activity"></i>
                    </div>
                    <div>
                        <div class="stat-title">Total</div>
                        <div class="stat-value">{{ $stats['total'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Total quiz attempt.</div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div>
                        <div class="stat-title">In Progress</div>
                        <div class="stat-value">{{ $stats['in_progress'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Sedang dikerjakan.</div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-send-check"></i>
                    </div>
                    <div>
                        <div class="stat-title">Submitted</div>
                        <div class="stat-value">{{ $stats['submitted'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Menunggu grading.</div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div>
                        <div class="stat-title">Graded</div>
                        <div class="stat-value">{{ $stats['graded'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Sudah dinilai.</div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-award-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Passed</div>
                        <div class="stat-value">{{ $stats['passed'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Lulus passing score.</div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-x-circle"></i>
                    </div>
                    <div>
                        <div class="stat-title">Failed</div>
                        <div class="stat-value">{{ $stats['failed'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Belum mencapai passing score.</div>
            </div>
        </div>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Filter Attempts</h5>
                <p class="content-card-subtitle mb-0">
                    Cari berdasarkan student, quiz, batch, status, atau hasil kelulusan.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            <form method="GET" action="{{ route('learning-quiz-attempts.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-xl-3 col-md-6">
                        <label class="form-label">Keyword</label>
                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            value="{{ request('search') }}"
                            placeholder="Cari student / quiz / batch..."
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

                    <div class="col-xl-3 col-md-6">
                        <label class="form-label">Batch Learning Quiz</label>
                        <select name="batch_learning_quiz_id" class="form-select">
                            <option value="">All Batch Quizzes</option>
                            @foreach($batchLearningQuizzes ?? [] as $batchQuiz)
                                <option value="{{ $batchQuiz->id }}" {{ request('batch_learning_quiz_id') == $batchQuiz->id ? 'selected' : '' }}>
                                    {{ $batchQuiz->batch->program->name ?? 'Program' }}
                                    -
                                    {{ $batchQuiz->batch->name ?? 'Batch' }}
                                    —
                                    {{ $batchQuiz->learningQuiz->title ?? 'Quiz' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-3 col-md-6">
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

                    <div class="col-xl-3 col-md-6">
                        <label class="form-label">Result</label>
                        <select name="passed" class="form-select">
                            <option value="">All Results</option>
                            <option value="1" {{ request('passed') === '1' ? 'selected' : '' }}>Passed</option>
                            <option value="0" {{ request('passed') === '0' ? 'selected' : '' }}>Failed</option>
                        </select>
                    </div>

                    <div class="col-xl-6">
                        <div class="d-flex gap-2 justify-content-xl-end flex-wrap">
                            <a href="{{ route('learning-quiz-attempts.index') }}" class="btn btn-outline-secondary btn-modern">
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
                <h5 class="content-card-title mb-1">Attempt List</h5>
                <p class="content-card-subtitle mb-0">
                    List pengerjaan quiz dari student. Klik detail untuk melihat jawaban per question.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            @if(($attempts ?? collect())->count())
                <div class="entity-list">
                    @foreach($attempts as $attempt)
                        @php
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

                            $batchLabel = ($attempt->batch?->program?->name ? $attempt->batch->program->name . ' - ' : '') . ($attempt->batch->name ?? 'Batch');
                        @endphp

                        <div class="entity-card">
                            <div class="entity-main">
                                <div class="entity-icon">
                                    <i class="bi bi-activity"></i>
                                </div>

                                <div class="entity-info">
                                    <div class="entity-title-row">
                                        <div>
                                            <h5 class="entity-title">
                                                {{ $attempt->learningQuiz->title ?? 'Untitled Quiz' }}
                                            </h5>
                                            <div class="entity-subtitle">
                                                {{ $attempt->student->name ?? 'Unknown Student' }} • {{ $batchLabel }}
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
                                            <i class="bi bi-envelope me-1"></i>{{ $attempt->student->email ?? '-' }}
                                        </span>

                                        <span>
                                            <i class="bi bi-list-check me-1"></i>{{ $attempt->answers_count ?? 0 }} Answers
                                        </span>

                                        <span>
                                            <i class="bi bi-person-check me-1"></i>
                                            Graded by: {{ $attempt->gradedBy->name ?? '-' }}
                                        </span>
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
                                            <div class="info-label">Graded At</div>
                                            <div class="info-value">
                                                {{ $attempt->graded_at ? $attempt->graded_at->format('d M Y, H:i') : '-' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="entity-actions">
                                <a
                                    href="{{ route('learning-quiz-attempts.show', $attempt->id) }}"
                                    class="btn btn-outline-primary btn-sm"
                                >
                                    <i class="bi bi-eye me-1"></i>Detail
                                </a>

                                <button
                                    type="button"
                                    class="btn btn-outline-success btn-sm"
                                    data-action-url="{{ route('learning-quiz-attempts.grade', $attempt->id) }}"
                                    data-action-message="Hitung ulang score dan tandai attempt ini sebagai graded?"
                                >
                                    <i class="bi bi-calculator me-1"></i>Recalculate
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-outline-secondary btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#statusModal"
                                    data-status-url="{{ route('learning-quiz-attempts.status', $attempt->id) }}"
                                    data-current-status="{{ $attempt->status }}"
                                    data-title="{{ $attempt->learningQuiz->title ?? 'Quiz Attempt' }}"
                                    data-student="{{ $attempt->student->name ?? 'Student' }}"
                                >
                                    <i class="bi bi-arrow-repeat me-1"></i>Status
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-outline-danger btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#deleteConfirmModal"
                                    data-delete-name="{{ ($attempt->learningQuiz->title ?? 'Quiz') . ' - ' . ($attempt->student->name ?? 'Student') }}"
                                    data-delete-url="{{ route('learning-quiz-attempts.destroy', $attempt->id) }}"
                                >
                                    <i class="bi bi-trash me-1"></i>Delete
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4">
                    {{ $attempts->links() }}
                </div>
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-activity"></i>
                    </div>
                    <h5 class="empty-state-title">Belum ada quiz attempt</h5>
                    <p class="empty-state-text mb-0">
                        Attempt akan muncul setelah student mulai mengerjakan quiz dari LMS.
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Status Modal --}}
<div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal">
            <form id="statusForm">
                @csrf

                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title" id="statusModalLabel">Update Attempt Status</h5>
                        <p class="text-muted mb-0" id="statusModalSubtitle">Perbarui status attempt.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-4">
                    <div class="alert alert-danger d-none form-alert" role="alert"></div>

                    <div class="soft-panel mb-3">
                        <div class="soft-panel-title" id="statusQuizTitle">-</div>
                        <div class="soft-panel-subtitle" id="statusStudentName">-</div>
                    </div>

                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                        @foreach($statuses ?? [] as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary btn-modern submit-btn">
                        Save Status
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
                        <h5 class="modal-title" id="deleteConfirmModalLabel">Delete Quiz Attempt</h5>
                        <p class="text-muted mb-0">
                            Konfirmasi sebelum menghapus quiz attempt.
                        </p>
                    </div>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pt-4">
                <div class="delete-confirm-message">
                    <div class="delete-confirm-label">Attempt yang akan dihapus</div>
                    <div class="delete-confirm-name" id="deleteConfirmName">-</div>
                </div>

                <div class="delete-confirm-warning mt-3">
                    Attempt dan jawaban student yang terkait akan ikut terhapus.
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
        document.querySelector('#statusForm input[name="_token"]')?.value
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

    async function postForm(actionUrl, form, successMessage) {
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

    function setupStatusModal() {
        const modalEl = document.getElementById('statusModal');
        const form = document.getElementById('statusForm');

        if (!modalEl || !form) return;

        modalEl.addEventListener('show.bs.modal', function (event) {
            form.reset();
            resetFormState(form);

            const button = event.relatedTarget;

            modalEl.dataset.statusUrl = button?.dataset?.statusUrl || '';
            form.querySelector('select[name="status"]').value = button?.dataset?.currentStatus || 'submitted';

            modalEl.querySelector('#statusQuizTitle').textContent = button?.dataset?.title || '-';
            modalEl.querySelector('#statusStudentName').textContent = button?.dataset?.student || '-';
        });

        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            const actionUrl = modalEl.dataset.statusUrl;

            if (!actionUrl) {
                showToast('Route status belum tersedia.', 'error');
                return;
            }

            await postForm(actionUrl, form, 'Status attempt berhasil diperbarui.');
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
                    showToast(data.message || 'Gagal menghapus attempt.', 'error');
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = '<i class="bi bi-trash me-2"></i>Delete';
                    return;
                }

                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) {
                    modalInstance.hide();
                }

                showToast(data.message || 'Quiz attempt berhasil dihapus.', 'success');

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

    setupStatusModal();
    setupActionButtons();
    setupDeleteConfirmModal();
});
</script>
@endpush