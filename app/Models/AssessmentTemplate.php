<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'program_id',
        'name',
        'code',
        'description',
        'passing_score',
        'min_attendance_percent',
        'min_progress_percent',
        'requires_final_project',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'passing_score' => 'decimal:2',
        'min_attendance_percent' => 'decimal:2',
        'min_progress_percent' => 'decimal:2',
        'requires_final_project' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function components(): HasMany
    {
        return $this->hasMany(AssessmentComponent::class)
            ->orderBy('sort_order');
    }

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(StudentAssessmentScore::class);
    }

    public function reportCards(): HasMany
    {
        return $this->hasMany(ReportCard::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}