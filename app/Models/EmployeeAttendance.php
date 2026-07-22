<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAttendance extends Model
{
    protected $fillable = [
        'attendance_import_id',
        'attendance_import_row_id',

        'employee_id',
        'attendance_date',

        'clock_in',
        'clock_out',

        'attendance_type',
        'punctuality_status',
        'late_minutes',

        'source',

        'remarks',
        'metadata',

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

        'leave_approved_by',
        'leave_approved_at',

        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'late_minutes' => 'integer',
        'metadata' => 'array',

        'scheduled_work_minutes' => 'integer',
        'worked_minutes' => 'integer',

        'schedule_is_inferred' => 'boolean',

        'early_leave_minutes' => 'integer',

        'leave_minutes' => 'integer',
        'is_excused' => 'boolean',

        'leave_approved_at' => 'datetime',
    ];

    public function attendanceImport(): BelongsTo
    {
        return $this->belongsTo(
            AttendanceImport::class
        );
    }

    public function importRow(): BelongsTo
    {
        return $this->belongsTo(
            AttendanceImportRow::class,
            'attendance_import_row_id'
        );
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(
            Employee::class
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }

    public function scopeBetween(
        Builder $query,
        string $dateFrom,
        string $dateTo
    ): Builder {
        return $query
            ->whereDate('attendance_date', '>=', $dateFrom)
            ->whereDate('attendance_date', '<=', $dateTo);
    }

    public function scopePresent(Builder $query): Builder
    {
        return $query->where(
            'attendance_type',
            'present'
        );
    }

    public function scopeLate(Builder $query): Builder
    {
        return $query->where(
            'punctuality_status',
            'late'
        );
    }

    public function workingHourTemplate(): BelongsTo
    {
        return $this->belongsTo(
            WorkingHourTemplate::class
        );
    }

    public function leaveApprover(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'leave_approved_by'
        );
    }
}