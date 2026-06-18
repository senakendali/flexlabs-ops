<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternalMemoApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'internal_memo_id',
        'step_order',
        'role_label',
        'approver_id',
        'approver_name',
        'approver_position',
        'status',
        'notes',
        'approved_at',
        'rejected_at',
    ];

    protected $casts = [
        'step_order' => 'integer',
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
}