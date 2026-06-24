<?php

namespace App\Console\Commands;

use App\Models\TrelloList;
use Illuminate\Console\Command;

class MapTrelloList extends Command
{
    protected $signature = 'trello:map-list
        {source : Source key, contoh: academic / marketing}
        {--list-id= : Trello List ID}
        {--name= : Trello List Name}
        {--status= : Normalized status}
        {--clear : Kosongkan mapping status untuk list ini}';

    protected $description = 'Map Trello list to normalized internal dashboard status.';

    private array $allowedStatuses = [
        'notes',
        'todo',
        'in_progress',
        'review',
        'scheduled',
        'done',
        'ignored',
        'archived',
    ];

    public function handle(): int
    {
        $source = $this->argument('source');
        $listId = $this->option('list-id');
        $name = $this->option('name');
        $status = $this->option('status');
        $clear = (bool) $this->option('clear');

        if (! $listId && ! $name) {
            return $this->showLists($source);
        }

        if (! $clear && ! $status) {
            $this->error('Status wajib diisi. Pakai --status=todo / in_progress / done, atau pakai --clear.');

            return self::FAILURE;
        }

        if ($status && ! in_array($status, $this->allowedStatuses, true)) {
            $this->error('Status tidak valid: ' . $status);
            $this->line('Allowed statuses: ' . implode(', ', $this->allowedStatuses));

            return self::FAILURE;
        }

        $list = $this->findList($source, $listId, $name);

        if (! $list) {
            $this->error('Trello list tidak ditemukan.');

            return self::FAILURE;
        }

        $list->forceFill([
            'normalized_status' => $clear ? null : $status,
        ])->save();

        $this->info('Trello list mapping updated.');
        $this->line('Source      : ' . $list->source_key);
        $this->line('List ID     : ' . $list->trello_list_id);
        $this->line('List Name   : ' . $list->name);
        $this->line('Status      : ' . ($list->normalized_status ?: '-'));

        return self::SUCCESS;
    }

    private function showLists(string $source): int
    {
        $lists = TrelloList::query()
            ->where('source_key', $source)
            ->orderBy('position')
            ->get();

        if ($lists->isEmpty()) {
            $this->warn("Belum ada Trello list untuk source: {$source}");
            $this->line("Jalankan dulu: php artisan trello:sync-lists --source={$source}");

            return self::SUCCESS;
        }

        $this->info("Trello lists for source: {$source}");
        $this->newLine();

        $this->table(
            ['ID', 'Trello List ID', 'Name', 'Closed', 'Normalized Status'],
            $lists->map(fn (TrelloList $list) => [
                $list->id,
                $list->trello_list_id,
                $list->name,
                $list->is_closed ? 'yes' : 'no',
                $list->normalized_status ?: '-',
            ])->toArray()
        );

        $this->newLine();
        $this->line('Allowed statuses: ' . implode(', ', $this->allowedStatuses));

        return self::SUCCESS;
    }

    private function findList(string $source, ?string $listId, ?string $name): ?TrelloList
    {
        $query = TrelloList::query()
            ->where('source_key', $source);

        if ($listId) {
            return $query
                ->where('trello_list_id', $listId)
                ->first();
        }

        if ($name) {
            return $query
                ->get()
                ->first(function (TrelloList $list) use ($name) {
                    return mb_strtolower(trim($list->name)) === mb_strtolower(trim($name));
                });
        }

        return null;
    }
}