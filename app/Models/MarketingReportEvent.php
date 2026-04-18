<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingReportEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'marketing_report_id',
        'name',
        'event_type',
        'event_date',
        'location',
        'target_participants',
        'budget',
        'status',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'event_date' => 'date',
        'target_participants' => 'integer',
        'budget' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function report(): BelongsTo
    {
        return $this->belongsTo(MarketingReport::class, 'marketing_report_id');
    }
}