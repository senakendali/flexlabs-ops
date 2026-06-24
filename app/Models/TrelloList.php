<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrelloList extends Model
{
    protected $fillable = [
        'trello_integration_id',
        'source_key',

        'trello_board_id',
        'trello_list_id',

        'name',
        'position',

        'is_closed',
        'normalized_status',

        'raw_json',
    ];

    protected $casts = [
        'position' => 'integer',
        'is_closed' => 'boolean',
        'raw_json' => 'array',
    ];

    public function integration(): BelongsTo
    {
        return $this->belongsTo(TrelloIntegration::class, 'trello_integration_id');
    }

    public function scopeForSource(Builder $query, string $sourceKey): Builder
    {
        return $query->where('source_key', $sourceKey);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('is_closed', false);
    }

    public function scopeMapped(Builder $query): Builder
    {
        return $query->whereNotNull('normalized_status');
    }

    public function scopeUnmapped(Builder $query): Builder
    {
        return $query->whereNull('normalized_status');
    }

    public function cards(): HasMany
    {
        return $this->hasMany(TrelloCard::class, 'trello_list_record_id');
    }
}