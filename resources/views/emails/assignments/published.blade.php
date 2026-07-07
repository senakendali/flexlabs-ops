@extends('emails.layouts.flexlabs')

@section('content')
    <h1 style="margin:0 0 16px; font-size:24px; line-height:1.35; color:#5B3E8E;">
        Tugas Baru Telah Tersedia
    </h1>

    <p style="margin:0 0 16px; font-size:16px; line-height:1.7;">
        Halo {{ $studentName ?? 'Pioneer' }},
    </p>

    <p style="margin:0 0 20px; font-size:16px; line-height:1.7;">
        Ada tugas baru yang sudah tersedia di LMS FlexLabs. Silakan cek detail tugas, deadline, dan instruksi pengerjaan melalui LMS.
    </p>

    <table width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0; background:#f9fafb; border:1px solid #ece7f7; border-radius:14px;">
        <tr>
            <td style="padding:20px;">
                <p style="margin:0 0 8px; font-size:13px; color:#6b7280;">
                    Judul Tugas
                </p>
                <p style="margin:0 0 16px; font-size:18px; font-weight:bold; color:#111827;">
                    {{ $assignmentTitle ?? '-' }}
                </p>

                <p style="margin:0 0 8px; font-size:13px; color:#6b7280;">
                    Tipe Tugas
                </p>
                <p style="margin:0 0 16px; font-size:15px; color:#111827;">
                    {{ $assignmentType ?? '-' }}
                </p>

                <p style="margin:0 0 8px; font-size:13px; color:#6b7280;">
                    Batch
                </p>
                <p style="margin:0 0 16px; font-size:15px; color:#111827;">
                    {{ $batchName ?? '-' }}
                </p>

                <p style="margin:0 0 8px; font-size:13px; color:#6b7280;">
                    Max Score
                </p>
                <p style="margin:0 0 16px; font-size:20px; font-weight:bold; color:#5B3E8E;">
                    {{ $maxScore ?? 100 }}
                </p>

                @if(!empty($availableAt))
                    <p style="margin:0 0 8px; font-size:13px; color:#6b7280;">
                        Mulai Tersedia
                    </p>
                    <p style="margin:0 0 16px; font-size:15px; color:#111827;">
                        {{ $availableAt }}
                    </p>
                @endif

                @if(!empty($dueAt))
                    <p style="margin:0 0 8px; font-size:13px; color:#6b7280;">
                        Deadline
                    </p>
                    <p style="margin:0 0 16px; font-size:15px; color:#111827;">
                        {{ $dueAt }}
                    </p>
                @endif

                @if(!empty($closedAt))
                    <p style="margin:0 0 8px; font-size:13px; color:#6b7280;">
                        Ditutup Pada
                    </p>
                    <p style="margin:0; font-size:15px; color:#111827;">
                        {{ $closedAt }}
                    </p>
                @endif
            </td>
        </tr>
    </table>

    <table width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0; background:#fff8e5; border:1px solid #fde68a; border-radius:14px;">
        <tr>
            <td style="padding:20px;">
                <p style="margin:0 0 10px; font-size:13px; color:#92400e; font-weight:bold;">
                    Catatan Penting
                </p>

                <p style="margin:0; font-size:15px; line-height:1.7; color:#374151;">
                    Pastikan kamu membaca instruksi tugas dengan teliti sebelum mengerjakan. Submit tugas sebelum deadline agar tidak tercatat terlambat.
                </p>
            </td>
        </tr>
    </table>

    <p style="margin:28px 0; text-align:center;">
        <a href="{{ $actionUrl }}"
           style="display:inline-block; background:#5B3E8E; color:#ffffff; text-decoration:none; padding:14px 24px; border-radius:999px; font-weight:bold; font-size:15px;">
            Buka Tugas di LMS FlexLabs
        </a>
    </p>

    <p style="margin:0; font-size:14px; line-height:1.7; color:#6b7280;">
        Kalau tombol tidak bisa dibuka, silakan login langsung ke LMS FlexLabs melalui browser kamu.
    </p>
@endsection