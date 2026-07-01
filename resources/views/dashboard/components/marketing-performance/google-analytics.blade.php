@php
    /*
    |--------------------------------------------------------------------------
    | Google Analytics Dashboard Data Contract
    |--------------------------------------------------------------------------
    | DashboardController mengirim variable:
    |
    | $googleAnalyticsDashboardInsight = [
    |     'is_available' => true,
    |     'period' => [...],
    |     'last_synced_at' => '...',
    |     'summary_text' => '...',
    |     'ai_summary' => [...],
    |     'kpis' => [...],
    |     'acquisition' => [...],
    |     'landing_pages' => [...],
    |     'conversion_funnel' => [...],
    |     'content_pages' => [...],
    |     'devices' => [...],
    |     'locations' => [...],
    | ];
    */

    $googleAnalyticsDashboardInsight = $googleAnalyticsDashboardInsight ?? [];

    $gaIsAvailable = (bool) ($googleAnalyticsDashboardInsight['is_available'] ?? false);
    $gaPeriod = $googleAnalyticsDashboardInsight['period'] ?? [];
    $gaLastSyncedAt = $googleAnalyticsDashboardInsight['last_synced_at'] ?? null;
    $gaSummaryText = trim((string) ($googleAnalyticsDashboardInsight['summary_text'] ?? ''));
    $gaAiSummary = is_array($googleAnalyticsDashboardInsight['ai_summary'] ?? null)
        ? $googleAnalyticsDashboardInsight['ai_summary']
        : [];
    $gaErrorMessage = $googleAnalyticsDashboardInsight['error_message'] ?? null;

    $gaKpis = $googleAnalyticsDashboardInsight['kpis'] ?? [
        'total_users' => 0,
        'new_users' => 0,
        'sessions' => 0,
        'engaged_sessions' => 0,
        'engagement_rate' => 0,
        'bounce_rate' => 0,
        'average_engagement_time_label' => '0s',
        'key_events' => 0,
        'key_event_rate' => 0,
    ];

    $gaAcquisition = $googleAnalyticsDashboardInsight['acquisition'] ?? [
        'channels' => [],
        'sources' => [],
        'campaigns' => [],
    ];

    $gaLandingPages = collect($googleAnalyticsDashboardInsight['landing_pages'] ?? []);
    $gaConversionFunnel = collect($googleAnalyticsDashboardInsight['conversion_funnel'] ?? []);
    $gaContentPages = collect($googleAnalyticsDashboardInsight['content_pages'] ?? []);
    $gaDevices = collect($googleAnalyticsDashboardInsight['devices'] ?? []);
    $gaLocations = collect($googleAnalyticsDashboardInsight['locations'] ?? []);
@endphp

<div class="google-analytics-dashboard-card" id="google-analytics-performance">
    <div class="content-card-header d-flex justify-content-between align-items-start gap-3 flex-wrap px-0 pt-0">
        <div>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                <span class="badge rounded-pill bg-primary-subtle text-primary">
                    <i class="bi bi-graph-up-arrow me-1"></i>
                    Google Analytics
                </span>

                @if($gaIsAvailable)
                    <span class="badge rounded-pill bg-success-subtle text-success">
                        Synced
                    </span>
                @else
                    <span class="badge rounded-pill bg-warning-subtle text-warning">
                        Not Synced
                    </span>
                @endif

                @if(! empty($gaAiSummary))
                    <span class="badge rounded-pill bg-success-subtle text-success">
                        <i class="bi bi-stars me-1"></i>
                        AI Ready
                    </span>
                @else
                    <span class="badge rounded-pill bg-light text-muted">
                        Local Summary
                    </span>
                @endif
            </div>

            <h5 class="content-card-title mb-1">Google Analytics Performance</h5>
            <p class="content-card-subtitle mb-0">
                Monitoring traffic website, kualitas audience, performa channel, landing page, conversion event,
                serta device/location yang berpengaruh ke keputusan marketing.
            </p>
        </div>

        <div class="text-lg-end small text-muted">
            <div>
                Periode:
                <strong class="text-dark">
                    {{ $gaPeriod['date_start'] ?? '-' }} — {{ $gaPeriod['date_stop'] ?? '-' }}
                </strong>
            </div>
            <div>
                Last sync:
                <strong class="text-dark">
                    {{ $gaLastSyncedAt ?: '-' }}
                </strong>
            </div>

            @if(! empty($gaAiSummary['generated_at']))
                <div>
                    AI generated:
                    <strong class="text-dark">
                        {{ \Carbon\Carbon::parse($gaAiSummary['generated_at'])->format('d M Y H:i') }}
                    </strong>
                </div>
            @endif
        </div>
    </div>

    <div class="content-card-body px-0 pb-0">
        @include('dashboard.components.marketing-performance.google-analytics.ai-summary', [
            'gaAiSummary' => $gaAiSummary,
            'gaSummaryText' => $gaSummaryText,
        ])

        @if(! $gaIsAvailable)
            <div class="empty-state-box mb-3">
                <div class="empty-state-icon">
                    <i class="bi bi-bar-chart-line"></i>
                </div>
                <h5 class="empty-state-title">Google Analytics belum tersambung</h5>
                <p class="empty-state-text mb-3">
                    Tab ini sudah siap. Setelah sync Google Analytics dijalankan, data traffic, channel,
                    landing page, conversion, content, device, dan location akan muncul di sini.
                </p>

                <code class="d-inline-block bg-white border rounded-3 px-3 py-2">
                    php artisan google-analytics:sync-dashboard --with-ai
                </code>

                @if($gaErrorMessage)
                    <div class="alert alert-warning mt-3 mb-0 text-start">
                        {{ $gaErrorMessage }}
                    </div>
                @endif
            </div>
        @endif

        @include('dashboard.components.marketing-performance.google-analytics.kpi-summary', [
            'gaKpis' => $gaKpis,
        ])

        @include('dashboard.components.marketing-performance.google-analytics.acquisition-breakdown', [
            'gaAcquisition' => $gaAcquisition,
        ])

        @include('dashboard.components.marketing-performance.google-analytics.landing-pages', [
            'gaLandingPages' => $gaLandingPages,
        ])

        @include('dashboard.components.marketing-performance.google-analytics.conversion-funnel', [
            'gaConversionFunnel' => $gaConversionFunnel,
        ])

        @include('dashboard.components.marketing-performance.google-analytics.content-performance', [
            'gaContentPages' => $gaContentPages,
        ])

        @include('dashboard.components.marketing-performance.google-analytics.device-location', [
            'gaDevices' => $gaDevices,
            'gaLocations' => $gaLocations,
        ])
    </div>
</div>