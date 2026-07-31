@extends('layouts.executive-center')

@section('title', 'Strategic Reports')
@section('page_title', 'Strategic Reports')
@section(
    'page_description',
    'Management evaluation snapshots, strategic decisions, and action plans by period.'
)

@section('header_actions')
    <button
        id="openGenerateReport"
        type="button"
        class="inline-flex h-10 items-center gap-2 rounded-xl bg-executive-primary px-4 text-xs font-extrabold text-white shadow-button transition hover:-translate-y-0.5 hover:bg-executive-primaryDark"
    >
        <i data-lucide="plus" class="h-4 w-4" aria-hidden="true"></i>

        Generate Report
    </button>
@endsection

@push('styles')
    <style>
        .executive-main-card {
            overflow: visible !important;
            border: 0 !important;
            background: transparent !important;
            box-shadow: none !important;
        }

        .executive-page-header {
            border: 1px solid #E5E1EE !important;
            border-radius: 1.25rem !important;
            background: #FFFFFF !important;
            box-shadow: 0 16px 45px rgba(31, 27, 46, .07) !important;
        }

        .executive-content {
            /*
             * Layout sudah memberi jarak vertikal pada wrapper ini.
             * Reset top padding agar gap tidak ditumpuk dengan spacing
             * yang sengaja dipasang pada root halaman di bawah.
             */
            padding-top: 0 !important;
            padding-right: 0 !important;
            padding-bottom: 0 !important;
            padding-left: 0 !important;
        }

        #reportLibraryPage {
            margin: 0 !important;
            padding-top: 1.25rem !important;
            padding-right: 0 !important;
            padding-bottom: 0 !important;
            padding-left: 0 !important;
        }

        .report-library-card {
            position: relative;
            overflow: hidden;
            transition:
                transform .2s ease,
                border-color .2s ease,
                box-shadow .2s ease;
        }

        .report-library-card::before {
            position: absolute;
            top: 0;
            right: 0;
            left: 0;
            height: 3px;
            background: linear-gradient(
                90deg,
                #5B3E8E 0%,
                #8768B5 55%,
                #FFBE04 100%
            );
            content: '';
            opacity: .85;
        }

        .report-library-card:hover {
            transform: translateY(-3px);
            border-color: #D9D0E8;
            box-shadow: 0 22px 50px rgba(31, 27, 46, .11);
        }

        .report-filter-control {
            width: 100%;
            height: 2.75rem;
            border: 1px solid #E5E1EE;
            border-radius: .75rem;
            background-color: #FFFFFF;
            padding-right: .875rem;
            padding-left: .875rem;
            color: #1F1B2E;
            font-size: .75rem;
            font-weight: 700;
            outline: none;
            transition:
                border-color .2s ease,
                box-shadow .2s ease;
        }

        .report-filter-control:focus {
            border-color: #8D74B4;
            box-shadow: 0 0 0 3px rgba(91, 62, 142, .10);
        }

        .report-filter-control::placeholder {
            color: #A39CAA;
            font-weight: 600;
        }

        .report-coverage-track {
            overflow: hidden;
            height: .35rem;
            border-radius: 999px;
            background: #EEEAF3;
        }

        .report-coverage-bar {
            height: 100%;
            min-width: .25rem;
            border-radius: inherit;
            background: linear-gradient(90deg, #5B3E8E, #8C6ABB);
        }

        .report-dialog-panel {
            animation: report-dialog-enter .18s ease-out;
        }

        @keyframes report-dialog-enter {
            from {
                transform: translateY(10px) scale(.98);
                opacity: 0;
            }

            to {
                transform: translateY(0) scale(1);
                opacity: 1;
            }
        }

        @media (max-width: 639px) {
            .executive-page-header {
                border-radius: 1rem !important;
            }

            #reportLibraryPage {
                padding-top: 1rem !important;
            }

            .report-library-card:hover {
                transform: none;
            }
        }
    </style>
@endpush

@php
    $reportItems = collect($reports->items());

    $totalReports = method_exists($reports, 'total')
        ? $reports->total()
        : $reportItems->count();

    $finalizedReports = $reportItems
        ->where('status', 'finalized')
        ->count();

    $draftReports = $reportItems
        ->where('status', 'draft')
        ->count();

    $latestGenerated = $reportItems
        ->filter(fn ($report) => $report->generated_at)
        ->sortByDesc('generated_at')
        ->first();

    $hasActiveFilters =
        filled($filters['type'] ?? null)
        || filled($filters['year'] ?? null)
        || filled($filters['status'] ?? null)
        || filled($filters['search'] ?? null);

    $healthStyles = [
        'healthy' => [
            'label' => 'Healthy',
            'dot' => 'bg-emerald-500',
        ],
        'watch' => [
            'label' => 'Watch',
            'dot' => 'bg-amber-500',
        ],
        'critical' => [
            'label' => 'Critical',
            'dot' => 'bg-rose-500',
        ],
        'data_limited' => [
            'label' => 'Data Limited',
            'dot' => 'bg-slate-400',
        ],
    ];
@endphp

@section('content')
    <div
        id="reportLibraryPage"
        class="relative min-w-0"
        aria-busy="false"
    >
        <div
            id="reportLibraryLoading"
            class="absolute inset-0 z-30 hidden min-h-[420px] items-start justify-center rounded-2xl bg-white/90 pt-28 backdrop-blur-sm"
            role="status"
        >
            <div class="rounded-2xl border border-executive-line bg-white px-8 py-6 text-center shadow-panel">
                <i
                    data-lucide="loader-circle"
                    class="mx-auto h-7 w-7 animate-spin text-executive-primary"
                    aria-hidden="true"
                ></i>

                <p class="mt-3 text-sm font-extrabold text-executive-ink">
                    Loading reports
                </p>

                <p class="mt-1 text-[10px] text-executive-muted">
                    Refreshing the strategic report library...
                </p>
            </div>
        </div>

        <section class="overflow-hidden rounded-2xl border border-executive-line bg-white shadow-panel">
            <div class="relative border-b border-executive-line px-5 py-5 sm:px-6">
                <div class="pointer-events-none absolute right-0 top-0 h-36 w-72 bg-gradient-to-bl from-violet-100/60 to-transparent"></div>

                <div class="relative flex flex-wrap items-start justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-violet-50 text-executive-primary ring-1 ring-violet-100">
                            <i data-lucide="library" class="h-4 w-4" aria-hidden="true"></i>
                        </span>

                        <div>
                            <h2 class="text-sm font-extrabold text-executive-ink">
                                Strategic Report Library
                            </h2>

                            <p class="mt-0.5 text-[11px] text-executive-muted">
                                Monthly and quarterly management evaluation history.
                            </p>
                        </div>
                    </div>

                    <span class="rounded-lg bg-violet-50 px-3 py-1.5 text-[10px] font-extrabold text-executive-primary ring-1 ring-inset ring-violet-100">
                        {{ number_format($totalReports) }}
                        {{ Str::plural('Report', $totalReports) }}
                    </span>
                </div>

                <div class="relative mt-5 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-xl border border-violet-100 bg-violet-50/60 p-3.5">
                        <p class="text-[9px] font-bold uppercase tracking-wide text-executive-muted">
                            Reports Found
                        </p>

                        <p class="mt-1 text-xl font-black text-executive-ink">
                            {{ number_format($totalReports) }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-emerald-100 bg-emerald-50/60 p-3.5">
                        <p class="text-[9px] font-bold uppercase tracking-wide text-executive-muted">
                            Finalized on Page
                        </p>

                        <p class="mt-1 text-xl font-black text-emerald-700">
                            {{ number_format($finalizedReports) }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-amber-100 bg-amber-50/60 p-3.5">
                        <p class="text-[9px] font-bold uppercase tracking-wide text-executive-muted">
                            Draft on Page
                        </p>

                        <div class="mt-1 flex items-end justify-between gap-2">
                            <p class="text-xl font-black text-amber-700">
                                {{ number_format($draftReports) }}
                            </p>

                            <p class="truncate text-right text-[9px] text-executive-muted">
                                {{ $latestGenerated?->generated_at?->format('d M Y H:i') ?? 'No generation yet' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <form
                id="reportLibraryFilters"
                method="GET"
                class="p-5 sm:p-6"
            >
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-extrabold text-executive-ink">
                            Filter Reports
                        </p>

                        <p class="mt-1 text-[10px] text-executive-muted">
                            Narrow the library by period, year, status, or title.
                        </p>
                    </div>

                    @if($hasActiveFilters)
                        <a
                            href="{{ route('executive-center.strategic-reports.index') }}"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-executive-line bg-white px-3 py-2 text-[10px] font-extrabold text-executive-muted transition hover:border-violet-200 hover:text-executive-primary"
                        >
                            <i data-lucide="rotate-ccw" class="h-3.5 w-3.5" aria-hidden="true"></i>

                            Reset Filters
                        </a>
                    @endif
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <label class="block">
                        <span class="mb-2 block text-[10px] font-extrabold text-executive-muted">
                            Report Type
                        </span>

                        <select name="type" class="report-filter-control">
                            <option value="">All report types</option>
                            <option value="monthly" @selected(($filters['type'] ?? '') === 'monthly')>
                                Monthly
                            </option>
                            <option value="quarterly" @selected(($filters['type'] ?? '') === 'quarterly')>
                                Quarterly
                            </option>
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-[10px] font-extrabold text-executive-muted">
                            Report Year
                        </span>

                        <select name="year" class="report-filter-control">
                            <option value="">All years</option>

                            @foreach($years as $year)
                                <option value="{{ $year }}" @selected(($filters['year'] ?? '') == $year)>
                                    {{ $year }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-[10px] font-extrabold text-executive-muted">
                            Report Status
                        </span>

                        <select name="status" class="report-filter-control">
                            <option value="">All statuses</option>
                            <option value="draft" @selected(($filters['status'] ?? '') === 'draft')>
                                Draft
                            </option>
                            <option value="finalized" @selected(($filters['status'] ?? '') === 'finalized')>
                                Finalized
                            </option>
                        </select>
                    </label>

                    <label class="block">
                        <span class="mb-2 block text-[10px] font-extrabold text-executive-muted">
                            Search Report
                        </span>

                        <div class="relative">
                            <i
                                data-lucide="search"
                                class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                                aria-hidden="true"
                            ></i>

                            <input
                                name="search"
                                value="{{ $filters['search'] ?? '' }}"
                                placeholder="Search report title..."
                                autocomplete="off"
                                class="report-filter-control !pl-9"
                            >
                        </div>
                    </label>
                </div>
            </form>
        </section>

        <div id="reportLibraryContent" class="mt-5">
            @if($reports->isEmpty())
                <section class="rounded-2xl border border-dashed border-violet-200 bg-white px-6 py-16 text-center shadow-panel">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-violet-50 text-executive-primary ring-1 ring-violet-100">
                        <i data-lucide="file-text" class="h-6 w-6" aria-hidden="true"></i>
                    </span>

                    <h2 class="mt-5 text-sm font-extrabold text-executive-ink">
                        {{ $hasActiveFilters
                            ? 'No reports match the current filters'
                            : 'No strategic reports yet' }}
                    </h2>

                    <p class="mx-auto mt-2 max-w-md text-xs leading-5 text-executive-muted">
                        {{ $hasActiveFilters
                            ? 'Try changing or resetting the filters to display other strategic reports.'
                            : 'Generate the first monthly or quarterly management snapshot to begin building your report history.' }}
                    </p>

                    <div class="mt-5 flex flex-wrap justify-center gap-2">
                        @if($hasActiveFilters)
                            <a
                                href="{{ route('executive-center.strategic-reports.index') }}"
                                class="rounded-xl border border-executive-line bg-white px-4 py-2.5 text-xs font-extrabold text-executive-primary"
                            >
                                Reset Filters
                            </a>
                        @endif

                        <button
                            type="button"
                            data-open-report-dialog
                            class="rounded-xl bg-executive-primary px-4 py-2.5 text-xs font-extrabold text-white"
                        >
                            Generate Report
                        </button>
                    </div>
                </section>
            @else
                <div class="grid gap-4 xl:grid-cols-2">
                    @foreach($reports as $report)
                        @php
                            $healthKey = $report->overall_business_health
                                ?? 'data_limited';

                            $health = $healthStyles[$healthKey]
                                ?? $healthStyles['data_limited'];

                            $coverage = max(
                                0,
                                min(100, (int) ($report->data_coverage ?? 0))
                            );

                            $isFinalized = $report->status === 'finalized';
                        @endphp

                        <article class="report-library-card flex min-h-full flex-col rounded-2xl border border-executive-line bg-white p-5 shadow-panel sm:p-6">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex min-w-0 items-start gap-3">
                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-executive-primary ring-1 ring-violet-100">
                                        @if($report->period_type === 'quarterly')
                                            <i data-lucide="calendar-range" class="h-5 w-5" aria-hidden="true"></i>
                                        @else
                                            <i data-lucide="file-text" class="h-5 w-5" aria-hidden="true"></i>
                                        @endif
                                    </span>

                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="text-[9px] font-extrabold uppercase tracking-wider text-executive-primary">
                                                {{ ucfirst($report->period_type) }} Report
                                            </span>

                                            <span class="h-1 w-1 rounded-full bg-slate-300"></span>

                                            <span class="text-[9px] font-bold text-executive-muted">
                                                Revision {{ $report->revision }}
                                            </span>
                                        </div>

                                        <h2 class="mt-2 text-base font-extrabold leading-6 text-executive-ink">
                                            {{ $report->title }}
                                        </h2>

                                        <p class="mt-1.5 inline-flex items-center gap-1.5 text-[10px] font-semibold text-executive-muted">
                                            <i data-lucide="calendar-days" class="h-3.5 w-3.5" aria-hidden="true"></i>

                                            {{ $report->period_start->format('d M Y') }}
                                            –
                                            {{ $report->period_end->format('d M Y') }}
                                        </p>
                                    </div>
                                </div>

                                <span
                                    class="shrink-0 rounded-lg px-2.5 py-1.5 text-[9px] font-extrabold uppercase ring-1 ring-inset {{ $isFinalized
                                        ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                                        : 'bg-amber-50 text-amber-700 ring-amber-200' }}"
                                >
                                    {{ ucfirst($report->status) }}
                                </span>
                            </div>

                            <div class="mt-5 grid grid-cols-2 gap-3">
                                <div class="rounded-xl border border-executive-line bg-slate-50/60 p-3.5">
                                    <p class="text-[9px] font-bold uppercase tracking-wide text-executive-muted">
                                        Business Health
                                    </p>

                                    <div class="mt-2 flex items-center gap-2">
                                        <span class="h-2 w-2 shrink-0 rounded-full {{ $health['dot'] }}"></span>

                                        <span class="text-xs font-extrabold text-executive-ink">
                                            {{ $health['label'] }}
                                        </span>
                                    </div>
                                </div>

                                <div class="rounded-xl border border-executive-line bg-slate-50/60 p-3.5">
                                    <p class="text-[9px] font-bold uppercase tracking-wide text-executive-muted">
                                        Data Confidence
                                    </p>

                                    <p class="mt-2 text-xs font-extrabold text-executive-ink">
                                        {{ ucfirst($report->data_confidence ?? 'Unavailable') }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4 rounded-xl border border-violet-100 bg-violet-50/40 p-3.5">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-[9px] font-bold uppercase tracking-wide text-executive-muted">
                                            Data Coverage
                                        </p>

                                        <p class="mt-1 text-[10px] text-executive-muted">
                                            Sources included in this report
                                        </p>
                                    </div>

                                    <p class="text-lg font-black text-executive-primary">
                                        {{ $coverage }}%
                                    </p>
                                </div>

                                <div class="report-coverage-track mt-3">
                                    <div
                                        class="report-coverage-bar"
                                        style="width: {{ $coverage }}%"
                                    ></div>
                                </div>
                            </div>

                            <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-3 border-t border-executive-line pt-4">
                                <div>
                                    <dt class="text-[9px] font-bold uppercase tracking-wide text-executive-muted">
                                        Generated
                                    </dt>

                                    <dd class="mt-1 text-[10px] font-extrabold text-executive-ink">
                                        {{ $report->generated_at?->format('d M Y H:i') ?? '—' }}
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-[9px] font-bold uppercase tracking-wide text-executive-muted">
                                        Finalized
                                    </dt>

                                    <dd class="mt-1 text-[10px] font-extrabold text-executive-ink">
                                        {{ $report->finalized_at?->format('d M Y H:i') ?? 'Not finalized' }}
                                    </dd>
                                </div>
                            </dl>

                            <div class="mt-auto flex flex-wrap items-center gap-2 pt-5">
                                <a
                                    href="{{ route('executive-center.strategic-reports.show', $report) }}"
                                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-executive-primary px-4 py-2.5 text-[10px] font-extrabold text-white transition hover:bg-executive-primaryDark"
                                >
                                    View Report

                                    <i data-lucide="chevron-right" class="h-3.5 w-3.5" aria-hidden="true"></i>
                                </a>

                                <a
                                    href="{{ route('executive-center.strategic-reports.pdf', $report) }}"
                                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-executive-line bg-white px-4 py-2.5 text-[10px] font-extrabold text-executive-primary transition hover:border-violet-200 hover:bg-violet-50"
                                >
                                    <i data-lucide="download" class="h-3.5 w-3.5" aria-hidden="true"></i>

                                    Export PDF
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>

                @if($reports->hasPages())
                    <div class="mt-5 rounded-2xl border border-executive-line bg-white px-4 py-3 shadow-panel">
                        {{ $reports->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>

    <div
        id="generateReportDialog"
        class="fixed inset-0 z-[80] hidden items-center justify-center bg-slate-950/40 p-4 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        aria-labelledby="generateReportTitle"
    >
        <form
            id="generateReportForm"
            action="{{ route('executive-center.strategic-reports.store') }}"
            method="POST"
            class="report-dialog-panel w-full max-w-lg overflow-hidden rounded-2xl border border-white/20 bg-white shadow-shell"
        >
            @csrf

            <div class="relative overflow-hidden border-b border-executive-line px-6 py-5">
                <div class="pointer-events-none absolute right-0 top-0 h-32 w-56 bg-gradient-to-bl from-violet-100 to-transparent"></div>

                <div class="relative flex items-start justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-executive-primary ring-1 ring-violet-100">
                            <i data-lucide="file-plus" class="h-5 w-5" aria-hidden="true"></i>
                        </span>

                        <div>
                            <h2 id="generateReportTitle" class="text-base font-extrabold text-executive-ink">
                                Generate Strategic Report
                            </h2>

                            <p class="mt-1 max-w-sm text-[11px] leading-5 text-executive-muted">
                                Consolidate business KPIs, academic performance, and source freshness into a management snapshot.
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        id="closeGenerateReportIcon"
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-executive-muted transition hover:bg-slate-100 hover:text-executive-ink"
                        aria-label="Close dialog"
                    >
                        <i data-lucide="x" class="h-4 w-4" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <div class="p-6">
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block">
                        <span class="text-[10px] font-extrabold text-executive-muted">
                            Report Type
                        </span>

                        <select
                            id="reportPeriodType"
                            name="period_type"
                            class="report-filter-control mt-2"
                        >
                            <option value="monthly">Monthly Report</option>
                            <option value="quarterly">Quarterly Report</option>
                        </select>
                    </label>

                    <label class="block">
                        <span
                            id="reportPeriodLabel"
                            class="text-[10px] font-extrabold text-executive-muted"
                        >
                            Reporting Month
                        </span>

                        <input
                            name="period"
                            type="month"
                            value="{{ now()->format('Y-m') }}"
                            class="report-filter-control mt-2"
                            required
                        >
                    </label>
                </div>

                <div class="mt-4 rounded-xl border border-violet-100 bg-violet-50/50 p-4">
                    <div class="flex gap-3">
                        <i
                            data-lucide="info"
                            class="mt-0.5 h-4 w-4 shrink-0 text-executive-primary"
                            aria-hidden="true"
                        ></i>

                        <p id="reportPeriodHelp" class="text-[10px] leading-5 text-executive-muted">
                            FlexOps will create or refresh the Draft snapshot for the selected month.
                        </p>
                    </div>
                </div>

                <div
                    id="generateReportStatus"
                    class="mt-4 hidden rounded-xl border border-violet-100 bg-violet-50 p-3 text-xs font-bold text-executive-primary"
                    role="status"
                >
                    FlexOps is consolidating strategic report data. This may take a moment.
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-executive-line bg-slate-50/60 px-6 py-4">
                <button
                    type="button"
                    id="closeGenerateReport"
                    class="rounded-xl border border-executive-line bg-white px-4 py-2.5 text-xs font-bold text-executive-muted transition hover:text-executive-ink"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    id="submitGenerateReport"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-executive-primary px-4 py-2.5 text-xs font-extrabold text-white transition hover:bg-executive-primaryDark disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <i data-lucide="plus" class="h-3.5 w-3.5" aria-hidden="true"></i>

                    <span>Generate Report</span>
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const filters = document.getElementById('reportLibraryFilters');
            const page = document.getElementById('reportLibraryPage');
            const loading = document.getElementById('reportLibraryLoading');
            const dialog = document.getElementById('generateReportDialog');
            const generateForm = document.getElementById('generateReportForm');
            const generateStatus = document.getElementById('generateReportStatus');
            const periodType = document.getElementById('reportPeriodType');
            const periodLabel = document.getElementById('reportPeriodLabel');
            const periodHelp = document.getElementById('reportPeriodHelp');

            let activeController = null;
            let searchTimer = null;

            const showLoading = () => {
                page.setAttribute('aria-busy', 'true');
                loading.classList.remove('hidden');
                loading.classList.add('flex');
            };

            const hideLoading = () => {
                page.setAttribute('aria-busy', 'false');
                loading.classList.add('hidden');
                loading.classList.remove('flex');
            };

            const buildFilterUrl = () => {
                const parameters = new URLSearchParams(new FormData(filters));

                [...parameters.entries()].forEach(([key, value]) => {
                    if (!String(value).trim()) {
                        parameters.delete(key);
                    }
                });

                const query = parameters.toString();

                return query
                    ? `${location.pathname}?${query}`
                    : location.pathname;
            };

            const loadReports = async () => {
                activeController?.abort();

                const controller = new AbortController();
                activeController = controller;
                const url = buildFilterUrl();

                showLoading();

                try {
                    const response = await fetch(url, {
                        signal: controller.signal,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('Unable to load strategic reports.');
                    }

                    const documentResult = new DOMParser().parseFromString(
                        await response.text(),
                        'text/html'
                    );

                    const nextContent = documentResult.getElementById(
                        'reportLibraryContent'
                    );

                    const currentContent = document.getElementById(
                        'reportLibraryContent'
                    );

                    if (!nextContent || !currentContent) {
                        throw new Error('Invalid strategic report response.');
                    }

                    currentContent.replaceWith(nextContent);
                    window.renderLucideIcons?.(nextContent);
                    history.pushState({}, '', url);
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        console.error(error);
                    }
                } finally {
                    if (activeController === controller) {
                        hideLoading();
                    }
                }
            };

            filters.querySelectorAll('select').forEach((select) => {
                select.addEventListener('change', loadReports);
            });

            filters.elements.search.addEventListener('input', () => {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(loadReports, 350);
            });

            const resetGenerateStatus = () => {
                generateStatus.classList.add('hidden');
                generateStatus.classList.remove(
                    'border-rose-100',
                    'bg-rose-50',
                    'text-rose-700'
                );

                generateStatus.classList.add(
                    'border-violet-100',
                    'bg-violet-50',
                    'text-executive-primary'
                );
            };

            const openDialog = () => {
                resetGenerateStatus();
                dialog.classList.remove('hidden');
                dialog.classList.add('flex');
                document.body.classList.add('overflow-hidden');

                setTimeout(() => periodType.focus(), 50);
            };

            const closeDialog = () => {
                if (
                    [...generateForm.elements].some(
                        (element) => element.disabled
                    )
                ) {
                    return;
                }

                dialog.classList.add('hidden');
                dialog.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            };

            document
                .getElementById('openGenerateReport')
                .addEventListener('click', openDialog);

            document
                .getElementById('closeGenerateReport')
                .addEventListener('click', closeDialog);

            document
                .getElementById('closeGenerateReportIcon')
                .addEventListener('click', closeDialog);

            document.addEventListener('click', (event) => {
                if (event.target.closest('[data-open-report-dialog]')) {
                    openDialog();
                }
            });

            dialog.addEventListener('click', (event) => {
                if (event.target === dialog) {
                    closeDialog();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (
                    event.key === 'Escape'
                    && dialog.classList.contains('flex')
                ) {
                    closeDialog();
                }
            });

            periodType.addEventListener('change', () => {
                const isQuarterly = periodType.value === 'quarterly';

                periodLabel.textContent = isQuarterly
                    ? 'Quarter Reference Month'
                    : 'Reporting Month';

                periodHelp.textContent = isQuarterly
                    ? 'The selected month will be used to determine the corresponding quarterly reporting period.'
                    : 'FlexOps will create or refresh the Draft snapshot for the selected month.';
            });

            generateForm.addEventListener('submit', async (event) => {
                event.preventDefault();

                const form = event.currentTarget;
                const controls = form.querySelectorAll(
                    'button, select, input'
                );

                const submitButton = document.getElementById(
                    'submitGenerateReport'
                );

                const submitLabel = submitButton.querySelector('span');

                const csrf =
                    document.querySelector('meta[name="csrf-token"]')?.content
                    || form.elements._token?.value
                    || '';

                const action = new URL(form.action, location.href);

                generateStatus.textContent =
                    'FlexOps is consolidating KPI, academic, and source freshness data. This may take a moment.';

                generateStatus.classList.remove('hidden');
                generateStatus.classList.remove(
                    'border-rose-100',
                    'bg-rose-50',
                    'text-rose-700'
                );

                generateStatus.classList.add(
                    'border-violet-100',
                    'bg-violet-50',
                    'text-executive-primary'
                );

                controls.forEach((control) => {
                    control.disabled = true;
                });

                submitLabel.textContent = 'Generating...';

                try {
                    const response = await fetch(
                        `${action.pathname}${action.search}`,
                        {
                            method: 'POST',
                            body: new FormData(form),
                            credentials: 'same-origin',
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': csrf,
                            },
                        }
                    );

                    const responseText = await response.text();
                    let payload = {};

                    try {
                        payload = JSON.parse(responseText);
                    } catch (error) {
                        payload = {};
                    }

                    if (!response.ok) {
                        throw new Error(
                            payload.message
                            || 'Report generation failed.'
                        );
                    }

                    if (!payload.redirect) {
                        throw new Error(
                            'Report generated, but redirect URL is unavailable.'
                        );
                    }

                    location.href = payload.redirect;
                } catch (error) {
                    generateStatus.textContent = error.message;

                    generateStatus.classList.remove(
                        'border-violet-100',
                        'bg-violet-50',
                        'text-executive-primary'
                    );

                    generateStatus.classList.add(
                        'border-rose-100',
                        'bg-rose-50',
                        'text-rose-700'
                    );

                    controls.forEach((control) => {
                        control.disabled = false;
                    });

                    submitLabel.textContent = 'Generate Report';
                }
            });
        });
    </script>
@endpush
