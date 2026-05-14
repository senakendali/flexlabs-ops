<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicLearningMaterialBlock extends Model
{
    protected $fillable = [
        'public_learning_material_id',
        'type',
        'title',
        'content',
        'code_language',
        'code_content',
        'image_path',
        'image_caption',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function material(): BelongsTo
    {
        return $this->belongsTo(PublicLearningMaterial::class, 'public_learning_material_id');
    }
}