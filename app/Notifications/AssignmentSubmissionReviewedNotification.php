<?php

namespace App\Notifications;

use App\Models\AssignmentSubmission;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class AssignmentSubmissionReviewedNotification extends Notification
{
    public function __construct(
        protected AssignmentSubmission $assignmentSubmission
    ) {
        //
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->assignmentSubmission->loadMissing([
            'assignment:id,title,assignment_type,max_score',
            'batch:id,name',
            'batchAssignment:id,assignment_id,batch_id,max_score,due_at',
            'batchAssignment.assignment:id,title,assignment_type,max_score',
            'reviewedBy:id,name',
        ]);

        $assignment = $this->assignmentSubmission->assignment
            ?? $this->assignmentSubmission->batchAssignment?->assignment;

        $batch = $this->assignmentSubmission->batch;
        $batchAssignment = $this->assignmentSubmission->batchAssignment;
        $reviewedBy = $this->assignmentSubmission->reviewedBy;

        $maxScore = $batchAssignment?->max_score
            ?? $assignment?->max_score
            ?? 100;

        return (new MailMessage)
            ->subject('Tugas Sudah Direview: ' . ($assignment?->title ?? 'Assignment FlexLabs'))
            ->view('emails.assignments.reviewed', [
                'studentName' => $notifiable->full_name
                    ?? $notifiable->name
                    ?? 'Pioneer',
                'logoUrl' => 'https://ops.flexlabs.co.id/images/logo.png',

                'assignmentTitle' => $assignment?->title ?? '-',
                'assignmentType' => $assignment?->assignment_type ?? '-',
                'batchName' => $batch?->name ?? '-',

                'score' => $this->assignmentSubmission->score ?? 0,
                'maxScore' => $maxScore,
                'feedback' => $this->assignmentSubmission->feedback ?? '-',

                'reviewedBy' => $reviewedBy?->name ?? 'Instructor FlexLabs',
                'reviewedAt' => $this->formatDateTime($this->assignmentSubmission->reviewed_at),

                'actionUrl' => 'https://mycourse.flexlabs.co.id/',
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