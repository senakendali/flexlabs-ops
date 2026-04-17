<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class MarketingEvent extends Model
{
    protected $fillable = [
        'marketing_campaign_id',
        'name',
        'slug',
        'event_type',
        'event_date',
        'location',
        'target_audience',
        'target_participants',
        'registrants',
        'attendees',
        'leads_generated',
        'conversions',
        'budget',
        'status',
        'description',
        'notes',
        'is_active',
        'pic_user_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'event_date' => 'date',
        'budget' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function ($event) {
            if (empty($event->slug)) {
                $event->slug = Str::slug($event->name . '-' . Str::random(5));
            }
        });
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(MarketingCampaign::class, 'marketing_campaign_id');
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