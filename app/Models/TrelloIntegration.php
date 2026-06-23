<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrelloIntegration extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'source_key',
        'name',
        'department',

        'trello_workspace_id',
        'trello_workspace_name',

        'trello_board_id',
        'trello_board_name',

        'token_owner_name',
        'token_owner_email',

        'api_key',
        'api_token',

        'webhook_id',
        'callback_url',

        'sync_mode',
        'status',
        'is_active',

        'last_registered_at',
        'last_webhook_at',
        'last_synced_at',
        'last_error',

        'settings',
        'raw_payload',
    ];

    protected $casts = [
        'api_key' => 'encrypted:string',
        'api_token' => 'encrypted:string',

        'is_active' => 'boolean',

        'last_registered_at' => 'datetime',
        'last_webhook_at' => 'datetime',
        'last_synced_at' => 'datetime',

        'settings' => 'array',
        'raw_payload' => 'array',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('status', 'active');
    }

    public function scopeForSource(Builder $query, string $sourceKey): Builder
    {
        return $query->where('source_key', $sourceKey);
    }

    public function scopeForDepartment(Builder $query, string $department): Builder
    {
        return $query->where('department', $department);
    }

    public function markAsActive(?string $webhookId = null): void
    {
        $this->forceFill([
            'webhook_id' => $webhookId ?: $this->webhook_id,
            'status' => 'active',
            'is_active' => true,
            'last_error' => null,
            'last_registered_at' => now(),
        ])->save();
    }

    public function markWebhookReceived(?array $payload = null): void
    {
        $this->forceFill([
            'last_webhook_at' => now(),
            'raw_payload' => $payload ?: $this->raw_payload,
        ])->save();
    }

    public function markSynced(): void
    {
        $this->forceFill([
            'last_synced_at' => now(),
            'last_error' => null,
        ])->save();
    }

    public function markAsError(string $message): void
    {
        $this->forceFill([
            'status' => 'error',
            'last_error' => $message,
        ])->save();
    }
}