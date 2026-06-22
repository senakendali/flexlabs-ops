<?php

namespace App\Notifications;

use App\Models\InternalMemoApproval;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class InternalMemoApprovalRequestedNotification extends Notification
{
    public function __construct(
        protected InternalMemoApproval $approval
    ) {
        //
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->approval->loadMissing([
            'memo:id,memo_number,memo_date,due_date,subject,attachment_label,attachment_url,payment_source,to_name,to_position,from_name,from_position,purpose,notes,subtotal_amount,tax_rate,tax_treatment,tax_entity_type,tax_amount,grand_total_amount,status,created_by,submitted_by,submitted_at',
            'memo.creator:id,name',
            'memo.submitter:id,name',
            'memo.items:id,internal_memo_id,details,price,quantity,estimated_price,remarks,sort_order',
        ]);

        $memo = $this->approval->memo;

        return (new MailMessage)
            ->subject('Approval Internal Memo: ' . ($memo?->memo_number ?? 'Internal Memo FlexLabs'))
            ->view('emails.internal-memos.approval-requested', [
                'title' => 'Internal Memo Membutuhkan Approval',
                'logoUrl' => asset('images/logo.png'),

                'approverName' => $notifiable->full_name
                    ?? $notifiable->name
                    ?? $this->approval->approver_name
                    ?? 'Team FlexLabs',

                'memoNumber' => $memo?->memo_number ?? '-',
                'memoSubject' => $memo?->subject ?? '-',

                'roleLabel' => $this->approval->role_label ?? '-',
                'stepOrder' => $this->approval->step_order ?? '-',

                'memoDate' => $this->formatDate($memo?->memo_date),
                'dueDate' => $this->formatDate($memo?->due_date),

                'toName' => $memo?->to_name ?? '-',
                'toPosition' => $memo?->to_position ?? '-',

                'fromName' => $memo?->from_name ?? '-',
                'fromPosition' => $memo?->from_position ?? '-',

                'paymentSource' => $memo?->payment_source_label
                    ?? $this->formatPaymentSource($memo?->payment_source),

                'subtotalAmount' => $this->formatRupiah($memo?->subtotal_amount),
                'taxRate' => $memo?->tax_rate ?? 0,
                'taxAmount' => $this->formatRupiah($memo?->tax_amount),
                'grandTotalAmount' => $this->formatRupiah($memo?->grand_total_amount),

                'items' => $memo?->items ?? collect(),

                'attachmentLabel' => $memo?->attachment_label,
                'attachmentUrl' => $memo?->attachment_url,

                'submittedBy' => $memo?->submitter?->name
                    ?? $memo?->creator?->name
                    ?? '-',

                'submittedAt' => $this->formatDateTime($memo?->submitted_at),

                'actionUrl' => $memo
                    ? route('internal-memos.show', $memo)
                    : url('/internal-memos'),
            ]);
    }

    private function formatDate(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return Carbon::parse($value)
            ->timezone(config('app.timezone', 'Asia/Jakarta'))
            ->translatedFormat('d M Y');
    }

    private function formatDateTime(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return Carbon::parse($value)
            ->timezone(config('app.timezone', 'Asia/Jakarta'))
            ->translatedFormat('d M Y H:i');
    }

    private function formatRupiah(mixed $value): string
    {
        return 'Rp ' . number_format((float) ($value ?? 0), 0, ',', '.');
    }

    private function formatPaymentSource(?string $value): string
    {
        return match ($value) {
            'bank' => 'Bank',
            'cash' => 'Cash',
            default => '-',
        };
    }
}