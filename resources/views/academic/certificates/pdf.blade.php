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

    $certificateNumber = data_get($certificate, 'certificate_number')
        ?: data_get($certificate, 'certificate_no')
        ?: data_get($certificate, 'number')
        ?: data_get($certificate, 'code')
        ?: '-';

    /*
     * Cormorant Garamond memberi karakter formal yang umum dipakai pada
     * certificate. Jika file font belum tersedia, Dompdf otomatis memakai
     * DejaVu Serif agar PDF tetap dapat dibuat tanpa error.
     */
    $certificateFontPath = public_path('fonts/certificate/CormorantGaramond-SemiBoldItalic.ttf');
    $certificateFontUrl = file_exists($certificateFontPath)
        ? 'file://' . str_replace('\\', '/', $certificateFontPath)
        : null;

    /*
     * Signature disimpan di private storage, sehingga tidak dapat diakses
     * melalui URL publik. Embed sebagai data URI agar stabil di Dompdf.
     */
    $signaturePath = storage_path('app/private/signatures/sena.png');
    $signatureDataUri = file_exists($signaturePath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($signaturePath))
        : null;

    /*
     * Logo F ditempatkan di kiri bawah certificate. Embed sebagai data URI
     * agar tetap konsisten ketika dirender menggunakan Dompdf.
     */
    $footerLogoPath = public_path('images/f-logo.png');
    $footerLogoDataUri = file_exists($footerLogoPath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($footerLogoPath))
        : null;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Certificate - {{ $studentName }}</title>
    <style>
        @page {
            /* A4 landscape dalam satuan native PDF. */
            size: 841.89pt 595.28pt;
            margin: 0;
        }

        @if($certificateFontUrl)
            @font-face {
                font-family: 'Certificate Script';
                font-style: italic;
                font-weight: 600;
                src: url('{{ $certificateFontUrl }}') format('truetype');
            }
        @endif

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #24212c;
            /*
             * Warna ungu menjadi alas halaman. Bidang putih sertifikat
             * dibuat oleh .paper-surface agar terlihat seperti lembar kertas
             * yang berada di atas background ungu.
             */
            background-color: #5B3E8E;
            background-image: linear-gradient(135deg, #3f286b 0%, #5B3E8E 48%, #7651a4 100%);
        }

        /*
         * Shadow dibuat sebagai layer tersendiri supaya tidak menambah ukuran
         * bidang putih dan tidak menggeser layout sertifikat di Dompdf.
         */
        .paper-shadow {
            position: fixed;
            top: 25pt;
            right: 15pt;
            bottom: 15pt;
            left: 25pt;
            z-index: 0;
            background: rgba(31, 18, 54, .28);
            border-radius: 14pt;
        }

        /*
         * Bidang putih dibuat sebagai layer tetap tanpa border dan tidak masuk
         * ke document flow. Inset yang lebih besar membuat alas ungu lebih
         * terlihat tanpa mengubah titik tengah konten sertifikat.
         */
        .paper-surface {
            position: fixed;
            top: 20pt;
            right: 20pt;
            bottom: 20pt;
            left: 20pt;
            z-index: 1;
            overflow: hidden;
            background: #ffffff;
            border-radius: 14pt;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        /*
         * Jangan memberi height setinggi halaman pada frame karena Dompdf
         * dapat membulatkan ukuran tersebut dan membuat halaman kedua.
         *
         * Frame hanya mengikuti tinggi konten dan selalu berada di atas
         * watermark logo F.
         */
        .certificate-frame {
            position: relative;
            z-index: 3;
            width: 100%;
            padding: 38pt 0 12pt;
            background: transparent;
            text-align: center;
        }

        .page-layout {
            width: 757pt;
            height: 520pt;
            margin-left: auto;
            margin-right: auto;
            table-layout: fixed;
        }

        .page-layout > tbody > tr > td {
            padding: 0;
            text-align: center;
        }

        .logo-row {
            height: 42pt;
        }

        .logo-cell {
            vertical-align: top;
        }

        .content-row,
        .content-cell {
            height: 478pt;
        }

        .content-cell {
            vertical-align: middle;
            text-align: center;
        }

        .content-stack {
            width: 700pt;
            margin-left: auto;
            margin-right: auto;
            text-align: center;
        }

        .brand-logo {
            width: 180px;
            height: auto;
            max-height: 48px;
            object-fit: contain;
        }

        .brand-fallback {
            font-family: DejaVu Serif, Georgia, serif;
            font-size: 22px;
            font-weight: bold;
            color: #5B3E8E;
        }

        .certificate-heading-group {
            position: relative;
            top: -9pt;
            margin-bottom: 13pt;
            text-align: center;
        }

        .certificate-kicker {
            margin-top: 0;
            font-size: 9px;
            font-weight: bold;
            letter-spacing: 3.5px;
            text-transform: uppercase;
            color: #9b7b2f;
        }

        .certificate-title {
            margin: 1px 0 0;
            font-family: DejaVu Serif, Georgia, serif;
            font-size: 40px;
            line-height: 1.05;
            font-weight: bold;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #34254f;
        }

        .certificate-number {
            margin-top: 4px;
            font-size: 8px;
            line-height: 1.3;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #81778d;
        }

        .presented-label {
            margin: 0;
            font-family: DejaVu Serif, Georgia, serif;
            font-size: 12px;
            font-style: italic;
            color: #6d6875;
        }

        .student-name {
            margin: 4pt auto 3pt;
            width: 82%;
            padding-bottom: 2pt;
            font-family: 'Certificate Script', 'Cormorant Garamond', DejaVu Serif, Georgia, serif;
            font-size: 37px;
            line-height: 1.12;
            font-weight: 600;
            font-style: italic;
            color: #17131e;
        }

        .achievement-copy {
            width: 76%;
            margin: 4px auto 0;
            font-family: DejaVu Serif, Georgia, serif;
            font-size: 11px;
            line-height: 1.55;
            color: #554f5d;
        }

        .program-name {
            width: 84%;
            margin: 3px auto 0;
            font-family: DejaVu Serif, Georgia, serif;
            font-size: 16px;
            line-height: 1.25;
            font-weight: bold;
            color: #5B3E8E;
        }

        .details-table {
            width: 78%;
            margin: 10pt auto 0;
        }

        .details-table td {
            padding: 6px 8px 5px;
            text-align: center;
            vertical-align: middle;
        }

        .detail-label {
            margin-bottom: 2px;
            font-size: 7px;
            font-weight: bold;
            letter-spacing: .8px;
            text-transform: uppercase;
            color: #8b8492;
        }

        .detail-value {
            font-family: DejaVu Serif, Georgia, serif;
            font-size: 10px;
            font-weight: bold;
            color: #2b2630;
        }

        .footer-table {
            width: 78%;
            margin: 22pt auto 0;
        }

        .footer-table td {
            vertical-align: bottom;
        }

        .signature-cell {
            width: 50%;
            text-align: left;
        }

        .signature-line {
            width: 210px;
            height: 48px;
        }

        .signature-image {
            display: block;
            width: auto;
            height: 48px;
            max-width: 180px;
            object-fit: contain;
        }

        .signature-name {
            margin-top: 5px;
            font-family: DejaVu Serif, Georgia, serif;
            font-size: 10px;
            font-weight: bold;
            color: #28232e;
        }

        .signature-role {
            margin-top: 1px;
            font-size: 7.5px;
            letter-spacing: .25px;
            color: #7a7480;
        }

        .verification-cell {
            width: 50%;
            text-align: right;
        }

        /*
         * Logo F ditempatkan sebagai child dari bidang putih.
         *
         * Ini lebih stabil untuk Dompdf dibanding mengandalkan z-index
         * antar beberapa elemen position: fixed.
         */
        .footer-brand-logo {
            position: absolute;
            left: -50pt;
            bottom: 0pt;
            width: auto;
            height: 500px;
            max-width: none;
            opacity: 0.60;
        }

        .verification-wrap {
            display: inline-block;
            text-align: center;
        }

        .qr-image {
            width: 60px;
            height: 60px;
            display: block;
            margin: 0 auto 3px;
        }

        .qr-placeholder {
            width: 60px;
            height: 60px;
            line-height: 60px;
            color: #817a87;
            font-size: 7px;
            text-align: center;
            margin: 0 auto 3px;
        }

        .verify-title {
            font-size: 7.5px;
            font-weight: bold;
            letter-spacing: .35px;
            text-transform: uppercase;
            color: #5B3E8E;
        }
    </style>
</head>
<body>
    <div class="paper-shadow"></div>

    <div class="paper-surface">
        
    </div>

    <div class="certificate-frame">
        <table class="page-layout">
            <tr class="logo-row">
                <td class="logo-cell">
                    @if($logoDataUri)
                        <img src="{{ $logoDataUri }}" class="brand-logo" alt="FlexLabs">
                    @else
                        <div class="brand-fallback">FlexLabs</div>
                    @endif
                </td>
            </tr>
            <tr class="content-row">
                <td class="content-cell" align="center">
                    <div class="content-stack">

                        <div class="certificate-heading-group">
                            <div class="certificate-kicker">Certificate of Achievement</div>
                            <h1 class="certificate-title">Certificate</h1>
                            <div class="certificate-number">
                                Certificate No. {{ $certificateNumber }}
                            </div>
                        </div>

                        <p class="presented-label">This certificate is proudly presented to</p>
                        <div class="student-name">{{ $studentName }}</div>

                        <div class="achievement-copy">
                            In recognition of the successful completion of the learning program
                        </div>
                        <div class="program-name">{{ $programName }}</div>
                        <div class="achievement-copy">
                            and for demonstrating commitment, competency, and academic achievement
                            in accordance with FlexLabs standards.
                        </div>

                        <table class="details-table">
                            <tr>
                                <td style="width: 25%;">
                                    <div class="detail-label">Batch</div>
                                    <div class="detail-value">{{ $batchName }}</div>
                                </td>
                                <td style="width: 25%;">
                                    <div class="detail-label">Completed</div>
                                    <div class="detail-value">{{ $completedDate }}</div>
                                </td>
                                <td style="width: 25%;">
                                    <div class="detail-label">Final Score</div>
                                    <div class="detail-value">{{ $finalScore }}</div>
                                </td>
                                <td style="width: 25%;">
                                    <div class="detail-label">Grade</div>
                                    <div class="detail-value">{{ $grade }}</div>
                                </td>
                            </tr>
                        </table>

                        <table class="footer-table">
                            <tr>
                                <td class="signature-cell">
                                    <div class="signature-line">
                                        @if($signatureDataUri)
                                            <img src="{{ $signatureDataUri }}" class="signature-image" alt="Sena Kendali Signature">
                                        @endif
                                    </div>
                                    <div class="signature-name">{{ $issuerName }}</div>
                                    <div class="signature-role">Academic Representative</div>
                                </td>
                                <td class="verification-cell">
                                    <div class="verification-wrap">
                                        @if($qrDataUri)
                                            <img src="{{ $qrDataUri }}" class="qr-image" alt="Certificate QR Code">
                                        @else
                                            <div class="qr-placeholder">QR Code</div>
                                        @endif
                                        <div class="verify-title">Scan to Verify</div>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>