<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicLearningMaterialImage extends Model
{
    protected $fillable = [
        'public_learning_material_id',
        'image_path',
        'caption',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function material(): BelongsTo
    {
        return $this->belongsTo(PublicLearningMaterial::class, 'public_learning_material_id');
    }
}