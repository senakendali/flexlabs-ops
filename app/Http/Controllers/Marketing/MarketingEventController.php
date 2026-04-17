<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\MarketingCampaign;
use App\Models\MarketingEvent;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MarketingEventController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $status = $request->input('status');

        $events = MarketingEvent::query()
            ->with(['campaign', 'pic'])
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('event_type', 'like', '%' . $search . '%')
                    ->orWhere('location', 'like', '%' . $search . '%');
            })
            ->when($status, fn($query) => $query->where('status', $status))
            ->latest('event_date')
            ->paginate(10)
            ->withQueryString();

        $campaigns = MarketingCampaign::query()->orderBy('name')->get();
        $users = User::query()->orderBy('name')->get();

        return view('marketing.events.index', compact('events', 'campaigns', 'users', 'search', 'status'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'marketing_campaign_id' => ['nullable', 'exists:marketing_campaigns,id'],
            'name' => ['required', 'string', 'max:255'],
            'event_type' => ['nullable', 'string', 'max:100'],
            'event_date' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'target_audience' => ['nullable', 'string', 'max:255'],
            'target_participants' => ['nullable', 'integer', 'min:0'],
            'registrants' => ['nullable', 'integer', 'min:0'],
            'attendees' => ['nullable', 'integer', 'min:0'],
            'leads_generated' => ['nullable', 'integer', 'min:0'],
            'conversions' => ['nullable', 'integer', 'min:0'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'pic_user_id' => ['nullable', 'exists:users,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['slug'] = Str::slug($validated['name'] . '-' . Str::random(5));
        $validated['target_participants'] = $validated['target_participants'] ?? 0;
        $validated['registrants'] = $validated['registrants'] ?? 0;
        $validated['attendees'] = $validated['attendees'] ?? 0;
        $validated['leads_generated'] = $validated['leads_generated'] ?? 0;
        $validated['conversions'] = $validated['conversions'] ?? 0;
        $validated['budget'] = $validated['budget'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        MarketingEvent::create($validated);

        return redirect()
            ->route('marketing.events.index')
            ->with('success', 'Event berhasil ditambahkan.');
    }

    public function update(Request $request, MarketingEvent $marketingEvent): RedirectResponse
    {
        $validated = $request->validate([
            'marketing_campaign_id' => ['nullable', 'exists:marketing_campaigns,id'],
            'name' => ['required', 'string', 'max:255'],
            'event_type' => ['nullable', 'string', 'max:100'],
            'event_date' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'target_audience' => ['nullable', 'string', 'max:255'],
            'target_participants' => ['nullable', 'integer', 'min:0'],
            'registrants' => ['nullable', 'integer', 'min:0'],
            'attendees' => ['nullable', 'integer', 'min:0'],
            'leads_generated' => ['nullable', 'integer', 'min:0'],
            'conversions' => ['nullable', 'integer', 'min:0'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'pic_user_id' => ['nullable', 'exists:users,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($marketingEvent->name !== $validated['name']) {
            $validated['slug'] = Str::slug($validated['name'] . '-' . Str::random(5));
        }

        $validated['target_participants'] = $validated['target_participants'] ?? 0;
        $validated['registrants'] = $validated['registrants'] ?? 0;
        $validated['attendees'] = $validated['attendees'] ?? 0;
        $validated['leads_generated'] = $validated['leads_generated'] ?? 0;
        $validated['conversions'] = $validated['conversions'] ?? 0;
        $validated['budget'] = $validated['budget'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active', false);
        $validated['updated_by'] = auth()->id();

        $marketingEvent->update($validated);

        return redirect()
            ->route('marketing.events.index')
            ->with('success', 'Event berhasil diperbarui.');
    }

    public function destroy(MarketingEvent $marketingEvent): RedirectResponse
    {
        $marketingEvent->delete();

        return redirect()
            ->route('marketing.events.index')
            ->with('success', 'Event berhasil dihapus.');
    }
}