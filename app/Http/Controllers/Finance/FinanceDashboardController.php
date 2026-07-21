<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\FinanceDashboardService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceDashboardController extends Controller
{
    /**
     * Menampilkan dashboard Finance.
     */
    public function index(
        Request $request,
        FinanceDashboardService $financeDashboardService
    ): View {
        $filters = $request->only([
            'date_from',
            'date_to',
        ]);

        return view(
            'finance.dashboard.index',
            $financeDashboardService->getData($filters)
        );
    }

    /**
     * Menyediakan payload chart Finance Dashboard dalam format JSON.
     */
    public function chartData(
        Request $request,
        FinanceDashboardService $financeDashboardService
    ): JsonResponse {
        $filters = $request->only([
            'date_from',
            'date_to',
        ]);

        return response()->json([
            'success' => true,
            'data' => $financeDashboardService->getChartData($filters),
        ]);
    }
}
