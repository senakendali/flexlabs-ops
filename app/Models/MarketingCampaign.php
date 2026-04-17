<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class MarketingCampaign extends Model
{
    protected $fillable = [
        'marketing_plan_id',
        'name',
        'slug',
        'channel',
        'type',
        'start_date',
        'end_date',
        'budget',
        'target_leads',
        'target_conversions',
        'actual_leads',
        'actual_conversions',
        'status',
        'description',
        'notes',
        'is_active',
        'pic_user_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function ($campaign) {
            if (empty($campaign->slug)) {
                $campaign->slug = Str::slug($campaign->name . '-' . Str::random(5));
            }
        });
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(MarketingPlan::class, 'marketing_plan_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(MarketingActivity::class);
    }

    public function ads(): HasMany
    {
        return $this->hasMany(MarketingAd::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(MarketingEvent::class);
    }

    public function leadSources(): HasMany
    {
        return $this->hasMany(MarketingLeadSource::class);
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}