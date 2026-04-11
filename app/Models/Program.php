<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\TrialTheme;

class Program extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function trialThemes(): HasMany
    {
        return $this->hasMany(TrialTheme::class)
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function trialSchedules(): HasMany
    {
        return $this->hasMany(TrialSchedule::class)
            ->orderBy('day_name')
            ->orderBy('start_time');
    }
}