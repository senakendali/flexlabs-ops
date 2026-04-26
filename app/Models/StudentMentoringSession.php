<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentMentoringSession extends Model
{
    protected $fillable = [
        'student_id',
        'instructor_id',
        'availability_slot_id',
        'topic_type',
        'notes',
        'meeting_url',
        'status',
        'requested_at',
        'approved_at',
        'cancelled_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

    public function availabilitySlot(): BelongsTo
    {
        return $this->belongsTo(InstructorAvailabilitySlot::class, 'availability_slot_id');
    }

    public function getTopicTypeLabelAttribute(): string
    {
        return match ($this->topic_type) {
            'code_review' => 'Code Review',
            'debugging' => 'Debugging',
            'project_consultation' => 'Project Consultation',
            'career_portfolio' => 'Career / Portfolio',
            'lesson_discussion' => 'Lesson Discussion',
            'other' => 'Other',
            default => str($this->topic_type)->replace('_', ' ')->title()->toString(),
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return str($this->status)->replace('_', ' ')->title()->toString();
    }

    public function getIsPendingAttribute(): bool
    {
        return $this->status === 'pending';
    }

    public function getIsApprovedAttribute(): bool
    {
        return $this->status === 'approved';
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === 'completed';
    }

    public function getIsCancelledAttribute(): bool
    {
        return in_array($this->status, ['cancelled', 'rejected'], true);
    }
}