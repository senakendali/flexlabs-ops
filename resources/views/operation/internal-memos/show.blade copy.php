@extends('layouts.app-dashboard')

@section('title', $memo->memo_number ?: 'Internal Memo Detail')

@section('content')
@php
    $statuses = $statuses ?? [];

    $paymentSources = $paymentSources ?? [
        'bank' => 'Bank',
        'cash' => 'Cash',
    ];

    $taxTreatments = $taxTreatments ?? [
        'not_include' => 'Tax Not Include',
        'include' => 'Tax Include',
    ];

    $taxEntityTypes = $taxEntityTypes ?? [
        'pkp' => 'PKP',
        'non_pkp' => 'Non PKP',
    ];

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

    $approvalCardClass = function (?string $status, bool $isActive = false) {
        if ($isActive) {
            return 'border-primary bg-primary bg-opacity-10';
        }

        return match ($status) {
            'approved' => 'border-success bg-success bg-opacity-10',
            'rejected' => 'border-danger bg-danger bg-opacity-10',
            'pending' => 'border-warning bg-warning bg-opacity-10',
            default => 'border bg-light-subtle',
        };
    };

    $paymentSourceLabel = $paymentSources[$memo->payment_source] ?? (
        $memo->payment_source ? \Illuminate\Support\Str::headline($memo->payment_source) : '-'
    );

    $taxTreatmentLabel = $taxTreatments[$memo->tax_treatment] ?? (
        $memo->tax_treatment ? \Illuminate\Support\Str::headline(str_replace('_', ' ', $memo->tax_treatment)) : '-'
    );

    $taxEntityTypeLabel = $taxEntityTypes[$memo->tax_entity_type] ?? (
        $memo->tax_entity_type ? \Illuminate\Support\Str::headline(str_replace('_', ' ', $memo->tax_entity_type)) : '-'
    );

    $allowedPurposeTags = '<p><br><strong><b><em><i><u><s><ol><ul><li><blockquote><a><span>';
    $purposeHtml = trim(strip_tags((string) $memo->purpose, $allowedPurposeTags));

    $approvalRows = $memo->relationLoaded('approvals')
        ? $memo->approvals->sortBy('step_order')->values()
        : collect();

    $activeApproval = $activeApproval ?? $approvalRows
        ->where('status', 'pending')
        ->sortBy('step_order')
        ->first();

    $activeApprovalId = $activeApproval?->id;

    $approvalStageLabel = function ($approval) {
        return match ((int) ($approval?->step_order ?? 0)) {
            1 => 'First Acknowledgement',
            2 => 'Second Acknowledgement',
            3 => 'Final Approval',
            default => $approval?->role_label ?: '-',
        };
    };

    $attachmentUrl = trim((string) ($memo->attachment_url ?? ''));
    $attachmentLabel = $memo->attachment_label ?: 'Google Drive Attachment';

    $canSubmit = $canSubmit ?? false;
    $canApprove = $canApprove ?? false;
    $canEdit = $canEdit ?? false;
@endphp

@push('styles')
<style>
    .memo-rich-content {
        min-height: 72px;
        line-height: 1.65;
    }

    .memo-rich-content p {
        margin-bottom: .75rem;
    }

    .memo-rich-content p:last-child {
        margin-bottom: 0;
    }

    .memo-rich-content ul,
    .memo-rich-content ol {
        margin-bottom: .75rem;
        padding-left: 1.35rem;
    }

    .memo-rich-content li {
        margin-bottom: .35rem;
    }

    .memo-rich-content blockquote {
        margin: 0 0 .75rem;
        padding-left: 1rem;
        border-left: 4px solid rgba(91, 62, 142, .24);
        color: #64748b;
    }

    .memo-approval-card {
        border-width: 1px;
        border-style: solid;
        border-radius: 1rem;
        transition: .2s ease;
    }

    .memo-approval-step {
        width: 38px;
        height: 38px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        background: rgba(91, 62, 142, .12);
        color: #5B3E8E;
        flex: 0 0 auto;
    }

    .memo-attachment-link {
        word-break: break-word;
    }

    .memo-confirm-icon {
        width: 48px;
        height: 48px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
        background: rgba(91, 62, 142, .12);
        color: #5B3E8E;
        flex: 0 0 auto;
    }

    .memo-confirm-message {
        line-height: 1.65;
    }
</style>
@endpush

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

    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm">
            <div class="fw-semibold mb-2">
                <i class="bi bi-exclamation-triangle me-2"></i>Action belum bisa diproses.
            </div>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
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
                        {{ $statuses[$memo->status] ?? \Illuminate\Support\Str::headline(str_replace('_', ' ', $memo->status)) }}
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

                        <div class="col-md-4">
                            <div class="border rounded-4 p-3 h-100 bg-light-subtle">
                                <div class="text-muted small mb-1">Due Date</div>
                                <div class="fw-semibold">{{ $formatDate($memo->due_date) }}</div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="border rounded-4 p-3 h-100 bg-light-subtle">
                                <div class="text-muted small mb-1">Payment Source</div>
                                <div class="fw-semibold">{{ $paymentSourceLabel }}</div>
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

                                @if ($attachmentUrl !== '')
                                    <div class="fw-semibold mb-2">{{ $attachmentLabel }}</div>

                                    <a
                                        href="{{ $attachmentUrl }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="btn btn-sm btn-outline-primary"
                                    >
                                        <i class="bi bi-box-arrow-up-right me-1"></i>Open Attachment
                                    </a>
                                @else
                                    <div class="fw-semibold">{{ $memo->attachment_label ?: '-' }}</div>
                                @endif
                            </div>
                        </div>

                        

                        <div class="col-md-6">
                            <div class="border rounded-4 p-3 h-100 bg-light-subtle">
                                <div class="text-muted small mb-1">Submitted At</div>
                                <div class="fw-semibold">{{ $formatDateTime($memo->submitted_at) }}</div>
                            </div>
                        </div>

                        <div class="col-md-6">
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

                        <div class="col-12">
                            <div class="text-muted small mb-1">Purpose</div>
                            <div class="border rounded-4 p-3 bg-light memo-rich-content">
                                @if (! blank($purposeHtml))
                                    {!! $purposeHtml !!}
                                @else
                                    -
                                @endif
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="text-muted small mb-1">Notes</div>
                            <div class="border rounded-4 p-3 bg-light" style="white-space: pre-line;">{{ $memo->notes ?: '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Budget Items moved before Approval Workflow --}}
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

        {{-- Approval Workflow --}}
        <div class="col-12">
            <div class="content-card mb-4">
                <div class="content-card-header d-flex justify-content-between align-items-start gap-3 flex-wrap">
                    <div>
                        <h5 class="content-card-title mb-1">Approval Workflow</h5>
                        <p class="content-card-subtitle mb-0">
                            Approval berjalan berurutan dari signer 1 sampai signer 3.
                        </p>
                    </div>

                    @if ($activeApproval)
                        <span class="badge bg-primary">
                            Active: Signer {{ $activeApproval->step_order }}
                        </span>
                    @endif
                </div>

                <div class="content-card-body">
                    @if ($approvalRows->isNotEmpty())
                        <div class="row g-3">
                            @foreach ($approvalRows as $approval)
                                @php
                                    $isActiveApproval = $activeApprovalId && (int) $activeApprovalId === (int) $approval->id;
                                    $approverName = $approval->approver_name ?: optional($approval->approver ?? null)->name;
                                    $approverEmail = $approval->approver_email ?: optional($approval->approver ?? null)->email;
                                @endphp

                                <div class="col-xl-4 col-lg-6">
                                    <div class="memo-approval-card {{ $approvalCardClass($approval->status, $isActiveApproval) }} p-3 h-100">
                                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                            <div class="d-flex align-items-start gap-2">
                                                <span class="memo-approval-step">{{ $approval->step_order }}</span>
                                                <div>
                                                    <div class="fw-bold">
                                                        Signer {{ $approval->step_order }}
                                                    </div>
                                                    <div class="text-muted small">
                                                        {{ $approvalStageLabel($approval) }}
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="d-flex flex-column align-items-end gap-1">
                                                <span class="badge {{ $approvalBadgeClass($approval->status) }}">
                                                    {{ \Illuminate\Support\Str::headline($approval->status ?? 'pending') }}
                                                </span>

                                                @if ($isActiveApproval)
                                                    <span class="badge bg-primary">
                                                        Active
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="row g-3">
                                            <div class="col-12">
                                                <div class="text-muted small mb-1">Name</div>
                                                <div class="fw-semibold">{{ $approverName ?: '-' }}</div>
                                            </div>

                                            <div class="col-12">
                                                <div class="text-muted small mb-1">Email</div>
                                                <div class="fw-semibold text-break">{{ $approverEmail ?: '-' }}</div>
                                            </div>

                                            <div class="col-12">
                                                <div class="text-muted small mb-1">Position</div>
                                                <div class="fw-semibold">{{ $approval->approver_position ?: '-' }}</div>
                                            </div>

                                            <div class="col-12">
                                                <div class="text-muted small mb-1">Notification Sent</div>
                                                <div class="fw-semibold">{{ $formatDateTime($approval->notification_sent_at ?? null) }}</div>
                                            </div>

                                            @if ($approval->status === 'approved')
                                                <div class="col-12">
                                                    <div class="text-muted small mb-1">Approved At</div>
                                                    <div class="fw-semibold">{{ $formatDateTime($approval->approved_at ?? null) }}</div>
                                                </div>
                                            @endif

                                            @if ($approval->status === 'rejected')
                                                <div class="col-12">
                                                    <div class="text-muted small mb-1">Rejected At</div>
                                                    <div class="fw-semibold">{{ $formatDateTime($approval->rejected_at ?? null) }}</div>
                                                </div>
                                            @endif

                                            @if (! blank($approval->notes ?? null))
                                                <div class="col-12">
                                                    <div class="text-muted small mb-1">Notes</div>
                                                    <div class="border rounded-3 bg-white p-2 small" style="white-space: pre-line;">{{ $approval->notes }}</div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            Belum ada approval signer.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if ($canApprove && $activeApproval)
            <div class="col-12">
                <div class="content-card mb-4 border border-primary">
                    <div class="content-card-header">
                        <div>
                            <h5 class="content-card-title mb-1">Approval Action</h5>
                            <p class="content-card-subtitle mb-0">
                                Anda adalah signer aktif untuk memo ini. Silakan approve atau reject memo.
                            </p>
                        </div>
                    </div>

                    <div class="content-card-body">
                        <div class="row g-3">
                            <div class="col-lg-5">
                                <div class="border rounded-4 p-3 bg-light-subtle h-100">
                                    <div class="text-muted small mb-1">Active Signer</div>
                                    <div class="fw-bold">
                                        Signer {{ $activeApproval->step_order }} - {{ $approvalStageLabel($activeApproval) }}
                                    </div>
                                    <div class="text-muted mt-1">
                                        {{ $activeApproval->approver_name ?: optional($activeApproval->approver ?? null)->name ?: '-' }}
                                    </div>

                                    <div class="alert alert-info border-0 mt-3 mb-0 small">
                                        Jika di-approve, memo akan otomatis dikirim ke signer berikutnya.
                                        Jika ini signer terakhir, memo akan menjadi approved.
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-7">
                                <div class="border rounded-4 p-3 bg-white h-100">
                                    <form method="POST" action="{{ route('internal-memos.approve', $memo) }}" class="mb-3">
                                        @csrf
                                        @method('PATCH')

                                        <label class="form-label">Approval Notes <span class="text-muted">(optional)</span></label>
                                        <textarea
                                            name="notes"
                                            rows="3"
                                            class="form-control mb-3 @error('notes') is-invalid @enderror"
                                            placeholder="Catatan approval jika ada..."
                                        >{{ old('notes') }}</textarea>

                                        @error('notes')
                                            <div class="invalid-feedback d-block mb-2">{{ $message }}</div>
                                        @enderror

                                        <button
                                            type="button"
                                            class="btn btn-success btn-modern w-100"
                                            data-confirm-submit
                                            data-confirm-title="Approve Internal Memo"
                                            data-confirm-message="Approve internal memo ini? Jika masih ada signer berikutnya, sistem akan otomatis mengirim notifikasi ke signer selanjutnya."
                                            data-confirm-button-text="Ya, Approve Memo"
                                            data-confirm-button-class="btn-success"
                                            data-confirm-icon="bi-check-circle"
                                        >
                                            <i class="bi bi-check-circle me-2"></i>Approve Memo
                                        </button>
                                    </form>

                                   

                                    <form method="POST" action="{{ route('internal-memos.reject', $memo) }}">
                                        @csrf
                                        @method('PATCH')

                                        <label class="form-label">Rejection Notes <span class="text-danger">*</span></label>
                                        <textarea
                                            name="notes"
                                            rows="3"
                                            class="form-control mb-3 @error('notes') is-invalid @enderror"
                                            placeholder="Tuliskan alasan rejection..."
                                            required
                                        >{{ old('notes') }}</textarea>

                                        @error('notes')
                                            <div class="invalid-feedback d-block mb-2">{{ $message }}</div>
                                        @enderror

                                        <button
                                            type="button"
                                            class="btn btn-danger btn-modern w-100"
                                            data-confirm-submit
                                            data-confirm-title="Reject Internal Memo"
                                            data-confirm-message="Reject internal memo ini? Memo akan dikembalikan ke creator/submitter bersama notes rejection."
                                            data-confirm-button-text="Ya, Reject Memo"
                                            data-confirm-button-class="btn-danger"
                                            data-confirm-icon="bi-x-circle"
                                        >
                                            <i class="bi bi-x-circle me-2"></i>Reject Memo
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="col-12">
            <div class="content-card mb-4">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Amount Summary</h5>
                        <p class="content-card-subtitle mb-0">
                            Ringkasan nominal memo berdasarkan budget item, tax treatment, dan status PKP / Non PKP.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="row g-3">
                        <div class="col-xl-2 col-md-4">
                            <div class="border rounded-4 p-3 h-100 bg-light-subtle">
                                <div class="text-muted small mb-1">Tax Treatment</div>
                                <div class="fs-6 fw-bold">{{ $taxTreatmentLabel }}</div>
                            </div>
                        </div>

                        <div class="col-xl-2 col-md-4">
                            <div class="border rounded-4 p-3 h-100 bg-light-subtle">
                                <div class="text-muted small mb-1">Tax Entity</div>
                                <div class="fs-6 fw-bold">{{ $taxEntityTypeLabel }}</div>
                            </div>
                        </div>

                        <div class="col-xl-2 col-md-4">
                            <div class="border rounded-4 p-3 h-100 bg-light-subtle">
                                <div class="text-muted small mb-1">Tax Rate</div>
                                <div class="fs-6 fw-bold">{{ number_format((float) $memo->tax_rate, 2) }}%</div>
                            </div>
                        </div>

                        <div class="col-xl-2 col-md-4">
                            <div class="border rounded-4 p-3 h-100 bg-light-subtle">
                                <div class="text-muted small mb-1">Subtotal</div>
                                <div class="fs-5 fw-bold">{{ $formatCurrency($memo->subtotal_amount) }}</div>
                            </div>
                        </div>

                        <div class="col-xl-2 col-md-4">
                            <div class="border rounded-4 p-3 h-100 bg-light-subtle">
                                <div class="text-muted small mb-1">Tax Amount</div>
                                <div class="fs-5 fw-bold">{{ $formatCurrency($memo->tax_amount) }}</div>
                            </div>
                        </div>

                        <div class="col-xl-2 col-md-4">
                            <div class="border rounded-4 p-3 h-100 bg-light-subtle">
                                <div class="text-muted small mb-1">Grand Total</div>
                                <div class="fs-4 fw-bold">{{ $formatCurrency($memo->grand_total_amount) }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-success border-0 mt-3 mb-0 py-2 px-3 small">
                        @if ($memo->tax_entity_type === 'non_pkp')
                            Non PKP dipilih, tax rate otomatis 0 dan grand total sama dengan subtotal.
                        @elseif ($memo->tax_treatment === 'include')
                            Tax Include dipilih, grand total mengikuti subtotal dan tax dihitung sebagai bagian dari nominal.
                        @else
                            Tax Not Include dipilih, tax ditambahkan ke subtotal.
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if ($canSubmit || in_array($memo->status, ['draft', 'rejected', 'waiting_acknowledgement', 'waiting_approval', 'approved'], true))
            <div class="col-12 d-none">
                <div class="content-card mb-4">
                    <div class="content-card-header">
                        <div>
                            <h5 class="content-card-title mb-1">Memo Actions</h5>
                            <p class="content-card-subtitle mb-0">
                                Action tambahan untuk publish atau cancel memo.
                            </p>
                        </div>
                    </div>

                    <div class="content-card-body">
                        <div class="d-flex justify-content-end align-items-center gap-2 flex-wrap">
                            @if ($canSubmit)
                                <form method="POST" action="{{ route('internal-memos.submit', $memo) }}">
                                    @csrf
                                    @method('PATCH')

                                    <button
                                        type="button"
                                        class="btn btn-primary btn-modern"
                                        data-confirm-submit
                                        data-confirm-title="Publish Internal Memo"
                                        data-confirm-message="Publish memo dan kirim notifikasi ke signer pertama?"
                                        data-confirm-button-text="Ya, Publish Memo"
                                        data-confirm-button-class="btn-primary"
                                        data-confirm-icon="bi-send"
                                    >
                                        <i class="bi bi-send me-2"></i>Publish Memo
                                    </button>
                                </form>
                            @endif

                            @if (in_array($memo->status, ['draft', 'rejected', 'waiting_acknowledgement', 'waiting_approval', 'approved'], true))
                                <form method="POST" action="{{ route('internal-memos.cancel', $memo) }}">
                                    @csrf
                                    @method('PATCH')

                                    <button
                                        type="button"
                                        class="btn btn-outline-danger btn-modern"
                                        data-confirm-submit
                                        data-confirm-title="Cancel Internal Memo"
                                        data-confirm-message="Cancel memo ini? Memo yang sudah dicancel tidak akan lanjut ke proses approval."
                                        data-confirm-button-text="Ya, Cancel Memo"
                                        data-confirm-button-class="btn-danger"
                                        data-confirm-icon="bi-slash-circle"
                                    >
                                        <i class="bi bi-slash-circle me-2"></i>Cancel Memo
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<div class="modal fade" id="memoConfirmModal" tabindex="-1" aria-labelledby="memoConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center gap-3">
                    <span class="memo-confirm-icon" id="memoConfirmIconWrap">
                        <i class="bi bi-question-circle" id="memoConfirmIcon"></i>
                    </span>

                    <div>
                        <h5 class="modal-title fw-bold mb-1" id="memoConfirmModalLabel">
                            Konfirmasi Action
                        </h5>
                        <div class="text-muted small">
                            Pastikan data memo sudah sesuai sebelum melanjutkan.
                        </div>
                    </div>
                </div>

                <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pt-3">
                <p class="mb-0 memo-confirm-message" id="memoConfirmMessage">
                    Apakah Anda yakin ingin melanjutkan action ini?
                </p>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light btn-modern" data-bs-dismiss="modal">
                    Batal
                </button>

                <button type="button" class="btn btn-primary btn-modern" id="memoConfirmSubmitBtn">
                    Ya, Lanjutkan
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modalElement = document.getElementById('memoConfirmModal');
        const modalTitle = document.getElementById('memoConfirmModalLabel');
        const modalMessage = document.getElementById('memoConfirmMessage');
        const modalIcon = document.getElementById('memoConfirmIcon');
        const confirmButton = document.getElementById('memoConfirmSubmitBtn');

        let targetForm = null;
        let memoConfirmModal = null;

        if (modalElement && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            memoConfirmModal = new bootstrap.Modal(modalElement);
        }

        function resetConfirmButtonClass() {
            if (! confirmButton) {
                return;
            }

            confirmButton.className = 'btn btn-primary btn-modern';
        }

        document.querySelectorAll('[data-confirm-submit]').forEach(function (button) {
            button.addEventListener('click', function () {
                const form = button.closest('form');

                if (! form) {
                    return;
                }

                if (typeof form.reportValidity === 'function' && ! form.reportValidity()) {
                    return;
                }

                targetForm = form;

                const title = button.dataset.confirmTitle || 'Konfirmasi Action';
                const message = button.dataset.confirmMessage || 'Apakah Anda yakin ingin melanjutkan action ini?';
                const buttonText = button.dataset.confirmButtonText || 'Ya, Lanjutkan';
                const buttonClass = button.dataset.confirmButtonClass || 'btn-primary';
                const iconClass = button.dataset.confirmIcon || 'bi-question-circle';

                if (! memoConfirmModal) {
                    if (window.confirm(message)) {
                        form.submit();
                    }

                    return;
                }

                if (modalTitle) {
                    modalTitle.textContent = title;
                }

                if (modalMessage) {
                    modalMessage.textContent = message;
                }

                if (modalIcon) {
                    modalIcon.className = 'bi ' + iconClass;
                }

                if (confirmButton) {
                    resetConfirmButtonClass();
                    confirmButton.classList.remove('btn-primary');
                    confirmButton.classList.add(buttonClass);
                    confirmButton.textContent = buttonText;
                    confirmButton.disabled = false;
                }

                memoConfirmModal.show();
            });
        });

        confirmButton?.addEventListener('click', function () {
            if (! targetForm) {
                return;
            }

            confirmButton.disabled = true;
            confirmButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Memproses...';

            if (typeof targetForm.requestSubmit === 'function') {
                targetForm.requestSubmit();
                return;
            }

            targetForm.submit();
        });

        modalElement?.addEventListener('hidden.bs.modal', function () {
            targetForm = null;

            if (confirmButton) {
                confirmButton.disabled = false;
                confirmButton.textContent = 'Ya, Lanjutkan';
                resetConfirmButtonClass();
            }

            if (modalIcon) {
                modalIcon.className = 'bi bi-question-circle';
            }
        });
    });
</script>
@endpush

@endsection