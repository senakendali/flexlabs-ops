<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\SalesDailyReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalesDailyReportController extends Controller
{
    public function index(Request $request): View
    {
        $query = SalesDailyReport::with('creator')
            ->latest('report_date')
            ->latest('id');

        if ($request->filled('date_from')) {
            $query->whereDate('report_date', '>=', $request->string('date_from')->toString());
        }

        if ($request->filled('date_to')) {
            $query->whereDate('report_date', '<=', $request->string('date_to')->toString());
        }

        $reports = $query->paginate(10)->withQueryString();

        return view('sales.daily-reports.index', [
            'reports' => $reports,
            'filters' => [
                'date_from' => $request->string('date_from')->toString(),
                'date_to' => $request->string('date_to')->toString(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('sales.daily-reports.form', [
            'report' => new SalesDailyReport([
                'report_date' => now()->toDateString(),
                'total_leads' => 0,
                'interacted' => 0,
                'ignored' => 0,
                'closed_lost' => 0,
                'not_related' => 0,
                'warm_leads' => 0,
                'hot_leads' => 0,
                'consultation' => 0,
                'closed_deal' => 0,
                'revenue' => 0,
            ]),
            'isEdit' => false,
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $this->validateRequest($request);
        $validated['created_by'] = auth()->id();

        $report = SalesDailyReport::create($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Sales daily report berhasil dibuat.',
                'data' => $report->load('creator'),
            ]);
        }

        return redirect()
            ->route('sales-daily-reports.index')
            ->with('success', 'Sales daily report berhasil dibuat.');
    }

    public function show(SalesDailyReport $salesDailyReport): View
    {
        $salesDailyReport->load('creator');

        return view('sales.daily-reports.show', [
            'report' => $salesDailyReport,
        ]);
    }

    public function edit(SalesDailyReport $salesDailyReport): View
    {
        return view('sales.daily-reports.form', [
            'report' => $salesDailyReport,
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, SalesDailyReport $salesDailyReport): JsonResponse|RedirectResponse
    {
        $validated = $this->validateRequest($request);

        $salesDailyReport->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Sales daily report berhasil diperbarui.',
                'data' => $salesDailyReport->fresh()->load('creator'),
            ]);
        }

        return redirect()
            ->route('sales-daily-reports.index')
            ->with('success', 'Sales daily report berhasil diperbarui.');
    }

    public function destroy(Request $request, SalesDailyReport $salesDailyReport): JsonResponse|RedirectResponse
    {
        $salesDailyReport->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Sales daily report berhasil dihapus.',
            ]);
        }

        return redirect()
            ->route('sales-daily-reports.index')
            ->with('success', 'Sales daily report berhasil dihapus.');
    }

    protected function validateRequest(Request $request): array
    {
        return $request->validate([
            'report_date' => ['required', 'date'],

            'total_leads' => ['required', 'integer', 'min:0'],
            'interacted' => ['required', 'integer', 'min:0'],
            'ignored' => ['required', 'integer', 'min:0'],
            'closed_lost' => ['required', 'integer', 'min:0'],
            'not_related' => ['required', 'integer', 'min:0'],
            'warm_leads' => ['required', 'integer', 'min:0'],
            'hot_leads' => ['required', 'integer', 'min:0'],
            'consultation' => ['required', 'integer', 'min:0'],
            'closed_deal' => ['required', 'integer', 'min:0'],
            'revenue' => ['required', 'numeric', 'min:0'],

            'summary' => ['nullable', 'string'],
            'highlight' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}