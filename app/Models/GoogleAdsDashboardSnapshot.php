<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoogleAdsDashboardSnapshot extends Model
{
    protected $fillable = [
        'customer_id',
        'login_customer_id',
        'date_preset',
        'date_start',
        'date_stop',
        'is_available',

        'campaign_count',
        'enabled_campaign_count',
        'paused_campaign_count',

        'total_cost',
        'total_impressions',
        'total_clicks',
        'ctr',
        'average_cpc',
        'total_conversions',
        'total_conversion_value',
        'cost_per_conversion',
        'conversion_rate',
        'roas',

        'critical_count',
        'attention_count',
        'healthy_count',

        'summary_text',

        'ai_summary_text',
        'ai_payload',
        'ai_model',
        'ai_generated_at',

        'payload',
        'error_message',
        'synced_at',
    ];

    protected $casts = [
        'is_available' => 'boolean',

        'date_start' => 'date',
        'date_stop' => 'date',

        'campaign_count' => 'integer',
        'enabled_campaign_count' => 'integer',
        'paused_campaign_count' => 'integer',

        'total_cost' => 'float',
        'total_impressions' => 'integer',
        'total_clicks' => 'integer',
        'ctr' => 'float',
        'average_cpc' => 'float',
        'total_conversions' => 'float',
        'total_conversion_value' => 'float',
        'cost_per_conversion' => 'float',
        'conversion_rate' => 'float',
        'roas' => 'float',

        'critical_count' => 'integer',
        'attention_count' => 'integer',
        'healthy_count' => 'integer',

        'payload' => 'array',
        'ai_payload' => 'array',

        'synced_at' => 'datetime',
        'ai_generated_at' => 'datetime',
    ];
}