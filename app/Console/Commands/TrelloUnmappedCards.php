<?php

namespace App\Console\Commands;

use App\Models\TrelloCard;
use Illuminate\Console\Command;

class TrelloUnmappedCards extends Command
{
    protected $signature = 'trello:unmapped-cards
        {--source=academic : Source key, contoh: academic / marketing}';

    protected $description = 'Show Trello cards without normalized status.';

    public function handle(): int
    {
        $source = $this->option('source');

        $cards = TrelloCard::query()
            ->with('list')
            ->where('source_key', $source)
            ->where('is_closed', false)
            ->whereNull('normalized_status')
            ->orderBy('trello_list_name')
            ->orderBy('name')
            ->get();

        if ($cards->isEmpty()) {
            $this->info("Tidak ada unmapped cards untuk source: {$source}");

            return self::SUCCESS;
        }

        $this->warn("Unmapped cards for source: {$source}");
        $this->newLine();

        $this->table(
            ['Card ID', 'Card Name', 'List ID', 'List Name', 'List Closed', 'URL'],
            $cards->map(function (TrelloCard $card) {
                return [
                    $card->trello_card_id,
                    mb_strimwidth($card->name, 0, 50, '...'),
                    $card->trello_list_id ?: '-',
                    $card->trello_list_name ?: '-',
                    $card->list?->is_closed ? 'yes' : 'no',
                    $card->short_url ?: $card->url ?: '-',
                ];
            })->toArray()
        );

        $this->newLine();

        $grouped = $cards
            ->groupBy(fn (TrelloCard $card) => $card->trello_list_name ?: 'Unknown List')
            ->map(fn ($items) => $items->count());

        $this->info('Summary by list:');

        $this->table(
            ['List Name', 'Total'],
            $grouped->map(fn ($total, $listName) => [
                $listName,
                $total,
            ])->values()->toArray()
        );

        return self::SUCCESS;
    }
}