<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentEnrollment extends Model
{
    use HasFactory;

    protected $table = 'student_enrollments';

    protected $fillable = [
        'student_id',
        'program_id',
        'batch_id',
        'status',
        'access_status',
        'enrollment_source',
        'enrolled_at',
        'started_at',
        'completed_at',
        'access_expires_at',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'access_expires_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active' => 'Active',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'on_hold' => 'On Hold',
            default => ucfirst((string) $this->status),
        };
    }

    public function getAccessStatusLabelAttribute(): string
    {
        return match ($this->access_status) {
            'active' => 'Active',
            'suspended' => 'Suspended',
            'expired' => 'Expired',
            default => ucfirst((string) $this->access_status),
        };
    }

    public function getEnrollmentSourceLabelAttribute(): string
    {
        return match ($this->enrollment_source) {
            'manual' => 'Manual',
            'payment' => 'Payment',
            'import' => 'Import',
            default => ucfirst((string) $this->enrollment_source),
        };
    }

    public function getIsAccessibleAttribute(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->access_status !== 'active') {
            return false;
        }

        if ($this->access_expires_at && now()->gt($this->access_expires_at)) {
            return false;
        }

        return true;
    }
}