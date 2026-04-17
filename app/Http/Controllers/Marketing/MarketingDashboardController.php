<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\MarketingActivity;
use App\Models\MarketingAd;
use App\Models\MarketingCampaign;
use App\Models\MarketingEvent;
use App\Models\MarketingLeadSource;
use App\Models\MarketingPlan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketingDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->endOfMonth()->toDateString());

        $plansCount = MarketingPlan::query()->count();
        $campaignsCount = MarketingCampaign::query()->count();
        $activitiesCount = MarketingActivity::query()
            ->whereBetween('activity_date', [$dateFrom, $dateTo])
            ->count();

        $adsCount = MarketingAd::query()->count();
        $eventsCount = MarketingEvent::query()
            ->whereBetween('event_date', [$dateFrom, $dateTo])
            ->count();

        $leadsCount = MarketingLeadSource::query()
            ->whereBetween('lead_date', [$dateFrom, $dateTo])
            ->count();

        $campaignStatusSummary = MarketingCampaign::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $activityChannelSummary = MarketingActivity::query()
            ->selectRaw('channel, COUNT(*) as total')
            ->whereBetween('activity_date', [$dateFrom, $dateTo])
            ->groupBy('channel')
            ->pluck('total', 'channel');

        $leadSourceSummary = MarketingLeadSource::query()
            ->selectRaw('source, COUNT(*) as total')
            ->whereBetween('lead_date', [$dateFrom, $dateTo])
            ->groupBy('source')
            ->pluck('total', 'source');

        $recentActivities = MarketingActivity::query()
            ->with(['campaign', 'pic'])
            ->latest('activity_date')
            ->limit(10)
            ->get();

        return view('marketing.dashboard', compact(
            'dateFrom',
            'dateTo',
            'plansCount',
            'campaignsCount',
            'activitiesCount',
            'adsCount',
            'eventsCount',
            'leadsCount',
            'campaignStatusSummary',
            'activityChannelSummary',
            'leadSourceSummary',
            'recentActivities'
        ));
    }
}