<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubTopic extends Model
{
    use HasFactory;

    protected $table = 'sub_topics';

    protected $fillable = [
        'topic_id',
        'name',
        'description',
        'sort_order',
        'is_active',

        // LMS / learning item fields
        'lesson_type',
        'video_url',
        'video_duration_minutes',
        'thumbnail_url',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'video_duration_minutes' => 'integer',
    ];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function getLessonTypeLabelAttribute(): string
    {
        return match ($this->lesson_type) {
            'live_session' => 'Live Session',
            default => 'Video Lesson',
        };
    }

    public function getIsVideoLessonAttribute(): bool
    {
        return ($this->lesson_type ?? 'video') === 'video';
    }

    public function getIsLiveSessionAttribute(): bool
    {
        return ($this->lesson_type ?? 'video') === 'live_session';
    }

    public function getHasVideoAttribute(): bool
    {
        return !empty($this->video_url);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'sub_topic_id')
            ->orderBy('sort_order')
            ->orderBy('title');
    }
}