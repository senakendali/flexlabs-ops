<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentRubricCriteria extends Model
{
    use SoftDeletes;

    protected $table = 'assessment_rubric_criteria';

    protected $fillable = [
        'assessment_rubric_id',
        'name',
        'code',
        'description',
        'weight',
        'max_score',
        'sort_order',
        'is_required',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'max_score' => 'decimal:2',
        'is_required' => 'boolean',
    ];

    public function rubric(): BelongsTo
    {
        return $this->belongsTo(AssessmentRubric::class, 'assessment_rubric_id');
    }

    public function studentScores(): HasMany
    {
        return $this->hasMany(StudentRubricScore::class, 'assessment_rubric_criteria_id');
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }
}