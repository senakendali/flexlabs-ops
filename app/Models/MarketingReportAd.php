<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

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
        'marketing_report_id' => 'integer',
        'marketing_report_campaign_id' => 'integer',
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

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getResolvedBudgetAttribute(): float
    {
        return (float) ($this->budget ?? 0);
    }

    public function getResolvedActualSpendAttribute(): float
    {
        return (float) ($this->actual_spend ?? 0);
    }

    public function getSpendRatioAttribute(): float
    {
        $budget = (float) ($this->budget ?? 0);
        $actualSpend = (float) ($this->actual_spend ?? 0);

        if ($budget <= 0) {
            return 0;
        }

        return round(($actualSpend / $budget) * 100, 2);
    }

    public function getRemainingBudgetAttribute(): float
    {
        return round(
            (float) ($this->budget ?? 0) - (float) ($this->actual_spend ?? 0),
            2
        );
    }

    public function getPeriodLabelAttribute(): ?string
    {
        if (!$this->start_date && !$this->end_date) {
            return null;
        }

        $start = $this->start_date?->format('d M Y');
        $end = $this->end_date?->format('d M Y');

        if ($start && $end) {
            return "{$start} - {$end}";
        }

        return $start ?: $end;
    }

    public function getCampaignNameAttribute(): ?string
    {
        return $this->campaign?->name;
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function overlapsPeriod(string|Carbon|null $startDate, string|Carbon|null $endDate): bool
    {
        if (blank($startDate) || blank($endDate)) {
            return true;
        }

        $start = $startDate instanceof Carbon
            ? $startDate->copy()->startOfDay()
            : Carbon::parse($startDate)->startOfDay();

        $end = $endDate instanceof Carbon
            ? $endDate->copy()->endOfDay()
            : Carbon::parse($endDate)->endOfDay();

        $adStart = $this->start_date
            ? Carbon::parse($this->start_date)->startOfDay()
            : null;

        $adEnd = $this->end_date
            ? Carbon::parse($this->end_date)->endOfDay()
            : null;

        if ($adStart && $adEnd) {
            return $adStart->lte($end) && $adEnd->gte($start);
        }

        if ($adStart && !$adEnd) {
            return $adStart->lte($end);
        }

        if (!$adStart && $adEnd) {
            return $adEnd->gte($start);
        }

        return true;
    }
}