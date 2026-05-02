@php
    $studentName = data_get($certificate, 'student.name')
        ?: data_get($certificate, 'student.full_name')
        ?: data_get($certificate, 'student.student_name')
        ?: data_get($certificate, 'reportCard.student.name')
        ?: data_get($certificate, 'reportCard.student.full_name')
        ?: data_get($certificate, 'reportCard.student.student_name')
        ?: data_get($certificate, 'reportCard.student_name')
        ?: data_get($certificate, 'student_name');

    if (! $studentName) {
        $firstName = data_get($certificate, 'student.first_name') ?: data_get($certificate, 'reportCard.student.first_name');
        $middleName = data_get($certificate, 'student.middle_name') ?: data_get($certificate, 'reportCard.student.middle_name');
        $lastName = data_get($certificate, 'student.last_name') ?: data_get($certificate, 'reportCard.student.last_name');
        $studentName = trim(implode(' ', array_filter([$firstName, $middleName, $lastName])));
    }

    if (! $studentName) {
        $studentName = data_get($certificate, 'student.email')
            ?: data_get($certificate, 'reportCard.student.email')
            ?: 'Student #' . ($certificate->student_id ?: '-');
    }

    $studentEmail = data_get($certificate, 'student.email')
        ?: data_get($certificate, 'reportCard.student.email')
        ?: null;

    $programName = data_get($certificate, 'program.name')
        ?: data_get($certificate, 'batch.program.name')
        ?: data_get($certificate, 'reportCard.program.name')
        ?: data_get($certificate, 'reportCard.batch.program.name')
        ?: 'Program Name';

    $batchName = data_get($certificate, 'batch.name')
        ?: data_get($certificate, 'reportCard.batch.name')
        ?: ('Batch #' . ($certificate->batch_id ?: '-'));

    $issuedDate = $certificate->issued_date
        ? \Illuminate\Support\Carbon::parse($certificate->issued_date)->format('d M Y')
        : ($certificate->issued_at ? \Illuminate\Support\Carbon::parse($certificate->issued_at)->format('d M Y') : '-');

    $completedDate = $certificate->completed_date
        ? \Illuminate\Support\Carbon::parse($certificate->completed_date)->format('d M Y')
        : '-';

    $finalScore = is_null($certificate->final_score) ? '-' : number_format((float) $certificate->final_score, 2);
    $grade = $certificate->grade ?: '-';
    $issuerName = data_get($certificate, 'issuer.name', 'Academic Team');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Certificate - {{ $studentName }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 30px 36px 34px 36px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10.5px;
            line-height: 1.45;
            color: #1f2937;
            background: #ffffff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-muted { color: #6b7280; }
        .text-primary { color: #5B3E8E; }
        .fw-bold { font-weight: bold; }

        .header-table {
            margin-bottom: 14px;
        }

        .header-table td {
            vertical-align: top;
        }

        .brand-logo {
            width: 145px;
            height: auto;
        }

        .brand-fallback {
            font-size: 22px;
            color: #5B3E8E;
            font-weight: bold;
        }

        .document-title h1 {
            margin: 0;
            padding: 0;
            font-size: 23px;
            line-height: 1.08;
            letter-spacing: .8px;
            color: #111827;
            font-weight: bold;
            text-transform: uppercase;
        }

        .document-subtitle {
            margin-top: 5px;
            font-size: 9.3px;
            color: #6b7280;
        }

        .accent-line {
            height: 4px;
            background: #5B3E8E;
            margin-bottom: 14px;
        }

        .certificate-box {
            border: 1px solid #d9d4e6;
            background: #fbfafc;
            padding: 16px 18px 17px;
            margin-bottom: 13px;
            position: relative;
        }

        .certificate-box:before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 5px;
            background: #5B3E8E;
        }

        .certificate-label {
            display: inline-block;
            padding: 4px 12px;
            border: 1px solid #d9d4e6;
            background: #ffffff;
            color: #5B3E8E;
            font-size: 8.4px;
            font-weight: bold;
            letter-spacing: .8px;
            text-transform: uppercase;
            margin-top: 4px;
        }

        .student-name {
            margin: 10px 0 4px;
            font-family: DejaVu Serif, Georgia, serif;
            font-size: 29px;
            line-height: 1.15;
            font-weight: bold;
            color: #111827;
        }

        .student-email {
            font-size: 9px;
            color: #6b7280;
            margin-bottom: 9px;
        }

        .certificate-statement {
            width: 92%;
            margin: 0 auto;
            font-size: 11px;
            color: #374151;
            line-height: 1.65;
        }

        .certificate-statement strong {
            color: #111827;
        }

        .section {
            margin-top: 12px;
            page-break-inside: avoid;
        }

        .section-title {
            font-size: 11.5px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: .35px;
        }

        .info-table td {
            border: 1px solid #e5e7eb;
            padding: 8px 9px;
            vertical-align: top;
            background: #ffffff;
        }

        .info-label {
            display: block;
            margin-bottom: 2px;
            font-size: 8px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: .25px;
        }

        .info-value {
            display: block;
            font-size: 10.2px;
            color: #111827;
            font-weight: bold;
            word-break: break-word;
        }

        .summary-table td {
            border: 1px solid #d9d4e6;
            padding: 9px 8px;
            vertical-align: middle;
            text-align: center;
            background: #ffffff;
        }

        .summary-label {
            margin-bottom: 3px;
            font-size: 8px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: .25px;
        }

        .summary-value {
            font-size: 18px;
            color: #111827;
            font-weight: bold;
            line-height: 1.15;
        }

        .summary-value.primary {
            color: #5B3E8E;
        }

        .footer-table {
            margin-top: 16px;
        }

        .footer-table td {
            vertical-align: bottom;
        }

        .signature-box {
            height: 80px;
        }

        .signature-line {
            width: 190px;
            height: 46px;
            border-bottom: 1px solid #9ca3af;
            margin-bottom: 5px;
        }

        .signature-name {
            font-size: 10px;
            font-weight: bold;
            color: #111827;
        }

        .signature-role {
            margin-top: 2px;
            font-size: 8.5px;
            color: #6b7280;
        }

        .verification-box {
            width: 116px;
            margin-left: auto;
            border: 1px solid #d9d4e6;
            background: #fbfafc;
            padding: 8px 9px;
            text-align: center;
        }

        .qr-image {
            width: 74px;
            height: 74px;
            display: block;
            margin: 0 auto 5px;
        }

        .qr-placeholder {
            width: 74px;
            height: 74px;
            line-height: 74px;
            border: 1px dashed #9ca3af;
            color: #6b7280;
            font-size: 8px;
            text-align: center;
            margin: 0 auto 5px;
        }

        .verify-title {
            font-size: 8.8px;
            color: #111827;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .verify-subtitle {
            font-size: 7.4px;
            color: #6b7280;
            line-height: 1.25;
        }

        .note {
            margin-top: 8px;
            font-size: 8px;
            color: #6b7280;
            text-align: center;
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="width: 50%;">
                @if($logoDataUri)
                    <img src="{{ $logoDataUri }}" class="brand-logo" alt="FlexLabs">
                @else
                    <div class="brand-fallback">FlexLabs</div>
                @endif
            </td>
            <td style="width: 50%;" class="document-title text-right">
                <h1>Certificate</h1>
                <div class="document-subtitle">Official academic achievement record</div>
            </td>
        </tr>
    </table>

    

    <div class="certificate-box text-center">
        <div class="certificate-label">Presented To</div>

        <div class="student-name">{{ $studentName }}</div>

        @if($studentEmail)
            <div class="student-email">{{ $studentEmail }}</div>
        @endif

        <div class="certificate-statement">
            This certificate is awarded to recognize the successful completion of
            <strong>{{ $programName }}</strong>. The recipient has demonstrated learning commitment,
            competency, and academic performance in accordance with FlexLabs standards.
        </div>
    </div>

    <div class="section">
        <div class="section-title">Certificate Information</div>
        <table class="info-table">
            <tr>
                <td style="width: 25%;">
                    <span class="info-label">Program</span>
                    <span class="info-value">{{ $programName }}</span>
                </td>
                <td style="width: 25%;">
                    <span class="info-label">Batch</span>
                    <span class="info-value">{{ $batchName }}</span>
                </td>
                <td style="width: 25%;">
                    <span class="info-label">Completed Date</span>
                    <span class="info-value">{{ $completedDate }}</span>
                </td>
                <td style="width: 25%;">
                    <span class="info-label">Issued Date</span>
                    <span class="info-value">{{ $issuedDate }}</span>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Final Result</div>
        <table class="summary-table">
            <tr>
                <td style="width: 50%;">
                    <div class="summary-label">Final Score</div>
                    <div class="summary-value primary">{{ $finalScore }}</div>
                </td>
                <td style="width: 50%;">
                    <div class="summary-label">Grade</div>
                    <div class="summary-value">{{ $grade }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="footer-table">
        <tr>
            <td style="width: 60%;">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-name">{{ $issuerName }}</div>
                    <div class="signature-role">Academic Representative</div>
                </div>
            </td>
            <td style="width: 40%;">
                <div class="verification-box">
                    @if($qrDataUri)
                        <img src="{{ $qrDataUri }}" class="qr-image" alt="Certificate QR Code">
                    @else
                        <div class="qr-placeholder">QR Code</div>
                    @endif
                    <div class="verify-title">Scan to Verify</div>
                    <div class="verify-subtitle">Official verification QR</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="note">
        This certificate is digitally generated by FlexLabs and can be verified through the QR code.
    </div>
</body>
</html>
