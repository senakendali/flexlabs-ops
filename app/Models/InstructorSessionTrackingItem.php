<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstructorSessionTrackingItem extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_NOT_DELIVERED = 'not_delivered';

    protected $fillable = [
        'instructor_session_tracking_id',
        'sub_topic_id',
        'is_delivered',
        'delivery_status',
        'not_delivered_reason',
        'delivery_notes',
        'sort_order',
    ];

    protected $casts = [
        'is_delivered' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function tracking(): BelongsTo
    {
        return $this->belongsTo(InstructorSessionTracking::class, 'instructor_session_tracking_id');
    }

    public function subTopic(): BelongsTo
    {
        return $this->belongsTo(SubTopic::class);
    }

    public function getDeliveryStatusLabelAttribute(): string
    {
        return match ($this->delivery_status) {
            self::STATUS_DELIVERED => 'Delivered',
            self::STATUS_PARTIAL => 'Partial',
            self::STATUS_NOT_DELIVERED => 'Not Delivered',
            default => 'Pending',
        };
    }
}
