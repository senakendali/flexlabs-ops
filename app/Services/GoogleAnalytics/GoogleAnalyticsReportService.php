<?php

namespace App\Services\GoogleAnalytics;

use Google\Analytics\Data\V1beta\Client\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\RunReportRequest;
use RuntimeException;
use Throwable;

class GoogleAnalyticsReportService
{
    public function dashboardInsight(string $datePreset = 'last_7d'): array
    {
        $summary = $this->summary($datePreset);

        $metrics = $summary['metrics'] ?? [];
        $period = $summary['period'] ?? [];

        $activeUsers = (int) ($metrics['active_users'] ?? 0);
        $newUsers = (int) ($metrics['new_users'] ?? 0);
        $sessions = (int) ($metrics['sessions'] ?? 0);
        $engagedSessions = (int) ($metrics['engaged_sessions'] ?? 0);
        $eventCount = (int) ($metrics['event_count'] ?? 0);
        $engagementRate = (float) ($metrics['engagement_rate'] ?? 0);
        $bounceRate = max(0, round(100 - $engagementRate, 1));
        $averageEngagementTimeSeconds = (float) ($metrics['average_engagement_time_seconds'] ?? 0);

        return [
            'is_available' => true,
            'period' => $period,
            'last_synced_at' => now()->format('d M Y H:i'),
            'summary_text' => $this->buildSummaryText(
                activeUsers: $activeUsers,
                sessions: $sessions,
                engagedSessions: $engagedSessions,
                engagementRate: $engagementRate,
                eventCount: $eventCount,
            ),
            'error_message' => null,

            'kpis' => [
                'total_users' => $activeUsers,
                'new_users' => $newUsers,
                'sessions' => $sessions,
                'engaged_sessions' => $engagedSessions,
                'engagement_rate' => $engagementRate,
                'bounce_rate' => $bounceRate,
                'average_engagement_time_label' => $this->formatDuration($averageEngagementTimeSeconds),

                /*
                |--------------------------------------------------------------------------
                | Temporary Mapping
                |--------------------------------------------------------------------------
                | Untuk step awal, key_events masih diisi dari eventCount supaya
                | dashboard hidup. Setelah custom key event FlexLabs rapi, nanti bisa
                | diganti ke metric keyEvents atau filtered eventName tertentu.
                */
                'key_events' => $eventCount,
                'key_event_rate' => $sessions > 0
                    ? round(($eventCount / $sessions) * 100, 1)
                    : 0,
            ],

            'acquisition' => $this->safeSection(
                fn () => $this->acquisitionBreakdown($datePreset),
                [
                    'channels' => [],
                    'sources' => [],
                    'campaigns' => [],
                ]
            ),

            'landing_pages' => $this->safeSection(
                fn () => $this->landingPages($datePreset),
                []
            ),

            'conversion_funnel' => $this->safeSection(
                fn () => $this->conversionFunnel($datePreset, $sessions, $engagedSessions, $eventCount),
                $this->fallbackConversionFunnel($sessions, $engagedSessions, $eventCount)
            ),

            'content_pages' => $this->safeSection(
                fn () => $this->contentPerformance($datePreset),
                []
            ),

            'devices' => $this->safeSection(
                fn () => $this->deviceBreakdown($datePreset),
                []
            ),

            'locations' => $this->safeSection(
                fn () => $this->locationBreakdown($datePreset),
                []
            ),
        ];
    }

    public function summary(string $datePreset = 'last_7d'): array
    {
        $this->ensureConfigured();

        $dateRange = $this->resolveDateRange($datePreset);
        $client = $this->makeClient();

        $request = (new RunReportRequest())
            ->setProperty($this->propertyName())
            ->setDateRanges([
                new DateRange([
                    'start_date' => $dateRange['date_start'],
                    'end_date' => $dateRange['date_stop'],
                ]),
            ])
            ->setMetrics([
                new Metric(['name' => 'activeUsers']),
                new Metric(['name' => 'newUsers']),
                new Metric(['name' => 'sessions']),
                new Metric(['name' => 'engagedSessions']),
                new Metric(['name' => 'screenPageViews']),
                new Metric(['name' => 'eventCount']),
                new Metric(['name' => 'userEngagementDuration']),
            ]);

        $response = $client->runReport($request);
        $row = $response->getRows()[0] ?? null;

        if (! $row) {
            return [
                'is_available' => true,
                'period' => $dateRange,
                'metrics' => $this->emptySummaryMetrics(),
            ];
        }

        $values = collect($row->getMetricValues())
            ->map(fn ($metricValue) => (float) $metricValue->getValue())
            ->values();

        $activeUsers = (int) ($values[0] ?? 0);
        $newUsers = (int) ($values[1] ?? 0);
        $sessions = (int) ($values[2] ?? 0);
        $engagedSessions = (int) ($values[3] ?? 0);
        $screenPageViews = (int) ($values[4] ?? 0);
        $eventCount = (int) ($values[5] ?? 0);
        $userEngagementDuration = (float) ($values[6] ?? 0);

        $engagementRate = $sessions > 0
            ? round(($engagedSessions / $sessions) * 100, 1)
            : 0;

        $averageEngagementTimeSeconds = $sessions > 0
            ? round($userEngagementDuration / $sessions)
            : 0;

        return [
            'is_available' => true,
            'period' => $dateRange,
            'metrics' => [
                'active_users' => $activeUsers,
                'new_users' => $newUsers,
                'sessions' => $sessions,
                'engaged_sessions' => $engagedSessions,
                'screen_page_views' => $screenPageViews,
                'event_count' => $eventCount,
                'user_engagement_duration' => $userEngagementDuration,
                'average_engagement_time_seconds' => $averageEngagementTimeSeconds,
                'engagement_rate' => $engagementRate,
            ],
        ];
    }

    public function acquisitionBreakdown(string $datePreset = 'last_7d'): array
    {
        $this->ensureConfigured();

        return [
            'channels' => $this->topChannels($datePreset),
            'sources' => $this->topSources($datePreset),
            'campaigns' => $this->topCampaigns($datePreset),
        ];
    }

    protected function topChannels(string $datePreset = 'last_7d', int $limit = 8): array
    {
        $rows = $this->runDimensionReport(
            datePreset: $datePreset,
            dimensions: ['sessionDefaultChannelGroup'],
            metrics: ['sessions', 'activeUsers', 'engagedSessions', 'eventCount'],
            limit: 50
        );

        return collect($rows)
            ->map(function (array $row) {
                $sessions = (int) ($row['metrics']['sessions'] ?? 0);
                $engagedSessions = (int) ($row['metrics']['engagedSessions'] ?? 0);

                return [
                    'channel' => $this->normalizeGaLabel($row['dimensions']['sessionDefaultChannelGroup'] ?? '(not set)'),
                    'sessions' => $sessions,
                    'users' => (int) ($row['metrics']['activeUsers'] ?? 0),
                    'engaged_sessions' => $engagedSessions,
                    'engagement_rate' => $this->calculateRate($engagedSessions, $sessions),
                    'key_events' => (int) ($row['metrics']['eventCount'] ?? 0),
                ];
            })
            ->sortByDesc('sessions')
            ->take($limit)
            ->values()
            ->all();
    }

    protected function topSources(string $datePreset = 'last_7d', int $limit = 10): array
    {
        $rows = $this->runDimensionReport(
            datePreset: $datePreset,
            dimensions: ['sessionSource', 'sessionMedium'],
            metrics: ['sessions', 'activeUsers', 'engagedSessions', 'eventCount'],
            limit: 80
        );

        return collect($rows)
            ->map(function (array $row) {
                $source = $this->normalizeGaLabel($row['dimensions']['sessionSource'] ?? '(not set)');
                $medium = $this->normalizeGaLabel($row['dimensions']['sessionMedium'] ?? '(not set)');

                $sessions = (int) ($row['metrics']['sessions'] ?? 0);
                $engagedSessions = (int) ($row['metrics']['engagedSessions'] ?? 0);

                return [
                    'source' => $source,
                    'medium' => $medium,
                    'source_medium' => $source . ' / ' . $medium,
                    'sessions' => $sessions,
                    'users' => (int) ($row['metrics']['activeUsers'] ?? 0),
                    'engaged_sessions' => $engagedSessions,
                    'engagement_rate' => $this->calculateRate($engagedSessions, $sessions),
                    'key_events' => (int) ($row['metrics']['eventCount'] ?? 0),
                ];
            })
            ->sortByDesc('sessions')
            ->take($limit)
            ->values()
            ->all();
    }

    protected function topCampaigns(string $datePreset = 'last_7d', int $limit = 10): array
    {
        $rows = $this->runDimensionReport(
            datePreset: $datePreset,
            dimensions: ['sessionCampaignName'],
            metrics: ['sessions', 'activeUsers', 'engagedSessions', 'eventCount'],
            limit: 80
        );

        return collect($rows)
            ->map(function (array $row) {
                $campaign = $this->normalizeGaLabel($row['dimensions']['sessionCampaignName'] ?? '(not set)');

                $sessions = (int) ($row['metrics']['sessions'] ?? 0);
                $engagedSessions = (int) ($row['metrics']['engagedSessions'] ?? 0);

                return [
                    'campaign' => $campaign,
                    'sessions' => $sessions,
                    'users' => (int) ($row['metrics']['activeUsers'] ?? 0),
                    'engaged_sessions' => $engagedSessions,
                    'engagement_rate' => $this->calculateRate($engagedSessions, $sessions),
                    'key_events' => (int) ($row['metrics']['eventCount'] ?? 0),
                ];
            })
            ->reject(fn (array $row) => in_array(strtolower($row['campaign']), ['(not set)', '(direct)', 'not set', '-'], true))
            ->sortByDesc('sessions')
            ->take($limit)
            ->values()
            ->all();
    }

    public function landingPages(string $datePreset = 'last_7d', int $limit = 10): array
    {
        $this->ensureConfigured();

        $rows = $this->runDimensionReport(
            datePreset: $datePreset,
            dimensions: ['landingPagePlusQueryString'],
            metrics: ['sessions', 'activeUsers', 'engagedSessions', 'eventCount', 'userEngagementDuration'],
            limit: 80
        );

        return collect($rows)
            ->map(function (array $row) {
                $sessions = (int) ($row['metrics']['sessions'] ?? 0);
                $engagedSessions = (int) ($row['metrics']['engagedSessions'] ?? 0);
                $eventCount = (int) ($row['metrics']['eventCount'] ?? 0);
                $engagementDuration = (float) ($row['metrics']['userEngagementDuration'] ?? 0);

                return [
                    'landing_page' => $this->normalizeGaPath($row['dimensions']['landingPagePlusQueryString'] ?? '/'),
                    'page_title' => null,
                    'sessions' => $sessions,
                    'users' => (int) ($row['metrics']['activeUsers'] ?? 0),
                    'engaged_sessions' => $engagedSessions,
                    'engagement_rate' => $this->calculateRate($engagedSessions, $sessions),
                    'key_events' => $eventCount,
                    'key_event_rate' => $this->calculateRate($eventCount, $sessions),
                    'average_engagement_time_label' => $this->formatDuration(
                        $sessions > 0 ? $engagementDuration / $sessions : 0
                    ),
                ];
            })
            ->sortByDesc('sessions')
            ->take($limit)
            ->values()
            ->all();
    }

    public function contentPerformance(string $datePreset = 'last_7d', int $limit = 10): array
    {
        $this->ensureConfigured();

        $rows = $this->runDimensionReport(
            datePreset: $datePreset,
            dimensions: ['pageTitle', 'pagePathPlusQueryString'],
            metrics: ['screenPageViews', 'activeUsers', 'eventCount', 'userEngagementDuration'],
            limit: 100
        );

        return collect($rows)
            ->map(function (array $row) {
                $views = (int) ($row['metrics']['screenPageViews'] ?? 0);
                $users = (int) ($row['metrics']['activeUsers'] ?? 0);
                $engagementDuration = (float) ($row['metrics']['userEngagementDuration'] ?? 0);

                return [
                    'page_title' => $this->normalizeGaLabel($row['dimensions']['pageTitle'] ?? '(not set)'),
                    'page_path' => $this->normalizeGaPath($row['dimensions']['pagePathPlusQueryString'] ?? '/'),
                    'views' => $views,
                    'users' => $users,
                    'average_engagement_time_label' => $this->formatDuration(
                        $users > 0 ? $engagementDuration / $users : 0
                    ),
                    'event_count' => (int) ($row['metrics']['eventCount'] ?? 0),
                ];
            })
            ->sortByDesc('views')
            ->take($limit)
            ->values()
            ->all();
    }

    public function deviceBreakdown(string $datePreset = 'last_7d', int $limit = 6): array
    {
        $this->ensureConfigured();

        $rows = $this->runDimensionReport(
            datePreset: $datePreset,
            dimensions: ['deviceCategory'],
            metrics: ['activeUsers', 'sessions', 'engagedSessions', 'eventCount'],
            limit: 20
        );

        return collect($rows)
            ->map(function (array $row) {
                $sessions = (int) ($row['metrics']['sessions'] ?? 0);
                $engagedSessions = (int) ($row['metrics']['engagedSessions'] ?? 0);

                return [
                    'device_category' => ucfirst($this->normalizeGaLabel($row['dimensions']['deviceCategory'] ?? '(not set)')),
                    'users' => (int) ($row['metrics']['activeUsers'] ?? 0),
                    'sessions' => $sessions,
                    'engaged_sessions' => $engagedSessions,
                    'engagement_rate' => $this->calculateRate($engagedSessions, $sessions),
                    'key_events' => (int) ($row['metrics']['eventCount'] ?? 0),
                ];
            })
            ->sortByDesc('sessions')
            ->take($limit)
            ->values()
            ->all();
    }

    public function locationBreakdown(string $datePreset = 'last_7d', int $limit = 10): array
    {
        $this->ensureConfigured();

        $rows = $this->runDimensionReport(
            datePreset: $datePreset,
            dimensions: ['city', 'region', 'country'],
            metrics: ['activeUsers', 'sessions', 'engagedSessions', 'eventCount'],
            limit: 100
        );

        return collect($rows)
            ->map(function (array $row) {
                $sessions = (int) ($row['metrics']['sessions'] ?? 0);
                $engagedSessions = (int) ($row['metrics']['engagedSessions'] ?? 0);

                return [
                    'city' => $this->normalizeGaLabel($row['dimensions']['city'] ?? '(not set)'),
                    'region' => $this->normalizeGaLabel($row['dimensions']['region'] ?? '(not set)'),
                    'country' => $this->normalizeGaLabel($row['dimensions']['country'] ?? '(not set)'),
                    'users' => (int) ($row['metrics']['activeUsers'] ?? 0),
                    'sessions' => $sessions,
                    'engaged_sessions' => $engagedSessions,
                    'engagement_rate' => $this->calculateRate($engagedSessions, $sessions),
                    'key_events' => (int) ($row['metrics']['eventCount'] ?? 0),
                ];
            })
            ->reject(fn (array $row) => $row['city'] === '(not set)' && $row['region'] === '(not set)' && $row['country'] === '(not set)')
            ->sortByDesc('sessions')
            ->take($limit)
            ->values()
            ->all();
    }

    public function conversionFunnel(
        string $datePreset = 'last_7d',
        ?int $sessions = null,
        ?int $engagedSessions = null,
        ?int $eventCount = null
    ): array {
        $this->ensureConfigured();

        if ($sessions === null || $engagedSessions === null || $eventCount === null) {
            $summary = $this->summary($datePreset);
            $metrics = $summary['metrics'] ?? [];

            $sessions = (int) ($metrics['sessions'] ?? 0);
            $engagedSessions = (int) ($metrics['engaged_sessions'] ?? 0);
            $eventCount = (int) ($metrics['event_count'] ?? 0);
        }

        $eventMap = $this->eventNameCounts($datePreset);

        $registerClick = $this->sumEventCounts($eventMap, [
            'click_register',
            'register_click',
            'click_daftar',
            'select_program',
        ]);

        $whatsappClick = $this->sumEventCounts($eventMap, [
            'click_whatsapp',
            'whatsapp_click',
            'wa_click',
            'click_wa',
        ]);

        $formSubmit = $this->sumEventCounts($eventMap, [
            'submit_workshop_form',
            'submit_webinar_form',
            'submit_trial_form',
            'submit_event_form',
            'form_submit',
            'generate_lead',
        ]);

        $paymentSuccess = $this->sumEventCounts($eventMap, [
            'payment_success',
            'purchase',
            'paid',
            'checkout_success',
        ]);

        return [
            [
                'key' => 'sessions',
                'label' => 'Sessions',
                'value' => max((int) $sessions, 0),
                'rate' => 100,
                'help' => 'Total traffic masuk.',
            ],
            [
                'key' => 'engaged_sessions',
                'label' => 'Engaged Sessions',
                'value' => max((int) $engagedSessions, 0),
                'rate' => $this->calculateRate($engagedSessions, max((int) $sessions, 0)),
                'help' => 'Traffic yang benar-benar engage.',
            ],
            [
                'key' => 'cta_click',
                'label' => 'CTA / WA Click',
                'value' => max($registerClick + $whatsappClick, 0),
                'rate' => $this->calculateRate($registerClick + $whatsappClick, max((int) $sessions, 0)),
                'help' => 'Klik daftar atau WhatsApp.',
            ],
            [
                'key' => 'form_submit',
                'label' => 'Form Submit',
                'value' => max($formSubmit, 0),
                'rate' => $this->calculateRate($formSubmit, max((int) $sessions, 0)),
                'help' => 'Submit form lead/workshop/webinar/event.',
            ],
            [
                'key' => 'payment_success',
                'label' => 'Payment / Success',
                'value' => max($paymentSuccess, 0),
                'rate' => $this->calculateRate($paymentSuccess, max((int) $sessions, 0)),
                'help' => 'Purchase/payment success jika tracking sudah aktif.',
            ],
        ];
    }

    protected function eventNameCounts(string $datePreset = 'last_7d'): array
    {
        $rows = $this->runDimensionReport(
            datePreset: $datePreset,
            dimensions: ['eventName'],
            metrics: ['eventCount'],
            limit: 200
        );

        return collect($rows)
            ->mapWithKeys(function (array $row) {
                $eventName = $this->normalizeGaLabel($row['dimensions']['eventName'] ?? '(not set)');

                return [
                    $eventName => (int) ($row['metrics']['eventCount'] ?? 0),
                ];
            })
            ->all();
    }

    protected function fallbackConversionFunnel(int $sessions, int $engagedSessions, int $eventCount): array
    {
        return [
            [
                'key' => 'sessions',
                'label' => 'Sessions',
                'value' => max($sessions, 0),
                'rate' => 100,
                'help' => 'Total traffic masuk.',
            ],
            [
                'key' => 'engaged_sessions',
                'label' => 'Engaged Sessions',
                'value' => max($engagedSessions, 0),
                'rate' => $this->calculateRate($engagedSessions, max($sessions, 0)),
                'help' => 'Traffic yang benar-benar engage.',
            ],
            [
                'key' => 'event_count',
                'label' => 'Event Count',
                'value' => max($eventCount, 0),
                'rate' => $this->calculateRate($eventCount, max($sessions, 0)),
                'help' => 'Total event yang terekam GA4.',
            ],
        ];
    }

    protected function runDimensionReport(
        string $datePreset,
        array $dimensions,
        array $metrics,
        int $limit = 10
    ): array {
        $dateRange = $this->resolveDateRange($datePreset);
        $client = $this->makeClient();

        $request = (new RunReportRequest())
            ->setProperty($this->propertyName())
            ->setDateRanges([
                new DateRange([
                    'start_date' => $dateRange['date_start'],
                    'end_date' => $dateRange['date_stop'],
                ]),
            ])
            ->setDimensions(
                collect($dimensions)
                    ->map(fn (string $dimension) => new Dimension(['name' => $dimension]))
                    ->values()
                    ->all()
            )
            ->setMetrics(
                collect($metrics)
                    ->map(fn (string $metric) => new Metric(['name' => $metric]))
                    ->values()
                    ->all()
            )
            ->setLimit($limit);

        $response = $client->runReport($request);

        return collect($response->getRows())
            ->map(function ($row) use ($dimensions, $metrics) {
                $dimensionValues = collect($row->getDimensionValues())
                    ->map(fn ($dimensionValue) => (string) $dimensionValue->getValue())
                    ->values();

                $metricValues = collect($row->getMetricValues())
                    ->map(fn ($metricValue) => (float) $metricValue->getValue())
                    ->values();

                $mappedDimensions = [];
                foreach (array_values($dimensions) as $index => $dimension) {
                    $mappedDimensions[$dimension] = $dimensionValues[$index] ?? null;
                }

                $mappedMetrics = [];
                foreach (array_values($metrics) as $index => $metric) {
                    $mappedMetrics[$metric] = $metricValues[$index] ?? 0;
                }

                return [
                    'dimensions' => $mappedDimensions,
                    'metrics' => $mappedMetrics,
                ];
            })
            ->values()
            ->all();
    }

    private function makeClient(): BetaAnalyticsDataClient
    {
        return new BetaAnalyticsDataClient([
            'credentials' => $this->credentialsPath(),
        ]);
    }

    private function ensureConfigured(): void
    {
        if (! config('services.google_analytics.enabled')) {
            throw new RuntimeException('Google Analytics integration is disabled.');
        }

        if (blank(config('services.google_analytics.property_id'))) {
            throw new RuntimeException('Google Analytics property ID is missing.');
        }

        if (! file_exists($this->credentialsPath())) {
            throw new RuntimeException('Google Analytics credential JSON file was not found.');
        }
    }

    private function propertyName(): string
    {
        return 'properties/' . config('services.google_analytics.property_id');
    }

    private function credentialsPath(): string
    {
        return config('services.google_analytics.credentials_path');
    }

    private function resolveDateRange(string $datePreset): array
    {
        return match ($datePreset) {
            'today' => [
                'date_start' => 'today',
                'date_stop' => 'today',
            ],

            'yesterday' => [
                'date_start' => 'yesterday',
                'date_stop' => 'yesterday',
            ],

            'last_30d' => [
                'date_start' => '30daysAgo',
                'date_stop' => 'today',
            ],

            'last_90d' => [
                'date_start' => '90daysAgo',
                'date_stop' => 'today',
            ],

            default => [
                'date_start' => '7daysAgo',
                'date_stop' => 'today',
            ],
        };
    }

    private function safeSection(callable $callback, array $fallback): array
    {
        try {
            return $callback();
        } catch (Throwable) {
            return $fallback;
        }
    }

    private function emptySummaryMetrics(): array
    {
        return [
            'active_users' => 0,
            'new_users' => 0,
            'sessions' => 0,
            'engaged_sessions' => 0,
            'screen_page_views' => 0,
            'event_count' => 0,
            'user_engagement_duration' => 0,
            'average_engagement_time_seconds' => 0,
            'engagement_rate' => 0,
        ];
    }

    private function calculateRate(int|float $value, int|float $total): float
    {
        $total = (float) $total;

        if ($total <= 0) {
            return 0;
        }

        return round(((float) $value / $total) * 100, 1);
    }

    private function normalizeGaLabel(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '(not set)';
        }

        return $value;
    }

    private function normalizeGaPath(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '' || $value === '(not set)') {
            return '/';
        }

        return str_starts_with($value, '/')
            ? $value
            : '/' . $value;
    }

    private function sumEventCounts(array $eventMap, array $names): int
    {
        return collect($names)
            ->sum(function (string $name) use ($eventMap) {
                return (int) ($eventMap[$name] ?? 0);
            });
    }

    private function formatDuration(float|int $seconds): string
    {
        $seconds = max((int) round($seconds), 0);

        if ($seconds < 60) {
            return $seconds . 's';
        }

        $minutes = intdiv($seconds, 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes < 60) {
            return $remainingSeconds > 0
                ? $minutes . 'm ' . $remainingSeconds . 's'
                : $minutes . 'm';
        }

        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        return $remainingMinutes > 0
            ? $hours . 'h ' . $remainingMinutes . 'm'
            : $hours . 'h';
    }

    private function buildSummaryText(
        int $activeUsers,
        int $sessions,
        int $engagedSessions,
        float $engagementRate,
        int $eventCount
    ): string {
        if ($sessions <= 0) {
            return 'Google Analytics sudah tersambung, tetapi belum ada traffic website pada periode ini.';
        }

        if ($engagementRate < 20) {
            return "Traffic website sudah masuk dengan {$sessions} sessions dan {$activeUsers} active users, tetapi engagement rate masih rendah di {$engagementRate}%. Prioritas berikutnya adalah cek kualitas traffic, relevansi landing page, dan CTA utama.";
        }

        if ($engagementRate >= 50) {
            return "Traffic website terlihat cukup sehat dengan {$sessions} sessions, {$activeUsers} active users, dan engagement rate {$engagementRate}%. Selanjutnya perlu dilihat channel, landing page, dan event mana yang paling berkontribusi.";
        }

        return "Google Analytics mencatat {$sessions} sessions, {$activeUsers} active users, {$engagedSessions} engaged sessions, dan {$eventCount} total events. Performa traffic sudah terbaca, berikutnya perlu breakdown channel dan landing page untuk keputusan optimasi.";
    }
}