@extends('layouts.public')

@section('title', 'Payment Invoice')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/payments/invoice.css') }}">
@endpush

@section('content')
{{--
    Fallback link supaya style invoice tetap kebaca walaupun layouts.public belum punya @stack('styles').
    Kalau layout sudah support @stack('styles'), browser akan mengabaikan efek duplikasinya.
--}}
<link rel="stylesheet" href="{{ asset('css/payments/invoice.css') }}">

@php
    $order = $order ?? $payment->order;

    // Public invoice harus memakai full pricing columns + source relations dari orders.
    // Ini penting supaya workshop tidak lagi dirender seperti program.
    if (!empty($payment->order_id)) {
        try {
            $freshOrder = \App\Models\Order::with([
                'student:id,full_name,email,phone,city',
                'batch:id,program_id,name,start_date,end_date',
                'batch.program:id,name',
                'workshop',
            ])->find($payment->order_id);

            if ($freshOrder) {
                $order = $freshOrder;
                $payment->setRelation('order', $freshOrder);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    $student = $student ?? $order?->student;
    $batch = $batch ?? $order?->batch;
    $program = $program ?? $batch?->program;
    $schedule = $schedule ?? $payment->paymentSchedule;
    $sourceContext = $sourceContext ?? [];

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

    $normalizeSourceType = function ($value) {
        return \Illuminate\Support\Str::of((string) $value)
            ->lower()
            ->replace(['-', ' '], '_')
            ->squish()
            ->toString();
    };

    $firstFilledAttribute = function ($model, array $keys) {
        if (!$model) {
            return null;
        }

        foreach ($keys as $key) {
            $value = data_get($model, $key);

            if (filled($value)) {
                return (string) $value;
            }
        }

        return null;
    };

    $sourceType = $sourceType ?? data_get($sourceContext, 'source_type') ?? data_get($order, 'order_type');
    $sourceTypeLabel = $sourceTypeLabel ?? data_get($sourceContext, 'source_type_label');
    $sourceItemName = $sourceItemName ?? data_get($sourceContext, 'source_item_name');
    $sourceDescription = $sourceDescription ?? data_get($sourceContext, 'source_description');

    $normalizedSourceType = $normalizeSourceType($sourceType);
    $normalizedOrderType = $normalizeSourceType(data_get($order, 'order_type'));
    $sourceTypeLabelText = \Illuminate\Support\Str::lower((string) $sourceTypeLabel);

    $isWorkshopDocument = (bool) ($isWorkshopDocument ?? false)
        || (bool) ($isSimpleWorkshopDocument ?? false)
        || in_array($normalizedSourceType, ['workshop', 'workshops'], true)
        || in_array($normalizedOrderType, ['workshop', 'workshops'], true)
        || filled(data_get($order, 'workshop_id'))
        || filled(data_get($order, 'workshop.id'))
        || \Illuminate\Support\Str::contains($sourceTypeLabelText, 'workshop');

    $workshopName = $workshopName
        ?? $firstFilledAttribute($order, [
            'workshop.title',
            'workshop.name',
            'workshop.workshop_title',
            'workshop.theme_name',
            'workshop.theme',
            'workshop.topic',
            'workshop.subject',
        ])
        ?? $sourceItemName
        ?? $sourceDescription
        ?? null;

    if ($isWorkshopDocument) {
        $candidateWorkshopName = \Illuminate\Support\Str::lower((string) $workshopName);

        if (!filled($workshopName) || in_array($candidateWorkshopName, ['workshop', 'workshops', 'program', 'payment', 'flexlabs payment'], true)) {
            $workshopId = data_get($order, 'workshop_id') ?: data_get($order, 'workshop.id');
            $workshopName = $workshopId ? 'Workshop #' . $workshopId : 'FlexLabs Workshop';
        }
    }

    $isPaid = $isPaid ?? $payment->status === 'paid';
    $isExpired = $isExpired ?? (!empty($payment->expired_at) && now()->greaterThan($payment->expired_at) && $payment->status !== 'paid');
    $canPay = $canPay ?? (!$isPaid && !$isExpired && !empty($payment->payment_url));

    $rawOriginalPrice = (float) ($order?->original_price ?? 0);
    $rawDiscount = (float) ($order?->discount ?? 0);
    $rawFinalPrice = (float) ($order?->final_price ?? 0);
    $currentInvoiceAmount = (float) ($currentInvoiceAmount ?? $currentDocumentAmount ?? $payment->amount ?? $schedule?->amount ?? 0);

    $controllerItems = collect($items ?? $financialSummaryRows ?? $invoiceBreakdownRows ?? [])->values();

    if ($isWorkshopDocument) {
        $currentInvoiceAmount = $currentInvoiceAmount > 0
            ? $currentInvoiceAmount
            : max((float) ($schedule?->amount ?? 0), (float) ($order?->final_price ?? 0), (float) ($order?->original_price ?? 0));

        $remainingBalance = 0;
        $remainingBalanceLabel = null;
        $showRemainingBalance = false;
        $shouldShowPaymentBreakdown = false;
        $isSimpleWorkshopDocument = true;
        // Khusus workshop, summary utama harus tampil sebagai Workshop Fee,
        // bukan Current Invoice Amount dari controller/fallback lama.
        $currentDocumentAmountLabel = 'Workshop Fee';
        $documentNote = $documentNote ?? 'This invoice is for the selected FlexLabs workshop registration.';

        $items = $controllerItems
            ->filter(function ($item) {
                $type = (string) ($item['type'] ?? '');
                $label = \Illuminate\Support\Str::lower((string) ($item['description'] ?? $item['label'] ?? ''));

                return $type === 'workshop_fee'
                    || $type === 'workshop_payment'
                    || \Illuminate\Support\Str::contains($label, 'workshop');
            })
            ->values();

        if ($items->isEmpty()) {
            $items = collect([
                [
                    'description' => 'Workshop Fee',
                    'meta' => $workshopName,
                    'rate' => $currentInvoiceAmount,
                    'amount' => $currentInvoiceAmount,
                    'type' => 'workshop_fee',
                    'is_emphasis' => true,
                ],
            ]);
        }
    } else {
        if ($rawFinalPrice <= 0) {
            $rawFinalPrice = max($currentInvoiceAmount, 0);
        }

        $programDiscount = $programDiscount ?? ($rawDiscount > 0
            ? $rawDiscount
            : max($rawOriginalPrice - $rawFinalPrice, 0));

        $normalProgramFee = $normalProgramFee ?? ($rawOriginalPrice > 0
            ? $rawOriginalPrice
            : max($rawFinalPrice + $programDiscount, $rawFinalPrice, $currentInvoiceAmount));

        $finalTuitionFee = $finalTuitionFee ?? ($rawFinalPrice > 0
            ? $rawFinalPrice
            : max($normalProgramFee - $programDiscount, 0));

        if ($programDiscount <= 0 && $normalProgramFee > $finalTuitionFee) {
            $programDiscount = $normalProgramFee - $finalTuitionFee;
        }

        if (!isset($previousPaymentReceived)) {
            $previousPaymentReceived = 0;

            if (!empty($order?->id)) {
                try {
                    $previousPaymentReceived = (float) \App\Models\Payment::query()
                        ->where('order_id', $order->id)
                        ->where('status', 'paid')
                        ->where('id', '<', $payment->id)
                        ->sum('amount');
                } catch (\Throwable $e) {
                    report($e);
                    $previousPaymentReceived = 0;
                }
            }
        }

        $previousPaymentReceived = (float) $previousPaymentReceived;
        $remainingBalance = (float) ($remainingBalance ?? max($finalTuitionFee - $previousPaymentReceived - $currentInvoiceAmount, 0));
        $remainingBalanceLabel = $remainingBalanceLabel ?? 'Remaining Balance After This Invoice';
        $showRemainingBalance = isset($showRemainingBalance) ? (bool) $showRemainingBalance : true;

        $programBatchLabel = collect([
            $program?->name,
            $batch?->name,
        ])->filter()->implode(' · ');

        $currentInvoiceDetail = $schedule?->title ?: 'Payment requested on this invoice';

        if (!empty($schedule?->due_date)) {
            $currentInvoiceDetail .= ' · Due ' . $formatDate($schedule->due_date);
        }

        $items = $controllerItems->isNotEmpty()
            ? $controllerItems
            : collect([
                [
                    'description' => 'Normal Program Fee',
                    'meta' => $programBatchLabel ?: 'FlexLabs Program',
                    'rate' => $normalProgramFee,
                    'amount' => $normalProgramFee,
                    'is_emphasis' => false,
                ],
                [
                    'description' => 'Special Program Discount',
                    'meta' => 'Approved program discount or payment adjustment',
                    'rate' => -abs($programDiscount),
                    'amount' => -abs($programDiscount),
                    'is_emphasis' => false,
                ],
                [
                    'description' => 'Final Tuition Fee',
                    'meta' => 'Program fee after discount or adjustment',
                    'rate' => $finalTuitionFee,
                    'amount' => $finalTuitionFee,
                    'is_emphasis' => true,
                ],
                [
                    'description' => 'Previous Payment Received',
                    'meta' => 'Confirmed paid amount recorded before this document',
                    'rate' => -abs($previousPaymentReceived),
                    'amount' => -abs($previousPaymentReceived),
                    'is_emphasis' => false,
                ],
                [
                    'description' => 'Current Invoice Amount',
                    'meta' => $currentInvoiceDetail,
                    'rate' => $currentInvoiceAmount,
                    'amount' => $currentInvoiceAmount,
                    'is_emphasis' => true,
                ],
                [
                    'description' => $remainingBalanceLabel,
                    'meta' => 'Outstanding amount assuming this invoice is completed',
                    'rate' => $remainingBalance,
                    'amount' => $remainingBalance,
                    'is_emphasis' => true,
                ],
            ])->values();

        $documentNote = $documentNote ?? 'The final tuition fee reflects the approved program discount or payment adjustment. Remaining balance shows the outstanding amount after this invoice.';
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

<div class="container py-4 invoice-shell">
    @if ($isPaid)
        <div class="alert alert-success no-print mb-4">
            <strong><i class="bi bi-check-circle-fill me-1"></i> Payment completed.</strong>
            Pembayaran untuk invoice ini sudah berhasil diterima.
        </div>
    @elseif ($isExpired)
        <div class="alert alert-warning no-print mb-4">
            <strong><i class="bi bi-exclamation-triangle-fill me-1"></i> Payment link expired.</strong>
            Silakan hubungi admin FlexLabs untuk mendapatkan link pembayaran baru.
        </div>
    @elseif (!$payment->payment_url)
        <div class="alert alert-secondary no-print mb-4">
            <strong><i class="bi bi-clock-history me-1"></i> Payment link belum tersedia.</strong>
            Silakan hubungi admin FlexLabs.
        </div>
    @endif

    <div class="invoice-toolbar no-print">
        <div>
            <h4 class="mb-1">Invoice</h4>
            <small class="text-muted">{{ $payment->invoice_number }}</small>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <button
                type="button"
                class="btn btn-primary"
                data-pdf-download
                data-pdf-target="#invoiceDocument"
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
                <a href="{{ $payment->payment_url }}" rel="noopener noreferrer" class="btn btn-success">
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
                                                @if ($isWorkshopDocument && !empty($workshopName))
                                                    <div class="invoice-item-subtitle">{{ $workshopName }}</div>
                                                @else
                                                    @if (!empty($program?->name))
                                                        <div class="invoice-item-subtitle">{{ $program->name }} Program</div>
                                                    @endif

                                                    @if (!empty($batch?->name))
                                                        <div class="invoice-item-subtitle">{{ $batch->name }}</div>
                                                    @endif
                                                @endif
                                            @endif
                                        </td>
                                        <td class="text-end text-nowrap">{{ $formatSignedMoney($itemRate) }}</td>
                                        <td class="text-end text-nowrap">{{ $formatSignedMoney($itemAmount) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td>
                                            <div class="invoice-item-title">{{ $isWorkshopDocument ? 'Workshop Fee' : 'Program Payment' }}</div>

                                            @if ($isWorkshopDocument && !empty($workshopName))
                                                <div class="invoice-item-subtitle">{{ $workshopName }}</div>
                                            @else
                                                @if (!empty($program?->name))
                                                    <div class="invoice-item-subtitle">{{ $program->name }} Program</div>
                                                @endif

                                                @if (!empty($batch?->name))
                                                    <div class="invoice-item-subtitle">{{ $batch->name }}</div>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="text-end text-nowrap">{{ $formatSignedMoney($currentInvoiceAmount) }}</td>
                                        <td class="text-end text-nowrap">{{ $formatSignedMoney($currentInvoiceAmount) }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="invoice-summary-wrap">
                        <table class="invoice-summary-table">
                            <tr @class([
                                'invoice-summary-total' => !$showRemainingBalance,
                            ])>
                                <td>{{ $currentDocumentAmountLabel ?? 'Current Invoice Amount' }}</td>
                                <td>{{ $formatMoney($currentInvoiceAmount) }}</td>
                            </tr>

                            @if ($showRemainingBalance)
                                <tr class="invoice-summary-total">
                                    <td>{{ $remainingBalanceLabel }}</td>
                                    <td>{{ $formatMoney($remainingBalance) }}</td>
                                </tr>
                            @endif
                        </table>
                    </div>

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

                // Invoice memang didesain sebagai 1 halaman A4.
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
