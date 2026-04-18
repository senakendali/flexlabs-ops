<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\MarketingActivity;
use App\Models\MarketingCampaign;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MarketingActivityController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $marketingCampaignId = $request->input('marketing_campaign_id');
        $perPage = (int) $request->input('per_page', 10);

        $activities = MarketingActivity::query()
            ->with(['campaign', 'pic'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', '%' . $search . '%')
                        ->orWhere('channel', 'like', '%' . $search . '%')
                        ->orWhere('activity_type', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%');
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($marketingCampaignId, fn ($query) => $query->where('marketing_campaign_id', $marketingCampaignId))
            ->latest('activity_date')
            ->paginate(in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10)
            ->withQueryString();

        $campaigns = MarketingCampaign::query()
            ->orderBy('name')
            ->get();

        $users = User::query()
            ->orderBy('name')
            ->get();

        return view('marketing.activities.index', compact(
            'activities',
            'campaigns',
            'users',
            'search',
            'status',
            'marketingCampaignId',
            'perPage'
        ));
    }

    public function store(Request $request): JsonResponse|RedirectResponse
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

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Activity berhasil ditambahkan.',
            ]);
        }

        return redirect()
            ->route('marketing.activities.index')
            ->with('success', 'Activity berhasil ditambahkan.');
    }

    public function update(Request $request, MarketingActivity $marketingActivity): JsonResponse|RedirectResponse
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

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Activity berhasil diperbarui.',
            ]);
        }

        return redirect()
            ->route('marketing.activities.index')
            ->with('success', 'Activity berhasil diperbarui.');
    }

    public function destroy(Request $request, MarketingActivity $marketingActivity): JsonResponse|RedirectResponse
    {
        $marketingActivity->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Activity berhasil dihapus.',
            ]);
        }

        return redirect()
            ->route('marketing.activities.index')
            ->with('success', 'Activity berhasil dihapus.');
    }
}