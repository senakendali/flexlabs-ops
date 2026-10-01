<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiConversation extends Model
{
    protected $fillable = [
        'user_id',
        'primary_agent_id',
        'division',
        'title',
        'status',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function primaryAgent(): BelongsTo
    {
        return $this->belongsTo(
            AiAgent::class,
            'primary_agent_id'
        );
    }

    public function messages(): HasMany
    {
        return $this->hasMany(
            AiMessage::class,
            'conversation_id'
        )->orderBy('created_at');
    }

    public function workflows(): HasMany
    {
        return $this->hasMany(
            AiWorkflow::class,
            'conversation_id'
        );
    }
}