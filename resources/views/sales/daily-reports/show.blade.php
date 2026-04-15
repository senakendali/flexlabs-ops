@extends('layouts.app-dashboard')

@section('title', 'Sales Daily Report Detail')

@section('content')
<div class="dashboard-content">
    <div class="container-fluid py-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h3 class="fw-bold mb-1">Sales Daily Report Detail</h3>
                <p class="text-muted mb-0">
                    Tinjau ringkasan performa harian sales untuk membaca volume leads, kualitas interaksi, <br>dan peluang konsultasi dengan lebih jelas.
                </p>
            </div>

            <div class="d-flex flex-wrap gap-2">
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

        <div id="exportWrapper">
            <div id="reportCaptureArea">
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
                                <div class="d-flex align-items-start gap-3">
                                    <div class="stat-icon">
                                        <i class="bi bi-people"></i>
                                    </div>

                                    <div>
                                        <div class="text-muted small">Total Leads</div>
                                        <h4 class="fw-bold mb-0">{{ $report->total_leads }}</h4>
                                    </div>
                                </div>

                                <div class="mt-auto pt-2">
                                    <small class="text-secondary-emphasis">
                                        <i class="bi bi-bar-chart-line"></i> Total leads yang tercatat pada hari ini
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card dashboard-stat-card h-100 export-stat-card">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="stat-icon">
                                        <i class="bi bi-chat-left-text"></i>
                                    </div>

                                    <div>
                                        <div class="text-muted small">Interacted</div>
                                        <h4 class="fw-bold mb-0">{{ $report->interacted }}</h4>
                                    </div>
                                </div>

                                <div class="mt-auto pt-2">
                                    <small class="text-info-emphasis">
                                        <i class="bi bi-arrow-up-right-circle"></i> Leads yang berhasil masuk tahap interaksi
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card dashboard-stat-card h-100 export-stat-card">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="stat-icon">
                                        <i class="bi bi-fire"></i>
                                    </div>

                                    <div>
                                        <div class="text-muted small">Hot Leads</div>
                                        <h4 class="fw-bold mb-0">{{ $report->hot_leads }}</h4>
                                    </div>
                                </div>

                                <div class="mt-auto pt-2">
                                    <small class="text-warning-emphasis">
                                        <i class="bi bi-stars"></i> Leads prioritas tinggi yang perlu difollow up
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card dashboard-stat-card h-100 export-stat-card">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="stat-icon">
                                        <i class="bi bi-telephone"></i>
                                    </div>

                                    <div>
                                        <div class="text-muted small">Consultation</div>
                                        <h4 class="fw-bold mb-0">{{ $report->consultation }}</h4>
                                    </div>
                                </div>

                                <div class="mt-auto pt-2">
                                    <small class="text-primary-emphasis">
                                        <i class="bi bi-headset"></i> Leads yang lanjut ke tahap konsultasi
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-lg-8">
                        <div class="card h-100 shadow-sm border-0 export-section-card">
                            <div class="card-header">
                                <h5 class="fw-bold mb-1">Leads Summary for Today</h5>
                                <p class="text-muted small mb-0">
                                    Ringkasan performa harian untuk memberikan gambaran jelas mengenai aktivitas dan kualitas leads pada hari ini.
                                </p>
                            </div>

                            <div class="card-body">
                                <div class="report-richtext">
                                    {!! nl2br(e($report->summary ?: '-')) !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card h-100 shadow-sm border-0 export-section-card">
                            <div class="card-header">
                                <h5 class="fw-bold mb-1">Report Information</h5>
                                <p class="text-muted small mb-0">
                                   Gambaran singkat performa harian untuk membaca tren leads, interaksi, dan peluang konsultasi.
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
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-lg-6">
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

                    <div class="col-lg-6">
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

@push('styles')
<style>
    #exportWrapper {
        padding: 40px;
        background: #f4f2f8;
        border-radius: 24px;
    }

    #reportCaptureArea {
        background: #ffffff;
        border-radius: 24px;
        padding: 28px;
        box-shadow: 0 20px 50px rgba(31, 23, 43, 0.08);
    }

    .export-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        padding-bottom: 8px;
        border-bottom: 1px solid #ebe7f3;
    }

    .export-eyebrow {
        display: inline-block;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #5b3e8e;
        margin-bottom: 8px;
    }

    .export-title {
        font-size: 28px;
        font-weight: 700;
        color: #1f172b;
    }

    .export-subtitle {
        font-size: 14px;
        color: #6b7280;
    }

    .export-stat-card,
    .export-section-card {
        border-radius: 18px;
    }

    .report-richtext {
        line-height: 1.85;
        color: #374151;
        font-size: 15px;
        white-space: normal;
        word-break: break-word;
    }

    .report-info-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .report-info-linklike {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 16px 18px;
        border: 1px solid #e6e0f0;
        border-radius: 18px;
        background: #ffffff;
        transition: all 0.2s ease;
    }

    .report-info-linklike.active {
        border-color: #cdb7ff;
        background: #faf7ff;
        color: #5b3e8e;
    }

    .report-info-left {
        display: inline-flex;
        align-items: center;
        font-size: 15px;
        font-weight: 500;
        color: #1f2937;
    }

    .report-info-linklike.active .report-info-left,
    .report-info-linklike.active .report-info-right {
        color: #5b3e8e;
    }

    .report-info-right {
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        text-align: right;
    }

    @media (max-width: 991.98px) {
        #exportWrapper {
            padding: 24px;
        }

        #reportCaptureArea {
            padding: 22px;
        }
    }

    @media (max-width: 767.98px) {
        #exportWrapper {
            padding: 16px;
            border-radius: 18px;
        }

        #reportCaptureArea {
            padding: 18px;
            border-radius: 18px;
        }

        .export-title {
            font-size: 22px;
        }

        .report-info-linklike {
            flex-direction: column;
            align-items: flex-start;
            gap: 6px;
        }

        .report-info-right {
            text-align: left;
        }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script>
    const downloadPngBtn = document.getElementById('downloadPngBtn');
    const exportWrapper = document.getElementById('exportWrapper');

    function setDownloadLoading(isLoading) {
        downloadPngBtn.disabled = isLoading;
        downloadPngBtn.querySelector('.default-text').classList.toggle('d-none', isLoading);
        downloadPngBtn.querySelector('.loading-text').classList.toggle('d-none', !isLoading);
    }

    async function downloadDashboardAsPng() {
        setDownloadLoading(true);

        try {
            const canvas = await html2canvas(exportWrapper, {
                scale: 2,
                useCORS: true,
                backgroundColor: null,
                logging: false
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

    downloadPngBtn.addEventListener('click', downloadDashboardAsPng);
</script>
@endpush