@php
    $gaAcquisition = $gaAcquisition ?? [];
    $gaChannels = collect($gaAcquisition['channels'] ?? []);
    $gaSources = collect($gaAcquisition['sources'] ?? []);
    $gaCampaigns = collect($gaAcquisition['campaigns'] ?? []);
@endphp

<div class="row g-3 mb-3">
    <div class="col-lg-4">
        <div class="meta-ads-detail-card h-100">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <div class="fw-semibold text-dark">Top Channels</div>
                    <div class="small text-muted">Channel group yang membawa traffic.</div>
                </div>
                <span class="badge rounded-pill bg-primary-subtle text-primary">Channel</span>
            </div>

            @if($gaChannels->isEmpty())
                <p class="text-muted mb-0">Belum ada data channel.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-modern align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Channel</th>
                                <th class="text-end">Sessions</th>
                                <th class="text-end">Key Events</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($gaChannels as $channel)
                                <tr>
                                    <td class="fw-semibold text-dark">{{ $channel['channel'] ?? '-' }}</td>
                                    <td class="text-end">{{ number_format((int) ($channel['sessions'] ?? 0)) }}</td>
                                    <td class="text-end">{{ number_format((int) ($channel['key_events'] ?? 0)) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="col-lg-4">
        <div class="meta-ads-detail-card h-100">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <div class="fw-semibold text-dark">Top Source / Medium</div>
                    <div class="small text-muted">Sumber traffic paling berpengaruh.</div>
                </div>
                <span class="badge rounded-pill bg-success-subtle text-success">Source</span>
            </div>

            @if($gaSources->isEmpty())
                <p class="text-muted mb-0">Belum ada data source / medium.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-modern align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Source / Medium</th>
                                <th class="text-end">Sessions</th>
                                <th class="text-end">Eng. Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($gaSources as $source)
                                <tr>
                                    <td class="fw-semibold text-dark">{{ $source['source_medium'] ?? '-' }}</td>
                                    <td class="text-end">{{ number_format((int) ($source['sessions'] ?? 0)) }}</td>
                                    <td class="text-end">{{ number_format((float) ($source['engagement_rate'] ?? 0), 1) }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="col-lg-4">
        <div class="meta-ads-detail-card h-100">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <div class="fw-semibold text-dark">Top Campaigns</div>
                    <div class="small text-muted">UTM campaign yang membawa traffic/action.</div>
                </div>
                <span class="badge rounded-pill bg-warning-subtle text-warning">UTM</span>
            </div>

            @if($gaCampaigns->isEmpty())
                <p class="text-muted mb-0">Belum ada data campaign / UTM.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-modern align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Campaign</th>
                                <th class="text-end">Sessions</th>
                                <th class="text-end">Key Events</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($gaCampaigns as $campaign)
                                <tr>
                                    <td class="fw-semibold text-dark">{{ \Illuminate\Support\Str::limit($campaign['campaign'] ?? '-', 32) }}</td>
                                    <td class="text-end">{{ number_format((int) ($campaign['sessions'] ?? 0)) }}</td>
                                    <td class="text-end">{{ number_format((int) ($campaign['key_events'] ?? 0)) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>