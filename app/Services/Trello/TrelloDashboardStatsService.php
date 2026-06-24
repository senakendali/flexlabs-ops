<?php

namespace App\Services\Trello;

use App\Models\TrelloCard;
use App\Models\TrelloIntegration;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TrelloDashboardStatsService
{
    public function getStats(?string $sourceKey = 'academic'): array
    {
        $integration = $this->getIntegration($sourceKey);

        if (! $integration) {
            return $this->emptyStats($sourceKey);
        }

        $baseQuery = $this->baseCardQuery($integration->source_key);

        $totalOpenCards = (clone $baseQuery)->count();

        $notes = $this->countByStatus($baseQuery, 'notes');
        $todo = $this->countByStatus($baseQuery, 'todo');
        $inProgress = $this->countByStatus($baseQuery, 'in_progress');
        $review = $this->countByStatus($baseQuery, 'review');
        $scheduled = $this->countByStatus($baseQuery, 'scheduled');
        $done = $this->countByStatus($baseQuery, 'done');
        $archived = $this->countByStatus($baseQuery, 'archived');
        $ignored = $this->countByStatus($baseQuery, 'ignored');

        $activeWork = (clone $baseQuery)
            ->whereIn('normalized_status', $this->activeStatuses())
            ->count();

        $completed = (clone $baseQuery)
            ->where(function (Builder $query) {
                $query->where('normalized_status', 'done')
                    ->orWhere('due_complete', true);
            })
            ->count();

        $unmapped = (clone $baseQuery)
            ->whereNull('normalized_status')
            ->count();

        $dueToday = $this->dueTodayQuery($baseQuery)->count();
        $overdue = $this->overdueQuery($baseQuery)->count();

        $completionRate = $totalOpenCards > 0
            ? round(($completed / $totalOpenCards) * 100)
            : 0;

        $activeWorkRate = $totalOpenCards > 0
            ? round(($activeWork / $totalOpenCards) * 100)
            : 0;

        return [
            'source_key' => $integration->source_key,
            'integration_name' => $integration->name,
            'department' => $integration->department,
            'board_id' => $integration->trello_board_id,
            'board_name' => $integration->trello_board_name,
            'webhook_status' => $integration->status,
            'last_synced_at' => $integration->last_synced_at,
            'last_webhook_at' => $integration->last_webhook_at,

            'summary' => [
                'total_open_cards' => $totalOpenCards,
                'active_work' => $activeWork,
                'completed' => $completed,
                'due_today' => $dueToday,
                'overdue' => $overdue,
                'unmapped' => $unmapped,
                'completion_rate' => $completionRate,
                'active_work_rate' => $activeWorkRate,
            ],

            'statuses' => [
                'notes' => $notes,
                'todo' => $todo,
                'in_progress' => $inProgress,
                'review' => $review,
                'scheduled' => $scheduled,
                'done' => $done,
                'archived' => $archived,
                'ignored' => $ignored,
            ],

            'due_today_cards' => $this->formatCards(
                $this->dueTodayQuery($baseQuery)
                    ->orderBy('due_at')
                    ->limit(8)
                    ->get()
            ),

            'overdue_cards' => $this->formatCards(
                $this->overdueQuery($baseQuery)
                    ->orderBy('due_at')
                    ->limit(8)
                    ->get()
            ),

            'active_cards' => $this->formatCards(
                (clone $baseQuery)
                    ->whereIn('normalized_status', $this->activeStatuses())
                    ->orderByRaw("FIELD(normalized_status, 'review', 'in_progress', 'todo', 'scheduled')")
                    ->orderByRaw('due_at IS NULL, due_at ASC')
                    ->orderByDesc('last_activity_at')
                    ->get()
            ),

            'recent_cards' => $this->formatCards(
                (clone $baseQuery)
                    ->orderByDesc('last_activity_at')
                    ->limit(10)
                    ->get()
            ),

            'insight' => $this->buildInsight(
                sourceKey: $integration->source_key,
                totalOpenCards: $totalOpenCards,
                activeWork: $activeWork,
                completed: $completed,
                dueToday: $dueToday,
                overdue: $overdue,
                completionRate: $completionRate,
            ),
        ];
    }

    public function getAllActiveStats(): array
    {
        return TrelloIntegration::query()
            ->where('is_active', true)
            ->orderBy('source_key')
            ->get()
            ->map(fn (TrelloIntegration $integration) => $this->getStats($integration->source_key))
            ->values()
            ->toArray();
    }

    private function getIntegration(?string $sourceKey): ?TrelloIntegration
    {
        return TrelloIntegration::query()
            ->where('source_key', $sourceKey)
            ->where('is_active', true)
            ->first();
    }

    private function baseCardQuery(string $sourceKey): Builder
    {
        return TrelloCard::query()
            ->where('source_key', $sourceKey)
            ->where('is_closed', false);
    }

    private function countByStatus(Builder $baseQuery, string $status): int
    {
        return (clone $baseQuery)
            ->where('normalized_status', $status)
            ->count();
    }

    private function dueTodayQuery(Builder $baseQuery): Builder
    {
        [$startUtc, $endUtc] = $this->todayUtcRangeFromDashboardTimezone();

        return (clone $baseQuery)
            ->whereNotNull('due_at')
            ->whereBetween('due_at', [
                $startUtc->toDateTimeString(),
                $endUtc->toDateTimeString(),
            ])
            ->where('due_complete', false)
            ->whereNotIn('normalized_status', $this->excludedStatuses());
    }

    private function overdueQuery(Builder $baseQuery): Builder
    {
        $nowUtc = $this->nowUtcFromDashboardTimezone();

        return (clone $baseQuery)
            ->whereNotNull('due_at')
            ->where('due_at', '<', $nowUtc->toDateTimeString())
            ->where('due_complete', false)
            ->whereNotIn('normalized_status', $this->excludedStatuses());
    }

    private function activeStatuses(): array
    {
        return [
            'todo',
            'in_progress',
            'review',
            'scheduled',
        ];
    }

    private function excludedStatuses(): array
    {
        return [
            'done',
            'archived',
            'ignored',
        ];
    }

    private function formatCards(Collection $cards): array
    {
        return $cards
            ->map(function (TrelloCard $card) {
                return [
                    'id' => $card->id,
                    'trello_card_id' => $card->trello_card_id,
                    'name' => $card->name,
                    'description' => $card->description,
                    'source_key' => $card->source_key,

                    'list_id' => $card->trello_list_id,
                    'list_name' => $card->trello_list_name,
                    'normalized_status' => $card->normalized_status,

                    /*
                    |--------------------------------------------------------------------------
                    | Local Dashboard Time
                    |--------------------------------------------------------------------------
                    |
                    | Trello due_at disimpan sebagai UTC dari API.
                    | Dashboard perlu baca sebagai timezone app supaya deadline 17:00 WIB
                    | tidak dianggap overdue sebelum jam 17:00 WIB.
                    |--------------------------------------------------------------------------
                    */
                    'due_at' => $this->trelloUtcDateToLocal($card->getRawOriginal('due_at')),
                    'due_complete' => $card->due_complete,
                    'last_activity_at' => $this->trelloUtcDateToLocal($card->getRawOriginal('last_activity_at')),

                    'url' => $card->url,
                    'short_url' => $card->short_url,

                    'labels' => collect($card->labels_json ?: [])
                        ->map(fn ($label) => [
                            'id' => $label['id'] ?? null,
                            'name' => $label['name'] ?? null,
                            'color' => $label['color'] ?? null,
                        ])
                        ->values()
                        ->toArray(),

                    'members' => collect($card->members_json ?: [])
                        ->map(fn ($member) => $this->formatMember($member))
                        ->values()
                        ->toArray(),
                ];
            })
            ->values()
            ->toArray();
    }

    private function formatMember(array $member): array
    {
        $id = $member['id'] ?? null;

        $name = trim((string) (
            $member['fullName']
            ?? $member['name']
            ?? $member['username']
            ?? ''
        ));

        if ($name === '') {
            $name = 'Unassigned';
        }

        $username = $member['username'] ?? null;

        $initials = trim((string) ($member['initials'] ?? ''));

        if ($initials === '') {
            $initials = $this->makeInitials($name);
        }

        $avatarHash = $member['avatarHash']
            ?? $member['avatar_hash']
            ?? null;

        $avatarUrl = null;

        if ($id && $avatarHash) {
            $avatarUrl = 'https://trello-members.s3.amazonaws.com/'
                . rawurlencode($id)
                . '/'
                . rawurlencode($avatarHash)
                . '/50.png';
        }

        return [
            'id' => $id,
            'name' => $name,
            'username' => $username,
            'initials' => $initials,
            'avatar_hash' => $avatarHash,
            'avatar_url' => $avatarUrl,
            'has_avatar' => filled($avatarUrl),
        ];
    }

    private function makeInitials(?string $name): string
    {
        $name = trim((string) $name);

        if ($name === '') {
            return '?';
        }

        $words = preg_split('/\s+/', $name);

        if (! $words || count($words) <= 0) {
            return strtoupper(mb_substr($name, 0, 1));
        }

        $first = mb_substr($words[0], 0, 1);
        $second = count($words) > 1
            ? mb_substr($words[1], 0, 1)
            : '';

        return strtoupper($first . $second);
    }

    private function dashboardTimezone(): string
    {
        return config('app.timezone', 'Asia/Jakarta');
    }

    private function nowUtcFromDashboardTimezone(): Carbon
    {
        return now($this->dashboardTimezone())->utc();
    }

    private function todayUtcRangeFromDashboardTimezone(): array
    {
        $timezone = $this->dashboardTimezone();

        $startUtc = now($timezone)
            ->startOfDay()
            ->utc();

        $endUtc = now($timezone)
            ->endOfDay()
            ->utc();

        return [$startUtc, $endUtc];
    }

    private function trelloUtcDateToLocal(mixed $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        return Carbon::parse($value, 'UTC')
            ->timezone($this->dashboardTimezone());
    }

    private function buildInsight(
        string $sourceKey,
        int $totalOpenCards,
        int $activeWork,
        int $completed,
        int $dueToday,
        int $overdue,
        int $completionRate,
    ): string {
        $departmentLabel = match ($sourceKey) {
            'academic' => 'Academic',
            'marketing' => 'Marketing',
            default => ucfirst($sourceKey),
        };

        if ($totalOpenCards <= 0) {
            return "{$departmentLabel} belum memiliki card aktif yang tersinkron dari Trello.";
        }

        if ($overdue > 0) {
            return "{$departmentLabel} memiliki {$totalOpenCards} card terbuka dengan {$activeWork} pekerjaan aktif. Ada {$overdue} task overdue dan {$dueToday} task due today, jadi prioritas utama adalah menyelesaikan pekerjaan yang sudah melewati deadline.";
        }

        if ($dueToday > 0) {
            return "{$departmentLabel} memiliki {$totalOpenCards} card terbuka dengan {$activeWork} pekerjaan aktif. Ada {$dueToday} task due today dan belum ada task overdue, jadi fokus hari ini adalah menyelesaikan deadline berjalan.";
        }

        if ($activeWork > 0) {
            return "{$departmentLabel} memiliki {$totalOpenCards} card terbuka dengan {$activeWork} pekerjaan aktif. Completion rate saat ini {$completionRate}%, dan tidak ada task overdue yang perlu dikejar hari ini.";
        }

        return "{$departmentLabel} memiliki {$totalOpenCards} card terbuka, mayoritas sudah berada di status selesai atau konteks. Completion rate saat ini {$completionRate}%.";
    }

    private function emptyStats(?string $sourceKey): array
    {
        return [
            'source_key' => $sourceKey,
            'integration_name' => null,
            'department' => null,
            'board_id' => null,
            'board_name' => null,
            'webhook_status' => 'inactive',
            'last_synced_at' => null,
            'last_webhook_at' => null,

            'summary' => [
                'total_open_cards' => 0,
                'active_work' => 0,
                'completed' => 0,
                'due_today' => 0,
                'overdue' => 0,
                'unmapped' => 0,
                'completion_rate' => 0,
                'active_work_rate' => 0,
            ],

            'statuses' => [
                'notes' => 0,
                'todo' => 0,
                'in_progress' => 0,
                'review' => 0,
                'scheduled' => 0,
                'done' => 0,
                'archived' => 0,
                'ignored' => 0,
            ],

            'due_today_cards' => [],
            'overdue_cards' => [],
            'active_cards' => [],
            'recent_cards' => [],

            'insight' => 'Trello integration belum aktif atau belum ditemukan.',
        ];
    }
}