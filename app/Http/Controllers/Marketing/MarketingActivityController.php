<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\MarketingActivity;
use App\Models\MarketingCampaign;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketingActivityController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $status = $request->input('status');

        $activities = MarketingActivity::query()
            ->with(['campaign', 'pic'])
            ->when($search, function ($query) use ($search) {
                $query->where('title', 'like', '%' . $search . '%')
                    ->orWhere('channel', 'like', '%' . $search . '%')
                    ->orWhere('activity_type', 'like', '%' . $search . '%');
            })
            ->when($status, fn($query) => $query->where('status', $status))
            ->latest('activity_date')
            ->paginate(10)
            ->withQueryString();

        $campaigns = MarketingCampaign::query()->orderBy('name')->get();
        $users = User::query()->orderBy('name')->get();

        return view('marketing.activities.index', compact('activities', 'campaigns', 'users', 'search', 'status'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'marketing_campaign_id' => ['nullable', 'exists:marketing_campaigns,id'],
            'activity_date' => ['required', 'date'],
            'channel' => ['nullable', 'string', 'max:100'],
            'activity_type' => ['nullable', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', 'max:50'],
            'output_link' => ['nullable', 'url', 'max:255'],
            'pic_user_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        MarketingActivity::create($validated);

        return redirect()
            ->route('marketing.activities.index')
            ->with('success', 'Activity berhasil ditambahkan.');
    }

    public function update(Request $request, MarketingActivity $marketingActivity): RedirectResponse
    {
        $validated = $request->validate([
            'marketing_campaign_id' => ['nullable', 'exists:marketing_campaigns,id'],
            'activity_date' => ['required', 'date'],
            'channel' => ['nullable', 'string', 'max:100'],
            'activity_type' => ['nullable', 'string', 'max:100'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', 'max:50'],
            'output_link' => ['nullable', 'url', 'max:255'],
            'pic_user_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', false);
        $validated['updated_by'] = auth()->id();

        $marketingActivity->update($validated);

        return redirect()
            ->route('marketing.activities.index')
            ->with('success', 'Activity berhasil diperbarui.');
    }

    public function destroy(MarketingActivity $marketingActivity): RedirectResponse
    {
        $marketingActivity->delete();

        return redirect()
            ->route('marketing.activities.index')
            ->with('success', 'Activity berhasil dihapus.');
    }
}