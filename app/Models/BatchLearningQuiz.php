<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BatchLearningQuiz extends Model
{
    use HasFactory;

    protected $table = 'batch_learning_quizzes';

    protected $fillable = [
        'learning_quiz_id',
        'batch_id',
        'available_at',
        'due_at',
        'closed_at',
        'duration_minutes',
        'passing_score',
        'max_attempts',
        'allow_late_attempt',
        'status',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'available_at' => 'datetime',
        'due_at' => 'datetime',
        'closed_at' => 'datetime',
        'duration_minutes' => 'integer',
        'passing_score' => 'integer',
        'max_attempts' => 'integer',
        'allow_late_attempt' => 'boolean',
        'is_active' => 'boolean',
    ];

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

    public function attempts(): HasMany
    {
        return $this->hasMany(LearningQuizAttempt::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getEffectiveDurationMinutesAttribute(): ?int
    {
        return $this->duration_minutes ?? $this->learningQuiz?->duration_minutes;
    }

    public function getEffectivePassingScoreAttribute(): int
    {
        return $this->passing_score ?? $this->learningQuiz?->passing_score ?? 70;
    }

    public function getEffectiveMaxAttemptsAttribute(): int
    {
        return $this->max_attempts ?? $this->learningQuiz?->max_attempts ?? 1;
    }

    public function getIsAvailableAttribute(): bool
    {
        if (!$this->is_active || $this->status !== 'published') {
            return false;
        }

        if ($this->available_at && now()->lt($this->available_at)) {
            return false;
        }

        if ($this->closed_at && now()->gt($this->closed_at)) {
            return false;
        }

        return true;
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_at && now()->gt($this->due_at);
    }

    public function getIsClosedAttribute(): bool
    {
        return $this->status === 'closed'
            || ($this->closed_at && now()->gt($this->closed_at));
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'published' => 'Published',
            'closed' => 'Closed',
            'archived' => 'Archived',
            default => ucfirst((string) $this->status),
        };
    }
}