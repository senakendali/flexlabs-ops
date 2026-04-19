<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\MarketingSetupCampaign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MarketingSetupCampaignController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $search = trim((string) $request->input('search'));
        $status = $request->input('status');
        $isActive = $request->input('is_active');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $perPage = (int) $request->input('per_page', 10);

        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;

        $campaigns = MarketingSetupCampaign::query()
            ->with(['pic', 'creator', 'updater'])
            ->withCount(['ads', 'reportCampaigns'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('objective', 'like', '%' . $search . '%')
                        ->orWhere('owner_name', 'like', '%' . $search . '%')
                        ->orWhere('slug', 'like', '%' . $search . '%');
                });
            })
            ->when(filled($status), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($isActive !== null && $isActive !== '', function ($query) use ($isActive) {
                $query->where('is_active', (bool) $isActive);
            })
            ->when($dateFrom, function ($query) use ($dateFrom) {
                $query->whereDate('end_date', '>=', $dateFrom);
            })
            ->when($dateTo, function ($query) use ($dateTo) {
                $query->whereDate('start_date', '<=', $dateTo);
            })
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $campaigns,
            ]);
        }

        return view('marketing.setup.campaigns.index', compact('campaigns'));
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:marketing_setup_campaigns,slug'],
            'objective' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'total_budget' => ['nullable', 'numeric', 'min:0'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'pic_user_id' => ['nullable', 'exists:users,id'],
            'status' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['slug'] = $this->generateUniqueSlug(
            $validated['slug'] ?? null,
            $validated['name']
        );

        $validated['total_budget'] = $validated['total_budget'] ?? 0;
        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        $campaign = MarketingSetupCampaign::create($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Campaign setup created successfully.',
                'data' => $campaign->load(['pic', 'creator', 'updater']),
            ]);
        }

        return redirect()
            ->route('marketing.setup.campaigns.index')
            ->with('success', 'Campaign setup created successfully.');
    }

    public function update(Request $request, MarketingSetupCampaign $campaign): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('marketing_setup_campaigns', 'slug')->ignore($campaign->id),
            ],
            'objective' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'total_budget' => ['nullable', 'numeric', 'min:0'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'pic_user_id' => ['nullable', 'exists:users,id'],
            'status' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['slug'] = $this->generateUniqueSlug(
            $validated['slug'] ?? null,
            $validated['name'],
            $campaign->id
        );

        $validated['total_budget'] = $validated['total_budget'] ?? 0;
        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);
        $validated['updated_by'] = auth()->id();

        $campaign->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Campaign setup updated successfully.',
                'data' => $campaign->fresh(['pic', 'creator', 'updater']),
            ]);
        }

        return redirect()
            ->route('marketing.setup.campaigns.index')
            ->with('success', 'Campaign setup updated successfully.');
    }

    public function destroy(Request $request, MarketingSetupCampaign $campaign): JsonResponse|RedirectResponse
    {
        $campaign->loadCount(['ads', 'reportCampaigns']);

        if ($campaign->ads_count > 0 || $campaign->report_campaigns_count > 0) {
            $message = 'Campaign setup cannot be deleted because it is already used by ads or reports.';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 422);
            }

            return redirect()
                ->route('marketing.setup.campaigns.index')
                ->with('error', $message);
        }

        $campaign->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Campaign setup deleted successfully.',
            ]);
        }

        return redirect()
            ->route('marketing.setup.campaigns.index')
            ->with('success', 'Campaign setup deleted successfully.');
    }

    public function options(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $campaigns = MarketingSetupCampaign::query()
            ->active()
            ->overlappingPeriod($validated['start_date'], $validated['end_date'])
            ->with(['pic'])
            ->orderBy('start_date')
            ->orderBy('name')
            ->get()
            ->map(function (MarketingSetupCampaign $campaign) {
                return [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'slug' => $campaign->slug,
                    'objective' => $campaign->objective,
                    'start_date' => optional($campaign->start_date)->toDateString(),
                    'end_date' => optional($campaign->end_date)->toDateString(),
                    'total_budget' => (float) $campaign->total_budget,
                    'owner_name' => $campaign->owner_name,
                    'pic_user_id' => $campaign->pic_user_id,
                    'pic_name' => optional($campaign->pic)->name,
                    'status' => $campaign->status,
                    'notes' => $campaign->notes,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $campaigns,
        ]);
    }

    protected function generateUniqueSlug(?string $slug, string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug(filled($slug) ? $slug : $name);
        $baseSlug = $baseSlug ?: 'campaign';

        $finalSlug = $baseSlug;
        $counter = 1;

        while (
            MarketingSetupCampaign::query()
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('slug', $finalSlug)
                ->exists()
        ) {
            $finalSlug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $finalSlug;
    }
}