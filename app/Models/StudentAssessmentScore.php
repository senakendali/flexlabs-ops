<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAssessmentScore extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'student_id',
        'batch_id',
        'assessment_template_id',
        'assessment_component_id',
        'raw_score',
        'weight',
        'weighted_score',
        'feedback',
        'metadata',
        'status',
        'assessed_by',
        'assessed_at',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'raw_score' => 'decimal:2',
        'weight' => 'decimal:2',
        'weighted_score' => 'decimal:2',
        'metadata' => 'array',
        'assessed_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (StudentAssessmentScore $score) {
            $score->syncWeightedScore();
        });
    }

    

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(AssessmentTemplate::class, 'assessment_template_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(AssessmentComponent::class, 'assessment_component_id');
    }

    public function rubricScores(): HasMany
    {
        return $this->hasMany(StudentRubricScore::class);
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
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

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeForStudentBatch($query, int $studentId, int $batchId)
    {
        return $query
            ->where('student_id', $studentId)
            ->where('batch_id', $batchId);
    }
}