<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\AssessmentComponent;
use App\Models\AssessmentRubric;
use App\Models\AssessmentTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssessmentRubricController extends Controller
{
    public function show(AssessmentTemplate $assessmentTemplate, AssessmentComponent $component)
    {
        $this->ensureComponentBelongsToTemplate($assessmentTemplate, $component);

        $component->load([
            'template.program',
            'rubric.criteria',
            'rubric.levels',
            'rubrics.criteria',
            'rubrics.levels',
        ]);

        $rubric = $component->rubric;
        $rubrics = $component->rubrics()
            ->with(['criteria', 'levels'])
            ->latest()
            ->get();

        return view('academic.assessment-rubrics.show', compact(
            'assessmentTemplate',
            'component',
            'rubric',
            'rubrics'
        ));
    }

    public function store(Request $request, AssessmentTemplate $assessmentTemplate, AssessmentComponent $component)
    {
        $this->ensureComponentBelongsToTemplate($assessmentTemplate, $component);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($component, $validated) {
            if ((bool) $validated['is_active']) {
                $component->rubrics()->update(['is_active' => false]);
            }

            $component->rubrics()->create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_active' => (bool) $validated['is_active'],
            ]);
        });

        return redirect()
            ->route('academic.assessment-templates.components.rubric.show', [$assessmentTemplate, $component])
            ->with('success', 'Rubric berhasil dibuat.');
    }

    public function update(
        Request $request,
        AssessmentTemplate $assessmentTemplate,
        AssessmentComponent $component,
        AssessmentRubric $rubric
    ) {
        $this->ensureRubricBelongsToComponent($assessmentTemplate, $component, $rubric);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($component, $rubric, $validated) {
            if ((bool) $validated['is_active']) {
                $component->rubrics()
                    ->where('id', '!=', $rubric->id)
                    ->update(['is_active' => false]);
            }

            $rubric->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_active' => (bool) $validated['is_active'],
            ]);
        });

        return redirect()
            ->route('academic.assessment-templates.components.rubric.show', [$assessmentTemplate, $component])
            ->with('success', 'Rubric berhasil diperbarui.');
    }

    public function activate(
        AssessmentTemplate $assessmentTemplate,
        AssessmentComponent $component,
        AssessmentRubric $rubric
    ) {
        $this->ensureRubricBelongsToComponent($assessmentTemplate, $component, $rubric);

        DB::transaction(function () use ($component, $rubric) {
            $component->rubrics()->update(['is_active' => false]);

            $rubric->update(['is_active' => true]);
        });

        return redirect()
            ->route('academic.assessment-templates.components.rubric.show', [$assessmentTemplate, $component])
            ->with('success', 'Rubric berhasil diaktifkan.');
    }

    private function ensureComponentBelongsToTemplate(
        AssessmentTemplate $assessmentTemplate,
        AssessmentComponent $component
    ): void {
        abort_if(
            (int) $component->assessment_template_id !== (int) $assessmentTemplate->id,
            404
        );
    }

    private function ensureRubricBelongsToComponent(
        AssessmentTemplate $assessmentTemplate,
        AssessmentComponent $component,
        AssessmentRubric $rubric
    ): void {
        $this->ensureComponentBelongsToTemplate($assessmentTemplate, $component);

        abort_if(
            (int) $rubric->assessment_component_id !== (int) $component->id,
            404
        );
    }
}