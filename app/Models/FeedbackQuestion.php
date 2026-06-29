<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeedbackQuestion extends Model
{
    protected $fillable = [
        'feedback_form_id',
        'section',
        'question_text',
        'help_text',
        'question_type',
        'rating_scale',
        'options',
        'is_required',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'feedback_form_id' => 'integer',
        'rating_scale' => 'integer',
        'options' => 'array',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(FeedbackForm::class, 'feedback_form_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(FeedbackAnswer::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeRequired(Builder $query): Builder
    {
        return $query->where('is_required', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function isRating(): bool
    {
        return in_array($this->question_type, ['rating_1_5', 'rating_0_10'], true);
    }

    public function isText(): bool
    {
        return in_array($this->question_type, ['text', 'textarea'], true);
    }

    public function isChoice(): bool
    {
        return in_array($this->question_type, ['select', 'radio', 'checkbox'], true);
    }
}