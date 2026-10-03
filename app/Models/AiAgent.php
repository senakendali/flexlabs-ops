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

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

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


    /*
    |--------------------------------------------------------------------------
    | Avatar State Mapping
    |--------------------------------------------------------------------------
    */

    public function avatarForState(?string $state = null): string
    {
        $state ??= $this->runtime_state;

        $stateMap = [
            'idle' => 'greeting',
            'greeting' => 'greeting',

            'listening' => 'listening',

            'thinking' => 'thinking',

            'working' => 'working',

            'waiting' => 'waiting',
            'waiting_approval' => 'waiting',

            'completed' => 'completed',

            'questioning' => 'questioning',
            'error' => 'questioning',

            'explaining' => 'explain',
            'explain' => 'explain',
        ];

        $visualState = $stateMap[$state] ?? 'greeting';

        return asset(
            'images/agent/'
            . $this->code
            . '/'
            . $this->code
            . '_'
            . $visualState
            . '.png'
        );
    }
}