@php
    $gaLandingPages = collect($gaLandingPages ?? []);
@endphp

<div class="meta-ads-detail-card mb-3">
    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
        <div>
            <div class="fw-semibold text-dark">Landing Page Performance</div>
            <div class="small text-muted">
                Halaman pertama yang dikunjungi user dan pengaruhnya ke conversion.
            </div>
        </div>
        <span class="badge rounded-pill bg-primary-subtle text-primary">
            Landing Pages
        </span>
    </div>

    @if($gaLandingPages->isEmpty())
        <div class="empty-state-box my-0 py-4">
            <div class="empty-state-icon">
                <i class="bi bi-window"></i>
            </div>
            <h5 class="empty-state-title">Belum ada data landing page</h5>
            <p class="empty-state-text mb-0">
                Data landing page akan muncul setelah sync Google Analytics berjalan.
            </p>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-modern align-middle mb-0">
                <thead>
                    <tr>
                        <th>Landing Page</th>
                        <th class="text-end">Sessions</th>
                        <th class="text-end">Eng. Rate</th>
                        <th class="text-end">Key Events</th>
                        <th class="text-end">Key Event Rate</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($gaLandingPages as $page)
                        <tr>
                            <td>
                                <div class="fw-semibold text-dark">{{ \Illuminate\Support\Str::limit($page['page_title'] ?? $page['landing_page'] ?? '-', 52) }}</div>
                                <div class="small text-muted">{{ $page['landing_page'] ?? '-' }}</div>
                            </td>
                            <td class="text-end">{{ number_format((int) ($page['sessions'] ?? 0)) }}</td>
                            <td class="text-end">{{ number_format((float) ($page['engagement_rate'] ?? 0), 1) }}%</td>
                            <td class="text-end">{{ number_format((int) ($page['key_events'] ?? 0)) }}</td>
                            <td class="text-end">{{ number_format((float) ($page['key_event_rate'] ?? 0), 1) }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>