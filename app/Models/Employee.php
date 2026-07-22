<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'employee_number',
        'name',
        'normalized_name',
        'email',
        'phone',
        'employee_type',
        'work_team',
        'duty_type',
        'default_start_time',
        'default_end_time',
        'source',
        'is_active',
        'first_seen_at',
        'last_seen_at',
        'metadata',
        'default_working_hour_template_id',
        'working_days_override',
    ];

    protected $casts = [
        'is_active' => 'boolean',

        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',

        'metadata' => 'array',

        'working_days_override' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (Employee $employee): void {
            $employee->name = trim((string) $employee->name);

            $employee->normalized_name = static::normalizeName(
                $employee->name
            );

            if (filled($employee->employee_number)) {
                $employee->employee_number = trim(
                    (string) $employee->employee_number
                );
            }
        });
    }

    public static function normalizeName(?string $name): string
    {
        return Str::of((string) $name)
            ->lower()
            ->squish()
            ->toString();
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

    public function scopeEmployeeNumber(
        Builder $query,
        string $employeeNumber
    ): Builder {
        return $query->where(
            'employee_number',
            trim($employeeNumber)
        );
    }

    public function defaultWorkingHourTemplate(): BelongsTo
    {
        return $this->belongsTo(
            WorkingHourTemplate::class,
            'default_working_hour_template_id'
        );
    }

    public function resolvedWorkingDays(): array
    {
        if (! empty($this->working_days_override)) {
            return array_map(
                'intval',
                $this->working_days_override
            );
        }

        if (! empty($this->defaultWorkingHourTemplate?->working_days)) {
            return array_map(
                'intval',
                $this->defaultWorkingHourTemplate->working_days
            );
        }

        return [1, 2, 3, 4, 5];
    }
}