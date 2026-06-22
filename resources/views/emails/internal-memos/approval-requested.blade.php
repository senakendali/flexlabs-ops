@extends('emails.layouts.flexlabs')

@section('content')
    @php
        $approvalStepLabel = match ((int) ($stepOrder ?? 0)) {
            1 => 'First Acknowledgement',
            2 => 'Second Acknowledgement',
            3 => 'Final Approval',
            default => $roleLabel ?? '-',
        };
    @endphp

    <h1 style="margin:0 0 16px; font-size:24px; line-height:1.35; color:#5B3E8E;">
        Internal Memo Membutuhkan Approval
    </h1>

    <p style="margin:0 0 16px; font-size:16px; line-height:1.7;">
        Halo {{ $approverName ?? 'Team FlexLabs' }},
    </p>

    <p style="margin:0 0 20px; font-size:16px; line-height:1.7;">
        Ada internal memo yang membutuhkan approval dari Anda sebagai
        <strong>{{ $approvalStepLabel }}</strong>. Silakan cek ringkasan memo di bawah ini sebelum melakukan approve atau reject melalui dashboard OPS FlexLabs.
    </p>

    <table width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0; background:#f9fafb; border:1px solid #ece7f7; border-radius:14px;">
        <tr>
            <td style="padding:20px;">
                <p style="margin:0 0 8px; font-size:13px; color:#6b7280;">
                    Memo Number
                </p>
                <p style="margin:0 0 16px; font-size:18px; font-weight:bold; color:#111827;">
                    {{ $memoNumber ?? '-' }}
                </p>

                <p style="margin:0 0 8px; font-size:13px; color:#6b7280;">
                    Subject
                </p>
                <p style="margin:0 0 16px; font-size:18px; font-weight:bold; color:#111827;">
                    {{ $memoSubject ?? '-' }}
                </p>

                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td width="50%" style="padding:0 8px 16px 0; vertical-align:top;">
                            <p style="margin:0 0 8px; font-size:13px; color:#6b7280;">
                                Memo Date
                            </p>
                            <p style="margin:0; font-size:15px; color:#111827;">
                                {{ $memoDate ?? '-' }}
                            </p>
                        </td>

                        <td width="50%" style="padding:0 0 16px 8px; vertical-align:top;">
                            <p style="margin:0 0 8px; font-size:13px; color:#6b7280;">
                                Due Date
                            </p>
                            <p style="margin:0; font-size:15px; color:#111827;">
                                {{ $dueDate ?? '-' }}
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td width="50%" style="padding:0 8px 16px 0; vertical-align:top;">
                            <p style="margin:0 0 8px; font-size:13px; color:#6b7280;">
                                To
                            </p>
                            <p style="margin:0; font-size:15px; color:#111827;">
                                {{ $toName ?? '-' }}
                            </p>

                            @if (!empty($toPosition))
                                <p style="margin:4px 0 0; font-size:13px; color:#6b7280;">
                                    {{ $toPosition }}
                                </p>
                            @endif
                        </td>

                        <td width="50%" style="padding:0 0 16px 8px; vertical-align:top;">
                            <p style="margin:0 0 8px; font-size:13px; color:#6b7280;">
                                From
                            </p>
                            <p style="margin:0; font-size:15px; color:#111827;">
                                {{ $fromName ?? '-' }}
                            </p>

                            @if (!empty($fromPosition))
                                <p style="margin:4px 0 0; font-size:13px; color:#6b7280;">
                                    {{ $fromPosition }}
                                </p>
                            @endif
                        </td>
                    </tr>
                </table>

                <p style="margin:0 0 8px; font-size:13px; color:#6b7280;">
                    Submitted By
                </p>
                <p style="margin:0 0 16px; font-size:15px; color:#111827;">
                    {{ $submittedBy ?? '-' }}
                    @if (!empty($submittedAt))
                        <br>
                        <span style="font-size:13px; color:#6b7280;">
                            {{ $submittedAt }}
                        </span>
                    @endif
                </p>

                <p style="margin:0 0 8px; font-size:13px; color:#6b7280;">
                    Approval Step
                </p>
                <p style="margin:0; font-size:15px; color:#111827;">
                    Step {{ $stepOrder ?? '-' }} - {{ $approvalStepLabel }}
                </p>
            </td>
        </tr>
    </table>

    <table width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0; background:#f8f5ff; border:1px solid #ece7f7; border-radius:14px;">
        <tr>
            <td style="padding:20px;">
                <p style="margin:0 0 8px; font-size:13px; color:#6b7280;">
                    Payment Source
                </p>
                <p style="margin:0 0 16px; font-size:15px; color:#111827;">
                    {{ $paymentSource ?? '-' }}
                </p>

                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td width="50%" style="padding:0 8px 16px 0; vertical-align:top;">
                            <p style="margin:0 0 8px; font-size:13px; color:#6b7280;">
                                Subtotal
                            </p>
                            <p style="margin:0; font-size:16px; font-weight:bold; color:#111827;">
                                {{ $subtotalAmount ?? 'Rp 0' }}
                            </p>
                        </td>

                        <td width="50%" style="padding:0 0 16px 8px; vertical-align:top;">
                            <p style="margin:0 0 8px; font-size:13px; color:#6b7280;">
                                Tax
                            </p>
                            <p style="margin:0; font-size:16px; font-weight:bold; color:#111827;">
                                {{ $taxAmount ?? 'Rp 0' }}
                                <span style="font-size:13px; font-weight:normal; color:#6b7280;">
                                    @if (isset($taxRate))
                                        ({{ $taxRate }}%)
                                    @endif
                                </span>
                            </p>
                        </td>
                    </tr>
                </table>

                <p style="margin:0 0 8px; font-size:13px; color:#6b7280;">
                    Grand Total
                </p>
                <p style="margin:0; font-size:22px; font-weight:bold; color:#5B3E8E;">
                    {{ $grandTotalAmount ?? 'Rp 0' }}
                </p>
            </td>
        </tr>
    </table>

    <table width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0; background:#ffffff; border:1px solid #ece7f7; border-radius:14px;">
        <tr>
            <td style="padding:20px;">
                <p style="margin:0 0 14px; font-size:16px; font-weight:bold; color:#111827;">
                    Budget Items
                </p>

                <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                    <thead>
                        <tr>
                            <th align="left" style="padding:10px 8px; font-size:12px; color:#6b7280; border-bottom:1px solid #e5e7eb;">
                                Details
                            </th>
                            <th align="right" style="padding:10px 8px; font-size:12px; color:#6b7280; border-bottom:1px solid #e5e7eb;">
                                Qty
                            </th>
                            <th align="right" style="padding:10px 8px; font-size:12px; color:#6b7280; border-bottom:1px solid #e5e7eb;">
                                Estimated
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($items ?? [] as $item)
                            <tr>
                                <td style="padding:12px 8px; font-size:14px; line-height:1.6; color:#111827; border-bottom:1px solid #f3f4f6; vertical-align:top;">
                                    {{ $item->details ?? '-' }}

                                    @if (!empty($item->remarks))
                                        <br>
                                        <span style="font-size:13px; color:#6b7280;">
                                            {{ $item->remarks }}
                                        </span>
                                    @endif
                                </td>

                                <td align="right" style="padding:12px 8px; font-size:14px; color:#111827; border-bottom:1px solid #f3f4f6; vertical-align:top;">
                                    {{ $item->quantity ?? 0 }}
                                </td>

                                <td align="right" style="padding:12px 8px; font-size:14px; font-weight:bold; color:#111827; border-bottom:1px solid #f3f4f6; vertical-align:top;">
                                    Rp {{ number_format((float) ($item->estimated_price ?? 0), 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" style="padding:14px 8px; font-size:14px; color:#6b7280; text-align:center;">
                                    Tidak ada budget item.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    @if (!empty($attachmentUrl))
        <table width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0; background:#fff8e5; border:1px solid #fde68a; border-radius:14px;">
            <tr>
                <td style="padding:20px;">
                    <p style="margin:0 0 8px; font-size:13px; color:#92400e; font-weight:bold;">
                        Attachment
                    </p>

                    <p style="margin:0; font-size:15px; line-height:1.7; color:#374151;">
                        Lampiran memo tersedia melalui link berikut:
                    </p>

                    <p style="margin:12px 0 0;">
                        <a href="{{ $attachmentUrl }}" style="color:#5B3E8E; font-weight:bold; text-decoration:none;">
                            {{ $attachmentLabel ?: 'Buka Attachment' }}
                        </a>
                    </p>
                </td>
            </tr>
        </table>
    @endif

    <p style="margin:28px 0; text-align:center;">
        <a href="{{ $actionUrl }}"
           style="display:inline-block; background:#5B3E8E; color:#ffffff; text-decoration:none; padding:14px 24px; border-radius:999px; font-weight:bold; font-size:15px;">
            Review Internal Memo
        </a>
    </p>

    <p style="margin:0; font-size:14px; line-height:1.7; color:#6b7280;">
        Kalau tombol tidak bisa dibuka, silakan login langsung ke dashboard OPS FlexLabs dan buka menu Internal Memo.
    </p>
@endsection