<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Instructor extends Model
{
    use HasFactory;

   protected $fillable = [
        'user_id',
        'name',
        'slug',
        'email',
        'phone',
        'specialization',
        'employment_type',
        'bio',
        'photo',
        'is_active',
    ];


    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function availabilitySlots(): HasMany
    {
        return $this->hasMany(InstructorAvailabilitySlot::class);
    }

    public function mentoringSessions(): HasMany
    {
        return $this->hasMany(StudentMentoringSession::class);
    }

    public function pendingMentoringSessions(): HasMany
    {
        return $this->hasMany(StudentMentoringSession::class)
            ->where('status', 'pending')
            ->latest('requested_at')
            ->latest('id');
    }
}