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

    <div
        id="toastContainer"
        class="toast-container position-fixed top-0 end-0 p-3"
        style="z-index: 1090;"
    ></div>

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
                        <input
                            type="text"
                            name="search"
                            class="form-control"
                            value="{{ request('search') }}"
                            placeholder="Search title, report no, or slug"
                        >
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
                        <label class="form-label d-block opacity-0">Action</label>
                        <div class="d-flex gap-2 justify-content-lg-end flex-wrap">
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
                            <th class="text-center pe-4" style="width: 170px;">Action</th>
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

                                <td style="min-width: 300px;">
                                    <div class="summary-card-grid">
                                        <div class="summary-card-mini">
                                            <div class="summary-card-head">
                                                <span class="summary-card-icon">
                                                    <i class="bi bi-people"></i>
                                                </span>
                                                <span class="summary-card-label">Leads</span>
                                            </div>
                                            <div class="summary-card-value">{{ number_format($item->total_leads ?? 0) }}</div>
                                        </div>

                                        <div class="summary-card-mini">
                                            <div class="summary-card-head">
                                                <span class="summary-card-icon">
                                                    <i class="bi bi-check2-circle"></i>
                                                </span>
                                                <span class="summary-card-label">Conversions</span>
                                            </div>
                                            <div class="summary-card-value">{{ number_format($item->total_conversions ?? 0) }}</div>
                                        </div>

                                        <div class="summary-card-mini summary-card-revenue">
                                            <div class="summary-card-head">
                                                <span class="summary-card-icon">
                                                    <i class="bi bi-cash-stack"></i>
                                                </span>
                                                <span class="summary-card-label">Revenue</span>
                                            </div>
                                            <div class="summary-card-value">
                                                Rp{{ number_format((float) $item->total_revenue, 0, ',', '.') }}
                                            </div>
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

                                <td class="text-center pe-4">
                                    <div class="d-inline-flex gap-2">
                                        <a
                                            href="{{ route('marketing.reports.show', $item) }}"
                                            class="btn btn-sm btn-outline-secondary"
                                            title="View"
                                        >
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        <a
                                            href="{{ route('marketing.reports.edit', $item) }}"
                                            class="btn btn-sm btn-outline-primary"
                                            title="Edit"
                                        >
                                            <i class="bi bi-pencil-square"></i>
                                        </a>

                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-danger delete-report-btn"
                                            data-url="{{ route('marketing.reports.destroy', $item) }}"
                                            data-title="{{ $item->title }}"
                                            title="Delete"
                                        >
                                            <i class="bi bi-trash"></i>
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

<div class="modal fade" id="deleteReportModal" tabindex="-1" aria-labelledby="deleteReportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold" id="deleteReportModalLabel">Delete Report</h5>
                    <p class="text-muted small mb-0 mt-1">Tindakan ini tidak bisa dibatalkan.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pt-3">
                <div class="delete-report-modal-box">
                    <div class="delete-report-modal-icon">
                        <i class="bi bi-trash3"></i>
                    </div>
                    <div>
                        <div class="fw-semibold text-dark mb-1">Yakin mau hapus report ini?</div>
                        <div class="text-muted small">
                            Report <span class="fw-semibold text-dark" id="deleteReportTitle">-</span> akan dihapus permanen.
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                    Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteReportBtn">
                    <i class="bi bi-trash me-1"></i> Delete Report
                </button>
            </div>
        </div>
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

    .marketing-report-index-page .summary-card-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }

    .marketing-report-index-page .summary-card-mini {
        position: relative;
        padding: 12px 12px 11px;
        border-radius: 16px;
        background: linear-gradient(180deg, #ffffff 0%, #f8f6fc 100%);
        border: 1px solid #ece7f6;
        box-shadow: 0 8px 20px rgba(91, 62, 142, 0.04);
        min-height: 84px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .marketing-report-index-page .summary-card-head {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
    }

    .marketing-report-index-page .summary-card-icon {
        width: 28px;
        height: 28px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #efe9fb;
        color: #5B3E8E;
        font-size: .9rem;
        flex-shrink: 0;
    }

    .marketing-report-index-page .summary-card-label {
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #7b8494;
        line-height: 1.2;
    }

    .marketing-report-index-page .summary-card-value {
        font-size: 1.08rem;
        font-weight: 800;
        line-height: 1.15;
        color: #1f2937;
        word-break: break-word;
    }

    .marketing-report-index-page .summary-card-revenue {
        grid-column: 1 / -1;
        background: linear-gradient(135deg, rgba(91, 62, 142, 0.10) 0%, rgba(142, 106, 201, 0.16) 100%);
        border-color: rgba(91, 62, 142, 0.18);
    }

    .marketing-report-index-page .summary-card-revenue .summary-card-icon {
        background: rgba(91, 62, 142, 0.14);
        color: #4b2f7c;
    }

    .marketing-report-index-page .summary-card-revenue .summary-card-label {
        color: #69588d;
    }

    .marketing-report-index-page .summary-card-revenue .summary-card-value {
        color: #5B3E8E;
        font-size: 1.15rem;
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

    .delete-report-modal-box {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        padding: 14px;
        border-radius: 16px;
        background: #fff5f5;
        border: 1px solid #ffd9d9;
    }

    .delete-report-modal-icon {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #fee2e2;
        color: #dc2626;
        font-size: 1.15rem;
        flex-shrink: 0;
    }

    .marketing-report-index-page .toast {
        min-width: 280px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    }

    @media (max-width: 991.98px) {
        .marketing-report-index-page .content-card-body .btn {
            white-space: nowrap;
        }
    }

    @media (max-width: 1399.98px) {
        .marketing-report-index-page .summary-card-grid {
            grid-template-columns: 1fr;
        }

        .marketing-report-index-page .summary-card-revenue {
            grid-column: auto;
        }
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalElement = document.getElementById('deleteReportModal');
    const deleteReportTitle = document.getElementById('deleteReportTitle');
    const confirmDeleteReportBtn = document.getElementById('confirmDeleteReportBtn');
    const toastContainer = document.getElementById('toastContainer');

    if (!modalElement || !confirmDeleteReportBtn || typeof bootstrap === 'undefined') {
        return;
    }

    const deleteModal = new bootstrap.Modal(modalElement);

    let selectedDeleteUrl = null;
    let selectedDeleteTrigger = null;
    let reloadTimeout = null;

    function showToast(message, type = 'success') {
        if (!toastContainer) return;

        const toastId = 'toast-' + Date.now();

        const bgClass = {
            success: 'bg-success',
            danger: 'bg-danger',
            warning: 'bg-warning text-dark',
            info: 'bg-info text-dark'
        }[type] || 'bg-success';

        const closeBtnClass = (type === 'warning' || type === 'info')
            ? 'btn-close me-2 m-auto'
            : 'btn-close btn-close-white me-2 m-auto';

        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="${closeBtnClass}" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

        toastContainer.insertAdjacentHTML('beforeend', toastHtml);

        const toastEl = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastEl, {
            delay: 1500
        });

        toast.show();

        toastEl.addEventListener('hidden.bs.toast', function () {
            toastEl.remove();
        });
    }

    function resetDeleteState() {
        selectedDeleteUrl = null;
        selectedDeleteTrigger = null;
        confirmDeleteReportBtn.disabled = false;
        confirmDeleteReportBtn.innerHTML = '<i class="bi bi-trash me-1"></i> Delete Report';
    }

    function scheduleReload() {
        if (reloadTimeout) {
            clearTimeout(reloadTimeout);
        }

        reloadTimeout = setTimeout(function () {
            window.location.reload();
        }, 1200);
    }

    async function parseResponse(response) {
        const contentType = response.headers.get('content-type') || '';

        if (contentType.includes('application/json')) {
            return await response.json();
        }

        const text = await response.text();

        return {
            success: false,
            message: text || 'Unexpected server response.',
        };
    }

    document.querySelectorAll('.delete-report-btn').forEach((button) => {
        button.addEventListener('click', function () {
            selectedDeleteUrl = this.dataset.url;
            selectedDeleteTrigger = this;

            deleteReportTitle.textContent = this.dataset.title || 'this report';
            deleteModal.show();
        });
    });

    modalElement.addEventListener('hidden.bs.modal', function () {
        resetDeleteState();
    });

    confirmDeleteReportBtn.addEventListener('click', async function () {
        if (!selectedDeleteUrl) {
            return;
        }

        confirmDeleteReportBtn.disabled = true;
        confirmDeleteReportBtn.innerHTML =
            '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Deleting...';

        if (selectedDeleteTrigger) {
            selectedDeleteTrigger.disabled = true;
        }

        try {
            const response = await fetch(selectedDeleteUrl, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            const result = await parseResponse(response);

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to delete report.');
            }

            deleteModal.hide();
            showToast(result.message || 'Report deleted successfully.', 'success');
            scheduleReload();
        } catch (error) {
            showToast(error.message || 'Failed to delete report.', 'danger');

            confirmDeleteReportBtn.disabled = false;
            confirmDeleteReportBtn.innerHTML = '<i class="bi bi-trash me-1"></i> Delete Report';

            if (selectedDeleteTrigger) {
                selectedDeleteTrigger.disabled = false;
            }
        }
    });
});
</script>
@endpush