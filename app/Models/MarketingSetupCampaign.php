<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingSetupCampaign extends Model
{
    use HasFactory;

    protected $table = 'marketing_setup_campaigns';

    protected $fillable = [
        'name',
        'slug',
        'objective',
        'start_date',
        'end_date',
        'total_budget',
        'owner_name',
        'pic_user_id',
        'status',
        'notes',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_budget' => 'decimal:2',
        'is_active' => 'boolean',
    ];

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

    public function ads(): HasMany
    {
        return $this->hasMany(MarketingSetupAd::class, 'marketing_setup_campaign_id')
            ->orderBy('start_date')
            ->orderBy('ad_name');
    }

    public function reportCampaigns(): HasMany
    {
        return $this->hasMany(MarketingReportCampaign::class, 'marketing_setup_campaign_id')
            ->orderByDesc('id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOverlappingPeriod(Builder $query, $startDate, $endDate): Builder
    {
        return $query
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate);
    }
}