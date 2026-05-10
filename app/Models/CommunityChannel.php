<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunityChannel extends Model
{
    use HasFactory;

    protected $fillable = [
        'community_group_id',
        'name',
        'slug',
        'type',
        'description',
        'is_readonly',
        'is_pinned',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_readonly' => 'boolean',
        'is_pinned' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(CommunityGroup::class, 'community_group_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(CommunityPost::class, 'community_channel_id');
    }

    public function activePosts(): HasMany
    {
        return $this->hasMany(CommunityPost::class, 'community_channel_id')
            ->where('is_active', true)
            ->orderByDesc('is_pinned')
            ->latest('published_at')
            ->latest();
    }
}