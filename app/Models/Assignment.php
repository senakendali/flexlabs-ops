<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assignment extends Model
{
    use HasFactory;

    protected $table = 'assignments';

    protected $fillable = [
        'topic_id',
        'sub_topic_id',
        'title',
        'slug',
        'assignment_type',
        'instruction',
        'attachment_url',
        'starter_file_url',
        'reference_url',
        'estimated_minutes',
        'max_score',
        'is_required',
        'sort_order',
        'status',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'estimated_minutes' => 'integer',
        'max_score' => 'integer',
        'is_required' => 'boolean',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class, 'topic_id');
    }

    public function subTopic(): BelongsTo
    {
        return $this->belongsTo(SubTopic::class, 'sub_topic_id');
    }

    public function batchAssignments(): HasMany
    {
        return $this->hasMany(BatchAssignment::class, 'assignment_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class, 'assignment_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->assignment_type) {
            'text' => 'Text Answer',
            'file' => 'File Upload',
            'link' => 'Link Submission',
            'mixed' => 'Mixed Submission',
            default => 'Assignment',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'published' => 'Published',
            'archived' => 'Archived',
            default => ucfirst((string) $this->status),
        };
    }

    public function getIsPublishedAttribute(): bool
    {
        return $this->status === 'published' && $this->is_active;
    }

    public function getAttachmentCountAttribute(): int
    {
        return collect([
            $this->attachment_url,
            $this->starter_file_url,
            $this->reference_url,
        ])->filter()->count();
    }
}