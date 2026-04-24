<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workshop extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'badge',
        'short_description',
        'overview',
        'price',
        'old_price',
        'rating',
        'rating_count',
        'duration',
        'level',
        'category',
        'audience',
        'image',
        'intro_video_type',
        'intro_video_url',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'old_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function benefits(): HasMany
    {
        return $this->hasMany(WorkshopBenefit::class)->orderBy('sort_order');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}