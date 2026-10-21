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
                | Attendance, VOD/progress, quiz, dan Coding Practice/Assignment
                | disync ke student_assessment_scores sebagai snapshot assessment.
                |
                | Mini Project dan Professional Attitude sengaja tetap manual.
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
            | Attendance, progress/VOD, quiz, dan Coding Practice/Assignment dihitung
            | dari source data. Raw score dari request diabaikan.
            |
            | Mini Project dan Professional Attitude dipaksa tetap manual lewat
            | isManualInputComponent().
            */
            if ($this->isAutoCalculatedComponent($component)) {
                $autoScore = $this->calculateAutoScore(
                    studentId: (int) $validated['student_id'],
                    batch: $batch,
                    component: $component
                );

                if ($autoScore !== null) {
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
                | Quiz Manual Fallback
                |--------------------------------------------------------------------------
                | Quiz tetap auto-first. Kalau source quiz/attempt belum tersedia,
                | instructor boleh isi manual supaya report card tidak blocked.
                |
                | Attendance, progress/VOD, dan assignment submission tetap tidak fallback
                | manual karena source-nya harus berasal dari sistem.
                */
                if ($this->isQuizComponent($component)) {
                    $manualRawScore = $validated['raw_score'] ?? null;

                    if ($manualRawScore === null) {
                        throw ValidationException::withMessages([
                            'raw_score' => 'Nilai quiz belum tersedia dari sistem. Silakan isi raw score manual.',
                        ]);
                    }

                    return $this->saveAssessmentScore(
                        studentId: (int) $validated['student_id'],
                        batch: $batch,
                        template: $template,
                        component: $component,
                        rawScore: (float) $manualRawScore,
                        feedback: $validated['feedback'] ?? 'Manual quiz score because quiz source data is not available yet.',
                        status: $validated['status'] ?? 'reviewed',
                        assessedBy: $request->user()?->id
                    );
                }

                throw ValidationException::withMessages([
                    'raw_score' => 'Auto score belum bisa dihitung karena source data belum tersedia.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Manual / Rubric Components
            |--------------------------------------------------------------------------
            | Mini Project, Professional Attitude, dan component manual lain tetap bisa
            | diinput langsung dari modal.
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

                $isAutoComponent = $this->isAutoCalculatedComponent($component);
                $usedManualQuizFallback = false;

                if ($isAutoComponent) {
                    $rawScore = $this->calculateAutoScore(
                        studentId: (int) $row['student_id'],
                        batch: $batch,
                        component: $component
                    );

                    if ($rawScore === null && $this->isQuizComponent($component)) {
                        $rawScore = $row['raw_score'] ?? null;
                        $usedManualQuizFallback = $rawScore !== null;
                    }

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
                    feedback: $row['feedback'] ?? (
                        $usedManualQuizFallback
                            ? 'Manual quiz score because quiz source data is not available yet.'
                            : ($isAutoComponent ? $this->autoFeedbackLabel($component) : null)
                    ),
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
        | Preview tetap baca student_assessment_scores. Jadi sebelum preview dibuka,
        | auto component termasuk Coding Practice harus disync dulu.
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
                    'weight' => (float) $component->weight,
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
        if ($this->isAssignmentSubmissionComponent($component)) {
            return $this->calculateAssignmentSubmissionScore($studentId, $batch);
        }

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

        $progressColumn = $this->firstExistingColumn($table, [
            'progress_percent',
            'progress_percentage',
            'percentage',
            'percentage_watched',
            'watch_percentage',
            'completion_percentage',
        ]);

        $lessonColumn = $this->firstExistingColumn($table, [
            'sub_topic_id',
            'subtopic_id',
            'lesson_id',
            'learning_lesson_id',
            'topic_lesson_id',
            'video_id',
        ]);

        $lessonIds = $this->resolveVodLessonIds($batch);

        $batchName = strtoupper(trim((string) (
            $batch->name
            ?? $batch->batch_name
            ?? $batch->code
            ?? ''
        )));
        $isIntroOrCoreBatch = preg_match('/(^|[^A-Z0-9])INTRO([^A-Z0-9]|$)/', $batchName)
            || preg_match('/(^|[^A-Z0-9])CORE([^A-Z0-9]|$)/', $batchName);

        // Untuk batch Intro/Core, lesson yang tidak berhasil di-resolve tidak
        // boleh memicu fallback AVG seluruh program karena bisa mencampur stage.
        if ($isIntroOrCoreBatch && empty($lessonIds)) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Match Student Progress Monitoring Calculation
        |--------------------------------------------------------------------------
        | Jangan pakai AVG(progress rows) karena itu cuma menghitung lesson yang
        | pernah dibuka. Untuk assessment/report card, semua VOD lesson yang
        | assigned ke batch/program harus jadi pembagi.
        |
        | Contoh:
        | - Total VOD lesson = 6
        | - Student baru nonton 1 lesson sampai 95%
        | - Score yang benar = 95 / 6 = 15.83
        */
        if (! empty($lessonIds) && $lessonColumn) {
            $query = DB::table($table)
                ->where("{$table}.student_id", $studentId)
                ->whereIn("{$table}.{$lessonColumn}", $lessonIds);

            $this->applyProgressBatchScope($query, $table, $batch);

            if ($progressColumn) {
                $progressRows = (clone $query)
                    ->select([
                        "{$table}.{$lessonColumn}",
                        DB::raw("MAX({$table}.{$progressColumn}) as progress_value"),
                    ])
                    ->groupBy("{$table}.{$lessonColumn}")
                    ->pluck('progress_value', $lessonColumn);

                $totalProgressPercentage = 0;

                foreach ($lessonIds as $lessonId) {
                    $totalProgressPercentage += $this->normalizeScore(
                        (float) ($progressRows[$lessonId] ?? 0)
                    );
                }

                return $this->normalizeScore($totalProgressPercentage / count($lessonIds));
            }

            if (Schema::hasColumn($table, 'is_completed')) {
                $completedLessonIds = (clone $query)
                    ->where("{$table}.is_completed", true)
                    ->pluck("{$table}.{$lessonColumn}")
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                return $this->normalizeScore(
                    (count($completedLessonIds) / count($lessonIds)) * 100
                );
            }

            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Fallback
        |--------------------------------------------------------------------------
        | Dipakai hanya kalau struktur lesson tidak bisa di-resolve. Ini bukan jalur
        | utama, tapi tetap dipertahankan supaya tidak mematikan data lama.
        */
        $query = DB::table($table)
            ->where("{$table}.student_id", $studentId);

        $this->applyProgressBatchScope($query, $table, $batch);

        if ($progressColumn) {
            $averageProgress = (float) (clone $query)->avg("{$table}.{$progressColumn}");

            return $this->normalizeScore($averageProgress);
        }

        if (Schema::hasColumn($table, 'is_completed')) {
            $completed = (clone $query)->where("{$table}.is_completed", true)->count();
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

    private function applyProgressBatchScope($query, string $table, Batch $batch): void
    {
        if (Schema::hasColumn($table, 'batch_id')) {
            $query->where("{$table}.batch_id", $batch->id);

            return;
        }

        if (Schema::hasColumn($table, 'program_id') && ! empty($batch->program_id)) {
            $query->where("{$table}.program_id", $batch->program_id);
        }
    }

    private function resolveVodLessonIds(Batch $batch): array
    {
        if (! Schema::hasTable('sub_topics')) {
            return [];
        }

        $query = DB::table('sub_topics')
            ->select('sub_topics.id');

        /*
        |--------------------------------------------------------------------------
        | VOD only
        |--------------------------------------------------------------------------
        | Kalau lesson_type tersedia, VOD progress hanya menghitung video.
        | Kalau kolomnya tidak ada, fallback ke semua sub_topics aktif.
        */
        if (Schema::hasColumn('sub_topics', 'lesson_type')) {
            $query->where(function ($query) {
                $query
                    ->where('sub_topics.lesson_type', 'video')
                    ->orWhere('sub_topics.lesson_type', 'vod')
                    ->orWhere('sub_topics.lesson_type', 'recorded_video');
            });
        }

        if (Schema::hasColumn('sub_topics', 'is_active')) {
            $query->where('sub_topics.is_active', true);
        }

        if (Schema::hasColumn('sub_topics', 'status')) {
            $query->whereIn('sub_topics.status', [
                'active',
                'published',
                'open',
            ]);
        }

        $this->applyVodLessonProgramScope($query, $batch);

        return $query
            ->pluck('sub_topics.id')
            ->filter()
            ->unique()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    private function applyVodLessonProgramScope($query, Batch $batch): void
    {
        $batchName = strtoupper(trim((string) (
            $batch->name
            ?? $batch->batch_name
            ?? $batch->code
            ?? ''
        )));

        // Nama batch Intro/Core adalah sumber penentu stage yang sudah dipakai
        // pada StudentProgressMonitoringController.
        $stageKeyword = null;

        if (preg_match('/(^|[^A-Z0-9])INTRO([^A-Z0-9]|$)/', $batchName)) {
            $stageKeyword = 'intro';
        } elseif (preg_match('/(^|[^A-Z0-9])CORE([^A-Z0-9]|$)/', $batchName)) {
            $stageKeyword = 'core';
        }

        $hasStagePath = Schema::hasTable('topics')
            && Schema::hasTable('modules')
            && Schema::hasTable('program_stages')
            && Schema::hasColumn('sub_topics', 'topic_id')
            && Schema::hasColumn('topics', 'module_id')
            && Schema::hasColumn('modules', 'program_stage_id')
            && Schema::hasColumn('program_stages', 'program_id');

        /*
        |--------------------------------------------------------------------------
        | Strict stage scope for Intro/Core batches
        |--------------------------------------------------------------------------
        | Jangan fallback ke seluruh program jika batch jelas-jelas Intro/Core,
        | tetapi struktur stage tidak bisa di-resolve. Lebih aman tidak mengambil
        | lesson daripada mencampur materi antar-stage.
        */
        if ($stageKeyword !== null) {
            if (! $hasStagePath || empty($batch->program_id)) {
                $query->whereRaw('1 = 0');

                return;
            }

            if (
                ! Schema::hasColumn('program_stages', 'slug')
                && ! Schema::hasColumn('program_stages', 'name')
            ) {
                $query->whereRaw('1 = 0');

                return;
            }

            $stageQuery = DB::table('program_stages')
                ->where('program_stages.program_id', $batch->program_id)
                ->where(function ($stageQuery) use ($stageKeyword) {
                    if (Schema::hasColumn('program_stages', 'slug')) {
                        $stageQuery->whereRaw('LOWER(program_stages.slug) = ?', [$stageKeyword]);
                    }

                    if (Schema::hasColumn('program_stages', 'name')) {
                        $method = Schema::hasColumn('program_stages', 'slug') ? 'orWhereRaw' : 'whereRaw';
                        $stageQuery->{$method}('LOWER(program_stages.name) = ?', [$stageKeyword]);
                    }
                });

            if (Schema::hasColumn('program_stages', 'is_active')) {
                $stageQuery->where('program_stages.is_active', true);
            }

            $stageIds = $stageQuery->pluck('program_stages.id')->all();

            if (empty($stageIds)) {
                $query->whereRaw('1 = 0');

                return;
            }

            $query
                ->join('topics', 'sub_topics.topic_id', '=', 'topics.id')
                ->join('modules', 'topics.module_id', '=', 'modules.id')
                ->join('program_stages', 'modules.program_stage_id', '=', 'program_stages.id')
                ->where('program_stages.program_id', $batch->program_id)
                ->whereIn('program_stages.id', $stageIds);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Program scope for programs/batches without Intro/Core stage markers
        |--------------------------------------------------------------------------
        | Contohnya program yang memang tidak memakai stage. Tetap batasi ke
        | program terkait dan jangan mengubah perilaku program non-stage.
        */
        if (empty($batch->program_id)) {
            if (Schema::hasColumn('sub_topics', 'batch_id')) {
                $query->where('sub_topics.batch_id', $batch->id);
            } else {
                $query->whereRaw('1 = 0');
            }

            return;
        }

        if (Schema::hasColumn('sub_topics', 'program_id')) {
            $query->where('sub_topics.program_id', $batch->program_id);

            return;
        }

        if (
            Schema::hasTable('topics')
            && Schema::hasTable('modules')
            && Schema::hasColumn('sub_topics', 'topic_id')
            && Schema::hasColumn('topics', 'module_id')
            && Schema::hasColumn('modules', 'program_id')
        ) {
            $query
                ->join('topics', 'sub_topics.topic_id', '=', 'topics.id')
                ->join('modules', 'topics.module_id', '=', 'modules.id')
                ->where('modules.program_id', $batch->program_id);

            return;
        }

        if (
            Schema::hasTable('topics')
            && Schema::hasTable('modules')
            && Schema::hasTable('program_stages')
            && Schema::hasColumn('sub_topics', 'topic_id')
            && Schema::hasColumn('topics', 'module_id')
            && Schema::hasColumn('modules', 'program_stage_id')
            && Schema::hasColumn('program_stages', 'program_id')
        ) {
            $query
                ->join('topics', 'sub_topics.topic_id', '=', 'topics.id')
                ->join('modules', 'topics.module_id', '=', 'modules.id')
                ->join('program_stages', 'modules.program_stage_id', '=', 'program_stages.id')
                ->where('program_stages.program_id', $batch->program_id);

            return;
        }

        if (
            Schema::hasTable('topics')
            && Schema::hasColumn('sub_topics', 'topic_id')
            && Schema::hasColumn('topics', 'program_id')
        ) {
            $query
                ->join('topics', 'sub_topics.topic_id', '=', 'topics.id')
                ->where('topics.program_id', $batch->program_id);

            return;
        }

        if (Schema::hasColumn('sub_topics', 'batch_id')) {
            $query->where('sub_topics.batch_id', $batch->id);

            return;
        }

        // Fail closed: jangan menghitung lesson yang tidak bisa di-scope.
        $query->whereRaw('1 = 0');
    }

    private function calculateQuizScore(int $studentId, Batch $batch): ?float
    {
        $table = $this->firstExistingTable([
            'learning_quiz_attempts',
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
        | Jangan cast null ke float.
        | Kalau avg null, artinya belum ada quiz score valid,
        | jadi biarkan return null / fallback ke score-total_score.
        */
        if (Schema::hasColumn($table, 'percentage')) {
            $averagePercentage = (clone $query)->avg("{$table}.percentage");

            if ($averagePercentage !== null) {
                return $this->normalizeScore((float) $averagePercentage);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Fallback: score / total_score
        |--------------------------------------------------------------------------
        */
        if (
            Schema::hasColumn($table, 'score')
            && Schema::hasColumn($table, 'total_score')
        ) {
            $attempts = (clone $query)
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

    private function calculateQuizScore_(int $studentId, Batch $batch): ?float
    {
        $table = $this->firstExistingTable([
            'learning_quiz_attempts',
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

        if (Schema::hasColumn($table, 'status')) {
            $query->where("{$table}.status", 'graded');
        }

        if (Schema::hasColumn($table, 'submitted_at')) {
            $query->whereNotNull("{$table}.submitted_at");
        }

        if (Schema::hasColumn($table, 'percentage')) {
            $averagePercentage = (float) $query->avg("{$table}.percentage");

            return $this->normalizeScore($averagePercentage);
        }

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

   private function calculateAssignmentSubmissionScore(int $studentId, Batch $batch): ?float
    {
        $submissionTable = 'assignment_submissions';
        $batchAssignmentTable = 'batch_assignments';

        if (! Schema::hasTable($submissionTable)) {
            return null;
        }

        if (! Schema::hasColumn($submissionTable, 'score')) {
            return null;
        }

        if (! Schema::hasTable($batchAssignmentTable)) {
            return null;
        }

        if (! Schema::hasColumn($batchAssignmentTable, 'id')) {
            return null;
        }

        if (! Schema::hasColumn($batchAssignmentTable, 'batch_id')) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Resolve All Assigned Assignments for This Batch
        |--------------------------------------------------------------------------
        | Ini jadi denominator utama. Assignment yang tidak dikerjakan / belum punya
        | score akan dihitung sebagai 0 supaya Coding Practice tidak terlalu tinggi.
        */
        $batchAssignmentsQuery = DB::table($batchAssignmentTable)
            ->where("{$batchAssignmentTable}.batch_id", $batch->id);

        if (Schema::hasColumn($batchAssignmentTable, 'is_active')) {
            $batchAssignmentsQuery->where("{$batchAssignmentTable}.is_active", true);
        }

        if (Schema::hasColumn($batchAssignmentTable, 'status')) {
            $batchAssignmentsQuery->whereNotIn("{$batchAssignmentTable}.status", [
                'draft',
                'archived',
                'cancelled',
                'inactive',
            ]);
        }

        $batchAssignmentIds = $batchAssignmentsQuery
            ->pluck("{$batchAssignmentTable}.id")
            ->filter()
            ->unique()
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($batchAssignmentIds->isEmpty()) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Preferred Scope: batch_assignment_id
        |--------------------------------------------------------------------------
        | Ambil score per assigned assignment. Kalau ada multiple submission untuk
        | assignment yang sama, pakai score tertinggi supaya tidak double count.
        */
        if (Schema::hasColumn($submissionTable, 'batch_assignment_id')) {
            $submissionScoresQuery = DB::table($submissionTable)
                ->select([
                    "{$submissionTable}.batch_assignment_id",
                    DB::raw("MAX({$submissionTable}.score) as score"),
                ])
                ->where("{$submissionTable}.student_id", $studentId)
                ->whereIn("{$submissionTable}.batch_assignment_id", $batchAssignmentIds->all())
                ->whereNotNull("{$submissionTable}.score")
                ->whereNotNull("{$submissionTable}.batch_assignment_id");

            if (Schema::hasColumn($submissionTable, 'status')) {
                $submissionScoresQuery->whereIn("{$submissionTable}.status", [
                    'submitted',
                    'late',
                    'reviewed',
                    'returned',
                ]);
            }

            if (Schema::hasColumn($submissionTable, 'batch_id')) {
                $submissionScoresQuery->where("{$submissionTable}.batch_id", $batch->id);
            }

            $submissionScores = $submissionScoresQuery
                ->groupBy("{$submissionTable}.batch_assignment_id")
                ->pluck('score', 'batch_assignment_id');

            $totalScore = 0;

            foreach ($batchAssignmentIds as $batchAssignmentId) {
                $score = $submissionScores->get($batchAssignmentId);

                $totalScore += $score !== null
                    ? $this->normalizeScore((float) $score)
                    : 0;
            }

            return $this->normalizeScore($totalScore / $batchAssignmentIds->count());
        }

        /*
        |--------------------------------------------------------------------------
        | Fallback Scope: assignment_id
        |--------------------------------------------------------------------------
        | Dipakai hanya kalau schema lama tidak punya batch_assignment_id.
        */
        if (
            Schema::hasColumn($submissionTable, 'assignment_id')
            && Schema::hasColumn($batchAssignmentTable, 'assignment_id')
        ) {
            $assignmentIds = DB::table($batchAssignmentTable)
                ->where("{$batchAssignmentTable}.batch_id", $batch->id)
                ->pluck("{$batchAssignmentTable}.assignment_id")
                ->filter()
                ->unique()
                ->map(fn ($id) => (int) $id)
                ->values();

            if ($assignmentIds->isEmpty()) {
                return null;
            }

            $submissionScoresQuery = DB::table($submissionTable)
                ->select([
                    "{$submissionTable}.assignment_id",
                    DB::raw("MAX({$submissionTable}.score) as score"),
                ])
                ->where("{$submissionTable}.student_id", $studentId)
                ->whereIn("{$submissionTable}.assignment_id", $assignmentIds->all())
                ->whereNotNull("{$submissionTable}.score")
                ->whereNotNull("{$submissionTable}.assignment_id");

            if (Schema::hasColumn($submissionTable, 'status')) {
                $submissionScoresQuery->whereIn("{$submissionTable}.status", [
                    'submitted',
                    'late',
                    'reviewed',
                    'returned',
                ]);
            }

            if (Schema::hasColumn($submissionTable, 'batch_id')) {
                $submissionScoresQuery->where("{$submissionTable}.batch_id", $batch->id);
            }

            $submissionScores = $submissionScoresQuery
                ->groupBy("{$submissionTable}.assignment_id")
                ->pluck('score', 'assignment_id');

            $totalScore = 0;

            foreach ($assignmentIds as $assignmentId) {
                $score = $submissionScores->get($assignmentId);

                $totalScore += $score !== null
                    ? $this->normalizeScore((float) $score)
                    : 0;
            }

            return $this->normalizeScore($totalScore / $assignmentIds->count());
        }

        return null;
    }

    private function resolveTotalVodLessons(Batch $batch): int
    {
        $lessonIds = $this->resolveVodLessonIds($batch);

        return count($lessonIds);
    }

    private function isAutoCalculatedComponent(AssessmentComponent $component): bool
    {
        /*
        |--------------------------------------------------------------------------
        | Manual Override
        |--------------------------------------------------------------------------
        | Mini Project dan Professional Attitude harus tetap bisa diinput manual,
        | walaupun type/code/is_auto_calculated di data lama kurang rapi.
        */
        if ($this->isManualInputComponent($component)) {
            return false;
        }

        if ($this->isAssignmentSubmissionComponent($component)) {
            return true;
        }

        if ((bool) $component->is_auto_calculated) {
            return true;
        }

        return in_array($component->type, [
            'attendance',
            'progress',
            'quiz',
        ], true);
    }

    private function isQuizComponent(AssessmentComponent $component): bool
    {
        $text = $this->normalizedComponentText($component);

        return str_contains($text, 'quiz')
            || $this->normalizedComponentKey($component->type) === 'quiz'
            || $this->normalizedComponentKey($component->code) === 'quiz';
    }

    private function isAssignmentSubmissionComponent(AssessmentComponent $component): bool
    {
        if ($this->isManualInputComponent($component)) {
            return false;
        }

        $text = $this->normalizedComponentText($component);

        return str_contains($text, 'coding practice')
            || str_contains($text, 'coding assignment')
            || str_contains($text, 'assignment submission')
            || str_contains($text, 'assignment submissions')
            || $this->normalizedComponentKey($component->type) === 'assignment'
            || $this->normalizedComponentKey($component->code) === 'assignment'
            || $this->normalizedComponentKey($component->type) === 'coding_practice'
            || $this->normalizedComponentKey($component->code) === 'coding_practice';
    }

    private function isManualInputComponent(AssessmentComponent $component): bool
    {
        $text = $this->normalizedComponentText($component);

        return str_contains($text, 'mini project')
            || str_contains($text, 'professional attitude')
            || str_contains($text, 'proffesional attitude')
            || str_contains($text, 'professionalism')
            || str_contains($text, 'attitude');
    }

    private function autoSourceLabel(AssessmentComponent $component): string
    {
        if ($this->isAssignmentSubmissionComponent($component)) {
            return 'Assignment submissions';
        }

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

    private function normalizedComponentText(AssessmentComponent $component): string
    {
        $text = implode(' ', [
            (string) ($component->type ?? ''),
            (string) ($component->code ?? ''),
            (string) ($component->name ?? ''),
        ]);

        $text = strtolower($text);

        return trim(preg_replace('/[^a-z0-9]+/', ' ', $text) ?? '');
    }

    private function normalizedComponentKey($value): string
    {
        $value = strtolower((string) ($value ?? ''));

        return trim(preg_replace('/[^a-z0-9]+/', '_', $value) ?? '', '_');
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