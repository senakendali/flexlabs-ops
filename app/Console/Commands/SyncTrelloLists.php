<?php

namespace App\Console\Commands;

use App\Models\TrelloIntegration;
use App\Models\TrelloList;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

class SyncTrelloLists extends Command
{
    protected $signature = 'trello:sync-lists
        {--source= : Sync hanya source_key tertentu, contoh: academic / marketing}';

    protected $description = 'Sync Trello lists from active Trello integrations.';

    public function handle(): int
    {
        $source = $this->option('source');

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
            $this->warn('Tidak ada Trello integration aktif untuk sync list.');

            return self::SUCCESS;
        }

        $this->info('Syncing Trello lists...');
        $this->newLine();

        foreach ($integrations as $integration) {
            $this->line("Source: {$integration->source_key}");
            $this->line("Board : {$integration->trello_board_name} ({$integration->trello_board_id})");

            try {
                $lists = $this->fetchLists($integration);

                if (empty($lists)) {
                    $this->warn('Tidak ada list yang ditemukan.');
                    $this->newLine();

                    continue;
                }

                foreach ($lists as $index => $list) {
                    $trelloList = TrelloList::updateOrCreate(
                        [
                            'trello_integration_id' => $integration->id,
                            'trello_list_id' => $list['id'],
                        ],
                        [
                            'source_key' => $integration->source_key,
                            'trello_board_id' => $integration->trello_board_id,

                            'name' => $list['name'] ?? 'Untitled List',
                            'position' => (int) ($list['pos'] ?? ($index + 1)),
                            'is_closed' => (bool) ($list['closed'] ?? false),

                            // jangan timpa mapping kalau sudah pernah diisi manual
                            'normalized_status' => TrelloList::query()
                                ->where('trello_integration_id', $integration->id)
                                ->where('trello_list_id', $list['id'])
                                ->value('normalized_status'),

                            'raw_json' => $list,
                        ]
                    );

                    $this->line('- ' . $trelloList->name . ' [' . ($trelloList->is_closed ? 'closed' : 'open') . ']');
                }

                $integration->forceFill([
                    'last_synced_at' => now(),
                    'last_error' => null,
                ])->save();

                $this->info("Synced {$integration->source_key}: " . count($lists) . ' lists');
            } catch (Throwable $exception) {
                $integration->markAsError($exception->getMessage());

                $this->error('Failed: ' . $exception->getMessage());
            }

            $this->newLine();
        }

        $this->info('Done.');

        return self::SUCCESS;
    }

    private function fetchLists(TrelloIntegration $integration): array
    {
        $baseUrl = rtrim(config('trello.base_url', 'https://api.trello.com/1'), '/');

        return Http::acceptJson()
            ->timeout(20)
            ->retry(2, 500)
            ->get($baseUrl . '/boards/' . rawurlencode($integration->trello_board_id) . '/lists', [
                'key' => $integration->api_key,
                'token' => $integration->api_token,
                'fields' => 'id,name,closed,pos,idBoard',
                'filter' => 'all',
            ])
            ->throw()
            ->json();
    }
}