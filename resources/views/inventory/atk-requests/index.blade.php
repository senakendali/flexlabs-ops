@extends('layouts.app-dashboard')

@section('title', 'ATK Request')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h4 class="mb-1">ATK Request</h4>
            <small class="text-muted">Manage stationery requests for internal operations</small>
        </div>

        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createRequestModal">
            <i class="bi bi-plus-lg me-1"></i> Create Request
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
                    <div class="text-muted small">Total Requests</div>
                    <div class="fs-3 fw-bold">{{ $stats['total_requests'] }}</div>
                    <div class="small text-muted mt-2">All submitted ATK requests.</div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Pending</div>
                    <div class="fs-3 fw-bold text-warning">{{ $stats['pending_requests'] }}</div>
                    <div class="small text-muted mt-2">Requests waiting for approval.</div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Approved</div>
                    <div class="fs-3 fw-bold text-success">{{ $stats['approved_requests'] }}</div>
                    <div class="small text-muted mt-2">Approved requests with processed stock.</div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Rejected</div>
                    <div class="fs-3 fw-bold text-danger">{{ $stats['rejected_requests'] }}</div>
                    <div class="small text-muted mt-2">Requests that were declined.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body border-bottom">
            <form method="GET" class="d-flex flex-column flex-md-row align-items-md-center gap-2">
                <div class="d-flex align-items-center gap-2 flex-wrap">
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

                    <button type="submit" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>

                    @if(request()->filled('status'))
                        <a href="{{ route('inventory.atk-requests.index') }}" class="btn btn-sm btn-outline-secondary">
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
                        <th>Request No</th>
                        <th>Requester</th>
                        <th style="width: 140px;">Date</th>
                        <th style="width: 120px;">Total Items</th>
                        <th style="width: 140px;">Status</th>
                        <th style="width: 180px;" class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $requestData)
                        <tr>
                            <td>
                                {{ ($requests->currentPage() - 1) * $requests->perPage() + $loop->iteration }}
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $requestData->request_number }}</div>
                                <div class="text-muted small">
                                    {{ \Illuminate\Support\Str::limit($requestData->notes ?: '-', 50) }}
                                </div>
                            </td>
                            <td>{{ $requestData->requester->name ?? '-' }}</td>
                            <td>{{ optional($requestData->request_date)->format('d M Y') ?? '-' }}</td>
                            <td class="fw-semibold">{{ $requestData->items->sum('qty') }}</td>
                            <td>
                                @if ($requestData->status === 'pending')
                                    <span class="badge bg-warning">Pending</span>
                                @elseif ($requestData->status === 'approved')
                                    <span class="badge bg-success">Approved</span>
                                @elseif ($requestData->status === 'rejected')
                                    <span class="badge bg-danger">Rejected</span>
                                @elseif ($requestData->status === 'cancelled')
                                    <span class="badge bg-secondary">Cancelled</span>
                                @else
                                    <span class="badge bg-dark">{{ ucfirst($requestData->status) }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-primary"
                                    data-bs-toggle="modal"
                                    data-bs-target="#detailRequestModal{{ $requestData->id }}"
                                >
                                    <i class="bi bi-eye me-1"></i> View Detail
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                No ATK requests found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($requests->hasPages())
            <div class="card-footer bg-white">
                {{ $requests->links() }}
            </div>
        @endif
    </div>
</div>

{{-- DETAIL MODALS: TARUH DI LUAR TABLE --}}
@foreach ($requests as $requestData)
    <div class="modal fade" id="detailRequestModal{{ $requestData->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-1">Request Detail</h5>
                        <small class="text-muted">{{ $requestData->request_number }}</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <div class="small text-muted">Requester</div>
                            <div class="fw-semibold">{{ $requestData->requester->name ?? '-' }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="small text-muted">Request Date</div>
                            <div class="fw-semibold">{{ optional($requestData->request_date)->format('d M Y') ?? '-' }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="small text-muted">Status</div>
                            <div>
                                @if ($requestData->status === 'pending')
                                    <span class="badge bg-warning">Pending</span>
                                @elseif ($requestData->status === 'approved')
                                    <span class="badge bg-success">Approved</span>
                                @elseif ($requestData->status === 'rejected')
                                    <span class="badge bg-danger">Rejected</span>
                                @elseif ($requestData->status === 'cancelled')
                                    <span class="badge bg-secondary">Cancelled</span>
                                @else
                                    <span class="badge bg-dark">{{ ucfirst($requestData->status) }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="small text-muted">Approved By</div>
                            <div class="fw-semibold">{{ $requestData->approver->name ?? '-' }}</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="small text-muted">Approval Time</div>
                            <div>{{ optional($requestData->approved_at)->format('d M Y H:i') ?? '-' }}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="small text-muted">Total Requested Qty</div>
                            <div class="fw-semibold">{{ $requestData->items->sum('qty') }}</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="small text-muted">Request Notes</div>
                        <div>{{ $requestData->notes ?: '-' }}</div>
                    </div>

                    @if($requestData->rejection_reason)
                        <div class="mb-3">
                            <div class="small text-muted">Rejection Reason</div>
                            <div class="text-danger">{{ $requestData->rejection_reason }}</div>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 70px;">No</th>
                                    <th>Item</th>
                                    <th style="width: 100px;">Qty</th>
                                    <th style="width: 100px;">Unit</th>
                                    <th style="width: 130px;">Current Stock</th>
                                    <th>Item Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($requestData->items as $detail)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td class="fw-semibold">{{ $detail->item->name ?? '-' }}</td>
                                        <td>{{ $detail->qty }}</td>
                                        <td>{{ $detail->unit ?? '-' }}</td>
                                        <td>{{ $detail->item->stock?->current_stock ?? 0 }}</td>
                                        <td class="text-muted">{{ $detail->notes ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            No request items found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer d-flex justify-content-between">
                    <div>
                        @if($requestData->status === 'pending' && ((int) $requestData->user_id === (int) auth()->id() || auth()->user()?->role === 'admin'))
                            <form method="POST" action="{{ route('inventory.atk-requests.cancel', $requestData) }}" class="d-inline">
                                @csrf
                                <button
                                    type="submit"
                                    class="btn btn-outline-secondary"
                                    onclick="return confirm('Cancel this request?')"
                                >
                                    Cancel Request
                                </button>
                            </form>
                        @endif
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>

                        @if($requestData->status === 'pending' && auth()->user()?->role === 'admin')
                            <button
                                type="button"
                                class="btn btn-danger"
                                data-bs-toggle="modal"
                                data-bs-target="#rejectModal{{ $requestData->id }}"
                            >
                                Reject
                            </button>

                            <form method="POST" action="{{ route('inventory.atk-requests.approve', $requestData) }}" class="d-inline">
                                @csrf
                                <button
                                    type="submit"
                                    class="btn btn-success"
                                    onclick="return confirm('Approve this request? Stock will be reduced automatically.')"
                                >
                                    Approve
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="rejectModal{{ $requestData->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form method="POST" action="{{ route('inventory.atk-requests.reject', $requestData) }}">
                    @csrf

                    <div class="modal-header">
                        <h5 class="modal-title">Reject Request</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <label class="form-label">Rejection Reason</label>
                        <textarea
                            name="rejection_reason"
                            class="form-control"
                            rows="4"
                            required
                            placeholder="Write the reason for rejection..."
                        ></textarea>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Reject Request</button>
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
                    <h5 class="modal-title">Create ATK Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea
                            name="notes"
                            class="form-control"
                            rows="3"
                            placeholder="Add request notes or additional needs..."
                        ></textarea>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Request Items</h6>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="addItemRowBtn">
                            Add Item
                        </button>
                    </div>

                    <div id="requestItemsWrapper">
                        <div class="row g-3 mb-3 request-item-row">
                            <div class="col-md-5">
                                <label class="form-label">Item</label>
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
                                <label class="form-label">Qty</label>
                                <input type="number" name="items[0][qty]" class="form-control" min="1" value="1" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Item Notes</label>
                                <input type="text" name="items[0][notes]" class="form-control" placeholder="Optional">
                            </div>

                            <div class="col-md-1 d-flex align-items-end">
                                <button type="button" class="btn btn-outline-danger w-100 remove-item-row">×</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Request</button>
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
                <label class="form-label">Item</label>
                <select name="items[${itemIndex}][atk_item_id]" class="form-select" required>
                    ${itemOptions}
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Qty</label>
                <input type="number" name="items[${itemIndex}][qty]" class="form-control" min="1" value="1" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Item Notes</label>
                <input type="text" name="items[${itemIndex}][notes]" class="form-control" placeholder="Optional">
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="button" class="btn btn-outline-danger w-100 remove-item-row">×</button>
            </div>
        `;

        wrapper.appendChild(row);
        itemIndex++;
    });

    document.addEventListener('click', function (event) {
        if (event.target.classList.contains('remove-item-row')) {
            const rows = document.querySelectorAll('.request-item-row');
            if (rows.length > 1) {
                event.target.closest('.request-item-row').remove();
            }
        }
    });
});
</script>
@endsection