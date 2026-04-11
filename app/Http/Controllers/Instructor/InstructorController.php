<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Instructor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InstructorController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->get('per_page', 10);

        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 10;
        }

        $instructors = Instructor::query()
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('instructors.index', compact('instructors'));
    }

    public function show(Instructor $instructor): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Instructor berhasil diambil.',
            'data' => $instructor,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:instructors,slug'],
            'email' => ['nullable', 'email', 'max:255', 'unique:instructors,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'employment_type' => ['nullable', 'string', 'in:full_time,part_time'],
            'bio' => ['nullable', 'string'],
            'photo' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['slug'] = $this->generateUniqueSlug(
            $validated['slug'] ?? null,
            $validated['name']
        );

        $validated['is_active'] = $request->boolean('is_active', true);

        $instructor = Instructor::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'specialization' => $validated['specialization'] ?? null,
            'employment_type' => $validated['employment_type'] ?? null,
            'bio' => $validated['bio'] ?? null,
            'photo' => $validated['photo'] ?? null,
            'is_active' => $validated['is_active'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Instructor berhasil ditambahkan.',
            'data' => $instructor,
        ], 201);
    }

    public function update(Request $request, Instructor $instructor): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('instructors', 'slug')->ignore($instructor->id),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('instructors', 'email')->ignore($instructor->id),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'employment_type' => ['nullable', 'string', 'in:full_time,part_time'],
            'bio' => ['nullable', 'string'],
            'photo' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['slug'] = $this->generateUniqueSlug(
            $validated['slug'] ?? null,
            $validated['name'],
            $instructor->id
        );

        $validated['is_active'] = $request->boolean('is_active', true);

        $instructor->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'specialization' => $validated['specialization'] ?? null,
            'employment_type' => $validated['employment_type'] ?? null,
            'bio' => $validated['bio'] ?? null,
            'photo' => $validated['photo'] ?? null,
            'is_active' => $validated['is_active'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Instructor berhasil diupdate.',
            'data' => $instructor->fresh(),
        ]);
    }

    public function destroy(Instructor $instructor): JsonResponse
    {
        $instructor->delete();

        return response()->json([
            'success' => true,
            'message' => 'Instructor berhasil dihapus.',
        ]);
    }

    private function generateUniqueSlug(?string $slug, string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($slug ?: $name);

        if (blank($baseSlug)) {
            $baseSlug = 'instructor';
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
        return Instructor::query()
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists();
    }
}