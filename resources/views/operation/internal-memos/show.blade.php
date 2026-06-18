@extends('layouts.app-dashboard')

@section('title', $memo->memo_number ?: 'Internal Memo Detail')

@section('content')
@php
    $statuses = $statuses ?? [];

    $formatDate = function ($value, string $format = 'd M Y') {
        if (blank($value)) {
            return '-';
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format($format);
        } catch (\Throwable $e) {
            return '-';
        }
    };

    $formatDateTime = function ($value) use ($formatDate) {
        return $formatDate($value, 'd M Y H:i');
    };

    $formatCurrency = function ($value) {
        return 'Rp ' . number_format((float) $value, 0, ',', '.');
    };

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

    $approvalBadgeClass = function (?string $status) {
        return match ($status) {
            'approved' => 'bg-success',
            'rejected' => 'bg-danger',
            'pending' => 'bg-warning text-dark',
            default => 'bg-secondary',
        };
    };

    $approvalRows = $memo->relationLoaded('approvals') ? $memo->approvals : collect();
    $acknowledgementRows = $approvalRows->where('role_label', 'Acknowledged by')->values();

    if ($acknowledgementRows->count() < 2) {
        $fallbackAcknowledgements = collect([
            (object) [
                'step_order' => 1,
                'role_label' => 'Acknowledged by',
                'approver_name' => 'Andres Dony Wijaya',
                'approver_position' => 'Business Admin Manager',
                'status' => $memo->status === 'approved' ? 'approved' : 'pending',
                'notes' => null,
            ],
            (object) [
                'step_order' => 2,
                'role_label' => 'Acknowledged by',
                'approver_name' => 'Awalokita Garnierit',
                'approver_position' => 'Academic Business Unit Head',
                'status' => $memo->status === 'approved' ? 'approved' : 'pending',
                'notes' => null,
            ],
        ]);

        $acknowledgementRows = $acknowledgementRows->concat($fallbackAcknowledgements)->take(2)->values();
    }
@endphp

<div class="container-fluid px-4 py-4">

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Operations</div>
                <h1 class="page-title mb-2">{{ $memo->memo_number }}</h1>
                <p class="page-subtitle mb-0">
                    {{ $memo->subject }}
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <a href="{{ route('internal-memos.index') }}" class="btn btn-outline-light btn-modern">
                    <i class="bi bi-arrow-left me-2"></i>Back
                </a>

                <a href="{{ route('internal-memos.download-pdf', $memo) }}" class="btn btn-light btn-modern">
                    <i class="bi bi-file-earmark-pdf me-2"></i>Download PDF
                </a>

                @if ($canEdit)
                    <a href="{{ route('internal-memos.edit', $memo) }}" class="btn btn-warning btn-modern">
                        <i class="bi bi-pencil-square me-2"></i>Edit
                    </a>
                @endif
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

    <div class="row g-4">
        <div class="col-12">
            <div class="content-card mb-4">
                <div class="content-card-header d-flex justify-content-between align-items-start gap-3 flex-wrap">
                    <div>
                        <h5 class="content-card-title mb-1">Memo Information</h5>
                        <p class="content-card-subtitle mb-0">
                            Detail informasi memo internal.
                        </p>
                    </div>
                    <span class="badge {{ $statusBadgeClass($memo->status) }}">
                        {{ $statuses[$memo->status] ?? \Illuminate\Support\Str::headline($memo->status) }}
                    </span>
                </div>

                <div class="content-card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="border rounded-4 p-3 h-100 bg-light-subtle">
                                <div class="text-muted small mb-1">Memo Date</div>
                                <div class="fw-semibold">{{ $formatDate($memo->memo_date) }}</div>
                            </div>
                        </div>

                        <div class="col-md-8">
                            <div class="border rounded-4 p-3 h-100 bg-light-subtle">
                                <div class="text-muted small mb-1">Subject</div>
                                <div class="fw-semibold">{{ $memo->subject }}</div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="border rounded-4 p-3 h-100 bg-light-subtle">
                                <div class="text-muted small mb-1">Attachment</div>
                                <div class="fw-semibold">{{ $memo->attachment_label ?: '-' }}</div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="border rounded-4 p-3 h-100 bg-light-subtle">
                                <div class="text-muted small mb-1">Submitted At</div>
                                <div class="fw-semibold">{{ $formatDateTime($memo->submitted_at) }}</div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="border rounded-4 p-3 h-100 bg-light-subtle">
                                <div class="text-muted small mb-1">Final Approved At</div>
                                <div class="fw-semibold">{{ $formatDateTime($memo->approved_at) }}</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="border rounded-4 p-3 h-100">
                                <div class="text-muted small mb-1">To</div>
                                <div class="fw-semibold">{{ $memo->to_name }}</div>
                                <div class="text-muted">{{ $memo->to_position ?: '-' }}</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="border rounded-4 p-3 h-100">
                                <div class="text-muted small mb-1">From</div>
                                <div class="fw-semibold">{{ $memo->from_name }}</div>
                                <div class="text-muted">{{ $memo->from_position ?: '-' }}</div>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="text-muted small mb-1">Purpose</div>
                            <div class="border rounded-4 p-3 bg-light" style="white-space: pre-line;">{{ $memo->purpose ?: '-' }}</div>
                        </div>

                        <div class="col-md-12">
                            <div class="text-muted small mb-1">Notes</div>
                            <div class="border rounded-4 p-3 bg-light" style="white-space: pre-line;">{{ $memo->notes ?: '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="content-card mb-4">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Acknowledgement Signers</h5>
                        <p class="content-card-subtitle mb-0">
                            Signer acknowledgement yang akan muncul di dokumen memo.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="row g-3">
                        @foreach ($acknowledgementRows as $approval)
                            <div class="col-lg-6">
                                <div class="border rounded-4 p-3 h-100 bg-light-subtle">
                                    <div class="d-flex justify-content-between align-items-start gap-3 mb-3 flex-wrap">
                                        <div>
                                            <div class="fw-semibold">{{ $approval->role_label }}</div>
                                            <div class="text-muted small">Signer {{ $approval->step_order }}</div>
                                        </div>

                                        <span class="badge {{ $approvalBadgeClass($approval->status) }}">
                                            {{ \Illuminate\Support\Str::headline($approval->status ?? 'pending') }}
                                        </span>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-12">
                                            <div class="text-muted small mb-1">Name</div>
                                            <div class="fw-semibold">{{ $approval->approver_name ?: optional($approval->approver ?? null)->name ?: '-' }}</div>
                                        </div>

                                        <div class="col-12">
                                            <div class="text-muted small mb-1">Position</div>
                                            <div class="fw-semibold">{{ $approval->approver_position ?: '-' }}</div>
                                        </div>

                                        @if (! blank($approval->notes ?? null))
                                            <div class="col-12">
                                                <div class="text-muted small mb-1">Notes</div>
                                                <div style="white-space: pre-line;">{{ $approval->notes }}</div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="content-card mb-4">
                <div class="content-card-header d-flex justify-content-between align-items-start gap-3 flex-wrap">
                    <div>
                        <h5 class="content-card-title mb-1">Budget Items</h5>
                        <p class="content-card-subtitle mb-0">
                            Setiap budget item ditampilkan sebagai row/card sendiri supaya lebarnya lega.
                        </p>
                    </div>

                    <span class="badge bg-light text-dark">
                        {{ $memo->items->count() }} item
                    </span>
                </div>

                <div class="content-card-body">
                    <div class="d-grid gap-3">
                        @forelse ($memo->items as $item)
                            <div class="border rounded-4 p-3 bg-light-subtle">
                                <div class="d-flex justify-content-between align-items-start gap-3 mb-3 flex-wrap">
                                    <div>
                                        <div class="fw-semibold">Budget Item #{{ $loop->iteration }}</div>
                                        <div class="text-muted small">{{ $item->remarks ?: 'No remarks' }}</div>
                                    </div>

                                    <div class="text-end">
                                        <div class="text-muted small">Estimated Price</div>
                                        <div class="fw-bold fs-5">{{ $formatCurrency($item->estimated_price) }}</div>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="text-muted small mb-1">Details</div>
                                        <div class="fw-semibold" style="white-space: pre-line;">{{ $item->details }}</div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="border rounded-3 p-3 bg-white h-100">
                                            <div class="text-muted small mb-1">Price</div>
                                            <div class="fw-semibold">{{ $formatCurrency($item->price) }}</div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="border rounded-3 p-3 bg-white h-100">
                                            <div class="text-muted small mb-1">Quantity</div>
                                            <div class="fw-semibold">{{ $item->quantity }}</div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="border rounded-3 p-3 bg-white h-100">
                                            <div class="text-muted small mb-1">Estimated Price</div>
                                            <div class="fw-semibold">{{ $formatCurrency($item->estimated_price) }}</div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="text-muted small mb-1">Remarks</div>
                                        <div style="white-space: pre-line;">{{ $item->remarks ?: '-' }}</div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-muted py-4">
                                Belum ada budget item.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="content-card mb-4">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Amount Summary</h5>
                        <p class="content-card-subtitle mb-0">
                            Ringkasan nominal memo.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="row g-3">
                        <div class="col-xl-3 col-md-6">
                            <div class="border rounded-4 p-3 h-100 bg-light-subtle">
                                <div class="text-muted small mb-1">Subtotal</div>
                                <div class="fs-5 fw-bold">{{ $formatCurrency($memo->subtotal_amount) }}</div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6">
                            <div class="border rounded-4 p-3 h-100 bg-light-subtle">
                                <div class="text-muted small mb-1">Tax Rate</div>
                                <div class="fs-5 fw-bold">{{ number_format((float) $memo->tax_rate, 2) }}%</div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6">
                            <div class="border rounded-4 p-3 h-100 bg-light-subtle">
                                <div class="text-muted small mb-1">Tax Amount</div>
                                <div class="fs-5 fw-bold">{{ $formatCurrency($memo->tax_amount) }}</div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6">
                            <div class="border rounded-4 p-3 h-100 bg-light-subtle">
                                <div class="text-muted small mb-1">Grand Total</div>
                                <div class="fs-4 fw-bold">{{ $formatCurrency($memo->grand_total_amount) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="content-card mb-4">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Memo Actions</h5>
                        <p class="content-card-subtitle mb-0">
                            Memo sekarang otomatis approved setelah disimpan.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    @if (in_array($memo->status, ['draft', 'rejected', 'waiting_acknowledgement', 'waiting_approval'], true))
                        <form method="POST" action="{{ route('internal-memos.cancel', $memo) }}" class="d-flex justify-content-end">
                            @csrf
                            @method('PATCH')

                            <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Cancel memo ini?')">
                                <i class="bi bi-slash-circle me-2"></i>Cancel Memo
                            </button>
                        </form>
                    @else
                        <div class="text-muted small">
                            Memo ini sudah otomatis approved. Edit memo masih bisa dilakukan selama belum cancelled.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
