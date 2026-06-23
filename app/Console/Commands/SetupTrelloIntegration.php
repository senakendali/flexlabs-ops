<?php

namespace App\Console\Commands;

use App\Models\TrelloIntegration;
use Illuminate\Console\Command;

class SetupTrelloIntegration extends Command
{
    protected $signature = 'trello:setup-integration
        {source : Source key, contoh: academic / marketing}
        {--name= : Nama integration}
        {--department= : Department}
        {--board-id= : Trello Board ID}
        {--board-name= : Trello Board Name}
        {--api-key= : Trello API Key}
        {--api-token= : Trello API Token}
        {--callback-url= : Trello webhook callback URL}
        {--token-owner-name= : Nama pemilik token}
        {--token-owner-email= : Email pemilik token}';

    protected $description = 'Create or update Trello integration data without using Tinker.';

    public function handle(): int
    {
        $source = $this->argument('source');

        $name = $this->option('name') ?: ucfirst($source) . ' Trello';
        $department = $this->option('department') ?: $source;

        $boardId = $this->option('board-id');
        $boardName = $this->option('board-name') ?: $name;

        $apiKey = $this->option('api-key');
        $apiToken = $this->option('api-token');

        $callbackUrl = $this->option('callback-url')
            ?: config('trello.webhook.callback_url');

        if (! $boardId) {
            $this->error('Trello Board ID wajib diisi. Pakai --board-id=');

            return self::FAILURE;
        }

        if (! $apiKey) {
            $this->error('Trello API Key wajib diisi. Pakai --api-key=');

            return self::FAILURE;
        }

        if (! $apiToken) {
            $this->error('Trello API Token wajib diisi. Pakai --api-token=');

            return self::FAILURE;
        }

        if (! $callbackUrl) {
            $this->error('Callback URL wajib diisi. Set TRELLO_WEBHOOK_CALLBACK_URL atau pakai --callback-url=');

            return self::FAILURE;
        }

        $integration = TrelloIntegration::updateOrCreate(
            ['source_key' => $source],
            [
                'name' => $name,
                'department' => $department,

                'trello_board_id' => $boardId,
                'trello_board_name' => $boardName,

                'token_owner_name' => $this->option('token-owner-name'),
                'token_owner_email' => $this->option('token-owner-email'),

                'api_key' => $apiKey,
                'api_token' => $apiToken,

                'callback_url' => $callbackUrl,
                'sync_mode' => 'webhook',
                'status' => 'pending',
                'is_active' => true,

                'last_error' => null,
            ]
        );

        $this->info('Trello integration saved.');
        $this->line('ID          : ' . $integration->id);
        $this->line('Source      : ' . $integration->source_key);
        $this->line('Name        : ' . $integration->name);
        $this->line('Department  : ' . $integration->department);
        $this->line('Board ID    : ' . $integration->trello_board_id);
        $this->line('Board Name  : ' . $integration->trello_board_name);
        $this->line('Callback URL: ' . $integration->callback_url);
        $this->line('Status      : ' . $integration->status);

        return self::SUCCESS;
    }
}