<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrialParticipant extends Model
{
    protected $fillable = [
        'trial_schedule_id',
        'trial_theme_id',
        'full_name',
        'email',
        'phone',
        'domicile_city',
        'current_activity',
        'goal',
        'input_source',
        'status',
        'notes',
    ];

    protected $attributes = [
        'input_source' => 'admin',
        'status' => 'registered',
    ];

    public function trialSchedule(): BelongsTo
    {
        return $this->belongsTo(TrialSchedule::class);
    }

    public function trialTheme(): BelongsTo
    {
        return $this->belongsTo(TrialTheme::class);
    }

    
}