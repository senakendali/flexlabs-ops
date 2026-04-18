<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\MarketingReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MarketingReportController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $periodType = $request->input('period_type');

        $reports = MarketingReport::query()
            ->withCount(['campaigns', 'ads', 'events'])
            ->with(['creator', 'updater'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('title', 'like', '%' . $search . '%')
                        ->orWhere('report_no', 'like', '%' . $search . '%')
                        ->orWhere('slug', 'like', '%' . $search . '%');
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($periodType, fn ($query) => $query->where('period_type', $periodType))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('marketing.reports.index', compact('reports', 'search', 'status', 'periodType'));
    }

    public function create(): View
    {
        $report = new MarketingReport([
            'period_type' => 'monthly',
            'status' => 'draft',
            'is_active' => true,
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
            'total_leads' => 0,
            'total_registrants' => 0,
            'total_attendees' => 0,
            'total_conversions' => 0,
            'total_revenue' => 0,
            'total_budget' => 0,
            'total_actual_spend' => 0,
            'is_overview_completed' => false,
            'is_campaign_completed' => false,
            'is_ads_completed' => false,
            'is_events_completed' => false,
            'is_snapshot_completed' => false,
            'is_insight_completed' => false,
        ]);

        return view('marketing.reports.form', [
            'report' => $report,
            'campaigns' => [],
            'ads' => [],
            'events' => [],
            'submitMode' => 'create',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateRequest($request);

        DB::beginTransaction();

        try {
            $report = new MarketingReport();
            $this->fillReport($report, $validated, true);
            $report->save();

            $campaignIdMap = $this->syncCampaigns($report, $validated['campaigns'] ?? []);
            $this->syncAds($report, $validated['ads'] ?? [], $campaignIdMap);
            $this->syncEvents($report, $validated['events'] ?? []);

            $this->refreshReportCompletion($report);
            $report->recalculateTotals();
            $report->refresh();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Marketing report berhasil dibuat.',
                'redirect' => route('marketing.reports.edit', $report),
                'data' => [
                    'id' => $report->id,
                    'title' => $report->title,
                    'status' => $report->status,
                ],
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat marketing report.',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function show(MarketingReport $report): View
    {
        $report->load([
            'campaigns.ads',
            'ads.campaign',
            'events',
            'creator',
            'updater',
        ]);

        return view('marketing.reports.show', compact('report'));
    }

    public function edit(MarketingReport $report): View
    {
        $report->load([
            'campaigns',
            'ads',
            'events',
        ]);

        return view('marketing.reports.form', [
            'report' => $report,
            'campaigns' => $report->campaigns,
            'ads' => $report->ads,
            'events' => $report->events,
            'submitMode' => 'edit',
        ]);
    }

    public function update(Request $request, MarketingReport $report): JsonResponse
    {
        $validated = $this->validateRequest($request, $report->id);

        DB::beginTransaction();

        try {
            $this->fillReport($report, $validated, false);
            $report->save();

            $campaignIdMap = $this->syncCampaigns($report, $validated['campaigns'] ?? []);
            $this->syncAds($report, $validated['ads'] ?? [], $campaignIdMap);
            $this->syncEvents($report, $validated['events'] ?? []);

            $this->refreshReportCompletion($report);
            $report->recalculateTotals();
            $report->refresh();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Marketing report berhasil diperbarui.',
                'redirect' => route('marketing.reports.edit', $report),
                'data' => [
                    'id' => $report->id,
                    'title' => $report->title,
                    'status' => $report->status,
                ],
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui marketing report.',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    public function destroy(MarketingReport $report): JsonResponse
    {
        try {
            $report->delete();

            return response()->json([
                'success' => true,
                'message' => 'Marketing report berhasil dihapus.',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Marketing report gagal dihapus.',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

    protected function validateRequest(Request $request, ?int $reportId = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('marketing_reports', 'slug')->ignore($reportId),
            ],
            'report_no' => ['nullable', 'string', 'max:100'],
            'period_type' => ['required', Rule::in(['weekly', 'monthly'])],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],

            'total_leads' => ['nullable', 'integer', 'min:0'],
            'total_registrants' => ['nullable', 'integer', 'min:0'],
            'total_attendees' => ['nullable', 'integer', 'min:0'],
            'total_conversions' => ['nullable', 'integer', 'min:0'],
            'total_revenue' => ['nullable', 'numeric', 'min:0'],

            'summary' => ['nullable', 'string'],
            'key_insight' => ['nullable', 'string'],
            'next_action' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],

            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'is_active' => ['nullable', 'boolean'],

            'campaigns' => ['nullable', 'array'],
            'campaigns.*.id' => ['nullable', 'integer'],
            'campaigns.*.temp_key' => ['nullable', 'string', 'max:100'],
            'campaigns.*.name' => ['required_with:campaigns', 'nullable', 'string', 'max:255'],
            'campaigns.*.objective' => ['nullable', 'string'],
            'campaigns.*.start_date' => ['nullable', 'date'],
            'campaigns.*.end_date' => ['nullable', 'date', 'after_or_equal:campaigns.*.start_date'],
            'campaigns.*.budget' => ['nullable', 'numeric', 'min:0'],
            'campaigns.*.actual_spend' => ['nullable', 'numeric', 'min:0'],
            'campaigns.*.owner_name' => ['nullable', 'string', 'max:255'],
            'campaigns.*.status' => ['required_with:campaigns', Rule::in(['planned', 'on_progress', 'review', 'done'])],
            'campaigns.*.notes' => ['nullable', 'string'],
            'campaigns.*.sort_order' => ['nullable', 'integer', 'min:0'],

            'ads' => ['nullable', 'array'],
            'ads.*.id' => ['nullable', 'integer'],
            'ads.*.campaign_id' => ['nullable'],
            'ads.*.campaign_temp_key' => ['nullable', 'string', 'max:100'],
            'ads.*.platform' => ['required_with:ads', 'nullable', 'string', 'max:100'],
            'ads.*.ad_name' => ['required_with:ads', 'nullable', 'string', 'max:255'],
            'ads.*.objective' => ['nullable', 'string', 'max:255'],
            'ads.*.start_date' => ['nullable', 'date'],
            'ads.*.end_date' => ['nullable', 'date', 'after_or_equal:ads.*.start_date'],
            'ads.*.budget' => ['nullable', 'numeric', 'min:0'],
            'ads.*.actual_spend' => ['nullable', 'numeric', 'min:0'],
            'ads.*.status' => ['required_with:ads', Rule::in(['active', 'paused', 'review', 'done'])],
            'ads.*.notes' => ['nullable', 'string'],
            'ads.*.sort_order' => ['nullable', 'integer', 'min:0'],

            'events' => ['nullable', 'array'],
            'events.*.id' => ['nullable', 'integer'],
            'events.*.name' => ['required_with:events', 'nullable', 'string', 'max:255'],
            'events.*.event_type' => [
                'required_with:events',
                Rule::in(['owned_event', 'external_event', 'participated_event', 'trial_class', 'workshop', 'info_session']),
            ],
            'events.*.event_date' => ['nullable', 'date'],
            'events.*.location' => ['nullable', 'string', 'max:255'],
            'events.*.target_participants' => ['nullable', 'integer', 'min:0'],
            'events.*.budget' => ['nullable', 'numeric', 'min:0'],
            'events.*.status' => [
                'required_with:events',
                Rule::in(['planned', 'scheduled', 'open_registration', 'confirmed', 'done', 'cancelled']),
            ],
            'events.*.notes' => ['nullable', 'string'],
            'events.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    protected function fillReport(MarketingReport $report, array $validated, bool $isCreate = false): void
    {
        $report->fill([
            'title' => $validated['title'],
            'slug' => filled($validated['slug'] ?? null)
                ? Str::slug($validated['slug'])
                : Str::slug($validated['title']),
            'report_no' => $validated['report_no'] ?? null,
            'period_type' => $validated['period_type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],

            'total_leads' => (int) ($validated['total_leads'] ?? 0),
            'total_registrants' => (int) ($validated['total_registrants'] ?? 0),
            'total_attendees' => (int) ($validated['total_attendees'] ?? 0),
            'total_conversions' => (int) ($validated['total_conversions'] ?? 0),
            'total_revenue' => (float) ($validated['total_revenue'] ?? 0),

            'summary' => $validated['summary'] ?? null,
            'key_insight' => $validated['key_insight'] ?? null,
            'next_action' => $validated['next_action'] ?? null,
            'notes' => $validated['notes'] ?? null,

            'status' => $validated['status'],
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        if ($isCreate) {
            $report->created_by = Auth::id();
        }

        $report->updated_by = Auth::id();
    }

    protected function syncCampaigns(MarketingReport $report, array $campaigns): array
    {
        $existingIds = $report->campaigns()->pluck('id')->map(fn ($id) => (int) $id)->toArray();
        $keptIds = [];
        $idMap = [];

        foreach ($campaigns as $index => $item) {
            $campaign = null;

            if (!empty($item['id'])) {
                $campaign = $report->campaigns()->where('id', $item['id'])->first();
            }

            if (!$campaign) {
                $campaign = $report->campaigns()->make();
            }

            $campaign->fill([
                'name' => $item['name'],
                'objective' => $item['objective'] ?? null,
                'start_date' => $item['start_date'] ?? null,
                'end_date' => $item['end_date'] ?? null,
                'budget' => (float) ($item['budget'] ?? 0),
                'actual_spend' => (float) ($item['actual_spend'] ?? 0),
                'owner_name' => $item['owner_name'] ?? null,
                'status' => $item['status'],
                'notes' => $item['notes'] ?? null,
                'sort_order' => $item['sort_order'] ?? $index,
            ]);

            $campaign->marketing_report_id = $report->id;
            $campaign->save();

            $keptIds[] = $campaign->id;

            if (!empty($item['temp_key'])) {
                $idMap[$item['temp_key']] = $campaign->id;
            }
        }

        $deleteIds = array_diff($existingIds, $keptIds);
        if (!empty($deleteIds)) {
            $report->campaigns()->whereIn('id', $deleteIds)->delete();
        }

        return $idMap;
    }

    protected function syncAds(MarketingReport $report, array $ads, array $campaignIdMap = []): void
    {
        $existingIds = $report->ads()->pluck('id')->map(fn ($id) => (int) $id)->toArray();
        $keptIds = [];

        foreach ($ads as $index => $item) {
            $ad = null;

            if (!empty($item['id'])) {
                $ad = $report->ads()->where('id', $item['id'])->first();
            }

            if (!$ad) {
                $ad = $report->ads()->make();
            }

            $campaignId = null;

            if (!empty($item['campaign_id']) && is_numeric($item['campaign_id'])) {
                $campaignId = (int) $item['campaign_id'];
            } elseif (!empty($item['campaign_temp_key']) && isset($campaignIdMap[$item['campaign_temp_key']])) {
                $campaignId = $campaignIdMap[$item['campaign_temp_key']];
            }

            $ad->fill([
                'marketing_report_campaign_id' => $campaignId,
                'platform' => $item['platform'],
                'ad_name' => $item['ad_name'],
                'objective' => $item['objective'] ?? null,
                'start_date' => $item['start_date'] ?? null,
                'end_date' => $item['end_date'] ?? null,
                'budget' => (float) ($item['budget'] ?? 0),
                'actual_spend' => (float) ($item['actual_spend'] ?? 0),
                'status' => $item['status'],
                'notes' => $item['notes'] ?? null,
                'sort_order' => $item['sort_order'] ?? $index,
            ]);

            $ad->marketing_report_id = $report->id;
            $ad->save();

            $keptIds[] = $ad->id;
        }

        $deleteIds = array_diff($existingIds, $keptIds);
        if (!empty($deleteIds)) {
            $report->ads()->whereIn('id', $deleteIds)->delete();
        }
    }

    protected function syncEvents(MarketingReport $report, array $events): void
    {
        $existingIds = $report->events()->pluck('id')->map(fn ($id) => (int) $id)->toArray();
        $keptIds = [];

        foreach ($events as $index => $item) {
            $event = null;

            if (!empty($item['id'])) {
                $event = $report->events()->where('id', $item['id'])->first();
            }

            if (!$event) {
                $event = $report->events()->make();
            }

            $event->fill([
                'name' => $item['name'],
                'event_type' => $item['event_type'],
                'event_date' => $item['event_date'] ?? null,
                'location' => $item['location'] ?? null,
                'target_participants' => (int) ($item['target_participants'] ?? 0),
                'budget' => (float) ($item['budget'] ?? 0),
                'status' => $item['status'],
                'notes' => $item['notes'] ?? null,
                'sort_order' => $item['sort_order'] ?? $index,
            ]);

            $event->marketing_report_id = $report->id;
            $event->save();

            $keptIds[] = $event->id;
        }

        $deleteIds = array_diff($existingIds, $keptIds);
        if (!empty($deleteIds)) {
            $report->events()->whereIn('id', $deleteIds)->delete();
        }
    }

    protected function refreshReportCompletion(MarketingReport $report): void
    {
        $report->loadMissing(['campaigns', 'ads', 'events']);

        $report->is_overview_completed = $this->isOverviewCompleted($report);
        $report->is_campaign_completed = $this->isCampaignCompleted($report);
        $report->is_ads_completed = $this->isAdsCompleted($report);
        $report->is_events_completed = $this->isEventsCompleted($report);
        $report->is_snapshot_completed = $this->isSnapshotCompleted($report);
        $report->is_insight_completed = $this->isInsightCompleted($report);

        $report->save();
    }

    protected function isOverviewCompleted(MarketingReport $report): bool
    {
        return filled($report->title)
            && filled($report->period_type)
            && filled($report->start_date)
            && filled($report->end_date)
            && filled($report->status);
    }

    protected function isCampaignCompleted(MarketingReport $report): bool
    {
        return $report->campaigns->count() > 0;
    }

    protected function isAdsCompleted(MarketingReport $report): bool
    {
        return $report->ads->count() > 0;
    }

    protected function isEventsCompleted(MarketingReport $report): bool
    {
        return $report->events->count() > 0;
    }

    protected function isSnapshotCompleted(MarketingReport $report): bool
    {
        return $report->total_leads >= 0
            && $report->total_registrants >= 0
            && $report->total_attendees >= 0
            && $report->total_conversions >= 0
            && $report->total_revenue >= 0;
    }

    protected function isInsightCompleted(MarketingReport $report): bool
    {
        return filled($report->summary)
            || filled($report->key_insight)
            || filled($report->next_action)
            || filled($report->notes);
    }
}