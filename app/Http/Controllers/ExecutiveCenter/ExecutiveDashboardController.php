<?php

namespace App\Http\Controllers\ExecutiveCenter;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\ExecutiveDashboardService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExecutiveDashboardController extends Controller
{
    public function __construct(
        private readonly ExecutiveDashboardService $executiveDashboardService
    ) {}

    /**
     * Menampilkan Executive Dashboard untuk periode bulanan terpilih.
     *
     * Halaman ini hanya menjadi presentation layer. Seluruh penggabungan
     * target, actual, status KPI, dan business attention ditangani oleh
     * ExecutiveDashboardService.
     */
    public function index(Request $request): View
    {
        return view(
            'executive-center.dashboard',
            $this->executiveDashboardService->getData(
                $this->validatedFilters($request)
            )
        );
    }

    /**
     * Menyediakan data Executive Dashboard untuk pembaruan periode secara
     * asynchronous tanpa me-reload seluruh halaman.
     */
    public function data(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->executiveDashboardService->getData(
                $this->validatedFilters($request)
            ),
        ]);
    }

    public function brief(Request $request): View
    {
        return view(
            'executive-center.ai-executive-brief',
            $this->executiveDashboardService->getData(
                $this->validatedBriefFilters($request)
            )
        );
    }

    public function briefData(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->executiveDashboardService->getData(
                $this->validatedBriefFilters($request)
            ),
        ]);
    }

    /**
     * Validate and normalize the dashboard filters shared by the page and
     * asynchronous endpoint.
     *
     * @return array{month: string}
     */
    private function validatedFilters(Request $request): array
    {
        $validated = $request->validate([
            'month' => [
                'nullable',
                'date_format:Y-m',
            ],
        ], [
            'month.date_format' => 'Format periode harus menggunakan format YYYY-MM.',
        ]);

        return [
            'month' => $validated['month'] ?? now()->format('Y-m'),
        ];
    }

    /** @return array{month: string} */
    private function validatedBriefFilters(Request $request): array
    {
        $validated = $request->validate([
            'period' => ['nullable', 'date_format:Y-m'],
        ], [
            'period.date_format' => 'Format periode harus menggunakan format YYYY-MM.',
        ]);

        return ['month' => $validated['period'] ?? now()->format('Y-m')];
    }
}
