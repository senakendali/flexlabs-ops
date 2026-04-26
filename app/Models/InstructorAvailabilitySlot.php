<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstructorAvailabilitySlot extends Model
{
    protected $fillable = [
        'instructor_id',
        'date',
        'start_time',
        'end_time',
        'status',
        'is_active',
    ];

    protected $casts = [
        'date' => 'date',
        'is_active' => 'boolean',
    ];

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

    public function mentoringSession(): HasOne
    {
        return $this->hasOne(StudentMentoringSession::class, 'availability_slot_id');
    }

    public function getIsAvailableForBookingAttribute(): bool
    {
        return $this->status === 'available'
            && $this->is_active
            && !$this->mentoringSession()->exists();
    }
}