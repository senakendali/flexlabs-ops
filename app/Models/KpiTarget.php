<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class KpiTarget extends Model
{
    use SoftDeletes;

    public const SCOPE_COMPANY = 'company';
    public const SCOPE_DIVISION = 'division';
    public const SCOPE_PROGRAM = 'program';
    public const SCOPE_BATCH = 'batch';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_LOCKED = 'locked';

    protected $fillable = [
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
    ];

    protected $casts = [
        'period_month' => 'date',
        'target_value' => 'decimal:4',
        'activated_at' => 'datetime',
        'locked_at' => 'datetime',
    ];

    public function kpiDefinition(): BelongsTo
    {
        return $this->belongsTo(KpiDefinition::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function sourceTarget(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_target_id');
    }

    public function copiedTargets(): HasMany
    {
        return $this->hasMany(self::class, 'source_target_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(KpiTargetHistory::class)
            ->orderByDesc('created_at');
    }

    public function latestHistory(): HasOne
    {
        return $this->hasOne(KpiTargetHistory::class)->latestOfMany();
    }

    public function scopeForPeriod(
        Builder $query,
        CarbonInterface|string $period
    ): Builder {
        $periodMonth = $period instanceof CarbonInterface
            ? $period->copy()->startOfMonth()->toDateString()
            : Carbon::parse($period)->startOfMonth()->toDateString();

        return $query->whereDate('period_month', $periodMonth);
    }

    public function scopeForScope(
        Builder $query,
        string $scopeType,
        string|int $scopeIdentifier
    ): Builder {
        return $query
            ->where('scope_type', $scopeType)
            ->where('scope_identifier', (string) $scopeIdentifier);
    }

    public function scopeCompany(Builder $query): Builder
    {
        return $query->forScope(self::SCOPE_COMPANY, self::SCOPE_COMPANY);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeLocked(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_LOCKED);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isLocked(): bool
    {
        return $this->status === self::STATUS_LOCKED;
    }

    public function canBeEdited(): bool
    {
        return ! $this->isLocked();
    }
}
