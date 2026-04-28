<?php

namespace App\Http\Controllers\Api\Lms;

use App\Http\Controllers\Controller;
use App\Models\LearningQuiz;
use App\Models\LearningQuizAttempt;
use App\Models\LearningQuizAnswer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StudentQuizController extends Controller
{
    private const FK_QUIZ = 'learning_quiz_id';
    private const FK_BATCH_LEARNING_QUIZ = 'batch_learning_quiz_id';
    private const FK_ATTEMPT = 'learning_quiz_attempt_id';
    private const FK_QUESTION = 'learning_quiz_question_id';
    private const FK_OPTION = 'learning_quiz_option_id';

    public function show(Request $request, LearningQuiz $quiz): JsonResponse
    {
        $studentId = $this->resolveStudentId($request);

        if (! $studentId) {
            return response()->json([
                'message' => 'Data student tidak ditemukan untuk user ini.',
            ], 422);
        }

        $quiz->loadMissing([
            'questions.options',
        ]);

        $attemptQuery = $this->attemptQuery($quiz, $studentId);

        $latestAttempt = (clone $attemptQuery)
            ->latest('id')
            ->first();

        $bestAttempt = (clone $attemptQuery)
            ->whereNotNull('percentage')
            ->orderByDesc('percentage')
            ->orderByDesc('id')
            ->first();

        $attemptCount = (clone $attemptQuery)
            ->whereIn('status', ['submitted', 'graded'])
            ->count();

        $canStart = $this->canStartQuiz($quiz, $attemptCount);

        return response()->json([
            'data' => [
                'quiz' => [
                    'id' => $quiz->id,
                    'title' => $quiz->title ?? 'Untitled Quiz',
                    'description' => $quiz->description
                        ?? $quiz->excerpt
                        ?? 'Kerjakan quiz ini untuk mengukur pemahaman kamu terhadap materi.',
                    'course_name' => $this->getQuizCourseName($quiz),
                    'module_name' => $this->getQuizModuleName($quiz),
                    'topic_name' => $this->getQuizTopicName($quiz),
                    'instructions' => $quiz->instructions ?? $quiz->content ?? '',
                    'question_count' => $quiz->questions->count(),
                    'duration_minutes' => $this->getQuizDurationMinutes($quiz),
                    'duration_label' => $this->formatDurationLabel($quiz),
                    'passing_score' => (int) ($quiz->passing_score ?? 70),
                    'attempt_count' => $attemptCount,
                    'max_attempts' => $quiz->max_attempts,
                    'attempt_label' => $this->formatAttemptLabel($attemptCount, $quiz->max_attempts),
                    'deadline_label' => $this->formatDeadlineLabel($quiz),
                    'remaining_label' => $this->formatRemainingLabel($quiz),
                    'status' => $this->getQuizStatus($quiz, $latestAttempt),
                    'status_label' => $this->getQuizStatusLabel($quiz, $latestAttempt),
                    'can_start' => $canStart,
                    'has_attempt' => (bool) $latestAttempt,
                    'is_passed' => (bool) optional($bestAttempt)->is_passed,
                    'best_score' => $bestAttempt?->percentage,
                    'best_score_label' => $bestAttempt?->percentage !== null
                        ? $this->formatPercent($bestAttempt->percentage)
                        : '-',
                    'last_attempt_label' => $latestAttempt?->submitted_at
                        ? Carbon::parse($latestAttempt->submitted_at)->format('d M Y, H:i')
                        : '-',
                ],
            ],
        ]);
    }

    public function start(Request $request, LearningQuiz $quiz): JsonResponse
    {
        $studentId = $this->resolveStudentId($request);

        if (! $studentId) {
            return response()->json([
                'message' => 'Data student tidak ditemukan untuk user ini.',
            ], 422);
        }

        $quiz->loadMissing([
            'questions.options',
        ]);

        $batchId = $this->resolveBatchId($request, $quiz, $studentId);
        $batchLearningQuizId = $this->resolveBatchLearningQuizId($request, $quiz, $batchId);

        if (! $batchId) {
            return response()->json([
                'message' => 'Batch student tidak ditemukan.',
            ], 422);
        }

        if (! $batchLearningQuizId) {
            return response()->json([
                'message' => 'Batch learning quiz tidak ditemukan.',
            ], 422);
        }

        $attemptCount = $this->attemptQuery($quiz, $studentId, $batchLearningQuizId)
            ->whereIn('status', ['submitted', 'graded'])
            ->count();

        if (! $this->canStartQuiz($quiz, $attemptCount)) {
            return response()->json([
                'message' => 'Quiz sudah tidak bisa dikerjakan.',
            ], 422);
        }

        $activeAttempt = $this->attemptQuery($quiz, $studentId, $batchLearningQuizId)
            ->where('status', 'in_progress')
            ->latest('id')
            ->first();

        if ($activeAttempt && $this->isAttemptExpired($quiz, $activeAttempt)) {
            $activeAttempt->update([
                'status' => 'expired',
                'submitted_at' => now(),
                'duration_seconds' => $this->calculateAttemptDurationSeconds($activeAttempt),
            ]);

            $activeAttempt = null;
        }

        if (! $activeAttempt) {
            $nextAttemptNumber = ((int) $this->attemptQuery($quiz, $studentId, $batchLearningQuizId)
                ->max('attempt_number')) + 1;

            $activeAttempt = LearningQuizAttempt::create([
                self::FK_BATCH_LEARNING_QUIZ => $batchLearningQuizId,
                self::FK_QUIZ => $quiz->id,
                'batch_id' => $batchId,
                'student_id' => $studentId,
                'attempt_number' => $nextAttemptNumber,
                'started_at' => now(),
                'submitted_at' => null,
                'duration_seconds' => 0,
                'score' => null,
                'total_score' => null,
                'percentage' => null,
                'is_passed' => false,
                'status' => 'in_progress',
                'graded_by' => null,
                'graded_at' => null,
            ]);
        }

        $existingAnswers = LearningQuizAnswer::query()
            ->where(self::FK_ATTEMPT, $activeAttempt->id)
            ->get();

        return response()->json([
            'data' => [
                'quiz' => [
                    'id' => $quiz->id,
                    'title' => $quiz->title ?? 'Untitled Quiz',
                    'course_name' => $this->getQuizCourseName($quiz),
                    'duration_minutes' => $this->getQuizDurationMinutes($quiz),
                ],
                'attempt' => [
                    'id' => $activeAttempt->id,
                    'attempt_number' => $activeAttempt->attempt_number,
                    'started_at' => optional($activeAttempt->started_at)->toDateTimeString(),
                ],
                'remaining_seconds' => $this->getRemainingSeconds($quiz, $activeAttempt),
                'questions' => $quiz->questions
                    ->sortBy(fn ($question) => $question->sort_order ?? $question->id)
                    ->values()
                    ->map(fn ($question, $index) => [
                        'id' => $question->id,
                        'title' => $question->title ?: 'Question ' . ($index + 1),
                        'question_text' => $question->question_text
                            ?? $question->text
                            ?? $question->content
                            ?? '',
                        'type' => $question->type ?? $question->question_type ?? 'multiple_choice',
                        'points' => (float) ($question->points ?? $question->score ?? 1),
                        'options' => $question->options
                            ->sortBy(fn ($option) => $option->sort_order ?? $option->id)
                            ->values()
                            ->map(fn ($option, $optionIndex) => [
                                'id' => $option->id,
                                'label' => $option->label ?: chr(65 + $optionIndex),
                                'text' => $option->option_text
                                    ?? $option->text
                                    ?? $option->answer
                                    ?? '-',
                            ])
                            ->values(),
                    ])
                    ->values(),
                'answers' => $existingAnswers->map(fn ($answer) => [
                    'question_id' => $answer->{self::FK_QUESTION},
                    'option_id' => $answer->{self::FK_OPTION},
                    'answer_text' => $answer->answer_text,
                ])->values(),
            ],
        ]);
    }

    public function submit(Request $request, LearningQuiz $quiz): JsonResponse
    {
        $validated = $request->validate([
            'attempt_id' => ['nullable', 'integer'],
            'answers' => ['required', 'array'],
            'answers.*.question_id' => ['required', 'integer'],
            'answers.*.option_id' => ['nullable', 'integer'],
            'answers.*.answer_text' => ['nullable', 'string'],
        ]);

        $studentId = $this->resolveStudentId($request);

        if (! $studentId) {
            return response()->json([
                'message' => 'Data student tidak ditemukan untuk user ini.',
            ], 422);
        }

        $quiz->loadMissing([
            'questions.options',
        ]);

        $attempt = $this->attemptQuery($quiz, $studentId)
            ->when($validated['attempt_id'] ?? null, function ($query, $attemptId) {
                $query->where('id', $attemptId);
            })
            ->where('status', 'in_progress')
            ->latest('id')
            ->first();

        if (! $attempt) {
            return response()->json([
                'message' => 'Attempt quiz tidak ditemukan atau sudah pernah dikirim.',
            ], 404);
        }

        $answerCollection = collect($validated['answers'])
            ->keyBy(fn ($answer) => (int) $answer['question_id']);

        $needsManualReview = false;
        $totalScore = 0;
        $earnedScore = 0;

        DB::transaction(function () use (
            $quiz,
            $attempt,
            $answerCollection,
            &$needsManualReview,
            &$totalScore,
            &$earnedScore
        ) {
            foreach ($quiz->questions as $question) {
                $questionType = $question->type ?? $question->question_type ?? 'multiple_choice';
                $questionScore = (float) ($question->points ?? $question->score ?? 1);

                $totalScore += $questionScore;

                $answer = $answerCollection->get((int) $question->id, []);
                $selectedOptionId = $answer['option_id'] ?? null;
                $answerText = $answer['answer_text'] ?? null;

                $isCorrect = false;
                $scoreEarned = 0;

                if (in_array($questionType, ['multiple_choice', 'single_choice'], true)) {
                    $correctOption = $question->options
                        ->first(fn ($option) => (bool) ($option->is_correct ?? false));

                    if ($correctOption && (int) $selectedOptionId === (int) $correctOption->id) {
                        $isCorrect = true;
                        $scoreEarned = $questionScore;
                    }
                } else {
                    $needsManualReview = true;
                    $isCorrect = null;
                    $scoreEarned = 0;
                }

                $earnedScore += $scoreEarned;

                $answerTable = (new LearningQuizAnswer())->getTable();

                LearningQuizAnswer::updateOrCreate(
                    [
                        self::FK_ATTEMPT => $attempt->id,
                        self::FK_QUESTION => $question->id,
                    ],
                    $this->filterExistingColumns($answerTable, [
                        self::FK_OPTION => $selectedOptionId,
                        'answer_text' => $answerText,
                        'is_correct' => $isCorrect,
                        'points_earned' => $scoreEarned,
                        'score' => $scoreEarned,
                    ])
                );
            }

            $percentage = $totalScore > 0
                ? round(($earnedScore / $totalScore) * 100, 2)
                : 0;

            $passingScore = (float) ($quiz->passing_score ?? 70);

            $attempt->update([
                'submitted_at' => now(),
                'duration_seconds' => $this->calculateAttemptDurationSeconds($attempt),
                'status' => $needsManualReview ? 'submitted' : 'graded',
                'score' => $earnedScore,
                'total_score' => $totalScore,
                'percentage' => $needsManualReview ? null : $percentage,
                'is_passed' => ! $needsManualReview && $percentage >= $passingScore,
                'graded_at' => $needsManualReview ? null : now(),
            ]);
        });

        $attempt->refresh();

        return response()->json([
            'message' => $attempt->status === 'submitted'
                ? 'Quiz berhasil dikirim dan menunggu review.'
                : 'Quiz berhasil dikirim.',
            'data' => [
                'attempt' => [
                    'id' => $attempt->id,
                    'status' => $attempt->status,
                    'score' => $attempt->percentage,
                    'score_label' => $attempt->percentage !== null
                        ? $this->formatPercent($attempt->percentage)
                        : '-',
                    'raw_score' => $attempt->score,
                    'total_score' => $attempt->total_score,
                    'percentage' => $attempt->percentage,
                    'is_passed' => (bool) $attempt->is_passed,
                    'submitted_at' => optional($attempt->submitted_at)->toDateTimeString(),
                ],
            ],
        ]);
    }

    private function attemptQuery(LearningQuiz $quiz, int $studentId, ?int $batchLearningQuizId = null)
    {
        return LearningQuizAttempt::query()
            ->where(self::FK_QUIZ, $quiz->id)
            ->where('student_id', $studentId)
            ->when($batchLearningQuizId, function ($query) use ($batchLearningQuizId) {
                $query->where(self::FK_BATCH_LEARNING_QUIZ, $batchLearningQuizId);
            });
    }

    private function resolveStudentId(Request $request): ?int
    {
        $user = $request->user();

        if (! $user) {
            return null;
        }

        if (! empty($user->student_id)) {
            return (int) $user->student_id;
        }

        if (method_exists($user, 'student') && $user->student) {
            return (int) $user->student->id;
        }

        if (class_exists(\App\Models\Student::class)) {
            $student = \App\Models\Student::query()
                ->where('user_id', $user->id)
                ->first();

            return $student?->id;
        }

        return null;
    }

    private function resolveBatchId(Request $request, LearningQuiz $quiz, int $studentId): ?int
    {
        if ($request->filled('batch_id')) {
            return (int) $request->input('batch_id');
        }

        if (! empty($quiz->batch_id)) {
            return (int) $quiz->batch_id;
        }

        if (isset($quiz->batchLearningQuiz) && ! empty($quiz->batchLearningQuiz->batch_id)) {
            return (int) $quiz->batchLearningQuiz->batch_id;
        }

        if (class_exists(\App\Models\Student::class)) {
            $student = \App\Models\Student::query()->find($studentId);

            if ($student && ! empty($student->batch_id)) {
                return (int) $student->batch_id;
            }

            if ($student && method_exists($student, 'batches')) {
                $batch = $student->batches()->latest('batches.id')->first();

                if ($batch) {
                    return (int) $batch->id;
                }
            }

            if ($student && method_exists($student, 'enrollments')) {
                $enrollment = $student->enrollments()->latest('id')->first();

                if ($enrollment && ! empty($enrollment->batch_id)) {
                    return (int) $enrollment->batch_id;
                }
            }
        }

        return null;
    }

    private function resolveBatchLearningQuizId(Request $request, LearningQuiz $quiz, ?int $batchId): ?int
    {
        if ($request->filled('batch_learning_quiz_id')) {
            return (int) $request->input('batch_learning_quiz_id');
        }

        if (! empty($quiz->batch_learning_quiz_id)) {
            return (int) $quiz->batch_learning_quiz_id;
        }

        if (isset($quiz->batchLearningQuiz) && ! empty($quiz->batchLearningQuiz->id)) {
            return (int) $quiz->batchLearningQuiz->id;
        }

        if (! $batchId) {
            return null;
        }

        if (class_exists(\App\Models\BatchLearningQuiz::class)) {
            $batchLearningQuiz = \App\Models\BatchLearningQuiz::query()
                ->where(self::FK_QUIZ, $quiz->id)
                ->where('batch_id', $batchId)
                ->first();

            return $batchLearningQuiz?->id;
        }

        return null;
    }

    private function canStartQuiz(LearningQuiz $quiz, int $attemptCount): bool
    {
        $isActive = (bool) ($quiz->is_active ?? true);
        $status = strtolower((string) ($quiz->status ?? 'active'));

        if (! $isActive || in_array($status, ['inactive', 'closed', 'archived'], true)) {
            return false;
        }

        if ($this->isQuizOverdue($quiz)) {
            return false;
        }

        $maxAttempts = $quiz->max_attempts;

        if ($maxAttempts && $attemptCount >= (int) $maxAttempts) {
            return false;
        }

        return true;
    }

    private function isQuizOverdue(LearningQuiz $quiz): bool
    {
        $deadline = $quiz->due_at
            ?? $quiz->deadline_at
            ?? $quiz->end_at
            ?? null;

        if (! $deadline) {
            return false;
        }

        return Carbon::parse($deadline)->isPast();
    }

    private function isAttemptExpired(LearningQuiz $quiz, LearningQuizAttempt $attempt): bool
    {
        $durationSeconds = $this->getQuizDurationSeconds($quiz);

        if ($durationSeconds <= 0 || ! $attempt->started_at) {
            return false;
        }

        return Carbon::parse($attempt->started_at)
            ->addSeconds($durationSeconds)
            ->isPast();
    }

    private function getRemainingSeconds(LearningQuiz $quiz, LearningQuizAttempt $attempt): int
    {
        $durationSeconds = $this->getQuizDurationSeconds($quiz);

        if ($durationSeconds <= 0 || ! $attempt->started_at) {
            return 0;
        }

        $endAt = Carbon::parse($attempt->started_at)->addSeconds($durationSeconds);

        return max(now()->diffInSeconds($endAt, false), 0);
    }

    private function getQuizDurationSeconds(LearningQuiz $quiz): int
    {
        if (! empty($quiz->duration_seconds)) {
            return (int) $quiz->duration_seconds;
        }

        if (! empty($quiz->time_limit_seconds)) {
            return (int) $quiz->time_limit_seconds;
        }

        $minutes = (int) ($quiz->duration_minutes ?? $quiz->time_limit_minutes ?? 0);

        return $minutes > 0 ? $minutes * 60 : 0;
    }

    private function getQuizDurationMinutes(LearningQuiz $quiz): int
    {
        $durationSeconds = $this->getQuizDurationSeconds($quiz);

        if ($durationSeconds <= 0) {
            return 0;
        }

        return (int) ceil($durationSeconds / 60);
    }

    private function calculateAttemptDurationSeconds(LearningQuizAttempt $attempt): int
    {
        if (! $attempt->started_at) {
            return 0;
        }

        return max(Carbon::parse($attempt->started_at)->diffInSeconds(now()), 0);
    }

    private function getQuizCourseName(LearningQuiz $quiz): string
    {
        return $quiz->course_name
            ?? $quiz->program_name
            ?? optional($quiz->course ?? null)->name
            ?? optional($quiz->program ?? null)->name
            ?? optional($quiz->batch ?? null)->name
            ?? 'Course';
    }

    private function getQuizModuleName(LearningQuiz $quiz): string
    {
        return $quiz->module_name
            ?? optional($quiz->module ?? null)->title
            ?? optional($quiz->module ?? null)->name
            ?? '-';
    }

    private function getQuizTopicName(LearningQuiz $quiz): string
    {
        return $quiz->topic_name
            ?? optional($quiz->topic ?? null)->title
            ?? optional($quiz->topic ?? null)->name
            ?? '-';
    }

    private function formatDurationLabel(LearningQuiz $quiz): string
    {
        $minutes = $this->getQuizDurationMinutes($quiz);

        return $minutes > 0 ? "{$minutes} min" : 'No limit';
    }

    private function formatAttemptLabel(int $attemptCount, ?int $maxAttempts): string
    {
        if ($maxAttempts) {
            return "{$attemptCount} / {$maxAttempts}";
        }

        return (string) $attemptCount;
    }

    private function formatDeadlineLabel(LearningQuiz $quiz): string
    {
        $deadline = $quiz->due_at
            ?? $quiz->deadline_at
            ?? $quiz->end_at
            ?? null;

        if (! $deadline) {
            return 'No deadline';
        }

        return Carbon::parse($deadline)->format('d M Y, H:i');
    }

    private function formatRemainingLabel(LearningQuiz $quiz): string
    {
        $deadline = $quiz->due_at
            ?? $quiz->deadline_at
            ?? $quiz->end_at
            ?? null;

        if (! $deadline) {
            return '-';
        }

        $deadline = Carbon::parse($deadline);

        if ($deadline->isPast()) {
            return 'Overdue';
        }

        return $deadline->diffForHumans(now(), [
            'parts' => 2,
            'short' => true,
            'syntax' => Carbon::DIFF_RELATIVE_TO_NOW,
        ]);
    }

    private function getQuizStatus(LearningQuiz $quiz, ?LearningQuizAttempt $latestAttempt): string
    {
        if ($this->isQuizOverdue($quiz)) {
            return 'overdue';
        }

        if ($latestAttempt?->is_passed) {
            return 'passed';
        }

        if ($latestAttempt?->status === 'graded') {
            return 'completed';
        }

        if ($latestAttempt?->status === 'submitted') {
            return 'submitted';
        }

        if ($latestAttempt?->status === 'in_progress') {
            return 'in_progress';
        }

        return $quiz->status ?? 'pending';
    }

    private function getQuizStatusLabel(LearningQuiz $quiz, ?LearningQuizAttempt $latestAttempt): string
    {
        return match ($this->getQuizStatus($quiz, $latestAttempt)) {
            'passed' => 'Passed',
            'completed' => 'Completed',
            'submitted' => 'Waiting Review',
            'in_progress' => 'In Progress',
            'overdue' => 'Overdue',
            'closed' => 'Closed',
            default => 'Pending',
        };
    }

    private function formatPercent(float|int|string $value): string
    {
        return rtrim(rtrim(number_format((float) $value, 2), '0'), '.') . '%';
    }

    private function filterExistingColumns(string $table, array $payload): array
    {
        return collect($payload)
            ->filter(fn ($value, $column) => Schema::hasColumn($table, $column))
            ->all();
    }
}