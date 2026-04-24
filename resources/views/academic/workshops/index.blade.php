@extends('layouts.app-dashboard')

@section('title', 'Workshops')

@section('content')
@php
    $workshopCollection = $workshops->getCollection();
    $summaryActive = $workshopCollection->where('is_active', true)->count();
    $summaryInactive = $workshopCollection->where('is_active', false)->count();
    $summaryDiscounted = $workshopCollection->filter(fn ($item) => !is_null($item->old_price) && $item->old_price > $item->price)->count();
@endphp

<div class="container-fluid px-4 py-4 workshops-index-page">
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Academic</div>
                <h1 class="page-title mb-2">Workshop List</h1>
                <p class="page-subtitle mb-0">
                    Kelola workshop yang tampil di landing page public beserta harga, preview video, benefit, dan status publikasinya.
                </p>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('academic.workshops.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i> Add Workshop
                </a>
            </div>
        </div>
    </div>

    <div
        id="toastContainer"
        class="toast-container position-fixed top-0 end-0 p-3"
        style="z-index: 1090;"
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
                    Jumlah seluruh workshop berdasarkan hasil filter saat ini.
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
                    Workshop yang sedang aktif dan siap ditampilkan di halaman public.
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
                    Workshop yang disimpan tetapi belum ditampilkan ke user.
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
                    Workshop yang punya old price dan sedang memakai harga promo.
                </div>
            </div>
        </div>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Filter Workshops</h5>
                <p class="content-card-subtitle mb-0">
                    Gunakan pencarian dan filter berikut untuk menelusuri workshop berdasarkan judul, kategori, dan status.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            <form method="GET" action="{{ route('academic.workshops.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-xl-4 col-lg-4 col-md-6">
                        <label class="form-label">Search</label>
                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            value="{{ $filters['search'] ?? '' }}"
                            placeholder="Search title, slug, badge, category..."
                        >
                    </div>

                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            <option value="">All Categories</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category }}" @selected(($filters['category'] ?? '') === $category)>
                                    {{ $category }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-2 col-lg-4 col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="1" @selected((string) ($filters['status'] ?? '') === '1')>Active</option>
                            <option value="0" @selected((string) ($filters['status'] ?? '') === '0')>Inactive</option>
                        </select>
                    </div>

                    <div class="col-xl-1 col-md-6">
                        <label class="form-label">Rows</label>
                        <select name="per_page" class="form-select">
                            @foreach ([10, 25, 50, 100] as $size)
                                <option value="{{ $size }}" @selected(($filters['per_page'] ?? 10) == $size)>{{ $size }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-12">
                        <div class="filter-action-row d-flex gap-2 flex-wrap justify-content-lg-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-funnel me-1"></i> Apply Filter
                            </button>

                            <a href="{{ route('academic.workshops.index') }}" class="btn btn-light border">
                                <i class="bi bi-arrow-clockwise me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="content-card">
        <div class="content-card-header d-flex justify-content-between align-items-center gap-3 flex-wrap">
            <div>
                <h5 class="content-card-title mb-1">Workshop Data</h5>
                <p class="content-card-subtitle mb-0">
                    Setiap baris menampilkan informasi inti workshop beserta status publikasi dan jumlah benefit.
                </p>
            </div>

            <div class="table-meta-info">
                Total: <strong>{{ $workshops->total() }}</strong> workshops
            </div>
        </div>

        <div class="content-card-body p-0">
            <div class="table-responsive">
                <table class="table table-modern align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4" style="width: 60px;">#</th>
                            <th>Workshop</th>
                            <th>Category</th>
                            <th>Pricing</th>
                            <th>Benefits</th>
                            <th>Status</th>
                            <th>Order</th>
                            <th class="text-center pe-4" style="width: 170px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($workshops as $index => $workshop)
                            <tr id="workshop-row-{{ $workshop->id }}">
                                <td class="ps-4">{{ $workshops->firstItem() + $index }}</td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $workshop->title }}</div>
                                    <div class="text-muted small mt-1">{{ $workshop->slug }}</div>
                                    @if ($workshop->badge)
                                        <div class="mt-2">
                                            <span class="badge rounded-pill bg-light text-dark border">{{ $workshop->badge }}</span>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $workshop->category ?: '-' }}</div>
                                    <div class="text-muted small mt-1">{{ $workshop->level ?: '-' }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold">Rp {{ number_format($workshop->price, 0, ',', '.') }}</div>
                                    @if ($workshop->old_price)
                                        <div class="text-danger small mt-1 text-decoration-line-through">
                                            Rp {{ number_format($workshop->old_price, 0, ',', '.') }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge rounded-pill bg-light text-dark border">
                                        {{ $workshop->benefits_count }} Benefits
                                    </span>
                                </td>
                                <td>
                                    @if ($workshop->is_active)
                                        <span class="badge rounded-pill bg-success-subtle text-success-emphasis">
                                            Active
                                        </span>
                                    @else
                                        <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis">
                                            Inactive
                                        </span>
                                    @endif
                                </td>
                                <td>{{ $workshop->sort_order }}</td>
                                <td class="text-center pe-4">
                                    <div class="d-inline-flex gap-2">
                                        <a
                                            href="{{ route('academic.workshops.show', $workshop) }}"
                                            class="btn btn-sm btn-outline-secondary"
                                            title="View"
                                        >
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        <a
                                            href="{{ route('academic.workshops.edit', $workshop) }}"
                                            class="btn btn-sm btn-outline-primary"
                                            title="Edit"
                                        >
                                            <i class="bi bi-pencil-square"></i>
                                        </a>

                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-danger delete-workshop-btn"
                                            data-url="{{ route('academic.workshops.destroy', $workshop) }}"
                                            data-title="{{ $workshop->title }}"
                                            title="Delete"
                                        >
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="empty-state-box mx-4 my-3">
                                        <div class="empty-state-icon">
                                            <i class="bi bi-easel2"></i>
                                        </div>
                                        <div class="empty-state-title">No workshops found</div>
                                        <div class="empty-state-subtitle">
                                            Belum ada workshop yang sesuai dengan filter saat ini.
                                        </div>
                                        <div class="mt-3">
                                            <a href="{{ route('academic.workshops.create') }}" class="btn btn-primary">
                                                <i class="bi bi-plus-circle me-1"></i> Create Workshop
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($workshops->hasPages())
            <div class="content-card-footer d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div class="table-meta-info">
                    Menampilkan <strong>{{ $workshops->firstItem() }}</strong> - <strong>{{ $workshops->lastItem() }}</strong>
                    dari <strong>{{ $workshops->total() }}</strong> workshops
                </div>
                <div>
                    {{ $workshops->links() }}
                </div>
            </div>
        @endif
    </div>
</div>

<div class="modal fade" id="deleteWorkshopModal" tabindex="-1" aria-labelledby="deleteWorkshopModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold" id="deleteWorkshopModalLabel">Delete Workshop</h5>
                    <p class="text-muted small mb-0 mt-1">Tindakan ini tidak bisa dibatalkan.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pt-3">
                <div class="delete-workshop-modal-box">
                    <div class="delete-workshop-modal-icon">
                        <i class="bi bi-trash3"></i>
                    </div>
                    <div>
                        <div class="fw-semibold text-dark mb-1">Yakin mau hapus workshop ini?</div>
                        <div class="text-muted small">
                            Workshop <span class="fw-semibold text-dark" id="deleteWorkshopTitle">-</span> akan dihapus permanen.
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                    Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteWorkshopBtn">
                    <i class="bi bi-trash me-1"></i> Delete Workshop
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .workshops-index-page .table-meta-info {
        font-size: .88rem;
        color: #6b7280;
    }

    .workshops-index-page .empty-state-box {
        padding: 28px 20px;
        border-radius: 18px;
        border: 1px dashed #d7dce3;
        text-align: center;
        background: #fcfcfd;
    }

    .workshops-index-page .empty-state-icon {
        width: 58px;
        height: 58px;
        margin: 0 auto 12px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #eee7fb;
        color: #5B3E8E;
        font-size: 1.4rem;
    }

    .workshops-index-page .empty-state-title {
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 4px;
    }

    .workshops-index-page .empty-state-subtitle {
        font-size: .85rem;
        color: #6b7280;
    }

    .workshops-index-page .content-card-footer {
        padding: 16px 20px;
        border-top: 1px solid #eef2f7;
        background: #fff;
    }

    .workshops-index-page .toast {
        min-width: 280px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    }

    .delete-workshop-modal-box {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        padding: 14px;
        border-radius: 16px;
        background: #fff5f5;
        border: 1px solid #ffd9d9;
    }

    .delete-workshop-modal-icon {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #fee2e2;
        color: #dc2626;
        font-size: 1.15rem;
        flex-shrink: 0;
    }
</style>
@endpush

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

        const closeBtnClass = (type === 'warning' || type === 'info')
            ? 'btn-close me-2 m-auto'
            : 'btn-close btn-close-white me-2 m-auto';

        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="${closeBtnClass}" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

        toastContainer.insertAdjacentHTML('beforeend', toastHtml);

        const toastEl = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastEl, { delay: 1500 });

        toast.show();

        toastEl.addEventListener('hidden.bs.toast', function () {
            toastEl.remove();
        });
    }

    function resetDeleteState() {
        selectedDeleteUrl = null;
        selectedDeleteTrigger = null;
        confirmDeleteWorkshopBtn.disabled = false;
        confirmDeleteWorkshopBtn.innerHTML = '<i class="bi bi-trash me-1"></i> Delete Workshop';
    }

    function scheduleReload() {
        if (reloadTimeout) {
            clearTimeout(reloadTimeout);
        }

        reloadTimeout = setTimeout(function () {
            window.location.reload();
        }, 1200);
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

            deleteWorkshopTitle.textContent = this.dataset.title || 'this workshop';
            deleteModal.show();
        });
    });

    modalElement.addEventListener('hidden.bs.modal', function () {
        resetDeleteState();
    });

    confirmDeleteWorkshopBtn.addEventListener('click', async function () {
        if (!selectedDeleteUrl) return;

        confirmDeleteWorkshopBtn.disabled = true;
        confirmDeleteWorkshopBtn.innerHTML =
            '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Deleting...';

        if (selectedDeleteTrigger) {
            selectedDeleteTrigger.disabled = true;
        }

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
            showToast(result.message || 'Workshop deleted successfully.', 'success');
            scheduleReload();
        } catch (error) {
            showToast(error.message || 'Failed to delete workshop.', 'danger');

            confirmDeleteWorkshopBtn.disabled = false;
            confirmDeleteWorkshopBtn.innerHTML = '<i class="bi bi-trash me-1"></i> Delete Workshop';

            if (selectedDeleteTrigger) {
                selectedDeleteTrigger.disabled = false;
            }
        }
    });
});
</script>
@endpush