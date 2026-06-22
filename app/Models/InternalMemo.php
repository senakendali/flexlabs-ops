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
        'attachment_url',

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

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_SUBMITTED => 'Submitted',
            self::STATUS_WAITING_ACKNOWLEDGEMENT => 'Waiting Acknowledgement',
            self::STATUS_WAITING_APPROVAL => 'Waiting Approval',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_CANCELLED => 'Cancelled',
            default => '-',
        };
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

    public function hasAttachmentUrl(): bool
    {
        return filled($this->attachment_url);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isSubmitted(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function isWaitingAcknowledgement(): bool
    {
        return $this->status === self::STATUS_WAITING_ACKNOWLEDGEMENT;
    }

    public function isWaitingApproval(): bool
    {
        return $this->status === self::STATUS_WAITING_APPROVAL;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
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