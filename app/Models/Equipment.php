<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Equipment extends Model
{
    protected $table = 'equipments';

    protected $fillable = [
        'name',
        'slug',
        'code',
        'serial_number',
        'brand',
        'model',
        'description',
        'condition',
        'status',
        'type',
        'assigned_user_id',
        'location',
        'purchase_date',
        'purchase_price',
        'last_maintenance_at',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'purchase_date' => 'date',
        'purchase_price' => 'decimal:2',
        'last_maintenance_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function borrowings(): HasMany
    {
        return $this->hasMany(EquipmentBorrowing::class);
    }

    public function activeBorrowing(): HasOne
    {
        return $this->hasOne(EquipmentBorrowing::class)
            ->where('status', 'borrowed')
            ->latestOfMany();
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(EquipmentAssignment::class);
    }

    public function activeAssignment(): HasOne
    {
        return $this->hasOne(EquipmentAssignment::class)
            ->whereNull('unassigned_at')
            ->latestOfMany('assigned_at');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeAssigned($query)
    {
        return $query->where('type', 'assigned');
    }

    public function scopeBorrowable($query)
    {
        return $query->where('type', 'borrowable');
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    public function isAssigned(): bool
    {
        return ($this->type ?? 'borrowable') === 'assigned';
    }

    public function isBorrowable(): bool
    {
        return ($this->type ?? 'borrowable') === 'borrowable';
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    public function isBorrowed(): bool
    {
        return $this->status === 'borrowed';
    }

    public function isUnderMaintenance(): bool
    {
        return $this->status === 'maintenance';
    }

    /*
    |--------------------------------------------------------------------------
    | BOOT
    |--------------------------------------------------------------------------
    */

    protected static function booted(): void
    {
        static::creating(function (self $equipment) {
            if (blank($equipment->slug) && filled($equipment->name)) {
                $equipment->slug = Str::slug($equipment->name);
            }

            if (blank($equipment->type)) {
                $equipment->type = 'borrowable';
            }
        });

        static::saving(function (self $equipment) {
            if (($equipment->type ?? 'borrowable') === 'assigned' && $equipment->status === 'borrowed') {
                $equipment->status = 'available';
            }

            if (($equipment->type ?? 'borrowable') === 'borrowable') {
                $equipment->assigned_user_id = null;
            }
        });
    }
}