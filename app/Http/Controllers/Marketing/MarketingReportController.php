<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\MarketingReport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
        $perPage = (int) $request->input('per_page', 10);

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
            ->paginate($perPage > 0 ? $perPage : 10)
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

        [$campaignRows, $adRows] = $this->buildSetupRowsForPeriod(
            (string) $report->start_date,
            (string) $report->end_date
        );

        return view('marketing.reports.form', [
            'report' => $report,
            'campaigns' => collect($campaignRows),
            'ads' => collect($adRows),
            'events' => collect(),
            'submitMode' => 'create',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateRequest($request);
        $validated = $this->hydrateAutoSetupRows($validated);

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
                'report_no' => $report->report_no,
                'redirect' => route('marketing.reports.edit', $report),
                'data' => [
                    'id' => $report->id,
                    'title' => $report->title,
                    'status' => $report->status,
                    'report_no' => $report->report_no,
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

        return view('marketing.reports.show', [
            'marketingReport' => $report,
            'campaigns' => $report->campaigns,
            'ads' => $report->ads,
            'events' => $report->events,
        ]);
    }

    public function edit(MarketingReport $report): View
    {
        $report->load([
            'campaigns',
            'ads',
            'events',
        ]);

        [$campaignRows, $adRows] = $this->resolveFormRowsForEdit($report);

        return view('marketing.reports.form', [
            'report' => $report,
            'campaigns' => $campaignRows,
            'ads' => $adRows,
            'events' => $report->events,
            'submitMode' => 'edit',
        ]);
    }

    public function update(Request $request, MarketingReport $report): JsonResponse
    {
        $validated = $this->validateRequest($request, $report->id);
        $validated = $this->hydrateAutoSetupRows($validated, $report);

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
                'report_no' => $report->report_no,
                'redirect' => route('marketing.reports.edit', $report),
                'data' => [
                    'id' => $report->id,
                    'title' => $report->title,
                    'status' => $report->status,
                    'report_no' => $report->report_no,
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

    public function syncPeriodData(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period_type' => ['required', Rule::in(['weekly', 'monthly'])],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        [$campaignRows, $adRows] = $this->buildSetupRowsForPeriod(
            $validated['start_date'],
            $validated['end_date']
        );

        return response()->json([
            'success' => true,
            'message' => 'Campaign dan ads berhasil dimuat otomatis.',
            'data' => [
                'campaigns' => $campaignRows,
                'ads' => $adRows,
            ],
        ]);
    }

    protected function validateRequest(Request $request, ?int $reportId = null): array
    {
        $validated = $request->validate([
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

            'sync_campaign_period' => ['nullable', 'boolean'],
            'sync_ads_period' => ['nullable', 'boolean'],

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
            'campaigns.*.end_date' => ['nullable', 'date'],
            'campaigns.*.budget' => ['nullable', 'numeric', 'min:0'],
            'campaigns.*.actual_spend' => ['nullable', 'numeric', 'min:0'],
            'campaigns.*.owner_name' => ['nullable', 'string', 'max:255'],
            'campaigns.*.status' => ['nullable', 'string', 'max:100'],
            'campaigns.*.notes' => ['nullable', 'string'],
            'campaigns.*.sort_order' => ['nullable', 'integer', 'min:0'],

            'ads' => ['nullable', 'array'],
            'ads.*.id' => ['nullable', 'integer'],
            'ads.*.campaign_id' => ['nullable', 'integer'],
            'ads.*.campaign_temp_key' => ['nullable', 'string', 'max:100'],
            'ads.*.platform' => ['required_with:ads', 'nullable', 'string', 'max:100'],
            'ads.*.ad_name' => ['required_with:ads', 'nullable', 'string', 'max:255'],
            'ads.*.objective' => ['nullable', 'string', 'max:255'],
            'ads.*.start_date' => ['nullable', 'date'],
            'ads.*.end_date' => ['nullable', 'date'],
            'ads.*.budget' => ['nullable', 'numeric', 'min:0'],
            'ads.*.actual_spend' => ['nullable', 'numeric', 'min:0'],
            'ads.*.status' => ['nullable', 'string', 'max:100'],
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

        $validated['is_active'] = $request->boolean('is_active');
        $validated['sync_campaign_period'] = $request->boolean('sync_campaign_period', true);
        $validated['sync_ads_period'] = $request->boolean('sync_ads_period', true);

        foreach (($validated['campaigns'] ?? []) as $index => $campaign) {
            if (
                !empty($campaign['start_date']) &&
                !empty($campaign['end_date']) &&
                Carbon::parse($campaign['end_date'])->lt(Carbon::parse($campaign['start_date']))
            ) {
                abort(response()->json([
                    'message' => 'Validasi gagal.',
                    'errors' => [
                        "campaigns.$index.end_date" => ['End date campaign harus sama atau setelah start date.'],
                    ],
                ], 422));
            }
        }

        foreach (($validated['ads'] ?? []) as $index => $ad) {
            if (
                !empty($ad['start_date']) &&
                !empty($ad['end_date']) &&
                Carbon::parse($ad['end_date'])->lt(Carbon::parse($ad['start_date']))
            ) {
                abort(response()->json([
                    'message' => 'Validasi gagal.',
                    'errors' => [
                        "ads.$index.end_date" => ['End date ads harus sama atau setelah start date.'],
                    ],
                ], 422));
            }
        }

        return $validated;
    }

    protected function fillReport(MarketingReport $report, array $validated, bool $isCreate = false): void
    {
        $reportNo = filled($validated['report_no'] ?? null)
            ? $validated['report_no']
            : ($report->report_no ?: $this->generateReportNumber(
                $validated['period_type'],
                $validated['start_date']
            ));

        $report->fill([
            'title' => $validated['title'],
            'slug' => filled($validated['slug'] ?? null)
                ? Str::slug($validated['slug'])
                : Str::slug($validated['title']),
            'report_no' => $reportNo,
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
            'is_active' => (bool) ($validated['is_active'] ?? false),
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
                'status' => $this->normalizeReportCampaignStatus($item['status'] ?? null),
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
                'status' => $this->normalizeReportAdStatus($item['status'] ?? null),
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
        if ($report->campaigns->isEmpty()) {
            return false;
        }

        return $report->campaigns->every(function ($campaign) {
            return filled($campaign->name);
        });
    }

    protected function isAdsCompleted(MarketingReport $report): bool
    {
        if ($report->ads->isEmpty()) {
            return false;
        }

        return $report->ads->every(function ($ad) {
            return filled($ad->platform) && filled($ad->ad_name);
        });
    }

    protected function isEventsCompleted(MarketingReport $report): bool
    {
        if ($report->events->isEmpty()) {
            return false;
        }

        return $report->events->every(function ($event) {
            return filled($event->name) && filled($event->event_type) && filled($event->status);
        });
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

    protected function generateReportNumber(string $periodType, string $startDate): string
    {
        $date = Carbon::parse($startDate);

        if ($periodType === 'weekly') {
            $base = 'MR-W-' . $date->format('oW');
        } else {
            $base = 'MR-M-' . $date->format('Ym');
        }

        $latestNumber = MarketingReport::query()
            ->where('report_no', 'like', $base . '-%')
            ->orderByDesc('id')
            ->value('report_no');

        $sequence = 1;

        if ($latestNumber && preg_match('/-(\d+)$/', $latestNumber, $matches)) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return sprintf('%s-%04d', $base, $sequence);
    }

    protected function hydrateAutoSetupRows(array $validated, ?MarketingReport $existingReport = null): array
    {
        $shouldSeedCampaigns = ($validated['sync_campaign_period'] ?? true)
            && empty($validated['campaigns']);

        $shouldSeedAds = ($validated['sync_ads_period'] ?? true)
            && empty($validated['ads']);

        if (!$shouldSeedCampaigns && !$shouldSeedAds) {
            return $validated;
        }

        [$setupCampaignRows, $setupAdRows] = $this->buildSetupRowsForPeriod(
            $validated['start_date'],
            $validated['end_date']
        );

        if ($shouldSeedCampaigns) {
            $validated['campaigns'] = $setupCampaignRows;
        }

        if ($shouldSeedAds) {
            $validated['ads'] = $setupAdRows;
        }

        return $validated;
    }

    protected function resolveFormRowsForEdit(MarketingReport $report): array
    {
        if ($report->campaigns->isNotEmpty() || $report->ads->isNotEmpty()) {
            return [$report->campaigns, $report->ads];
        }

        [$campaignRows, $adRows] = $this->buildSetupRowsForPeriod(
            Carbon::parse($report->start_date)->toDateString(),
            Carbon::parse($report->end_date)->toDateString()
        );

        return [collect($campaignRows), collect($adRows)];
    }

    protected function buildSetupRowsForPeriod(string $startDate, string $endDate): array
    {
        $campaignRows = [];
        $adRows = [];
        $campaignTempKeyMap = [];
        $campaignNameMap = [];

        $setupCampaigns = $this->getSetupCampaignQueryForPeriod($startDate, $endDate)->get();

        foreach ($setupCampaigns as $index => $campaign) {
            $setupCampaignId = $this->extractValue($campaign, ['id']);
            $tempKey = $this->makeSetupTempKey('setup_cmp', $setupCampaignId ?: ($index + 1));
            $campaignName = (string) $this->extractValue($campaign, ['name'], '');

            if ($setupCampaignId) {
                $campaignTempKeyMap[(string) $setupCampaignId] = $tempKey;
                $campaignNameMap[(string) $setupCampaignId] = $campaignName;
            }

            $campaignRows[] = [
                'id' => null,
                'temp_key' => $tempKey,
                'name' => $campaignName,
                'objective' => $this->extractValue($campaign, ['objective', 'description']),
                'start_date' => $this->normalizeNullableDate($this->extractValue($campaign, ['start_date'])),
                'end_date' => $this->normalizeNullableDate($this->extractValue($campaign, ['end_date'])),
                'budget' => $this->extractNumericValue($campaign, ['budget', 'total_budget', 'resolved_budget']),
                'actual_spend' => 0,
                'owner_name' => $this->extractValue($campaign, ['owner_name']),
                'status' => $this->normalizeReportCampaignStatus(
                    (string) $this->extractValue($campaign, ['status'])
                ),
                'notes' => null,
                'sort_order' => $index,
            ];
        }

        $setupAds = $this->getSetupAdQueryForPeriod($startDate, $endDate)->get();

        foreach ($setupAds as $index => $ad) {
            $setupCampaignId = $this->extractValue($ad, ['marketing_setup_campaign_id', 'marketing_campaign_id', 'campaign_id']);
            $campaignTempKey = $setupCampaignId !== null
                ? ($campaignTempKeyMap[(string) $setupCampaignId] ?? null)
                : null;
            $campaignLabel = $setupCampaignId !== null
                ? ($campaignNameMap[(string) $setupCampaignId] ?? null)
                : null;

            $adRows[] = [
                'id' => null,
                'platform' => (string) $this->extractValue($ad, ['platform'], ''),
                'ad_name' => (string) $this->extractValue($ad, ['ad_name', 'name'], ''),
                'objective' => $this->extractValue($ad, ['objective', 'description']),
                'start_date' => $this->normalizeNullableDate($this->extractValue($ad, ['start_date'])),
                'end_date' => $this->normalizeNullableDate($this->extractValue($ad, ['end_date'])),
                'budget' => $this->extractNumericValue($ad, ['budget', 'total_budget', 'resolved_budget']),
                'actual_spend' => 0,
                'status' => $this->normalizeReportAdStatus(
                    (string) $this->extractValue($ad, ['status'])
                ),
                'notes' => null,
                'campaign_temp_key' => $campaignTempKey,
                'campaign_label' => $campaignLabel,
                'sort_order' => $index,
            ];
        }

        return [$campaignRows, $adRows];
    }

    protected function getSetupCampaignQueryForPeriod(string $startDate, string $endDate): Builder
    {
        $modelClass = class_exists(\App\Models\MarketingSetupCampaign::class)
            ? \App\Models\MarketingSetupCampaign::class
            : \App\Models\MarketingCampaign::class;

        $query = $modelClass::query();

        if (method_exists($modelClass, 'scopeActive')) {
            $query->active();
        } elseif ($this->hasColumnLike($query, 'is_active')) {
            $query->where('is_active', true);
        }

        if (method_exists($modelClass, 'scopeOverlappingPeriod')) {
            $query->overlappingPeriod($startDate, $endDate);
        } else {
            $query->where(function ($subQuery) use ($startDate, $endDate) {
                $subQuery
                    ->where(function ($q) use ($startDate, $endDate) {
                        $q->whereNotNull('start_date')
                            ->whereNotNull('end_date')
                            ->whereDate('start_date', '<=', $endDate)
                            ->whereDate('end_date', '>=', $startDate);
                    })
                    ->orWhere(function ($q) use ($startDate) {
                        $q->whereNull('start_date')
                            ->whereNotNull('end_date')
                            ->whereDate('end_date', '>=', $startDate);
                    })
                    ->orWhere(function ($q) use ($endDate) {
                        $q->whereNotNull('start_date')
                            ->whereNull('end_date')
                            ->whereDate('start_date', '<=', $endDate);
                    })
                    ->orWhere(function ($q) {
                        $q->whereNull('start_date')
                            ->whereNull('end_date');
                    });
            });
        }

        if ($this->hasColumnLike($query, 'start_date')) {
            $query->orderBy('start_date');
        }

        if ($this->hasColumnLike($query, 'name')) {
            $query->orderBy('name');
        }

        return $query;
    }

    protected function getSetupAdQueryForPeriod(string $startDate, string $endDate): Builder
    {
        $modelClass = class_exists(\App\Models\MarketingSetupAd::class)
            ? \App\Models\MarketingSetupAd::class
            : \App\Models\MarketingAd::class;

        $query = $modelClass::query();

        if (method_exists($modelClass, 'campaign')) {
            $query->with('campaign');
        }

        if (method_exists($modelClass, 'scopeActive')) {
            $query->active();
        } elseif ($this->hasColumnLike($query, 'is_active')) {
            $query->where('is_active', true);
        }

        if (method_exists($modelClass, 'scopeOverlappingPeriod')) {
            $query->overlappingPeriod($startDate, $endDate);
        } else {
            $query->where(function ($subQuery) use ($startDate, $endDate) {
                $subQuery
                    ->where(function ($q) use ($startDate, $endDate) {
                        $q->whereNotNull('start_date')
                            ->whereNotNull('end_date')
                            ->whereDate('start_date', '<=', $endDate)
                            ->whereDate('end_date', '>=', $startDate);
                    })
                    ->orWhere(function ($q) use ($startDate) {
                        $q->whereNull('start_date')
                            ->whereNotNull('end_date')
                            ->whereDate('end_date', '>=', $startDate);
                    })
                    ->orWhere(function ($q) use ($endDate) {
                        $q->whereNotNull('start_date')
                            ->whereNull('end_date')
                            ->whereDate('start_date', '<=', $endDate);
                    })
                    ->orWhere(function ($q) {
                        $q->whereNull('start_date')
                            ->whereNull('end_date');
                    });
            });
        }

        if ($this->hasColumnLike($query, 'start_date')) {
            $query->orderBy('start_date');
        }

        if ($this->hasColumnLike($query, 'ad_name')) {
            $query->orderBy('ad_name');
        } elseif ($this->hasColumnLike($query, 'name')) {
            $query->orderBy('name');
        }

        return $query;
    }

    protected function hasColumnLike(Builder $query, string $column): bool
    {
        try {
            return in_array(
                $column,
                $query->getModel()->getConnection()->getSchemaBuilder()->getColumnListing($query->getModel()->getTable()),
                true
            );
        } catch (\Throwable $th) {
            return false;
        }
    }

    protected function makeSetupTempKey(string $prefix, mixed $value): string
    {
        return $prefix . '_' . (string) $value;
    }

    protected function normalizeNullableDate(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return Carbon::parse($value)->toDateString();
    }

    protected function extractNumericValue(object|array $source, array $keys, float|int $default = 0): float
    {
        $value = $this->extractValue($source, $keys, $default);

        if ($value === null || $value === '') {
            return (float) $default;
        }

        return (float) $value;
    }

    protected function extractValue(object|array $source, array $keys, mixed $default = null): mixed
    {
        foreach ($keys as $key) {
            if (is_array($source) && array_key_exists($key, $source)) {
                return $source[$key];
            }

            if (is_object($source) && isset($source->{$key})) {
                return $source->{$key};
            }

            if (is_object($source) && method_exists($source, '__get')) {
                try {
                    $value = $source->{$key};
                    if ($value !== null) {
                        return $value;
                    }
                } catch (\Throwable $th) {
                    // skip
                }
            }
        }

        return $default;
    }

    protected function normalizeReportCampaignStatus(?string $status): string
    {
        return match ((string) $status) {
            'planned' => 'planned',
            'active' => 'on_progress',
            'on_progress' => 'on_progress',
            'paused' => 'review',
            'review' => 'review',
            'done' => 'done',
            'completed' => 'done',
            'cancelled' => 'review',
            default => 'planned',
        };
    }

    protected function normalizeReportAdStatus(?string $status): string
    {
        return match ((string) $status) {
            'active' => 'active',
            'paused' => 'paused',
            'review' => 'review',
            'done' => 'done',
            'completed' => 'done',
            'planned' => 'review',
            'cancelled' => 'review',
            default => 'active',
        };
    }
}