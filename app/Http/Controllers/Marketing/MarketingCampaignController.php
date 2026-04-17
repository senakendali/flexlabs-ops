<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\MarketingCampaign;
use App\Models\MarketingPlan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MarketingCampaignController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $status = $request->input('status');

        $campaigns = MarketingCampaign::query()
            ->with(['plan', 'pic'])
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('channel', 'like', '%' . $search . '%')
                    ->orWhere('type', 'like', '%' . $search . '%');
            })
            ->when($status, fn($query) => $query->where('status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $plans = MarketingPlan::query()->orderBy('title')->get();
        $users = User::query()->orderBy('name')->get();

        return view('marketing.campaigns.index', compact('campaigns', 'plans', 'users', 'search', 'status'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'marketing_plan_id' => ['nullable', 'exists:marketing_plans,id'],
            'name' => ['required', 'string', 'max:255'],
            'channel' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'string', 'max:100'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'target_leads' => ['nullable', 'integer', 'min:0'],
            'target_conversions' => ['nullable', 'integer', 'min:0'],
            'actual_leads' => ['nullable', 'integer', 'min:0'],
            'actual_conversions' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'pic_user_id' => ['nullable', 'exists:users,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['slug'] = Str::slug($validated['name'] . '-' . Str::random(5));
        $validated['budget'] = $validated['budget'] ?? 0;
        $validated['target_leads'] = $validated['target_leads'] ?? 0;
        $validated['target_conversions'] = $validated['target_conversions'] ?? 0;
        $validated['actual_leads'] = $validated['actual_leads'] ?? 0;
        $validated['actual_conversions'] = $validated['actual_conversions'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        MarketingCampaign::create($validated);

        return redirect()
            ->route('marketing.campaigns.index')
            ->with('success', 'Campaign berhasil ditambahkan.');
    }

    public function update(Request $request, MarketingCampaign $marketingCampaign): RedirectResponse
    {
        $validated = $request->validate([
            'marketing_plan_id' => ['nullable', 'exists:marketing_plans,id'],
            'name' => ['required', 'string', 'max:255'],
            'channel' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'string', 'max:100'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'target_leads' => ['nullable', 'integer', 'min:0'],
            'target_conversions' => ['nullable', 'integer', 'min:0'],
            'actual_leads' => ['nullable', 'integer', 'min:0'],
            'actual_conversions' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'pic_user_id' => ['nullable', 'exists:users,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($marketingCampaign->name !== $validated['name']) {
            $validated['slug'] = Str::slug($validated['name'] . '-' . Str::random(5));
        }

        $validated['budget'] = $validated['budget'] ?? 0;
        $validated['target_leads'] = $validated['target_leads'] ?? 0;
        $validated['target_conversions'] = $validated['target_conversions'] ?? 0;
        $validated['actual_leads'] = $validated['actual_leads'] ?? 0;
        $validated['actual_conversions'] = $validated['actual_conversions'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active', false);
        $validated['updated_by'] = auth()->id();

        $marketingCampaign->update($validated);

        return redirect()
            ->route('marketing.campaigns.index')
            ->with('success', 'Campaign berhasil diperbarui.');
    }

    public function destroy(MarketingCampaign $marketingCampaign): RedirectResponse
    {
        $marketingCampaign->delete();

        return redirect()
            ->route('marketing.campaigns.index')
            ->with('success', 'Campaign berhasil dihapus.');
    }
}