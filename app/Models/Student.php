<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $fillable = [
        'user_id',
        'full_name',
        'email',
        'phone',
        'city',
        'current_status',
        'goal',
        'source',
        'status',
    ];

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

    public function learningQuizAnswers(): HasMany
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    public function assessmentScores()
    {
        return $this->hasMany(StudentAssessmentScore::class);
    }

    public function reportCards()
    {
        return $this->hasMany(ReportCard::class);
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(StudentAttendance::class);
    }
}