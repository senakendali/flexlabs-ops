<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AtkRequest extends Model
{
    protected $table = 'atk_requests';

    protected $fillable = [
        'request_number',
        'user_id',
        'request_date',
        'status',
        'notes',
        'rejection_reason',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'approved_by' => 'integer',
        'request_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected static function booted(): void
    {
        static::creating(function (self $request) {
            if (blank($request->request_number)) {
                $request->request_number = self::generateRequestNumber();
            }

            if (blank($request->request_date)) {
                $request->request_date = now()->toDateString();
            }

            if (blank($request->status)) {
                $request->status = self::STATUS_PENDING;
            }
        });
    }

    public static function generateRequestNumber(): string
    {
        $date = now()->format('Ymd');
        $count = self::whereDate('created_at', now()->toDateString())->count() + 1;

        return 'ATK-REQ-' . $date . '-' . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(AtkRequestItem::class, 'atk_request_id');
    }

    public function getTotalItemsAttribute(): int
    {
        return (int) $this->items->sum('qty');
    }
}