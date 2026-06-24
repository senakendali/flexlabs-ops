<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrelloCard extends Model
{
    protected $fillable = [
        'trello_integration_id',
        'trello_list_record_id',

        'source_key',

        'trello_board_id',
        'trello_card_id',
        'trello_list_id',

        'name',
        'description',

        'trello_list_name',
        'normalized_status',

        'url',
        'short_url',

        'due_at',
        'due_complete',

        'is_closed',

        'position',

        'last_activity_at',

        'labels_json',
        'members_json',
        'badges_json',
        'raw_json',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'due_complete' => 'boolean',

        'is_closed' => 'boolean',

        'position' => 'decimal:4',

        'last_activity_at' => 'datetime',

        'labels_json' => 'array',
        'members_json' => 'array',
        'badges_json' => 'array',
        'raw_json' => 'array',
    ];

    public function integration(): BelongsTo
    {
        return $this->belongsTo(TrelloIntegration::class, 'trello_integration_id');
    }

    public function list(): BelongsTo
    {
        return $this->belongsTo(TrelloList::class, 'trello_list_record_id');
    }

    public function scopeForSource(Builder $query, string $sourceKey): Builder
    {
        return $query->where('source_key', $sourceKey);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('is_closed', false);
    }

    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('normalized_status', $status);
    }

    public function scopeDueToday(Builder $query): Builder
    {
        return $query
            ->whereDate('due_at', today())
            ->where('due_complete', false)
            ->where('is_closed', false);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->where('due_complete', false)
            ->where('is_closed', false);
    }
}