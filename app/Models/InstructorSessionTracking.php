<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InstructorSessionTracking extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_CHECKED_IN = 'checked_in';
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_REVIEWED = 'reviewed';
    public const STATUS_RETURNED = 'returned';

    protected $fillable = [
        'instructor_schedule_id',
        'instructor_id',
        'batch_id',
        'program_id',
        'session_date',
        'scheduled_start_time',
        'scheduled_end_time',
        'checked_in_at',
        'checked_out_at',
        'actual_duration_minutes',
        'late_minutes',
        'coverage_percentage',
        'session_notes',
        'issue_notes',
        'follow_up_notes',
        'status',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'session_date' => 'date',
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'actual_duration_minutes' => 'integer',
        'late_minutes' => 'integer',
        'coverage_percentage' => 'decimal:2',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(InstructorSchedule::class, 'instructor_schedule_id');
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InstructorSessionTrackingItem::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_CHECKED_IN => 'Checked In',
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_SUBMITTED => 'Submitted',
            self::STATUS_REVIEWED => 'Reviewed',
            self::STATUS_RETURNED => 'Returned',
            default => 'Pending',
        };
    }

    public function getCanEditAttribute(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_CHECKED_IN,
            self::STATUS_DRAFT,
            self::STATUS_RETURNED,
        ], true);
    }
}
