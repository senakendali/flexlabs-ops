<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningQuizAnswer extends Model
{
    use HasFactory;

    protected $table = 'learning_quiz_answers';

    protected $fillable = [
        'learning_quiz_attempt_id',
        'learning_quiz_question_id',
        'learning_quiz_option_id',
        'selected_option_ids',
        'answer_text',
        'is_correct',
        'score',
        'feedback',
    ];

    protected $casts = [
        'selected_option_ids' => 'array',
        'is_correct' => 'boolean',
        'score' => 'decimal:2',
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(LearningQuizAttempt::class, 'learning_quiz_attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(LearningQuizQuestion::class, 'learning_quiz_question_id');
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(LearningQuizOption::class, 'learning_quiz_option_id');
    }
}