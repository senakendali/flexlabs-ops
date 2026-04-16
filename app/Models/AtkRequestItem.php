<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AtkRequestItem extends Model
{
    protected $table = 'atk_request_items';

    protected $fillable = [
        'atk_request_id',
        'atk_item_id',
        'qty',
        'unit',
        'notes',
    ];

    protected $casts = [
        'atk_request_id' => 'integer',
        'atk_item_id' => 'integer',
        'qty' => 'integer',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(AtkRequest::class, 'atk_request_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(AtkItem::class, 'atk_item_id');
    }
}