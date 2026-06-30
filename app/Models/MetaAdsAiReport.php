<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetaAdsAiReport extends Model
{
    protected $fillable = [
        'report_type',
        'campaign_id',
        'campaign_name',
        'date_start',
        'date_stop',
        'provider',
        'model',
        'input_snapshot',
        'output',
        'summary_text',
        'health_status',
        'main_bottleneck',
        'generated_at',
    ];

    protected $casts = [
        'date_start' => 'date',
        'date_stop' => 'date',
        'input_snapshot' => 'array',
        'output' => 'array',
        'generated_at' => 'datetime',
    ];
}