<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizParticipantAnswer extends Model
{
   protected $fillable = [
        'quiz_id',
        'quiz_participant_id',
        'quiz_question_id',
        'quiz_option_id', 
    ];

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(QuizParticipant::class, 'quiz_participant_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class, 'quiz_question_id');
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(QuizQuestionOption::class, 'quiz_question_option_id');
    }
}