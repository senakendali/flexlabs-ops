@extends('layouts.public')

@section('title', 'Payment Invoice')

@section('content')
@php
    $invoiceDate = $payment->payment_date ?? $payment->created_at;
    $canPay = !$isPaid && !$isExpired && !empty($payment->payment_url);
@endphp

<div class="container py-5">
    <div class="public-invoice-wrapper">
        @if ($isPaid)
            <div class="alert alert-success public-alert mb-4">
                <strong>Payment completed.</strong>
                This invoice has already been paid successfully.
            </div>
        @elseif ($isExpired)
            <div class="alert alert-warning public-alert mb-4">
                <strong>Payment link expired.</strong>
                This payment link is no longer active. Please contact admin for a new payment link.
            </div>
        @elseif (!$payment->payment_url)
            <div class="alert alert-secondary public-alert mb-4">
                <strong>Payment link is not available yet.</strong>
                Please contact admin to activate the payment link for this invoice.
            </div>
        @endif

        <div class="invoice-page">
            <div class="invoice-card">
                <div class="invoice-top">
                    <div class="invoice-brand">
                        <img
                            src="{{ asset('images/logo-black.png') }}"
                            alt="FlexLabs Logo"
                            class="invoice-logo"
                        >
                    </div>

                    <div class="invoice-title-box">
                        <div class="invoice-circle-outline"></div>
                        <div class="invoice-circle-solid"></div>
                        <h1>INVOICE</h1>
                    </div>
                </div>

                <div class="invoice-banner">
                    <div class="invoice-banner-grid">
                        <div class="invoice-banner-item">
                            <strong>Invoice No:</strong> {{ $payment->invoice_number }}
                        </div>
                        <div class="invoice-banner-item">
                            <strong>Date:</strong> {{ $invoiceDate?->format('M d, Y') ?? '-' }}
                        </div>
                        <div class="invoice-banner-item">
                            <strong>Invoice To:</strong> {{ $student->full_name ?? '-' }}
                        </div>
                        <div class="invoice-banner-item">
                            @if (!empty($student?->email))
                                {{ $student->email }}
                            @elseif (!empty($student?->phone))
                                {{ $student->phone }}
                            @else
                                -
                            @endif
                        </div>
                    </div>

                    <div class="invoice-banner-action no-print">
                        @if ($isPaid)
                            <!--button type="button" class="btn btn-success btn-lg px-4" disabled>
                                <i class="bi bi-check-circle me-1"></i> Already Paid
                            </button-->
                        @elseif ($isExpired)
                            <button type="button" class="btn btn-secondary btn-lg px-4" disabled>
                                <i class="bi bi-x-circle me-1"></i> Link Expired
                            </button>
                        @elseif ($payment->payment_url)
                            <!--a
                                href="{{ $payment->payment_url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="btn btn-light btn-pay-now btn-lg px-4"
                            >
                                <i class="bi bi-credit-card me-1"></i> Pay Now
                            </a-->
                        @else
                            <!--button type="button" class="btn btn-outline-light btn-lg px-4" disabled>
                                <i class="bi bi-clock-history me-1"></i> Payment Link Not Ready
                            </button-->
                        @endif
                    </div>
                </div>

                <div class="invoice-body">
                    <div class="table-responsive invoice-table-wrap">
                        <table class="table invoice-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th class="text-center" style="width: 90px;">Qty</th>
                                    <th class="text-end" style="width: 140px;">Rate/Unit</th>
                                    <th class="text-end" style="width: 160px;">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        @if ($schedule)
                                            {{ $schedule->title }}
                                        @else
                                            Program Payment
                                        @endif
                                        {{ $program->name ? ' - ' . $program->name : '' }}
                                        @if ($batch)
                                            / {{ $batch->name }}
                                        @endif
                                    </td>
                                    <td class="text-center">1</td>
                                    <td class="text-end">Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="invoice-bottom">
                        <div class="invoice-payment-info">
                            <div class="section-label">Payment Information</div>

                            <div class="invoice-meta-block">
                                <div><strong>Program:</strong> {{ $program->name ?? '-' }}</div>
                                <div><strong>Batch:</strong> {{ $batch->name ?? '-' }}</div>

                                @if ($schedule)
                                    <div><strong>Schedule:</strong> {{ $schedule->title }}</div>
                                @endif

                                <div>
                                    <strong>Status:</strong>
                                    <span class="status-badge status-{{ strtolower($payment->status) }}">
                                        {{ ucfirst($payment->status) }}
                                    </span>
                                </div>

                                @if ($payment->expired_at)
                                    <div><strong>Expires At:</strong> {{ $payment->expired_at->format('d M Y H:i') }}</div>
                                @endif

                                @if ($payment->gateway_provider)
                                    <div><strong>Gateway:</strong> {{ ucfirst($payment->gateway_provider) }}</div>
                                @endif
                            </div>
                        </div>

                        <div class="invoice-summary">
                            <table class="summary-table">
                                <tr>
                                    <td>Subtotal</td>
                                    <td class="text-end">Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}</td>
                                </tr>
                                <tr>
                                    <td>Tax</td>
                                    <td class="text-end">Rp 0</td>
                                </tr>
                                <tr class="grand-total-row">
                                    <td>Grand Total</td>
                                    <td class="text-end">Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="public-payment-action no-print">
                        @if ($isPaid)
                            <button type="button" class="btn btn-success btn-lg px-4" disabled>
                                <i class="bi bi-check-circle me-1"></i> Already Paid
                            </button>
                        @elseif ($isExpired)
                            <button type="button" class="btn btn-secondary btn-lg px-4" disabled>
                                <i class="bi bi-x-circle me-1"></i> Link Expired
                            </button>
                        @elseif ($payment->payment_url)
                            <a
                                href="{{ $payment->payment_url }}"
                                rel="noopener noreferrer"
                                class="btn btn-brand btn-lg px-5"
                            >
                                <i class="bi bi-credit-card me-1"></i> Pay Now
                            </a>
                        @else
                            <button type="button" class="btn btn-outline-secondary btn-lg px-4" disabled>
                                <i class="bi bi-clock-history me-1"></i> Payment Link Not Ready
                            </button>
                        @endif
                    </div>
                </div>

                <div class="invoice-footer">
                    <div class="invoice-contact-card">
                        <div class="contact-item">
                            <span class="contact-icon">☎</span>
                            <span>+62 812-0000-0000</span>
                        </div>
                        <div class="contact-item">
                            <span class="contact-icon">⌂</span>
                            <span>FlexLabs Office, Indonesia</span>
                        </div>
                        <div class="contact-item">
                            <span class="contact-icon">◎</span>
                            <span>www.flexlabs.co.id</span>
                        </div>
                    </div>

                    <div class="invoice-signature-wrap">
                        <div class="invoice-date">
                            Date: {{ $invoiceDate?->format('M d, Y') ?? '-' }}
                        </div>

                        <div class="signature-mark">
                            <svg width="70" height="34" viewBox="0 0 70 34" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 25C9 25 9 9 15 9C20 9 20 29 25 29C30 29 30 6 36 6C41 6 41 24 46 24C50 24 51 15 55 15C59 15 60 22 67 22" stroke="#1F1F29" stroke-width="2.5" stroke-linecap="round"/>
                            </svg>
                        </div>

                        <div class="signature-line"></div>
                        <div class="signature-name">FlexLabs Finance</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .public-invoice-wrapper {
        max-width: 980px;
        margin: 0 auto;
    }

    .public-alert {
        border-radius: 16px;
        border: none;
        box-shadow: 0 10px 24px rgba(45, 28, 80, 0.08);
    }

    .invoice-page {
        max-width: 900px;
        margin: 0 auto;
    }

    .invoice-card {
        background: #ffffff;
        border-radius: 24px;
        overflow: visible;
        box-shadow: 0 14px 40px rgba(45, 28, 80, 0.10);
        position: relative;
    }

    .invoice-top {
        display: flex;
        justify-content: space-between;
        align-items: stretch;
        background: #f4f4f5;
        min-height: 145px;
        border-top-left-radius: 24px;
        border-top-right-radius: 24px;
        position: relative;
        z-index: 1;
    }

    .invoice-brand {
        flex: 1;
        padding: 26px 30px;
        display: flex;
        align-items: center;
    }

    .invoice-logo {
        width: 180px;
        max-width: 100%;
        height: auto;
        display: block;
    }

    .invoice-title-box {
        width: 280px;
        min-height: 118px;
        background: #ffffff;
        border-bottom-left-radius: 26px;
        border-bottom-right-radius: 26px;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        margin-right: 24px;
        margin-bottom: -34px;
        z-index: 3;
        box-shadow: 0 12px 24px rgba(45, 28, 80, 0.08);
    }

    .invoice-title-box h1 {
        margin: 0;
        font-size: 42px;
        font-weight: 800;
        letter-spacing: 1px;
        color: #2d2f39;
    }

    .invoice-circle-outline {
        position: absolute;
        left: 22px;
        top: 30px;
        width: 16px;
        height: 16px;
        border: 4px solid #5B3E8E;
        border-radius: 50%;
    }

    .invoice-circle-solid {
        position: absolute;
        left: 40px;
        top: 18px;
        width: 8px;
        height: 8px;
        background: #5B3E8E;
        border-radius: 50%;
    }

    .invoice-banner {
        background: #5B3E8E;
        color: #ffffff;
        padding: 22px 30px 30px;
        font-size: 13px;
        line-height: 1.6;
        position: relative;
        z-index: 1;
    }

    .invoice-banner-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px 24px;
    }

    .invoice-banner-item strong {
        color: #ffffff;
    }

    .invoice-banner-action {
        margin-top: 18px;
        display: flex;
        justify-content: flex-start;
    }

    .btn-pay-now {
        color: #5B3E8E;
        border: none;
        font-weight: 700;
    }

    .btn-pay-now:hover {
        color: #5B3E8E;
        transform: translateY(-1px);
    }

    .invoice-body {
        position: relative;
        z-index: 2;
        padding: 0 30px 18px;
        margin-top: -34px;
    }

    .invoice-table-wrap {
        background: #ffffff;
        border-radius: 22px;
        overflow: hidden;
        box-shadow: 0 12px 28px rgba(45, 28, 80, 0.08);
        margin-bottom: 24px;
        border: 1px solid #ece7f4;
    }

    .invoice-table {
        background: #ffffff;
    }

    .invoice-table thead th {
        border-top: none;
        border-bottom: 2px solid #d4c8ea;
        color: #3a3a46;
        font-size: 13px;
        font-weight: 700;
        padding: 16px 18px;
        background: #ffffff;
    }

    .invoice-table tbody td {
        padding: 15px 18px;
        font-size: 14px;
        border-bottom: 1px solid #ece7f4;
        color: #3a3a46;
        background: #ffffff;
    }

    .invoice-table tbody tr:last-child td {
        border-bottom: none;
    }

    .invoice-bottom {
        display: grid;
        grid-template-columns: 1.15fr 0.85fr;
        gap: 28px;
        align-items: start;
    }

    .section-label {
        font-size: 13px;
        font-weight: 700;
        color: #3b3b45;
        margin-bottom: 12px;
    }

    .invoice-meta-block {
        font-size: 13px;
        color: #4e4a58;
        line-height: 1.8;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        margin-left: 6px;
    }

    .status-paid {
        background: #dcfce7;
        color: #166534;
    }

    .status-pending {
        background: #ede9fe;
        color: #5B3E8E;
    }

    .status-expired {
        background: #fef3c7;
        color: #92400e;
    }

    .status-failed,
    .status-cancelled {
        background: #fee2e2;
        color: #991b1b;
    }

    .invoice-summary {
        padding-top: 8px;
        background: #ffffff;
        border-radius: 18px;
    }

    .summary-table {
        width: 100%;
        border-collapse: collapse;
    }

    .summary-table td {
        padding: 7px 0;
        font-size: 14px;
        color: #3d3947;
    }

    .grand-total-row td {
        padding-top: 14px;
        border-top: 1px solid #d8d1e5;
        font-size: 18px;
        font-weight: 800;
        color: #5B3E8E;
    }

    .public-payment-action {
        margin-top: 28px;
        text-align: center;
    }

    .invoice-footer {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 22px;
        align-items: end;
        padding: 4px 30px 30px;
    }

    .invoice-contact-card {
        background: #5B3E8E;
        color: #ffffff;
        padding: 18px 20px;
        max-width: 270px;
        border-radius: 0 18px 18px 18px;
    }

    .contact-item {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 13px;
        line-height: 1.8;
    }

    .contact-icon {
        width: 16px;
        text-align: center;
        display: inline-block;
        opacity: 0.95;
    }

    .invoice-signature-wrap {
        text-align: center;
        padding-top: 8px;
    }

    .invoice-date {
        font-size: 13px;
        color: #3d3947;
        margin-bottom: 20px;
    }

    .signature-mark {
        margin-bottom: 6px;
    }

    .signature-line {
        width: 160px;
        margin: 0 auto 10px;
        border-top: 2px solid #c5bfce;
    }

    .signature-name {
        font-size: 13px;
        color: #2f2f39;
        font-weight: 600;
    }

    @media (max-width: 768px) {
        .invoice-top,
        .invoice-bottom,
        .invoice-footer {
            display: block;
        }

        .invoice-title-box {
            width: calc(100% - 28px);
            min-height: 96px;
            margin: 0 14px -24px;
            border-bottom-left-radius: 20px;
            border-bottom-right-radius: 20px;
        }

        .invoice-title-box h1 {
            font-size: 32px;
        }

        .invoice-banner {
            padding: 18px 20px 24px;
        }

        .invoice-banner-grid {
            grid-template-columns: 1fr;
            gap: 8px;
        }

        .invoice-banner-action {
            margin-top: 16px;
        }

        .invoice-banner-action .btn,
        .public-payment-action .btn {
            width: 100%;
        }

        .invoice-body {
            padding: 0 16px 18px;
            margin-top: -20px;
        }

        .invoice-summary,
        .invoice-signature-wrap {
            margin-top: 22px;
        }

        .invoice-footer {
            padding: 8px 16px 20px;
        }

        .invoice-contact-card {
            max-width: 100%;
            margin-bottom: 20px;
        }
    }
</style>
@endpush