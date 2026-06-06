<?php

namespace App\Http\Controllers\Trial;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\TrialTheme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class TrialThemeController extends Controller
{
    private const IMAGE_DISK = 'public';
    private const IMAGE_DIRECTORY = 'trial-themes';
    private const DEFAULT_IMAGE_PATH = 'images/placeholders/webinar-theme.png';

    public function index(Request $request): View
    {
        $perPage = (int) $request->get('per_page', 10);

        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $themes = TrialTheme::query()
            ->with('program')
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
        $trialTheme->load('program');

        return response()->json([
            'success' => true,
            'data' => $this->transformTheme($trialTheme),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateTheme($request);

        $validated['slug'] = $this->generateUniqueSlug(
            $validated['slug'] ?? null,
            $validated['name']
        );

        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active', true);

        unset($validated['image']);

        if ($request->hasFile('image')) {
            $validated['image'] = $this->storeImage($request);
        }

        $theme = TrialTheme::create($validated);
        $theme->load('program');

        return response()->json([
            'success' => true,
            'message' => 'Trial theme berhasil ditambahkan.',
            'data' => $this->transformTheme($theme),
        ]);
    }

    public function update(Request $request, TrialTheme $trialTheme): JsonResponse
    {
        $validated = $this->validateTheme($request, $trialTheme);

        $validated['slug'] = $this->generateUniqueSlug(
            $validated['slug'] ?? null,
            $validated['name'],
            $trialTheme->id
        );

        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active');

        unset($validated['image']);

        $oldImage = $trialTheme->image;
        $newImage = null;

        try {
            if ($request->hasFile('image')) {
                $newImage = $this->storeImage($request);
                $validated['image'] = $newImage;
            }

            $trialTheme->update($validated);

            if ($newImage && $oldImage) {
                $this->deleteImage($oldImage);
            }
        } catch (Throwable $exception) {
            if ($newImage) {
                $this->deleteImage($newImage);
            }

            throw $exception;
        }

        $trialTheme->refresh()->load('program');

        return response()->json([
            'success' => true,
            'message' => 'Trial theme berhasil diperbarui.',
            'data' => $this->transformTheme($trialTheme),
        ]);
    }

    public function destroy(TrialTheme $trialTheme): JsonResponse
    {
        $image = $trialTheme->image;

        $trialTheme->delete();

        if ($image) {
            $this->deleteImage($image);
        }

        return response()->json([
            'success' => true,
            'message' => 'Trial theme berhasil dihapus.',
        ]);
    }

    private function validateTheme(Request $request, ?TrialTheme $trialTheme = null): array
    {
        return $request->validate([
            'program_id' => ['required', 'exists:programs,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('trial_themes', 'slug')->ignore($trialTheme?->id),
            ],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],

            // Untuk upload gambar dari FormData.
            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ]);
    }

    private function storeImage(Request $request): string
    {
        return $request
            ->file('image')
            ->store(self::IMAGE_DIRECTORY, self::IMAGE_DISK);
    }

    private function deleteImage(?string $path): void
    {
        if (! $path) {
            return;
        }

        if (Storage::disk(self::IMAGE_DISK)->exists($path)) {
            Storage::disk(self::IMAGE_DISK)->delete($path);
        }
    }

    private function transformTheme(TrialTheme $theme): array
    {
        return [
            'id' => $theme->id,
            'program_id' => $theme->program_id,
            'program' => $theme->program,
            'name' => $theme->name,
            'slug' => $theme->slug,
            'description' => $theme->description,
            'image' => $theme->image,
            'image_url' => $this->getImageUrl($theme),
            'sort_order' => $theme->sort_order,
            'is_active' => (bool) $theme->is_active,
            'created_at' => $theme->created_at,
            'updated_at' => $theme->updated_at,
        ];
    }

    private function getImageUrl(TrialTheme $theme): string
    {
        if ($theme->image) {
            return Storage::disk(self::IMAGE_DISK)->url($theme->image);
        }

        return asset(self::DEFAULT_IMAGE_PATH);
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