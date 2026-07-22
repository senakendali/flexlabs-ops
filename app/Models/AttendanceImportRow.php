<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceImportRow extends Model
{
    /*
    |--------------------------------------------------------------------------
    | Row Sources
    |--------------------------------------------------------------------------
    */
    public const SOURCE_EXCEL = 'excel';
    public const SOURCE_GENERATED_GAP = 'generated_gap';
    public const SOURCE_MANUAL = 'manual';

    /*
    |--------------------------------------------------------------------------
    | Review Statuses
    |--------------------------------------------------------------------------
    */
    public const REVIEW_VALID = 'valid';
    public const REVIEW_NEEDS_REVIEW = 'needs_review';
    public const REVIEW_RESOLVED = 'resolved';
    public const REVIEW_IGNORED = 'ignored';
    public const REVIEW_ERROR = 'error';
    public const REVIEW_DUPLICATE = 'duplicate';

    protected $fillable = [
        'attendance_import_id',
        'employee_id',

        'row_number',
        'source_row_key',

        'attendance_date',

        'employee_number_raw',
        'employee_name_raw',

        'employee_number',
        'employee_name',

        'clock_in',
        'clock_out',

        'attendance_type',
        'punctuality_status',
        'late_minutes',

        'source',
        'review_status',

        'validation_message',
        'remarks',

        'raw_payload',
        'resolution_metadata',

        'resolved_by',
        'resolved_at',

        'working_hour_template_id',
        'working_hours_template_raw',

        'scheduled_start_time',
        'scheduled_end_time',
        'scheduled_work_minutes',
        'worked_minutes',

        'schedule_source',
        'schedule_is_inferred',

        'arrival_status',
        'departure_status',
        'early_leave_minutes',

        'leave_type',
        'leave_duration',
        'leave_session',

        'leave_start_time',
        'leave_end_time',
        'leave_minutes',

        'is_excused',
        'leave_reason',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'late_minutes' => 'integer',

        'raw_payload' => 'array',
        'resolution_metadata' => 'array',

        'resolved_at' => 'datetime',

        'scheduled_work_minutes' => 'integer',
        'worked_minutes' => 'integer',

        'schedule_is_inferred' => 'boolean',

        'early_leave_minutes' => 'integer',

        'leave_minutes' => 'integer',
        'is_excused' => 'boolean',
    ];

    public function attendanceImport(): BelongsTo
    {
        return $this->belongsTo(
            AttendanceImport::class
        );
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(
            Employee::class
        );
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'resolved_by'
        );
    }

    public function scopeNeedsReview(Builder $query): Builder
    {
        return $query->where(
            'review_status',
            self::REVIEW_NEEDS_REVIEW
        );
    }

    public function scopeGeneratedGap(Builder $query): Builder
    {
        return $query->where(
            'source',
            self::SOURCE_GENERATED_GAP
        );
    }

    public function workingHourTemplate(): BelongsTo
    {
        return $this->belongsTo(
            WorkingHourTemplate::class
        );
    }
}