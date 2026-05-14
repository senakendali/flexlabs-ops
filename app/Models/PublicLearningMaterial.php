<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicLearningMaterial extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type',
        'title',
        'slug',
        'public_token',
        'subtitle',
        'description',
        'instructor_name',
        'location',
        'event_date',
        'starts_at',
        'ends_at',
        'access_starts_at',
        'access_ends_at',
        'cover_image_path',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'event_date' => 'date',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'access_starts_at' => 'datetime',
        'access_ends_at' => 'datetime',
    ];

    public function blocks(): HasMany
    {
        return $this->hasMany(PublicLearningMaterialBlock::class)
            ->orderBy('sort_order');
    }

    public function activeBlocks(): HasMany
    {
        return $this->hasMany(PublicLearningMaterialBlock::class)
            ->where('is_active', true)
            ->orderBy('sort_order');
    }

    public function images(): HasMany
    {
        return $this->hasMany(PublicLearningMaterialImage::class)
            ->orderBy('sort_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getPublicUrlAttribute(): string
    {
        return route('public-learning-materials.show', [
            'token' => $this->public_token,
            'slug' => $this->slug,
        ]);
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isAccessibleNow(): bool
    {
        $now = now();

        if (! $this->isPublished()) {
            return false;
        }

        if ($this->access_starts_at && $now->lt($this->access_starts_at)) {
            return false;
        }

        if ($this->access_ends_at && $now->gt($this->access_ends_at)) {
            return false;
        }

        return true;
    }
}