<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\Dashboard\HrDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HrDashboardController extends Controller
{
    public function __construct(
        protected HrDashboardService $hrDashboardService
    ) {
    }

    /**
     * Menampilkan HR Dashboard Overview.
     *
     * Supported filters:
     * - date_from: Y-m-d
     * - date_to: Y-m-d
     * - work_team: optional
     */
    public function index(Request $request): View
    {
        $filters = $this->dashboardFilters($request);

        return view(
            'hr.dashboard.index',
            $this->hrDashboardService->getData($filters)
        );
    }

    /**
     * Menyediakan data chart HR Dashboard secara asynchronous.
     */
    public function chartData(Request $request): JsonResponse
    {
        $filters = $this->dashboardFilters($request);

        return response()->json([
            'success' => true,
            'data' => $this->hrDashboardService
                ->getChartData($filters),
            'refreshed_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Menampilkan Monthly Attendance Report.
     *
     * Supported filters:
     * - month: Y-m
     * - work_team: optional
     */
    public function monthlyReport(Request $request): View
    {
        $filters = $this->monthlyReportFilters($request);

        return view(
            'hr.attendance-reports.monthly',
            $this->hrDashboardService
                ->getMonthlyReportData($filters)
        );
    }

    /**
     * Menyediakan data Monthly Attendance Report secara asynchronous.
     */
    public function monthlyReportData(
        Request $request
    ): JsonResponse {
        $filters = $this->monthlyReportFilters($request);

        return response()->json([
            'success' => true,
            'data' => $this->hrDashboardService
                ->getMonthlyReportData($filters),
            'refreshed_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Menampilkan attendance detail untuk satu employee.
     *
     * Supported filters:
     * - month: Y-m
     */
    public function employeeDetail(
        Request $request,
        Employee $employee
    ): View {
        $filters = $this->employeeDetailFilters($request);

        return view(
            'hr.employees.attendance-detail',
            $this->hrDashboardService
                ->getEmployeeDetailData(
                    employeeId: $employee->id,
                    filters: $filters
                )
        );
    }

    /**
     * Menyediakan employee attendance detail secara asynchronous.
     */
    public function employeeDetailData(
        Request $request,
        Employee $employee
    ): JsonResponse {
        $filters = $this->employeeDetailFilters($request);

        return response()->json([
            'success' => true,
            'data' => $this->hrDashboardService
                ->getEmployeeDetailData(
                    employeeId: $employee->id,
                    filters: $filters
                ),
            'refreshed_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Validasi dan normalisasi filter HR Dashboard Overview.
     */
    protected function dashboardFilters(
        Request $request
    ): array {
        $validated = $request->validate([
            'date_from' => [
                'nullable',
                'date_format:Y-m-d',
            ],
            'date_to' => [
                'nullable',
                'date_format:Y-m-d',
            ],
            'work_team' => [
                'nullable',
                'string',
                'max:150',
            ],
        ]);

        return [
            'date_from' => $this->normalizedString(
                $validated['date_from'] ?? null
            ),
            'date_to' => $this->normalizedString(
                $validated['date_to'] ?? null
            ),
            'work_team' => $this->normalizedString(
                $validated['work_team'] ?? null
            ),
        ];
    }

    /**
     * Validasi dan normalisasi filter Monthly Attendance Report.
     */
    protected function monthlyReportFilters(
        Request $request
    ): array {
        $validated = $request->validate([
            'month' => [
                'nullable',
                'date_format:Y-m',
            ],
            'work_team' => [
                'nullable',
                'string',
                'max:150',
            ],
        ]);

        return [
            'month' => $this->normalizedString(
                $validated['month'] ?? null
            ),
            'work_team' => $this->normalizedString(
                $validated['work_team'] ?? null
            ),
        ];
    }

    /**
     * Validasi dan normalisasi filter Employee Attendance Detail.
     */
    protected function employeeDetailFilters(
        Request $request
    ): array {
        $validated = $request->validate([
            'month' => [
                'nullable',
                'date_format:Y-m',
            ],
        ]);

        return [
            'month' => $this->normalizedString(
                $validated['month'] ?? null
            ),
        ];
    }

    protected function normalizedString(
        mixed $value
    ): ?string {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
