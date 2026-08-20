@extends('layouts.app-dashboard')

@section('title', 'Create Group Registration')

@section('content')
<div class="container-fluid px-4 py-4 group-registration-form-page">
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Sales & Payment</div>
                <h1 class="page-title mb-2">Create Group Registration</h1>
                <p class="page-subtitle mb-0">Create a multi-participant order with automatic pricing, company WHT, payment schedules, and Xendit links.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('group-registrations.index') }}" class="btn btn-light border btn-modern"><i class="bi bi-arrow-left me-1"></i>Back</a>
                <button type="button" id="submitRegistrationTop" class="btn btn-light btn-modern"><i class="bi bi-check-circle me-1"></i>Create Registration</button>
            </div>
        </div>
    </div>

    <div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1090;"></div>

    <div class="content-card mb-4">
        <div class="content-card-body">
            <div class="row g-3 align-items-stretch">
                <div class="col-lg-8 d-flex">
                    <div class="progress-summary-wrap w-100">
                        <div class="d-flex justify-content-between align-items-center gap-3 mb-2 flex-wrap">
                            <div><div class="progress-summary-label">Form Progress</div><div class="progress-summary-subtitle">Complete buyer, pricing, payment plan, and optional participant assignment.</div></div>
                            <div class="progress-summary-count"><span id="completedSectionCount">0</span> of 4 sections ready</div>
                        </div>
                        <div class="progress progress-modern"><div id="sectionProgressBar" class="progress-bar" style="width:0%"></div></div>
                    </div>
                </div>
                <div class="col-lg-4 d-flex">
                    <div class="status-pill-card w-100">
                        <div class="status-pill-label">Registration Type</div>
                        <div class="status-pill-value" id="registrationTypeSummary">Individual Group</div>
                        <div class="status-pill-help" id="registrationTypeHelp">PPh 23 is not applicable.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form id="groupRegistrationForm" action="{{ route('group-registrations.store') }}" method="POST" novalidate>
        @csrf
        <div class="row g-4">
            <div class="col-xl-3">
                <div class="section-nav-card sticky-top" style="top:92px">
                    <div class="section-nav-header"><div class="section-nav-title">Form Sections</div><div class="section-nav-subtitle">Complete the registration setup.</div></div>
                    <div class="nav flex-column nav-pills custom-section-nav" role="tablist">
                        @foreach ([
                            ['buyer', 'Buyer & Course', 'Buyer, batch, seats', 'bi-person-vcard-fill'],
                            ['pricing', 'Pricing & WHT', 'Discount and tax', 'bi-calculator-fill'],
                            ['payment', 'Payment Plan', 'Full or installment', 'bi-credit-card-fill'],
                            ['participants', 'Participants', 'Assign now or later', 'bi-people-fill'],
                        ] as $i => $tab)
                            <button class="nav-link {{ $i === 0 ? 'active' : '' }}" data-bs-toggle="pill" data-bs-target="#tab-{{ $tab[0] }}" type="button">
                                <span class="nav-link-content"><span class="nav-link-icon"><i class="bi {{ $tab[3] }}"></i></span><span class="nav-link-text"><span class="nav-link-title">{{ $tab[1] }}</span><span class="nav-link-subtitle">{{ $tab[2] }}</span></span></span>
                                <span class="section-check" data-section-indicator="{{ $tab[0] }}"><i class="bi bi-circle"></i></span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="tab-buyer">
                        <div class="content-card section-card mb-4">
                            <div class="content-card-header section-card-header"><div><h5 class="content-card-title mb-1">Buyer & Course</h5><p class="content-card-subtitle mb-0">Choose who pays, the target batch, and purchased seats.</p></div><div class="section-status-badge" data-section-badge="buyer"><i class="bi bi-hourglass-split me-1"></i>Need Input</div></div>
                            <div class="content-card-body">
                                <div class="mb-4">
                                    <label class="form-label d-block">Buyer Type <span class="text-danger">*</span></label>
                                    <div class="row g-3">
                                        <div class="col-md-6"><label class="choice-card w-100"><input class="form-check-input buyer-type-radio" type="radio" name="buyer_type" value="individual" checked><span><strong>Individual</strong><small>One person purchases multiple seats.</small></span></label></div>
                                        <div class="col-md-6"><label class="choice-card w-100"><input class="form-check-input buyer-type-radio" type="radio" name="buyer_type" value="company"><span><strong>Company</strong><small>Gross-up PPh 23 automatically applies.</small></span></label></div>
                                    </div>
                                    <div class="invalid-feedback d-block error-text" data-error-for="buyer_type"></div>
                                </div>

                                <div id="individualBuyerFields" class="row g-3 mb-3">
                                    <div class="col-12">
                                        <label class="form-label">Existing Student <span class="text-muted fw-normal">(optional)</span></label>
                                        <select name="buyer_student_id" id="buyerStudentId" class="form-select">
                                            <option value="">Buyer is not an existing student</option>
                                            @foreach ($students as $student)
                                                <option
                                                    value="{{ $student->id }}"
                                                    data-name="{{ $student->full_name }}"
                                                    data-email="{{ $student->email }}"
                                                    data-phone="{{ $student->phone }}"
                                                >{{ $student->full_name }} — {{ $student->email ?: $student->phone }}</option>
                                            @endforeach
                                        </select>
                                        <div class="form-text">Optional. Select only when the payer is already registered as a student.</div>
                                        <div class="invalid-feedback error-text" data-error-for="buyer_student_id"></div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Buyer Name <span class="text-danger">*</span></label>
                                        <input type="text" name="buyer_name" id="buyerName" class="form-control" placeholder="Person responsible for the purchase">
                                        <div class="invalid-feedback error-text" data-error-for="buyer_name"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Buyer Email</label>
                                        <input type="email" name="buyer_email" id="buyerEmail" class="form-control" placeholder="buyer@example.com">
                                        <div class="invalid-feedback error-text" data-error-for="buyer_email"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Buyer Phone</label>
                                        <input type="text" name="buyer_phone" id="buyerPhone" class="form-control" placeholder="08xxxxxxxxxx">
                                        <div class="invalid-feedback error-text" data-error-for="buyer_phone"></div>
                                    </div>
                                </div>

                                <div id="companyBuyerFields" class="d-none">
                                    <div class="row g-3 mb-3">
                                        <div class="col-12"><label class="form-label">Existing Company</label><select name="company_id" id="companyId" class="form-select"><option value="">Create new company</option>@foreach ($companies as $company)<option value="{{ $company->id }}">{{ $company->name }}{{ $company->tax_id ? ' — '.$company->tax_id : '' }}</option>@endforeach</select><div class="invalid-feedback error-text" data-error-for="company_id"></div></div>
                                    </div>
                                    <div id="newCompanyFields" class="border rounded-4 p-3 bg-light-subtle">
                                        <div class="small fw-semibold text-uppercase text-muted mb-3">New Company</div>
                                        <div class="row g-3">
                                            <div class="col-md-8"><label class="form-label">Company Name <span class="text-danger">*</span></label><input name="company[name]" class="form-control" placeholder="PT Example Indonesia"><div class="invalid-feedback error-text" data-error-for="company.name"></div></div>
                                            <div class="col-md-4"><label class="form-label">NPWP / Tax ID</label><input name="company[tax_id]" class="form-control"><div class="invalid-feedback error-text" data-error-for="company.tax_id"></div></div>
                                            <div class="col-md-6"><label class="form-label">Company Email</label><input type="email" name="company[email]" class="form-control"></div>
                                            <div class="col-md-6"><label class="form-label">Company Phone</label><input name="company[phone]" class="form-control"></div>
                                            <div class="col-12"><label class="form-label">Address</label><textarea name="company[address]" class="form-control" rows="2"></textarea></div>
                                            <div class="col-md-4"><label class="form-label">PIC Name</label><input name="company[pic_name]" class="form-control"></div>
                                            <div class="col-md-4"><label class="form-label">PIC Email</label><input type="email" name="company[pic_email]" class="form-control"></div>
                                            <div class="col-md-4"><label class="form-label">PIC Phone</label><input name="company[pic_phone]" class="form-control"></div>
                                        </div>
                                    </div>
                                </div>

                                <hr class="my-4">
                                <div class="row g-3">
                                    <div class="col-md-8"><label class="form-label">Program & Batch <span class="text-danger">*</span></label><select name="batch_id" id="batchId" class="form-select"><option value="">Select batch</option>@foreach ($batches as $batch)<option value="{{ $batch->id }}" data-price="{{ (float) $batch->price }}">{{ $batch->program?->name }} — {{ $batch->name }} (Rp {{ number_format((float) $batch->price, 0, ',', '.') }})</option>@endforeach</select><div class="invalid-feedback error-text" data-error-for="batch_id"></div></div>
                                    <div class="col-md-4"><label class="form-label">Purchased Seats <span class="text-danger">*</span></label><input type="number" name="quantity" id="quantity" class="form-control" min="2" max="1000" value="2"><div class="invalid-feedback error-text" data-error-for="quantity"></div></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-pricing">
                        <div class="content-card section-card mb-4">
                            <div class="content-card-header section-card-header"><div><h5 class="content-card-title mb-1">Pricing & WHT</h5><p class="content-card-subtitle mb-0">Price comes from the selected batch; company WHT is grossed up automatically.</p></div><div class="section-status-badge" data-section-badge="pricing"><i class="bi bi-hourglass-split me-1"></i>Need Input</div></div>
                            <div class="content-card-body">
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6"><label class="form-label">Discount Type</label><select name="discount_type" id="discountType" class="form-select"><option value="none">No Discount</option><option value="fixed">Fixed Amount</option><option value="percentage">Percentage</option></select></div>
                                    <div class="col-md-6"><label class="form-label">Discount Value</label><input type="number" name="discount_value" id="discountValue" class="form-control" min="0" step="0.01" value="0"></div>
                                </div>
                                <div class="calculation-panel">
                                    <div class="calculation-row"><span>Price per Seat</span><strong id="pricePerSeatText">Rp 0</strong></div>
                                    <div class="calculation-row"><span>Original Price</span><strong id="originalPriceText">Rp 0</strong></div>
                                    <div class="calculation-row"><span>Discount</span><strong class="text-danger" id="discountText">- Rp 0</strong></div>
                                    <div class="calculation-row"><span>Service Amount</span><strong id="serviceAmountText">Rp 0</strong></div>
                                    <div class="calculation-row company-wht-row d-none"><span>Gross-up PPh 23 (2%)</span><strong id="whtAmountText">Rp 0</strong></div>
                                    <div class="calculation-row total"><span>Invoice Total</span><strong id="invoiceTotalText">Rp 0</strong></div>
                                    <div class="calculation-row net"><span>Net Payment to FlexLabs/Xendit</span><strong id="netPayableText">Rp 0</strong></div>
                                </div>
                                <div id="whtExplanation" class="alert alert-warning border-0 rounded-4 mt-3 d-none"><i class="bi bi-info-circle-fill me-2"></i>Company pays the net amount to FlexLabs and deposits PPh 23 separately, then provides the withholding certificate.</div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-payment">
                        <div class="content-card section-card mb-4">
                            <div class="content-card-header section-card-header"><div><h5 class="content-card-title mb-1">Payment Plan</h5><p class="content-card-subtitle mb-0">Set full payment or installments. The final installment balances automatically.</p></div><div class="section-status-badge" data-section-badge="payment"><i class="bi bi-hourglass-split me-1"></i>Need Input</div></div>
                            <div class="content-card-body">
                                <div class="payment-settings-row mb-4">
                                    <div class="payment-setting-field"><label class="form-label">Payment Scheme</label><select name="payment_scheme" id="paymentScheme" class="form-select"><option value="full">Full Payment</option><option value="installment">Installment</option></select></div>
                                    <div class="payment-setting-field" id="installmentCountWrap" style="display:none"><label class="form-label">Number of Terms</label><select id="installmentCount" class="form-select">@for ($i=2;$i<=12;$i++)<option value="{{ $i }}">{{ $i }} Terms</option>@endfor</select></div>
                                    <div class="payment-setting-field"><label class="form-label">Link Validity after Due Date</label><div class="input-group"><input type="number" name="invoice_expiry_days" class="form-control" min="1" max="365" value="3"><span class="input-group-text">days</span></div></div>
                                </div>
                                <div id="paymentTerms" class="d-grid gap-3"></div>
                                <div class="invalid-feedback d-block error-text" data-error-for="payment_terms"></div>
                                <div class="mt-4"><label class="form-label">Payment Notes</label><textarea name="payment_notes" class="form-control" rows="3" placeholder="Optional notes added after the automatic payment label."></textarea></div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="tab-participants">
                        <div class="content-card section-card mb-4">
                            <div class="content-card-header section-card-header"><div><h5 class="content-card-title mb-1">Participant Assignment</h5><p class="content-card-subtitle mb-0">Participants are optional now and can be completed later.</p></div><div class="section-status-badge optional" data-section-badge="participants"><i class="bi bi-stars me-1"></i>Optional</div></div>
                            <div class="content-card-body">
                                <div class="alert alert-info border-0 rounded-4"><i class="bi bi-info-circle-fill me-2"></i>Purchased seats can remain unassigned. Enrollment is created later when participant activation is processed.</div>
                                <div id="participantRows" class="d-grid gap-3"></div>
                                <button type="button" id="addParticipantBtn" class="btn btn-outline-primary mt-3"><i class="bi bi-person-plus me-2"></i>Add Participant</button>
                                <div class="invalid-feedback d-block error-text" data-error-for="participants"></div>
                                <div class="mt-4"><label class="form-label">Internal Notes</label><textarea name="notes" class="form-control" rows="4"></textarea></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3">
                <div class="sticky-top" style="top:92px">
                    <div class="content-card mb-4">
                        <div class="content-card-header"><div><h5 class="content-card-title mb-1">Live Summary</h5><p class="content-card-subtitle mb-0">Review before creating.</p></div></div>
                        <div class="content-card-body">
                            <div class="simple-list"><div class="simple-list-item"><div class="simple-list-title">Buyer Type</div><div class="simple-list-value" id="summaryBuyerType">Individual</div></div><div class="simple-list-item"><div class="simple-list-title">Seats</div><div class="simple-list-value" id="summarySeats">2</div></div><div class="simple-list-item"><div class="simple-list-title">Service Amount</div><div class="simple-list-value" id="summaryService">Rp 0</div></div><div class="simple-list-item"><div class="simple-list-title">PPh 23</div><div class="simple-list-value" id="summaryWht">Not applicable</div></div><div class="simple-list-item"><div class="simple-list-title">Net Payment</div><div class="simple-list-value" id="summaryNet">Rp 0</div></div><div class="simple-list-item"><div class="simple-list-title">Assigned</div><div class="simple-list-value" id="summaryParticipants">0 / 2</div></div></div>
                        </div>
                    </div>
                    <div class="content-card"><div class="content-card-body"><button type="button" id="submitRegistrationBottom" class="btn btn-primary btn-modern w-100"><span class="default-text"><i class="bi bi-check-circle me-2"></i>Create Registration</span><span class="loading-text d-none"><span class="spinner-border spinner-border-sm me-2"></span>Creating...</span></button><p class="small text-muted mt-3 mb-0">Payment links are generated after the database transaction succeeds.</p></div></div>
                </div>
            </div>
        </div>
    </form>
</div>

<template id="participantTemplate">
    <div class="participant-row border rounded-4 p-3 bg-light-subtle">
        <div class="participant-input-row">
            <div class="participant-select-wrap">
                <label class="form-label">Student</label>
                <select class="form-select participant-select">
                    <option value="">Select participant</option>
                    @foreach ($students as $student)
                        <option value="{{ $student->id }}">{{ $student->full_name }} — {{ $student->email ?: $student->phone }}</option>
                    @endforeach
                </select>
            </div>
            <button type="button" class="btn btn-outline-danger remove-participant" aria-label="Remove participant" title="Remove participant">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>
</template>
@endsection

@push('styles')
<style>
.choice-card{display:flex;gap:.85rem;align-items:flex-start;border:1px solid #e5e7eb;border-radius:16px;padding:1rem;cursor:pointer;background:#fff}.choice-card:has(input:checked){border-color:#5B3E8E;box-shadow:0 0 0 3px rgba(91,62,142,.08)}.choice-card span{display:flex;flex-direction:column}.choice-card small{color:#6b7280;margin-top:.2rem}.calculation-panel{border:1px solid #ece7f7;background:#faf9fd;border-radius:18px;padding:1rem 1.25rem}.calculation-row{display:flex;justify-content:space-between;gap:1rem;padding:.7rem 0;border-bottom:1px dashed #ddd5ec}.calculation-row:last-child{border-bottom:0}.calculation-row.total{font-size:1.05rem;color:#5B3E8E}.calculation-row.net{background:#fff;border-radius:12px;padding:.9rem;margin-top:.5rem;border:1px solid #ece7f7}.term-card{border:1px solid #e8e3f0;border-radius:16px;padding:1rem;background:#fff}.term-label{font-weight:700;color:#5B3E8E}

.group-registration-form-page .simple-list {
    display: grid;
    gap: .75rem;
}

.group-registration-form-page .simple-list-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    min-height: 64px;
    padding: 1rem 1.15rem !important;
    border: 1px solid #e8eaf0 !important;
    border-radius: 18px;
    background: #ffffff;
}

.group-registration-form-page .simple-list-item:last-child {
    border: 1px solid #e8eaf0 !important;
}

.group-registration-form-page .simple-list-title {
    min-width: 0;
    color: #6b7280;
    font-size: .88rem;
    font-weight: 600;
    line-height: 1.35;
}

.group-registration-form-page .simple-list-value {
    flex: 0 0 auto;
    color: #202533;
    font-size: .95rem;
    font-weight: 700;
    line-height: 1.35;
    text-align: right;
    white-space: nowrap;
}

.group-registration-form-page .section-card-header {
    display: grid !important;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: start !important;
    gap: 1rem 1.5rem;
}

.group-registration-form-page .section-card-header > div:first-child {
    min-width: 0;
}

.group-registration-form-page .section-card-header .content-card-subtitle {
    max-width: 100%;
    line-height: 1.55;
}

.group-registration-form-page .section-status-badge {
    align-self: start;
    justify-self: end;
    flex: 0 0 auto;
    margin: 0 !important;
    white-space: nowrap;
}

.group-registration-form-page .payment-settings-row {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    width: 100%;
}

.group-registration-form-page .payment-setting-field {
    flex: 1 1 0;
    min-width: 0;
}

.group-registration-form-page .payment-setting-field .form-label {
    display: flex;
    align-items: flex-end;
    min-height: 48px;
    margin-bottom: .55rem;
    line-height: 1.35;
}

.group-registration-form-page .payment-setting-field .form-select,
.group-registration-form-page .payment-setting-field .form-control,
.group-registration-form-page .payment-setting-field .input-group-text {
    min-height: 48px;
}

.group-registration-form-page .term-card {
    padding: 1.15rem 1.25rem;
    border-color: #e5e0ed;
    box-shadow: 0 4px 14px rgba(32, 37, 51, .035);
}

.group-registration-form-page .term-card .term-label {
    padding-bottom: .75rem;
    border-bottom: 1px solid #f0edf4;
}

.group-registration-form-page .term-card .form-label {
    margin-bottom: .5rem;
}

.group-registration-form-page .participant-input-row {
    display: flex;
    align-items: flex-end;
    gap: .75rem;
    width: 100%;
}

.group-registration-form-page .participant-select-wrap {
    flex: 1 1 auto;
    min-width: 0;
}

.group-registration-form-page .participant-select-wrap .form-label {
    margin-bottom: .5rem;
}

.group-registration-form-page .participant-select {
    width: 100%;
    min-height: 48px;
}

.group-registration-form-page .remove-participant {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 48px;
    width: 48px;
    height: 48px;
    margin: 0 !important;
    padding: 0 !important;
    border-radius: 12px;
}

.group-registration-form-page .remove-participant i {
    margin: 0 !important;
    font-size: 1rem;
    line-height: 1;
}

@media (max-width: 767.98px) {
    .group-registration-form-page .section-card-header {
        grid-template-columns: minmax(0, 1fr) auto;
        gap: .75rem;
    }

    .group-registration-form-page .payment-settings-row {
        flex-direction: column;
    }

    .group-registration-form-page .payment-setting-field {
        flex: 0 0 auto;
        width: 100%;
    }

    .group-registration-form-page .payment-setting-field .form-label {
        min-height: 0;
    }

    .group-registration-form-page .participant-input-row {
        align-items: stretch;
        flex-direction: column;
    }

    .group-registration-form-page .remove-participant {
        align-self: flex-end;
    }
}

@media (max-width: 1399.98px) and (min-width: 1200px) {
    .group-registration-form-page .simple-list-item {
        align-items: flex-start;
        flex-direction: column;
        gap: .35rem;
    }

    .group-registration-form-page .simple-list-value {
        width: 100%;
        text-align: left;
    }
}

/*
|--------------------------------------------------------------------------
| Group Registration section navigation
|--------------------------------------------------------------------------
| Selector dibuat spesifik agar style nav global dashboard tidak membuat
| judul dan subtitle kehilangan kontras ketika item hover atau active.
*/
.group-registration-form-page .custom-section-nav {
    gap: .65rem;
    padding: .85rem;
}

.group-registration-form-page .custom-section-nav .nav-link {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .8rem;
    width: 100%;
    min-height: 80px;
    padding: .85rem 1rem;
    border: 1px solid transparent;
    border-radius: 18px;
    background: transparent;
    color: #25272b !important;
    text-align: left;
    transition: background-color .2s ease, border-color .2s ease,
        color .2s ease, transform .2s ease, box-shadow .2s ease;
}

.group-registration-form-page .custom-section-nav .nav-link-content {
    display: flex;
    align-items: center;
    gap: .85rem;
    min-width: 0;
}

.group-registration-form-page .custom-section-nav .nav-link-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 46px;
    width: 46px;
    height: 46px;
    border-radius: 14px;
    background: #f2eef8;
    color: #5B3E8E !important;
    font-size: 1.05rem;
}

.group-registration-form-page .custom-section-nav .nav-link-text {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.group-registration-form-page .custom-section-nav .nav-link-title {
    color: #25272b !important;
    font-size: .92rem;
    font-weight: 700;
    line-height: 1.3;
}

.group-registration-form-page .custom-section-nav .nav-link-subtitle {
    margin-top: .2rem;
    color: #777d87 !important;
    font-size: .78rem;
    font-weight: 500;
    line-height: 1.35;
}

.group-registration-form-page .custom-section-nav .section-check {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 24px;
    width: 24px;
    height: 24px;
    padding: 0 !important;
    border: 0 !important;
    border-radius: 0 !important;
    background: transparent !important;
    box-shadow: none !important;
    color: #9ca3af !important;
    font-size: 1rem;
    line-height: 1;
    overflow: visible;
}

.group-registration-form-page .custom-section-nav .section-check i,
.group-registration-form-page .custom-section-nav .section-check .bi {
    display: inline-block;
    width: auto !important;
    height: auto !important;
    padding: 0 !important;
    border: 0 !important;
    border-radius: 0 !important;
    background: transparent !important;
    box-shadow: none !important;
    color: inherit !important;
    font-size: 1.05rem;
    line-height: 1;
}

.group-registration-form-page .custom-section-nav .nav-link:hover {
    border-color: #dcd2ec;
    background: #f6f3fa;
    color: #5B3E8E !important;
    transform: translateY(-1px);
}

.group-registration-form-page .custom-section-nav .nav-link:hover .nav-link-title {
    color: #5B3E8E !important;
}

.group-registration-form-page .custom-section-nav .nav-link:hover .nav-link-subtitle {
    color: #665b73 !important;
}

.group-registration-form-page .custom-section-nav .nav-link:hover .section-check {
    color: #5B3E8E !important;
}

.group-registration-form-page .custom-section-nav .nav-link.active,
.group-registration-form-page .custom-section-nav .nav-link.active:hover,
.group-registration-form-page .custom-section-nav .nav-link[aria-selected="true"] {
    border-color: #5B3E8E;
    background: #5B3E8E !important;
    color: #ffffff !important;
    box-shadow: 0 10px 24px rgba(91, 62, 142, .2);
    transform: none;
}

.group-registration-form-page .custom-section-nav .nav-link.active .nav-link-title,
.group-registration-form-page .custom-section-nav .nav-link.active:hover .nav-link-title,
.group-registration-form-page .custom-section-nav .nav-link[aria-selected="true"] .nav-link-title {
    color: #ffffff !important;
    opacity: 1 !important;
}

.group-registration-form-page .custom-section-nav .nav-link.active .nav-link-subtitle,
.group-registration-form-page .custom-section-nav .nav-link.active:hover .nav-link-subtitle,
.group-registration-form-page .custom-section-nav .nav-link[aria-selected="true"] .nav-link-subtitle {
    color: rgba(255, 255, 255, .82) !important;
    opacity: 1 !important;
}

.group-registration-form-page .custom-section-nav .nav-link.active .nav-link-icon,
.group-registration-form-page .custom-section-nav .nav-link.active:hover .nav-link-icon,
.group-registration-form-page .custom-section-nav .nav-link[aria-selected="true"] .nav-link-icon {
    background: #ffffff !important;
    color: #5B3E8E !important;
}

.group-registration-form-page .custom-section-nav .nav-link.active .section-check,
.group-registration-form-page .custom-section-nav .nav-link.active:hover .section-check,
.group-registration-form-page .custom-section-nav .nav-link[aria-selected="true"] .section-check {
    color: #ffffff !important;
    opacity: 1 !important;
}

.group-registration-form-page .custom-section-nav .section-check.completed {
    color: #3B8E4D !important;
}

.group-registration-form-page .custom-section-nav .nav-link.active .section-check.completed,
.group-registration-form-page .custom-section-nav .nav-link[aria-selected="true"] .section-check.completed {
    color: #ffffff !important;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('groupRegistrationForm');
    const batch = document.getElementById('batchId');
    const quantity = document.getElementById('quantity');
    const discountType = document.getElementById('discountType');
    const discountValue = document.getElementById('discountValue');
    const paymentScheme = document.getElementById('paymentScheme');
    const installmentCount = document.getElementById('installmentCount');
    const termsWrap = document.getElementById('paymentTerms');
    const participantRows = document.getElementById('participantRows');
    const participantTemplate = document.getElementById('participantTemplate');
    const toastContainer = document.getElementById('toastContainer');
    const buyerStudent = document.getElementById('buyerStudentId');
    const buyerName = document.getElementById('buyerName');
    const buyerEmail = document.getElementById('buyerEmail');
    const buyerPhone = document.getElementById('buyerPhone');
    let amounts = {price:0, original:0, discount:0, service:0, wht:0, invoice:0, net:0};

    const money = value => 'Rp ' + Math.round(Number(value || 0)).toLocaleString('id-ID');
    const buyerType = () => form.querySelector('[name="buyer_type"]:checked')?.value || 'individual';
    const isCompany = () => buyerType() === 'company';

    function calculate() {
        const price = Number(batch.options[batch.selectedIndex]?.dataset.price || 0);
        const seats = Math.max(2, Number(quantity.value || 2));
        const original = price * seats;
        const value = Math.max(0, Number(discountValue.value || 0));
        const discount = discountType.value === 'percentage' ? original * Math.min(value,100)/100 : (discountType.value === 'fixed' ? Math.min(value,original) : 0);
        const service = Math.max(0, original-discount);
        const invoice = isCompany() && service > 0 ? Math.round((service/0.98)*100)/100 : service;
        amounts = {price, original, discount, service, wht:invoice-service, invoice, net:service};
        document.getElementById('pricePerSeatText').textContent=money(price); document.getElementById('originalPriceText').textContent=money(original); document.getElementById('discountText').textContent='- '+money(discount); document.getElementById('serviceAmountText').textContent=money(service); document.getElementById('whtAmountText').textContent=money(amounts.wht); document.getElementById('invoiceTotalText').textContent=money(invoice); document.getElementById('netPayableText').textContent=money(service);
        document.querySelectorAll('.company-wht-row').forEach(el=>el.classList.toggle('d-none',!isCompany())); document.getElementById('whtExplanation').classList.toggle('d-none',!isCompany());
        resetTerms(); refreshSummary(); refreshProgress();
    }

    function toggleBuyer() {
        document.getElementById('individualBuyerFields').classList.toggle('d-none',isCompany()); document.getElementById('companyBuyerFields').classList.toggle('d-none',!isCompany());
        document.getElementById('registrationTypeSummary').textContent=isCompany()?'Company Group':'Individual Group'; document.getElementById('registrationTypeHelp').textContent=isCompany()?'Gross-up PPh 23 applies automatically.':'PPh 23 is not applicable.'; calculate();
    }

    function toggleNewCompany(){const useExisting=Boolean(document.getElementById('companyId').value),wrap=document.getElementById('newCompanyFields');wrap.classList.toggle('d-none',useExisting);wrap.querySelectorAll('input,textarea,select').forEach(el=>el.disabled=useExisting);}

    function autofillIndividualBuyer() {
        const option = buyerStudent.options[buyerStudent.selectedIndex];

        if (!buyerStudent.value || !option) {
            return;
        }

        buyerName.value = option.dataset.name || '';
        buyerEmail.value = option.dataset.email || '';
        buyerPhone.value = option.dataset.phone || '';
        refreshProgress();
    }

    function termLabel(i,count){return paymentScheme.value==='full'?'Full Payment':(i===0?'Down Payment (DP)':`Installment ${i+1}`)}
    function resetTerms(){
        const count=paymentScheme.value==='full'?1:Number(installmentCount.value||2); termsWrap.innerHTML=''; let allocated=0;
        for(let i=0;i<count;i++){const remaining=amounts.net-allocated; const value=i===count-1?remaining:Math.floor((amounts.net/count)*100)/100; allocated+=value; const label=termLabel(i,count); termsWrap.insertAdjacentHTML('beforeend',`<div class="term-card" data-index="${i}"><div class="term-label mb-3">${label}</div><div class="row g-3"><div class="col-md-6"><label class="form-label">Net Amount</label><input type="number" class="form-control term-amount" name="payment_terms[${i}][amount]" min="0.01" step="0.01" value="${value.toFixed(2)}" ${i===count-1?'readonly':''}></div><div class="col-md-6"><label class="form-label">Due Date</label><input type="date" class="form-control term-date" name="payment_terms[${i}][due_date]"></div></div><div class="small text-muted mt-2">${isCompany()?`Gross invoice allocation: ${money(value/0.98)} · WHT: ${money(value/0.98-value)}`:'No WHT allocation.'}</div></div>`)} bindTermInputs();
    }
    function bindTermInputs(){document.querySelectorAll('.term-amount:not([readonly])').forEach(input=>input.addEventListener('input',()=>redistributeFrom(Number(input.closest('.term-card').dataset.index))));}
    function redistributeFrom(index){const inputs=[...document.querySelectorAll('.term-amount')]; let used=inputs.slice(0,index+1).reduce((sum,input)=>sum+Math.max(0,Number(input.value||0)),0); const following=inputs.length-index-1; if(following<1)return; let remaining=Math.max(0,amounts.net-used); let assigned=0; for(let i=index+1;i<inputs.length;i++){const value=i===inputs.length-1?remaining-assigned:Math.floor((remaining/following)*100)/100; inputs[i].value=Math.max(0,value).toFixed(2); assigned+=value;} refreshSummary();}

    function addParticipant(){const max=Math.max(2,Number(quantity.value||2)); if(participantRows.children.length>=max){showToast('Assigned participants cannot exceed purchased seats.','warning');return;} participantRows.appendChild(participantTemplate.content.cloneNode(true)); reindexParticipants();}
    function reindexParticipants(){[...participantRows.querySelectorAll('.participant-row')].forEach((row,i)=>{row.querySelector('select').name=`participants[${i}][student_id]`; row.querySelector('.remove-participant').onclick=()=>{row.remove();reindexParticipants();};});refreshSummary();}

    function refreshSummary(){document.getElementById('summaryBuyerType').textContent=isCompany()?'Company':'Individual';document.getElementById('summarySeats').textContent=quantity.value||2;document.getElementById('summaryService').textContent=money(amounts.service);document.getElementById('summaryWht').textContent=isCompany()?money(amounts.wht):'Not applicable';document.getElementById('summaryNet').textContent=money(amounts.net);document.getElementById('summaryParticipants').textContent=`${participantRows.children.length} / ${quantity.value||2}`;}
    function sectionReady(key){if(key==='buyer')return Boolean(batch.value)&&Number(quantity.value)>=2&&(isCompany()?Boolean(document.getElementById('companyId').value||form.querySelector('[name="company[name]"]').value):Boolean(buyerName.value.trim()));if(key==='pricing')return amounts.service>0;if(key==='payment')return [...document.querySelectorAll('.term-date')].every(el=>el.value)&&[...document.querySelectorAll('.term-amount')].reduce((s,e)=>s+Number(e.value||0),0)>0;return true;}
    function refreshProgress(){const keys=['buyer','pricing','payment','participants'];let ready=0;keys.forEach(key=>{const ok=sectionReady(key);if(ok)ready++;const ind=document.querySelector(`[data-section-indicator="${key}"]`),badge=document.querySelector(`[data-section-badge="${key}"]`);if(key==='participants'){ind.innerHTML='<i class="bi bi-stars"></i>';return;}ind.innerHTML=ok?'<i class="bi bi-check-circle-fill"></i>':'<i class="bi bi-circle"></i>';ind.classList.toggle('completed',ok);if(badge){badge.innerHTML=ok?'<i class="bi bi-check-circle-fill me-1"></i>Ready':'<i class="bi bi-hourglass-split me-1"></i>Need Input';badge.classList.toggle('completed',ok);}});document.getElementById('completedSectionCount').textContent=ready;document.getElementById('sectionProgressBar').style.width=`${Math.round(ready/4*100)}%`;}

    function showToast(message,type='success'){const id=`toast-${Date.now()}`,bg=type==='warning'?'warning text-dark':type;toastContainer.insertAdjacentHTML('beforeend',`<div id="${id}" class="toast bg-${bg} ${type==='warning'?'':'text-white'} border-0 mb-2"><div class="d-flex"><div class="toast-body">${message}</div><button class="btn-close ${type==='warning'?'':'btn-close-white'} me-2 m-auto" data-bs-dismiss="toast"></button></div></div>`);const el=document.getElementById(id);new bootstrap.Toast(el,{delay:3000}).show();el.addEventListener('hidden.bs.toast',()=>el.remove());}
    function clearErrors(){form.querySelectorAll('.is-invalid').forEach(el=>el.classList.remove('is-invalid'));form.querySelectorAll('.error-text').forEach(el=>el.textContent='');}
    function applyErrors(errors){Object.entries(errors).forEach(([key,msgs])=>{const msg=Array.isArray(msgs)?msgs[0]:msgs;const holder=form.querySelector(`[data-error-for="${key}"]`);if(holder)holder.textContent=msg;const name=key.replace(/\.([^.]+)/g,'[$1]');const field=form.querySelector(`[name="${name}"]`);if(field)field.classList.add('is-invalid');});}
    async function submit(){clearErrors();const buttons=[document.getElementById('submitRegistrationTop'),document.getElementById('submitRegistrationBottom')];buttons.forEach(b=>b.disabled=true);document.querySelector('#submitRegistrationBottom .default-text').classList.add('d-none');document.querySelector('#submitRegistrationBottom .loading-text').classList.remove('d-none');try{const response=await fetch(form.action,{method:'POST',headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':'{{ csrf_token() }}'},body:new FormData(form)});const result=await response.json();if(!response.ok){if(response.status===422&&result.errors)applyErrors(result.errors);throw new Error(result.message||'Failed to create registration.');}showToast(result.message||'Registration created.');setTimeout(()=>location.href=result.data.redirect_url,800);}catch(error){showToast(error.message,'danger');buttons.forEach(b=>b.disabled=false);document.querySelector('#submitRegistrationBottom .default-text').classList.remove('d-none');document.querySelector('#submitRegistrationBottom .loading-text').classList.add('d-none');}}

    document.querySelectorAll('.buyer-type-radio').forEach(el=>el.addEventListener('change',toggleBuyer));buyerStudent.addEventListener('change',autofillIndividualBuyer);document.getElementById('companyId').addEventListener('change',()=>{toggleNewCompany();refreshProgress();});[batch,quantity,discountType,discountValue].forEach(el=>{el.addEventListener('input',calculate);el.addEventListener('change',calculate)});paymentScheme.addEventListener('change',()=>{document.getElementById('installmentCountWrap').style.display=paymentScheme.value==='installment'?'block':'none';resetTerms();refreshProgress();});installmentCount.addEventListener('change',()=>{resetTerms();refreshProgress();});document.getElementById('addParticipantBtn').addEventListener('click',addParticipant);[document.getElementById('submitRegistrationTop'),document.getElementById('submitRegistrationBottom')].forEach(btn=>btn.addEventListener('click',submit));form.addEventListener('input',refreshProgress);form.addEventListener('change',refreshProgress);toggleBuyer();toggleNewCompany();resetTerms();refreshSummary();refreshProgress();
});
</script>
@endpush