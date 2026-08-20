<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GroupRegistration extends Model
{
    public const BUYER_INDIVIDUAL = 'individual';
    public const BUYER_COMPANY = 'company';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_COMPLETED = 'completed';

    public const WHT_NOT_APPLICABLE = 'not_applicable';
    public const WHT_PENDING = 'pending';
    public const WHT_RECEIVED = 'received';

    protected $fillable = [
        'registration_number',
        'buyer_type',
        'buyer_student_id',
        'company_id',
        'batch_id',
        'buyer_name',
        'buyer_email',
        'buyer_phone',
        'quantity',
        'price_per_seat',
        'original_price',
        'discount',
        'service_amount',
        'wht_rate',
        'wht_amount',
        'invoice_total',
        'net_payable',
        'wht_status',
        'wht_certificate_number',
        'wht_certificate_date',
        'wht_certificate_file',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'buyer_student_id' => 'integer',
        'company_id' => 'integer',
        'batch_id' => 'integer',
        'quantity' => 'integer',
        'price_per_seat' => 'decimal:2',
        'original_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'service_amount' => 'decimal:2',
        'wht_rate' => 'decimal:2',
        'wht_amount' => 'decimal:2',
        'invoice_total' => 'decimal:2',
        'net_payable' => 'decimal:2',
        'wht_certificate_date' => 'date',
    ];

    public function buyerStudent(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'buyer_student_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(GroupRegistrationParticipant::class);
    }

    public function activeParticipants(): HasMany
    {
        return $this->hasMany(GroupRegistrationParticipant::class)
            ->where('status', '!=', GroupRegistrationParticipant::STATUS_CANCELLED);
    }

    public function order(): HasOne
    {
        return $this->hasOne(Order::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isCompany(): bool
    {
        return $this->buyer_type === self::BUYER_COMPANY;
    }

    public function isIndividual(): bool
    {
        return $this->buyer_type === self::BUYER_INDIVIDUAL;
    }

    public function usesWht(): bool
    {
        return $this->isCompany() && (float) $this->wht_rate > 0;
    }

    public function assignedSeatsCount(): int
    {
        if ($this->relationLoaded('activeParticipants')) {
            return $this->activeParticipants->count();
        }

        return $this->activeParticipants()->count();
    }

    public function availableSeatsCount(): int
    {
        return max(0, $this->quantity - $this->assignedSeatsCount());
    }

    public function hasAvailableSeat(): bool
    {
        return $this->availableSeatsCount() > 0;
    }
}