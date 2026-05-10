<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunityGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_id',
        'batch_id',
        'name',
        'slug',
        'description',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function channels(): HasMany
    {
        return $this->hasMany(CommunityChannel::class);
    }

    public function activeChannels(): HasMany
    {
        return $this->hasMany(CommunityChannel::class)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function posts(): HasMany
    {
        return $this->hasManyThrough(
            CommunityPost::class,
            CommunityChannel::class,
            'community_group_id',
            'community_channel_id',
            'id',
            'id'
        );
    }
}