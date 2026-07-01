@php
    $gaKpis = $gaKpis ?? [];
@endphp

<div class="row g-3 mb-3">
    <div class="col-xl-3 col-md-6">
        <div class="meta-ads-kpi-card h-100">
            <div class="meta-ads-kpi-label">Total Users</div>
            <div class="meta-ads-kpi-value">{{ number_format((int) ($gaKpis['total_users'] ?? 0)) }}</div>
            <div class="meta-ads-kpi-help">Total user yang mengunjungi website pada periode ini.</div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="meta-ads-kpi-card h-100">
            <div class="meta-ads-kpi-label">New Users</div>
            <div class="meta-ads-kpi-value">{{ number_format((int) ($gaKpis['new_users'] ?? 0)) }}</div>
            <div class="meta-ads-kpi-help">User baru yang datang dari channel marketing.</div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="meta-ads-kpi-card h-100">
            <div class="meta-ads-kpi-label">Sessions</div>
            <div class="meta-ads-kpi-value">{{ number_format((int) ($gaKpis['sessions'] ?? 0)) }}</div>
            <div class="meta-ads-kpi-help">Total kunjungan website.</div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="meta-ads-kpi-card h-100">
            <div class="meta-ads-kpi-label">Engaged Sessions</div>
            <div class="meta-ads-kpi-value">{{ number_format((int) ($gaKpis['engaged_sessions'] ?? 0)) }}</div>
            <div class="meta-ads-kpi-help">Session yang benar-benar engage.</div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="meta-ads-kpi-card h-100">
            <div class="meta-ads-kpi-label">Engagement Rate</div>
            <div class="meta-ads-kpi-value">{{ number_format((float) ($gaKpis['engagement_rate'] ?? 0), 1) }}%</div>
            <div class="meta-ads-kpi-help">Kualitas traffic yang masuk ke website.</div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="meta-ads-kpi-card h-100">
            <div class="meta-ads-kpi-label">Bounce Rate</div>
            <div class="meta-ads-kpi-value">{{ number_format((float) ($gaKpis['bounce_rate'] ?? 0), 1) }}%</div>
            <div class="meta-ads-kpi-help">Traffic yang tidak cukup engage.</div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="meta-ads-kpi-card h-100">
            <div class="meta-ads-kpi-label">Key Events</div>
            <div class="meta-ads-kpi-value">{{ number_format((int) ($gaKpis['key_events'] ?? 0)) }}</div>
            <div class="meta-ads-kpi-help">Action penting seperti submit form / klik WhatsApp.</div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="meta-ads-kpi-card h-100">
            <div class="meta-ads-kpi-label">Avg Engagement</div>
            <div class="meta-ads-kpi-value">{{ $gaKpis['average_engagement_time_label'] ?? '0s' }}</div>
            <div class="meta-ads-kpi-help">Rata-rata waktu user engage dengan website.</div>
        </div>
    </div>
</div>