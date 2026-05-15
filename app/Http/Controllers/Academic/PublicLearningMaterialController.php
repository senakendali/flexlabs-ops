<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\PublicLearningMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PublicLearningMaterialController extends Controller
{
    public function index(Request $request)
    {
        $query = PublicLearningMaterial::query()
            ->withCount(['blocks', 'images'])
            ->latest();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('subtitle', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('instructor_name', 'like', "%{$search}%");
            });
        }

        $materials = $query->paginate(12)->withQueryString();

        $stats = [
            'total' => PublicLearningMaterial::count(),
            'trial' => PublicLearningMaterial::where('type', 'trial')->count(),
            'workshop' => PublicLearningMaterial::where('type', 'workshop')->count(),
            'published' => PublicLearningMaterial::where('status', 'published')->count(),
            'draft' => PublicLearningMaterial::where('status', 'draft')->count(),
            'archived' => PublicLearningMaterial::where('status', 'archived')->count(),
        ];

        return view('academic.public-learning-materials.index', compact('materials', 'stats'));
    }

    public function create()
    {
        $material = new PublicLearningMaterial([
            'type' => 'trial',
            'status' => 'draft',
        ]);

        return view('academic.public-learning-materials.form', compact('material'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateMaterial($request);

        unset($validated['cover_image']);

        $validated['slug'] = $this->generateUniqueSlug(
            $request->filled('slug') ? $request->slug : $validated['title']
        );

        $validated['public_token'] = Str::random(64);
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        $validated = array_merge($validated, $this->buildSchedulePayload($request));

        if ($request->hasFile('cover_image')) {
            $validated['cover_image_path'] = $request->file('cover_image')
                ->store('public-learning-materials/covers', 'public');
        }

        $material = PublicLearningMaterial::create($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Material berhasil dibuat.',
                'data' => [
                    'id' => $material->id,
                    'title' => $material->title,
                    'slug' => $material->slug,
                    'status' => $material->status,
                    'public_url' => $material->public_url,
                ],
                'redirect_url' => route('public-learning-materials.edit', $material),
            ]);
        }

        return redirect()
            ->route('public-learning-materials.edit', $material)
            ->with('success', 'Material berhasil dibuat.');
    }

    public function edit(PublicLearningMaterial $publicLearningMaterial)
    {
        $material = $publicLearningMaterial->load([
            'blocks',
            'images',
        ]);

        return view('academic.public-learning-materials.form', compact('material'));
    }

    public function update(Request $request, PublicLearningMaterial $publicLearningMaterial)
    {
        $material = $publicLearningMaterial;

        $validated = $this->validateMaterial($request, $material->id);

        unset($validated['cover_image']);

        $validated['slug'] = $this->generateUniqueSlug(
            $request->filled('slug') ? $request->slug : $validated['title'],
            $material->id
        );

        if (! $material->public_token) {
            $validated['public_token'] = Str::random(64);
        }

        $validated['updated_by'] = auth()->id();

        $validated = array_merge($validated, $this->buildSchedulePayload($request));

        if ($request->hasFile('cover_image')) {
            if ($material->cover_image_path) {
                Storage::disk('public')->delete($material->cover_image_path);
            }

            $validated['cover_image_path'] = $request->file('cover_image')
                ->store('public-learning-materials/covers', 'public');
        }

        $material->update($validated);

        $material->refresh();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Material berhasil diperbarui.',
                'data' => [
                    'id' => $material->id,
                    'title' => $material->title,
                    'slug' => $material->slug,
                    'status' => $material->status,
                    'public_url' => $material->public_url,
                ],
            ]);
        }

        return redirect()
            ->route('public-learning-materials.edit', $material)
            ->with('success', 'Material berhasil diperbarui.');
    }

    public function destroy(PublicLearningMaterial $publicLearningMaterial)
    {
        $material = $publicLearningMaterial->load(['blocks', 'images']);

        if ($material->cover_image_path) {
            Storage::disk('public')->delete($material->cover_image_path);
        }

        foreach ($material->blocks as $block) {
            if ($block->image_path) {
                Storage::disk('public')->delete($block->image_path);
            }
        }

        foreach ($material->images as $image) {
            if ($image->image_path) {
                Storage::disk('public')->delete($image->image_path);
            }
        }

        $material->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Material berhasil dihapus.',
            ]);
        }

        return redirect()
            ->route('public-learning-materials.index')
            ->with('success', 'Material berhasil dihapus.');
    }

    public function publish(PublicLearningMaterial $publicLearningMaterial)
    {
        $publicLearningMaterial->update([
            'status' => 'published',
            'updated_by' => auth()->id(),
        ]);

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Material berhasil dipublish.',
                'data' => [
                    'id' => $publicLearningMaterial->id,
                    'status' => $publicLearningMaterial->fresh()->status,
                ],
            ]);
        }

        return back()->with('success', 'Material berhasil dipublish.');
    }

    public function archive(PublicLearningMaterial $publicLearningMaterial)
    {
        $publicLearningMaterial->update([
            'status' => 'archived',
            'updated_by' => auth()->id(),
        ]);

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Material berhasil diarsipkan.',
                'data' => [
                    'id' => $publicLearningMaterial->id,
                    'status' => $publicLearningMaterial->fresh()->status,
                ],
            ]);
        }

        return back()->with('success', 'Material berhasil diarsipkan.');
    }

    public function duplicate(PublicLearningMaterial $publicLearningMaterial)
    {
        $source = $publicLearningMaterial->load(['blocks', 'images']);

        $copy = $source->replicate([
            'slug',
            'public_token',
            'status',
            'created_by',
            'updated_by',
            'created_at',
            'updated_at',
            'deleted_at',
        ]);

        $copy->title = $source->title . ' Copy';
        $copy->slug = $this->generateUniqueSlug($copy->title);
        $copy->public_token = Str::random(64);
        $copy->status = 'draft';
        $copy->created_by = auth()->id();
        $copy->updated_by = auth()->id();
        $copy->save();

        foreach ($source->blocks as $block) {
            $newBlock = $block->replicate([
                'created_at',
                'updated_at',
            ]);

            $newBlock->public_learning_material_id = $copy->id;
            $newBlock->save();
        }

        foreach ($source->images as $image) {
            $newImage = $image->replicate([
                'created_at',
                'updated_at',
            ]);

            $newImage->public_learning_material_id = $copy->id;
            $newImage->save();
        }

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Material berhasil diduplikasi sebagai draft.',
                'data' => [
                    'id' => $copy->id,
                    'title' => $copy->title,
                    'slug' => $copy->slug,
                    'status' => $copy->status,
                    'public_url' => $copy->public_url,
                ],
                'redirect_url' => route('public-learning-materials.edit', $copy),
            ]);
        }

        return redirect()
            ->route('public-learning-materials.edit', $copy)
            ->with('success', 'Material berhasil diduplikasi sebagai draft.');
    }

    private function validateMaterial(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'type' => ['required', Rule::in(['trial', 'workshop'])],
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('public_learning_materials', 'slug')->ignore($ignoreId),
            ],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'instructor_name' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'event_date' => ['nullable', 'date'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'access_starts_at' => ['nullable', 'date'],
            'access_ends_at' => ['nullable', 'date', 'after_or_equal:access_starts_at'],
            'cover_image' => ['nullable', 'image', 'max:4096'],
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
        ]);
    }

    private function buildSchedulePayload(Request $request): array
    {
        $startsAt = $request->filled('starts_at')
            ? Carbon::parse($request->starts_at)
            : null;

        $endsAt = $request->filled('ends_at')
            ? Carbon::parse($request->ends_at)
            : null;

        $accessStartsAt = $request->filled('access_starts_at')
            ? Carbon::parse($request->access_starts_at)
            : $startsAt;

        $accessEndsAt = $request->filled('access_ends_at')
            ? Carbon::parse($request->access_ends_at)
            : ($endsAt ? $endsAt->copy()->addHour() : null);

        return [
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'access_starts_at' => $accessStartsAt,
            'access_ends_at' => $accessEndsAt,
        ];
    }

    private function generateUniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($value);

        if (! $baseSlug) {
            $baseSlug = 'material';
        }

        $slug = $baseSlug;
        $counter = 2;

        while (
            PublicLearningMaterial::query()
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}