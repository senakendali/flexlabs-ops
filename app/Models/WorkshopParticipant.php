<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkshopParticipant extends Model
{
    protected $fillable = [
        'workshop_id',
        'workshop_schedule_id',
        'student_id',
        'order_id',
        'status',
        'registered_at',
        'paid_at',
        'attended_at',
        'notes',
    ];

    protected $casts = [
        'registered_at' => 'datetime',
        'paid_at' => 'datetime',
        'attended_at' => 'datetime',
    ];

    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isPaid(): bool
    {
        return in_array($this->status, ['confirmed', 'attended'], true);
    }

    public function workshopSchedule(): BelongsTo
    {
        return $this->belongsTo(WorkshopSchedule::class);
    }
}