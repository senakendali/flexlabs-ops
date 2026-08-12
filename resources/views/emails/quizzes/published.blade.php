<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
</head>
<body style="margin:0;background:#f4f2f8;font-family:Arial,sans-serif;color:#2d2638;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f2f8;padding:32px 16px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;background:#ffffff;border-radius:16px;overflow:hidden;">
                <tr>
                    <td style="padding:28px 32px;background:#5B3E8E;">
                        <img src="{{ $logoUrl }}" alt="FlexLabs" style="display:block;max-height:42px;max-width:180px;">
                    </td>
                </tr>
                <tr>
                    <td style="padding:32px;">
                        <h1 style="margin:0 0 16px;font-size:24px;line-height:1.35;">{{ $title }}</h1>
                        <p style="margin:0 0 20px;line-height:1.7;">Hi {{ $studentName }},</p>
                        <p style="margin:0 0 24px;line-height:1.7;">Quiz baru telah tersedia untuk batch kamu. Berikut detailnya:</p>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f7f5fa;border-radius:12px;padding:20px;">
                            <tr><td style="padding:6px 0;color:#746b80;">Quiz</td><td style="padding:6px 0;text-align:right;font-weight:700;">{{ $quizTitle }}</td></tr>
                            <tr><td style="padding:6px 0;color:#746b80;">Batch</td><td style="padding:6px 0;text-align:right;">{{ $batchName }}</td></tr>
                            <tr><td style="padding:6px 0;color:#746b80;">Tipe</td><td style="padding:6px 0;text-align:right;">{{ $quizType }}</td></tr>
                            @if($durationMinutes)<tr><td style="padding:6px 0;color:#746b80;">Durasi</td><td style="padding:6px 0;text-align:right;">{{ $durationMinutes }} menit</td></tr>@endif
                            @if(!is_null($passingScore))<tr><td style="padding:6px 0;color:#746b80;">Passing score</td><td style="padding:6px 0;text-align:right;">{{ $passingScore }}</td></tr>@endif
                            @if($maxAttempts)<tr><td style="padding:6px 0;color:#746b80;">Maks. percobaan</td><td style="padding:6px 0;text-align:right;">{{ $maxAttempts }} kali</td></tr>@endif
                            @if($availableAt)<tr><td style="padding:6px 0;color:#746b80;">Tersedia</td><td style="padding:6px 0;text-align:right;">{{ $availableAt }}</td></tr>@endif
                            @if($dueAt)<tr><td style="padding:6px 0;color:#746b80;">Deadline</td><td style="padding:6px 0;text-align:right;">{{ $dueAt }}</td></tr>@endif
                            @if($closedAt)<tr><td style="padding:6px 0;color:#746b80;">Ditutup</td><td style="padding:6px 0;text-align:right;">{{ $closedAt }}</td></tr>@endif
                        </table>

                        <p style="margin:28px 0 0;text-align:center;">
                            <a href="{{ $actionUrl }}" style="display:inline-block;padding:13px 24px;background:#FFBE04;color:#2d2638;text-decoration:none;font-weight:700;border-radius:9px;">Buka Quiz</a>
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>