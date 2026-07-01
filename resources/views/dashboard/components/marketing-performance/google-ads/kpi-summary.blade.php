@php
    $overview = $overview ?? [];
@endphp

<div class="row g-3 mb-3">
    @foreach([
        ['label' => 'Total Cost', 'value' => $overview['total_cost_label'] ?? 'Rp 0', 'help' => 'Total spend Google Ads.'],
        ['label' => 'Impressions', 'value' => number_format((int) ($overview['total_impressions'] ?? 0)), 'help' => 'Total iklan tampil.'],
        ['label' => 'Clicks', 'value' => number_format((int) ($overview['total_clicks'] ?? 0)), 'help' => 'Total klik dari iklan.'],
        ['label' => 'CTR', 'value' => number_format((float) ($overview['ctr'] ?? 0), 2) . '%', 'help' => 'Click-through rate iklan.'],
        ['label' => 'Avg CPC', 'value' => $overview['average_cpc_label'] ?? 'Rp 0', 'help' => 'Rata-rata biaya per klik.'],
        ['label' => 'Conversions', 'value' => number_format((float) ($overview['total_conversions'] ?? 0), 2), 'help' => 'Conversion yang tercatat.'],
        ['label' => 'Cost / Conv', 'value' => $overview['cost_per_conversion_label'] ?? '-', 'help' => 'Biaya rata-rata per conversion.'],
        ['label' => 'Conv. Rate', 'value' => number_format((float) ($overview['conversion_rate'] ?? 0), 2) . '%', 'help' => 'Persentase klik menjadi conversion.'],
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