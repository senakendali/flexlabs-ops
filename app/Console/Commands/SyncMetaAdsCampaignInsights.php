<?php

namespace App\Console\Commands;

use App\Services\MetaAds\MetaAdsInsightService;
use Illuminate\Console\Command;

class SyncMetaAdsCampaignInsights extends Command
{
    protected $signature = 'meta-ads:sync-campaign-insights {--date-preset=last_7d}';

    protected $description = 'Sync Meta Ads campaign insights into local database';

    public function handle(MetaAdsInsightService $service): int
    {
        $datePreset = $this->option('date-preset');

        $this->info("Syncing Meta Ads campaign insights for {$datePreset}...");

        $result = $service->syncCampaignInsights($datePreset);

        $this->info("Synced {$result['synced']} campaign insight rows.");

        return self::SUCCESS;
    }
}