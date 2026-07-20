<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\AcademicDashboardService;
use Illuminate\Contracts\View\View;

class AcademicDashboardController extends Controller
{
    public function index(
        AcademicDashboardService $academicDashboardService
    ): View {
        return view(
            'academic.dashboard.index',
            $academicDashboardService->getData()
        );
    }
}