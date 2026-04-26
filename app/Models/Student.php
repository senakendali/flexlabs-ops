<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $fillable = [
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
}