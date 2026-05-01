@extends('layouts.app-dashboard')

@section('title', 'Assessment Template Detail')

@section('content')
@php
    $componentTypes = [
        'attendance' => 'Attendance',
        'progress' => 'Progress',
        'quiz' => 'Quiz',
        'assignment' => 'Assignment',
        'project' => 'Project',
        'attitude' => 'Attitude',
        'custom' => 'Custom',
    ];

    $components = $template->components ?? collect();

    $totalWeight = (float) $components->sum('weight');
    $requiredComponents = $components->where('is_required', true)->count();
    $autoComponents = $components->where('is_auto_calculated', true)->count();
@endphp

<div class="container-fluid px-4 py-4">

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Academic Management</div>
                <h1 class="page-title mb-2">{{ $template->name }}</h1>
                <p class="page-subtitle mb-0">
                    Detail standar assessment untuk program
                    <strong>{{ $template->program->name ?? '-' }}</strong>.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <a href="{{ route('academic.assessment-templates.index') }}" class="btn btn-light btn-modern">
                    <i class="bi bi-arrow-left me-2"></i>Back
                </a>

                <a href="{{ route('academic.assessment-templates.edit', $template) }}" class="btn btn-primary btn-modern">
                    <i class="bi bi-pencil-square me-2"></i>Edit Template
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-award"></i>
                    </div>
                    <div>
                        <div class="stat-title">Passing Score</div>
                        <div class="stat-value">{{ number_format((float) $template->passing_score, 2) }}</div>
                    </div>
                </div>
                <div class="stat-description">Minimum nilai akhir untuk lulus.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-person-check"></i>
                    </div>
                    <div>
                        <div class="stat-title">Attendance</div>
                        <div class="stat-value">{{ number_format((float) $template->min_attendance_percent, 2) }}%</div>
                    </div>
                </div>
                <div class="stat-description">Minimum kehadiran student.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <div>
                        <div class="stat-title">Progress</div>
                        <div class="stat-value">{{ number_format((float) $template->min_progress_percent, 2) }}%</div>
                    </div>
                </div>
                <div class="stat-description">Minimum progress pembelajaran.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-kanban"></i>
                    </div>
                    <div>
                        <div class="stat-title">Components</div>
                        <div class="stat-value">{{ $components->count() }}</div>
                    </div>
                </div>
                <div class="stat-description">Total component assessment.</div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-8">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Template Detail</h5>
                        <p class="content-card-subtitle mb-0">
                            Informasi utama assessment template.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="table-responsive">
                        <table class="table table-borderless align-middle mb-0">
                            <tbody>
                                <tr>
                                    <th width="220" class="text-muted">Template Name</th>
                                    <td class="fw-semibold text-dark">{{ $template->name }}</td>
                                </tr>

                                <tr>
                                    <th class="text-muted">Code</th>
                                    <td>
                                        @if($template->code)
                                            <span class="badge rounded-pill bg-primary-subtle text-primary">
                                                {{ $template->code }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>

                                <tr>
                                    <th class="text-muted">Program</th>
                                    <td>{{ $template->program->name ?? '-' }}</td>
                                </tr>

                                <tr>
                                    <th class="text-muted">Description</th>
                                    <td>
                                        @if($template->description)
                                            {!! nl2br(e($template->description)) !!}
                                        @else
                                            <span class="text-muted">No description.</span>
                                        @endif
                                    </td>
                                </tr>

                                <tr>
                                    <th class="text-muted">Final Project</th>
                                    <td>
                                        @if($template->requires_final_project)
                                            <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis">
                                                Required
                                            </span>
                                        @else
                                            <span class="badge rounded-pill bg-secondary-subtle text-secondary">
                                                Not Required
                                            </span>
                                        @endif
                                    </td>
                                </tr>

                                <tr>
                                    <th class="text-muted">Status</th>
                                    <td>
                                        @if($template->is_active)
                                            <span class="badge rounded-pill bg-success-subtle text-success">
                                                Active
                                            </span>
                                        @else
                                            <span class="badge rounded-pill bg-secondary-subtle text-secondary">
                                                Inactive
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Component Summary</h5>
                        <p class="content-card-subtitle mb-0">
                            Ringkasan bobot component assessment.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted">Total Weight</span>
                        <span class="fw-semibold {{ $totalWeight > 100 ? 'text-danger' : 'text-dark' }}">
                            {{ number_format($totalWeight, 2) }}%
                        </span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted">Required Components</span>
                        <span class="fw-semibold">{{ $requiredComponents }}</span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span class="text-muted">Auto Calculated</span>
                        <span class="fw-semibold">{{ $autoComponents }}</span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center py-2">
                        <span class="text-muted">Manual Grading</span>
                        <span class="fw-semibold">{{ $components->count() - $autoComponents }}</span>
                    </div>

                    @if($totalWeight < 100)
                        <div class="alert alert-warning mt-3 mb-0">
                            Total weight masih kurang dari 100%.
                        </div>
                    @elseif($totalWeight > 100)
                        <div class="alert alert-danger mt-3 mb-0">
                            Total weight melebihi 100%.
                        </div>
                    @else
                        <div class="alert alert-success mt-3 mb-0">
                            Total weight sudah 100%.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="content-card">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Assessment Components</h5>
                <p class="content-card-subtitle mb-0">
                    Kelola komponen penilaian seperti attendance, progress, quiz, assignment, dan final project.
                </p>
            </div>

            <button
                type="button"
                class="btn btn-primary btn-modern"
                data-bs-toggle="modal"
                data-bs-target="#componentModal"
                data-mode="create"
            >
                <i class="bi bi-plus-circle me-2"></i>Add Component
            </button>
        </div>

        <div class="content-card-body">
            @if($components->count())
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Component</th>
                                <th class="text-nowrap">Type</th>
                                <th class="text-nowrap">Weight</th>
                                <th class="text-nowrap">Max Score</th>
                                <th class="text-nowrap">Required</th>
                                <th class="text-nowrap">Auto</th>
                                <th class="text-nowrap">Rubric</th>
                                <th class="text-end text-nowrap">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($components as $component)
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $component->name }}</div>
                                        <div class="text-muted small">{{ $component->code ?: '-' }}</div>

                                        @if($component->description)
                                            <div class="text-muted small mt-1">
                                                {{ \Illuminate\Support\Str::limit(strip_tags($component->description), 90) }}
                                            </div>
                                        @endif
                                    </td>

                                    <td class="text-nowrap">
                                        <span class="badge rounded-pill bg-primary-subtle text-primary">
                                            {{ $componentTypes[$component->type] ?? ucfirst($component->type) }}
                                        </span>
                                    </td>

                                    <td class="text-nowrap">
                                        <span class="fw-semibold">
                                            {{ number_format((float) $component->weight, 2) }}%
                                        </span>
                                    </td>

                                    <td class="text-nowrap">
                                        {{ number_format((float) $component->max_score, 2) }}
                                    </td>

                                    <td class="text-nowrap">
                                        @if($component->is_required)
                                            <span class="badge rounded-pill bg-success-subtle text-success">Yes</span>
                                        @else
                                            <span class="badge rounded-pill bg-secondary-subtle text-secondary">No</span>
                                        @endif
                                    </td>

                                    <td class="text-nowrap">
                                        @if($component->is_auto_calculated)
                                            <span class="badge rounded-pill bg-info-subtle text-info">Yes</span>
                                        @else
                                            <span class="badge rounded-pill bg-secondary-subtle text-secondary">No</span>
                                        @endif
                                    </td>

                                    <td class="text-nowrap">
                                        @if($component->rubric)
                                            <span class="badge rounded-pill bg-success-subtle text-success">
                                                Active
                                            </span>
                                        @else
                                            <span class="badge rounded-pill bg-secondary-subtle text-secondary">
                                                Empty
                                            </span>
                                        @endif
                                    </td>

                                    <td class="text-end text-nowrap">
                                        <div class="dropdown">
                                            <button
                                                class="btn btn-sm btn-outline-secondary dropdown-toggle px-3"
                                                type="button"
                                                data-bs-toggle="dropdown"
                                                data-bs-boundary="viewport"
                                                aria-expanded="false"
                                            >
                                                Actions
                                            </button>

                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                <li>
                                                    <button
                                                        type="button"
                                                        class="dropdown-item"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#componentModal"
                                                        data-mode="edit"
                                                        data-update-url="{{ route('academic.assessment-templates.components.update', [$template, $component]) }}"
                                                        data-id="{{ $component->id }}"
                                                        data-name="{{ $component->name }}"
                                                        data-code="{{ $component->code }}"
                                                        data-type="{{ $component->type }}"
                                                        data-weight="{{ $component->weight }}"
                                                        data-max-score="{{ $component->max_score }}"
                                                        data-is-required="{{ (int) $component->is_required }}"
                                                        data-is-auto-calculated="{{ (int) $component->is_auto_calculated }}"
                                                        data-sort-order="{{ $component->sort_order }}"
                                                        data-description="{{ e($component->description) }}"
                                                    >
                                                        <i class="bi bi-pencil-square me-2"></i>Edit Component
                                                    </button>
                                                </li>

                                                <li>
                                                    <a
                                                        href="{{ route('academic.assessment-templates.components.rubric.show', [$template, $component]) }}"
                                                        class="dropdown-item"
                                                    >
                                                        <i class="bi bi-ui-checks-grid me-2"></i>Manage Rubric
                                                    </a>
                                                </li>

                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>

                                                <li>
                                                    <button
                                                        type="button"
                                                        class="dropdown-item text-danger"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#deleteComponentModal"
                                                        data-delete-name="{{ $component->name }}"
                                                        data-delete-url="{{ route('academic.assessment-templates.components.destroy', [$template, $component]) }}"
                                                    >
                                                        <i class="bi bi-trash me-2"></i>Delete Component
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-layers"></i>
                    </div>

                    <h5 class="empty-state-title">Component belum tersedia</h5>
                    <p class="empty-state-text mb-3">
                        Buat component assessment seperti attendance, progress, quiz, assignment, atau final project.
                    </p>

                    <button
                        type="button"
                        class="btn btn-primary btn-modern"
                        data-bs-toggle="modal"
                        data-bs-target="#componentModal"
                        data-mode="create"
                    >
                        <i class="bi bi-plus-circle me-2"></i>Add First Component
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="modal fade" id="componentModal" tabindex="-1" aria-labelledby="componentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content custom-modal">
            <form
                id="componentForm"
                data-store-url="{{ route('academic.assessment-templates.components.store', $template) }}"
            >
                @csrf
                <input type="hidden" name="_method" value="POST">

                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title" id="componentModalLabel">Add Assessment Component</h5>
                        <p class="text-muted mb-0" id="componentModalSubtitle">
                            Tambahkan component penilaian untuk template ini.
                        </p>
                    </div>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-4">
                    <div class="alert alert-danger d-none" id="componentFormAlert"></div>

                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label">Component Name <span class="text-danger">*</span></label>
                            <input
                                type="text"
                                name="name"
                                class="form-control"
                                placeholder="Contoh: Final Project"
                                required
                            >
                        </div>

                        <div class="col-md-5">
                            <label class="form-label">Code</label>
                            <input
                                type="text"
                                name="code"
                                class="form-control"
                                placeholder="Contoh: FINAL_PROJECT"
                            >
                            <div class="form-text">Kosongkan kalau ingin dibuat otomatis.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Type <span class="text-danger">*</span></label>
                            <select name="type" class="form-select" required>
                                @foreach($componentTypes as $typeValue => $typeLabel)
                                    <option value="{{ $typeValue }}">{{ $typeLabel }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Sort Order</label>
                            <input
                                type="number"
                                name="sort_order"
                                class="form-control"
                                min="0"
                                step="1"
                                placeholder="Auto"
                            >
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Weight <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input
                                    type="number"
                                    name="weight"
                                    class="form-control"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    value="0"
                                    required
                                >
                                <span class="input-group-text">%</span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Max Score <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input
                                    type="number"
                                    name="max_score"
                                    class="form-control"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    value="100"
                                    required
                                >
                                <span class="input-group-text">/100</span>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Required</label>
                            <select name="is_required" class="form-select">
                                <option value="1">Required</option>
                                <option value="0">Optional</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Calculation</label>
                            <select name="is_auto_calculated" class="form-select">
                                <option value="0">Manual Grading</option>
                                <option value="1">Auto Calculated</option>
                            </select>
                            <div class="form-text">
                                Attendance/progress biasanya auto calculated.
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea
                                name="description"
                                class="form-control"
                                rows="4"
                                placeholder="Jelaskan fungsi component ini..."
                            ></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-primary btn-modern" id="componentSubmitBtn">
                        Save Component
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteComponentModal" tabindex="-1" aria-labelledby="deleteComponentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title" id="deleteComponentModalLabel">Delete Assessment Component</h5>
                    <p class="text-muted mb-0">
                        Konfirmasi sebelum menghapus component assessment.
                    </p>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pt-4">
                <div class="alert alert-warning mb-3">
                    Component yang dihapus tidak akan tampil lagi pada template ini.
                    Pastikan component ini belum digunakan untuk scoring aktif.
                </div>

                <div class="border rounded-3 p-3">
                    <div class="text-muted small mb-1">Component yang akan dihapus</div>
                    <div class="fw-bold text-dark" id="deleteComponentName">-</div>
                </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                    Cancel
                </button>

                <button type="button" class="btn btn-danger btn-modern" id="confirmDeleteComponentBtn">
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
    const csrfToken = '{{ csrf_token() }}';

    function showToast(message, type = 'success') {
        const toastEl = document.getElementById('appToast');

        if (!toastEl) {
            alert(message);
            return;
        }

        const toastBody = toastEl.querySelector('.toast-body');

        if (toastBody) {
            toastBody.innerHTML = message;
        }

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

    function showFormErrors(alertBox, errors, fallbackMessage = 'Terjadi kesalahan. Silakan cek kembali input.') {
        if (!alertBox) {
            return;
        }

        let html = '';

        if (errors && typeof errors === 'object') {
            Object.keys(errors).forEach(function (field) {
                const messages = errors[field];

                if (Array.isArray(messages)) {
                    messages.forEach(function (message) {
                        html += `<div>${message}</div>`;
                    });
                } else if (messages) {
                    html += `<div>${messages}</div>`;
                }
            });
        }

        if (!html) {
            html = `<div>${fallbackMessage}</div>`;
        }

        alertBox.innerHTML = html;
        alertBox.classList.remove('d-none');
    }

    function setupComponentModal() {
        const modal = document.getElementById('componentModal');
        const form = document.getElementById('componentForm');
        const alertBox = document.getElementById('componentFormAlert');
        const submitBtn = document.getElementById('componentSubmitBtn');

        if (!modal || !form || !submitBtn) {
            return;
        }

        modal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const mode = button?.dataset?.mode || 'create';

            form.reset();
            form.dataset.updateUrl = '';
            form.querySelector('input[name="_method"]').value = 'POST';

            if (alertBox) {
                alertBox.classList.add('d-none');
                alertBox.innerHTML = '';
            }

            submitBtn.disabled = false;

            const title = document.getElementById('componentModalLabel');
            const subtitle = document.getElementById('componentModalSubtitle');

            if (mode === 'edit') {
                title.textContent = 'Edit Assessment Component';
                subtitle.textContent = 'Perbarui component penilaian pada template ini.';
                submitBtn.textContent = 'Update Component';

                form.dataset.updateUrl = button.dataset.updateUrl || '';
                form.querySelector('input[name="_method"]').value = 'PUT';

                form.querySelector('input[name="name"]').value = button.dataset.name || '';
                form.querySelector('input[name="code"]').value = button.dataset.code || '';
                form.querySelector('select[name="type"]').value = button.dataset.type || 'custom';
                form.querySelector('input[name="weight"]').value = button.dataset.weight || 0;
                form.querySelector('input[name="max_score"]').value = button.dataset.maxScore || 100;
                form.querySelector('select[name="is_required"]').value = button.dataset.isRequired || '1';
                form.querySelector('select[name="is_auto_calculated"]').value = button.dataset.isAutoCalculated || '0';
                form.querySelector('input[name="sort_order"]').value = button.dataset.sortOrder || '';
                form.querySelector('textarea[name="description"]').value = button.dataset.description || '';
            } else {
                title.textContent = 'Add Assessment Component';
                subtitle.textContent = 'Tambahkan component penilaian untuk template ini.';
                submitBtn.textContent = 'Save Component';

                form.querySelector('input[name="weight"]').value = 0;
                form.querySelector('input[name="max_score"]').value = 100;
                form.querySelector('select[name="is_required"]').value = '1';
                form.querySelector('select[name="is_auto_calculated"]').value = '0';
            }
        });

        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            const method = form.querySelector('input[name="_method"]').value;
            const url = method === 'PUT'
                ? form.dataset.updateUrl
                : form.dataset.storeUrl;

            if (!url) {
                showToast('Route component belum tersedia.', 'error');
                return;
            }

            if (alertBox) {
                alertBox.classList.add('d-none');
                alertBox.innerHTML = '';
            }

            submitBtn.disabled = true;
            submitBtn.textContent = method === 'PUT' ? 'Updating...' : 'Saving...';

            const formData = new FormData(form);

            try {
                const response = await fetch(url, {
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
                    showFormErrors(alertBox, data.errors || {}, data.message || 'Gagal menyimpan component.');
                    showToast(data.message || 'Gagal menyimpan component.', 'error');

                    submitBtn.disabled = false;
                    submitBtn.textContent = method === 'PUT' ? 'Update Component' : 'Save Component';

                    return;
                }

                const modalInstance = bootstrap.Modal.getInstance(modal);

                if (modalInstance) {
                    modalInstance.hide();
                }

                showToast(data.message || 'Assessment component berhasil disimpan.', 'success');

                setTimeout(() => {
                    window.location.reload();
                }, 900);
            } catch (error) {
                showFormErrors(alertBox, {}, 'Gagal menghubungi server.');
                showToast('Gagal menghubungi server.', 'error');

                submitBtn.disabled = false;
                submitBtn.textContent = method === 'PUT' ? 'Update Component' : 'Save Component';
            }
        });
    }

    function setupDeleteComponentModal() {
        const modal = document.getElementById('deleteComponentModal');
        const confirmBtn = document.getElementById('confirmDeleteComponentBtn');
        const nameTarget = document.getElementById('deleteComponentName');

        if (!modal || !confirmBtn) {
            return;
        }

        modal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;

            modal.dataset.deleteUrl = button?.dataset?.deleteUrl || '';

            if (nameTarget) {
                nameTarget.textContent = button?.dataset?.deleteName || '-';
            }

            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<i class="bi bi-trash me-2"></i>Delete';
        });

        confirmBtn.addEventListener('click', async function () {
            const deleteUrl = modal.dataset.deleteUrl;

            if (!deleteUrl) {
                showToast('Route delete component belum tersedia.', 'error');
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
                    showToast(data.message || 'Gagal menghapus component.', 'error');

                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = '<i class="bi bi-trash me-2"></i>Delete';

                    return;
                }

                const modalInstance = bootstrap.Modal.getInstance(modal);

                if (modalInstance) {
                    modalInstance.hide();
                }

                showToast(data.message || 'Assessment component berhasil dihapus.', 'success');

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

    setupComponentModal();
    setupDeleteComponentModal();
});
</script>
@endpush