@extends('layouts.app-dashboard')

@section('title', 'Sales Daily Report Detail')

@section('content')
<div class="dashboard-content sales-report-page sales-report-detail-page">
    <div class="container-fluid py-4">
        <div class="sales-report-detail-header mb-4">
            <div class="sales-report-detail-heading">
                <h3 class="fw-bold mb-1">Sales Daily Report Detail</h3>
                <p class="text-muted mb-0">
                    Tinjau ringkasan performa harian sales untuk membaca perkembangan leads, kualitas interaksi,
                    <br class="d-none d-lg-block">serta hasil akhir dalam bentuk deal dan revenue dengan lebih jelas.
                </p>
            </div>

            <div class="sales-report-detail-actions">
                <button type="button" class="btn btn-outline-dark" id="downloadPngBtn">
                    <span class="default-text">
                        <i class="bi bi-download me-1"></i> Download
                    </span>
                    <span class="loading-text d-none">
                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        Processing...
                    </span>
                </button>

                <a href="{{ route('sales-daily-reports.edit', $report) }}" class="btn btn-primary">
                    <i class="bi bi-pencil-square me-1"></i> Edit
                </a>

                <a href="{{ route('sales-daily-reports.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>

        <div id="exportWrapper" class="sales-report-export-wrapper">
            <div id="reportCaptureArea" class="sales-report-capture-area">
                <div class="export-header mb-4">
                    <div>
                        <div class="export-eyebrow">FlexLabs Ops</div>
                        <h2 class="export-title mb-1">Sales Daily Report</h2>
                        <p class="export-subtitle mb-0">
                            {{ optional($report->report_date)->format('l, d F Y') }}
                        </p>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card dashboard-stat-card h-100 export-stat-card">
                            <div class="card-body d-flex flex-column">
                                <div class="stat-card-top">
                                    <div class="stat-icon">
                                        <i class="bi bi-people"></i>
                                    </div>

                                    <div class="stat-copy">
                                        <div class="text-muted small">Total Leads</div>
                                        <h4 class="fw-bold mb-0">{{ $report->total_leads }}</h4>
                                    </div>
                                </div>

                                <div class="mt-auto pt-2">
                                    <small class="text-secondary-emphasis d-block">
                                        <i class="bi bi-bar-chart-line me-1"></i> Total leads yang tercatat pada hari ini
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card dashboard-stat-card h-100 export-stat-card">
                            <div class="card-body d-flex flex-column">
                                <div class="stat-card-top">
                                    <div class="stat-icon">
                                        <i class="bi bi-chat-left-text"></i>
                                    </div>

                                    <div class="stat-copy">
                                        <div class="text-muted small">Interacted</div>
                                        <h4 class="fw-bold mb-0">{{ $report->interacted }}</h4>
                                    </div>
                                </div>

                                <div class="mt-auto pt-2">
                                    <small class="text-info-emphasis d-block">
                                        <i class="bi bi-arrow-up-right-circle me-1"></i> Leads yang berhasil masuk tahap interaksi
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card dashboard-stat-card h-100 export-stat-card">
                            <div class="card-body d-flex flex-column">
                                <div class="stat-card-top">
                                    <div class="stat-icon">
                                        <i class="bi bi-check2-circle"></i>
                                    </div>

                                    <div class="stat-copy">
                                        <div class="text-muted small">Closed Deal</div>
                                        <h4 class="fw-bold mb-0">{{ $report->closed_deal ?? 0 }}</h4>
                                    </div>
                                </div>

                                <div class="mt-auto pt-2">
                                    <small class="text-success-emphasis d-block">
                                        <i class="bi bi-trophy me-1"></i> Leads yang berhasil dikonversi menjadi deal
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card dashboard-stat-card h-100 export-stat-card">
                            <div class="card-body d-flex flex-column">
                                <div class="stat-card-top">
                                    <div class="stat-icon">
                                        <i class="bi bi-cash-stack"></i>
                                    </div>

                                    <div class="stat-copy">
                                        <div class="text-muted small">Revenue</div>
                                        <h4 class="fw-bold mb-0">Rp {{ number_format((float) ($report->revenue ?? 0), 0, ',', '.') }}</h4>
                                    </div>
                                </div>

                                <div class="mt-auto pt-2">
                                    <small class="text-success-emphasis d-block">
                                        <i class="bi bi-graph-up-arrow me-1"></i> Nilai revenue yang dihasilkan hari ini
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12 col-lg-8">
                        <div class="card h-100 shadow-sm border-0 export-section-card">
                            <div class="card-header">
                                <h5 class="fw-bold mb-1">Leads Summary for Today</h5>
                                <p class="text-muted small mb-0">
                                    Berikan insight singkat agar management dapat memahami cerita di balik angka dan performa hari ini.
                                </p>
                            </div>

                            <div class="card-body">
                                <div class="report-richtext">
                                    {!! nl2br(e($report->summary ?: '-')) !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-4">
                        <div class="card h-100 shadow-sm border-0 export-section-card">
                            <div class="card-header">
                                <h5 class="fw-bold mb-1">Report Information</h5>
                                <p class="text-muted small mb-0">
                                    Ringkasan angka utama untuk melihat funnel, outcome, dan pemilik laporan.
                                </p>
                            </div>

                            <div class="card-body">
                                <div class="report-info-list">
                                    <div class="report-info-linklike">
                                        <span class="report-info-left">
                                            <i class="bi bi-calendar-event me-2"></i>
                                            Report Date
                                        </span>
                                        <span class="report-info-right">
                                            {{ optional($report->report_date)->format('d M Y') }}
                                        </span>
                                    </div>

                                    <div class="report-info-linklike active">
                                        <span class="report-info-left">
                                            <i class="bi bi-person-badge me-2"></i>
                                            Created By
                                        </span>
                                        <span class="report-info-right">
                                            {{ $report->creator?->name ?? '-' }}
                                        </span>
                                    </div>

                                    <div class="report-info-linklike">
                                        <span class="report-info-left">
                                            <i class="bi bi-fire me-2"></i>
                                            Hot Leads
                                        </span>
                                        <span class="report-info-right">{{ $report->hot_leads }}</span>
                                    </div>

                                    <div class="report-info-linklike">
                                        <span class="report-info-left">
                                            <i class="bi bi-telephone me-2"></i>
                                            Consultation
                                        </span>
                                        <span class="report-info-right">{{ $report->consultation }}</span>
                                    </div>

                                    <div class="report-info-linklike">
                                        <span class="report-info-left">
                                            <i class="bi bi-eye-slash me-2"></i>
                                            Ignored
                                        </span>
                                        <span class="report-info-right">{{ $report->ignored }}</span>
                                    </div>

                                    <div class="report-info-linklike">
                                        <span class="report-info-left">
                                            <i class="bi bi-x-circle me-2"></i>
                                            Closed Lost
                                        </span>
                                        <span class="report-info-right">{{ $report->closed_lost }}</span>
                                    </div>

                                    <div class="report-info-linklike">
                                        <span class="report-info-left">
                                            <i class="bi bi-slash-circle me-2"></i>
                                            Not Related
                                        </span>
                                        <span class="report-info-right">{{ $report->not_related }}</span>
                                    </div>

                                    <div class="report-info-linklike">
                                        <span class="report-info-left">
                                            <i class="bi bi-thermometer-half me-2"></i>
                                            Warm Leads
                                        </span>
                                        <span class="report-info-right">{{ $report->warm_leads }}</span>
                                    </div>

                                    <div class="report-info-linklike">
                                        <span class="report-info-left">
                                            <i class="bi bi-check2-circle me-2"></i>
                                            Closed Deal
                                        </span>
                                        <span class="report-info-right">{{ $report->closed_deal ?? 0 }}</span>
                                    </div>

                                    <div class="report-info-linklike">
                                        <span class="report-info-left">
                                            <i class="bi bi-cash-stack me-2"></i>
                                            Revenue
                                        </span>
                                        <span class="report-info-right">
                                            Rp {{ number_format((float) ($report->revenue ?? 0), 0, ',', '.') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-lg-6">
                        <div class="card h-100 shadow-sm border-0 export-section-card">
                            <div class="card-header">
                                <h5 class="fw-bold mb-1">Hot Lead Highlight</h5>
                                <p class="text-muted small mb-0">
                                    Highlight lead potensial yang paling menonjol dan layak diprioritaskan.
                                </p>
                            </div>

                            <div class="card-body">
                                <div class="report-richtext">
                                    {!! nl2br(e($report->highlight ?: '-')) !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-6">
                        <div class="card h-100 shadow-sm border-0 export-section-card">
                            <div class="card-header">
                                <h5 class="fw-bold mb-1">Additional Notes</h5>
                                <p class="text-muted small mb-0">
                                    Catatan hambatan, insight lanjutan, atau next action yang perlu diperhatikan.
                                </p>
                            </div>

                            <div class="card-body">
                                <div class="report-richtext">
                                    {!! nl2br(e($report->notes ?: '-')) !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script>
    const downloadPngBtn = document.getElementById('downloadPngBtn');
    const exportWrapper = document.getElementById('exportWrapper');

    function setDownloadLoading(isLoading) {
        if (!downloadPngBtn) return;

        downloadPngBtn.disabled = isLoading;
        downloadPngBtn.querySelector('.default-text')?.classList.toggle('d-none', isLoading);
        downloadPngBtn.querySelector('.loading-text')?.classList.toggle('d-none', !isLoading);
    }

    async function downloadDashboardAsPng() {
        if (!exportWrapper) return;

        setDownloadLoading(true);

        try {
            const canvas = await html2canvas(exportWrapper, {
                scale: window.innerWidth < 768 ? 1.6 : 2,
                useCORS: true,
                backgroundColor: null,
                logging: false,
                windowWidth: document.documentElement.scrollWidth
            });

            const link = document.createElement('a');
            const formattedDate = @json(optional($report->report_date)->format('Y-m-d') ?: 'report');
            link.download = `sales-daily-report-${formattedDate}.png`;
            link.href = canvas.toDataURL('image/png');
            link.click();
        } catch (error) {
            alert('Failed to download PNG. Please try again.');
        } finally {
            setDownloadLoading(false);
        }
    }

    downloadPngBtn?.addEventListener('click', downloadDashboardAsPng);
</script>
@endpush