@extends('layouts.app-dashboard')

@section('title', 'Master ATK')

@section('content')
@php
    $activeBadgeClass = function ($isActive) {
        return $isActive
            ? 'bg-success-subtle text-success-emphasis border border-success-subtle'
            : 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle';
    };

    $stockBadgeClass = function ($currentStock, $minimumStock) {
        return $currentStock <= $minimumStock
            ? 'bg-danger-subtle text-danger-emphasis border border-danger-subtle'
            : 'bg-success-subtle text-success-emphasis border border-success-subtle';
    };
@endphp

<div class="container-fluid px-4 py-4">
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Operations</div>
                <h1 class="page-title mb-2">Master ATK</h1>
                <p class="page-subtitle mb-0">
                    Manage stationery master data, stock availability, minimum stock, item location, and active status.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-light btn-modern" data-bs-toggle="modal" data-bs-target="#createItemModal">
                    <i class="bi bi-plus-lg me-2"></i>Add Item
                </button>
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

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <div class="fw-semibold mb-1">There are invalid inputs:</div>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-box-seam"></i>
                    </div>

                    <div>
                        <div class="stat-title">Total Items</div>
                        <div class="stat-value">{{ number_format((int) ($stats['total_items'] ?? 0)) }}</div>
                    </div>
                </div>

                <div class="stat-description">All registered ATK items.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-check-circle"></i>
                    </div>

                    <div>
                        <div class="stat-title">Active Items</div>
                        <div class="stat-value">{{ number_format((int) ($stats['active_items'] ?? 0)) }}</div>
                    </div>
                </div>

                <div class="stat-description">Items currently active for use.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>

                    <div>
                        <div class="stat-title">Low Stock</div>
                        <div class="stat-value">{{ number_format((int) ($stats['low_stock'] ?? 0)) }}</div>
                    </div>
                </div>

                <div class="stat-description">Items below minimum stock.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-stack"></i>
                    </div>

                    <div>
                        <div class="stat-title">Total Stock</div>
                        <div class="stat-value">{{ number_format((int) ($stats['total_stock'] ?? 0)) }}</div>
                    </div>
                </div>

                <div class="stat-description">Total stock across all items.</div>
            </div>
        </div>
    </div>

    <div class="content-card">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">ATK Item List</h5>
                <p class="content-card-subtitle mb-0">
                    Review stationery item code, unit, current stock, minimum stock, status, and storage location.
                </p>
            </div>

            <form method="GET" action="{{ route('inventory.atk-items.index') }}" class="d-flex align-items-center gap-2 flex-wrap">
                <label for="search" class="form-label mb-0 small text-muted">Search</label>

                <input
                    type="text"
                    name="search"
                    id="search"
                    class="form-control form-control-sm"
                    style="width: 260px;"
                    placeholder="Name, code, or unit..."
                    value="{{ request('search') }}"
                >

                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-search me-1"></i>Filter
                </button>

                @if(request()->filled('search'))
                    <a href="{{ route('inventory.atk-items.index') }}" class="btn btn-sm btn-outline-secondary">
                        Reset
                    </a>
                @endif
            </form>
        </div>

        <div class="content-card-body">
            @if($items->count())
                <div class="table-responsive dropdown-safe-table">
                    <table class="table table-hover align-middle admin-table mb-0">
                        <thead>
                            <tr>
                                <th class="text-nowrap" style="width: 80px;">No</th>
                                <th class="text-nowrap">Item</th>
                                <th class="text-nowrap">Code</th>
                                <th class="text-nowrap">Unit</th>
                                <th class="text-end text-nowrap">Stock</th>
                                <th class="text-end text-nowrap">Min. Stock</th>
                                <th class="text-nowrap">Status</th>
                                <th class="text-nowrap">Location</th>
                                <th class="text-end text-nowrap" style="width: 160px;">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($items as $item)
                                @php
                                    $currentStock = (int) ($item->stock?->current_stock ?? 0);
                                    $minimumStock = (int) ($item->minimum_stock ?? 0);
                                    $isLowStock = $currentStock <= $minimumStock;
                                @endphp

                                <tr>
                                    <td class="text-muted">
                                        {{ ($items->currentPage() - 1) * $items->perPage() + $loop->iteration }}
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark">{{ $item->name }}</div>
                                        <div class="small text-muted">
                                            {{ $item->description ?: 'No description' }}
                                        </div>
                                    </td>

                                    <td class="text-nowrap">
                                        <code>{{ $item->code ?: '-' }}</code>
                                    </td>

                                    <td class="text-nowrap">
                                        {{ $item->unit ?: '-' }}
                                    </td>

                                    <td class="text-end text-nowrap">
                                        <span class="badge rounded-pill {{ $stockBadgeClass($currentStock, $minimumStock) }}">
                                            {{ number_format($currentStock) }}
                                        </span>
                                    </td>

                                    <td class="text-end text-nowrap">
                                        {{ number_format($minimumStock) }}
                                    </td>

                                    <td class="text-nowrap">
                                        <div class="d-flex flex-wrap gap-1">
                                            <span class="badge rounded-pill {{ $activeBadgeClass($item->is_active) }}">
                                                {{ $item->is_active ? 'Active' : 'Inactive' }}
                                            </span>

                                            @if($isLowStock)
                                                <span class="badge rounded-pill bg-danger-subtle text-danger-emphasis border border-danger-subtle">
                                                    Low Stock
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    <td class="text-nowrap text-muted">
                                        {{ $item->stock?->location ?: '-' }}
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
                                                        data-bs-target="#editItemModal{{ $item->id }}"
                                                    >
                                                        <i class="bi bi-pencil-square me-2"></i>Edit Item
                                                    </button>
                                                </li>

                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>

                                                <li>
                                                    <form
                                                        action="{{ route('inventory.atk-items.destroy', $item) }}"
                                                        method="POST"
                                                        class="m-0"
                                                        onsubmit="return confirm('Delete this item?')"
                                                    >
                                                        @csrf
                                                        @method('DELETE')

                                                        <button type="submit" class="dropdown-item text-danger">
                                                            <i class="bi bi-trash me-2"></i>Delete
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>

                                {{-- Edit Modal --}}
                                <div class="modal fade" id="editItemModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg modal-dialog-centered">
                                        <div class="modal-content border-0 shadow">
                                            <form method="POST" action="{{ route('inventory.atk-items.update', $item) }}">
                                                @csrf
                                                @method('PUT')

                                                <div class="modal-header">
                                                    <div>
                                                        <h5 class="modal-title fw-bold mb-1">Edit ATK Item</h5>
                                                        <div class="small text-muted">
                                                            Update item identity, minimum stock, current stock, and location.
                                                        </div>
                                                    </div>

                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>

                                                <div class="modal-body">
                                                    <div class="content-card mb-3">
                                                        <div class="content-card-header">
                                                            <div>
                                                                <h5 class="content-card-title mb-1">Item Information</h5>
                                                                <p class="content-card-subtitle mb-0">
                                                                    Basic identity for this stationery item.
                                                                </p>
                                                            </div>
                                                        </div>

                                                        <div class="content-card-body">
                                                            <div class="row g-3">
                                                                <div class="col-md-6">
                                                                    <label class="form-label">
                                                                        Name <span class="text-danger">*</span>
                                                                    </label>
                                                                    <input
                                                                        type="text"
                                                                        name="name"
                                                                        class="form-control"
                                                                        value="{{ old('name', $item->name) }}"
                                                                        required
                                                                    >
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <label class="form-label">Code</label>
                                                                    <input
                                                                        type="text"
                                                                        name="code"
                                                                        class="form-control"
                                                                        value="{{ old('code', $item->code) }}"
                                                                    >
                                                                </div>

                                                                <div class="col-md-4">
                                                                    <label class="form-label">Unit</label>
                                                                    <input
                                                                        type="text"
                                                                        name="unit"
                                                                        class="form-control"
                                                                        value="{{ old('unit', $item->unit) }}"
                                                                        placeholder="pcs, box, pack..."
                                                                    >
                                                                </div>

                                                                <div class="col-md-4">
                                                                    <label class="form-label">
                                                                        Minimum Stock <span class="text-danger">*</span>
                                                                    </label>
                                                                    <input
                                                                        type="number"
                                                                        name="minimum_stock"
                                                                        class="form-control"
                                                                        min="0"
                                                                        value="{{ old('minimum_stock', $item->minimum_stock) }}"
                                                                        required
                                                                    >
                                                                </div>

                                                                <div class="col-md-4">
                                                                    <label class="form-label">
                                                                        Current Stock <span class="text-danger">*</span>
                                                                    </label>
                                                                    <input
                                                                        type="number"
                                                                        name="current_stock"
                                                                        class="form-control"
                                                                        min="0"
                                                                        value="{{ old('current_stock', $item->stock?->current_stock ?? 0) }}"
                                                                        required
                                                                    >
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <label class="form-label">Location</label>
                                                                    <input
                                                                        type="text"
                                                                        name="location"
                                                                        class="form-control"
                                                                        value="{{ old('location', $item->stock?->location) }}"
                                                                        placeholder="Storage room, cabinet, shelf..."
                                                                    >
                                                                </div>

                                                                <div class="col-md-6">
                                                                    <label class="form-label d-block">Status</label>
                                                                    <div class="form-check form-switch mt-2">
                                                                        <input
                                                                            class="form-check-input"
                                                                            type="checkbox"
                                                                            name="is_active"
                                                                            value="1"
                                                                            {{ old('is_active', $item->is_active) ? 'checked' : '' }}
                                                                        >
                                                                        <label class="form-check-label">Active</label>
                                                                    </div>
                                                                </div>

                                                                <div class="col-12">
                                                                    <label class="form-label">Description</label>
                                                                    <textarea
                                                                        name="description"
                                                                        class="form-control"
                                                                        rows="3"
                                                                        placeholder="Optional item description"
                                                                    >{{ old('description', $item->description) }}</textarea>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                                                        <i class="bi bi-x-circle me-2"></i>Cancel
                                                    </button>
                                                    <button type="submit" class="btn btn-primary btn-modern">
                                                        <i class="bi bi-check-circle me-2"></i>Save Changes
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($items->hasPages())
                    <div class="mt-3">
                        {{ $items->withQueryString()->links() }}
                    </div>
                @endif
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-box-seam"></i>
                    </div>

                    <h5 class="empty-state-title">No ATK items found</h5>
                    <p class="empty-state-text mb-0">
                        Belum ada item ATK yang tercatat. Tambahkan item baru untuk mulai mengelola stok ATK.
                    </p>

                    <div class="mt-3">
                        <button type="button" class="btn btn-primary btn-modern" data-bs-toggle="modal" data-bs-target="#createItemModal">
                            <i class="bi bi-plus-lg me-2"></i>Add Item
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Create Modal --}}
<div class="modal fade" id="createItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('inventory.atk-items.store') }}">
                @csrf

                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold mb-1">Add ATK Item</h5>
                        <div class="small text-muted">
                            Create new stationery item and define initial stock availability.
                        </div>
                    </div>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="content-card mb-3">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Item Information</h5>
                                <p class="content-card-subtitle mb-0">
                                    Basic identity and stock setup for this stationery item.
                                </p>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">
                                        Name <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        name="name"
                                        class="form-control"
                                        value="{{ old('name') }}"
                                        required
                                    >
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Code</label>
                                    <input
                                        type="text"
                                        name="code"
                                        class="form-control"
                                        value="{{ old('code') }}"
                                        placeholder="Optional item code"
                                    >
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Unit</label>
                                    <input
                                        type="text"
                                        name="unit"
                                        class="form-control"
                                        value="{{ old('unit') }}"
                                        placeholder="pcs, box, pack..."
                                    >
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">
                                        Minimum Stock <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        name="minimum_stock"
                                        class="form-control"
                                        min="0"
                                        value="{{ old('minimum_stock', 0) }}"
                                        required
                                    >
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">
                                        Current Stock <span class="text-danger">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        name="current_stock"
                                        class="form-control"
                                        min="0"
                                        value="{{ old('current_stock', 0) }}"
                                        required
                                    >
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Location</label>
                                    <input
                                        type="text"
                                        name="location"
                                        class="form-control"
                                        value="{{ old('location') }}"
                                        placeholder="Storage room, cabinet, shelf..."
                                    >
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label d-block">Status</label>
                                    <div class="form-check form-switch mt-2">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            name="is_active"
                                            value="1"
                                            {{ old('is_active', true) ? 'checked' : '' }}
                                        >
                                        <label class="form-check-label">Active</label>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Description</label>
                                    <textarea
                                        name="description"
                                        class="form-control"
                                        rows="3"
                                        placeholder="Optional item description"
                                    >{{ old('description') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-primary btn-modern">
                        <i class="bi bi-check-circle me-2"></i>Save Item
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection