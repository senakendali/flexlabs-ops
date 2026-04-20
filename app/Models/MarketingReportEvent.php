<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class MarketingReportEvent extends Model
{
    use HasFactory;

    protected $table = 'marketing_report_events';

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
        'marketing_report_id' => 'integer',
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

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getResolvedBudgetAttribute(): float
    {
        return (float) ($this->budget ?? 0);
    }

    public function getEventTypeLabelAttribute(): string
    {
        return match ($this->event_type) {
            'owned_event' => 'Owned Event',
            'external_event' => 'External Event',
            'participated_event' => 'Participated Event',
            'trial_class' => 'Trial Class',
            'workshop' => 'Workshop',
            'info_session' => 'Info Session',
            default => (string) $this->event_type,
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'planned' => 'Planned',
            'scheduled' => 'Scheduled',
            'open_registration' => 'Open Registration',
            'confirmed' => 'Confirmed',
            'done' => 'Done',
            'cancelled' => 'Cancelled',
            default => (string) $this->status,
        };
    }

    public function getEventDateLabelAttribute(): ?string
    {
        return $this->event_date?->format('d M Y');
    }

    public function getLocationLabelAttribute(): string
    {
        return filled($this->location) ? (string) $this->location : '-';
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function occursWithinPeriod(string|Carbon|null $startDate, string|Carbon|null $endDate): bool
    {
        if (!$this->event_date) {
            return false;
        }

        if (blank($startDate) || blank($endDate)) {
            return true;
        }

        $start = $startDate instanceof Carbon
            ? $startDate->copy()->startOfDay()
            : Carbon::parse($startDate)->startOfDay();

        $end = $endDate instanceof Carbon
            ? $endDate->copy()->endOfDay()
            : Carbon::parse($endDate)->endOfDay();

        $eventDate = Carbon::parse($this->event_date)->startOfDay();

        return $eventDate->betweenIncluded($start, $end);
    }
}