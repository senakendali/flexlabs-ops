@extends('layouts.app-dashboard')

@section('title', 'Sales Daily Reports')

@section('content')
@php
    $reportItems = method_exists($reports, 'items')
        ? collect($reports->items())
        : collect($reports ?? []);

    $totalReports = method_exists($reports, 'total')
        ? (int) $reports->total()
        : $reportItems->count();

    $pageTotalLeads = (int) $reportItems->sum('total_leads');
    $pageInteracted = (int) $reportItems->sum('interacted');
    $pageIgnored = (int) $reportItems->sum('ignored');
    $pageConsultation = (int) $reportItems->sum('consultation');
    $pageHotLeads = (int) $reportItems->sum('hot_leads');
    $pageClosedDeal = (int) $reportItems->sum('closed_deal');
    $pageRevenue = (float) $reportItems->sum('revenue');

    $summaryTotalLeads = (int) data_get($totals ?? [], 'total_leads', $pageTotalLeads);
    $summaryInteracted = (int) data_get($totals ?? [], 'interacted', $pageInteracted);
    $summaryIgnored = (int) data_get($totals ?? [], 'ignored', $pageIgnored);
    $summaryConsultation = (int) data_get($totals ?? [], 'consultation', $pageConsultation);
    $summaryHotLeads = (int) data_get($totals ?? [], 'hot_leads', $pageHotLeads);
    $summaryClosedDeal = (int) data_get($totals ?? [], 'closed_deal', $pageClosedDeal);
    $summaryRevenue = (float) data_get($totals ?? [], 'revenue', $pageRevenue);

    $interactionRate = $summaryTotalLeads > 0
        ? round(($summaryInteracted / $summaryTotalLeads) * 100, 1)
        : 0;

    $consultationRate = $summaryTotalLeads > 0
        ? round(($summaryConsultation / $summaryTotalLeads) * 100, 1)
        : 0;

    $dealRate = $summaryTotalLeads > 0
        ? round(($summaryClosedDeal / $summaryTotalLeads) * 100, 1)
        : 0;

    $perPageValue = (int) request('per_page', $filters['per_page'] ?? 10);
@endphp

<div class="container-fluid px-4 py-4">

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Sales Reporting</div>
                <h1 class="page-title mb-2">Sales Daily Reports</h1>
                <p class="page-subtitle mb-0">
                    Pantau laporan harian sales untuk melihat perkembangan leads, interaksi, consultation, closed deal, dan revenue.
                </p>
            </div>

            <div class="page-header-actions d-flex gap-2 flex-wrap">
                @if(Route::has('sales-performance.index'))
                    <a href="{{ route('sales-performance.index') }}" class="btn btn-light btn-modern">
                        <i class="bi bi-graph-up-arrow me-2"></i>Performance
                    </a>
                @endif

                <a href="{{ route('sales-daily-reports.create') }}" class="btn btn-light btn-modern">
                    <i class="bi bi-plus-lg me-2"></i>Add Report
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-people"></i>
                    </div>
                    <div>
                        <div class="stat-title">Total Leads</div>
                        <div class="stat-value">{{ number_format($summaryTotalLeads) }}</div>
                    </div>
                </div>
                <div class="stat-description">Akumulasi leads sesuai filter aktif.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-chat-left-text"></i>
                    </div>
                    <div>
                        <div class="stat-title">Interacted</div>
                        <div class="stat-value">{{ number_format($summaryInteracted) }}</div>
                    </div>
                </div>
                <div class="stat-description">Interaction rate {{ number_format($interactionRate, 1) }}% dari total leads.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div>
                        <div class="stat-title">Closed Deal</div>
                        <div class="stat-value">{{ number_format($summaryClosedDeal) }}</div>
                    </div>
                </div>
                <div class="stat-description">Deal rate {{ number_format($dealRate, 1) }}% dari total leads.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div>
                        <div class="stat-title">Total Revenue</div>
                        <div class="stat-value">Rp {{ number_format($summaryRevenue, 0, ',', '.') }}</div>
                    </div>
                </div>
                <div class="stat-description">Akumulasi revenue sesuai filter aktif.</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-telephone"></i>
                    </div>
                    <div>
                        <div class="stat-title">Consultation</div>
                        <div class="stat-value">{{ number_format($summaryConsultation) }}</div>
                    </div>
                </div>
                <div class="stat-description">Consultation rate {{ number_format($consultationRate, 1) }}% dari total leads.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-fire"></i>
                    </div>
                    <div>
                        <div class="stat-title">Hot Leads</div>
                        <div class="stat-value">{{ number_format($summaryHotLeads) }}</div>
                    </div>
                </div>
                <div class="stat-description">Leads dengan potensi closing lebih tinggi.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-x-circle"></i>
                    </div>
                    <div>
                        <div class="stat-title">Ignored</div>
                        <div class="stat-value">{{ number_format($summaryIgnored) }}</div>
                    </div>
                </div>
                <div class="stat-description">Leads yang belum berhasil masuk ke interaksi.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>
                    <div>
                        <div class="stat-title">Reports</div>
                        <div class="stat-value">{{ number_format($totalReports) }}</div>
                    </div>
                </div>
                <div class="stat-description">Total report berdasarkan filter aktif.</div>
            </div>
        </div>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Filter Sales Reports</h5>
                <p class="content-card-subtitle mb-0">
                    Filter berdasarkan periode laporan dan jumlah data yang ingin ditampilkan.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            <form method="GET" action="{{ route('sales-daily-reports.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-xl-3 col-md-6">
                        <label for="date_from" class="form-label">Date From</label>
                        <input
                            type="date"
                            id="date_from"
                            name="date_from"
                            class="form-control"
                            value="{{ $filters['date_from'] ?? request('date_from') }}"
                        >
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <label for="date_to" class="form-label">Date To</label>
                        <input
                            type="date"
                            id="date_to"
                            name="date_to"
                            class="form-control"
                            value="{{ $filters['date_to'] ?? request('date_to') }}"
                        >
                    </div>

                    <div class="col-xl-2 col-md-6">
                        <label for="per_page" class="form-label">Show Entries</label>
                        <select name="per_page" id="per_page" class="form-select">
                            @foreach ([10, 25, 50, 100] as $size)
                                <option value="{{ $size }}" {{ $perPageValue === $size ? 'selected' : '' }}>
                                    {{ $size }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-xl-4 col-md-6">
                        <div class="d-flex gap-2 justify-content-xl-end flex-wrap">
                            <a href="{{ route('sales-daily-reports.index') }}" class="btn btn-outline-secondary btn-modern">
                                <i class="bi bi-arrow-counterclockwise me-2"></i>Reset
                            </a>

                            <button type="submit" class="btn btn-primary btn-modern">
                                <i class="bi bi-funnel me-2"></i>Apply Filter
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <div class="content-card">
        <div class="content-card-header">
            <div>
                <h5 class="content-card-title mb-1">Sales Daily Report List</h5>
                <p class="content-card-subtitle mb-0">
                    Klik detail untuk melihat highlight, summary, notes, dan breakdown report harian.
                </p>
            </div>
        </div>

        <div class="content-card-body">
            @if($reports->count())
                <div class="table-responsive dropdown-safe-table">
                    <table class="table table-hover align-middle admin-table mb-0">
                        <thead>
                            <tr>
                                <th class="text-nowrap">No</th>
                                <th class="text-nowrap">Report Date</th>
                                <th class="text-end text-nowrap">Leads</th>
                                <th class="text-end text-nowrap">Interacted</th>
                                <th class="text-end text-nowrap">Ignored</th>
                                <th class="text-end text-nowrap">Consultation</th>
                                <th class="text-end text-nowrap">Hot Leads</th>
                                <th class="text-end text-nowrap">Closed Deal</th>
                                <th class="text-end text-nowrap">Revenue</th>
                                <th class="text-nowrap">Created By</th>
                                <th class="text-end text-nowrap">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($reports as $report)
                                @php
                                    $rowNumber = method_exists($reports, 'currentPage')
                                        ? (($reports->currentPage() - 1) * $reports->perPage()) + $loop->iteration
                                        : $loop->iteration;
                                @endphp

                                <tr>
                                    <td class="text-muted">
                                        {{ $rowNumber }}
                                    </td>

                                    <td class="text-nowrap">
                                        <div class="fw-bold text-dark">
                                            {{ optional($report->report_date)->format('d M Y') ?? '-' }}
                                        </div>
                                        <div class="text-muted small">
                                            Created {{ optional($report->created_at)->format('d M Y H:i') ?? '-' }}
                                        </div>
                                    </td>

                                    <td class="text-end text-nowrap">
                                        <div class="fw-semibold text-dark">
                                            {{ number_format((int) ($report->total_leads ?? 0)) }}
                                        </div>
                                    </td>

                                    <td class="text-end text-nowrap">
                                        <div class="fw-semibold text-dark">
                                            {{ number_format((int) ($report->interacted ?? 0)) }}
                                        </div>
                                    </td>

                                    <td class="text-end text-nowrap">
                                        <div class="fw-semibold text-dark">
                                            {{ number_format((int) ($report->ignored ?? 0)) }}
                                        </div>
                                    </td>

                                    <td class="text-end text-nowrap">
                                        <div class="fw-semibold text-dark">
                                            {{ number_format((int) ($report->consultation ?? 0)) }}
                                        </div>
                                    </td>

                                    <td class="text-end text-nowrap">
                                        <div class="fw-semibold text-dark">
                                            {{ number_format((int) ($report->hot_leads ?? 0)) }}
                                        </div>
                                    </td>

                                    <td class="text-end text-nowrap">
                                        <div class="fw-bold text-dark">
                                            {{ number_format((int) ($report->closed_deal ?? 0)) }}
                                        </div>
                                    </td>

                                    <td class="text-end text-nowrap">
                                        <div class="fw-bold text-success">
                                            Rp {{ number_format((float) ($report->revenue ?? 0), 0, ',', '.') }}
                                        </div>
                                    </td>

                                    <td class="text-nowrap">
                                        <div class="fw-semibold">
                                            {{ $report->creator?->name ?? '-' }}
                                        </div>
                                        <div class="text-muted small">Report owner</div>
                                    </td>

                                    <td class="text-end text-nowrap">
                                        <div class="dropdown">
                                            <button
                                                class="btn btn-sm btn-outline-secondary dropdown-toggle px-3"
                                                type="button"
                                                data-bs-toggle="dropdown"
                                                data-bs-boundary="viewport"
                                                aria-expanded="false"
                                            >
                                                Actions
                                            </button>

                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                <li>
                                                    <a
                                                        href="{{ route('sales-daily-reports.show', $report) }}"
                                                        class="dropdown-item"
                                                    >
                                                        <i class="bi bi-eye me-2"></i>Show Detail
                                                    </a>
                                                </li>

                                                <li>
                                                    <a
                                                        href="{{ route('sales-daily-reports.edit', $report) }}"
                                                        class="dropdown-item"
                                                    >
                                                        <i class="bi bi-pencil-square me-2"></i>Edit Report
                                                    </a>
                                                </li>

                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>

                                                <li>
                                                    <form
                                                        action="{{ route('sales-daily-reports.destroy', $report) }}"
                                                        method="POST"
                                                        class="m-0"
                                                        onsubmit="return confirm('Delete sales daily report ini?')"
                                                    >
                                                        @csrf
                                                        @method('DELETE')

                                                        <button type="submit" class="dropdown-item text-danger">
                                                            <i class="bi bi-trash me-2"></i>Delete
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if(method_exists($reports, 'hasPages') && $reports->hasPages())
                    <div class="mt-3">
                        {{ $reports->withQueryString()->links() }}
                    </div>
                @endif
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>

                    <h5 class="empty-state-title">Belum ada sales daily report</h5>
                    <p class="empty-state-text mb-0">
                        Coba ubah filter tanggal atau buat laporan harian sales baru.
                    </p>

                    <div class="mt-3">
                        <a href="{{ route('sales-daily-reports.create') }}" class="btn btn-primary btn-modern">
                            <i class="bi bi-plus-lg me-2"></i>Add Report
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection