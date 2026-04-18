@extends('layouts.app-dashboard')

@section('title', 'Marketing Reports')

@section('content')
<div class="container-fluid px-4 py-4 marketing-report-index-page">

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Marketing Reports</div>
                <h1 class="page-title mb-2">Marketing Report List</h1>
                <p class="page-subtitle mb-0">
                    Daftar report marketing berdasarkan periode pelaporan, status, progres pengisian, dan ringkasan hasil utama.
                </p>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('marketing.dashboard') }}" class="btn btn-light border">
                    <i class="bi bi-bar-chart-line me-1"></i> Dashboard
                </a>
                <a href="{{ route('marketing.reports.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i> Add Report
                </a>
            </div>
        </div>
    </div>

    @php
        $summaryDraft = $reports->where('status', 'draft')->count();
        $summaryPublished = $reports->where('status', 'published')->count();
        $summaryArchived = $reports->where('status', 'archived')->count();
        $summaryActive = $reports->where('is_active', true)->count();
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-folder2-open"></i>
                    </div>
                    <div>
                        <div class="stat-title">Total Reports</div>
                        <div class="stat-value">{{ $reports->total() }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Jumlah seluruh report marketing yang tersedia dalam daftar.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-pencil-square"></i>
                    </div>
                    <div>
                        <div class="stat-title">Draft Reports</div>
                        <div class="stat-value">{{ $summaryDraft }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Report yang masih dalam proses pengisian atau penyempurnaan.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div>
                        <div class="stat-title">Published Reports</div>
                        <div class="stat-value">{{ $summaryPublished }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Report yang telah dipublikasikan sebagai ringkasan periode berjalan.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-toggle-on"></i>
                    </div>
                    <div>
                        <div class="stat-title">Active Reports</div>
                        <div class="stat-value">{{ $summaryActive }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Report aktif yang masih digunakan dalam monitoring dan evaluasi.
                </div>
            </div>
        </div>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Filter Reports</h5>
                <p class="content-card-subtitle mb-0">
                    Gunakan pencarian dan filter berikut untuk menelusuri report berdasarkan judul, status, dan periode pelaporan.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            <form method="GET" action="{{ route('marketing.reports.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-4">
                        <label class="form-label">Search</label>
                        <input type="text"
                               name="search"
                               class="form-control"
                               value="{{ request('search') }}"
                               placeholder="Search title, report no, or slug">
                    </div>

                    <div class="col-lg-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                            <option value="published" @selected(request('status') === 'published')>Published</option>
                            <option value="archived" @selected(request('status') === 'archived')>Archived</option>
                        </select>
                    </div>

                    <div class="col-lg-3">
                        <label class="form-label">Period Type</label>
                        <select name="period_type" class="form-select">
                            <option value="">All Periods</option>
                            <option value="weekly" @selected(request('period_type') === 'weekly')>Weekly</option>
                            <option value="monthly" @selected(request('period_type') === 'monthly')>Monthly</option>
                        </select>
                    </div>

                    <div class="col-lg-2">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-funnel me-1"></i> Apply
                            </button>
                            <a href="{{ route('marketing.reports.index') }}" class="btn btn-light border">
                                <i class="bi bi-arrow-clockwise me-1"></i> Reset
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="content-card">
        <div class="content-card-header d-flex justify-content-between align-items-center gap-3 flex-wrap">
            <div>
                <h5 class="content-card-title mb-1">Marketing Reports</h5>
                <p class="content-card-subtitle mb-0">
                    Setiap report menampilkan periode, status, progres section, serta ringkasan singkat hasil utama.
                </p>
            </div>

            <div class="table-meta-info">
                Total: <strong>{{ $reports->total() }}</strong> report
            </div>
        </div>

        <div class="content-card-body p-0">
            <div class="table-responsive">
                <table class="table table-modern align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Report</th>
                            <th>Period</th>
                            <th>Status</th>
                            <th>Sections</th>
                            <th>Summary</th>
                            <th>Updated By</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reports as $item)
                            @php
                                $completedSections = collect([
                                    $item->is_overview_completed,
                                    $item->is_campaign_completed,
                                    $item->is_ads_completed,
                                    $item->is_events_completed,
                                    $item->is_snapshot_completed,
                                    $item->is_insight_completed,
                                ])->filter()->count();

                                $totalSections = 6;
                                $completionPercent = $totalSections > 0 ? round(($completedSections / $totalSections) * 100) : 0;

                                $statusClass = match ($item->status) {
                                    'draft' => 'bg-warning-subtle text-warning-emphasis',
                                    'published' => 'bg-success-subtle text-success-emphasis',
                                    'archived' => 'bg-secondary-subtle text-secondary-emphasis',
                                    default => 'bg-light text-dark',
                                };

                                $periodLabel = match ($item->period_type) {
                                    'weekly' => 'Weekly',
                                    'monthly' => 'Monthly',
                                    default => ucfirst($item->period_type),
                                };
                            @endphp

                            <tr>
                                <td class="ps-4">
                                    <div class="fw-semibold text-dark">{{ $item->title }}</div>

                                    <div class="text-muted small mt-1">
                                        {{ $item->report_no ?: 'No report number' }}
                                        @if($item->slug)
                                            · {{ $item->slug }}
                                        @endif
                                    </div>

                                    <div class="text-muted small mt-1">
                                        {{ \Illuminate\Support\Carbon::parse($item->start_date)->format('d M Y') }}
                                        -
                                        {{ \Illuminate\Support\Carbon::parse($item->end_date)->format('d M Y') }}
                                    </div>

                                    <div class="mt-2 d-flex flex-wrap gap-2">
                                        <span class="badge rounded-pill bg-light text-dark border">
                                            {{ $item->campaigns_count ?? 0 }} Campaigns
                                        </span>
                                        <span class="badge rounded-pill bg-light text-dark border">
                                            {{ $item->ads_count ?? 0 }} Ads
                                        </span>
                                        <span class="badge rounded-pill bg-light text-dark border">
                                            {{ $item->events_count ?? 0 }} Events
                                        </span>
                                        @if($item->is_active)
                                            <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis">
                                                Active
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <td>
                                    <div class="fw-semibold">{{ $periodLabel }}</div>
                                    <div class="text-muted small">
                                        {{ \Illuminate\Support\Carbon::parse($item->start_date)->format('M Y') }}
                                    </div>
                                </td>

                                <td>
                                    <span class="badge rounded-pill {{ $statusClass }}">
                                        {{ ucfirst($item->status) }}
                                    </span>
                                </td>

                                <td style="min-width: 220px;">
                                    <div class="progress-summary-mini mb-2">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="small text-muted">Completion</span>
                                            <span class="small fw-semibold">{{ $completedSections }}/{{ $totalSections }}</span>
                                        </div>

                                        <div class="progress progress-modern-sm">
                                            <div class="progress-bar" style="width: {{ $completionPercent }}%"></div>
                                        </div>
                                    </div>

                                    <div class="section-check-inline">
                                        <span class="check-pill {{ $item->is_overview_completed ? 'completed' : '' }}">O</span>
                                        <span class="check-pill {{ $item->is_campaign_completed ? 'completed' : '' }}">C</span>
                                        <span class="check-pill {{ $item->is_ads_completed ? 'completed' : '' }}">A</span>
                                        <span class="check-pill {{ $item->is_events_completed ? 'completed' : '' }}">E</span>
                                        <span class="check-pill {{ $item->is_snapshot_completed ? 'completed' : '' }}">S</span>
                                        <span class="check-pill {{ $item->is_insight_completed ? 'completed' : '' }}">I</span>
                                    </div>
                                </td>

                                <td style="min-width: 250px;">
                                    <div class="summary-metric-list">
                                        <div class="summary-metric-item">
                                            <span class="label">Leads</span>
                                            <span class="value">{{ number_format($item->total_leads ?? 0) }}</span>
                                        </div>
                                        <div class="summary-metric-item">
                                            <span class="label">Registrants</span>
                                            <span class="value">{{ number_format($item->total_registrants ?? 0) }}</span>
                                        </div>
                                        <div class="summary-metric-item">
                                            <span class="label">Attendees</span>
                                            <span class="value">{{ number_format($item->total_attendees ?? 0) }}</span>
                                        </div>
                                        <div class="summary-metric-item">
                                            <span class="label">Conversions</span>
                                            <span class="value">{{ number_format($item->total_conversions ?? 0) }}</span>
                                        </div>
                                        <div class="summary-metric-item">
                                            <span class="label">Revenue</span>
                                            <span class="value">Rp{{ number_format((float) $item->total_revenue, 0, ',', '.') }}</span>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <div class="fw-semibold text-dark">
                                        {{ $item->updater?->name ?? '-' }}
                                    </div>
                                    <div class="text-muted small">
                                        {{ optional($item->updated_at)->format('d M Y H:i') }}
                                    </div>
                                </td>

                                <td class="text-end pe-4">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('marketing.reports.show', $item) }}" class="btn btn-light border">
                                            View
                                        </a>
                                        <a href="{{ route('marketing.reports.edit', $item) }}" class="btn btn-light border">
                                            Edit
                                        </a>
                                        <button type="button"
                                                class="btn btn-light border text-danger delete-report-btn"
                                                data-url="{{ route('marketing.reports.destroy', $item) }}"
                                                data-title="{{ $item->title }}">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="empty-state-box mx-4 my-3">
                                        <div class="empty-state-icon">
                                            <i class="bi bi-folder-x"></i>
                                        </div>
                                        <div class="empty-state-title">No marketing reports found</div>
                                        <div class="empty-state-subtitle">
                                            Belum ada report yang sesuai dengan filter saat ini.
                                        </div>
                                        <div class="mt-3">
                                            <a href="{{ route('marketing.reports.create') }}" class="btn btn-primary">
                                                <i class="bi bi-plus-circle me-1"></i> Create Report
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($reports->hasPages())
            <div class="content-card-footer">
                {{ $reports->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@push('styles')
<style>
    .marketing-report-index-page .table-meta-info {
        font-size: .88rem;
        color: #6b7280;
    }

    .marketing-report-index-page .progress-modern-sm {
        height: 8px;
        background: #ece7f8;
        border-radius: 999px;
        overflow: hidden;
    }

    .marketing-report-index-page .progress-modern-sm .progress-bar {
        background: linear-gradient(90deg, #5B3E8E 0%, #8e6ac9 100%);
        border-radius: 999px;
    }

    .marketing-report-index-page .section-check-inline {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .marketing-report-index-page .check-pill {
        width: 26px;
        height: 26px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #f3f4f6;
        color: #9ca3af;
        font-size: .75rem;
        font-weight: 700;
        border: 1px solid #e5e7eb;
    }

    .marketing-report-index-page .check-pill.completed {
        background: #e8f8ee;
        color: #15803d;
        border-color: #c9ebd4;
    }

    .marketing-report-index-page .summary-metric-list {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .marketing-report-index-page .summary-metric-item {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        font-size: .84rem;
    }

    .marketing-report-index-page .summary-metric-item .label {
        color: #6b7280;
    }

    .marketing-report-index-page .summary-metric-item .value {
        color: #1f2937;
        font-weight: 600;
        text-align: right;
    }

    .marketing-report-index-page .empty-state-box {
        padding: 28px 20px;
        border-radius: 18px;
        border: 1px dashed #d7dce3;
        text-align: center;
        background: #fcfcfd;
    }

    .marketing-report-index-page .empty-state-icon {
        width: 58px;
        height: 58px;
        margin: 0 auto 12px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #eee7fb;
        color: #5B3E8E;
        font-size: 1.4rem;
    }

    .marketing-report-index-page .empty-state-title {
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 4px;
    }

    .marketing-report-index-page .empty-state-subtitle {
        font-size: .85rem;
        color: #6b7280;
    }

    .marketing-report-index-page .content-card-footer {
        padding: 16px 20px;
        border-top: 1px solid #eef2f7;
        background: #fff;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.delete-report-btn').forEach((button) => {
        button.addEventListener('click', async function () {
            const url = this.dataset.url;
            const title = this.dataset.title || 'this report';

            const confirmed = confirm(`Delete "${title}"?`);
            if (!confirmed) return;

            this.disabled = true;

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: new URLSearchParams({
                        _method: 'DELETE',
                    }),
                });

                const data = await response.json();

                if (!response.ok) {
                    if (window.showToast) {
                        window.showToast(data.message || 'Failed to delete report.', 'danger');
                    } else {
                        alert(data.message || 'Failed to delete report.');
                    }
                    this.disabled = false;
                    return;
                }

                if (window.showToast) {
                    window.showToast(data.message || 'Report deleted successfully.', 'success');
                }

                window.location.reload();
            } catch (error) {
                if (window.showToast) {
                    window.showToast('An error occurred while deleting the report.', 'danger');
                } else {
                    alert('An error occurred while deleting the report.');
                }
                this.disabled = false;
            }
        });
    });
});
</script>
@endpush