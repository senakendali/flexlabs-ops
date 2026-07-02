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

    $metaAdsHasCampaignAi = $metaAdsCampaigns
        ->filter(fn ($campaign) => ! empty($campaign['ai_summary'] ?? []))
        ->isNotEmpty();

    $metaAdsHasAiSummary = $metaAdsSummaryText !== '' || $metaAdsHasCampaignAi;

    $metaAdsAttentionCampaigns = collect($metaAdsOverview['attention_campaigns'] ?? []);
    $metaAdsBestCampaign = $metaAdsOverview['best_campaign'] ?? null;
@endphp

<div class="meta-ads-dashboard-card" id="meta-ads-performance">
    <div class="content-card-header d-flex justify-content-between align-items-start gap-3 flex-wrap px-0 pt-0">
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

                @if($metaAdsHasAiSummary)
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

            <h5 class="content-card-title mb-1">Meta Ads Performance</h5>
            <p class="content-card-subtitle mb-0">
                Monitoring spend, reach, impressions, engagement, lead form, WhatsApp chat, dan campaign yang perlu dioptimasi.
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

    <div class="content-card-body px-0 pb-0">
        @if($metaAdsSummaryText !== '')
            <div class="meta-ads-ai-card mb-3">
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
                    <div class="d-flex align-items-start gap-3">
                        <div class="meta-ads-ai-icon">
                            <i class="bi bi-stars"></i>
                        </div>
                        <div>
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                <div class="meta-ads-ai-title">AI Meta Ads Insight</div>
                                <span class="badge rounded-pill bg-success-subtle text-success">
                                    AI Generated
                                </span>
                            </div>
                            <p class="text-muted mb-0 small fw-semibold">
                                Ringkasan performa campaign berdasarkan spend, reach, engagement, lead, WhatsApp chat, dan bottleneck campaign.
                            </p>
                        </div>
                    </div>

                    <div class="text-end small text-muted">
                        <div>Source: <strong class="text-dark">Gemini</strong></div>
                        <div>Level: <strong class="text-dark">Campaign Insight</strong></div>
                    </div>
                </div>

                <div class="meta-ads-ai-box">
                    <div class="fw-semibold text-dark mb-1">Executive Summary</div>
                    <p class="text-muted mb-0">{{ $metaAdsSummaryText }}</p>
                </div>
            </div>
        @endif

        @if(! $metaAdsIsAvailable || $metaAdsCampaigns->isEmpty())
            <div class="empty-state-box mb-3">
                <div class="empty-state-icon">
                    <i class="bi bi-megaphone"></i>
                </div>
                <h5 class="empty-state-title">Data Meta Ads belum tersedia</h5>
                <p class="empty-state-text mb-3">
                    Jalankan sync Meta Ads terlebih dulu supaya dashboard bisa menampilkan campaign, KPI, dan AI insight.
                </p>

                <code class="d-inline-block bg-white border rounded-3 px-3 py-2">
                    php artisan meta-ads:sync-campaign-insights --date-preset=last_7d --with-ai
                </code>

                @if($metaAdsErrorMessage)
                    <div class="alert alert-warning mt-3 mb-0 text-start">
                        {{ $metaAdsErrorMessage }}
                    </div>
                @endif
            </div>
        @else
            <ul
                class="nav nav-pills work-progress-tabs meta-ads-campaign-tabs dashboard-nested-tabs flex-nowrap overflow-auto"
                id="metaAdsDashboardTabs"
                role="tablist"
            >
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
                        @foreach([
                            [
                                'label' => 'Total Spend',
                                'value' => $metaAdsOverview['total_spend_label'] ?? 'Rp 0',
                                'help' => 'Total biaya campaign periode terbaru.',
                            ],
                            [
                                'label' => 'Reach',
                                'value' => number_format((int) ($metaAdsOverview['total_reach'] ?? 0)),
                                'help' => 'Orang unik yang melihat iklan.',
                            ],
                            [
                                'label' => 'Impressions',
                                'value' => number_format((int) ($metaAdsOverview['total_impressions'] ?? 0)),
                                'help' => 'Total tayangan iklan.',
                            ],
                            [
                                'label' => 'Campaign',
                                'value' => number_format((int) ($metaAdsOverview['campaign_count'] ?? 0)),
                                'help' => 'Campaign pada periode terbaru.',
                            ],
                            [
                                'label' => 'Engagement',
                                'value' => number_format((int) ($metaAdsOverview['total_engagement'] ?? 0)),
                                'help' => 'Post/page engagement.',
                            ],
                            [
                                'label' => 'Link Click',
                                'value' => number_format((int) ($metaAdsOverview['total_link_click'] ?? 0)),
                                'help' => 'Klik menuju tujuan iklan.',
                            ],
                            [
                                'label' => 'Lead Form',
                                'value' => number_format((int) ($metaAdsOverview['total_lead_form_submission'] ?? 0)),
                                'help' => 'CPL: ' . ($metaAdsOverview['cost_per_lead_label'] ?? '-'),
                            ],
                            [
                                'label' => 'WhatsApp Chat',
                                'value' => number_format((int) ($metaAdsOverview['total_whatsapp_chat'] ?? 0)),
                                'help' => 'Cost/chat: ' . ($metaAdsOverview['cost_per_whatsapp_chat_label'] ?? '-'),
                            ],
                        ] as $item)
                            <div class="col-xl-3 col-md-6">
                                <div class="meta-ads-kpi-card h-100">
                                    <div class="meta-ads-kpi-label">{{ $item['label'] }}</div>
                                    <div class="meta-ads-kpi-value">{{ $item['value'] }}</div>
                                    <div class="meta-ads-kpi-help">{{ $item['help'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="row g-3">
                        <div class="col-lg-5">
                            <div class="meta-ads-detail-card h-100">
                                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                    <div>
                                        <div class="fw-semibold text-dark">Campaign terbaik</div>
                                        <div class="small text-muted">Dipilih dari CPL / Cost per WhatsApp terbaik.</div>
                                    </div>
                                    <span class="badge rounded-pill bg-success-subtle text-success">Best</span>
                                </div>

                                @if($metaAdsBestCampaign)
                                    <div class="fw-bold text-dark mb-1">
                                        {{ $metaAdsBestCampaign['campaign_name'] ?? '-' }}
                                    </div>
                                    <div class="small text-muted mb-3">
                                        {{ $metaAdsBestCampaign['date_start'] ?? '-' }} — {{ $metaAdsBestCampaign['date_stop'] ?? '-' }}
                                    </div>

                                    <div class="row g-2">
                                        <div class="col-6">
                                            <div class="meta-ads-mini-metric h-100">
                                                <span>Spend</span>
                                                <strong>{{ $metaAdsBestCampaign['spend_label'] ?? 'Rp 0' }}</strong>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="meta-ads-mini-metric h-100">
                                                <span>Lead</span>
                                                <strong>{{ number_format((int) ($metaAdsBestCampaign['lead_form_submission'] ?? 0)) }}</strong>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="meta-ads-mini-metric h-100">
                                                <span>WA Chat</span>
                                                <strong>{{ number_format((int) ($metaAdsBestCampaign['whatsapp_chat'] ?? 0)) }}</strong>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="meta-ads-mini-metric h-100">
                                                <span>CPL</span>
                                                <strong>{{ $metaAdsBestCampaign['cost_per_lead_label'] ?? '-' }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="empty-state-box my-0 py-4">
                                        <div class="empty-state-icon">
                                            <i class="bi bi-trophy"></i>
                                        </div>
                                        <h5 class="empty-state-title">Belum ada best campaign</h5>
                                        <p class="empty-state-text mb-0">
                                            Belum ada campaign yang menghasilkan lead atau WhatsApp chat.
                                        </p>
                                    </div>
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

                                @if($metaAdsAttentionCampaigns->isEmpty())
                                    <div class="empty-state-box my-0 py-4">
                                        <div class="empty-state-icon">
                                            <i class="bi bi-check2-circle"></i>
                                        </div>
                                        <h5 class="empty-state-title">Tidak ada campaign kritis</h5>
                                        <p class="empty-state-text mb-0">
                                            Campaign belum masuk status critical/warning berdasarkan data terbaru.
                                        </p>
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
                                                            <div class="fw-semibold text-dark">
                                                                {{ \Illuminate\Support\Str::limit($campaign['campaign_name'] ?? '-', 42) }}
                                                            </div>
                                                            <div class="small text-muted">
                                                                {{ $campaign['date_start'] ?? '-' }} — {{ $campaign['date_stop'] ?? '-' }}
                                                            </div>
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
                        $metaAdsAiSummary = is_array($campaign['ai_summary'] ?? null) ? $campaign['ai_summary'] : [];
                        $metaAdsBlockingFactors = collect($metaAdsAiSummary['blocking_factors'] ?? []);
                        $metaAdsRecommendedSteps = collect($metaAdsAiSummary['recommended_steps'] ?? []);
                        $metaAdsActions = collect($campaign['actions'] ?? []);
                        $metaAdsCampaignSummary = trim((string) ($metaAdsAiSummary['summary'] ?? ''));
                    @endphp

                    <div
                        class="tab-pane fade"
                        id="{{ $metaAdsCampaignTabId }}-pane"
                        role="tabpanel"
                        aria-labelledby="{{ $metaAdsCampaignTabId }}-tab"
                        tabindex="0"
                    >
                        <div class="meta-ads-campaign-header mb-3">
                            <div>
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                    <span class="badge rounded-pill {{ $campaign['health_badge_class'] ?? 'bg-light text-muted' }}">
                                        {{ $campaign['health_label'] ?? 'Monitor' }}
                                    </span>

                                    <span class="badge rounded-pill bg-light text-muted">
                                        {{ $campaign['date_start'] ?? '-' }} — {{ $campaign['date_stop'] ?? '-' }}
                                    </span>

                                    @if($metaAdsCampaignSummary !== '')
                                        <span class="badge rounded-pill bg-success-subtle text-success">
                                            <i class="bi bi-stars me-1"></i>
                                            AI Insight
                                        </span>
                                    @endif
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
                            @foreach([
                                [
                                    'label' => 'Reach',
                                    'value' => number_format((int) ($campaign['reach'] ?? 0)),
                                    'help' => 'Orang unik yang melihat iklan.',
                                ],
                                [
                                    'label' => 'Impressions',
                                    'value' => number_format((int) ($campaign['impressions'] ?? 0)),
                                    'help' => 'Total tayangan iklan.',
                                ],
                                [
                                    'label' => 'Frequency',
                                    'value' => number_format((float) ($campaign['frequency'] ?? 0), 2),
                                    'help' => 'Rata-rata tayangan per orang.',
                                ],
                                [
                                    'label' => 'CTR',
                                    'value' => number_format((float) ($campaign['ctr'] ?? 0), 2) . '%',
                                    'help' => 'Click-through rate.',
                                ],
                                [
                                    'label' => 'Engagement',
                                    'value' => number_format((int) ($campaign['engagement'] ?? 0)),
                                    'help' => 'Rate: ' . number_format((float) ($campaign['engagement_rate'] ?? 0), 1) . '%',
                                ],
                                [
                                    'label' => 'Link Click',
                                    'value' => number_format((int) ($campaign['link_click'] ?? 0)),
                                    'help' => 'Rate: ' . number_format((float) ($campaign['link_click_rate'] ?? 0), 2) . '%',
                                ],
                                [
                                    'label' => 'Lead Form',
                                    'value' => number_format((int) ($campaign['lead_form_submission'] ?? 0)),
                                    'help' => 'CPL: ' . ($campaign['cost_per_lead_label'] ?? '-'),
                                ],
                                [
                                    'label' => 'WhatsApp Chat',
                                    'value' => number_format((int) ($campaign['whatsapp_chat'] ?? 0)),
                                    'help' => 'Cost/chat: ' . ($campaign['cost_per_whatsapp_chat_label'] ?? '-'),
                                ],
                            ] as $item)
                                <div class="col-xl-3 col-md-6">
                                    <div class="meta-ads-kpi-card h-100">
                                        <div class="meta-ads-kpi-label">{{ $item['label'] }}</div>
                                        <div class="meta-ads-kpi-value">{{ $item['value'] }}</div>
                                        <div class="meta-ads-kpi-help">{{ $item['help'] }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-lg-5">
                                <div class="meta-ads-detail-card h-100">
                                    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                        <div>
                                            <div class="fw-semibold text-dark">Funnel Performance</div>
                                            <div class="small text-muted">Alur dari reach sampai lead / WhatsApp chat.</div>
                                        </div>
                                        <span class="badge rounded-pill bg-primary-subtle text-primary">
                                            Funnel
                                        </span>
                                    </div>

                                    <div class="meta-ads-funnel-list">
                                        @foreach([
                                            [
                                                'label' => 'Reach',
                                                'help' => 'Orang unik yang melihat iklan.',
                                                'value' => number_format((int) ($campaign['reach'] ?? 0)),
                                            ],
                                            [
                                                'label' => 'Impressions',
                                                'help' => 'Total tayangan iklan.',
                                                'value' => number_format((int) ($campaign['impressions'] ?? 0)),
                                            ],
                                            [
                                                'label' => 'Link Click',
                                                'help' => 'User yang klik menuju tujuan iklan.',
                                                'value' => number_format((int) ($campaign['link_click'] ?? 0)),
                                            ],
                                            [
                                                'label' => 'Lead Form',
                                                'help' => 'Conversion: ' . number_format((float) ($campaign['lead_conversion_rate'] ?? 0), 1) . '%',
                                                'value' => number_format((int) ($campaign['lead_form_submission'] ?? 0)),
                                            ],
                                            [
                                                'label' => 'WhatsApp Chat',
                                                'help' => 'Conversion: ' . number_format((float) ($campaign['whatsapp_conversion_rate'] ?? 0), 1) . '%',
                                                'value' => number_format((int) ($campaign['whatsapp_chat'] ?? 0)),
                                            ],
                                        ] as $funnelItem)
                                            <div class="meta-ads-funnel-item">
                                                <div>
                                                    <div class="fw-semibold text-dark">{{ $funnelItem['label'] }}</div>
                                                    <div class="small text-muted">{{ $funnelItem['help'] }}</div>
                                                </div>
                                                <strong>{{ $funnelItem['value'] }}</strong>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-7">
                                <div class="meta-ads-ai-card h-100">
                                    <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                        <div class="d-flex align-items-start gap-3">
                                            <div class="meta-ads-ai-icon">
                                                <i class="bi bi-stars"></i>
                                            </div>
                                            <div>
                                                <div class="meta-ads-ai-title mb-1">AI Performance Summary</div>
                                                <p class="small text-muted mb-0">
                                                    Ringkasan bottleneck dan step solusi per campaign.
                                                </p>
                                            </div>
                                        </div>
                                        <span class="badge rounded-pill bg-primary-subtle text-primary">AI Analyst</span>
                                    </div>

                                    <div class="meta-ads-ai-box mb-3">
                                        <div class="fw-semibold text-dark mb-1">Ringkasan</div>
                                        <p class="text-muted mb-0">
                                            {{ $metaAdsCampaignSummary !== '' ? $metaAdsCampaignSummary : 'Belum ada summary untuk campaign ini.' }}
                                        </p>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-lg-5">
                                            <div class="meta-ads-ai-box h-100">
                                                <div class="meta-ads-ai-label">
                                                    <i class="bi bi-exclamation-diamond-fill me-1"></i>
                                                    Main Bottleneck
                                                </div>
                                                <div class="fw-bold text-dark">
                                                    {{ $metaAdsAiSummary['main_bottleneck'] ?? 'Belum ada bottleneck utama.' }}
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-lg-7">
                                            <div class="meta-ads-ai-box h-100">
                                                <div class="meta-ads-ai-label">
                                                    <i class="bi bi-bar-chart-steps me-1"></i>
                                                    Faktor Penghambat
                                                </div>

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
                                                                    <span class="badge rounded-pill bg-light text-muted">
                                                                        {{ $factor['severity'] ?? 'low' }}
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="meta-ads-ai-box">
                                                <div class="meta-ads-ai-label">
                                                    <i class="bi bi-list-check me-1"></i>
                                                    Step-by-step Solusi
                                                </div>

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
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-lg-6">
                                <div class="meta-ads-detail-card h-100">
                                    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                        <div>
                                            <div class="fw-semibold text-dark">Cost & Efficiency</div>
                                            <div class="small text-muted">Biaya per klik, per seribu tayangan, lead, dan chat.</div>
                                        </div>
                                        <span class="badge rounded-pill bg-success-subtle text-success">
                                            Efficiency
                                        </span>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-modern align-middle mb-0">
                                            <tbody>
                                                <tr>
                                                    <td class="text-muted">CPC</td>
                                                    <td class="text-end fw-semibold">
                                                        Rp {{ number_format((float) ($campaign['cpc'] ?? 0), 0, ',', '.') }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted">CPM</td>
                                                    <td class="text-end fw-semibold">
                                                        Rp {{ number_format((float) ($campaign['cpm'] ?? 0), 0, ',', '.') }}
                                                    </td>
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
                                                    <td class="text-end fw-semibold">
                                                        {{ number_format((float) ($campaign['lead_conversion_rate'] ?? 0), 1) }}%
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="text-muted">WhatsApp Conversion Rate</td>
                                                    <td class="text-end fw-semibold">
                                                        {{ number_format((float) ($campaign['whatsapp_conversion_rate'] ?? 0), 1) }}%
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="meta-ads-detail-card h-100">
                                    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                        <div>
                                            <div class="fw-semibold text-dark">Meta Actions</div>
                                            <div class="small text-muted">Detail action mentah dari Meta Ads.</div>
                                        </div>
                                        <span class="badge rounded-pill bg-info-subtle text-info">
                                            Actions
                                        </span>
                                    </div>

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
                                                            <td class="text-end fw-semibold">
                                                                {{ number_format((int) ($action['value'] ?? 0)) }}
                                                            </td>
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

@once
    @push('styles')
        <style>
            .meta-ads-dashboard-card {
                position: relative;
            }

            .meta-ads-ai-card {
                border: 1px solid rgba(24, 119, 242, 0.16);
                border-radius: 22px;
                background:
                    radial-gradient(circle at top left, rgba(24, 119, 242, 0.12), transparent 34%),
                    linear-gradient(135deg, rgba(24, 119, 242, 0.06), rgba(16, 185, 129, 0.08));
                padding: 1.15rem;
                box-shadow: 0 16px 36px rgba(15, 23, 42, 0.05);
            }

            .meta-ads-ai-icon {
                width: 46px;
                height: 46px;
                border-radius: 16px;
                background: rgba(24, 119, 242, 0.14);
                color: #1877f2;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex: 0 0 auto;
                font-size: 1.25rem;
                box-shadow: 0 10px 24px rgba(24, 119, 242, 0.10);
            }

            .meta-ads-ai-title {
                color: #111827;
                font-size: 1rem;
                font-weight: 900;
                line-height: 1.2;
            }

            .meta-ads-ai-box {
                background: rgba(255, 255, 255, 0.88);
                border: 1px solid rgba(15, 23, 42, 0.08);
                border-radius: 18px;
                padding: 1rem;
                box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
            }

            .meta-ads-ai-label {
                color: #1877f2;
                font-size: .76rem;
                font-weight: 900;
                letter-spacing: .04em;
                text-transform: uppercase;
                margin-bottom: .75rem;
            }

            .meta-ads-campaign-header {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 1rem;
                flex-wrap: wrap;
                background: rgba(248, 250, 252, 0.88);
                border: 1px solid rgba(15, 23, 42, 0.07);
                border-radius: 20px;
                padding: 1rem;
            }

            .meta-ads-factor-box {
                background: rgba(248, 250, 252, 0.88);
                border: 1px solid rgba(15, 23, 42, 0.07);
                border-radius: 14px;
                padding: .8rem .9rem;
            }

            .meta-ads-funnel-list {
                display: grid;
                gap: .75rem;
            }

            .meta-ads-funnel-item {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                background: rgba(248, 250, 252, 0.88);
                border: 1px solid rgba(15, 23, 42, 0.07);
                border-radius: 16px;
                padding: .85rem .95rem;
            }

            .meta-ads-funnel-item strong {
                color: #111827;
                font-size: .98rem;
                white-space: nowrap;
            }

            .meta-ads-campaign-tabs {
                padding-bottom: .15rem;
            }

            .meta-ads-action-table {
                max-height: 320px;
                overflow: auto;
            }

            @media (max-width: 767.98px) {
                .meta-ads-ai-card {
                    padding: 1rem;
                }

                .meta-ads-campaign-header {
                    padding: .9rem;
                }

                .meta-ads-funnel-item {
                    align-items: flex-start;
                    flex-direction: column;
                    gap: .35rem;
                }
            }
        </style>
    @endpush
@endonce