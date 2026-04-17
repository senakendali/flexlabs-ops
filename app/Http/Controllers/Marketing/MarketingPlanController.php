<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\MarketingPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MarketingPlanController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $periodType = $request->input('period_type');
        $perPage = (int) $request->input('per_page', 10);

        $plans = MarketingPlan::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', '%' . $search . '%')
                        ->orWhere('objective', 'like', '%' . $search . '%');
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($periodType, fn ($query) => $query->where('period_type', $periodType))
            ->latest()
            ->paginate(in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10)
            ->withQueryString();

        return view('marketing.plans.index', compact('plans', 'search', 'status', 'periodType', 'perPage'));
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'period_type' => ['required', 'string', 'max:50'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'objective' => ['nullable', 'string', 'max:255'],
            'strategy' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['slug'] = Str::slug($validated['title'] . '-' . Str::random(5));
        $validated['budget'] = $validated['budget'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        MarketingPlan::create($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Marketing plan berhasil ditambahkan.',
            ]);
        }

        return redirect()
            ->route('marketing.plans.index')
            ->with('success', 'Marketing plan berhasil ditambahkan.');
    }

    public function update(Request $request, MarketingPlan $marketingPlan): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'period_type' => ['required', 'string', 'max:50'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'objective' => ['nullable', 'string', 'max:255'],
            'strategy' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['budget'] = $validated['budget'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active', false);
        $validated['updated_by'] = auth()->id();

        if ($marketingPlan->title !== $validated['title']) {
            $validated['slug'] = Str::slug($validated['title'] . '-' . Str::random(5));
        }

        $marketingPlan->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Marketing plan berhasil diperbarui.',
            ]);
        }

        return redirect()
            ->route('marketing.plans.index')
            ->with('success', 'Marketing plan berhasil diperbarui.');
    }

    public function destroy(Request $request, MarketingPlan $marketingPlan): JsonResponse|RedirectResponse
    {
        $marketingPlan->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Marketing plan berhasil dihapus.',
            ]);
        }

        return redirect()
            ->route('marketing.plans.index')
            ->with('success', 'Marketing plan berhasil dihapus.');
    }
}