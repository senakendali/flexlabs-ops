<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningQuizOption extends Model
{
    use HasFactory;

    protected $table = 'learning_quiz_options';

    protected $fillable = [
        'learning_quiz_question_id',
        'option_text',
        'is_correct',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(LearningQuizQuestion::class, 'learning_quiz_question_id');
    }
}