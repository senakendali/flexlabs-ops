@extends('layouts.app-dashboard')

@section('title', 'Assignments')

@section('content')
<div class="container-fluid px-4 py-4">

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Academic Management</div>
                <h1 class="page-title mb-2">Assignments</h1>
                <p class="page-subtitle mb-0">
                    Kelola tugas pembelajaran yang terhubung ke <strong>Topic</strong> atau <strong>Sub Topic</strong>.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <button
                    type="button"
                    class="btn btn-primary btn-modern"
                    data-bs-toggle="modal"
                    data-bs-target="#assignmentModal"
                    data-mode="create"
                >
                    <i class="bi bi-plus-circle me-2"></i>Add Assignment
                </button>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-journal-check"></i>
                    </div>
                    <div>
                        <div class="stat-title">Total Assignments</div>
                        <div class="stat-value">{{ $stats['total'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Total master assignment yang tersedia.</div>
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
                <div class="stat-description">Assignment yang siap digunakan.</div>
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
                <div class="stat-description">Assignment yang masih disiapkan.</div>
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
                        <div class="stat-value">{{ $stats['active'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="stat-description">Assignment aktif di sistem.</div>
            </div>
        </div>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Filter Assignments</h5>
                <p class="content-card-subtitle mb-0">Cari assignment berdasarkan topic, sub topic, tipe, atau status.</p>
            </div>
        </div>

        <div class="content-card-body">
            <form method="GET" action="{{ route('assignments.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-xl-3 col-md-6">
                        <label class="form-label">Keyword</label>
                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            value="{{ request('search') }}"
                            placeholder="Cari judul/instruction..."
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
                        <label class="form-label">Type</label>
                        <select name="assignment_type" class="form-select">
                            <option value="">All Types</option>
                            @foreach($assignmentTypes ?? [] as $key => $label)
                                <option value="{{ $key }}" {{ request('assignment_type') === $key ? 'selected' : '' }}>
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
                            <a href="{{ route('assignments.index') }}" class="btn btn-outline-secondary btn-modern">
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
                <h5 class="content-card-title mb-1">Assignment List</h5>
                <p class="content-card-subtitle mb-0">
                    Assignment ini masih berupa master/template. Deadline per batch akan diatur di Batch Assignment.
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

                            $instructionPreview = \Illuminate\Support\Str::limit(strip_tags($assignment->instruction ?? ''), 180);
                        @endphp

                        <script type="application/json" id="assignment-instruction-{{ $assignment->id }}">
                            @json($assignment->instruction)
                        </script>

                        <div class="assignment-card">
                            <div class="assignment-main">
                                <div class="assignment-icon">
                                    <i class="bi bi-journal-check"></i>
                                </div>

                                <div class="assignment-info">
                                    <div class="assignment-title-row">
                                        <h5 class="assignment-title mb-0">{{ $assignment->title }}</h5>

                                        <div class="assignment-badges">
                                            <span class="assignment-type-badge {{ $typeClass }}">
                                                {{ $assignment->type_label }}
                                            </span>

                                            <span class="assignment-status-badge {{ $statusClass }}">
                                                {{ $assignment->status_label }}
                                            </span>

                                            @if($assignment->is_required)
                                                <span class="assignment-required-badge">Required</span>
                                            @else
                                                <span class="assignment-optional-badge">Optional</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="assignment-meta">
                                        <span>
                                            <i class="bi bi-layers me-1"></i>{{ $targetLabel }}
                                        </span>

                                        @if($assignment->topic?->module)
                                            <span>
                                                <i class="bi bi-folder2-open me-1"></i>{{ $assignment->topic->module->name }}
                                            </span>
                                        @endif

                                        <span>
                                            <i class="bi bi-star me-1"></i>Max Score {{ $assignment->max_score }}
                                        </span>
                                    </div>

                                    @if(!empty($instructionPreview))
                                        <div class="assignment-instruction">
                                            {{ $instructionPreview }}
                                        </div>
                                    @endif

                                    <div class="assignment-footer-meta">
                                        <span>
                                            <i class="bi bi-paperclip me-1"></i>{{ $materialCount }} resources
                                        </span>
                                        <span>
                                            <i class="bi bi-people me-1"></i>{{ $assignment->batch_assignments_count ?? 0 }} batch assignments
                                        </span>
                                        <span>
                                            <i class="bi bi-inbox me-1"></i>{{ $assignment->submissions_count ?? 0 }} submissions
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
                                    <i class="bi bi-pencil-square me-1"></i>Edit
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-outline-danger btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#deleteConfirmModal"
                                    data-delete-name="{{ $assignment->title }}"
                                    data-delete-url="{{ route('assignments.destroy', $assignment->id) }}"
                                >
                                    <i class="bi bi-trash me-1"></i>Delete
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
                    <h5 class="empty-state-title">Assignment belum tersedia</h5>
                    <p class="empty-state-text mb-3">
                        Belum ada assignment yang dibuat. Mulai dengan membuat assignment untuk topic atau sub topic tertentu.
                    </p>
                    <button
                        type="button"
                        class="btn btn-primary btn-modern"
                        data-bs-toggle="modal"
                        data-bs-target="#assignmentModal"
                        data-mode="create"
                    >
                        <i class="bi bi-plus-circle me-2"></i>Add First Assignment
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="modal fade" id="assignmentModal" tabindex="-1" aria-labelledby="assignmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content custom-modal">
            <form id="assignmentForm" data-create-url="{{ route('assignments.store') }}">
                @csrf
                <input type="hidden" name="_method" value="POST">
                <input type="hidden" name="id" value="">
                <input type="hidden" name="instruction" value="">

                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title" id="assignmentModalLabel">Add Assignment</h5>
                        <p class="text-muted mb-0" id="assignmentModalSubtitle">
                            Buat assignment master yang bisa dihubungkan ke topic atau sub topic.
                        </p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-4">
                    <div class="alert alert-danger d-none form-alert" role="alert"></div>

                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label">Topic</label>
                            <select name="topic_id" class="form-select assignment-topic-select">
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
                                Pilih topic jika assignment berlaku untuk satu topic penuh.
                            </div>
                        </div>

                        <div class="col-md-5">
                            <label class="form-label">Sub Topic</label>
                            <select name="sub_topic_id" class="form-select assignment-sub-topic-select">
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
                                Optional. Kalau dipilih, assignment akan lebih spesifik ke sub topic.
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Assignment Title</label>
                            <input
                                type="text"
                                name="title"
                                class="form-control"
                                placeholder="Contoh: Buat user flow sederhana dari fitur aplikasi"
                                required
                            >
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Assignment Type</label>
                            <select name="assignment_type" class="form-select" required>
                                @foreach($assignmentTypes ?? [] as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Max Score</label>
                            <input
                                type="number"
                                name="max_score"
                                class="form-control"
                                min="1"
                                max="999"
                                value="100"
                            >
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Required</label>
                            <select name="is_required" class="form-select">
                                <option value="1">Required</option>
                                <option value="0">Optional</option>
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
                            <select name="status" class="form-select">
                                @foreach($statuses ?? [] as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Active Status</label>
                            <select name="is_active" class="form-select">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <div class="assignment-quill-block">
                                <label class="form-label">Instruction</label>

                                <div class="assignment-quill-shell">
                                    <div id="assignmentInstructionEditor" class="assignment-quill-editor"></div>
                                </div>

                                <div class="form-text mt-2">
                                    Tulis instruksi assignment secara jelas. Bisa pakai format heading, list, bold, dan link.
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="assignment-resource-card">
                                <div class="assignment-resource-header">
                                    <div>
                                        <div class="assignment-resource-title">
                                            <i class="bi bi-paperclip me-2"></i>Assignment Resources
                                        </div>
                                        <div class="assignment-resource-subtitle">
                                            Link pendukung untuk tugas, starter file, atau referensi eksternal.
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Attachment URL</label>
                                        <input
                                            type="url"
                                            name="attachment_url"
                                            class="form-control"
                                            placeholder="https://drive.google.com/..."
                                        >
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Starter File URL</label>
                                        <input
                                            type="url"
                                            name="starter_file_url"
                                            class="form-control"
                                            placeholder="https://github.com/... atau link file"
                                        >
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Reference URL</label>
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
                    <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary btn-modern submit-btn">
                        Save Assignment
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
                        <h5 class="modal-title" id="deleteConfirmModalLabel">Delete Assignment</h5>
                        <p class="text-muted mb-0">
                            Konfirmasi sebelum menghapus assignment.
                        </p>
                    </div>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pt-4">
                <div class="delete-confirm-message">
                    <div class="delete-confirm-label">Assignment yang akan dihapus</div>
                    <div class="delete-confirm-name" id="deleteConfirmName">-</div>
                </div>

                <div class="delete-confirm-warning mt-3">
                    Assignment yang sudah dihapus tidak bisa dikembalikan.
                    Jika sudah digunakan di batch/submission, pastikan data memang aman untuk dihapus.
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
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken =
        document.querySelector('#assignmentForm input[name="_token"]')?.value
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
        const editorEl = document.getElementById('assignmentInstructionEditor');

        if (!editorEl || typeof Quill === 'undefined') return;

        instructionQuill = new Quill(editorEl, {
            theme: 'snow',
            placeholder: 'Tulis instruksi assignment untuk student...',
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

    function setupAssignmentModal() {
        const modalEl = document.getElementById('assignmentModal');
        const form = document.getElementById('assignmentForm');

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

            const title = modalEl.querySelector('#assignmentModalLabel');
            const subtitle = modalEl.querySelector('#assignmentModalSubtitle');
            const submitBtn = form.querySelector('.submit-btn');

            if (mode === 'edit') {
                title.textContent = 'Edit Assignment';
                subtitle.textContent = 'Perbarui data assignment.';
                submitBtn.innerHTML = 'Update Assignment';
                submitBtn.dataset.defaultText = 'Update Assignment';

                form.querySelector('input[name="id"]').value = button.dataset.id || '';
                form.querySelector('input[name="_method"]').value = 'PUT';

                form.querySelector('select[name="topic_id"]').value = button.dataset.topicId || '';
                form.querySelector('select[name="sub_topic_id"]').value = button.dataset.subTopicId || '';
                form.querySelector('input[name="title"]').value = button.dataset.title || '';
                form.querySelector('select[name="assignment_type"]').value = button.dataset.assignmentType || 'mixed';

                const instructionHtml = getInstructionFromJsonScript(button.dataset.instructionJsonId);
                setInstructionHtml(instructionHtml);
                form.querySelector('input[name="instruction"]').value = instructionHtml;

                form.querySelector('input[name="attachment_url"]').value = button.dataset.attachmentUrl || '';
                form.querySelector('input[name="starter_file_url"]').value = button.dataset.starterFileUrl || '';
                form.querySelector('input[name="reference_url"]').value = button.dataset.referenceUrl || '';

                form.querySelector('input[name="max_score"]').value = button.dataset.maxScore || 100;
                form.querySelector('select[name="is_required"]').value = button.dataset.isRequired || '1';
                form.querySelector('input[name="sort_order"]').value = button.dataset.sortOrder || 1;
                form.querySelector('select[name="status"]').value = button.dataset.status || 'draft';
                form.querySelector('select[name="is_active"]').value = button.dataset.isActive || '1';
            } else {
                title.textContent = 'Add Assignment';
                subtitle.textContent = 'Buat assignment master yang bisa dihubungkan ke topic atau sub topic.';
                submitBtn.innerHTML = 'Save Assignment';
                submitBtn.dataset.defaultText = 'Save Assignment';

                form.querySelector('select[name="assignment_type"]').value = 'mixed';
                form.querySelector('input[name="max_score"]').value = 100;
                form.querySelector('select[name="is_required"]').value = '1';
                form.querySelector('input[name="sort_order"]').value = 1;
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
                ? `{{ route('assignments.update', ['assignment' => '__ID__']) }}`.replace('__ID__', id)
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

                showToast(data.message || 'Assignment berhasil disimpan.', 'success');

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
                    showToast(data.message || 'Gagal menghapus assignment.', 'error');
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = '<i class="bi bi-trash me-2"></i>Delete';
                    return;
                }

                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) {
                    modalInstance.hide();
                }

                showToast(data.message || 'Assignment berhasil dihapus.', 'success');

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
    setupAssignmentModal();
    setupDeleteConfirmModal();
});
</script>
@endpush