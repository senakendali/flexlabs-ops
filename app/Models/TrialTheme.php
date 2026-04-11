<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TrialTheme extends Model
{
    protected $fillable = [
        'program_id',
        'name',
        'slug',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (TrialTheme $trialTheme) {
            if (blank($trialTheme->slug) && filled($trialTheme->name)) {
                $trialTheme->slug = Str::slug($trialTheme->name);
            }
        });
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function trialSchedules(): HasMany
    {
        return $this->hasMany(TrialSchedule::class)
            ->orderBy('day_name')
            ->orderBy('start_time');
    }
}