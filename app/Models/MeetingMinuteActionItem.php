<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingMinuteActionItem extends Model
{
    protected $fillable = [
        'meeting_minute_id',
        'title',
        'description',
        'pic_user_id',
        'pic_name',
        'priority',
        'due_date',
        'status',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'due_date'     => 'date',
        'completed_at' => 'datetime',
    ];

    public function meetingMinute(): BelongsTo
    {
        return $this->belongsTo(MeetingMinute::class);
    }

    public function picUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_user_id');
    }

    public function getPicDisplayNameAttribute(): string
    {
        return $this->picUser?->name
            ?? $this->pic_name
            ?? 'Unassigned';
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date
            && ! in_array($this->status, ['done', 'cancelled'], true)
            && $this->due_date->isPast();
    }
}