<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\MarketingCampaign;
use App\Models\MarketingEvent;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MarketingEventController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $eventType = $request->input('event_type');

        $events = MarketingEvent::query()
            ->with(['campaign', 'pic'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('name', 'like', '%' . $search . '%')
                        ->orWhere('event_type', 'like', '%' . $search . '%')
                        ->orWhere('location', 'like', '%' . $search . '%')
                        ->orWhere('target_audience', 'like', '%' . $search . '%')
                        ->orWhereHas('campaign', function ($campaignQuery) use ($search) {
                            $campaignQuery->where('name', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($eventType, fn ($query) => $query->where('event_type', $eventType))
            ->latest('event_date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $campaigns = MarketingCampaign::query()
            ->orderBy('name')
            ->get();

        $users = User::query()
            ->orderBy('name')
            ->get();

        return view('marketing.events.index', compact(
            'events',
            'campaigns',
            'users',
            'search',
            'status',
            'eventType'
        ));
    }

    public function show(MarketingEvent $marketingEvent): JsonResponse
    {
        $marketingEvent->load(['campaign', 'pic', 'creator', 'updater']);

        return response()->json([
            'message' => 'Detail event berhasil diambil.',
            'data' => [
                'id' => $marketingEvent->id,
                'marketing_campaign_id' => $marketingEvent->marketing_campaign_id,
                'campaign_name' => $marketingEvent->campaign?->name,
                'name' => $marketingEvent->name,
                'slug' => $marketingEvent->slug,
                'event_type' => $marketingEvent->event_type,
                'event_type_label' => $marketingEvent->event_type_label,
                'event_date' => $marketingEvent->event_date?->format('Y-m-d'),
                'location' => $marketingEvent->location,
                'target_audience' => $marketingEvent->target_audience,
                'target_participants' => (int) $marketingEvent->target_participants,
                'registrants' => (int) $marketingEvent->registrants,
                'attendees' => (int) $marketingEvent->attendees,
                'leads_generated' => (int) $marketingEvent->leads_generated,
                'conversions' => (int) $marketingEvent->conversions,
                'budget' => (float) $marketingEvent->budget,
                'attendance_rate' => $marketingEvent->attendance_rate,
                'registration_rate' => $marketingEvent->registration_rate,
                'conversion_rate' => $marketingEvent->conversion_rate,
                'status' => $marketingEvent->status,
                'description' => $marketingEvent->description,
                'notes' => $marketingEvent->notes,
                'is_active' => (bool) $marketingEvent->is_active,
                'pic_user_id' => $marketingEvent->pic_user_id,
                'pic_name' => $marketingEvent->pic?->name,
                'created_by' => $marketingEvent->created_by,
                'created_by_name' => $marketingEvent->creator?->name,
                'updated_by' => $marketingEvent->updated_by,
                'updated_by_name' => $marketingEvent->updater?->name,
                'created_at' => $marketingEvent->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $marketingEvent->updated_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateRequest($request);

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

        $marketingEvent = MarketingEvent::create($validated);
        $marketingEvent->load(['campaign', 'pic']);

        return response()->json([
            'message' => 'Event berhasil ditambahkan.',
            'data' => [
                'id' => $marketingEvent->id,
                'name' => $marketingEvent->name,
                'campaign_name' => $marketingEvent->campaign?->name,
                'event_type' => $marketingEvent->event_type,
                'event_type_label' => $marketingEvent->event_type_label,
                'event_date' => $marketingEvent->event_date?->format('Y-m-d'),
                'status' => $marketingEvent->status,
                'is_active' => (bool) $marketingEvent->is_active,
                'attendance_rate' => $marketingEvent->attendance_rate,
                'registration_rate' => $marketingEvent->registration_rate,
                'conversion_rate' => $marketingEvent->conversion_rate,
            ],
        ], 201);
    }

    public function update(Request $request, MarketingEvent $marketingEvent): JsonResponse
    {
        $validated = $this->validateRequest($request);

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
        $marketingEvent->load(['campaign', 'pic']);

        return response()->json([
            'message' => 'Event berhasil diperbarui.',
            'data' => [
                'id' => $marketingEvent->id,
                'name' => $marketingEvent->name,
                'campaign_name' => $marketingEvent->campaign?->name,
                'event_type' => $marketingEvent->event_type,
                'event_type_label' => $marketingEvent->event_type_label,
                'event_date' => $marketingEvent->event_date?->format('Y-m-d'),
                'status' => $marketingEvent->status,
                'is_active' => (bool) $marketingEvent->is_active,
                'attendance_rate' => $marketingEvent->attendance_rate,
                'registration_rate' => $marketingEvent->registration_rate,
                'conversion_rate' => $marketingEvent->conversion_rate,
            ],
        ]);
    }

    public function destroy(MarketingEvent $marketingEvent): JsonResponse
    {
        $marketingEvent->delete();

        return response()->json([
            'message' => 'Event berhasil dihapus.',
        ]);
    }

    protected function validateRequest(Request $request): array
    {
        $validated = $request->validate([
            'marketing_campaign_id' => ['nullable', 'exists:marketing_campaigns,id'],
            'name' => ['required', 'string', 'max:255'],
            'event_type' => [
                'required',
                'string',
                Rule::in([
                    'workshop',
                    'webinar',
                    'expo',
                    'school_visit',
                    'booth',
                    'community_event',
                    'internal_event',
                    'other',
                ]),
            ],
            'event_date' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'target_audience' => ['nullable', 'string', 'max:255'],
            'target_participants' => ['nullable', 'integer', 'min:0'],
            'registrants' => ['nullable', 'integer', 'min:0'],
            'attendees' => ['nullable', 'integer', 'min:0'],
            'leads_generated' => ['nullable', 'integer', 'min:0'],
            'conversions' => ['nullable', 'integer', 'min:0'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'status' => [
                'required',
                'string',
                Rule::in(['draft', 'planned', 'ongoing', 'completed', 'cancelled']),
            ],
            'description' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'pic_user_id' => ['nullable', 'exists:users,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (
            isset($validated['registrants'], $validated['target_participants']) &&
            (int) $validated['registrants'] > (int) $validated['target_participants'] &&
            (int) $validated['target_participants'] > 0
        ) {
            abort(response()->json([
                'message' => 'Jumlah registrants tidak boleh melebihi target participants.',
                'errors' => [
                    'registrants' => ['Jumlah registrants tidak boleh melebihi target participants.'],
                ],
            ], 422));
        }

        if (
            isset($validated['attendees'], $validated['registrants']) &&
            (int) $validated['attendees'] > (int) $validated['registrants'] &&
            (int) $validated['registrants'] > 0
        ) {
            abort(response()->json([
                'message' => 'Jumlah attendees tidak boleh melebihi registrants.',
                'errors' => [
                    'attendees' => ['Jumlah attendees tidak boleh melebihi registrants.'],
                ],
            ], 422));
        }

        if (
            isset($validated['conversions'], $validated['leads_generated']) &&
            (int) $validated['conversions'] > (int) $validated['leads_generated'] &&
            (int) $validated['leads_generated'] > 0
        ) {
            abort(response()->json([
                'message' => 'Jumlah conversions tidak boleh melebihi leads generated.',
                'errors' => [
                    'conversions' => ['Jumlah conversions tidak boleh melebihi leads generated.'],
                ],
            ], 422));
        }

        return $validated;
    }
}