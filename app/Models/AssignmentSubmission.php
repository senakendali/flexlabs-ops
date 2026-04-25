<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignmentSubmission extends Model
{
    use HasFactory;

    protected $table = 'assignment_submissions';

    protected $fillable = [
        'assignment_id',
        'batch_assignment_id',
        'batch_id',
        'student_id',
        'answer_text',
        'answer_url',
        'submitted_file',
        'status',
        'score',
        'feedback',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'score' => 'integer',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class, 'assignment_id');
    }

    public function batchAssignment(): BelongsTo
    {
        return $this->belongsTo(BatchAssignment::class, 'batch_assignment_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'submitted' => 'Submitted',
            'late' => 'Late',
            'reviewed' => 'Reviewed',
            'returned' => 'Returned',
            default => ucfirst((string) $this->status),
        };
    }

    public function getIsSubmittedAttribute(): bool
    {
        return in_array($this->status, ['submitted', 'late', 'reviewed', 'returned'], true);
    }

    public function getIsReviewedAttribute(): bool
    {
        return $this->status === 'reviewed' || ! is_null($this->reviewed_at);
    }

    public function getHasAnswerAttribute(): bool
    {
        return !empty($this->answer_text)
            || !empty($this->answer_url)
            || !empty($this->submitted_file);
    }
}