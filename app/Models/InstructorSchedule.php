<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class InstructorSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'instructor_id',
        'replacement_instructor_id',
        'batch_id',
        'program_id',
        'sub_topic_id',
        'rescheduled_from_id',
        'session_title',
        'schedule_date',
        'start_time',
        'end_time',
        'delivery_mode',
        'meeting_link',
        'location',
        'is_makeup_session',
        'status',
        'notes',
    ];

    protected $casts = [
        'instructor_id' => 'integer',
        'replacement_instructor_id' => 'integer',
        'batch_id' => 'integer',
        'program_id' => 'integer',
        'sub_topic_id' => 'integer',
        'rescheduled_from_id' => 'integer',
        'schedule_date' => 'date',
        'is_makeup_session' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

    public function replacementInstructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class, 'replacement_instructor_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function subTopic(): BelongsTo
    {
        return $this->belongsTo(SubTopic::class);
    }

    public function rescheduledFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'rescheduled_from_id');
    }

    public function rescheduledSessions(): HasMany
    {
        return $this->hasMany(self::class, 'rescheduled_from_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getScheduleDateLabelAttribute(): ?string
    {
        return $this->schedule_date?->format('d M Y');
    }

    public function getTimeRangeAttribute(): string
    {
        $start = $this->formatTimeValue($this->start_time);
        $end = $this->formatTimeValue($this->end_time);

        if (!$start && !$end) {
            return '-';
        }

        return trim(($start ?: '-') . ' - ' . ($end ?: '-'));
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'scheduled' => 'Scheduled',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'rescheduled' => 'Rescheduled',
            default => (string) $this->status,
        };
    }

    public function getDeliveryModeLabelAttribute(): string
    {
        return match ($this->delivery_mode) {
            'online' => 'Online',
            'offline' => 'Offline',
            'hybrid' => 'Hybrid',
            default => (string) $this->delivery_mode,
        };
    }

    public function getInstructorDisplayNameAttribute(): ?string
    {
        return $this->replacementInstructor?->name ?: $this->instructor?->name;
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    protected function formatTimeValue(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->format('H:i');
        }

        try {
            return Carbon::parse((string) $value)->format('H:i');
        } catch (\Throwable $th) {
            return (string) $value;
        }
    }

    public function studentAttendances(): HasMany
    {
        return $this->hasMany(StudentAttendance::class);
    }
}