@extends('layouts.app-dashboard')

@section('title', 'Receipt')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/payments/invoice.css') }}">
@endpush

@section('content')
@php
    $formatDate = function ($date, string $format = 'd F Y') {
        if (empty($date)) {
            return '-';
        }

        return \Carbon\Carbon::parse($date)->format($format);
    };

    $formatMoney = function ($amount) {
        return 'Rp ' . number_format((float) $amount, 0, ',', '.');
    };

    $resolvedReceiptNumber = $receiptNumber ?? null;

    if (empty($resolvedReceiptNumber)) {
        $invoiceNumber = (string) ($payment->invoice_number ?? '');

        if ($invoiceNumber !== '' && \Illuminate\Support\Str::startsWith($invoiceNumber, 'FLX-')) {
            $resolvedReceiptNumber = 'FLX-RCPT-' . \Illuminate\Support\Str::after($invoiceNumber, 'FLX-');
        } elseif ($invoiceNumber !== '') {
            $resolvedReceiptNumber = 'RCPT-' . $invoiceNumber;
        } else {
            $resolvedReceiptNumber = 'FLX-RCPT-' . now()->format('Ymd') . '-' . str_pad((string) ($payment->id ?? 0), 4, '0', STR_PAD_LEFT);
        }
    }

    $receiptDate = $paidAt ?? $payment->paid_at ?? $payment->payment_date ?? $payment->updated_at ?? $payment->created_at;

    $paymentMethod = $payment->payment_method
        ?: ($payment->gateway_provider ? ucfirst($payment->gateway_provider) : '-');

    $studentAddressParts = collect([
        $student->city ?? null,
    ])->filter()->implode(', ');

    $companyName = $companyName ?? 'FlexLabs';
    $companyAddressLines = $companyAddressLines ?? [
        'MyRepublic Plaza Wing B 2nd Floor',
        'Jl. BSD Grand Boulevard',
        'BSD Green Office Park BSD City',
        'Desa Sampora, Kec. Cisauk',
        'Tangerang 15345',
    ];

    $receiptPdfFilename = 'receipt-' . \Illuminate\Support\Str::slug((string) ($resolvedReceiptNumber ?? 'document')) . '.pdf';
@endphp

<div class="container py-4 invoice-shell">
    <div class="invoice-toolbar no-print">
        <div>
            <h4 class="mb-1">Receipt</h4>
            <small class="text-muted">{{ $resolvedReceiptNumber }}</small>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('payments.index') }}" class="btn btn-light border">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>

            <button
                type="button"
                class="btn btn-primary"
                data-pdf-download
                data-pdf-target="#receiptDocument"
                data-pdf-filename="{{ $receiptPdfFilename }}"
            >
                <i class="bi bi-download me-1"></i> Download PDF
            </button>
        </div>
    </div>

    <div class="invoice-page">
        <div id="receiptDocument" class="invoice-card">
            <div class="invoice-content">
                <header class="invoice-header">
                    <div class="invoice-logo-wrap">
                        <img
                            src="{{ asset('images/logo-black.png') }}"
                            alt="FlexLabs Logo"
                            class="invoice-logo"
                        >
                    </div>

                    <div class="invoice-number-box">
                        <span class="invoice-number-label">No.</span>
                        <span class="invoice-number-value">{{ $resolvedReceiptNumber }}</span>
                    </div>
                </header>

                <h1 class="invoice-title">RECEIPT</h1>

                <div class="invoice-date-line invoice-info-line">
                    <span class="invoice-info-label">Date</span>
                    <span class="invoice-info-colon">:</span>
                    <span class="invoice-info-value">{{ $formatDate($receiptDate) }}</span>
                </div>

                <section class="invoice-parties">
                    <div class="invoice-party-card">
                        <h2>Received from</h2>

                        <div class="invoice-party-name">{{ $student->full_name ?? '-' }}</div>

                        @if (!empty($studentAddressParts))
                            <div>{{ $studentAddressParts }}</div>
                        @endif

                        @if (!empty($student?->email))
                            <div>{{ $student->email }}</div>
                        @endif

                        @if (!empty($student?->phone))
                            <div>{{ $student->phone }}</div>
                        @endif
                    </div>

                    <div class="invoice-party-card">
                        <h2>Received by</h2>

                        <div class="invoice-party-name">{{ $companyName }}</div>
                        <div class="invoice-company-address">
                            @foreach ($companyAddressLines as $addressLine)
                                <div>{{ $addressLine }}</div>
                            @endforeach
                        </div>
                    </div>
                </section>

                <section class="invoice-table-section">
                    <div class="table-responsive invoice-table-wrap">
                        <table class="table invoice-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th class="text-center invoice-table-qty">Quantity</th>
                                    <th class="text-end invoice-table-price">Price</th>
                                    <th class="text-end invoice-table-amount">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $item)
                                    <tr>
                                        <td>
                                            <div class="invoice-item-title">{{ $item['description'] ?? '-' }}</div>

                                            @if (!empty($program?->name))
                                                <div class="invoice-item-subtitle">{{ $program->name }} Program</div>
                                            @endif

                                            @if (!empty($batch?->name))
                                                <div class="invoice-item-subtitle">{{ $batch->name }}</div>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ $item['qty'] ?? 1 }}</td>
                                        <td class="text-end">{{ $formatMoney($item['rate'] ?? 0) }}</td>
                                        <td class="text-end">{{ $formatMoney($item['amount'] ?? 0) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="invoice-summary-wrap">
                        <table class="invoice-summary-table">
                            <tr>
                                <td>Sub Total</td>
                                <td>{{ $formatMoney($subtotal ?? 0) }}</td>
                            </tr>

                            @if ((float) ($tax ?? 0) > 0)
                                <tr>
                                    <td>Tax</td>
                                    <td>{{ $formatMoney($tax ?? 0) }}</td>
                                </tr>
                            @endif

                            <tr class="invoice-summary-total">
                                <td>Total Paid</td>
                                <td>{{ $formatMoney($grandTotal ?? 0) }}</td>
                            </tr>
                        </table>
                    </div>
                </section>

                <section class="invoice-payment-section">
                    <div class="invoice-info-line">
                        <span class="invoice-info-label">Invoice no</span>
                        <span class="invoice-info-colon">:</span>
                        <span class="invoice-info-value">{{ $payment->invoice_number ?? '-' }}</span>
                    </div>

                    <div class="invoice-info-line">
                        <span class="invoice-info-label">Payment method</span>
                        <span class="invoice-info-colon">:</span>
                        <span class="invoice-info-value">{{ $paymentMethod }}</span>
                    </div>

                    <div class="invoice-info-line">
                        <span class="invoice-info-label">Paid at</span>
                        <span class="invoice-info-colon">:</span>
                        <span class="invoice-info-value">{{ $formatDate($receiptDate, 'd F Y H:i') }}</span>
                    </div>

                    @if (!empty($payment->reference_number))
                        <div class="invoice-info-line">
                            <span class="invoice-info-label">Reference no</span>
                            <span class="invoice-info-colon">:</span>
                            <span class="invoice-info-value">{{ $payment->reference_number }}</span>
                        </div>
                    @endif

                    @if (!empty($payment->gateway_transaction_id))
                        <div class="invoice-info-line">
                            <span class="invoice-info-label">Transaction ID</span>
                            <span class="invoice-info-colon">:</span>
                            <span class="invoice-info-value">{{ $payment->gateway_transaction_id }}</span>
                        </div>
                    @endif

                    <div class="invoice-info-line">
                        <span class="invoice-info-label">Note</span>
                        <span class="invoice-info-colon">:</span>
                        <span class="invoice-info-value">{{ $payment->notes ?: 'Payment has been received. Thank you for choosing FlexLabs.' }}</span>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js" defer></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const A4_WIDTH_MM = 210;
        const A4_HEIGHT_MM = 297;

        function getJsPdf() {
            if (!window.jspdf || !window.jspdf.jsPDF) {
                return null;
            }

            return window.jspdf.jsPDF;
        }

        function sanitizeFilename(filename) {
            const fallback = 'flexlabs-receipt.pdf';

            if (!filename) {
                return fallback;
            }

            const cleaned = String(filename)
                .trim()
                .replace(/[\\/:*?"<>|]+/g, '-')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-');

            if (!cleaned) {
                return fallback;
            }

            return cleaned.toLowerCase().endsWith('.pdf') ? cleaned : `${cleaned}.pdf`;
        }

        async function waitForFonts() {
            if (document.fonts && typeof document.fonts.ready !== 'undefined') {
                try {
                    await document.fonts.ready;
                } catch (error) {
                    // Font readiness is optional. Export should still continue.
                }
            }
        }

        async function downloadElementAsPdf(button) {
            const targetSelector = button.dataset.pdfTarget;
            const target = document.querySelector(targetSelector);
            const filename = sanitizeFilename(button.dataset.pdfFilename);
            const originalHtml = button.innerHTML;
            const originalDisabled = button.disabled;

            if (!target) {
                alert('Area receipt tidak ditemukan. Coba refresh halaman dulu, bro.');
                return;
            }

            if (typeof window.html2canvas === 'undefined') {
                alert('Library html2canvas belum berhasil dimuat. Cek koneksi internet atau simpan library-nya secara lokal.');
                return;
            }

            const JsPDF = getJsPdf();

            if (!JsPDF) {
                alert('Library jsPDF belum berhasil dimuat. Cek koneksi internet atau simpan library-nya secara lokal.');
                return;
            }

            const previousBoxShadow = target.style.boxShadow;

            try {
                button.disabled = true;
                button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span> Generating...';

                await waitForFonts();

                target.style.boxShadow = 'none';
                target.classList.add('invoice-exporting');

                const canvas = await window.html2canvas(target, {
                    backgroundColor: '#ffffff',
                    scale: Math.min(3, Math.max(2, window.devicePixelRatio || 2)),
                    useCORS: true,
                    allowTaint: false,
                    logging: false,
                    scrollX: 0,
                    scrollY: -window.scrollY,
                    windowWidth: document.documentElement.scrollWidth,
                    windowHeight: document.documentElement.scrollHeight
                });

                const imageData = canvas.toDataURL('image/png', 1.0);
                const pdf = new JsPDF('p', 'mm', 'a4');

                // Invoice/receipt memang didesain sebagai 1 halaman A4.
                // Jangan pakai slicing multi-page, karena selisih tinggi canvas 1-2px
                // bisa bikin jsPDF menambah halaman kedua kosong.
                pdf.addImage(
                    imageData,
                    'PNG',
                    0,
                    0,
                    A4_WIDTH_MM,
                    A4_HEIGHT_MM,
                    undefined,
                    'FAST'
                );

                pdf.save(filename);
            } catch (error) {
                console.error(error);
                alert('Gagal generate PDF. Coba refresh halaman atau cek console browser.');
            } finally {
                target.style.boxShadow = previousBoxShadow;
                target.classList.remove('invoice-exporting');
                button.disabled = originalDisabled;
                button.innerHTML = originalHtml;
            }
        }

        document.addEventListener('click', function (event) {
            const button = event.target.closest('[data-pdf-download]');

            if (!button) {
                return;
            }

            event.preventDefault();
            downloadElementAsPdf(button);
        });
    });
</script>
@endpush
