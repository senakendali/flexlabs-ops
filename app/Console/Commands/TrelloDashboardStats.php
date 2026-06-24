<?php

namespace App\Console\Commands;

use App\Services\Trello\TrelloDashboardStatsService;
use Illuminate\Console\Command;

class TrelloDashboardStats extends Command
{
    protected $signature = 'trello:dashboard-stats
        {--source=academic : Source key, contoh: academic / marketing}';

    protected $description = 'Show Trello dashboard statistics from TrelloDashboardStatsService.';

    public function handle(TrelloDashboardStatsService $service): int
    {
        $source = $this->option('source');

        $stats = $service->getStats($source);

        $this->info('Trello Dashboard Stats');
        $this->line('Source      : ' . ($stats['source_key'] ?? '-'));
        $this->line('Board       : ' . ($stats['board_name'] ?? '-'));
        $this->line('Webhook     : ' . ($stats['webhook_status'] ?? '-'));
        $this->line('Last Sync   : ' . optional($stats['last_synced_at'])->format('d M Y H:i'));
        $this->line('Last Webhook: ' . optional($stats['last_webhook_at'])->format('d M Y H:i'));

        $this->newLine();

        $summary = $stats['summary'] ?? [];
        $statuses = $stats['statuses'] ?? [];

        $this->table(
            ['Metric', 'Total'],
            [
                ['Total Open Cards', $summary['total_open_cards'] ?? 0],
                ['Active Work', $summary['active_work'] ?? 0],
                ['Completed', $summary['completed'] ?? 0],
                ['Due Today', $summary['due_today'] ?? 0],
                ['Overdue', $summary['overdue'] ?? 0],
                ['Unmapped', $summary['unmapped'] ?? 0],
                ['Completion Rate', ($summary['completion_rate'] ?? 0) . '%'],
                ['Active Work Rate', ($summary['active_work_rate'] ?? 0) . '%'],
            ]
        );

        $this->newLine();

        $this->table(
            ['Status', 'Total'],
            [
                ['Notes', $statuses['notes'] ?? 0],
                ['To Do', $statuses['todo'] ?? 0],
                ['In Progress', $statuses['in_progress'] ?? 0],
                ['Review', $statuses['review'] ?? 0],
                ['Scheduled', $statuses['scheduled'] ?? 0],
                ['Done', $statuses['done'] ?? 0],
                ['Archived', $statuses['archived'] ?? 0],
                ['Ignored', $statuses['ignored'] ?? 0],
            ]
        );

        $this->newLine();
        $this->warn('Insight:');
        $this->line($stats['insight'] ?? '-');

        $this->newLine();

        $this->warn('Due Today Cards:');
        $this->showCardRows($stats['due_today_cards'] ?? []);

        $this->newLine();

        $this->warn('Overdue Cards:');
        $this->showCardRows($stats['overdue_cards'] ?? []);

        return self::SUCCESS;
    }

    private function showCardRows(array $cards): void
    {
        if (empty($cards)) {
            $this->line('-');

            return;
        }

        $this->table(
            ['Name', 'List', 'Due At', 'URL'],
            collect($cards)
                ->map(fn (array $card) => [
                    mb_strimwidth($card['name'] ?? '-', 0, 50, '...'),
                    $card['list_name'] ?? '-',
                    optional($card['due_at'] ?? null)->format('d M Y H:i') ?: '-',
                    $card['short_url'] ?? $card['url'] ?? '-',
                ])
                ->toArray()
        );
    }
}