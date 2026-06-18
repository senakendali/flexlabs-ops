@extends('layouts.app-dashboard')

@section('title', $pageTitle ?? 'Internal Memo')

@section('content')
@php
    $filters = $filters ?? [];
    $statuses = $statuses ?? [];

    $statusBadgeClass = function (?string $status) {
        return match ($status) {
            'draft' => 'bg-secondary',
            'submitted' => 'bg-info',
            'waiting_acknowledgement' => 'bg-warning text-dark',
            'waiting_approval' => 'bg-primary',
            'approved' => 'bg-success',
            'rejected' => 'bg-danger',
            'cancelled' => 'bg-dark',
            default => 'bg-light text-dark',
        };
    };

    $formatDate = function ($value) {
        if (blank($value)) {
            return '-';
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('d M Y');
        } catch (\Throwable $e) {
            return '-';
        }
    };

    $formatCurrency = function ($value) {
        return 'Rp ' . number_format((float) $value, 0, ',', '.');
    };
@endphp

<div class="container-fluid px-4 py-4">

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Operations</div>
                <h1 class="page-title mb-2">{{ $pageTitle ?? 'Internal Memo' }}</h1>
                <p class="page-subtitle mb-0">
                    Kelola memo internal, budget request, dan approval 2 level secara rapi.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <a href="{{ route('internal-memos.pending-approvals') }}" class="btn btn-outline-light btn-modern">
                    <i class="bi bi-check2-square me-2"></i>Pending Approval
                </a>

                <a href="{{ route('internal-memos.my-memos') }}" class="btn btn-outline-light btn-modern">
                    <i class="bi bi-person-lines-fill me-2"></i>My Memos
                </a>

                <a href="{{ route('internal-memos.create') }}" class="btn btn-light btn-modern">
                    <i class="bi bi-plus-circle me-2"></i>Create Memo
                </a>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success border-0 shadow-sm">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger border-0 shadow-sm">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
        </div>
    @endif

    <div class="content-card mb-4">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Filter Memo</h5>
                <p class="content-card-subtitle mb-0">
                    Cari berdasarkan nomor memo, subject, penerima, pengirim, status, dan tanggal.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            <form method="GET" action="{{ request()->url() }}">
                <div class="row g-3 align-items-end">
                    <div class="col-xl-4 col-md-6">
                        <label class="form-label">Keyword</label>
                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            value="{{ $filters['search'] ?? request('search') }}"
                            placeholder="Cari memo no / subject / to / from..."
                        >
                    </div>

                    @if (($filters['scope'] ?? '') !== 'pending-approvals')
                        <div class="col-xl-3 col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}" @selected(($filters['status'] ?? request('status')) === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if (! in_array(($filters['scope'] ?? ''), ['my-memos', 'pending-approvals'], true))
                        <div class="col-xl-2 col-md-6">
                            <label class="form-label">Date From</label>
                            <input
                                type="date"
                                name="date_from"
                                class="form-control"
                                value="{{ $filters['date_from'] ?? request('date_from') }}"
                            >
                        </div>

                        <div class="col-xl-2 col-md-6">
                            <label class="form-label">Date To</label>
                            <input
                                type="date"
                                name="date_to"
                                class="form-control"
                                value="{{ $filters['date_to'] ?? request('date_to') }}"
                            >
                        </div>
                    @endif

                    <div class="col-xl-1 col-md-6 d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="content-card">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Memo List</h5>
                <p class="content-card-subtitle mb-0">
                    Total data: {{ $memos->total() }}
                </p>
            </div>
        </div>

        <div class="content-card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Memo</th>
                            <th>Date</th>
                            <th>To / From</th>
                            <th>Total</th>
                            <th>Approval</th>
                            <th>Status</th>
                            <th class="text-end" style="width: 180px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($memos as $memo)
                            @php
                                $approvalRows = $memo->relationLoaded('approvals') ? $memo->approvals : collect();
                                $nextApproval = $approvalRows
                                    ->where('status', 'pending')
                                    ->sortBy('step_order')
                                    ->first();
                            @endphp

                            <tr>
                                <td>
                                    <div class="fw-semibold">
                                        {{ $memo->memo_number }}
                                    </div>
                                    <div class="text-muted small">
                                        {{ \Illuminate\Support\Str::limit($memo->subject, 80) }}
                                    </div>
                                </td>
                                <td>{{ $formatDate($memo->memo_date) }}</td>
                                <td>
                                    <div class="small">
                                        <strong>To:</strong> {{ $memo->to_name }}
                                    </div>
                                    <div class="small text-muted">
                                        <strong>From:</strong> {{ $memo->from_name }}
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-semibold">{{ $formatCurrency($memo->grand_total_amount) }}</span>
                                </td>
                                <td>
                                    @if ($nextApproval)
                                        <div class="small fw-semibold">{{ $nextApproval->role_label }}</div>
                                        <div class="small text-muted">
                                            {{ $nextApproval->approver_name ?: optional($nextApproval->approver)->name ?: '-' }}
                                        </div>
                                    @else
                                        <span class="text-muted small">No pending approval</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $statusBadgeClass($memo->status) }}">
                                        {{ $statuses[$memo->status] ?? \Illuminate\Support\Str::headline($memo->status) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <a href="{{ route('internal-memos.show', $memo) }}" class="btn btn-sm btn-outline-primary">
                                            View
                                        </a>

                                        <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
                                            <span class="visually-hidden">Toggle Dropdown</span>
                                        </button>

                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('internal-memos.download-pdf', $memo) }}">
                                                    <i class="bi bi-file-earmark-pdf me-2"></i>Download PDF
                                                </a>
                                            </li>

                                            @if (in_array($memo->status, ['draft', 'rejected'], true))
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('internal-memos.edit', $memo) }}">
                                                        <i class="bi bi-pencil-square me-2"></i>Edit
                                                    </a>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                        Belum ada internal memo.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($memos->hasPages())
            <div class="content-card-footer">
                {{ $memos->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
