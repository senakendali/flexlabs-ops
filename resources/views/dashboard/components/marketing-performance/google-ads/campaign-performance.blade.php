@php
    $campaigns = collect($campaigns ?? []);
@endphp

<div class="meta-ads-detail-card mb-3">
    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
        <div>
            <div class="fw-semibold text-dark">Campaign Performance</div>
            <div class="small text-muted">
                Performa campaign Google Ads berdasarkan spend, klik, CTR, CPC, conversion, dan health status.
            </div>
        </div>
        <span class="badge rounded-pill bg-primary-subtle text-primary">
            Campaigns
        </span>
    </div>

    @if($campaigns->isEmpty())
        <div class="empty-state-box my-0 py-4">
            <div class="empty-state-icon">
                <i class="bi bi-megaphone"></i>
            </div>
            <h5 class="empty-state-title">Belum ada data campaign</h5>
            <p class="empty-state-text mb-0">
                Data campaign akan muncul setelah sync Google Ads berjalan.
            </p>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-modern align-middle mb-0">
                <thead>
                    <tr>
                        <th>Campaign</th>
                        <th>Status</th>
                        <th>Type</th>
                        <th class="text-end">Cost</th>
                        <th class="text-end">Clicks</th>
                        <th class="text-end">CTR</th>
                        <th class="text-end">Avg CPC</th>
                        <th class="text-end">Conv.</th>
                        <th class="text-end">Cost/Conv</th>
                        <th>Health</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($campaigns as $campaign)
                        <tr>
                            <td>
                                <div class="fw-semibold text-dark">
                                    {{ \Illuminate\Support\Str::limit($campaign['campaign_name'] ?? '-', 42) }}
                                </div>
                                <div class="small text-muted">
                                    ID: {{ $campaign['campaign_id'] ?? '-' }}
                                </div>
                            </td>
                            <td>
                                <span class="badge rounded-pill {{ $campaign['status_badge_class'] ?? 'bg-light text-muted' }}">
                                    {{ $campaign['status_label'] ?? '-' }}
                                </span>
                            </td>
                            <td>{{ $campaign['advertising_channel_label'] ?? '-' }}</td>
                            <td class="text-end">{{ $campaign['cost_label'] ?? 'Rp 0' }}</td>
                            <td class="text-end">{{ number_format((int) ($campaign['clicks'] ?? 0)) }}</td>
                            <td class="text-end">{{ number_format((float) ($campaign['ctr'] ?? 0), 2) }}%</td>
                            <td class="text-end">{{ $campaign['average_cpc_label'] ?? 'Rp 0' }}</td>
                            <td class="text-end">{{ number_format((float) ($campaign['conversions'] ?? 0), 2) }}</td>
                            <td class="text-end">{{ $campaign['cost_per_conversion_label'] ?? '-' }}</td>
                            <td>
                                <span class="badge rounded-pill {{ $campaign['health_badge_class'] ?? 'bg-light text-muted' }}">
                                    {{ $campaign['health_label'] ?? '-' }}
                                </span>
                            </td>
                        </tr>

                        @if(! empty($campaign['insight']))
                            <tr>
                                <td colspan="10" class="pt-0">
                                    <div class="small text-muted bg-light rounded-3 px-3 py-2">
                                        <i class="bi bi-lightbulb me-1"></i>
                                        {{ $campaign['insight'] }}
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>