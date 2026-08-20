<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentSchedule extends Model
{
    protected $fillable = [
        'order_id',
        'title',
        'amount',
        'gross_amount',
        'wht_rate',
        'wht_amount',
        'net_amount',
        'due_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gross_amount' => 'decimal:2',
        'wht_rate' => 'decimal:2',
        'wht_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'due_date' => 'date',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function resolvedGrossAmount(): float
    {
        return (float) ($this->gross_amount ?? $this->amount);
    }

    public function resolvedNetAmount(): float
    {
        return (float) ($this->net_amount ?? $this->amount);
    }
}