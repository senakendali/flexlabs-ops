<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiAgent extends Model
{
    protected $fillable = [
        'division',
        'code',
        'name',
        'role',
        'description',
        'system_prompt',
        'agent_type',
        'status',
        'runtime_state',
        'avatar',
        'can_delegate',
    ];

    protected $casts = [
        'can_delegate' => 'boolean',
    ];

    public function conversations(): HasMany
    {
        return $this->hasMany(
            AiConversation::class,
            'primary_agent_id'
        );
    }

    public function messages(): HasMany
    {
        return $this->hasMany(
            AiMessage::class,
            'agent_id'
        );
    }

    public function orchestratedWorkflows(): HasMany
    {
        return $this->hasMany(
            AiWorkflow::class,
            'orchestrator_agent_id'
        );
    }

    public function workflowSteps(): HasMany
    {
        return $this->hasMany(
            AiWorkflowStep::class,
            'agent_id'
        );
    }
}