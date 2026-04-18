<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingLeadSource extends Model
{
    protected $fillable = [
        'marketing_campaign_id',
        'marketing_event_id',

        'date',

        'source_type',
        'source_name',

        'leads',
        'qualified_leads',
        'conversions',
        'revenue',

        'notes',

        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
        'leads' => 'integer',
        'qualified_leads' => 'integer',
        'conversions' => 'integer',
        'revenue' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'conversion_rate',
        'qualification_rate',
        'source_type_label',
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

    public function event(): BelongsTo
    {
        return $this->belongsTo(MarketingEvent::class, 'marketing_event_id');
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

    public function getConversionRateAttribute(): float
    {
        $leads = (int) ($this->leads ?? 0);
        $conversions = (int) ($this->conversions ?? 0);

        if ($leads <= 0) {
            return 0;
        }

        return round(($conversions / $leads) * 100, 2);
    }

    public function getQualificationRateAttribute(): float
    {
        $leads = (int) ($this->leads ?? 0);
        $qualified = (int) ($this->qualified_leads ?? 0);

        if ($leads <= 0) {
            return 0;
        }

        return round(($qualified / $leads) * 100, 2);
    }

    public function getSourceTypeLabelAttribute(): string
    {
        return match ($this->source_type) {
            'ads' => 'Ads',
            'event' => 'Event',
            'organic' => 'Organic',
            'referral' => 'Referral',
            'direct' => 'Direct',
            'partnership' => 'Partnership',
            default => ucfirst($this->source_type),
        };
    }
}