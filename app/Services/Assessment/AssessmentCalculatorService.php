<?php

namespace App\Services\Assessment;

use App\Models\AssessmentTemplate;
use App\Models\Batch;
use App\Models\Student;
use App\Models\StudentAssessmentScore;
use Illuminate\Support\Collection;

class AssessmentCalculatorService
{
    public function calculate(Student $student, Batch $batch): array
    {
        $template = $this->resolveTemplate($batch);

        if (! $template) {
            return $this->emptyResult(
                student: $student,
                batch: $batch,
                reason: 'Assessment template is not assigned to this batch.'
            );
        }

        $template->loadMissing([
            'program',
            'components.rubric.criteria',
            'components.rubric.levels',
        ]);

        $scores = StudentAssessmentScore::query()
            ->with([
                'component',
                'rubricScores.criteria',
            ])
            ->where('student_id', $student->id)
            ->where('batch_id', $batch->id)
            ->where('assessment_template_id', $template->id)
            ->get()
            ->keyBy('assessment_component_id');

        $componentRows = $this->buildComponentRows($template, $scores);

        $finalScore = round($componentRows->sum('weighted_score'), 2);

        $attendancePercent = $this->getComponentRawScore($componentRows, 'attendance');
        $progressPercent = $this->getComponentRawScore($componentRows, 'progress');

        $grade = $this->resolveGrade($finalScore);

        $missingRequiredComponents = $componentRows
            ->filter(fn ($row) => $row['is_required'] && ! $row['has_score'])
            ->values()
            ->map(fn ($row) => [
                'component_id' => $row['component_id'],
                'name' => $row['name'],
                'code' => $row['code'],
                'type' => $row['type'],
            ])
            ->all();

        $hasMissingRequiredScores = count($missingRequiredComponents) > 0;

        $finalProjectSubmitted = $this->resolveFinalProjectSubmitted($componentRows);

        $passesScore = $finalScore >= (float) $template->passing_score;
        $passesAttendance = $attendancePercent >= (float) $template->min_attendance_percent;
        $passesProgress = $progressPercent >= (float) $template->min_progress_percent;
        $passesFinalProject = ! $template->requires_final_project || $finalProjectSubmitted;

        $isPassed = $passesScore
            && $passesAttendance
            && $passesProgress
            && $passesFinalProject
            && ! $hasMissingRequiredScores;

        $status = $isPassed ? 'passed' : 'not_passed';

        $isCertificateEligible = $isPassed;

        $ruleSnapshot = [
            'passing_score' => (float) $template->passing_score,
            'min_attendance_percent' => (float) $template->min_attendance_percent,
            'min_progress_percent' => (float) $template->min_progress_percent,
            'requires_final_project' => (bool) $template->requires_final_project,
            'passes_score' => $passesScore,
            'passes_attendance' => $passesAttendance,
            'passes_progress' => $passesProgress,
            'passes_final_project' => $passesFinalProject,
            'has_missing_required_scores' => $hasMissingRequiredScores,
            'missing_required_components' => $missingRequiredComponents,
        ];

        return [
            'student_id' => $student->id,
            'batch_id' => $batch->id,
            'program_id' => $batch->program_id,
            'assessment_template_id' => $template->id,

            'template' => [
                'id' => $template->id,
                'name' => $template->name,
                'code' => $template->code,
                'program_id' => $template->program_id,
            ],

            'attendance_percent' => $attendancePercent,
            'progress_percent' => $progressPercent,
            'final_score' => $finalScore,
            'grade' => $grade,
            'status' => $status,
            'is_passed' => $isPassed,
            'is_certificate_eligible' => $isCertificateEligible,

            'summary' => [
                'total_components' => $componentRows->count(),
                'completed_components' => $componentRows->where('has_score', true)->count(),
                'missing_required_components_count' => count($missingRequiredComponents),
                'total_weight' => round($componentRows->sum('weight'), 2),
                'scored_weight' => round($componentRows->where('has_score', true)->sum('weight'), 2),
            ],

            'components' => $componentRows->values()->all(),

            'score_snapshot' => [
                'student' => [
                    'id' => $student->id,
                    'name' => $this->resolveStudentName($student),
                ],
                'batch' => [
                    'id' => $batch->id,
                    'name' => $batch->name ?? $batch->title ?? null,
                    'program_id' => $batch->program_id,
                ],
                'template' => [
                    'id' => $template->id,
                    'name' => $template->name,
                    'code' => $template->code,
                    'passing_score' => (float) $template->passing_score,
                    'min_attendance_percent' => (float) $template->min_attendance_percent,
                    'min_progress_percent' => (float) $template->min_progress_percent,
                    'requires_final_project' => (bool) $template->requires_final_project,
                ],
                'components' => $componentRows->values()->all(),
                'final_score' => $finalScore,
                'grade' => $grade,
                'status' => $status,
            ],

            'rubric_snapshot' => $this->buildRubricSnapshot($componentRows),

            'rule_snapshot' => $ruleSnapshot,
        ];
    }

    public function calculateByIds(int $studentId, int $batchId): array
    {
        $student = Student::query()->findOrFail($studentId);
        $batch = Batch::query()->findOrFail($batchId);

        return $this->calculate($student, $batch);
    }

    public function resolveGrade(float $score): string
    {
        return match (true) {
            $score >= 90 => 'A',
            $score >= 80 => 'B',
            $score >= 70 => 'C',
            $score >= 60 => 'D',
            default => 'E',
        };
    }

    public function resolveStatusLabel(string $status): string
    {
        return match ($status) {
            'passed' => 'Passed',
            'not_passed' => 'Not Passed',
            'published' => 'Published',
            'draft' => 'Draft',
            'cancelled' => 'Cancelled',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
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

    private function buildComponentRows(AssessmentTemplate $template, Collection $scores): Collection
    {
        return $template->components
            ->sortBy('sort_order')
            ->map(function ($component) use ($scores) {
                /** @var StudentAssessmentScore|null $score */
                $score = $scores->get($component->id);

                $rawScore = $score ? (float) $score->raw_score : 0;
                $weight = (float) $component->weight;
                $weightedScore = round(($rawScore * $weight) / 100, 2);

                $rubricRows = collect();

                if ($score) {
                    $rubricRows = $score->rubricScores
                        ->map(function ($rubricScore) {
                            return [
                                'criteria_id' => $rubricScore->assessment_rubric_criteria_id,
                                'criteria_name' => $rubricScore->criteria?->name,
                                'criteria_code' => $rubricScore->criteria?->code,
                                'raw_score' => (float) $rubricScore->raw_score,
                                'weight' => (float) $rubricScore->weight,
                                'weighted_score' => (float) $rubricScore->weighted_score,
                                'note' => $rubricScore->note,
                            ];
                        })
                        ->values();
                }

                return [
                    'component_id' => $component->id,
                    'name' => $component->name,
                    'code' => $component->code,
                    'type' => $component->type,
                    'description' => $component->description,
                    'weight' => $weight,
                    'max_score' => (float) $component->max_score,
                    'raw_score' => $rawScore,
                    'weighted_score' => $weightedScore,
                    'is_required' => (bool) $component->is_required,
                    'is_auto_calculated' => (bool) $component->is_auto_calculated,
                    'sort_order' => (int) $component->sort_order,
                    'has_score' => (bool) $score,
                    'score_id' => $score?->id,
                    'score_status' => $score?->status,
                    'feedback' => $score?->feedback,
                    'assessed_at' => $score?->assessed_at?->toDateTimeString(),
                    'rubric_scores' => $rubricRows->all(),
                ];
            })
            ->values();
    }

    private function getComponentRawScore(Collection $componentRows, string $type): float
    {
        $row = $componentRows->firstWhere('type', $type);

        if (! $row) {
            return 0;
        }

        return (float) $row['raw_score'];
    }

    private function resolveFinalProjectSubmitted(Collection $componentRows): bool
    {
        $projectRows = $componentRows
            ->filter(fn ($row) => $row['type'] === 'project');

        if ($projectRows->isEmpty()) {
            return true;
        }

        return $projectRows->contains(function ($row) {
            return $row['has_score'] && (float) $row['raw_score'] > 0;
        });
    }

    private function buildRubricSnapshot(Collection $componentRows): array
    {
        return $componentRows
            ->filter(fn ($row) => ! empty($row['rubric_scores']))
            ->map(fn ($row) => [
                'component_id' => $row['component_id'],
                'component_name' => $row['name'],
                'component_code' => $row['code'],
                'rubric_scores' => $row['rubric_scores'],
            ])
            ->values()
            ->all();
    }

    private function emptyResult(Student $student, Batch $batch, string $reason): array
    {
        return [
            'student_id' => $student->id,
            'batch_id' => $batch->id,
            'program_id' => $batch->program_id ?? null,
            'assessment_template_id' => null,

            'attendance_percent' => 0,
            'progress_percent' => 0,
            'final_score' => 0,
            'grade' => 'E',
            'status' => 'draft',
            'is_passed' => false,
            'is_certificate_eligible' => false,

            'summary' => [
                'total_components' => 0,
                'completed_components' => 0,
                'missing_required_components_count' => 0,
                'total_weight' => 0,
                'scored_weight' => 0,
            ],

            'components' => [],

            'score_snapshot' => [
                'student' => [
                    'id' => $student->id,
                    'name' => $this->resolveStudentName($student),
                ],
                'batch' => [
                    'id' => $batch->id,
                    'name' => $batch->name ?? $batch->title ?? null,
                    'program_id' => $batch->program_id ?? null,
                ],
                'template' => null,
                'components' => [],
                'final_score' => 0,
                'grade' => 'E',
                'status' => 'draft',
            ],

            'rubric_snapshot' => [],

            'rule_snapshot' => [
                'reason' => $reason,
            ],
        ];
    }

    private function resolveStudentName(Student $student): ?string
    {
        if (! empty($student->name)) {
            return $student->name;
        }

        $firstName = $student->first_name ?? null;
        $lastName = $student->last_name ?? null;

        $fullName = trim(($firstName ?? '') . ' ' . ($lastName ?? ''));

        if ($fullName !== '') {
            return $fullName;
        }

        return $student->email ?? null;
    }
}