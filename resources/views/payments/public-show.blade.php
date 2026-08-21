@extends('layouts.public-invoice')

@section('title', 'Payment Invoice')

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

        [x-cloak] {
            display: none !important;
        }

        #invoiceDocument.invoice-exporting {
            width: 794px !important;
            min-width: 794px !important;
            max-width: 794px !important;
            margin: 0 !important;
            box-shadow: none !important;
            transform: none !important;
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

        @page {
            size: A4 portrait;
            margin: 0;
        }
    </style>
@endpush

@section('content')

@php
    $order = $order ?? $payment->order;

    // Public invoice harus memakai full pricing columns + source relations dari orders.
    // Ini penting supaya workshop tidak lagi dirender seperti program.
    if (!empty($payment->order_id)) {
        try {
            $freshOrder = \App\Models\Order::with([
                'student:id,full_name,email,phone,city',
                'groupRegistration:id,registration_number,buyer_type,buyer_student_id,company_id,buyer_name,buyer_email,buyer_phone',
                'groupRegistration.company:id,name,tax_id,email,phone,address,pic_name,pic_email,pic_phone',
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

    $groupRegistration = $order?->groupRegistration;

    if ($groupRegistration) {
        // Pada Group Registration, buyer/payer tidak wajib menjadi student.
        // Karena orders.student_id memang null, identitas customer harus
        // diambil dari snapshot buyer pada group_registrations.
        $student = (object) [
            'id' => $groupRegistration->buyer_student_id,
            'full_name' => $groupRegistration->buyer_name
                ?: $groupRegistration->company?->name
                ?: 'Group Registration Buyer',
            'email' => $groupRegistration->buyer_email
                ?: $groupRegistration->company?->pic_email
                ?: $groupRegistration->company?->email,
            'phone' => $groupRegistration->buyer_phone
                ?: $groupRegistration->company?->pic_phone
                ?: $groupRegistration->company?->phone,
            'city' => null,
            'address' => $groupRegistration->company?->address,
            'buyer_type' => $groupRegistration->buyer_type,
            'registration_number' => $groupRegistration->registration_number,
            'company_name' => $groupRegistration->company?->name,
            'tax_id' => $groupRegistration->company?->tax_id,
        ];
    } else {
        $student = $student ?? $order?->student;
    }

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

    // Gunakan financial rows yang sama dengan admin invoice sebagai sumber utama.
    // Jangan memakai null-coalescing langsung ke $items karena empty array tetap
    // dianggap tersedia dan dapat menutupi financialSummaryRows yang lebih lengkap.
    $controllerItems = collect($financialSummaryRows ?? [])->values();

    if ($controllerItems->isEmpty()) {
        $controllerItems = collect($financialRows ?? [])->values();
    }

    if ($controllerItems->isEmpty()) {
        $controllerItems = collect($invoiceBreakdownRows ?? [])->values();
    }

    if ($controllerItems->isEmpty()) {
        $controllerItems = collect($items ?? [])->values();
    }

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
        $student->address ?? null,
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

<div class="min-h-screen bg-flex-page px-4 py-6 font-sans text-flex-ink sm:px-6 lg:px-8">
    <div class="mx-auto max-w-6xl">
        @if ($isPaid)
            <div class="no-print invoice-public-alert mb-4 rounded-2xl border-green-200 bg-green-50 px-5 py-4 text-sm text-green-800">
                <div class="flex items-start gap-3">
                    <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-green-600 text-white">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M16.704 5.29a1 1 0 0 1 .006 1.414l-7.25 7.31a1 1 0 0 1-1.42 0L3.29 9.224a1 1 0 1 1 1.42-1.408l4.04 4.073 6.54-6.593a1 1 0 0 1 1.414-.006Z" clip-rule="evenodd" />
                        </svg>
                    </span>
                    <div>
                        <div class="font-extrabold">Payment completed.</div>
                        <div class="mt-0.5 text-green-700">Pembayaran untuk invoice ini sudah berhasil diterima.</div>
                    </div>
                </div>
            </div>
        @elseif ($isExpired)
            <div class="no-print invoice-public-alert mb-4 rounded-2xl border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800">
                <div class="flex items-start gap-3">
                    <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-amber-600 text-white">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M8.485 2.495a1.75 1.75 0 0 1 3.03 0l6.28 10.88A1.75 1.75 0 0 1 16.28 16H3.72a1.75 1.75 0 0 1-1.515-2.625l6.28-10.88ZM10 6a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 6Zm0 7a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                        </svg>
                    </span>
                    <div>
                        <div class="font-extrabold">Payment link expired.</div>
                        <div class="mt-0.5 text-amber-700">Silakan hubungi admin FlexLabs untuk mendapatkan link pembayaran baru.</div>
                    </div>
                </div>
            </div>
        @elseif (!$payment->payment_url)
            <div class="no-print invoice-public-alert mb-4 rounded-2xl border-slate-200 bg-white px-5 py-4 text-sm text-slate-700">
                <div class="flex items-start gap-3">
                    <span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-500 text-white">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-12.25a.75.75 0 0 0-1.5 0V10c0 .214.092.418.252.56l2.75 2.438a.75.75 0 0 0 .996-1.122l-2.498-2.214V5.75Z" clip-rule="evenodd" />
                        </svg>
                    </span>
                    <div>
                        <div class="font-extrabold">Payment link belum tersedia.</div>
                        <div class="mt-0.5 text-slate-600">Silakan hubungi admin FlexLabs.</div>
                    </div>
                </div>
            </div>
        @endif

        <div class="no-print invoice-toolbar-card mb-6 flex flex-col gap-4 rounded-3xl p-4 backdrop-blur md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-flex-primary">FlexLabs Payment</p>
                <h1 class="mt-1 text-2xl font-black tracking-[-0.04em] text-flex-ink">Invoice</h1>
                <p class="mt-1 text-sm text-flex-muted">{{ $payment->invoice_number }}</p>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center sm:justify-end">
                <button
                    type="button"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-flex-primary px-5 py-3 text-sm font-extrabold text-white shadow-sm transition hover:bg-flex-primaryDark focus:outline-none focus:ring-4 focus:ring-flex-primary/20 disabled:cursor-not-allowed disabled:opacity-70"
                    data-pdf-download
                    data-pdf-target="#invoiceDocument"
                    data-pdf-filename="{{ $invoicePdfFilename }}"
                >
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path d="M10.75 2.75a.75.75 0 0 0-1.5 0v7.69L6.03 7.22a.75.75 0 0 0-1.06 1.06l4.5 4.5a.75.75 0 0 0 1.06 0l4.5-4.5a.75.75 0 1 0-1.06-1.06l-3.22 3.22V2.75Z" />
                        <path d="M3.5 13.75a.75.75 0 0 1 .75.75v1.25h11.5V14.5a.75.75 0 0 1 1.5 0v1.25A1.75 1.75 0 0 1 15.5 17.5h-11a1.75 1.75 0 0 1-1.75-1.75V14.5a.75.75 0 0 1 .75-.75Z" />
                    </svg>
                    Download PDF
                </button>

                @if ($isPaid)
                    <button type="button" class="inline-flex cursor-not-allowed items-center justify-center gap-2 rounded-2xl bg-green-600 px-5 py-3 text-sm font-extrabold text-white opacity-90" disabled>
                        Already Paid
                    </button>
                @elseif ($isExpired)
                    <button type="button" class="inline-flex cursor-not-allowed items-center justify-center gap-2 rounded-2xl bg-slate-400 px-5 py-3 text-sm font-extrabold text-white" disabled>
                        Link Expired
                    </button>
                @elseif ($canPay)
                    <a href="{{ $payment->payment_url }}" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-green-600 px-5 py-3 text-sm font-extrabold text-white shadow-sm transition hover:bg-green-700 focus:outline-none focus:ring-4 focus:ring-green-600/20">
                        Pay Now
                    </a>
                @else
                    <button type="button" class="inline-flex cursor-not-allowed items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-slate-100 px-5 py-3 text-sm font-extrabold text-slate-500" disabled>
                        Payment Link Not Ready
                    </button>
                @endif
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-full overflow-x-auto pb-6">
        <div class="invoice-page">
            <article id="invoiceDocument" class="invoice-card">
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
                            <div class="invoice-table-wrap">
                                <table class="invoice-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 52px; text-align: center;">No</th>
                                            <th>Description</th>
                                            <th style="width: 70px; text-align: center;">QTY</th>
                                            <th class="invoice-table-price">Unit Price</th>
                                            <th class="invoice-table-price">Discount</th>
                                            <th class="invoice-table-amount">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($groupOrderItems as $groupItem)
                                            <tr>
                                                <td style="text-align: center;">{{ $groupItem['no'] }}</td>
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
                                                <td style="text-align: center;">{{ $groupItem['qty'] }}</td>
                                                <td class="invoice-table-price">{{ $formatMoney($groupItem['unit_price']) }}</td>
                                                <td class="invoice-table-price">{{ $formatMoney($groupItem['discount']) }}</td>
                                                <td class="invoice-table-amount">{{ $formatMoney($groupItem['amount']) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                        <div class="invoice-table-wrap">
                            <table class="invoice-table">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th class="invoice-table-price">Price</th>
                                        <th class="invoice-table-amount">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($items as $item)
                                        @php
                                            $itemTitle = $item['description'] ?? $item['label'] ?? '-';
                                            $itemDetail = $item['meta'] ?? $item['details'] ?? null;
                                            $itemRate = (float) ($item['rate'] ?? $item['amount'] ?? 0);
                                            $itemAmount = (float) ($item['amount'] ?? 0);
                                        @endphp

                                        <tr>
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
                                            <td class="invoice-table-price">{{ $formatSignedMoney($itemRate) }}</td>
                                            <td class="invoice-table-amount">{{ $formatSignedMoney($itemAmount) }}</td>
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
                                            <td class="invoice-table-price">{{ $formatSignedMoney($currentInvoiceAmount) }}</td>
                                            <td class="invoice-table-amount">{{ $formatSignedMoney($currentInvoiceAmount) }}</td>
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
                                    <tr>
                                        <td>Total Invoice</td>
                                        <td>{{ $formatMoney((float) ($totalInvoiceAmount ?? 0) + (float) ($vatAmount ?? 0)) }}</td>
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
                                <tr>
                                    <td>{{ $currentDocumentAmountLabel ?? ($isWorkshopDocument ? 'Workshop Fee' : 'Current Invoice Amount') }}</td>
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
            </article>
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
            const previousBorderRadius = target.style.borderRadius;

            try {
                button.disabled = true;
                button.innerHTML = '<span class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" aria-hidden="true"></span> Generating...';

                await waitForFonts();

                target.style.boxShadow = 'none';
                target.style.borderRadius = '0';
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
                        const clonedInvoice = clonedDocument.querySelector('#invoiceDocument');

                        if (!clonedInvoice) {
                            return;
                        }

                        clonedInvoice.classList.add('invoice-exporting');
                        clonedInvoice.style.width = '794px';
                        clonedInvoice.style.minWidth = '794px';
                        clonedInvoice.style.maxWidth = '794px';
                        clonedInvoice.style.margin = '0';
                        clonedInvoice.style.boxShadow = 'none';
                        clonedInvoice.style.borderRadius = '0';
                        clonedInvoice.style.transform = 'none';
                    }
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
                target.style.borderRadius = previousBorderRadius;
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