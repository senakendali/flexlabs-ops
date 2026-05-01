<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\AssessmentComponent;
use App\Models\AssessmentRubric;
use App\Models\AssessmentRubricCriteria;
use App\Models\AssessmentTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AssessmentRubricCriteriaController extends Controller
{
    public function store(
        Request $request,
        AssessmentTemplate $assessmentTemplate,
        AssessmentComponent $component,
        AssessmentRubric $rubric
    ) {
        $this->ensureRubricBelongsToComponent($assessmentTemplate, $component, $rubric);

        $validated = $this->validateCriteria($request);

        $totalWeight = $this->getTotalWeight($rubric);

        if (($totalWeight + (float) $validated['weight']) > 100) {
            return $this->weightError($assessmentTemplate, $component, $totalWeight, (float) $validated['weight']);
        }

        DB::transaction(function () use ($rubric, $validated) {
            $nextSortOrder = ((int) $rubric->criteria()->max('sort_order')) + 1;

            $rubric->criteria()->create([
                'name' => $validated['name'],
                'code' => $this->resolveCode($rubric, $validated['code'] ?? null, $validated['name']),
                'description' => $validated['description'] ?? null,
                'weight' => $validated['weight'],
                'max_score' => $validated['max_score'],
                'sort_order' => $validated['sort_order'] ?? $nextSortOrder,
                'is_required' => (bool) $validated['is_required'],
            ]);
        });

        return redirect()
            ->route('academic.assessment-templates.components.rubric.show', [$assessmentTemplate, $component])
            ->with('success', 'Rubric criteria berhasil ditambahkan.');
    }

    public function update(
        Request $request,
        AssessmentTemplate $assessmentTemplate,
        AssessmentComponent $component,
        AssessmentRubric $rubric,
        AssessmentRubricCriteria $criterion
    ) {
        $this->ensureCriterionBelongsToRubric($assessmentTemplate, $component, $rubric, $criterion);

        $validated = $this->validateCriteria($request);

        $totalWeight = $this->getTotalWeight($rubric, $criterion->id);

        if (($totalWeight + (float) $validated['weight']) > 100) {
            return $this->weightError($assessmentTemplate, $component, $totalWeight, (float) $validated['weight']);
        }

        DB::transaction(function () use ($rubric, $criterion, $validated) {
            $criterion->update([
                'name' => $validated['name'],
                'code' => $this->resolveCode($rubric, $validated['code'] ?? null, $validated['name'], $criterion->id),
                'description' => $validated['description'] ?? null,
                'weight' => $validated['weight'],
                'max_score' => $validated['max_score'],
                'sort_order' => $validated['sort_order'] ?? $criterion->sort_order,
                'is_required' => (bool) $validated['is_required'],
            ]);
        });

        return redirect()
            ->route('academic.assessment-templates.components.rubric.show', [$assessmentTemplate, $component])
            ->with('success', 'Rubric criteria berhasil diperbarui.');
    }

    public function destroy(
        AssessmentTemplate $assessmentTemplate,
        AssessmentComponent $component,
        AssessmentRubric $rubric,
        AssessmentRubricCriteria $criterion
    ) {
        $this->ensureCriterionBelongsToRubric($assessmentTemplate, $component, $rubric, $criterion);

        $criterion->delete();

        return redirect()
            ->route('academic.assessment-templates.components.rubric.show', [$assessmentTemplate, $component])
            ->with('success', 'Rubric criteria berhasil dihapus.');
    }

    private function validateCriteria(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'max_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_required' => ['required', 'boolean'],
        ]);
    }

    private function getTotalWeight(AssessmentRubric $rubric, ?int $exceptCriteriaId = null): float
    {
        return (float) $rubric
            ->criteria()
            ->when($exceptCriteriaId, function ($query) use ($exceptCriteriaId) {
                $query->where('id', '!=', $exceptCriteriaId);
            })
            ->sum('weight');
    }

    private function weightError(
        AssessmentTemplate $assessmentTemplate,
        AssessmentComponent $component,
        float $currentWeight,
        float $newWeight
    ) {
        $message = 'Total weight criteria tidak boleh lebih dari 100%. '
            . 'Saat ini sudah ' . number_format($currentWeight, 2)
            . '%, input baru ' . number_format($newWeight, 2) . '%.';

        return redirect()
            ->route('academic.assessment-templates.components.rubric.show', [$assessmentTemplate, $component])
            ->withErrors(['weight' => $message])
            ->withInput();
    }

    private function resolveCode(
        AssessmentRubric $rubric,
        ?string $code,
        string $name,
        ?int $exceptCriteriaId = null
    ): string {
        $baseCode = $code ?: Str::slug($name, '_');
        $baseCode = Str::upper($baseCode);

        $candidate = $baseCode;
        $counter = 2;

        while (
            $rubric
                ->criteria()
                ->when($exceptCriteriaId, function ($query) use ($exceptCriteriaId) {
                    $query->where('id', '!=', $exceptCriteriaId);
                })
                ->where('code', $candidate)
                ->exists()
        ) {
            $candidate = $baseCode . '_' . $counter;
            $counter++;
        }

        return $candidate;
    }

    private function ensureRubricBelongsToComponent(
        AssessmentTemplate $assessmentTemplate,
        AssessmentComponent $component,
        AssessmentRubric $rubric
    ): void {
        abort_if(
            (int) $component->assessment_template_id !== (int) $assessmentTemplate->id,
            404
        );

        abort_if(
            (int) $rubric->assessment_component_id !== (int) $component->id,
            404
        );
    }

    private function ensureCriterionBelongsToRubric(
        AssessmentTemplate $assessmentTemplate,
        AssessmentComponent $component,
        AssessmentRubric $rubric,
        AssessmentRubricCriteria $criterion
    ): void {
        $this->ensureRubricBelongsToComponent($assessmentTemplate, $component, $rubric);

        abort_if(
            (int) $criterion->assessment_rubric_id !== (int) $rubric->id,
            404
        );
    }
}