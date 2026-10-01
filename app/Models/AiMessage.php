<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiMessage extends Model
{
    protected $fillable = [
        'conversation_id',
        'agent_id',
        'user_id',
        'sender_type',
        'message_type',
        'content',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(
            AiConversation::class,
            'conversation_id'
        );
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(
            AiAgent::class,
            'agent_id'
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}