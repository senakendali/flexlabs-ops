<?php

namespace App\Http\Controllers\ExecutiveCenter;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\ExecutiveBusinessAttentionService;
use App\Services\Dashboard\ExecutiveKpiScorecardService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BusinessAttentionController extends Controller
{
    public function __construct(private readonly ExecutiveBusinessAttentionService $service) {}

    public function index(Request $request): View
    {
        return view('executive-center.business-attention', $this->data($request));
    }

    public function json(Request $request): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->data($request)]);
    }

    private function data(Request $request): array
    {
        $divisions = ['all', ...array_keys(ExecutiveKpiScorecardService::DIVISIONS)];
        $validated = $request->validate([
            'period' => ['nullable', 'date_format:Y-m'], 'division' => ['nullable', 'in:'.implode(',', $divisions)],
            'state' => ['nullable', 'in:'.implode(',', ExecutiveBusinessAttentionService::STATES)], 'issue' => ['nullable', 'alpha_dash:ascii', 'max:100'],
        ]);

        return $this->service->getData($validated['period'] ?? now()->format('Y-m'), $validated['division'] ?? 'all', $validated['state'] ?? 'open', $validated['issue'] ?? null);
    }
}
