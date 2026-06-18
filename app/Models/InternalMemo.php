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

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_WAITING_ACKNOWLEDGEMENT = 'waiting_acknowledgement';
    public const STATUS_WAITING_APPROVAL = 'waiting_approval';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    public const PAYMENT_SOURCE_BANK = 'bank';
    public const PAYMENT_SOURCE_CASH = 'cash';

    public const TAX_TREATMENT_INCLUDE = 'include';
    public const TAX_TREATMENT_NOT_INCLUDE = 'not_include';

    public const TAX_ENTITY_PKP = 'pkp';
    public const TAX_ENTITY_NON_PKP = 'non_pkp';

    protected $fillable = [
        'memo_number',
        'memo_date',
        'due_date',

        'subject',
        'attachment_label',

        'to_name',
        'to_position',

        'from_name',
        'from_position',

        'purpose',
        'notes',

        'payment_source',

        'subtotal_amount',
        'tax_rate',
        'tax_treatment',
        'tax_entity_type',
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
        'due_date' => 'date',

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

    public function getPaymentSourceLabelAttribute(): string
    {
        return match ($this->payment_source) {
            self::PAYMENT_SOURCE_BANK => 'Bank',
            self::PAYMENT_SOURCE_CASH => 'Cash',
            default => '-',
        };
    }

    public function getTaxTreatmentLabelAttribute(): string
    {
        return match ($this->tax_treatment) {
            self::TAX_TREATMENT_INCLUDE => 'Tax Include',
            self::TAX_TREATMENT_NOT_INCLUDE => 'Tax Not Include',
            default => '-',
        };
    }

    public function getTaxEntityTypeLabelAttribute(): string
    {
        return match ($this->tax_entity_type) {
            self::TAX_ENTITY_PKP => 'PKP',
            self::TAX_ENTITY_NON_PKP => 'Non PKP',
            default => '-',
        };
    }

    public function isTaxIncluded(): bool
    {
        return $this->tax_treatment === self::TAX_TREATMENT_INCLUDE;
    }

    public function isTaxNotIncluded(): bool
    {
        return $this->tax_treatment === self::TAX_TREATMENT_NOT_INCLUDE;
    }

    public function isPkp(): bool
    {
        return $this->tax_entity_type === self::TAX_ENTITY_PKP;
    }

    public function isNonPkp(): bool
    {
        return $this->tax_entity_type === self::TAX_ENTITY_NON_PKP;
    }
}