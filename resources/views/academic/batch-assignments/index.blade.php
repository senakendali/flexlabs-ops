@extends('layouts.app-dashboard')

@section('title', 'Batch Assignments')

@section('content')
<div class="container-fluid px-4 py-4">

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Academic Management</div>
                <h1 class="page-title mb-2">Batch Assignments</h1>
                <p class="page-subtitle mb-0">
                    Atur assignment yang diberikan ke <strong>Batch</strong>, termasuk deadline, status, dan aturan late submission.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <button
                    type="button"
                    class="btn btn-primary btn-modern"
                    data-bs-toggle="modal"
                    data-bs-target="#batchAssignmentModal"
                    data-mode="create"
                >
                    <i class="bi bi-plus-circle me-2"></i>Add Batch Assignment
                </button>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-clipboard-check"></i>
                    </div>
                    <div>
                        <div class="stat-title">Total</div>
                        <div class="stat-value">{{ $stats['total'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Total assignment yang sudah diberikan ke batch.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
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
                <div class="stat-description">Assignment yang sudah bisa muncul untuk student.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
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
                <div class="stat-description">Assignment batch yang belum dipublish.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
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
                <div class="stat-description">Assignment yang sudah ditutup.</div>
            </div>
        </div>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Filter Batch Assignments</h5>
                <p class="content-card-subtitle mb-0">Cari berdasarkan assignment, batch, atau status.</p>
            </div>
        </div>

        <div class="content-card-body">
            <form method="GET" action="{{ route('batch-assignments.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-xl-3 col-md-6">
                        <label class="form-label">Keyword</label>
                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            value="{{ request('search') }}"
                            placeholder="Cari assignment / batch..."
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
                            <a href="{{ route('batch-assignments.index') }}" class="btn btn-outline-secondary btn-modern">
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
                <h5 class="content-card-title mb-1">Batch Assignment List</h5>
                <p class="content-card-subtitle mb-0">
                    Data ini menentukan assignment mana yang muncul di batch tertentu beserta deadline-nya.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            @if(($batchAssignments ?? collect())->count())
                <div class="batch-assignment-list">
                    @foreach($batchAssignments as $batchAssignment)
                        @php
                            $statusClass = match($batchAssignment->status) {
                                'published' => 'status-published',
                                'closed' => 'status-closed',
                                'archived' => 'status-archived',
                                default => 'status-draft',
                            };

                            $assignment = $batchAssignment->assignment;
                            $batch = $batchAssignment->batch;

                            $effectiveMaxScore = $batchAssignment->max_score
                                ?? $assignment?->max_score
                                ?? 100;

                            $deadlineState = 'No deadline';
                            $deadlineClass = 'deadline-neutral';

                            if ($batchAssignment->due_at) {
                                if ($batchAssignment->is_closed) {
                                    $deadlineState = 'Closed';
                                    $deadlineClass = 'deadline-closed';
                                } elseif ($batchAssignment->is_overdue) {
                                    $deadlineState = 'Overdue';
                                    $deadlineClass = 'deadline-overdue';
                                } else {
                                    $deadlineState = 'Open';
                                    $deadlineClass = 'deadline-open';
                                }
                            }

                            $targetLabel = $assignment?->subTopic
                                ? 'Sub Topic: ' . $assignment->subTopic->name
                                : 'Topic: ' . ($assignment?->topic->name ?? '-');

                            $availableAtValue = $batchAssignment->available_at?->format('Y-m-d\TH:i');
                            $dueAtValue = $batchAssignment->due_at?->format('Y-m-d\TH:i');
                            $closedAtValue = $batchAssignment->closed_at?->format('Y-m-d\TH:i');
                        @endphp

                        <div class="batch-assignment-card">
                            <div class="batch-assignment-main">
                                <div class="batch-assignment-icon">
                                    <i class="bi bi-clipboard-check"></i>
                                </div>

                                <div class="batch-assignment-info">
                                    <div class="batch-assignment-title-row">
                                        <div>
                                            <h5 class="batch-assignment-title mb-1">
                                                {{ $assignment->title ?? 'Untitled Assignment' }}
                                            </h5>

                                            <div class="batch-assignment-subtitle">
                                                {{ $batch->program->name ?? 'Program' }} - {{ $batch->name ?? 'Batch' }}
                                            </div>
                                        </div>

                                        <div class="batch-assignment-badges">
                                            <span class="assignment-status-badge {{ $statusClass }}">
                                                {{ $statuses[$batchAssignment->status] ?? ucfirst($batchAssignment->status) }}
                                            </span>

                                            <span class="deadline-badge {{ $deadlineClass }}">
                                                {{ $deadlineState }}
                                            </span>

                                            @if($batchAssignment->allow_late_submission)
                                                <span class="late-badge is-allowed">
                                                    Late Allowed
                                                </span>
                                            @else
                                                <span class="late-badge">
                                                    No Late
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="batch-assignment-meta">
                                        <span>
                                            <i class="bi bi-layers me-1"></i>{{ $targetLabel }}
                                        </span>

                                        @if($assignment?->topic?->module)
                                            <span>
                                                <i class="bi bi-folder2-open me-1"></i>{{ $assignment->topic->module->name }}
                                            </span>
                                        @endif

                                        <span>
                                            <i class="bi bi-star me-1"></i>Max Score {{ $effectiveMaxScore }}
                                        </span>

                                        <span>
                                            <i class="bi bi-inbox me-1"></i>{{ $batchAssignment->submissions_count ?? 0 }} submissions
                                        </span>
                                    </div>

                                    <div class="deadline-grid">
                                        <div class="deadline-item">
                                            <div class="deadline-label">Available At</div>
                                            <div class="deadline-value">
                                                @if($batchAssignment->available_at)
                                                    {{ $batchAssignment->available_at->format('d M Y, H:i') }}
                                                @else
                                                    <span class="text-muted">Not set</span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="deadline-item">
                                            <div class="deadline-label">Deadline</div>
                                            <div class="deadline-value">
                                                @if($batchAssignment->due_at)
                                                    {{ $batchAssignment->due_at->format('d M Y, H:i') }}
                                                @else
                                                    <span class="text-muted">No deadline</span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="deadline-item">
                                            <div class="deadline-label">Closed At</div>
                                            <div class="deadline-value">
                                                @if($batchAssignment->closed_at)
                                                    {{ $batchAssignment->closed_at->format('d M Y, H:i') }}
                                                @else
                                                    <span class="text-muted">Not set</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="batch-assignment-actions">
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#batchAssignmentModal"
                                    data-mode="edit"
                                    data-id="{{ $batchAssignment->id }}"
                                    data-assignment-id="{{ $batchAssignment->assignment_id }}"
                                    data-batch-id="{{ $batchAssignment->batch_id }}"
                                    data-available-at="{{ $availableAtValue }}"
                                    data-due-at="{{ $dueAtValue }}"
                                    data-closed-at="{{ $closedAtValue }}"
                                    data-max-score="{{ $batchAssignment->max_score }}"
                                    data-allow-late-submission="{{ (int) $batchAssignment->allow_late_submission }}"
                                    data-status="{{ $batchAssignment->status }}"
                                    data-is-active="{{ (int) $batchAssignment->is_active }}"
                                >
                                    <i class="bi bi-pencil-square me-1"></i>Edit
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-outline-danger btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#deleteConfirmModal"
                                    data-delete-name="{{ ($assignment->title ?? 'Assignment') . ' - ' . ($batch->name ?? 'Batch') }}"
                                    data-delete-url="{{ route('batch-assignments.destroy', $batchAssignment->id) }}"
                                >
                                    <i class="bi bi-trash me-1"></i>Delete
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4">
                    {{ $batchAssignments->links() }}
                </div>
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-clipboard-x"></i>
                    </div>
                    <h5 class="empty-state-title">Batch assignment belum tersedia</h5>
                    <p class="empty-state-text mb-3">
                        Belum ada assignment yang diberikan ke batch. Mulai dengan memilih assignment dan batch tujuan.
                    </p>
                    <button
                        type="button"
                        class="btn btn-primary btn-modern"
                        data-bs-toggle="modal"
                        data-bs-target="#batchAssignmentModal"
                        data-mode="create"
                    >
                        <i class="bi bi-plus-circle me-2"></i>Add First Batch Assignment
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="modal fade" id="batchAssignmentModal" tabindex="-1" aria-labelledby="batchAssignmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content custom-modal">
            <form id="batchAssignmentForm" data-create-url="{{ route('batch-assignments.store') }}">
                @csrf
                <input type="hidden" name="_method" value="POST">
                <input type="hidden" name="id" value="">

                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title" id="batchAssignmentModalLabel">Add Batch Assignment</h5>
                        <p class="text-muted mb-0" id="batchAssignmentModalSubtitle">
                            Berikan assignment ke batch tertentu dan tentukan deadline-nya.
                        </p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-4">
                    <div class="alert alert-danger d-none form-alert" role="alert"></div>

                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label">Assignment</label>
                            <select name="assignment_id" class="form-select" required>
                                <option value="">Select Assignment</option>
                                @foreach($assignments ?? [] as $assignment)
                                    <option
                                        value="{{ $assignment->id }}"
                                        data-max-score="{{ $assignment->max_score }}"
                                    >
                                        {{ $assignment->title }} — {{ $assignment->status }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">
                                Pilih master assignment yang akan diberikan ke batch.
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
                                Student dalam batch ini akan mendapatkan assignment.
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
                                Kosongkan kalau assignment langsung tersedia.
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
                                Deadline tugas untuk batch ini.
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
                                Setelah waktu ini, tugas ditutup.
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Max Score Override</label>
                            <input
                                type="number"
                                name="max_score"
                                class="form-control"
                                min="1"
                                max="999"
                                placeholder="Default assignment"
                            >
                            <div class="form-text">
                                Kosongkan jika mengikuti master assignment.
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Allow Late Submission</label>
                            <select name="allow_late_submission" class="form-select" required>
                                <option value="0">No</option>
                                <option value="1">Yes</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                @foreach($statuses ?? [] as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Active Status</label>
                            <select name="is_active" class="form-select" required>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <div class="batch-assignment-help">
                                <div class="help-icon">
                                    <i class="bi bi-info-circle"></i>
                                </div>
                                <div>
                                    <div class="help-title">Catatan deadline</div>
                                    <div class="help-text">
                                        Assignment akan muncul untuk student jika status <strong>Published</strong>, aktif,
                                        dan sudah melewati <strong>Available At</strong>. Deadline akan dipakai untuk card
                                        <strong>Must Do</strong> di LMS student.
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
                        Save Batch Assignment
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
                        <h5 class="modal-title" id="deleteConfirmModalLabel">Delete Batch Assignment</h5>
                        <p class="text-muted mb-0">
                            Konfirmasi sebelum menghapus batch assignment.
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
                    Batch assignment yang sudah dihapus tidak bisa dikembalikan.
                    Pastikan belum ada submission penting sebelum menghapus data ini.
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
        document.querySelector('#batchAssignmentForm input[name="_token"]')?.value
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

    function setupMaxScoreFromAssignment(form) {
        const assignmentSelect = form.querySelector('select[name="assignment_id"]');
        const maxScoreInput = form.querySelector('input[name="max_score"]');

        if (!assignmentSelect || !maxScoreInput) return;

        assignmentSelect.addEventListener('change', function () {
            const selectedOption = assignmentSelect.options[assignmentSelect.selectedIndex];

            if (!maxScoreInput.value && selectedOption?.dataset?.maxScore) {
                maxScoreInput.placeholder = `Default: ${selectedOption.dataset.maxScore}`;
            }
        });
    }

    function setupBatchAssignmentModal() {
        const modalEl = document.getElementById('batchAssignmentModal');
        const form = document.getElementById('batchAssignmentForm');

        if (!modalEl || !form) return;

        setupMaxScoreFromAssignment(form);

        modalEl.addEventListener('show.bs.modal', function (event) {
            resetFormState(form);

            const button = event.relatedTarget;
            const mode = button?.dataset?.mode || 'create';

            const title = modalEl.querySelector('#batchAssignmentModalLabel');
            const subtitle = modalEl.querySelector('#batchAssignmentModalSubtitle');
            const submitBtn = form.querySelector('.submit-btn');

            if (mode === 'edit') {
                title.textContent = 'Edit Batch Assignment';
                subtitle.textContent = 'Perbarui assignment batch dan deadline-nya.';
                submitBtn.innerHTML = 'Update Batch Assignment';
                submitBtn.dataset.defaultText = 'Update Batch Assignment';

                form.querySelector('input[name="id"]').value = button.dataset.id || '';
                form.querySelector('input[name="_method"]').value = 'PUT';

                form.querySelector('select[name="assignment_id"]').value = button.dataset.assignmentId || '';
                form.querySelector('select[name="batch_id"]').value = button.dataset.batchId || '';

                form.querySelector('input[name="available_at"]').value = button.dataset.availableAt || '';
                form.querySelector('input[name="due_at"]').value = button.dataset.dueAt || '';
                form.querySelector('input[name="closed_at"]').value = button.dataset.closedAt || '';

                form.querySelector('input[name="max_score"]').value = button.dataset.maxScore || '';
                form.querySelector('select[name="allow_late_submission"]').value = button.dataset.allowLateSubmission || '0';

                form.querySelector('select[name="status"]').value = button.dataset.status || 'draft';
                form.querySelector('select[name="is_active"]').value = button.dataset.isActive || '1';
            } else {
                title.textContent = 'Add Batch Assignment';
                subtitle.textContent = 'Berikan assignment ke batch tertentu dan tentukan deadline-nya.';
                submitBtn.innerHTML = 'Save Batch Assignment';
                submitBtn.dataset.defaultText = 'Save Batch Assignment';

                form.querySelector('select[name="allow_late_submission"]').value = '0';
                form.querySelector('select[name="status"]').value = 'draft';
                form.querySelector('select[name="is_active"]').value = '1';
            }
        });

        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            const submitBtn = form.querySelector('.submit-btn');
            const id = form.querySelector('input[name="id"]')?.value || '';
            const method = form.querySelector('input[name="_method"]')?.value || 'POST';
            const createUrl = form.dataset.createUrl;
            const actionUrl = method === 'PUT'
                ? `{{ route('batch-assignments.update', ['batchAssignment' => '__ID__']) }}`.replace('__ID__', id)
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

                showToast(data.message || 'Batch assignment berhasil disimpan.', 'success');

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
                    showToast(data.message || 'Gagal menghapus batch assignment.', 'error');
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = '<i class="bi bi-trash me-2"></i>Delete';
                    return;
                }

                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) {
                    modalInstance.hide();
                }

                showToast(data.message || 'Batch assignment berhasil dihapus.', 'success');

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

    setupBatchAssignmentModal();
    setupDeleteConfirmModal();
});
</script>
@endpush