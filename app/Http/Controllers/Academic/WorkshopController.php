<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Workshop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WorkshopController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->input('per_page', 10);

        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $search = trim((string) $request->input('search'));
        $status = $request->input('status');
        $category = $request->input('category');

        $workshops = Workshop::query()
            ->withCount('benefits')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('title', 'like', '%' . $search . '%')
                        ->orWhere('slug', 'like', '%' . $search . '%')
                        ->orWhere('badge', 'like', '%' . $search . '%')
                        ->orWhere('category', 'like', '%' . $search . '%')
                        ->orWhere('level', 'like', '%' . $search . '%');
                });
            })
            ->when($status !== null && $status !== '', fn ($query) => $query->where('is_active', (bool) $status))
            ->when($category, fn ($query) => $query->where('category', $category))
            ->orderBy('sort_order')
            ->orderBy('title')
            ->paginate($perPage)
            ->withQueryString();

        return view('academic.workshops.index', [
            'workshops' => $workshops,
            'categories' => Workshop::query()
                ->whereNotNull('category')
                ->where('category', '!=', '')
                ->select('category')
                ->distinct()
                ->orderBy('category')
                ->pluck('category'),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'category' => $category,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create(): View
    {
        return view('academic.workshops.form', [
            'workshop' => new Workshop([
                'price' => 0,
                'rating' => 5,
                'rating_count' => 0,
                'intro_video_type' => 'youtube',
                'is_active' => true,
                'sort_order' => 0,
            ]),
            'benefits' => [''],
            'submitMode' => 'create',
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $this->validateRequest($request);

        $workshop = DB::transaction(function () use ($request, $validated) {
            $workshop = new Workshop();
            $workshop->fill($this->buildPayload($validated));

            if ($request->hasFile('image')) {
                $workshop->image = $this->storeImage($request->file('image'));
            }

            $workshop->save();

            $this->syncBenefits($workshop, $validated['benefits'] ?? []);

            return $workshop->fresh('benefits');
        });

        return $this->successResponse(
            $request,
            'Workshop berhasil dibuat.',
            route('academic.workshops.edit', $workshop),
            $workshop
        );
    }

    public function show(Workshop $workshop): View
    {
        $workshop->load([
            'benefits' => fn ($query) => $query->orderBy('sort_order'),
        ]);

        return view('academic.workshops.show', [
            'workshop' => $workshop,
        ]);
    }

    public function edit(Workshop $workshop): View
    {
        $workshop->load([
            'benefits' => fn ($query) => $query->orderBy('sort_order'),
        ]);

        return view('academic.workshops.form', [
            'workshop' => $workshop,
            'benefits' => $workshop->benefits->pluck('content')->values()->all(),
            'submitMode' => 'edit',
        ]);
    }

    public function update(Request $request, Workshop $workshop): JsonResponse|RedirectResponse
    {
        $validated = $this->validateRequest($request, $workshop->id);

        DB::transaction(function () use ($request, $workshop, $validated) {
            $payload = $this->buildPayload($validated);

            if ($request->hasFile('image')) {
                $this->deleteManagedImage($workshop->image);
                $payload['image'] = $this->storeImage($request->file('image'));
            }

            $workshop->update($payload);
            $this->syncBenefits($workshop, $validated['benefits'] ?? []);
        });

        $workshop->refresh();

        return $this->successResponse(
            $request,
            'Workshop berhasil diperbarui.',
            route('academic.workshops.edit', $workshop),
            $workshop
        );
    }

    public function destroy(Request $request, Workshop $workshop): JsonResponse|RedirectResponse
    {
        $this->deleteManagedImage($workshop->image);
        $workshop->delete();

        return $this->successResponse(
            $request,
            'Workshop berhasil dihapus.',
            route('academic.workshops.index')
        );
    }

    protected function validateRequest(Request $request, ?int $ignoreId = null): array
    {
        $request->merge([
            'slug' => trim((string) $request->input('slug')) ?: Str::slug((string) $request->input('title')),
        ]);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('workshops', 'slug')->ignore($ignoreId),
            ],
            'badge' => ['nullable', 'string', 'max:255'],
            'short_description' => ['nullable', 'string'],
            'overview' => ['nullable', 'string'],

            'price' => ['required', 'numeric', 'min:0'],
            'old_price' => ['nullable', 'numeric', 'min:0'],

            'rating' => ['required', 'integer', 'min:0', 'max:5'],
            'rating_count' => ['required', 'integer', 'min:0'],

            'duration' => ['nullable', 'string', 'max:255'],
            'level' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'audience' => ['nullable', 'string'],

            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'intro_video_type' => ['required', Rule::in(['youtube', 'upload'])],
            'intro_video_url' => ['nullable', 'string', 'max:2048'],

            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],

            'benefits' => ['nullable', 'array'],
            'benefits.*' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);
        $validated['rating'] = (int) ($validated['rating'] ?? 5);
        $validated['rating_count'] = (int) ($validated['rating_count'] ?? 0);

        return $validated;
    }

    protected function buildPayload(array $validated): array
    {
        return [
            'title' => $validated['title'],
            'slug' => $validated['slug'],
            'badge' => $validated['badge'] ?? null,
            'short_description' => $validated['short_description'] ?? null,
            'overview' => $validated['overview'] ?? null,
            'price' => $validated['price'],
            'old_price' => $validated['old_price'] ?? null,
            'rating' => $validated['rating'],
            'rating_count' => $validated['rating_count'],
            'duration' => $validated['duration'] ?? null,
            'level' => $validated['level'] ?? null,
            'category' => $validated['category'] ?? null,
            'audience' => $validated['audience'] ?? null,
            'intro_video_type' => $validated['intro_video_type'],
            'intro_video_url' => $validated['intro_video_url'] ?? null,
            'is_active' => $validated['is_active'],
            'sort_order' => $validated['sort_order'],
        ];
    }

    protected function syncBenefits(Workshop $workshop, array $benefits): void
    {
        $filteredBenefits = collect($benefits)
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->values();

        $workshop->benefits()->delete();

        foreach ($filteredBenefits as $index => $benefit) {
            $workshop->benefits()->create([
                'content' => $benefit,
                'sort_order' => $index + 1,
            ]);
        }
    }

    protected function storeImage($file): string
    {
        $path = $file->store('workshops', 'public');

        return 'storage/' . $path;
    }

    protected function deleteManagedImage(?string $imagePath): void
    {
        if (!$imagePath) {
            return;
        }

        if (!Str::startsWith($imagePath, 'storage/')) {
            return;
        }

        $relativePath = Str::after($imagePath, 'storage/');

        if ($relativePath && Storage::disk('public')->exists($relativePath)) {
            Storage::disk('public')->delete($relativePath);
        }
    }

    protected function successResponse(
        Request $request,
        string $message,
        string $redirect,
        ?Workshop $workshop = null
    ): JsonResponse|RedirectResponse {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'redirect' => $redirect,
                'data' => $workshop ? [
                    'id' => $workshop->id,
                    'title' => $workshop->title,
                    'slug' => $workshop->slug,
                    'is_active' => $workshop->is_active,
                    'image' => $workshop->image,
                ] : null,
            ]);
        }

        return redirect($redirect)->with('success', $message);
    }
}