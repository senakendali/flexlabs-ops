<?php

namespace App\Services;

use App\Models\KpiDefinition;
use App\Models\KpiTarget;
use App\Models\KpiTargetHistory;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TargetService
{
    /**
     * User-supplied attributes accepted when a target is created.
     *
     * Scope and owner are intentionally excluded because they are derived
     * from the selected KPI definition.
     */
    private const CREATE_ATTRIBUTES = [
        'kpi_definition_id',
        'period_month',
        'target_value',
        'status',
        'notes',
    ];

    /**
     * User-supplied attributes accepted through the regular update flow.
     *
     * Status changes are intentionally handled by changeStatus(). Scope and
     * owner remain system-managed.
     */
    private const UPDATE_ATTRIBUTES = [
        'kpi_definition_id',
        'period_month',
        'target_value',
        'notes',
    ];

    /**
     * Business attributes tracked when an update is audited.
     *
     * This includes the system-managed scope and owner fields because changing
     * the selected KPI may also change those values.
     */
    private const UPDATE_TRACKED_ATTRIBUTES = [
        'kpi_definition_id',
        'period_month',
        'scope_type',
        'scope_identifier',
        'scope_label',
        'target_value',
        'owner_user_id',
        'notes',
    ];

    /**
     * Target fields stored in the audit history snapshots.
     */
    private const HISTORY_ATTRIBUTES = [
        'kpi_definition_id',
        'period_month',
        'scope_type',
        'scope_identifier',
        'scope_label',
        'target_value',
        'owner_user_id',
        'status',
        'notes',
        'source_target_id',
        'activated_at',
        'locked_at',
        'locked_by',
        'created_by',
        'updated_by',
        'deleted_at',
    ];

    /**
     * Create a monthly KPI target.
     *
     * If the same KPI-period-scope combination was previously soft deleted,
     * the existing record is restored because the database unique index still
     * owns that combination.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(
        array $attributes,
        ?int $actorId = null,
        ?string $historyNotes = null
    ): KpiTarget {
        return DB::transaction(function () use (
            $attributes,
            $actorId,
            $historyNotes
        ): KpiTarget {
            $payload = $this->prepareCreatePayload($attributes);

            $existingTarget = $this->duplicateQuery($payload)
                ->withTrashed()
                ->lockForUpdate()
                ->first();

            if ($existingTarget && ! $existingTarget->trashed()) {
                $this->throwDuplicateValidationException();
            }

            if ($existingTarget && $existingTarget->trashed()) {
                $oldValues = $this->snapshot($existingTarget);

                $existingTarget->restore();
                $existingTarget->fill($payload);
                $existingTarget->source_target_id = null;
                $existingTarget->updated_by = $actorId;

                $this->applyStatusState(
                    $existingTarget,
                    $payload['status'],
                    $actorId
                );

                $existingTarget->save();

                $this->recordHistory(
                    $existingTarget,
                    KpiTargetHistory::ACTION_RESTORED,
                    $oldValues,
                    $this->snapshot($existingTarget),
                    $actorId,
                    $historyNotes
                );

                return $existingTarget->fresh([
                    'kpiDefinition',
                    'owner',
                ]);
            }

            $target = new KpiTarget();
            $target->fill($payload);
            $target->created_by = $actorId;
            $target->updated_by = $actorId;

            $this->applyStatusState(
                $target,
                $payload['status'],
                $actorId
            );

            $target->save();

            $this->recordHistory(
                $target,
                KpiTargetHistory::ACTION_CREATED,
                null,
                $this->snapshot($target),
                $actorId,
                $historyNotes
            );

            return $target->fresh([
                'kpiDefinition',
                'owner',
            ]);
        });
    }

    /**
     * Update an editable target and store only the changed business fields in
     * its audit history.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(
        KpiTarget $target,
        array $attributes,
        ?int $actorId = null,
        ?string $historyNotes = null
    ): KpiTarget {
        return DB::transaction(function () use (
            $target,
            $attributes,
            $actorId,
            $historyNotes
        ): KpiTarget {
            $target = KpiTarget::query()
                ->lockForUpdate()
                ->findOrFail($target->getKey());

            $this->ensureEditable($target);

            $payload = $this->prepareUpdatePayload($target, $attributes);

            $this->ensureCombinationIsAvailable(
                $payload,
                $target->getKey()
            );

            $target->fill($payload);

            $changedAttributes = array_keys(
                array_intersect_key(
                    $target->getDirty(),
                    array_flip(self::UPDATE_TRACKED_ATTRIBUTES)
                )
            );

            if ($changedAttributes === []) {
                return $target->fresh([
                    'kpiDefinition',
                    'owner',
                ]);
            }

            $oldValues = $this->valuesForAttributes(
                $target,
                $changedAttributes,
                true
            );

            $target->updated_by = $actorId;
            $target->save();

            $newValues = $this->valuesForAttributes(
                $target,
                $changedAttributes
            );

            $this->recordHistory(
                $target,
                KpiTargetHistory::ACTION_UPDATED,
                $oldValues,
                $newValues,
                $actorId,
                $historyNotes
            );

            return $target->fresh([
                'kpiDefinition',
                'owner',
            ]);
        });
    }

    /**
     * Copy targets from the previous month into the requested month.
     *
     * Existing active records in the destination month are skipped. A matching
     * soft-deleted record is restored and reused so the database unique index
     * remains valid.
     *
     * @return array{
     *     source_period: string,
     *     target_period: string,
     *     copied_count: int,
     *     restored_count: int,
     *     skipped_count: int,
     *     targets: Collection<int, KpiTarget>
     * }
     */
    public function copyPreviousMonth(
        CarbonInterface|string $targetPeriod,
        ?int $actorId = null,
        ?string $scopeType = null,
        string|int|null $scopeIdentifier = null,
        ?string $historyNotes = null
    ): array {
        return DB::transaction(function () use (
            $targetPeriod,
            $actorId,
            $scopeType,
            $scopeIdentifier,
            $historyNotes
        ): array {
            $destinationMonth = $this->normalizePeriod($targetPeriod);
            $sourceMonth = Carbon::parse($destinationMonth)
                ->subMonthNoOverflow()
                ->startOfMonth()
                ->toDateString();

            $sourceQuery = KpiTarget::query()
                ->with('kpiDefinition')
                ->whereHas(
                    'kpiDefinition',
                    fn (Builder $query): Builder => $query->active()
                )
                ->forPeriod($sourceMonth)
                ->orderBy('id');

            if ($scopeType !== null) {
                [$resolvedScopeType, $resolvedScopeIdentifier] =
                    $this->normalizeScope(
                        $scopeType,
                        $scopeIdentifier
                    );

                $sourceQuery->forScope(
                    $resolvedScopeType,
                    $resolvedScopeIdentifier
                );
            } elseif ($scopeIdentifier !== null) {
                throw ValidationException::withMessages([
                    'scope_type' => 'Scope type wajib diisi ketika scope identifier digunakan.',
                ]);
            }

            $sourceTargets = $sourceQuery
                ->lockForUpdate()
                ->get();

            $copiedTargets = collect();
            $copiedCount = 0;
            $restoredCount = 0;
            $skippedCount = 0;

            foreach ($sourceTargets as $sourceTarget) {
                $resolvedScope = $sourceTarget
                    ->kpiDefinition
                    ->resolveTargetScope();

                $destinationAttributes = [
                    'kpi_definition_id' => $sourceTarget->kpi_definition_id,
                    'period_month' => $destinationMonth,
                    'scope_type' => $resolvedScope['scope_type'],
                    'scope_identifier' =>
                        $resolvedScope['scope_identifier'],
                    'scope_label' => $resolvedScope['scope_label'],
                    'target_value' => $sourceTarget->target_value,
                    'owner_user_id' => null,
                    'status' => KpiTarget::STATUS_DRAFT,
                    'notes' => $sourceTarget->notes,
                    'source_target_id' => $sourceTarget->getKey(),
                ];

                $destinationTarget = $this
                    ->duplicateQuery($destinationAttributes)
                    ->withTrashed()
                    ->lockForUpdate()
                    ->first();

                if ($destinationTarget && ! $destinationTarget->trashed()) {
                    $skippedCount++;

                    continue;
                }

                if ($destinationTarget && $destinationTarget->trashed()) {
                    $oldValues = $this->snapshot($destinationTarget);

                    $destinationTarget->restore();
                    $destinationTarget->fill($destinationAttributes);
                    $destinationTarget->updated_by = $actorId;

                    $this->applyStatusState(
                        $destinationTarget,
                        KpiTarget::STATUS_DRAFT,
                        $actorId
                    );

                    $destinationTarget->save();

                    $this->recordHistory(
                        $destinationTarget,
                        KpiTargetHistory::ACTION_RESTORED,
                        $oldValues,
                        $this->snapshot($destinationTarget),
                        $actorId,
                        $historyNotes
                    );

                    $this->recordHistory(
                        $destinationTarget,
                        KpiTargetHistory::ACTION_COPIED,
                        null,
                        [
                            'source_target_id' => $sourceTarget->getKey(),
                            'period_month' => $destinationMonth,
                        ],
                        $actorId,
                        $historyNotes
                    );

                    $restoredCount++;
                    $copiedTargets->push($destinationTarget);

                    continue;
                }

                $destinationTarget = new KpiTarget();
                $destinationTarget->fill($destinationAttributes);
                $destinationTarget->created_by = $actorId;
                $destinationTarget->updated_by = $actorId;

                $this->applyStatusState(
                    $destinationTarget,
                    KpiTarget::STATUS_DRAFT,
                    $actorId
                );

                $destinationTarget->save();

                $this->recordHistory(
                    $destinationTarget,
                    KpiTargetHistory::ACTION_COPIED,
                    null,
                    $this->snapshot($destinationTarget),
                    $actorId,
                    $historyNotes
                );

                $copiedCount++;
                $copiedTargets->push($destinationTarget);
            }

            $copiedTargets->each->load([
                'kpiDefinition',
                'owner',
            ]);

            return [
                'source_period' => $sourceMonth,
                'target_period' => $destinationMonth,
                'copied_count' => $copiedCount,
                'restored_count' => $restoredCount,
                'skipped_count' => $skippedCount,
                'targets' => $copiedTargets,
            ];
        });
    }

    /**
     * Change a target status and maintain its activation/locking metadata.
     */
    public function changeStatus(
        KpiTarget $target,
        string $status,
        ?int $actorId = null,
        ?string $historyNotes = null
    ): KpiTarget {
        return DB::transaction(function () use (
            $target,
            $status,
            $actorId,
            $historyNotes
        ): KpiTarget {
            $target = KpiTarget::query()
                ->lockForUpdate()
                ->findOrFail($target->getKey());

            $this->ensureValidStatus($status);

            if ($target->status === $status) {
                return $target->fresh([
                    'kpiDefinition',
                    'owner',
                    'lockedBy',
                ]);
            }

            $previousStatus = $target->status;
            $oldValues = $this->valuesForAttributes(
                $target,
                [
                    'status',
                    'activated_at',
                    'locked_at',
                    'locked_by',
                ]
            );

            $this->applyStatusState($target, $status, $actorId);
            $target->updated_by = $actorId;
            $target->save();

            $action = match (true) {
                $status === KpiTarget::STATUS_LOCKED =>
                    KpiTargetHistory::ACTION_LOCKED,
                $previousStatus === KpiTarget::STATUS_LOCKED =>
                    KpiTargetHistory::ACTION_UNLOCKED,
                default =>
                    KpiTargetHistory::ACTION_STATUS_CHANGED,
            };

            $this->recordHistory(
                $target,
                $action,
                $oldValues,
                $this->valuesForAttributes(
                    $target,
                    [
                        'status',
                        'activated_at',
                        'locked_at',
                        'locked_by',
                    ]
                ),
                $actorId,
                $historyNotes
            );

            return $target->fresh([
                'kpiDefinition',
                'owner',
                'lockedBy',
            ]);
        });
    }

    /**
     * Soft delete an editable target and retain its audit history.
     */
    public function delete(
        KpiTarget $target,
        ?int $actorId = null,
        ?string $historyNotes = null
    ): void {
        DB::transaction(function () use (
            $target,
            $actorId,
            $historyNotes
        ): void {
            $target = KpiTarget::query()
                ->lockForUpdate()
                ->findOrFail($target->getKey());

            $this->ensureEditable($target);

            $oldValues = $this->snapshot($target);
            $deletedAt = now();

            $target->updated_by = $actorId;
            $target->save();

            $this->recordHistory(
                $target,
                KpiTargetHistory::ACTION_DELETED,
                $oldValues,
                [
                    'deleted_at' => $deletedAt->toISOString(),
                    'updated_by' => $actorId,
                ],
                $actorId,
                $historyNotes
            );

            $target->delete();
        });
    }

    /**
     * Return the complete newest-first audit trail for a target.
     *
     * @return Collection<int, KpiTargetHistory>
     */
    public function history(KpiTarget $target): Collection
    {
        return $target->histories()
            ->with('changedBy')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareCreatePayload(array $attributes): array
    {
        $payload = $this->only($attributes, self::CREATE_ATTRIBUTES);

        foreach ([
            'kpi_definition_id',
            'period_month',
            'target_value',
        ] as $requiredAttribute) {
            if (! array_key_exists($requiredAttribute, $payload)) {
                throw ValidationException::withMessages([
                    $requiredAttribute => "Field {$requiredAttribute} wajib diisi.",
                ]);
            }
        }

        $payload['period_month'] = $this->normalizePeriod(
            $payload['period_month']
        );

        $kpiDefinition = $this->resolveActiveKpiDefinition(
            $payload['kpi_definition_id']
        );

        $payload = array_merge(
            $payload,
            $kpiDefinition->resolveTargetScope(),
            ['owner_user_id' => null]
        );

        $payload['status'] = $payload['status']
            ?? KpiTarget::STATUS_DRAFT;

        $this->ensureValidStatus($payload['status']);

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function prepareUpdatePayload(
        KpiTarget $target,
        array $attributes
    ): array {
        $payload = $this->only($attributes, self::UPDATE_ATTRIBUTES);

        /*
         * The complete unique-key combination is retained even for PATCH
         * requests that only submit one business field.
         */
        $payload['kpi_definition_id'] = $payload['kpi_definition_id']
            ?? $target->kpi_definition_id;
        $payload['period_month'] = $payload['period_month']
            ?? $target->period_month;

        $payload['period_month'] = $this->normalizePeriod(
            $payload['period_month']
        );

        $kpiDefinition = $this->resolveActiveKpiDefinition(
            $payload['kpi_definition_id']
        );

        $payload = array_merge(
            $payload,
            $kpiDefinition->resolveTargetScope(),
            ['owner_user_id' => null]
        );

        return $payload;
    }

    /**
     * Resolve an active, non-deleted KPI definition used by a target.
     */
    private function resolveActiveKpiDefinition(
        mixed $kpiDefinitionId
    ): KpiDefinition {
        $kpiDefinition = KpiDefinition::query()
            ->active()
            ->find($kpiDefinitionId);

        if (! $kpiDefinition) {
            throw ValidationException::withMessages([
                'kpi_definition_id' =>
                    'KPI yang dipilih tidak tersedia atau sudah tidak aktif.',
            ]);
        }

        return $kpiDefinition;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function duplicateQuery(array $attributes): Builder
    {
        return KpiTarget::query()
            ->where(
                'kpi_definition_id',
                $attributes['kpi_definition_id']
            )
            ->whereDate('period_month', $attributes['period_month'])
            ->where('scope_type', $attributes['scope_type'])
            ->where(
                'scope_identifier',
                (string) $attributes['scope_identifier']
            );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function ensureCombinationIsAvailable(
        array $attributes,
        int $ignoredTargetId
    ): void {
        $duplicateAttributes = [
            'kpi_definition_id' => $attributes['kpi_definition_id'],
            'period_month' => $attributes['period_month'],
            'scope_type' => $attributes['scope_type'],
            'scope_identifier' => $attributes['scope_identifier'],
        ];

        $duplicateExists = $this
            ->duplicateQuery($duplicateAttributes)
            ->withTrashed()
            ->where('id', '!=', $ignoredTargetId)
            ->exists();

        if ($duplicateExists) {
            $this->throwDuplicateValidationException();
        }
    }

    private function throwDuplicateValidationException(): never
    {
        throw ValidationException::withMessages([
            'target' => 'Target untuk kombinasi KPI, bulan, dan scope tersebut sudah tersedia.',
        ]);
    }

    private function ensureEditable(KpiTarget $target): void
    {
        if ($target->isLocked()) {
            throw ValidationException::withMessages([
                'target' => 'Target yang sudah locked tidak dapat diubah atau dihapus.',
            ]);
        }
    }

    private function ensureValidStatus(string $status): void
    {
        if (! in_array($status, [
            KpiTarget::STATUS_DRAFT,
            KpiTarget::STATUS_ACTIVE,
            KpiTarget::STATUS_LOCKED,
        ], true)) {
            throw ValidationException::withMessages([
                'status' => 'Status target harus draft, active, atau locked.',
            ]);
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function normalizeScope(
        string $scopeType,
        string|int|null $scopeIdentifier
    ): array {
        $allowedScopeTypes = [
            KpiTarget::SCOPE_COMPANY,
            KpiTarget::SCOPE_DIVISION,
        ];

        if (! in_array($scopeType, $allowedScopeTypes, true)) {
            throw ValidationException::withMessages([
                'scope_type' => 'Scope target harus company atau division.',
            ]);
        }

        if ($scopeType === KpiTarget::SCOPE_COMPANY) {
            return [
                KpiTarget::SCOPE_COMPANY,
                KpiTarget::SCOPE_COMPANY,
            ];
        }

        if ($scopeIdentifier === null || $scopeIdentifier === '') {
            throw ValidationException::withMessages([
                'scope_identifier' => 'Scope identifier wajib diisi untuk scope selain company.',
            ]);
        }

        return [$scopeType, (string) $scopeIdentifier];
    }

    private function normalizePeriod(
        CarbonInterface|string $period
    ): string {
        return $period instanceof CarbonInterface
            ? $period->copy()->startOfMonth()->toDateString()
            : Carbon::parse($period)->startOfMonth()->toDateString();
    }

    private function applyStatusState(
        KpiTarget $target,
        string $status,
        ?int $actorId
    ): void {
        $target->status = $status;

        if ($status === KpiTarget::STATUS_DRAFT) {
            $target->activated_at = null;
            $target->locked_at = null;
            $target->locked_by = null;

            return;
        }

        if ($status === KpiTarget::STATUS_ACTIVE) {
            $target->activated_at = $target->activated_at ?? now();
            $target->locked_at = null;
            $target->locked_by = null;

            return;
        }

        $target->locked_at = now();
        $target->locked_by = $actorId;
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    private function recordHistory(
        KpiTarget $target,
        string $action,
        ?array $oldValues,
        ?array $newValues,
        ?int $actorId,
        ?string $notes
    ): KpiTargetHistory {
        return $target->histories()->create([
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'notes' => $notes,
            'changed_by' => $actorId,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(KpiTarget $target): array
    {
        return $this->valuesForAttributes(
            $target,
            self::HISTORY_ATTRIBUTES
        );
    }

    /**
     * @param  array<int, string>  $attributes
     * @return array<string, mixed>
     */
    private function valuesForAttributes(
        KpiTarget $target,
        array $attributes,
        bool $useOriginal = false
    ): array {
        $values = [];

        foreach ($attributes as $attribute) {
            $value = $useOriginal
                ? $target->getRawOriginal($attribute)
                : $target->getAttribute($attribute);

            if ($value instanceof CarbonInterface) {
                $value = $value->toISOString();
            }

            $values[$attribute] = $value;
        }

        return $values;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, string>  $allowed
     * @return array<string, mixed>
     */
    private function only(array $attributes, array $allowed): array
    {
        return array_intersect_key(
            $attributes,
            array_flip($allowed)
        );
    }
}
