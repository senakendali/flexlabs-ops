<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentRubric extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'assessment_component_id',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function component(): BelongsTo
    {
        return $this->belongsTo(AssessmentComponent::class, 'assessment_component_id');
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(AssessmentRubricCriteria::class)
            ->orderBy('sort_order');
    }

    public function levels(): HasMany
    {
        return $this->hasMany(AssessmentRubricLevel::class)
            ->orderBy('sort_order');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}