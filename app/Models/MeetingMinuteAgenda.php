<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingMinuteAgenda extends Model
{
    protected $fillable = [
        'meeting_minute_id',
        'topic',
        'description',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function meetingMinute(): BelongsTo
    {
        return $this->belongsTo(MeetingMinute::class);
    }
}