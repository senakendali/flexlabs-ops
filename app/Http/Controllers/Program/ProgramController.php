<?php

namespace App\Http\Controllers\Program;

use App\Http\Controllers\Controller;
use App\Models\Program;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProgramController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->get('per_page', 10);

        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 10;
        }

        $programs = Program::query()
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('programs.index', compact('programs'));
    }

    public function show(Program $program): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Program berhasil diambil.',
            'data' => $program,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:programs,slug'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['slug'] = $this->generateUniqueSlug(
            $validated['slug'] ?? null,
            $validated['name']
        );

        $validated['is_active'] = $request->boolean('is_active', true);

        $program = Program::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Program berhasil ditambahkan.',
            'data' => $program,
        ], 201);
    }

    public function update(Request $request, Program $program): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('programs', 'slug')->ignore($program->id),
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['slug'] = $this->generateUniqueSlug(
            $validated['slug'] ?? null,
            $validated['name'],
            $program->id
        );

        $validated['is_active'] = $request->boolean('is_active', true);

        $program->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Program berhasil diupdate.',
            'data' => $program->fresh(),
        ]);
    }

    public function destroy(Program $program): JsonResponse
    {
        $program->delete();

        return response()->json([
            'success' => true,
            'message' => 'Program berhasil dihapus.',
        ]);
    }

    private function generateUniqueSlug(?string $slug, string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($slug ?: $name);

        if (blank($baseSlug)) {
            $baseSlug = 'program';
        }

        $finalSlug = $baseSlug;
        $counter = 1;

        while ($this->slugExists($finalSlug, $ignoreId)) {
            $finalSlug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $finalSlug;
    }

    private function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        return Program::query()
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists();
    }
}