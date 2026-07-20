<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\MarketingDashboardService;
use Illuminate\Contracts\View\View;

class MarketingDashboardController extends Controller
{
    public function index(
        MarketingDashboardService $marketingDashboardService
    ): View {
        return view(
            'marketing.dashboard',
            $marketingDashboardService->getData()
        );
    }
}