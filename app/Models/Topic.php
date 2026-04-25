<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Topic extends Model
{
    use HasFactory;

    protected $table = 'topics';

    protected $fillable = [
        'module_id',
        'name',
        'description',
        'sort_order',
        'is_active',

        // Topic materials
        'slide_url',
        'starter_code_url',
        'supporting_file_url',
        'external_reference_url',
        'practice_brief',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function subTopics(): HasMany
    {
        return $this->hasMany(SubTopic::class)
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function getHasMaterialsAttribute(): bool
    {
        return !empty($this->slide_url)
            || !empty($this->starter_code_url)
            || !empty($this->supporting_file_url)
            || !empty($this->external_reference_url)
            || !empty($this->practice_brief);
    }

    public function getMaterialsCountAttribute(): int
    {
        return collect([
            $this->slide_url,
            $this->starter_code_url,
            $this->supporting_file_url,
            $this->external_reference_url,
            $this->practice_brief,
        ])->filter()->count();
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'topic_id')
            ->orderBy('sort_order')
            ->orderBy('title');
    }
}