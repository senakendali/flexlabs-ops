@extends('layouts.public')

@section('title', 'Payment Invoice')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/payments/invoice.css') }}">
@endpush

@section('content')
@php
    $order = $order ?? $payment->order;
    $student = $student ?? $order?->student;
    $batch = $batch ?? $order?->batch;
    $program = $program ?? $batch?->program;
    $schedule = $schedule ?? $payment->paymentSchedule;

    $isPaid = $isPaid ?? $payment->status === 'paid';

    if (!isset($isExpired)) {
        $isExpired = false;

        if ($payment->status === 'expired') {
            $isExpired = true;
        } elseif ($payment->status !== 'paid' && !empty($payment->expired_at)) {
            $isExpired = \Carbon\Carbon::parse($payment->expired_at)->isPast();
        }
    }

    $canPay = $canPay ?? (!$isPaid && !$isExpired && !empty($payment->payment_url));

    $publicPaymentLink = $publicPaymentLink ?? ($payment->public_token
        ? route('public.payments.show', $payment->public_token)
        : null);

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

    $currentInvoiceAmount = (float) ($currentInvoiceAmount ?? $currentDocumentAmount ?? $grandTotal ?? $payment->amount ?? 0);
    $remainingBalance = isset($remainingBalance) ? (float) $remainingBalance : null;
    $remainingBalanceLabel = $remainingBalanceLabel ?? 'Remaining Balance After This Invoice';
    $documentNote = $documentNote ?? 'The final tuition fee reflects the approved program discount or payment adjustment. Remaining balance shows the outstanding amount after this invoice.';

    $items = collect($items ?? $financialRows ?? $financialSummaryRows ?? $invoiceBreakdownRows ?? [])->values();

    if ($items->isEmpty()) {
        $normalProgramFee = (float) ($normalProgramFee ?? $order?->original_price ?? $order?->final_price ?? $payment->amount ?? 0);
        $programDiscount = (float) ($programDiscount ?? $order?->discount ?? 0);
        $finalTuitionFee = (float) ($finalTuitionFee ?? $order?->final_price ?? max($normalProgramFee - $programDiscount, 0));
        $previousPaymentReceived = (float) ($previousPaymentReceived ?? 0);
        $currentInvoiceAmount = (float) ($payment->amount ?? 0);
        $remainingBalance = max($finalTuitionFee - $previousPaymentReceived - $currentInvoiceAmount, 0);

        $detailDescription = collect([$program?->name, $batch?->name])
            ->filter(fn ($value) => filled($value))
            ->implode(' · ') ?: 'FlexLabs Program';

        $items = collect([
            [
                'label' => 'Normal Program Fee',
                'description' => 'Normal Program Fee',
                'details' => $detailDescription,
                'amount' => $normalProgramFee,
                'is_negative' => false,
                'is_emphasis' => false,
            ],
            [
                'label' => 'Special Program Discount',
                'description' => 'Special Program Discount',
                'details' => 'Approved program discount or payment adjustment',
                'amount' => -1 * abs($programDiscount),
                'is_negative' => $programDiscount > 0,
                'is_emphasis' => false,
            ],
            [
                'label' => 'Final Tuition Fee',
                'description' => 'Final Tuition Fee',
                'details' => 'Program fee after discount or adjustment',
                'amount' => $finalTuitionFee,
                'is_negative' => false,
                'is_emphasis' => true,
            ],
            [
                'label' => 'Previous Payment Received',
                'description' => 'Previous Payment Received',
                'details' => 'Confirmed paid amount recorded before this invoice',
                'amount' => -1 * abs($previousPaymentReceived),
                'is_negative' => $previousPaymentReceived > 0,
                'is_emphasis' => false,
            ],
            [
                'label' => 'Current Invoice Amount',
                'description' => 'Current Invoice Amount',
                'details' => $schedule?->title ?: 'Payment requested on this invoice',
                'amount' => $currentInvoiceAmount,
                'is_negative' => false,
                'is_emphasis' => true,
            ],
            [
                'label' => $remainingBalanceLabel,
                'description' => $remainingBalanceLabel,
                'details' => 'Outstanding amount assuming this invoice is completed',
                'amount' => $remainingBalance,
                'is_negative' => false,
                'is_emphasis' => true,
            ],
        ]);
    }

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

<div class="container py-4 invoice-shell public-payment-wrapper">
    @if ($isPaid)
        <div class="alert alert-success public-payment-alert no-print mb-4">
            <div class="public-payment-alert-title">
                <i class="bi bi-check-circle-fill me-1"></i>
                Payment completed
            </div>
            <div class="public-payment-alert-text">
                Pembayaran untuk invoice ini sudah berhasil diterima.
            </div>
        </div>
    @elseif ($isExpired)
        <div class="alert alert-warning public-payment-alert no-print mb-4">
            <div class="public-payment-alert-title">
                <i class="bi bi-exclamation-triangle-fill me-1"></i>
                Payment link expired
            </div>
            <div class="public-payment-alert-text">
                Link pembayaran sudah tidak aktif. Silakan hubungi admin FlexLabs untuk mendapatkan link pembayaran baru.
            </div>
        </div>
    @elseif (empty($payment->payment_url))
        <div class="alert alert-secondary public-payment-alert no-print mb-4">
            <div class="public-payment-alert-title">
                <i class="bi bi-clock-history me-1"></i>
                Payment link belum tersedia
            </div>
            <div class="public-payment-alert-text">
                Link pembayaran untuk invoice ini belum aktif. Silakan hubungi admin FlexLabs.
            </div>
        </div>
    @endif

    <div class="invoice-toolbar no-print">
        <div>
            <div class="public-payment-eyebrow">FlexLabs Payment</div>
            <h4 class="mb-1">Student Payment Invoice</h4>
            <small class="text-muted">{{ $payment->invoice_number ?: '-' }}</small>
        </div>

        <div class="d-flex gap-2 flex-wrap public-payment-actions">
            <button
                type="button"
                class="btn btn-light border"
                data-pdf-download
                data-pdf-target="#publicInvoiceDocument"
                data-pdf-filename="{{ $invoicePdfFilename }}"
            >
                <i class="bi bi-download me-1"></i> Download PDF
            </button>

            @if ($isPaid)
                <button type="button" class="btn btn-success" disabled>
                    <i class="bi bi-check-circle me-1"></i> Already Paid
                </button>
            @elseif ($isExpired)
                <button type="button" class="btn btn-secondary" disabled>
                    <i class="bi bi-x-circle me-1"></i> Link Expired
                </button>
            @elseif ($canPay)
                <a href="{{ $payment->payment_url }}" rel="noopener noreferrer" class="btn btn-primary btn-brand">
                    <i class="bi bi-credit-card me-1"></i> Pay Now
                </a>
            @else
                <button type="button" class="btn btn-outline-secondary" disabled>
                    <i class="bi bi-clock-history me-1"></i> Payment Link Not Ready
                </button>
            @endif
        </div>
    </div>

    <div class="invoice-page">
        <div id="publicInvoiceDocument" class="invoice-card">
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
                        <span class="invoice-number-value">{{ $payment->invoice_number ?: '-' }}</span>
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
                    <div class="table-responsive invoice-table-wrap">
                        <table class="table invoice-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th>Details</th>
                                    <th class="text-end invoice-table-amount">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($items as $item)
                                    @php
                                        $itemLabel = $item['label'] ?? $item['description'] ?? '-';
                                        $itemDetails = $item['details'] ?? $item['meta'] ?? (($item['label'] ?? null) ? ($item['description'] ?? null) : null);
                                        $itemAmount = (float) ($item['amount'] ?? 0);
                                        $isEmphasis = (bool) ($item['is_emphasis'] ?? false);
                                    @endphp
                                    <tr @class([
                                        'fw-semibold' => $isEmphasis,
                                    ])>
                                        <td>
                                            <div class="invoice-item-title">{{ $itemLabel }}</div>
                                        </td>
                                        <td>
                                            @if (!empty($itemDetails) && $itemDetails !== $itemLabel)
                                                <div class="invoice-item-subtitle">{{ $itemDetails }}</div>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td @class([
                                            'text-end',
                                            'text-nowrap',
                                            'text-muted' => $itemAmount == 0,
                                        ])>
                                            {{ $formatSignedMoney($itemAmount) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td>
                                            <div class="invoice-item-title">Program Payment</div>
                                        </td>
                                        <td>
                                            <div class="invoice-item-subtitle">{{ $program->name ?? 'FlexLabs Program' }}</div>
                                        </td>
                                        <td class="text-end text-nowrap">{{ $formatSignedMoney($currentInvoiceAmount) }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="invoice-summary-wrap">
                        <table class="invoice-summary-table">
                            <tr>
                                <td>Current Invoice Amount</td>
                                <td>{{ $formatMoney($currentInvoiceAmount) }}</td>
                            </tr>

                            @if ((float) ($tax ?? 0) > 0)
                                <tr>
                                    <td>Tax</td>
                                    <td>{{ $formatMoney($tax ?? 0) }}</td>
                                </tr>
                            @endif

                            <tr class="invoice-summary-total">
                                <td>{{ $remainingBalanceLabel }}</td>
                                <td>{{ $formatMoney($remainingBalance ?? 0) }}</td>
                            </tr>
                        </table>
                    </div>

                    <div class="invoice-note-box mt-3">
                        {{ $documentNote }}
                    </div>
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

                    <div class="invoice-info-line">
                        <span class="invoice-info-label">Note</span>
                        <span class="invoice-info-colon">:</span>
                        <span class="invoice-info-value">
                            {{ $payment->notes ?: 'Please complete your payment using the Pay Now button above.' }}
                        </span>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <div class="public-payment-bottom-action no-print">
        @if ($isPaid)
            <button type="button" class="btn btn-success btn-lg px-5" disabled>
                <i class="bi bi-check-circle me-1"></i> Already Paid
            </button>
        @elseif ($isExpired)
            <button type="button" class="btn btn-secondary btn-lg px-5" disabled>
                <i class="bi bi-x-circle me-1"></i> Link Expired
            </button>
        @elseif ($canPay)
            <a href="{{ $payment->payment_url }}" rel="noopener noreferrer" class="btn btn-brand btn-primary btn-lg px-5">
                <i class="bi bi-credit-card me-1"></i> Pay Now
            </a>
        @else
            <button type="button" class="btn btn-outline-secondary btn-lg px-5" disabled>
                <i class="bi bi-clock-history me-1"></i> Payment Link Not Ready
            </button>
        @endif
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
