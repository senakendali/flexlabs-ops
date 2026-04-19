<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\MarketingSetupAd;
use App\Models\MarketingSetupCampaign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MarketingSetupAdController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $search = trim((string) $request->input('search'));
        $platform = $request->input('platform');
        $status = $request->input('status');
        $campaignId = $request->input('marketing_setup_campaign_id');
        $isActive = $request->input('is_active');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $perPage = (int) $request->input('per_page', 10);

        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;

        $ads = MarketingSetupAd::query()
            ->with(['campaign', 'creator', 'updater'])
            ->withCount(['reportAds'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('ad_name', 'like', '%' . $search . '%')
                        ->orWhere('platform', 'like', '%' . $search . '%')
                        ->orWhere('objective', 'like', '%' . $search . '%')
                        ->orWhere('slug', 'like', '%' . $search . '%');
                });
            })
            ->when(filled($platform), function ($query) use ($platform) {
                $query->where('platform', $platform);
            })
            ->when(filled($status), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when(filled($campaignId), function ($query) use ($campaignId) {
                $query->where('marketing_setup_campaign_id', $campaignId);
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

        $campaignOptions = MarketingSetupCampaign::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $ads,
                'campaign_options' => $campaignOptions,
            ]);
        }

        return view('marketing.setup.ads.index', [
            'ads' => $ads,
            'campaignOptions' => $campaignOptions,
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'marketing_setup_campaign_id' => ['nullable', 'exists:marketing_setup_campaigns,id'],
            'platform' => ['required', 'string', 'max:255'],
            'ad_name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:marketing_setup_ads,slug'],
            'objective' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'total_budget' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['slug'] = $this->generateUniqueSlug(
            $validated['slug'] ?? null,
            $validated['ad_name']
        );

        $validated['total_budget'] = $validated['total_budget'] ?? 0;
        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        $ad = MarketingSetupAd::create($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Ad setup created successfully.',
                'data' => $ad->load(['campaign', 'creator', 'updater']),
            ]);
        }

        return redirect()
            ->route('marketing.setup.ads.index')
            ->with('success', 'Ad setup created successfully.');
    }

    public function update(Request $request, MarketingSetupAd $ad): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'marketing_setup_campaign_id' => ['nullable', 'exists:marketing_setup_campaigns,id'],
            'platform' => ['required', 'string', 'max:255'],
            'ad_name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('marketing_setup_ads', 'slug')->ignore($ad->id),
            ],
            'objective' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'total_budget' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['slug'] = $this->generateUniqueSlug(
            $validated['slug'] ?? null,
            $validated['ad_name'],
            $ad->id
        );

        $validated['total_budget'] = $validated['total_budget'] ?? 0;
        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);
        $validated['updated_by'] = auth()->id();

        $ad->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Ad setup updated successfully.',
                'data' => $ad->fresh(['campaign', 'creator', 'updater']),
            ]);
        }

        return redirect()
            ->route('marketing.setup.ads.index')
            ->with('success', 'Ad setup updated successfully.');
    }

    public function destroy(Request $request, MarketingSetupAd $ad): JsonResponse|RedirectResponse
    {
        $ad->loadCount(['reportAds']);

        if ($ad->report_ads_count > 0) {
            $message = 'Ad setup cannot be deleted because it is already used by reports.';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 422);
            }

            return redirect()
                ->route('marketing.setup.ads.index')
                ->with('error', $message);
        }

        $ad->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Ad setup deleted successfully.',
            ]);
        }

        return redirect()
            ->route('marketing.setup.ads.index')
            ->with('success', 'Ad setup deleted successfully.');
    }

    public function options(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'marketing_setup_campaign_id' => ['nullable', 'exists:marketing_setup_campaigns,id'],
        ]);

        $ads = MarketingSetupAd::query()
            ->active()
            ->overlappingPeriod($validated['start_date'], $validated['end_date'])
            ->when(filled($validated['marketing_setup_campaign_id'] ?? null), function ($query) use ($validated) {
                $query->where('marketing_setup_campaign_id', $validated['marketing_setup_campaign_id']);
            })
            ->with(['campaign'])
            ->orderBy('start_date')
            ->orderBy('ad_name')
            ->get()
            ->map(function (MarketingSetupAd $ad) {
                return [
                    'id' => $ad->id,
                    'marketing_setup_campaign_id' => $ad->marketing_setup_campaign_id,
                    'campaign_name' => optional($ad->campaign)->name,
                    'platform' => $ad->platform,
                    'ad_name' => $ad->ad_name,
                    'slug' => $ad->slug,
                    'objective' => $ad->objective,
                    'start_date' => optional($ad->start_date)->toDateString(),
                    'end_date' => optional($ad->end_date)->toDateString(),
                    'total_budget' => (float) $ad->total_budget,
                    'status' => $ad->status,
                    'notes' => $ad->notes,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $ads,
        ]);
    }

    protected function generateUniqueSlug(?string $slug, string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug(filled($slug) ? $slug : $name);
        $baseSlug = $baseSlug ?: 'ad';

        $finalSlug = $baseSlug;
        $counter = 1;

        while (
            MarketingSetupAd::query()
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