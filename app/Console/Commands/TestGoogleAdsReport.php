<?php

namespace App\Console\Commands;

use App\Services\GoogleAds\GoogleAdsReportService;
use Illuminate\Console\Command;
use Throwable;

class TestGoogleAdsReport extends Command
{
    protected $signature = 'google-ads:test-report
        {--date-preset=last_7d : Date preset: today, yesterday, last_7d, last_30d, last_90d}
        {--limit=10 : Number of campaigns to fetch}';

    protected $description = 'Test Google Ads campaign performance report.';

    public function handle(GoogleAdsReportService $googleAdsReportService): int
    {
        $datePreset = (string) $this->option('date-preset');
        $limit = (int) $this->option('limit');

        $this->info('Testing Google Ads campaign performance report...');
        $this->line('Date preset: ' . $datePreset);
        $this->line('Limit: ' . $limit);

        try {
            $insight = $googleAdsReportService->dashboardInsight(
                datePreset: $datePreset,
                limit: $limit
            );

            $overview = $insight['overview'] ?? [];
            $period = $insight['period'] ?? [];
            $campaigns = $insight['campaigns'] ?? [];

            $this->newLine();
            $this->info('Google Ads report OK.');

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Customer ID', $insight['customer_id'] ?? '-'],
                    ['Login Customer ID', $insight['login_customer_id'] ?? '-'],
                    ['Period', ($period['date_start'] ?? '-') . ' — ' . ($period['date_stop'] ?? '-')],
                    ['Campaign Count', number_format((int) ($overview['campaign_count'] ?? 0))],
                    ['Enabled Campaigns', number_format((int) ($overview['enabled_campaign_count'] ?? 0))],
                    ['Paused Campaigns', number_format((int) ($overview['paused_campaign_count'] ?? 0))],
                    ['Total Cost', $overview['total_cost_label'] ?? 'Rp 0'],
                    ['Impressions', number_format((int) ($overview['total_impressions'] ?? 0))],
                    ['Clicks', number_format((int) ($overview['total_clicks'] ?? 0))],
                    ['CTR', number_format((float) ($overview['ctr'] ?? 0), 2) . '%'],
                    ['Avg CPC', $overview['average_cpc_label'] ?? 'Rp 0'],
                    ['Conversions', number_format((float) ($overview['total_conversions'] ?? 0), 2)],
                    ['Cost / Conversion', $overview['cost_per_conversion_label'] ?? '-'],
                    ['Conversion Rate', number_format((float) ($overview['conversion_rate'] ?? 0), 2) . '%'],
                    ['ROAS', number_format((float) ($overview['roas'] ?? 0), 2) . 'x'],
                ]
            );

            $summaryText = trim((string) ($overview['summary_text'] ?? ''));

            if ($summaryText !== '') {
                $this->newLine();
                $this->warn('Summary:');
                $this->line($summaryText);
            }

            if (! empty($campaigns)) {
                $this->newLine();

                $this->table(
                    [
                        'Campaign',
                        'Status',
                        'Type',
                        'Cost',
                        'Imp.',
                        'Clicks',
                        'CTR',
                        'Avg CPC',
                        'Conv.',
                        'Cost/Conv',
                        'Health',
                    ],
                    collect($campaigns)
                        ->map(fn (array $campaign) => [
                            $campaign['campaign_name'] ?? '-',
                            $campaign['status_label'] ?? '-',
                            $campaign['advertising_channel_label'] ?? '-',
                            $campaign['cost_label'] ?? 'Rp 0',
                            number_format((int) ($campaign['impressions'] ?? 0)),
                            number_format((int) ($campaign['clicks'] ?? 0)),
                            number_format((float) ($campaign['ctr'] ?? 0), 2) . '%',
                            $campaign['average_cpc_label'] ?? 'Rp 0',
                            number_format((float) ($campaign['conversions'] ?? 0), 2),
                            $campaign['cost_per_conversion_label'] ?? '-',
                            $campaign['health_label'] ?? '-',
                        ])
                        ->all()
                );
            } else {
                $this->newLine();
                $this->warn('No campaign performance data found for this period.');
            }

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Google Ads report test failed.');
            $this->newLine();
            $this->line($e->getMessage());

            return self::FAILURE;
        }
    }
}