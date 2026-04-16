<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class AtkItem extends Model
{
    protected $table = 'atk_items';

    protected $fillable = [
        'name',
        'slug',
        'code',
        'unit',
        'description',
        'minimum_stock',
        'is_active',
    ];

    protected $casts = [
        'minimum_stock' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $item) {
            if (blank($item->slug)) {
                $item->slug = Str::slug($item->name);
            }

            if ($item->code === null || $item->code === '') {
                $item->code = self::generateCode();
            }
        });

        static::updating(function (self $item) {
            if (blank($item->slug)) {
                $item->slug = Str::slug($item->name);
            }
        });
    }

    public static function generateCode(): string
    {
        $prefix = 'ATK';
        $lastId = (self::max('id') ?? 0) + 1;

        return $prefix . '-' . str_pad((string) $lastId, 5, '0', STR_PAD_LEFT);
    }

    public function stock(): HasOne
    {
        return $this->hasOne(AtkStock::class, 'atk_item_id');
    }

    public function requestItems(): HasMany
    {
        return $this->hasMany(AtkRequestItem::class, 'atk_item_id');
    }

    public function getCurrentStockAttribute(): int
    {
        return (int) ($this->stock?->current_stock ?? 0);
    }

    public function getReservedStockAttribute(): int
    {
        return (int) ($this->stock?->reserved_stock ?? 0);
    }

    public function getAvailableStockAttribute(): int
    {
        return max(0, $this->current_stock - $this->reserved_stock);
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->current_stock <= $this->minimum_stock;
    }
}