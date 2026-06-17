@extends('emails.layouts.flexlabs')

@section('content')
    <h1 style="margin:0 0 16px; font-size:24px; line-height:1.35; color:#5B3E8E;">
        Tugas Kamu Sudah Direview
    </h1>

    <p style="margin:0 0 16px; font-size:16px; line-height:1.7;">
        Halo {{ $studentName ?? 'Pioneer' }},
    </p>

    <p style="margin:0 0 20px; font-size:16px; line-height:1.7;">
        Submission tugas kamu sudah direview oleh instructor. Silakan cek hasil penilaian dan notes dari instructor di bawah ini.
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
                    Batch
                </p>
                <p style="margin:0 0 16px; font-size:15px; color:#111827;">
                    {{ $batchName ?? '-' }}
                </p>

                <p style="margin:0 0 8px; font-size:13px; color:#6b7280;">
                    Score
                </p>
                <p style="margin:0 0 16px; font-size:20px; font-weight:bold; color:#5B3E8E;">
                    {{ $score ?? 0 }} / {{ $maxScore ?? 100 }}
                </p>

                <p style="margin:0 0 8px; font-size:13px; color:#6b7280;">
                    Direview Oleh
                </p>
                <p style="margin:0 0 16px; font-size:15px; color:#111827;">
                    {{ $reviewedBy ?? 'Instructor FlexLabs' }}
                </p>

                @if(!empty($reviewedAt))
                    <p style="margin:0 0 8px; font-size:13px; color:#6b7280;">
                        Waktu Review
                    </p>
                    <p style="margin:0; font-size:15px; color:#111827;">
                        {{ $reviewedAt }}
                    </p>
                @endif
            </td>
        </tr>
    </table>

    <table width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0; background:#fff8e5; border:1px solid #fde68a; border-radius:14px;">
        <tr>
            <td style="padding:20px;">
                <p style="margin:0 0 10px; font-size:13px; color:#92400e; font-weight:bold;">
                    Notes dari Instructor
                </p>

                <div style="margin:0; font-size:15px; line-height:1.7; color:#374151;">
                    {!! nl2br(e($feedback ?? '-')) !!}
                </div>
            </td>
        </tr>
    </table>

    <p style="margin:28px 0; text-align:center;">
        <a href="{{ $actionUrl ?? 'https://mycourse.flexlabs.co.id/' }}"
           style="display:inline-block; background:#5B3E8E; color:#ffffff; text-decoration:none; padding:14px 24px; border-radius:999px; font-weight:bold; font-size:15px;">
            Buka LMS FlexLabs
        </a>
    </p>

    <p style="margin:0; font-size:14px; line-height:1.7; color:#6b7280;">
        Kalau tombol tidak bisa dibuka, silakan login langsung ke LMS FlexLabs melalui browser kamu.
    </p>
@endsection