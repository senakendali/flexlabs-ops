<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;

class KpiDefinition extends Model
{
    use SoftDeletes;

    public const UNIT_CURRENCY = 'currency';
    public const UNIT_NUMBER = 'number';
    public const UNIT_PERCENTAGE = 'percentage';
    public const UNIT_DECIMAL = 'decimal';

    public const DIRECTION_HIGHER = 'higher';
    public const DIRECTION_LOWER = 'lower';

    public const FREQUENCY_MONTHLY = 'monthly';
    public const FREQUENCY_QUARTERLY = 'quarterly';
    public const FREQUENCY_YEARLY = 'yearly';
    public const FREQUENCY_BATCH = 'batch';

    public const CALCULATION_AUTOMATIC = 'automatic';
    public const CALCULATION_MANUAL = 'manual';

    /**
     * KPI whose result represents the whole FlexLabs company even though the
     * accountable division remains stored on the KPI definition.
     *
     * @var array<int, string>
     */
    private const COMPANY_SCOPE_CODES = [
        'confirmed_revenue',
    ];

    protected $fillable = [
        'code',
        'name',
        'description',
        'division',
        'category',
        'unit',
        'direction',
        'frequency',
        'calculation_type',
        'data_source_key',
        'calculation_key',
        'calculation_settings',
        'sort_order',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'calculation_settings' => 'array',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function targets(): HasMany
    {
        return $this->hasMany(KpiTarget::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForDivision(Builder $query, ?string $division): Builder
    {
        if (blank($division)) {
            return $query;
        }

        return $query->where('division', $division);
    }

    public function scopeAutomatic(Builder $query): Builder
    {
        return $query->where('calculation_type', self::CALCULATION_AUTOMATIC);
    }

    public function scopeManual(Builder $query): Builder
    {
        return $query->where('calculation_type', self::CALCULATION_MANUAL);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function isAutomatic(): bool
    {
        return $this->calculation_type === self::CALCULATION_AUTOMATIC;
    }

    public function isManual(): bool
    {
        return $this->calculation_type === self::CALCULATION_MANUAL;
    }

    public function isHigherBetter(): bool
    {
        return $this->direction === self::DIRECTION_HIGHER;
    }

    public function isLowerBetter(): bool
    {
        return $this->direction === self::DIRECTION_LOWER;
    }

    /**
     * Resolve the target scope from the KPI definition.
     *
     * Scope is intentionally not accepted from form input. Confirmed Revenue
     * represents the whole company, while every other KPI follows its
     * accountable division.
     *
     * @return array{
     *     scope_type: string,
     *     scope_identifier: string,
     *     scope_label: string
     * }
     */
    public function resolveTargetScope(): array
    {
        if (in_array($this->code, self::COMPANY_SCOPE_CODES, true)) {
            return [
                'scope_type' => KpiTarget::SCOPE_COMPANY,
                'scope_identifier' => KpiTarget::SCOPE_COMPANY,
                'scope_label' => 'FlexLabs',
            ];
        }

        $division = strtolower(trim((string) $this->division));

        if ($division === '') {
            throw new LogicException(
                "KPI [{$this->code}] tidak memiliki division untuk menentukan target scope."
            );
        }

        return [
            'scope_type' => KpiTarget::SCOPE_DIVISION,
            'scope_identifier' => $division,
            'scope_label' => $this->resolveDivisionLabel($division),
        ];
    }

    private function resolveDivisionLabel(string $division): string
    {
        return match ($division) {
            'academic' => 'Academic',
            'sales' => 'Sales',
            'marketing' => 'Marketing',
            'finance' => 'Finance',
            'hr' => 'HR',
            'operations' => 'Operations',
            default => ucwords(str_replace(['_', '-'], ' ', $division)),
        };
    }
}
