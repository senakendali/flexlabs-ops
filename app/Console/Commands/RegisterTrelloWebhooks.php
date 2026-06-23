<?php

namespace App\Console\Commands;

use App\Models\TrelloIntegration;
use App\Services\Trello\TrelloWebhookRegistrar;
use Illuminate\Console\Command;
use Throwable;

class RegisterTrelloWebhooks extends Command
{
    protected $signature = 'trello:register-webhooks
        {--source= : Register hanya untuk source_key tertentu, contoh: marketing / academic}
        {--force : Paksa create webhook baru walaupun webhook_id sudah ada}';

    protected $description = 'Register Trello webhooks from trello_integrations table.';

    public function handle(TrelloWebhookRegistrar $registrar): int
    {
        $source = $this->option('source');
        $force = (bool) $this->option('force');

        $query = TrelloIntegration::query()
            ->where('is_active', true)
            ->whereNotNull('trello_board_id')
            ->whereNotNull('api_key')
            ->whereNotNull('api_token');

        if ($source) {
            $query->where('source_key', $source);
        }

        $integrations = $query->get();

        if ($integrations->isEmpty()) {
            $this->warn('Tidak ada Trello integration yang bisa diregister.');

            return self::SUCCESS;
        }

        $this->info('Registering Trello webhooks...');
        $this->newLine();

        foreach ($integrations as $integration) {
            $this->line("Source: {$integration->source_key}");
            $this->line("Board : {$integration->trello_board_name} ({$integration->trello_board_id})");

            try {
                $result = $registrar->register(
                    integration: $integration,
                    forceCreate: $force
                );

                $webhook = $result['webhook'] ?? [];
                $mode = $result['mode'] ?? 'registered';

                $this->info("Status: {$mode}");
                $this->line('Webhook ID: ' . ($webhook['id'] ?? '-'));
                $this->line('Callback  : ' . ($webhook['callbackURL'] ?? $integration->callback_url ?? '-'));
            } catch (Throwable $exception) {
                $integration->markAsError($exception->getMessage());

                $this->error('Failed: ' . $exception->getMessage());
            }

            $this->newLine();
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}