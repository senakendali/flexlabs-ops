<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentAssignment extends Model
{
    protected $table = 'equipment_assignments';

    protected $fillable = [
        'equipment_id',
        'user_id',
        'assigned_at',
        'unassigned_at',
        'notes',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'unassigned_at' => 'datetime',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->unassigned_at === null;
    }
}