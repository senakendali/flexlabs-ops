<?php

namespace App\Http\Controllers\Trial;

use App\Http\Controllers\Controller;
use App\Models\TrialClass;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class TrialClassController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->get('per_page', 10);

        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 10;
        }

        $classes = TrialClass::latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('trial.classes.index', compact('classes'));
    }

    public function show(TrialClass $trialClass): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $trialClass,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:trial_classes,slug'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $validated['slug'] = $validated['slug'] ?: Str::slug($validated['name']);

        $originalSlug = $validated['slug'];
        $counter = 1;

        while (TrialClass::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $originalSlug . '-' . $counter++;
        }

        $validated['is_active'] = $validated['status'] === 'active';
        unset($validated['status']);

        $trialClass = TrialClass::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Trial class berhasil ditambahkan.',
            'data' => $trialClass->fresh(),
        ]);
    }

    public function update(Request $request, TrialClass $trialClass): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('trial_classes', 'slug')->ignore($trialClass->id),
            ],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $validated['slug'] = $validated['slug'] ?: Str::slug($validated['name']);

        $originalSlug = $validated['slug'];
        $counter = 1;

        while (
            TrialClass::where('slug', $validated['slug'])
                ->where('id', '!=', $trialClass->id)
                ->exists()
        ) {
            $validated['slug'] = $originalSlug . '-' . $counter++;
        }

        $validated['is_active'] = $validated['status'] === 'active';
        unset($validated['status']);

        $trialClass->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Trial class berhasil diupdate.',
            'data' => $trialClass->fresh(),
        ]);
    }

    public function destroy(TrialClass $trialClass): JsonResponse
    {
        $trialClass->delete();

        return response()->json([
            'success' => true,
            'message' => 'Trial class berhasil dihapus.',
        ]);
    }
}