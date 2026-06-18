<?php

namespace App\Notifications;

use App\Models\StudentMentoringSession;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class MentoringSessionRejectedNotification extends Notification
{
    public function __construct(
        protected StudentMentoringSession $session
    ) {
        //
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->session->loadMissing([
            'student',
            'instructor',
            'availabilitySlot',
        ]);

        $slot = $this->session->availabilitySlot;

        return (new MailMessage)
            ->subject('Mentoring Session Belum Disetujui')
            ->view('emails.mentoring-sessions.rejected', [
                'logoUrl' => asset('images/logo.png'),

                'studentName' => $notifiable->full_name
                    ?? $notifiable->name
                    ?? 'Pioneer',

                'instructorName' => $this->session->instructor?->name ?? 'Instructor FlexLabs',
                'topicType' => $this->session->topic_type_label ?? $this->session->topic_type ?? '-',
                'notes' => $this->session->notes,

                'slotDate' => $this->formatDate($slot?->date),
                'slotStartTime' => $this->formatTime($slot?->start_time),
                'slotEndTime' => $this->formatTime($slot?->end_time),

                'actionUrl' => 'https://mycourse.flexlabs.co.id/',
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

    private function formatTime(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return Carbon::parse($value)->format('H:i');
    }
}