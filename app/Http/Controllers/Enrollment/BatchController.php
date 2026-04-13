<?php

namespace App\Http\Controllers\Enrollment;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Program;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BatchController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->get('per_page', 10);

        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $batches = Batch::with('program')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $programs = Program::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('enrollment.batches.index', compact('batches', 'programs'));
    }

    public function show(Batch $batch): JsonResponse
    {
        $batch->load('program:id,name');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $batch->id,
                'program_id' => $batch->program_id,
                'name' => $batch->name,
                'slug' => $batch->slug,
                'start_date' => optional($batch->start_date)->format('Y-m-d'),
                'end_date' => optional($batch->end_date)->format('Y-m-d'),
                'quota' => $batch->quota,
                'price' => $batch->price,
                'status' => $batch->status,
                'description' => $batch->description,
                'program' => $batch->program ? [
                    'id' => $batch->program->id,
                    'name' => $batch->program->name,
                ] : null,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:batches,slug'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'quota' => ['nullable', 'integer', 'min:1'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['draft', 'open', 'closed', 'ongoing', 'completed'])],
            'description' => ['nullable', 'string'],
        ]);

        $validated['slug'] = $this->generateUniqueSlug(
            $validated['slug'] ?? null,
            $validated['name']
        );

        $validated['price'] = $validated['price'] ?? 0;

        $batch = Batch::create($validated);
        $batch->load('program:id,name');

        return response()->json([
            'success' => true,
            'message' => 'Batch created successfully.',
            'data' => $batch,
        ], 201);
    }

    public function update(Request $request, Batch $batch): JsonResponse
    {
        $validated = $request->validate([
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('batches', 'slug')->ignore($batch->id),
            ],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'quota' => ['nullable', 'integer', 'min:1'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['draft', 'open', 'closed', 'ongoing', 'completed'])],
            'description' => ['nullable', 'string'],
        ]);

        $validated['slug'] = $this->generateUniqueSlug(
            $validated['slug'] ?? null,
            $validated['name'],
            $batch->id
        );

        $validated['price'] = $validated['price'] ?? 0;

        $batch->update($validated);
        $batch->load('program:id,name');

        return response()->json([
            'success' => true,
            'message' => 'Batch updated successfully.',
            'data' => $batch,
        ]);
    }

    public function destroy(Batch $batch): JsonResponse
    {
        $batch->delete();

        return response()->json([
            'success' => true,
            'message' => 'Batch deleted successfully.',
        ]);
    }

    private function generateUniqueSlug(?string $slug, string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($slug ?: $name);

        if ($baseSlug === '') {
            $baseSlug = 'batch';
        }

        $uniqueSlug = $baseSlug;
        $counter = 1;

        while ($this->slugExists($uniqueSlug, $ignoreId)) {
            $uniqueSlug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $uniqueSlug;
    }

    private function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        return Batch::query()
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists();
    }
}