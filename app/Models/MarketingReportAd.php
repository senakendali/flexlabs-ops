<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingReportAd extends Model
{
    use HasFactory;

    protected $table = 'marketing_report_ads';

    protected $fillable = [
        'marketing_report_id',
        'marketing_report_campaign_id',
        'platform',
        'ad_name',
        'objective',
        'start_date',
        'end_date',
        'budget',
        'actual_spend',
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

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(MarketingReportCampaign::class, 'marketing_report_campaign_id');
    }
}