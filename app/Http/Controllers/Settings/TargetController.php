<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreKpiTargetRequest;
use App\Http\Requests\Settings\UpdateKpiTargetRequest;
use App\Http\Requests\Settings\UpdateKpiTargetStatusRequest;
use App\Models\KpiDefinition;
use App\Models\KpiTarget;
use App\Services\TargetService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TargetController extends Controller
{
    public function __construct(
        private readonly TargetService $targetService
    ) {
    }

    /**
     * Display monthly KPI targets and the data required by the target form.
     */
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'period' => [
                'nullable',
                'date_format:Y-m',
            ],
            'kpi_definition_id' => [
                'nullable',
                'integer',
                Rule::exists('kpi_definitions', 'id')
                    ->whereNull('deleted_at')
                    ->where('is_active', true),
            ],
            'division' => [
                'nullable',
                'string',
                Rule::exists('kpi_definitions', 'division')
                    ->whereNull('deleted_at')
                    ->where('is_active', true),
            ],
            'scope_type' => [
                'nullable',
                'string',
                Rule::in($this->scopeTypes()),
            ],
            'status' => [
                'nullable',
                'string',
                Rule::in($this->statuses()),
            ],
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $period = $validated['period'] ?? now()->format('Y-m');
        $periodMonth = Carbon::createFromFormat('Y-m', $period)
            ->startOfMonth();

        $kpiDefinitionId = $validated['kpi_definition_id'] ?? null;
        $division = $validated['division'] ?? null;
        $scopeType = $validated['scope_type'] ?? null;
        $status = $validated['status'] ?? null;
        $search = trim((string) ($validated['search'] ?? ''));

        $targetQuery = KpiTarget::query()
            ->with([
                'kpiDefinition' => fn ($query) => $query->withTrashed(),
                'lockedBy',
                'createdBy',
                'updatedBy',
                'sourceTarget',
            ])
            ->whereHas(
                'kpiDefinition',
                fn ($query) => $query->active()
            )
            ->forPeriod($periodMonth);

        if ($kpiDefinitionId !== null) {
            $targetQuery->where(
                'kpi_definition_id',
                $kpiDefinitionId
            );
        }

        if ($division !== null) {
            $targetQuery->whereHas(
                'kpiDefinition',
                fn ($query) => $query->where('division', $division)
            );
        }

        if ($scopeType !== null) {
            $targetQuery->where('scope_type', $scopeType);
        }

        if ($status !== null) {
            $targetQuery->where('status', $status);
        }

        if ($search !== '') {
            $targetQuery->where(function ($query) use ($search): void {
                $query
                    ->where('scope_identifier', 'like', "%{$search}%")
                    ->orWhere('scope_label', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas(
                        'kpiDefinition',
                        function ($kpiQuery) use ($search): void {
                            $kpiQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%");
                        }
                    );
            });
        }

        $targets = $targetQuery
            ->orderBy('scope_type')
            ->orderBy('scope_identifier')
            ->orderBy('kpi_definition_id')
            ->get();

        $kpiDefinitions = KpiDefinition::query()
            ->active()
            ->ordered()
            ->get();

        $divisions = $kpiDefinitions
            ->pluck('division')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $summary = [
            'total' => $targets->count(),
            'draft' => $targets
                ->where('status', KpiTarget::STATUS_DRAFT)
                ->count(),
            'active' => $targets
                ->where('status', KpiTarget::STATUS_ACTIVE)
                ->count(),
            'locked' => $targets
                ->where('status', KpiTarget::STATUS_LOCKED)
                ->count(),
        ];

        return view('settings.targets.index', [
            'targets' => $targets,
            'kpiDefinitions' => $kpiDefinitions,
            /*
             * Kept temporarily for compatibility with the previous Blade.
             * The owner field is system-managed as null and will be removed
             * from the form in the Blade patch.
             */
            'owners' => collect(),
            'divisions' => $divisions,
            'summary' => $summary,
            'period' => $period,
            'periodMonth' => $periodMonth,
            'filters' => [
                'kpi_definition_id' => $kpiDefinitionId,
                'division' => $division,
                'scope_type' => $scopeType,
                'status' => $status,
                'search' => $search,
            ],
            'scopeOptions' => [
                KpiTarget::SCOPE_COMPANY => 'Company',
                KpiTarget::SCOPE_DIVISION => 'Division',
            ],
            'statusOptions' => [
                KpiTarget::STATUS_DRAFT => 'Draft',
                KpiTarget::STATUS_ACTIVE => 'Active',
                KpiTarget::STATUS_LOCKED => 'Locked',
            ],
        ]);
    }

    /**
     * Store a new monthly KPI target.
     */
    public function store(
        StoreKpiTargetRequest $request
    ): RedirectResponse|JsonResponse {
        $validated = $request->validated();

        $target = $this->targetService->create(
            $request->targetAttributes(),
            $this->actorId($request),
            $validated['history_notes'] ?? null
        );

        return $this->successResponse(
            $request,
            'Target berhasil ditambahkan.',
            ['target' => $target],
            $target->period_month?->format('Y-m')
        );
    }

    /**
     * Copy the previous month's targets into the requested month as drafts.
     */
    public function copyPreviousMonth(
        Request $request
    ): RedirectResponse|JsonResponse {
        $validated = $request->validate([
            'period_month' => [
                'required',
                'date',
            ],
            'history_notes' => [
                'nullable',
                'string',
            ],
        ], [
            'period_month.required' => 'Periode tujuan wajib diisi.',
            'period_month.date' => 'Format periode tujuan tidak valid.',
        ]);

        $result = $this->targetService->copyPreviousMonth(
            $validated['period_month'],
            $this->actorId($request),
            historyNotes: $validated['history_notes'] ?? null
        );

        $message = sprintf(
            '%d target berhasil disalin, %d target direstore, dan %d target dilewati karena sudah tersedia.',
            $result['copied_count'],
            $result['restored_count'],
            $result['skipped_count']
        );

        return $this->successResponse(
            $request,
            $message,
            ['copy_result' => $result],
            Carbon::parse($result['target_period'])->format('Y-m')
        );
    }

    /**
     * Update an existing editable target.
     */
    public function update(
        UpdateKpiTargetRequest $request,
        KpiTarget $target
    ): RedirectResponse|JsonResponse {
        $validated = $request->validated();

        $target = $this->targetService->update(
            $target,
            $request->targetAttributes(),
            $this->actorId($request),
            $validated['history_notes'] ?? null
        );

        return $this->successResponse(
            $request,
            'Target berhasil diperbarui.',
            ['target' => $target],
            $target->period_month?->format('Y-m')
        );
    }

    /**
     * Change a target between draft, active, and locked.
     */
    public function updateStatus(
        UpdateKpiTargetStatusRequest $request,
        KpiTarget $target
    ): RedirectResponse|JsonResponse {
        $validated = $request->validated();

        $target = $this->targetService->changeStatus(
            $target,
            $validated['status'],
            $this->actorId($request),
            $validated['history_notes'] ?? null
        );

        $statusLabel = [
            KpiTarget::STATUS_DRAFT => 'Draft',
            KpiTarget::STATUS_ACTIVE => 'Active',
            KpiTarget::STATUS_LOCKED => 'Locked',
        ][$target->status];

        return $this->successResponse(
            $request,
            "Status target berhasil diubah menjadi {$statusLabel}.",
            ['target' => $target],
            $target->period_month?->format('Y-m')
        );
    }

    /**
     * Return the target audit history for the history modal.
     */
    public function history(KpiTarget $target): JsonResponse
    {
        $target->load([
            'kpiDefinition' => fn ($query) => $query->withTrashed(),
            'lockedBy',
            'createdBy',
            'updatedBy',
            'sourceTarget',
        ]);

        return response()->json([
            'success' => true,
            'target' => $target,
            'histories' => $this->targetService->history($target),
        ]);
    }

    /**
     * Soft delete an editable target.
     */
    public function destroy(
        Request $request,
        KpiTarget $target
    ): RedirectResponse|JsonResponse {
        $validated = $request->validate([
            'history_notes' => [
                'nullable',
                'string',
            ],
        ]);

        $period = $target->period_month?->format('Y-m');

        $this->targetService->delete(
            $target,
            $this->actorId($request),
            $validated['history_notes'] ?? null
        );

        return $this->successResponse(
            $request,
            'Target berhasil dihapus.',
            [],
            $period
        );
    }

    /**
     * @return array<int, string>
     */
    private function scopeTypes(): array
    {
        return [
            KpiTarget::SCOPE_COMPANY,
            KpiTarget::SCOPE_DIVISION,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function statuses(): array
    {
        return [
            KpiTarget::STATUS_DRAFT,
            KpiTarget::STATUS_ACTIVE,
            KpiTarget::STATUS_LOCKED,
        ];
    }

    private function actorId(Request $request): ?int
    {
        $actorId = $request->user()?->getAuthIdentifier();

        return is_numeric($actorId)
            ? (int) $actorId
            : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function successResponse(
        Request $request,
        string $message,
        array $data = [],
        ?string $period = null
    ): RedirectResponse|JsonResponse {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                ...$data,
            ]);
        }

        if ($period !== null) {
            return redirect()
                ->route('settings.targets.index', ['period' => $period])
                ->with('success', $message);
        }

        return back()->with('success', $message);
    }
}
