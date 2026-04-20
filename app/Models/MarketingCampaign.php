<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class MarketingCampaign extends Model
{
    protected $fillable = [
        'marketing_plan_id',
        'name',
        'slug',
        'channel',
        'type',
        'start_date',
        'end_date',
        'budget',
        'target_leads',
        'target_conversions',
        'actual_leads',
        'actual_conversions',
        'status',
        'description',
        'notes',
        'is_active',
        'pic_user_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
        'target_leads' => 'integer',
        'target_conversions' => 'integer',
        'actual_leads' => 'integer',
        'actual_conversions' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $campaign) {
            if (blank($campaign->slug) && filled($campaign->name)) {
                $campaign->slug = static::generateSlug($campaign->name);
            }
        });

        static::updating(function (self $campaign) {
            if (blank($campaign->slug) && filled($campaign->name)) {
                $campaign->slug = static::generateSlug($campaign->name);
            }
        });
    }

    protected static function generateSlug(string $name): string
    {
        return Str::slug($name . '-' . Str::random(5));
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

    public function scopeOverlappingPeriod(
        Builder $query,
        string|Carbon|null $startDate,
        string|Carbon|null $endDate
    ): Builder {
        if (blank($startDate) || blank($endDate)) {
            return $query;
        }

        $start = $startDate instanceof Carbon
            ? $startDate->toDateString()
            : Carbon::parse($startDate)->toDateString();

        $end = $endDate instanceof Carbon
            ? $endDate->toDateString()
            : Carbon::parse($endDate)->toDateString();

        return $query->where(function (Builder $subQuery) use ($start, $end) {
            $subQuery
                ->where(function (Builder $q) use ($start, $end) {
                    $q->whereNotNull('start_date')
                        ->whereNotNull('end_date')
                        ->whereDate('start_date', '<=', $end)
                        ->whereDate('end_date', '>=', $start);
                })
                ->orWhere(function (Builder $q) use ($start, $end) {
                    $q->whereNull('start_date')
                        ->whereNotNull('end_date')
                        ->whereDate('end_date', '>=', $start);
                })
                ->orWhere(function (Builder $q) use ($start, $end) {
                    $q->whereNotNull('start_date')
                        ->whereNull('end_date')
                        ->whereDate('start_date', '<=', $end);
                })
                ->orWhere(function (Builder $q) {
                    $q->whereNull('start_date')
                        ->whereNull('end_date');
                });
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function plan(): BelongsTo
    {
        return $this->belongsTo(MarketingPlan::class, 'marketing_plan_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(MarketingActivity::class);
    }

    public function ads(): HasMany
    {
        return $this->hasMany(MarketingAd::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(MarketingEvent::class);
    }

    public function leadSources(): HasMany
    {
        return $this->hasMany(MarketingLeadSource::class);
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_user_id');
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
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getResolvedBudgetAttribute(): float
    {
        return (float) ($this->budget ?? 0);
    }

    public function getLeadAchievementPercentAttribute(): float
    {
        return $this->calculatePercent(
            (int) ($this->actual_leads ?? 0),
            (int) ($this->target_leads ?? 0)
        );
    }

    public function getConversionAchievementPercentAttribute(): float
    {
        return $this->calculatePercent(
            (int) ($this->actual_conversions ?? 0),
            (int) ($this->target_conversions ?? 0)
        );
    }

    public function getLeadToConversionRateAttribute(): float
    {
        return $this->calculatePercent(
            (int) ($this->actual_conversions ?? 0),
            (int) ($this->actual_leads ?? 0)
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

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    protected function calculatePercent(int|float $actual, int|float $target): float
    {
        if ($target <= 0) {
            return 0;
        }

        return round(($actual / $target) * 100, 2);
    }
}