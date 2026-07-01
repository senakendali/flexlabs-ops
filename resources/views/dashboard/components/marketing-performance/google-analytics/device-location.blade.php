@php
    $gaDevices = collect($gaDevices ?? []);
    $gaLocations = collect($gaLocations ?? []);
@endphp

<div class="row g-3">
    <div class="col-lg-6">
        <div class="meta-ads-detail-card h-100">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <div class="fw-semibold text-dark">Device Breakdown</div>
                    <div class="small text-muted">Device yang dipakai user saat mengakses website.</div>
                </div>
                <span class="badge rounded-pill bg-primary-subtle text-primary">Device</span>
            </div>

            @if($gaDevices->isEmpty())
                <p class="text-muted mb-0">Belum ada data device.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-modern align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Device</th>
                                <th class="text-end">Users</th>
                                <th class="text-end">Sessions</th>
                                <th class="text-end">Key Events</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($gaDevices as $device)
                                <tr>
                                    <td class="fw-semibold text-dark">{{ $device['device_category'] ?? '-' }}</td>
                                    <td class="text-end">{{ number_format((int) ($device['users'] ?? 0)) }}</td>
                                    <td class="text-end">{{ number_format((int) ($device['sessions'] ?? 0)) }}</td>
                                    <td class="text-end">{{ number_format((int) ($device['key_events'] ?? 0)) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="col-lg-6">
        <div class="meta-ads-detail-card h-100">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <div class="fw-semibold text-dark">Top Locations</div>
                    <div class="small text-muted">Kota/region yang membawa traffic dan conversion.</div>
                </div>
                <span class="badge rounded-pill bg-success-subtle text-success">Location</span>
            </div>

            @if($gaLocations->isEmpty())
                <p class="text-muted mb-0">Belum ada data location.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-modern align-middle mb-0">
                        <thead>
                            <tr>
                                <th>City / Region</th>
                                <th class="text-end">Users</th>
                                <th class="text-end">Sessions</th>
                                <th class="text-end">Key Events</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($gaLocations as $location)
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $location['city'] ?? '-' }}</div>
                                        <div class="small text-muted">{{ $location['region'] ?? $location['country'] ?? '-' }}</div>
                                    </td>
                                    <td class="text-end">{{ number_format((int) ($location['users'] ?? 0)) }}</td>
                                    <td class="text-end">{{ number_format((int) ($location['sessions'] ?? 0)) }}</td>
                                    <td class="text-end">{{ number_format((int) ($location['key_events'] ?? 0)) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>