<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\MarketingAd;
use App\Models\MarketingCampaign;
use App\Models\MarketingEvent;
use App\Models\MarketingLeadSource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MarketingDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $campaignBaseQuery = MarketingCampaign::query()
            ->where(function ($query) use ($dateFrom, $dateTo) {
                $query->whereBetween('start_date', [$dateFrom, $dateTo])
                    ->orWhereBetween('end_date', [$dateFrom, $dateTo])
                    ->orWhere(function ($subQuery) use ($dateFrom, $dateTo) {
                        $subQuery->whereDate('start_date', '<=', $dateFrom)
                            ->whereDate('end_date', '>=', $dateTo);
                    });
            });

        $adsBaseQuery = MarketingAd::query()
            ->where(function ($query) use ($dateFrom, $dateTo) {
                $query->whereBetween('start_date', [$dateFrom, $dateTo])
                    ->orWhereBetween('end_date', [$dateFrom, $dateTo])
                    ->orWhere(function ($subQuery) use ($dateFrom, $dateTo) {
                        $subQuery->whereDate('start_date', '<=', $dateFrom)
                            ->whereDate('end_date', '>=', $dateTo);
                    });
            });

        $eventsBaseQuery = MarketingEvent::query()
            ->whereBetween('event_date', [$dateFrom, $dateTo]);

        $leadSourcesBaseQuery = MarketingLeadSource::query()
            ->whereBetween('date', [$dateFrom, $dateTo]);

        $campaignStats = [
            'total' => (clone $campaignBaseQuery)->count(),
            'planned' => (clone $campaignBaseQuery)->where('status', 'planned')->count(),
            'ongoing' => (clone $campaignBaseQuery)->where('status', 'ongoing')->count(),
            'completed' => (clone $campaignBaseQuery)->where('status', 'completed')->count(),
            'budget' => (float) (clone $campaignBaseQuery)->sum('budget'),
            'actual_leads' => (int) (clone $campaignBaseQuery)->sum('actual_leads'),
            'actual_conversions' => (int) (clone $campaignBaseQuery)->sum('actual_conversions'),
        ];

        $adsStats = [
            'total' => (clone $adsBaseQuery)->count(),
            'active' => (clone $adsBaseQuery)->where('status', 'active')->count(),
            'spend' => (float) (clone $adsBaseQuery)->sum('spend'),
            'impressions' => (int) (clone $adsBaseQuery)->sum('impressions'),
            'clicks' => (int) (clone $adsBaseQuery)->sum('clicks'),
            'leads' => (int) (clone $adsBaseQuery)->sum('leads'),
            'conversions' => (int) (clone $adsBaseQuery)->sum('conversions'),
        ];

        $eventsStats = [
            'total' => (clone $eventsBaseQuery)->count(),
            'planned' => (clone $eventsBaseQuery)->where('status', 'planned')->count(),
            'ongoing' => (clone $eventsBaseQuery)->where('status', 'ongoing')->count(),
            'completed' => (clone $eventsBaseQuery)->where('status', 'completed')->count(),
            'target_participants' => (int) (clone $eventsBaseQuery)->sum('target_participants'),
            'registrants' => (int) (clone $eventsBaseQuery)->sum('registrants'),
            'attendees' => (int) (clone $eventsBaseQuery)->sum('attendees'),
            'leads_generated' => (int) (clone $eventsBaseQuery)->sum('leads_generated'),
            'conversions' => (int) (clone $eventsBaseQuery)->sum('conversions'),
            'budget' => (float) (clone $eventsBaseQuery)->sum('budget'),
        ];

        $leadStats = [
            'total_sources' => (clone $leadSourcesBaseQuery)->count(),
            'leads' => (int) (clone $leadSourcesBaseQuery)->sum('leads'),
            'qualified_leads' => (int) (clone $leadSourcesBaseQuery)->sum('qualified_leads'),
            'conversions' => (int) (clone $leadSourcesBaseQuery)->sum('conversions'),
            'revenue' => (float) (clone $leadSourcesBaseQuery)->sum('revenue'),
        ];

        $overallCtr = $adsStats['impressions'] > 0
            ? round(($adsStats['clicks'] / $adsStats['impressions']) * 100, 2)
            : 0;

        $overallCpc = $adsStats['clicks'] > 0
            ? round($adsStats['spend'] / $adsStats['clicks'], 2)
            : 0;

        $overallCpl = $adsStats['leads'] > 0
            ? round($adsStats['spend'] / $adsStats['leads'], 2)
            : 0;

        $leadQualificationRate = $leadStats['leads'] > 0
            ? round(($leadStats['qualified_leads'] / $leadStats['leads']) * 100, 2)
            : 0;

        $leadConversionRate = $leadStats['qualified_leads'] > 0
            ? round(($leadStats['conversions'] / $leadStats['qualified_leads']) * 100, 2)
            : 0;

        $eventAttendanceRate = $eventsStats['target_participants'] > 0
            ? round(($eventsStats['attendees'] / $eventsStats['target_participants']) * 100, 2)
            : 0;

        $campaignPerformance = (clone $campaignBaseQuery)
            ->select([
                'id',
                'name',
                'status',
                'budget',
                'actual_leads',
                'actual_conversions',
            ])
            ->latest('start_date')
            ->limit(5)
            ->get();

        $adsByPlatform = (clone $adsBaseQuery)
            ->select(
                'platform',
                DB::raw('COUNT(*) as total_ads'),
                DB::raw('SUM(spend) as total_spend'),
                DB::raw('SUM(leads) as total_leads'),
                DB::raw('SUM(conversions) as total_conversions')
            )
            ->groupBy('platform')
            ->orderByDesc('total_leads')
            ->get();

        $leadSourcesSummary = (clone $leadSourcesBaseQuery)
            ->select(
                'source_type',
                DB::raw('SUM(leads) as total_leads'),
                DB::raw('SUM(qualified_leads) as total_qualified_leads'),
                DB::raw('SUM(conversions) as total_conversions'),
                DB::raw('SUM(revenue) as total_revenue')
            )
            ->groupBy('source_type')
            ->orderByDesc('total_leads')
            ->get();

        $eventPerformance = (clone $eventsBaseQuery)
            ->select([
                'id',
                'name',
                'event_type',
                'event_date',
                'attendees',
                'leads_generated',
                'conversions',
                'status',
            ])
            ->latest('event_date')
            ->limit(5)
            ->get();

        $monthlyLeadTrend = $this->buildMonthlyLeadTrend($dateFrom, $dateTo);

        return view('marketing.dashboard', compact(
            'dateFrom',
            'dateTo',
            'campaignStats',
            'adsStats',
            'eventsStats',
            'leadStats',
            'overallCtr',
            'overallCpc',
            'overallCpl',
            'leadQualificationRate',
            'leadConversionRate',
            'eventAttendanceRate',
            'campaignPerformance',
            'adsByPlatform',
            'leadSourcesSummary',
            'eventPerformance',
            'monthlyLeadTrend'
        ));
    }

    protected function buildMonthlyLeadTrend(string $dateFrom, string $dateTo): array
    {
        $start = Carbon::parse($dateFrom)->startOfMonth();
        $end = Carbon::parse($dateTo)->startOfMonth();

        $periods = [];
        $cursor = $start->copy();

        while ($cursor <= $end) {
            $key = $cursor->format('Y-m');
            $periods[$key] = [
                'label' => $cursor->format('M Y'),
                'leads' => 0,
                'qualified_leads' => 0,
                'conversions' => 0,
                'revenue' => 0,
            ];
            $cursor->addMonth();
        }

        $rows = MarketingLeadSource::query()
            ->selectRaw("DATE_FORMAT(date, '%Y-%m') as month_key")
            ->selectRaw('SUM(leads) as total_leads')
            ->selectRaw('SUM(qualified_leads) as total_qualified_leads')
            ->selectRaw('SUM(conversions) as total_conversions')
            ->selectRaw('SUM(revenue) as total_revenue')
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->groupBy('month_key')
            ->orderBy('month_key')
            ->get();

        foreach ($rows as $row) {
            if (isset($periods[$row->month_key])) {
                $periods[$row->month_key]['leads'] = (int) $row->total_leads;
                $periods[$row->month_key]['qualified_leads'] = (int) $row->total_qualified_leads;
                $periods[$row->month_key]['conversions'] = (int) $row->total_conversions;
                $periods[$row->month_key]['revenue'] = (float) $row->total_revenue;
            }
        }

        return [
            'labels' => array_column($periods, 'label'),
            'leads' => array_column($periods, 'leads'),
            'qualified_leads' => array_column($periods, 'qualified_leads'),
            'conversions' => array_column($periods, 'conversions'),
            'revenue' => array_column($periods, 'revenue'),
        ];
    }
}