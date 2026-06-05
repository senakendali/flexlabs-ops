<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventLead extends Model
{
    use SoftDeletes;

    public const STATUS_NEW = 'new';
    public const STATUS_CONTACTED = 'contacted';
    public const STATUS_INTERESTED = 'interested';
    public const STATUS_REGISTERED = 'registered';
    public const STATUS_NOT_INTERESTED = 'not_interested';

    protected $fillable = [
        'lead_event_id',
        'name',
        'email',
        'phone',
        'institution',
        'position',
        'city',
        'interest',
        'notes',
        'source',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'status',
        'contacted_at',
        'registered_at',
        'is_consent_given',
        'consent_given_at',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'contacted_at' => 'datetime',
        'registered_at' => 'datetime',
        'is_consent_given' => 'boolean',
        'consent_given_at' => 'datetime',
        'metadata' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function leadEvent(): BelongsTo
    {
        return $this->belongsTo(LeadEvent::class, 'lead_event_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeNew(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_NEW);
    }

    public function scopeContacted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CONTACTED);
    }

    public function scopeInterested(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_INTERESTED);
    }

    public function scopeRegistered(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_REGISTERED);
    }

    public function scopeByEvent(Builder $query, int|string|null $leadEventId): Builder
    {
        return $query->when($leadEventId, function (Builder $query) use ($leadEventId) {
            $query->where('lead_event_id', $leadEventId);
        });
    }

    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        return $query->when($keyword, function (Builder $query) use ($keyword) {
            $query->where(function (Builder $query) use ($keyword) {
                $query->where('name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%")
                    ->orWhere('institution', 'like', "%{$keyword}%")
                    ->orWhere('interest', 'like', "%{$keyword}%");
            });
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public static function statuses(): array
    {
        return [
            self::STATUS_NEW => 'New',
            self::STATUS_CONTACTED => 'Contacted',
            self::STATUS_INTERESTED => 'Interested',
            self::STATUS_REGISTERED => 'Registered',
            self::STATUS_NOT_INTERESTED => 'Not Interested',
        ];
    }

    public function markAsContacted(): void
    {
        $this->update([
            'status' => self::STATUS_CONTACTED,
            'contacted_at' => now(),
        ]);
    }

    public function markAsRegistered(): void
    {
        $this->update([
            'status' => self::STATUS_REGISTERED,
            'registered_at' => now(),
        ]);
    }
}