<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAttendance extends Model
{
    public const STATUS_PRESENT = 'present';
    public const STATUS_LATE = 'late';
    public const STATUS_EXCUSED = 'excused';
    public const STATUS_ABSENT = 'absent';

    public const MODE_OFFLINE = 'offline';
    public const MODE_ONLINE = 'online';

    public const STATUSES = [
        self::STATUS_PRESENT => 'Present',
        self::STATUS_LATE => 'Late',
        self::STATUS_EXCUSED => 'Excused',
        self::STATUS_ABSENT => 'Absent',
    ];

    public const ATTENDANCE_MODES = [
        self::MODE_OFFLINE => 'Offline',
        self::MODE_ONLINE => 'Online',
    ];

    /**
     * Status yang dihitung sebagai hadir untuk assessment.
     */
    public const COUNTED_AS_PRESENT = [
        self::STATUS_PRESENT,
        self::STATUS_LATE,
    ];

    protected $fillable = [
        'batch_id',
        'instructor_schedule_id',
        'student_id',
        'status',
        'attendance_mode',
        'attended_at',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'attended_at' => 'datetime',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function instructorSchedule(): BelongsTo
    {
        return $this->belongsTo(InstructorSchedule::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopePresentLike($query)
    {
        return $query->whereIn('status', self::COUNTED_AS_PRESENT);
    }

    public function scopeOnline($query)
    {
        return $query->where('attendance_mode', self::MODE_ONLINE);
    }

    public function scopeOffline($query)
    {
        return $query->where('attendance_mode', self::MODE_OFFLINE);
    }

    public function isCountedAsPresent(): bool
    {
        return in_array($this->status, self::COUNTED_AS_PRESENT, true);
    }

    public function isPresent(): bool
    {
        return $this->status === self::STATUS_PRESENT;
    }

    public function isLate(): bool
    {
        return $this->status === self::STATUS_LATE;
    }

    public function isExcused(): bool
    {
        return $this->status === self::STATUS_EXCUSED;
    }

    public function isAbsent(): bool
    {
        return $this->status === self::STATUS_ABSENT;
    }

    public function isOnline(): bool
    {
        return $this->attendance_mode === self::MODE_ONLINE;
    }

    public function isOffline(): bool
    {
        return $this->attendance_mode === self::MODE_OFFLINE;
    }
}