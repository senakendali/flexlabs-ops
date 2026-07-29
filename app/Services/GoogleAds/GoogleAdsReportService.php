<?php

namespace App\Services\GoogleAds;

use Carbon\Carbon;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use RuntimeException;

class GoogleAdsReportService
{
    public function dashboardInsight(string $datePreset = 'last_7d', int $limit = 20): array
    {
        $this->ensureConfigured();

        $dateRange = $this->resolveDateRange($datePreset);
        $campaigns = $this->campaignPerformance($datePreset, $limit);
        $overview = $this->buildOverview($campaigns);

        return [
            'is_available' => true,
            'source' => 'google_ads',
            'customer_id' => $this->customerId(),
            'login_customer_id' => $this->loginCustomerId(),
            'period' => [
                'date_preset' => $datePreset,
                'date_start' => $dateRange['date_start'],
                'date_stop' => $dateRange['date_stop'],
            ],
            'overview' => $overview,
            'campaigns' => $campaigns,
            'summary_text' => $overview['summary_text'] ?? 'Data Google Ads berhasil ditarik.',
            'last_synced_at' => now()->format('d M Y H:i'),
            'error_message' => null,
        ];
    }

    /**
     * Mengambil metrik akun per hari untuk kebutuhan agregasi KPI.
     *
     * Snapshot harian sengaja dipisahkan dari dashboardInsight():
     * - dashboardInsight() tetap menyimpan detail campaign dan AI summary;
     * - method ini hanya menyediakan metrik akun per tanggal;
     * - tanggal tanpa aktivitas tetap dikembalikan dengan nilai nol agar
     *   coverage periode dapat diverifikasi dengan benar.
     *
     * @return array<int, array<string, mixed>>
     */
    public function dailyDashboardInsights(string $datePreset = 'last_7d'): array
    {
        $this->ensureConfigured();

        $dateRange = $this->resolveDateRange($datePreset);
        $version = $this->resolveGoogleAdsVersion();
        $googleAdsClient = $this->makeGoogleAdsClient($version);
        $googleAdsServiceClient = $googleAdsClient->getGoogleAdsServiceClient();

        $startDate = $dateRange['date_start'];
        $endDate = $dateRange['date_stop'];
        $timezone = config('app.timezone', 'Asia/Jakarta');

        $dailyMetrics = [];
        $cursor = Carbon::parse($startDate, $timezone)->startOfDay();
        $lastDate = Carbon::parse($endDate, $timezone)->startOfDay();

        while ($cursor->lte($lastDate)) {
            $date = $cursor->toDateString();
            $dailyMetrics[$date] = [
                'total_cost' => 0.0,
                'total_impressions' => 0,
                'total_clicks' => 0,
                'total_conversions' => 0.0,
                'total_conversion_value' => 0.0,
            ];

            $cursor->addDay();
        }

        $query = <<<GAQL
SELECT
  segments.date,
  metrics.cost_micros,
  metrics.impressions,
  metrics.clicks,
  metrics.conversions,
  metrics.conversions_value
FROM customer
WHERE segments.date BETWEEN '{$startDate}' AND '{$endDate}'
ORDER BY segments.date ASC
GAQL;

        $request = $this->makeSearchRequest(
            version: $version,
            customerId: $this->customerId(),
            query: $query
        );

        $response = $googleAdsServiceClient->search($request);

        foreach ($response->iterateAllElements() as $row) {
            $date = (string) $row->getSegments()->getDate();

            if (! isset($dailyMetrics[$date])) {
                continue;
            }

            $metrics = $row->getMetrics();
            $dailyMetrics[$date]['total_cost'] += $this->microsToCurrency(
                $metrics->getCostMicros()
            );
            $dailyMetrics[$date]['total_impressions'] += (int) $metrics->getImpressions();
            $dailyMetrics[$date]['total_clicks'] += (int) $metrics->getClicks();
            $dailyMetrics[$date]['total_conversions'] += (float) $metrics->getConversions();
            $dailyMetrics[$date]['total_conversion_value'] += (float) $metrics->getConversionsValue();
        }

        return collect($dailyMetrics)
            ->map(function (array $metrics, string $date) use ($datePreset) {
                $overview = $this->buildDailyOverview($metrics);

                return [
                    'is_available' => true,
                    'source' => 'google_ads',
                    'granularity' => 'daily',
                    'customer_id' => $this->customerId(),
                    'login_customer_id' => $this->loginCustomerId(),
                    'period' => [
                        'date_preset' => 'daily',
                        'sync_preset' => $datePreset,
                        'date_start' => $date,
                        'date_stop' => $date,
                    ],
                    'overview' => $overview,
                    'campaigns' => [],
                    'summary_text' => $overview['summary_text'],
                    'last_synced_at' => now()->format('d M Y H:i'),
                    'error_message' => null,
                ];
            })
            ->values()
            ->all();
    }

    public function campaignPerformance(string $datePreset = 'last_7d', int $limit = 20): array
    {
        $this->ensureConfigured();

        $dateRange = $this->resolveDateRange($datePreset);
        $version = $this->resolveGoogleAdsVersion();

        $googleAdsClient = $this->makeGoogleAdsClient($version);
        $googleAdsServiceClient = $googleAdsClient->getGoogleAdsServiceClient();

        $startDate = $dateRange['date_start'];
        $endDate = $dateRange['date_stop'];
        $limit = max(min($limit, 100), 1);

        $query = <<<GAQL
SELECT
  campaign.id,
  campaign.name,
  campaign.status,
  campaign.advertising_channel_type,
  metrics.cost_micros,
  metrics.impressions,
  metrics.clicks,
  metrics.ctr,
  metrics.average_cpc,
  metrics.conversions,
  metrics.conversions_value
FROM campaign
WHERE campaign.status != 'REMOVED'
  AND segments.date BETWEEN '{$startDate}' AND '{$endDate}'
ORDER BY metrics.cost_micros DESC
LIMIT {$limit}
GAQL;

        $request = $this->makeSearchRequest(
            version: $version,
            customerId: $this->customerId(),
            query: $query
        );

        $response = $googleAdsServiceClient->search($request);

        $campaigns = [];

        foreach ($response->iterateAllElements() as $row) {
            $campaign = $row->getCampaign();
            $metrics = $row->getMetrics();

            $cost = $this->microsToCurrency($metrics->getCostMicros());
            $impressions = (int) $metrics->getImpressions();
            $clicks = (int) $metrics->getClicks();
            $conversions = (float) $metrics->getConversions();
            $conversionValue = (float) $metrics->getConversionsValue();

            $ctr = $impressions > 0
                ? round(($clicks / $impressions) * 100, 2)
                : 0;

            $avgCpc = $clicks > 0
                ? round($cost / $clicks, 2)
                : 0;

            $costPerConversion = $conversions > 0
                ? round($cost / $conversions, 2)
                : null;

            $conversionRate = $clicks > 0
                ? round(($conversions / $clicks) * 100, 2)
                : 0;

            $status = (int) $campaign->getStatus();
            $channelType = (int) $campaign->getAdvertisingChannelType();

            $health = $this->getCampaignHealth(
                cost: $cost,
                impressions: $impressions,
                clicks: $clicks,
                ctr: $ctr,
                conversions: $conversions
            );

            $campaigns[] = [
                'campaign_id' => (string) $campaign->getId(),
                'campaign_name' => $campaign->getName(),

                'status' => $status,
                'status_label' => $this->campaignStatusLabel($status),
                'status_badge_class' => $this->campaignStatusBadgeClass($status),

                'advertising_channel_type' => $channelType,
                'advertising_channel_label' => $this->advertisingChannelLabel($channelType),

                'cost' => $cost,
                'cost_label' => $this->formatCurrency($cost),

                'impressions' => $impressions,
                'clicks' => $clicks,
                'ctr' => $ctr,

                'average_cpc' => $avgCpc,
                'average_cpc_label' => $this->formatCurrency($avgCpc),

                'conversions' => $conversions,
                'conversion_value' => $conversionValue,

                'cost_per_conversion' => $costPerConversion,
                'cost_per_conversion_label' => $costPerConversion !== null
                    ? $this->formatCurrency($costPerConversion)
                    : '-',

                'conversion_rate' => $conversionRate,

                'health_status' => $health['status'],
                'health_label' => $health['label'],
                'health_type' => $health['type'],
                'health_badge_class' => $this->healthBadgeClass($health['type']),
                'insight' => $this->buildCampaignInsight(
                    campaignName: $campaign->getName(),
                    cost: $cost,
                    impressions: $impressions,
                    clicks: $clicks,
                    ctr: $ctr,
                    conversions: $conversions,
                    costPerConversion: $costPerConversion,
                    healthType: $health['type']
                ),
            ];
        }

        return collect($campaigns)
            ->sortByDesc('cost')
            ->values()
            ->all();
    }

    /**
     * @param array<string, int|float> $metrics
     * @return array<string, mixed>
     */
    protected function buildDailyOverview(array $metrics): array
    {
        $totalCost = max((float) ($metrics['total_cost'] ?? 0), 0);
        $totalImpressions = max((int) ($metrics['total_impressions'] ?? 0), 0);
        $totalClicks = max((int) ($metrics['total_clicks'] ?? 0), 0);
        $totalConversions = max((float) ($metrics['total_conversions'] ?? 0), 0);
        $totalConversionValue = max(
            (float) ($metrics['total_conversion_value'] ?? 0),
            0
        );

        $ctr = $totalImpressions > 0
            ? round(($totalClicks / $totalImpressions) * 100, 2)
            : 0;

        $averageCpc = $totalClicks > 0
            ? round($totalCost / $totalClicks, 2)
            : 0;

        $costPerConversion = $totalConversions > 0
            ? round($totalCost / $totalConversions, 2)
            : null;

        $conversionRate = $totalClicks > 0
            ? round(($totalConversions / $totalClicks) * 100, 2)
            : 0;

        $roas = $totalCost > 0
            ? round($totalConversionValue / $totalCost, 2)
            : 0;

        return [
            'campaign_count' => 0,
            'enabled_campaign_count' => 0,
            'paused_campaign_count' => 0,

            'total_cost' => round($totalCost, 2),
            'total_cost_label' => $this->formatCurrency($totalCost),

            'total_impressions' => $totalImpressions,
            'total_clicks' => $totalClicks,
            'ctr' => $ctr,

            'average_cpc' => $averageCpc,
            'average_cpc_label' => $this->formatCurrency($averageCpc),

            'total_conversions' => round($totalConversions, 2),
            'total_conversion_value' => round($totalConversionValue, 2),

            'cost_per_conversion' => $costPerConversion,
            'cost_per_conversion_label' => $costPerConversion !== null
                ? $this->formatCurrency($costPerConversion)
                : '-',

            'conversion_rate' => $conversionRate,
            'roas' => $roas,

            'critical_count' => 0,
            'attention_count' => 0,
            'healthy_count' => 0,
            'best_campaign' => null,

            'summary_text' => $totalCost > 0
                ? 'Google Ads mengeluarkan spend harian ' . $this->formatCurrency($totalCost) . '.'
                : 'Tidak ada spend Google Ads pada tanggal ini.',
        ];
    }

    protected function buildOverview(array $campaigns): array
    {
        $campaignCollection = collect($campaigns);

        $totalCost = (float) $campaignCollection->sum('cost');
        $totalImpressions = (int) $campaignCollection->sum('impressions');
        $totalClicks = (int) $campaignCollection->sum('clicks');
        $totalConversions = (float) $campaignCollection->sum('conversions');
        $totalConversionValue = (float) $campaignCollection->sum('conversion_value');

        $ctr = $totalImpressions > 0
            ? round(($totalClicks / $totalImpressions) * 100, 2)
            : 0;

        $averageCpc = $totalClicks > 0
            ? round($totalCost / $totalClicks, 2)
            : 0;

        $costPerConversion = $totalConversions > 0
            ? round($totalCost / $totalConversions, 2)
            : null;

        $conversionRate = $totalClicks > 0
            ? round(($totalConversions / $totalClicks) * 100, 2)
            : 0;

        $roas = $totalCost > 0
            ? round($totalConversionValue / $totalCost, 2)
            : 0;

        $enabledCampaigns = $campaignCollection
            ->filter(fn (array $campaign) => ($campaign['status_label'] ?? null) === 'Enabled')
            ->count();

        $pausedCampaigns = $campaignCollection
            ->filter(fn (array $campaign) => ($campaign['status_label'] ?? null) === 'Paused')
            ->count();

        $criticalCount = $campaignCollection
            ->filter(fn (array $campaign) => ($campaign['health_type'] ?? null) === 'critical')
            ->count();

        $attentionCount = $campaignCollection
            ->filter(fn (array $campaign) => in_array(($campaign['health_type'] ?? null), ['warning', 'action'], true))
            ->count();

        $healthyCount = $campaignCollection
            ->filter(fn (array $campaign) => ($campaign['health_type'] ?? null) === 'good')
            ->count();

        $bestCampaign = $campaignCollection
            ->filter(fn (array $campaign) => (float) ($campaign['conversions'] ?? 0) > 0)
            ->sortBy(fn (array $campaign) => $campaign['cost_per_conversion'] ?? PHP_INT_MAX)
            ->first();

        if (! $bestCampaign) {
            $bestCampaign = $campaignCollection
                ->sortByDesc('clicks')
                ->first();
        }

        $summaryText = match (true) {
            $campaignCollection->isEmpty() => 'Data Google Ads belum tersedia untuk periode terbaru.',
            $totalCost > 0 && $totalClicks <= 0 => 'Google Ads sudah mengeluarkan spend ' . $this->formatCurrency($totalCost) . ', tetapi belum menghasilkan klik. Prioritasnya cek targeting, keyword, bidding, dan creative/ad copy.',
            $totalClicks > 0 && $totalConversions <= 0 => 'Google Ads menghasilkan ' . number_format($totalClicks) . ' klik dari spend ' . $this->formatCurrency($totalCost) . ', tetapi belum menghasilkan conversion yang tercatat. Prioritasnya cek landing page, tracking conversion, CTA, dan intent keyword.',
            $criticalCount > 0 => 'Google Ads menghasilkan ' . number_format($totalConversions, 2) . ' conversions dari spend ' . $this->formatCurrency($totalCost) . ', namun ada ' . number_format($criticalCount) . ' campaign yang perlu dicek karena performa conversion lemah.',
            $attentionCount > 0 => 'Google Ads menghasilkan ' . number_format($totalConversions, 2) . ' conversions dari spend ' . $this->formatCurrency($totalCost) . '. Ada beberapa campaign yang perlu dioptimasi agar biaya tidak bocor.',
            $totalConversions > 0 => 'Google Ads terlihat mulai menghasilkan. Total spend ' . $this->formatCurrency($totalCost) . ' menghasilkan ' . number_format($totalConversions, 2) . ' conversions dengan cost/conv ' . ($costPerConversion !== null ? $this->formatCurrency($costPerConversion) : '-') . '.',
            default => 'Google Ads sudah memiliki data performa. Lanjutkan monitoring cost, CTR, CPC, dan conversion per campaign.',
        };

        if ($bestCampaign) {
            $summaryText .= ' Campaign terbaik sementara: ' . ($bestCampaign['campaign_name'] ?? '-') . '.';
        }

        return [
            'campaign_count' => $campaignCollection->count(),
            'enabled_campaign_count' => $enabledCampaigns,
            'paused_campaign_count' => $pausedCampaigns,

            'total_cost' => $totalCost,
            'total_cost_label' => $this->formatCurrency($totalCost),

            'total_impressions' => $totalImpressions,
            'total_clicks' => $totalClicks,
            'ctr' => $ctr,

            'average_cpc' => $averageCpc,
            'average_cpc_label' => $this->formatCurrency($averageCpc),

            'total_conversions' => $totalConversions,
            'total_conversion_value' => $totalConversionValue,

            'cost_per_conversion' => $costPerConversion,
            'cost_per_conversion_label' => $costPerConversion !== null
                ? $this->formatCurrency($costPerConversion)
                : '-',

            'conversion_rate' => $conversionRate,
            'roas' => $roas,

            'critical_count' => $criticalCount,
            'attention_count' => $attentionCount,
            'healthy_count' => $healthyCount,

            'best_campaign' => $bestCampaign,
            'summary_text' => $summaryText,
        ];
    }

    protected function makeGoogleAdsClient(string $version)
    {
        $builderClass = "Google\\Ads\\GoogleAds\\Lib\\{$version}\\GoogleAdsClientBuilder";

        if (! class_exists($builderClass)) {
            throw new RuntimeException("Google Ads client builder class not found: {$builderClass}");
        }

        $oAuth2Credential = (new OAuth2TokenBuilder())
            ->withClientId(config('services.google_ads.client_id'))
            ->withClientSecret(config('services.google_ads.client_secret'))
            ->withRefreshToken(config('services.google_ads.refresh_token'))
            ->build();

        $builder = (new $builderClass())
            ->withOAuth2Credential($oAuth2Credential)
            ->withDeveloperToken(config('services.google_ads.developer_token'));

        if ($this->loginCustomerId()) {
            $builder->withLoginCustomerId((int) $this->loginCustomerId());
        }

        return $builder->build();
    }

    protected function makeSearchRequest(string $version, string $customerId, string $query)
    {
        $requestClass = "Google\\Ads\\GoogleAds\\{$version}\\Services\\SearchGoogleAdsRequest";

        if (! class_exists($requestClass)) {
            throw new RuntimeException("Google Ads search request class not found: {$requestClass}");
        }

        return new $requestClass([
            'customer_id' => $customerId,
            'query' => $query,
        ]);
    }

    protected function resolveGoogleAdsVersion(): string
    {
        $versions = [
            'V22',
            'V21',
            'V20',
            'V19',
            'V18',
            'V17',
        ];

        foreach ($versions as $version) {
            $class = "Google\\Ads\\GoogleAds\\Lib\\{$version}\\GoogleAdsClientBuilder";

            if (class_exists($class)) {
                return $version;
            }
        }

        throw new RuntimeException('No supported Google Ads API client builder version was found.');
    }

    protected function resolveDateRange(string $datePreset): array
    {
        $timezone = config('app.timezone', 'Asia/Jakarta');
        $today = Carbon::now($timezone)->toDateString();

        return match ($datePreset) {
            'today' => [
                'date_start' => $today,
                'date_stop' => $today,
            ],

            'yesterday' => [
                'date_start' => Carbon::now($timezone)->subDay()->toDateString(),
                'date_stop' => Carbon::now($timezone)->subDay()->toDateString(),
            ],

            'last_30d' => [
                'date_start' => Carbon::now($timezone)->subDays(29)->toDateString(),
                'date_stop' => $today,
            ],

            'last_90d' => [
                'date_start' => Carbon::now($timezone)->subDays(89)->toDateString(),
                'date_stop' => $today,
            ],

            default => [
                'date_start' => Carbon::now($timezone)->subDays(6)->toDateString(),
                'date_stop' => $today,
            ],
        };
    }

    protected function getCampaignHealth(
        float $cost,
        int $impressions,
        int $clicks,
        float $ctr,
        float $conversions
    ): array {
        if ($cost > 0 && $clicks <= 0) {
            return [
                'status' => 'no_clicks',
                'label' => 'No Clicks',
                'type' => 'critical',
            ];
        }

        if ($clicks > 0 && $conversions <= 0) {
            return [
                'status' => 'conversion_bottleneck',
                'label' => 'Conversion Bottleneck',
                'type' => 'critical',
            ];
        }

        if ($impressions >= 100 && $ctr < 1) {
            return [
                'status' => 'low_ctr',
                'label' => 'Low CTR',
                'type' => 'warning',
            ];
        }

        if ($conversions > 0) {
            return [
                'status' => 'converting',
                'label' => 'Converting',
                'type' => 'good',
            ];
        }

        if ($impressions > 0 || $clicks > 0) {
            return [
                'status' => 'monitor',
                'label' => 'Monitor',
                'type' => 'info',
            ];
        }

        return [
            'status' => 'no_data',
            'label' => 'No Data',
            'type' => 'info',
        ];
    }

    protected function buildCampaignInsight(
        string $campaignName,
        float $cost,
        int $impressions,
        int $clicks,
        float $ctr,
        float $conversions,
        ?float $costPerConversion,
        string $healthType
    ): string {
        return match ($healthType) {
            'critical' => $campaignName . ' perlu perhatian. Spend ' . $this->formatCurrency($cost) . ' menghasilkan ' . number_format($clicks) . ' klik dan ' . number_format($conversions, 2) . ' conversions. Cek keyword/audience, landing page, CTA, dan tracking conversion.',
            'warning' => $campaignName . ' perlu dioptimasi. Campaign sudah mendapat ' . number_format($impressions) . ' impressions, tetapi CTR baru ' . number_format($ctr, 2) . '%. Cek ad copy, asset, keyword intent, dan relevansi landing page.',
            'good' => $campaignName . ' sudah menghasilkan conversion. Cost/conv saat ini ' . ($costPerConversion !== null ? $this->formatCurrency($costPerConversion) : '-') . '. Jika kualitas lead bagus, budget bisa dinaikkan bertahap sambil menjaga cost/conv.',
            default => $campaignName . ' sudah memiliki data performa. Pantau cost, CTR, CPC, dan conversion sebelum mengambil keputusan budget besar.',
        };
    }

    protected function campaignStatusLabel(int $status): string
    {
        return match ($status) {
            2 => 'Enabled',
            3 => 'Paused',
            4 => 'Removed',
            default => 'Unknown',
        };
    }

    protected function campaignStatusBadgeClass(int $status): string
    {
        return match ($status) {
            2 => 'bg-success-subtle text-success',
            3 => 'bg-warning-subtle text-warning',
            4 => 'bg-secondary-subtle text-secondary',
            default => 'bg-light text-muted',
        };
    }

    protected function advertisingChannelLabel(int $channelType): string
    {
        return match ($channelType) {
            2 => 'Search',
            3 => 'Display',
            4 => 'Shopping',
            5 => 'Hotel',
            6 => 'Video',
            7 => 'Multi Channel',
            8 => 'Local',
            9 => 'Smart',
            10 => 'Performance Max',
            11 => 'Local Services',
            12 => 'Demand Gen',
            default => 'Unknown',
        };
    }

    protected function healthBadgeClass(string $type): string
    {
        return match ($type) {
            'critical' => 'bg-danger-subtle text-danger',
            'warning' => 'bg-warning-subtle text-warning',
            'action' => 'bg-primary-subtle text-primary',
            'good' => 'bg-success-subtle text-success',
            default => 'bg-light text-muted',
        };
    }

    protected function microsToCurrency(int|float|string|null $micros): float
    {
        return round(((float) ($micros ?? 0)) / 1_000_000, 2);
    }

    protected function formatCurrency(float $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    protected function ensureConfigured(): void
    {
        if (! config('services.google_ads.enabled')) {
            throw new RuntimeException('Google Ads integration is disabled.');
        }

        $required = [
            'developer_token',
            'client_id',
            'client_secret',
            'refresh_token',
            'customer_id',
        ];

        foreach ($required as $key) {
            if (blank(config("services.google_ads.{$key}"))) {
                throw new RuntimeException("Google Ads config is missing: {$key}");
            }
        }
    }

    protected function customerId(): string
    {
        return preg_replace('/\D+/', '', (string) config('services.google_ads.customer_id'));
    }

    protected function loginCustomerId(): ?string
    {
        $loginCustomerId = preg_replace('/\D+/', '', (string) config('services.google_ads.login_customer_id'));

        return $loginCustomerId !== ''
            ? $loginCustomerId
            : null;
    }
}
