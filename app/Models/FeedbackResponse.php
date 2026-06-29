<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class FeedbackResponse extends Model
{
    protected $fillable = [
        'feedback_form_id',
        'student_id',
        'program_id',
        'batch_id',
        'instructor_id',
        'token',
        'student_name',
        'student_email',
        'status',
        'overall_score',
        'nps_score',
        'submitted_at',
        'metadata',
    ];

    protected $casts = [
        'feedback_form_id' => 'integer',
        'student_id' => 'integer',
        'program_id' => 'integer',
        'batch_id' => 'integer',
        'instructor_id' => 'integer',
        'overall_score' => 'decimal:2',
        'nps_score' => 'integer',
        'submitted_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (FeedbackResponse $response) {
            if (blank($response->token)) {
                $response->token = self::generateUniqueToken();
            }

            if (blank($response->status)) {
                $response->status = 'draft';
            }
        });
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(FeedbackForm::class, 'feedback_form_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(FeedbackAnswer::class);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function scopeSubmitted(Builder $query): Builder
    {
        return $query->where('status', 'submitted');
    }

    public function scopeForProgram(Builder $query, int $programId): Builder
    {
        return $query->where('program_id', $programId);
    }

    public function scopeForBatch(Builder $query, int $batchId): Builder
    {
        return $query->where('batch_id', $batchId);
    }

    public function markAsSubmitted(): void
    {
        $this->forceFill([
            'status' => 'submitted',
            'submitted_at' => now(),
        ])->save();
    }

    private static function generateUniqueToken(): string
    {
        do {
            $token = Str::random(48);
        } while (self::query()->where('token', $token)->exists());

        return $token;
    }
}