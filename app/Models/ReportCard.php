<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportCard extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'student_id',
        'batch_id',
        'program_id',
        'assessment_template_id',
        'report_no',
        'attendance_percent',
        'progress_percent',
        'final_score',
        'grade',
        'status',
        'is_certificate_eligible',
        'summary',
        'strengths',
        'improvements',
        'instructor_note',
        'academic_note',
        'score_snapshot',
        'rubric_snapshot',
        'rule_snapshot',
        'pdf_path',
        'generated_by',
        'generated_at',
        'published_by',
        'published_at',
    ];

    protected $casts = [
        'attendance_percent' => 'decimal:2',
        'progress_percent' => 'decimal:2',
        'final_score' => 'decimal:2',
        'is_certificate_eligible' => 'boolean',
        'score_snapshot' => 'array',
        'rubric_snapshot' => 'array',
        'rule_snapshot' => 'array',
        'generated_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(AssessmentTemplate::class, 'assessment_template_id');
    }

    public function certificate(): HasOne
    {
        return $this->hasOne(Certificate::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function isPassed(): bool
    {
        return in_array($this->status, ['passed', 'published'], true);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeEligibleForCertificate($query)
    {
        return $query->where('is_certificate_eligible', true);
    }
}