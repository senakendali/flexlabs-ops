<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternalMemoItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'internal_memo_id',
        'details',
        'price',
        'quantity',
        'estimated_price',
        'remarks',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
        'estimated_price' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function memo(): BelongsTo
    {
        return $this->belongsTo(InternalMemo::class, 'internal_memo_id');
    }

    public function internalMemo(): BelongsTo
    {
        return $this->belongsTo(InternalMemo::class, 'internal_memo_id');
    }
}