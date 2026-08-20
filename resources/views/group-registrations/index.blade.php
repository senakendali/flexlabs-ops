@extends('layouts.app-dashboard')

@section('title', 'Group Registration')

@section('content')
<div class="container-fluid px-4 py-4 group-registration-page">
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Sales & Payment</div>
                <h1 class="page-title mb-2">Group Registration</h1>
                <p class="page-subtitle mb-0">
                    Manage multi-participant registrations, company WHT, seat allocation, orders, and payment progress.
                </p>
            </div>

            <a href="{{ route('group-registrations.create') }}" class="btn btn-light btn-modern">
                <i class="bi bi-plus-lg me-2"></i>Create Group Registration
            </a>
        </div>
    </div>

    <div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1090;"></div>

    <div class="row g-3 mb-4">
        @foreach ([
            ['icon' => 'bi-people-fill', 'title' => 'Total Registration', 'value' => $stats['total'] ?? 0, 'desc' => 'All group registration records.'],
            ['icon' => 'bi-hourglass-split', 'title' => 'Pending', 'value' => $stats['pending'] ?? 0, 'desc' => 'Registrations waiting for completion.'],
            ['icon' => 'bi-check-circle', 'title' => 'Confirmed', 'value' => $stats['confirmed'] ?? 0, 'desc' => 'Confirmed group registrations.'],
            ['icon' => 'bi-buildings', 'title' => 'Company Buyer', 'value' => $stats['company'] ?? 0, 'desc' => 'Registrations subject to company flow.'],
        ] as $card)
            <div class="col-xl-3 col-md-6">
                <div class="stat-card h-100">
                    <div class="stat-card-top">
                        <div class="stat-icon-wrap"><i class="bi {{ $card['icon'] }}"></i></div>
                        <div>
                            <div class="stat-title">{{ $card['title'] }}</div>
                            <div class="stat-value">{{ number_format($card['value'], 0, ',', '.') }}</div>
                        </div>
                    </div>
                    <div class="stat-description">{{ $card['desc'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="content-card mb-4">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Filter Registration</h5>
                <p class="content-card-subtitle mb-0">Search by registration number, buyer, company, program, or batch.</p>
            </div>
        </div>
        <div class="content-card-body">
            <form method="GET" action="{{ route('group-registrations.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-xl-4 col-md-6">
                        <label class="form-label">Search</label>
                        <input type="text" name="keyword" class="form-control" value="{{ request('keyword') }}" placeholder="Registration number, buyer, company...">
                    </div>
                    <div class="col-xl-2 col-md-6">
                        <label class="form-label">Buyer Type</label>
                        <select name="buyer_type" class="form-select">
                            <option value="">All Buyers</option>
                            <option value="individual" @selected(request('buyer_type') === 'individual')>Individual</option>
                            <option value="company" @selected(request('buyer_type') === 'company')>Company</option>
                        </select>
                    </div>
                    <div class="col-xl-2 col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            @foreach (['draft', 'pending', 'confirmed', 'cancelled', 'completed'] as $status)
                                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-2 col-md-6">
                        <label class="form-label">Batch</label>
                        <select name="batch_id" class="form-select">
                            <option value="">All Batches</option>
                            @foreach ($batches as $batch)
                                <option value="{{ $batch->id }}" @selected((string) request('batch_id') === (string) $batch->id)>
                                    {{ $batch->program?->name }} — {{ $batch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-2 col-md-6">
                        <div class="d-flex gap-2">
                            <button class="btn btn-primary btn-modern flex-grow-1"><i class="bi bi-search me-1"></i>Filter</button>
                            <a href="{{ route('group-registrations.index') }}" class="btn btn-outline-secondary btn-modern">Reset</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="content-card">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Registration List</h5>
                <p class="content-card-subtitle mb-0">Review buyers, seats, pricing, WHT, and payment status.</p>
            </div>
            <div class="small text-muted">Total: <strong>{{ $groupRegistrations->total() }}</strong></div>
        </div>
        <div class="content-card-body">
            @if ($groupRegistrations->count())
                <div class="table-responsive dropdown-safe-table">
                    <table class="table table-hover align-middle admin-table mb-0">
                        <thead>
                            <tr>
                                <th>Registration</th>
                                <th>Buyer</th>
                                <th>Program & Batch</th>
                                <th>Seats</th>
                                <th>Net Payable</th>
                                <th>WHT</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($groupRegistrations as $registration)
                                @php
                                    $availableSeats = max(0, $registration->quantity - ($registration->assigned_seats ?? 0));
                                    $statusClass = match ($registration->status) {
                                        'confirmed', 'completed' => 'bg-success-subtle text-success-emphasis border-success-subtle',
                                        'cancelled' => 'bg-danger-subtle text-danger-emphasis border-danger-subtle',
                                        'pending' => 'bg-warning-subtle text-warning-emphasis border-warning-subtle',
                                        default => 'bg-secondary-subtle text-secondary-emphasis border-secondary-subtle',
                                    };
                                @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route('group-registrations.show', $registration) }}" class="fw-semibold text-decoration-none">
                                            {{ $registration->registration_number }}
                                        </a>
                                        <div class="small text-muted mt-1">{{ $registration->created_at?->format('d M Y, H:i') }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $registration->buyer_name }}</div>
                                        <div class="small text-muted mt-1">{{ ucfirst($registration->buyer_type) }} · {{ $registration->buyer_email ?: '-' }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $registration->batch?->program?->name ?: '-' }}</div>
                                        <div class="small text-muted mt-1">{{ $registration->batch?->name ?: '-' }}</div>
                                    </td>
                                    <td class="text-nowrap">
                                        <div class="fw-semibold">{{ $registration->assigned_seats ?? 0 }} / {{ $registration->quantity }}</div>
                                        <div class="small text-muted">{{ $availableSeats }} available</div>
                                    </td>
                                    <td class="text-nowrap">
                                        <div class="fw-semibold">Rp {{ number_format((float) $registration->net_payable, 0, ',', '.') }}</div>
                                        <div class="small text-muted">Invoice Rp {{ number_format((float) $registration->invoice_total, 0, ',', '.') }}</div>
                                    </td>
                                    <td class="text-nowrap">
                                        @if ($registration->buyer_type === 'company')
                                            <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis border border-warning-subtle">PPh 23 · {{ $registration->wht_rate }}%</span>
                                            <div class="small text-muted mt-1">{{ ucfirst(str_replace('_', ' ', $registration->wht_status)) }}</div>
                                        @else
                                            <span class="text-muted">Not applicable</span>
                                        @endif
                                    </td>
                                    <td><span class="badge rounded-pill border {{ $statusClass }}">{{ ucfirst($registration->status) }}</span></td>
                                    <td class="text-end text-nowrap">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle px-3" data-bs-toggle="dropdown" data-bs-boundary="viewport">Actions</button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                <li><a class="dropdown-item" href="{{ route('group-registrations.show', $registration) }}"><i class="bi bi-eye me-2"></i>View Detail</a></li>
                                                <li><a class="dropdown-item" href="{{ route('group-registrations.edit', $registration) }}"><i class="bi bi-pencil-square me-2"></i>Edit Metadata</a></li>
                                                @if ($registration->status !== 'cancelled')
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><button type="button" class="dropdown-item text-danger cancel-registration-btn" data-url="{{ route('group-registrations.destroy', $registration) }}" data-number="{{ $registration->registration_number }}"><i class="bi bi-x-circle me-2"></i>Cancel Registration</button></li>
                                                @endif
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($groupRegistrations->hasPages())
                    <div class="mt-3 d-flex justify-content-between align-items-center gap-3 flex-wrap">
                        <div class="small text-muted">Showing {{ $groupRegistrations->firstItem() }}–{{ $groupRegistrations->lastItem() }} of {{ $groupRegistrations->total() }}</div>
                        {{ $groupRegistrations->links() }}
                    </div>
                @endif
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon"><i class="bi bi-people-fill"></i></div>
                    <h5 class="empty-state-title">No group registrations found</h5>
                    <p class="empty-state-text">Create a registration for two or more participants.</p>
                    <a href="{{ route('group-registrations.create') }}" class="btn btn-primary btn-modern"><i class="bi bi-plus-lg me-2"></i>Create Registration</a>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="modal fade" id="cancelRegistrationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-0 pb-0"><h5 class="modal-title">Cancel Group Registration</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><p class="mb-2">Cancel registration <strong id="cancelRegistrationNumber"></strong>?</p><div class="alert alert-warning mb-0">Payment paid akan membuat pembatalan ditolak.</div></div>
            <div class="modal-footer border-0"><button class="btn btn-light border" data-bs-dismiss="modal">Back</button><button id="confirmCancelBtn" class="btn btn-danger"><span class="default-text">Cancel Registration</span><span class="loading-text d-none"><span class="spinner-border spinner-border-sm me-2"></span>Processing...</span></button></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('cancelRegistrationModal');
    const confirmBtn = document.getElementById('confirmCancelBtn');
    const numberEl = document.getElementById('cancelRegistrationNumber');
    const toastContainer = document.getElementById('toastContainer');
    if (!modalEl || !confirmBtn || typeof bootstrap === 'undefined') return;
    const modal = new bootstrap.Modal(modalEl);
    let cancelUrl = null;

    const toast = (message, type = 'success') => {
        const id = `toast-${Date.now()}`;
        toastContainer.insertAdjacentHTML('beforeend', `<div id="${id}" class="toast text-white bg-${type} border-0 mb-2"><div class="d-flex"><div class="toast-body">${message}</div><button class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>`);
        const el = document.getElementById(id); new bootstrap.Toast(el, {delay: 3000}).show(); el.addEventListener('hidden.bs.toast', () => el.remove());
    };

    document.querySelectorAll('.cancel-registration-btn').forEach(btn => btn.addEventListener('click', () => {
        cancelUrl = btn.dataset.url; numberEl.textContent = btn.dataset.number || '-'; modal.show();
    }));

    confirmBtn.addEventListener('click', async () => {
        if (!cancelUrl) return;
        confirmBtn.disabled = true;
        confirmBtn.querySelector('.default-text').classList.add('d-none');
        confirmBtn.querySelector('.loading-text').classList.remove('d-none');
        try {
            const response = await fetch(cancelUrl, {method: 'DELETE', headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}'}});
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || 'Failed to cancel registration.');
            modal.hide(); toast(result.message || 'Registration cancelled.', 'success'); setTimeout(() => location.reload(), 800);
        } catch (error) {
            toast(error.message, 'danger');
            confirmBtn.disabled = false;
            confirmBtn.querySelector('.default-text').classList.remove('d-none');
            confirmBtn.querySelector('.loading-text').classList.add('d-none');
        }
    });
});
</script>
@endpush