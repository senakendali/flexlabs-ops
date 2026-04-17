<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\MarketingAd;
use App\Models\MarketingCampaign;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketingAdController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $status = $request->input('status');

        $ads = MarketingAd::query()
            ->with(['campaign', 'pic'])
            ->when($search, function ($query) use ($search) {
                $query->where('campaign_name', 'like', '%' . $search . '%')
                    ->orWhere('ad_name', 'like', '%' . $search . '%')
                    ->orWhere('platform', 'like', '%' . $search . '%');
            })
            ->when($status, fn($query) => $query->where('status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $campaigns = MarketingCampaign::query()->orderBy('name')->get();
        $users = User::query()->orderBy('name')->get();

        return view('marketing.ads.index', compact('ads', 'campaigns', 'users', 'search', 'status'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'marketing_campaign_id' => ['nullable', 'exists:marketing_campaigns,id'],
            'platform' => ['required', 'string', 'max:100'],
            'campaign_name' => ['required', 'string', 'max:255'],
            'ad_name' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'spend' => ['nullable', 'numeric', 'min:0'],
            'impressions' => ['nullable', 'integer', 'min:0'],
            'clicks' => ['nullable', 'integer', 'min:0'],
            'leads' => ['nullable', 'integer', 'min:0'],
            'conversions' => ['nullable', 'integer', 'min:0'],
            'cost_per_click' => ['nullable', 'numeric', 'min:0'],
            'cost_per_lead' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'pic_user_id' => ['nullable', 'exists:users,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['budget'] = $validated['budget'] ?? 0;
        $validated['spend'] = $validated['spend'] ?? 0;
        $validated['impressions'] = $validated['impressions'] ?? 0;
        $validated['clicks'] = $validated['clicks'] ?? 0;
        $validated['leads'] = $validated['leads'] ?? 0;
        $validated['conversions'] = $validated['conversions'] ?? 0;
        $validated['cost_per_click'] = $validated['cost_per_click'] ?? 0;
        $validated['cost_per_lead'] = $validated['cost_per_lead'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        MarketingAd::create($validated);

        return redirect()
            ->route('marketing.ads.index')
            ->with('success', 'Ads data berhasil ditambahkan.');
    }

    public function update(Request $request, MarketingAd $marketingAd): RedirectResponse
    {
        $validated = $request->validate([
            'marketing_campaign_id' => ['nullable', 'exists:marketing_campaigns,id'],
            'platform' => ['required', 'string', 'max:100'],
            'campaign_name' => ['required', 'string', 'max:255'],
            'ad_name' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'spend' => ['nullable', 'numeric', 'min:0'],
            'impressions' => ['nullable', 'integer', 'min:0'],
            'clicks' => ['nullable', 'integer', 'min:0'],
            'leads' => ['nullable', 'integer', 'min:0'],
            'conversions' => ['nullable', 'integer', 'min:0'],
            'cost_per_click' => ['nullable', 'numeric', 'min:0'],
            'cost_per_lead' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'pic_user_id' => ['nullable', 'exists:users,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['budget'] = $validated['budget'] ?? 0;
        $validated['spend'] = $validated['spend'] ?? 0;
        $validated['impressions'] = $validated['impressions'] ?? 0;
        $validated['clicks'] = $validated['clicks'] ?? 0;
        $validated['leads'] = $validated['leads'] ?? 0;
        $validated['conversions'] = $validated['conversions'] ?? 0;
        $validated['cost_per_click'] = $validated['cost_per_click'] ?? 0;
        $validated['cost_per_lead'] = $validated['cost_per_lead'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active', false);
        $validated['updated_by'] = auth()->id();

        $marketingAd->update($validated);

        return redirect()
            ->route('marketing.ads.index')
            ->with('success', 'Ads data berhasil diperbarui.');
    }

    public function destroy(MarketingAd $marketingAd): RedirectResponse
    {
        $marketingAd->delete();

        return redirect()
            ->route('marketing.ads.index')
            ->with('success', 'Ads data berhasil dihapus.');
    }
}