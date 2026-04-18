<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\MarketingAd;
use App\Models\MarketingCampaign;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MarketingAdController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $platform = $request->input('platform');

        $ads = MarketingAd::query()
            ->with(['campaign', 'pic'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('ad_name', 'like', '%' . $search . '%')
                        ->orWhere('platform', 'like', '%' . $search . '%')
                        ->orWhere('utm_campaign', 'like', '%' . $search . '%')
                        ->orWhere('utm_content', 'like', '%' . $search . '%')
                        ->orWhereHas('campaign', function ($campaignQuery) use ($search) {
                            $campaignQuery->where('name', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($platform, fn ($query) => $query->where('platform', $platform))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $campaigns = MarketingCampaign::query()
            ->orderBy('name')
            ->get();

        $users = User::query()
            ->orderBy('name')
            ->get();

        return view('marketing.ads.index', compact(
            'ads',
            'campaigns',
            'users',
            'search',
            'status',
            'platform'
        ));
    }

    public function show(MarketingAd $marketingAd): JsonResponse
    {
        $marketingAd->load(['campaign', 'pic', 'creator', 'updater']);

        return response()->json([
            'message' => 'Ads detail berhasil diambil.',
            'data' => [
                'id' => $marketingAd->id,
                'marketing_campaign_id' => $marketingAd->marketing_campaign_id,
                'campaign_name' => $marketingAd->campaign?->name,
                'platform' => $marketingAd->platform,
                'platform_label' => $marketingAd->platform_label,
                'ad_name' => $marketingAd->ad_name,
                'start_date' => $marketingAd->start_date?->format('Y-m-d'),
                'end_date' => $marketingAd->end_date?->format('Y-m-d'),
                'budget' => (float) $marketingAd->budget,
                'spend' => (float) $marketingAd->spend,
                'impressions' => (int) $marketingAd->impressions,
                'clicks' => (int) $marketingAd->clicks,
                'leads' => (int) $marketingAd->leads,
                'conversions' => (int) $marketingAd->conversions,
                'ctr' => $marketingAd->ctr,
                'cpc' => $marketingAd->cpc,
                'cpl' => $marketingAd->cpl,
                'conversion_rate' => $marketingAd->conversion_rate,
                'duration_days' => $marketingAd->duration_days,
                'status' => $marketingAd->status,
                'notes' => $marketingAd->notes,
                'is_active' => (bool) $marketingAd->is_active,
                'source_type' => $marketingAd->source_type,
                'source_type_label' => $marketingAd->source_type_label,
                'external_reference' => $marketingAd->external_reference,
                'utm_source' => $marketingAd->utm_source,
                'utm_campaign' => $marketingAd->utm_campaign,
                'utm_content' => $marketingAd->utm_content,
                'pic_user_id' => $marketingAd->pic_user_id,
                'pic_name' => $marketingAd->pic?->name,
                'created_by' => $marketingAd->created_by,
                'created_by_name' => $marketingAd->creator?->name,
                'updated_by' => $marketingAd->updated_by,
                'updated_by_name' => $marketingAd->updater?->name,
                'created_at' => $marketingAd->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $marketingAd->updated_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateRequest($request);

        $validated['budget'] = $validated['budget'] ?? 0;
        $validated['spend'] = $validated['spend'] ?? 0;
        $validated['impressions'] = $validated['impressions'] ?? 0;
        $validated['clicks'] = $validated['clicks'] ?? 0;
        $validated['leads'] = $validated['leads'] ?? 0;
        $validated['conversions'] = $validated['conversions'] ?? 0;
        $validated['source_type'] = $validated['source_type'] ?? 'manual';
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        $marketingAd = MarketingAd::create($validated);
        $marketingAd->load(['campaign', 'pic']);

        return response()->json([
            'message' => 'Ads data berhasil ditambahkan.',
            'data' => [
                'id' => $marketingAd->id,
                'campaign_name' => $marketingAd->campaign?->name,
                'platform' => $marketingAd->platform,
                'platform_label' => $marketingAd->platform_label,
                'ad_name' => $marketingAd->ad_name,
                'status' => $marketingAd->status,
                'is_active' => (bool) $marketingAd->is_active,
                'ctr' => $marketingAd->ctr,
                'cpc' => $marketingAd->cpc,
                'cpl' => $marketingAd->cpl,
                'conversion_rate' => $marketingAd->conversion_rate,
            ],
        ], 201);
    }

    public function update(Request $request, MarketingAd $marketingAd): JsonResponse
    {
        $validated = $this->validateRequest($request);

        $validated['budget'] = $validated['budget'] ?? 0;
        $validated['spend'] = $validated['spend'] ?? 0;
        $validated['impressions'] = $validated['impressions'] ?? 0;
        $validated['clicks'] = $validated['clicks'] ?? 0;
        $validated['leads'] = $validated['leads'] ?? 0;
        $validated['conversions'] = $validated['conversions'] ?? 0;
        $validated['source_type'] = $validated['source_type'] ?? ($marketingAd->source_type ?: 'manual');
        $validated['is_active'] = $request->boolean('is_active', false);
        $validated['updated_by'] = auth()->id();

        $marketingAd->update($validated);
        $marketingAd->load(['campaign', 'pic']);

        return response()->json([
            'message' => 'Ads data berhasil diperbarui.',
            'data' => [
                'id' => $marketingAd->id,
                'campaign_name' => $marketingAd->campaign?->name,
                'platform' => $marketingAd->platform,
                'platform_label' => $marketingAd->platform_label,
                'ad_name' => $marketingAd->ad_name,
                'status' => $marketingAd->status,
                'is_active' => (bool) $marketingAd->is_active,
                'ctr' => $marketingAd->ctr,
                'cpc' => $marketingAd->cpc,
                'cpl' => $marketingAd->cpl,
                'conversion_rate' => $marketingAd->conversion_rate,
            ],
        ]);
    }

    public function destroy(MarketingAd $marketingAd): JsonResponse
    {
        $marketingAd->delete();

        return response()->json([
            'message' => 'Ads data berhasil dihapus.',
        ]);
    }

    protected function validateRequest(Request $request): array
    {
        $validated = $request->validate([
            'marketing_campaign_id' => ['nullable', 'exists:marketing_campaigns,id'],
            'platform' => [
                'required',
                'string',
                Rule::in(['meta_ads', 'tiktok_ads', 'google_ads']),
            ],
            'ad_name' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'spend' => ['nullable', 'numeric', 'min:0'],
            'impressions' => ['nullable', 'integer', 'min:0'],
            'clicks' => ['nullable', 'integer', 'min:0'],
            'leads' => ['nullable', 'integer', 'min:0'],
            'conversions' => ['nullable', 'integer', 'min:0'],
            'status' => [
                'required',
                'string',
                Rule::in(['draft', 'active', 'paused', 'completed']),
            ],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'pic_user_id' => ['nullable', 'exists:users,id'],

            'source_type' => [
                'nullable',
                'string',
                Rule::in(['manual', 'kommo_sync', 'ads_api']),
            ],
            'external_reference' => ['nullable', 'string', 'max:255'],
            'utm_source' => ['nullable', 'string', 'max:255'],
            'utm_campaign' => ['nullable', 'string', 'max:255'],
            'utm_content' => ['nullable', 'string', 'max:255'],
        ]);

        if (!empty($validated['marketing_campaign_id']) && (!empty($validated['start_date']) || !empty($validated['end_date']))) {
            $campaign = MarketingCampaign::query()->find($validated['marketing_campaign_id']);

            if ($campaign) {
                if (
                    !empty($validated['start_date']) &&
                    $campaign->start_date &&
                    $validated['start_date'] < $campaign->start_date->format('Y-m-d')
                ) {
                    abort(response()->json([
                        'message' => 'Tanggal mulai ads tidak boleh lebih awal dari tanggal mulai campaign.',
                        'errors' => [
                            'start_date' => ['Tanggal mulai ads tidak boleh lebih awal dari tanggal mulai campaign.'],
                        ],
                    ], 422));
                }

                if (
                    !empty($validated['end_date']) &&
                    $campaign->end_date &&
                    $validated['end_date'] > $campaign->end_date->format('Y-m-d')
                ) {
                    abort(response()->json([
                        'message' => 'Tanggal selesai ads tidak boleh melebihi tanggal selesai campaign.',
                        'errors' => [
                            'end_date' => ['Tanggal selesai ads tidak boleh melebihi tanggal selesai campaign.'],
                        ],
                    ], 422));
                }
            }
        }

        return $validated;
    }
}