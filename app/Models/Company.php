<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = [
        'name',
        'tax_id',
        'email',
        'phone',
        'address',
        'pic_name',
        'pic_email',
        'pic_phone',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function groupRegistrations(): HasMany
    {
        return $this->hasMany(GroupRegistration::class);
    }
}