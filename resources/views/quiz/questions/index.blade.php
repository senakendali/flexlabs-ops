@extends('layouts.app-dashboard')

@section('title', 'Quiz Questions')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="mb-1">Quiz Questions</h4>
            <small class="text-muted">
                Manage questions for quiz:
                <span class="fw-semibold">{{ $quiz->title }}</span>
            </small>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('quiz.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>

            <button type="button" class="btn btn-primary" onclick="openCreateModal()">
                <i class="bi bi-plus-lg me-1"></i> Add Question
            </button>
        </div>
    </div>

    <div
        id="toastContainer"
        class="toast-container position-fixed top-0 end-0 p-3"
        style="z-index: 9999;"
    ></div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-8">
                    <div class="small text-muted mb-1">Quiz Title</div>
                    <div class="fw-semibold">{{ $quiz->title }}</div>
                </div>

                <div class="col-md-2">
                    <div class="small text-muted mb-1">Status</div>
                    @if ($quiz->status === 'active')
                        <span class="badge bg-success">Active</span>
                    @elseif ($quiz->status === 'finished')
                        <span class="badge bg-secondary">Finished</span>
                    @else
                        <span class="badge bg-warning text-dark">Draft</span>
                    @endif
                </div>

                <div class="col-md-2">
                    <div class="small text-muted mb-1">Quota</div>
                    <div class="fw-semibold">{{ $quiz->quota ?: '-' }}</div>
                </div>

                @if ($quiz->description)
                    <div class="col-12">
                        <div class="small text-muted mb-1">Description</div>
                        <div>{{ $quiz->description }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body border-bottom">
            <form method="GET" class="d-flex align-items-center gap-2">
                <label for="per_page" class="form-label mb-0 small text-muted">Show</label>
                <select
                    name="per_page"
                    id="per_page"
                    class="form-select form-select-sm"
                    style="width: auto;"
                    onchange="this.form.submit()"
                >
                    @foreach ([10, 25, 50, 100] as $size)
                        <option value="{{ $size }}" {{ (int) request('per_page', 10) === $size ? 'selected' : '' }}>
                            {{ $size }}
                        </option>
                    @endforeach
                </select>
                <span class="small text-muted">entries</span>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 80px;">No</th>
                        <th style="width: 100px;">Order</th>
                        <th>Question</th>
                        <th style="width: 120px;">Options</th>
                        <th style="width: 190px;" class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($questions as $question)
                        <tr>
                            <td>
                                {{ ($questions->currentPage() - 1) * $questions->perPage() + $loop->iteration }}
                            </td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                    {{ $question->order }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold">
                                    {{ \Illuminate\Support\Str::limit($question->question_text, 120) }}
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-info-subtle text-info border border-info-subtle">
                                    {{ $question->options_count ?? 0 }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-inline-flex gap-2">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        onclick="editQuestion({{ $question->id }})"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    <a
                                        href="{{ route('quiz.options.index', $question->id) }}"
                                        class="btn btn-sm btn-outline-info"
                                    >
                                        <i class="bi bi-list-check"></i>
                                    </a>

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="openDeleteModal({{ $question->id }}, @js(\Illuminate\Support\Str::limit($question->question_text, 100)))"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                No questions found for this quiz.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($questions->hasPages())
            <div class="card-footer bg-white">
                {{ $questions->links() }}
            </div>
        @endif
    </div>
</div>

{{-- Question Form Modal --}}
<div class="modal fade" id="questionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="questionForm">
            @csrf
            <input type="hidden" id="question_id">

            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title" id="questionModalTitle">Add Question</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div id="formAlert" class="alert alert-danger d-none mb-3"></div>

                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="order" class="form-label">
                                Order <span class="text-danger">*</span>
                            </label>
                            <input type="number" id="order" class="form-control" min="1">
                            <div class="invalid-feedback" id="error_order"></div>
                        </div>

                        <div class="col-md-9">
                            <label for="question_text" class="form-label">
                                Question <span class="text-danger">*</span>
                            </label>
                            <textarea id="question_text" rows="5" class="form-control"></textarea>
                            <div class="invalid-feedback" id="error_question_text"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <span class="default-text">Save</span>
                        <span class="loading-text d-none">Saving...</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Delete Modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title">Delete Question</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <p class="mb-0">
                    Are you sure you want to delete this question?
                </p>
                <div class="mt-2 text-muted small" id="deleteQuestionText"></div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                    Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <span class="default-delete-text">Delete</span>
                    <span class="loading-delete-text d-none">Deleting...</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const questionModalEl = document.getElementById('questionModal');
    const questionModal = new bootstrap.Modal(questionModalEl);
    const questionForm = document.getElementById('questionForm');
    const submitBtn = document.getElementById('submitBtn');
    const modalTitle = document.getElementById('questionModalTitle');
    const formAlert = document.getElementById('formAlert');

    const deleteModalEl = document.getElementById('deleteModal');
    const deleteModal = new bootstrap.Modal(deleteModalEl);
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const deleteQuestionTextEl = document.getElementById('deleteQuestionText');

    const fields = {
        id: document.getElementById('question_id'),
        question_text: document.getElementById('question_text'),
        order: document.getElementById('order'),
    };

    let deleteQuestionId = null;
    let reloadTimeout = null;
    const quizId = @json($quiz->id);

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
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="${closeBtnClass} me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', html);

        const toastEl = document.getElementById(id);
        const toast = new bootstrap.Toast(toastEl, { delay: 3000 });

        toast.show();

        toastEl.addEventListener('hidden.bs.toast', () => {
            toastEl.remove();
        });
    }

    function scheduleReload() {
        if (reloadTimeout) {
            clearTimeout(reloadTimeout);
        }

        reloadTimeout = setTimeout(() => {
            location.reload();
        }, 1500);
    }

    function clearValidationErrors() {
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        document.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
    }

    function setValidationErrors(errors = {}) {
        clearValidationErrors();

        Object.keys(errors).forEach(key => {
            const field = document.getElementById(key) || fields[key];
            const errorEl = document.getElementById(`error_${key}`);

            if (field && field.classList) {
                field.classList.add('is-invalid');
            }

            if (errorEl) {
                errorEl.textContent = Array.isArray(errors[key]) ? errors[key][0] : errors[key];
            }
        });
    }

    function setSubmitLoading(isLoading) {
        submitBtn.disabled = isLoading;
        submitBtn.querySelector('.default-text').classList.toggle('d-none', isLoading);
        submitBtn.querySelector('.loading-text').classList.toggle('d-none', !isLoading);
    }

    function setDeleteLoading(isLoading) {
        confirmDeleteBtn.disabled = isLoading;
        confirmDeleteBtn.querySelector('.default-delete-text').classList.toggle('d-none', isLoading);
        confirmDeleteBtn.querySelector('.loading-delete-text').classList.toggle('d-none', !isLoading);
    }

    function resetForm() {
        questionForm.reset();
        fields.id.value = '';
        formAlert.classList.add('d-none');
        formAlert.innerHTML = '';
        clearValidationErrors();
        setSubmitLoading(false);
    }

    function openCreateModal() {
        resetForm();
        modalTitle.textContent = 'Add Question';
        questionModal.show();
    }

    async function editQuestion(id) {
        resetForm();
        modalTitle.textContent = 'Edit Question';

        try {
            const response = await fetch(`/quiz/questions/${id}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to fetch question data.');
            }

            const data = result.data;

            fields.id.value = data.id;
            fields.question_text.value = data.question_text ?? '';
            fields.order.value = data.order ?? '';

            questionModal.show();
        } catch (error) {
            formAlert.classList.remove('d-none');
            formAlert.innerHTML = error.message || 'Failed to load question data.';
            questionModal.show();
        }
    }

    function openDeleteModal(id, questionText) {
        deleteQuestionId = id;
        deleteQuestionTextEl.textContent = questionText || '-';
        setDeleteLoading(false);
        deleteModal.show();
    }

    questionForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        clearValidationErrors();
        formAlert.classList.add('d-none');
        formAlert.innerHTML = '';

        const id = fields.id.value;
        const url = id ? `/quiz/questions/${id}` : `/quiz/${quizId}/questions`;

        const payload = {
            question_text: fields.question_text.value.trim(),
            order: fields.order.value || null,
        };

        setSubmitLoading(true);

        try {
            const response = await fetch(url, {
                method: id ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            });

            const result = await response.json();

            if (response.status === 422) {
                setValidationErrors(result.errors || {});
                throw new Error(result.message || 'Validation failed.');
            }

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to save question.');
            }

            questionModal.hide();
            showToast(result.message || 'Question saved successfully', 'success');
            scheduleReload();
        } catch (error) {
            if (error.message !== 'Validation failed.') {
                formAlert.classList.remove('d-none');
                formAlert.innerHTML = error.message || 'Something went wrong.';
            }
        } finally {
            setSubmitLoading(false);
        }
    });

    confirmDeleteBtn.addEventListener('click', async function () {
        if (!deleteQuestionId) return;

        setDeleteLoading(true);

        try {
            const response = await fetch(`/quiz/questions/${deleteQuestionId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to delete question.');
            }

            deleteModal.hide();
            showToast(result.message || 'Question deleted successfully', 'danger');
            scheduleReload();
        } catch (error) {
            showToast(error.message || 'Failed to delete question.', 'danger');
        } finally {
            setDeleteLoading(false);
            deleteQuestionId = null;
        }
    });

    questionModalEl.addEventListener('hidden.bs.modal', function () {
        resetForm();
    });

    deleteModalEl.addEventListener('hidden.bs.modal', function () {
        deleteQuestionId = null;
        deleteQuestionTextEl.textContent = '';
        setDeleteLoading(false);
    });
</script>
@endpush