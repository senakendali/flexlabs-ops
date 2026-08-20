@extends('layouts.app-dashboard')

@section('title', 'Group Registration Detail')

@section('content')
@php
    $order = $groupRegistration->order;
    $assignedSeats = $groupRegistration->participants
        ->where('status', '!=', 'cancelled')
        ->count();
    $availableSeats = max(0, $groupRegistration->quantity - $assignedSeats);
    $paidAmount = $order
        ? $order->paymentSchedules
            ->flatMap(fn ($schedule) => $schedule->payments)
            ->where('status', 'paid')
            ->sum('amount')
        : 0;
    $statusClass = match ($groupRegistration->status) {
        'confirmed', 'completed' => 'bg-success-subtle text-success-emphasis border-success-subtle',
        'cancelled' => 'bg-danger-subtle text-danger-emphasis border-danger-subtle',
        'pending' => 'bg-warning-subtle text-warning-emphasis border-warning-subtle',
        default => 'bg-secondary-subtle text-secondary-emphasis border-secondary-subtle',
    };
@endphp

<div class="container-fluid px-4 py-4 group-registration-show-page">
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Sales & Payment</div>
                <h1 class="page-title mb-2">{{ $groupRegistration->registration_number }}</h1>
                <p class="page-subtitle mb-0">Review buyer, course, seat allocation, WHT, schedules, and Xendit payment links.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('group-registrations.index') }}" class="btn btn-light border btn-modern"><i class="bi bi-arrow-left me-1"></i>Back</a>
                <a href="{{ route('group-registrations.edit', $groupRegistration) }}" class="btn btn-light btn-modern"><i class="bi bi-pencil-square me-1"></i>Edit Metadata</a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach ([
            ['bi-people-fill', 'Purchased Seats', $groupRegistration->quantity, $assignedSeats.' assigned · '.$availableSeats.' available'],
            ['bi-receipt-cutoff', 'Invoice Total', 'Rp '.number_format((float)$groupRegistration->invoice_total,0,',','.'), 'Gross value including WHT gross-up.'],
            ['bi-credit-card-fill', 'Net Payment', 'Rp '.number_format((float)$groupRegistration->net_payable,0,',','.'), 'Amount payable to FlexLabs/Xendit.'],
            ['bi-cash-coin', 'Paid Amount', 'Rp '.number_format((float)$paidAmount,0,',','.'), 'Confirmed payments received.'],
        ] as $card)
            <div class="col-xl-3 col-md-6"><div class="stat-card h-100"><div class="stat-card-top"><div class="stat-icon-wrap"><i class="bi {{ $card[0] }}"></i></div><div><div class="stat-title">{{ $card[1] }}</div><div class="stat-value">{{ $card[2] }}</div></div></div><div class="stat-description">{{ $card[3] }}</div></div></div>
        @endforeach
    </div>

    <div class="row g-4 align-items-start">
        <div class="col-xl-8">
            <div class="content-card mb-4">
                <div class="content-card-header"><div><h5 class="content-card-title mb-1">Buyer & Registration</h5><p class="content-card-subtitle mb-0">Primary buyer and course information.</p></div><span class="badge rounded-pill border {{ $statusClass }}">{{ ucfirst($groupRegistration->status) }}</span></div>
                <div class="content-card-body">
                    <div class="row g-4">
                        <div class="col-md-6"><div class="detail-label">Buyer</div><div class="detail-value">{{ $groupRegistration->buyer_name }}</div><div class="text-muted small mt-1">{{ ucfirst($groupRegistration->buyer_type) }}</div></div>
                        <div class="col-md-6"><div class="detail-label">Contact</div><div class="detail-value">{{ $groupRegistration->buyer_email ?: '-' }}</div><div class="text-muted small mt-1">{{ $groupRegistration->buyer_phone ?: '-' }}</div></div>
                        <div class="col-md-6"><div class="detail-label">Program</div><div class="detail-value">{{ $groupRegistration->batch?->program?->name ?: '-' }}</div></div>
                        <div class="col-md-6"><div class="detail-label">Batch</div><div class="detail-value">{{ $groupRegistration->batch?->name ?: '-' }}</div><div class="text-muted small mt-1">{{ $groupRegistration->batch?->start_date?->format('d M Y') }} – {{ $groupRegistration->batch?->end_date?->format('d M Y') }}</div></div>
                    </div>
                </div>
            </div>

            <div class="content-card mb-4">
                <div class="content-card-header"><div><h5 class="content-card-title mb-1">Payment Schedule</h5><p class="content-card-subtitle mb-0">Gross invoice allocation, withholding, net payment, and payment links.</p></div></div>
                <div class="content-card-body">
                    @if ($order?->paymentSchedules?->isNotEmpty())
                        <div class="table-responsive"><table class="table table-hover align-middle admin-table mb-0"><thead><tr><th>Schedule</th><th>Due Date</th><th>Gross</th><th>WHT</th><th>Net</th><th>Status</th><th>Payment Link</th></tr></thead><tbody>
                        @foreach ($order->paymentSchedules as $schedule)
                            @php $payment = $schedule->payments->sortByDesc('id')->first(); @endphp
                            <tr><td><div class="fw-semibold">{{ $schedule->title }}</div><div class="small text-muted">{{ $payment?->invoice_number ?: '-' }}</div></td><td>{{ $schedule->due_date?->format('d M Y') ?: '-' }}</td><td>Rp {{ number_format((float)($schedule->gross_amount ?? $schedule->amount),0,',','.') }}</td><td>Rp {{ number_format((float)$schedule->wht_amount,0,',','.') }}</td><td class="fw-semibold">Rp {{ number_format((float)($schedule->net_amount ?? $schedule->amount),0,',','.') }}</td><td><span class="badge rounded-pill bg-light text-dark border">{{ ucfirst($schedule->status) }}</span></td><td>@if ($payment?->payment_url)<a href="{{ $payment->payment_url }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-box-arrow-up-right me-1"></i>Open</a>@else<span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle">Link unavailable</span>@endif</td></tr>
                        @endforeach
                        </tbody></table></div>
                    @else
                        <div class="empty-inline-state">No payment schedules available.</div>
                    @endif
                </div>
            </div>

            <div class="content-card">
                <div class="content-card-header"><div><h5 class="content-card-title mb-1">Participants</h5><p class="content-card-subtitle mb-0">Assigned participants and enrollment status.</p></div><span class="badge rounded-pill bg-light text-dark border">{{ $assignedSeats }} / {{ $groupRegistration->quantity }} assigned</span></div>
                <div class="content-card-body">
                    @if ($groupRegistration->participants->isNotEmpty())
                        <div class="table-responsive"><table class="table table-hover align-middle admin-table mb-0"><thead><tr><th>Participant</th><th>Contact</th><th>Seat Status</th><th>Enrollment</th></tr></thead><tbody>@foreach ($groupRegistration->participants as $participant)<tr><td class="fw-semibold">{{ $participant->student?->full_name ?: '-' }}</td><td><div>{{ $participant->student?->email ?: '-' }}</div><div class="small text-muted">{{ $participant->student?->phone ?: '-' }}</div></td><td><span class="badge rounded-pill bg-light text-dark border">{{ ucfirst($participant->status) }}</span></td><td>{{ $participant->studentEnrollment ? ucfirst($participant->studentEnrollment->status) : 'Not enrolled' }}</td></tr>@endforeach</tbody></table></div>
                    @else
                        <div class="empty-inline-state">Participant names have not been assigned yet. {{ $groupRegistration->quantity }} seats remain available.</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="sticky-top" style="top:92px">
                <div class="content-card mb-4">
                    <div class="content-card-header"><div><h5 class="content-card-title mb-1">Financial Summary</h5><p class="content-card-subtitle mb-0">Stored transaction values.</p></div></div>
                    <div class="content-card-body"><div class="finance-box"><div><span>Price per Seat</span><strong>Rp {{ number_format((float)$groupRegistration->price_per_seat,0,',','.') }}</strong></div><div><span>Original Price</span><strong>Rp {{ number_format((float)$groupRegistration->original_price,0,',','.') }}</strong></div><div><span>Discount</span><strong class="text-danger">- Rp {{ number_format((float)$groupRegistration->discount,0,',','.') }}</strong></div><div><span>Service Amount</span><strong>Rp {{ number_format((float)$groupRegistration->service_amount,0,',','.') }}</strong></div>@if($groupRegistration->buyer_type==='company')<div><span>Gross-up PPh 23</span><strong>Rp {{ number_format((float)$groupRegistration->wht_amount,0,',','.') }}</strong></div>@endif<div class="total"><span>Invoice Total</span><strong>Rp {{ number_format((float)$groupRegistration->invoice_total,0,',','.') }}</strong></div><div class="net"><span>Net Payment</span><strong>Rp {{ number_format((float)$groupRegistration->net_payable,0,',','.') }}</strong></div></div></div>
                </div>

                <div class="content-card mb-4">
                    <div class="content-card-header"><div><h5 class="content-card-title mb-1">WHT Administration</h5><p class="content-card-subtitle mb-0">PPh 23 withholding details.</p></div></div>
                    <div class="content-card-body">
                        @if ($groupRegistration->buyer_type === 'company')
                            <div class="simple-list-item"><span>Rate</span><strong>{{ $groupRegistration->wht_rate }}%</strong></div><div class="simple-list-item"><span>Status</span><strong>{{ ucfirst(str_replace('_',' ',$groupRegistration->wht_status)) }}</strong></div><div class="simple-list-item"><span>Certificate</span><strong>{{ $groupRegistration->wht_certificate_number ?: 'Awaiting document' }}</strong></div>
                        @else
                            <div class="empty-inline-state">WHT is not applicable to an individual buyer.</div>
                        @endif
                    </div>
                </div>

                <div class="content-card"><div class="content-card-header"><div><h5 class="content-card-title mb-1">Audit Information</h5></div></div><div class="content-card-body"><div class="simple-list-item"><span>Created By</span><strong>{{ $groupRegistration->createdBy?->name ?: '-' }}</strong></div><div class="simple-list-item"><span>Created At</span><strong>{{ $groupRegistration->created_at?->format('d M Y H:i') }}</strong></div><div class="simple-list-item"><span>Updated By</span><strong>{{ $groupRegistration->updatedBy?->name ?: '-' }}</strong></div><div class="mt-3"><div class="detail-label">Notes</div><p class="mb-0 text-muted">{{ $groupRegistration->notes ?: '-' }}</p></div></div></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.detail-label{font-size:.78rem;text-transform:uppercase;letter-spacing:.05em;color:#8a8f98;font-weight:700;margin-bottom:.35rem}.detail-value{font-weight:700;color:#25272b}.finance-box>div{display:flex;justify-content:space-between;gap:1rem;padding:.75rem 0;border-bottom:1px solid #edf0f2}.finance-box>div:last-child{border-bottom:0}.finance-box span{color:#6b7280}.finance-box .total{color:#5B3E8E;font-size:1.05rem}.finance-box .net{background:#faf9fd;border:1px solid #ece7f7;border-radius:12px;padding:.85rem;margin-top:.6rem}.empty-inline-state{padding:1.25rem;border:1px dashed #d8dce2;border-radius:16px;color:#6b7280;text-align:center;background:#fafbfc}

.group-registration-show-page .content-card-body:has(> .simple-list-item) {
    display: grid;
    gap: .75rem;
}

.group-registration-show-page .simple-list-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    min-height: 62px;
    padding: 1rem 1.15rem !important;
    border: 1px solid #e8eaf0 !important;
    border-radius: 18px;
    background: #ffffff;
}

.group-registration-show-page .simple-list-item:last-child {
    border: 1px solid #e8eaf0 !important;
}

.group-registration-show-page .simple-list-item span {
    min-width: 0;
    color: #6b7280;
    font-size: .88rem;
    font-weight: 600;
    line-height: 1.35;
}

.group-registration-show-page .simple-list-item strong {
    flex: 0 0 auto;
    color: #202533;
    font-size: .95rem;
    font-weight: 700;
    line-height: 1.35;
    text-align: right;
}

@media (max-width: 420px) {
    .group-registration-show-page .simple-list-item {
        align-items: flex-start;
        flex-direction: column;
        gap: .35rem;
    }

    .group-registration-show-page .simple-list-item strong {
        width: 100%;
        text-align: left;
    }
}
</style>
@endpush