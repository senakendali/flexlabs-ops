@php
    $googleAdsDashboardInsight = $googleAdsDashboardInsight ?? [];

    $googleAdsIsAvailable = (bool) ($googleAdsDashboardInsight['is_available'] ?? false);
    $googleAdsPeriod = $googleAdsDashboardInsight['period'] ?? [];
    $googleAdsOverview = $googleAdsDashboardInsight['overview'] ?? [];
    $googleAdsCampaigns = collect($googleAdsDashboardInsight['campaigns'] ?? []);
    $googleAdsSummaryText = trim((string) ($googleAdsDashboardInsight['summary_text'] ?? ''));
    $googleAdsAiSummary = is_array($googleAdsDashboardInsight['ai_summary'] ?? null)
        ? $googleAdsDashboardInsight['ai_summary']
        : [];
    $googleAdsLastSyncedAt = $googleAdsDashboardInsight['last_synced_at'] ?? null;
    $googleAdsErrorMessage = $googleAdsDashboardInsight['error_message'] ?? null;
@endphp

<div class="google-ads-dashboard-card" id="google-ads-performance">
    <div class="content-card-header d-flex justify-content-between align-items-start gap-3 flex-wrap px-0 pt-0">
        <div>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                <span class="badge rounded-pill bg-primary-subtle text-primary">
                    <i class="bi bi-google me-1"></i>
                    Google Ads
                </span>

                @if($googleAdsIsAvailable)
                    <span class="badge rounded-pill bg-success-subtle text-success">
                        Synced
                    </span>
                @else
                    <span class="badge rounded-pill bg-warning-subtle text-warning">
                        Not Synced
                    </span>
                @endif

                @if(! empty($googleAdsAiSummary))
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

            <h5 class="content-card-title mb-1">Google Ads Performance</h5>
            <p class="content-card-subtitle mb-0">
                Monitoring spend, impressions, clicks, CTR, CPC, conversions, cost per conversion,
                dan campaign yang perlu dioptimasi.
            </p>
        </div>

        <div class="text-lg-end small text-muted">
            <div>
                Periode:
                <strong class="text-dark">
                    {{ $googleAdsPeriod['date_start'] ?? '-' }} — {{ $googleAdsPeriod['date_stop'] ?? '-' }}
                </strong>
            </div>
            <div>
                Last sync:
                <strong class="text-dark">
                    {{ $googleAdsLastSyncedAt ?: '-' }}
                </strong>
            </div>

            @if(! empty($googleAdsAiSummary['generated_at']))
                <div>
                    AI generated:
                    <strong class="text-dark">
                        {{ \Carbon\Carbon::parse($googleAdsAiSummary['generated_at'])->format('d M Y H:i') }}
                    </strong>
                </div>
            @endif
        </div>
    </div>

    <div class="content-card-body px-0 pb-0">
        @include('dashboard.components.marketing-performance.google-ads.ai-summary', [
            'googleAdsAiSummary' => $googleAdsAiSummary,
            'googleAdsSummaryText' => $googleAdsSummaryText,
        ])

        @if(! $googleAdsIsAvailable)
            <div class="empty-state-box mb-3">
                <div class="empty-state-icon">
                    <i class="bi bi-megaphone"></i>
                </div>
                <h5 class="empty-state-title">Google Ads belum tersambung</h5>
                <p class="empty-state-text mb-3">
                    Jalankan sync Google Ads untuk menampilkan spend, klik, conversion, dan campaign performance.
                </p>

                <code class="d-inline-block bg-white border rounded-3 px-3 py-2">
                    php artisan google-ads:sync-dashboard --with-ai
                </code>

                @if($googleAdsErrorMessage)
                    <div class="alert alert-warning mt-3 mb-0 text-start">
                        {{ $googleAdsErrorMessage }}
                    </div>
                @endif
            </div>
        @endif

        @include('dashboard.components.marketing-performance.google-ads.kpi-summary', [
            'overview' => $googleAdsOverview,
        ])

        @include('dashboard.components.marketing-performance.google-ads.campaign-performance', [
            'campaigns' => $googleAdsCampaigns,
        ])
    </div>
</div>