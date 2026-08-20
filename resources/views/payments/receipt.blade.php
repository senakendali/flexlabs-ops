@extends('layouts.app-dashboard')

@section('title', 'Receipt')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/payments/invoice.css') }}">
    <style>
        .invoice-table tbody tr:nth-child(odd) > td {
            --bs-table-bg: #ffffff;
            background-color: #ffffff !important;
        }

        .invoice-table tbody tr:nth-child(even) > td {
            --bs-table-bg: #f6f7f9;
            background-color: #f6f7f9 !important;
        }

        .invoice-title {
            font-family: "Arial Black", "Arial Bold", Arial, Helvetica, sans-serif !important;
            font-weight: 900 !important;
            font-style: normal !important;
            letter-spacing: -0.065em !important;
            line-height: 0.9 !important;
            color: #000000 !important;
            text-transform: uppercase;
        }

        #receiptDocument.invoice-exporting {
            width: 794px !important;
            min-width: 794px !important;
            max-width: 794px !important;
            margin: 0 !important;
            box-shadow: none !important;
            transform: none !important;
        }

        #receiptDocument.invoice-exporting .invoice-table {
            --bs-table-border-color: transparent !important;
            border-collapse: collapse !important;
            border-spacing: 0 !important;
            border: 0 !important;
        }

        #receiptDocument.invoice-exporting .invoice-table > :not(caption) > * > *,
        #receiptDocument.invoice-exporting .invoice-table thead,
        #receiptDocument.invoice-exporting .invoice-table tbody,
        #receiptDocument.invoice-exporting .invoice-table tr,
        #receiptDocument.invoice-exporting .invoice-table th,
        #receiptDocument.invoice-exporting .invoice-table td {
            border: 0 !important;
            border-width: 0 !important;
            border-color: transparent !important;
            box-shadow: none !important;
            outline: 0 !important;
        }

        #receiptDocument.invoice-exporting .invoice-table thead,
        #receiptDocument.invoice-exporting .invoice-table thead tr {
            background: #5b3e8e !important;
            background-color: #5b3e8e !important;
        }

        #receiptDocument.invoice-exporting .invoice-table thead th {
            background: transparent !important;
            background-color: transparent !important;
            background-image: none !important;
            background-clip: border-box !important;
        }

        #receiptDocument.invoice-exporting .invoice-table th::before,
        #receiptDocument.invoice-exporting .invoice-table th::after,
        #receiptDocument.invoice-exporting .invoice-table td::before,
        #receiptDocument.invoice-exporting .invoice-table td::after {
            display: none !important;
            content: none !important;
            border: 0 !important;
            box-shadow: none !important;
        }

        .group-tax-summary {
            margin-top: 18px;
            padding: 16px 18px;
            border: 1px solid #e6dcf5;
            border-radius: 14px;
            background: #faf7ff;
        }

        .group-tax-summary .invoice-summary-table {
            width: 100%;
        }

        .group-tax-summary .invoice-summary-table td {
            padding: 7px 0;
            border-bottom: 1px solid #ece5f6;
        }

        .group-tax-summary .invoice-summary-table tr:last-child td {
            border-bottom: 0;
        }

        .group-tax-summary .group-wht-row td {
            color: #dc2626;
            font-weight: 700;
        }

        .group-tax-summary .group-total-paid-row td {
            padding-top: 11px;
            color: #1f2937;
            font-size: 15px;
            font-weight: 800;
        }
    </style>
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

    $groupOrderItems = collect($groupOrderItems ?? [])->values();

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
                    @if ($groupOrderItems->isNotEmpty())
                        <div class="table-responsive invoice-table-wrap">
                            <table class="table invoice-table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width: 52px;">No</th>
                                        <th>Description</th>
                                        <th class="text-center" style="width: 70px;">QTY</th>
                                        <th class="text-end text-nowrap">Unit Price</th>
                                        <th class="text-end text-nowrap">Discount</th>
                                        <th class="text-end text-nowrap">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($groupOrderItems as $groupItem)
                                        <tr>
                                            <td class="text-center">{{ $groupItem['no'] }}</td>
                                            <td>
                                                <div class="invoice-item-title">{{ $groupItem['description'] }}</div>

                                                @if (!empty($groupItem['participant_name']))
                                                    <div class="invoice-item-subtitle">
                                                        {{ $groupItem['participant_name'] }}
                                                        @if (!empty($groupItem['participant_email']))
                                                            &middot; {{ $groupItem['participant_email'] }}
                                                        @endif
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="text-center">{{ $groupItem['qty'] }}</td>
                                            <td class="text-end text-nowrap">{{ $formatMoney($groupItem['unit_price']) }}</td>
                                            <td class="text-end text-nowrap">{{ $formatMoney($groupItem['discount']) }}</td>
                                            <td class="text-end text-nowrap">{{ $formatMoney($groupItem['amount']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                    <div class="table-responsive invoice-table-wrap">
                        <table class="table invoice-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Item</th>
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
                                        <td class="text-end">{{ $formatMoney($item['rate'] ?? 0) }}</td>
                                        <td class="text-end">{{ $formatMoney($item['amount'] ?? 0) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif

                    @if ($groupOrderItems->isNotEmpty() && (bool) ($usesWht ?? false))
                        <div class="invoice-summary-wrap group-tax-summary">
                            <table class="invoice-summary-table">
                                <tr>
                                    <td>Subtotal</td>
                                    <td>{{ $formatMoney($amountBeforeVat ?? 0) }}</td>
                                </tr>
                                <tr>
                                    <td>Gross Up WHT</td>
                                    <td>{{ $formatMoney($totalInvoiceAmount ?? 0) }}</td>
                                </tr>
                                <tr>
                                    <td>VAT Calculation Base</td>
                                    <td>{{ $formatMoney($vatCalculationBase ?? 0) }}</td>
                                </tr>
                                <tr>
                                    <td>VAT (12%)</td>
                                    <td>{{ $formatMoney($vatAmount ?? 0) }}</td>
                                </tr>
                                <tr class="group-wht-row">
                                    <td>WHT ({{ number_format((float) ($whtRate ?? 2), 0) }}%)</td>
                                    <td>{{ $formatMoney($whtAmount ?? 0) }}</td>
                                </tr>
                                <tr class="group-total-paid-row">
                                    <td>Total Paid</td>
                                    <td>{{ $formatMoney($grandTotal ?? $totalPaid ?? 0) }}</td>
                                </tr>
                            </table>
                        </div>
                    @else
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
                    @endif
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

                    <!--div class="invoice-info-line">
                        <span class="invoice-info-label">Note</span>
                        <span class="invoice-info-colon">:</span>
                        <span class="invoice-info-value">{{ $payment->notes ?: 'Payment has been received. Thank you for choosing FlexLabs.' }}</span>
                    </div-->
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
                    scale: 2,
                    useCORS: true,
                    allowTaint: false,
                    logging: false,
                    scrollX: 0,
                    scrollY: 0,
                    windowWidth: 1440,
                    windowHeight: Math.max(target.scrollHeight, 1123),
                    onclone: function (clonedDocument) {
                        const clonedReceipt = clonedDocument.querySelector('#receiptDocument');

                        if (!clonedReceipt) {
                            return;
                        }

                        clonedReceipt.classList.add('invoice-exporting');
                        clonedReceipt.style.width = '794px';
                        clonedReceipt.style.minWidth = '794px';
                        clonedReceipt.style.maxWidth = '794px';
                        clonedReceipt.style.margin = '0';
                        clonedReceipt.style.boxShadow = 'none';
                        clonedReceipt.style.transform = 'none';
                    }
                });

                const imageData = canvas.toDataURL('image/png', 1.0);
                const pdf = new JsPDF('p', 'mm', 'a4');

                // Jangan paksa canvas menjadi 210 x 297 mm karena tinggi receipt
                // bisa berbeda dari rasio A4 dan hasilnya akan terlihat stretch.
                // Scale secara proporsional agar rasio asli receipt tetap terjaga.
                const canvasRatio = canvas.width / canvas.height;
                const pageRatio = A4_WIDTH_MM / A4_HEIGHT_MM;

                let renderedWidth;
                let renderedHeight;
                let renderedX = 0;
                let renderedY = 0;

                if (canvasRatio >= pageRatio) {
                    renderedWidth = A4_WIDTH_MM;
                    renderedHeight = renderedWidth / canvasRatio;
                    // Pertahankan posisi receipt dari bagian atas halaman.
                    // Sisa ruang putih diletakkan di bawah, bukan dibagi atas-bawah.
                    renderedY = 0;
                } else {
                    renderedHeight = A4_HEIGHT_MM;
                    renderedWidth = renderedHeight * canvasRatio;
                    renderedX = Math.max((A4_WIDTH_MM - renderedWidth) / 2, 0);
                }

                pdf.addImage(
                    imageData,
                    'PNG',
                    renderedX,
                    renderedY,
                    renderedWidth,
                    renderedHeight,
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