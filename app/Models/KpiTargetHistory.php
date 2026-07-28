<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiTargetHistory extends Model
{
    public const ACTION_CREATED = 'created';
    public const ACTION_UPDATED = 'updated';
    public const ACTION_COPIED = 'copied';
    public const ACTION_STATUS_CHANGED = 'status_changed';
    public const ACTION_LOCKED = 'locked';
    public const ACTION_UNLOCKED = 'unlocked';
    public const ACTION_DELETED = 'deleted';
    public const ACTION_RESTORED = 'restored';

    protected $fillable = [
        'kpi_target_id',
        'action',
        'old_values',
        'new_values',
        'notes',
        'changed_by',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function target(): BelongsTo
    {
        return $this->belongsTo(KpiTarget::class, 'kpi_target_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function scopeForAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->latest('created_at');
    }
}
