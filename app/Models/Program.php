<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\TrialTheme;
use App\Models\TrialSchedule;
use App\Models\ProgramStage;

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

    public function stages(): HasMany
    {
        return $this->hasMany(ProgramStage::class)
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function studentEnrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function activeStudentEnrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class)
            ->where('status', 'active')
            ->where('access_status', 'active');
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    public function assessmentTemplates()
    {
        return $this->hasMany(AssessmentTemplate::class);
    }

    public function reportCards()
    {
        return $this->hasMany(ReportCard::class);
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    
}