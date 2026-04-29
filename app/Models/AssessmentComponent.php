<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentComponent extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'assessment_template_id',
        'name',
        'code',
        'type',
        'weight',
        'max_score',
        'is_required',
        'is_auto_calculated',
        'sort_order',
        'description',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'max_score' => 'decimal:2',
        'is_required' => 'boolean',
        'is_auto_calculated' => 'boolean',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(AssessmentTemplate::class, 'assessment_template_id');
    }

    public function rubric(): HasOne
    {
        return $this->hasOne(AssessmentRubric::class)
            ->where('is_active', true);
    }

    public function rubrics(): HasMany
    {
        return $this->hasMany(AssessmentRubric::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(StudentAssessmentScore::class);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeAutoCalculated($query)
    {
        return $query->where('is_auto_calculated', true);
    }
}