<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AtkStock extends Model
{
    protected $table = 'atk_stocks';

    protected $fillable = [
        'atk_item_id',
        'current_stock',
        'reserved_stock',
        'location',
        'notes',
    ];

    protected $casts = [
        'atk_item_id' => 'integer',
        'current_stock' => 'integer',
        'reserved_stock' => 'integer',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(AtkItem::class, 'atk_item_id');
    }

    public function getAvailableStockAttribute(): int
    {
        return max(0, $this->current_stock - $this->reserved_stock);
    }
}