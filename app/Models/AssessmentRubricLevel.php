<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentRubricLevel extends Model
{
    protected $fillable = [
        'assessment_rubric_id',
        'name',
        'min_score',
        'max_score',
        'description',
        'sort_order',
    ];

    protected $casts = [
        'min_score' => 'decimal:2',
        'max_score' => 'decimal:2',
    ];

    public function rubric(): BelongsTo
    {
        return $this->belongsTo(AssessmentRubric::class, 'assessment_rubric_id');
    }

    public function scopeForScore($query, float $score)
    {
        return $query
            ->where('min_score', '<=', $score)
            ->where('max_score', '>=', $score);
    }
}