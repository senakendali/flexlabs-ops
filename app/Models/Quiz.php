<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quiz extends Model
{
    protected $fillable = [
        'title',
        'description',
        'status',
        'opens_at',
        'quota',
    ];

    protected $casts = [
        'opens_at' => 'datetime',
        'quota' => 'integer',
    ];

    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class);
    }

    public function participants()
    {
        return $this->hasMany(QuizParticipant::class);
    }

    public function participantAnswers()
    {
        return $this->hasMany(QuizParticipantAnswer::class);
    }
}