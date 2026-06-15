<?php

namespace App\Notifications;

use App\Models\BatchAssignment;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class AssignmentPublishedNotification extends Notification
{
    public function __construct(
        protected BatchAssignment $batchAssignment
    ) {
        //
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->batchAssignment->loadMissing([
            'assignment:id,title,assignment_type,max_score',
            'batch:id,name',
        ]);

        $assignment = $this->batchAssignment->assignment;
        $batch = $this->batchAssignment->batch;

        return (new MailMessage)
            ->subject('Tugas Baru: ' . ($assignment?->title ?? 'Assignment FlexLabs'))
            ->view('emails.assignments.published', [
                'title' => 'Tugas Baru Telah Tersedia',
                'logoUrl' => asset('images/logo.png'),

                'studentName' => $notifiable->full_name
                    ?? $notifiable->name
                    ?? 'Pioneer',

                'assignmentTitle' => $assignment?->title ?? '-',
                'assignmentType' => $assignment?->assignment_type ?? '-',
                'batchName' => $batch?->name ?? '-',
                'maxScore' => $this->batchAssignment->max_score
                    ?? $assignment?->max_score
                    ?? 100,

                'availableAt' => $this->formatDateTime($this->batchAssignment->available_at),
                'dueAt' => $this->formatDateTime($this->batchAssignment->due_at),
                'closedAt' => $this->formatDateTime($this->batchAssignment->closed_at),

                'actionUrl' => url('https://mycourse.flexlabs.co.id/'),
            ]);
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
}