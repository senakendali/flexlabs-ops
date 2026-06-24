<?php

namespace App\Console\Commands;

use App\Models\TrelloCard;
use App\Models\TrelloIntegration;
use App\Models\TrelloList;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

class SyncTrelloCards extends Command
{
    protected $signature = 'trello:sync-cards
        {--source= : Sync hanya source_key tertentu, contoh: academic / marketing}
        {--include-closed : Ikut tarik archived/closed card}';

    protected $description = 'Sync Trello cards from active Trello integrations.';

    public function handle(): int
    {
        $source = $this->option('source');
        $includeClosed = (bool) $this->option('include-closed');

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
            $this->warn('Tidak ada Trello integration aktif untuk sync card.');

            return self::SUCCESS;
        }

        $this->info('Syncing Trello cards...');
        $this->newLine();

        foreach ($integrations as $integration) {
            $this->line("Source: {$integration->source_key}");
            $this->line("Board : {$integration->trello_board_name} ({$integration->trello_board_id})");

            try {
                $cards = $this->fetchCards($integration, $includeClosed);

                $fetchedCardIds = collect($cards)
                    ->pluck('id')
                    ->filter()
                    ->values()
                    ->all();

                if (empty($cards)) {
                    $this->warn('Tidak ada card yang ditemukan.');

                    $closedMissing = 0;

                    if (! $includeClosed) {
                        $closedMissing = $this->markMissingOpenCardsAsClosed(
                            integration: $integration,
                            fetchedCardIds: []
                        );
                    }

                    if ($closedMissing > 0) {
                        $this->warn("Marked {$closedMissing} local cards as closed karena sudah tidak ada di open cards Trello.");
                    }

                    $integration->forceFill([
                        'last_synced_at' => now(),
                        'last_error' => null,
                    ])->save();

                    $this->newLine();

                    continue;
                }

                $synced = 0;
                $unmapped = 0;

                foreach ($cards as $card) {
                    $listId = $card['idList'] ?? null;

                    $list = null;

                    if ($listId) {
                        $list = TrelloList::query()
                            ->where('trello_integration_id', $integration->id)
                            ->where('trello_list_id', $listId)
                            ->first();
                    }

                    if (! $list) {
                        $unmapped++;
                    }

                    TrelloCard::updateOrCreate(
                        [
                            'trello_integration_id' => $integration->id,
                            'trello_card_id' => $card['id'],
                        ],
                        [
                            'trello_list_record_id' => $list?->id,

                            'source_key' => $integration->source_key,

                            'trello_board_id' => $integration->trello_board_id,
                            'trello_list_id' => $listId,

                            'name' => $card['name'] ?? 'Untitled Card',
                            'description' => $card['desc'] ?? null,

                            'trello_list_name' => $list?->name,
                            'normalized_status' => $list?->normalized_status,

                            'url' => $card['url'] ?? null,
                            'short_url' => $card['shortUrl'] ?? null,

                            'due_at' => $card['due'] ?? null,
                            'due_complete' => (bool) ($card['dueComplete'] ?? false),

                            'is_closed' => (bool) ($card['closed'] ?? false),

                            'position' => $card['pos'] ?? null,

                            'last_activity_at' => $card['dateLastActivity'] ?? null,

                            'labels_json' => $card['labels'] ?? [],
                            'members_json' => $card['members'] ?? [],
                            'badges_json' => $card['badges'] ?? [],
                            'raw_json' => $card,
                        ]
                    );

                    $synced++;
                }

                $closedMissing = 0;

                /*
                |--------------------------------------------------------------------------
                | Close local stale cards
                |--------------------------------------------------------------------------
                |
                | Saat sync normal, Trello API pakai filter=open. Kalau card dihapus /
                | di-archive di Trello, card itu tidak muncul lagi di response API.
                | Tanpa cleanup ini, row lama di trello_cards tetap is_closed=false
                | dan dashboard masih menghitung task tersebut.
                |
                | Untuk --include-closed, cleanup ini sengaja dilewati karena response
                | Trello berisi open + closed cards.
                |
                */
                if (! $includeClosed) {
                    $closedMissing = $this->markMissingOpenCardsAsClosed(
                        integration: $integration,
                        fetchedCardIds: $fetchedCardIds
                    );
                }

                $integration->forceFill([
                    'last_synced_at' => now(),
                    'last_error' => null,
                ])->save();

                $this->info("Synced {$integration->source_key}: {$synced} cards");

                if ($closedMissing > 0) {
                    $this->warn("Marked {$closedMissing} local cards as closed karena sudah tidak ada di open cards Trello.");
                }

                if ($unmapped > 0) {
                    $this->warn("Ada {$unmapped} card yang list-nya belum kebaca/mapped. Jalankan trello:sync-lists lagi kalau perlu.");
                }
            } catch (Throwable $exception) {
                $integration->markAsError($exception->getMessage());

                $this->error('Failed: ' . $exception->getMessage());
            }

            $this->newLine();
        }

        $this->info('Done.');

        return self::SUCCESS;
    }

    private function fetchCards(TrelloIntegration $integration, bool $includeClosed = false): array
    {
        $baseUrl = rtrim(config('trello.base_url', 'https://api.trello.com/1'), '/');

        return Http::acceptJson()
            ->timeout(30)
            ->retry(2, 500)
            ->get($baseUrl . '/boards/' . rawurlencode($integration->trello_board_id) . '/cards', [
                'key' => $integration->api_key,
                'token' => $integration->api_token,

                'filter' => $includeClosed ? 'all' : 'open',

                'fields' => implode(',', [
                    'id',
                    'idBoard',
                    'idList',
                    'name',
                    'desc',
                    'closed',
                    'pos',
                    'due',
                    'dueComplete',
                    'dateLastActivity',
                    'url',
                    'shortUrl',
                    'badges',
                ]),

                'members' => 'true',
                'member_fields' => 'id,fullName,username,initials,avatarHash,avatarUrl',

                'labels' => 'true',
                'label_fields' => 'id,name,color',
            ])
            ->throw()
            ->json();
    }

    private function markMissingOpenCardsAsClosed(TrelloIntegration $integration, array $fetchedCardIds): int
    {
        $query = TrelloCard::query()
            ->where('trello_integration_id', $integration->id)
            ->where('source_key', $integration->source_key)
            ->where('trello_board_id', $integration->trello_board_id)
            ->where('is_closed', false);

        if (! empty($fetchedCardIds)) {
            $query->whereNotIn('trello_card_id', $fetchedCardIds);
        }

        return (int) $query->update([
            'is_closed' => true,
            'updated_at' => now(),
        ]);
    }
}