<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningQuizQuestion extends Model
{
    use HasFactory;

    protected $table = 'learning_quiz_questions';

    protected $fillable = [
        'learning_quiz_id',
        'question_text',
        'question_type',
        'explanation',
        'score',
        'sort_order',
        'is_required',
        'is_active',
    ];

    protected $casts = [
        'score' => 'integer',
        'sort_order' => 'integer',
        'is_required' => 'boolean',
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

    public function options(): HasMany
    {
        return $this->hasMany(LearningQuizOption::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function activeOptions(): HasMany
    {
        return $this->hasMany(LearningQuizOption::class)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(LearningQuizAnswer::class);
    }

    public function getQuestionTypeLabelAttribute(): string
    {
        return match ($this->question_type) {
            'single_choice' => 'Single Choice',
            'multiple_choice' => 'Multiple Choice',
            'true_false' => 'True / False',
            'short_answer' => 'Short Answer',
            default => ucfirst(str_replace('_', ' ', (string) $this->question_type)),
        };
    }
}