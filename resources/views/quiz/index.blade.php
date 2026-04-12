@extends('layouts.app-dashboard')

@section('title', 'Quiz Management')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="mb-1">Quiz Management</h4>
            <small class="text-muted">Manage quizzes, opening schedule, quota, and question setup</small>
        </div>

        <button type="button" class="btn btn-primary" onclick="openCreateModal()">
            <i class="bi bi-plus-lg me-1"></i> Add Quiz
        </button>
    </div>

    <div
        id="toastContainer"
        class="toast-container position-fixed top-0 end-0 p-3"
        style="z-index: 9999;"
    ></div>

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
                        <th>Title</th>
                        <th style="width: 120px;">Questions</th>
                        <th style="width: 120px;">Quota</th>
                        <th style="width: 180px;">Open At</th>
                        <th style="width: 120px;">Status</th>
                        <th style="width: 340px;" class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($quizzes as $quiz)
                        @php
                            $quizLink = route('quiz.play', $quiz->id);
                        @endphp
                        <tr>
                            <td>
                                {{ ($quizzes->currentPage() - 1) * $quizzes->perPage() + $loop->iteration }}
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $quiz->title }}</div>
                                @if ($quiz->description)
                                    <small class="text-muted">
                                        {{ \Illuminate\Support\Str::limit($quiz->description, 60) }}
                                    </small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                    {{ $quiz->questions_count ?? 0 }}
                                </span>
                            </td>
                            <td>{{ $quiz->quota ?: '-' }}</td>
                            <td>
                                @if ($quiz->opens_at)
                                    <div class="fw-semibold">{{ $quiz->opens_at->format('d M Y') }}</div>
                                    <small class="text-muted">{{ $quiz->opens_at->format('H:i') }}</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if ($quiz->status === 'active')
                                    <span class="badge bg-success">Active</span>
                                @elseif ($quiz->status === 'finished')
                                    <span class="badge bg-secondary">Finished</span>
                                @else
                                    <span class="badge bg-warning text-dark">Draft</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-inline-flex flex-wrap gap-2 justify-content-center">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        onclick="editQuiz({{ $quiz->id }})"
                                        title="Edit Quiz"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    <a
                                        href="{{ route('quiz.questions.index', $quiz->id) }}"
                                        class="btn btn-sm btn-outline-info"
                                        title="Manage Questions"
                                    >
                                        <i class="bi bi-list-check"></i>
                                    </a>

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-secondary"
                                        onclick="copyQuizLink('{{ $quizLink }}')"
                                        title="Copy Quiz Link"
                                    >
                                        <i class="bi bi-link-45deg"></i>
                                    </button>

                                    <a
                                        href="{{ route('quiz.leaderboard', $quiz->id) }}"
                                        class="btn btn-sm btn-outline-dark"
                                        title="View Leaderboard"
                                    >
                                        <i class="bi bi-trophy"></i>
                                    </a>

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="openDeleteModal({{ $quiz->id }}, @js($quiz->title))"
                                        title="Delete Quiz"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                No quizzes found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($quizzes->hasPages())
            <div class="card-footer bg-white">
                {{ $quizzes->links() }}
            </div>
        @endif
    </div>
</div>

{{-- Quiz Form Modal --}}
<div class="modal fade" id="quizModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="quizForm">
            @csrf
            <input type="hidden" id="quiz_id">

            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title" id="quizModalTitle">Add Quiz</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div id="formAlert" class="alert alert-danger d-none mb-3"></div>

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="title" class="form-label">
                                Title <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="title" class="form-control">
                            <div class="invalid-feedback" id="error_title"></div>
                        </div>

                        <div class="col-md-4">
                            <label for="status" class="form-label">
                                Status <span class="text-danger">*</span>
                            </label>
                            <select id="status" class="form-select">
                                <option value="draft">Draft</option>
                                <option value="active">Active</option>
                                <option value="finished">Finished</option>
                            </select>
                            <div class="invalid-feedback" id="error_status"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="opens_at" class="form-label">Open At</label>
                            <input type="datetime-local" id="opens_at" class="form-control">
                            <div class="form-text">Set when this quiz will be available.</div>
                            <div class="invalid-feedback" id="error_opens_at"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="quota" class="form-label">Quota</label>
                            <input type="number" id="quota" class="form-control" min="1">
                            <div class="form-text">Optional. Leave empty for unlimited participants.</div>
                            <div class="invalid-feedback" id="error_quota"></div>
                        </div>

                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea id="description" rows="4" class="form-control"></textarea>
                            <div class="invalid-feedback" id="error_description"></div>
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
                <h5 class="modal-title">Delete Quiz</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <p class="mb-0">
                    Are you sure you want to delete
                    <strong id="deleteQuizTitle"></strong>?
                </p>
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
    const quizModalEl = document.getElementById('quizModal');
    const quizModal = new bootstrap.Modal(quizModalEl);
    const quizForm = document.getElementById('quizForm');
    const submitBtn = document.getElementById('submitBtn');
    const modalTitle = document.getElementById('quizModalTitle');
    const formAlert = document.getElementById('formAlert');

    const deleteModalEl = document.getElementById('deleteModal');
    const deleteModal = new bootstrap.Modal(deleteModalEl);
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const deleteQuizTitleEl = document.getElementById('deleteQuizTitle');

    const fields = {
        id: document.getElementById('quiz_id'),
        title: document.getElementById('title'),
        description: document.getElementById('description'),
        status: document.getElementById('status'),
        opens_at: document.getElementById('opens_at'),
        quota: document.getElementById('quota'),
    };

    let deleteQuizId = null;
    let reloadTimeout = null;

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

    function formatDatetimeLocal(datetimeString) {
        if (!datetimeString) return '';

        const date = new Date(datetimeString);
        const offset = date.getTimezoneOffset();
        const localDate = new Date(date.getTime() - (offset * 60000));

        return localDate.toISOString().slice(0, 16);
    }

    function resetForm() {
        quizForm.reset();
        fields.id.value = '';
        fields.status.value = 'draft';
        formAlert.classList.add('d-none');
        formAlert.innerHTML = '';
        clearValidationErrors();
        setSubmitLoading(false);
    }

    function openCreateModal() {
        resetForm();
        modalTitle.textContent = 'Add Quiz';
        quizModal.show();
    }

    async function editQuiz(id) {
        resetForm();
        modalTitle.textContent = 'Edit Quiz';

        try {
            const response = await fetch(`/quiz/${id}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to fetch quiz data.');
            }

            const data = result.data;

            fields.id.value = data.id;
            fields.title.value = data.title ?? '';
            fields.description.value = data.description ?? '';
            fields.status.value = data.status ?? 'draft';
            fields.opens_at.value = formatDatetimeLocal(data.opens_at);
            fields.quota.value = data.quota ?? '';

            quizModal.show();
        } catch (error) {
            formAlert.classList.remove('d-none');
            formAlert.innerHTML = error.message || 'Failed to load quiz data.';
            quizModal.show();
        }
    }

    function openDeleteModal(id, title) {
        deleteQuizId = id;
        deleteQuizTitleEl.textContent = title || '-';
        setDeleteLoading(false);
        deleteModal.show();
    }

    async function copyQuizLink(link) {
        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(link);
            } else {
                const tempInput = document.createElement('input');
                tempInput.value = link;
                document.body.appendChild(tempInput);
                tempInput.select();
                document.execCommand('copy');
                document.body.removeChild(tempInput);
            }

            showToast('Quiz link copied successfully.', 'success');
        } catch (error) {
            showToast('Failed to copy quiz link.', 'danger');
        }
    }

    quizForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        clearValidationErrors();
        formAlert.classList.add('d-none');
        formAlert.innerHTML = '';

        const id = fields.id.value;
        const url = id ? `/quiz/${id}` : `/quiz`;

        const payload = {
            title: fields.title.value.trim(),
            description: fields.description.value.trim(),
            status: fields.status.value,
            opens_at: fields.opens_at.value || null,
            quota: fields.quota.value || null,
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
                throw new Error(result.message || 'Failed to save quiz.');
            }

            quizModal.hide();
            showToast(result.message || 'Quiz saved successfully', 'success');
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
        if (!deleteQuizId) return;

        setDeleteLoading(true);

        try {
            const response = await fetch(`/quiz/${deleteQuizId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to delete quiz.');
            }

            deleteModal.hide();
            showToast(result.message || 'Quiz deleted successfully', 'danger');
            scheduleReload();
        } catch (error) {
            showToast(error.message || 'Failed to delete quiz.', 'danger');
        } finally {
            setDeleteLoading(false);
            deleteQuizId = null;
        }
    });

    quizModalEl.addEventListener('hidden.bs.modal', function () {
        resetForm();
    });

    deleteModalEl.addEventListener('hidden.bs.modal', function () {
        deleteQuizId = null;
        deleteQuizTitleEl.textContent = '';
        setDeleteLoading(false);
    });
</script>
@endpush