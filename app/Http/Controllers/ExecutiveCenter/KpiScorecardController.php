<?php

namespace App\Http\Controllers\ExecutiveCenter;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\ExecutiveKpiScorecardService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class KpiScorecardController extends Controller
{
    public function __construct(private readonly ExecutiveKpiScorecardService $scorecardService) {}

    public function __invoke(Request $request): View
    {
        $validated = $request->validate([
            'period' => ['nullable', 'date_format:Y-m'],
            'division' => ['nullable', 'in:'.implode(',', array_keys(ExecutiveKpiScorecardService::DIVISIONS))],
        ], [
            'period.date_format' => 'Format periode harus menggunakan format YYYY-MM.',
            'division.in' => 'Division KPI tidak dikenali.',
        ]);

        return view('executive-center.kpi-scorecard', $this->scorecardService->getData(
            $validated['period'] ?? now()->format('Y-m'),
            $validated['division'] ?? 'company'
        ));
    }
}
