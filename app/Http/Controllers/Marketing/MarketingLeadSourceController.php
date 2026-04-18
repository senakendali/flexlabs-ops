<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\MarketingCampaign;
use App\Models\MarketingEvent;
use App\Models\MarketingLeadSource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MarketingLeadSourceController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $sourceType = $request->input('source_type');

        $leads = MarketingLeadSource::query()
            ->with(['campaign', 'event', 'creator', 'updater'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('source_type', 'like', '%' . $search . '%')
                        ->orWhere('source_name', 'like', '%' . $search . '%')
                        ->orWhereHas('campaign', function ($campaignQuery) use ($search) {
                            $campaignQuery->where('name', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('event', function ($eventQuery) use ($search) {
                            $eventQuery->where('name', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when($sourceType, fn ($query) => $query->where('source_type', $sourceType))
            ->latest('date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $campaigns = MarketingCampaign::query()
            ->orderBy('name')
            ->get();

        $events = MarketingEvent::query()
            ->orderBy('name')
            ->get();

        return view('marketing.leads.index', compact(
            'leads',
            'campaigns',
            'events',
            'search',
            'sourceType'
        ));
    }

    public function show(MarketingLeadSource $marketingLeadSource): JsonResponse
    {
        $marketingLeadSource->load(['campaign', 'event', 'creator', 'updater']);

        return response()->json([
            'message' => 'Lead source detail berhasil diambil.',
            'data' => [
                'id' => $marketingLeadSource->id,
                'marketing_campaign_id' => $marketingLeadSource->marketing_campaign_id,
                'campaign_name' => $marketingLeadSource->campaign?->name,
                'marketing_event_id' => $marketingLeadSource->marketing_event_id,
                'event_name' => $marketingLeadSource->event?->name,
                'date' => $marketingLeadSource->date?->format('Y-m-d'),
                'source_type' => $marketingLeadSource->source_type,
                'source_type_label' => $marketingLeadSource->source_type_label,
                'source_name' => $marketingLeadSource->source_name,
                'leads' => (int) $marketingLeadSource->leads,
                'qualified_leads' => (int) $marketingLeadSource->qualified_leads,
                'conversions' => (int) $marketingLeadSource->conversions,
                'revenue' => (float) $marketingLeadSource->revenue,
                'qualification_rate' => $marketingLeadSource->qualification_rate,
                'conversion_rate' => $marketingLeadSource->conversion_rate,
                'notes' => $marketingLeadSource->notes,
                'is_active' => (bool) $marketingLeadSource->is_active,
                'created_by' => $marketingLeadSource->created_by,
                'created_by_name' => $marketingLeadSource->creator?->name,
                'updated_by' => $marketingLeadSource->updated_by,
                'updated_by_name' => $marketingLeadSource->updater?->name,
                'created_at' => $marketingLeadSource->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $marketingLeadSource->updated_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateRequest($request);

        $validated['leads'] = $validated['leads'] ?? 0;
        $validated['qualified_leads'] = $validated['qualified_leads'] ?? 0;
        $validated['conversions'] = $validated['conversions'] ?? 0;
        $validated['revenue'] = $validated['revenue'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        $marketingLeadSource = MarketingLeadSource::create($validated);
        $marketingLeadSource->load(['campaign', 'event']);

        return response()->json([
            'message' => 'Lead source summary berhasil ditambahkan.',
            'data' => [
                'id' => $marketingLeadSource->id,
                'source_type' => $marketingLeadSource->source_type,
                'source_type_label' => $marketingLeadSource->source_type_label,
                'source_name' => $marketingLeadSource->source_name,
                'campaign_name' => $marketingLeadSource->campaign?->name,
                'event_name' => $marketingLeadSource->event?->name,
                'date' => $marketingLeadSource->date?->format('Y-m-d'),
                'leads' => $marketingLeadSource->leads,
                'qualified_leads' => $marketingLeadSource->qualified_leads,
                'conversions' => $marketingLeadSource->conversions,
                'revenue' => (float) $marketingLeadSource->revenue,
                'qualification_rate' => $marketingLeadSource->qualification_rate,
                'conversion_rate' => $marketingLeadSource->conversion_rate,
            ],
        ], 201);
    }

    public function update(Request $request, MarketingLeadSource $marketingLeadSource): JsonResponse
    {
        $validated = $this->validateRequest($request);

        $validated['leads'] = $validated['leads'] ?? 0;
        $validated['qualified_leads'] = $validated['qualified_leads'] ?? 0;
        $validated['conversions'] = $validated['conversions'] ?? 0;
        $validated['revenue'] = $validated['revenue'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active', false);
        $validated['updated_by'] = auth()->id();

        $marketingLeadSource->update($validated);
        $marketingLeadSource->load(['campaign', 'event']);

        return response()->json([
            'message' => 'Lead source summary berhasil diperbarui.',
            'data' => [
                'id' => $marketingLeadSource->id,
                'source_type' => $marketingLeadSource->source_type,
                'source_type_label' => $marketingLeadSource->source_type_label,
                'source_name' => $marketingLeadSource->source_name,
                'campaign_name' => $marketingLeadSource->campaign?->name,
                'event_name' => $marketingLeadSource->event?->name,
                'date' => $marketingLeadSource->date?->format('Y-m-d'),
                'leads' => $marketingLeadSource->leads,
                'qualified_leads' => $marketingLeadSource->qualified_leads,
                'conversions' => $marketingLeadSource->conversions,
                'revenue' => (float) $marketingLeadSource->revenue,
                'qualification_rate' => $marketingLeadSource->qualification_rate,
                'conversion_rate' => $marketingLeadSource->conversion_rate,
            ],
        ]);
    }

    public function destroy(MarketingLeadSource $marketingLeadSource): JsonResponse
    {
        $marketingLeadSource->delete();

        return response()->json([
            'message' => 'Lead source summary berhasil dihapus.',
        ]);
    }

    protected function validateRequest(Request $request): array
    {
        $validated = $request->validate([
            'marketing_campaign_id' => ['nullable', 'exists:marketing_campaigns,id'],
            'marketing_event_id' => ['nullable', 'exists:marketing_events,id'],
            'date' => ['nullable', 'date'],
            'source_type' => [
                'required',
                'string',
                Rule::in(['ads', 'event', 'organic', 'referral', 'direct', 'partnership']),
            ],
            'source_name' => ['nullable', 'string', 'max:255'],
            'leads' => ['nullable', 'integer', 'min:0'],
            'qualified_leads' => ['nullable', 'integer', 'min:0'],
            'conversions' => ['nullable', 'integer', 'min:0'],
            'revenue' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (
            isset($validated['qualified_leads'], $validated['leads']) &&
            (int) $validated['qualified_leads'] > (int) $validated['leads'] &&
            (int) $validated['leads'] > 0
        ) {
            abort(response()->json([
                'message' => 'Jumlah qualified leads tidak boleh melebihi leads.',
                'errors' => [
                    'qualified_leads' => ['Jumlah qualified leads tidak boleh melebihi leads.'],
                ],
            ], 422));
        }

        if (
            isset($validated['conversions'], $validated['qualified_leads']) &&
            (int) $validated['conversions'] > (int) $validated['qualified_leads'] &&
            (int) $validated['qualified_leads'] > 0
        ) {
            abort(response()->json([
                'message' => 'Jumlah conversions tidak boleh melebihi qualified leads.',
                'errors' => [
                    'conversions' => ['Jumlah conversions tidak boleh melebihi qualified leads.'],
                ],
            ], 422));
        }

        return $validated;
    }
}