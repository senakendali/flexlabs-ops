<?php

namespace App\Http\Controllers\Api\Lms;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StudentGradeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $student = $this->resolveStudent($request);

        $assignments = $this->getAssignmentGrades($student);
        $quizzes = $this->getQuizGrades($student);

        $items = $quizzes
            ->merge($assignments)
            ->sortByDesc(fn (array $item) => strtotime($item['sortDate'] ?? '') ?: 0)
            ->values();

        return response()->json([
            'data' => [
                'student' => $this->formatStudent($student, $request),
                'notification_count' => 0,
                'summary' => $this->buildSummary($quizzes, $assignments, $items),
                'items' => $items,
                'quizzes' => $quizzes->values(),
                'assignments' => $assignments->values(),
                'next_action' => $this->buildNextAction($items),
            ],
        ]);
    }

    private function resolveStudent(Request $request): Student
    {
        $user = $request->user();

        abort_if(!$user, 401, 'Unauthorized.');

        $student = Student::query()
            ->where('user_id', $user->id)
            ->first();

        abort_if(!$student, 404, 'Student profile tidak ditemukan.');

        return $student;
    }

    private function formatStudent(Student $student, Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $student->id,
            'user_id' => $student->user_id,
            'full_name' => $student->full_name ?? $student->name ?? $user?->name ?? 'Student',
            'name' => $student->full_name ?? $student->name ?? $user?->name ?? 'Student',
            'email' => $student->email ?? $user?->email ?? 'student@email.com',
            'role' => 'FlexLabs Student',
            'avatar_url' => $student->avatar_url ?? null,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Assignments
    |--------------------------------------------------------------------------
    */

    private function getAssignmentGrades(Student $student): Collection
    {
        if (!$this->hasTable('assignment_submissions')) {
            return collect();
        }

        $query = DB::table('assignment_submissions as sub')
            ->where('sub.student_id', $student->id);

        $selects = [
            'sub.id',
            'sub.assignment_id',
            'sub.batch_assignment_id',
            'sub.batch_id',
            'sub.student_id',
            'sub.status',
            'sub.score',
            'sub.feedback',
            'sub.submitted_at',
            'sub.reviewed_at',
            'sub.created_at',
            'sub.updated_at',
        ];

        if ($this->hasTable('assignments') && $this->hasColumn('assignments', 'id')) {
            $query->leftJoin('assignments as a', 'a.id', '=', 'sub.assignment_id');

            $titleColumn = $this->firstAvailableColumn('assignments', [
                'title',
                'name',
            ]);

            $descriptionColumn = $this->firstAvailableColumn('assignments', [
                'description',
                'instruction',
                'instructions',
            ]);

            $dueDateColumn = $this->firstAvailableColumn('assignments', [
                'due_date',
                'deadline_at',
                'deadline',
                'available_until',
                'end_at',
            ]);

            if ($titleColumn) {
                $selects[] = DB::raw("a.{$titleColumn} as assignment_title");
            }

            if ($descriptionColumn) {
                $selects[] = DB::raw("a.{$descriptionColumn} as assignment_description");
            }

            if ($dueDateColumn) {
                $selects[] = DB::raw("a.{$dueDateColumn} as assignment_due_date");
            }
        }

        if ($this->hasTable('batch_assignments') && $this->hasColumn('batch_assignments', 'id')) {
            $query->leftJoin('batch_assignments as ba', 'ba.id', '=', 'sub.batch_assignment_id');

            $titleColumn = $this->firstAvailableColumn('batch_assignments', [
                'title',
                'name',
            ]);

            $dueDateColumn = $this->firstAvailableColumn('batch_assignments', [
                'due_date',
                'deadline_at',
                'deadline',
                'available_until',
                'end_at',
            ]);

            if ($titleColumn) {
                $selects[] = DB::raw("ba.{$titleColumn} as batch_assignment_title");
            }

            if ($dueDateColumn) {
                $selects[] = DB::raw("ba.{$dueDateColumn} as batch_assignment_due_date");
            }
        }

        if ($this->hasTable('batches') && $this->hasColumn('batches', 'id')) {
            $query->leftJoin('batches as b', 'b.id', '=', 'sub.batch_id');

            $batchNameColumn = $this->firstAvailableColumn('batches', [
                'name',
                'title',
                'batch_name',
            ]);

            if ($batchNameColumn) {
                $selects[] = DB::raw("b.{$batchNameColumn} as batch_name");
            }

            if (
                $this->hasColumn('batches', 'program_id')
                && $this->hasTable('programs')
                && $this->hasColumn('programs', 'id')
            ) {
                $query->leftJoin('programs as p', 'p.id', '=', 'b.program_id');

                $programNameColumn = $this->firstAvailableColumn('programs', [
                    'name',
                    'title',
                    'program_name',
                ]);

                if ($programNameColumn) {
                    $selects[] = DB::raw("p.{$programNameColumn} as course_title");
                }
            }
        }

        return $query
            ->select($selects)
            ->orderByDesc('sub.updated_at')
            ->get()
            ->map(fn (object $item) => $this->normalizeAssignmentSubmission($item))
            ->filter()
            ->values();
    }

    private function normalizeAssignmentSubmission(object $submission): array
    {
        $score = $this->toNumberOrNull($submission->score ?? null);
        $maxScore = 100;
        $percentage = $score !== null ? $this->clampPercent($score) : 0;

        $status = $this->normalizeAssignmentStatus(
            $submission->status ?? null,
            $score
        );

        $submittedAt = $submission->submitted_at ?? null;
        $reviewedAt = $submission->reviewed_at ?? null;

        $dueDate = $submission->assignment_due_date
            ?? $submission->batch_assignment_due_date
            ?? null;

        $title = $submission->assignment_title
            ?? $submission->batch_assignment_title
            ?? "Assignment #{$submission->assignment_id}";

        $courseTitle = $submission->course_title
            ?? $submission->batch_name
            ?? '-';

        $actionId = $submission->batch_assignment_id
            ?: $submission->assignment_id;

        return [
            'uid' => "assignment-{$submission->id}",
            'id' => $submission->id,
            'type' => 'assignment',
            'typeLabel' => 'Assignment',
            'title' => $title,
            'description' => $submission->assignment_description
                ?? 'Assignment result from your submitted task.',
            'courseTitle' => $courseTitle,
            'lessonTitle' => '-',
            'score' => $score,
            'maxScore' => $maxScore,
            'scoreLabel' => $score !== null ? $this->formatNumber($score) . "/{$maxScore}" : '-',
            'percentage' => $percentage,
            'percentageLabel' => "{$percentage}%",
            'status' => $status,
            'statusLabel' => $this->getStatusLabel($status),
            'submittedAt' => $submittedAt,
            'submittedAtLabel' => $this->formatDateTime($submittedAt),
            'gradedAt' => $reviewedAt,
            'gradedAtLabel' => $this->formatDateTime($reviewedAt),
            'dueDate' => $dueDate,
            'dueDateLabel' => $this->formatDateTime($dueDate),
            'attempt' => '-',
            'attemptLabel' => '-',
            'feedback' => $submission->feedback ?? '',
            'actionUrl' => $actionId ? "/assignments/{$actionId}" : null,
            'dateLabel' => $this->formatDate($reviewedAt ?: $submittedAt ?: $dueDate ?: $submission->updated_at),
            'sortDate' => $reviewedAt ?: $submittedAt ?: $dueDate ?: $submission->updated_at,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Quizzes
    |--------------------------------------------------------------------------
    */

    private function getQuizGrades(Student $student): Collection
    {
        if (!$this->hasTable('learning_quiz_answers')) {
            return collect();
        }

        if (!$this->hasTable('learning_quiz_attempts') || !$this->hasColumn('learning_quiz_attempts', 'id')) {
            return collect();
        }

        $studentColumn = null;
        $studentValue = null;

        if ($this->hasColumn('learning_quiz_attempts', 'student_id')) {
            $studentColumn = 'student_id';
            $studentValue = $student->id;
        } elseif ($this->hasColumn('learning_quiz_attempts', 'user_id')) {
            $studentColumn = 'user_id';
            $studentValue = $student->user_id;
        }

        if (!$studentColumn || !$studentValue) {
            return collect();
        }

        $attemptSelects = ['id'];

        foreach ([
            'learning_quiz_id',
            'quiz_id',
            'batch_id',
            'batch_assignment_id',
            'assignment_id',
            'program_id',
            'course_id',
            'status',
            'score',
            'final_score',
            'total_score',
            'max_score',
            'percentage',
            'due_date',
            'deadline_at',
            'deadline',
            'available_until',
            'end_at',
            'submitted_at',
            'completed_at',
            'graded_at',
            'reviewed_at',
            'created_at',
            'updated_at',
            'attempt_no',
            'attempt_count',
        ] as $column) {
            if ($this->hasColumn('learning_quiz_attempts', $column)) {
                $attemptSelects[] = $column;
            }
        }

        $orderColumn = $this->hasColumn('learning_quiz_attempts', 'updated_at')
            ? 'updated_at'
            : 'id';

        $attempts = DB::table('learning_quiz_attempts')
            ->where($studentColumn, $studentValue)
            ->select($attemptSelects)
            ->orderByDesc($orderColumn)
            ->get();

        if ($attempts->isEmpty()) {
            return collect();
        }

        $this->applyQuizAttemptLabels($attempts);

        $attemptIds = $attempts
            ->pluck('id')
            ->filter()
            ->values();

        $aggregates = DB::table('learning_quiz_answers')
            ->whereIn('learning_quiz_attempt_id', $attemptIds)
            ->select([
                'learning_quiz_attempt_id',
                DB::raw('COALESCE(SUM(score), 0) as answer_score'),
                DB::raw('COUNT(id) as total_answers'),
                DB::raw('SUM(CASE WHEN is_correct = 1 THEN 1 ELSE 0 END) as correct_answers'),
            ])
            ->groupBy('learning_quiz_attempt_id')
            ->get()
            ->keyBy('learning_quiz_attempt_id');

        $quizMaster = $this->getQuizMasterData($attempts);
        $batchAssignmentMaster = $this->getBatchAssignmentMasterData($attempts, $quizMaster);
        $batchMaster = $this->getBatchMasterData($attempts, $quizMaster, $batchAssignmentMaster);
        $programMaster = $this->getProgramMasterData($attempts, $quizMaster, $batchAssignmentMaster, $batchMaster);
        $moduleMaster = $this->getSimpleMasterData($quizMaster, 'module_id', [
            'modules',
            'learning_modules',
            'course_modules',
            'program_modules',
        ]);
        $topicMaster = $this->getSimpleMasterData($quizMaster, 'topic_id', [
            'topics',
            'learning_topics',
            'course_topics',
        ]);
        $subTopicMaster = $this->getSimpleMasterData($quizMaster, 'sub_topic_id', [
            'sub_topics',
            'subtopics',
            'learning_sub_topics',
        ]);
        $lessonMaster = $this->getSimpleMasterData($quizMaster, 'lesson_id', [
            'lessons',
            'learning_lessons',
        ]);

        return $attempts
            ->map(function (object $attempt) use (
                $aggregates,
                $quizMaster,
                $batchAssignmentMaster,
                $batchMaster,
                $programMaster,
                $moduleMaster,
                $topicMaster,
                $subTopicMaster,
                $lessonMaster
            ) {
                $quizId = $this->rowValue($attempt, ['learning_quiz_id', 'quiz_id']);
                $quiz = $quizId ? $quizMaster->get($quizId) : null;

                $batchAssignmentId = $this->rowValue($attempt, ['batch_assignment_id'])
                    ?? $this->rowValue($quiz, ['batch_assignment_id']);

                $batchAssignment = $batchAssignmentId
                    ? $batchAssignmentMaster->get($batchAssignmentId)
                    : null;

                $batchId = $this->rowValue($attempt, ['batch_id'])
                    ?? $this->rowValue($quiz, ['batch_id'])
                    ?? $this->rowValue($batchAssignment, ['batch_id']);

                $batch = $batchId ? $batchMaster->get($batchId) : null;
                $aggregate = $aggregates->get($attempt->id);

                return $this->normalizeQuizAttempt(
                    $attempt,
                    $aggregate,
                    $quiz,
                    $batch,
                    $batchAssignment,
                    $programMaster,
                    $moduleMaster,
                    $topicMaster,
                    $subTopicMaster,
                    $lessonMaster
                );
            })
            ->filter()
            ->values();
    }

    private function getQuizMasterData(Collection $attempts): Collection
    {
        if (!$this->hasTable('learning_quizzes') || !$this->hasColumn('learning_quizzes', 'id')) {
            return collect();
        }

        $quizIds = $attempts
            ->map(fn (object $attempt) => $this->rowValue($attempt, ['learning_quiz_id', 'quiz_id']))
            ->filter()
            ->unique()
            ->values();

        if ($quizIds->isEmpty()) {
            return collect();
        }

        $selects = ['id'];

        foreach ([
            'title',
            'name',
            'description',
            'passing_score',
            'max_score',
            'batch_id',
            'batch_assignment_id',
            'assignment_id',
            'program_id',
            'course_id',
            'topic_id',
            'module_id',
            'sub_topic_id',
            'lesson_id',
            'due_date',
            'deadline_at',
            'deadline',
            'available_until',
            'end_at',
        ] as $column) {
            if ($this->hasColumn('learning_quizzes', $column)) {
                $selects[] = $column;
            }
        }

        return DB::table('learning_quizzes')
            ->whereIn('id', $quizIds)
            ->select($selects)
            ->get()
            ->keyBy('id');
    }

    private function getBatchAssignmentMasterData(Collection $attempts, Collection $quizMaster): Collection
    {
        if (!$this->hasTable('batch_assignments') || !$this->hasColumn('batch_assignments', 'id')) {
            return collect();
        }

        $ids = collect()
            ->merge($attempts->map(fn (object $attempt) => $this->rowValue($attempt, ['batch_assignment_id'])))
            ->merge($quizMaster->map(fn (object $quiz) => $this->rowValue($quiz, ['batch_assignment_id'])))
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $selects = ['id'];

        foreach ([
            'batch_id',
            'assignment_id',
            'program_id',
            'title',
            'name',
            'due_date',
            'deadline_at',
            'deadline',
            'available_until',
            'end_at',
        ] as $column) {
            if ($this->hasColumn('batch_assignments', $column)) {
                $selects[] = $column;
            }
        }

        return DB::table('batch_assignments')
            ->whereIn('id', $ids)
            ->select($selects)
            ->get()
            ->keyBy('id');
    }

    private function getBatchMasterData(
        Collection $attempts,
        Collection $quizMaster,
        Collection $batchAssignmentMaster
    ): Collection {
        if (!$this->hasTable('batches') || !$this->hasColumn('batches', 'id')) {
            return collect();
        }

        $ids = collect()
            ->merge($attempts->map(fn (object $attempt) => $this->rowValue($attempt, ['batch_id'])))
            ->merge($quizMaster->map(fn (object $quiz) => $this->rowValue($quiz, ['batch_id'])))
            ->merge($batchAssignmentMaster->map(fn (object $batchAssignment) => $this->rowValue($batchAssignment, ['batch_id'])))
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $selects = ['id'];

        foreach ([
            'name',
            'title',
            'batch_name',
            'program_id',
        ] as $column) {
            if ($this->hasColumn('batches', $column)) {
                $selects[] = $column;
            }
        }

        return DB::table('batches')
            ->whereIn('id', $ids)
            ->select($selects)
            ->get()
            ->keyBy('id');
    }

    private function getProgramMasterData(
        Collection $attempts,
        Collection $quizMaster,
        Collection $batchAssignmentMaster,
        Collection $batchMaster
    ): Collection {
        if (!$this->hasTable('programs') || !$this->hasColumn('programs', 'id')) {
            return collect();
        }

        $ids = collect()
            ->merge($attempts->map(fn (object $attempt) => $this->rowValue($attempt, ['program_id', 'course_id'])))
            ->merge($quizMaster->map(fn (object $quiz) => $this->rowValue($quiz, ['program_id', 'course_id'])))
            ->merge($batchAssignmentMaster->map(fn (object $batchAssignment) => $this->rowValue($batchAssignment, ['program_id'])))
            ->merge($batchMaster->map(fn (object $batch) => $this->rowValue($batch, ['program_id'])))
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $selects = ['id'];

        foreach ([
            'name',
            'title',
            'program_name',
        ] as $column) {
            if ($this->hasColumn('programs', $column)) {
                $selects[] = $column;
            }
        }

        return DB::table('programs')
            ->whereIn('id', $ids)
            ->select($selects)
            ->get()
            ->keyBy('id');
    }

    private function getSimpleMasterData(Collection $quizMaster, string $foreignKey, array $tables): Collection
    {
        $table = $this->firstAvailableTable($tables);

        if (!$table || !$this->hasColumn($table, 'id')) {
            return collect();
        }

        $ids = $quizMaster
            ->map(fn (object $quiz) => $this->rowValue($quiz, [$foreignKey]))
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $selects = ['id'];

        foreach ([
            'title',
            'name',
        ] as $column) {
            if ($this->hasColumn($table, $column)) {
                $selects[] = $column;
            }
        }

        return DB::table($table)
            ->whereIn('id', $ids)
            ->select($selects)
            ->get()
            ->keyBy('id');
    }

    private function normalizeQuizAttempt(
        object $attempt,
        ?object $aggregate,
        ?object $quiz,
        ?object $batch,
        ?object $batchAssignment,
        Collection $programMaster,
        Collection $moduleMaster,
        Collection $topicMaster,
        Collection $subTopicMaster,
        Collection $lessonMaster
    ): array {
        $quizId = $this->rowValue($attempt, ['learning_quiz_id', 'quiz_id']);

        $storedScore = $this->toNumberOrNull($this->rowValue($attempt, [
            'score',
            'final_score',
            'total_score',
        ]));

        $answerScore = $this->toNumberOrNull($aggregate->answer_score ?? null);
        $correctAnswers = (int) ($aggregate->correct_answers ?? 0);
        $totalAnswers = (int) ($aggregate->total_answers ?? 0);

        /*
        |--------------------------------------------------------------------------
        | Score Scale
        |--------------------------------------------------------------------------
        | Final quiz score selalu dinormalisasi ke skala 0-100.
        */
        $percentage = $this->resolveQuizPercentage(
            $attempt,
            $storedScore,
            $answerScore,
            $correctAnswers,
            $totalAnswers
        );

        $score = $percentage;
        $maxScore = 100;

        $submittedAt = $this->rowValue($attempt, [
            'submitted_at',
            'completed_at',
            'updated_at',
            'created_at',
        ]);

        $gradedAt = $this->rowValue($attempt, [
            'graded_at',
            'reviewed_at',
        ]);

        $dueDate = $this->resolveDueDateForQuiz($attempt, $quiz, $batchAssignment);
        $attemptLabel = $this->resolveAttemptLabel($attempt);
        $lessonTitle = $this->resolveLessonTitleForQuiz($quiz, $batchAssignment, $moduleMaster, $topicMaster, $subTopicMaster, $lessonMaster);
        $courseTitle = $this->resolveCourseTitleForQuiz($attempt, $quiz, $batch, $batchAssignment, $programMaster);

        $passingScore = $this->toNumberOrNull($this->rowValue($quiz, ['passing_score'])) ?? 70;

        $status = $this->normalizeQuizStatus(
            $this->rowValue($attempt, ['status']),
            [
                'score' => $score,
                'percentage' => $percentage,
                'submitted_at' => $submittedAt,
                'graded_at' => $gradedAt,
                'passing_score' => $passingScore,
            ]
        );

        $title = $this->rowValue($quiz, ['title', 'name'])
            ?? ($quizId ? "Quiz #{$quizId}" : "Quiz Attempt #{$attempt->id}");

        return [
            'uid' => "quiz-{$attempt->id}",
            'id' => $attempt->id,
            'type' => 'quiz',
            'typeLabel' => 'Quiz',
            'title' => $title,
            'description' => $this->rowValue($quiz, ['description'])
                ?? 'Quiz result from your learning activity.',
            'courseTitle' => $courseTitle,
            'lessonTitle' => $lessonTitle,
            'score' => $score,
            'maxScore' => $maxScore,
            'scoreLabel' => "{$score}/{$maxScore}",
            'percentage' => $percentage,
            'percentageLabel' => "{$percentage}%",
            'status' => $status,
            'statusLabel' => $this->getStatusLabel($status),
            'submittedAt' => $submittedAt,
            'submittedAtLabel' => $this->formatDateTime($submittedAt),
            'gradedAt' => $gradedAt,
            'gradedAtLabel' => $this->formatDateTime($gradedAt),
            'dueDate' => $dueDate,
            'dueDateLabel' => $this->formatDateTime($dueDate),

            /*
            |--------------------------------------------------------------------------
            | Frontend Compatibility
            |--------------------------------------------------------------------------
            | GradesPage.vue membaca quiz.attempt atau quiz.attemptLabel.
            */
            'attempt' => $attemptLabel,
            'attemptLabel' => $attemptLabel,
            'attempt_no' => $attemptLabel,
            'attemptNo' => $attemptLabel,

            'feedback' => '',
            'actionUrl' => $quizId ? "/quizzes/{$quizId}" : null,
            'dateLabel' => $this->formatDate($gradedAt ?: $submittedAt ?: $dueDate ?: $this->rowValue($attempt, ['updated_at', 'created_at'])),
            'sortDate' => $gradedAt ?: $submittedAt ?: $dueDate ?: $this->rowValue($attempt, ['updated_at', 'created_at']),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Summary
    |--------------------------------------------------------------------------
    */

    private function buildSummary(Collection $quizzes, Collection $assignments, Collection $items): array
    {
        return [
            'average_quiz_score' => $this->averagePercentage($quizzes),
            'average_assignment_score' => $this->averagePercentage($assignments),
            'graded_count' => $items
                ->filter(fn (array $item) => in_array($item['status'], ['graded', 'passed', 'failed'], true))
                ->count(),
            'pending_review_count' => $items
                ->filter(fn (array $item) => in_array($item['status'], ['need_review', 'submitted'], true))
                ->count(),
            'quiz_count' => $quizzes->count(),
            'assignment_count' => $assignments->count(),
            'total_count' => $items->count(),
        ];
    }

    private function buildNextAction(Collection $items): ?array
    {
        $next = $items->first(fn (array $item) => in_array($item['status'], [
            'missing',
            'returned',
            'need_review',
            'submitted',
            'in_progress',
            'not_started',
        ], true));

        if (!$next) {
            return null;
        }

        return [
            'title' => $next['title'],
            'description' => match ($next['status']) {
                'missing' => 'Ada aktivitas yang belum dikumpulkan atau sudah melewati deadline.',
                'need_review', 'submitted' => 'Aktivitas sudah dikumpulkan dan sedang menunggu review instructor.',
                'in_progress', 'not_started' => 'Lanjutkan aktivitas belajar supaya nilai bisa diperbarui.',
                default => 'Cek detail aktivitas untuk melihat status terbaru.',
            },
            'url' => $next['actionUrl'] ?? null,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Status Resolver
    |--------------------------------------------------------------------------
    */

    private function normalizeAssignmentStatus(?string $status, ?float $score): string
    {
        $status = strtolower(trim((string) $status));

        if ($status === 'reviewed') {
            if ($score === null) {
                return 'graded';
            }

            return $score >= 70 ? 'passed' : 'failed';
        }

        if ($status === 'submitted') {
            return 'submitted';
        }

        if ($status === 'late') {
            return 'missing';
        }

        if ($status === 'returned') {
            return 'need_review';
        }

        if ($status === 'draft') {
            return 'not_started';
        }

        return 'not_started';
    }

    private function normalizeQuizStatus(?string $status, array $context = []): string
    {
        $status = strtolower(trim((string) $status));

        if (in_array($status, ['passed', 'pass', 'completed', 'complete'], true)) {
            return 'passed';
        }

        if (in_array($status, ['failed', 'fail', 'not_passed', 'not passed'], true)) {
            return 'failed';
        }

        if (in_array($status, ['graded', 'reviewed', 'scored'], true)) {
            return 'graded';
        }

        if (in_array($status, ['submitted', 'turned_in'], true)) {
            return 'submitted';
        }

        if (in_array($status, ['need_review', 'needs_review', 'under_review', 'pending_review', 'waiting_review'], true)) {
            return 'need_review';
        }

        if (in_array($status, ['missing', 'late', 'overdue'], true)) {
            return 'missing';
        }

        if (in_array($status, ['in_progress', 'progress'], true)) {
            return 'in_progress';
        }

        if (in_array($status, ['not_started', 'not started', 'locked', 'draft'], true)) {
            return 'not_started';
        }

        $score = $context['score'] ?? null;
        $percentage = (int) ($context['percentage'] ?? 0);
        $passingScore = (int) ($context['passing_score'] ?? 70);

        if ($score !== null) {
            return $percentage >= $passingScore ? 'passed' : 'failed';
        }

        if (!empty($context['graded_at'])) {
            return 'graded';
        }

        if (!empty($context['submitted_at'])) {
            return 'submitted';
        }

        return 'not_started';
    }

    private function getStatusLabel(string $status): string
    {
        return match ($status) {
            'passed' => 'Passed',
            'failed' => 'Failed',
            'graded' => 'Graded',
            'need_review' => 'Need Review',
            'submitted' => 'Submitted',
            'missing' => 'Missing',
            'in_progress' => 'In Progress',
            'not_started' => 'Not Started',
            default => 'Not Started',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Quiz Detail Resolver
    |--------------------------------------------------------------------------
    */

    private function resolveQuizPercentage(
        object $attempt,
        ?float $storedScore,
        ?float $answerScore,
        int $correctAnswers,
        int $totalAnswers
    ): int {
        $directPercentage = $this->toNumberOrNull($this->rowValue($attempt, ['percentage']));

        if ($directPercentage !== null) {
            return $this->clampPercent($directPercentage);
        }

        /*
        |--------------------------------------------------------------------------
        | Attempt score
        |--------------------------------------------------------------------------
        | Kalau attempt punya score/final_score/total_score, anggap sudah skala 0-100.
        */
        if ($storedScore !== null) {
            return $this->clampPercent($storedScore);
        }

        /*
        |--------------------------------------------------------------------------
        | Answer score
        |--------------------------------------------------------------------------
        | Kalau total score answer <= jumlah answer, berarti score per soal 0/1.
        | Maka dikonversi ke skala 100.
        */
        if ($answerScore !== null && $answerScore > 0) {
            if ($totalAnswers > 0 && $answerScore <= $totalAnswers) {
                return $this->clampPercent(($answerScore / $totalAnswers) * 100);
            }

            return $this->clampPercent($answerScore);
        }

        if ($totalAnswers > 0) {
            return $this->clampPercent(($correctAnswers / $totalAnswers) * 100);
        }

        return 0;
    }

    private function applyQuizAttemptLabels(Collection $attempts): void
    {
        $attempts
            ->sortBy(function (object $attempt) {
                $date = $this->rowValue($attempt, [
                    'submitted_at',
                    'completed_at',
                    'created_at',
                    'updated_at',
                ]);

                return strtotime($date ?: '') ?: (int) $attempt->id;
            })
            ->groupBy(function (object $attempt) {
                $quizId = $this->rowValue($attempt, ['learning_quiz_id', 'quiz_id']) ?? 'quizless';
                $batchId = $this->rowValue($attempt, ['batch_id']) ?? 'batchless';
                $batchAssignmentId = $this->rowValue($attempt, ['batch_assignment_id']) ?? 'assignmentless';

                return "{$quizId}|{$batchId}|{$batchAssignmentId}";
            })
            ->each(function (Collection $group) {
                $total = $group->count();

                $group
                    ->values()
                    ->each(function (object $attempt, int $index) use ($total) {
                        $attempt->__attempt_number = $index + 1;
                        $attempt->__attempt_total = $total;
                    });
            });
    }

    private function resolveAttemptLabel(object $attempt): string
    {
        $number = $this->rowValue($attempt, ['__attempt_number']);
        $total = $this->rowValue($attempt, ['__attempt_total']);

        if ($number && $total && (int) $total > 1) {
            return "Attempt {$number} of {$total}";
        }

        if ($number) {
            return "Attempt {$number}";
        }

        $dbAttempt = $this->rowValue($attempt, [
            'attempt_no',
            'attempt_count',
        ]);

        if ($dbAttempt) {
            return "Attempt {$dbAttempt}";
        }

        return '-';
    }

    private function resolveDueDateForQuiz(
        ?object $attempt,
        ?object $quiz,
        ?object $batchAssignment
    ): ?string {
        return $this->rowValue($attempt, [
            'due_date',
            'deadline_at',
            'deadline',
            'available_until',
            'end_at',
        ])
            ?? $this->rowValue($quiz, [
                'due_date',
                'deadline_at',
                'deadline',
                'available_until',
                'end_at',
            ])
            ?? $this->rowValue($batchAssignment, [
                'due_date',
                'deadline_at',
                'deadline',
                'available_until',
                'end_at',
            ]);
    }

    private function resolveLessonTitleForQuiz(
        ?object $quiz,
        ?object $batchAssignment,
        Collection $moduleMaster,
        Collection $topicMaster,
        Collection $subTopicMaster,
        Collection $lessonMaster
    ): string {
        $module = $this->getMasterTitle($moduleMaster, $this->rowValue($quiz, ['module_id']));
        $topic = $this->getMasterTitle($topicMaster, $this->rowValue($quiz, ['topic_id']));
        $subTopic = $this->getMasterTitle($subTopicMaster, $this->rowValue($quiz, ['sub_topic_id']));
        $lesson = $this->getMasterTitle($lessonMaster, $this->rowValue($quiz, ['lesson_id']));

        $parts = collect([
            $module,
            $topic,
            $subTopic,
            $lesson,
        ])
            ->filter()
            ->unique()
            ->values();

        if ($parts->isNotEmpty()) {
            return $parts->implode(' · ');
        }

        return $this->rowValue($batchAssignment, [
            'title',
            'name',
        ])
            ?? $this->rowValue($quiz, [
                'title',
                'name',
            ])
            ?? '-';
    }

    private function resolveCourseTitleForQuiz(
        ?object $attempt,
        ?object $quiz,
        ?object $batch,
        ?object $batchAssignment,
        Collection $programMaster
    ): string {
        $programId = $this->rowValue($attempt, ['program_id', 'course_id'])
            ?? $this->rowValue($quiz, ['program_id', 'course_id'])
            ?? $this->rowValue($batchAssignment, ['program_id'])
            ?? $this->rowValue($batch, ['program_id']);

        $programTitle = $this->getMasterTitle($programMaster, $programId);

        if ($programTitle) {
            return $programTitle;
        }

        return $this->rowValue($batch, ['name', 'title', 'batch_name'])
            ?? $this->rowValue($batchAssignment, ['title', 'name'])
            ?? '-';
    }

    /*
    |--------------------------------------------------------------------------
    | Generic Helpers
    |--------------------------------------------------------------------------
    */

    private function averagePercentage(Collection $items): int
    {
        $scoredItems = $items
            ->filter(fn (array $item) => isset($item['percentage']) && $item['percentage'] > 0);

        if ($scoredItems->isEmpty()) {
            return 0;
        }

        return (int) round($scoredItems->avg('percentage'));
    }

    private function getMasterTitle(Collection $master, mixed $id): ?string
    {
        if (!$id) {
            return null;
        }

        $row = $master->get($id);

        if (!$row) {
            return null;
        }

        return $this->rowValue($row, [
            'name',
            'title',
            'program_name',
            'batch_name',
        ]);
    }

    private function formatDate(?string $date): string
    {
        if (!$date) {
            return '-';
        }

        $timestamp = strtotime($date);

        if (!$timestamp) {
            return '-';
        }

        return date('d M Y', $timestamp);
    }

    private function formatDateTime(?string $date): string
    {
        if (!$date) {
            return '-';
        }

        $timestamp = strtotime($date);

        if (!$timestamp) {
            return '-';
        }

        return date('d M Y H:i', $timestamp);
    }

    private function formatNumber(float|int|null $number): string
    {
        if ($number === null) {
            return '-';
        }

        if (floor($number) == $number) {
            return (string) (int) $number;
        }

        return rtrim(rtrim(number_format((float) $number, 2, '.', ''), '0'), '.');
    }

    private function toNumberOrNull(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function clampPercent(mixed $value): int
    {
        $number = is_numeric($value) ? (float) $value : 0;

        return (int) min(100, max(0, round($number)));
    }

    private function rowValue(mixed $row, array $keys, mixed $default = null): mixed
    {
        if (!$row) {
            return $default;
        }

        foreach ($keys as $key) {
            $value = data_get($row, $key);

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return $default;
    }

    private function firstAvailableTable(array $tables): ?string
    {
        foreach ($tables as $table) {
            if ($this->hasTable($table)) {
                return $table;
            }
        }

        return null;
    }

    private function firstAvailableColumn(string $table, array $columns): ?string
    {
        foreach ($columns as $column) {
            if ($this->hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    private function hasTable(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }

    private function hasColumn(string $table, string $column): bool
    {
        try {
            return Schema::hasTable($table) && Schema::hasColumn($table, $column);
        } catch (\Throwable) {
            return false;
        }
    }
}