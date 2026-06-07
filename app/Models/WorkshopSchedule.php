<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkshopSchedule extends Model
{
    protected $fillable = [
        'workshop_id',
        'title',
        'schedule_date',
        'start_time',
        'end_time',
        'location_type',
        'location',
        'meeting_url',
        'quota',
        'registered_count',
        'price',
        'old_price',
        'status',
        'notes',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'schedule_date' => 'date',
        'is_active' => 'boolean',
        'price' => 'decimal:2',
        'old_price' => 'decimal:2',
        'quota' => 'integer',
        'registered_count' => 'integer',
        'sort_order' => 'integer',
    ];

    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(WorkshopParticipant::class);
    }

    public function getEffectivePriceAttribute(): ?string
    {
        return $this->price ?? $this->workshop?->price;
    }

    public function getEffectiveOldPriceAttribute(): ?string
    {
        return $this->old_price ?? $this->workshop?->old_price;
    }
}