<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'city',
        'current_status',
        'goal',
        'source',
        'status',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}