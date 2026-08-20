<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupRegistrationParticipant extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_ENROLLED = 'enrolled';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'group_registration_id',
        'student_id',
        'student_enrollment_id',
        'status',
        'enrolled_at',
        'notes',
    ];

    protected $casts = [
        'group_registration_id' => 'integer',
        'student_id' => 'integer',
        'student_enrollment_id' => 'integer',
        'enrolled_at' => 'datetime',
    ];

    public function groupRegistration(): BelongsTo
    {
        return $this->belongsTo(GroupRegistration::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function studentEnrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class);
    }
}