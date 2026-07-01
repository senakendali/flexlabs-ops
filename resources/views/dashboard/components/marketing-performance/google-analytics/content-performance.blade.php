@php
    $gaContentPages = collect($gaContentPages ?? []);
@endphp

<div class="meta-ads-detail-card mb-3">
    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
        <div>
            <div class="fw-semibold text-dark">Content Performance</div>
            <div class="small text-muted">
                Halaman/konten yang paling banyak dilihat dan paling menghasilkan engagement.
            </div>
        </div>
        <span class="badge rounded-pill bg-info-subtle text-info">
            Content
        </span>
    </div>

    @if($gaContentPages->isEmpty())
        <div class="empty-state-box my-0 py-4">
            <div class="empty-state-icon">
                <i class="bi bi-file-earmark-text"></i>
            </div>
            <h5 class="empty-state-title">Belum ada data content performance</h5>
            <p class="empty-state-text mb-0">
                Top pages dan engagement content akan muncul setelah sync Google Analytics berjalan.
            </p>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-modern align-middle mb-0">
                <thead>
                    <tr>
                        <th>Page</th>
                        <th class="text-end">Views</th>
                        <th class="text-end">Users</th>
                        <th class="text-end">Avg Engagement</th>
                        <th class="text-end">Events</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($gaContentPages as $page)
                        <tr>
                            <td>
                                <div class="fw-semibold text-dark">{{ \Illuminate\Support\Str::limit($page['page_title'] ?? '-', 52) }}</div>
                                <div class="small text-muted">{{ $page['page_path'] ?? '-' }}</div>
                            </td>
                            <td class="text-end">{{ number_format((int) ($page['views'] ?? 0)) }}</td>
                            <td class="text-end">{{ number_format((int) ($page['users'] ?? 0)) }}</td>
                            <td class="text-end">{{ $page['average_engagement_time_label'] ?? '0s' }}</td>
                            <td class="text-end">{{ number_format((int) ($page['event_count'] ?? 0)) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>