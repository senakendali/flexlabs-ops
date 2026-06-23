<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class DebugKommoDailyLeads extends Command
{
    protected $signature = 'kommo:debug-daily-leads {date?}';

    protected $description = 'Debug Kommo daily leads from API and show status breakdown.';

    public function handle(): int
    {
        $timezone = config('app.timezone', 'Asia/Jakarta');
        $date = $this->argument('date') ?: now($timezone)->toDateString();

        $startAt = CarbonImmutable::parse($date, $timezone)->startOfDay()->timestamp;
        $endAt = CarbonImmutable::parse($date, $timezone)->endOfDay()->timestamp;

        $baseUrl = rtrim(
            config('services.kommo.base_url') ?: ('https://' . config('services.kommo.subdomain') . '.kommo.com'),
            '/'
        );

        $pipelineId = (int) config('services.kommo.pipeline_id');
        $token = config('services.kommo.long_lived_token');

        if (! $baseUrl || ! $pipelineId || ! $token) {
            $this->error('Kommo config belum lengkap.');
            return self::FAILURE;
        }

        $this->info('Kommo Daily Leads Debug');
        $this->line('Date: ' . $date);
        $this->line('Timezone: ' . $timezone);
        $this->line('Pipeline ID: ' . $pipelineId);
        $this->line('Start: ' . Carbon::createFromTimestamp($startAt, $timezone)->format('Y-m-d H:i:s'));
        $this->line('End: ' . Carbon::createFromTimestamp($endAt, $timezone)->format('Y-m-d H:i:s'));
        $this->newLine();

        $statusResponse = Http::baseUrl($baseUrl)
            ->withToken($token)
            ->acceptJson()
            ->get("/api/v4/leads/pipelines/{$pipelineId}/statuses");

        if (! $statusResponse->successful()) {
            $this->error('Failed to fetch Kommo statuses.');
            $this->line('Status: ' . $statusResponse->status());
            $this->line($statusResponse->body());

            return self::FAILURE;
        }

        $statuses = collect(data_get($statusResponse->json(), '_embedded.statuses', []))
            ->mapWithKeys(fn ($status) => [
                (int) $status['id'] => $status['name'] ?? 'Unknown',
            ]);

        $response = Http::baseUrl($baseUrl)
            ->withToken($token)
            ->acceptJson()
            ->get('/api/v4/leads', [
                'limit' => 250,
                'filter[pipeline_id]' => $pipelineId,
                'filter[created_at][from]' => $startAt,
                'filter[created_at][to]' => $endAt,
            ]);

        if (! $response->successful()) {
            $this->error('Failed to fetch Kommo leads.');
            $this->line('Status: ' . $response->status());
            $this->line($response->body());

            return self::FAILURE;
        }

        $leads = collect(data_get($response->json(), '_embedded.leads', []))
            ->map(function ($lead) use ($timezone, $statuses, $baseUrl) {
                $statusId = (int) ($lead['status_id'] ?? 0);

                return [
                    'id' => $lead['id'] ?? null,
                    'name' => $lead['name'] ?? null,
                    'status_id' => $statusId,
                    'status_name' => $statuses[$statusId] ?? 'Unknown',
                    'created_at' => isset($lead['created_at'])
                        ? Carbon::createFromTimestamp((int) $lead['created_at'], $timezone)->format('Y-m-d H:i:s')
                        : null,
                    'updated_at' => isset($lead['updated_at'])
                        ? Carbon::createFromTimestamp((int) $lead['updated_at'], $timezone)->format('Y-m-d H:i:s')
                        : null,
                    'closed_at' => ! empty($lead['closed_at'])
                        ? Carbon::createFromTimestamp((int) $lead['closed_at'], $timezone)->format('Y-m-d H:i:s')
                        : '-',
                    'responsible_user_id' => $lead['responsible_user_id'] ?? null,
                    'url' => $baseUrl . '/leads/detail/' . ($lead['id'] ?? ''),
                ];
            })
            ->sortBy('created_at')
            ->values();

        $this->info('API Total Leads: ' . $leads->count());
        $this->newLine();

        $this->info('Status Counts:');
        $statusCounts = $leads
            ->groupBy('status_name')
            ->map(fn ($items) => $items->count())
            ->toArray();

        foreach ($statusCounts as $status => $total) {
            $this->line('- ' . $status . ': ' . $total);
        }

        $this->newLine();

        $this->info('Lead List:');

        $this->table(
            [
                'No',
                'ID',
                'Name',
                'Status',
                'Created At',
                'Updated At',
                'Closed At',
            ],
            $leads->map(function ($lead, $index) {
                return [
                    $index + 1,
                    $lead['id'],
                    $lead['name'],
                    $lead['status_name'],
                    $lead['created_at'],
                    $lead['updated_at'],
                    $lead['closed_at'],
                ];
            })->toArray()
        );

        $this->newLine();
        $this->warn('Kalau Kommo UI cuma 9 tapi API total 10, cek row yang status/tanggalnya beda, duplicate, atau closed/hidden di UI.');

        return self::SUCCESS;
    }
}