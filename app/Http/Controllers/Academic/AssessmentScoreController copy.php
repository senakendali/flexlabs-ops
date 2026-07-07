<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\AssessmentComponent;
use App\Models\AssessmentTemplate;
use App\Models\Batch;
use App\Models\Student;
use App\Models\StudentAssessmentScore;
use App\Models\StudentAttendance;
use App\Models\StudentRubricScore;
use App\Services\Assessment\AssessmentCalculatorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AssessmentScoreController extends Controller
{
    public function __construct(
        protected AssessmentCalculatorService $calculator
    ) {
    }

    public function index(Request $request)
    {
        $batchId = $request->query('batch_id');

        $batches = Batch::query()
            ->with(['program', 'assessmentTemplate'])
            ->latest()
            ->get();

        $selectedBatch = null;
        $template = null;
        $components = collect();
        $students = collect();
        $scoreMap = [];
        $componentsPayload = [];
        $studentPayload = [];

        $stats = [
            'students' => 0,
            'components' => 0,
            'scores_filled' => 0,
            'completion_rate' => 0,
        ];

        if ($batchId) {
            $selectedBatch = Batch::query()
                ->with(['program', 'assessmentTemplate.components.rubric.criteria'])
                ->findOrFail($batchId);

            $template = $this->resolveTemplate($selectedBatch);

            if ($template) {
                $template->loadMissing([
                    'components.rubric.criteria',
                    'components.rubric.levels',
                ]);

                $components = $template->components;

                $students = $this->getStudentsByBatch($selectedBatch);

                /*
                |--------------------------------------------------------------------------
                | Sync Auto Calculated Components
                |--------------------------------------------------------------------------
                | Attendance, VOD/progress, dan quiz tidak diinput manual.
                | Saat halaman dibuka, sistem sync score dari source data ke
                | student_assessment_scores sebagai snapshot assessment.
                */
                $this->syncAutoCalculatedScores(
                    batch: $selectedBatch,
                    template: $template,
                    components: $components,
                    students: $students,
                    assessedBy: $request->user()?->id
                );
            }

            $scores = StudentAssessmentScore::query()
                ->with(['component', 'rubricScores.criteria'])
                ->where('batch_id', $selectedBatch->id)
                ->when($template, function ($query) use ($template) {
                    $query->where('assessment_template_id', $template->id);
                })
                ->get();

            $scoreMap = $scores
                ->groupBy('student_id')
                ->map(function ($studentScores) {
                    return $studentScores
                        ->keyBy('assessment_component_id')
                        ->map(function ($score) {
                            return [
                                'id' => $score->id,
                                'raw_score' => (float) $score->raw_score,
                                'weight' => (float) $score->weight,
                                'weighted_score' => (float) $score->weighted_score,
                                'feedback' => $score->feedback,
                                'status' => $score->status,
                                'rubric_scores' => $score->rubricScores->map(function ($rubricScore) {
                                    return [
                                        'criteria_id' => $rubricScore->assessment_rubric_criteria_id,
                                        'raw_score' => (float) $rubricScore->raw_score,
                                        'note' => $rubricScore->note,
                                    ];
                                })->values(),
                            ];
                        });
                })
                ->toArray();

            $componentsPayload = $components->map(function ($component) {
                return [
                    'id' => $component->id,
                    'name' => $component->name,
                    'code' => $component->code,
                    'type' => $component->type,
                    'weight' => (float) $component->weight,
                    'max_score' => (float) $component->max_score,
                    'is_auto_calculated' => $this->isAutoCalculatedComponent($component),
                    'auto_source_label' => $this->autoSourceLabel($component),
                    'has_rubric' => (bool) $component->rubric,
                    'rubric' => $component->rubric ? [
                        'id' => $component->rubric->id,
                        'name' => $component->rubric->name,
                        'criteria' => $component->rubric->criteria->map(function ($criterion) {
                            return [
                                'id' => $criterion->id,
                                'name' => $criterion->name,
                                'code' => $criterion->code,
                                'weight' => (float) $criterion->weight,
                                'max_score' => (float) $criterion->max_score,
                                'description' => $criterion->description,
                            ];
                        })->values(),
                    ] : null,
                ];
            })->values()->toArray();

            $studentPayload = $students->map(function ($student) {
                return [
                    'id' => $student->id,
                    'name' => $this->resolveStudentDisplayName($student),
                    'email' => $student->email ?? null,
                ];
            })->values()->toArray();

            $totalPossibleScores = $students->count() * $components->count();
            $filledScores = $scores->count();

            $stats = [
                'students' => $students->count(),
                'components' => $components->count(),
                'scores_filled' => $filledScores,
                'completion_rate' => $totalPossibleScores > 0
                    ? round(($filledScores / $totalPossibleScores) * 100)
                    : 0,
            ];
        }

        if ($request->expectsJson()) {
            return response()->json([
                'batches' => $batches,
                'selected_batch' => $selectedBatch,
                'template' => $template,
                'components' => $components,
                'students' => $students,
                'score_map' => $scoreMap,
                'stats' => $stats,
            ]);
        }

        return view('academic.assessment-scores.index', compact(
            'batches',
            'selectedBatch',
            'template',
            'components',
            'students',
            'scoreMap',
            'componentsPayload',
            'studentPayload',
            'stats'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'batch_id' => ['required', 'exists:batches,id'],
            'assessment_component_id' => ['required', 'exists:assessment_components,id'],
            'raw_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'feedback' => ['nullable', 'string'],
            'status' => ['nullable', 'in:draft,submitted,reviewed,approved'],

            'rubric_scores' => ['nullable', 'array'],
            'rubric_scores.*.criteria_id' => ['required_with:rubric_scores', 'exists:assessment_rubric_criteria,id'],
            'rubric_scores.*.raw_score' => ['required_with:rubric_scores', 'numeric', 'min:0', 'max:100'],
            'rubric_scores.*.note' => ['nullable', 'string'],
        ]);

        $score = DB::transaction(function () use ($validated, $request) {
            $batch = Batch::query()->findOrFail($validated['batch_id']);

            $component = AssessmentComponent::query()
                ->with('rubric.criteria')
                ->findOrFail($validated['assessment_component_id']);

            $template = $this->resolveTemplate($batch);

            if (! $template) {
                throw ValidationException::withMessages([
                    'batch_id' => 'This batch does not have an assessment template.',
                ]);
            }

            if ((int) $component->assessment_template_id !== (int) $template->id) {
                throw ValidationException::withMessages([
                    'assessment_component_id' => 'Selected component does not belong to this batch assessment template.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Auto Calculated Components
            |--------------------------------------------------------------------------
            | Attendance, progress/VOD, dan quiz selalu dihitung dari source data.
            | raw_score dari request akan diabaikan supaya tidak bisa diisi manual.
            */
            if ($this->isAutoCalculatedComponent($component)) {
                $autoScore = $this->calculateAutoScore(
                    studentId: (int) $validated['student_id'],
                    batch: $batch,
                    component: $component
                );

                if ($autoScore === null) {
                    throw ValidationException::withMessages([
                        'raw_score' => 'Auto score belum bisa dihitung karena source data belum tersedia.',
                    ]);
                }

                return $this->saveAssessmentScore(
                    studentId: (int) $validated['student_id'],
                    batch: $batch,
                    template: $template,
                    component: $component,
                    rawScore: $autoScore,
                    feedback: $validated['feedback'] ?? $this->autoFeedbackLabel($component),
                    status: $validated['status'] ?? 'reviewed',
                    assessedBy: $request->user()?->id
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Manual / Rubric Components
            |--------------------------------------------------------------------------
            | Component manual bisa diisi langsung raw_score atau dari rubric criteria.
            */
            $rawScore = $validated['raw_score'] ?? null;

            $score = $this->saveAssessmentScore(
                studentId: (int) $validated['student_id'],
                batch: $batch,
                template: $template,
                component: $component,
                rawScore: (float) ($rawScore ?? 0),
                feedback: $validated['feedback'] ?? null,
                status: $validated['status'] ?? 'reviewed',
                assessedBy: $request->user()?->id
            );

            if (! empty($validated['rubric_scores'])) {
                $rubricRawScore = $this->storeRubricScores(
                    score: $score,
                    component: $component,
                    rubricScores: $validated['rubric_scores']
                );

                $score = $this->saveAssessmentScore(
                    studentId: (int) $validated['student_id'],
                    batch: $batch,
                    template: $template,
                    component: $component,
                    rawScore: $rubricRawScore,
                    feedback: $validated['feedback'] ?? null,
                    status: $validated['status'] ?? 'reviewed',
                    assessedBy: $request->user()?->id
                );
            } elseif ($rawScore === null) {
                throw ValidationException::withMessages([
                    'raw_score' => 'Raw score wajib diisi untuk component manual.',
                ]);
            }

            return $score->fresh([
                'student',
                'batch',
                'template',
                'component.rubric.criteria',
                'rubricScores.criteria',
            ]);
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Assessment score berhasil disimpan.',
                'score' => $score,
            ]);
        }

        return redirect()
            ->route('academic.assessment-scores.index', [
                'batch_id' => $validated['batch_id'],
            ])
            ->with('success', 'Assessment score berhasil disimpan.');
    }

    public function bulkStore(Request $request)
    {
        $validated = $request->validate([
            'batch_id' => ['required', 'exists:batches,id'],
            'scores' => ['required', 'array'],
            'scores.*.student_id' => ['required', 'exists:students,id'],
            'scores.*.assessment_component_id' => ['required', 'exists:assessment_components,id'],
            'scores.*.raw_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'scores.*.feedback' => ['nullable', 'string'],
            'scores.*.status' => ['nullable', 'in:draft,submitted,reviewed,approved'],
        ]);

        $batch = Batch::query()->findOrFail($validated['batch_id']);
        $template = $this->resolveTemplate($batch);

        if (! $template) {
            throw ValidationException::withMessages([
                'batch_id' => 'This batch does not have an assessment template.',
            ]);
        }

        $components = AssessmentComponent::query()
            ->where('assessment_template_id', $template->id)
            ->get()
            ->keyBy('id');

        DB::transaction(function () use ($validated, $batch, $template, $components, $request) {
            foreach ($validated['scores'] as $row) {
                $component = $components->get((int) $row['assessment_component_id']);

                if (! $component) {
                    continue;
                }

                if ($this->isAutoCalculatedComponent($component)) {
                    $rawScore = $this->calculateAutoScore(
                        studentId: (int) $row['student_id'],
                        batch: $batch,
                        component: $component
                    );

                    if ($rawScore === null) {
                        continue;
                    }
                } else {
                    $rawScore = $row['raw_score'] ?? null;

                    if ($rawScore === null) {
                        continue;
                    }
                }

                $this->saveAssessmentScore(
                    studentId: (int) $row['student_id'],
                    batch: $batch,
                    template: $template,
                    component: $component,
                    rawScore: (float) $rawScore,
                    feedback: $row['feedback'] ?? ($this->isAutoCalculatedComponent($component) ? $this->autoFeedbackLabel($component) : null),
                    status: $row['status'] ?? 'reviewed',
                    assessedBy: $request->user()?->id
                );
            }
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Bulk assessment score berhasil disimpan.',
            ]);
        }

        return redirect()
            ->route('academic.assessment-scores.index', [
                'batch_id' => $batch->id,
            ])
            ->with('success', 'Bulk assessment score berhasil disimpan.');
    }

    public function preview(Request $request)
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'batch_id' => ['required', 'exists:batches,id'],
        ]);

        $batch = Batch::query()->with('program')->findOrFail($validated['batch_id']);
        $template = $this->resolveTemplate($batch);

        if (! $template) {
            throw ValidationException::withMessages([
                'batch_id' => 'This batch does not have an assessment template.',
            ]);
        }

        $template->loadMissing('components');

        $student = Student::query()->findOrFail($validated['student_id']);

        /*
        |--------------------------------------------------------------------------
        | Ensure auto score is fresh before preview.
        |--------------------------------------------------------------------------
        */
        $this->syncAutoCalculatedScores(
            batch: $batch,
            template: $template,
            components: $template->components,
            students: collect([$student]),
            assessedBy: $request->user()?->id
        );

        $scores = StudentAssessmentScore::query()
            ->with(['component', 'rubricScores.criteria'])
            ->where('student_id', $student->id)
            ->where('batch_id', $batch->id)
            ->where('assessment_template_id', $template->id)
            ->get();

        $totalWeightedScore = round((float) $scores->sum('weighted_score'), 2);

        $missingComponents = $template->components
            ->filter(function ($component) use ($scores) {
                return ! $scores->firstWhere('assessment_component_id', $component->id);
            })
            ->values();

        $payload = [
            'student' => [
                'id' => $student->id,
                'name' => $this->resolveStudentDisplayName($student),
                'email' => $student->email ?? null,
            ],
            'batch' => [
                'id' => $batch->id,
                'name' => $batch->name ?? $batch->batch_name ?? $batch->code ?? "Batch #{$batch->id}",
                'program' => $batch->program->name ?? null,
            ],
            'template' => [
                'id' => $template->id,
                'name' => $template->name,
                'passing_score' => (float) $template->passing_score,
            ],
            'scores' => $scores->map(function ($score) {
                return [
                    'component' => $score->component->name ?? '-',
                    'type' => $score->component->type ?? null,
                    'raw_score' => (float) $score->raw_score,
                    'weight' => (float) $score->weight,
                    'weighted_score' => (float) $score->weighted_score,
                    'feedback' => $score->feedback,
                    'status' => $score->status,
                ];
            })->values(),
            'missing_components' => $missingComponents->map(function ($component) {
                return [
                    'id' => $component->id,
                    'name' => $component->name,
                    'type' => $component->type,
                    'is_auto_calculated' => $this->isAutoCalculatedComponent($component),
                ];
            })->values(),
            'final_score' => $totalWeightedScore,
            'is_passed' => $totalWeightedScore >= (float) $template->passing_score,
        ];

        return response()->json($payload);
    }

    private function syncAutoCalculatedScores(
        Batch $batch,
        AssessmentTemplate $template,
        $components,
        $students,
        ?int $assessedBy = null
    ): void {
        $autoComponents = collect($components)
            ->filter(fn ($component) => $this->isAutoCalculatedComponent($component));

        if ($autoComponents->isEmpty() || collect($students)->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($batch, $template, $autoComponents, $students, $assessedBy) {
            foreach ($students as $student) {
                foreach ($autoComponents as $component) {
                    $rawScore = $this->calculateAutoScore(
                        studentId: (int) $student->id,
                        batch: $batch,
                        component: $component
                    );

                    if ($rawScore === null) {
                        continue;
                    }

                    $this->saveAssessmentScore(
                        studentId: (int) $student->id,
                        batch: $batch,
                        template: $template,
                        component: $component,
                        rawScore: $rawScore,
                        feedback: $this->autoFeedbackLabel($component),
                        status: 'reviewed',
                        assessedBy: $assessedBy
                    );
                }
            }
        });
    }

    private function saveAssessmentScore(
        int $studentId,
        Batch $batch,
        AssessmentTemplate $template,
        AssessmentComponent $component,
        float $rawScore,
        ?string $feedback = null,
        string $status = 'reviewed',
        ?int $assessedBy = null
    ): StudentAssessmentScore {
        $rawScore = $this->normalizeScore($rawScore);
        $weight = (float) $component->weight;
        $weightedScore = $this->calculateWeightedScore($rawScore, $weight);

        $score = StudentAssessmentScore::query()->firstOrNew([
            'student_id' => $studentId,
            'batch_id' => $batch->id,
            'assessment_component_id' => $component->id,
        ]);

        $payload = [
            'assessment_template_id' => $template->id,
            'raw_score' => $rawScore,
            'weight' => $weight,
            'weighted_score' => $weightedScore,
            'status' => $status,
            'assessed_at' => now(),
        ];

        if ($assessedBy) {
            $payload['assessed_by'] = $assessedBy;
        }

        if ($feedback !== null) {
            $payload['feedback'] = $feedback;
        }

        $score->fill($payload);
        $score->save();

        return $score->fresh([
            'student',
            'batch',
            'template',
            'component.rubric.criteria',
            'rubricScores.criteria',
        ]);
    }

    private function storeRubricScores(
        StudentAssessmentScore $score,
        AssessmentComponent $component,
        array $rubricScores
    ): float {
        $rubric = $component->rubric;

        if (! $rubric) {
            throw ValidationException::withMessages([
                'rubric_scores' => 'This component does not have an active rubric.',
            ]);
        }

        $rubric->loadMissing('criteria');

        $criteria = $rubric->criteria->keyBy('id');

        $submittedCriteriaIds = collect($rubricScores)
            ->pluck('criteria_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();

        if (Schema::hasTable('student_rubric_scores')) {
            StudentRubricScore::query()
                ->where('student_assessment_score_id', $score->id)
                ->when(! empty($submittedCriteriaIds), function ($query) use ($submittedCriteriaIds) {
                    $query->whereNotIn('assessment_rubric_criteria_id', $submittedCriteriaIds);
                })
                ->delete();
        }

        $rubricRawScore = 0;

        foreach ($rubricScores as $row) {
            $criteriaId = (int) $row['criteria_id'];
            $criterion = $criteria->get($criteriaId);

            if (! $criterion) {
                throw ValidationException::withMessages([
                    'rubric_scores' => 'One of the rubric criteria does not belong to this component rubric.',
                ]);
            }

            $raw = $this->normalizeScore((float) $row['raw_score']);
            $criteriaWeight = (float) $criterion->weight;
            $criteriaWeightedScore = $this->calculateWeightedScore($raw, $criteriaWeight);

            $rubricRawScore += $criteriaWeightedScore;

            $payload = [
                'raw_score' => $raw,
                'note' => $row['note'] ?? null,
            ];

            if (Schema::hasColumn('student_rubric_scores', 'weighted_score')) {
                $payload['weighted_score'] = $criteriaWeightedScore;
            }

            StudentRubricScore::query()->updateOrCreate(
                [
                    'student_assessment_score_id' => $score->id,
                    'assessment_rubric_criteria_id' => $criteriaId,
                ],
                $payload
            );
        }

        return $this->normalizeScore($rubricRawScore);
    }

    private function calculateAutoScore(
        int $studentId,
        Batch $batch,
        AssessmentComponent $component
    ): ?float {
        return match ($component->type) {
            'attendance' => $this->calculateAttendanceScore($studentId, $batch),
            'progress' => $this->calculateVodProgressScore($studentId, $batch),
            'quiz' => $this->calculateQuizScore($studentId, $batch),
            default => null,
        };
    }

    private function calculateAttendanceScore(int $studentId, Batch $batch): ?float
    {
        if (! Schema::hasTable('student_attendances')) {
            return null;
        }

        $totalSessions = StudentAttendance::query()
            ->where('batch_id', $batch->id)
            ->distinct('instructor_schedule_id')
            ->count('instructor_schedule_id');

        if ($totalSessions <= 0) {
            $totalSessions = Schema::hasTable('instructor_schedules') && Schema::hasColumn('instructor_schedules', 'batch_id')
                ? DB::table('instructor_schedules')->where('batch_id', $batch->id)->count()
                : 0;
        }

        if ($totalSessions <= 0) {
            return null;
        }

        $presentCount = StudentAttendance::query()
            ->where('batch_id', $batch->id)
            ->where('student_id', $studentId)
            ->whereIn('status', StudentAttendance::COUNTED_AS_PRESENT)
            ->count();

        return $this->normalizeScore(($presentCount / $totalSessions) * 100);
    }

    private function calculateVodProgressScore(int $studentId, Batch $batch): ?float
    {
        $table = $this->firstExistingTable([
            'student_lesson_progresses',
            'student_lesson_progress',
            'lesson_progresses',
            'student_progresses',
        ]);

        if (! $table) {
            return null;
        }

        $query = DB::table($table)
            ->where('student_id', $studentId);

        if (Schema::hasColumn($table, 'batch_id')) {
            $query->where('batch_id', $batch->id);
        } elseif (Schema::hasColumn($table, 'program_id') && ! empty($batch->program_id)) {
            $query->where('program_id', $batch->program_id);
        }

        $progressColumn = $this->firstExistingColumn($table, [
            'progress_percent',
            'progress_percentage',
            'percentage',
            'percentage_watched',
            'watch_percentage',
            'completion_percentage',
        ]);

        if ($progressColumn) {
            $averageProgress = (float) (clone $query)->avg($progressColumn);

            return $this->normalizeScore($averageProgress);
        }

        if (Schema::hasColumn($table, 'is_completed')) {
            $completed = (clone $query)->where('is_completed', true)->count();
            $total = $this->resolveTotalVodLessons($batch);

            if ($total <= 0) {
                $total = (clone $query)->count();
            }

            if ($total <= 0) {
                return null;
            }

            return $this->normalizeScore(($completed / $total) * 100);
        }

        return null;
    }

    private function calculateQuizScore(int $studentId, Batch $batch): ?float
    {
        $table = $this->firstExistingTable([
            'learning_quiz_attempts'
        ]);

        if (! $table) {
            return null;
        }

        $query = DB::table($table)
            ->where("{$table}.student_id", $studentId);

        if (Schema::hasColumn($table, 'batch_id')) {
            $query->where("{$table}.batch_id", $batch->id);
        } elseif (
            Schema::hasColumn($table, 'batch_learning_quiz_id')
            && Schema::hasTable('batch_learning_quizzes')
            && Schema::hasColumn('batch_learning_quizzes', 'batch_id')
        ) {
            $query
                ->join('batch_learning_quizzes', "{$table}.batch_learning_quiz_id", '=', 'batch_learning_quizzes.id')
                ->where('batch_learning_quizzes.batch_id', $batch->id);
        }

        /*
        |--------------------------------------------------------------------------
        | Only graded / submitted attempts
        |--------------------------------------------------------------------------
        | Percentage baru valid setelah quiz selesai dihitung.
        */
        if (Schema::hasColumn($table, 'status')) {
            $query->where("{$table}.status", 'graded');
        }

        if (Schema::hasColumn($table, 'submitted_at')) {
            $query->whereNotNull("{$table}.submitted_at");
        }

        /*
        |--------------------------------------------------------------------------
        | Main Source: percentage
        |--------------------------------------------------------------------------
        | Dari quiz controller:
        | - score       = raw point
        | - total_score = max point
        | - percentage  = nilai final 0-100
        */
        if (Schema::hasColumn($table, 'percentage')) {
            $averagePercentage = (float) $query->avg("{$table}.percentage");

            return $this->normalizeScore($averagePercentage);
        }

        /*
        |--------------------------------------------------------------------------
        | Fallback: hitung dari score / total_score
        |--------------------------------------------------------------------------
        | Dipakai kalau kolom percentage belum ada.
        */
        if (
            Schema::hasColumn($table, 'score')
            && Schema::hasColumn($table, 'total_score')
        ) {
            $attempts = $query
                ->select([
                    "{$table}.score",
                    "{$table}.total_score",
                ])
                ->get();

            if ($attempts->isEmpty()) {
                return null;
            }

            $percentages = $attempts
                ->map(function ($attempt) {
                    $score = (float) ($attempt->score ?? 0);
                    $totalScore = (float) ($attempt->total_score ?? 0);

                    if ($totalScore <= 0) {
                        return null;
                    }

                    return ($score / $totalScore) * 100;
                })
                ->filter(fn ($value) => $value !== null)
                ->values();

            if ($percentages->isEmpty()) {
                return null;
            }

            return $this->normalizeScore((float) $percentages->avg());
        }

        return null;
    }

    private function resolveTotalVodLessons(Batch $batch): int
    {
        if (! Schema::hasTable('sub_topics')) {
            return 0;
        }

        $query = DB::table('sub_topics');

        if (Schema::hasColumn('sub_topics', 'lesson_type')) {
            $query->where('sub_topics.lesson_type', 'video');
        }

        if (Schema::hasColumn('sub_topics', 'is_active')) {
            $query->where('sub_topics.is_active', true);
        }

        if (
            ! empty($batch->program_id)
            && Schema::hasTable('topics')
            && Schema::hasTable('modules')
            && Schema::hasColumn('sub_topics', 'topic_id')
            && Schema::hasColumn('topics', 'module_id')
            && Schema::hasColumn('modules', 'program_id')
        ) {
            $query
                ->join('topics', 'sub_topics.topic_id', '=', 'topics.id')
                ->join('modules', 'topics.module_id', '=', 'modules.id')
                ->where('modules.program_id', $batch->program_id);
        }

        return (int) $query->count();
    }

    private function isAutoCalculatedComponent(AssessmentComponent $component): bool
    {
        if ((bool) $component->is_auto_calculated) {
            return true;
        }

        return in_array($component->type, [
            'attendance',
            'progress',
            'quiz',
        ], true);
    }

    private function autoSourceLabel(AssessmentComponent $component): string
    {
        return match ($component->type) {
            'attendance' => 'Student attendance records',
            'progress' => 'VOD / learning progress',
            'quiz' => 'Learning quiz attempts',
            default => 'System source data',
        };
    }

    private function autoFeedbackLabel(AssessmentComponent $component): string
    {
        return 'Auto calculated from ' . $this->autoSourceLabel($component) . '.';
    }

    private function calculateWeightedScore(float $rawScore, float $weight): float
    {
        return round(($rawScore * $weight) / 100, 2);
    }

    private function normalizeScore(float $score): float
    {
        return round(max(0, min(100, $score)), 2);
    }

    private function resolveTemplate(Batch $batch): ?AssessmentTemplate
    {
        if ($batch->relationLoaded('assessmentTemplate') && $batch->assessmentTemplate) {
            return $batch->assessmentTemplate;
        }

        if (! empty($batch->assessment_template_id)) {
            return AssessmentTemplate::query()
                ->with(['components.rubric.criteria'])
                ->find($batch->assessment_template_id);
        }

        if (! empty($batch->program_id)) {
            return AssessmentTemplate::query()
                ->with(['components.rubric.criteria'])
                ->where('program_id', $batch->program_id)
                ->where('is_active', true)
                ->latest('id')
                ->first();
        }

        return null;
    }

    private function getStudentsByBatch(Batch $batch)
    {
        $studentIds = [];

        if (Schema::hasTable('student_enrollments')) {
            $query = DB::table('student_enrollments')
                ->where('batch_id', $batch->id);

            if (Schema::hasColumn('student_enrollments', 'status')) {
                $query->whereIn('status', [
                    'active',
                    'ongoing',
                    'enrolled',
                    'approved',
                    'paid',
                ]);
            }

            $studentIds = $query
                ->pluck('student_id')
                ->filter()
                ->unique()
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();
        }

        if (empty($studentIds) && Schema::hasColumn('students', 'batch_id')) {
            $studentIds = Student::query()
                ->where('batch_id', $batch->id)
                ->pluck('id')
                ->filter()
                ->unique()
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();
        }

        return Student::query()
            ->whereIn('id', $studentIds)
            ->get()
            ->sortBy(fn ($student) => $this->resolveStudentDisplayName($student))
            ->values();
    }

    private function resolveStudentDisplayName(Student $student): string
    {
        foreach ([
            'name',
            'full_name',
            'student_name',
            'email',
            'phone',
        ] as $column) {
            if (isset($student->{$column}) && filled($student->{$column})) {
                return (string) $student->{$column};
            }
        }

        $fullName = trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''));

        if ($fullName !== '') {
            return $fullName;
        }

        return "Student #{$student->id}";
    }

    private function firstExistingTable(array $tables): ?string
    {
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                return $table;
            }
        }

        return null;
    }

    private function firstExistingColumn(string $table, array $columns): ?string
    {
        foreach ($columns as $column) {
            if (Schema::hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }
}