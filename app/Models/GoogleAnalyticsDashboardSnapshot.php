<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoogleAnalyticsDashboardSnapshot extends Model
{
    protected $fillable = [
        'property_id',
        'date_preset',
        'date_start',
        'date_stop',
        'is_available',

        'total_users',
        'new_users',
        'sessions',
        'engaged_sessions',
        'engagement_rate',
        'bounce_rate',
        'key_events',
        'key_event_rate',
        'average_engagement_time_label',

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

        'total_users' => 'integer',
        'new_users' => 'integer',
        'sessions' => 'integer',
        'engaged_sessions' => 'integer',
        'engagement_rate' => 'float',
        'bounce_rate' => 'float',
        'key_events' => 'integer',
        'key_event_rate' => 'float',

        'payload' => 'array',
        'ai_payload' => 'array',

        'synced_at' => 'datetime',
        'ai_generated_at' => 'datetime',
    ];
}