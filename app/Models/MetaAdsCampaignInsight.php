<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetaAdsCampaignInsight extends Model
{
    protected $fillable = [
        'ad_account_id',
        'campaign_id',
        'campaign_name',
        'date_start',
        'date_stop',
        'spend',
        'reach',
        'impressions',
        'frequency',
        'clicks',
        'inline_link_clicks',
        'ctr',
        'cpc',
        'cpm',
        'engagement',
        'link_click',
        'lead_form_submission',
        'whatsapp_chat',
        'cost_per_lead',
        'cost_per_whatsapp_chat',
        'actions',
        'cost_per_action_type',
        'raw_payload',
    ];

    protected $casts = [
        'date_start' => 'date',
        'date_stop' => 'date',
        'spend' => 'decimal:2',
        'frequency' => 'decimal:4',
        'ctr' => 'decimal:6',
        'cpc' => 'decimal:6',
        'cpm' => 'decimal:6',
        'cost_per_lead' => 'decimal:2',
        'cost_per_whatsapp_chat' => 'decimal:2',
        'actions' => 'array',
        'cost_per_action_type' => 'array',
        'raw_payload' => 'array',
    ];
}