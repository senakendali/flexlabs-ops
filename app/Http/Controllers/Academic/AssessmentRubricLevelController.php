<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\AssessmentComponent;
use App\Models\AssessmentRubric;
use App\Models\AssessmentRubricLevel;
use App\Models\AssessmentTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssessmentRubricLevelController extends Controller
{
    public function store(
        Request $request,
        AssessmentTemplate $assessmentTemplate,
        AssessmentComponent $component,
        AssessmentRubric $rubric
    ) {
        $this->ensureRubricBelongsToComponent($assessmentTemplate, $component, $rubric);

        $validated = $this->validateLevel($request);

        if ($this->hasOverlappingScoreRange($rubric, (float) $validated['min_score'], (float) $validated['max_score'])) {
            return $this->rangeError($assessmentTemplate, $component);
        }

        DB::transaction(function () use ($rubric, $validated) {
            $nextSortOrder = ((int) $rubric->levels()->max('sort_order')) + 1;

            $rubric->levels()->create([
                'name' => $validated['name'],
                'min_score' => $validated['min_score'],
                'max_score' => $validated['max_score'],
                'description' => $validated['description'] ?? null,
                'sort_order' => $validated['sort_order'] ?? $nextSortOrder,
            ]);
        });

        return redirect()
            ->route('academic.assessment-templates.components.rubric.show', [$assessmentTemplate, $component])
            ->with('success', 'Rubric level berhasil ditambahkan.');
    }

    public function update(
        Request $request,
        AssessmentTemplate $assessmentTemplate,
        AssessmentComponent $component,
        AssessmentRubric $rubric,
        AssessmentRubricLevel $level
    ) {
        $this->ensureLevelBelongsToRubric($assessmentTemplate, $component, $rubric, $level);

        $validated = $this->validateLevel($request);

        if (
            $this->hasOverlappingScoreRange(
                $rubric,
                (float) $validated['min_score'],
                (float) $validated['max_score'],
                $level->id
            )
        ) {
            return $this->rangeError($assessmentTemplate, $component);
        }

        DB::transaction(function () use ($level, $validated) {
            $level->update([
                'name' => $validated['name'],
                'min_score' => $validated['min_score'],
                'max_score' => $validated['max_score'],
                'description' => $validated['description'] ?? null,
                'sort_order' => $validated['sort_order'] ?? $level->sort_order,
            ]);
        });

        return redirect()
            ->route('academic.assessment-templates.components.rubric.show', [$assessmentTemplate, $component])
            ->with('success', 'Rubric level berhasil diperbarui.');
    }

    public function destroy(
        AssessmentTemplate $assessmentTemplate,
        AssessmentComponent $component,
        AssessmentRubric $rubric,
        AssessmentRubricLevel $level
    ) {
        $this->ensureLevelBelongsToRubric($assessmentTemplate, $component, $rubric, $level);

        $level->delete();

        return redirect()
            ->route('academic.assessment-templates.components.rubric.show', [$assessmentTemplate, $component])
            ->with('success', 'Rubric level berhasil dihapus.');
    }

    private function validateLevel(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'min_score' => ['required', 'numeric', 'min:0', 'max:100', 'lte:max_score'],
            'max_score' => ['required', 'numeric', 'min:0', 'max:100', 'gte:min_score'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function hasOverlappingScoreRange(
        AssessmentRubric $rubric,
        float $minScore,
        float $maxScore,
        ?int $exceptLevelId = null
    ): bool {
        return $rubric
            ->levels()
            ->when($exceptLevelId, function ($query) use ($exceptLevelId) {
                $query->where('id', '!=', $exceptLevelId);
            })
            ->where(function ($query) use ($minScore, $maxScore) {
                $query
                    ->whereBetween('min_score', [$minScore, $maxScore])
                    ->orWhereBetween('max_score', [$minScore, $maxScore])
                    ->orWhere(function ($innerQuery) use ($minScore, $maxScore) {
                        $innerQuery
                            ->where('min_score', '<=', $minScore)
                            ->where('max_score', '>=', $maxScore);
                    });
            })
            ->exists();
    }

    private function rangeError(AssessmentTemplate $assessmentTemplate, AssessmentComponent $component)
    {
        return redirect()
            ->route('academic.assessment-templates.components.rubric.show', [$assessmentTemplate, $component])
            ->withErrors([
                'score_range' => 'Range score level tidak boleh overlap dengan level lain.',
            ])
            ->withInput();
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

    private function ensureLevelBelongsToRubric(
        AssessmentTemplate $assessmentTemplate,
        AssessmentComponent $component,
        AssessmentRubric $rubric,
        AssessmentRubricLevel $level
    ): void {
        $this->ensureRubricBelongsToComponent($assessmentTemplate, $component, $rubric);

        abort_if(
            (int) $level->assessment_rubric_id !== (int) $rubric->id,
            404
        );
    }
}