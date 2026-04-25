<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Batch extends Model
{
    protected $fillable = [
        'program_id',
        'name',
        'slug',
        'start_date',
        'end_date',
        'quota',
        'price',
        'status',
        'description',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'price' => 'decimal:2',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function batchAssignments(): HasMany
    {
        return $this->hasMany(BatchAssignment::class, 'batch_id');
    }
}