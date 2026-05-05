@extends('layouts.app-dashboard')

@section('title', 'Workshops')

@section('content')
@php
    $workshopCollection = $workshops->getCollection();
    $summaryActive = $workshopCollection->where('is_active', true)->count();
    $summaryInactive = $workshopCollection->where('is_active', false)->count();
    $summaryDiscounted = $workshopCollection
        ->filter(fn ($item) => !is_null($item->old_price) && $item->old_price > $item->price)
        ->count();
@endphp

<div class="container-fluid px-4 py-4">
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Academic</div>
                <h1 class="page-title mb-2">Workshops</h1>
                <p class="page-subtitle mb-0">
                    Manage public workshop programs, pricing, categories, benefits, preview content, and publication status.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <a href="{{ route('academic.workshops.create') }}" class="btn btn-light btn-modern">
                    <i class="bi bi-plus-lg me-2"></i>Add Workshop
                </a>
            </div>
        </div>
    </div>

    <div
        id="toastContainer"
        class="toast-container position-fixed top-0 end-0 p-3"
        style="z-index: 9999;"
    ></div>

    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-easel2"></i>
                    </div>
                    <div>
                        <div class="stat-title">Total Workshops</div>
                        <div class="stat-value">{{ $workshops->total() }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Total workshop records based on the current filter.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div>
                        <div class="stat-title">Active</div>
                        <div class="stat-value">{{ $summaryActive }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Workshops currently published and available for public visitors.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-pause-circle"></i>
                    </div>
                    <div>
                        <div class="stat-title">Inactive</div>
                        <div class="stat-value">{{ $summaryInactive }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Workshops saved in the system but not shown publicly.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-tags"></i>
                    </div>
                    <div>
                        <div class="stat-title">Discounted</div>
                        <div class="stat-value">{{ $summaryDiscounted }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Workshops using promotional pricing with an old price value.
                </div>
            </div>
        </div>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Filter Workshops</h5>
                <p class="content-card-subtitle mb-0">
                    Search workshop records by title, slug, badge, category, or publication status.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            <form method="GET" action="{{ route('academic.workshops.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-xl-4 col-lg-4 col-md-6">
                        <label for="search" class="form-label">Search</label>
                        <input
                            type="text"
                            name="search"
                            id="search"
                            class="form-control"
                            value="{{ $filters['search'] ?? '' }}"
                            placeholder="Search title, slug, badge, category..."
                        >
                    </div>

                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <label for="category" class="form-label">Category</label>
                        <select name="category" id="category" class="form-select">
                            <option value="">All Categories</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category }}" @selected(($filters['category'] ?? '') === $category)>
                                    {{ $category }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-2 col-lg-4 col-md-6">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="1" @selected((string) ($filters['status'] ?? '') === '1')>
                                Active
                            </option>
                            <option value="0" @selected((string) ($filters['status'] ?? '') === '0')>
                                Inactive
                            </option>
                        </select>
                    </div>

                    <div class="col-xl-1 col-lg-4 col-md-6">
                        <label for="per_page" class="form-label">Show</label>
                        <select name="per_page" id="per_page" class="form-select">
                            @foreach ([10, 25, 50, 100] as $size)
                                <option value="{{ $size }}" @selected(($filters['per_page'] ?? 10) == $size)>
                                    {{ $size }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-2 col-lg-8 col-md-6">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-modern flex-fill">
                                <i class="bi bi-search me-2"></i>Filter
                            </button>

                            <a href="{{ route('academic.workshops.index') }}" class="btn btn-outline-secondary btn-modern">
                                Reset
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="content-card">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Workshop List</h5>
                <p class="content-card-subtitle mb-0">
                    Review workshop details, category, pricing, benefits, sort order, and publication status.
                </p>
            </div>

            <div class="small text-muted">
                Total: <strong>{{ $workshops->total() }}</strong> workshops
            </div>
        </div>

        <div class="content-card-body">
            @if ($workshops->count())
                <div class="table-responsive dropdown-safe-table">
                    <table class="table table-hover align-middle admin-table mb-0">
                        <thead>
                            <tr>
                                <th class="text-nowrap" style="width: 80px;">No</th>
                                <th class="text-nowrap">Workshop</th>
                                <th class="text-nowrap">Category</th>
                                <th class="text-nowrap">Pricing</th>
                                <th class="text-nowrap">Benefits</th>
                                <th class="text-nowrap">Status</th>
                                <th class="text-end text-nowrap">Order</th>
                                <th class="text-end text-nowrap" style="width: 160px;">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($workshops as $index => $workshop)
                                <tr id="workshop-row-{{ $workshop->id }}">
                                    <td class="text-muted">
                                        {{ $workshops->firstItem() + $index }}
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ $workshop->title }}
                                        </div>

                                        <div class="small text-muted mt-1">
                                            <code>{{ $workshop->slug }}</code>
                                        </div>

                                        @if ($workshop->badge)
                                            <div class="mt-2">
                                                <span class="badge rounded-pill bg-light text-dark border">
                                                    {{ $workshop->badge }}
                                                </span>
                                            </div>
                                        @endif
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ $workshop->category ?: '-' }}
                                        </div>
                                        <div class="small text-muted mt-1">
                                            {{ $workshop->level ?: 'No level set' }}
                                        </div>
                                    </td>

                                    <td class="text-nowrap">
                                        <div class="fw-semibold text-dark">
                                            Rp {{ number_format($workshop->price, 0, ',', '.') }}
                                        </div>

                                        @if ($workshop->old_price)
                                            <div class="text-danger small mt-1 text-decoration-line-through">
                                                Rp {{ number_format($workshop->old_price, 0, ',', '.') }}
                                            </div>
                                        @else
                                            <div class="small text-muted mt-1">
                                                Regular price
                                            </div>
                                        @endif
                                    </td>

                                    <td class="text-nowrap">
                                        <span class="badge rounded-pill bg-light text-dark border">
                                            {{ $workshop->benefits_count }} Benefits
                                        </span>
                                    </td>

                                    <td class="text-nowrap">
                                        @if ($workshop->is_active)
                                            <span class="badge rounded-pill bg-success-subtle text-success-emphasis border border-success-subtle">
                                                Active
                                            </span>
                                        @else
                                            <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle">
                                                Inactive
                                            </span>
                                        @endif
                                    </td>

                                    <td class="text-end text-nowrap">
                                        {{ $workshop->sort_order }}
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
                                                        href="{{ route('academic.workshops.show', $workshop) }}"
                                                        class="dropdown-item"
                                                    >
                                                        <i class="bi bi-eye me-2"></i>View Detail
                                                    </a>
                                                </li>

                                                <li>
                                                    <a
                                                        href="{{ route('academic.workshops.edit', $workshop) }}"
                                                        class="dropdown-item"
                                                    >
                                                        <i class="bi bi-pencil-square me-2"></i>Edit Workshop
                                                    </a>
                                                </li>

                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>

                                                <li>
                                                    <button
                                                        type="button"
                                                        class="dropdown-item text-danger delete-workshop-btn"
                                                        data-url="{{ route('academic.workshops.destroy', $workshop) }}"
                                                        data-title="{{ $workshop->title }}"
                                                    >
                                                        <i class="bi bi-trash me-2"></i>Delete
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

                @if ($workshops->hasPages())
                    <div class="mt-3 d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                        <div class="small text-muted">
                            Showing
                            <strong>{{ $workshops->firstItem() }}</strong>
                            -
                            <strong>{{ $workshops->lastItem() }}</strong>
                            of
                            <strong>{{ $workshops->total() }}</strong>
                            workshops
                        </div>

                        <div>
                            {{ $workshops->links() }}
                        </div>
                    </div>
                @endif
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-easel2"></i>
                    </div>

                    <h5 class="empty-state-title">No workshops found</h5>
                    <p class="empty-state-text mb-0">
                        Belum ada workshop yang sesuai dengan filter saat ini. Tambahkan workshop baru atau ubah filter pencarian.
                    </p>

                    <div class="mt-3">
                        <a href="{{ route('academic.workshops.create') }}" class="btn btn-primary btn-modern">
                            <i class="bi bi-plus-lg me-2"></i>Add Workshop
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Delete Modal --}}
<div class="modal fade" id="deleteWorkshopModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold mb-1">Delete Workshop</h5>
                    <div class="small text-muted">
                        This action will remove selected workshop from the system.
                    </div>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="alert alert-danger mb-0">
                    <div class="d-flex gap-2 align-items-start">
                        <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                        <div>
                            <div class="fw-semibold">Delete this workshop?</div>
                            <div class="small mt-1">
                                Are you sure you want to delete
                                <strong id="deleteWorkshopTitle"></strong>?
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-2"></i>Cancel
                </button>

                <button type="button" class="btn btn-danger btn-modern" id="confirmDeleteWorkshopBtn">
                    <span class="default-delete-text">
                        <i class="bi bi-trash me-2"></i>Delete
                    </span>
                    <span class="loading-delete-text d-none">
                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        Deleting...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalElement = document.getElementById('deleteWorkshopModal');
    const deleteWorkshopTitle = document.getElementById('deleteWorkshopTitle');
    const confirmDeleteWorkshopBtn = document.getElementById('confirmDeleteWorkshopBtn');
    const toastContainer = document.getElementById('toastContainer');

    if (!modalElement || !confirmDeleteWorkshopBtn || typeof bootstrap === 'undefined') {
        return;
    }

    const deleteModal = new bootstrap.Modal(modalElement);

    let selectedDeleteUrl = null;
    let selectedDeleteTrigger = null;
    let reloadTimeout = null;

    function showToast(message, type = 'success') {
        if (!toastContainer) return;

        const toastId = 'toast-' + Date.now();

        const bgClass = {
            success: 'bg-success',
            danger: 'bg-danger',
            warning: 'bg-warning text-dark',
            info: 'bg-info text-dark'
        }[type] || 'bg-success';

        const closeBtnClass = type === 'warning' || type === 'info'
            ? 'btn-close'
            : 'btn-close btn-close-white';

        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="${closeBtnClass} me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

        toastContainer.insertAdjacentHTML('beforeend', toastHtml);

        const toastEl = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastEl, { delay: 3000 });

        toast.show();

        toastEl.addEventListener('hidden.bs.toast', function () {
            toastEl.remove();
        });
    }

    function setDeleteLoading(isLoading) {
        confirmDeleteWorkshopBtn.disabled = isLoading;
        confirmDeleteWorkshopBtn.querySelector('.default-delete-text').classList.toggle('d-none', isLoading);
        confirmDeleteWorkshopBtn.querySelector('.loading-delete-text').classList.toggle('d-none', !isLoading);

        if (selectedDeleteTrigger) {
            selectedDeleteTrigger.disabled = isLoading;
        }
    }

    function resetDeleteState() {
        selectedDeleteUrl = null;
        selectedDeleteTrigger = null;
        deleteWorkshopTitle.textContent = '';
        setDeleteLoading(false);
    }

    function scheduleReload() {
        if (reloadTimeout) {
            clearTimeout(reloadTimeout);
        }

        reloadTimeout = setTimeout(function () {
            window.location.reload();
        }, 1500);
    }

    async function parseResponse(response) {
        const contentType = response.headers.get('content-type') || '';

        if (contentType.includes('application/json')) {
            return await response.json();
        }

        const text = await response.text();

        return {
            success: false,
            message: text || 'Unexpected server response.',
        };
    }

    document.querySelectorAll('.delete-workshop-btn').forEach((button) => {
        button.addEventListener('click', function () {
            selectedDeleteUrl = this.dataset.url;
            selectedDeleteTrigger = this;

            deleteWorkshopTitle.textContent = this.dataset.title || '-';
            setDeleteLoading(false);
            deleteModal.show();
        });
    });

    modalElement.addEventListener('hidden.bs.modal', function () {
        resetDeleteState();
    });

    confirmDeleteWorkshopBtn.addEventListener('click', async function () {
        if (!selectedDeleteUrl) return;

        setDeleteLoading(true);

        try {
            const response = await fetch(selectedDeleteUrl, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            const result = await parseResponse(response);

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to delete workshop.');
            }

            deleteModal.hide();
            showToast(result.message || 'Workshop deleted successfully.', 'danger');
            scheduleReload();
        } catch (error) {
            showToast(error.message || 'Failed to delete workshop.', 'danger');
            setDeleteLoading(false);
        }
    });
});
</script>
@endpush