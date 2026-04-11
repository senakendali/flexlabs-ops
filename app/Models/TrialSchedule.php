<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrialSchedule extends Model
{
    protected $fillable = [
        'program_id',
        'trial_theme_id',
        'name',
        'schedule_date',
        'start_time',
        'end_time',
        'quota',
        'description',
        'is_active',
    ];

    protected $casts = [
        'schedule_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function trialTheme(): BelongsTo
    {
        return $this->belongsTo(TrialTheme::class);
    }
}