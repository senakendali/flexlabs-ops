@extends('layouts.app-dashboard')

@section('title', 'Invoice')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/payments/invoice.css') }}">
    <style>
        .invoice-title {
            font-family: "Arial Black", "Arial Bold", Arial, Helvetica, sans-serif !important;
            font-weight: 900 !important;
            font-style: normal !important;
            letter-spacing: -0.065em !important;
            line-height: 0.9 !important;
            color: #000000 !important;
            text-transform: uppercase;
        }

        #invoiceDocument.invoice-exporting .invoice-table {
            --bs-table-border-color: transparent !important;
            border-collapse: collapse !important;
            border-spacing: 0 !important;
            border: 0 !important;
        }

        #invoiceDocument.invoice-exporting .invoice-table > :not(caption) > * > *,
        #invoiceDocument.invoice-exporting .invoice-table thead,
        #invoiceDocument.invoice-exporting .invoice-table tbody,
        #invoiceDocument.invoice-exporting .invoice-table tr,
        #invoiceDocument.invoice-exporting .invoice-table th,
        #invoiceDocument.invoice-exporting .invoice-table td {
            border: 0 !important;
            border-width: 0 !important;
            border-color: transparent !important;
            box-shadow: none !important;
            outline: 0 !important;
        }

        /*
         * html2canvas dapat membuat garis tipis di antara setiap TH ketika
         * background ungu dirender per-cell lalu diperkecil ke ukuran A4.
         * Render warna header sebagai satu bidang pada TR, bukan per-cell.
         */
        #invoiceDocument.invoice-exporting .invoice-table thead,
        #invoiceDocument.invoice-exporting .invoice-table thead tr {
            background: #5b3e8e !important;
            background-color: #5b3e8e !important;
        }

        #invoiceDocument.invoice-exporting .invoice-table thead th {
            background: transparent !important;
            background-color: transparent !important;
            background-image: none !important;
            background-clip: border-box !important;
        }

        #invoiceDocument.invoice-exporting .invoice-table th::before,
        #invoiceDocument.invoice-exporting .invoice-table th::after,
        #invoiceDocument.invoice-exporting .invoice-table td::before,
        #invoiceDocument.invoice-exporting .invoice-table td::after {
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

        .group-tax-summary .group-total-due-row td {
            padding-top: 11px;
            color: #1f2937;
            font-size: 15px;
            font-weight: 800;
        }
    </style>
@endpush

@section('content')
@php
    $publicPaymentLink = $payment->public_token
        ? route('public.payments.show', $payment->public_token)
        : null;

    $invoiceDate = $invoiceDate ?? ($payment->payment_date ?: $payment->created_at);

    $formatDate = function ($date, string $format = 'd F Y') {
        if (empty($date)) {
            return '-';
        }

        return \Carbon\Carbon::parse($date)->format($format);
    };

    $formatMoney = function ($amount) {
        return 'Rp ' . number_format((float) $amount, 0, ',', '.');
    };

    $formatSignedMoney = function ($amount) {
        $amount = (float) $amount;
        $prefix = $amount < 0 ? '-Rp ' : 'Rp ';

        return $prefix . number_format(abs($amount), 0, ',', '.');
    };

    $items = collect($items ?? $financialSummaryRows ?? [])->values();

    $isWorkshopDocument = (bool) ($isWorkshopDocument ?? $isSimpleWorkshopDocument ?? false);
    $isSimpleWorkshopDocument = (bool) ($isSimpleWorkshopDocument ?? $isWorkshopDocument);
    $shouldShowPaymentBreakdown = (bool) ($shouldShowPaymentBreakdown ?? !$isSimpleWorkshopDocument);
    $showRemainingBalance = (bool) ($showRemainingBalance ?? !$isWorkshopDocument);

    $sourceTypeLabel = $sourceTypeLabel
        ?? ($sourceContext['source_type_label'] ?? ($isWorkshopDocument ? 'Workshop' : 'Program'));

    $sourceItemName = $sourceItemName
        ?? ($sourceContext['source_item_name'] ?? null);

    $sourceDescription = $sourceDescription
        ?? ($sourceContext['source_description'] ?? null);

    $cleanSourceValue = function ($value) {
        if (!filled($value)) {
            return null;
        }

        $value = trim((string) $value);
        $normalized = \Illuminate\Support\Str::of($value)
            ->lower()
            ->squish()
            ->toString();

        return in_array($normalized, ['workshop', 'workshops', 'program', 'payment', 'flexlabs payment'], true)
            ? null
            : $value;
    };

    $displaySourceTypeLabel = $isWorkshopDocument
        ? 'Workshop'
        : ($sourceTypeLabel ?: 'Program');

    $displaySourceName = collect([
            $workshopName ?? null,
            $sourceItemName,
            $sourceDescription,
            $program->name ?? null,
            $batch->name ?? null,
        ])
        ->map(fn ($value) => $cleanSourceValue($value))
        ->filter()
        ->first() ?: 'FlexLabs Payment';

    $currentDocumentAmountLabel = $isWorkshopDocument
        ? 'Workshop Fee'
        : 'Current Invoice Amount';

    $remainingBalanceLabel = $remainingBalanceLabel ?? 'Remaining Balance After This Invoice';

    $documentNote = $documentNote ?? ($isWorkshopDocument
        ? 'This invoice is for the selected FlexLabs workshop registration.'
        : 'The final tuition fee reflects the approved program discount or payment adjustment. Remaining balance shows the outstanding amount after this invoice.');

    $paymentMethod = $payment->payment_method
        ?: ($payment->gateway_provider ? ucfirst($payment->gateway_provider) : 'Payment Link');

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

    $invoicePdfFilename = 'invoice-' . \Illuminate\Support\Str::slug((string) ($payment->invoice_number ?? 'document')) . '.pdf';
@endphp

<div class="container py-4 invoice-shell">
    <div class="invoice-toolbar no-print">
        <div>
            <h4 class="mb-1">Invoice</h4>
            <small class="text-muted">{{ $payment->invoice_number }}</small>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('payments.index') }}" class="btn btn-light border">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>

            <button
                type="button"
                class="btn btn-primary"
                data-pdf-download
                data-pdf-target="#invoiceDocument"
                data-pdf-filename="{{ $invoicePdfFilename }}"
            >
                <i class="bi bi-download me-1"></i> Download PDF
            </button>

            <button
                type="button"
                class="btn btn-outline-success"
                onclick="sendPaymentLinkViaWhatsApp()"
                @disabled(empty($student?->phone) || empty($publicPaymentLink))
                title="{{ empty($student?->phone) ? 'Customer phone number is not available.' : (empty($publicPaymentLink) ? 'Payment link is not available yet.' : 'Send payment link via WhatsApp') }}"
            >
                <i class="bi bi-whatsapp me-1"></i> Send Payment Link
            </button>
        </div>
    </div>

    <div id="waAlert" class="alert alert-warning d-none no-print mb-4"></div>

    <div class="invoice-page">
        <div id="invoiceDocument" class="invoice-card">
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
                        <span class="invoice-number-value">{{ $payment->invoice_number }}</span>
                    </div>
                </header>

                <h1 class="invoice-title">INVOICE</h1>

                <div class="invoice-date-line invoice-info-line">
                    <span class="invoice-info-label">Date</span>
                    <span class="invoice-info-colon">:</span>
                    <span class="invoice-info-value">{{ $formatDate($invoiceDate) }}</span>
                </div>

                <section class="invoice-parties">
                    <div class="invoice-party-card">
                        <h2>Billed to</h2>

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
                        <h2>From</h2>

                        <div class="invoice-party-name">{{ $companyName }}</div>
                        <div class="invoice-company-address">
                            @foreach ($companyAddressLines as $addressLine)
                                <div>{{ $addressLine }}</div>
                            @endforeach
                        </div>
                    </div>
                </section>

                <section class="invoice-table-section">
                    @php
                        $groupOrderItems = collect($groupOrderItems ?? [])->values();
                    @endphp

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
                                @forelse ($items as $item)
                                    @php
                                        $itemTitle = $item['description'] ?? $item['label'] ?? '-';
                                        $itemDetail = $item['meta'] ?? $item['details'] ?? null;
                                        $itemRate = (float) ($item['rate'] ?? $item['amount'] ?? 0);
                                        $itemAmount = (float) ($item['amount'] ?? 0);
                                        $isEmphasis = (bool) ($item['is_emphasis'] ?? false);
                                    @endphp
                                    <tr @class([
                                        'fw-semibold' => $isEmphasis,
                                    ])>
                                        <td>
                                            <div class="invoice-item-title">{{ $itemTitle }}</div>

                                            @if (!empty($itemDetail))
                                                <div class="invoice-item-subtitle">{{ $itemDetail }}</div>
                                            @else
                                                @if (!empty($program?->name))
                                                    <div class="invoice-item-subtitle">{{ $program->name }} Program</div>
                                                @endif

                                                @if (!empty($batch?->name))
                                                    <div class="invoice-item-subtitle">{{ $batch->name }}</div>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="text-end text-nowrap">{{ $formatSignedMoney($itemRate) }}</td>
                                        <td class="text-end text-nowrap">{{ $formatSignedMoney($itemAmount) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td>
                                            <div class="invoice-item-title">
                                                {{ $isWorkshopDocument ? 'Workshop Fee' : $displaySourceTypeLabel . ' Payment' }}
                                            </div>

                                            @if (!empty($displaySourceName))
                                                <div class="invoice-item-subtitle">{{ $displaySourceName }}</div>
                                            @endif

                                            @if (!$isWorkshopDocument && !empty($batch?->name))
                                                <div class="invoice-item-subtitle">{{ $batch->name }}</div>
                                            @endif
                                        </td>
                                        <td class="text-end text-nowrap">{{ $formatSignedMoney($grandTotal ?? 0) }}</td>
                                        <td class="text-end text-nowrap">{{ $formatSignedMoney($grandTotal ?? 0) }}</td>
                                    </tr>
                                @endforelse
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
                                <tr class="group-total-due-row">
                                    <td>Total Due</td>
                                    <td>{{ $formatMoney($grandTotal ?? $currentInvoiceAmount ?? 0) }}</td>
                                </tr>
                            </table>
                        </div>
                    @else
                    <div class="invoice-summary-wrap">
                        <table class="invoice-summary-table">
                            <tr @class([
                                'invoice-summary-total' => !$showRemainingBalance,
                            ])>
                                <td>{{ $currentDocumentAmountLabel }}</td>
                                <td>{{ $formatMoney($currentInvoiceAmount ?? $grandTotal ?? 0) }}</td>
                            </tr>

                            @if ((float) ($tax ?? 0) > 0)
                                <tr>
                                    <td>Tax</td>
                                    <td>{{ $formatMoney($tax ?? 0) }}</td>
                                </tr>
                            @endif

                            @if ($showRemainingBalance)
                                <tr class="invoice-summary-total">
                                    <td>{{ $remainingBalanceLabel }}</td>
                                    <td>{{ $formatMoney($remainingBalance ?? 0) }}</td>
                                </tr>
                            @endif
                        </table>
                    </div>
                    @endif

                    <!--div class="invoice-note-box mt-3">
                        {{ $documentNote }}
                    </div-->
                </section>

                <section class="invoice-payment-section">
                    <div class="invoice-info-line">
                        <span class="invoice-info-label">Payment method</span>
                        <span class="invoice-info-colon">:</span>
                        <span class="invoice-info-value">{{ $paymentMethod }}</span>
                    </div>

                    @if (!empty($payment->reference_number))
                        <div class="invoice-info-line">
                            <span class="invoice-info-label">Reference no</span>
                            <span class="invoice-info-colon">:</span>
                            <span class="invoice-info-value">{{ $payment->reference_number }}</span>
                        </div>
                    @endif

                    @if (!empty($payment->expired_at))
                        <div class="invoice-info-line">
                            <span class="invoice-info-label">Payment due</span>
                            <span class="invoice-info-colon">:</span>
                            <span class="invoice-info-value">{{ $formatDate($payment->expired_at, 'd F Y H:i') }}</span>
                        </div>
                    @endif

                    <div class="invoice-info-line">
                        <span class="invoice-info-label">Status</span>
                        <span class="invoice-info-colon">:</span>
                        <span class="invoice-info-value">{{ \Illuminate\Support\Str::headline((string) $payment->status) }}</span>
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
    function showWaAlert(message, type = 'warning') {
        const alertEl = document.getElementById('waAlert');

        alertEl.className = `alert alert-${type} no-print mb-4`;
        alertEl.textContent = message;
        alertEl.classList.remove('d-none');

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function normalizeWhatsAppNumber(phone) {
        if (!phone) return '';

        let normalized = String(phone).trim().replace(/[^\d+]/g, '');

        if (!normalized) return '';

        if (normalized.startsWith('+')) {
            normalized = normalized.substring(1);
        }

        if (normalized.startsWith('0')) {
            normalized = '62' + normalized.substring(1);
        } else if (normalized.startsWith('8')) {
            normalized = '62' + normalized;
        }

        return normalized;
    }

    function buildPaymentMessage() {
        const customerName = {{ Js::from($student->full_name ?? 'Customer') }};
        const sourceTypeLabel = {{ Js::from($displaySourceTypeLabel ?? 'Program') }};
        const sourceItemName = {{ Js::from($displaySourceName ?? '-') }};
        const batchName = {{ Js::from($batch->name ?? '-') }};
        const invoiceNumber = {{ Js::from($payment->invoice_number ?? '-') }};
        const amount = {{ Js::from($formatMoney($currentInvoiceAmount ?? $grandTotal ?? 0)) }};
        const remainingBalance = {{ Js::from($formatMoney($remainingBalance ?? 0)) }};
        const showRemainingBalance = {{ Js::from((bool) $showRemainingBalance) }};
        const isWorkshopDocument = {{ Js::from((bool) $isWorkshopDocument) }};
        const paymentLink = {{ Js::from($publicPaymentLink ?? null) }};

        const lines = [];
        lines.push(`Halo ${customerName},`);
        lines.push('');
        lines.push('Berikut link pembayaran untuk invoice Anda.');
        lines.push('');
        lines.push(`Invoice: ${invoiceNumber}`);
        lines.push(`${sourceTypeLabel}: ${sourceItemName}`);

        if (!isWorkshopDocument && batchName && batchName !== '-') {
            lines.push(`Batch: ${batchName}`);
        }

        lines.push(`Nominal invoice: ${amount}`);

        if (showRemainingBalance) {
            lines.push(`Sisa pembayaran setelah invoice ini: ${remainingBalance}`);
        }

        lines.push('');
        lines.push('Silakan lakukan pembayaran melalui link berikut:');
        lines.push(paymentLink);
        lines.push('');
        lines.push('Terima kasih.');

        return lines.join('\n');
    }

    function sendPaymentLinkViaWhatsApp() {
        const customerPhone = {{ Js::from($student->phone ?? null) }};
        const paymentLink = {{ Js::from($publicPaymentLink ?? null) }};

        if (!customerPhone) {
            showWaAlert('Nomor WhatsApp customer belum tersedia.');
            return;
        }

        if (!paymentLink) {
            showWaAlert('Link pembayaran belum tersedia untuk invoice ini.');
            return;
        }

        const waNumber = normalizeWhatsAppNumber(customerPhone);

        if (!waNumber) {
            showWaAlert('Nomor WhatsApp customer tidak valid.');
            return;
        }

        const message = buildPaymentMessage();
        const waUrl = `https://wa.me/${waNumber}?text=${encodeURIComponent(message)}`;

        window.open(waUrl, '_blank', 'noopener');
    }

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
            const fallback = 'flexlabs-invoice.pdf';

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
                alert('Area invoice tidak ditemukan. Coba refresh halaman dulu, bro.');
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