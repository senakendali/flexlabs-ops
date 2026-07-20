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
    ];

    $platformDescriptionMap = [
        'meta_ads' => 'Paid social campaign, reach, lead form, dan WhatsApp conversion.',
        'google_analytics' => 'Website traffic, engagement, acquisition, dan conversion event.',
        'google_ads' => 'Paid search campaign, clicks, conversion, dan cost efficiency.',
        'trello_marketing' => 'Marketing workload, deadline, PIC, dan progress eksekusi.',
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
    | Marketing Trello
    |--------------------------------------------------------------------------
    */
    $trelloMarketingStats = $trelloMarketingStats
        ?: ($trelloDashboardStats['marketing'] ?? []);

    $trelloMarketingSummary = $trelloMarketingStats['summary'] ?? [];
    $trelloMarketingStatuses = $trelloMarketingStats['statuses'] ?? [];

    $trelloMarketingTotalOpenCards = max((int) ($trelloMarketingSummary['total_open_cards'] ?? 0), 0);
    $trelloMarketingActiveWork = max((int) ($trelloMarketingSummary['active_work'] ?? 0), 0);
    $trelloMarketingCompleted = max((int) ($trelloMarketingSummary['completed'] ?? 0), 0);
    $trelloMarketingDueToday = max((int) ($trelloMarketingSummary['due_today'] ?? 0), 0);
    $trelloMarketingOverdue = max((int) ($trelloMarketingSummary['overdue'] ?? 0), 0);
    $trelloMarketingUnmapped = max((int) ($trelloMarketingSummary['unmapped'] ?? 0), 0);
    $trelloMarketingCompletionRate = $safePercent($trelloMarketingSummary['completion_rate'] ?? 0);

    $trelloMarketingDueTodayCards = collect($trelloMarketingStats['due_today_cards'] ?? []);
    $trelloMarketingOverdueCards = collect($trelloMarketingStats['overdue_cards'] ?? []);

    $trelloMarketingPriorityCards = $trelloMarketingOverdueCards
        ->merge($trelloMarketingDueTodayCards)
        ->unique(function ($card) {
            return data_get($card, 'trello_card_id')
                ?: data_get($card, 'id')
                ?: data_get($card, 'name')
                ?: data_get($card, 'title')
                ?: uniqid('marketing-card-', true);
        })
        ->values();

    $trelloMarketingActiveCards = collect($trelloMarketingStats['active_cards'] ?? []);

    $trelloMarketingWebhookStatus = strtolower((string) ($trelloMarketingStats['webhook_status'] ?? 'inactive'));
    $trelloMarketingIsSynced = in_array($trelloMarketingWebhookStatus, ['active', 'synced'], true);

    $trelloMarketingBoardName = filled($trelloMarketingStats['board_name'] ?? null)
        ? (string) $trelloMarketingStats['board_name']
        : 'Marketing Trello';

    $trelloMarketingInsight = filled($trelloMarketingStats['insight'] ?? null)
        ? (string) $trelloMarketingStats['insight']
        : 'Marketing Trello insight belum tersedia.';

    $trelloMarketingLastSyncedText = $formatDate($trelloMarketingStats['last_synced_at'] ?? null);
    $trelloMarketingLastWebhookText = $formatDate($trelloMarketingStats['last_webhook_at'] ?? null);

    $trelloMarketingProgressClass = $trelloMarketingCompletionRate >= 80
        ? 'bg-success'
        : ($trelloMarketingCompletionRate >= 50 ? 'bg-warning' : 'bg-danger');

    $trelloMarketingDueTodayClass = $trelloMarketingDueToday > 0
        ? 'text-warning'
        : 'text-success';

    $trelloMarketingOverdueClass = $trelloMarketingOverdue > 0
        ? 'text-danger'
        : 'text-success';

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
        @forelse($platformStatuses as $platformKey => $platform)
            @php
                $platformAvailable = (bool) ($platform['is_available'] ?? false);
                $resolvedKey = $platform['key'] ?? $platformKey;
            @endphp

            <div class="col-xl-3 col-md-6">
                <div class="platform-status-card h-100">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div class="platform-status-icon">
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
                        Last sync:
                        <strong>{{ $formatDate($platform['last_synced_at'] ?? null) }}</strong>
                    </div>

                    @if(! $platformAvailable && filled($platform['error_message'] ?? null))
                        <div class="small text-danger mt-2">
                            {{ $platform['error_message'] }}
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="empty-state-box">
                    <div class="empty-state-icon"><i class="bi bi-cloud-slash"></i></div>
                    <h5 class="empty-state-title">Belum ada status integrasi</h5>
                    <p class="empty-state-text mb-0">
                        Status sumber data Marketing belum tersedia.
                    </p>
                </div>
            </div>
        @endforelse
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
        <div class="dashboard-section-eyebrow">Paid Social</div>
        <h4 class="dashboard-section-title mb-1">Meta Ads Performance</h4>
        <p class="dashboard-section-subtitle mb-0">
            Analisis spend, reach, click, lead form, WhatsApp chat, dan kesehatan campaign Meta Ads.
        </p>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-header d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h5 class="content-card-title mb-1">Meta Ads Summary</h5>
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
                        <div class="small text-muted mt-2">
                            Last sync: <strong>{{ $formatDate($metaAdsDashboardInsight['last_synced_at'] ?? null) }}</strong>
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
                                <strong class="text-success">{{ $formatNumber($metaOverview['healthy_count'] ?? 0) }}</strong>
                            </div>
                            <div class="marketing-health-item">
                                <span>Attention</span>
                                <strong class="text-warning">{{ $formatNumber($metaOverview['attention_count'] ?? 0) }}</strong>
                            </div>
                            <div class="marketing-health-item">
                                <span>Critical</span>
                                <strong class="text-danger">{{ $formatNumber($metaOverview['critical_count'] ?? 0) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-8">
                    <div class="marketing-detail-card h-100">
                        <div class="marketing-detail-label">Best Campaign</div>

                        @if($metaBestCampaign)
                            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mt-3">
                                <div>
                                    <div class="fw-bold text-dark">
                                        {{ data_get($metaBestCampaign, 'campaign_name', '-') }}
                                    </div>
                                    <div class="small text-muted mt-1">
                                        {{ data_get($metaBestCampaign, 'lead_form_submission', 0) }} lead
                                        · {{ data_get($metaBestCampaign, 'whatsapp_chat', 0) }} WhatsApp
                                        · {{ data_get($metaBestCampaign, 'link_click', 0) }} link clicks
                                    </div>
                                </div>

                                <span class="badge rounded-pill {{ data_get($metaBestCampaign, 'health_badge_class', 'bg-light text-muted') }}">
                                    {{ data_get($metaBestCampaign, 'health_label', 'Tracked') }}
                                </span>
                            </div>

                            <div class="small text-muted mt-3">
                                Spend {{ data_get($metaBestCampaign, 'spend_label', 'Rp 0') }}
                                · CPL {{ data_get($metaBestCampaign, 'cost_per_lead_label', '-') }}
                                · Cost/WA {{ data_get($metaBestCampaign, 'cost_per_whatsapp_chat_label', '-') }}
                            </div>
                        @else
                            <div class="text-muted mt-3">Belum ada campaign terbaik yang bisa ditentukan.</div>
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
                                                            <p class="text-muted mb-3">
                                                                {{ $campaignAiSummary['summary'] ?? 'Summary campaign belum tersedia.' }}
                                                            </p>

                                                            <div class="campaign-ai-box-label">Main Bottleneck</div>
                                                            <div class="campaign-ai-bottleneck-box">
                                                                {{ $campaignAiSummary['main_bottleneck'] ?? 'Belum ada bottleneck utama.' }}
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
                                                                                            <div class="small text-muted mt-1">
                                                                                                {{ data_get($factor, 'evidence') }}
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
                                                                            <li>{{ is_array($step)
                                                                                ? data_get($step, 'action', data_get($step, 'step', '-'))
                                                                                : $step }}</li>
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
                        <div class="small text-muted mt-2">
                            Last sync: <strong>{{ $formatDate($googleAnalyticsDashboardInsight['last_synced_at'] ?? null) }}</strong>
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
                        <div class="small text-muted mt-2">
                            Last sync: <strong>{{ $formatDate($googleAdsDashboardInsight['last_synced_at'] ?? null) }}</strong>
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
                                <strong class="text-success">{{ $formatNumber($googleAdsOverview['healthy_count'] ?? 0) }}</strong>
                            </div>
                            <div class="marketing-health-item">
                                <span>Attention</span>
                                <strong class="text-warning">{{ $formatNumber($googleAdsOverview['attention_count'] ?? 0) }}</strong>
                            </div>
                            <div class="marketing-health-item">
                                <span>Critical</span>
                                <strong class="text-danger">{{ $formatNumber($googleAdsOverview['critical_count'] ?? 0) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-8">
                    <div class="marketing-detail-card h-100">
                        <div class="marketing-detail-label">Best Campaign</div>

                        @if($googleAdsBestCampaign)
                            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mt-3">
                                <div>
                                    <div class="fw-bold text-dark">
                                        {{ data_get($googleAdsBestCampaign, 'campaign_name')
                                            ?: data_get($googleAdsBestCampaign, 'name')
                                            ?: '-' }}
                                    </div>
                                    <div class="small text-muted mt-1">
                                        {{ $formatNumber(data_get($googleAdsBestCampaign, 'clicks', 0)) }} clicks
                                        · {{ $formatNumber(data_get($googleAdsBestCampaign, 'conversions', 0), 1) }} conversions
                                    </div>
                                </div>

                                <span class="badge rounded-pill {{ data_get($googleAdsBestCampaign, 'health_badge_class')
                                    ?: $healthBadgeClass(data_get($googleAdsBestCampaign, 'health_type')) }}">
                                    {{ data_get($googleAdsBestCampaign, 'health_label')
                                        ?: \Illuminate\Support\Str::headline(data_get($googleAdsBestCampaign, 'health_type', 'tracked')) }}
                                </span>
                            </div>

                            <div class="small text-muted mt-3">
                                Cost {{ data_get($googleAdsBestCampaign, 'cost_label')
                                    ?: $formatCurrency(data_get($googleAdsBestCampaign, 'cost', 0)) }}
                                · Cost/conversion {{ data_get($googleAdsBestCampaign, 'cost_per_conversion_label', '-') }}
                            </div>
                        @else
                            <div class="text-muted mt-3">Belum ada campaign terbaik yang bisa ditentukan.</div>
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
        <h4 class="dashboard-section-title mb-1">Marketing Work Progress</h4>
        <p class="dashboard-section-subtitle mb-0">
            Pantau progres pekerjaan Marketing berdasarkan status, deadline, PIC, dan aktivitas terbaru dari Trello.
        </p>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-header d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h5 class="content-card-title mb-1">Marketing Work Progress</h5>
                <p class="content-card-subtitle mb-0">
                    Ringkasan pekerjaan operasional dari board {{ $trelloMarketingBoardName }}.
                </p>
            </div>

            <span class="badge rounded-pill {{ $trelloMarketingIsSynced
                ? 'bg-success-subtle text-success'
                : 'bg-warning-subtle text-warning' }}">
                <i class="bi {{ $trelloMarketingIsSynced ? 'bi-cloud-check-fill' : 'bi-cloud-slash-fill' }} me-1"></i>
                {{ $trelloMarketingIsSynced ? 'Synced' : 'Not Synced' }}
            </span>
        </div>

        <div class="content-card-body">
            <div class="trello-insight-box mb-3">
                <div class="d-flex align-items-start gap-3">
                    <div class="trello-insight-icon"><i class="bi bi-megaphone-fill"></i></div>
                    <div>
                        <div class="fw-semibold text-dark mb-1">Marketing Work Insight</div>
                        <p class="text-muted mb-0">{{ $trelloMarketingInsight }}</p>
                        <div class="small text-muted mt-2">
                            Last sync: <strong>{{ $trelloMarketingLastSyncedText }}</strong>
                            <span class="mx-1">•</span>
                            Last webhook: <strong>{{ $trelloMarketingLastWebhookText }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="work-progress-completion-card mb-4">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-3">
                    <div>
                        <div class="work-progress-completion-eyebrow">Marketing Progress</div>
                        <div class="work-progress-completion-value">
                            {{ $formatNumber($trelloMarketingCompletionRate) }}%
                        </div>
                        <div class="work-progress-completion-label">
                            {{ $formatNumber($trelloMarketingCompleted) }}
                            dari
                            {{ $formatNumber($trelloMarketingTotalOpenCards) }}
                            card sudah selesai.
                        </div>
                    </div>

                    <div class="work-progress-completion-meta text-lg-end">
                        <div class="small text-muted">Active Work</div>
                        <div class="fw-semibold text-dark">
                            {{ $formatNumber($trelloMarketingActiveWork) }} card berjalan
                        </div>
                    </div>
                </div>

                <div class="progress progress-modern work-progress-completion-track mb-3">
                    <div
                        class="progress-bar {{ $trelloMarketingProgressClass }}"
                        role="progressbar"
                        style="width: {{ $trelloMarketingCompletionRate }}%;"
                        aria-valuenow="{{ $trelloMarketingCompletionRate }}"
                        aria-valuemin="0"
                        aria-valuemax="100"
                    ></div>
                </div>

                <div class="row g-3">
                    <div class="col-xl-4 col-md-4">
                        <div class="work-progress-mini-metric">
                            <span>Due Today</span>
                            <strong class="{{ $trelloMarketingDueTodayClass }}">
                                {{ $formatNumber($trelloMarketingDueToday) }}
                            </strong>
                        </div>
                    </div>

                    <div class="col-xl-4 col-md-4">
                        <div class="work-progress-mini-metric">
                            <span>Overdue</span>
                            <strong class="{{ $trelloMarketingOverdueClass }}">
                                {{ $formatNumber($trelloMarketingOverdue) }}
                            </strong>
                        </div>
                    </div>

                    <div class="col-xl-4 col-md-4">
                        <div class="work-progress-mini-metric">
                            <span>Unmapped</span>
                            <strong>{{ $formatNumber($trelloMarketingUnmapped) }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                @foreach(['todo', 'in_progress', 'review', 'done'] as $statusKey)
                    @php
                        $statusTotal = (int) ($trelloMarketingStatuses[$statusKey] ?? 0);
                        $statusLabel = $trelloStatusLabels[$statusKey] ?? \Illuminate\Support\Str::headline($statusKey);
                        $statusClass = $trelloStatusBadgeClasses[$statusKey] ?? 'bg-light text-muted';
                        $statusIcon = $trelloStatusIcons[$statusKey] ?? 'bi-circle';

                        $statusDescription = match ($statusKey) {
                            'todo' => 'Task yang sudah masuk antrean kerja dan menunggu eksekusi.',
                            'in_progress' => 'Task yang sedang dikerjakan oleh tim Marketing.',
                            'review' => 'Task yang sudah dikerjakan dan menunggu pengecekan.',
                            'done' => 'Task yang sudah selesai dan tercatat sebagai completed.',
                            default => 'Status pekerjaan Marketing.',
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
                            <div class="stat-description">{{ $statusDescription }}</div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($trelloMarketingUnmapped > 0)
                <div class="alert alert-warning mb-4">
                    Ada {{ $formatNumber($trelloMarketingUnmapped) }} card yang belum punya status dashboard.
                    Jalankan mapping list sebelum angka dipakai untuk keputusan operasional.
                </div>
            @endif

            <div class="row g-3 trello-table-row">
                <div class="col-12 d-flex flex-column trello-table-column">
                    <div class="trello-table-card flex-fill">
                        <div class="trello-table-header">
                            <div>
                                <div class="fw-semibold text-dark">Priority Cards</div>
                                <div class="small text-muted">Card dengan deadline hari ini atau sudah melewati deadline.</div>
                            </div>
                            <span class="badge rounded-pill bg-danger-subtle text-danger">
                                {{ $formatNumber($trelloMarketingPriorityCards->count()) }} card
                            </span>
                        </div>

                        @if($trelloMarketingPriorityCards->count())
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
                                    <tbody class="trello-load-more-list auto-expand-list is-collapsed" data-initial-visible="4">
                                        @foreach($trelloMarketingPriorityCards as $card)
                                            @php
                                                $cardStatus = \Illuminate\Support\Str::of(
                                                    data_get($card, 'normalized_status')
                                                        ?: data_get($card, 'status')
                                                        ?: 'unmapped'
                                                )->lower()->replace([' ', '-'], '_')->toString();

                                                $cardDueAt = data_get($card, 'due_at')
                                                    ?: data_get($card, 'due')
                                                    ?: data_get($card, 'due_date');

                                                $cardUrl = data_get($card, 'short_url')
                                                    ?: data_get($card, 'url')
                                                    ?: data_get($card, 'card_url');

                                                $cardMembers = collect(data_get($card, 'members', []));
                                                $cardMemberNames = $cardMembers->pluck('name')->filter()->implode(', ');
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
                                                                <div class="work-card-avatar" title="{{ data_get($member, 'name', 'PIC') }}">
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
                                                                <div class="work-card-avatar is-empty" title="No PIC"><span>?</span></div>
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
                                                        <a href="{{ $cardUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-light">
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
                                <div class="empty-state-icon"><i class="bi bi-check2-circle"></i></div>
                                <h5 class="empty-state-title">Tidak ada priority card</h5>
                                <p class="empty-state-text mb-0">
                                    Belum ada card Marketing dengan deadline hari ini atau overdue.
                                </p>
                            </div>
                        @endif
                    </div>

                    @if($trelloMarketingPriorityCards->count() > 4)
                        <div class="auto-expand-trigger trello-auto-expand-trigger" aria-hidden="true"></div>
                    @endif
                </div>

                <div class="col-12 d-flex flex-column trello-table-column">
                    <div class="trello-table-card flex-fill">
                        <div class="trello-table-header">
                            <div>
                                <div class="fw-semibold text-dark">Active Work Queue</div>
                                <div class="small text-muted">Card aktif di To Do, Doing, Review, atau Scheduled.</div>
                            </div>
                            <span class="badge rounded-pill bg-primary-subtle text-primary">
                                {{ $formatNumber($trelloMarketingActiveCards->count()) }} card
                            </span>
                        </div>

                        @if($trelloMarketingActiveCards->count())
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
                                    <tbody class="trello-load-more-list auto-expand-list is-collapsed" data-initial-visible="4">
                                        @foreach($trelloMarketingActiveCards as $card)
                                            @php
                                                $cardStatus = \Illuminate\Support\Str::of(
                                                    data_get($card, 'normalized_status')
                                                        ?: data_get($card, 'status')
                                                        ?: 'unmapped'
                                                )->lower()->replace([' ', '-'], '_')->toString();

                                                $cardLastActivity = data_get($card, 'last_activity_at')
                                                    ?: data_get($card, 'date_last_activity')
                                                    ?: data_get($card, 'updated_at');

                                                $cardUrl = data_get($card, 'short_url')
                                                    ?: data_get($card, 'url')
                                                    ?: data_get($card, 'card_url');

                                                $cardMembers = collect(data_get($card, 'members', []));
                                                $cardMemberNames = $cardMembers->pluck('name')->filter()->implode(', ');
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
                                                                <div class="work-card-avatar" title="{{ data_get($member, 'name', 'PIC') }}">
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
                                                                <div class="work-card-avatar is-empty" title="No PIC"><span>?</span></div>
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
                                                        <a href="{{ $cardUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-light">
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
                                <div class="empty-state-icon"><i class="bi bi-kanban"></i></div>
                                <h5 class="empty-state-title">Tidak ada active work</h5>
                                <p class="empty-state-text mb-0">
                                    Belum ada card Marketing di status To Do, Doing, Review, atau Scheduled.
                                </p>
                            </div>
                        @endif
                    </div>

                    @if($trelloMarketingActiveCards->count() > 4)
                        <div class="auto-expand-trigger trello-auto-expand-trigger" aria-hidden="true"></div>
                    @endif
                </div>
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
    .marketing-ai-bottleneck-card,
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
    .marketing-ai-bottleneck-icon,
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

    .marketing-ai-bottleneck-card {
        padding: 1.1rem;
        background: linear-gradient(135deg, rgba(239, 68, 68, .07), rgba(255, 190, 4, .06));
    }

    .marketing-ai-bottleneck-icon {
        width: 34px;
        height: 34px;
        border-radius: 12px;
        color: #dc2626;
        background: rgba(239, 68, 68, .12);
    }

    .marketing-ai-bottleneck-value {
        color: #111827;
        font-size: 1.05rem;
        font-weight: 800;
        line-height: 1.5;
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
        border-left: 3px solid rgba(91, 62, 142, .45);
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
        border-left: 3px solid rgba(91, 62, 142, .45);
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
        background: rgba(244, 126, 32, .10);
        overflow: hidden;
    }

    .ga-funnel-bar {
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, #f47e20, #5B3E8E);
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
