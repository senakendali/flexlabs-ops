<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

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

    protected static function booted(): void
    {
        static::creating(function (self $equipment) {
            if (blank($equipment->slug) && filled($equipment->name)) {
                $equipment->slug = Str::slug($equipment->name);
            }
        });
    }
}