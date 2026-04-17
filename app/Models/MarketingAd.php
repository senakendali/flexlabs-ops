<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingAd extends Model
{
    protected $fillable = [
        'marketing_campaign_id',
        'platform',
        'campaign_name',
        'ad_name',
        'start_date',
        'end_date',
        'budget',
        'spend',
        'impressions',
        'clicks',
        'leads',
        'conversions',
        'cost_per_click',
        'cost_per_lead',
        'status',
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
        'spend' => 'decimal:2',
        'cost_per_click' => 'decimal:2',
        'cost_per_lead' => 'decimal:2',
        'is_active' => 'boolean',
    ];

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
}