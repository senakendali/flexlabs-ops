<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingMinuteParticipant extends Model
{
    protected $fillable = [
        'meeting_minute_id',
        'user_id',
        'name',
        'email',
        'role',
        'attendance_status',
        'notes',
    ];

    public function meetingMinute(): BelongsTo
    {
        return $this->belongsTo(MeetingMinute::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->user?->name
            ?? $this->name
            ?? 'Unknown Participant';
    }

    public function getDisplayEmailAttribute(): ?string
    {
        return $this->user?->email
            ?? $this->email;
    }
}