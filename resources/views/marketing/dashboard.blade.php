@extends('layouts.app-dashboard')

@section('title', 'Marketing Dashboard')

@section('content')
@php
    /*
    |--------------------------------------------------------------------------
    | Marketing API Performance Dashboard
    |--------------------------------------------------------------------------
    | Source data:
    | - Meta Ads snapshot
    | - Google Analytics snapshot
    | - Google Ads snapshot
    | - Trello Marketing
    |
    | Dashboard ini tidak menggunakan MarketingReport manual.
    */

    $metaAdsDashboardInsight = $metaAdsDashboardInsight ?? [];
    $googleAnalyticsDashboardInsight = $googleAnalyticsDashboardInsight ?? [];
    $googleAdsDashboardInsight = $googleAdsDashboardInsight ?? [];
    $trelloMarketingStats = $trelloMarketingStats ?? [];
    $trelloSeiStats = $trelloSeiStats ?? [];
    $trelloDashboardStats = $trelloDashboardStats ?? [];
    $platformStatuses = collect($platformStatuses ?? []);
    $marketingOverview = $marketingOverview ?? [];
    $marketingSummary = $marketingSummary ?? [];
    $marketingAiSummaryText = $marketingAiSummaryText
        ?? ($marketingSummary['summary_text'] ?? '');

    /*
    |--------------------------------------------------------------------------
    | AI Marketing Recommendations
    |--------------------------------------------------------------------------
    | Service menormalkan output AI Meta Ads, GA4, Google Ads, dan Trello
    | menjadi satu format agar bottleneck dan saran selalu bisa ditampilkan.
    */
    $marketingAiRecommendations = $marketingAiRecommendations
        ?? ($marketingSummary['ai_recommendations'] ?? []);

    $marketingPlatformAi = $marketingAiRecommendations['platforms'] ?? [];
    $marketingExecutiveSummary = (string) (
        $marketingAiRecommendations['executive_summary']
        ?? $marketingAiSummaryText
        ?? ''
    );
    $marketingMainBottleneck = (string) (
        $marketingAiRecommendations['main_bottleneck']
        ?? 'Belum ada bottleneck utama yang terdeteksi.'
    );
    $marketingBlockingFactors = collect($marketingAiRecommendations['blocking_factors'] ?? []);
    $marketingRecommendedSteps = collect($marketingAiRecommendations['recommended_steps'] ?? []);
    $marketingPriorityActions = collect($marketingAiRecommendations['priority_actions'] ?? []);

    $aiSeverityBadgeClass = function ($severity) {
        return match (strtolower((string) $severity)) {
            'critical', 'danger', 'urgent', 'high' => 'bg-danger-subtle text-danger-emphasis border border-danger-subtle',
            'warning', 'attention', 'medium' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
            'good', 'healthy', 'success', 'low' => 'bg-success-subtle text-success-emphasis border border-success-subtle',
            default => 'bg-primary-subtle text-primary-emphasis border border-primary-subtle',
        };
    };

    /*
    |--------------------------------------------------------------------------
    | AI Paragraph Formatter
    |--------------------------------------------------------------------------
    | Mempertahankan jeda paragraf dari response AI seperti AI robot.
    | Juga menangani literal "\n" yang kadang tersimpan di snapshot JSON.
    */
    $aiParagraphs = function ($value) {
        if (is_array($value) || $value instanceof \Illuminate\Support\Collection) {
            $value = collect($value)
                ->flatten()
                ->filter(fn ($item) => is_scalar($item))
                ->implode("\n\n");
        }

        $text = trim((string) ($value ?? ''));

        if ($text === '') {
            return collect();
        }

        $text = str_replace(
            ['\\r\\n', '\\n', '\\r', "\r\n", "\r"],
            ["\n", "\n", "\n", "\n", "\n"],
            $text
        );

        // Beri paragraph break sebelum label platform bila AI menggabungkannya.
        $text = preg_replace(
            '/(?<!^)(?=\b(?:Meta Ads|Google Analytics|Google Ads|Trello Marketing|Trello SEI|Marketing|SEI)\s*[:\-])/u',
            "\n\n",
            $text
        ) ?? $text;

        $blocks = preg_split('/\n\s*\n+/u', $text) ?: [];

        return collect($blocks)
            ->map(function ($block) {
                $block = trim((string) $block);

                // Pertahankan line break dalam bullet/numbered list.
                $block = preg_replace('/^[\t ]+/m', '', $block) ?? $block;

                return $block;
            })
            ->filter()
            ->values();
    };

    $safePercent = function ($value) {
        $value = (float) ($value ?? 0);

        if ($value > 0 && $value <= 1) {
            $value *= 100;
        }

        return max(0, min(100, $value));
    };

    $formatCurrency = function ($value) {
        return 'Rp ' . number_format((float) ($value ?? 0), 0, ',', '.');
    };

    $formatNumber = function ($value, $decimals = 0) {
        return number_format((float) ($value ?? 0), $decimals, ',', '.');
    };

    $formatDate = function ($value, $format = 'd M Y H:i') {
        if (blank($value)) {
            return '-';
        }

        try {
            return \Carbon\Carbon::parse($value)->format($format);
        } catch (\Throwable) {
            return '-';
        }
    };

    $healthBadgeClass = function ($type) {
        return match (strtolower((string) $type)) {
            'good', 'healthy', 'success' => 'bg-success-subtle text-success-emphasis border border-success-subtle',
            'warning', 'attention', 'action' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
            'critical', 'danger', 'failed' => 'bg-danger-subtle text-danger-emphasis border border-danger-subtle',
            default => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
        };
    };

    $platformIconMap = [
        'meta_ads' => 'bi-meta',
        'google_analytics' => 'bi-bar-chart-fill',
        'google_ads' => 'bi-google',
        'trello_marketing' => 'bi-trello',
        'trello_sei' => 'bi-building-fill',
    ];

    $platformDescriptionMap = [
        'meta_ads' => 'Paid social campaign, reach, lead form, dan WhatsApp conversion.',
        'google_analytics' => 'Website traffic, engagement, acquisition, dan conversion event.',
        'google_ads' => 'Paid search campaign, clicks, conversion, dan cost efficiency.',
        'trello_marketing' => 'Marketing workload, deadline, PIC, dan progress eksekusi.',
        'trello_sei' => 'SEI workload, deadline, PIC, dan progress eksekusi.',
    ];

    /*
    |--------------------------------------------------------------------------
    | Meta Ads
    |--------------------------------------------------------------------------
    */
    $metaAvailable = (bool) ($metaAdsDashboardInsight['is_available'] ?? false);
    $metaOverview = $metaAdsDashboardInsight['overview'] ?? [];
    $metaPeriod = $metaAdsDashboardInsight['period'] ?? [];
    $metaCampaigns = collect($metaAdsDashboardInsight['campaigns'] ?? []);
    $metaBestCampaign = $metaOverview['best_campaign'] ?? null;
    $metaAttentionCampaigns = collect($metaOverview['attention_campaigns'] ?? []);
    $metaTotalConversions = (int) ($metaOverview['total_lead_form_submission'] ?? 0)
        + (int) ($metaOverview['total_whatsapp_chat'] ?? 0);

    /*
    |--------------------------------------------------------------------------
    | Google Analytics
    |--------------------------------------------------------------------------
    */
    $gaAvailable = (bool) ($googleAnalyticsDashboardInsight['is_available'] ?? false);
    $gaKpis = $googleAnalyticsDashboardInsight['kpis'] ?? [];
    $gaPeriod = $googleAnalyticsDashboardInsight['period'] ?? [];
    $gaAcquisition = $googleAnalyticsDashboardInsight['acquisition'] ?? [];
    $gaChannels = collect($gaAcquisition['channels'] ?? []);
    $gaSources = collect($gaAcquisition['sources'] ?? []);
    $gaCampaigns = collect($gaAcquisition['campaigns'] ?? []);
    $gaLandingPages = collect($googleAnalyticsDashboardInsight['landing_pages'] ?? []);
    $gaConversionFunnel = collect($googleAnalyticsDashboardInsight['conversion_funnel'] ?? []);
    $gaContentPages = collect($googleAnalyticsDashboardInsight['content_pages'] ?? []);
    $gaDevices = collect($googleAnalyticsDashboardInsight['devices'] ?? []);
    $gaLocations = collect($googleAnalyticsDashboardInsight['locations'] ?? []);
    $gaEngagementRate = $safePercent($gaKpis['engagement_rate'] ?? 0);
    $gaBounceRate = $safePercent($gaKpis['bounce_rate'] ?? 0);
    $gaKeyEventRate = $safePercent($gaKpis['key_event_rate'] ?? 0);

    /*
    |--------------------------------------------------------------------------
    | Google Ads
    |--------------------------------------------------------------------------
    */
    $googleAdsAvailable = (bool) ($googleAdsDashboardInsight['is_available'] ?? false);
    $googleAdsOverview = $googleAdsDashboardInsight['overview'] ?? [];
    $googleAdsPeriod = $googleAdsDashboardInsight['period'] ?? [];
    $googleAdsCampaigns = collect($googleAdsDashboardInsight['campaigns'] ?? []);
    $googleAdsBestCampaign = $googleAdsOverview['best_campaign'] ?? null;
    $googleAdsCtr = $safePercent($googleAdsOverview['ctr'] ?? 0);
    $googleAdsConversionRate = $safePercent($googleAdsOverview['conversion_rate'] ?? 0);

    /*
    |--------------------------------------------------------------------------
    | Work Progress: Marketing & SEI
    |--------------------------------------------------------------------------
    */
    $trelloMarketingStats = $trelloMarketingStats
        ?: ($trelloDashboardStats['marketing'] ?? []);

    $trelloSeiStats = $trelloSeiStats
        ?: ($trelloDashboardStats['sei'] ?? []);

    $trelloStatusLabels = [
        'notes' => 'Notes',
        'todo' => 'To Do',
        'in_progress' => 'Doing',
        'review' => 'Review',
        'scheduled' => 'Scheduled',
        'done' => 'Done',
        'archived' => 'Archived',
        'ignored' => 'Ignored',
        'unmapped' => 'Unmapped',
    ];

    $trelloStatusIcons = [
        'notes' => 'bi-journal-text',
        'todo' => 'bi-list-check',
        'in_progress' => 'bi-lightning-charge-fill',
        'review' => 'bi-eye-fill',
        'scheduled' => 'bi-calendar-event-fill',
        'done' => 'bi-check2-circle',
        'archived' => 'bi-archive-fill',
        'ignored' => 'bi-slash-circle',
        'unmapped' => 'bi-question-circle',
    ];

    $trelloStatusBadgeClasses = [
        'notes' => 'bg-light text-muted',
        'todo' => 'bg-primary-subtle text-primary',
        'in_progress' => 'bg-warning-subtle text-warning',
        'review' => 'bg-info-subtle text-info',
        'scheduled' => 'bg-purple-subtle text-purple',
        'done' => 'bg-success-subtle text-success',
        'archived' => 'bg-secondary-subtle text-secondary',
        'ignored' => 'bg-secondary-subtle text-secondary',
        'unmapped' => 'bg-secondary-subtle text-secondary',
    ];

    $normalizeTrelloWorkProgress = function (
        array $stats,
        string $key,
        string $label,
        string $icon
    ) use ($safePercent, $formatDate) {
        $summary = $stats['summary'] ?? [];
        $statuses = $stats['statuses'] ?? [];

        $dueTodayCards = collect($stats['due_today_cards'] ?? []);
        $overdueCards = collect($stats['overdue_cards'] ?? []);

        $priorityCards = $overdueCards
            ->merge($dueTodayCards)
            ->unique(function ($card) use ($key) {
                return data_get($card, 'trello_card_id')
                    ?: data_get($card, 'id')
                    ?: data_get($card, 'name')
                    ?: data_get($card, 'title')
                    ?: uniqid($key . '-card-', true);
            })
            ->values();

        $webhookStatus = strtolower((string) ($stats['webhook_status'] ?? 'inactive'));
        $isSynced = in_array($webhookStatus, ['active', 'synced'], true);
        $completionRate = $safePercent($summary['completion_rate'] ?? 0);

        return [
            'key' => $key,
            'label' => $label,
            'icon' => $icon,
            'board_name' => filled($stats['board_name'] ?? null)
                ? (string) $stats['board_name']
                : $label . ' Trello',
            'insight' => filled($stats['insight'] ?? null)
                ? (string) $stats['insight']
                : $label . ' Work insight belum tersedia.',
            'is_synced' => $isSynced,
            'last_synced_text' => $formatDate($stats['last_synced_at'] ?? null),
            'last_webhook_text' => $formatDate($stats['last_webhook_at'] ?? null),
            'summary' => [
                'total_open_cards' => max((int) ($summary['total_open_cards'] ?? 0), 0),
                'active_work' => max((int) ($summary['active_work'] ?? 0), 0),
                'completed' => max((int) ($summary['completed'] ?? 0), 0),
                'due_today' => max((int) ($summary['due_today'] ?? 0), 0),
                'overdue' => max((int) ($summary['overdue'] ?? 0), 0),
                'unmapped' => max((int) ($summary['unmapped'] ?? 0), 0),
                'completion_rate' => $completionRate,
            ],
            'statuses' => $statuses,
            'priority_cards' => $priorityCards,
            'active_cards' => collect($stats['active_cards'] ?? []),
            'progress_class' => $completionRate >= 80
                ? 'bg-success'
                : ($completionRate >= 50 ? 'bg-warning' : 'bg-danger'),
        ];
    };

    $workProgressTabs = collect([
        'marketing' => $normalizeTrelloWorkProgress(
            $trelloMarketingStats,
            'marketing',
            'Marketing',
            'bi-megaphone-fill'
        ),
        'sei' => $normalizeTrelloWorkProgress(
            $trelloSeiStats,
            'sei',
            'SEI',
            'bi-building-fill'
        ),
    ]);

    /*
    |--------------------------------------------------------------------------
    | Grouped Integration Health
    |--------------------------------------------------------------------------
    | Trello Marketing dan SEI ditampilkan sebagai satu platform card,
    | tetapi status masing-masing board tetap terlihat di dalam card.
    */
    $standardPlatformStatuses = $platformStatuses
        ->reject(fn ($platform, $key) => in_array(
            $platform['key'] ?? $key,
            ['trello_marketing', 'trello_sei'],
            true
        ));

    $trelloWorkPlatformStatuses = collect([
        'marketing' => array_merge(
            [
                'key' => 'trello_marketing',
                'label' => 'Marketing',
                'is_available' => false,
                'last_synced_at' => null,
                'error_message' => null,
            ],
            (array) $platformStatuses->get('trello_marketing', [])
        ),
        'sei' => array_merge(
            [
                'key' => 'trello_sei',
                'label' => 'SEI',
                'is_available' => false,
                'last_synced_at' => null,
                'error_message' => null,
            ],
            (array) $platformStatuses->get('trello_sei', [])
        ),
    ]);

    $trelloWorkSyncedCount = $trelloWorkPlatformStatuses
        ->filter(fn ($platform) => (bool) ($platform['is_available'] ?? false))
        ->count();

    $trelloWorkAllSynced = $trelloWorkSyncedCount === $trelloWorkPlatformStatuses->count();
    $trelloWorkPartiallySynced = $trelloWorkSyncedCount > 0 && ! $trelloWorkAllSynced;

    $displayPlatformCount = $standardPlatformStatuses->count() + 1;

    $displayAvailablePlatformCount = $standardPlatformStatuses
        ->filter(fn ($platform) => (bool) ($platform['is_available'] ?? false))
        ->count()
        + ($trelloWorkAllSynced ? 1 : 0);

@endphp

<div class="container-fluid px-4 py-4 marketing-api-dashboard">
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Marketing</div>
                <h1 class="page-title mb-2">Marketing Performance Dashboard</h1>
                <p class="page-subtitle mb-0">
                    Pantau paid media, website performance, conversion, dan workload Marketing berdasarkan data terbaru dari Meta Ads, Google Analytics, Google Ads, dan Trello.
                </p>
            </div>
        </div>
    </div>

    <div class="dashboard-section-label mb-3">
        <div class="dashboard-section-eyebrow">Integration Health</div>
        <h4 class="dashboard-section-title mb-1">Marketing Data Sources</h4>
        <p class="dashboard-section-subtitle mb-0">
            Status sinkronisasi sumber data yang digunakan oleh dashboard Marketing.
        </p>
    </div>

    <div class="row g-3 mb-4">
        @foreach($standardPlatformStatuses as $platformKey => $platform)
            @php
                $platformAvailable = (bool) ($platform['is_available'] ?? false);
                $resolvedKey = $platform['key'] ?? $platformKey;
            @endphp

            <div class="col-xl-3 col-md-6">
                <div class="platform-status-card h-100">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div class="platform-status-icon platform-status-icon-{{ $resolvedKey }}">
                            <i class="bi {{ $platformIconMap[$resolvedKey] ?? 'bi-database-fill' }}"></i>
                        </div>

                        <span class="badge rounded-pill {{ $platformAvailable
                            ? 'bg-success-subtle text-success-emphasis border border-success-subtle'
                            : 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' }}">
                            {{ $platformAvailable ? 'Synced' : 'Not Synced' }}
                        </span>
                    </div>

                    <div class="platform-status-title mt-3">
                        {{ $platform['label'] ?? \Illuminate\Support\Str::headline($resolvedKey) }}
                    </div>

                    <div class="platform-status-description">
                        {{ $platformDescriptionMap[$resolvedKey] ?? 'Marketing integration source.' }}
                    </div>

                    <div class="platform-status-meta mt-3">
                        <span class="last-sync-badge {{ $platformAvailable ? 'is-synced' : 'is-not-synced' }}">
                            <i class="bi bi-clock-history"></i>
                            <span>Last Sync</span>
                            <strong>{{ $formatDate($platform['last_synced_at'] ?? null) }}</strong>
                        </span>
                    </div>

                    @if(! $platformAvailable && filled($platform['error_message'] ?? null))
                        <div class="small text-danger mt-2">
                            {{ $platform['error_message'] }}
                        </div>
                    @endif
                </div>
            </div>
        @endforeach

        <div class="col-xl-3 col-md-6">
            <div class="platform-status-card platform-status-card-trello h-100">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div class="platform-status-icon platform-status-icon-trello_marketing">
                        <i class="bi bi-trello"></i>
                    </div>

                    <span class="badge rounded-pill {{
                        $trelloWorkAllSynced
                            ? 'bg-success-subtle text-success-emphasis border border-success-subtle'
                            : (
                                $trelloWorkPartiallySynced
                                    ? 'bg-warning-subtle text-warning-emphasis border border-warning-subtle'
                                    : 'bg-danger-subtle text-danger-emphasis border border-danger-subtle'
                            )
                    }}">
                        @if($trelloWorkAllSynced)
                            Synced
                        @elseif($trelloWorkPartiallySynced)
                            Partial Sync
                        @else
                            Not Synced
                        @endif
                    </span>
                </div>

                <div class="platform-status-title mt-3">
                    Trello Work Progress
                </div>

                <div class="platform-status-description">
                    Status koneksi workload Marketing dan SEI dalam satu integrasi Trello.
                </div>

                <div class="trello-combined-status-list mt-3">
                    @foreach($trelloWorkPlatformStatuses as $trelloKey => $trelloPlatform)
                        @php
                            $trelloAvailable = (bool) ($trelloPlatform['is_available'] ?? false);
                        @endphp

                        <div class="trello-combined-status-item">
                            <div class="trello-combined-status-main">
                                <span class="trello-combined-status-icon">
                                    <i class="bi {{
                                        $trelloKey === 'marketing'
                                            ? 'bi-megaphone-fill'
                                            : 'bi-building-fill'
                                    }}"></i>
                                </span>

                                <div>
                                    <div class="trello-combined-status-name">
                                        {{ $trelloPlatform['label'] ?? \Illuminate\Support\Str::headline($trelloKey) }}
                                    </div>
                                    <div class="trello-combined-status-sync">
                                        <span class="last-sync-badge last-sync-badge-compact {{ $trelloAvailable ? 'is-synced' : 'is-not-synced' }}">
                                            <i class="bi bi-clock-history"></i>
                                            <strong>{{ $formatDate($trelloPlatform['last_synced_at'] ?? null) }}</strong>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <span class="trello-combined-status-dot {{
                                $trelloAvailable ? 'is-synced' : 'is-not-synced'
                            }}"
                                  title="{{ $trelloAvailable ? 'Synced' : 'Not Synced' }}">
                            </span>
                        </div>

                        @if(! $trelloAvailable && filled($trelloPlatform['error_message'] ?? null))
                            <div class="small text-danger px-1">
                                {{ $trelloPlatform['error_message'] }}
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-section-label mb-3">
        <div class="dashboard-section-eyebrow">Executive Snapshot</div>
        <h4 class="dashboard-section-title mb-1">Marketing Performance Overview</h4>
        <p class="dashboard-section-subtitle mb-0">
            Ringkasan paid media, conversion, website traffic, dan platform yang perlu perhatian.
        </p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap"><i class="bi bi-wallet2"></i></div>
                    <div>
                        <div class="stat-title">Total Ad Spend</div>
                        <div class="stat-value stat-value-currency">
                            {{ $marketingOverview['total_ad_spend_label'] ?? $formatCurrency($marketingOverview['total_ad_spend'] ?? 0) }}
                        </div>
                    </div>
                </div>
                <div class="stat-description">Gabungan spend Meta Ads dan Google Ads.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap"><i class="bi bi-bullseye"></i></div>
                    <div>
                        <div class="stat-title">Paid Conversions</div>
                        <div class="stat-value">
                            {{ $formatNumber($marketingOverview['total_paid_conversions'] ?? 0, 0) }}
                        </div>
                    </div>
                </div>
                <div class="stat-description">
                    Meta {{ $formatNumber($marketingOverview['meta_conversions'] ?? 0) }}
                    · Google Ads {{ $formatNumber($marketingOverview['google_ads_conversions'] ?? 0) }}
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap"><i class="bi bi-globe2"></i></div>
                    <div>
                        <div class="stat-title">Website Sessions</div>
                        <div class="stat-value">
                            {{ $formatNumber($marketingOverview['website_sessions'] ?? 0) }}
                        </div>
                    </div>
                </div>
                <div class="stat-description">
                    Engagement {{ $formatNumber($safePercent($marketingOverview['website_engagement_rate'] ?? 0), 1) }}%
                    · {{ $formatNumber($marketingOverview['website_key_events'] ?? 0) }} key events
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap"><i class="bi bi-exclamation-triangle-fill"></i></div>
                    <div>
                        <div class="stat-title">Need Attention</div>
                        <div class="stat-value {{ (int) ($marketingOverview['attention_platforms'] ?? 0) > 0 ? 'text-warning' : 'text-success' }}">
                            {{ $formatNumber($marketingOverview['attention_platforms'] ?? 0) }}
                        </div>
                    </div>
                </div>
                <div class="stat-description">Platform yang punya alert performa atau sinkronisasi.</div>
            </div>
        </div>
    </div>

    <div class="dashboard-section-label mb-3 mt-1">
        <div class="dashboard-section-eyebrow">AI Decision Support</div>
        <h4 class="dashboard-section-title mb-1">Marketing AI Action Center</h4>
        <p class="dashboard-section-subtitle mb-0">
            Ringkasan lintas platform untuk membaca faktor penghambat, rekomendasi, dan urutan tindakan yang paling penting.
        </p>
    </div>

    <div class="content-card mb-4 marketing-ai-action-center">
        <div class="content-card-header d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h5 class="content-card-title mb-1">AI Marketing Recommendations</h5>
                <p class="content-card-subtitle mb-0">
                    Analisis Meta Ads, Google Analytics, Google Ads, Trello Marketing, dan Trello SEI dalam satu action plan.
                </p>
            </div>

            <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis border border-primary-subtle">
                <i class="bi bi-stars me-1"></i>
                AI Analyst
            </span>
        </div>

        <div class="content-card-body">
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <div class="marketing-ai-executive-card h-100">
                        <div class="marketing-ai-card-icon">
                            <i class="bi bi-robot"></i>
                        </div>

                        <div class="flex-grow-1">
                            <div class="marketing-ai-card-label">Executive Summary</div>
                            <div class="marketing-ai-paragraphs">
                                @forelse(
                                    $aiParagraphs(
                                        $marketingExecutiveSummary
                                            ?: 'AI Marketing summary belum tersedia.'
                                    ) as $paragraph
                                )
                                    <p class="marketing-ai-card-text">
                                        {!! nl2br(e($paragraph)) !!}
                                    </p>
                                @empty
                                    <p class="marketing-ai-card-text mb-0">
                                        AI Marketing summary belum tersedia.
                                    </p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-xl-6">
                    <div class="marketing-ai-panel h-100">
                        <div class="marketing-ai-panel-header">
                            <div>
                                <div class="fw-bold text-dark">Blocking Factors</div>
                                <div class="small text-muted">Masalah dan bukti yang menghambat performa.</div>
                            </div>

                            <span class="badge rounded-pill bg-light text-dark border">
                                {{ number_format($marketingBlockingFactors->count()) }}
                            </span>
                        </div>

                        <div class="marketing-ai-factor-list">
                            @forelse($marketingBlockingFactors->take(8) as $factor)
                                <div class="marketing-ai-factor-item">
                                    <div class="d-flex justify-content-between align-items-start gap-3">
                                        <div>
                                            <div class="fw-semibold text-dark marketing-ai-inline-paragraphs">
                                                @foreach(
                                                    $aiParagraphs(
                                                        data_get($factor, 'factor', 'Faktor penghambat')
                                                    ) as $paragraph
                                                )
                                                    <p>{!! nl2br(e($paragraph)) !!}</p>
                                                @endforeach
                                            </div>

                                            @if(filled(data_get($factor, 'evidence')))
                                                <div class="small text-muted mt-1 marketing-ai-inline-paragraphs">
                                                    @foreach(
                                                        $aiParagraphs(data_get($factor, 'evidence')) as $paragraph
                                                    )
                                                        <p>{!! nl2br(e($paragraph)) !!}</p>
                                                    @endforeach
                                                </div>
                                            @endif

                                            <div class="small text-primary fw-semibold mt-2">
                                                {{ data_get($factor, 'platform_label', 'Marketing') }}
                                                @if(filled(data_get($factor, 'campaign_name')))
                                                    · {{ data_get($factor, 'campaign_name') }}
                                                @endif
                                            </div>
                                        </div>

                                        <span class="badge rounded-pill {{ $aiSeverityBadgeClass(data_get($factor, 'severity', 'medium')) }}">
                                            {{ \Illuminate\Support\Str::headline(data_get($factor, 'severity', 'medium')) }}
                                        </span>
                                    </div>
                                </div>
                            @empty
                                <div class="empty-state-box compact-empty-state">
                                    <div class="empty-state-icon"><i class="bi bi-check2-circle"></i></div>
                                    <h5 class="empty-state-title">Belum ada blocking factor</h5>
                                    <p class="empty-state-text mb-0">
                                        AI belum menemukan penghambat utama pada data terbaru.
                                    </p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="col-xl-6">
                    <div class="marketing-ai-panel h-100">
                        <div class="marketing-ai-panel-header">
                            <div>
                                <div class="fw-bold text-dark">Recommended Actions</div>
                                <div class="small text-muted">Langkah optimasi yang disarankan AI.</div>
                            </div>

                            <span class="badge rounded-pill bg-light text-dark border">
                                {{ number_format($marketingRecommendedSteps->count()) }}
                            </span>
                        </div>

                        <div class="marketing-ai-step-list">
                            @forelse($marketingRecommendedSteps->take(10) as $index => $step)
                                <div class="marketing-ai-step-item">
                                    <span class="marketing-ai-step-number">{{ $index + 1 }}</span>
                                    <div>
                                        <div class="fw-semibold text-dark marketing-ai-inline-paragraphs">
                                            @foreach(
                                                $aiParagraphs(
                                                    data_get(
                                                        $step,
                                                        'action',
                                                        is_string($step) ? $step : '-'
                                                    )
                                                ) as $paragraph
                                            )
                                                <p>{!! nl2br(e($paragraph)) !!}</p>
                                            @endforeach
                                        </div>
                                        <div class="small text-primary fw-semibold mt-1">
                                            {{ data_get($step, 'platform_label', 'Marketing') }}
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="empty-state-box compact-empty-state">
                                    <div class="empty-state-icon"><i class="bi bi-lightbulb"></i></div>
                                    <h5 class="empty-state-title">Belum ada rekomendasi</h5>
                                    <p class="empty-state-text mb-0">
                                        Rekomendasi akan muncul setelah AI analysis tersedia.
                                    </p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="marketing-ai-panel mb-4">
                <div class="marketing-ai-panel-header">
                    <div>
                        <div class="fw-bold text-dark">Priority Action Plan</div>
                        <div class="small text-muted">
                            Urutan tindakan berdasarkan tingkat urgensi dan bottleneck lintas platform.
                        </div>
                    </div>

                    <span class="badge rounded-pill bg-danger-subtle text-danger-emphasis border border-danger-subtle">
                        {{ number_format($marketingPriorityActions->count()) }} actions
                    </span>
                </div>

                @if($marketingPriorityActions->count())
                    <div class="table-responsive">
                        <table class="table table-modern align-middle mb-0">
                            <thead>
                                <tr>
                                    <th width="90">Priority</th>
                                    <th>Platform</th>
                                    <th>Recommended Action</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($marketingPriorityActions->take(10) as $action)
                                    <tr>
                                        <td>
                                            <span class="badge rounded-pill {{ $aiSeverityBadgeClass(data_get($action, 'priority', 'medium')) }}">
                                                {{ \Illuminate\Support\Str::headline(data_get($action, 'priority', 'medium')) }}
                                            </span>
                                        </td>
                                        <td class="fw-semibold text-dark">
                                            {{ data_get($action, 'platform_label', 'Marketing') }}
                                            @if(filled(data_get($action, 'campaign_name')))
                                                <div class="small text-muted">
                                                    {{ data_get($action, 'campaign_name') }}
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="marketing-ai-table-paragraphs">
                                                @foreach(
                                                    $aiParagraphs(data_get($action, 'action', '-')) as $paragraph
                                                )
                                                    <p>{!! nl2br(e($paragraph)) !!}</p>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="text-muted">
                                            <div class="marketing-ai-table-paragraphs">
                                                @foreach(
                                                    $aiParagraphs(data_get($action, 'reason', '-')) as $paragraph
                                                )
                                                    <p>{!! nl2br(e($paragraph)) !!}</p>
                                                @endforeach
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="empty-state-box compact-empty-state">
                        <div class="empty-state-icon"><i class="bi bi-list-check"></i></div>
                        <h5 class="empty-state-title">Priority action belum tersedia</h5>
                    </div>
                @endif
            </div>

            <div class="row g-3">
                @foreach([
                    'meta_ads' => ['label' => 'Meta Ads', 'icon' => 'bi-meta'],
                    'google_analytics' => ['label' => 'Google Analytics', 'icon' => 'bi-bar-chart-fill'],
                    'google_ads' => ['label' => 'Google Ads', 'icon' => 'bi-google'],
                    'trello_marketing' => ['label' => 'Trello Marketing', 'icon' => 'bi-trello'],
                    'trello_sei' => ['label' => 'Trello SEI', 'icon' => 'bi-building-fill'],
                ] as $platformKey => $platformMeta)
                    @php
                        $platformAi = $marketingPlatformAi[$platformKey] ?? [];
                        $platformFactors = collect($platformAi['blocking_factors'] ?? []);
                        $platformSteps = collect($platformAi['recommended_steps'] ?? []);
                    @endphp

                    <div class="col-xl col-lg-4 col-md-6">
                        <div class="marketing-platform-ai-card h-100">
                            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                <span class="marketing-platform-ai-icon">
                                    <i class="bi {{ $platformMeta['icon'] }}"></i>
                                </span>

                                <span class="badge rounded-pill {{ $aiSeverityBadgeClass($platformAi['severity'] ?? 'info') }}">
                                    {{ \Illuminate\Support\Str::headline($platformAi['severity'] ?? 'Info') }}
                                </span>
                            </div>

                            <div class="fw-bold text-dark mb-2">{{ $platformMeta['label'] }}</div>
                            <div class="small text-muted mb-3 marketing-ai-paragraphs">
                                @foreach(
                                    $aiParagraphs(
                                        $platformAi['summary']
                                            ?? 'AI insight belum tersedia.'
                                    ) as $paragraph
                                )
                                    <p>{!! nl2br(e($paragraph)) !!}</p>
                                @endforeach
                            </div>

                            <div class="marketing-platform-ai-bottleneck">
                                <div class="small text-muted fw-semibold mb-1">Bottleneck</div>
                                <div class="fw-semibold text-dark marketing-ai-inline-paragraphs">
                                    @foreach(
                                        $aiParagraphs(
                                            $platformAi['main_bottleneck']
                                                ?? 'Belum terdeteksi.'
                                        ) as $paragraph
                                    )
                                        <p>{!! nl2br(e($paragraph)) !!}</p>
                                    @endforeach
                                </div>
                            </div>

                            <div class="small text-muted mt-3">
                                {{ $platformFactors->count() }} factors
                                · {{ $platformSteps->count() }} recommendations
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="dashboard-section-label mb-3 mt-1">
        <div class="dashboard-section-eyebrow">Paid Social</div>
        <h4 class="dashboard-section-title mb-1">Meta Ads Performance</h4>
        <p class="dashboard-section-subtitle mb-0">
            Analisis spend, reach, click, lead form, WhatsApp chat, dan kesehatan campaign Meta Ads.
        </p>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-header d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="badge rounded-pill bg-primary-subtle text-primary">
                        <i class="bi bi-meta me-1"></i> Meta Ads
                    </span>
                </div>

                <h5 class="content-card-title mb-1">Meta Ads Campaign Performance</h5>
                <p class="content-card-subtitle mb-0">
                    Periode {{ $formatDate($metaPeriod['date_start'] ?? null, 'd M Y') }}
                    - {{ $formatDate($metaPeriod['date_stop'] ?? null, 'd M Y') }}
                </p>
            </div>

            <span class="badge rounded-pill {{ $metaAvailable
                ? 'bg-success-subtle text-success-emphasis border border-success-subtle'
                : 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' }}">
                {{ $metaAvailable ? 'Synced' : 'Not Synced' }}
            </span>
        </div>

        <div class="content-card-body">
            <div class="marketing-insight-box mb-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="marketing-insight-icon meta-insight-icon">
                        <i class="bi bi-meta"></i>
                    </div>
                    <div>
                        <div class="fw-semibold text-dark mb-1">Meta Ads Insight</div>
                        <p class="text-muted mb-0">
                            {{ $metaAdsDashboardInsight['summary_text'] ?? 'Meta Ads insight belum tersedia.' }}
                        </p>
                        <div class="mt-3">
                            <span class="last-sync-badge {{ $metaAvailable ? 'is-synced' : 'is-not-synced' }}">
                                <i class="bi bi-clock-history"></i>
                                <span>Last Sync</span>
                                <strong>{{ $formatDate($metaAdsDashboardInsight['last_synced_at'] ?? null) }}</strong>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                @foreach([
                    [
                        'label' => 'Spend',
                        'value' => $metaOverview['total_spend_label'] ?? $formatCurrency($metaOverview['total_spend'] ?? 0),
                        'help' => ($metaOverview['campaign_count'] ?? 0) . ' campaign',
                        'icon' => 'bi-wallet2',
                    ],
                    [
                        'label' => 'Reach',
                        'value' => $formatNumber($metaOverview['total_reach'] ?? 0),
                        'help' => $formatNumber($metaOverview['total_impressions'] ?? 0) . ' impressions',
                        'icon' => 'bi-people-fill',
                    ],
                    [
                        'label' => 'Lead Form',
                        'value' => $formatNumber($metaOverview['total_lead_form_submission'] ?? 0),
                        'help' => 'CPL ' . ($metaOverview['cost_per_lead_label'] ?? '-'),
                        'icon' => 'bi-ui-checks',
                    ],
                    [
                        'label' => 'WhatsApp Chat',
                        'value' => $formatNumber($metaOverview['total_whatsapp_chat'] ?? 0),
                        'help' => 'Cost/chat ' . ($metaOverview['cost_per_whatsapp_chat_label'] ?? '-'),
                        'icon' => 'bi-whatsapp',
                    ],
                ] as $metric)
                    <div class="col-xl-3 col-md-6">
                        <div class="marketing-kpi-card h-100">
                            <div class="marketing-kpi-icon"><i class="bi {{ $metric['icon'] }}"></i></div>
                            <div class="marketing-kpi-label">{{ $metric['label'] }}</div>
                            <div class="marketing-kpi-value">{{ $metric['value'] }}</div>
                            <div class="marketing-kpi-help">{{ $metric['help'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="row g-3 mb-4">
                <div class="col-xl-4">
                    <div class="marketing-detail-card h-100">
                        <div class="marketing-detail-label">Campaign Health</div>

                        <div class="marketing-health-grid mt-3">
                            <div class="marketing-health-item">
                                <span>Healthy</span>
                                <strong class="text-success">
                                    {{ $formatNumber($metaOverview['healthy_count'] ?? 0) }}
                                </strong>
                            </div>

                            <div class="marketing-health-item">
                                <span>Attention</span>
                                <strong class="text-warning">
                                    {{ $formatNumber($metaOverview['attention_count'] ?? 0) }}
                                </strong>
                            </div>

                            <div class="marketing-health-item">
                                <span>Critical</span>
                                <strong class="text-danger">
                                    {{ $formatNumber($metaOverview['critical_count'] ?? 0) }}
                                </strong>
                            </div>
                        </div>

                        <div class="campaign-health-guide mt-3">
                            <div class="campaign-health-guide-row">
                                <span class="campaign-health-guide-dot is-healthy"></span>
                                <div>
                                    <strong>Healthy</strong>
                                    <p>
                                        Campaign menghasilkan conversion dengan efisiensi biaya yang masih baik.
                                    </p>
                                </div>
                            </div>

                            <div class="campaign-health-guide-row">
                                <span class="campaign-health-guide-dot is-attention"></span>
                                <div>
                                    <strong>Attention</strong>
                                    <p>
                                        Campaign sudah berjalan, tetapi ada metrik yang perlu dioptimalkan.
                                    </p>
                                </div>
                            </div>

                            <div class="campaign-health-guide-row">
                                <span class="campaign-health-guide-dot is-critical"></span>
                                <div>
                                    <strong>Critical</strong>
                                    <p>
                                        Spend atau traffic sudah ada, tetapi conversion lemah atau belum terbentuk.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-8">
                    <div class="marketing-detail-card best-campaign-card h-100">
                        @if($metaBestCampaign)
                            @php
                                $bestCampaignLead = max(
                                    (int) data_get($metaBestCampaign, 'lead_form_submission', 0),
                                    0
                                );

                                $bestCampaignWhatsApp = max(
                                    (int) data_get($metaBestCampaign, 'whatsapp_chat', 0),
                                    0
                                );

                                $bestCampaignLinkClicks = max(
                                    (int) data_get($metaBestCampaign, 'link_click', 0),
                                    0
                                );

                                $bestCampaignConversions = $bestCampaignLead + $bestCampaignWhatsApp;
                            @endphp

                            <div class="best-campaign-header">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="marketing-kpi-icon best-campaign-icon">
                                        <i class="bi bi-trophy-fill"></i>
                                    </div>

                                    <div>
                                        <div class="marketing-detail-label">Best Campaign</div>
                                        <div class="best-campaign-title mt-1">
                                            {{ data_get($metaBestCampaign, 'campaign_name', '-') }}
                                        </div>
                                        <div class="best-campaign-subtitle">
                                            Campaign dengan conversion action terbaik pada periode aktif.
                                        </div>
                                    </div>
                                </div>

                                <span class="badge rounded-pill best-campaign-health {{ data_get($metaBestCampaign, 'health_badge_class', 'bg-light text-muted') }}">
                                    {{ data_get($metaBestCampaign, 'health_label', 'Tracked') }}
                                </span>
                            </div>

                            <div class="best-campaign-highlight-grid mt-3">
                                <div class="best-campaign-highlight-item">
                                    <span>Total Conversion Actions</span>
                                    <strong>{{ number_format($bestCampaignConversions) }}</strong>
                                    <small>Lead form + WhatsApp chat</small>
                                </div>

                                <div class="best-campaign-highlight-item">
                                    <span>Spend</span>
                                    <strong>{{ data_get($metaBestCampaign, 'spend_label', 'Rp 0') }}</strong>
                                    <small>Total biaya campaign</small>
                                </div>
                            </div>

                            <div class="best-campaign-metric-grid">
                                <div class="best-campaign-metric">
                                    <span class="marketing-kpi-icon best-campaign-metric-icon">
                                        <i class="bi bi-ui-checks"></i>
                                    </span>
                                    <div>
                                        <span>Lead Form</span>
                                        <strong>{{ number_format($bestCampaignLead) }}</strong>
                                    </div>
                                </div>

                                <div class="best-campaign-metric">
                                    <span class="marketing-kpi-icon best-campaign-metric-icon">
                                        <i class="bi bi-whatsapp"></i>
                                    </span>
                                    <div>
                                        <span>WhatsApp</span>
                                        <strong>{{ number_format($bestCampaignWhatsApp) }}</strong>
                                    </div>
                                </div>

                                <div class="best-campaign-metric">
                                    <span class="marketing-kpi-icon best-campaign-metric-icon">
                                        <i class="bi bi-cursor-fill"></i>
                                    </span>
                                    <div>
                                        <span>Link Clicks</span>
                                        <strong>{{ number_format($bestCampaignLinkClicks) }}</strong>
                                    </div>
                                </div>
                            </div>

                            <div class="best-campaign-efficiency-grid">
                                <div class="best-campaign-efficiency-item">
                                    <span>Cost per Lead</span>
                                    <strong>
                                        {{ data_get($metaBestCampaign, 'cost_per_lead_label', '-') }}
                                    </strong>
                                </div>

                                <div class="best-campaign-efficiency-item">
                                    <span>Cost per WhatsApp</span>
                                    <strong>
                                        {{ data_get($metaBestCampaign, 'cost_per_whatsapp_chat_label', '-') }}
                                    </strong>
                                </div>
                            </div>
                        @else
                            <div class="empty-state-box compact-empty-state">
                                <div class="empty-state-icon">
                                    <i class="bi bi-trophy"></i>
                                </div>
                                <h5 class="empty-state-title">Best campaign belum tersedia</h5>
                                <p class="empty-state-text mb-0">
                                    Belum ada campaign dengan data conversion yang cukup untuk ditetapkan sebagai top performer.
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            @if($metaCampaigns->count())
                <div class="table-responsive marketing-table-wrap">
                    <table class="table table-modern align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Campaign</th>
                                <th class="text-end">Spend</th>
                                <th class="text-end">Reach</th>
                                <th class="text-end">Link Click</th>
                                <th class="text-end">Lead</th>
                                <th class="text-end">WhatsApp</th>
                                <th>Health</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($metaCampaigns as $campaign)
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ data_get($campaign, 'campaign_name', '-') }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ $formatDate(data_get($campaign, 'date_start'), 'd M') }}
                                            - {{ $formatDate(data_get($campaign, 'date_stop'), 'd M Y') }}
                                        </div>
                                    </td>
                                    <td class="text-end">{{ data_get($campaign, 'spend_label', 'Rp 0') }}</td>
                                    <td class="text-end">{{ $formatNumber(data_get($campaign, 'reach', 0)) }}</td>
                                    <td class="text-end">{{ $formatNumber(data_get($campaign, 'link_click', 0)) }}</td>
                                    <td class="text-end">{{ $formatNumber(data_get($campaign, 'lead_form_submission', 0)) }}</td>
                                    <td class="text-end">{{ $formatNumber(data_get($campaign, 'whatsapp_chat', 0)) }}</td>
                                    <td>
                                        <span class="badge rounded-pill {{ data_get($campaign, 'health_badge_class', 'bg-light text-muted') }}">
                                            {{ data_get($campaign, 'health_label', 'Tracked') }}
                                        </span>
                                    </td>
                                </tr>

                                @php
                                    $campaignAiSummary = is_array(data_get($campaign, 'ai_summary'))
                                        ? data_get($campaign, 'ai_summary')
                                        : [];

                                    $campaignBlockingFactors = collect($campaignAiSummary['blocking_factors'] ?? []);
                                    $campaignRecommendedSteps = collect($campaignAiSummary['recommended_steps'] ?? []);
                                @endphp

                                @if(
                                    filled($campaignAiSummary['summary'] ?? null)
                                    || filled($campaignAiSummary['main_bottleneck'] ?? null)
                                    || $campaignBlockingFactors->count()
                                    || $campaignRecommendedSteps->count()
                                )
                                    <tr class="marketing-ai-row">
                                        <td colspan="7">
                                            <div class="campaign-ai-detail">
                                                <div class="campaign-ai-detail-header">
                                                    <div>
                                                        <div class="fw-bold text-dark">
                                                            <i class="bi bi-stars me-1"></i>
                                                            AI Performance Analysis
                                                        </div>
                                                        <div class="small text-muted">
                                                            Bottleneck dan rekomendasi optimasi untuk campaign ini.
                                                        </div>
                                                    </div>

                                                    <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis border border-primary-subtle">
                                                        AI Analyst
                                                    </span>
                                                </div>

                                                <div class="row g-3">
                                                    <div class="col-xl-5">
                                                        <div class="campaign-ai-summary-box h-100">
                                                            <div class="campaign-ai-box-label">Summary</div>
                                                            <div class="text-muted mb-3 marketing-ai-paragraphs">
                                                                @foreach(
                                                                    $aiParagraphs(
                                                                        $campaignAiSummary['summary']
                                                                            ?? 'Summary campaign belum tersedia.'
                                                                    ) as $paragraph
                                                                )
                                                                    <p>{!! nl2br(e($paragraph)) !!}</p>
                                                                @endforeach
                                                            </div>

                                                            <div class="campaign-ai-box-label">Main Bottleneck</div>
                                                            <div class="campaign-ai-bottleneck-box marketing-ai-paragraphs">
                                                                @foreach(
                                                                    $aiParagraphs(
                                                                        $campaignAiSummary['main_bottleneck']
                                                                            ?? 'Belum ada bottleneck utama.'
                                                                    ) as $paragraph
                                                                )
                                                                    <p>{!! nl2br(e($paragraph)) !!}</p>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-xl-7">
                                                        <div class="campaign-ai-recommendation-box h-100">
                                                            <div class="row g-3">
                                                                <div class="col-lg-6">
                                                                    <div class="campaign-ai-box-label mb-2">Blocking Factors</div>

                                                                    <div class="d-grid gap-2">
                                                                        @forelse($campaignBlockingFactors as $factor)
                                                                            <div class="campaign-ai-factor">
                                                                                <div class="d-flex justify-content-between align-items-start gap-2">
                                                                                    <div>
                                                                                        <div class="fw-semibold text-dark">
                                                                                            {{ data_get($factor, 'factor', 'Faktor penghambat') }}
                                                                                        </div>
                                                                                        @if(filled(data_get($factor, 'evidence')))
                                                                                            <div class="small text-muted mt-1 marketing-ai-inline-paragraphs">
                                                                                                @foreach(
                                                                                                    $aiParagraphs(data_get($factor, 'evidence')) as $paragraph
                                                                                                )
                                                                                                    <p>{!! nl2br(e($paragraph)) !!}</p>
                                                                                                @endforeach
                                                                                            </div>
                                                                                        @endif
                                                                                    </div>

                                                                                    <span class="badge rounded-pill {{ $aiSeverityBadgeClass(data_get($factor, 'severity', 'medium')) }}">
                                                                                        {{ \Illuminate\Support\Str::headline(data_get($factor, 'severity', 'medium')) }}
                                                                                    </span>
                                                                                </div>
                                                                            </div>
                                                                        @empty
                                                                            <div class="small text-muted">Belum ada faktor penghambat.</div>
                                                                        @endforelse
                                                                    </div>
                                                                </div>

                                                                <div class="col-lg-6">
                                                                    <div class="campaign-ai-box-label mb-2">Recommended Steps</div>

                                                                    <ol class="campaign-ai-step-list mb-0">
                                                                        @forelse($campaignRecommendedSteps as $step)
                                                                            <li>
                                                                                <div class="marketing-ai-inline-paragraphs">
                                                                                    @foreach(
                                                                                        $aiParagraphs(
                                                                                            is_array($step)
                                                                                                ? data_get(
                                                                                                    $step,
                                                                                                    'action',
                                                                                                    data_get($step, 'step', '-')
                                                                                                )
                                                                                                : $step
                                                                                        ) as $paragraph
                                                                                    )
                                                                                        <p>{!! nl2br(e($paragraph)) !!}</p>
                                                                                    @endforeach
                                                                                </div>
                                                                            </li>
                                                                        @empty
                                                                            <li class="text-muted">Belum ada rekomendasi.</li>
                                                                        @endforelse
                                                                    </ol>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon"><i class="bi bi-meta"></i></div>
                    <h5 class="empty-state-title">Data Meta Ads belum tersedia</h5>
                    <p class="empty-state-text mb-0">
                        Jalankan sinkronisasi Meta Ads untuk menampilkan performa campaign.
                    </p>
                </div>
            @endif
        </div>
    </div>

    <div class="dashboard-section-label mb-3 mt-1">
        <div class="dashboard-section-eyebrow">Website Performance</div>
        <h4 class="dashboard-section-title mb-1">Google Analytics</h4>
        <p class="dashboard-section-subtitle mb-0">
            Analisis traffic, engagement, acquisition, landing page, dan conversion event website.
        </p>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-header d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h5 class="content-card-title mb-1">Website Performance Summary</h5>
                <p class="content-card-subtitle mb-0">
                    Periode {{ $formatDate($gaPeriod['date_start'] ?? null, 'd M Y') }}
                    - {{ $formatDate($gaPeriod['date_stop'] ?? null, 'd M Y') }}
                </p>
            </div>

            <span class="badge rounded-pill {{ $gaAvailable
                ? 'bg-success-subtle text-success-emphasis border border-success-subtle'
                : 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' }}">
                {{ $gaAvailable ? 'Synced' : 'Not Synced' }}
            </span>
        </div>

        <div class="content-card-body">
            <div class="marketing-insight-box ga-insight-box mb-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="marketing-insight-icon ga-insight-icon">
                        <i class="bi bi-bar-chart-fill"></i>
                    </div>
                    <div>
                        <div class="fw-semibold text-dark mb-1">Google Analytics Insight</div>
                        <p class="text-muted mb-0">
                            {{ $googleAnalyticsDashboardInsight['summary_text'] ?? 'Google Analytics insight belum tersedia.' }}
                        </p>
                        <div class="mt-3">
                            <span class="last-sync-badge {{ $gaAvailable ? 'is-synced' : 'is-not-synced' }}">
                                <i class="bi bi-clock-history"></i>
                                <span>Last Sync</span>
                                <strong>{{ $formatDate($googleAnalyticsDashboardInsight['last_synced_at'] ?? null) }}</strong>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                @foreach([
                    [
                        'label' => 'Total Users',
                        'value' => $formatNumber($gaKpis['total_users'] ?? 0),
                        'help' => $formatNumber($gaKpis['new_users'] ?? 0) . ' new users',
                        'icon' => 'bi-people-fill',
                    ],
                    [
                        'label' => 'Sessions',
                        'value' => $formatNumber($gaKpis['sessions'] ?? 0),
                        'help' => $formatNumber($gaKpis['engaged_sessions'] ?? 0) . ' engaged sessions',
                        'icon' => 'bi-window-stack',
                    ],
                    [
                        'label' => 'Engagement Rate',
                        'value' => $formatNumber($gaEngagementRate, 1) . '%',
                        'help' => 'Bounce ' . $formatNumber($gaBounceRate, 1) . '%',
                        'icon' => 'bi-activity',
                    ],
                    [
                        'label' => 'Key Events',
                        'value' => $formatNumber($gaKpis['key_events'] ?? 0),
                        'help' => 'Rate ' . $formatNumber($gaKeyEventRate, 1) . '%',
                        'icon' => 'bi-bullseye',
                    ],
                ] as $metric)
                    <div class="col-xl-3 col-md-6">
                        <div class="marketing-kpi-card h-100">
                            <div class="marketing-kpi-icon ga-kpi-icon"><i class="bi {{ $metric['icon'] }}"></i></div>
                            <div class="marketing-kpi-label">{{ $metric['label'] }}</div>
                            <div class="marketing-kpi-value">{{ $metric['value'] }}</div>
                            <div class="marketing-kpi-help">{{ $metric['help'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="row g-4 mb-4">
                <div class="col-xl-6">
                    <div class="marketing-table-card h-100">
                        <div class="marketing-table-header">
                            <div>
                                <div class="fw-semibold text-dark">Acquisition Channels</div>
                                <div class="small text-muted">Channel yang membawa traffic ke website.</div>
                            </div>
                            <span class="badge rounded-pill bg-light text-dark border">
                                {{ $gaChannels->count() }}
                            </span>
                        </div>

                        @if($gaChannels->count())
                            <div class="table-responsive">
                                <table class="table table-modern align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Channel</th>
                                            <th class="text-end">Users</th>
                                            <th class="text-end">Sessions</th>
                                            <th class="text-end">Key Events</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($gaChannels->take(8) as $channel)
                                            <tr>
                                                <td class="fw-semibold text-dark">
                                                    {{ data_get($channel, 'channel')
                                                        ?: data_get($channel, 'name')
                                                        ?: data_get($channel, 'session_default_channel_group')
                                                        ?: '-' }}
                                                </td>
                                                <td class="text-end">
                                                    {{ $formatNumber(
                                                        data_get($channel, 'users')
                                                            ?: data_get($channel, 'total_users')
                                                            ?: 0
                                                    ) }}
                                                </td>
                                                <td class="text-end">
                                                    {{ $formatNumber(data_get($channel, 'sessions', 0)) }}
                                                </td>
                                                <td class="text-end">
                                                    {{ $formatNumber(
                                                        data_get($channel, 'key_events')
                                                            ?: data_get($channel, 'conversions')
                                                            ?: 0
                                                    ) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="empty-state-box compact-empty-state">
                                <div class="empty-state-icon"><i class="bi bi-diagram-3"></i></div>
                                <h5 class="empty-state-title">Channel data belum tersedia</h5>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="col-xl-6">
                    <div class="marketing-table-card h-100">
                        <div class="marketing-table-header">
                            <div>
                                <div class="fw-semibold text-dark">Top Landing Pages</div>
                                <div class="small text-muted">Halaman pertama yang menerima traffic.</div>
                            </div>
                            <span class="badge rounded-pill bg-light text-dark border">
                                {{ $gaLandingPages->count() }}
                            </span>
                        </div>

                        @if($gaLandingPages->count())
                            <div class="table-responsive">
                                <table class="table table-modern align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Landing Page</th>
                                            <th class="text-end">Sessions</th>
                                            <th class="text-end">Engagement</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($gaLandingPages->take(8) as $page)
                                            @php
                                                $pageEngagement = $safePercent(
                                                    data_get($page, 'engagement_rate')
                                                        ?: data_get($page, 'session_engagement_rate')
                                                        ?: 0
                                                );
                                            @endphp
                                            <tr>
                                                <td>
                                                    <div class="fw-semibold text-dark marketing-path-text">
                                                        {{ data_get($page, 'landing_page')
                                                            ?: data_get($page, 'path')
                                                            ?: data_get($page, 'page_path')
                                                            ?: data_get($page, 'name')
                                                            ?: '-' }}
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    {{ $formatNumber(data_get($page, 'sessions', 0)) }}
                                                </td>
                                                <td class="text-end">
                                                    {{ $formatNumber($pageEngagement, 1) }}%
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="empty-state-box compact-empty-state">
                                <div class="empty-state-icon"><i class="bi bi-window"></i></div>
                                <h5 class="empty-state-title">Landing page data belum tersedia</h5>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            @if($gaConversionFunnel->count())
                <div class="marketing-detail-card mb-4">
                    <div class="marketing-detail-label mb-3">Conversion Funnel</div>

                    <div class="ga-funnel-list">
                        @php
                            $maxFunnelValue = max(
                                1,
                                (int) $gaConversionFunnel->max(function ($item) {
                                    return (int) (
                                        data_get($item, 'value')
                                        ?: data_get($item, 'users')
                                        ?: data_get($item, 'sessions')
                                        ?: data_get($item, 'event_count')
                                        ?: data_get($item, 'count')
                                        ?: 0
                                    );
                                })
                            );
                        @endphp

                        @foreach($gaConversionFunnel as $funnelItem)
                            @php
                                $funnelValue = (int) (
                                    data_get($funnelItem, 'value')
                                    ?: data_get($funnelItem, 'users')
                                    ?: data_get($funnelItem, 'sessions')
                                    ?: data_get($funnelItem, 'event_count')
                                    ?: data_get($funnelItem, 'count')
                                    ?: 0
                                );

                                $funnelWidth = min(100, max(4, ($funnelValue / $maxFunnelValue) * 100));
                            @endphp

                            <div class="ga-funnel-item">
                                <div class="d-flex justify-content-between gap-3 mb-2">
                                    <span class="fw-semibold text-dark">
                                        {{ data_get($funnelItem, 'label')
                                            ?: data_get($funnelItem, 'stage')
                                            ?: data_get($funnelItem, 'event_name')
                                            ?: data_get($funnelItem, 'name')
                                            ?: '-' }}
                                    </span>
                                    <strong>{{ $formatNumber($funnelValue) }}</strong>
                                </div>
                                <div class="ga-funnel-track">
                                    <div class="ga-funnel-bar" style="width: {{ $funnelWidth }}%;"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="row g-4">
                <div class="col-xl-4">
                    <div class="marketing-list-card h-100">
                        <div class="marketing-detail-label">Devices</div>
                        <div class="marketing-compact-list mt-3">
                            @forelse($gaDevices->take(6) as $device)
                                <div class="marketing-compact-item">
                                    <span>
                                        {{ data_get($device, 'device')
                                            ?: data_get($device, 'category')
                                            ?: data_get($device, 'device_category')
                                            ?: data_get($device, 'name')
                                            ?: '-' }}
                                    </span>
                                    <strong>
                                        {{ $formatNumber(
                                            data_get($device, 'users')
                                                ?: data_get($device, 'sessions')
                                                ?: data_get($device, 'value')
                                                ?: 0
                                        ) }}
                                    </strong>
                                </div>
                            @empty
                                <div class="text-muted small">Device data belum tersedia.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="col-xl-4">
                    <div class="marketing-list-card h-100">
                        <div class="marketing-detail-label">Locations</div>
                        <div class="marketing-compact-list mt-3">
                            @forelse($gaLocations->take(6) as $location)
                                <div class="marketing-compact-item">
                                    <span>
                                        {{ data_get($location, 'location')
                                            ?: data_get($location, 'city')
                                            ?: data_get($location, 'country')
                                            ?: data_get($location, 'name')
                                            ?: '-' }}
                                    </span>
                                    <strong>
                                        {{ $formatNumber(
                                            data_get($location, 'users')
                                                ?: data_get($location, 'sessions')
                                                ?: data_get($location, 'value')
                                                ?: 0
                                        ) }}
                                    </strong>
                                </div>
                            @empty
                                <div class="text-muted small">Location data belum tersedia.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="col-xl-4">
                    <div class="marketing-list-card h-100">
                        <div class="marketing-detail-label">Content Pages</div>
                        <div class="marketing-compact-list mt-3">
                            @forelse($gaContentPages->take(6) as $contentPage)
                                <div class="marketing-compact-item marketing-compact-item-stacked">
                                    <span class="marketing-path-text">
                                        {{ data_get($contentPage, 'page_title')
                                            ?: data_get($contentPage, 'page_path')
                                            ?: data_get($contentPage, 'path')
                                            ?: data_get($contentPage, 'name')
                                            ?: '-' }}
                                    </span>
                                    <strong>
                                        {{ $formatNumber(
                                            data_get($contentPage, 'views')
                                                ?: data_get($contentPage, 'screen_page_views')
                                                ?: data_get($contentPage, 'sessions')
                                                ?: 0
                                        ) }}
                                    </strong>
                                </div>
                            @empty
                                <div class="text-muted small">Content page data belum tersedia.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-section-label mb-3 mt-1">
        <div class="dashboard-section-eyebrow">Paid Search</div>
        <h4 class="dashboard-section-title mb-1">Google Ads Performance</h4>
        <p class="dashboard-section-subtitle mb-0">
            Analisis cost, click, CTR, conversion, dan efisiensi campaign Google Ads.
        </p>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-header d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h5 class="content-card-title mb-1">Google Ads Summary</h5>
                <p class="content-card-subtitle mb-0">
                    Periode {{ $formatDate($googleAdsPeriod['date_start'] ?? null, 'd M Y') }}
                    - {{ $formatDate($googleAdsPeriod['date_stop'] ?? null, 'd M Y') }}
                </p>
            </div>

            <span class="badge rounded-pill {{ $googleAdsAvailable
                ? 'bg-success-subtle text-success-emphasis border border-success-subtle'
                : 'bg-warning-subtle text-warning-emphasis border border-warning-subtle' }}">
                {{ $googleAdsAvailable ? 'Synced' : 'Not Synced' }}
            </span>
        </div>

        <div class="content-card-body">
            <div class="marketing-insight-box google-ads-insight-box mb-4">
                <div class="d-flex align-items-start gap-3">
                    <div class="marketing-insight-icon google-ads-insight-icon">
                        <i class="bi bi-google"></i>
                    </div>
                    <div>
                        <div class="fw-semibold text-dark mb-1">Google Ads Insight</div>
                        <p class="text-muted mb-0">
                            {{ $googleAdsDashboardInsight['summary_text'] ?? 'Google Ads insight belum tersedia.' }}
                        </p>
                        <div class="mt-3">
                            <span class="last-sync-badge {{ $googleAdsAvailable ? 'is-synced' : 'is-not-synced' }}">
                                <i class="bi bi-clock-history"></i>
                                <span>Last Sync</span>
                                <strong>{{ $formatDate($googleAdsDashboardInsight['last_synced_at'] ?? null) }}</strong>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                @foreach([
                    [
                        'label' => 'Cost',
                        'value' => $googleAdsOverview['total_cost_label'] ?? $formatCurrency($googleAdsOverview['total_cost'] ?? 0),
                        'help' => ($googleAdsOverview['campaign_count'] ?? 0) . ' campaign',
                        'icon' => 'bi-wallet2',
                    ],
                    [
                        'label' => 'Clicks',
                        'value' => $formatNumber($googleAdsOverview['total_clicks'] ?? 0),
                        'help' => 'CTR ' . $formatNumber($googleAdsCtr, 2) . '%',
                        'icon' => 'bi-cursor-fill',
                    ],
                    [
                        'label' => 'Conversions',
                        'value' => $formatNumber($googleAdsOverview['total_conversions'] ?? 0, 1),
                        'help' => 'Rate ' . $formatNumber($googleAdsConversionRate, 2) . '%',
                        'icon' => 'bi-bullseye',
                    ],
                    [
                        'label' => 'Cost / Conversion',
                        'value' => $googleAdsOverview['cost_per_conversion_label'] ?? '-',
                        'help' => 'Avg CPC ' . ($googleAdsOverview['average_cpc_label'] ?? 'Rp 0'),
                        'icon' => 'bi-calculator-fill',
                    ],
                ] as $metric)
                    <div class="col-xl-3 col-md-6">
                        <div class="marketing-kpi-card h-100">
                            <div class="marketing-kpi-icon google-ads-kpi-icon"><i class="bi {{ $metric['icon'] }}"></i></div>
                            <div class="marketing-kpi-label">{{ $metric['label'] }}</div>
                            <div class="marketing-kpi-value">{{ $metric['value'] }}</div>
                            <div class="marketing-kpi-help">{{ $metric['help'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="row g-3 mb-4">
                <div class="col-xl-4">
                    <div class="marketing-detail-card h-100">
                        <div class="marketing-detail-label">Campaign Health</div>

                        <div class="marketing-health-grid mt-3">
                            <div class="marketing-health-item">
                                <span>Healthy</span>
                                <strong class="text-success">
                                    {{ $formatNumber($googleAdsOverview['healthy_count'] ?? 0) }}
                                </strong>
                            </div>

                            <div class="marketing-health-item">
                                <span>Attention</span>
                                <strong class="text-warning">
                                    {{ $formatNumber($googleAdsOverview['attention_count'] ?? 0) }}
                                </strong>
                            </div>

                            <div class="marketing-health-item">
                                <span>Critical</span>
                                <strong class="text-danger">
                                    {{ $formatNumber($googleAdsOverview['critical_count'] ?? 0) }}
                                </strong>
                            </div>
                        </div>

                        <div class="campaign-health-guide mt-3">
                            <div class="campaign-health-guide-row">
                                <span class="campaign-health-guide-dot is-healthy"></span>
                                <div>
                                    <strong>Healthy</strong>
                                    <p>
                                        Campaign menghasilkan conversion dengan cost yang masih efisien.
                                    </p>
                                </div>
                            </div>

                            <div class="campaign-health-guide-row">
                                <span class="campaign-health-guide-dot is-attention"></span>
                                <div>
                                    <strong>Attention</strong>
                                    <p>
                                        Campaign punya traffic atau conversion, tetapi efisiensinya perlu ditingkatkan.
                                    </p>
                                </div>
                            </div>

                            <div class="campaign-health-guide-row">
                                <span class="campaign-health-guide-dot is-critical"></span>
                                <div>
                                    <strong>Critical</strong>
                                    <p>
                                        Cost sudah keluar, tetapi conversion tidak terbentuk atau terlalu mahal.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-8">
                    <div class="marketing-detail-card best-campaign-card h-100">
                        @if($googleAdsBestCampaign)
                            @php
                                $googleBestClicks = max(
                                    (int) data_get($googleAdsBestCampaign, 'clicks', 0),
                                    0
                                );

                                $googleBestImpressions = max(
                                    (int) data_get($googleAdsBestCampaign, 'impressions', 0),
                                    0
                                );

                                $googleBestConversions = max(
                                    (float) data_get($googleAdsBestCampaign, 'conversions', 0),
                                    0
                                );

                                $googleBestCtr = $safePercent(
                                    data_get($googleAdsBestCampaign, 'ctr', 0)
                                );
                            @endphp

                            <div class="best-campaign-header">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="marketing-kpi-icon google-ads-kpi-icon best-campaign-icon">
                                        <i class="bi bi-trophy-fill"></i>
                                    </div>

                                    <div>
                                        <div class="marketing-detail-label">Best Campaign</div>
                                        <div class="best-campaign-title mt-1">
                                            {{ data_get($googleAdsBestCampaign, 'campaign_name')
                                                ?: data_get($googleAdsBestCampaign, 'name')
                                                ?: '-' }}
                                        </div>
                                        <div class="best-campaign-subtitle">
                                            Campaign Google Ads dengan hasil conversion terbaik pada periode aktif.
                                        </div>
                                    </div>
                                </div>

                                <span class="badge rounded-pill best-campaign-health {{ data_get($googleAdsBestCampaign, 'health_badge_class')
                                    ?: $healthBadgeClass(data_get($googleAdsBestCampaign, 'health_type')) }}">
                                    {{ data_get($googleAdsBestCampaign, 'health_label')
                                        ?: \Illuminate\Support\Str::headline(
                                            data_get($googleAdsBestCampaign, 'health_type', 'tracked')
                                        ) }}
                                </span>
                            </div>

                            <div class="best-campaign-highlight-grid mt-3">
                                <div class="best-campaign-highlight-item">
                                    <span>Conversions</span>
                                    <strong>{{ $formatNumber($googleBestConversions, 1) }}</strong>
                                    <small>Total conversion campaign</small>
                                </div>

                                <div class="best-campaign-highlight-item">
                                    <span>Cost</span>
                                    <strong>
                                        {{ data_get($googleAdsBestCampaign, 'cost_label')
                                            ?: $formatCurrency(
                                                data_get($googleAdsBestCampaign, 'cost', 0)
                                            ) }}
                                    </strong>
                                    <small>Total biaya campaign</small>
                                </div>
                            </div>

                            <div class="best-campaign-metric-grid">
                                <div class="best-campaign-metric">
                                    <span class="marketing-kpi-icon google-ads-kpi-icon best-campaign-metric-icon">
                                        <i class="bi bi-cursor-fill"></i>
                                    </span>
                                    <div>
                                        <span>Clicks</span>
                                        <strong>{{ $formatNumber($googleBestClicks) }}</strong>
                                    </div>
                                </div>

                                <div class="best-campaign-metric">
                                    <span class="marketing-kpi-icon google-ads-kpi-icon best-campaign-metric-icon">
                                        <i class="bi bi-eye-fill"></i>
                                    </span>
                                    <div>
                                        <span>Impressions</span>
                                        <strong>{{ $formatNumber($googleBestImpressions) }}</strong>
                                    </div>
                                </div>

                                <div class="best-campaign-metric">
                                    <span class="marketing-kpi-icon google-ads-kpi-icon best-campaign-metric-icon">
                                        <i class="bi bi-percent"></i>
                                    </span>
                                    <div>
                                        <span>CTR</span>
                                        <strong>{{ $formatNumber($googleBestCtr, 2) }}%</strong>
                                    </div>
                                </div>
                            </div>

                            <div class="best-campaign-efficiency-grid">
                                <div class="best-campaign-efficiency-item">
                                    <span>Cost per Conversion</span>
                                    <strong>
                                        {{ data_get(
                                            $googleAdsBestCampaign,
                                            'cost_per_conversion_label',
                                            '-'
                                        ) }}
                                    </strong>
                                </div>

                                <div class="best-campaign-efficiency-item">
                                    <span>Average CPC</span>
                                    <strong>
                                        {{ data_get(
                                            $googleAdsBestCampaign,
                                            'average_cpc_label',
                                            data_get($googleAdsBestCampaign, 'avg_cpc_label', '-')
                                        ) }}
                                    </strong>
                                </div>
                            </div>
                        @else
                            <div class="empty-state-box compact-empty-state">
                                <div class="empty-state-icon">
                                    <i class="bi bi-trophy"></i>
                                </div>
                                <h5 class="empty-state-title">Best campaign belum tersedia</h5>
                                <p class="empty-state-text mb-0">
                                    Belum ada campaign Google Ads dengan data conversion yang cukup untuk ditetapkan sebagai top performer.
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            @if($googleAdsCampaigns->count())
                <div class="table-responsive marketing-table-wrap">
                    <table class="table table-modern align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Campaign</th>
                                <th>Status</th>
                                <th class="text-end">Cost</th>
                                <th class="text-end">Impressions</th>
                                <th class="text-end">Clicks</th>
                                <th class="text-end">Conversions</th>
                                <th class="text-end">Cost / Conv.</th>
                                <th>Health</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($googleAdsCampaigns as $campaign)
                                @php
                                    $campaignHealthType = data_get($campaign, 'health_type')
                                        ?: data_get($campaign, 'health_status')
                                        ?: 'info';
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ data_get($campaign, 'campaign_name')
                                                ?: data_get($campaign, 'name')
                                                ?: '-' }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill bg-light text-dark border">
                                            {{ \Illuminate\Support\Str::headline(
                                                data_get($campaign, 'status')
                                                    ?: data_get($campaign, 'campaign_status')
                                                    ?: '-'
                                            ) }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        {{ data_get($campaign, 'cost_label')
                                            ?: $formatCurrency(data_get($campaign, 'cost', 0)) }}
                                    </td>
                                    <td class="text-end">{{ $formatNumber(data_get($campaign, 'impressions', 0)) }}</td>
                                    <td class="text-end">{{ $formatNumber(data_get($campaign, 'clicks', 0)) }}</td>
                                    <td class="text-end">{{ $formatNumber(data_get($campaign, 'conversions', 0), 1) }}</td>
                                    <td class="text-end">
                                        {{ data_get($campaign, 'cost_per_conversion_label')
                                            ?: (
                                                data_get($campaign, 'cost_per_conversion') !== null
                                                    ? $formatCurrency(data_get($campaign, 'cost_per_conversion'))
                                                    : '-'
                                            ) }}
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill {{ data_get($campaign, 'health_badge_class')
                                            ?: $healthBadgeClass($campaignHealthType) }}">
                                            {{ data_get($campaign, 'health_label')
                                                ?: \Illuminate\Support\Str::headline($campaignHealthType) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon"><i class="bi bi-google"></i></div>
                    <h5 class="empty-state-title">Data Google Ads belum tersedia</h5>
                    <p class="empty-state-text mb-0">
                        Jalankan sinkronisasi Google Ads untuk menampilkan performa campaign.
                    </p>
                </div>
            @endif
        </div>
    </div>

    <div class="dashboard-section-label mb-3 mt-1">
        <div class="dashboard-section-eyebrow">Team Overview</div>
        <h4 class="dashboard-section-title mb-1">Work Progress</h4>
        <p class="dashboard-section-subtitle mb-0">
            Pantau progres pekerjaan Marketing dan SEI berdasarkan status, deadline, PIC, serta aktivitas terbaru dari Trello.
        </p>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-header d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h5 class="content-card-title mb-1">Work Progress</h5>
                <p class="content-card-subtitle mb-0">
                    Ringkasan pekerjaan operasional berdasarkan board Marketing dan SEI.
                </p>
            </div>

            <ul class="nav nav-pills work-progress-tabs" id="marketingWorkProgressTabs" role="tablist">
                @foreach($workProgressTabs as $workKey => $work)
                    <li class="nav-item" role="presentation">
                        <button
                            class="nav-link {{ $loop->first ? 'active' : '' }}"
                            id="{{ $workKey }}-work-tab"
                            data-bs-toggle="pill"
                            data-bs-target="#{{ $workKey }}-work-pane"
                            type="button"
                            role="tab"
                            aria-controls="{{ $workKey }}-work-pane"
                            aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                        >
                            <i class="bi {{ $work['icon'] }} me-1"></i>
                            {{ $work['label'] }}
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="content-card-body">
            <div class="tab-content" id="marketingWorkProgressTabsContent">
                @foreach($workProgressTabs as $workKey => $work)
                    @php
                        $workSummary = $work['summary'];
                        $workStatuses = $work['statuses'];
                        $workPriorityCards = $work['priority_cards'];
                        $workActiveCards = $work['active_cards'];

                        $dueTodayClass = $workSummary['due_today'] > 0
                            ? 'text-warning'
                            : 'text-success';

                        $overdueClass = $workSummary['overdue'] > 0
                            ? 'text-danger'
                            : 'text-success';
                    @endphp

                    <div
                        class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                        id="{{ $workKey }}-work-pane"
                        role="tabpanel"
                        aria-labelledby="{{ $workKey }}-work-tab"
                        tabindex="0"
                    >
                        <div class="trello-insight-box mb-3">
                            <div class="d-flex align-items-start gap-3">
                                <div class="trello-insight-icon">
                                    <i class="bi {{ $work['icon'] }}"></i>
                                </div>

                                <div>
                                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                        <div class="fw-semibold text-dark">
                                            {{ $work['label'] }} Work Insight
                                        </div>

                                        <span class="badge rounded-pill {{ $work['is_synced']
                                            ? 'bg-success-subtle text-success'
                                            : 'bg-warning-subtle text-warning' }}">
                                            <i class="bi {{ $work['is_synced']
                                                ? 'bi-cloud-check-fill'
                                                : 'bi-cloud-slash-fill' }} me-1"></i>
                                            {{ $work['is_synced'] ? 'Synced' : 'Not Synced' }}
                                        </span>
                                    </div>

                                    <p class="text-muted mb-0">{{ $work['insight'] }}</p>

                                    <div class="trello-sync-meta mt-3">
                                        <span class="trello-board-name">
                                            <i class="bi bi-kanban me-1"></i>
                                            {{ $work['board_name'] }}
                                        </span>

                                        <span class="last-sync-badge {{ $work['is_synced'] ? 'is-synced' : 'is-not-synced' }}">
                                            <i class="bi bi-clock-history"></i>
                                            <span>Last Sync</span>
                                            <strong>{{ $work['last_synced_text'] }}</strong>
                                        </span>

                                        <span class="last-webhook-text">
                                            Last webhook:
                                            <strong>{{ $work['last_webhook_text'] }}</strong>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="work-progress-completion-card mb-4">
                            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-3">
                                <div>
                                    <div class="work-progress-completion-eyebrow">
                                        {{ $work['label'] }} Progress
                                    </div>

                                    <div class="work-progress-completion-value">
                                        {{ $formatNumber($workSummary['completion_rate']) }}%
                                    </div>

                                    <div class="work-progress-completion-label">
                                        {{ $formatNumber($workSummary['completed']) }}
                                        dari
                                        {{ $formatNumber($workSummary['total_open_cards']) }}
                                        card sudah selesai.
                                    </div>
                                </div>

                                <div class="work-progress-completion-meta text-lg-end">
                                    <div class="small text-muted">Active Work</div>
                                    <div class="fw-semibold text-dark">
                                        {{ $formatNumber($workSummary['active_work']) }} card berjalan
                                    </div>
                                </div>
                            </div>

                            <div class="progress progress-modern work-progress-completion-track mb-3">
                                <div
                                    class="progress-bar {{ $work['progress_class'] }}"
                                    role="progressbar"
                                    style="width: {{ $workSummary['completion_rate'] }}%;"
                                    aria-valuenow="{{ $workSummary['completion_rate'] }}"
                                    aria-valuemin="0"
                                    aria-valuemax="100"
                                ></div>
                            </div>

                            <div class="row g-3">
                                <div class="col-xl-4 col-md-4">
                                    <div class="work-progress-mini-metric">
                                        <span>Due Today</span>
                                        <strong class="{{ $dueTodayClass }}">
                                            {{ $formatNumber($workSummary['due_today']) }}
                                        </strong>
                                    </div>
                                </div>

                                <div class="col-xl-4 col-md-4">
                                    <div class="work-progress-mini-metric">
                                        <span>Overdue</span>
                                        <strong class="{{ $overdueClass }}">
                                            {{ $formatNumber($workSummary['overdue']) }}
                                        </strong>
                                    </div>
                                </div>

                                <div class="col-xl-4 col-md-4">
                                    <div class="work-progress-mini-metric">
                                        <span>Unmapped</span>
                                        <strong>{{ $formatNumber($workSummary['unmapped']) }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            @foreach(['todo', 'in_progress', 'review', 'done'] as $statusKey)
                                @php
                                    $statusTotal = (int) ($workStatuses[$statusKey] ?? 0);
                                    $statusLabel = $trelloStatusLabels[$statusKey]
                                        ?? \Illuminate\Support\Str::headline($statusKey);
                                    $statusClass = $trelloStatusBadgeClasses[$statusKey]
                                        ?? 'bg-light text-muted';
                                    $statusIcon = $trelloStatusIcons[$statusKey]
                                        ?? 'bi-circle';

                                    $statusDescription = match ($statusKey) {
                                        'todo' => 'Task yang sudah masuk antrean kerja dan menunggu eksekusi.',
                                        'in_progress' => 'Task yang sedang dikerjakan oleh tim ' . $work['label'] . '.',
                                        'review' => 'Task yang sudah dikerjakan dan menunggu pengecekan.',
                                        'done' => 'Task yang sudah selesai dan tercatat sebagai completed.',
                                        default => 'Status pekerjaan ' . $work['label'] . '.',
                                    };
                                @endphp

                                <div class="col-xl-3 col-md-6">
                                    <div class="stat-card h-100 work-progress-stat-card">
                                        <div class="stat-card-top">
                                            <div class="stat-icon-wrap {{ $statusClass }}">
                                                <i class="bi {{ $statusIcon }}"></i>
                                            </div>

                                            <div>
                                                <div class="stat-title">{{ $statusLabel }}</div>
                                                <div class="stat-value">{{ $formatNumber($statusTotal) }}</div>
                                            </div>
                                        </div>

                                        <div class="stat-description">
                                            {{ $statusDescription }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if($workSummary['unmapped'] > 0)
                            <div class="alert alert-warning mb-4">
                                Ada {{ $formatNumber($workSummary['unmapped']) }}
                                card {{ $work['label'] }} yang belum punya status dashboard.
                                Jalankan mapping list sebelum angka dipakai untuk keputusan operasional.
                            </div>
                        @endif

                        <div class="row g-3 trello-table-row">
                            <div class="col-12 d-flex flex-column trello-table-column">
                                <div class="trello-table-card flex-fill">
                                    <div class="trello-table-header">
                                        <div>
                                            <div class="fw-semibold text-dark">Priority Cards</div>
                                            <div class="small text-muted">
                                                Card dengan deadline hari ini atau sudah melewati deadline.
                                            </div>
                                        </div>

                                        <span class="badge rounded-pill bg-danger-subtle text-danger">
                                            {{ $formatNumber($workPriorityCards->count()) }} card
                                        </span>
                                    </div>

                                    @if($workPriorityCards->count())
                                        <div class="table-responsive trello-table-scroll">
                                            <table class="table table-modern align-middle mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Card</th>
                                                        <th>PIC</th>
                                                        <th>Status</th>
                                                        <th>Due</th>
                                                        <th class="text-end">Link</th>
                                                    </tr>
                                                </thead>

                                                <tbody
                                                    class="trello-load-more-list auto-expand-list is-collapsed"
                                                    data-initial-visible="4"
                                                >
                                                    @foreach($workPriorityCards as $card)
                                                        @php
                                                            $cardStatus = \Illuminate\Support\Str::of(
                                                                data_get($card, 'normalized_status')
                                                                    ?: data_get($card, 'status')
                                                                    ?: 'unmapped'
                                                            )
                                                                ->lower()
                                                                ->replace([' ', '-'], '_')
                                                                ->toString();

                                                            $cardDueAt = data_get($card, 'due_at')
                                                                ?: data_get($card, 'due')
                                                                ?: data_get($card, 'due_date');

                                                            $cardUrl = data_get($card, 'short_url')
                                                                ?: data_get($card, 'url')
                                                                ?: data_get($card, 'card_url');

                                                            $cardMembers = collect(data_get($card, 'members', []));
                                                            $cardMemberNames = $cardMembers
                                                                ->pluck('name')
                                                                ->filter()
                                                                ->implode(', ');
                                                        @endphp

                                                        <tr>
                                                            <td>
                                                                <div class="fw-semibold text-dark">
                                                                    {{ \Illuminate\Support\Str::limit(
                                                                        data_get($card, 'name')
                                                                            ?: data_get($card, 'title')
                                                                            ?: '-',
                                                                        48
                                                                    ) }}
                                                                </div>

                                                                <div class="small text-muted">
                                                                    {{ data_get($card, 'list_name')
                                                                        ?: data_get($card, 'trello_list_name')
                                                                        ?: '-' }}
                                                                </div>
                                                            </td>

                                                            <td>
                                                                <div class="work-card-pic">
                                                                    <div class="work-card-avatar-stack">
                                                                        @forelse($cardMembers->take(3) as $member)
                                                                            <div
                                                                                class="work-card-avatar"
                                                                                title="{{ data_get($member, 'name', 'PIC') }}"
                                                                            >
                                                                                @if(filled(data_get($member, 'avatar_url')))
                                                                                    <img
                                                                                        src="{{ data_get($member, 'avatar_url') }}"
                                                                                        alt="{{ data_get($member, 'name', 'PIC') }}"
                                                                                        loading="lazy"
                                                                                        referrerpolicy="no-referrer"
                                                                                        onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';"
                                                                                    >
                                                                                    <span class="work-card-avatar-fallback">
                                                                                        {{ data_get($member, 'initials', '?') }}
                                                                                    </span>
                                                                                @else
                                                                                    <span>{{ data_get($member, 'initials', '?') }}</span>
                                                                                @endif
                                                                            </div>
                                                                        @empty
                                                                            <div class="work-card-avatar is-empty" title="No PIC">
                                                                                <span>?</span>
                                                                            </div>
                                                                        @endforelse

                                                                        @if($cardMembers->count() > 3)
                                                                            <div class="work-card-avatar is-more">
                                                                                <span>+{{ $cardMembers->count() - 3 }}</span>
                                                                            </div>
                                                                        @endif
                                                                    </div>

                                                                    <div class="work-card-pic-name">
                                                                        {{ $cardMemberNames
                                                                            ? \Illuminate\Support\Str::limit($cardMemberNames, 24)
                                                                            : 'No PIC' }}
                                                                    </div>
                                                                </div>
                                                            </td>

                                                            <td>
                                                                <span class="badge rounded-pill {{ $trelloStatusBadgeClasses[$cardStatus] ?? 'bg-light text-muted' }}">
                                                                    {{ $trelloStatusLabels[$cardStatus]
                                                                        ?? \Illuminate\Support\Str::headline($cardStatus) }}
                                                                </span>
                                                            </td>

                                                            <td>{{ $formatDate($cardDueAt, 'd M H:i') }}</td>

                                                            <td class="text-end">
                                                                @if($cardUrl)
                                                                    <a
                                                                        href="{{ $cardUrl }}"
                                                                        target="_blank"
                                                                        rel="noopener"
                                                                        class="btn btn-sm btn-light"
                                                                    >
                                                                        Open
                                                                    </a>
                                                                @else
                                                                    <span class="text-muted">-</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="empty-state-box my-0">
                                            <div class="empty-state-icon">
                                                <i class="bi bi-check2-circle"></i>
                                            </div>
                                            <h5 class="empty-state-title">Tidak ada priority card</h5>
                                            <p class="empty-state-text mb-0">
                                                Belum ada card {{ $work['label'] }} dengan deadline hari ini atau overdue.
                                            </p>
                                        </div>
                                    @endif
                                </div>

                                @if($workPriorityCards->count() > 4)
                                    <div
                                        class="auto-expand-trigger trello-auto-expand-trigger"
                                        data-auto-expand-key="trello-{{ $workKey }}-priority"
                                        aria-hidden="true"
                                    ></div>
                                @endif
                            </div>

                            <div class="col-12 d-flex flex-column trello-table-column">
                                <div class="trello-table-card flex-fill">
                                    <div class="trello-table-header">
                                        <div>
                                            <div class="fw-semibold text-dark">Active Work Queue</div>
                                            <div class="small text-muted">
                                                Card aktif di To Do, Doing, Review, atau Scheduled.
                                            </div>
                                        </div>

                                        <span class="badge rounded-pill bg-primary-subtle text-primary">
                                            {{ $formatNumber($workActiveCards->count()) }} card
                                        </span>
                                    </div>

                                    @if($workActiveCards->count())
                                        <div class="table-responsive trello-table-scroll">
                                            <table class="table table-modern align-middle mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Card</th>
                                                        <th>PIC</th>
                                                        <th>Status</th>
                                                        <th>Last Activity</th>
                                                        <th class="text-end">Link</th>
                                                    </tr>
                                                </thead>

                                                <tbody
                                                    class="trello-load-more-list auto-expand-list is-collapsed"
                                                    data-initial-visible="4"
                                                >
                                                    @foreach($workActiveCards as $card)
                                                        @php
                                                            $cardStatus = \Illuminate\Support\Str::of(
                                                                data_get($card, 'normalized_status')
                                                                    ?: data_get($card, 'status')
                                                                    ?: 'unmapped'
                                                            )
                                                                ->lower()
                                                                ->replace([' ', '-'], '_')
                                                                ->toString();

                                                            $cardLastActivity = data_get($card, 'last_activity_at')
                                                                ?: data_get($card, 'date_last_activity')
                                                                ?: data_get($card, 'updated_at');

                                                            $cardUrl = data_get($card, 'short_url')
                                                                ?: data_get($card, 'url')
                                                                ?: data_get($card, 'card_url');

                                                            $cardMembers = collect(data_get($card, 'members', []));
                                                            $cardMemberNames = $cardMembers
                                                                ->pluck('name')
                                                                ->filter()
                                                                ->implode(', ');
                                                        @endphp

                                                        <tr>
                                                            <td>
                                                                <div class="fw-semibold text-dark">
                                                                    {{ \Illuminate\Support\Str::limit(
                                                                        data_get($card, 'name')
                                                                            ?: data_get($card, 'title')
                                                                            ?: '-',
                                                                        48
                                                                    ) }}
                                                                </div>

                                                                <div class="small text-muted">
                                                                    {{ data_get($card, 'list_name')
                                                                        ?: data_get($card, 'trello_list_name')
                                                                        ?: '-' }}
                                                                </div>
                                                            </td>

                                                            <td>
                                                                <div class="work-card-pic">
                                                                    <div class="work-card-avatar-stack">
                                                                        @forelse($cardMembers->take(3) as $member)
                                                                            <div
                                                                                class="work-card-avatar"
                                                                                title="{{ data_get($member, 'name', 'PIC') }}"
                                                                            >
                                                                                @if(filled(data_get($member, 'avatar_url')))
                                                                                    <img
                                                                                        src="{{ data_get($member, 'avatar_url') }}"
                                                                                        alt="{{ data_get($member, 'name', 'PIC') }}"
                                                                                        loading="lazy"
                                                                                        referrerpolicy="no-referrer"
                                                                                        onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';"
                                                                                    >
                                                                                    <span class="work-card-avatar-fallback">
                                                                                        {{ data_get($member, 'initials', '?') }}
                                                                                    </span>
                                                                                @else
                                                                                    <span>{{ data_get($member, 'initials', '?') }}</span>
                                                                                @endif
                                                                            </div>
                                                                        @empty
                                                                            <div class="work-card-avatar is-empty" title="No PIC">
                                                                                <span>?</span>
                                                                            </div>
                                                                        @endforelse

                                                                        @if($cardMembers->count() > 3)
                                                                            <div class="work-card-avatar is-more">
                                                                                <span>+{{ $cardMembers->count() - 3 }}</span>
                                                                            </div>
                                                                        @endif
                                                                    </div>

                                                                    <div class="work-card-pic-name">
                                                                        {{ $cardMemberNames
                                                                            ? \Illuminate\Support\Str::limit($cardMemberNames, 24)
                                                                            : 'No PIC' }}
                                                                    </div>
                                                                </div>
                                                            </td>

                                                            <td>
                                                                <span class="badge rounded-pill {{ $trelloStatusBadgeClasses[$cardStatus] ?? 'bg-light text-muted' }}">
                                                                    {{ $trelloStatusLabels[$cardStatus]
                                                                        ?? \Illuminate\Support\Str::headline($cardStatus) }}
                                                                </span>
                                                            </td>

                                                            <td>{{ $formatDate($cardLastActivity, 'd M H:i') }}</td>

                                                            <td class="text-end">
                                                                @if($cardUrl)
                                                                    <a
                                                                        href="{{ $cardUrl }}"
                                                                        target="_blank"
                                                                        rel="noopener"
                                                                        class="btn btn-sm btn-light"
                                                                    >
                                                                        Open
                                                                    </a>
                                                                @else
                                                                    <span class="text-muted">-</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="empty-state-box my-0">
                                            <div class="empty-state-icon">
                                                <i class="bi bi-kanban"></i>
                                            </div>
                                            <h5 class="empty-state-title">Tidak ada active work</h5>
                                            <p class="empty-state-text mb-0">
                                                Belum ada card {{ $work['label'] }} di status To Do, Doing, Review, atau Scheduled.
                                            </p>
                                        </div>
                                    @endif
                                </div>

                                @if($workActiveCards->count() > 4)
                                    <div
                                        class="auto-expand-trigger trello-auto-expand-trigger"
                                        data-auto-expand-key="trello-{{ $workKey }}-active"
                                        aria-hidden="true"
                                    ></div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <x-ai-insight-widget
        title="AI Marketing Insight"
        :insight="$marketingSummary ?? []"
        :summary="$marketingAiSummaryText ?? null"
    />
</div>
@endsection

@push('styles')
<style>
    .marketing-api-dashboard .stat-value-currency {
        font-size: clamp(1.35rem, 2vw, 2rem);
    }

    .marketing-header-status {
        min-width: 180px;
        padding: .9rem 1rem;
        border-radius: 16px;
        background: rgba(255, 255, 255, .72);
        border: 1px solid rgba(15, 23, 42, .08);
        text-align: right;
    }

    .platform-status-card,
    .marketing-kpi-card,
    .marketing-detail-card,
    .marketing-list-card,
    .marketing-table-card {
        background: #ffffff;
        border: 1px solid rgba(15, 23, 42, .08);
        border-radius: 18px;
        box-shadow: 0 12px 28px rgba(15, 23, 42, .04);
    }

    .platform-status-card {
        padding: 1rem;
    }

    .platform-status-icon,
    .marketing-kpi-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #5B3E8E;
        background: rgba(91, 62, 142, .10);
    }

    .platform-status-icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        font-size: 1.1rem;
    }

    .platform-status-title {
        color: #111827;
        font-size: 1rem;
        font-weight: 800;
    }

    .platform-status-icon-meta_ads,
    .meta-insight-icon {
        color: #0866FF;
        background: rgba(8, 102, 255, .12);
    }

    .platform-status-icon-google_analytics {
        color: #E37400;
        background: rgba(249, 171, 0, .14);
    }

    .platform-status-icon-google_ads {
        color: #2563eb;
        background: rgba(66, 133, 244, .12);
    }

    .platform-status-icon-trello_marketing {
        color: #0079BF;
        background: rgba(0, 121, 191, .12);
    }

    .platform-status-icon-trello_sei {
        color: #5B3E8E;
        background: rgba(91, 62, 142, .12);
    }

    .work-progress-tabs {
        background: rgba(91, 62, 142, .06);
        border: 1px solid rgba(91, 62, 142, .10);
        border-radius: 999px;
        padding: .25rem;
        gap: .25rem;
    }

    .work-progress-tabs .nav-link {
        border-radius: 999px;
        color: #6b7280;
        font-size: .85rem;
        font-weight: 700;
        padding: .45rem .85rem;
        white-space: nowrap;
    }

    .work-progress-tabs .nav-link.active {
        background: #5B3E8E;
        color: #ffffff;
        box-shadow: 0 10px 20px rgba(91, 62, 142, .18);
    }

    .platform-status-description {
        color: #6b7280;
        font-size: .82rem;
        line-height: 1.45;
        margin-top: .4rem;
    }

    .platform-status-meta {
        color: #6b7280;
        font-size: .75rem;
    }

    .last-sync-badge {
        width: fit-content;
        max-width: 100%;
        display: inline-flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .35rem;
        padding: .42rem .65rem;
        border-radius: 999px;
        border: 1px solid rgba(15, 23, 42, .08);
        background: #f8fafc;
        color: #64748b;
        font-size: .7rem;
        font-weight: 700;
        line-height: 1.25;
    }

    .last-sync-badge i {
        font-size: .78rem;
    }

    .last-sync-badge strong {
        color: #334155;
        font-weight: 850;
    }

    .last-sync-badge.is-synced {
        color: #047857;
        background: rgba(16, 185, 129, .09);
        border-color: rgba(16, 185, 129, .18);
    }

    .last-sync-badge.is-synced strong {
        color: #065f46;
    }

    .last-sync-badge.is-not-synced {
        color: #b45309;
        background: rgba(245, 158, 11, .10);
        border-color: rgba(245, 158, 11, .20);
    }

    .last-sync-badge.is-not-synced strong {
        color: #92400e;
    }

    .last-sync-badge-compact {
        padding: .26rem .45rem;
        font-size: .64rem;
        margin-top: .25rem;
    }

    .trello-sync-meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .55rem;
    }

    .trello-board-name {
        display: inline-flex;
        align-items: center;
        color: #475569;
        font-size: .74rem;
        font-weight: 750;
    }

    .last-webhook-text {
        color: #64748b;
        font-size: .72rem;
    }

    .last-webhook-text strong {
        color: #475569;
    }

    .platform-status-card-trello {
        background:
            linear-gradient(135deg, rgba(0, 121, 191, .035), rgba(91, 62, 142, .035)),
            #ffffff;
    }

    .trello-combined-status-list {
        display: grid;
        gap: .65rem;
    }

    .trello-combined-status-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        min-height: 52px;
        padding: .65rem .7rem;
        border: 1px solid rgba(15, 23, 42, .07);
        border-radius: 14px;
        background: rgba(248, 250, 252, .82);
    }

    .trello-combined-status-main {
        display: flex;
        align-items: center;
        gap: .65rem;
        min-width: 0;
    }

    .trello-combined-status-icon {
        width: 30px;
        height: 30px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        color: #5B3E8E;
        background: rgba(91, 62, 142, .10);
        font-size: .82rem;
    }

    .trello-combined-status-name {
        color: #111827;
        font-size: .8rem;
        font-weight: 800;
        line-height: 1.2;
    }

    .trello-combined-status-sync {
        color: #6b7280;
        font-size: .68rem;
        line-height: 1.35;
        margin-top: .15rem;
    }

    .trello-combined-status-dot {
        width: 10px;
        height: 10px;
        border-radius: 999px;
        flex: 0 0 auto;
        box-shadow: 0 0 0 4px rgba(107, 114, 128, .08);
    }

    .trello-combined-status-dot.is-synced {
        background: #22c55e;
        box-shadow: 0 0 0 4px rgba(34, 197, 94, .12);
    }

    .trello-combined-status-dot.is-not-synced {
        background: #f59e0b;
        box-shadow: 0 0 0 4px rgba(245, 158, 11, .12);
    }

    .marketing-insight-box {
        border: 1px solid rgba(91, 62, 142, .12);
        border-radius: 18px;
        background: linear-gradient(135deg, rgba(91, 62, 142, .06), rgba(255, 190, 4, .08));
        padding: 1rem;
    }

    .ga-insight-box {
        background: linear-gradient(135deg, rgba(244, 126, 32, .08), rgba(91, 62, 142, .05));
        border-color: rgba(244, 126, 32, .14);
    }

    .google-ads-insight-box {
        background: linear-gradient(135deg, rgba(66, 133, 244, .08), rgba(52, 168, 83, .06));
        border-color: rgba(66, 133, 244, .14);
    }

    .marketing-insight-icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        color: #5B3E8E;
        background: rgba(91, 62, 142, .12);
        font-size: 1.15rem;
    }

    .ga-insight-icon {
        color: #d97706;
        background: rgba(244, 126, 32, .13);
    }

    .google-ads-insight-icon {
        color: #2563eb;
        background: rgba(66, 133, 244, .12);
    }

    .marketing-kpi-card {
        min-height: 132px;
        padding: 1rem;
        position: relative;
        overflow: hidden;
    }

    .marketing-kpi-icon {
        width: 36px;
        height: 36px;
        border-radius: 12px;
        margin-bottom: .8rem;
    }

    .ga-kpi-icon {
        color: #d97706;
        background: rgba(244, 126, 32, .12);
    }

    .google-ads-kpi-icon {
        color: #2563eb;
        background: rgba(66, 133, 244, .12);
    }

    .marketing-kpi-label,
    .marketing-detail-label {
        color: #64748b;
        font-size: .72rem;
        font-weight: 900;
        letter-spacing: .06em;
        text-transform: uppercase;
    }

    .marketing-kpi-value {
        color: #0f172a;
        font-size: 1.35rem;
        font-weight: 950;
        line-height: 1.2;
        margin-top: .45rem;
    }

    .marketing-kpi-help {
        color: #64748b;
        font-size: .78rem;
        margin-top: .55rem;
        line-height: 1.35;
    }

    .marketing-detail-card,
    .marketing-list-card {
        padding: 1rem;
    }

    .best-campaign-card {
        padding: 1rem;
    }

    .best-campaign-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .best-campaign-icon {
        width: 36px;
        height: 36px;
        margin-bottom: 0;
        flex: 0 0 auto;
    }

    .best-campaign-title {
        color: #0f172a;
        font-size: 1.05rem;
        font-weight: 900;
        line-height: 1.35;
    }

    .best-campaign-subtitle {
        color: #64748b;
        font-size: .78rem;
        line-height: 1.45;
        margin-top: .25rem;
    }

    .best-campaign-health {
        padding: .45rem .7rem;
        font-size: .72rem;
        font-weight: 800;
    }

    .best-campaign-highlight-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .7rem;
    }

    .best-campaign-highlight-item {
        padding: .85rem;
        border: 1px solid rgba(15, 23, 42, .08);
        border-radius: 15px;
        background: #f8fafc;
        display: grid;
        gap: .25rem;
    }

    .best-campaign-highlight-item span {
        color: #64748b;
        font-size: .68rem;
        font-weight: 850;
        letter-spacing: .05em;
        text-transform: uppercase;
    }

    .best-campaign-highlight-item strong {
        color: #0f172a;
        font-size: 1.2rem;
        font-weight: 900;
        line-height: 1.2;
    }

    .best-campaign-highlight-item small {
        color: #94a3b8;
        font-size: .68rem;
        line-height: 1.35;
    }

    .best-campaign-metric-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .7rem;
        margin-top: .7rem;
    }

    .best-campaign-metric {
        min-width: 0;
        display: flex;
        align-items: center;
        gap: .65rem;
        padding: .75rem;
        border: 1px solid rgba(15, 23, 42, .07);
        border-radius: 15px;
        background: #ffffff;
    }

    .best-campaign-metric-icon {
        width: 34px;
        height: 34px;
        margin-bottom: 0;
        flex: 0 0 auto;
    }

    .best-campaign-metric div {
        min-width: 0;
        display: grid;
        gap: .12rem;
    }

    .best-campaign-metric div > span {
        color: #64748b;
        font-size: .68rem;
        font-weight: 800;
    }

    .best-campaign-metric strong {
        color: #0f172a;
        font-size: 1rem;
        font-weight: 900;
    }

    .best-campaign-efficiency-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .7rem;
        margin-top: .7rem;
    }

    .best-campaign-efficiency-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .8rem;
        padding: .72rem .8rem;
        border-radius: 14px;
        border: 1px solid rgba(15, 23, 42, .08);
        background: #f8fafc;
    }

    .best-campaign-efficiency-item span {
        color: #64748b;
        font-size: .72rem;
        font-weight: 750;
    }

    .best-campaign-efficiency-item strong {
        color: #334155;
        font-size: .84rem;
        font-weight: 900;
        text-align: right;
    }

    .campaign-health-guide {
        display: grid;
        gap: .55rem;
        padding-top: .75rem;
        border-top: 1px solid rgba(15, 23, 42, .07);
    }

    .campaign-health-guide-row {
        display: flex;
        align-items: flex-start;
        gap: .6rem;
    }

    .campaign-health-guide-row > div {
        min-width: 0;
    }

    .campaign-health-guide-row strong {
        display: block;
        color: #334155;
        font-size: .72rem;
        font-weight: 850;
        line-height: 1.3;
    }

    .campaign-health-guide-row p {
        color: #64748b;
        font-size: .7rem;
        line-height: 1.42;
        margin: .12rem 0 0;
    }

    .campaign-health-guide-dot {
        width: 9px;
        height: 9px;
        border-radius: 999px;
        flex: 0 0 auto;
        margin-top: .25rem;
    }

    .campaign-health-guide-dot.is-healthy {
        background: #22c55e;
    }

    .campaign-health-guide-dot.is-attention {
        background: #f59e0b;
    }

    .campaign-health-guide-dot.is-critical {
        background: #ef4444;
    }

    .marketing-health-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .65rem;
    }

    .marketing-health-item {
        border: 1px solid rgba(15, 23, 42, .08);
        border-radius: 14px;
        background: #f8fafc;
        padding: .8rem;
        display: grid;
        gap: .3rem;
    }

    .marketing-health-item span {
        color: #64748b;
        font-size: .75rem;
        font-weight: 700;
    }

    .marketing-health-item strong {
        font-size: 1.15rem;
        font-weight: 900;
    }

    .marketing-table-wrap,
    .marketing-table-card {
        overflow: hidden;
    }

    .marketing-table-wrap {
        border: 1px solid rgba(15, 23, 42, .08);
        border-radius: 18px;
    }

    .marketing-table-header {
        padding: 1rem;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        border-bottom: 1px solid rgba(15, 23, 42, .06);
        background: linear-gradient(180deg, #ffffff, rgba(248, 250, 252, .82));
    }

    .marketing-ai-row td {
        background: rgba(91, 62, 142, .025);
    }

    .marketing-ai-action-center {
        overflow: hidden;
    }

    .marketing-ai-executive-card,
    .marketing-ai-panel,
    .marketing-platform-ai-card {
        border: 1px solid rgba(15, 23, 42, .08);
        border-radius: 18px;
        background: #ffffff;
        box-shadow: 0 12px 28px rgba(15, 23, 42, .04);
    }

    .marketing-ai-executive-card {
        padding: 1.1rem;
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        background: linear-gradient(135deg, rgba(91, 62, 142, .07), rgba(255, 190, 4, .07));
    }

    .marketing-ai-card-icon,
    .marketing-platform-ai-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
    }

    .marketing-ai-card-icon {
        width: 46px;
        height: 46px;
        border-radius: 15px;
        color: #5B3E8E;
        background: rgba(91, 62, 142, .12);
        font-size: 1.15rem;
    }

    .marketing-ai-card-label,
    .campaign-ai-box-label {
        color: #64748b;
        font-size: .72rem;
        font-weight: 900;
        letter-spacing: .06em;
        text-transform: uppercase;
        margin-bottom: .55rem;
    }

    .marketing-ai-card-text {
        color: #475569;
        line-height: 1.65;
    }

    .marketing-ai-paragraphs {
        min-width: 0;
    }

    .marketing-ai-paragraphs > p,
    .marketing-ai-inline-paragraphs > p,
    .marketing-ai-table-paragraphs > p {
        margin: 0;
        white-space: normal;
    }

    .marketing-ai-paragraphs > p + p {
        margin-top: .85rem;
    }

    .marketing-ai-inline-paragraphs > p + p {
        margin-top: .5rem;
    }

    .marketing-ai-table-paragraphs > p + p {
        margin-top: .6rem;
    }

    .campaign-ai-bottleneck-box > p {
        margin-bottom: 0;
    }

    .campaign-ai-bottleneck-box > p + p {
        margin-top: .65rem;
    }

    .marketing-ai-panel {
        overflow: hidden;
    }

    .marketing-ai-panel-header {
        padding: 1rem;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        border-bottom: 1px solid rgba(15, 23, 42, .06);
        background: linear-gradient(180deg, #ffffff, rgba(248, 250, 252, .82));
    }

    .marketing-ai-factor-list,
    .marketing-ai-step-list {
        padding: 1rem;
        display: grid;
        gap: .75rem;
    }

    .marketing-ai-factor-item,
    .marketing-ai-step-item,
    .campaign-ai-factor {
        border: 1px solid rgba(15, 23, 42, .08);
        border-radius: 15px;
        background: #f8fafc;
        padding: .85rem;
    }

    .marketing-ai-step-item {
        display: flex;
        align-items: flex-start;
        gap: .8rem;
    }

    .marketing-ai-step-number {
        width: 28px;
        height: 28px;
        border-radius: 10px;
        background: rgba(91, 62, 142, .12);
        color: #5B3E8E;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        font-size: .75rem;
        font-weight: 900;
    }

    .marketing-platform-ai-card {
        padding: 1rem;
    }

    .marketing-platform-ai-icon {
        width: 40px;
        height: 40px;
        border-radius: 13px;
        color: #5B3E8E;
        background: rgba(91, 62, 142, .10);
    }

    .marketing-platform-ai-bottleneck {
        border-top: 1px solid rgba(15, 23, 42, .07);
        padding-top: .8rem;
    }

    .campaign-ai-detail {
        padding: .9rem;
    }

    .campaign-ai-detail-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .campaign-ai-summary-box,
    .campaign-ai-recommendation-box {
        border: 1px solid rgba(15, 23, 42, .08);
        border-radius: 16px;
        background: #ffffff;
        padding: .95rem;
    }

    .campaign-ai-bottleneck-box {
        border-radius: 14px;
        background: rgba(239, 68, 68, .06);
        border: 1px solid rgba(239, 68, 68, .12);
        color: #7f1d1d;
        font-weight: 700;
        padding: .8rem;
    }

    .campaign-ai-step-list {
        padding-left: 1.2rem;
    }

    .campaign-ai-step-list li {
        margin-bottom: .65rem;
        color: #475569;
        line-height: 1.5;
    }

    .campaign-ai-step-list li:last-child {
        margin-bottom: 0;
    }

    .campaign-ai-summary {
        padding: .75rem;
    }

    .compact-empty-state {
        min-height: 210px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .ga-funnel-list {
        display: grid;
        gap: .9rem;
    }

    .ga-funnel-item {
        border: 1px solid rgba(15, 23, 42, .06);
        border-radius: 14px;
        padding: .8rem;
        background: #f8fafc;
    }

    .ga-funnel-track {
        height: 10px;
        border-radius: 999px;
        background: rgba(249, 171, 0, .16);
        overflow: hidden;
    }

    .ga-funnel-bar {
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, #F9AB00 0%, #E37400 100%);
        box-shadow: 0 6px 14px rgba(227, 116, 0, .18);
    }

    .marketing-compact-list {
        display: grid;
        gap: .6rem;
    }

    .marketing-compact-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .8rem;
        border: 1px solid rgba(15, 23, 42, .07);
        border-radius: 13px;
        background: #f8fafc;
        padding: .75rem;
    }

    .marketing-compact-item span {
        color: #64748b;
        font-size: .8rem;
        font-weight: 700;
        min-width: 0;
    }

    .marketing-compact-item strong {
        color: #0f172a;
        font-weight: 900;
        white-space: nowrap;
    }

    .marketing-compact-item-stacked {
        align-items: flex-start;
    }

    .marketing-path-text {
        max-width: 330px;
        overflow-wrap: anywhere;
    }

    .trello-insight-box {
        background: linear-gradient(135deg, rgba(0, 121, 191, .08), rgba(91, 62, 142, .06));
        border: 1px solid rgba(0, 121, 191, .12);
        border-radius: 18px;
        padding: 1rem;
    }

    .trello-insight-icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        background: rgba(0, 121, 191, .12);
        color: #0079BF;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 1.15rem;
    }

    .work-progress-stat-card .stat-icon-wrap {
        background: rgba(91, 62, 142, .10);
        color: #5B3E8E;
    }

    .work-progress-completion-card {
        border: 1px solid rgba(91, 62, 142, .12);
        border-radius: 20px;
        background: linear-gradient(135deg, rgba(91, 62, 142, .07), rgba(255, 190, 4, .08));
        padding: 1.25rem;
    }

    .work-progress-completion-eyebrow {
        color: #5B3E8E;
        font-size: .75rem;
        font-weight: 800;
        letter-spacing: .04em;
        text-transform: uppercase;
        margin-bottom: .35rem;
    }

    .work-progress-completion-value {
        color: #111827;
        font-size: 2.25rem;
        font-weight: 900;
        line-height: 1;
    }

    .work-progress-completion-label {
        color: #6b7280;
        font-size: .92rem;
        font-weight: 600;
        margin-top: .35rem;
    }

    .work-progress-completion-meta {
        background: rgba(255, 255, 255, .72);
        border: 1px solid rgba(15, 23, 42, .06);
        border-radius: 16px;
        padding: .75rem 1rem;
    }

    .work-progress-completion-track {
        height: 10px;
        background: rgba(91, 62, 142, .10);
    }

    .work-progress-mini-metric {
        background: #ffffff;
        border: 1px solid rgba(15, 23, 42, .08);
        border-radius: 16px;
        padding: .9rem 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        box-shadow: 0 10px 24px rgba(15, 23, 42, .04);
    }

    .work-progress-mini-metric span {
        color: #6b7280;
        font-size: .85rem;
        font-weight: 700;
    }

    .work-progress-mini-metric strong {
        color: #111827;
        font-size: 1.2rem;
        font-weight: 900;
    }

    .trello-table-row {
        align-items: stretch;
    }

    .trello-table-row > [class*="col-"] {
        display: flex;
        align-items: stretch;
    }

    .trello-table-column {
        gap: .8rem;
    }

    .trello-table-column + .trello-table-column {
        margin-top: 1.35rem;
    }

    .trello-table-card {
        border: 1px solid rgba(15, 23, 42, .08);
        border-radius: 18px;
        background: #ffffff;
        overflow: hidden;
        width: 100%;
        min-height: 0;
        display: flex;
        flex-direction: column;
        box-shadow: 0 12px 28px rgba(15, 23, 42, .04);
    }

    .trello-table-header {
        padding: 1rem 1rem .85rem;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        min-height: 78px;
        border-bottom: 1px solid rgba(15, 23, 42, .06);
        background: linear-gradient(180deg, #ffffff, rgba(248, 250, 252, .82));
    }

    .trello-table-scroll table {
        width: 100%;
        min-width: 760px;
        table-layout: fixed;
    }

    .trello-table-scroll th:nth-child(1),
    .trello-table-scroll td:nth-child(1) { width: 38%; }

    .trello-table-scroll th:nth-child(2),
    .trello-table-scroll td:nth-child(2) { width: 20%; }

    .trello-table-scroll th:nth-child(3),
    .trello-table-scroll td:nth-child(3) { width: 16%; }

    .trello-table-scroll th:nth-child(4),
    .trello-table-scroll td:nth-child(4) { width: 16%; }

    .trello-table-scroll th:nth-child(5),
    .trello-table-scroll td:nth-child(5) { width: 10%; }

    .trello-table-card .empty-state-box {
        min-height: 280px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .work-card-pic {
        min-width: 112px;
    }

    .work-card-avatar-stack {
        display: flex;
        align-items: center;
        margin-bottom: .35rem;
        min-height: 30px;
    }

    .work-card-avatar {
        width: 30px;
        height: 30px;
        border-radius: 999px;
        overflow: hidden;
        background: rgba(91, 62, 142, .12);
        color: #5B3E8E;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        border: 2px solid #ffffff;
        box-shadow: 0 6px 14px rgba(15, 23, 42, .10);
        font-size: .72rem;
        font-weight: 900;
    }

    .work-card-avatar + .work-card-avatar {
        margin-left: -8px;
    }

    .work-card-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .work-card-avatar span {
        width: 100%;
        height: 100%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .work-card-avatar img + .work-card-avatar-fallback {
        display: none;
    }

    .work-card-avatar.is-empty {
        background: rgba(107, 114, 128, .12);
        color: #6b7280;
    }

    .work-card-avatar.is-more {
        background: #111827;
        color: #ffffff;
        font-size: .65rem;
    }

    .work-card-pic-name {
        color: #6b7280;
        font-size: .76rem;
        font-weight: 700;
        max-width: 132px;
        overflow-wrap: anywhere;
    }

    .auto-expand-list.is-collapsed tr:nth-child(n+5),
    .trello-load-more-list.is-collapsed tr:nth-child(n+5) {
        display: none;
    }

    .auto-expand-trigger {
        width: 100%;
        height: 1px;
        pointer-events: none;
        opacity: 0;
    }

    .auto-expand-list:not(.is-collapsed) tr:nth-child(n+5) {
        animation: marketingTrelloFadeIn .22s ease both;
    }

    @keyframes marketingTrelloFadeIn {
        from {
            opacity: 0;
            transform: translateY(-4px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .bg-purple-subtle {
        background-color: rgba(91, 62, 142, .12) !important;
    }

    .text-purple {
        color: #5B3E8E !important;
    }

    @media (max-width: 767.98px) {
        .best-campaign-highlight-grid,
        .best-campaign-metric-grid,
        .best-campaign-efficiency-grid {
            grid-template-columns: 1fr;
        }

        .last-sync-badge {
            border-radius: 14px;
        }

        .work-progress-tabs {
            width: 100%;
            overflow-x: auto;
            flex-wrap: nowrap;
        }

        .marketing-header-status {
            width: 100%;
            text-align: left;
        }

        .marketing-health-grid {
            grid-template-columns: 1fr;
        }

        .trello-table-scroll {
            overflow-x: auto;
        }
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const expandList = function (list) {
        if (!list || !list.classList.contains('is-collapsed')) {
            return;
        }

        list.classList.remove('is-collapsed');
    };

    const expandSectionFromTrigger = function (trigger) {
        const column = trigger.closest('.trello-table-column');
        const list = column ? column.querySelector('.auto-expand-list') : null;

        expandList(list);
    };

    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver(function (entries, currentObserver) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) {
                    return;
                }

                expandSectionFromTrigger(entry.target);
                currentObserver.unobserve(entry.target);
            });
        }, {
            root: null,
            threshold: 0.01,
            rootMargin: '0px 0px -8% 0px'
        });

        document.querySelectorAll('.trello-auto-expand-trigger').forEach(function (trigger) {
            observer.observe(trigger);
        });
    } else {
        document.querySelectorAll('.trello-auto-expand-trigger').forEach(expandSectionFromTrigger);
    }
});
</script>
@endpush
