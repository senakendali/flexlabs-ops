<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class MarketingReportCampaign extends Model
{
    use HasFactory;

    protected $table = 'marketing_report_campaigns';

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
        'marketing_report_id' => 'integer',
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

    public function getAdsBudgetTotalAttribute(): float
    {
        return round(
            (float) $this->ads->sum(function ($ad) {
                return (float) ($ad->budget ?? 0);
            }),
            2
        );
    }

    public function getAdsActualSpendTotalAttribute(): float
    {
        return round(
            (float) $this->ads->sum(function ($ad) {
                return (float) ($ad->actual_spend ?? 0);
            }),
            2
        );
    }

    public function getTotalBudgetAttribute(): float
    {
        $ownBudget = (float) ($this->budget ?? 0);
        $adsBudget = (float) $this->ads_budget_total;

        return round(max($ownBudget, $adsBudget), 2);
    }

    public function getTotalActualSpendAttribute(): float
    {
        $ownActualSpend = (float) ($this->actual_spend ?? 0);
        $adsActualSpend = (float) $this->ads_actual_spend_total;

        return round(max($ownActualSpend, $adsActualSpend), 2);
    }

    public function getRemainingBudgetAttribute(): float
    {
        return round(
            (float) $this->total_budget - (float) $this->total_actual_spend,
            2
        );
    }

    public function getSpendRatioAttribute(): float
    {
        $budget = (float) $this->total_budget;
        $actualSpend = (float) $this->total_actual_spend;

        if ($budget <= 0) {
            return 0;
        }

        return round(($actualSpend / $budget) * 100, 2);
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

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'planned' => 'Planned',
            'on_progress' => 'On Progress',
            'review' => 'Review',
            'done' => 'Done',
            default => (string) $this->status,
        };
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

        $campaignStart = $this->start_date
            ? Carbon::parse($this->start_date)->startOfDay()
            : null;

        $campaignEnd = $this->end_date
            ? Carbon::parse($this->end_date)->endOfDay()
            : null;

        if ($campaignStart && $campaignEnd) {
            return $campaignStart->lte($end) && $campaignEnd->gte($start);
        }

        if ($campaignStart && !$campaignEnd) {
            return $campaignStart->lte($end);
        }

        if (!$campaignStart && $campaignEnd) {
            return $campaignEnd->gte($start);
        }

        return true;
    }
}