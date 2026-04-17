<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingLeadSource extends Model
{
    protected $fillable = [
        'marketing_campaign_id',
        'marketing_event_id',
        'lead_date',
        'lead_name',
        'email',
        'phone',
        'source',
        'source_detail',
        'status',
        'notes',
        'assigned_to',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'lead_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(MarketingCampaign::class, 'marketing_campaign_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(MarketingEvent::class, 'marketing_event_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
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