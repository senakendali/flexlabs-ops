<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiWorkflow extends Model
{
    protected $fillable = [
        'conversation_id',
        'requested_by_user_id',
        'orchestrator_agent_id',
        'division',
        'title',
        'objective',
        'status',
        'context',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'context' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(
            AiConversation::class,
            'conversation_id'
        );
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'requested_by_user_id'
        );
    }

    public function orchestrator(): BelongsTo
    {
        return $this->belongsTo(
            AiAgent::class,
            'orchestrator_agent_id'
        );
    }

    public function steps(): HasMany
    {
        return $this->hasMany(
            AiWorkflowStep::class,
            'workflow_id'
        )->orderBy('step_order');
    }
}