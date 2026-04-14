<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'order_id',
        'payment_schedule_id',
        'invoice_number',
        'public_token',
        'payment_url',
        'amount',
        'payment_date',
        'payment_method',
        'reference_number',
        'gateway_transaction_id',
        'gateway_provider',
        'gateway_payload',
        'status',
        'expired_at',
        'notes',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'gateway_payload' => 'array',
        'expired_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function paymentSchedule(): BelongsTo
    {
        return $this->belongsTo(PaymentSchedule::class);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isActivePaymentLink(): bool
    {
        if (!$this->payment_url || !$this->public_token) {
            return false;
        }

        if ($this->isPaid() || $this->isExpired() || $this->isCancelled()) {
            return false;
        }

        if ($this->expired_at && now()->greaterThan($this->expired_at)) {
            return false;
        }

        return true;
    }

    public function getPublicPaymentLinkAttribute(): ?string
    {
        if (!$this->public_token) {
            return null;
        }

        return route('public.payments.show', $this->public_token);
    }
}