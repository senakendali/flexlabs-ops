<?php

namespace App\Console\Commands;

use App\Models\GoogleAdsDashboardSnapshot;
use App\Services\GoogleAds\GoogleAdsAiInsightService;
use App\Services\GoogleAds\GoogleAdsReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncGoogleAdsDashboardSnapshot extends Command
{
    protected $signature = 'google-ads:sync-dashboard
        {--date-preset= : Date preset: today, yesterday, last_7d, last_30d, last_90d}
        {--limit=20 : Number of campaigns to fetch}
        {--with-ai : Generate AI analysis for Google Ads dashboard snapshot}';

    protected $description = 'Sync Google Ads dashboard insight and daily metrics into local snapshot table.';

    public function handle(
        GoogleAdsReportService $googleAdsReportService,
        GoogleAdsAiInsightService $googleAdsAiInsightService
    ): int {
        $datePreset = (string) (
            $this->option('date-preset')
            ?: config('services.google_ads.default_date_preset', 'last_7d')
        );

        $limit = max((int) $this->option('limit'), 1);
        $withAi = (bool) $this->option('with-ai');

        $customerId = preg_replace(
            '/\D+/',
            '',
            (string) config('services.google_ads.customer_id')
        );
        $loginCustomerId = preg_replace(
            '/\D+/',
            '',
            (string) config('services.google_ads.login_customer_id')
        );

        $this->info('Syncing Google Ads dashboard snapshot...');
        $this->line('Customer ID: ' . ($customerId ?: '-'));
        $this->line('Login Customer ID: ' . ($loginCustomerId ?: '-'));
        $this->line('Date preset: ' . $datePreset);
        $this->line('Campaign limit: ' . $limit);
        $this->line('AI analysis: ' . ($withAi ? 'yes' : 'no'));

        try {
            $existingSnapshot = GoogleAdsDashboardSnapshot::query()
                ->where('customer_id', $customerId)
                ->where('date_preset', $datePreset)
                ->first();

            /*
             * Tetap buat snapshot rolling untuk dashboard Google Ads.
             * Snapshot ini memuat detail campaign dan dapat memiliki AI summary.
             */
            $insight = $googleAdsReportService->dashboardInsight(
                datePreset: $datePreset,
                limit: $limit
            );

            /*
             * Ambil metrik akun per hari melalui query terpisah.
             * Daily snapshot tidak menjalankan AI dan hanya dipakai untuk
             * agregasi KPI lintas rentang tanggal.
             */
            $dailyInsights = $googleAdsReportService->dailyDashboardInsights(
                datePreset: $datePreset
            );

            $localSummaryText = $insight['summary_text'] ?? null;
            $aiSummary = null;

            if ($withAi) {
                $this->newLine();
                $this->info('Generating Google Ads AI analysis...');

                $aiSummary = $googleAdsAiInsightService->generate($insight);

                $insight['local_summary_text'] = $localSummaryText;
                $insight['ai_summary'] = $aiSummary;
                $insight['summary_text'] = $aiSummary['summary']
                    ?? $localSummaryText;

                $this->info('AI analysis generated.');
            } elseif (
                $existingSnapshot
                && is_array($existingSnapshot->ai_payload)
            ) {
                $aiSummary = $existingSnapshot->ai_payload;

                $insight['local_summary_text'] = $localSummaryText;
                $insight['ai_summary'] = $aiSummary;
                $insight['summary_text'] = $existingSnapshot->ai_summary_text
                    ?: $localSummaryText;
            }

            $data = $this->buildSnapshotData(
                insight: $insight,
                loginCustomerId: $loginCustomerId ?: null
            );

            if ($withAi) {
                $data['ai_summary_text'] = $aiSummary['summary'] ?? null;
                $data['ai_payload'] = $aiSummary;
                $data['ai_model'] = $aiSummary['model']
                    ?? config('services.google_ads.ai_model');
                $data['ai_generated_at'] = now();
            } elseif (
                $existingSnapshot
                && is_array($existingSnapshot->ai_payload)
            ) {
                $data['ai_summary_text'] = $existingSnapshot->ai_summary_text;
                $data['ai_payload'] = $existingSnapshot->ai_payload;
                $data['ai_model'] = $existingSnapshot->ai_model;
                $data['ai_generated_at'] = $existingSnapshot->ai_generated_at;
            }

            $snapshot = GoogleAdsDashboardSnapshot::query()->updateOrCreate(
                [
                    'customer_id' => $customerId,
                    'date_preset' => $datePreset,
                ],
                $data
            );

            $dailySnapshotCount = $this->persistDailySnapshots(
                dailyInsights: $dailyInsights,
                customerId: $customerId,
                loginCustomerId: $loginCustomerId ?: null
            );

            $this->newLine();
            $this->info('Google Ads snapshot synced.');

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Snapshot ID', $snapshot->id],
                    ['Daily Snapshots', number_format($dailySnapshotCount)],
                    ['Campaigns', number_format((int) $snapshot->campaign_count)],
                    ['Total Cost', 'Rp ' . number_format((float) $snapshot->total_cost, 0, ',', '.')],
                    ['Impressions', number_format((int) $snapshot->total_impressions)],
                    ['Clicks', number_format((int) $snapshot->total_clicks)],
                    ['CTR', number_format((float) $snapshot->ctr, 2) . '%'],
                    ['Avg CPC', 'Rp ' . number_format((float) $snapshot->average_cpc, 0, ',', '.')],
                    ['Conversions', number_format((float) $snapshot->total_conversions, 2)],
                    [
                        'Cost / Conv',
                        $snapshot->cost_per_conversion !== null
                            ? 'Rp ' . number_format((float) $snapshot->cost_per_conversion, 0, ',', '.')
                            : '-',
                    ],
                    ['AI Summary', $snapshot->ai_summary_text ? 'yes' : 'no'],
                    ['AI Model', $snapshot->ai_model ?: '-'],
                    ['Synced At', optional($snapshot->synced_at)->format('d M Y H:i')],
                ]
            );

            return self::SUCCESS;
        } catch (Throwable $exception) {
            Log::error('Failed to sync Google Ads dashboard snapshot.', [
                'customer_id' => $customerId,
                'date_preset' => $datePreset,
                'with_ai' => $withAi,
                'message' => $exception->getMessage(),
            ]);

            $snapshot = GoogleAdsDashboardSnapshot::query()
                ->firstOrNew([
                    'customer_id' => $customerId,
                    'date_preset' => $datePreset,
                ]);

            if (! $snapshot->exists) {
                $snapshot->is_available = false;
                $snapshot->date_start = null;
                $snapshot->date_stop = null;
                $snapshot->payload = null;
                $snapshot->summary_text = 'Google Ads belum berhasil disinkronkan.';
            }

            $snapshot->error_message = $exception->getMessage();
            $snapshot->synced_at = now();
            $snapshot->save();

            $this->error('Google Ads sync failed.');
            $this->line($exception->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * @param array<string, mixed> $insight
     * @return array<string, mixed>
     */
    private function buildSnapshotData(
        array $insight,
        ?string $loginCustomerId
    ): array {
        $overview = is_array($insight['overview'] ?? null)
            ? $insight['overview']
            : [];
        $period = is_array($insight['period'] ?? null)
            ? $insight['period']
            : [];

        return [
            'login_customer_id' => $loginCustomerId,
            'date_start' => $period['date_start'] ?? null,
            'date_stop' => $period['date_stop'] ?? null,
            'is_available' => (bool) ($insight['is_available'] ?? true),

            'campaign_count' => max((int) ($overview['campaign_count'] ?? 0), 0),
            'enabled_campaign_count' => max((int) ($overview['enabled_campaign_count'] ?? 0), 0),
            'paused_campaign_count' => max((int) ($overview['paused_campaign_count'] ?? 0), 0),

            'total_cost' => max((float) ($overview['total_cost'] ?? 0), 0),
            'total_impressions' => max((int) ($overview['total_impressions'] ?? 0), 0),
            'total_clicks' => max((int) ($overview['total_clicks'] ?? 0), 0),
            'ctr' => max((float) ($overview['ctr'] ?? 0), 0),
            'average_cpc' => max((float) ($overview['average_cpc'] ?? 0), 0),
            'total_conversions' => max((float) ($overview['total_conversions'] ?? 0), 0),
            'total_conversion_value' => max((float) ($overview['total_conversion_value'] ?? 0), 0),
            'cost_per_conversion' => isset($overview['cost_per_conversion'])
                ? max((float) $overview['cost_per_conversion'], 0)
                : null,
            'conversion_rate' => max((float) ($overview['conversion_rate'] ?? 0), 0),
            'roas' => max((float) ($overview['roas'] ?? 0), 0),

            'critical_count' => max((int) ($overview['critical_count'] ?? 0), 0),
            'attention_count' => max((int) ($overview['attention_count'] ?? 0), 0),
            'healthy_count' => max((int) ($overview['healthy_count'] ?? 0), 0),

            'summary_text' => $insight['summary_text'] ?? null,
            'payload' => $insight,
            'error_message' => null,
            'synced_at' => now(),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $dailyInsights
     */
    private function persistDailySnapshots(
        array $dailyInsights,
        string $customerId,
        ?string $loginCustomerId
    ): int {
        $saved = 0;

        foreach ($dailyInsights as $dailyInsight) {
            $period = is_array($dailyInsight['period'] ?? null)
                ? $dailyInsight['period']
                : [];
            $dateStart = (string) ($period['date_start'] ?? '');
            $dateStop = (string) ($period['date_stop'] ?? '');

            if (
                $dateStart === ''
                || $dateStop === ''
                || $dateStart !== $dateStop
            ) {
                continue;
            }

            GoogleAdsDashboardSnapshot::query()->updateOrCreate(
                [
                    'customer_id' => $customerId,
                    'date_preset' => 'daily',
                    'date_start' => $dateStart,
                    'date_stop' => $dateStop,
                ],
                $this->buildSnapshotData(
                    insight: $dailyInsight,
                    loginCustomerId: $loginCustomerId
                )
            );

            $saved++;
        }

        return $saved;
    }
}
