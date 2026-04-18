<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingAd extends Model
{
    protected $fillable = [
        'marketing_campaign_id',
        'platform',
        'ad_name',
        'start_date',
        'end_date',
        'budget',
        'spend',
        'impressions',
        'clicks',
        'leads',
        'conversions',
        'status',
        'notes',
        'is_active',
        'source_type',
        'external_reference',
        'utm_source',
        'utm_campaign',
        'utm_content',
        'pic_user_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
        'spend' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'ctr',
        'cpc',
        'cpl',
        'conversion_rate',
        'duration_days',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(MarketingCampaign::class, 'marketing_campaign_id');
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

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getCtrAttribute(): float
    {
        $impressions = (int) ($this->impressions ?? 0);
        $clicks = (int) ($this->clicks ?? 0);

        if ($impressions <= 0) {
            return 0;
        }

        return round(($clicks / $impressions) * 100, 2);
    }

    public function getCpcAttribute(): float
    {
        $clicks = (int) ($this->clicks ?? 0);
        $spend = (float) ($this->spend ?? 0);

        if ($clicks <= 0) {
            return 0;
        }

        return round($spend / $clicks, 2);
    }

    public function getCplAttribute(): float
    {
        $leads = (int) ($this->leads ?? 0);
        $spend = (float) ($this->spend ?? 0);

        if ($leads <= 0) {
            return 0;
        }

        return round($spend / $leads, 2);
    }

    public function getConversionRateAttribute(): float
    {
        $leads = (int) ($this->leads ?? 0);
        $conversions = (int) ($this->conversions ?? 0);

        if ($leads <= 0) {
            return 0;
        }

        return round(($conversions / $leads) * 100, 2);
    }

    public function getDurationDaysAttribute(): int
    {
        if (!$this->start_date || !$this->end_date) {
            return 0;
        }

        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isActiveStatus(): bool
    {
        return $this->status === 'active';
    }

    public function isPaused(): bool
    {
        return $this->status === 'paused';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function getPlatformLabelAttribute(): string
    {
        return match ($this->platform) {
            'meta_ads' => 'Meta Ads',
            'tiktok_ads' => 'TikTok Ads',
            'google_ads' => 'Google Ads',
            default => ucwords(str_replace('_', ' ', (string) $this->platform)),
        };
    }

    public function getSourceTypeLabelAttribute(): string
    {
        return match ($this->source_type) {
            'manual' => 'Manual',
            'kommo_sync' => 'Kommo Sync',
            'ads_api' => 'Ads API',
            default => ucwords(str_replace('_', ' ', (string) $this->source_type)),
        };
    }
}