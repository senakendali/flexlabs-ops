<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class LeadEvent extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'event_type',
        'event_mode',
        'location',
        'event_url',
        'image',
        'short_description',
        'description',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'cta_label',
        'external_registration_url',
        'is_active',
        'is_featured',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'metadata' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function leads(): HasMany
    {
        return $this->hasMany(EventLead::class, 'lead_event_id');
    }

    public function newLeads(): HasMany
    {
        return $this->hasMany(EventLead::class, 'lead_event_id')
            ->where('status', 'new');
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

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('sort_order')
            ->orderByDesc('start_date')
            ->orderBy('title');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted(): void
    {
        static::creating(function (LeadEvent $leadEvent) {
            if (blank($leadEvent->slug) && filled($leadEvent->title)) {
                $leadEvent->slug = static::generateUniqueSlug($leadEvent->title);
            }
        });

        static::updating(function (LeadEvent $leadEvent) {
            if (blank($leadEvent->slug) && filled($leadEvent->title)) {
                $leadEvent->slug = static::generateUniqueSlug($leadEvent->title, $leadEvent->id);
            }
        });
    }

    public static function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug;
        $counter = 2;

        while (
            static::query()
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->where('slug', $slug)
                ->withTrashed()
                ->exists()
        ) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}