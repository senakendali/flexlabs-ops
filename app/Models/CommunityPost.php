<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunityPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'community_channel_id',
        'author_type',
        'author_id',
        'student_id',
        'title',
        'body',
        'post_type',
        'status',
        'is_pinned',
        'is_locked',
        'is_active',
        'published_at',
        'solved_at',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_locked' => 'boolean',
        'is_active' => 'boolean',
        'published_at' => 'datetime',
        'solved_at' => 'datetime',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(CommunityChannel::class, 'community_channel_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(CommunityComment::class, 'community_post_id');
    }

    public function activeComments(): HasMany
    {
        return $this->hasMany(CommunityComment::class, 'community_post_id')
            ->where('is_active', true)
            ->oldest();
    }

    public function reads(): HasMany
    {
        return $this->hasMany(CommunityPostRead::class, 'community_post_id');
    }

    public function solutionComments(): HasMany
    {
        return $this->hasMany(CommunityComment::class, 'community_post_id')
            ->where('is_solution', true);
    }
}