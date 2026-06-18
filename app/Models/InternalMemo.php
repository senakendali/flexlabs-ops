<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InternalMemo extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'memo_number',
        'memo_date',
        'subject',
        'attachment_label',
        'to_name',
        'to_position',
        'from_name',
        'from_position',
        'purpose',
        'notes',
        'subtotal_amount',
        'tax_rate',
        'tax_amount',
        'grand_total_amount',
        'status',
        'created_by',
        'submitted_by',
        'submitted_at',
        'approved_at',
        'rejected_at',
        'cancelled_at',
    ];

    protected $casts = [
        'memo_date' => 'date',
        'subtotal_amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'grand_total_amount' => 'decimal:2',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(InternalMemoItem::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(InternalMemoApproval::class)
            ->orderBy('step_order')
            ->orderBy('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}