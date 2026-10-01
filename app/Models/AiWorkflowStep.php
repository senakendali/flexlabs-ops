<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiWorkflowStep extends Model
{
    protected $fillable = [
        'workflow_id',
        'agent_id',
        'step_order',
        'name',
        'description',
        'action',
        'status',
        'input',
        'output',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'input' => 'array',
        'output' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(
            AiWorkflow::class,
            'workflow_id'
        );
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(
            AiAgent::class,
            'agent_id'
        );
    }
}