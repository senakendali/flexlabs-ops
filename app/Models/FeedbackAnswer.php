<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedbackAnswer extends Model
{
    protected $fillable = [
        'feedback_response_id',
        'feedback_question_id',
        'question_text_snapshot',
        'question_type_snapshot',
        'answer_value',
        'answer_number',
        'answer_text',
        'answer_json',
    ];

    protected $casts = [
        'feedback_response_id' => 'integer',
        'feedback_question_id' => 'integer',
        'answer_number' => 'decimal:2',
        'answer_json' => 'array',
    ];

    public function response(): BelongsTo
    {
        return $this->belongsTo(FeedbackResponse::class, 'feedback_response_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(FeedbackQuestion::class, 'feedback_question_id');
    }

    public function getDisplayAnswerAttribute(): mixed
    {
        if (! blank($this->answer_text)) {
            return $this->answer_text;
        }

        if (! is_null($this->answer_number)) {
            return $this->answer_number;
        }

        if (! blank($this->answer_value)) {
            return $this->answer_value;
        }

        return $this->answer_json;
    }
}