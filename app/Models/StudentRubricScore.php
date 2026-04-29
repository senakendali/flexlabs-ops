<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentRubricScore extends Model
{
    protected $fillable = [
        'student_assessment_score_id',
        'assessment_rubric_criteria_id',
        'raw_score',
        'weight',
        'weighted_score',
        'note',
    ];

    protected $casts = [
        'raw_score' => 'decimal:2',
        'weight' => 'decimal:2',
        'weighted_score' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (StudentRubricScore $score) {
            $score->syncWeightedScore();
        });
    }

    public function assessmentScore(): BelongsTo
    {
        return $this->belongsTo(StudentAssessmentScore::class, 'student_assessment_score_id');
    }

    public function criteria(): BelongsTo
    {
        return $this->belongsTo(AssessmentRubricCriteria::class, 'assessment_rubric_criteria_id');
    }

    public function calculateWeightedScore(): float
    {
        $rawScore = (float) $this->raw_score;
        $weight = (float) $this->weight;

        return round(($rawScore * $weight) / 100, 2);
    }

    public function syncWeightedScore(): void
    {
        $this->weighted_score = $this->calculateWeightedScore();
    }
}