<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingReportCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'marketing_report_id',
        'name',
        'objective',
        'start_date',
        'end_date',
        'budget',
        'actual_spend',
        'owner_name',
        'status',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
        'actual_spend' => 'decimal:2',
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

    public function ads(): HasMany
    {
        return $this->hasMany(MarketingReportAd::class, 'marketing_report_campaign_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}