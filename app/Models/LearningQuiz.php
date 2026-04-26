<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningQuiz extends Model
{
    use HasFactory;

    protected $table = 'learning_quizzes';

    protected $fillable = [
        'topic_id',
        'sub_topic_id',
        'title',
        'slug',
        'instruction',
        'quiz_type',
        'duration_minutes',
        'passing_score',
        'max_attempts',
        'randomize_questions',
        'randomize_options',
        'show_result_after_submit',
        'show_correct_answer_after_submit',
        'status',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'passing_score' => 'integer',
        'max_attempts' => 'integer',
        'randomize_questions' => 'boolean',
        'randomize_options' => 'boolean',
        'show_result_after_submit' => 'boolean',
        'show_correct_answer_after_submit' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function subTopic(): BelongsTo
    {
        return $this->belongsTo(SubTopic::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(LearningQuizQuestion::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function activeQuestions(): HasMany
    {
        return $this->hasMany(LearningQuizQuestion::class)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function batchLearningQuizzes(): HasMany
    {
        return $this->hasMany(BatchLearningQuiz::class);
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

    public function getQuizTypeLabelAttribute(): string
    {
        return match ($this->quiz_type) {
            'practice' => 'Practice',
            'graded' => 'Graded',
            default => ucfirst((string) $this->quiz_type),
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'published' => 'Published',
            'archived' => 'Archived',
            default => ucfirst((string) $this->status),
        };
    }
}