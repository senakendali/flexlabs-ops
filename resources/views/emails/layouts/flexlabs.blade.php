<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'FlexLabs Notification' }}</title>
</head>
<body style="margin:0; padding:0; background:#f4f1fb; font-family:Arial, sans-serif; color:#1f2937;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f1fb; padding:32px 16px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:620px; background:#ffffff; border-radius:18px; overflow:hidden; box-shadow:0 12px 32px rgba(91,62,142,0.12);">
                    <tr>
                        <td style="background:#5B3E8E; padding:28px 32px; text-align:center;">
                            <img src="{{ $logoUrl ?? asset('assets/images/logo-black.png') }}"
                                 alt="FlexLabs"
                                 style="max-height:52px; max-width:190px; display:inline-block;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:36px 32px;">
                            @yield('content')
                        </td>
                    </tr>

                    <tr>
                        <td style="background:#f9fafb; padding:24px 32px; text-align:center; font-size:13px; color:#6b7280;">
                            <p style="margin:0 0 8px;">
                                Email ini dikirim otomatis oleh sistem FlexLabs.
                            </p>
                            <p style="margin:0;">
                                © {{ date('Y') }} FlexLabs. All rights reserved.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>