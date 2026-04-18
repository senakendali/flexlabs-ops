<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MarketingReport extends Model
{
    protected $table = 'marketing_reports';

    protected $fillable = [
        'title',
        'slug',
        'period_type',
        'start_date',
        'end_date',
        'total_leads',
        'qualified_leads',
        'total_conversions',
        'total_revenue',
        'budget',
        'actual_spend',
        'summary',
        'key_insight',
        'next_action',
        'notes',
        'status',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_leads' => 'integer',
        'qualified_leads' => 'integer',
        'total_conversions' => 'integer',
        'total_revenue' => 'decimal:2',
        'budget' => 'decimal:2',
        'actual_spend' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getPeriodLabelAttribute(): string
    {
        if ($this->start_date && $this->end_date) {
            return $this->start_date->format('d M Y') . ' - ' . $this->end_date->format('d M Y');
        }

        if ($this->start_date) {
            return $this->start_date->format('d M Y');
        }

        if ($this->end_date) {
            return $this->end_date->format('d M Y');
        }

        return '-';
    }

    public function getConversionRateAttribute(): float
    {
        if (($this->total_leads ?? 0) <= 0) {
            return 0;
        }

        return round((($this->total_conversions ?? 0) / $this->total_leads) * 100, 2);
    }

    public function getQualifiedRateAttribute(): float
    {
        if (($this->total_leads ?? 0) <= 0) {
            return 0;
        }

        return round((($this->qualified_leads ?? 0) / $this->total_leads) * 100, 2);
    }

    public function getCplAttribute(): float
    {
        if (($this->total_leads ?? 0) <= 0) {
            return 0;
        }

        return round((float) $this->actual_spend / $this->total_leads, 2);
    }

    public function getCacAttribute(): float
    {
        if (($this->total_conversions ?? 0) <= 0) {
            return 0;
        }

        return round((float) $this->actual_spend / $this->total_conversions, 2);
    }

    public static function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug;
        $counter = 1;

        while (
            static::query()
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}