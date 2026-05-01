<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\AssessmentComponent;
use App\Models\AssessmentTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AssessmentComponentController extends Controller
{
    public function store(Request $request, AssessmentTemplate $assessmentTemplate)
    {
        $validated = $this->validateComponent($request);

        $totalWeight = $this->getTotalWeight($assessmentTemplate);

        if (($totalWeight + (float) $validated['weight']) > 100) {
            return $this->weightError($request, $totalWeight, (float) $validated['weight']);
        }

        $component = DB::transaction(function () use ($assessmentTemplate, $validated) {
            $nextSortOrder = ((int) $assessmentTemplate->components()->max('sort_order')) + 1;

            return $assessmentTemplate->components()->create([
                'name' => $validated['name'],
                'code' => $this->resolveCode($assessmentTemplate, $validated['code'] ?? null, $validated['name']),
                'type' => $validated['type'],
                'weight' => $validated['weight'],
                'max_score' => $validated['max_score'],
                'is_required' => (bool) ($validated['is_required'] ?? false),
                'is_auto_calculated' => (bool) ($validated['is_auto_calculated'] ?? false),
                'sort_order' => $validated['sort_order'] ?? $nextSortOrder,
                'description' => $validated['description'] ?? null,
            ]);
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Assessment component berhasil ditambahkan.',
                'component' => $component,
            ]);
        }

        return redirect()
            ->route('academic.assessment-templates.show', $assessmentTemplate)
            ->with('success', 'Assessment component berhasil ditambahkan.');
    }

    public function update(Request $request, AssessmentTemplate $assessmentTemplate, AssessmentComponent $component)
    {
        $this->ensureComponentBelongsToTemplate($assessmentTemplate, $component);

        $validated = $this->validateComponent($request, $component);

        $totalWeight = $this->getTotalWeight($assessmentTemplate, $component->id);

        if (($totalWeight + (float) $validated['weight']) > 100) {
            return $this->weightError($request, $totalWeight, (float) $validated['weight']);
        }

        DB::transaction(function () use ($assessmentTemplate, $component, $validated) {
            $component->update([
                'name' => $validated['name'],
                'code' => $this->resolveCode($assessmentTemplate, $validated['code'] ?? null, $validated['name'], $component->id),
                'type' => $validated['type'],
                'weight' => $validated['weight'],
                'max_score' => $validated['max_score'],
                'is_required' => (bool) ($validated['is_required'] ?? false),
                'is_auto_calculated' => (bool) ($validated['is_auto_calculated'] ?? false),
                'sort_order' => $validated['sort_order'] ?? $component->sort_order,
                'description' => $validated['description'] ?? null,
            ]);
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Assessment component berhasil diperbarui.',
                'component' => $component->fresh(),
            ]);
        }

        return redirect()
            ->route('academic.assessment-templates.show', $assessmentTemplate)
            ->with('success', 'Assessment component berhasil diperbarui.');
    }

    public function destroy(Request $request, AssessmentTemplate $assessmentTemplate, AssessmentComponent $component)
    {
        $this->ensureComponentBelongsToTemplate($assessmentTemplate, $component);

        DB::transaction(function () use ($component) {
            $component->delete();
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Assessment component berhasil dihapus.',
            ]);
        }

        return redirect()
            ->route('academic.assessment-templates.show', $assessmentTemplate)
            ->with('success', 'Assessment component berhasil dihapus.');
    }

    private function validateComponent(Request $request, ?AssessmentComponent $component = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255'],
            'type' => [
                'required',
                Rule::in([
                    'attendance',
                    'progress',
                    'quiz',
                    'assignment',
                    'project',
                    'attitude',
                    'custom',
                ]),
            ],
            'weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'max_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_required' => ['required', 'boolean'],
            'is_auto_calculated' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);
    }

    private function getTotalWeight(AssessmentTemplate $assessmentTemplate, ?int $exceptComponentId = null): float
    {
        return (float) $assessmentTemplate
            ->components()
            ->when($exceptComponentId, function ($query) use ($exceptComponentId) {
                $query->where('id', '!=', $exceptComponentId);
            })
            ->sum('weight');
    }

    private function weightError(Request $request, float $currentWeight, float $newWeight)
    {
        $message = 'Total weight component tidak boleh lebih dari 100%. '
            . 'Saat ini sudah ' . number_format($currentWeight, 2)
            . '%, input baru ' . number_format($newWeight, 2) . '%.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'errors' => [
                    'weight' => [$message],
                ],
            ], 422);
        }

        return back()
            ->withErrors(['weight' => $message])
            ->withInput();
    }

    private function resolveCode(
        AssessmentTemplate $assessmentTemplate,
        ?string $code,
        string $name,
        ?int $exceptComponentId = null
    ): string {
        $baseCode = $code ?: Str::slug($name, '_');

        $baseCode = Str::upper($baseCode);

        $candidate = $baseCode;
        $counter = 2;

        while (
            $assessmentTemplate
                ->components()
                ->when($exceptComponentId, function ($query) use ($exceptComponentId) {
                    $query->where('id', '!=', $exceptComponentId);
                })
                ->where('code', $candidate)
                ->exists()
        ) {
            $candidate = $baseCode . '_' . $counter;
            $counter++;
        }

        return $candidate;
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
}