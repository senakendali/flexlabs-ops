<?php

namespace App\Http\Controllers\ExecutiveCenter;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExecutiveCenter\GenerateStrategicReportRequest;
use App\Models\StrategicReport;
use App\Services\StrategicReports\StrategicReportPdfService;
use App\Services\StrategicReports\StrategicReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class StrategicReportController extends Controller
{
    public function __construct(private readonly StrategicReportService $service, private readonly StrategicReportPdfService $pdfService) {}

    public function index(Request $request): View
    {
        $this->guard($request);
        $filters = $request->validate(['type' => ['nullable', 'in:monthly,quarterly'], 'year' => ['nullable', 'integer', 'min:2020', 'max:2100'], 'status' => ['nullable', 'in:draft,finalized'], 'search' => ['nullable', 'string', 'max:100']]);

        return view('executive-center.strategic-reports.index', $this->service->library($filters));
    }

    public function store(GenerateStrategicReportRequest $request): JsonResponse|RedirectResponse
    {
        $report = $this->service->generate($request->validated('period_type'), $request->validated('period'), $request->user());
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'redirect' => route('executive-center.strategic-reports.show', $report)]);
        }

        return redirect()->route('executive-center.strategic-reports.show', $report);
    }

    public function show(Request $request, StrategicReport $strategicReport): View
    {
        $this->guard($request);

        return view('executive-center.strategic-reports.show', ['report' => $strategicReport->load(['generatedBy:id,name', 'finalizedBy:id,name'])]);
    }

    public function regenerate(Request $request, StrategicReport $strategicReport): JsonResponse
    {
        $this->guard($request);
        $report = $this->service->regenerate($strategicReport, $request->user());

        return response()->json(['success' => true, 'redirect' => route('executive-center.strategic-reports.show', $report)]);
    }

    public function finalize(Request $request, StrategicReport $strategicReport): JsonResponse
    {
        $this->guard($request);
        $report = $this->service->finalize($strategicReport, $request->user());

        return response()->json(['success' => true, 'redirect' => route('executive-center.strategic-reports.show', $report)]);
    }

    public function pdf(Request $request, StrategicReport $strategicReport): Response
    {
        $this->guard($request);

        return $this->pdfService->download($strategicReport);
    }

    private function guard(Request $request): void
    {
        abort_unless($request->user() && in_array((string) $request->user()->role, ['super_admin', 'admin'], true), 403);
    }
}
