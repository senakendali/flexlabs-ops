<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternalMemoApproval extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'internal_memo_id',
        'step_order',
        'role_label',

        'approver_id',
        'approver_email',
        'approver_name',
        'approver_position',

        'status',
        'notes',

        'notification_sent_at',
        'reminder_sent_at',

        'approved_at',
        'rejected_at',
    ];

    protected $casts = [
        'step_order' => 'integer',

        'notification_sent_at' => 'datetime',
        'reminder_sent_at' => 'datetime',

        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function memo(): BelongsTo
    {
        return $this->belongsTo(InternalMemo::class, 'internal_memo_id');
    }

    public function internalMemo(): BelongsTo
    {
        return $this->belongsTo(InternalMemo::class, 'internal_memo_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_SKIPPED => 'Skipped',
            default => '-',
        };
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isSkipped(): bool
    {
        return $this->status === self::STATUS_SKIPPED;
    }

    public function hasNotificationBeenSent(): bool
    {
        return ! is_null($this->notification_sent_at);
    }

    public function hasReminderBeenSent(): bool
    {
        return ! is_null($this->reminder_sent_at);
    }

    public function markNotificationSent(): bool
    {
        return $this->forceFill([
            'notification_sent_at' => now(),
        ])->save();
    }

    public function markReminderSent(): bool
    {
        return $this->forceFill([
            'reminder_sent_at' => now(),
        ])->save();
    }

    public function markApproved(?string $notes = null): bool
    {
        return $this->forceFill([
            'status' => self::STATUS_APPROVED,
            'notes' => $notes,
            'approved_at' => now(),
            'rejected_at' => null,
        ])->save();
    }

    public function markRejected(?string $notes = null): bool
    {
        return $this->forceFill([
            'status' => self::STATUS_REJECTED,
            'notes' => $notes,
            'rejected_at' => now(),
            'approved_at' => null,
        ])->save();
    }
}