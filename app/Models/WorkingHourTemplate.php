<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkingHourTemplate extends Model
{
    protected $fillable = [
        'code',
        'name',

        'start_time',
        'end_time',

        'break_start_time',
        'break_end_time',

        'first_half_end_time',
        'second_half_start_time',

        'working_days',

        'late_tolerance_minutes',
        'scheduled_work_minutes',

        'is_active',
        'source',
        'metadata',
    ];

    protected $casts = [
        'working_days' => 'array',
        'late_tolerance_minutes' => 'integer',
        'scheduled_work_minutes' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function employees(): HasMany
    {
        return $this->hasMany(
            Employee::class,
            'default_working_hour_template_id'
        );
    }

    public function importRows(): HasMany
    {
        return $this->hasMany(
            AttendanceImportRow::class
        );
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(
            EmployeeAttendance::class
        );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isExpectedWorkday(int $isoDayNumber): bool
    {
        $workingDays = $this->working_days ?: [1, 2, 3, 4, 5];

        return in_array($isoDayNumber, $workingDays, true);
    }
}