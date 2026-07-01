@php
    /*
    |--------------------------------------------------------------------------
    | Meta Ads Dashboard Values
    |--------------------------------------------------------------------------
    | Data disiapkan dari DashboardController melalui metaAdsDashboardInsight.
    | Partial ini sengaja dibuat self-contained supaya dashboard utama tetap ringan.
    */
    $metaAdsDashboardInsight = $metaAdsDashboardInsight ?? [];
    $metaAdsOverview = $metaAdsDashboardInsight['overview'] ?? [];
    $metaAdsCampaigns = collect($metaAdsDashboardInsight['campaigns'] ?? []);
    $metaAdsPeriod = $metaAdsDashboardInsight['period'] ?? [];
    $metaAdsIsAvailable = (bool) ($metaAdsDashboardInsight['is_available'] ?? false);
    $metaAdsSummaryText = trim((string) ($metaAdsDashboardInsight['summary_text'] ?? ''));
    $metaAdsLastSyncedAt = $metaAdsDashboardInsight['last_synced_at'] ?? null;
    $metaAdsErrorMessage = $metaAdsDashboardInsight['error_message'] ?? null;
@endphp

<div class="meta-ads-dashboard-card" id="meta-ads-performance">
    <div class="content-card-header d-flex justify-content-between align-items-start gap-3 flex-wrap">
        <div>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                <span class="badge rounded-pill bg-primary-subtle text-primary">
                    <i class="bi bi-megaphone-fill me-1"></i>
                    Meta Ads
                </span>

                @if($metaAdsIsAvailable)
                    <span class="badge rounded-pill bg-success-subtle text-success">
                        Synced
                    </span>
                @else
                    <span class="badge rounded-pill bg-warning-subtle text-warning">
                        Not Synced
                    </span>
                @endif
            </div>

            <h5 class="content-card-title mb-1">Meta Ads Performance</h5>
            <p class="content-card-subtitle mb-0">
                Analisis performa campaign secara terpisah untuk membantu owner melihat campaign yang berjalan efektif, area yang perlu diperbaiki, dan prioritas optimasi berikutnya.
            </p>
        </div>

        <div class="text-lg-end small text-muted">
            <div>
                Periode:
                <strong class="text-dark">
                    {{ $metaAdsPeriod['date_start'] ?? '-' }} — {{ $metaAdsPeriod['date_stop'] ?? '-' }}
                </strong>
            </div>
            <div>
                Last sync:
                <strong class="text-dark">
                    {{ $metaAdsLastSyncedAt ?: '-' }}
                </strong>
            </div>
        </div>
    </div>

    <div class="content-card-body">
        @if($metaAdsSummaryText !== '')
            <div class="meta-ads-insight-box mb-3">
                <div class="d-flex align-items-start gap-3">
                    <div class="meta-ads-insight-icon">
                        <i class="bi bi-robot"></i>
                    </div>
                    <div>
                        <div class="fw-semibold text-dark mb-1">AI Meta Ads Summary</div>
                        <p class="text-muted mb-0">{{ $metaAdsSummaryText }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if(! $metaAdsIsAvailable || $metaAdsCampaigns->isEmpty())
            <div class="empty-state-box my-0">
                <div class="empty-state-icon">
                    <i class="bi bi-megaphone"></i>
                </div>
                <h5 class="empty-state-title">Data Meta Ads belum tersedia</h5>
                <p class="empty-state-text mb-3">
                    Jalankan sync Meta Ads terlebih dulu supaya dashboard bisa menampilkan campaign dan insight.
                </p>
                <code class="d-inline-block bg-white border rounded-3 px-3 py-2">
                    php artisan meta-ads:sync-campaign-insights
                </code>

                @if($metaAdsErrorMessage)
                    <div class="alert alert-warning mt-3 mb-0 text-start">
                        {{ $metaAdsErrorMessage }}
                    </div>
                @endif
            </div>
        @else
            <ul class="nav nav-pills work-progress-tabs meta-ads-campaign-tabs dashboard-nested-tabs flex-nowrap overflow-auto" id="metaAdsDashboardTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button
                        class="nav-link active"
                        id="meta-ads-overview-tab"
                        data-bs-toggle="pill"
                        data-bs-target="#meta-ads-overview-pane"
                        type="button"
                        role="tab"
                        aria-controls="meta-ads-overview-pane"
                        aria-selected="true"
                    >
                        Overview
                    </button>
                </li>

                @foreach($metaAdsCampaigns as $campaign)
                    @php
                        $metaAdsCampaignTabId = 'meta-ads-campaign-' . $loop->iteration;
                    @endphp

                    <li class="nav-item" role="presentation">
                        <button
                            class="nav-link text-nowrap"
                            id="{{ $metaAdsCampaignTabId }}-tab"
                            data-bs-toggle="pill"
                            data-bs-target="#{{ $metaAdsCampaignTabId }}-pane"
                            type="button"
                            role="tab"
                            aria-controls="{{ $metaAdsCampaignTabId }}-pane"
                            aria-selected="false"
                        >
                            {{ \Illuminate\Support\Str::limit($campaign['campaign_name'] ?? 'Campaign', 34) }}
                        </button>
                    </li>
                @endforeach
            </ul>

            <div class="tab-content pt-3" id="metaAdsDashboardTabsContent">
                <div
                    class="tab-pane fade show active"
                    id="meta-ads-overview-pane"
                    role="tabpanel"
                    aria-labelledby="meta-ads-overview-tab"
                    tabindex="0"
                >
                    <div class="row g-3 mb-3">
                        <div class="col-xl-3 col-md-6">
                            <div class="meta-ads-kpi-card h-100">
                                <div class="meta-ads-kpi-label">Total Spend</div>
                                <div class="meta-ads-kpi-value">{{ $metaAdsOverview['total_spend_label'] ?? 'Rp 0' }}</div>
                                <div class="meta-ads-kpi-help">Total biaya campaign periode terbaru.</div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="meta-ads-kpi-card h-100">
                                <div class="meta-ads-kpi-label">Reach</div>
                                <div class="meta-ads-kpi-value">{{ number_format((int) ($metaAdsOverview['total_reach'] ?? 0)) }}</div>
                                <div class="meta-ads-kpi-help">Orang unik yang melihat iklan.</div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="meta-ads-kpi-card h-100">
                                <div class="meta-ads-kpi-label">Impressions</div>
                                <div class="meta-ads-kpi-value">{{ number_format((int) ($metaAdsOverview['total_impressions'] ?? 0)) }}</div>
                                <div class="meta-ads-kpi-help">Total tayangan iklan.</div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="meta-ads-kpi-card h-100">
                                <div class="meta-ads-kpi-label">Campaign</div>
                                <div class="meta-ads-kpi-value">{{ number_format((int) ($metaAdsOverview['campaign_count'] ?? 0)) }}</div>
                                <div class="meta-ads-kpi-help">Campaign pada periode terbaru.</div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="meta-ads-kpi-card h-100">
                                <div class="meta-ads-kpi-label">Engagement</div>
                                <div class="meta-ads-kpi-value">{{ number_format((int) ($metaAdsOverview['total_engagement'] ?? 0)) }}</div>
                                <div class="meta-ads-kpi-help">Post/page engagement.</div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="meta-ads-kpi-card h-100">
                                <div class="meta-ads-kpi-label">Link Click</div>
                                <div class="meta-ads-kpi-value">{{ number_format((int) ($metaAdsOverview['total_link_click'] ?? 0)) }}</div>
                                <div class="meta-ads-kpi-help">Klik menuju tujuan iklan.</div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="meta-ads-kpi-card h-100">
                                <div class="meta-ads-kpi-label">Lead Form</div>
                                <div class="meta-ads-kpi-value">{{ number_format((int) ($metaAdsOverview['total_lead_form_submission'] ?? 0)) }}</div>
                                <div class="meta-ads-kpi-help">CPL: {{ $metaAdsOverview['cost_per_lead_label'] ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="meta-ads-kpi-card h-100">
                                <div class="meta-ads-kpi-label">WhatsApp Chat</div>
                                <div class="meta-ads-kpi-value">{{ number_format((int) ($metaAdsOverview['total_whatsapp_chat'] ?? 0)) }}</div>
                                <div class="meta-ads-kpi-help">Cost/chat: {{ $metaAdsOverview['cost_per_whatsapp_chat_label'] ?? '-' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-lg-5">
                            <div class="meta-ads-detail-card h-100">
                                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                    <div>
                                        <div class="fw-semibold text-dark">Campaign terbaik</div>
                                        <div class="small text-muted">Dipilih dari CPL/Cost per WhatsApp terbaik.</div>
                                    </div>
                                    <span class="badge rounded-pill bg-success-subtle text-success">Best</span>
                                </div>

                                @php
                                    $metaAdsBestCampaign = $metaAdsOverview['best_campaign'] ?? null;
                                @endphp

                                @if($metaAdsBestCampaign)
                                    <div class="fw-bold text-dark mb-1">{{ $metaAdsBestCampaign['campaign_name'] ?? '-' }}</div>
                                    <div class="small text-muted mb-3">
                                        {{ $metaAdsBestCampaign['date_start'] ?? '-' }} — {{ $metaAdsBestCampaign['date_stop'] ?? '-' }}
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <div class="meta-ads-mini-metric">
                                                <span>Spend</span>
                                                <strong>{{ $metaAdsBestCampaign['spend_label'] ?? 'Rp 0' }}</strong>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="meta-ads-mini-metric">
                                                <span>Lead</span>
                                                <strong>{{ number_format((int) ($metaAdsBestCampaign['lead_form_submission'] ?? 0)) }}</strong>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="meta-ads-mini-metric">
                                                <span>WA Chat</span>
                                                <strong>{{ number_format((int) ($metaAdsBestCampaign['whatsapp_chat'] ?? 0)) }}</strong>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="meta-ads-mini-metric">
                                                <span>CPL</span>
                                                <strong>{{ $metaAdsBestCampaign['cost_per_lead_label'] ?? '-' }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <p class="text-muted mb-0">Belum ada campaign yang menghasilkan lead atau WhatsApp chat.</p>
                                @endif
                            </div>
                        </div>

                        <div class="col-lg-7">
                            <div class="meta-ads-detail-card h-100">
                                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                    <div>
                                        <div class="fw-semibold text-dark">Campaign perlu perhatian</div>
                                        <div class="small text-muted">Campaign dengan status critical, warning, atau action.</div>
                                    </div>
                                    <span class="badge rounded-pill bg-warning-subtle text-warning">
                                        {{ number_format((int) (($metaAdsOverview['critical_count'] ?? 0) + ($metaAdsOverview['attention_count'] ?? 0))) }} item
                                    </span>
                                </div>

                                @php
                                    $metaAdsAttentionCampaigns = collect($metaAdsOverview['attention_campaigns'] ?? []);
                                @endphp

                                @if($metaAdsAttentionCampaigns->isEmpty())
                                    <div class="empty-state-box my-0 py-4">
                                        <div class="empty-state-icon">
                                            <i class="bi bi-check2-circle"></i>
                                        </div>
                                        <h5 class="empty-state-title">Tidak ada campaign kritis</h5>
                                        <p class="empty-state-text mb-0">Campaign belum masuk status critical/warning berdasarkan data terbaru.</p>
                                    </div>
                                @else
                                    <div class="table-responsive">
                                        <table class="table table-modern align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Campaign</th>
                                                    <th>Status</th>
                                                    <th class="text-end">Spend</th>
                                                    <th class="text-end">Lead</th>
                                                    <th class="text-end">WA</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($metaAdsAttentionCampaigns as $campaign)
                                                    <tr>
                                                        <td>
                                                            <div class="fw-semibold text-dark">{{ \Illuminate\Support\Str::limit($campaign['campaign_name'] ?? '-', 42) }}</div>
                                                            <div class="small text-muted">{{ $campaign['date_start'] ?? '-' }} — {{ $campaign['date_stop'] ?? '-' }}</div>
                                                        </td>
                                                        <td>
                                                            <span class="badge rounded-pill {{ $campaign['health_badge_class'] ?? 'bg-light text-muted' }}">
                                                                {{ $campaign['health_label'] ?? 'Monitor' }}
                                                            </span>
                                                        </td>
                                                        <td class="text-end">{{ $campaign['spend_label'] ?? 'Rp 0' }}</td>
                                                        <td class="text-end">{{ number_format((int) ($campaign['lead_form_submission'] ?? 0)) }}</td>
                                                        <td class="text-end">{{ number_format((int) ($campaign['whatsapp_chat'] ?? 0)) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                @foreach($metaAdsCampaigns as $campaign)
                    @php
                        $metaAdsCampaignTabId = 'meta-ads-campaign-' . $loop->iteration;
                        $metaAdsAiSummary = $campaign['ai_summary'] ?? [];
                        $metaAdsBlockingFactors = collect($metaAdsAiSummary['blocking_factors'] ?? []);
                        $metaAdsRecommendedSteps = collect($metaAdsAiSummary['recommended_steps'] ?? []);
                        $metaAdsActions = collect($campaign['actions'] ?? []);
                    @endphp

                    <div
                        class="tab-pane fade"
                        id="{{ $metaAdsCampaignTabId }}-pane"
                        role="tabpanel"
                        aria-labelledby="{{ $metaAdsCampaignTabId }}-tab"
                        tabindex="0"
                    >
                        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3 mb-3">
                            <div>
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                    <span class="badge rounded-pill {{ $campaign['health_badge_class'] ?? 'bg-light text-muted' }}">
                                        {{ $campaign['health_label'] ?? 'Monitor' }}
                                    </span>
                                    <span class="badge rounded-pill bg-light text-muted">
                                        {{ $campaign['date_start'] ?? '-' }} — {{ $campaign['date_stop'] ?? '-' }}
                                    </span>
                                </div>

                                <h5 class="fw-bold mb-1">{{ $campaign['campaign_name'] ?? 'Campaign' }}</h5>
                                <div class="small text-muted">Campaign ID: {{ $campaign['campaign_id'] ?? '-' }}</div>
                            </div>

                            <div class="text-lg-end">
                                <div class="small text-muted">Spend</div>
                                <div class="fs-5 fw-black text-dark">{{ $campaign['spend_label'] ?? 'Rp 0' }}</div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-xl-3 col-md-6">
                                <div class="meta-ads-kpi-card h-100">
                                    <div class="meta-ads-kpi-label">Reach</div>
                                    <div class="meta-ads-kpi-value">{{ number_format((int) ($campaign['reach'] ?? 0)) }}</div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="meta-ads-kpi-card h-100">
                                    <div class="meta-ads-kpi-label">Impressions</div>
                                    <div class="meta-ads-kpi-value">{{ number_format((int) ($campaign['impressions'] ?? 0)) }}</div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="meta-ads-kpi-card h-100">
                                    <div class="meta-ads-kpi-label">Frequency</div>
                                    <div class="meta-ads-kpi-value">{{ number_format((float) ($campaign['frequency'] ?? 0), 2) }}</div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="meta-ads-kpi-card h-100">
                                    <div class="meta-ads-kpi-label">CTR</div>
                                    <div class="meta-ads-kpi-value">{{ number_format((float) ($campaign['ctr'] ?? 0), 2) }}%</div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="meta-ads-kpi-card h-100">
                                    <div class="meta-ads-kpi-label">Engagement</div>
                                    <div class="meta-ads-kpi-value">{{ number_format((int) ($campaign['engagement'] ?? 0)) }}</div>
                                    <div class="meta-ads-kpi-help">Rate: {{ number_format((float) ($campaign['engagement_rate'] ?? 0), 1) }}%</div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="meta-ads-kpi-card h-100">
                                    <div class="meta-ads-kpi-label">Link Click</div>
                                    <div class="meta-ads-kpi-value">{{ number_format((int) ($campaign['link_click'] ?? 0)) }}</div>
                                    <div class="meta-ads-kpi-help">Rate: {{ number_format((float) ($campaign['link_click_rate'] ?? 0), 2) }}%</div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="meta-ads-kpi-card h-100">
                                    <div class="meta-ads-kpi-label">Lead Form</div>
                                    <div class="meta-ads-kpi-value">{{ number_format((int) ($campaign['lead_form_submission'] ?? 0)) }}</div>
                                    <div class="meta-ads-kpi-help">CPL: {{ $campaign['cost_per_lead_label'] ?? '-' }}</div>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6">
                                <div class="meta-ads-kpi-card h-100">
                                    <div class="meta-ads-kpi-label">WhatsApp Chat</div>
                                    <div class="meta-ads-kpi-value">{{ number_format((int) ($campaign['whatsapp_chat'] ?? 0)) }}</div>
                                    <div class="meta-ads-kpi-help">Cost/chat: {{ $campaign['cost_per_whatsapp_chat_label'] ?? '-' }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-lg-5">
                                <div class="meta-ads-detail-card h-100">
                                    <h6 class="fw-bold mb-3">Funnel Performance</h6>
                                    <div class="meta-ads-funnel-list">
                                        <div class="meta-ads-funnel-item">
                                            <div>
                                                <div class="fw-semibold text-dark">Reach</div>
                                                <div class="small text-muted">Orang unik yang melihat iklan.</div>
                                            </div>
                                            <strong>{{ number_format((int) ($campaign['reach'] ?? 0)) }}</strong>
                                        </div>
                                        <div class="meta-ads-funnel-item">
                                            <div>
                                                <div class="fw-semibold text-dark">Impressions</div>
                                                <div class="small text-muted">Total tayangan iklan.</div>
                                            </div>
                                            <strong>{{ number_format((int) ($campaign['impressions'] ?? 0)) }}</strong>
                                        </div>
                                        <div class="meta-ads-funnel-item">
                                            <div>
                                                <div class="fw-semibold text-dark">Link Click</div>
                                                <div class="small text-muted">User yang klik menuju tujuan iklan.</div>
                                            </div>
                                            <strong>{{ number_format((int) ($campaign['link_click'] ?? 0)) }}</strong>
                                        </div>
                                        <div class="meta-ads-funnel-item">
                                            <div>
                                                <div class="fw-semibold text-dark">Lead Form</div>
                                                <div class="small text-muted">Conversion: {{ number_format((float) ($campaign['lead_conversion_rate'] ?? 0), 1) }}%</div>
                                            </div>
                                            <strong>{{ number_format((int) ($campaign['lead_form_submission'] ?? 0)) }}</strong>
                                        </div>
                                        <div class="meta-ads-funnel-item">
                                            <div>
                                                <div class="fw-semibold text-dark">WhatsApp Chat</div>
                                                <div class="small text-muted">Conversion: {{ number_format((float) ($campaign['whatsapp_conversion_rate'] ?? 0), 1) }}%</div>
                                            </div>
                                            <strong>{{ number_format((int) ($campaign['whatsapp_chat'] ?? 0)) }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-7">
                                <div class="meta-ads-detail-card h-100">
                                    <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                        <div>
                                            <h6 class="fw-bold mb-1">AI Performance Summary</h6>
                                            <p class="small text-muted mb-0">Ringkasan bottleneck dan step solusi per campaign.</p>
                                        </div>
                                        <span class="badge rounded-pill bg-primary-subtle text-primary">AI Analyst</span>
                                    </div>

                                    <div class="meta-ads-ai-summary mb-3">
                                        <div class="fw-semibold text-dark mb-1">Ringkasan</div>
                                        <p class="text-muted mb-0">{{ $metaAdsAiSummary['summary'] ?? 'Belum ada summary untuk campaign ini.' }}</p>
                                    </div>

                                    <div class="mb-3">
                                        <div class="fw-semibold text-dark mb-2">Main Bottleneck</div>
                                        <div class="meta-ads-bottleneck-box">
                                            {{ $metaAdsAiSummary['main_bottleneck'] ?? 'Belum ada bottleneck utama.' }}
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <div class="fw-semibold text-dark mb-2">Faktor Penghambat</div>
                                        @if($metaAdsBlockingFactors->isEmpty())
                                            <p class="text-muted mb-0">Belum ada faktor penghambat.</p>
                                        @else
                                            <div class="d-grid gap-2">
                                                @foreach($metaAdsBlockingFactors as $factor)
                                                    <div class="meta-ads-factor-box">
                                                        <div class="d-flex align-items-start justify-content-between gap-3">
                                                            <div>
                                                                <div class="fw-semibold text-dark">{{ $factor['factor'] ?? '-' }}</div>
                                                                <div class="small text-muted">{{ $factor['evidence'] ?? '-' }}</div>
                                                            </div>
                                                            <span class="badge rounded-pill bg-light text-muted">{{ $factor['severity'] ?? 'low' }}</span>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>

                                    <div>
                                        <div class="fw-semibold text-dark mb-2">Step-by-step Solusi</div>
                                        @if($metaAdsRecommendedSteps->isEmpty())
                                            <p class="text-muted mb-0">Belum ada rekomendasi.</p>
                                        @else
                                            <ol class="mb-0 ps-3">
                                                @foreach($metaAdsRecommendedSteps as $step)
                                                    <li class="mb-2">{{ $step }}</li>
                                                @endforeach
                                            </ol>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-lg-6">
                                <div class="meta-ads-detail-card h-100">
                                    <h6 class="fw-bold mb-3">Cost & Efficiency</h6>
                                    <div class="table-responsive">
                                        <table class="table table-modern align-middle mb-0">
                                            <tbody>
                                                <tr>
                                                    <td class="text-muted">CPC</td>
                                                    <td class="text-end fw-semibold">Rp {{ number_format((float) ($campaign['cpc'] ?? 0), 0, ',', '.') }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted">CPM</td>
                                                    <td class="text-end fw-semibold">Rp {{ number_format((float) ($campaign['cpm'] ?? 0), 0, ',', '.') }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted">Cost per Lead</td>
                                                    <td class="text-end fw-semibold">{{ $campaign['cost_per_lead_label'] ?? '-' }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted">Cost per WhatsApp Chat</td>
                                                    <td class="text-end fw-semibold">{{ $campaign['cost_per_whatsapp_chat_label'] ?? '-' }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted">Lead Conversion Rate</td>
                                                    <td class="text-end fw-semibold">{{ number_format((float) ($campaign['lead_conversion_rate'] ?? 0), 1) }}%</td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted">WhatsApp Conversion Rate</td>
                                                    <td class="text-end fw-semibold">{{ number_format((float) ($campaign['whatsapp_conversion_rate'] ?? 0), 1) }}%</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="meta-ads-detail-card h-100">
                                    <h6 class="fw-bold mb-3">Meta Actions</h6>
                                    @if($metaAdsActions->isEmpty())
                                        <p class="text-muted mb-0">Tidak ada action detail.</p>
                                    @else
                                        <div class="table-responsive meta-ads-action-table">
                                            <table class="table table-modern align-middle mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Action Type</th>
                                                        <th class="text-end">Value</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($metaAdsActions as $action)
                                                        <tr>
                                                            <td class="small">{{ $action['action_type'] ?? '-' }}</td>
                                                            <td class="text-end fw-semibold">{{ number_format((int) ($action['value'] ?? 0)) }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
