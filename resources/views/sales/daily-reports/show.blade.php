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
                    <button type="button" class="btn btn-success" id="copyWhatsAppBtn">
                        <span class="default-text">
                            <i class="bi bi-whatsapp me-1"></i> Copy for WhatsApp
                        </span>
                        <span class="success-text d-none">
                            <i class="bi bi-check2-circle me-1"></i> Copied
                        </span>
                    </button>

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
                        @php
                            $stats = [
                                [
                                    'label' => 'Total Leads',
                                    'value' => $report->total_leads,
                                    'icon' => 'bi-people',
                                    'description_icon' => 'bi-bar-chart-line',
                                    'description' => 'Total leads yang tercatat pada hari ini',
                                    'description_class' => 'text-secondary-emphasis',
                                ],
                                [
                                    'label' => 'Interacted',
                                    'value' => $report->interacted,
                                    'icon' => 'bi-chat-left-text',
                                    'description_icon' => 'bi-arrow-up-right-circle',
                                    'description' => 'Leads yang berhasil masuk tahap interaksi',
                                    'description_class' => 'text-info-emphasis',
                                ],
                                [
                                    'label' => 'Closed Deal',
                                    'value' => $report->closed_deal ?? 0,
                                    'icon' => 'bi-check2-circle',
                                    'description_icon' => 'bi-trophy',
                                    'description' => 'Leads yang berhasil dikonversi menjadi deal',
                                    'description_class' => 'text-success-emphasis',
                                ],
                                [
                                    'label' => 'Revenue',
                                    'value' => 'Rp ' . number_format((float) ($report->revenue ?? 0), 0, ',', '.'),
                                    'icon' => 'bi-cash-stack',
                                    'description_icon' => 'bi-graph-up-arrow',
                                    'description' => 'Nilai revenue yang dihasilkan hari ini',
                                    'description_class' => 'text-success-emphasis',
                                ],
                            ];
                        @endphp

                        @foreach ($stats as $stat)
                            <div class="col-12 col-sm-6 col-xl-3">
                                <div class="card dashboard-stat-card h-100 export-stat-card">
                                    <div class="card-body d-flex flex-column">
                                        <div class="stat-card-top">
                                            <div class="stat-icon">
                                                <i class="bi {{ $stat['icon'] }}"></i>
                                            </div>
                                            <div class="stat-copy">
                                                <div class="text-muted small">{{ $stat['label'] }}</div>
                                                <h4 class="fw-bold mb-0">{{ $stat['value'] }}</h4>
                                            </div>
                                        </div>
                                        <div class="mt-auto pt-2">
                                            <small class="{{ $stat['description_class'] }} d-block">
                                                <i class="bi {{ $stat['description_icon'] }} me-1"></i>
                                                {{ $stat['description'] }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
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
                                        @php
                                            $reportInformation = [
                                                ['label' => 'Report Date', 'value' => optional($report->report_date)->format('d M Y'), 'icon' => 'bi-calendar-event'],
                                                ['label' => 'Created By', 'value' => $report->creator?->name ?? '-', 'icon' => 'bi-person-badge', 'active' => true],
                                                ['label' => 'Hot Leads', 'value' => $report->hot_leads, 'icon' => 'bi-fire'],
                                                ['label' => 'Consultation', 'value' => $report->consultation, 'icon' => 'bi-telephone'],
                                                ['label' => 'Ignored', 'value' => $report->ignored, 'icon' => 'bi-eye-slash'],
                                                ['label' => 'Closed Lost', 'value' => $report->closed_lost, 'icon' => 'bi-x-circle'],
                                                ['label' => 'Not Related', 'value' => $report->not_related, 'icon' => 'bi-slash-circle'],
                                                ['label' => 'Warm Leads', 'value' => $report->warm_leads, 'icon' => 'bi-thermometer-half'],
                                                ['label' => 'Closed Deal', 'value' => $report->closed_deal ?? 0, 'icon' => 'bi-check2-circle'],
                                                ['label' => 'Revenue', 'value' => 'Rp ' . number_format((float) ($report->revenue ?? 0), 0, ',', '.'), 'icon' => 'bi-cash-stack'],
                                            ];
                                        @endphp

                                        @foreach ($reportInformation as $information)
                                            <div class="report-info-linklike {{ !empty($information['active']) ? 'active' : '' }}">
                                                <span class="report-info-left">
                                                    <i class="bi {{ $information['icon'] }} me-2"></i>
                                                    {{ $information['label'] }}
                                                </span>
                                                <span class="report-info-right">{{ $information['value'] }}</span>
                                            </div>
                                        @endforeach
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
        const copyWhatsAppBtn = document.getElementById('copyWhatsAppBtn');
        const downloadPngBtn = document.getElementById('downloadPngBtn');
        const exportWrapper = document.getElementById('exportWrapper');

        const reportData = @json([
            'date' => optional($report->report_date)->format('l, d F Y'),
            'short_date' => optional($report->report_date)->format('d M Y'),
            'created_by' => $report->creator?->name ?? '-',
            'total_leads' => $report->total_leads ?? 0,
            'interacted' => $report->interacted ?? 0,
            'closed_deal' => $report->closed_deal ?? 0,
            'revenue' => (float) ($report->revenue ?? 0),
            'hot_leads' => $report->hot_leads ?? 0,
            'warm_leads' => $report->warm_leads ?? 0,
            'consultation' => $report->consultation ?? 0,
            'ignored' => $report->ignored ?? 0,
            'closed_lost' => $report->closed_lost ?? 0,
            'not_related' => $report->not_related ?? 0,
            'summary' => $report->summary ?: '-',
            'highlight' => $report->highlight ?: '-',
            'notes' => $report->notes ?: '-',
        ]);

        function formatRupiah(value) {
            return new Intl.NumberFormat('id-ID', {
                maximumFractionDigits: 0
            }).format(Number(value) || 0);
        }

        function cleanReportText(value) {
            if (!value) return '-';

            return String(value)
                .replace(/\r\n/g, '\n')
                .replace(/\r/g, '\n')
                .replace(/\\+/g, '')
                .replace(/^\s*>\s?/gm, '')
                .replace(/[ \t]+\n/g, '\n')
                .replace(/\n{3,}/g, '\n\n')
                .trim() || '-';
        }

        function generateWhatsAppReport() {
            return [
                '*SALES DAILY REPORT*',
                `*${reportData.date || '-'}*`,
                '',
                '*PERFORMANCE SUMMARY*',
                `• Total Leads: ${reportData.total_leads}`,
                `• Interacted: ${reportData.interacted}`,
                `• Closed Deal: ${reportData.closed_deal}`,
                `• Revenue: Rp ${formatRupiah(reportData.revenue)}`,
                '',
                '*LEAD FUNNEL*',
                `• Hot Leads: ${reportData.hot_leads}`,
                `• Warm Leads: ${reportData.warm_leads}`,
                `• Consultation: ${reportData.consultation}`,
                `• Ignored: ${reportData.ignored}`,
                `• Closed Lost: ${reportData.closed_lost}`,
                `• Not Related: ${reportData.not_related}`,
                '',
                '*LEADS SUMMARY*',
                cleanReportText(reportData.summary),
                '',
                '*HOT LEAD HIGHLIGHT*',
                cleanReportText(reportData.highlight),
                '',
                '*ADDITIONAL NOTES*',
                cleanReportText(reportData.notes),
                '',
                '*REPORT INFORMATION*',
                `• Report Date: ${reportData.short_date || '-'}`,
                `• Created By: ${reportData.created_by || '-'}`,
            ].join('\n');
        }

        async function copyTextToClipboard(text) {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(text);
                return;
            }

            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'fixed';
            textarea.style.top = '-9999px';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            textarea.setSelectionRange(0, textarea.value.length);

            const copied = document.execCommand('copy');
            document.body.removeChild(textarea);

            if (!copied) {
                throw new Error('Unable to copy report.');
            }
        }

        function showCopySuccess() {
            if (!copyWhatsAppBtn) return;

            const defaultText = copyWhatsAppBtn.querySelector('.default-text');
            const successText = copyWhatsAppBtn.querySelector('.success-text');

            defaultText?.classList.add('d-none');
            successText?.classList.remove('d-none');
            copyWhatsAppBtn.disabled = true;

            window.setTimeout(() => {
                defaultText?.classList.remove('d-none');
                successText?.classList.add('d-none');
                copyWhatsAppBtn.disabled = false;
            }, 2000);
        }

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

        copyWhatsAppBtn?.addEventListener('click', async () => {
            try {
                await copyTextToClipboard(generateWhatsAppReport());
                showCopySuccess();
            } catch (error) {
                alert('Report gagal disalin. Silakan coba kembali.');
            }
        });

        downloadPngBtn?.addEventListener('click', downloadDashboardAsPng);
    </script>
@endpush