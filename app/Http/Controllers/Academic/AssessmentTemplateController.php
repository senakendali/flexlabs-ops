<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\AssessmentTemplate;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AssessmentTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = AssessmentTemplate::query()
            ->with('program')
            ->withCount('components')
            ->when($request->filled('program_id'), function ($q) use ($request) {
                $q->where('program_id', $request->program_id);
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                if ($request->status === 'active') {
                    $q->where('is_active', true);
                }

                if ($request->status === 'inactive') {
                    $q->where('is_active', false);
                }
            })
            ->latest();

        $templates = $query->paginate(15)->withQueryString();

        $programs = Program::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $templates,
                'programs' => $programs,
            ]);
        }

        return view('academic.assessment-templates.index', compact(
            'templates',
            'programs'
        ));
    }

    public function create()
    {
        $programs = Program::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('academic.assessment-templates.form', [
            'template' => new AssessmentTemplate(),
            'programs' => $programs,
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateTemplate($request);

        $template = DB::transaction(function () use ($validated, $request) {
            $validated['code'] = $validated['code']
                ?: Str::slug($validated['name'], '_');

            $validated['created_by'] = $request->user()?->id;
            $validated['updated_by'] = $request->user()?->id;
            $validated['is_active'] = $request->boolean('is_active', true);
            $validated['requires_final_project'] = $request->boolean('requires_final_project', true);

            return AssessmentTemplate::query()->create($validated);
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Assessment template created successfully.',
                'data' => $template->load('program'),
            ], 201);
        }

        return redirect()
            ->route('academic.assessment-templates.show', $template)
            ->with('success', 'Assessment template created successfully.');
    }

    public function show(Request $request, AssessmentTemplate $assessmentTemplate)
    {
        $assessmentTemplate->load([
            'program',
            'components.rubric.criteria',
            'components.rubric.levels',
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $assessmentTemplate,
            ]);
        }

        return view('academic.assessment-templates.show', [
            'template' => $assessmentTemplate,
        ]);
    }

    public function edit(AssessmentTemplate $assessmentTemplate)
    {
        $programs = Program::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('academic.assessment-templates.form', [
            'template' => $assessmentTemplate,
            'programs' => $programs,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, AssessmentTemplate $assessmentTemplate)
    {
        $validated = $this->validateTemplate($request, $assessmentTemplate);

        DB::transaction(function () use ($validated, $request, $assessmentTemplate) {
            $validated['code'] = $validated['code']
                ?: Str::slug($validated['name'], '_');

            $validated['updated_by'] = $request->user()?->id;
            $validated['is_active'] = $request->boolean('is_active');
            $validated['requires_final_project'] = $request->boolean('requires_final_project');

            $assessmentTemplate->update($validated);
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Assessment template updated successfully.',
                'data' => $assessmentTemplate->fresh('program'),
            ]);
        }

        return redirect()
            ->route('academic.assessment-templates.show', $assessmentTemplate)
            ->with('success', 'Assessment template updated successfully.');
    }

    public function destroy(Request $request, AssessmentTemplate $assessmentTemplate)
    {
        $assessmentTemplate->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Assessment template deleted successfully.',
            ]);
        }

        return redirect()
            ->route('academic.assessment-templates.index')
            ->with('success', 'Assessment template deleted successfully.');
    }

    private function validateTemplate(Request $request, ?AssessmentTemplate $template = null): array
    {
        return $request->validate([
            'program_id' => ['required', 'exists:programs,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('assessment_templates', 'code')
                    ->where('program_id', $request->program_id)
                    ->ignore($template?->id),
            ],
            'description' => ['nullable', 'string'],
            'passing_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'min_attendance_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'min_progress_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'requires_final_project' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}