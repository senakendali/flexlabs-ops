@extends('layouts.app-dashboard')

@section('title', 'Marketing Report Detail')

@section('content')
@php
    $overviewFilled = collect([
        $marketingReport->title ?? null,
        $marketingReport->period_type ?? null,
        $marketingReport->status ?? null,
        optional($marketingReport->start_date)->format('Y-m-d') ?? $marketingReport->start_date ?? null,
        optional($marketingReport->end_date)->format('Y-m-d') ?? $marketingReport->end_date ?? null,
    ])->filter(fn ($value) => filled($value))->count() === 5;

    $snapshotFilled = collect([
        $marketingReport->total_leads ?? null,
        $marketingReport->total_registrants ?? null,
        $marketingReport->total_attendees ?? null,
        $marketingReport->total_conversions ?? null,
        $marketingReport->total_revenue ?? null,
    ])->filter(fn ($value) => $value !== null && $value !== '')->count() === 5;

    $insightFilled = collect([
        $marketingReport->summary ?? null,
        $marketingReport->key_insight ?? null,
        $marketingReport->next_action ?? null,
        $marketingReport->notes ?? null,
    ])->filter(fn ($value) => filled($value))->isNotEmpty();

    $sectionStates = [
        'overview' => $overviewFilled ? 'completed' : 'incomplete',
        'campaign' => isset($campaigns) && count($campaigns) ? 'completed' : 'optional',
        'ads' => isset($ads) && count($ads) ? 'completed' : 'optional',
        'events' => isset($events) && count($events) ? 'completed' : 'optional',
        'snapshot' => $snapshotFilled ? 'completed' : 'incomplete',
        'insight' => $insightFilled ? 'completed' : 'optional',
    ];

    $filledSectionsCount = collect($sectionStates)->filter(fn ($state) => $state === 'completed')->count();
    $totalSectionsCount = count($sectionStates);
    $sectionProgressPercent = $totalSectionsCount > 0
        ? round(($filledSectionsCount / $totalSectionsCount) * 100)
        : 0;

    $periodLabel = match ($marketingReport->period_type) {
        'weekly' => 'Weekly',
        'monthly' => 'Monthly',
        default => ucfirst($marketingReport->period_type ?? '-'),
    };

    $statusClass = match ($marketingReport->status) {
        'draft' => 'bg-warning-subtle text-warning-emphasis',
        'published' => 'bg-success-subtle text-success-emphasis',
        'archived' => 'bg-secondary-subtle text-secondary-emphasis',
        default => 'bg-light text-dark',
    };

    $totalBudget = (float) ($marketingReport->total_budget ?? 0);
    $totalActualSpend = (float) ($marketingReport->total_actual_spend ?? 0);
@endphp

<div class="container-fluid px-4 py-4 marketing-report-show-page">

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Marketing Reports</div>
                <h1 class="page-title mb-2">{{ $marketingReport->title }}</h1>
                <p class="page-subtitle mb-0">
                    Detail report marketing dalam format read-only agar lebih nyaman dibaca untuk monitoring dan review management.
                </p>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('marketing.reports.index') }}" class="btn btn-light border">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
                <a href="{{ route('marketing.reports.edit', $marketingReport) }}" class="btn btn-edit-accent">
                    <i class="bi bi-pencil-square me-1"></i> Edit
                </a>
                <button type="button" class="btn btn-danger" id="openDeleteModalBtn">
                    <i class="bi bi-trash me-1"></i> Delete
                </button>
            </div>
        </div>
    </div>

    <div
        id="toastContainer"
        class="toast-container position-fixed top-0 end-0 p-3"
        style="z-index: 1090;"
    ></div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Leads</div>
                        <div class="stat-value">{{ number_format((int) $marketingReport->total_leads) }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Jumlah leads yang dicatat pada periode report ini.
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
                        <div class="stat-title">Conversions</div>
                        <div class="stat-value">{{ number_format((int) $marketingReport->total_conversions) }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Jumlah konversi yang berhasil dicapai pada periode ini.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-cash-coin"></i>
                    </div>
                    <div>
                        <div class="stat-title">Revenue</div>
                        <div class="stat-value">Rp{{ number_format((float) $marketingReport->total_revenue, 0, ',', '.') }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Total revenue yang tercatat pada report ini.
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <div>
                        <div class="stat-title">Actual Spend</div>
                        <div class="stat-value">Rp{{ number_format($totalActualSpend, 0, ',', '.') }}</div>
                    </div>
                </div>
                <div class="stat-description">
                    Total budget Rp{{ number_format($totalBudget, 0, ',', '.') }}
                </div>
            </div>
        </div>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-body">
            <div class="row g-3 align-items-stretch report-summary-row">
                <div class="col-lg-8 d-flex">
                    <div class="progress-summary-wrap w-100">
                        <div class="progress-summary-top d-flex justify-content-between align-items-center gap-3 mb-2">
                            <div>
                                <div class="progress-summary-label">Section Progress</div>
                                <div class="progress-summary-subtitle">
                                    Progress menghitung komponen yang benar-benar sudah diisi. Komponen optional yang kosong tidak dihitung sebagai terisi.
                                </div>
                            </div>
                            <div class="progress-summary-count">
                                <span id="completedSectionCount">{{ $filledSectionsCount }}</span> dari
                                <span id="totalSectionCount">{{ $totalSectionsCount }}</span>
                                komponen report sudah diisi
                            </div>
                        </div>

                        <div class="progress progress-modern" role="progressbar" aria-label="Section completion progress">
                            <div id="sectionProgressBar" class="progress-bar" style="width: {{ $sectionProgressPercent }}%"></div>
                        </div>

                        <div class="section-pill-summary mt-3">
                            @foreach ($sectionStates as $label => $state)
                                <span class="section-pill {{ $state }}">
                                    {{ ucfirst($label) }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 d-flex">
                    <div class="status-pill-card w-100">
                        <div class="status-pill-label">Report Status</div>
                        <div class="status-pill-value">
                            {{ ucfirst($marketingReport->status ?? 'draft') }}
                        </div>
                        <div class="status-pill-help mb-2">
                            {{ $periodLabel }} ·
                            {{ optional($marketingReport->start_date)->format('d M Y') ?? '-' }}
                            -
                            {{ optional($marketingReport->end_date)->format('d M Y') ?? '-' }}
                        </div>

                        <div class="meta-chip-grid">
                            <div class="meta-chip">
                                <span class="meta-chip-label">Report No</span>
                                <span class="meta-chip-value">{{ $marketingReport->report_no ?: '-' }}</span>
                            </div>
                            <div class="meta-chip">
                                <span class="meta-chip-label">Status</span>
                                <span class="badge rounded-pill {{ $statusClass }}">
                                    {{ ucfirst($marketingReport->status ?? 'draft') }}
                                </span>
                            </div>
                            <div class="meta-chip">
                                <span class="meta-chip-label">Active</span>
                                <span class="meta-chip-value">{{ $marketingReport->is_active ? 'Yes' : 'No' }}</span>
                            </div>
                            <div class="meta-chip">
                                <span class="meta-chip-label">Slug</span>
                                <span class="meta-chip-value text-break">{{ $marketingReport->slug ?: '-' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-8">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Overview</h5>
                        <p class="content-card-subtitle mb-0">
                            Informasi utama report dan periode pelaporan.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Title</div>
                            <div class="info-value">{{ $marketingReport->title ?: '-' }}</div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Period Type</div>
                            <div class="info-value">{{ $periodLabel }}</div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Start Date</div>
                            <div class="info-value">{{ optional($marketingReport->start_date)->format('d M Y') ?? '-' }}</div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">End Date</div>
                            <div class="info-value">{{ optional($marketingReport->end_date)->format('d M Y') ?? '-' }}</div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Status</div>
                            <div class="info-value">
                                <span class="badge rounded-pill {{ $statusClass }}">
                                    {{ ucfirst($marketingReport->status ?? 'draft') }}
                                </span>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Report No</div>
                            <div class="info-value">{{ $marketingReport->report_no ?: '-' }}</div>
                        </div>

                        <div class="info-item info-item-full">
                            <div class="info-label">Slug</div>
                            <div class="info-value text-break">{{ $marketingReport->slug ?: '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Audit Info</h5>
                        <p class="content-card-subtitle mb-0">
                            Informasi pencatatan dan pembaruan report.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="audit-item">
                        <div class="audit-label">Created By</div>
                        <div class="audit-value">{{ optional($marketingReport->creator)->name ?? '-' }}</div>
                    </div>

                    <div class="audit-item">
                        <div class="audit-label">Updated By</div>
                        <div class="audit-value">{{ optional($marketingReport->updater)->name ?? '-' }}</div>
                    </div>

                    <div class="audit-item">
                        <div class="audit-label">Created At</div>
                        <div class="audit-value">{{ optional($marketingReport->created_at)->format('d M Y H:i') ?? '-' }}</div>
                    </div>

                    <div class="audit-item mb-0">
                        <div class="audit-label">Updated At</div>
                        <div class="audit-value">{{ optional($marketingReport->updated_at)->format('d M Y H:i') ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-6">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Result Snapshot</h5>
                        <p class="content-card-subtitle mb-0">
                            Ringkasan hasil utama untuk kebutuhan review management.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="snapshot-grid">
                        <div class="snapshot-item">
                            <span class="snapshot-label">Total Leads</span>
                            <span class="snapshot-value">{{ number_format((int) $marketingReport->total_leads) }}</span>
                        </div>

                        <div class="snapshot-item">
                            <span class="snapshot-label">Total Conversions</span>
                            <span class="snapshot-value">{{ number_format((int) $marketingReport->total_conversions) }}</span>
                        </div>

                        <div class="snapshot-item">
                            <span class="snapshot-label">Total Revenue</span>
                            <span class="snapshot-value">Rp{{ number_format((float) $marketingReport->total_revenue, 0, ',', '.') }}</span>
                        </div>

                        <div class="snapshot-item">
                            <span class="snapshot-label">Total Budget</span>
                            <span class="snapshot-value">Rp{{ number_format($totalBudget, 0, ',', '.') }}</span>
                        </div>

                        <div class="snapshot-item">
                            <span class="snapshot-label">Actual Spend</span>
                            <span class="snapshot-value">Rp{{ number_format($totalActualSpend, 0, ',', '.') }}</span>
                        </div>

                        <div class="snapshot-item">
                            <span class="snapshot-label">Status</span>
                            <span class="snapshot-value">{{ ucfirst($marketingReport->status ?? 'draft') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Insight & Notes</h5>
                        <p class="content-card-subtitle mb-0">
                            Narasi ringkasan, insight, dan tindak lanjut periode ini.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="text-block-group">
                        <div class="text-block-item">
                            <div class="text-block-label">Summary</div>
                            <div class="text-block-value">{{ $marketingReport->summary ?: '-' }}</div>
                        </div>

                        <div class="text-block-item">
                            <div class="text-block-label">Key Insight</div>
                            <div class="text-block-value">{{ $marketingReport->key_insight ?: '-' }}</div>
                        </div>

                        <div class="text-block-item">
                            <div class="text-block-label">Next Action</div>
                            <div class="text-block-value">{{ $marketingReport->next_action ?: '-' }}</div>
                        </div>

                        <div class="text-block-item mb-0">
                            <div class="text-block-label">Notes</div>
                            <div class="text-block-value">{{ $marketingReport->notes ?: '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-4">
            <div class="content-card h-100">
                <div class="content-card-header d-flex justify-content-between align-items-center gap-2">
                    <div>
                        <h5 class="content-card-title mb-1">Campaigns</h5>
                        <p class="content-card-subtitle mb-0">
                            Daftar campaign pada report ini.
                        </p>
                    </div>
                    <span class="badge rounded-pill bg-light text-dark border">
                        {{ isset($campaigns) ? count($campaigns) : 0 }}
                    </span>
                </div>

                <div class="content-card-body">
                    @if(isset($campaigns) && count($campaigns))
                        <div class="simple-list">
                            @foreach($campaigns as $campaign)
                                <div class="simple-list-item">
                                    <div class="simple-list-title">{{ $campaign->name ?: '-' }}</div>
                                    <div class="simple-list-meta">
                                        {{ ucfirst(str_replace('_', ' ', $campaign->status ?? '-')) }}
                                        @if(!empty($campaign->owner_name))
                                            · {{ $campaign->owner_name }}
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="empty-state-box small-empty-state">
                            <div class="empty-state-icon"><i class="bi bi-megaphone"></i></div>
                            <div class="empty-state-title">No campaign</div>
                            <div class="empty-state-subtitle">Tidak ada campaign pada report ini.</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="content-card h-100">
                <div class="content-card-header d-flex justify-content-between align-items-center gap-2">
                    <div>
                        <h5 class="content-card-title mb-1">Ads</h5>
                        <p class="content-card-subtitle mb-0">
                            Daftar ads yang tercatat pada report ini.
                        </p>
                    </div>
                    <span class="badge rounded-pill bg-light text-dark border">
                        {{ isset($ads) ? count($ads) : 0 }}
                    </span>
                </div>

                <div class="content-card-body">
                    @if(isset($ads) && count($ads))
                        <div class="simple-list">
                            @foreach($ads as $ad)
                                <div class="simple-list-item">
                                    <div class="simple-list-title">{{ $ad->ad_name ?: '-' }}</div>
                                    <div class="simple-list-meta">
                                        {{ $ad->platform ?: '-' }}
                                        @if(!empty($ad->status))
                                            · {{ ucfirst(str_replace('_', ' ', $ad->status)) }}
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="empty-state-box small-empty-state">
                            <div class="empty-state-icon"><i class="bi bi-badge-ad"></i></div>
                            <div class="empty-state-title">No ads</div>
                            <div class="empty-state-subtitle">Tidak ada ads pada report ini.</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="content-card h-100">
                <div class="content-card-header d-flex justify-content-between align-items-center gap-2">
                    <div>
                        <h5 class="content-card-title mb-1">Events</h5>
                        <p class="content-card-subtitle mb-0">
                            Daftar event pada report ini.
                        </p>
                    </div>
                    <span class="badge rounded-pill bg-light text-dark border">
                        {{ isset($events) ? count($events) : 0 }}
                    </span>
                </div>

                <div class="content-card-body">
                    @if(isset($events) && count($events))
                        <div class="simple-list">
                            @foreach($events as $event)
                                <div class="simple-list-item">
                                    <div class="simple-list-title">{{ $event->name ?: '-' }}</div>
                                    <div class="simple-list-meta">
                                        {{ ucfirst(str_replace('_', ' ', $event->event_type ?? '-')) }}
                                        @if(!empty($event->event_date))
                                            · {{ \Illuminate\Support\Carbon::parse($event->event_date)->format('d M Y') }}
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="empty-state-box small-empty-state">
                            <div class="empty-state-icon"><i class="bi bi-calendar-event"></i></div>
                            <div class="empty-state-title">No event</div>
                            <div class="empty-state-subtitle">Tidak ada event pada report ini.</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
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
                            Report <span class="fw-semibold text-dark">{{ $marketingReport->title }}</span> akan dihapus permanen.
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                    Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <span class="default-delete-text">
                        <i class="bi bi-trash me-1"></i> Delete Report
                    </span>
                    <span class="loading-delete-text d-none">
                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Deleting...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    
</style>
@endpush

@push('scripts')
<script>
    const reportDeleteUrl = @json(route('marketing.reports.destroy', $marketingReport));
    const reportIndexUrl = @json(route('marketing.reports.index'));
    const csrfToken = @json(csrf_token());

    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('openDeleteModalBtn')?.addEventListener('click', openDeleteModal);
        document.getElementById('confirmDeleteBtn')?.addEventListener('click', deleteCurrentReport);
    });

    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        if (!container || typeof bootstrap === 'undefined') return;

        const toastId = `toast-${Date.now()}`;
        const bgClass = type === 'success' ? 'text-bg-success' : 'text-bg-danger';
        const icon = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill';

        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center ${bgClass} border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi ${icon} me-2"></i>${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', toastHtml);

        const toastEl = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastEl, { delay: 1500 });

        toast.show();

        toastEl.addEventListener('hidden.bs.toast', function () {
            toastEl.remove();
        });
    }

    function scheduleRedirect(url) {
        setTimeout(() => {
            window.location.href = url;
        }, 900);
    }

    function openDeleteModal() {
        const modalEl = document.getElementById('deleteReportModal');
        if (!modalEl || typeof bootstrap === 'undefined') return;

        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        setDeleteLoading(false);
        modal.show();
    }

    function setDeleteLoading(isLoading) {
        const button = document.getElementById('confirmDeleteBtn');
        if (!button) return;

        const defaultText = button.querySelector('.default-delete-text');
        const loadingText = button.querySelector('.loading-delete-text');

        button.disabled = isLoading;

        if (defaultText) {
            defaultText.classList.toggle('d-none', isLoading);
        }

        if (loadingText) {
            loadingText.classList.toggle('d-none', !isLoading);
        }
    }

    async function parseJsonSafe(response) {
        const text = await response.text();

        try {
            return text ? JSON.parse(text) : {};
        } catch (e) {
            return {
                success: false,
                message: text || 'Invalid server response.',
            };
        }
    }

    async function deleteCurrentReport() {
        const modalEl = document.getElementById('deleteReportModal');
        const modal = modalEl && typeof bootstrap !== 'undefined'
            ? bootstrap.Modal.getOrCreateInstance(modalEl)
            : null;

        setDeleteLoading(true);

        try {
            const response = await fetch(reportDeleteUrl, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            const data = await parseJsonSafe(response);

            if (!response.ok) {
                showToast(data.message || 'Failed to delete marketing report.', 'danger');
                return;
            }

            if (modal) {
                modal.hide();
            }

            showToast(data.message || 'Marketing report deleted successfully.', 'danger');
            scheduleRedirect(reportIndexUrl);
        } catch (error) {
            showToast('Network error. Please try again.', 'danger');
        } finally {
            setDeleteLoading(false);
        }
    }
</script>
@endpush