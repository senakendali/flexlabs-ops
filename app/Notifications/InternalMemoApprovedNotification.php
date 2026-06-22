<?php

namespace App\Notifications;

use App\Models\InternalMemo;
use App\Models\InternalMemoApproval;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class InternalMemoApprovedNotification extends Notification
{
    public function __construct(
        protected InternalMemo $memo,
        protected ?InternalMemoApproval $approval = null
    ) {
        //
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->memo->loadMissing([
            'creator:id,name,email',
            'submitter:id,name,email',
            'items:id,internal_memo_id,details,price,quantity,estimated_price,remarks,sort_order',
        ]);

        $this->approval?->loadMissing([
            'approver:id,name,email',
        ]);

        return (new MailMessage)
            ->subject('Internal Memo Approved: ' . ($this->memo->memo_number ?? 'Internal Memo FlexLabs'))
            ->view('emails.internal-memos.approved', [
                'title' => 'Internal Memo Sudah Approved',
                'logoUrl' => asset('images/logo.png'),

                'recipientName' => $notifiable->full_name
                    ?? $notifiable->name
                    ?? 'Team FlexLabs',

                'memoNumber' => $this->memo->memo_number ?? '-',
                'memoSubject' => $this->memo->subject ?? '-',

                'memoDate' => $this->formatDate($this->memo->memo_date),
                'dueDate' => $this->formatDate($this->memo->due_date),

                'toName' => $this->memo->to_name ?? '-',
                'toPosition' => $this->memo->to_position ?? '-',

                'fromName' => $this->memo->from_name ?? '-',
                'fromPosition' => $this->memo->from_position ?? '-',

                'roleLabel' => $this->approval?->role_label ?? '-',
                'stepOrder' => $this->approval?->step_order ?? '-',

                'approvedBy' => $this->approval?->approver?->name
                    ?? $this->approval?->approver_name
                    ?? 'Approver FlexLabs',

                'approvedAt' => $this->formatDateTime(
                    $this->approval?->approved_at ?? $this->memo->approved_at
                ),

                'paymentSource' => $this->memo->payment_source_label
                    ?? $this->formatPaymentSource($this->memo->payment_source),

                'subtotalAmount' => $this->formatRupiah($this->memo->subtotal_amount),
                'taxRate' => $this->memo->tax_rate ?? 0,
                'taxAmount' => $this->formatRupiah($this->memo->tax_amount),
                'grandTotalAmount' => $this->formatRupiah($this->memo->grand_total_amount),

                'items' => $this->memo->items ?? collect(),

                'attachmentLabel' => $this->memo->attachment_label,
                'attachmentUrl' => $this->memo->attachment_url,

                'submittedBy' => $this->memo->submitter?->name
                    ?? $this->memo->creator?->name
                    ?? '-',

                'submittedAt' => $this->formatDateTime($this->memo->submitted_at),

                'actionUrl' => route('internal-memos.show', $this->memo),
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