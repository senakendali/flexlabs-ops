<?php

namespace App\Console\Commands;

use App\Models\GoogleAnalyticsDashboardSnapshot;
use App\Services\GoogleAnalytics\GoogleAnalyticsAiInsightService;
use App\Services\GoogleAnalytics\GoogleAnalyticsReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncGoogleAnalyticsDashboardSnapshot extends Command
{
    protected $signature = 'google-analytics:sync-dashboard
        {--date-preset= : Date preset: today, yesterday, last_7d, last_30d, last_90d}
        {--with-ai : Generate AI analysis for Google Analytics dashboard snapshot}';

    protected $description = 'Sync Google Analytics dashboard insight into local snapshot table.';

    public function handle(
        GoogleAnalyticsReportService $googleAnalyticsReportService,
        GoogleAnalyticsAiInsightService $googleAnalyticsAiInsightService
    ): int {
        $datePreset = (string) (
            $this->option('date-preset')
            ?: config('services.google_analytics.default_date_preset', 'last_7d')
        );

        $withAi = (bool) $this->option('with-ai');
        $propertyId = (string) config('services.google_analytics.property_id');

        $this->info('Syncing Google Analytics dashboard snapshot...');
        $this->line('Property ID: ' . ($propertyId ?: '-'));
        $this->line('Date preset: ' . $datePreset);
        $this->line('AI analysis: ' . ($withAi ? 'yes' : 'no'));

        try {
            $existingSnapshot = GoogleAnalyticsDashboardSnapshot::query()
                ->where('property_id', $propertyId)
                ->where('date_preset', $datePreset)
                ->first();

            $insight = $googleAnalyticsReportService->dashboardInsight($datePreset);

            $localSummaryText = $insight['summary_text'] ?? null;
            $aiSummary = null;

            if ($withAi) {
                $this->newLine();
                $this->info('Generating Google Analytics AI analysis...');

                $aiSummary = $googleAnalyticsAiInsightService->generate($insight);

                $insight['local_summary_text'] = $localSummaryText;
                $insight['ai_summary'] = $aiSummary;
                $insight['summary_text'] = $aiSummary['summary'] ?? $localSummaryText;

                $this->info('AI analysis generated.');
            } elseif ($existingSnapshot && is_array($existingSnapshot->ai_payload)) {
                /*
                 * Kalau sync biasa tanpa --with-ai, jangan hilangkan AI terakhir.
                 * Data GA di-refresh, AI summary terakhir tetap dipakai sampai command
                 * berikutnya dijalankan dengan --with-ai.
                 */
                $aiSummary = $existingSnapshot->ai_payload;

                $insight['local_summary_text'] = $localSummaryText;
                $insight['ai_summary'] = $aiSummary;
                $insight['summary_text'] = $existingSnapshot->ai_summary_text ?: $localSummaryText;
            }

            $period = $insight['period'] ?? [];
            $kpis = $insight['kpis'] ?? [];

            $data = [
                'date_start' => $period['date_start'] ?? null,
                'date_stop' => $period['date_stop'] ?? null,
                'is_available' => (bool) ($insight['is_available'] ?? true),

                'total_users' => max((int) ($kpis['total_users'] ?? 0), 0),
                'new_users' => max((int) ($kpis['new_users'] ?? 0), 0),
                'sessions' => max((int) ($kpis['sessions'] ?? 0), 0),
                'engaged_sessions' => max((int) ($kpis['engaged_sessions'] ?? 0), 0),
                'engagement_rate' => max((float) ($kpis['engagement_rate'] ?? 0), 0),
                'bounce_rate' => max((float) ($kpis['bounce_rate'] ?? 0), 0),
                'key_events' => max((int) ($kpis['key_events'] ?? 0), 0),
                'key_event_rate' => max((float) ($kpis['key_event_rate'] ?? 0), 0),
                'average_engagement_time_label' => $kpis['average_engagement_time_label'] ?? '0s',

                'summary_text' => $insight['summary_text'] ?? null,
                'payload' => $insight,
                'error_message' => null,
                'synced_at' => now(),
            ];

            if ($withAi) {
                $data['ai_summary_text'] = $aiSummary['summary'] ?? null;
                $data['ai_payload'] = $aiSummary;
                $data['ai_model'] = $aiSummary['model'] ?? config('services.google_analytics.ai_model');
                $data['ai_generated_at'] = now();
            } elseif ($existingSnapshot && is_array($existingSnapshot->ai_payload)) {
                $data['ai_summary_text'] = $existingSnapshot->ai_summary_text;
                $data['ai_payload'] = $existingSnapshot->ai_payload;
                $data['ai_model'] = $existingSnapshot->ai_model;
                $data['ai_generated_at'] = $existingSnapshot->ai_generated_at;
            }

            $snapshot = GoogleAnalyticsDashboardSnapshot::query()->updateOrCreate(
                [
                    'property_id' => $propertyId,
                    'date_preset' => $datePreset,
                ],
                $data
            );

            $this->newLine();
            $this->info('Google Analytics snapshot synced.');

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Snapshot ID', $snapshot->id],
                    ['Sessions', number_format((int) $snapshot->sessions)],
                    ['Total Users', number_format((int) $snapshot->total_users)],
                    ['New Users', number_format((int) $snapshot->new_users)],
                    ['Engaged Sessions', number_format((int) $snapshot->engaged_sessions)],
                    ['Engagement Rate', number_format((float) $snapshot->engagement_rate, 1) . '%'],
                    ['Key Events', number_format((int) $snapshot->key_events)],
                    ['AI Summary', $snapshot->ai_summary_text ? 'yes' : 'no'],
                    ['AI Model', $snapshot->ai_model ?: '-'],
                    ['Synced At', optional($snapshot->synced_at)->format('d M Y H:i')],
                ]
            );

            return self::SUCCESS;
        } catch (Throwable $exception) {
            Log::error('Failed to sync Google Analytics dashboard snapshot.', [
                'property_id' => $propertyId,
                'date_preset' => $datePreset,
                'with_ai' => $withAi,
                'message' => $exception->getMessage(),
            ]);

            /*
             * Jangan overwrite payload lama kalau sync gagal.
             */
            $snapshot = GoogleAnalyticsDashboardSnapshot::query()
                ->firstOrNew([
                    'property_id' => $propertyId,
                    'date_preset' => $datePreset,
                ]);

            if (! $snapshot->exists) {
                $snapshot->is_available = false;
                $snapshot->date_start = null;
                $snapshot->date_stop = null;
                $snapshot->payload = null;
                $snapshot->summary_text = 'Google Analytics belum berhasil disinkronkan.';
            }

            $snapshot->error_message = $exception->getMessage();
            $snapshot->synced_at = now();
            $snapshot->save();

            $this->error('Google Analytics sync failed.');
            $this->line($exception->getMessage());

            return self::FAILURE;
        }
    }
}