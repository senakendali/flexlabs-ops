<?php

namespace App\Services\Dashboard;

use App\Models\GoogleAdsDashboardSnapshot;
use App\Models\GoogleAnalyticsDashboardSnapshot;
use App\Services\Trello\TrelloDashboardStatsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class MarketingDashboardService
{
    public function __construct(
        private readonly TrelloDashboardStatsService $trelloDashboardStatsService
    ) {
    }

    public function getData(): array
    {
        /*
        |--------------------------------------------------------------------------
        | Marketing API Performance Sources
        |--------------------------------------------------------------------------
        | Dashboard tidak hit API eksternal saat page load.
        | Data dibaca dari snapshot/sync lokal:
        | - Meta Ads
        | - Google Analytics
        | - Google Ads
        | - Trello Marketing
        | - Trello SEI
        |--------------------------------------------------------------------------
        */
        $metaAdsDashboardInsight = $this->getMetaAdsDashboardInsight();
        $googleAnalyticsDashboardInsight = $this->getGoogleAnalyticsDashboardInsight();
        $googleAdsDashboardInsight = $this->getGoogleAdsDashboardInsight();

        $trelloMarketingStats = $this->getTrelloMarketingStats();
        $trelloSeiStats = $this->getTrelloSeiStats();

        $trelloDashboardStats = [
            'marketing' => $trelloMarketingStats,
            'sei' => $trelloSeiStats,
        ];

        $platformStatuses = $this->buildPlatformStatuses(
            metaAds: $metaAdsDashboardInsight,
            googleAnalytics: $googleAnalyticsDashboardInsight,
            googleAds: $googleAdsDashboardInsight,
            trelloMarketing: $trelloMarketingStats,
            trelloSei: $trelloSeiStats
        );

        $marketingOverview = $this->buildMarketingOverview(
            metaAds: $metaAdsDashboardInsight,
            googleAnalytics: $googleAnalyticsDashboardInsight,
            googleAds: $googleAdsDashboardInsight,
            trelloMarketing: $trelloMarketingStats,
            trelloSei: $trelloSeiStats,
            platformStatuses: $platformStatuses
        );

        $marketingSummaryContext = [
            'meta_ads_dashboard_insight' => $metaAdsDashboardInsight,
            'google_analytics_dashboard_insight' => $googleAnalyticsDashboardInsight,
            'google_ads_dashboard_insight' => $googleAdsDashboardInsight,
            'trello_marketing_stats' => $trelloMarketingStats,
            'trello_sei_stats' => $trelloSeiStats,
            'platform_statuses' => $platformStatuses,
            'marketing_overview' => $marketingOverview,
        ];

        $marketingAiRecommendations = $this->buildMarketingAiRecommendations(
            metaAds: $metaAdsDashboardInsight,
            googleAnalytics: $googleAnalyticsDashboardInsight,
            googleAds: $googleAdsDashboardInsight,
            trelloMarketing: $trelloMarketingStats,
            trelloSei: $trelloSeiStats
        );

        $marketingSummaryContext['ai_recommendations'] = $marketingAiRecommendations;

        $marketingSummary = $this->buildMarketingSummary($marketingSummaryContext);
        $marketingSummary['ai_recommendations'] = $marketingAiRecommendations;

        $marketingAiSummaryText = (string) (
            $marketingAiRecommendations['executive_summary']
            ?? $marketingSummary['summary_text']
            ?? ''
        );

        return compact(
            'metaAdsDashboardInsight',
            'googleAnalyticsDashboardInsight',
            'googleAdsDashboardInsight',
            'trelloMarketingStats',
            'trelloSeiStats',
            'trelloDashboardStats',
            'platformStatuses',
            'marketingOverview',
            'marketingSummaryContext',
            'marketingSummary',
            'marketingAiSummaryText',
            'marketingAiRecommendations'
        );
    }

    protected function getTrelloMarketingStats(): array
    {
        return $this->getTrelloStats(
            sourceKey: 'marketing',
            department: 'marketing',
            label: 'Marketing'
        );
    }

    protected function getTrelloSeiStats(): array
    {
        return $this->getTrelloStats(
            sourceKey: 'sei',
            department: 'sei',
            label: 'SEI'
        );
    }

    protected function getTrelloStats(
        string $sourceKey,
        string $department,
        string $label
    ): array {
        try {
            return $this->trelloDashboardStatsService->getStats($sourceKey);
        } catch (Throwable $exception) {
            Log::error("Failed to fetch {$label} Trello dashboard stats.", [
                'source_key' => $sourceKey,
                'message' => $exception->getMessage(),
            ]);

            return $this->emptyTrelloDashboardStats(
                sourceKey: $sourceKey,
                department: $department,
                label: $label,
                insight: "Data Trello {$label} belum bisa ditarik. Dashboard tetap aman, tetapi koneksi, scheduler, webhook, atau mapping list perlu dicek."
            );
        }
    }

    protected function emptyTrelloDashboardStats(
        string $sourceKey,
        string $department,
        string $label,
        ?string $insight = null
    ): array {
        return [
            'source_key' => $sourceKey,
            'integration_name' => null,
            'department' => $department,
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

            'insight' => $insight ?: "Trello {$label} belum aktif atau belum ditemukan.",
        ];
    }

    protected function buildPlatformStatuses(
        array $metaAds,
        array $googleAnalytics,
        array $googleAds,
        array $trelloMarketing,
        array $trelloSei
    ): array {
        $resolveTrelloStatus = function (array $stats): array {
            $webhookStatus = strtolower((string) ($stats['webhook_status'] ?? 'inactive'));

            $isAvailable = filled($stats['board_id'] ?? null)
                || filled($stats['board_name'] ?? null)
                || in_array($webhookStatus, ['active', 'synced'], true);

            return [
                'is_available' => $isAvailable,
                'status' => $isAvailable ? 'synced' : 'not_synced',
                'last_synced_at' => $stats['last_synced_at'] ?? null,
                'webhook_status' => $webhookStatus,
            ];
        };

        $marketingTrelloStatus = $resolveTrelloStatus($trelloMarketing);
        $seiTrelloStatus = $resolveTrelloStatus($trelloSei);

        return [
            'meta_ads' => [
                'key' => 'meta_ads',
                'label' => 'Meta Ads',
                'is_available' => (bool) ($metaAds['is_available'] ?? false),
                'status' => (bool) ($metaAds['is_available'] ?? false) ? 'synced' : 'not_synced',
                'last_synced_at' => $metaAds['last_synced_at'] ?? null,
                'summary_text' => $metaAds['summary_text'] ?? 'Data Meta Ads belum tersedia.',
                'error_message' => $metaAds['error_message'] ?? null,
            ],
            'google_analytics' => [
                'key' => 'google_analytics',
                'label' => 'Google Analytics',
                'is_available' => (bool) ($googleAnalytics['is_available'] ?? false),
                'status' => (bool) ($googleAnalytics['is_available'] ?? false) ? 'synced' : 'not_synced',
                'last_synced_at' => $googleAnalytics['last_synced_at'] ?? null,
                'summary_text' => $googleAnalytics['summary_text'] ?? 'Data Google Analytics belum tersedia.',
                'error_message' => $googleAnalytics['error_message'] ?? null,
            ],
            'google_ads' => [
                'key' => 'google_ads',
                'label' => 'Google Ads',
                'is_available' => (bool) ($googleAds['is_available'] ?? false),
                'status' => (bool) ($googleAds['is_available'] ?? false) ? 'synced' : 'not_synced',
                'last_synced_at' => $googleAds['last_synced_at'] ?? null,
                'summary_text' => $googleAds['summary_text'] ?? 'Data Google Ads belum tersedia.',
                'error_message' => $googleAds['error_message'] ?? null,
            ],
            'trello_marketing' => [
                'key' => 'trello_marketing',
                'label' => 'Trello Marketing',
                'is_available' => $marketingTrelloStatus['is_available'],
                'status' => $marketingTrelloStatus['status'],
                'last_synced_at' => $marketingTrelloStatus['last_synced_at'],
                'summary_text' => $trelloMarketing['insight'] ?? 'Data Trello Marketing belum tersedia.',
                'error_message' => null,
                'webhook_status' => $marketingTrelloStatus['webhook_status'],
            ],
            'trello_sei' => [
                'key' => 'trello_sei',
                'label' => 'Trello SEI',
                'is_available' => $seiTrelloStatus['is_available'],
                'status' => $seiTrelloStatus['status'],
                'last_synced_at' => $seiTrelloStatus['last_synced_at'],
                'summary_text' => $trelloSei['insight'] ?? 'Data Trello SEI belum tersedia.',
                'error_message' => null,
                'webhook_status' => $seiTrelloStatus['webhook_status'],
            ],
        ];
    }

    protected function buildMarketingOverview(
        array $metaAds,
        array $googleAnalytics,
        array $googleAds,
        array $trelloMarketing,
        array $trelloSei,
        array $platformStatuses
    ): array {
        $metaOverview = $metaAds['overview'] ?? [];
        $googleAnalyticsKpis = $googleAnalytics['kpis'] ?? [];
        $googleAdsOverview = $googleAds['overview'] ?? [];

        $trelloMarketingSummary = $trelloMarketing['summary'] ?? [];
        $trelloSeiSummary = $trelloSei['summary'] ?? [];

        $metaConversions = (int) ($metaOverview['total_lead_form_submission'] ?? 0)
            + (int) ($metaOverview['total_whatsapp_chat'] ?? 0);

        $googleAdsConversions = (float) ($googleAdsOverview['total_conversions'] ?? 0);

        $availablePlatforms = collect($platformStatuses)
            ->filter(fn (array $platform) => (bool) ($platform['is_available'] ?? false))
            ->count();

        $attentionPlatforms = 0;

        if (
            ((int) ($metaOverview['critical_count'] ?? 0)) > 0
            || ((int) ($metaOverview['attention_count'] ?? 0)) > 0
            || (
                ((float) ($metaOverview['total_spend'] ?? 0)) > 0
                && $metaConversions <= 0
            )
        ) {
            $attentionPlatforms++;
        }

        $engagementRate = (float) ($googleAnalyticsKpis['engagement_rate'] ?? 0);
        $sessions = (int) ($googleAnalyticsKpis['sessions'] ?? 0);
        $keyEvents = (int) ($googleAnalyticsKpis['key_events'] ?? 0);

        if (
            (bool) ($googleAnalytics['is_available'] ?? false)
            && (
                $sessions <= 0
                || ($engagementRate > 0 && $engagementRate < 50)
                || ($sessions > 0 && $keyEvents <= 0)
            )
        ) {
            $attentionPlatforms++;
        }

        if (
            ((int) ($googleAdsOverview['critical_count'] ?? 0)) > 0
            || ((int) ($googleAdsOverview['attention_count'] ?? 0)) > 0
            || (
                ((float) ($googleAdsOverview['total_cost'] ?? 0)) > 0
                && $googleAdsConversions <= 0
            )
        ) {
            $attentionPlatforms++;
        }

        foreach ([$trelloMarketing, $trelloSei] as $trelloStats) {
            $summary = $trelloStats['summary'] ?? [];

            if (
                ! in_array(
                    strtolower((string) ($trelloStats['webhook_status'] ?? 'inactive')),
                    ['active', 'synced'],
                    true
                )
                || ((int) ($summary['overdue'] ?? 0)) > 0
                || ((int) ($summary['unmapped'] ?? 0)) > 0
            ) {
                $attentionPlatforms++;
            }
        }

        $trelloOpenCards = (int) ($trelloMarketingSummary['total_open_cards'] ?? 0)
            + (int) ($trelloSeiSummary['total_open_cards'] ?? 0);

        $trelloActiveWork = (int) ($trelloMarketingSummary['active_work'] ?? 0)
            + (int) ($trelloSeiSummary['active_work'] ?? 0);

        $trelloDueToday = (int) ($trelloMarketingSummary['due_today'] ?? 0)
            + (int) ($trelloSeiSummary['due_today'] ?? 0);

        $trelloOverdue = (int) ($trelloMarketingSummary['overdue'] ?? 0)
            + (int) ($trelloSeiSummary['overdue'] ?? 0);

        $trelloCompleted = (int) ($trelloMarketingSummary['completed'] ?? 0)
            + (int) ($trelloSeiSummary['completed'] ?? 0);

        $trelloCompletionRate = $trelloOpenCards > 0
            ? (int) round(($trelloCompleted / $trelloOpenCards) * 100)
            : 0;

        return [
            'platform_count' => count($platformStatuses),
            'available_platforms' => $availablePlatforms,
            'attention_platforms' => $attentionPlatforms,

            'total_ad_spend' => (float) ($metaOverview['total_spend'] ?? 0)
                + (float) ($googleAdsOverview['total_cost'] ?? 0),

            'total_ad_spend_label' => $this->formatMetaAdsCurrency(
                (float) ($metaOverview['total_spend'] ?? 0)
                + (float) ($googleAdsOverview['total_cost'] ?? 0)
            ),

            'total_paid_conversions' => $metaConversions + $googleAdsConversions,
            'meta_conversions' => $metaConversions,
            'google_ads_conversions' => $googleAdsConversions,

            'website_sessions' => $sessions,
            'website_users' => (int) ($googleAnalyticsKpis['total_users'] ?? 0),
            'website_engagement_rate' => $engagementRate,
            'website_key_events' => $keyEvents,

            'trello_open_cards' => $trelloOpenCards,
            'trello_active_work' => $trelloActiveWork,
            'trello_due_today' => $trelloDueToday,
            'trello_overdue' => $trelloOverdue,
            'trello_completion_rate' => $trelloCompletionRate,

            'trello_marketing_open_cards' => (int) ($trelloMarketingSummary['total_open_cards'] ?? 0),
            'trello_marketing_overdue' => (int) ($trelloMarketingSummary['overdue'] ?? 0),
            'trello_sei_open_cards' => (int) ($trelloSeiSummary['total_open_cards'] ?? 0),
            'trello_sei_overdue' => (int) ($trelloSeiSummary['overdue'] ?? 0),
        ];
    }

    protected function buildMarketingAiRecommendations(
        array $metaAds,
        array $googleAnalytics,
        array $googleAds,
        array $trelloMarketing,
        array $trelloSei
    ): array {
        $platforms = [
            'meta_ads' => $this->buildMetaAdsAiRecommendation($metaAds),
            'google_analytics' => $this->normalizePlatformAiRecommendation(
                platformKey: 'google_analytics',
                platformLabel: 'Google Analytics',
                payload: is_array($googleAnalytics['ai_summary'] ?? null)
                    ? $googleAnalytics['ai_summary']
                    : [],
                fallbackSummary: (string) ($googleAnalytics['summary_text'] ?? ''),
                isAvailable: (bool) ($googleAnalytics['is_available'] ?? false)
            ),
            'google_ads' => $this->normalizePlatformAiRecommendation(
                platformKey: 'google_ads',
                platformLabel: 'Google Ads',
                payload: is_array($googleAds['ai_summary'] ?? null)
                    ? $googleAds['ai_summary']
                    : [],
                fallbackSummary: (string) ($googleAds['summary_text'] ?? ''),
                isAvailable: (bool) ($googleAds['is_available'] ?? false)
            ),
            'trello_marketing' => $this->buildTrelloAiRecommendation(
                $trelloMarketing,
                'trello_marketing',
                'Trello Marketing',
                'Marketing'
            ),
            'trello_sei' => $this->buildTrelloAiRecommendation(
                $trelloSei,
                'trello_sei',
                'Trello SEI',
                'SEI'
            ),
        ];

        $blockingFactors = collect($platforms)
            ->flatMap(function (array $platform) {
                return collect($platform['blocking_factors'] ?? [])
                    ->map(function (array $factor) use ($platform) {
                        return array_merge($factor, [
                            'platform_key' => $platform['platform_key'],
                            'platform_label' => $platform['platform_label'],
                        ]);
                    });
            })
            ->sortByDesc(fn (array $factor) => $this->aiSeverityScore($factor['severity'] ?? 'low'))
            ->values();

        $recommendedSteps = collect($platforms)
            ->flatMap(function (array $platform) {
                return collect($platform['recommended_steps'] ?? [])
                    ->map(function ($step) use ($platform) {
                        $text = is_array($step)
                            ? ($step['action'] ?? $step['step'] ?? $step['recommendation'] ?? $step['text'] ?? null)
                            : $step;

                        if (blank($text)) {
                            return null;
                        }

                        return [
                            'platform_key' => $platform['platform_key'],
                            'platform_label' => $platform['platform_label'],
                            'action' => trim((string) $text),
                        ];
                    });
            })
            ->filter()
            ->unique(fn (array $item) => Str::lower($item['platform_key'] . '|' . $item['action']))
            ->values();

        $priorityActions = collect($platforms)
            ->flatMap(fn (array $platform) => $platform['priority_actions'] ?? [])
            ->filter(fn ($item) => is_array($item) && filled($item['action'] ?? null))
            ->sortByDesc(fn (array $item) => (int) ($item['score'] ?? 0))
            ->values();

        if ($priorityActions->isEmpty()) {
            $priorityActions = $recommendedSteps
                ->take(8)
                ->values()
                ->map(function (array $item, int $index) {
                    return array_merge($item, [
                        'priority' => $index <= 1 ? 'high' : 'medium',
                        'reason' => 'Rekomendasi dari analisis platform.',
                        'score' => 700 - ($index * 10),
                    ]);
                });
        }

        $platformPriority = collect($platforms)
            ->sortByDesc(function (array $platform) {
                $severity = $platform['severity'] ?? 'info';
                $factorScore = collect($platform['blocking_factors'] ?? [])
                    ->max(fn (array $factor) => $this->aiSeverityScore($factor['severity'] ?? 'low')) ?? 0;

                return $this->aiSeverityScore($severity) + $factorScore;
            })
            ->values();

        $mainPlatform = $platformPriority->first() ?? [];
        $mainBottleneck = trim((string) ($mainPlatform['main_bottleneck'] ?? ''));

        if ($mainBottleneck === '') {
            $mainBottleneck = (string) (
                $blockingFactors->first()['factor']
                ?? 'Belum ada bottleneck utama yang terdeteksi dari data terbaru.'
            );
        }

        $summaryParagraphs = $platformPriority
            ->map(fn (array $platform) => trim((string) ($platform['summary'] ?? '')))
            ->filter()
            ->take(4)
            ->values()
            ->all();

        $executiveSummary = $this->joinSummaryParagraphs($summaryParagraphs);

        if (blank($executiveSummary)) {
            $executiveSummary = 'AI Marketing insight belum tersedia karena snapshot atau hasil analisis platform masih kosong.';
        }

        return [
            'generated_at' => now()->format('d M Y H:i'),
            'source' => 'platform_ai_and_local_fallback',
            'executive_summary' => $executiveSummary,
            'main_bottleneck' => $mainBottleneck,
            'blocking_factors' => $blockingFactors->take(12)->all(),
            'recommended_steps' => $recommendedSteps->take(16)->all(),
            'priority_actions' => $priorityActions->take(10)->all(),
            'platforms' => $platforms,
        ];
    }


    protected function buildMetaAdsAiRecommendation(array $metaAds): array
    {
        $campaigns = collect($metaAds['campaigns'] ?? []);

        $campaignAnalyses = $campaigns
            ->map(function (array $campaign) {
                $ai = is_array($campaign['ai_summary'] ?? null)
                    ? $campaign['ai_summary']
                    : [];

                return [
                    'campaign_name' => $campaign['campaign_name'] ?? 'Untitled Campaign',
                    'health_type' => $campaign['health_type'] ?? 'info',
                    'summary' => $ai['summary'] ?? null,
                    'main_bottleneck' => $ai['main_bottleneck'] ?? null,
                    'blocking_factors' => is_array($ai['blocking_factors'] ?? null)
                        ? $ai['blocking_factors']
                        : [],
                    'recommended_steps' => is_array($ai['recommended_steps'] ?? null)
                        ? $ai['recommended_steps']
                        : [],
                ];
            })
            ->filter(fn (array $item) => filled($item['summary'])
                || filled($item['main_bottleneck'])
                || ! empty($item['blocking_factors'])
                || ! empty($item['recommended_steps']))
            ->values();

        $blockingFactors = $campaignAnalyses
            ->flatMap(function (array $analysis) {
                return collect($analysis['blocking_factors'])
                    ->map(function ($factor) use ($analysis) {
                        if (is_string($factor)) {
                            return [
                                'factor' => $factor,
                                'evidence' => null,
                                'severity' => $analysis['health_type'] === 'critical' ? 'high' : 'medium',
                                'campaign_name' => $analysis['campaign_name'],
                            ];
                        }

                        if (! is_array($factor)) {
                            return null;
                        }

                        return [
                            'factor' => $factor['factor']
                                ?? $factor['issue']
                                ?? $factor['title']
                                ?? 'Faktor penghambat',
                            'evidence' => $factor['evidence']
                                ?? $factor['description']
                                ?? $factor['detail']
                                ?? null,
                            'severity' => $factor['severity']
                                ?? ($analysis['health_type'] === 'critical' ? 'high' : 'medium'),
                            'campaign_name' => $analysis['campaign_name'],
                        ];
                    });
            })
            ->filter()
            ->sortByDesc(fn (array $factor) => $this->aiSeverityScore($factor['severity'] ?? 'low'))
            ->values();

        $recommendedSteps = $campaignAnalyses
            ->flatMap(fn (array $analysis) => collect($analysis['recommended_steps']))
            ->map(function ($step) {
                return is_array($step)
                    ? ($step['action'] ?? $step['step'] ?? $step['recommendation'] ?? $step['text'] ?? null)
                    : $step;
            })
            ->filter()
            ->map(fn ($step) => trim((string) $step))
            ->unique(fn (string $step) => Str::lower($step))
            ->values();

        $priorityActions = $campaignAnalyses
            ->flatMap(function (array $analysis) {
                $severity = $analysis['health_type'] === 'critical'
                    ? 'high'
                    : ($analysis['health_type'] === 'warning' ? 'medium' : 'low');

                return collect($analysis['recommended_steps'])
                    ->map(function ($step, int $index) use ($analysis, $severity) {
                        $text = is_array($step)
                            ? ($step['action'] ?? $step['step'] ?? $step['recommendation'] ?? $step['text'] ?? null)
                            : $step;

                        if (blank($text)) {
                            return null;
                        }

                        return [
                            'platform_key' => 'meta_ads',
                            'platform_label' => 'Meta Ads',
                            'campaign_name' => $analysis['campaign_name'],
                            'action' => trim((string) $text),
                            'priority' => $severity,
                            'reason' => $analysis['main_bottleneck']
                                ?: 'Rekomendasi AI berdasarkan performa campaign.',
                            'score' => $this->aiSeverityScore($severity) + max(0, 40 - ($index * 5)),
                        ];
                    });
            })
            ->filter()
            ->values();

        $mainAnalysis = $campaignAnalyses
            ->sortByDesc(fn (array $item) => $this->aiSeverityScore($item['health_type'] ?? 'info'))
            ->first();

        return [
            'platform_key' => 'meta_ads',
            'platform_label' => 'Meta Ads',
            'is_available' => (bool) ($metaAds['is_available'] ?? false),
            'severity' => $mainAnalysis['health_type'] ?? (
                (int) data_get($metaAds, 'overview.critical_count', 0) > 0
                    ? 'critical'
                    : ((int) data_get($metaAds, 'overview.attention_count', 0) > 0 ? 'warning' : 'good')
            ),
            'summary' => $mainAnalysis['summary']
                ?? $metaAds['summary_text']
                ?? 'Meta Ads insight belum tersedia.',
            'main_bottleneck' => $mainAnalysis['main_bottleneck']
                ?? $blockingFactors->first()['factor']
                ?? 'Belum ada bottleneck utama pada Meta Ads.',
            'blocking_factors' => $blockingFactors->take(8)->all(),
            'recommended_steps' => $recommendedSteps->take(10)->all(),
            'priority_actions' => $priorityActions->take(8)->all(),
            'campaign_analyses' => $campaignAnalyses->all(),
        ];
    }


    protected function normalizePlatformAiRecommendation(
        string $platformKey,
        string $platformLabel,
        array $payload,
        string $fallbackSummary,
        bool $isAvailable
    ): array {
        $summary = $this->firstFilledAiValue($payload, [
            'executive_summary',
            'summary',
            'summary_text',
            'overview',
            'performance_summary',
            'analysis_summary',
        ]) ?: $fallbackSummary;

        $mainBottleneck = $this->firstFilledAiValue($payload, [
            'main_bottleneck',
            'bottleneck',
            'biggest_bottleneck',
            'main_issue',
            'biggest_risk',
            'primary_issue',
            'key_problem',
        ]);

        $blockingFactors = $this->normalizeAiFactors(
            $this->firstArrayAiValue($payload, [
                'blocking_factors',
                'bottlenecks',
                'issues',
                'risks',
                'key_findings',
                'problems',
                'weaknesses',
            ])
        );

        $recommendedSteps = $this->normalizeAiSteps(
            $this->firstArrayAiValue($payload, [
                'recommended_steps',
                'recommended_actions',
                'recommendations',
                'next_actions',
                'action_plan',
                'actions',
                'optimization_actions',
            ])
        );

        if (blank($mainBottleneck)) {
            $mainBottleneck = $blockingFactors[0]['factor'] ?? null;
        }

        $severity = $this->firstFilledAiValue($payload, [
            'severity',
            'health_type',
            'status',
            'health',
        ]) ?: ($isAvailable ? 'info' : 'warning');

        $priorityActions = collect($recommendedSteps)
            ->map(function (string $step, int $index) use (
                $platformKey,
                $platformLabel,
                $mainBottleneck,
                $severity
            ) {
                $normalizedPriority = in_array(Str::lower((string) $severity), ['critical', 'danger', 'high'], true)
                    ? 'high'
                    : (in_array(Str::lower((string) $severity), ['warning', 'attention', 'medium'], true)
                        ? 'medium'
                        : 'low');

                return [
                    'platform_key' => $platformKey,
                    'platform_label' => $platformLabel,
                    'action' => $step,
                    'priority' => $normalizedPriority,
                    'reason' => $mainBottleneck ?: 'Rekomendasi AI berdasarkan data platform.',
                    'score' => $this->aiSeverityScore($normalizedPriority) + max(0, 40 - ($index * 5)),
                ];
            })
            ->values()
            ->all();

        return [
            'platform_key' => $platformKey,
            'platform_label' => $platformLabel,
            'is_available' => $isAvailable,
            'severity' => Str::lower((string) $severity),
            'summary' => $summary !== '' ? $summary : $platformLabel . ' insight belum tersedia.',
            'main_bottleneck' => $mainBottleneck ?: 'Belum ada bottleneck utama yang tertulis.',
            'blocking_factors' => $blockingFactors,
            'recommended_steps' => $recommendedSteps,
            'priority_actions' => $priorityActions,
            'raw_ai_payload' => $payload,
        ];
    }


    protected function buildTrelloAiRecommendation(
        array $trelloStats,
        string $platformKey,
        string $platformLabel,
        string $teamLabel
    ): array {
        $summary = $trelloStats['summary'] ?? [];
        $overdue = (int) ($summary['overdue'] ?? 0);
        $dueToday = (int) ($summary['due_today'] ?? 0);
        $unmapped = (int) ($summary['unmapped'] ?? 0);
        $activeWork = (int) ($summary['active_work'] ?? 0);
        $completionRate = (int) ($summary['completion_rate'] ?? 0);
        $webhookStatus = Str::lower((string) ($trelloStats['webhook_status'] ?? 'inactive'));

        $isSynced = in_array($webhookStatus, ['active', 'synced'], true);

        $factors = [];
        $steps = [];
        $priorityActions = [];

        if (! $isSynced) {
            $factors[] = [
                'factor' => $platformLabel . ' belum tersinkronisasi',
                'evidence' => 'Webhook status saat ini: ' . ($webhookStatus ?: 'inactive') . '.',
                'severity' => 'high',
            ];

            $steps[] = 'Periksa scheduler, webhook, token, dan koneksi board ' . $platformLabel . '.';
        }

        if ($unmapped > 0) {
            $factors[] = [
                'factor' => 'Masih ada card tanpa mapping status',
                'evidence' => number_format($unmapped) . ' card belum masuk status dashboard.',
                'severity' => 'high',
            ];

            $steps[] = 'Selesaikan mapping list ' . $platformLabel . ' agar seluruh workload terbaca dengan benar.';
        }

        if ($overdue > 0) {
            $factors[] = [
                'factor' => $teamLabel . ' task overdue',
                'evidence' => number_format($overdue) . ' card sudah melewati deadline.',
                'severity' => 'high',
            ];

            $steps[] = 'Review card overdue, tetapkan PIC, dan reschedule deadline yang masih realistis.';
        }

        if ($dueToday > 0) {
            $factors[] = [
                'factor' => 'Ada task yang jatuh tempo hari ini',
                'evidence' => number_format($dueToday) . ' card perlu diselesaikan atau dikonfirmasi hari ini.',
                'severity' => 'medium',
            ];

            $steps[] = 'Prioritaskan card due today sebelum membuka pekerjaan baru.';
        }

        if ($activeWork > 0 && $completionRate < 50) {
            $factors[] = [
                'factor' => 'Work in progress relatif tinggi',
                'evidence' => number_format($activeWork)
                    . ' card aktif dengan completion rate '
                    . number_format($completionRate)
                    . '%.',
                'severity' => 'medium',
            ];

            $steps[] = 'Batasi work in progress dan dorong task mendekati selesai sebelum menambah task baru.';
        }

        if (empty($factors)) {
            $factors[] = [
                'factor' => 'Belum ada bottleneck workload yang berat',
                'evidence' => 'Workload ' . $teamLabel . ' tidak memiliki overdue atau mapping issue yang signifikan.',
                'severity' => 'low',
            ];
        }

        if (empty($steps)) {
            $steps[] = 'Pertahankan disiplin update status, PIC, dan deadline pada setiap card ' . $teamLabel . '.';
        }

        foreach ($steps as $index => $step) {
            $priority = $index <= 1 && ($overdue > 0 || $unmapped > 0 || ! $isSynced)
                ? 'high'
                : 'medium';

            $priorityActions[] = [
                'platform_key' => $platformKey,
                'platform_label' => $platformLabel,
                'action' => $step,
                'priority' => $priority,
                'reason' => $factors[0]['factor'] ?? ($teamLabel . ' workload action.'),
                'score' => $this->aiSeverityScore($priority) + max(0, 40 - ($index * 5)),
            ];
        }

        return [
            'platform_key' => $platformKey,
            'platform_label' => $platformLabel,
            'is_available' => $isSynced,
            'severity' => ($overdue > 0 || $unmapped > 0 || ! $isSynced)
                ? 'critical'
                : ($dueToday > 0 ? 'warning' : 'good'),
            'summary' => (string) (
                $trelloStats['insight']
                ?? ($teamLabel . ' workload tercatat dari Trello.')
            ),
            'main_bottleneck' => $factors[0]['factor'] ?? 'Belum ada bottleneck utama.',
            'blocking_factors' => $factors,
            'recommended_steps' => array_values(array_unique($steps)),
            'priority_actions' => $priorityActions,
        ];
    }


    protected function firstFilledAiValue(array $payload, array $keys): string
    {
        foreach ($keys as $key) {
            $value = data_get($payload, $key);

            if (is_scalar($value) && filled((string) $value)) {
                return trim((string) $value);
            }
        }

        return '';
    }


    protected function firstArrayAiValue(array $payload, array $keys): array
    {
        foreach ($keys as $key) {
            $value = data_get($payload, $key);

            if (is_array($value) && ! empty($value)) {
                return $value;
            }
        }

        return [];
    }


    protected function normalizeAiFactors(array $items): array
    {
        return collect($items)
            ->map(function ($item) {
                if (is_string($item)) {
                    return [
                        'factor' => trim($item),
                        'evidence' => null,
                        'severity' => 'medium',
                    ];
                }

                if (! is_array($item)) {
                    return null;
                }

                $factor = $item['factor']
                    ?? $item['issue']
                    ?? $item['title']
                    ?? $item['finding']
                    ?? $item['risk']
                    ?? $item['problem']
                    ?? null;

                if (blank($factor)) {
                    return null;
                }

                return [
                    'factor' => trim((string) $factor),
                    'evidence' => $item['evidence']
                        ?? $item['description']
                        ?? $item['detail']
                        ?? $item['reason']
                        ?? $item['impact']
                        ?? null,
                    'severity' => Str::lower((string) ($item['severity'] ?? $item['priority'] ?? 'medium')),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }


    protected function normalizeAiSteps(array $items): array
    {
        return collect($items)
            ->map(function ($item) {
                if (is_string($item)) {
                    return trim($item);
                }

                if (! is_array($item)) {
                    return null;
                }

                $step = $item['action']
                    ?? $item['step']
                    ?? $item['recommendation']
                    ?? $item['title']
                    ?? $item['text']
                    ?? $item['next_action']
                    ?? null;

                return filled($step)
                    ? trim((string) $step)
                    : null;
            })
            ->filter()
            ->unique(fn (string $step) => Str::lower($step))
            ->values()
            ->all();
    }


    protected function aiSeverityScore(string $severity): int
    {
        return match (Str::lower(trim($severity))) {
            'critical', 'danger', 'urgent', 'high' => 1000,
            'warning', 'attention', 'medium' => 750,
            'action' => 700,
            'info', 'monitor' => 450,
            'good', 'healthy', 'success', 'low' => 200,
            default => 300,
        };
    }


    protected function buildMarketingSummary(array $context): array
    {
        $items = [];

        $metaAds = $context['meta_ads_dashboard_insight'] ?? [];
        $googleAnalytics = $context['google_analytics_dashboard_insight'] ?? [];
        $googleAds = $context['google_ads_dashboard_insight'] ?? [];
        $trelloMarketing = $context['trello_marketing_stats'] ?? [];

        $metaOverview = $metaAds['overview'] ?? [];
        $metaSummaryText = trim((string) ($metaAds['summary_text'] ?? ''));
        $metaSpend = (float) ($metaOverview['total_spend'] ?? 0);
        $metaConversions = (int) ($metaOverview['total_lead_form_submission'] ?? 0)
            + (int) ($metaOverview['total_whatsapp_chat'] ?? 0);
        $metaCritical = (int) ($metaOverview['critical_count'] ?? 0);
        $metaAttention = (int) ($metaOverview['attention_count'] ?? 0);

        if (! (bool) ($metaAds['is_available'] ?? false)) {
            $items[] = $this->marketingSummaryItem(
                filled($metaAds['error_message'] ?? null) ? 'warning' : 'info',
                'Meta Ads belum tersedia',
                $metaSummaryText !== '' ? $metaSummaryText : 'Snapshot Meta Ads belum tersedia.',
                700
            );
        } elseif ($metaCritical > 0) {
            $items[] = $this->marketingSummaryItem(
                'critical',
                'Meta Ads perlu perhatian',
                $metaSummaryText,
                980
            );
        } elseif ($metaAttention > 0 || ($metaSpend > 0 && $metaConversions <= 0)) {
            $items[] = $this->marketingSummaryItem(
                'warning',
                'Meta Ads perlu optimasi',
                $metaSummaryText,
                900
            );
        } else {
            $items[] = $this->marketingSummaryItem(
                'good',
                'Meta Ads terpantau',
                $metaSummaryText,
                520
            );
        }

        $gaKpis = $googleAnalytics['kpis'] ?? [];
        $gaSummaryText = trim((string) ($googleAnalytics['summary_text'] ?? ''));
        $gaSessions = (int) ($gaKpis['sessions'] ?? 0);
        $gaEngagementRate = (float) ($gaKpis['engagement_rate'] ?? 0);
        $gaKeyEvents = (int) ($gaKpis['key_events'] ?? 0);

        if (! (bool) ($googleAnalytics['is_available'] ?? false)) {
            $items[] = $this->marketingSummaryItem(
                filled($googleAnalytics['error_message'] ?? null) ? 'warning' : 'info',
                'Google Analytics belum tersedia',
                $gaSummaryText !== '' ? $gaSummaryText : 'Snapshot Google Analytics belum tersedia.',
                690
            );
        } elseif ($gaSessions <= 0) {
            $items[] = $this->marketingSummaryItem(
                'warning',
                'Website belum mencatat traffic',
                $gaSummaryText !== '' ? $gaSummaryText : 'Google Analytics tersambung, tetapi belum mencatat session pada periode terbaru.',
                910
            );
        } elseif ($gaEngagementRate > 0 && $gaEngagementRate < 20) {
            $items[] = $this->marketingSummaryItem(
                'warning',
                'Website engagement sangat rendah',
                $gaSummaryText,
                920
            );
        } elseif ($gaEngagementRate < 50 || $gaKeyEvents <= 0) {
            $items[] = $this->marketingSummaryItem(
                'action',
                'Website performance perlu optimasi',
                $gaSummaryText,
                830
            );
        } else {
            $items[] = $this->marketingSummaryItem(
                'good',
                'Website traffic terpantau',
                $gaSummaryText,
                510
            );
        }

        $googleAdsOverview = $googleAds['overview'] ?? [];
        $googleAdsSummaryText = trim((string) ($googleAds['summary_text'] ?? ''));
        $googleAdsCost = (float) ($googleAdsOverview['total_cost'] ?? 0);
        $googleAdsClicks = (int) ($googleAdsOverview['total_clicks'] ?? 0);
        $googleAdsConversions = (float) ($googleAdsOverview['total_conversions'] ?? 0);
        $googleAdsCritical = (int) ($googleAdsOverview['critical_count'] ?? 0);
        $googleAdsAttention = (int) ($googleAdsOverview['attention_count'] ?? 0);

        if (! (bool) ($googleAds['is_available'] ?? false)) {
            $items[] = $this->marketingSummaryItem(
                filled($googleAds['error_message'] ?? null) ? 'warning' : 'info',
                'Google Ads belum tersedia',
                $googleAdsSummaryText !== '' ? $googleAdsSummaryText : 'Snapshot Google Ads belum tersedia.',
                680
            );
        } elseif ($googleAdsCost > 0 && $googleAdsClicks <= 0) {
            $items[] = $this->marketingSummaryItem(
                'warning',
                'Google Ads spend belum menghasilkan klik',
                $googleAdsSummaryText,
                940
            );
        } elseif ($googleAdsClicks > 0 && $googleAdsConversions <= 0) {
            $items[] = $this->marketingSummaryItem(
                'warning',
                'Google Ads conversion bottleneck',
                $googleAdsSummaryText,
                930
            );
        } elseif ($googleAdsCritical > 0 || $googleAdsAttention > 0) {
            $items[] = $this->marketingSummaryItem(
                $googleAdsCritical > 0 ? 'critical' : 'action',
                $googleAdsCritical > 0
                    ? 'Google Ads punya campaign kritis'
                    : 'Google Ads perlu optimasi',
                $googleAdsSummaryText,
                $googleAdsCritical > 0 ? 970 : 820
            );
        } else {
            $items[] = $this->marketingSummaryItem(
                'good',
                'Google Ads terpantau',
                $googleAdsSummaryText,
                500
            );
        }

        $trelloSummary = $trelloMarketing['summary'] ?? [];
        $trelloInsight = trim((string) ($trelloMarketing['insight'] ?? ''));
        $trelloWebhookStatus = strtolower((string) ($trelloMarketing['webhook_status'] ?? 'inactive'));
        $trelloOverdue = (int) ($trelloSummary['overdue'] ?? 0);
        $trelloDueToday = (int) ($trelloSummary['due_today'] ?? 0);
        $trelloUnmapped = (int) ($trelloSummary['unmapped'] ?? 0);
        $trelloActiveWork = (int) ($trelloSummary['active_work'] ?? 0);

        if ($trelloWebhookStatus !== 'active') {
            $items[] = $this->marketingSummaryItem(
                'warning',
                'Trello Marketing belum aktif',
                $trelloInsight !== '' ? $trelloInsight : 'Webhook atau integrasi Trello Marketing belum aktif.',
                880
            );
        } elseif ($trelloUnmapped > 0) {
            $items[] = $this->marketingSummaryItem(
                'warning',
                'Trello Marketing perlu mapping',
                $trelloInsight !== '' ? $trelloInsight : number_format($trelloUnmapped) . ' card belum memiliki mapping status.',
                890
            );
        } elseif ($trelloOverdue > 0) {
            $items[] = $this->marketingSummaryItem(
                'critical',
                'Marketing task overdue',
                $trelloInsight,
                990
            );
        } elseif ($trelloDueToday > 0) {
            $items[] = $this->marketingSummaryItem(
                'action',
                'Marketing task due today',
                $trelloInsight,
                850
            );
        } elseif ($trelloActiveWork > 0) {
            $items[] = $this->marketingSummaryItem(
                'info',
                'Marketing work sedang berjalan',
                $trelloInsight,
                480
            );
        } else {
            $items[] = $this->marketingSummaryItem(
                'good',
                'Marketing workload terkendali',
                $trelloInsight !== '' ? $trelloInsight : 'Belum ada workload Marketing yang membutuhkan perhatian.',
                450
            );
        }

        $priority = collect($items)
            ->sortByDesc('score')
            ->values();

        $availablePlatforms = collect($context['platform_statuses'] ?? [])
            ->filter(fn (array $platform) => (bool) ($platform['is_available'] ?? false))
            ->count();

        if ($availablePlatforms <= 0) {
            $headline = 'Marketing data belum tersedia';
        } else {
            $headline = $priority->first()['title'] ?? 'Marketing Performance';
        }

        $summaryText = $this->joinSummaryParagraphs(
            $priority
                ->take(4)
                ->pluck('message')
                ->all()
        );

        return [
            'generated_at' => now()->format('d M Y H:i'),
            'source' => 'local',
            'source_label' => 'Smart Marketing Insight',
            'mode' => 'local_smart',
            'headline' => $headline,
            'summary_text' => $summaryText,
            'items' => $priority->take(8)->values()->all(),
            'focus' => $priority
                ->filter(fn (array $item) => in_array($item['type'], ['critical', 'warning', 'action'], true))
                ->take(4)
                ->values()
                ->all(),
        ];
    }

    protected function marketingSummaryItem(
        string $type,
        string $title,
        string $message,
        int $score
    ): array {
        $message = trim($message);

        if ($message === '') {
            $message = 'Detail insight belum tersedia.';
        }

        return [
            'type' => $type,
            'level' => $type,
            'title' => $title,
            'message' => $message,
            'description' => $message,
            'score' => $score,
        ];
    }

    protected function joinSummaryParagraphs(array $paragraphs): string
    {
        return collect($paragraphs)
            ->flatten()
            ->flatMap(function ($paragraph) {
                return preg_split('/\n{2,}/', (string) $paragraph) ?: [];
            })
            ->map(fn ($paragraph) => trim((string) $paragraph))
            ->filter()
            ->unique()
            ->values()
            ->implode("\n\n");
    }

    protected function summarySeverityRank(string $type): int
    {
        return match ($type) {
            'critical' => 1,
            'warning' => 2,
            'action' => 3,
            'good' => 4,
            'info' => 5,
            default => 6,
        };
    }


    protected function getGoogleAnalyticsDashboardInsight(): array
    {
        $empty = $this->emptyGoogleAnalyticsDashboardInsight();

        if (! Schema::hasTable('google_analytics_dashboard_snapshots')) {
            return array_merge($empty, [
                'summary_text' => 'Google Analytics snapshot belum tersedia karena table google_analytics_dashboard_snapshots belum dibuat.',
                'error_message' => 'Table google_analytics_dashboard_snapshots belum tersedia.',
            ]);
        }

        $datePreset = (string) config('services.google_analytics.default_date_preset', 'last_7d');
        $propertyId = (string) config('services.google_analytics.property_id');

        $snapshot = GoogleAnalyticsDashboardSnapshot::query()
            ->where('date_preset', $datePreset)
            ->when($propertyId !== '', fn ($query) => $query->where('property_id', $propertyId))
            ->latest('synced_at')
            ->first();

        if (! $snapshot) {
            return array_merge($empty, [
                'summary_text' => 'Google Analytics belum memiliki snapshot. Jalankan sync Google Analytics terlebih dulu.',
                'error_message' => null,
            ]);
        }

        $payload = is_array($snapshot->payload)
            ? $snapshot->payload
            : [];

        if (empty($payload)) {
            return array_merge($empty, [
                'is_available' => false,
                'summary_text' => $snapshot->summary_text ?: 'Google Analytics snapshot belum memiliki payload.',
                'last_synced_at' => optional($snapshot->synced_at)->format('d M Y H:i'),
                'error_message' => $snapshot->error_message,
            ]);
        }

        /*
        * Payload tetap jadi source of truth untuk Blade karena semua section
        * sudah lengkap di sana: kpis, acquisition, landing_pages, funnel, content,
        * devices, locations.
        */
        $aiPayload = is_array($snapshot->ai_payload)
            ? $snapshot->ai_payload
            : ($payload['ai_summary'] ?? null);

        if (is_array($aiPayload)) {
            $payload['ai_summary'] = $aiPayload;
        }

        if (! empty($snapshot->ai_summary_text)) {
            $payload['summary_text'] = $snapshot->ai_summary_text;
        }

        return array_replace_recursive($empty, $payload, [
            'is_available' => (bool) $snapshot->is_available,
            'last_synced_at' => optional($snapshot->synced_at)->format('d M Y H:i'),
            'error_message' => $snapshot->error_message,
        ]);
    }


    protected function emptyGoogleAnalyticsDashboardInsight(): array
    {
        return [
            'is_available' => false,
            'period' => [
                'date_start' => null,
                'date_stop' => null,
            ],
            'last_synced_at' => null,
            'summary_text' => 'Data Google Analytics belum tersedia.',
            'error_message' => null,

            'kpis' => [
                'total_users' => 0,
                'new_users' => 0,
                'sessions' => 0,
                'engaged_sessions' => 0,
                'engagement_rate' => 0,
                'bounce_rate' => 0,
                'average_engagement_time_label' => '0s',
                'key_events' => 0,
                'key_event_rate' => 0,
            ],

            'acquisition' => [
                'channels' => [],
                'sources' => [],
                'campaigns' => [],
            ],

            'landing_pages' => [],
            'conversion_funnel' => [],
            'content_pages' => [],
            'devices' => [],
            'locations' => [],
        ];
    }


    protected function getGoogleAdsDashboardInsight(): array
    {
        $empty = $this->emptyGoogleAdsDashboardInsight();

        if (! Schema::hasTable('google_ads_dashboard_snapshots')) {
            return array_merge($empty, [
                'summary_text' => 'Google Ads snapshot belum tersedia karena table google_ads_dashboard_snapshots belum dibuat.',
                'error_message' => 'Table google_ads_dashboard_snapshots belum tersedia.',
            ]);
        }

        $datePreset = (string) config('services.google_ads.default_date_preset', 'last_7d');
        $customerId = preg_replace('/\D+/', '', (string) config('services.google_ads.customer_id'));

        $snapshot = GoogleAdsDashboardSnapshot::query()
            ->where('date_preset', $datePreset)
            ->when($customerId !== '', fn ($query) => $query->where('customer_id', $customerId))
            ->latest('synced_at')
            ->first();

        if (! $snapshot) {
            return array_merge($empty, [
                'summary_text' => 'Google Ads belum memiliki snapshot. Jalankan sync Google Ads terlebih dulu.',
                'error_message' => null,
            ]);
        }

        $payload = is_array($snapshot->payload)
            ? $snapshot->payload
            : [];

        if (empty($payload)) {
            return array_merge($empty, [
                'is_available' => false,
                'summary_text' => $snapshot->summary_text ?: 'Google Ads snapshot belum memiliki payload.',
                'last_synced_at' => optional($snapshot->synced_at)->format('d M Y H:i'),
                'error_message' => $snapshot->error_message,
            ]);
        }

        $aiPayload = is_array($snapshot->ai_payload)
            ? $snapshot->ai_payload
            : ($payload['ai_summary'] ?? null);

        if (is_array($aiPayload)) {
            $payload['ai_summary'] = $aiPayload;
        }

        if (! empty($snapshot->ai_summary_text)) {
            $payload['summary_text'] = $snapshot->ai_summary_text;
        }

        return array_replace_recursive($empty, $payload, [
            'is_available' => (bool) $snapshot->is_available,
            'last_synced_at' => optional($snapshot->synced_at)->format('d M Y H:i'),
            'error_message' => $snapshot->error_message,
        ]);
    }


    protected function emptyGoogleAdsDashboardInsight(): array
    {
        return [
            'is_available' => false,
            'source' => 'google_ads',
            'customer_id' => null,
            'login_customer_id' => null,
            'period' => [
                'date_preset' => null,
                'date_start' => null,
                'date_stop' => null,
            ],
            'overview' => [
                'campaign_count' => 0,
                'enabled_campaign_count' => 0,
                'paused_campaign_count' => 0,
                'total_cost' => 0,
                'total_cost_label' => 'Rp 0',
                'total_impressions' => 0,
                'total_clicks' => 0,
                'ctr' => 0,
                'average_cpc' => 0,
                'average_cpc_label' => 'Rp 0',
                'total_conversions' => 0,
                'total_conversion_value' => 0,
                'cost_per_conversion' => null,
                'cost_per_conversion_label' => '-',
                'conversion_rate' => 0,
                'roas' => 0,
                'critical_count' => 0,
                'attention_count' => 0,
                'healthy_count' => 0,
                'best_campaign' => null,
                'summary_text' => 'Data Google Ads belum tersedia.',
            ],
            'campaigns' => [],
            'summary_text' => 'Data Google Ads belum tersedia.',
            'ai_summary' => [],
            'last_synced_at' => null,
            'error_message' => null,
        ];
    }


    protected function getMetaAdsDashboardInsight(): array
    {
        $table = 'meta_ads_campaign_insights';

        $empty = $this->emptyMetaAdsDashboardInsight();

        if (! Schema::hasTable($table)) {
            return array_merge($empty, [
                'summary_text' => 'Data Meta Ads belum tersedia karena table meta_ads_campaign_insights belum dibuat.',
                'error_message' => 'Table meta_ads_campaign_insights belum tersedia.',
            ]);
        }

        try {
            $latestDateStop = DB::table($table)->max('date_stop');

            if (! $latestDateStop) {
                return array_merge($empty, [
                    'summary_text' => 'Data Meta Ads belum tersedia. Jalankan sync Meta Ads terlebih dulu.',
                ]);
            }

            $rows = DB::table($table)
                ->whereDate('date_stop', $latestDateStop)
                ->orderByDesc('spend')
                ->get();

            if ($rows->isEmpty()) {
                return array_merge($empty, [
                    'summary_text' => 'Data Meta Ads belum tersedia untuk periode terbaru.',
                ]);
            }

            $campaigns = $rows
                ->map(fn ($row) => $this->mapMetaAdsCampaignInsightRow($row))
                ->values();

            $overview = $this->buildMetaAdsOverview($campaigns->all());

            return [
                'is_available' => true,
                'source' => 'meta_ads',
                'period' => [
                    'date_start' => $campaigns->min('date_start'),
                    'date_stop' => $campaigns->max('date_stop'),
                ],
                'overview' => $overview,
                'campaigns' => $campaigns->all(),
                'summary_text' => $overview['summary_text'] ?? 'Data Meta Ads berhasil ditarik.',
                'last_synced_at' => optional(
                    DB::table($table)->max('updated_at')
                        ? Carbon::parse(DB::table($table)->max('updated_at'))
                        : null
                )->format('d M Y H:i'),
                'error_message' => null,
            ];
        } catch (Throwable $exception) {
            Log::error('Failed to build Meta Ads dashboard insight.', [
                'message' => $exception->getMessage(),
            ]);

            return array_merge($empty, [
                'summary_text' => 'Data Meta Ads belum bisa dibaca. Dashboard tetap aman, tapi table/sync Meta Ads perlu dicek.',
                'error_message' => app()->hasDebugModeEnabled()
                    ? $exception->getMessage()
                    : 'Data Meta Ads belum bisa dibaca.',
                'last_synced_at' => now()->format('d M Y H:i'),
            ]);
        }
    }


    protected function emptyMetaAdsDashboardInsight(): array
    {
        return [
            'is_available' => false,
            'source' => 'meta_ads',
            'period' => [
                'date_start' => null,
                'date_stop' => null,
            ],
            'overview' => [
                'campaign_count' => 0,
                'total_spend' => 0,
                'total_reach' => 0,
                'total_impressions' => 0,
                'total_engagement' => 0,
                'total_link_click' => 0,
                'total_lead_form_submission' => 0,
                'total_whatsapp_chat' => 0,
                'cost_per_lead' => null,
                'cost_per_whatsapp_chat' => null,
                'best_campaign' => null,
                'attention_campaigns' => [],
                'critical_count' => 0,
                'attention_count' => 0,
                'healthy_count' => 0,
                'summary_text' => 'Data Meta Ads belum tersedia.',
            ],
            'campaigns' => [],
            'summary_text' => 'Data Meta Ads belum tersedia.',
            'last_synced_at' => null,
            'error_message' => null,
        ];
    }


    protected function mapMetaAdsCampaignInsightRow(object $row): array
    {
        $spend = (float) ($row->spend ?? 0);
        $reach = (int) ($row->reach ?? 0);
        $impressions = (int) ($row->impressions ?? 0);
        $frequency = (float) ($row->frequency ?? 0);

        $clicks = (int) ($row->clicks ?? 0);
        $inlineLinkClicks = (int) ($row->inline_link_clicks ?? 0);

        $engagement = (int) ($row->engagement ?? 0);
        $linkClick = (int) ($row->link_click ?? 0);

        if ($linkClick <= 0) {
            $linkClick = $inlineLinkClicks;
        }

        $leadFormSubmission = (int) ($row->lead_form_submission ?? 0);
        $whatsappChat = (int) ($row->whatsapp_chat ?? 0);

        $costPerLead = $leadFormSubmission > 0
            ? (float) (($row->cost_per_lead ?? null) ?: round($spend / $leadFormSubmission, 2))
            : null;

        $costPerWhatsappChat = $whatsappChat > 0
            ? (float) (($row->cost_per_whatsapp_chat ?? null) ?: round($spend / $whatsappChat, 2))
            : null;

        $leadConversionRate = $linkClick > 0
            ? round(($leadFormSubmission / $linkClick) * 100, 1)
            : 0;

        $whatsappConversionRate = $linkClick > 0
            ? round(($whatsappChat / $linkClick) * 100, 1)
            : 0;

        $engagementRate = $reach > 0
            ? round(($engagement / $reach) * 100, 1)
            : 0;

        $linkClickRate = $impressions > 0
            ? round(($linkClick / $impressions) * 100, 2)
            : 0;

        $health = $this->getMetaAdsCampaignHealth(
            spend: $spend,
            reach: $reach,
            impressions: $impressions,
            frequency: $frequency,
            linkClick: $linkClick,
            leadFormSubmission: $leadFormSubmission,
            whatsappChat: $whatsappChat
        );

        $campaign = [
            'id' => (int) ($row->id ?? 0),
            'ad_account_id' => $row->ad_account_id ?? null,
            'campaign_id' => $row->campaign_id ?? null,
            'campaign_name' => $row->campaign_name ?? 'Untitled Campaign',

            'date_start' => $row->date_start ?? null,
            'date_stop' => $row->date_stop ?? null,

            'spend' => $spend,
            'spend_label' => $this->formatMetaAdsCurrency($spend),

            'reach' => $reach,
            'impressions' => $impressions,
            'frequency' => round($frequency, 2),

            'clicks' => $clicks,
            'inline_link_clicks' => $inlineLinkClicks,

            'ctr' => round((float) ($row->ctr ?? 0), 2),
            'cpc' => (float) ($row->cpc ?? 0),
            'cpm' => (float) ($row->cpm ?? 0),

            'engagement' => $engagement,
            'link_click' => $linkClick,
            'lead_form_submission' => $leadFormSubmission,
            'whatsapp_chat' => $whatsappChat,

            'cost_per_lead' => $costPerLead,
            'cost_per_lead_label' => $costPerLead !== null
                ? $this->formatMetaAdsCurrency($costPerLead)
                : '-',

            'cost_per_whatsapp_chat' => $costPerWhatsappChat,
            'cost_per_whatsapp_chat_label' => $costPerWhatsappChat !== null
                ? $this->formatMetaAdsCurrency($costPerWhatsappChat)
                : '-',

            'engagement_rate' => $engagementRate,
            'link_click_rate' => $linkClickRate,
            'lead_conversion_rate' => $leadConversionRate,
            'whatsapp_conversion_rate' => $whatsappConversionRate,

            'health_status' => $health['status'],
            'health_label' => $health['label'],
            'health_type' => $health['type'],
            'health_badge_class' => $this->getMetaAdsHealthBadgeClass($health['type']),

            'actions' => is_string($row->actions ?? null)
                ? json_decode($row->actions, true)
                : ($row->actions ?? []),

            'cost_per_action_type' => is_string($row->cost_per_action_type ?? null)
                ? json_decode($row->cost_per_action_type, true)
                : ($row->cost_per_action_type ?? []),

            'raw_payload' => is_string($row->raw_payload ?? null)
                ? json_decode($row->raw_payload, true)
                : ($row->raw_payload ?? []),
        ];

        $campaign['ai_summary'] = $this->getMetaAdsCampaignAiSummary($campaign)
        ?? $this->buildMetaAdsCampaignSummary($campaign);

        return $campaign;
    }


    protected function getMetaAdsCampaignAiSummary(array $campaign): ?array
    {
        if (! Schema::hasTable('meta_ads_ai_reports')) {
            return null;
        }

        $report = DB::table('meta_ads_ai_reports')
            ->where('report_type', 'campaign')
            ->where('campaign_id', $campaign['campaign_id'] ?? null)
            ->whereDate('date_start', $campaign['date_start'] ?? null)
            ->whereDate('date_stop', $campaign['date_stop'] ?? null)
            ->latest('generated_at')
            ->first();

        if (! $report) {
            return null;
        }

        $output = is_string($report->output ?? null)
            ? json_decode($report->output, true)
            : ($report->output ?? null);

        if (! is_array($output)) {
            return null;
        }

        return [
            'summary' => $output['summary'] ?? $report->summary_text ?? null,
            'main_bottleneck' => $output['main_bottleneck'] ?? $report->main_bottleneck ?? null,
            'blocking_factors' => $output['blocking_factors'] ?? [],
            'recommended_steps' => $output['recommended_steps'] ?? [],
            'source' => 'gemini',
            'generated_at' => $report->generated_at ?? null,
        ];
    }


    protected function buildMetaAdsOverview(array $campaigns): array
    {
        $campaignCollection = collect($campaigns);

        $totalSpend = (float) $campaignCollection->sum('spend');
        $totalReach = (int) $campaignCollection->sum('reach');
        $totalImpressions = (int) $campaignCollection->sum('impressions');
        $totalEngagement = (int) $campaignCollection->sum('engagement');
        $totalLinkClick = (int) $campaignCollection->sum('link_click');
        $totalLead = (int) $campaignCollection->sum('lead_form_submission');
        $totalWhatsapp = (int) $campaignCollection->sum('whatsapp_chat');

        $costPerLead = $totalLead > 0
            ? round($totalSpend / $totalLead, 2)
            : null;

        $costPerWhatsappChat = $totalWhatsapp > 0
            ? round($totalSpend / $totalWhatsapp, 2)
            : null;

        $bestCampaign = $campaignCollection
            ->filter(fn ($campaign) => (int) ($campaign['lead_form_submission'] ?? 0) > 0)
            ->sortBy(fn ($campaign) => $campaign['cost_per_lead'] ?? PHP_INT_MAX)
            ->first();

        if (! $bestCampaign) {
            $bestCampaign = $campaignCollection
                ->filter(fn ($campaign) => (int) ($campaign['whatsapp_chat'] ?? 0) > 0)
                ->sortBy(fn ($campaign) => $campaign['cost_per_whatsapp_chat'] ?? PHP_INT_MAX)
                ->first();
        }

        $attentionCampaigns = $campaignCollection
            ->filter(fn ($campaign) => in_array($campaign['health_type'] ?? null, ['critical', 'warning', 'action'], true))
            ->sortBy(fn ($campaign) => $this->summarySeverityRank($campaign['health_type'] ?? 'info'))
            ->values()
            ->all();

        $criticalCount = $campaignCollection
            ->filter(fn ($campaign) => ($campaign['health_type'] ?? null) === 'critical')
            ->count();

        $attentionCount = $campaignCollection
            ->filter(fn ($campaign) => in_array($campaign['health_type'] ?? null, ['warning', 'action'], true))
            ->count();

        $healthyCount = $campaignCollection
            ->filter(fn ($campaign) => ($campaign['health_type'] ?? null) === 'good')
            ->count();

        $summaryText = match (true) {
            $campaignCollection->isEmpty() => 'Data Meta Ads belum tersedia.',
            $totalLead <= 0 && $totalWhatsapp <= 0 => 'Meta Ads sudah mengeluarkan spend ' . $this->formatMetaAdsCurrency($totalSpend) . ', tapi belum menghasilkan lead form atau WhatsApp chat pada periode terbaru. Prioritasnya cek objective, audience, offer, dan CTA.',
            $criticalCount > 0 => 'Meta Ads menghasilkan ' . number_format($totalLead) . ' lead form dan ' . number_format($totalWhatsapp) . ' WhatsApp chat dari spend ' . $this->formatMetaAdsCurrency($totalSpend) . '. Ada ' . number_format($criticalCount) . ' campaign yang perlu dicek karena performa konversinya lemah.',
            $attentionCount > 0 => 'Meta Ads menghasilkan ' . number_format($totalLead) . ' lead form dan ' . number_format($totalWhatsapp) . ' WhatsApp chat. Ada beberapa campaign yang perlu diperbaiki agar spend tidak bocor.',
            default => 'Meta Ads terlihat sehat. Total spend ' . $this->formatMetaAdsCurrency($totalSpend) . ' menghasilkan ' . number_format($totalLead) . ' lead form dan ' . number_format($totalWhatsapp) . ' WhatsApp chat pada periode terbaru.',
        };

        if ($bestCampaign) {
            $summaryText .= ' Campaign terbaik sementara: ' . ($bestCampaign['campaign_name'] ?? '-') . '.';
        }

        return [
            'campaign_count' => $campaignCollection->count(),

            'total_spend' => $totalSpend,
            'total_spend_label' => $this->formatMetaAdsCurrency($totalSpend),

            'total_reach' => $totalReach,
            'total_impressions' => $totalImpressions,
            'total_engagement' => $totalEngagement,
            'total_link_click' => $totalLinkClick,
            'total_lead_form_submission' => $totalLead,
            'total_whatsapp_chat' => $totalWhatsapp,

            'cost_per_lead' => $costPerLead,
            'cost_per_lead_label' => $costPerLead !== null
                ? $this->formatMetaAdsCurrency($costPerLead)
                : '-',

            'cost_per_whatsapp_chat' => $costPerWhatsappChat,
            'cost_per_whatsapp_chat_label' => $costPerWhatsappChat !== null
                ? $this->formatMetaAdsCurrency($costPerWhatsappChat)
                : '-',

            'best_campaign' => $bestCampaign,
            'attention_campaigns' => $attentionCampaigns,

            'critical_count' => $criticalCount,
            'attention_count' => $attentionCount,
            'healthy_count' => $healthyCount,

            'summary_text' => $summaryText,
        ];
    }


    protected function getMetaAdsCampaignHealth(
        float $spend,
        int $reach,
        int $impressions,
        float $frequency,
        int $linkClick,
        int $leadFormSubmission,
        int $whatsappChat
    ): array {
        $conversionTotal = $leadFormSubmission + $whatsappChat;

        if ($spend > 0 && $linkClick <= 0 && $conversionTotal <= 0) {
            return [
                'status' => 'critical',
                'label' => 'Critical',
                'type' => 'critical',
            ];
        }

        if ($spend > 0 && $linkClick > 0 && $conversionTotal <= 0) {
            return [
                'status' => 'conversion_bottleneck',
                'label' => 'Conversion Bottleneck',
                'type' => 'critical',
            ];
        }

        if ($frequency >= 3 && $conversionTotal <= 0) {
            return [
                'status' => 'possible_fatigue',
                'label' => 'Possible Fatigue',
                'type' => 'warning',
            ];
        }

        if ($linkClick > 0 && $conversionTotal > 0) {
            return [
                'status' => 'healthy',
                'label' => 'Healthy',
                'type' => 'good',
            ];
        }

        if ($reach > 0 || $impressions > 0) {
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


    protected function buildMetaAdsCampaignSummary(array $campaign): array
    {
        $campaignName = $campaign['campaign_name'] ?? 'Campaign';
        $spendLabel = $campaign['spend_label'] ?? 'Rp 0';
        $lead = (int) ($campaign['lead_form_submission'] ?? 0);
        $whatsapp = (int) ($campaign['whatsapp_chat'] ?? 0);
        $linkClick = (int) ($campaign['link_click'] ?? 0);
        $engagement = (int) ($campaign['engagement'] ?? 0);
        $frequency = (float) ($campaign['frequency'] ?? 0);
        $healthType = $campaign['health_type'] ?? 'info';

        $blockingFactors = [];
        $recommendedSteps = [];

        if ($linkClick > 0 && ($lead + $whatsapp) <= 0) {
            $blockingFactors[] = [
                'factor' => 'Klik belum berubah menjadi lead/chat',
                'evidence' => 'Campaign mendapatkan ' . number_format($linkClick) . ' link click, tapi belum menghasilkan lead form atau WhatsApp chat.',
                'severity' => 'high',
            ];

            $recommendedSteps[] = 'Periksa landing page/form/CTA WhatsApp karena user sudah klik tapi belum lanjut konversi.';
            $recommendedSteps[] = 'Perjelas offer utama di caption dan landing/form.';
            $recommendedSteps[] = 'Sederhanakan form atau ubah CTA menjadi lebih spesifik.';
        }

        if ($linkClick <= 0 && (float) ($campaign['spend'] ?? 0) > 0) {
            $blockingFactors[] = [
                'factor' => 'Iklan belum cukup menarik untuk diklik',
                'evidence' => 'Campaign sudah mengeluarkan spend ' . $spendLabel . ', tapi link click masih 0.',
                'severity' => 'high',
            ];

            $recommendedSteps[] = 'Ganti hook creative dan headline agar alasan klik lebih jelas.';
            $recommendedSteps[] = 'Test angle problem-solution, benefit, dan urgency.';
        }

        if ($frequency >= 3) {
            $blockingFactors[] = [
                'factor' => 'Potensi audience fatigue',
                'evidence' => 'Frequency sudah mencapai ' . number_format($frequency, 2) . '.',
                'severity' => 'medium',
            ];

            $recommendedSteps[] = 'Siapkan variasi creative baru dan pantau CTR harian.';
        }

        if (($lead + $whatsapp) > 0) {
            $recommendedSteps[] = 'Pertahankan campaign ini dan breakdown ke level adset/ad untuk mencari creative terbaik.';
            $recommendedSteps[] = 'Jika CPL/Cost per WhatsApp masih masuk target, naikkan budget bertahap 15–25%.';
        }

        if (empty($blockingFactors)) {
            $blockingFactors[] = [
                'factor' => 'Belum ada bottleneck berat dari data utama.',
                'evidence' => 'Campaign menghasilkan ' . number_format($lead) . ' lead form dan ' . number_format($whatsapp) . ' WhatsApp chat.',
                'severity' => 'low',
            ];
        }

        if (empty($recommendedSteps)) {
            $recommendedSteps[] = 'Pantau performa harian dan bandingkan dengan campaign lain.';
            $recommendedSteps[] = 'Lanjutkan analisa ke level adset/ad agar keputusan budget lebih akurat.';
        }

        $summary = match ($healthType) {
            'critical' => $campaignName . ' perlu perhatian. Spend ' . $spendLabel . ' belum menghasilkan konversi yang sepadan. Fokus utama adalah memperbaiki bottleneck dari klik menuju lead/chat.',
            'warning' => $campaignName . ' perlu dipantau. Ada sinyal potensi fatigue atau performa belum stabil.',
            'good' => $campaignName . ' terlihat sehat. Campaign menghasilkan ' . number_format($lead) . ' lead form dan ' . number_format($whatsapp) . ' WhatsApp chat dari spend ' . $spendLabel . '.',
            default => $campaignName . ' sudah memiliki data performa. Lanjutkan pemantauan untuk memastikan arah optimasi.',
        };

        return [
            'summary' => $summary,
            'main_bottleneck' => $blockingFactors[0]['factor'] ?? 'Belum ada bottleneck utama.',
            'blocking_factors' => $blockingFactors,
            'recommended_steps' => array_values(array_unique($recommendedSteps)),
        ];
    }


    protected function getMetaAdsHealthBadgeClass(string $type): string
    {
        return match ($type) {
            'critical' => 'bg-danger-subtle text-danger',
            'warning' => 'bg-warning-subtle text-warning',
            'action' => 'bg-primary-subtle text-primary',
            'good' => 'bg-success-subtle text-success',
            default => 'bg-light text-muted',
        };
    }


    protected function formatMetaAdsCurrency(float $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}
