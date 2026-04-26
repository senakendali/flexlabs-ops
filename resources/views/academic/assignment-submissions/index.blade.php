@extends('layouts.app-dashboard')

@section('title', 'Assignment Submissions')

@section('content')
<div class="container-fluid px-4 py-4">

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Academic Management</div>
                <h1 class="page-title mb-2">Assignment Submissions</h1>
                <p class="page-subtitle mb-0">
                    Review jawaban student, berikan score, feedback, atau kembalikan untuk revisi.
                </p>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-inbox-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Total</div>
                        <div class="stat-value">{{ $stats['total'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Total submission yang masuk.</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
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
                <div class="stat-description">Submission menunggu review.</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div>
                        <div class="stat-title">Late</div>
                        <div class="stat-value">{{ $stats['late'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Submission lewat deadline.</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div>
                        <div class="stat-title">Reviewed</div>
                        <div class="stat-value">{{ $stats['reviewed'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Submission sudah dinilai.</div>
            </div>
        </div>

        <div class="col-xl col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </div>
                    <div>
                        <div class="stat-title">Returned</div>
                        <div class="stat-value">{{ $stats['returned'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Submission dikembalikan untuk revisi.</div>
            </div>
        </div>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Filter Submissions</h5>
                <p class="content-card-subtitle mb-0">
                    Cari berdasarkan student, assignment, batch, atau status submission.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            <form method="GET" action="{{ route('assignment-submissions.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-xl-3 col-md-6">
                        <label class="form-label">Keyword</label>
                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            value="{{ request('search') }}"
                            placeholder="Cari student / assignment / jawaban..."
                        >
                    </div>

                    <div class="col-xl-2 col-md-6">
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
                        <label class="form-label">Assignment</label>
                        <select name="assignment_id" class="form-select">
                            <option value="">All Assignments</option>
                            @foreach($assignments ?? [] as $assignment)
                                <option value="{{ $assignment->id }}" {{ request('assignment_id') == $assignment->id ? 'selected' : '' }}>
                                    {{ $assignment->title }}
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
                            <a href="{{ route('assignment-submissions.index') }}" class="btn btn-outline-secondary btn-modern">
                                <i class="bi bi-arrow-counterclockwise me-2"></i>Reset
                            </a>
                            <button type="submit" class="btn btn-primary btn-modern">
                                <i class="bi bi-funnel me-2"></i>Filter
                            </button>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Batch Assignment</label>
                        <select name="batch_assignment_id" class="form-select">
                            <option value="">All Batch Assignments</option>
                            @foreach($batchAssignments ?? [] as $batchAssignment)
                                <option value="{{ $batchAssignment->id }}" {{ request('batch_assignment_id') == $batchAssignment->id ? 'selected' : '' }}>
                                    {{ $batchAssignment->batch->program->name ?? 'Program' }}
                                    -
                                    {{ $batchAssignment->batch->name ?? 'Batch' }}
                                    —
                                    {{ $batchAssignment->assignment->title ?? 'Assignment' }}
                                    @if($batchAssignment->due_at)
                                        (Deadline: {{ $batchAssignment->due_at->format('d M Y, H:i') }})
                                    @endif
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
                <h5 class="content-card-title mb-1">Submission List</h5>
                <p class="content-card-subtitle mb-0">
                    Data submission dari student. Review dapat dilakukan langsung dari halaman ini.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            @if(($submissions ?? collect())->count())
                <div class="entity-list">
                    @foreach($submissions as $submission)
                        @php
                            $statusClass = match($submission->status) {
                                'submitted' => 'ui-badge-primary',
                                'late' => 'ui-badge-warning',
                                'reviewed' => 'ui-badge-success',
                                'returned' => 'ui-badge-danger',
                                default => 'ui-badge-muted',
                            };

                            $assignment = $submission->assignment ?? $submission->batchAssignment?->assignment;
                            $batch = $submission->batch ?? $submission->batchAssignment?->batch;

                            $maxScore = $submission->batchAssignment?->max_score
                                ?? $assignment?->max_score
                                ?? 100;

                            $answerPreview = \Illuminate\Support\Str::limit(strip_tags($submission->answer_text ?? ''), 180);
                            $feedbackPreview = \Illuminate\Support\Str::limit(strip_tags($submission->feedback ?? ''), 140);

                            $submittedAt = $submission->submitted_at
                                ? $submission->submitted_at->format('d M Y, H:i')
                                : 'Not submitted';

                            $reviewedAt = $submission->reviewed_at
                                ? $submission->reviewed_at->format('d M Y, H:i')
                                : 'Not reviewed';

                            $studentLabel = $submission->student?->name ?? 'Unknown Student';
                            $assignmentLabel = $assignment?->title ?? 'Untitled Assignment';
                            $batchLabel = ($batch?->program?->name ? $batch->program->name . ' - ' : '') . ($batch?->name ?? 'Batch');

                            $detailPayload = [
                                'student' => $studentLabel,
                                'email' => $submission->student?->email,
                                'phone' => $submission->student?->phone,
                                'assignment' => $assignmentLabel,
                                'batch' => $batchLabel,
                                'status' => $statuses[$submission->status] ?? ucfirst($submission->status),
                                'score' => $submission->score,
                                'max_score' => $maxScore,
                                'answer_text' => $submission->answer_text,
                                'answer_url' => $submission->answer_url,
                                'submitted_file' => $submission->submitted_file,
                                'feedback' => $submission->feedback,
                                'submitted_at' => $submittedAt,
                                'reviewed_at' => $reviewedAt,
                                'reviewed_by' => $submission->reviewedBy?->name,
                            ];
                        @endphp

                        <script type="application/json" id="submission-detail-{{ $submission->id }}">
                            @json($detailPayload)
                        </script>

                        <div class="entity-card">
                            <div class="entity-main">
                                <div class="entity-icon">
                                    <i class="bi bi-inbox-fill"></i>
                                </div>

                                <div class="entity-info">
                                    <div class="entity-title-row">
                                        <div>
                                            <h5 class="entity-title">{{ $assignmentLabel }}</h5>
                                            <div class="entity-subtitle">
                                                {{ $studentLabel }} • {{ $batchLabel }}
                                            </div>
                                        </div>

                                        <div class="entity-badges">
                                            <span class="ui-badge {{ $statusClass }}">
                                                {{ $statuses[$submission->status] ?? ucfirst($submission->status) }}
                                            </span>

                                            @if(!is_null($submission->score))
                                                <span class="ui-badge ui-badge-success">
                                                    Score {{ $submission->score }}/{{ $maxScore }}
                                                </span>
                                            @else
                                                <span class="ui-badge ui-badge-muted">
                                                    Not Scored
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="entity-meta">
                                        <span>
                                            <i class="bi bi-calendar-check me-1"></i>Submitted: {{ $submittedAt }}
                                        </span>

                                        <span>
                                            <i class="bi bi-person-check me-1"></i>Reviewed: {{ $reviewedAt }}
                                        </span>

                                        @if($submission->reviewedBy)
                                            <span>
                                                <i class="bi bi-person-badge me-1"></i>{{ $submission->reviewedBy->name }}
                                            </span>
                                        @endif
                                    </div>

                                    @if(!empty($answerPreview))
                                        <div class="richtext-preview">
                                            <div class="richtext-content">
                                                {{ $answerPreview }}
                                            </div>
                                        </div>
                                    @endif

                                    @if(!empty($feedbackPreview))
                                        <div class="mt-2">
                                            <span class="ui-badge ui-badge-info mb-2">Feedback</span>
                                            <div class="text-muted small">
                                                {{ $feedbackPreview }}
                                            </div>
                                        </div>
                                    @endif

                                    <div class="info-grid">
                                        <div class="info-item">
                                            <div class="info-label">Answer URL</div>
                                            <div class="info-value">
                                                @if($submission->answer_url)
                                                    <a href="{{ $submission->answer_url }}" target="_blank" rel="noopener">
                                                        Open Link
                                                    </a>
                                                @else
                                                    <span class="text-muted">Not set</span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="info-item">
                                            <div class="info-label">Submitted File</div>
                                            <div class="info-value">
                                                @if($submission->submitted_file)
                                                    <a href="{{ $submission->submitted_file }}" target="_blank" rel="noopener">
                                                        Open File
                                                    </a>
                                                @else
                                                    <span class="text-muted">Not set</span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="info-item">
                                            <div class="info-label">Max Score</div>
                                            <div class="info-value">{{ $maxScore }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="entity-actions">
                                <button
                                    type="button"
                                    class="btn btn-outline-primary btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#detailModal"
                                    data-detail-json-id="submission-detail-{{ $submission->id }}"
                                >
                                    <i class="bi bi-eye me-1"></i>View
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-outline-secondary btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#reviewModal"
                                    data-review-url="{{ route('assignment-submissions.review', $submission->id) }}"
                                    data-title="{{ $assignmentLabel }}"
                                    data-student="{{ $studentLabel }}"
                                    data-score="{{ $submission->score }}"
                                    data-max-score="{{ $maxScore }}"
                                    data-feedback="{{ $submission->feedback }}"
                                >
                                    <i class="bi bi-check2-square me-1"></i>Review
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-outline-warning btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#returnModal"
                                    data-return-url="{{ route('assignment-submissions.return-revision', $submission->id) }}"
                                    data-title="{{ $assignmentLabel }}"
                                    data-student="{{ $studentLabel }}"
                                    data-feedback="{{ $submission->feedback }}"
                                >
                                    <i class="bi bi-arrow-counterclockwise me-1"></i>Return
                                </button>

                                @if(in_array($submission->status, ['draft', 'returned']))
                                    <button
                                        type="button"
                                        class="btn btn-outline-success btn-sm"
                                        data-action-url="{{ route('assignment-submissions.mark-submitted', $submission->id) }}"
                                        data-action-status="submitted"
                                        data-action-message="Tandai submission ini sebagai Submitted?"
                                    >
                                        <i class="bi bi-send-check me-1"></i>Mark Submitted
                                    </button>
                                @endif

                                @if($submission->status !== 'late')
                                    <button
                                        type="button"
                                        class="btn btn-outline-warning btn-sm"
                                        data-action-url="{{ route('assignment-submissions.mark-submitted', $submission->id) }}"
                                        data-action-status="late"
                                        data-action-message="Tandai submission ini sebagai Late?"
                                    >
                                        <i class="bi bi-exclamation-triangle me-1"></i>Mark Late
                                    </button>
                                @endif

                                <button
                                    type="button"
                                    class="btn btn-outline-danger btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#deleteConfirmModal"
                                    data-delete-name="{{ $assignmentLabel . ' - ' . $studentLabel }}"
                                    data-delete-url="{{ route('assignment-submissions.destroy', $submission->id) }}"
                                >
                                    <i class="bi bi-trash me-1"></i>Delete
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4">
                    {{ $submissions->links() }}
                </div>
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-inbox"></i>
                    </div>
                    <h5 class="empty-state-title">Belum ada submission</h5>
                    <p class="empty-state-text mb-0">
                        Submission akan muncul setelah student mengumpulkan assignment. Untuk testing, bisa buat seed dummy submission dulu.
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Detail Modal --}}
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content custom-modal">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title" id="detailModalLabel">Submission Detail</h5>
                    <p class="text-muted mb-0" id="detailModalSubtitle">Detail jawaban dan feedback student.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pt-4">
                <div class="info-grid mb-3">
                    <div class="info-item">
                        <div class="info-label">Student</div>
                        <div class="info-value" id="detailStudent">-</div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Assignment</div>
                        <div class="info-value" id="detailAssignment">-</div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Batch</div>
                        <div class="info-value" id="detailBatch">-</div>
                    </div>
                </div>

                <div class="info-grid mb-3">
                    <div class="info-item">
                        <div class="info-label">Status</div>
                        <div class="info-value" id="detailStatus">-</div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Score</div>
                        <div class="info-value" id="detailScore">-</div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Submitted At</div>
                        <div class="info-value" id="detailSubmittedAt">-</div>
                    </div>
                </div>

                <div class="soft-panel mb-3">
                    <div class="soft-panel-header">
                        <div class="soft-panel-title">
                            <i class="bi bi-chat-left-text me-2"></i>Answer Text
                        </div>
                    </div>
                    <div id="detailAnswerText" class="text-muted"></div>
                </div>

                <div class="info-grid mb-3">
                    <div class="info-item">
                        <div class="info-label">Answer URL</div>
                        <div class="info-value" id="detailAnswerUrl">-</div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Submitted File</div>
                        <div class="info-value" id="detailSubmittedFile">-</div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Reviewed By</div>
                        <div class="info-value" id="detailReviewedBy">-</div>
                    </div>
                </div>

                <div class="soft-panel">
                    <div class="soft-panel-header">
                        <div class="soft-panel-title">
                            <i class="bi bi-chat-square-quote me-2"></i>Feedback
                        </div>
                    </div>
                    <div id="detailFeedback" class="text-muted"></div>
                </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Review Modal --}}
<div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content custom-modal">
            <form id="reviewForm">
                @csrf

                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title" id="reviewModalLabel">Review Submission</h5>
                        <p class="text-muted mb-0" id="reviewModalSubtitle">Berikan score dan feedback.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-4">
                    <div class="alert alert-danger d-none form-alert" role="alert"></div>

                    <div class="soft-panel mb-3">
                        <div class="soft-panel-title" id="reviewAssignmentTitle">-</div>
                        <div class="soft-panel-subtitle" id="reviewStudentName">-</div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Score</label>
                            <input type="number" name="score" class="form-control" min="0" max="999" required>
                            <div class="form-text" id="reviewMaxScoreText">Max score: -</div>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label">Feedback</label>
                            <textarea
                                name="feedback"
                                rows="5"
                                class="form-control"
                                placeholder="Tulis feedback untuk student..."
                            ></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary btn-modern submit-btn">
                        Save Review
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Return Modal --}}
<div class="modal fade" id="returnModal" tabindex="-1" aria-labelledby="returnModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content custom-modal">
            <form id="returnForm">
                @csrf

                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title" id="returnModalLabel">Return Revision</h5>
                        <p class="text-muted mb-0" id="returnModalSubtitle">Kembalikan submission untuk revisi.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-4">
                    <div class="alert alert-danger d-none form-alert" role="alert"></div>

                    <div class="soft-panel mb-3">
                        <div class="soft-panel-title" id="returnAssignmentTitle">-</div>
                        <div class="soft-panel-subtitle" id="returnStudentName">-</div>
                    </div>

                    <label class="form-label">Revision Feedback</label>
                    <textarea
                        name="feedback"
                        rows="6"
                        class="form-control"
                        placeholder="Jelaskan apa yang perlu direvisi..."
                        required
                    ></textarea>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-warning btn-modern submit-btn">
                        Return Revision
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
                        <h5 class="modal-title" id="deleteConfirmModalLabel">Delete Submission</h5>
                        <p class="text-muted mb-0">
                            Konfirmasi sebelum menghapus submission.
                        </p>
                    </div>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pt-4">
                <div class="delete-confirm-message">
                    <div class="delete-confirm-label">Submission yang akan dihapus</div>
                    <div class="delete-confirm-name" id="deleteConfirmName">-</div>
                </div>

                <div class="delete-confirm-warning mt-3">
                    Submission yang sudah dihapus tidak bisa dikembalikan.
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
        document.querySelector('#reviewForm input[name="_token"]')?.value
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

    function nl2br(value) {
        return escapeHtml(value || '').replace(/\n/g, '<br>');
    }

    function linkHtml(url, label = 'Open Link') {
        if (!url) return '<span class="text-muted">Not set</span>';

        const safeUrl = escapeHtml(url);

        return `<a href="${safeUrl}" target="_blank" rel="noopener">${escapeHtml(label)}</a>`;
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

    async function postForm(actionUrl, form, submitBtn, successMessage) {
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

    function setupDetailModal() {
        const modalEl = document.getElementById('detailModal');

        if (!modalEl) return;

        modalEl.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const payload = getJsonFromScript(button?.dataset?.detailJsonId) || {};

            modalEl.querySelector('#detailStudent').textContent = payload.student || '-';
            modalEl.querySelector('#detailAssignment').textContent = payload.assignment || '-';
            modalEl.querySelector('#detailBatch').textContent = payload.batch || '-';
            modalEl.querySelector('#detailStatus').textContent = payload.status || '-';
            modalEl.querySelector('#detailScore').textContent = payload.score !== null && payload.score !== undefined
                ? `${payload.score}/${payload.max_score || 100}`
                : `Not scored / ${payload.max_score || 100}`;
            modalEl.querySelector('#detailSubmittedAt').textContent = payload.submitted_at || '-';
            modalEl.querySelector('#detailReviewedBy').textContent = payload.reviewed_by || '-';

            modalEl.querySelector('#detailAnswerText').innerHTML = payload.answer_text
                ? nl2br(payload.answer_text)
                : '<span class="text-muted">No answer text.</span>';

            modalEl.querySelector('#detailAnswerUrl').innerHTML = linkHtml(payload.answer_url, 'Open Answer URL');
            modalEl.querySelector('#detailSubmittedFile').innerHTML = linkHtml(payload.submitted_file, 'Open Submitted File');

            modalEl.querySelector('#detailFeedback').innerHTML = payload.feedback
                ? nl2br(payload.feedback)
                : '<span class="text-muted">No feedback yet.</span>';
        });
    }

    function setupReviewModal() {
        const modalEl = document.getElementById('reviewModal');
        const form = document.getElementById('reviewForm');

        if (!modalEl || !form) return;

        modalEl.addEventListener('show.bs.modal', function (event) {
            form.reset();
            resetFormState(form);

            const button = event.relatedTarget;

            modalEl.dataset.reviewUrl = button?.dataset?.reviewUrl || '';

            form.querySelector('input[name="score"]').value = button?.dataset?.score || '';
            form.querySelector('textarea[name="feedback"]').value = button?.dataset?.feedback || '';

            modalEl.querySelector('#reviewAssignmentTitle').textContent = button?.dataset?.title || '-';
            modalEl.querySelector('#reviewStudentName').textContent = button?.dataset?.student || '-';
            modalEl.querySelector('#reviewMaxScoreText').textContent = `Max score: ${button?.dataset?.maxScore || 100}`;
        });

        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            const actionUrl = modalEl.dataset.reviewUrl;
            const submitBtn = form.querySelector('.submit-btn');

            if (!actionUrl) {
                showToast('Route review belum tersedia.', 'error');
                return;
            }

            await postForm(actionUrl, form, submitBtn, 'Submission berhasil direview.');
        });
    }

    function setupReturnModal() {
        const modalEl = document.getElementById('returnModal');
        const form = document.getElementById('returnForm');

        if (!modalEl || !form) return;

        modalEl.addEventListener('show.bs.modal', function (event) {
            form.reset();
            resetFormState(form);

            const button = event.relatedTarget;

            modalEl.dataset.returnUrl = button?.dataset?.returnUrl || '';

            form.querySelector('textarea[name="feedback"]').value = button?.dataset?.feedback || '';

            modalEl.querySelector('#returnAssignmentTitle').textContent = button?.dataset?.title || '-';
            modalEl.querySelector('#returnStudentName').textContent = button?.dataset?.student || '-';
        });

        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            const actionUrl = modalEl.dataset.returnUrl;
            const submitBtn = form.querySelector('.submit-btn');

            if (!actionUrl) {
                showToast('Route return revision belum tersedia.', 'error');
                return;
            }

            await postForm(actionUrl, form, submitBtn, 'Submission berhasil dikembalikan untuk revisi.');
        });
    }

    function setupMarkStatusButtons() {
        document.querySelectorAll('[data-action-url][data-action-status]').forEach(function (button) {
            button.addEventListener('click', async function () {
                const actionUrl = button.dataset.actionUrl;
                const status = button.dataset.actionStatus;
                const message = button.dataset.actionMessage || 'Update status submission?';

                if (!actionUrl || !status) {
                    showToast('Route status belum tersedia.', 'error');
                    return;
                }

                if (!confirm(message)) return;

                button.disabled = true;
                const defaultText = button.innerHTML;
                button.innerHTML = 'Updating...';

                const formData = new FormData();
                formData.append('_token', csrfToken);
                formData.append('status', status);

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
                        showToast(data.message || 'Gagal update status submission.', 'error');
                        button.disabled = false;
                        button.innerHTML = defaultText;
                        return;
                    }

                    showToast(data.message || 'Status submission berhasil diperbarui.', 'success');

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
                    showToast(data.message || 'Gagal menghapus submission.', 'error');
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = '<i class="bi bi-trash me-2"></i>Delete';
                    return;
                }

                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) {
                    modalInstance.hide();
                }

                showToast(data.message || 'Submission berhasil dihapus.', 'success');

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

    setupDetailModal();
    setupReviewModal();
    setupReturnModal();
    setupMarkStatusButtons();
    setupDeleteConfirmModal();
});
</script>
@endpush