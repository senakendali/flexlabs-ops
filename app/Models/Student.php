<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Student extends Model
{
    protected $fillable = [
        'user_id',
        'full_name',
        'avatar_url',
        'email',
        'phone',
        'city',
        'current_status',
        'bio',
        'goal',
        'source',
        'status',
    ];

    protected $casts = [
        'user_id' => 'integer',
    ];

    protected $appends = [
        'avatarUrl',
        'fullName',
    ];

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    |
    | Jangan panggil $this->avatar_url di dalam getAvatarUrlAttribute(),
    | karena itu akan memanggil accessor yang sama lagi.
    |
    */

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->attributes['avatar_url'] ?? null;
    }

    public function getFullNameAttribute(): string
    {
        return $this->attributes['full_name'] ?? '';
    }

    /*
    |--------------------------------------------------------------------------
    | Preferences
    |--------------------------------------------------------------------------
    */

    public function preferences(): HasMany
    {
        return $this->hasMany(StudentPreference::class);
    }

    public function getPreference(string $key, bool $default = true): bool
    {
        $preference = $this->relationLoaded('preferences')
            ? $this->preferences->firstWhere('preference_key', $key)
            : $this->preferences()->where('preference_key', $key)->first();

        if (!$preference) {
            return $default;
        }

        return (bool) $preference->enabled;
    }

    public function setPreference(string $key, bool $enabled): StudentPreference
    {
        return $this->preferences()->updateOrCreate(
            [
                'preference_key' => $key,
            ],
            [
                'enabled' => $enabled,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function assignmentSubmissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class, 'student_id');
    }

    public function learningQuizAttempts(): HasMany
    {
        return $this->hasMany(LearningQuizAttempt::class);
    }

    public function learningQuizAnswers(): HasManyThrough
    {
        return $this->hasManyThrough(
            LearningQuizAnswer::class,
            LearningQuizAttempt::class,
            'student_id',
            'learning_quiz_attempt_id',
            'id',
            'id'
        );
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class)
            ->latest('enrolled_at')
            ->latest('id');
    }

    public function activeEnrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class)
            ->where('status', 'active')
            ->where('access_status', 'active')
            ->latest('enrolled_at')
            ->latest('id');
    }

    public function mentoringSessions(): HasMany
    {
        return $this->hasMany(StudentMentoringSession::class);
    }

    public function activeMentoringSessions(): HasMany
    {
        return $this->hasMany(StudentMentoringSession::class)
            ->whereIn('status', ['pending', 'approved', 'rescheduled'])
            ->latest('requested_at')
            ->latest('id');
    }

    public function assessmentScores(): HasMany
    {
        return $this->hasMany(StudentAssessmentScore::class);
    }

    public function reportCards(): HasMany
    {
        return $this->hasMany(ReportCard::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(StudentAttendance::class);
    }
}