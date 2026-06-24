<?php

namespace App\Console\Commands;

use App\Models\TrelloCard;
use Illuminate\Console\Command;

class TrelloStats extends Command
{
    protected $signature = 'trello:stats
        {--source=academic : Source key, contoh: academic / marketing}';

    protected $description = 'Show Trello card statistics by source.';

    public function handle(): int
    {
        $source = $this->option('source');

        $baseQuery = TrelloCard::query()
            ->where('source_key', $source)
            ->where('is_closed', false);

        $total = (clone $baseQuery)->count();

        $notes = (clone $baseQuery)->where('normalized_status', 'notes')->count();
        $todo = (clone $baseQuery)->where('normalized_status', 'todo')->count();
        $inProgress = (clone $baseQuery)->where('normalized_status', 'in_progress')->count();
        $review = (clone $baseQuery)->where('normalized_status', 'review')->count();
        $scheduled = (clone $baseQuery)->where('normalized_status', 'scheduled')->count();

        $done = (clone $baseQuery)->where('normalized_status', 'done')->count();
        $archived = (clone $baseQuery)->where('normalized_status', 'archived')->count();
        $ignored = (clone $baseQuery)->where('normalized_status', 'ignored')->count();

        $unmapped = (clone $baseQuery)
            ->whereNull('normalized_status')
            ->count();

        $dueToday = (clone $baseQuery)
            ->whereDate('due_at', today())
            ->where('due_complete', false)
            ->whereNotIn('normalized_status', ['done', 'archived', 'ignored'])
            ->count();

        $overdue = (clone $baseQuery)
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->where('due_complete', false)
            ->whereNotIn('normalized_status', ['done', 'archived', 'ignored'])
            ->count();

        $completed = (clone $baseQuery)
            ->where(function ($query) {
                $query->where('normalized_status', 'done')
                    ->orWhere('due_complete', true);
            })
            ->count();

        $activeWork = (clone $baseQuery)
            ->whereIn('normalized_status', [
                'todo',
                'in_progress',
                'review',
                'scheduled',
            ])
            ->count();

        $pendingContext = (clone $baseQuery)
            ->where('normalized_status', 'notes')
            ->count();

        $excluded = $archived + $ignored;

        $completionRate = $total > 0
            ? round(($completed / $total) * 100)
            : 0;

        $activeWorkRate = $total > 0
            ? round(($activeWork / $total) * 100)
            : 0;

        $this->info("Trello Stats: {$source}");
        $this->newLine();

        $this->table(
            ['Metric', 'Total'],
            [
                ['Total Open Cards', $total],
                ['Notes / Context', $notes],
                ['To Do', $todo],
                ['In Progress', $inProgress],
                ['Review', $review],
                ['Scheduled', $scheduled],
                ['Done', $done],
                ['Archived', $archived],
                ['Ignored', $ignored],
                ['Active Work', $activeWork],
                ['Pending Context', $pendingContext],
                ['Due Today', $dueToday],
                ['Overdue', $overdue],
                ['Unmapped', $unmapped],
                ['Excluded', $excluded],
                ['Completion Rate', $completionRate . '%'],
                ['Active Work Rate', $activeWorkRate . '%'],
            ]
        );

        return self::SUCCESS;
    }
}