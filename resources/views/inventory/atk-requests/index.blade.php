@extends('layouts.app-dashboard')

@section('title', 'ATK Request')

@section('content')
@php
    $statusBadgeClass = function ($status) {
        return match($status) {
            'pending' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
            'approved' => 'bg-success-subtle text-success-emphasis border border-success-subtle',
            'rejected' => 'bg-danger-subtle text-danger-emphasis border border-danger-subtle',
            'cancelled' => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
            default => 'bg-dark-subtle text-dark-emphasis border border-dark-subtle',
        };
    };

    $formatStatus = function ($status) {
        return ucfirst((string) $status);
    };
@endphp

<div class="container-fluid px-4 py-4">
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Operations</div>
                <h1 class="page-title mb-2">ATK Request</h1>
                <p class="page-subtitle mb-0">
                    Manage stationery requests, approval status, requested items, stock usage, and internal operational needs.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-light btn-modern" data-bs-toggle="modal" data-bs-target="#createRequestModal">
                    <i class="bi bi-plus-lg me-2"></i>Create Request
                </button>
            </div>
        </div>
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

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>

                    <div>
                        <div class="stat-title">Total Requests</div>
                        <div class="stat-value">{{ number_format((int) ($stats['total_requests'] ?? 0)) }}</div>
                    </div>
                </div>

                <div class="stat-description">All submitted ATK requests.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-hourglass-split"></i>
                    </div>

                    <div>
                        <div class="stat-title">Pending</div>
                        <div class="stat-value">{{ number_format((int) ($stats['pending_requests'] ?? 0)) }}</div>
                    </div>
                </div>

                <div class="stat-description">Requests waiting for approval.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-check-circle"></i>
                    </div>

                    <div>
                        <div class="stat-title">Approved</div>
                        <div class="stat-value">{{ number_format((int) ($stats['approved_requests'] ?? 0)) }}</div>
                    </div>
                </div>

                <div class="stat-description">Approved requests with processed stock.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-x-circle"></i>
                    </div>

                    <div>
                        <div class="stat-title">Rejected</div>
                        <div class="stat-value">{{ number_format((int) ($stats['rejected_requests'] ?? 0)) }}</div>
                    </div>
                </div>

                <div class="stat-description">Requests that were declined.</div>
            </div>
        </div>
    </div>

    <div class="content-card">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">ATK Request List</h5>
                <p class="content-card-subtitle mb-0">
                    Review request number, requester, request date, total requested quantity, and approval status.
                </p>
            </div>

            <form method="GET" action="{{ route('inventory.atk-requests.index') }}" class="d-flex align-items-center gap-2 flex-wrap">
                <label for="status" class="form-label mb-0 small text-muted">Status</label>

                <select
                    name="status"
                    id="status"
                    class="form-select form-select-sm"
                    style="width: 180px;"
                >
                    <option value="">All Status</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>

                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>

                @if(request()->filled('status'))
                    <a href="{{ route('inventory.atk-requests.index') }}" class="btn btn-sm btn-outline-secondary">
                        Reset
                    </a>
                @endif
            </form>
        </div>

        <div class="content-card-body">
            @if($requests->count())
                <div class="table-responsive dropdown-safe-table">
                    <table class="table table-hover align-middle admin-table mb-0">
                        <thead>
                            <tr>
                                <th class="text-nowrap" style="width: 80px;">No</th>
                                <th class="text-nowrap">Request No</th>
                                <th class="text-nowrap">Requester</th>
                                <th class="text-nowrap">Date</th>
                                <th class="text-end text-nowrap">Total Items</th>
                                <th class="text-nowrap">Status</th>
                                <th class="text-end text-nowrap" style="width: 160px;">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($requests as $requestData)
                                @php
                                    $canCancel = $requestData->status === 'pending'
                                        && ((int) $requestData->user_id === (int) auth()->id() || auth()->user()?->role === 'admin');

                                    $canApproveReject = $requestData->status === 'pending'
                                        && auth()->user()?->role === 'admin';
                                @endphp

                                <tr>
                                    <td class="text-muted">
                                        {{ ($requests->currentPage() - 1) * $requests->perPage() + $loop->iteration }}
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark">{{ $requestData->request_number }}</div>
                                        <div class="small text-muted">
                                            {{ \Illuminate\Support\Str::limit($requestData->notes ?: '-', 50) }}
                                        </div>
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ $requestData->requester->name ?? '-' }}
                                        </div>
                                        <div class="small text-muted">Requester</div>
                                    </td>

                                    <td class="text-nowrap">
                                        {{ optional($requestData->request_date)->format('d M Y') ?? '-' }}
                                    </td>

                                    <td class="text-end text-nowrap">
                                        <div class="fw-bold text-dark">
                                            {{ number_format((int) $requestData->items->sum('qty')) }}
                                        </div>
                                    </td>

                                    <td class="text-nowrap">
                                        <span class="badge rounded-pill {{ $statusBadgeClass($requestData->status) }}">
                                            {{ $formatStatus($requestData->status) }}
                                        </span>
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
                                                        data-bs-target="#detailRequestModal{{ $requestData->id }}"
                                                    >
                                                        <i class="bi bi-eye me-2"></i>View Detail
                                                    </button>
                                                </li>

                                                @if($canCancel || $canApproveReject)
                                                    <li>
                                                        <hr class="dropdown-divider">
                                                    </li>
                                                @endif

                                                @if($canCancel)
                                                    <li>
                                                        <form method="POST" action="{{ route('inventory.atk-requests.cancel', $requestData) }}" class="m-0">
                                                            @csrf
                                                            <button
                                                                type="submit"
                                                                class="dropdown-item text-secondary"
                                                                onclick="return confirm('Cancel this request?')"
                                                            >
                                                                <i class="bi bi-x-circle me-2"></i>Cancel Request
                                                            </button>
                                                        </form>
                                                    </li>
                                                @endif

                                                @if($canApproveReject)
                                                    <li>
                                                        <button
                                                            type="button"
                                                            class="dropdown-item text-danger"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#rejectModal{{ $requestData->id }}"
                                                        >
                                                            <i class="bi bi-x-octagon me-2"></i>Reject
                                                        </button>
                                                    </li>

                                                    <li>
                                                        <form method="POST" action="{{ route('inventory.atk-requests.approve', $requestData) }}" class="m-0">
                                                            @csrf
                                                            <button
                                                                type="submit"
                                                                class="dropdown-item text-success"
                                                                onclick="return confirm('Approve this request? Stock will be reduced automatically.')"
                                                            >
                                                                <i class="bi bi-check-circle me-2"></i>Approve
                                                            </button>
                                                        </form>
                                                    </li>
                                                @endif
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($requests->hasPages())
                    <div class="mt-3">
                        {{ $requests->withQueryString()->links() }}
                    </div>
                @endif
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>

                    <h5 class="empty-state-title">No ATK requests found</h5>
                    <p class="empty-state-text mb-0">
                        Belum ada request ATK yang tercatat. Buat request baru untuk mulai mengajukan kebutuhan operasional.
                    </p>

                    <div class="mt-3">
                        <button type="button" class="btn btn-primary btn-modern" data-bs-toggle="modal" data-bs-target="#createRequestModal">
                            <i class="bi bi-plus-lg me-2"></i>Create Request
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- DETAIL MODALS: TARUH DI LUAR TABLE --}}
@foreach ($requests as $requestData)
    <div class="modal fade" id="detailRequestModal{{ $requestData->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold mb-1">Request Detail</h5>
                        <div class="small text-muted">
                            {{ $requestData->request_number }}
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="content-card mb-3">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Request Information</h5>
                                <p class="content-card-subtitle mb-0">
                                    Review requester, approval status, request date, approval time, and total requested quantity.
                                </p>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <div class="small text-muted mb-1">Requester</div>
                                    <div class="fw-semibold text-dark">{{ $requestData->requester->name ?? '-' }}</div>
                                </div>

                                <div class="col-md-3">
                                    <div class="small text-muted mb-1">Request Date</div>
                                    <div class="fw-semibold text-dark">{{ optional($requestData->request_date)->format('d M Y') ?? '-' }}</div>
                                </div>

                                <div class="col-md-3">
                                    <div class="small text-muted mb-1">Status</div>
                                    <div>
                                        <span class="badge rounded-pill {{ $statusBadgeClass($requestData->status) }}">
                                            {{ $formatStatus($requestData->status) }}
                                        </span>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="small text-muted mb-1">Approved By</div>
                                    <div class="fw-semibold text-dark">{{ $requestData->approver->name ?? '-' }}</div>
                                </div>

                                <div class="col-md-6">
                                    <div class="small text-muted mb-1">Approval Time</div>
                                    <div class="fw-semibold text-dark">{{ optional($requestData->approved_at)->format('d M Y H:i') ?? '-' }}</div>
                                </div>

                                <div class="col-md-6">
                                    <div class="small text-muted mb-1">Total Requested Qty</div>
                                    <div class="fw-bold text-dark">{{ number_format((int) $requestData->items->sum('qty')) }}</div>
                                </div>

                                <div class="col-12">
                                    <div class="small text-muted mb-1">Request Notes</div>
                                    <div class="fw-semibold text-dark">{{ $requestData->notes ?: '-' }}</div>
                                </div>

                                @if($requestData->rejection_reason)
                                    <div class="col-12">
                                        <div class="small text-muted mb-1">Rejection Reason</div>
                                        <div class="fw-semibold text-danger">{{ $requestData->rejection_reason }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="content-card">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Requested Items</h5>
                                <p class="content-card-subtitle mb-0">
                                    Detail item ATK yang diajukan beserta quantity, unit, stock saat ini, dan catatan item.
                                </p>
                            </div>
                        </div>

                        <div class="content-card-body">
                            @if($requestData->items->count())
                                <div class="table-responsive dropdown-safe-table">
                                    <table class="table table-hover align-middle admin-table mb-0">
                                        <thead>
                                            <tr>
                                                <th class="text-nowrap" style="width: 70px;">No</th>
                                                <th class="text-nowrap">Item</th>
                                                <th class="text-end text-nowrap">Qty</th>
                                                <th class="text-nowrap">Unit</th>
                                                <th class="text-end text-nowrap">Current Stock</th>
                                                <th class="text-nowrap">Item Notes</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            @foreach($requestData->items as $detail)
                                                <tr>
                                                    <td class="text-muted">{{ $loop->iteration }}</td>

                                                    <td>
                                                        <div class="fw-semibold text-dark">
                                                            {{ $detail->item->name ?? '-' }}
                                                        </div>
                                                    </td>

                                                    <td class="text-end text-nowrap">
                                                        {{ number_format((int) $detail->qty) }}
                                                    </td>

                                                    <td class="text-nowrap">
                                                        {{ $detail->unit ?? '-' }}
                                                    </td>

                                                    <td class="text-end text-nowrap">
                                                        {{ number_format((int) ($detail->item->stock?->current_stock ?? 0)) }}
                                                    </td>

                                                    <td class="text-muted">
                                                        {{ $detail->notes ?: '-' }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="empty-state-box">
                                    <div class="empty-state-icon">
                                        <i class="bi bi-box-seam"></i>
                                    </div>

                                    <h5 class="empty-state-title">No request items found</h5>
                                    <p class="empty-state-text mb-0">
                                        Request ini belum memiliki item ATK.
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="modal-footer d-flex justify-content-between">
                    <div>
                        @if($requestData->status === 'pending' && ((int) $requestData->user_id === (int) auth()->id() || auth()->user()?->role === 'admin'))
                            <form method="POST" action="{{ route('inventory.atk-requests.cancel', $requestData) }}" class="d-inline">
                                @csrf
                                <button
                                    type="submit"
                                    class="btn btn-outline-secondary btn-modern"
                                    onclick="return confirm('Cancel this request?')"
                                >
                                    <i class="bi bi-x-circle me-2"></i>Cancel Request
                                </button>
                            </form>
                        @endif
                    </div>

                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-2"></i>Close
                        </button>

                        @if($requestData->status === 'pending' && auth()->user()?->role === 'admin')
                            <button
                                type="button"
                                class="btn btn-danger btn-modern"
                                data-bs-toggle="modal"
                                data-bs-target="#rejectModal{{ $requestData->id }}"
                            >
                                <i class="bi bi-x-octagon me-2"></i>Reject
                            </button>

                            <form method="POST" action="{{ route('inventory.atk-requests.approve', $requestData) }}" class="d-inline">
                                @csrf
                                <button
                                    type="submit"
                                    class="btn btn-success btn-modern"
                                    onclick="return confirm('Approve this request? Stock will be reduced automatically.')"
                                >
                                    <i class="bi bi-check-circle me-2"></i>Approve
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Reject Modal --}}
    <div class="modal fade" id="rejectModal{{ $requestData->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form method="POST" action="{{ route('inventory.atk-requests.reject', $requestData) }}">
                    @csrf

                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title fw-bold mb-1">Reject Request</h5>
                            <div class="small text-muted">
                                Add rejection reason for request {{ $requestData->request_number }}.
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="content-card">
                            <div class="content-card-header">
                                <div>
                                    <h5 class="content-card-title mb-1">Rejection Detail</h5>
                                    <p class="content-card-subtitle mb-0">
                                        Tulis alasan penolakan agar requester memahami kenapa request tidak disetujui.
                                    </p>
                                </div>
                            </div>

                            <div class="content-card-body">
                                <label class="form-label">
                                    Rejection Reason <span class="text-danger">*</span>
                                </label>
                                <textarea
                                    name="rejection_reason"
                                    class="form-control"
                                    rows="4"
                                    required
                                    placeholder="Write the reason for rejection..."
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-2"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-danger btn-modern">
                            <i class="bi bi-x-octagon me-2"></i>Reject Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

{{-- CREATE REQUEST MODAL --}}
<div class="modal fade" id="createRequestModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('inventory.atk-requests.store') }}">
                @csrf

                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold mb-1">Create ATK Request</h5>
                        <div class="small text-muted">
                            Create a new stationery request and add one or more ATK items.
                        </div>
                    </div>

                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="content-card mb-3">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Request Notes</h5>
                                <p class="content-card-subtitle mb-0">
                                    Tambahkan catatan kebutuhan atau konteks request jika diperlukan.
                                </p>
                            </div>
                        </div>

                        <div class="content-card-body">
                            <label class="form-label">Notes</label>
                            <textarea
                                name="notes"
                                class="form-control"
                                rows="3"
                                placeholder="Add request notes or additional needs..."
                            ></textarea>
                        </div>
                    </div>

                    <div class="content-card">
                        <div class="content-card-header">
                            <div>
                                <h5 class="content-card-title mb-1">Request Items</h5>
                                <p class="content-card-subtitle mb-0">
                                    Pilih item ATK, quantity yang dibutuhkan, dan catatan untuk setiap item.
                                </p>
                            </div>

                            <button type="button" class="btn btn-sm btn-light btn-modern" id="addItemRowBtn">
                                <i class="bi bi-plus-lg me-2"></i>Add Item
                            </button>
                        </div>

                        <div class="content-card-body">
                            <div id="requestItemsWrapper">
                                <div class="row g-3 mb-3 request-item-row">
                                    <div class="col-md-5">
                                        <label class="form-label">
                                            Item <span class="text-danger">*</span>
                                        </label>
                                        <select name="items[0][atk_item_id]" class="form-select" required>
                                            <option value="">Select Item</option>
                                            @foreach($items as $item)
                                                <option value="{{ $item->id }}">
                                                    {{ $item->name }} (stock: {{ $item->stock?->current_stock ?? 0 }} {{ $item->unit }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label">
                                            Qty <span class="text-danger">*</span>
                                        </label>
                                        <input type="number" name="items[0][qty]" class="form-control" min="1" value="1" required>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Item Notes</label>
                                        <input type="text" name="items[0][notes]" class="form-control" placeholder="Optional">
                                    </div>

                                    <div class="col-md-1 d-flex align-items-end">
                                        <button type="button" class="btn btn-outline-danger w-100 remove-item-row">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
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
                        <i class="bi bi-check-circle me-2"></i>Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    let itemIndex = 1;
    const wrapper = document.getElementById('requestItemsWrapper');
    const addButton = document.getElementById('addItemRowBtn');

    if (!wrapper || !addButton) return;

    const itemOptions = `
        <option value="">Select Item</option>
        @foreach($items as $item)
            <option value="{{ $item->id }}">
                {{ $item->name }} (stock: {{ $item->stock?->current_stock ?? 0 }} {{ $item->unit }})
            </option>
        @endforeach
    `;

    addButton.addEventListener('click', function () {
        const row = document.createElement('div');
        row.className = 'row g-3 mb-3 request-item-row';
        row.innerHTML = `
            <div class="col-md-5">
                <label class="form-label">Item <span class="text-danger">*</span></label>
                <select name="items[${itemIndex}][atk_item_id]" class="form-select" required>
                    ${itemOptions}
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Qty <span class="text-danger">*</span></label>
                <input type="number" name="items[${itemIndex}][qty]" class="form-control" min="1" value="1" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Item Notes</label>
                <input type="text" name="items[${itemIndex}][notes]" class="form-control" placeholder="Optional">
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="button" class="btn btn-outline-danger w-100 remove-item-row">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        `;

        wrapper.appendChild(row);
        itemIndex++;
    });

    document.addEventListener('click', function (event) {
        if (event.target.closest('.remove-item-row')) {
            const rows = document.querySelectorAll('.request-item-row');

            if (rows.length > 1) {
                event.target.closest('.request-item-row').remove();
            }
        }
    });
});
</script>
@endsection