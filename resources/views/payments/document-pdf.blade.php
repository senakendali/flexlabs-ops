<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $documentTitle ?? 'Payment Document' }} - {{ $documentNumber ?? '-' }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            width: 794px;
            min-height: 1123px;
            margin: 0;
            padding: 0;
            background: #ffffff;
            color: #1f1f29;
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 12px;
            line-height: 1.4;
        }

        .text-center {
            text-align: center;
        }

        .text-end {
            text-align: right;
        }

        .invoice-page {
            width: 794px;
            min-height: 1123px;
            margin: 0;
            padding: 0;
            background: #ffffff;
        }

        .invoice-card {
            position: relative;
            width: 794px;
            min-height: 1123px;
            overflow: hidden;
            background: #ffffff;
        }

        .invoice-content {
            position: relative;
            z-index: 2;
            padding: 66px 76px 210px;
        }

        .invoice-header-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 54px;
        }

        .invoice-header-table td {
            padding: 0;
            border: 0;
            vertical-align: top;
        }

        .invoice-logo-wrap {
            width: 120px;
        }

        .invoice-logo {
            display: block;
            width: 118px;
            max-width: 118px;
            height: auto;
        }

        .invoice-number-box {
            display: inline-block;
            color: #1f1f29;
            font-size: 11px;
            line-height: 1.2;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            white-space: normal;
        }

        .invoice-number-label {
            display: inline;
            font-weight: 800;
        }

        .invoice-number-value {
            display: inline;
            margin-left: 5px;
            font-weight: 700;
            word-break: break-word;
        }

        .invoice-title {
            margin: 0 0 26px;
            color: #111217;
            font-size: 58px;
            line-height: 0.95;
            font-weight: 900;
            letter-spacing: -3px;
        }

        .invoice-info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .invoice-info-table td {
            padding: 0 0 6px;
            border: 0;
            vertical-align: top;
            color: #17181f;
            font-size: 13px;
            line-height: 1.45;
        }

        .invoice-info-label {
            width: 126px;
            font-weight: 800;
            white-space: nowrap;
        }

        .invoice-info-colon {
            width: 12px;
            text-align: center;
            font-weight: 800;
        }

        .invoice-info-value {
            word-break: break-word;
        }

        .invoice-date-table {
            margin-bottom: 28px;
        }

        .invoice-party-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 36px;
        }

        .invoice-party-table td {
            width: 50%;
            padding: 0;
            vertical-align: top;
            border: 0;
        }

        .invoice-party-table .party-left {
            padding-right: 28px;
        }

        .invoice-party-table .party-right {
            padding-left: 28px;
        }

        .invoice-party-card {
            min-height: 72px;
            color: #1f1f29;
            font-size: 12.5px;
            line-height: 1.42;
        }

        .invoice-party-card h2 {
            margin: 0 0 8px;
            color: #17181f;
            font-size: 13px;
            line-height: 1.2;
            font-weight: 900;
        }

        .invoice-party-name {
            font-weight: 700;
        }

        .invoice-company-address div {
            margin: 0 0 1px;
        }

        .invoice-table-section {
            margin-top: 6px;
        }

        .invoice-table {
            width: 100%;
            margin-bottom: 0;
            border-collapse: collapse;
            color: #1f1f29;
            table-layout: fixed;
        }

        .invoice-table thead th {
            background: #5B3E8E;
            color: #ffffff;
            border: 0;
            padding: 12px 16px;
            font-size: 12px;
            line-height: 1.2;
            font-weight: 700;
        }

        .invoice-table tbody td {
            padding: 13px 16px;
            border: 0;
            color: #23242b;
            font-size: 12.5px;
            line-height: 1.35;
            vertical-align: top;
        }

        .invoice-table tbody tr:first-child td {
            padding-top: 16px;
        }

        .invoice-table tbody tr:last-child td {
            padding-bottom: 20px;
            border-bottom: 1px solid #E5DEF0;
        }

        .invoice-table-qty {
            width: 105px;
        }

        .invoice-table-price,
        .invoice-table-amount {
            width: 125px;
        }

        .invoice-item-title {
            font-weight: 700;
        }

        .invoice-item-subtitle {
            margin-top: 3px;
            color: #62646c;
            font-size: 11.5px;
        }

        .invoice-summary-wrap {
            width: 320px;
            margin-top: 28px;
            margin-left: auto;
        }

        .invoice-summary-table {
            width: 320px;
            border-collapse: collapse;
        }

        .invoice-summary-table td {
            padding: 7px 0;
            color: #17181f;
            font-size: 13px;
            line-height: 1.25;
            border: 0;
        }

        .invoice-summary-table td:first-child {
            font-weight: 800;
        }

        .invoice-summary-table td:last-child {
            text-align: right;
            font-weight: 800;
        }

        .invoice-summary-total td {
            border-top: 1px solid #E5DEF0;
            padding-top: 12px;
            font-weight: 900;
        }

        .invoice-payment-section {
            margin-top: 54px;
        }

        .invoice-payment-section .invoice-info-table td {
            padding-bottom: 6px;
        }

        .invoice-wave {
            position: absolute;
            z-index: 1;
            pointer-events: none;
        }

        .invoice-wave-light {
            left: -120px;
            bottom: -42px;
            width: 610px;
            height: 180px;
            background: #ECE6F5;
            border-radius: 55% 45% 0 0 / 100% 100% 0 0;
            transform: rotate(5deg);
        }

        .invoice-wave-dark {
            right: -108px;
            bottom: -86px;
            width: 780px;
            height: 270px;
            background: #5B3E8E;
            border-radius: 58% 42% 0 0 / 100% 100% 0 0;
            transform: rotate(-9deg);
        }
    </style>
</head>
<body>
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

    $logoSrc = null;
    $resolvedLogoPath = $logoPath ?? public_path('images/logo-black.png');

    if (!empty($resolvedLogoPath) && is_file($resolvedLogoPath)) {
        $logoMime = mime_content_type($resolvedLogoPath) ?: 'image/png';
        $logoSrc = 'data:' . $logoMime . ';base64,' . base64_encode(file_get_contents($resolvedLogoPath));
    }
@endphp

<div class="invoice-page">
    <div class="invoice-card">
        <div class="invoice-content">
            <table class="invoice-header-table">
                <tr>
                    <td>
                        <div class="invoice-logo-wrap">
                            @if (!empty($logoSrc))
                                <img src="{{ $logoSrc }}" alt="FlexLabs Logo" class="invoice-logo">
                            @else
                                <div class="invoice-party-name">{{ $companyName }}</div>
                            @endif
                        </div>
                    </td>
                    <td class="text-end">
                        <div class="invoice-number-box">
                            <span class="invoice-number-label">No.</span>
                            <span class="invoice-number-value">{{ $documentNumber ?? '-' }}</span>
                        </div>
                    </td>
                </tr>
            </table>

            <h1 class="invoice-title">{{ $documentTitle ?? 'DOCUMENT' }}</h1>

            <table class="invoice-info-table invoice-date-table">
                <tr>
                    <td class="invoice-info-label">Date</td>
                    <td class="invoice-info-colon">:</td>
                    <td class="invoice-info-value">{{ $formatDate($documentDate ?? null) }}</td>
                </tr>
            </table>

            <table class="invoice-party-table">
                <tr>
                    <td class="party-left">
                        <div class="invoice-party-card">
                            <h2>{{ $leftPartyTitle ?? 'Billed to' }}</h2>

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
                    </td>
                    <td class="party-right">
                        <div class="invoice-party-card">
                            <h2>{{ $rightPartyTitle ?? 'From' }}</h2>

                            <div class="invoice-party-name">{{ $companyName }}</div>
                            <div class="invoice-company-address">
                                @foreach ($companyAddressLines as $addressLine)
                                    <div>{{ $addressLine }}</div>
                                @endforeach
                            </div>
                        </div>
                    </td>
                </tr>
            </table>

            <section class="invoice-table-section">
                <table class="invoice-table">
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
                            <td>{{ $totalLabel ?? 'Total' }}</td>
                            <td>{{ $formatMoney($grandTotal ?? 0) }}</td>
                        </tr>
                    </table>
                </div>
            </section>

            @if (!empty($paymentRows))
                <section class="invoice-payment-section">
                    <table class="invoice-info-table">
                        @foreach ($paymentRows as $row)
                            @continue(empty($row['value']))

                            <tr>
                                <td class="invoice-info-label">{{ $row['label'] }}</td>
                                <td class="invoice-info-colon">:</td>
                                <td class="invoice-info-value">{{ $row['value'] }}</td>
                            </tr>
                        @endforeach
                    </table>
                </section>
            @endif
        </div>

        <div class="invoice-wave invoice-wave-light"></div>
        <div class="invoice-wave invoice-wave-dark"></div>
    </div>
</div>
</body>
</html>
