<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\MarketingCampaign;
use App\Models\MarketingEvent;
use App\Models\MarketingLeadSource;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketingLeadSourceController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $status = $request->input('status');

        $leads = MarketingLeadSource::query()
            ->with(['campaign', 'event', 'assignee'])
            ->when($search, function ($query) use ($search) {
                $query->where('lead_name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%')
                    ->orWhere('source', 'like', '%' . $search . '%');
            })
            ->when($status, fn($query) => $query->where('status', $status))
            ->latest('lead_date')
            ->paginate(10)
            ->withQueryString();

        $campaigns = MarketingCampaign::query()->orderBy('name')->get();
        $events = MarketingEvent::query()->orderBy('name')->get();
        $users = User::query()->orderBy('name')->get();

        return view('marketing.leads.index', compact('leads', 'campaigns', 'events', 'users', 'search', 'status'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'marketing_campaign_id' => ['nullable', 'exists:marketing_campaigns,id'],
            'marketing_event_id' => ['nullable', 'exists:marketing_events,id'],
            'lead_date' => ['nullable', 'date'],
            'lead_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'source' => ['required', 'string', 'max:100'],
            'source_detail' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        MarketingLeadSource::create($validated);

        return redirect()
            ->route('marketing.leads.index')
            ->with('success', 'Lead source berhasil ditambahkan.');
    }

    public function update(Request $request, MarketingLeadSource $marketingLeadSource): RedirectResponse
    {
        $validated = $request->validate([
            'marketing_campaign_id' => ['nullable', 'exists:marketing_campaigns,id'],
            'marketing_event_id' => ['nullable', 'exists:marketing_events,id'],
            'lead_date' => ['nullable', 'date'],
            'lead_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'source' => ['required', 'string', 'max:100'],
            'source_detail' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', false);
        $validated['updated_by'] = auth()->id();

        $marketingLeadSource->update($validated);

        return redirect()
            ->route('marketing.leads.index')
            ->with('success', 'Lead source berhasil diperbarui.');
    }

    public function destroy(MarketingLeadSource $marketingLeadSource): RedirectResponse
    {
        $marketingLeadSource->delete();

        return redirect()
            ->route('marketing.leads.index')
            ->with('success', 'Lead source berhasil dihapus.');
    }
}