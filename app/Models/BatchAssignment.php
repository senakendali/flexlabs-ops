<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BatchAssignment extends Model
{
    use HasFactory;

    protected $table = 'batch_assignments';

    protected $fillable = [
        'assignment_id',
        'batch_id',
        'available_at',
        'due_at',
        'closed_at',
        'max_score',
        'allow_late_submission',
        'status',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'available_at' => 'datetime',
        'due_at' => 'datetime',
        'closed_at' => 'datetime',
        'max_score' => 'integer',
        'allow_late_submission' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class, 'assignment_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class, 'batch_assignment_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getEffectiveMaxScoreAttribute(): int
    {
        return $this->max_score ?? $this->assignment?->max_score ?? 100;
    }

    public function getIsPublishedAttribute(): bool
    {
        return $this->status === 'published' && $this->is_active;
    }

    public function getIsClosedAttribute(): bool
    {
        if ($this->status === 'closed') {
            return true;
        }

        if ($this->closed_at && now()->greaterThan($this->closed_at)) {
            return true;
        }

        return false;
    }

    public function getIsAvailableAttribute(): bool
    {
        if (! $this->is_published) {
            return false;
        }

        if ($this->available_at && now()->lessThan($this->available_at)) {
            return false;
        }

        if ($this->is_closed) {
            return false;
        }

        return true;
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_at && now()->greaterThan($this->due_at);
    }
}