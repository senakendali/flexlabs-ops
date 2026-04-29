<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\AssessmentComponent;
use App\Models\AssessmentTemplate;
use App\Models\Batch;
use App\Models\Student;
use App\Models\StudentAssessmentScore;
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
                $template->loadMissing('components.rubric.criteria');
                $components = $template->components;
            }

            $students = $this->getStudentsByBatch($selectedBatch);

            $scores = StudentAssessmentScore::query()
                ->with(['component', 'rubricScores.criteria'])
                ->where('batch_id', $selectedBatch->id)
                ->when($template, function ($q) use ($template) {
                    $q->where('assessment_template_id', $template->id);
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

    private function resolveStudentDisplayName(Student $student): string
    {
        if (! empty($student->name)) {
            return $student->name;
        }

        if (! empty($student->full_name)) {
            return $student->full_name;
        }

        $fullName = trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''));

        if ($fullName !== '') {
            return $fullName;
        }

        return $student->email ?? "Student #{$student->id}";
    }

    public function verify(string $token)
    {
        $certificate = $this->certificateService->verifyByToken($token);

        abort_if(! $certificate, 404);

        return view('public.certificates.verify', compact('certificate'));
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

            $score = StudentAssessmentScore::query()->updateOrCreate(
                [
                    'student_id' => $validated['student_id'],
                    'batch_id' => $validated['batch_id'],
                    'assessment_component_id' => $component->id,
                ],
                [
                    'assessment_template_id' => $template->id,
                    'raw_score' => $validated['raw_score'] ?? 0,
                    'weight' => $component->weight,
                    'feedback' => $validated['feedback'] ?? null,
                    'status' => $validated['status'] ?? 'reviewed',
                    'assessed_by' => $request->user()?->id,
                    'assessed_at' => now(),
                ]
            );

            if (! empty($validated['rubric_scores'])) {
                $rubricRawScore = $this->storeRubricScores($score, $component, $validated['rubric_scores']);

                $score->raw_score = $rubricRawScore;
                $score->weight = $component->weight;
                $score->save();
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
                'message' => 'Assessment score saved successfully.',
                'data' => $score,
            ]);
        }

        return back()->with('success', 'Assessment score saved successfully.');
    }

    public function bulkStore(Request $request)
    {
        $validated = $request->validate([
            'batch_id' => ['required', 'exists:batches,id'],
            'scores' => ['required', 'array'],
            'scores.*.student_id' => ['required', 'exists:students,id'],
            'scores.*.assessment_component_id' => ['required', 'exists:assessment_components,id'],
            'scores.*.raw_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'scores.*.feedback' => ['nullable', 'string'],
            'scores.*.status' => ['nullable', 'in:draft,submitted,reviewed,approved'],
        ]);

        DB::transaction(function () use ($validated, $request) {
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

            foreach ($validated['scores'] as $row) {
                $component = $components->get((int) $row['assessment_component_id']);

                if (! $component) {
                    continue;
                }

                StudentAssessmentScore::query()->updateOrCreate(
                    [
                        'student_id' => $row['student_id'],
                        'batch_id' => $batch->id,
                        'assessment_component_id' => $component->id,
                    ],
                    [
                        'assessment_template_id' => $template->id,
                        'raw_score' => $row['raw_score'],
                        'weight' => $component->weight,
                        'feedback' => $row['feedback'] ?? null,
                        'status' => $row['status'] ?? 'reviewed',
                        'assessed_by' => $request->user()?->id,
                        'assessed_at' => now(),
                    ]
                );
            }
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Assessment scores saved successfully.',
            ]);
        }

        return back()->with('success', 'Assessment scores saved successfully.');
    }

    public function preview(Request $request)
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'batch_id' => ['required', 'exists:batches,id'],
        ]);

        $student = Student::query()->findOrFail($validated['student_id']);
        $batch = Batch::query()->findOrFail($validated['batch_id']);

        $result = $this->calculator->calculate($student, $batch);

        return response()->json([
            'data' => $result,
        ]);
    }

    private function storeRubricScores(
        StudentAssessmentScore $score,
        AssessmentComponent $component,
        array $rubricScores
    ): float {
        $criteria = $component->rubric?->criteria?->keyBy('id') ?? collect();

        $total = 0;

        foreach ($rubricScores as $row) {
            $criterion = $criteria->get((int) $row['criteria_id']);

            if (! $criterion) {
                continue;
            }

            $rubricScore = StudentRubricScore::query()->updateOrCreate(
                [
                    'student_assessment_score_id' => $score->id,
                    'assessment_rubric_criteria_id' => $criterion->id,
                ],
                [
                    'raw_score' => $row['raw_score'],
                    'weight' => $criterion->weight,
                    'note' => $row['note'] ?? null,
                ]
            );

            $total += (float) $rubricScore->weighted_score;
        }

        return round($total, 2);
    }

    private function resolveTemplate(Batch $batch): ?AssessmentTemplate
    {
        if (! empty($batch->assessment_template_id)) {
            return AssessmentTemplate::query()
                ->with('components')
                ->find($batch->assessment_template_id);
        }

        if (! empty($batch->program_id)) {
            return AssessmentTemplate::query()
                ->where('program_id', $batch->program_id)
                ->where('is_active', true)
                ->latest('id')
                ->first();
        }

        return null;
    }

    private function getStudentsByBatch(Batch $batch)
    {
        if (Schema::hasTable('student_enrollments')) {
            return Student::query()
                ->whereIn('id', function ($query) use ($batch) {
                    $query->select('student_id')
                        ->from('student_enrollments')
                        ->where('batch_id', $batch->id);
                })
                ->orderBy('id')
                ->get();
        }

        return collect();
    }
}