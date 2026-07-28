<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Internal Memorandum - {{ $memo->memo_number }}</title>

    <style>
        @page {
            margin: 178px 32px 96px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1f2933;
            line-height: 1.42;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .pdf-header {
            position: fixed;
            top: -156px;
            left: 0;
            right: 0;
            height: 142px;
            padding: 0 0 12px;
            border-bottom: 3px solid #019641;
        }

        .header-logo-row {
            width: 100%;
            height: 72px;
            text-align: left;
        }

        .logo-wrap {
            width: 250px;
            text-align: left;
        }

        .logo {
            width: 205px;
            height: auto;
        }

        .brand-fallback {
            font-size: 18px;
            font-weight: bold;
            color: #019641;
            letter-spacing: 0.03em;
            line-height: 1.18;
        }

        .header-title-row {
            width: 100%;
            text-align: center;
            padding: 8px 28px 0;
        }

        .document-label {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            color: #019641;
            text-transform: uppercase;
            letter-spacing: 0.09em;
            line-height: 1.2;
        }

        .document-title {
            margin-top: 7px;
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            color: #1f2933;
            line-height: 1.35;
        }

        .section {
            margin-top: 16px;
            page-break-inside: auto;
        }

        .section:first-of-type {
            margin-top: 0;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #019641;
            margin-bottom: 7px;
            page-break-after: avoid;
        }

        .greeting-section {
            margin-top: 18px;
            margin-bottom: 14px;
            font-size: 11px;
            color: #1f2933;
            page-break-after: avoid;
            font-weight: bold;
        }

        .info-table {
            page-break-inside: avoid;
        }

        .info-table td {
            padding: 7px 8px;
            border: 1px solid #dcebdd;
            vertical-align: top;
        }

        .info-label {
            width: 24%;
            background: #F1F9F1;
            font-weight: bold;
            color: #334155;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: auto;
        }

        .data-table thead {
            display: table-header-group;
        }

        .data-table tbody {
            display: table-row-group;
        }

        .data-table tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        .data-table th {
            background: #019641;
            color: #ffffff;
            padding: 7px 8px;
            border: 1px solid #019641;
            text-align: left;
            font-size: 10px;
            line-height: 1.25;
        }

        .data-table td {
            padding: 7px 8px;
            border: 1px solid #dcebdd;
            vertical-align: top;
            font-size: 10px;
            line-height: 1.35;
        }

        .summary-table {
            width: 54%;
            margin-left: auto;
            margin-top: 10px;
            page-break-inside: avoid;
        }

        .summary-table td {
            padding: 7px 8px;
            border: 1px solid #dcebdd;
            font-size: 10px;
            vertical-align: top;
        }

        .summary-label {
            background: #F1F9F1;
            font-weight: bold;
            width: 46%;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .text-muted {
            color: #64748b;
        }

        .text-box {
            border: 1px solid #dcebdd;
            background: #F1F9F1;
            padding: 10px;
            min-height: 40px;
            white-space: pre-line;
            page-break-inside: avoid;
        }

        .rich-content {
            white-space: normal;
            line-height: 1.55;
        }

        .rich-content p {
            margin: 0 0 7px;
        }

        .rich-content p:last-child {
            margin-bottom: 0;
        }

        .rich-content ul,
        .rich-content ol {
            margin: 0 0 7px 18px;
            padding-left: 12px;
        }

        .rich-content li {
            margin-bottom: 4px;
        }

        .rich-content blockquote {
            margin: 0 0 7px;
            padding-left: 10px;
            border-left: 3px solid #019641;
            color: #475569;
        }

        .tax-note {
            margin-top: 8px;
            padding: 8px 10px;
            background: #F1F9F1;
            border: 1px solid #dcebdd;
            color: #334155;
            font-size: 9px;
            line-height: 1.35;
            page-break-inside: avoid;
        }

        .closing-section {
            margin-top: 20px;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .closing-section .section {
            margin-top: 0;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .signature-section {
            margin-top: 26px;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .signature-table {
            width: 100%;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .signature-table tr,
        .signature-table td {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .signature-table td {
            width: 25%;
            text-align: center;
            vertical-align: top;
            border: none;
            padding: 0 6px;
        }

        .signature-title {
            font-weight: bold;
            color: #019641;
            margin-bottom: 6px;
            min-height: 16px;
        }

        .signature-space {
            height: 62px;
        }

        .signature-name {
            font-weight: bold;
            text-decoration: underline;
            line-height: 1.25;
            min-height: 28px;
        }

        .signature-position {
            color: #64748b;
            font-size: 9px;
            line-height: 1.25;
            min-height: 24px;
            margin-top: 3px;
        }

        .company-footer {
            position: fixed;
            right: 0;
            bottom: -66px;
            width: 58%;
            text-align: right;
            color: #1f2933;
            font-size: 9px;
            line-height: 1.35;
        }

        .company-footer strong {
            color: #019641;
            font-size: 10px;
        }
    </style>
</head>

<body>
    @php
        $formatDate = function ($value, string $format = 'd M Y') {
            if (blank($value)) {
                return '-';
            }

            try {
                return \Illuminate\Support\Carbon::parse($value)->format($format);
            } catch (\Throwable $e) {
                return '-';
            }
        };

        $formatCurrency = function ($value) {
            return 'Rp ' . number_format((float) $value, 0, ',', '.');
        };

        $paymentSources = $paymentSources ?? [
            'bank' => 'Bank',
            'cash' => 'Cash',
        ];

        $taxTreatments = $taxTreatments ?? [
            'not_include' => 'Tax Not Include',
            'include' => 'Tax Include',
        ];

        $taxEntityTypes = $taxEntityTypes ?? [
            'pkp' => 'PKP',
            'non_pkp' => 'Non PKP',
        ];

        $paymentSourceLabel = $paymentSources[$memo->payment_source] ?? (
            $memo->payment_source ? \Illuminate\Support\Str::headline($memo->payment_source) : '-'
        );

        $taxTreatmentLabel = $taxTreatments[$memo->tax_treatment] ?? (
            $memo->tax_treatment ? \Illuminate\Support\Str::headline(str_replace('_', ' ', $memo->tax_treatment)) : '-'
        );

        $taxEntityTypeLabel = $taxEntityTypes[$memo->tax_entity_type] ?? (
            $memo->tax_entity_type ? \Illuminate\Support\Str::headline(str_replace('_', ' ', $memo->tax_entity_type)) : '-'
        );

        $approvalRows = $memo->relationLoaded('approvals') ? $memo->approvals : collect();
        $acknowledgements = $approvalRows->where('role_label', 'Acknowledged by')->values();

        $acknowledgementDefaults = collect([
            (object) [
                'approver_name' => 'Andres Dony Wijaya',
                'approver_position' => 'Business Admin Manager',
                'approved_at' => $memo->approved_at,
                'approver' => null,
            ],
            (object) [
                'approver_name' => 'Awalokita Garnierit',
                'approver_position' => 'Academic Business Unit Head',
                'approved_at' => $memo->approved_at,
                'approver' => null,
            ],
        ]);

        $acknowledgerOne = $acknowledgements->get(0) ?: $acknowledgementDefaults->get(0);
        $acknowledgerTwo = $acknowledgements->get(1) ?: $acknowledgementDefaults->get(1);

        $getSignerName = function ($approval) {
            return data_get($approval, 'approver_name')
                ?: data_get($approval, 'approver.name')
                ?: '-';
        };

        $getSignerPosition = function ($approval) {
            return data_get($approval, 'approver_position') ?: '-';
        };

        $logoPath = public_path('images/sei.png');

        $allowedPurposeTags = '<p><br><strong><b><em><i><u><s><ol><ul><li><blockquote><a><span>';
        $purposeHtml = trim(strip_tags((string) $memo->purpose, $allowedPurposeTags));
    @endphp

    <div class="pdf-header">
        <div class="header-logo-row">
            <div class="logo-wrap">
                @if (file_exists($logoPath))
                    <img src="{{ $logoPath }}" class="logo" alt="PT System Ever Indonesia">
                @else
                    <div class="brand-fallback">PT SYSTEM EVER INDONESIA</div>
                @endif
            </div>
        </div>

        <div class="header-title-row">
            <div class="document-label">Internal Memorandum</div>
            <div class="document-title">{{ $memo->subject }}</div>
        </div>
    </div>

    <div class="company-footer">
        <strong>PT SYSTEM EVER INDONESIA</strong><br>
        Menara Jamsostek South Tower 12th Floor,<br>
        Jl. Jend. Gatot Subroto Kav. 38 South Jakarta, 12710<br>
        Tel: +62 21-5296-2129<br>
        www.systemever.co.id
    </div>

    <div class="section">
        <table class="info-table">
            <tr>
                <td class="info-label">No</td>
                <td>{{ $memo->memo_number }}</td>
            </tr>
            <tr>
                <td class="info-label">Date</td>
                <td>{{ $formatDate($memo->memo_date) }}</td>
            </tr>
            <tr>
                <td class="info-label">Due Date</td>
                <td>{{ $formatDate($memo->due_date) }}</td>
            </tr>
            <tr>
                <td class="info-label">To</td>
                <td>
                    {{ $memo->to_name }}
                    @if ($memo->to_position)
                        <br><span class="text-muted">{{ $memo->to_position }}</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="info-label">From</td>
                <td>
                    {{ $memo->from_name }}
                    @if ($memo->from_position)
                        <br><span class="text-muted">{{ $memo->from_position }}</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="info-label">Payment Source</td>
                <td>{{ $paymentSourceLabel }}</td>
            </tr>
            <tr>
                <td class="info-label">Attachment</td>
                <td>{{ $memo->attachment_label ?: '-' }}</td>
            </tr>
        </table>
    </div>

    <div class="greeting-section">
        Dear Mr. Kwon,
    </div>

    <div class="section">
        <div class="section-title">Purpose</div>

        <div class="text-box rich-content">
            @if (! blank($purposeHtml))
                {!! $purposeHtml !!}
            @else
                -
            @endif
        </div>
    </div>

    <div class="section">
        <div class="section-title">Budget</div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 34%;">Details</th>
                    <th style="width: 14%;" class="text-right">Price</th>
                    <th style="width: 8%;" class="text-center">Qty</th>
                    <th style="width: 16%;" class="text-right">Estimated Price</th>
                    <th style="width: 23%;">Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($memo->items as $item)
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td>{{ $item->details }}</td>
                        <td class="text-right">{{ $formatCurrency($item->price) }}</td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-right">{{ $formatCurrency($item->estimated_price) }}</td>
                        <td>{{ $item->remarks ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">No budget items.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <table class="summary-table">
            <tr>
                <td class="summary-label">Tax Treatment</td>
                <td class="text-right">{{ $taxTreatmentLabel }}</td>
            </tr>
            <tr>
                <td class="summary-label">Tax Entity</td>
                <td class="text-right">{{ $taxEntityTypeLabel }}</td>
            </tr>
            <tr>
                <td class="summary-label">Subtotal</td>
                <td class="text-right">{{ $formatCurrency($memo->subtotal_amount) }}</td>
            </tr>
            <tr>
                <td class="summary-label">Tax {{ number_format((float) $memo->tax_rate, 2) }}%</td>
                <td class="text-right">{{ $formatCurrency($memo->tax_amount) }}</td>
            </tr>
            <tr>
                <td class="summary-label">Total</td>
                <td class="text-right"><strong>{{ $formatCurrency($memo->grand_total_amount) }}</strong></td>
            </tr>
        </table>

        <!--div class="tax-note">
            @if ($memo->tax_entity_type === 'non_pkp')
                Non PKP selected. Tax rate is set to 0 and grand total follows the subtotal.
            @elseif ($memo->tax_treatment === 'include')
                Tax Include selected. Grand total follows the submitted subtotal and tax is calculated as part of the submitted amount.
            @else
                Tax Not Include selected. Tax is added on top of the subtotal.
            @endif
        </!--div-->
    </div>

    <div class="closing-section">
        <div class="section">
            <div class="section-title">Notes</div>
            <div class="text-box">{{ $memo->notes ?: '-' }}</div>
        </div>

        <div class="signature-section">
            <table class="signature-table">
                <tr>
                    <td>
                        <div class="signature-title">Requested by</div>
                        <div class="signature-space"></div>
                        <div class="signature-name">{{ $memo->from_name ?: optional($memo->creator)->name ?: '-' }}</div>
                        <div class="signature-position">{{ $memo->from_position ?: '-' }}</div>
                    </td>

                    <td>
                        <div class="signature-title">Acknowledged by</div>
                        <div class="signature-space"></div>
                        <div class="signature-name">{{ $getSignerName($acknowledgerOne) }}</div>
                        <div class="signature-position">{{ $getSignerPosition($acknowledgerOne) }}</div>
                    </td>

                    <td>
                        <div class="signature-title">Acknowledged by</div>
                        <div class="signature-space"></div>
                        <div class="signature-name">{{ $getSignerName($acknowledgerTwo) }}</div>
                        <div class="signature-position">{{ $getSignerPosition($acknowledgerTwo) }}</div>
                    </td>

                    <td>
                        <div class="signature-title">Approved by</div>
                        <div class="signature-space"></div>
                        <div class="signature-name">{{ $memo->to_name ?: '-' }}</div>
                        <div class="signature-position">{{ $memo->to_position ?: '-' }}</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>