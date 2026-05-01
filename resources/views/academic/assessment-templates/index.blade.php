@extends('layouts.app-dashboard')

@section('title', 'Assessment Templates')

@section('content')
<div class="container-fluid px-4 py-4">

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Academic Management</div>
                <h1 class="page-title mb-2">Assessment Templates</h1>
                <p class="page-subtitle mb-0">
                    Kelola standar penilaian per program, mulai dari passing score, minimum attendance,
                    learning progress, sampai kebutuhan final project.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <a href="{{ route('academic.assessment-templates.create') }}" class="btn btn-primary btn-modern">
                    <i class="bi bi-plus-circle me-2"></i>Add Template
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

    @php
        $templateCollection = method_exists($templates ?? null, 'getCollection')
            ? $templates->getCollection()
            : collect($templates ?? []);

        $totalTemplates = method_exists($templates ?? null, 'total')
            ? $templates->total()
            : $templateCollection->count();

        $activeTemplates = $templateCollection->where('is_active', true)->count();
        $inactiveTemplates = $templateCollection->where('is_active', false)->count();
        $finalProjectTemplates = $templateCollection->where('requires_final_project', true)->count();
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-clipboard-check"></i>
                    </div>
                    <div>
                        <div class="stat-title">Total Templates</div>
                        <div class="stat-value">{{ $totalTemplates }}</div>
                    </div>
                </div>
                <div class="stat-description">Total assessment template yang tersedia.</div>
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
                        <div class="stat-value">{{ $activeTemplates }}</div>
                    </div>
                </div>
                <div class="stat-description">Template aktif pada halaman ini.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-toggle-off"></i>
                    </div>
                    <div>
                        <div class="stat-title">Inactive</div>
                        <div class="stat-value">{{ $inactiveTemplates }}</div>
                    </div>
                </div>
                <div class="stat-description">Template nonaktif pada halaman ini.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-kanban"></i>
                    </div>
                    <div>
                        <div class="stat-title">Final Project</div>
                        <div class="stat-value">{{ $finalProjectTemplates }}</div>
                    </div>
                </div>
                <div class="stat-description">Template yang membutuhkan final project.</div>
            </div>
        </div>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Filter Templates</h5>
                <p class="content-card-subtitle mb-0">
                    Filter assessment template berdasarkan program dan status.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            <form method="GET" action="{{ route('academic.assessment-templates.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-xl-5 col-md-6">
                        <label class="form-label">Program</label>
                        <select name="program_id" class="form-select">
                            <option value="">All Programs</option>
                            @foreach($programs ?? [] as $program)
                                <option value="{{ $program->id }}" {{ request('program_id') == $program->id ? 'selected' : '' }}>
                                    {{ $program->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>
                                Active
                            </option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>
                                Inactive
                            </option>
                        </select>
                    </div>

                    <div class="col-xl-4">
                        <div class="d-flex gap-2 justify-content-xl-end flex-wrap">
                            <a href="{{ route('academic.assessment-templates.index') }}" class="btn btn-outline-secondary btn-modern">
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
                <h5 class="content-card-title mb-1">Assessment Template List</h5>
                <p class="content-card-subtitle mb-0">
                    Template ini menjadi standar kelulusan dan komponen assessment untuk program tertentu.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            @if(($templates ?? collect())->count())
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Template</th>
                                <th>Program</th>
                                <th class="text-nowrap">Passing Score</th>
                                <th class="text-nowrap">Attendance</th>
                                <th class="text-nowrap">Progress</th>
                                <th class="text-nowrap">Final Project</th>
                                <th>Status</th>
                                <th class="text-end text-nowrap">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($templates as $template)
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $template->name }}</div>

                                        <div class="text-muted small">
                                            {{ $template->code ?: 'No code' }}
                                        </div>

                                        @if($template->description)
                                            <div class="text-muted small mt-1">
                                                {{ \Illuminate\Support\Str::limit(strip_tags($template->description), 90) }}
                                            </div>
                                        @endif
                                    </td>

                                    <td>
                                        <div class="text-nowrap">
                                            {{ $template->program->name ?? '-' }}
                                        </div>
                                    </td>

                                    <td class="text-nowrap">
                                        <span class="fw-semibold">
                                            {{ number_format((float) $template->passing_score, 2) }}
                                        </span>
                                    </td>

                                    <td class="text-nowrap">
                                        {{ number_format((float) $template->min_attendance_percent, 2) }}%
                                    </td>

                                    <td class="text-nowrap">
                                        {{ number_format((float) $template->min_progress_percent, 2) }}%
                                    </td>

                                    <td class="text-nowrap">
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

                                    <td class="text-nowrap">
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
                                                    <a
                                                        href="{{ route('academic.assessment-templates.show', $template) }}"
                                                        class="dropdown-item"
                                                    >
                                                        <i class="bi bi-eye me-2"></i>Show Detail
                                                    </a>
                                                </li>

                                                <li>
                                                    <a
                                                        href="{{ route('academic.assessment-templates.edit', $template) }}"
                                                        class="dropdown-item"
                                                    >
                                                        <i class="bi bi-pencil-square me-2"></i>Edit Template
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
                                                        data-bs-target="#deleteConfirmModal"
                                                        data-delete-name="{{ $template->name }}"
                                                        data-delete-url="{{ route('academic.assessment-templates.destroy', $template) }}"
                                                    >
                                                        <i class="bi bi-trash me-2"></i>Delete Template
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

                @if(method_exists($templates, 'links'))
                    <div class="mt-4">
                        {{ $templates->links() }}
                    </div>
                @endif
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-clipboard-x"></i>
                    </div>

                    <h5 class="empty-state-title">Assessment template belum tersedia</h5>
                    <p class="empty-state-text mb-3">
                        Belum ada assessment template yang dibuat. Mulai dengan membuat standar assessment untuk program.
                    </p>

                    <a href="{{ route('academic.assessment-templates.create') }}" class="btn btn-primary btn-modern">
                        <i class="bi bi-plus-circle me-2"></i>Add First Template
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content custom-modal">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title" id="deleteConfirmModalLabel">Delete Assessment Template</h5>
                    <p class="text-muted mb-0">
                        Konfirmasi sebelum menghapus template assessment.
                    </p>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pt-4">
                <div class="alert alert-warning mb-3">
                    Template yang sudah dihapus tidak akan tampil lagi di daftar assessment template.
                    Pastikan template ini belum digunakan pada assessment aktif.
                </div>

                <div class="border rounded-3 p-3">
                    <div class="text-muted small mb-1">Template yang akan dihapus</div>
                    <div class="fw-bold text-dark" id="deleteConfirmName">-</div>
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

    const deleteModal = document.getElementById('deleteConfirmModal');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

    if (!deleteModal || !confirmDeleteBtn) {
        return;
    }

    deleteModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;

        deleteModal.dataset.deleteUrl = button?.dataset?.deleteUrl || '';

        const nameTarget = deleteModal.querySelector('#deleteConfirmName');

        if (nameTarget) {
            nameTarget.textContent = button?.dataset?.deleteName || '-';
        }

        confirmDeleteBtn.disabled = false;
        confirmDeleteBtn.innerHTML = '<i class="bi bi-trash me-2"></i>Delete';
    });

    confirmDeleteBtn.addEventListener('click', async function () {
        const deleteUrl = deleteModal.dataset.deleteUrl;

        if (!deleteUrl) {
            showToast('Route delete belum tersedia.', 'error');
            return;
        }

        confirmDeleteBtn.disabled = true;
        confirmDeleteBtn.innerHTML = 'Deleting...';

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
                showToast(data.message || 'Gagal menghapus assessment template.', 'error');

                confirmDeleteBtn.disabled = false;
                confirmDeleteBtn.innerHTML = '<i class="bi bi-trash me-2"></i>Delete';

                return;
            }

            const modalInstance = bootstrap.Modal.getInstance(deleteModal);

            if (modalInstance) {
                modalInstance.hide();
            }

            showToast(data.message || 'Assessment template berhasil dihapus.', 'success');

            setTimeout(() => {
                window.location.reload();
            }, 900);
        } catch (error) {
            showToast('Gagal menghubungi server.', 'error');

            confirmDeleteBtn.disabled = false;
            confirmDeleteBtn.innerHTML = '<i class="bi bi-trash me-2"></i>Delete';
        }
    });
});
</script>
@endpush