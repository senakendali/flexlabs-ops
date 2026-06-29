<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeedbackForm extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'type',
        'program_id',
        'batch_id',
        'is_active',
        'starts_at',
        'ends_at',
        'settings',
    ];

    protected $casts = [
        'program_id' => 'integer',
        'batch_id' => 'integer',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'settings' => 'array',
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(FeedbackQuestion::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function activeQuestions(): HasMany
    {
        return $this->questions()->where('is_active', true);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(FeedbackResponse::class);
    }

    public function submittedResponses(): HasMany
    {
        return $this->responses()->where('status', 'submitted');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(function (Builder $query) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            });
    }
}