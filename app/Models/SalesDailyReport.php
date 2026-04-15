<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesDailyReport extends Model
{
    protected $fillable = [
        'report_date',
        'total_leads',
        'interacted',
        'ignored',
        'closed_lost',
        'not_related',
        'warm_leads',
        'hot_leads',
        'consultation',
        'summary',
        'highlight',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'report_date' => 'date',
        'total_leads' => 'integer',
        'interacted' => 'integer',
        'ignored' => 'integer',
        'closed_lost' => 'integer',
        'not_related' => 'integer',
        'warm_leads' => 'integer',
        'hot_leads' => 'integer',
        'consultation' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}