<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\MarketingReport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class MarketingDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $selectedMonth = $request->input('month', now()->format('Y-m'));

        try {
            $monthDate = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
        } catch (\Throwable $th) {
            $monthDate = now()->startOfMonth();
            $selectedMonth = $monthDate->format('Y-m');
        }

        $monthStart = $monthDate->copy()->startOfMonth();
        $monthEnd = $monthDate->copy()->endOfMonth();

        $selectedWeek = $request->input('week', 'all');

        $weeklyReports = MarketingReport::query()
            ->withCount(['campaigns', 'ads', 'events'])
            ->with([
                'campaigns',
                'ads',
                'events',
                'creator',
                'updater',
            ])
            ->where('period_type', 'weekly')
            ->whereDate('start_date', '<=', $monthEnd->toDateString())
            ->whereDate('end_date', '>=', $monthStart->toDateString())
            ->orderBy('start_date')
            ->orderBy('id')
            ->get();

        $monthlyReport = MarketingReport::query()
            ->withCount(['campaigns', 'ads', 'events'])
            ->with([
                'campaigns',
                'ads',
                'events',
                'creator',
                'updater',
            ])
            ->where('period_type', 'monthly')
            ->whereDate('start_date', '<=', $monthEnd->toDateString())
            ->whereDate('end_date', '>=', $monthStart->toDateString())
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();

        $weekCards = $weeklyReports->values()->map(function ($report, $index) {
            return [
                'id' => $report->id,
                'title' => 'Week ' . ($index + 1),
                'date_label' => $this->formatDateRange($report->start_date, $report->end_date),
                'status' => ucfirst($report->status ?? 'draft'),
                'leads' => (int) ($report->total_leads ?? 0),
                'conversions' => (int) ($report->total_conversions ?? 0),
                'revenue' => (float) ($report->total_revenue ?? 0),
                'actual_spend' => (float) ($report->total_actual_spend ?? 0),
                'campaigns_count' => (int) ($report->campaigns_count ?? 0),
                'ads_count' => (int) ($report->ads_count ?? 0),
                'events_count' => (int) ($report->events_count ?? 0),
            ];
        });

        $activeWeeklyReport = null;

        if ($selectedWeek !== 'all') {
            $activeWeeklyReport = $weeklyReports->firstWhere('id', (int) $selectedWeek);
        }

        $effectiveReports = $activeWeeklyReport
            ? collect([$activeWeeklyReport])
            : $weeklyReports;

        if ($effectiveReports->isEmpty() && $monthlyReport) {
            $effectiveReports = collect([$monthlyReport]);
        }

        $summary = [
            'campaigns' => (int) $effectiveReports->sum('campaigns_count'),
            'ads' => (int) $effectiveReports->sum('ads_count'),
            'events' => (int) $effectiveReports->sum('events_count'),
            'leads' => (int) $effectiveReports->sum('total_leads'),
            'conversions' => (int) $effectiveReports->sum('total_conversions'),
            'revenue' => (float) $effectiveReports->sum('total_revenue'),
            'total_budget' => (float) $effectiveReports->sum('total_budget'),
            'actual_spend' => (float) $effectiveReports->sum('total_actual_spend'),
        ];

        $summary['remaining_budget'] = max($summary['total_budget'] - $summary['actual_spend'], 0);
        $summary['budget_utilization'] = $summary['total_budget'] > 0
            ? round(($summary['actual_spend'] / $summary['total_budget']) * 100, 1)
            : 0;

        $campaignRows = $this->buildCampaignRows($effectiveReports);
        $adRows = $this->buildAdRows($effectiveReports);
        $eventRows = $this->buildEventRows($effectiveReports);

        $chartSourceCards = $activeWeeklyReport
            ? $weekCards->where('id', $activeWeeklyReport->id)->values()
            : $weekCards->values();

        $showChart = $chartSourceCards->count() > 0;
        $chartPayload = $showChart ? $this->buildChartPayload($chartSourceCards) : null;

        $scopeTitle = $activeWeeklyReport
            ? ($activeWeeklyReport->title ?: $this->formatDateRange($activeWeeklyReport->start_date, $activeWeeklyReport->end_date))
            : 'All Weeks in ' . $monthDate->translatedFormat('F Y');

        $scopeLabel = $activeWeeklyReport
            ? 'Dashboard membaca satu weekly report yang dipilih.'
            : 'Dashboard menggabungkan seluruh weekly report dalam bulan ini.';

        $insightCards = $this->buildInsightCards(
            $activeWeeklyReport
                ? collect([$activeWeeklyReport])
                : $weeklyReports
        );

        if ($insightCards->isEmpty() && $monthlyReport) {
            $insightCards = collect([
                $this->makeInsightCard(
                    'Monthly Summary',
                    $this->formatDateRange($monthlyReport->start_date, $monthlyReport->end_date),
                    $monthlyReport->summary,
                    $monthlyReport->key_insight,
                    $monthlyReport->next_action,
                    $monthlyReport->notes
                ),
            ])->filter(fn ($item) => $item['has_content'])->values();
        }

        $monthOptions = $this->buildMonthOptions($selectedMonth);

        return view('marketing.dashboard', [
            'selectedMonth' => $selectedMonth,
            'selectedWeek' => $selectedWeek,
            'monthLabel' => $monthDate->translatedFormat('F Y'),
            'monthOptions' => $monthOptions,
            'weekCards' => $weekCards,
            'weeklyReports' => $weeklyReports,
            'monthlyReport' => $monthlyReport,
            'activeWeeklyReport' => $activeWeeklyReport,
            'scopeTitle' => $scopeTitle,
            'scopeLabel' => $scopeLabel,
            'summary' => $summary,
            'campaignRows' => $campaignRows,
            'adRows' => $adRows,
            'eventRows' => $eventRows,
            'showChart' => $showChart,
            'chartPayload' => $chartPayload,
            'insightCards' => $insightCards,
        ]);
    }

    protected function buildCampaignRows(Collection $reports): Collection
    {
        return $reports
            ->flatMap(function ($report) {
                return $report->campaigns->map(function ($campaign) use ($report) {
                    return (object) [
                        'report_title' => $report->title,
                        'report_no' => $report->report_no,
                        'report_period_label' => $this->formatDateRange($report->start_date, $report->end_date),
                        'name' => $campaign->name,
                        'objective' => $campaign->objective,
                        'owner_name' => $campaign->owner_name,
                        'budget' => (float) ($campaign->budget ?? 0),
                        'actual_spend' => (float) ($campaign->actual_spend ?? 0),
                        'status' => $campaign->status,
                        'start_date' => $campaign->start_date,
                        'end_date' => $campaign->end_date,
                        'notes' => $campaign->notes,
                    ];
                });
            })
            ->sortBy([
                fn ($item) => $item->start_date ?? '9999-12-31',
                fn ($item) => $item->name ?? '',
            ])
            ->values();
    }

    protected function buildAdRows(Collection $reports): Collection
    {
        return $reports
            ->flatMap(function ($report) {
                return $report->ads->map(function ($ad) use ($report) {
                    return (object) [
                        'report_title' => $report->title,
                        'report_no' => $report->report_no,
                        'report_period_label' => $this->formatDateRange($report->start_date, $report->end_date),
                        'platform' => $ad->platform,
                        'ad_name' => $ad->ad_name,
                        'objective' => $ad->objective,
                        'budget' => (float) ($ad->budget ?? 0),
                        'actual_spend' => (float) ($ad->actual_spend ?? 0),
                        'status' => $ad->status,
                        'start_date' => $ad->start_date,
                        'end_date' => $ad->end_date,
                        'notes' => $ad->notes,
                    ];
                });
            })
            ->sortBy([
                fn ($item) => $item->start_date ?? '9999-12-31',
                fn ($item) => $item->ad_name ?? '',
            ])
            ->values();
    }

    protected function buildEventRows(Collection $reports): Collection
    {
        return $reports
            ->flatMap(function ($report) {
                return $report->events->map(function ($event) use ($report) {
                    return (object) [
                        'report_title' => $report->title,
                        'report_no' => $report->report_no,
                        'report_period_label' => $this->formatDateRange($report->start_date, $report->end_date),
                        'name' => $event->name,
                        'event_type' => $event->event_type,
                        'event_date' => $event->event_date,
                        'location' => $event->location,
                        'target_participants' => (int) ($event->target_participants ?? 0),
                        'budget' => (float) ($event->budget ?? 0),
                        'status' => $event->status,
                        'notes' => $event->notes,
                    ];
                });
            })
            ->sortBy([
                fn ($item) => $item->event_date ?? '9999-12-31',
                fn ($item) => $item->name ?? '',
            ])
            ->values();
    }

    protected function buildChartPayload(Collection $cards): array
    {
        return [
            'labels' => $cards->pluck('title')->values(),
            'leads' => $cards->pluck('leads')->values(),
            'conversions' => $cards->pluck('conversions')->values(),
            'revenue_million' => $cards->map(fn ($item) => round(($item['revenue'] ?? 0) / 1000000, 2))->values(),
            'actual_spend_million' => $cards->map(fn ($item) => round(($item['actual_spend'] ?? 0) / 1000000, 2))->values(),
        ];
    }

    protected function buildInsightCards(Collection $reports): Collection
    {
        return $reports
            ->values()
            ->map(function ($report, $index) {
                return $this->makeInsightCard(
                    'Week ' . ($index + 1),
                    $this->formatDateRange($report->start_date, $report->end_date),
                    $report->summary,
                    $report->key_insight,
                    $report->next_action,
                    $report->notes
                );
            })
            ->filter(fn ($item) => $item['has_content'])
            ->values();
    }

    protected function makeInsightCard(
        string $title,
        string $period,
        ?string $summary,
        ?string $keyInsight,
        ?string $nextAction,
        ?string $notes
    ): array {
        $hasContent = filled($summary) || filled($keyInsight) || filled($nextAction) || filled($notes);

        return [
            'title' => $title,
            'period' => $period,
            'summary' => $summary,
            'key_insight' => $keyInsight,
            'next_action' => $nextAction,
            'notes' => $notes,
            'has_content' => $hasContent,
        ];
    }

    protected function buildMonthOptions(string $selectedMonth): array
    {
        $minDate = MarketingReport::query()->min('start_date');
        $maxDate = MarketingReport::query()->max('start_date');

        $rangeStart = $minDate
            ? Carbon::parse($minDate)->startOfMonth()
            : now()->copy()->subMonths(5)->startOfMonth();

        $rangeEnd = $maxDate
            ? Carbon::parse($maxDate)->startOfMonth()
            : now()->copy()->startOfMonth();

        $defaultStart = now()->copy()->subMonths(5)->startOfMonth();
        $defaultEnd = now()->copy()->startOfMonth();

        if ($rangeStart->greaterThan($defaultStart)) {
            $rangeStart = $defaultStart;
        }

        if ($rangeEnd->lessThan($defaultEnd)) {
            $rangeEnd = $defaultEnd;
        }

        try {
            $selectedDate = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();

            if ($selectedDate->lessThan($rangeStart)) {
                $rangeStart = $selectedDate->copy();
            }

            if ($selectedDate->greaterThan($rangeEnd)) {
                $rangeEnd = $selectedDate->copy();
            }
        } catch (\Throwable $th) {
            //
        }

        $months = [];
        $cursor = $rangeStart->copy();

        while ($cursor <= $rangeEnd) {
            $months[] = [
                'value' => $cursor->format('Y-m'),
                'short_label' => $cursor->translatedFormat('M Y'),
                'label' => $cursor->translatedFormat('F Y'),
            ];

            $cursor->addMonth();
        }

        return array_reverse($months);
    }

    protected function formatDateRange($startDate, $endDate): string
    {
        $start = $startDate ? Carbon::parse($startDate) : null;
        $end = $endDate ? Carbon::parse($endDate) : null;

        if (!$start && !$end) {
            return '-';
        }

        if ($start && $end) {
            return $start->format('d M') . ' - ' . $end->format('d M Y');
        }

        return ($start ?? $end)?->format('d M Y') ?? '-';
    }
}