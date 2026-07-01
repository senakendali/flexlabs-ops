<?php

namespace App\Console\Commands;

use App\Services\GoogleAnalytics\GoogleAnalyticsReportService;
use Illuminate\Console\Command;
use Throwable;

class TestGoogleAnalyticsConnection extends Command
{
    protected $signature = 'google-analytics:test {--date-preset=last_7d}';

    protected $description = 'Test Google Analytics GA4 Data API connection and fetch summary metrics.';

    public function handle(): int
    {
        $datePreset = (string) $this->option('date-preset');

        $this->info('Testing Google Analytics GA4 connection...');
        $this->line('Date preset: ' . $datePreset);

        try {
            $service = app(GoogleAnalyticsReportService::class);

            $result = $service->summary($datePreset);

            $metrics = $result['metrics'] ?? [];
            $period = $result['period'] ?? [];

            $this->newLine();
            $this->info('Connection OK.');

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Period Start', $period['date_start'] ?? '-'],
                    ['Period Stop', $period['date_stop'] ?? '-'],
                    ['Active Users', number_format((int) ($metrics['active_users'] ?? 0))],
                    ['New Users', number_format((int) ($metrics['new_users'] ?? 0))],
                    ['Sessions', number_format((int) ($metrics['sessions'] ?? 0))],
                    ['Engaged Sessions', number_format((int) ($metrics['engaged_sessions'] ?? 0))],
                    ['Screen Page Views', number_format((int) ($metrics['screen_page_views'] ?? 0))],
                    ['Event Count', number_format((int) ($metrics['event_count'] ?? 0))],
                    ['Engagement Rate', number_format((float) ($metrics['engagement_rate'] ?? 0), 1) . '%'],
                ]
            );

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Google Analytics test failed.');
            $this->newLine();
            $this->line($e->getMessage());

            return self::FAILURE;
        }
    }
}