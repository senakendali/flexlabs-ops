@extends('layouts.app-dashboard')

@section('title', 'Master ATK')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="mb-1">Master ATK</h4>
            <small class="text-muted">Manage stationery master data and stock availability</small>
        </div>

        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createItemModal">
            <i class="bi bi-plus-lg me-1"></i> Add Item
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
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
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Total Items</div>
                    <div class="fs-3 fw-bold">{{ $stats['total_items'] }}</div>
                    <div class="small text-muted mt-2">All registered ATK items.</div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Active Items</div>
                    <div class="fs-3 fw-bold">{{ $stats['active_items'] }}</div>
                    <div class="small text-muted mt-2">Items currently active for use.</div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Low Stock</div>
                    <div class="fs-3 fw-bold text-danger">{{ $stats['low_stock'] }}</div>
                    <div class="small text-muted mt-2">Items below minimum stock.</div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Total Stock</div>
                    <div class="fs-3 fw-bold">{{ $stats['total_stock'] }}</div>
                    <div class="small text-muted mt-2">Total stock across all items.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body border-bottom">
            <form method="GET" class="d-flex flex-column flex-md-row align-items-md-center gap-2">
                <div class="d-flex align-items-center gap-2 flex-wrap">
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

                    <button type="submit" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-search me-1"></i> Filter
                    </button>

                    @if(request()->filled('search'))
                        <a href="{{ route('inventory.atk-items.index') }}" class="btn btn-sm btn-outline-secondary">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 80px;">No</th>
                        <th>Name</th>
                        <th style="width: 140px;">Code</th>
                        <th style="width: 100px;">Unit</th>
                        <th style="width: 100px;">Stock</th>
                        <th style="width: 110px;">Min. Stock</th>
                        <th style="width: 150px;">Status</th>
                        <th style="width: 160px;">Location</th>
                        <th style="width: 170px;" class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        @php
                            $currentStock = $item->stock?->current_stock ?? 0;
                            $isLowStock = $currentStock <= $item->minimum_stock;
                        @endphp
                        <tr>
                            <td>
                                {{ ($items->currentPage() - 1) * $items->perPage() + $loop->iteration }}
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $item->name }}</div>
                                <div class="text-muted small">
                                    {{ $item->description ?: '-' }}
                                </div>
                            </td>
                            <td>
                                <code>{{ $item->code ?: '-' }}</code>
                            </td>
                            <td>{{ $item->unit ?: '-' }}</td>
                            <td class="fw-semibold">{{ $currentStock }}</td>
                            <td>{{ $item->minimum_stock }}</td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    @if ($item->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif

                                    @if ($isLowStock)
                                        <span class="badge bg-danger">Low Stock</span>
                                    @endif
                                </div>
                            </td>
                            <td class="text-muted">{{ $item->stock?->location ?: '-' }}</td>
                            <td class="text-center">
                                <div class="d-inline-flex gap-2">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editItemModal{{ $item->id }}"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    <form action="{{ route('inventory.atk-items.destroy', $item) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Delete this item?')"
                                        >
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <div class="modal fade" id="editItemModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content border-0 shadow">
                                    <form method="POST" action="{{ route('inventory.atk-items.update', $item) }}">
                                        @csrf
                                        @method('PUT')

                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit ATK Item</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">Name</label>
                                                    <input type="text" name="name" class="form-control" value="{{ $item->name }}" required>
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label">Code</label>
                                                    <input type="text" name="code" class="form-control" value="{{ $item->code }}">
                                                </div>

                                                <div class="col-md-4">
                                                    <label class="form-label">Unit</label>
                                                    <input type="text" name="unit" class="form-control" value="{{ $item->unit }}">
                                                </div>

                                                <div class="col-md-4">
                                                    <label class="form-label">Minimum Stock</label>
                                                    <input type="number" name="minimum_stock" class="form-control" min="0" value="{{ $item->minimum_stock }}" required>
                                                </div>

                                                <div class="col-md-4">
                                                    <label class="form-label">Current Stock</label>
                                                    <input type="number" name="current_stock" class="form-control" min="0" value="{{ $item->stock?->current_stock ?? 0 }}" required>
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label">Location</label>
                                                    <input type="text" name="location" class="form-control" value="{{ $item->stock?->location }}">
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label d-block">Status</label>
                                                    <div class="form-check form-switch mt-2">
                                                        <input
                                                            class="form-check-input"
                                                            type="checkbox"
                                                            name="is_active"
                                                            value="1"
                                                            {{ $item->is_active ? 'checked' : '' }}
                                                        >
                                                        <label class="form-check-label">Active</label>
                                                    </div>
                                                </div>

                                                <div class="col-12">
                                                    <label class="form-label">Description</label>
                                                    <textarea name="description" class="form-control" rows="3">{{ $item->description }}</textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                No ATK items found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($items->hasPages())
            <div class="card-footer bg-white">
                {{ $items->links() }}
            </div>
        @endif
    </div>
</div>

<div class="modal fade" id="createItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('inventory.atk-items.store') }}">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">Add ATK Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Code</label>
                            <input type="text" name="code" class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Unit</label>
                            <input type="text" name="unit" class="form-control" placeholder="pcs / box / rim">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Minimum Stock</label>
                            <input type="number" name="minimum_stock" class="form-control" min="0" value="0" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Current Stock</label>
                            <input type="number" name="current_stock" class="form-control" min="0" value="0" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" class="form-control" placeholder="Main Warehouse">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label d-block">Status</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                                <label class="form-check-label">Active</label>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Item</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection