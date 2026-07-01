@php
    $metaAdsDashboardInsight = $metaAdsDashboardInsight ?? [];
@endphp

<div class="dashboard-section-label mb-3 mt-1">
    <div class="dashboard-section-eyebrow">Marketing Data Overview</div>
    <h4 class="dashboard-section-title mb-1">Marketing Performance</h4>
    <p class="dashboard-section-subtitle mb-0">
        Monitoring data marketing dibuat per source supaya dashboard tetap ringkas: Meta Ads, Google Analytics, dan Google Ads.
    </p>
</div>

<div class="content-card mb-4 marketing-performance-card" id="marketing-performance">
    <div class="content-card-header d-flex justify-content-between align-items-start gap-3 flex-wrap">
        <div>
            <h5 class="content-card-title mb-1">Marketing Performance</h5>
            <p class="content-card-subtitle mb-0">
                Pilih source data untuk membaca performa campaign, traffic website, dan paid ads tanpa membuat halaman dashboard terlalu panjang.
            </p>
        </div>

        <ul class="nav nav-pills work-progress-tabs marketing-performance-tabs flex-nowrap overflow-auto" id="marketingPerformanceTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button
                    class="nav-link active text-nowrap"
                    id="marketing-meta-ads-tab"
                    data-bs-toggle="pill"
                    data-bs-target="#marketing-meta-ads-pane"
                    type="button"
                    role="tab"
                    aria-controls="marketing-meta-ads-pane"
                    aria-selected="true"
                >
                    <i class="bi bi-megaphone-fill me-1"></i>
                    Meta Ads
                </button>
            </li>

            <li class="nav-item" role="presentation">
                <button
                    class="nav-link text-nowrap"
                    id="marketing-google-analytics-tab"
                    data-bs-toggle="pill"
                    data-bs-target="#marketing-google-analytics-pane"
                    type="button"
                    role="tab"
                    aria-controls="marketing-google-analytics-pane"
                    aria-selected="false"
                >
                    <i class="bi bi-graph-up-arrow me-1"></i>
                    Google Analytics
                </button>
            </li>

            <li class="nav-item" role="presentation">
                <button
                    class="nav-link text-nowrap"
                    id="marketing-google-ads-tab"
                    data-bs-toggle="pill"
                    data-bs-target="#marketing-google-ads-pane"
                    type="button"
                    role="tab"
                    aria-controls="marketing-google-ads-pane"
                    aria-selected="false"
                >
                    <i class="bi bi-google me-1"></i>
                    Google Ads
                </button>
            </li>
        </ul>
    </div>

    <div class="content-card-body">
        <div class="tab-content" id="marketingPerformanceTabsContent">
            <div
                class="tab-pane fade show active"
                id="marketing-meta-ads-pane"
                role="tabpanel"
                aria-labelledby="marketing-meta-ads-tab"
                tabindex="0"
            >
                @include('dashboard.components.marketing-performance.meta-ads', [
                    'metaAdsDashboardInsight' => $metaAdsDashboardInsight,
                ])
            </div>

            <div
                class="tab-pane fade"
                id="marketing-google-analytics-pane"
                role="tabpanel"
                aria-labelledby="marketing-google-analytics-tab"
                tabindex="0"
            >
                @include('dashboard.components.marketing-performance.google-analytics')
            </div>

            <div
                class="tab-pane fade"
                id="marketing-google-ads-pane"
                role="tabpanel"
                aria-labelledby="marketing-google-ads-tab"
                tabindex="0"
            >
                @include('dashboard.components.marketing-performance.google-ads')
            </div>
        </div>
    </div>
</div>
