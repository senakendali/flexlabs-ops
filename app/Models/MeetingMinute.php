<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeetingMinute extends Model
{
    protected $fillable = [
        'meeting_no',
        'title',
        'meeting_type',
        'meeting_date',
        'start_time',
        'end_time',
        'location',
        'platform',
        'department',
        'related_project',
        'organizer_id',
        'summary',
        'notes',
        'status',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'meeting_date' => 'date',
        'start_time'   => 'datetime:H:i',
        'end_time'     => 'datetime:H:i',
        'is_active'    => 'boolean',
    ];

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(MeetingMinuteParticipant::class);
    }

    public function agendas(): HasMany
    {
        return $this->hasMany(MeetingMinuteAgenda::class)
            ->orderBy('sort_order');
    }

    public function actionItems(): HasMany
    {
        return $this->hasMany(MeetingMinuteActionItem::class);
    }

    public function pendingActionItems(): HasMany
    {
        return $this->hasMany(MeetingMinuteActionItem::class)
            ->whereIn('status', ['pending', 'in_progress', 'blocked']);
    }

    public function completedActionItems(): HasMany
    {
        return $this->hasMany(MeetingMinuteActionItem::class)
            ->where('status', 'done');
    }
}