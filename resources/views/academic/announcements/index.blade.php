@extends('layouts.app-dashboard')

@section('title', 'Announcements')

@php
    $routePrefix = Route::has('academic.announcements.index')
        ? 'academic.announcements'
        : 'announcements';

    $indexRoute = Route::has($routePrefix . '.index')
        ? route($routePrefix . '.index')
        : url()->current();

    $storeRoute = Route::has($routePrefix . '.store')
        ? route($routePrefix . '.store')
        : '#';

    $showRouteTemplate = Route::has($routePrefix . '.show')
        ? route($routePrefix . '.show', ['announcement' => '__ID__'])
        : '#';

    $updateRouteTemplate = Route::has($routePrefix . '.update')
        ? route($routePrefix . '.update', ['announcement' => '__ID__'])
        : '#';

    $destroyRouteTemplate = Route::has($routePrefix . '.destroy')
        ? route($routePrefix . '.destroy', ['announcement' => '__ID__'])
        : '#';

    $targetOptions = [
        'all' => 'All Students',
        'program' => 'Program',
        'batch' => 'Batch',
    ];

    $statusOptions = [
        'draft' => 'Draft',
        'published' => 'Published',
        'archived' => 'Archived',
    ];

    $statusBadgeMap = [
        'draft' => 'ui-badge-muted',
        'published' => 'ui-badge-success',
        'archived' => 'ui-badge-primary',
    ];

    $targetBadgeMap = [
        'all' => 'ui-badge-primary',
        'program' => 'ui-badge-info',
        'batch' => 'ui-badge-warning',
    ];

    $pageStats = [
        'total' => $stats['total'] ?? 0,
        'published' => $stats['published'] ?? 0,
        'draft' => $stats['draft'] ?? 0,
        'pinned' => $stats['pinned'] ?? 0,
    ];

    $currentSearch = $search ?? request('search');
    $currentStatus = $status ?? request('status');
    $currentTargetType = $targetType ?? request('target_type');
    $currentProgramId = $programId ?? request('program_id');
    $currentBatchId = $batchId ?? request('batch_id');
    $currentPerPage = (int) request('per_page', 10);
@endphp

@section('content')
<div class="container-fluid px-4 py-4">
    <div
        id="toastContainer"
        class="toast-container position-fixed top-0 end-0 p-3"
        style="z-index: 9999;"
    ></div>

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Academic Communication</div>

                <h1 class="page-title mb-2">Announcements</h1>

                <p class="page-subtitle mb-0">
                    Kelola pengumuman untuk student berdasarkan target all students, program, atau batch tertentu.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <a
                    href="{{ $indexRoute }}"
                    class="btn btn-light btn-modern"
                >
                    <i class="bi bi-arrow-counterclockwise me-2"></i>Reset Filter
                </a>

                <button
                    type="button"
                    class="btn btn-primary btn-modern"
                    onclick="openCreateModal()"
                >
                    <i class="bi bi-plus-circle me-2"></i>Add Announcement
                </button>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-megaphone-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Total</div>
                        <div class="stat-value">{{ $pageStats['total'] }}</div>
                    </div>
                </div>
                <div class="stat-description">Semua announcement yang dibuat.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-broadcast-pin"></i>
                    </div>
                    <div>
                        <div class="stat-title">Published</div>
                        <div class="stat-value">{{ $pageStats['published'] }}</div>
                    </div>
                </div>
                <div class="stat-description">Announcement yang bisa tampil di LMS.</div>
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
                        <div class="stat-value">{{ $pageStats['draft'] }}</div>
                    </div>
                </div>
                <div class="stat-description">Masih disimpan sebagai draft.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-pin-angle-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Pinned</div>
                        <div class="stat-value">{{ $pageStats['pinned'] }}</div>
                    </div>
                </div>
                <div class="stat-description">Announcement prioritas di LMS.</div>
            </div>
        </div>
    </div>

    <div class="content-card">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Announcement List</h5>
                <p class="content-card-subtitle mb-0">
                    Announcement yang published dan sesuai target akan tampil di dashboard LMS student.
                </p>
            </div>

            <form method="GET" action="{{ $indexRoute }}" class="d-flex align-items-center gap-2 flex-wrap">
                <input type="hidden" name="search" value="{{ $currentSearch }}">
                <input type="hidden" name="status" value="{{ $currentStatus }}">
                <input type="hidden" name="target_type" value="{{ $currentTargetType }}">
                <input type="hidden" name="program_id" value="{{ $currentProgramId }}">
                <input type="hidden" name="batch_id" value="{{ $currentBatchId }}">

                <label for="per_page" class="form-label mb-0 text-muted">Show</label>

                <select
                    name="per_page"
                    id="per_page"
                    class="form-select form-select-sm"
                    style="width: 90px;"
                    onchange="this.form.submit()"
                >
                    @foreach ([10, 25, 50, 100] as $size)
                        <option value="{{ $size }}" {{ $currentPerPage === $size ? 'selected' : '' }}>
                            {{ $size }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="content-card-body border-bottom">
            <form method="GET" action="{{ $indexRoute }}" class="row g-3 align-items-end">
                <input type="hidden" name="per_page" value="{{ $currentPerPage }}">

                <div class="col-xl-3 col-md-6">
                    <label for="search" class="form-label">Search</label>
                    <input
                        type="text"
                        name="search"
                        id="search"
                        class="form-control"
                        value="{{ $currentSearch }}"
                        placeholder="Title, slug, content..."
                    >
                </div>

                <div class="col-xl-2 col-md-6">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">All status</option>
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" {{ (string) $currentStatus === (string) $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-2 col-md-6">
                    <label for="target_type" class="form-label">Target</label>
                    <select name="target_type" id="target_type" class="form-select">
                        <option value="">All target</option>
                        @foreach($targetOptions as $value => $label)
                            <option value="{{ $value }}" {{ (string) $currentTargetType === (string) $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-2 col-md-6">
                    <label for="program_filter" class="form-label">Program</label>
                    <select name="program_id" id="program_filter" class="form-select">
                        <option value="">All programs</option>
                        @foreach($programs as $program)
                            <option value="{{ $program->id }}" {{ (string) $currentProgramId === (string) $program->id ? 'selected' : '' }}>
                                {{ $program->name ?? $program->title ?? 'Untitled Program' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="d-flex gap-2">
                        <a href="{{ $indexRoute }}" class="btn btn-outline-secondary btn-modern w-100">
                            Clear
                        </a>

                        <button type="submit" class="btn btn-primary btn-modern w-100">
                            Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="content-card-body p-0">
            @if(($announcements ?? collect())->count())
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 70px;">No</th>
                                <th>Announcement</th>
                                <th>Target</th>
                                <th>Publish Window</th>
                                <th>Status</th>
                                <th>Visibility</th>
                                <th class="text-end" style="width: 300px; min-width: 300px;">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($announcements as $announcement)
                                @php
                                    $targetValue = $announcement->target_type ?? 'all';
                                    $targetLabel = $targetOptions[$targetValue] ?? \Illuminate\Support\Str::title($targetValue);
                                    $targetBadge = $targetBadgeMap[$targetValue] ?? 'ui-badge-muted';

                                    $statusValue = $announcement->status ?? 'draft';
                                    $statusLabel = $statusOptions[$statusValue] ?? \Illuminate\Support\Str::title($statusValue);
                                    $statusBadge = $statusBadgeMap[$statusValue] ?? 'ui-badge-muted';

                                    $publishLabel = $announcement->publish_at
                                        ? $announcement->publish_at->format('d M Y H:i')
                                        : 'Immediately';

                                    $expiredLabel = $announcement->expired_at
                                        ? $announcement->expired_at->format('d M Y H:i')
                                        : 'No expiry';

                                    $targetDetail = 'All Students';

                                    if ($targetValue === 'program') {
                                        $targetDetail = $announcement->program?->name ?? 'Program not found';
                                    }

                                    if ($targetValue === 'batch') {
                                        $targetDetail = ($announcement->batch?->name ?? 'Batch not found')
                                            . ($announcement->batch?->program?->name ? ' — ' . $announcement->batch->program->name : '');
                                    }
                                @endphp

                                <tr>
                                    <td>
                                        @if(method_exists($announcements, 'currentPage'))
                                            {{ ($announcements->currentPage() - 1) * $announcements->perPage() + $loop->iteration }}
                                        @else
                                            {{ $loop->iteration }}
                                        @endif
                                    </td>

                                    <td>
                                        <div class="entity-main">
                                            <div class="entity-icon">
                                                <i class="bi bi-megaphone-fill"></i>
                                            </div>

                                            <div class="entity-info">
                                                <div class="entity-title-row">
                                                    <div>
                                                        <h6 class="entity-title mb-1">
                                                            {{ $announcement->title }}
                                                            @if($announcement->is_pinned)
                                                                <span class="ui-badge ui-badge-warning ms-1">
                                                                    <i class="bi bi-pin-angle-fill me-1"></i>Pinned
                                                                </span>
                                                            @endif
                                                        </h6>

                                                        <div class="entity-subtitle">
                                                            {{ $announcement->content ? \Illuminate\Support\Str::limit(strip_tags($announcement->content), 90) : 'No content added yet' }}
                                                        </div>

                                                        <div class="text-muted small mt-1">
                                                            <code>{{ $announcement->slug }}</code>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <span class="ui-badge {{ $targetBadge }}">
                                            {{ $targetLabel }}
                                        </span>

                                        <div class="text-muted small mt-1">
                                            {{ $targetDetail }}
                                        </div>
                                    </td>

                                    <td>
                                        <div class="fw-semibold">{{ $publishLabel }}</div>
                                        <div class="text-muted small">
                                            Until: {{ $expiredLabel }}
                                        </div>
                                    </td>

                                    <td>
                                        <span class="ui-badge {{ $statusBadge }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>

                                    <td>
                                        @if($announcement->is_visible)
                                            <span class="ui-badge ui-badge-success">Visible</span>
                                        @elseif(!$announcement->is_active)
                                            <span class="ui-badge ui-badge-muted">Inactive</span>
                                        @else
                                            <span class="ui-badge ui-badge-warning">Hidden</span>
                                        @endif
                                    </td>

                                    <td>
                                        <div class="d-flex justify-content-end gap-2 flex-nowrap">
                                            <button
                                                type="button"
                                                class="btn btn-outline-secondary btn-sm btn-modern text-nowrap"
                                                onclick="editAnnouncement({{ $announcement->id }})"
                                            >
                                                <i class="bi bi-pencil-square me-1"></i>Edit
                                            </button>

                                            <button
                                                type="button"
                                                class="btn btn-outline-danger btn-sm btn-modern text-nowrap"
                                                onclick="openDeleteModal({{ $announcement->id }}, @js($announcement->title))"
                                            >
                                                <i class="bi bi-trash me-1"></i>Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if(method_exists($announcements, 'hasPages') && $announcements->hasPages())
                    <div class="p-3 border-top">
                        {{ $announcements->links() }}
                    </div>
                @endif
            @else
                <div class="d-flex align-items-center justify-content-center px-4 py-5" style="min-height: 360px;">
                    <div class="text-center" style="max-width: 460px;">
                        <div
                            class="mx-auto mb-4 d-flex align-items-center justify-content-center rounded-4"
                            style="width: 76px; height: 76px; background: #E8DBFF; color: #5B3E8E;"
                        >
                            <i class="bi bi-megaphone fs-2"></i>
                        </div>

                        <h5 class="mb-2 fw-bold text-dark">
                            No announcements found
                        </h5>

                        <p class="mb-3 text-muted">
                            Announcement untuk LMS student akan muncul di sini setelah dibuat dari FlexOps.
                        </p>

                        <button type="button" class="btn btn-primary btn-modern" onclick="openCreateModal()">
                            <i class="bi bi-plus-circle me-2"></i>Add Announcement
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Announcement Modal --}}
<div class="modal fade" id="announcementModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <form id="announcementForm">
            @csrf
            <input type="hidden" id="announcement_id" name="announcement_id">

            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title" id="announcementModalTitle">Add Announcement</h5>
                        <p class="text-muted small mb-0">
                            Buat announcement dan pilih target student yang akan melihat pengumuman ini di LMS.
                        </p>
                    </div>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-4">
                    <div id="formAlert" class="alert alert-danger d-none mb-3"></div>

                    <div class="entity-card mb-4">
                        <div class="entity-main">
                            <div class="entity-icon">
                                <i class="bi bi-megaphone-fill"></i>
                            </div>

                            <div class="entity-info">
                                <div class="entity-title-row">
                                    <div>
                                        <h6 class="entity-title mb-1">Announcement Content</h6>
                                        <div class="entity-subtitle">
                                            Published announcement akan tampil ke student sesuai target program atau batch yang dipilih.
                                        </div>
                                    </div>

                                    <div class="entity-badges">
                                        <span class="ui-badge ui-badge-primary">LMS</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="title" class="form-label">
                                Title <span class="text-danger">*</span>
                            </label>
                            <input
                                type="text"
                                id="title"
                                name="title"
                                class="form-control"
                                placeholder="Example: Reminder Project Submission"
                            >
                            <div class="invalid-feedback" id="error_title"></div>
                        </div>

                        <div class="col-md-4">
                            <label for="slug" class="form-label">Slug</label>
                            <input
                                type="text"
                                id="slug"
                                name="slug"
                                class="form-control"
                                placeholder="auto from title"
                            >
                            <div class="form-text">Kosongkan kalau mau auto generate.</div>
                            <div class="invalid-feedback" id="error_slug"></div>
                        </div>

                        <div class="col-md-4">
                            <label for="target_type_modal" class="form-label">
                                Target <span class="text-danger">*</span>
                            </label>
                            <select id="target_type_modal" name="target_type" class="form-select">
                                <option value="all">All Students</option>
                                <option value="program">Program</option>
                                <option value="batch">Batch</option>
                            </select>
                            <div class="invalid-feedback" id="error_target_type"></div>
                        </div>

                        <div class="col-md-4" id="programField">
                            <label for="program_id_modal" class="form-label">Program</label>
                            <select id="program_id_modal" name="program_id" class="form-select">
                                <option value="">Select program</option>
                                @foreach($programs as $program)
                                    <option value="{{ $program->id }}">
                                        {{ $program->name ?? $program->title ?? 'Untitled Program' }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="error_program_id"></div>
                        </div>

                        <div class="col-md-4" id="batchField">
                            <label for="batch_id_modal" class="form-label">Batch</label>
                            <select id="batch_id_modal" name="batch_id" class="form-select">
                                <option value="">Select batch</option>
                                @foreach($batches as $batch)
                                    @php
                                        $batchProgramId = $batch->program_id ?? $batch->program?->id ?? null;
                                        $batchProgramName = $batch->program?->name ?? 'Program not set';
                                    @endphp
                                    <option
                                        value="{{ $batch->id }}"
                                        data-program-id="{{ $batchProgramId }}"
                                    >
                                        {{ $batch->name ?? 'Untitled Batch' }} — {{ $batchProgramName }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Batch akan difilter sesuai program.</div>
                            <div class="invalid-feedback" id="error_batch_id"></div>
                        </div>

                        <div class="col-md-4">
                            <label for="publish_at" class="form-label">Publish At</label>
                            <input
                                type="datetime-local"
                                id="publish_at"
                                name="publish_at"
                                class="form-control"
                            >
                            <div class="form-text">Kosongkan jika ingin langsung aktif saat published.</div>
                            <div class="invalid-feedback" id="error_publish_at"></div>
                        </div>

                        <div class="col-md-4">
                            <label for="expired_at" class="form-label">Expired At</label>
                            <input
                                type="datetime-local"
                                id="expired_at"
                                name="expired_at"
                                class="form-control"
                            >
                            <div class="form-text">Kosongkan jika tidak ada tanggal expired.</div>
                            <div class="invalid-feedback" id="error_expired_at"></div>
                        </div>

                        <div class="col-md-4">
                            <label for="status_modal" class="form-label">Status</label>
                            <select id="status_modal" name="status" class="form-select">
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                                <option value="archived">Archived</option>
                            </select>
                            <div class="invalid-feedback" id="error_status"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="is_pinned" class="form-label">Pinned</label>
                            <select id="is_pinned" name="is_pinned" class="form-select">
                                <option value="0">No</option>
                                <option value="1">Yes</option>
                            </select>
                            <div class="form-text">Pinned akan diprioritaskan di dashboard LMS.</div>
                            <div class="invalid-feedback" id="error_is_pinned"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="is_active" class="form-label">Active</label>
                            <select id="is_active" name="is_active" class="form-select">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                            <div class="form-text">Inactive tidak akan tampil walaupun status published.</div>
                            <div class="invalid-feedback" id="error_is_active"></div>
                        </div>

                        <div class="col-12">
                            <label for="content" class="form-label">Content</label>
                            <textarea
                                id="content"
                                name="content"
                                rows="7"
                                class="form-control"
                                placeholder="Write announcement content..."
                            ></textarea>
                            <div class="invalid-feedback" id="error_content"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-primary btn-modern" id="submitBtn">
                        <span class="default-text">
                            <i class="bi bi-check-circle me-2"></i>Save Announcement
                        </span>
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
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title">Delete Announcement</h5>
                    <p class="text-muted small mb-0">
                        Announcement yang dihapus tidak bisa dikembalikan.
                    </p>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pt-4">
                <div class="delete-confirm-message">
                    <div class="delete-confirm-label">Announcement yang akan dihapus</div>
                    <div class="delete-confirm-name" id="deleteAnnouncementName">-</div>
                </div>

                <div class="delete-confirm-warning mt-3">
                    Announcement ini akan hilang dari FlexOps dan LMS student.
                </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                    Cancel
                </button>

                <button type="button" class="btn btn-danger btn-modern" id="confirmDeleteBtn">
                    <span class="default-text">
                        <i class="bi bi-trash me-2"></i>Delete
                    </span>
                    <span class="loading-text d-none">Deleting...</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let announcementModal;
let deleteModal;
let deleteAnnouncementId = null;

const storeRoute = @js($storeRoute);
const showRouteTemplate = @js($showRouteTemplate);
const updateRouteTemplate = @js($updateRouteTemplate);
const destroyRouteTemplate = @js($destroyRouteTemplate);

document.addEventListener('DOMContentLoaded', function () {
    announcementModal = new bootstrap.Modal(document.getElementById('announcementModal'));
    deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));

    document.getElementById('announcementForm').addEventListener('submit', submitAnnouncementForm);
    document.getElementById('confirmDeleteBtn').addEventListener('click', deleteAnnouncement);

    document.getElementById('target_type_modal')?.addEventListener('change', syncTargetFields);
    document.getElementById('program_id_modal')?.addEventListener('change', syncBatchOptionsByProgram);

    syncTargetFields();
});

function csrfToken() {
    return document.querySelector('#announcementForm input[name="_token"]')?.value || '{{ csrf_token() }}';
}

function buildRoute(template, id) {
    return String(template || '#').replace('__ID__', id);
}

function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    const toastId = 'toast-' + Date.now();

    const bgClass = type === 'success'
        ? 'bg-success'
        : type === 'warning'
            ? 'bg-warning'
            : type === 'info'
                ? 'bg-primary'
                : 'bg-danger';

    container.insertAdjacentHTML('beforeend', `
        <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body fw-semibold">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `);

    const toastEl = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastEl, { delay: 2600 });

    toast.show();

    toastEl.addEventListener('hidden.bs.toast', function () {
        toastEl.remove();
    });
}

function setButtonLoading(button, loading = true) {
    const defaultText = button.querySelector('.default-text');
    const loadingText = button.querySelector('.loading-text');

    button.disabled = loading;

    if (defaultText) defaultText.classList.toggle('d-none', loading);
    if (loadingText) loadingText.classList.toggle('d-none', !loading);
}

function resetErrors() {
    document.querySelectorAll('#announcementForm .is-invalid').forEach(el => el.classList.remove('is-invalid'));
    document.querySelectorAll('#announcementForm .invalid-feedback').forEach(el => el.innerText = '');

    const alert = document.getElementById('formAlert');
    alert.classList.add('d-none');
    alert.innerText = '';
}

function fillValidationErrors(errors) {
    const alert = document.getElementById('formAlert');
    let firstMessage = 'Please check the form again.';

    Object.keys(errors || {}).forEach(function (field) {
        const messages = errors[field];
        const message = Array.isArray(messages) ? messages[0] : messages;

        firstMessage = message || firstMessage;

        const input = document.querySelector(`#announcementForm [name="${field}"]`);
        const feedback = document.getElementById('error_' + field);

        if (input) input.classList.add('is-invalid');
        if (feedback) feedback.innerText = message;
    });

    alert.innerText = firstMessage;
    alert.classList.remove('d-none');
}

function resetAnnouncementForm() {
    resetErrors();

    document.getElementById('announcementForm').reset();
    document.getElementById('announcement_id').value = '';
    document.getElementById('target_type_modal').value = 'all';
    document.getElementById('status_modal').value = 'draft';
    document.getElementById('is_pinned').value = '0';
    document.getElementById('is_active').value = '1';

    syncTargetFields();
}

function syncTargetFields() {
    const targetType = document.getElementById('target_type_modal')?.value || 'all';
    const programField = document.getElementById('programField');
    const batchField = document.getElementById('batchField');
    const programInput = document.getElementById('program_id_modal');
    const batchInput = document.getElementById('batch_id_modal');

    if (targetType === 'all') {
        programField.classList.add('d-none');
        batchField.classList.add('d-none');
        programInput.value = '';
        batchInput.value = '';
        return;
    }

    if (targetType === 'program') {
        programField.classList.remove('d-none');
        batchField.classList.add('d-none');
        batchInput.value = '';
        return;
    }

    programField.classList.remove('d-none');
    batchField.classList.remove('d-none');

    syncBatchOptionsByProgram();
}

function syncBatchOptionsByProgram() {
    const programSelect = document.getElementById('program_id_modal');
    const batchSelect = document.getElementById('batch_id_modal');

    if (!programSelect || !batchSelect) return;

    const selectedProgramId = String(programSelect.value || '');

    Array.from(batchSelect.options).forEach((option) => {
        if (!option.value) {
            option.hidden = false;
            option.disabled = false;
            return;
        }

        const optionProgramId = String(option.dataset.programId || '');
        const shouldHide = selectedProgramId && optionProgramId !== selectedProgramId;

        option.hidden = shouldHide;
        option.disabled = shouldHide;
    });

    const selectedOption = batchSelect.options[batchSelect.selectedIndex];

    if (selectedOption && selectedOption.disabled) {
        batchSelect.value = '';
    }
}

function openCreateModal() {
    resetAnnouncementForm();

    document.getElementById('announcementModalTitle').innerText = 'Add Announcement';

    announcementModal.show();
}

async function editAnnouncement(id) {
    resetAnnouncementForm();

    try {
        const response = await fetch(buildRoute(showRouteTemplate, id), {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        const result = await response.json().catch(() => ({}));

        if (!response.ok || !result.success) {
            showToast(result.message || 'Failed to load announcement.', 'error');
            return;
        }

        const data = result.data || result;

        document.getElementById('announcementModalTitle').innerText = 'Edit Announcement';
        document.getElementById('announcement_id').value = data.id || '';

        document.getElementById('title').value = data.title || '';
        document.getElementById('slug').value = data.slug || '';
        document.getElementById('content').value = data.content || '';

        document.getElementById('target_type_modal').value = data.target_type || 'all';
        document.getElementById('program_id_modal').value = data.program_id || '';
        document.getElementById('batch_id_modal').value = data.batch_id || '';

        document.getElementById('publish_at').value = data.publish_at_input || '';
        document.getElementById('expired_at').value = data.expired_at_input || '';

        document.getElementById('status_modal').value = data.status || 'draft';
        document.getElementById('is_pinned').value = Number(data.is_pinned ?? 0).toString();
        document.getElementById('is_active').value = Number(data.is_active ?? 1).toString();

        syncTargetFields();
        syncBatchOptionsByProgram();

        announcementModal.show();
    } catch (error) {
        showToast('Failed to load announcement.', 'error');
    }
}

async function submitAnnouncementForm(event) {
    event.preventDefault();
    resetErrors();

    const submitBtn = document.getElementById('submitBtn');
    setButtonLoading(submitBtn, true);

    const id = document.getElementById('announcement_id').value;
    const form = document.getElementById('announcementForm');
    const formData = new FormData(form);

    let url = storeRoute;

    if (id) {
        url = buildRoute(updateRouteTemplate, id);
        formData.append('_method', 'PUT');
    }

    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: formData,
            credentials: 'same-origin',
        });

        const result = await response.json().catch(() => ({}));

        if (!response.ok) {
            if (response.status === 422) {
                fillValidationErrors(result.errors || {});
            } else {
                const alert = document.getElementById('formAlert');
                alert.innerText = result.message || 'Failed to save announcement.';
                alert.classList.remove('d-none');
            }

            setButtonLoading(submitBtn, false);
            return;
        }

        showToast(result.message || 'Announcement saved successfully.');
        announcementModal.hide();

        setTimeout(() => window.location.reload(), 700);
    } catch (error) {
        const alert = document.getElementById('formAlert');
        alert.innerText = 'Failed to connect to server.';
        alert.classList.remove('d-none');

        setButtonLoading(submitBtn, false);
    }
}

function openDeleteModal(id, title) {
    deleteAnnouncementId = id;
    document.getElementById('deleteAnnouncementName').innerText = title || '-';
    deleteModal.show();
}

async function deleteAnnouncement() {
    if (!deleteAnnouncementId) return;

    const deleteBtn = document.getElementById('confirmDeleteBtn');
    setButtonLoading(deleteBtn, true);

    const formData = new FormData();
    formData.append('_token', csrfToken());
    formData.append('_method', 'DELETE');

    try {
        const response = await fetch(buildRoute(destroyRouteTemplate, deleteAnnouncementId), {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: formData,
            credentials: 'same-origin',
        });

        const result = await response.json().catch(() => ({}));

        if (!response.ok) {
            showToast(result.message || 'Failed to delete announcement.', 'error');
            setButtonLoading(deleteBtn, false);
            return;
        }

        showToast(result.message || 'Announcement deleted successfully.');
        deleteModal.hide();

        setTimeout(() => window.location.reload(), 700);
    } catch (error) {
        showToast('Failed to connect to server.', 'error');
        setButtonLoading(deleteBtn, false);
    }
}
</script>
@endpush