<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class MarketingReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'report_no',
        'period_type',
        'start_date',
        'end_date',

        'total_budget',
        'total_actual_spend',

        'total_leads',
        'total_registrants',
        'total_attendees',
        'total_conversions',
        'total_revenue',

        'summary',
        'key_insight',
        'next_action',
        'notes',

        'is_overview_completed',
        'is_campaign_completed',
        'is_ads_completed',
        'is_events_completed',
        'is_snapshot_completed',
        'is_insight_completed',

        'status',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',

        'total_budget' => 'decimal:2',
        'total_actual_spend' => 'decimal:2',
        'total_revenue' => 'decimal:2',

        'total_leads' => 'integer',
        'total_registrants' => 'integer',
        'total_attendees' => 'integer',
        'total_conversions' => 'integer',

        'is_overview_completed' => 'boolean',
        'is_campaign_completed' => 'boolean',
        'is_ads_completed' => 'boolean',
        'is_events_completed' => 'boolean',
        'is_snapshot_completed' => 'boolean',
        'is_insight_completed' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (MarketingReport $report) {
            if (blank($report->slug) && filled($report->title)) {
                $report->slug = Str::slug($report->title);
            }
        });

        static::updating(function (MarketingReport $report) {
            if (blank($report->slug) && filled($report->title)) {
                $report->slug = Str::slug($report->title);
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function campaigns(): HasMany
    {
        return $this->hasMany(MarketingReportCampaign::class, 'marketing_report_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function ads(): HasMany
    {
        return $this->hasMany(MarketingReportAd::class, 'marketing_report_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(MarketingReportEvent::class, 'marketing_report_id')
            ->orderBy('sort_order')
            ->orderBy('event_date')
            ->orderBy('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /*
    |--------------------------------------------------------------------------

    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeWeekly(Builder $query): Builder
    {
        return $query->where('period_type', 'weekly');
    }

    public function scopeMonthly(Builder $query): Builder
    {
        return $query->where('period_type', 'monthly');
    }

    /*
    |--------------------------------------------------------------------------

    | Helpers
    |--------------------------------------------------------------------------
    */

    public function getIsCompletedAttribute(): bool
    {
        return $this->is_overview_completed
            && $this->is_campaign_completed
            && $this->is_ads_completed
            && $this->is_events_completed
            && $this->is_snapshot_completed
            && $this->is_insight_completed;
    }

    public function getPeriodLabelAttribute(): string
    {
        return match ($this->period_type) {
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            default => ucfirst((string) $this->period_type),
        };
    }

    public function availableSetupCampaigns(): Builder
    {
        if (blank($this->start_date) || blank($this->end_date)) {
            return MarketingSetupCampaign::query()->whereRaw('1 = 0');
        }

        return MarketingSetupCampaign::query()
            ->active()
            ->overlappingPeriod(
                $this->start_date->toDateString(),
                $this->end_date->toDateString()
            )
            ->orderBy('start_date')
            ->orderBy('name');
    }

    public function availableSetupAds(): Builder
    {
        if (blank($this->start_date) || blank($this->end_date)) {
            return MarketingSetupAd::query()->whereRaw('1 = 0');
        }

        return MarketingSetupAd::query()
            ->active()
            ->overlappingPeriod(
                $this->start_date->toDateString(),
                $this->end_date->toDateString()
            )
            ->orderBy('start_date')
            ->orderBy('ad_name');
    }

    public function recalculateTotals(): void
    {
        $this->loadMissing([
            'campaigns',
            'ads',
            'events',
        ]);

        $campaignBudget = $this->campaigns->sum(function ($campaign) {
            return (float) ($campaign->resolved_budget ?? $campaign->budget ?? 0);
        });

        $adBudget = $this->ads->sum(function ($ad) {
            return (float) ($ad->resolved_budget ?? $ad->budget ?? 0);
        });

        $eventBudget = $this->events->sum(function ($event) {
            return (float) ($event->budget ?? 0);
        });

        $campaignActualSpend = $this->campaigns->sum(function ($campaign) {
            return (float) ($campaign->actual_spend ?? 0);
        });

        $adActualSpend = $this->ads->sum(function ($ad) {
            return (float) ($ad->actual_spend ?? 0);
        });

        $this->total_budget = $campaignBudget + $adBudget + $eventBudget;
        $this->total_actual_spend = $campaignActualSpend + $adActualSpend;

        $this->saveQuietly();
    }
}