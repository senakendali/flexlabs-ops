<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrelloWebhookEvent extends Model
{
    protected $fillable = [
        'trello_integration_id',
        'source_key',

        'trello_board_id',
        'trello_action_id',
        'trello_action_type',

        'trello_card_id',
        'trello_card_name',

        'trello_list_id',
        'trello_list_name',

        'trello_member_creator_id',
        'trello_member_creator_name',
        'trello_member_creator_username',

        'happened_at',
        'received_at',

        'processing_status',
        'processed_at',
        'processing_error',

        'headers_json',
        'payload_json',
    ];

    protected $casts = [
        'happened_at' => 'datetime',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',

        'headers_json' => 'array',
        'payload_json' => 'array',
    ];

    public function integration(): BelongsTo
    {
        return $this->belongsTo(TrelloIntegration::class, 'trello_integration_id');
    }

    public function markAsProcessed(): void
    {
        $this->forceFill([
            'processing_status' => 'processed',
            'processed_at' => now(),
            'processing_error' => null,
        ])->save();
    }

    public function markAsIgnored(?string $reason = null): void
    {
        $this->forceFill([
            'processing_status' => 'ignored',
            'processed_at' => now(),
            'processing_error' => $reason,
        ])->save();
    }

    public function markAsFailed(string $message): void
    {
        $this->forceFill([
            'processing_status' => 'failed',
            'processing_error' => $message,
        ])->save();
    }
}