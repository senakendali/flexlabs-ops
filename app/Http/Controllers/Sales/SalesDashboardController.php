<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\SalesDashboardService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalesDashboardController extends Controller
{
    /**
     * Menampilkan dashboard Sales.
     */
    public function index(
        Request $request,
        SalesDashboardService $salesDashboardService
    ): View {
        $filters = $request->only([
            'date_from',
            'date_to',
        ]);

        return view(
            'sales.dashboard.index',
            $salesDashboardService->getData($filters)
        );
    }

    /**
     * Menyediakan data chart dashboard Sales dalam format JSON.
     */
    public function chartData(
        Request $request,
        SalesDashboardService $salesDashboardService
    ): JsonResponse {
        $filters = $request->only([
            'date_from',
            'date_to',
        ]);

        return response()->json([
            'success' => true,
            'data' => $salesDashboardService->getChartData($filters),
        ]);
    }
}