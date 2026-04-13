@extends('layouts.app-dashboard')

@section('title', 'Invoice')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 no-print">
        <div>
            <h4 class="mb-1">Invoice</h4>
            <small class="text-muted">{{ $payment->invoice_number }}</small>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('payments.index') }}" class="btn btn-light border">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
            <button type="button" class="btn btn-primary" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Print
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                <i class="bi bi-send me-1"></i> Send Payment Link
            </button>
        </div>
    </div>

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
                <div class="invoice-banner-item">
                    <strong>Invoice No:</strong> {{ $payment->invoice_number }}
                </div>
                <div class="invoice-banner-item">
                    <strong>Date:</strong> {{ $payment->payment_date?->format('M d, Y') ?? '-' }}
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
                            @foreach ($items as $item)
                                <tr>
                                    <td>{{ $item['description'] }} {{ $program->name ?? '-' }} Program</td>
                                    <td class="text-center">{{ $item['qty'] }}</td>
                                    <td class="text-end">Rp {{ number_format($item['rate'], 0, ',', '.') }}</td>
                                    <td class="text-end">Rp {{ number_format($item['amount'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="invoice-bottom">
                    <div class="invoice-payment-info">
                        <div class="section-label">Payment Method:</div>

                        <div class="payment-method-list">
                            <span class="payment-method-item {{ strtolower($payment->payment_method ?? '') === 'paypal' ? 'active' : '' }}">
                                <span class="payment-check"></span> Paypal
                            </span>
                            <span class="payment-method-item {{ in_array(strtolower($payment->payment_method ?? ''), ['bank transfer', 'transfer']) ? 'active' : '' }}">
                                <span class="payment-check"></span> Bank Transfer
                            </span>
                            <span class="payment-method-item {{ strtolower($payment->payment_method ?? '') === 'cash' ? 'active' : '' }}">
                                <span class="payment-check"></span> Cash
                            </span>
                            <span class="payment-method-item {{ strtolower($payment->payment_method ?? '') === 'qris' ? 'active' : '' }}">
                                <span class="payment-check"></span> QRIS
                            </span>
                        </div>

                        <!--div class="invoice-meta-block">
                            <div><strong>Program:</strong> {{ $program->name ?? '-' }}</div>
                            <div><strong>Batch:</strong> {{ $batch->name ?? '-' }}</div>
                            @if ($schedule)
                                <div><strong>Schedule:</strong> {{ $schedule->title }}</div>
                            @endif
                            @if ($payment->reference_number)
                                <div><strong>Reference No:</strong> {{ $payment->reference_number }}</div>
                            @endif
                            @if ($payment->gateway_provider)
                                <div><strong>Gateway:</strong> {{ $payment->gateway_provider }}</div>
                            @endif
                        </div-->
                    </div>

                    <div class="invoice-summary">
                        <table class="summary-table">
                            <tr>
                                <td>Subtotal</td>
                                <td class="text-end">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td>Tax</td>
                                <td class="text-end">Rp {{ number_format($tax, 0, ',', '.') }}</td>
                            </tr>
                            <tr class="grand-total-row">
                                <td>Grand Total</td>
                                <td class="text-end">Rp {{ number_format($grandTotal, 0, ',', '.') }}</td>
                            </tr>
                        </table>
                    </div>
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
                        Date: {{ $payment->payment_date?->format('M d, Y') ?? '-' }}
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
@endsection

@push('styles')
<style>
    
</style>
@endpush