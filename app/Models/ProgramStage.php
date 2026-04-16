<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ProgramStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_id',
        'name',
        'slug',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $stage) {
            if (empty($stage->slug) && !empty($stage->name)) {
                $stage->slug = Str::slug($stage->name);
            }
        });
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function modules(): HasMany
    {
        return $this->hasMany(Module::class, 'program_stage_id')
            ->orderBy('sort_order')
            ->orderBy('name');
    }
}