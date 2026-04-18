<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\MarketingEvent;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MarketingEventController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $eventType = $request->input('event_type');
        $status = $request->input('status');

        $events = MarketingEvent::query()
            ->with(['pic'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('location', 'like', '%' . $search . '%')
                        ->orWhere('target_audience', 'like', '%' . $search . '%')
                        ->orWhere('event_type', 'like', '%' . $search . '%')
                        ->orWhereHas('pic', function ($picQuery) use ($search) {
                            $picQuery->where('name', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when($eventType, fn ($query) => $query->where('event_type', $eventType))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest('event_date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('marketing.events.index', compact('events'));
    }

    public function create(): View
    {
        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('marketing.events.create', compact('users'));
    }

    public function show(MarketingEvent $marketingEvent): View
    {
        $marketingEvent->load(['pic']);

        return view('marketing.events.show', [
            'event' => $marketingEvent,
        ]);
    }

    public function edit(MarketingEvent $marketingEvent): View
    {
        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('marketing.events.edit', [
            'event' => $marketingEvent,
            'users' => $users,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'event_type' => ['required', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'target_audience' => ['nullable', 'string', 'max:255'],
            'target_participants' => ['nullable', 'integer', 'min:0'],
            'registrants' => ['nullable', 'integer', 'min:0'],
            'attendees' => ['nullable', 'integer', 'min:0'],
            'leads_generated' => ['nullable', 'integer', 'min:0'],
            'conversions' => ['nullable', 'integer', 'min:0'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'pic_user_id' => ['nullable', 'exists:users,id'],
        ]);

        $validated['slug'] = $this->generateUniqueSlug($validated['name']);
        $validated['target_participants'] = $validated['target_participants'] ?? 0;
        $validated['registrants'] = $validated['registrants'] ?? 0;
        $validated['attendees'] = $validated['attendees'] ?? 0;
        $validated['leads_generated'] = $validated['leads_generated'] ?? 0;
        $validated['conversions'] = $validated['conversions'] ?? 0;
        $validated['budget'] = $validated['budget'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active');
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        $event = MarketingEvent::create($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Marketing event created successfully.',
                'redirect' => route('events.edit', $event),
            ]);
        }

        return redirect()
            ->route('events.index')
            ->with('success', 'Marketing event created successfully.');
    }

    public function update(Request $request, MarketingEvent $marketingEvent): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'event_type' => ['required', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'target_audience' => ['nullable', 'string', 'max:255'],
            'target_participants' => ['nullable', 'integer', 'min:0'],
            'registrants' => ['nullable', 'integer', 'min:0'],
            'attendees' => ['nullable', 'integer', 'min:0'],
            'leads_generated' => ['nullable', 'integer', 'min:0'],
            'conversions' => ['nullable', 'integer', 'min:0'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'pic_user_id' => ['nullable', 'exists:users,id'],
        ]);

        if ($marketingEvent->name !== $validated['name']) {
            $validated['slug'] = $this->generateUniqueSlug($validated['name'], $marketingEvent->id);
        }

        $validated['target_participants'] = $validated['target_participants'] ?? 0;
        $validated['registrants'] = $validated['registrants'] ?? 0;
        $validated['attendees'] = $validated['attendees'] ?? 0;
        $validated['leads_generated'] = $validated['leads_generated'] ?? 0;
        $validated['conversions'] = $validated['conversions'] ?? 0;
        $validated['budget'] = $validated['budget'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active');
        $validated['updated_by'] = auth()->id();

        $marketingEvent->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Marketing event updated successfully.',
            ]);
        }

        return redirect()
            ->route('events.index')
            ->with('success', 'Marketing event updated successfully.');
    }

    public function destroy(Request $request, MarketingEvent $marketingEvent): RedirectResponse|JsonResponse
    {
        $marketingEvent->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Marketing event deleted successfully.',
            ]);
        }

        return redirect()
            ->route('events.index')
            ->with('success', 'Marketing event deleted successfully.');
    }

    protected function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (
            MarketingEvent::query()
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}