<?php

namespace App\Http\Controllers\Trial;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\TrialTheme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TrialThemeController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->get('per_page', 10);

        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 10;
        }

        $themes = TrialTheme::with('program')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $programs = Program::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('trial.themes.index', compact('themes', 'programs'));
    }

    public function show(TrialTheme $trialTheme): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $trialTheme->load('program'),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'program_id' => ['required', 'exists:programs,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:trial_themes,slug'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['slug'] = $this->generateUniqueSlug(
            $validated['slug'] ?? null,
            $validated['name']
        );

        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active', true);

        $theme = TrialTheme::create($validated)->load('program');

        return response()->json([
            'success' => true,
            'message' => 'Trial theme berhasil ditambahkan.',
            'data' => $theme,
        ]);
    }

    public function update(Request $request, TrialTheme $trialTheme): JsonResponse
    {
        $validated = $request->validate([
            'program_id' => ['required', 'exists:programs,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('trial_themes', 'slug')->ignore($trialTheme->id),
            ],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['slug'] = $this->generateUniqueSlug(
            $validated['slug'] ?? null,
            $validated['name'],
            $trialTheme->id
        );

        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active');

        $trialTheme->update($validated);
        $trialTheme->load('program');

        return response()->json([
            'success' => true,
            'message' => 'Trial theme berhasil diperbarui.',
            'data' => $trialTheme,
        ]);
    }

    public function destroy(TrialTheme $trialTheme): JsonResponse
    {
        $trialTheme->delete();

        return response()->json([
            'success' => true,
            'message' => 'Trial theme berhasil dihapus.',
        ]);
    }

    private function generateUniqueSlug(?string $slug, string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($slug ?: $name);

        if (blank($baseSlug)) {
            $baseSlug = 'trial-theme';
        }

        $uniqueSlug = $baseSlug;
        $counter = 1;

        while (
            TrialTheme::query()
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('slug', $uniqueSlug)
                ->exists()
        ) {
            $uniqueSlug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $uniqueSlug;
    }
}