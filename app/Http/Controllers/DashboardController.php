<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\ManagementDashboardService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected ManagementDashboardService $managementDashboardService
    ) {
    }

    /**
     * Menampilkan Management Dashboard.
     *
     * Filter month menggunakan format Y-m dan dibatasi pada bulan
     * yang sudah berjalan di tahun saat ini. KPI bisnis mengikuti
     * bulan terpilih, sementara snapshot Kommo, Trello, dan status
     * pembaruan integrasi tetap menggunakan kondisi terbaru.
     */
    public function index(Request $request): View
    {
        return view(
            'dashboard',
            $this->managementDashboardService->getData(
                $request->only([
                    'month',
                ])
            )
        );
    }
}
