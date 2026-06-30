<?php

namespace App\Console\Commands;

use App\Services\MetaAds\MetaAdsAiAnalysisService;
use App\Services\MetaAds\MetaAdsInsightService;
use Illuminate\Console\Command;

class SyncMetaAdsCampaignInsights extends Command
{
    protected $signature = 'meta-ads:sync-campaign-insights
        {--date-preset=last_7d}
        {--with-ai : Generate Gemini AI analysis after sync}';

    protected $description = 'Sync Meta Ads campaign insights into local database';

    public function handle(
        MetaAdsInsightService $service,
        MetaAdsAiAnalysisService $aiAnalysisService
    ): int {
        $datePreset = $this->option('date-preset');

        $this->info("Syncing Meta Ads campaign insights for {$datePreset}...");

        $result = $service->syncCampaignInsights($datePreset);

        $this->info("Synced {$result['synced']} campaign insight rows.");

        if ($this->option('with-ai')) {
            $this->info('Generating Gemini AI analysis...');

            $aiResult = $aiAnalysisService->analyzeLatestCampaigns();

            $this->info($aiResult['message'] ?? 'AI analysis generated.');
        }

        return self::SUCCESS;
    }
}