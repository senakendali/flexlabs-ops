<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\MarketingReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MarketingReportController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $status = $request->input('status');
        $periodType = $request->input('period_type');
        $perPage = (int) $request->input('per_page', 10);

        $allowedPerPage = [10, 25, 50, 100];
        $perPage = in_array($perPage, $allowedPerPage, true) ? $perPage : 10;

        $reports = MarketingReport::query()
            ->with(['creator', 'updater'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', '%' . $search . '%')
                        ->orWhere('summary', 'like', '%' . $search . '%')
                        ->orWhere('key_insight', 'like', '%' . $search . '%')
                        ->orWhere('next_action', 'like', '%' . $search . '%')
                        ->orWhere('notes', 'like', '%' . $search . '%');
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($periodType, fn ($query) => $query->where('period_type', $periodType))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('marketing.reports.index', compact(
            'reports',
            'search',
            'status',
            'periodType',
            'perPage'
        ));
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $payload = $this->preparePayload($request, $validated, true);

        $report = MarketingReport::create($payload);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Marketing report berhasil ditambahkan.',
                'data' => [
                    'id' => $report->id,
                    'title' => $report->title,
                    'slug' => $report->slug,
                ],
            ]);
        }

        return redirect()
            ->route('marketing.reports.index')
            ->with('success', 'Marketing report berhasil ditambahkan.');
    }

    public function show(MarketingReport $marketingReport): View
    {
        $marketingReport->load(['creator', 'updater']);

        return view('marketing.reports.show', compact('marketingReport'));
    }

    public function update(Request $request, MarketingReport $marketingReport): JsonResponse|RedirectResponse
    {
        $validated = $request->validate($this->rules($marketingReport));

        $payload = $this->preparePayload($request, $validated, false, $marketingReport);

        $marketingReport->update($payload);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Marketing report berhasil diperbarui.',
                'data' => [
                    'id' => $marketingReport->id,
                    'title' => $marketingReport->title,
                    'slug' => $marketingReport->slug,
                ],
            ]);
        }

        return redirect()
            ->route('marketing.reports.show', $marketingReport)
            ->with('success', 'Marketing report berhasil diperbarui.');
    }

    public function destroy(Request $request, MarketingReport $marketingReport): JsonResponse|RedirectResponse
    {
        $marketingReport->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Marketing report berhasil dihapus.',
            ]);
        }

        return redirect()
            ->route('marketing.reports.index')
            ->with('success', 'Marketing report berhasil dihapus.');
    }

    protected function rules(?MarketingReport $marketingReport = null): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'period_type' => [
                'required',
                'string',
                Rule::in(['weekly', 'monthly']),
            ],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],

            'total_leads' => ['nullable', 'integer', 'min:0'],
            'qualified_leads' => ['nullable', 'integer', 'min:0'],
            'total_conversions' => ['nullable', 'integer', 'min:0'],

            'total_revenue' => ['nullable', 'numeric', 'min:0'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'actual_spend' => ['nullable', 'numeric', 'min:0'],

            'summary' => ['nullable', 'string'],
            'key_insight' => ['nullable', 'string'],
            'next_action' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],

            'status' => [
                'required',
                'string',
                Rule::in(['draft', 'published', 'archived']),
            ],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function preparePayload(
        Request $request,
        array $validated,
        bool $isCreate = true,
        ?MarketingReport $marketingReport = null
    ): array {
        $validated['title'] = trim($validated['title']);

        $validated['summary'] = isset($validated['summary'])
            ? trim((string) $validated['summary'])
            : null;

        $validated['key_insight'] = isset($validated['key_insight'])
            ? trim((string) $validated['key_insight'])
            : null;

        $validated['next_action'] = isset($validated['next_action'])
            ? trim((string) $validated['next_action'])
            : null;

        $validated['notes'] = isset($validated['notes'])
            ? trim((string) $validated['notes'])
            : null;

        $validated['total_leads'] = isset($validated['total_leads']) && $validated['total_leads'] !== ''
            ? (int) $validated['total_leads']
            : 0;

        $validated['qualified_leads'] = isset($validated['qualified_leads']) && $validated['qualified_leads'] !== ''
            ? (int) $validated['qualified_leads']
            : 0;

        $validated['total_conversions'] = isset($validated['total_conversions']) && $validated['total_conversions'] !== ''
            ? (int) $validated['total_conversions']
            : 0;

        $validated['total_revenue'] = isset($validated['total_revenue']) && $validated['total_revenue'] !== ''
            ? (float) $validated['total_revenue']
            : 0;

        $validated['budget'] = isset($validated['budget']) && $validated['budget'] !== ''
            ? (float) $validated['budget']
            : 0;

        $validated['actual_spend'] = isset($validated['actual_spend']) && $validated['actual_spend'] !== ''
            ? (float) $validated['actual_spend']
            : 0;

        // Jaga supaya data summary tetap masuk akal
        if ($validated['qualified_leads'] > $validated['total_leads']) {
            $validated['qualified_leads'] = $validated['total_leads'];
        }

        if ($validated['total_conversions'] > $validated['qualified_leads']) {
            $validated['total_conversions'] = $validated['qualified_leads'];
        }

        if ($isCreate) {
            $validated['slug'] = MarketingReport::generateUniqueSlug($validated['title']);
            $validated['created_by'] = auth()->id();
            $validated['is_active'] = $request->boolean('is_active', true);
        } else {
            $titleChanged = $marketingReport && $marketingReport->title !== $validated['title'];

            if ($titleChanged) {
                $validated['slug'] = MarketingReport::generateUniqueSlug(
                    $validated['title'],
                    $marketingReport?->id
                );
            }

            $validated['is_active'] = $request->boolean('is_active', false);
        }

        $validated['updated_by'] = auth()->id();

        return $validated;
    }
}