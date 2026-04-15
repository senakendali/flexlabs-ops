<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\SalesDailyReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalesPerformanceController extends Controller
{
    public function index(Request $request): View
    {
        $dateFrom = $request->input('date_from', now()->subDays(29)->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $reports = SalesDailyReport::query()
            ->whereBetween('report_date', [$dateFrom, $dateTo])
            ->orderBy('report_date')
            ->get();

        $totals = [
            'total_leads' => (int) $reports->sum('total_leads'),
            'interacted' => (int) $reports->sum('interacted'),
            'ignored' => (int) $reports->sum('ignored'),
            'closed_lost' => (int) $reports->sum('closed_lost'),
            'not_related' => (int) $reports->sum('not_related'),
            'warm_leads' => (int) $reports->sum('warm_leads'),
            'hot_leads' => (int) $reports->sum('hot_leads'),
            'consultation' => (int) $reports->sum('consultation'),
        ];

        $interactionRate = $totals['total_leads'] > 0
            ? round(($totals['interacted'] / $totals['total_leads']) * 100, 1)
            : 0;

        $consultationRate = $totals['total_leads'] > 0
            ? round(($totals['consultation'] / $totals['total_leads']) * 100, 1)
            : 0;

        $hotLeadRate = $totals['total_leads'] > 0
            ? round(($totals['hot_leads'] / $totals['total_leads']) * 100, 1)
            : 0;

        return view('sales.performance.index', [
            'totals' => $totals,
            'reports' => $reports,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'kpis' => [
                'interaction_rate' => $interactionRate,
                'consultation_rate' => $consultationRate,
                'hot_lead_rate' => $hotLeadRate,
            ],
        ]);
    }

    public function chartData(Request $request): JsonResponse
    {
        $dateFrom = $request->input('date_from', now()->subDays(29)->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $reports = SalesDailyReport::query()
            ->whereBetween('report_date', [$dateFrom, $dateTo])
            ->orderBy('report_date')
            ->get();

        return response()->json([
            'labels' => $reports->map(fn ($report) => $report->report_date->format('d M'))->values(),
            'datasets' => [
                'total_leads' => $reports->pluck('total_leads')->values(),
                'interacted' => $reports->pluck('interacted')->values(),
                'ignored' => $reports->pluck('ignored')->values(),
                'consultation' => $reports->pluck('consultation')->values(),
                'hot_leads' => $reports->pluck('hot_leads')->values(),
            ],
            'summary' => [
                'total_leads' => (int) $reports->sum('total_leads'),
                'interacted' => (int) $reports->sum('interacted'),
                'ignored' => (int) $reports->sum('ignored'),
                'consultation' => (int) $reports->sum('consultation'),
                'hot_leads' => (int) $reports->sum('hot_leads'),
            ],
        ]);
    }
}