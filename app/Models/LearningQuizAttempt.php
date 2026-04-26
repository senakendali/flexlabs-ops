<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningQuizAttempt extends Model
{
    use HasFactory;

    protected $table = 'learning_quiz_attempts';

    protected $fillable = [
        'batch_learning_quiz_id',
        'learning_quiz_id',
        'batch_id',
        'student_id',
        'attempt_number',
        'started_at',
        'submitted_at',
        'duration_seconds',
        'score',
        'total_score',
        'percentage',
        'is_passed',
        'status',
        'graded_by',
        'graded_at',
    ];

    protected $casts = [
        'attempt_number' => 'integer',
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'duration_seconds' => 'integer',
        'score' => 'decimal:2',
        'total_score' => 'decimal:2',
        'percentage' => 'decimal:2',
        'is_passed' => 'boolean',
        'graded_at' => 'datetime',
    ];

    public function batchLearningQuiz(): BelongsTo
    {
        return $this->belongsTo(BatchLearningQuiz::class);
    }

    public function learningQuiz(): BelongsTo
    {
        return $this->belongsTo(LearningQuiz::class);
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(LearningQuiz::class, 'learning_quiz_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(LearningQuizAnswer::class);
    }

    public function gradedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'in_progress' => 'In Progress',
            'submitted' => 'Submitted',
            'graded' => 'Graded',
            'expired' => 'Expired',
            'cancelled' => 'Cancelled',
            default => ucfirst(str_replace('_', ' ', (string) $this->status)),
        };
    }
}