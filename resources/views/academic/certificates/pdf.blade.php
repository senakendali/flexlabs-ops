@php
    $student = $certificate->student;

    $studentName = $student->name
        ?? $student->full_name
        ?? trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''));

    if (! $studentName) {
        $studentName = $student->email ?? 'Student #' . $certificate->student_id;
    }

    $programName = $certificate->program->name ?? '-';
    $batchName = $certificate->batch->name ?? ('Batch #' . $certificate->batch_id);
    $issuedDate = optional($certificate->issued_date)->format('d F Y') ?? '-';
    $completedDate = optional($certificate->completed_date)->format('d F Y') ?? '-';
    $typeLabel = ucwords(str_replace('_', ' ', $certificate->type));

    $finalScore = $certificate->final_score !== null
        ? number_format((float) $certificate->final_score, 2)
        : '-';

    $grade = $certificate->grade ?? '-';

    $certificateNo = $certificate->certificate_no ?? '-';
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $certificateNo }}</title>

    <style>
        @page {
            size: A4 landscape;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            width: 297mm;
            height: 210mm;
            margin: 0;
            padding: 0;
            background: #ffffff;
            font-family: "DejaVu Sans", Arial, sans-serif;
            color: #171327;
            overflow: hidden;
        }

        body {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .certificate-page {
            position: relative;
            width: 297mm;
            height: 210mm;
            padding: 8mm;
            background: #ffffff;
            overflow: hidden;
        }

        .certificate-shell {
            position: relative;
            width: 100%;
            height: 100%;
            border: 2px solid #5B3E8E;
            background: #ffffff;
            overflow: hidden;
        }

        .certificate-inner-border {
            position: absolute;
            inset: 4mm;
            border: 1px solid #D8C9F8;
            z-index: 2;
            pointer-events: none;
        }

        .decor-top {
            position: absolute;
            top: -38mm;
            right: -38mm;
            width: 92mm;
            height: 92mm;
            border-radius: 999px;
            background: #F8F5FF;
            z-index: 1;
        }

        .decor-bottom {
            position: absolute;
            bottom: -34mm;
            left: -34mm;
            width: 78mm;
            height: 78mm;
            border-radius: 999px;
            background: #FFF6D8;
            z-index: 1;
        }

        .content {
            position: relative;
            z-index: 3;
            width: 100%;
            height: 100%;
            padding: 13mm 16mm 12mm;
            display: flex;
            flex-direction: column;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 24px;
            flex: 0 0 auto;
        }

        .logo {
            width: 45mm;
            height: auto;
            display: block;
        }

        .logo-fallback {
            font-size: 24px;
            font-weight: 900;
            color: #5B3E8E;
            letter-spacing: -0.04em;
        }

        .header-meta {
            text-align: right;
            font-size: 10px;
            line-height: 1.55;
            color: #6f6879;
            max-width: 95mm;
        }

        .header-meta strong {
            color: #5f5870;
            font-weight: 900;
        }

        .main {
            text-align: center;
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding-bottom: 6mm;
        }

        .eyebrow {
            color: #5B3E8E;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 5px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .title {
            color: #5B3E8E;
            font-size: 43px;
            line-height: 1.08;
            font-weight: 900;
            letter-spacing: -0.04em;
            margin-bottom: 16px;
        }

        .subtitle {
            color: #6f6879;
            font-size: 15px;
            line-height: 1.45;
            margin-bottom: 10px;
        }

        .student-name {
            display: inline-block;
            min-width: 150mm;
            max-width: 210mm;
            margin: 0 auto 14px;
            padding-bottom: 9px;
            border-bottom: 3px solid #FFBE04;
            color: #171327;
            font-size: 40px;
            line-height: 1.12;
            font-weight: 900;
            letter-spacing: -0.04em;
            word-break: break-word;
        }

        .description {
            width: 222mm;
            max-width: 100%;
            margin: 0 auto;
            color: #39324c;
            font-size: 15px;
            line-height: 1.7;
        }

        .program-name {
            color: #5B3E8E;
            font-weight: 900;
        }

        .type-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            align-self: center;
            margin-top: 16px;
            padding: 8px 24px;
            border-radius: 999px;
            background: #5B3E8E;
            color: #ffffff;
            font-size: 13px;
            font-weight: 900;
            min-width: 120px;
        }

        .meta-grid {
            width: 205mm;
            max-width: 100%;
            margin: 22px auto 0;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }

        .meta-card {
            min-height: 56px;
            padding: 12px 14px;
            border: 1px solid #EEE7FF;
            background: #F8F5FF;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .meta-label {
            color: #777283;
            font-size: 9px;
            letter-spacing: 1.6px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .meta-value {
            color: #171327;
            font-size: 12px;
            line-height: 1.28;
            font-weight: 900;
            text-align: center;
        }

        .footer {
            flex: 0 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            align-items: end;
            gap: 16px;
            padding-top: 8mm;
        }

        .score-block {
            text-align: left;
            color: #777283;
            font-size: 11px;
            line-height: 1.6;
        }

        .score-block strong {
            color: #171327;
            font-weight: 900;
        }

        .signature {
            text-align: center;
        }

        .signature-space {
            height: 30px;
        }

        .signature-line {
            width: 58mm;
            height: 1px;
            background: #171327;
            margin: 0 auto 8px;
        }

        .signature-name {
            color: #171327;
            font-size: 12px;
            font-weight: 900;
            line-height: 1.25;
        }

        .signature-role {
            color: #777283;
            font-size: 10px;
            line-height: 1.25;
        }

        .qr-area {
            text-align: right;
        }

        .qr-box {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }

        .qr {
            width: 24mm;
            height: 24mm;
            display: block;
            object-fit: contain;
        }

        .qr-placeholder {
            width: 24mm;
            height: 24mm;
            border: 1px dashed #D8C9F8;
            background: #F8F5FF;
        }

        .qr-caption {
            color: #777283;
            font-size: 9px;
            line-height: 1.2;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="certificate-page">
        <div class="certificate-shell">
            <div class="decor-top"></div>
            <div class="decor-bottom"></div>
            <div class="certificate-inner-border"></div>

            <div class="content">
                <header class="header">
                    <div>
                        @if(!empty($logoDataUri))
                            <img src="{{ $logoDataUri }}" class="logo" alt="FlexLabs Logo">
                        @else
                            <div class="logo-fallback">FlexLabs</div>
                        @endif
                    </div>

                    <div class="header-meta">
                        Issued Date: {{ $issuedDate }}<br>
                        Certificate No:<br>
                        <strong>{{ $certificateNo }}</strong>
                    </div>
                </header>

                <main class="main">
                    <div class="eyebrow">Official Certificate</div>

                    <div class="title">{{ $certificate->title }}</div>

                    <div class="subtitle">This certificate is proudly presented to</div>

                    <div class="student-name">{{ $studentName }}</div>

                    <div class="description">
                        For successfully completing
                        <span class="program-name">{{ $programName }}</span>
                        as part of FlexLabs learning program. This achievement reflects the student's
                        commitment, participation, and completion of the required learning evaluation.
                    </div>

                    <div class="type-badge">{{ $typeLabel }}</div>

                    <div class="meta-grid">
                        <div class="meta-card">
                            <div class="meta-label">Program</div>
                            <div class="meta-value">{{ $programName }}</div>
                        </div>

                        <div class="meta-card">
                            <div class="meta-label">Batch</div>
                            <div class="meta-value">{{ $batchName }}</div>
                        </div>

                        <div class="meta-card">
                            <div class="meta-label">Completed Date</div>
                            <div class="meta-value">{{ $completedDate }}</div>
                        </div>
                    </div>
                </main>

                <footer class="footer">
                    <div class="score-block">
                        Final Score: <strong>{{ $finalScore }}</strong><br>
                        Grade: <strong>{{ $grade }}</strong>
                    </div>

                    <div class="signature">
                        <div class="signature-space"></div>
                        <div class="signature-line"></div>
                        <div class="signature-name">Sena Kendali</div>
                        <div class="signature-role">Academic Manager</div>
                    </div>

                    <div class="qr-area">
                        <div class="qr-box">
                            @if(!empty($qrDataUri))
                                <img src="{{ $qrDataUri }}" class="qr" alt="QR Verification">
                            @else
                                <div class="qr-placeholder"></div>
                            @endif

                            <div class="qr-caption">Scan to verify certificate</div>
                        </div>
                    </div>
                </footer>
            </div>
        </div>
    </div>
</body>
</html>