<?php

namespace App\Notifications;

use App\Models\BatchLearningQuiz;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class QuizPublishedNotification extends Notification
{
    public function __construct(
        protected BatchLearningQuiz $batchLearningQuiz
    ) {
        //
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->batchLearningQuiz->loadMissing([
            'learningQuiz:id,title,quiz_type,duration_minutes,passing_score,max_attempts',
            'batch:id,name',
        ]);

        $quiz = $this->batchLearningQuiz->learningQuiz;
        $batch = $this->batchLearningQuiz->batch;

        return (new MailMessage)
            ->subject('Quiz Baru: ' . ($quiz?->title ?? 'Quiz FlexLabs'))
            ->view('emails.quizzes.published', [
                'title' => 'Quiz Baru Telah Tersedia',
                'logoUrl' => asset('images/logo.png'),
                'studentName' => $notifiable->full_name
                    ?? $notifiable->name
                    ?? 'Pioneer',
                'quizTitle' => $quiz?->title ?? '-',
                'quizType' => $quiz?->quiz_type ?? '-',
                'batchName' => $batch?->name ?? '-',
                'durationMinutes' => $this->batchLearningQuiz->duration_minutes
                    ?? $quiz?->duration_minutes,
                'passingScore' => $this->batchLearningQuiz->passing_score
                    ?? $quiz?->passing_score,
                'maxAttempts' => $this->batchLearningQuiz->max_attempts
                    ?? $quiz?->max_attempts,
                'availableAt' => $this->formatDateTime($this->batchLearningQuiz->available_at),
                'dueAt' => $this->formatDateTime($this->batchLearningQuiz->due_at),
                'closedAt' => $this->formatDateTime($this->batchLearningQuiz->closed_at),
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