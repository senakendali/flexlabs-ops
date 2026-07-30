@extends('layouts.executive-center')

@section('title', 'Executive Dashboard')
@section('page_title')
    Executive Dashboard
@endsection
@section('meta_description', 'Real-time business visibility and decision support across FlexLabs.')
@section('page_description', 'Real-time business visibility and decision support across FlexLabs.')

@push('styles')
    <style>
        /*
         * Jadikan header halaman sebagai card mandiri.
         * Rules ini hanya berlaku pada Executive Dashboard ini.
         */
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
            box-shadow: 0 16px 45px rgba(31, 27, 46, 0.07) !important;
        }

        .executive-content {
            padding-top: 0 !important;
            padding-right: 0 !important;
            padding-bottom: 0 !important;
            padding-left: 0 !important;
        }

        /*
         * Gunakan satu sumber jarak antara page header card dan konten.
         * Ini mencegah gap bertambah karena padding layout dan root page
         * diterapkan bersamaan.
         */
        #executiveDashboardRoot {
            margin: 0 !important;
            padding-top: 1.25rem !important;
        }

        @media (max-width: 639px) {
            .executive-page-header {
                border-radius: 1rem !important;
            }

            #executiveDashboardRoot {
                padding-top: 1rem !important;
            }
        }

        @media print {
            .executive-content {
                padding-top: 0 !important;
            }

            #executiveDashboardRoot {
                padding-top: 0 !important;
            }
        }
    </style>
@endpush

@php
    $resolvedPeriod = $period ?? [];
    $resolvedSummary = $summary ?? [];
    $resolvedHighlights = $executiveHighlights ?? [];
    $resolvedBusinessHealth = $businessHealth ?? [];
    $resolvedBrief = $executiveBrief ?? [];
    $resolvedAttention = $businessAttention ?? [];
    $resolvedFreshness = $dataFreshness ?? [];

    $statusStyles = [
        'healthy' => [
            'badge' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'dot' => 'bg-emerald-500',
            'bar' => 'bg-emerald-500',
            'icon' => 'bg-emerald-50 text-emerald-600',
        ],
        'watch' => [
            'badge' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'dot' => 'bg-amber-500',
            'bar' => 'bg-amber-400',
            'icon' => 'bg-amber-50 text-amber-600',
        ],
        'critical' => [
            'badge' => 'bg-rose-50 text-rose-700 ring-rose-200',
            'dot' => 'bg-rose-500',
            'bar' => 'bg-rose-500',
            'icon' => 'bg-rose-50 text-rose-600',
        ],
        'unavailable' => [
            'badge' => 'bg-slate-100 text-slate-600 ring-slate-200',
            'dot' => 'bg-slate-400',
            'bar' => 'bg-slate-400',
            'icon' => 'bg-slate-100 text-slate-500',
        ],
        'no_data' => [
            'badge' => 'bg-sky-50 text-sky-700 ring-sky-200',
            'dot' => 'bg-sky-400',
            'bar' => 'bg-sky-400',
            'icon' => 'bg-sky-50 text-sky-600',
        ],
        'not_configured' => [
            'badge' => 'bg-slate-100 text-slate-600 ring-slate-200',
            'dot' => 'bg-slate-300',
            'bar' => 'bg-slate-300',
            'icon' => 'bg-slate-100 text-slate-500',
        ],
        'pending' => [
            'badge' => 'bg-violet-50 text-violet-700 ring-violet-200',
            'dot' => 'bg-violet-400',
            'bar' => 'bg-violet-400',
            'icon' => 'bg-violet-50 text-violet-600',
        ],
    ];

    $defaultStatusStyle = $statusStyles['not_configured'];

    $highlightIcons = [
        'confirmed_revenue' => '<path d="M4 7.5h16M6 4.5h12a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-11a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.8"/><path d="M16 13.5h.01M8 12h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
        'total_leads' => '<path d="M15 19.5v-1.2a4.3 4.3 0 0 0-4.3-4.3H6.3A4.3 4.3 0 0 0 2 18.3v1.2M8.5 10a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM18 8v6M15 11h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
        'closed_deals' => '<path d="M8 4h8v3a4 4 0 0 1-8 0V4Z" stroke="currentColor" stroke-width="1.8"/><path d="M8 6H4v1a4 4 0 0 0 4 4M16 6h4v1a4 4 0 0 1-4 4M12 11v5M8.5 20h7M9 16h6v4H9z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
        'paid_students' => '<path d="m3 9 9-5 9 5-9 5-9-5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M7 12v4.5c2.7 2 7.3 2 10 0V12M21 9v6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
        'critical_kpis' => '<path d="M10.3 3.4 2.2 17.5A2 2 0 0 0 3.9 20h16.2a2 2 0 0 0 1.7-2.5L13.7 3.4a2 2 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M12 8v5M12 16.5h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
    ];

    $centreIcons = [
        'growth_engine' => '<path d="M4 18 10 12l4 4 6-8M15 8h5v5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
        'learning_centre' => '<path d="m3 9 9-5 9 5-9 5-9-5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M7 12v4.5c2.7 2 7.3 2 10 0V12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'talent_hub' => '<path d="M16 20v-1.5a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4V20M9 10.5a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM22 20v-1.5a4 4 0 0 0-3-3.9M16 2.7a4 4 0 0 1 0 7.7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'finance_centre' => '<path d="M4 7.5h16M6 4.5h12a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-11a2 2 0 0 1 2-2ZM16 13.5h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'operations_centre' => '<path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z" stroke="currentColor" stroke-width="1.8"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06-2.83 2.83-.06-.06A1.7 1.7 0 0 0 15 19.36a1.7 1.7 0 0 0-1 .64 1.7 1.7 0 0 0-.36 1.06V21h-4v-.09A1.7 1.7 0 0 0 8.6 19.4a1.7 1.7 0 0 0-1.87.34l-.06.06-2.83-2.83.06-.06A1.7 1.7 0 0 0 4.24 15a1.7 1.7 0 0 0-1.24-1H3v-4h.09A1.7 1.7 0 0 0 4.6 8.96a1.7 1.7 0 0 0-.34-1.87l-.06-.06L7.03 4.2l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.64A1.7 1.7 0 0 0 10.36 3H14v.09A1.7 1.7 0 0 0 15.04 4.6a1.7 1.7 0 0 0 1.87-.34l.06-.06 2.83 2.83-.06.06A1.7 1.7 0 0 0 19.4 9c.18.47.55.84 1.04 1H21v4h-.09A1.7 1.7 0 0 0 19.4 15Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',
    ];
@endphp

@section('header_actions')
    <div class="flex min-w-0 flex-col gap-3 xl:items-end">
        <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
            <div class="min-w-0">
                <p
                    id="executivePeriodLabel"
                    class="text-sm font-extrabold text-executive-ink"
                >
                    {{ $resolvedPeriod['label'] ?? 'Current Period' }}
                </p>

                <p
                    id="executivePeriodContext"
                    class="mt-0.5 text-[10px] font-semibold text-executive-muted"
                >
                    {{ $resolvedPeriod['actual_label'] ?? 'Periode data belum tersedia' }}
                    @if (isset($resolvedPeriod['elapsed_percentage']) && ($resolvedPeriod['is_current'] ?? false))
                        · {{ number_format((float) $resolvedPeriod['elapsed_percentage'], 1, ',', '.') }}% bulan berjalan
                    @endif
                </p>
            </div>

            <div id="executiveSummaryBadges" class="flex flex-wrap items-center gap-2">
                <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-[10px] font-bold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                    {{ (int) ($resolvedSummary['healthy_kpis'] ?? 0) }} healthy
                </span>

                <span class="rounded-full bg-rose-50 px-3 py-1.5 text-[10px] font-bold text-rose-700 ring-1 ring-inset ring-rose-200">
                    {{ (int) ($resolvedSummary['critical_kpis'] ?? 0) }} critical
                </span>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <div
                id="executiveLiveBadge"
                class="hidden items-center gap-2 rounded-full bg-emerald-50 px-3 py-2 text-[11px] font-bold text-emerald-700 ring-1 ring-inset ring-emerald-200 sm:flex"
            >
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                <span id="executiveLiveBadgeText">
                    {{ ($resolvedPeriod['is_future'] ?? false) ? 'Future period' : (($resolvedPeriod['is_current'] ?? false) ? 'Live data' : 'Closed period') }}
                </span>
            </div>

            <label class="relative block">
                <span class="sr-only">Pilih periode bulan</span>

                <input
                    id="executiveMonthFilter"
                    type="month"
                    value="{{ $filters['month'] ?? now()->format('Y-m') }}"
                    class="h-10 min-w-[152px] rounded-xl border border-executive-line bg-white px-3 pr-9 text-xs font-bold text-executive-ink outline-none transition focus:border-executive-primary focus:ring-4 focus:ring-executive-primary/10 disabled:cursor-wait disabled:opacity-60"
                    autocomplete="off"
                >

                <svg
                    class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-executive-muted"
                    viewBox="0 0 24 24"
                    fill="none"
                    aria-hidden="true"
                >
                    <path d="M7 3v3M17 3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
            </label>
        </div>
    </div>
@endsection

@section('content')
    <div
        id="executiveDashboardRoot"
        class="relative"
        data-url="{{ route('executive-center.dashboard.data') }}"
    >
        <div
            id="executiveDashboardLoading"
            class="pointer-events-none fixed inset-0 z-[70] hidden items-center justify-center bg-slate-950/10 backdrop-blur-[1px]"
            aria-hidden="true"
        >
            <div class="rounded-2xl bg-white px-7 py-5 text-center shadow-panel ring-1 ring-black/5">
                <svg class="h-5 w-5 animate-spin text-executive-primary" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" class="opacity-20"></circle>
                    <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                </svg>
                <p id="executiveLoadingTitle" class="mt-3 text-sm font-bold text-executive-ink">Updating executive data...</p>
                <p class="mt-1 text-xs font-medium text-executive-muted">FlexOps is consolidating the latest KPI and business performance data.</p>
            </div>
        </div>

        <div
            id="executiveToast"
            class="fixed right-4 top-4 z-[80] hidden max-w-sm rounded-2xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white shadow-2xl lg:right-8"
            role="status"
            aria-live="polite"
        ></div>

        <section
            id="executiveHighlights"
            class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5"
            aria-label="Executive KPI highlights"
        >
            @forelse ($resolvedHighlights as $highlight)
                @php
                    $highlightStatus = $highlight['status'] ?? 'not_configured';
                    $highlightStyle = $statusStyles[$highlightStatus] ?? $defaultStatusStyle;
                    $trend = $highlight['trend'] ?? [];
                @endphp

                <article class="relative overflow-hidden rounded-2xl border border-executive-line bg-white p-4 shadow-panel">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-[11px] font-semibold text-executive-muted">
                                {{ $highlight['label'] ?? 'KPI' }}
                            </p>

                            <p class="mt-2 truncate text-2xl font-extrabold tracking-[-0.04em] text-executive-ink">
                                {{ $highlight['value_formatted'] ?? '—' }}
                            </p>
                        </div>

                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $highlightStyle['icon'] }}">
                            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                {!! $highlightIcons[$highlight['code'] ?? ''] ?? $highlightIcons['critical_kpis'] !!}
                            </svg>
                        </span>
                    </div>

                    <div class="mt-3 flex min-h-[20px] items-center gap-1.5 text-[10px] font-bold">
                        @if (($trend['available'] ?? false) && ($trend['is_new'] ?? false))
                            <span class="text-emerald-600">New vs last month</span>
                        @elseif (($trend['available'] ?? false) && isset($trend['change_percentage']))
                            <span class="{{ ($trend['is_positive'] ?? false) ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ (float) $trend['change_percentage'] > 0 ? '+' : '' }}{{ number_format((float) $trend['change_percentage'], 1, ',', '.') }}%
                            </span>
                            <span class="font-medium text-executive-muted">vs last month</span>
                        @else
                            <span class="{{ $highlightStyle['badge'] }} rounded-full px-2 py-1 ring-1 ring-inset">
                                {{ $highlight['status_label'] ?? 'Not configured' }}
                            </span>
                        @endif
                    </div>
                </article>
            @empty
                <div class="col-span-full rounded-2xl border border-dashed border-executive-line bg-white px-5 py-8 text-center">
                    <p class="text-sm font-bold text-executive-ink">Executive KPI belum tersedia</p>
                    <p class="mt-1 text-xs text-executive-muted">Aktifkan KPI definitions dan Monthly Targets untuk mulai monitoring.</p>
                </div>
            @endforelse
        </section>

        <section class="mt-5 grid grid-cols-1 gap-5 xl:grid-cols-3">
            <article class="rounded-2xl border border-executive-line bg-white p-5 shadow-panel xl:col-span-2">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-sm font-extrabold text-executive-ink">
                            Business Health Overview
                        </h3>
                        <p class="mt-1 text-[11px] font-medium text-executive-muted">
                            Cross-centre KPI health based on target pace.
                        </p>
                    </div>

                    <div class="flex items-center gap-3 text-[10px] font-semibold text-executive-muted">
                        <span class="flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            Healthy
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full bg-amber-400"></span>
                            Watch
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full bg-rose-500"></span>
                            Critical
                        </span>
                    </div>
                </div>

                <div id="executiveBusinessHealth" class="mt-5 space-y-4">
                    @forelse ($resolvedBusinessHealth as $centre)
                        @continue(($centre['key'] ?? null) === 'talent_hub')
                        @php
                            $centreStatus = $centre['status'] ?? 'not_configured';
                            $centreStyle = $statusStyles[$centreStatus] ?? $defaultStatusStyle;
                            $healthPercentage = $centre['health_percentage'] ?? null;
                        @endphp

                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-[160px_minmax(0,1fr)_52px_110px] sm:items-center sm:gap-3">
                            <div class="flex min-w-0 items-center gap-2.5">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $centreStyle['icon'] }}">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        {!! $centreIcons[$centre['key'] ?? ''] ?? $centreIcons['operations_centre'] !!}
                                    </svg>
                                </span>

                                <span class="truncate text-xs font-bold text-executive-ink">
                                    {{ $centre['name'] ?? 'Centre' }}
                                </span>
                            </div>

                            <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                <div
                                    class="h-full rounded-full {{ $centreStyle['bar'] }}"
                                    style="width: {{ $healthPercentage !== null ? max(0, min(100, (float) $healthPercentage)) : 0 }}%"
                                ></div>
                            </div>

                            <p class="text-xs font-extrabold text-executive-ink sm:text-right">
                                {{ $healthPercentage !== null ? number_format((float) $healthPercentage, 0, ',', '.') . '%' : '—' }}
                            </p>

                            <div class="sm:text-right">
                                <span class="inline-flex rounded-full px-2 py-1 text-[9px] font-extrabold ring-1 ring-inset {{ $centreStyle['badge'] }}">
                                    {{ $centre['status_label'] ?? 'Not configured' }}
                                </span>
                            </div>

                            @if (!empty($centre['message']))
                                <p class="text-[10px] leading-4 text-executive-muted sm:col-start-2 sm:col-span-3">
                                    {{ $centre['message'] }}
                                </p>
                            @endif
                        </div>
                    @empty
                        <div class="rounded-xl bg-slate-50 px-4 py-6 text-center text-xs font-semibold text-executive-muted">
                            Business Health belum dapat dihitung.
                        </div>
                    @endforelse
                </div>
            </article>

            <article class="rounded-2xl border border-executive-line bg-white p-5 shadow-panel">
                <div>
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-extrabold text-executive-primary">
                                AI Executive Brief
                            </p>
                            <p class="mt-1 text-[10px] font-semibold text-executive-muted">
                                Decision-support summary
                            </p>
                        </div>

                        <span
                            id="executiveBriefType"
                            class="rounded-full bg-violet-50 px-2 py-1 text-[9px] font-extrabold uppercase tracking-[0.08em] text-executive-primary ring-1 ring-inset ring-violet-200"
                        >
                            {{ ($resolvedBrief['is_ai_generated'] ?? false) ? 'AI generated' : 'Local fallback' }}
                        </span>
                    </div>

                    <div id="executiveBriefContent" class="mt-5">
                        <h4 class="text-lg font-extrabold leading-6 tracking-[-0.025em] text-executive-ink">
                            {{ $resolvedBrief['headline'] ?? 'Executive brief belum tersedia' }}
                        </h4>

                        <p class="mt-3 text-xs leading-5 text-slate-600">
                            {{ $resolvedBrief['summary'] ?? 'Data KPI belum cukup untuk membuat executive brief.' }}
                        </p>

                        @if (!empty($resolvedBrief['root_causes']))
                            <div class="mt-4">
                                <p class="text-[9px] font-extrabold uppercase tracking-[0.12em] text-executive-primary">
                                    Kemungkinan penyebab utama
                                </p>
                                <ul class="mt-2 space-y-1.5 text-[11px] font-medium leading-5 text-slate-600">
                                    @foreach ($resolvedBrief['root_causes'] as $cause)
                                        <li class="flex gap-2">
                                            <span class="mt-2 h-1 w-1 shrink-0 rounded-full bg-[#5B3E8E]"></span>
                                            <span>{{ is_array($cause) ? ($cause['evidence'] ?? $cause['title'] ?? '') : $cause }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if (!empty($resolvedBrief['recommendations']))
                            <div class="mt-5 rounded-xl bg-violet-50/70 p-3.5 ring-1 ring-inset ring-violet-100">
                                <p class="text-[9px] font-extrabold uppercase tracking-[0.12em] text-executive-primary">
                                    Recommended actions
                                </p>
                                <ul class="mt-2 space-y-1.5 text-[11px] font-semibold leading-5 text-executive-ink">
                                    @foreach ($resolvedBrief['recommendations'] as $recommendation)
                                        <li class="flex gap-2">
                                            <span class="text-executive-primary">{{ $loop->iteration }}.</span>
                                            <span>{{ $recommendation }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if (!empty($resolvedBrief['priority']))
                            <div class="mt-4 flex items-center gap-2 text-[10px] font-bold text-executive-muted">
                                <span>Prioritas tindakan</span>
                                <span class="rounded-full bg-violet-50 px-2.5 py-1 text-executive-primary ring-1 ring-inset ring-violet-200">
                                    {{ $resolvedBrief['priority'] }}
                                </span>
                            </div>
                        @endif

                        <a
                            id="executiveBriefDetailLink"
                            href="{{ route('executive-center.ai-executive-brief', ['period' => $filters['month'] ?? now()->format('Y-m')]) }}"
                            class="mt-5 inline-flex items-center gap-2 text-[11px] font-extrabold text-executive-primary hover:underline"
                        >
                            View Full Brief <span aria-hidden="true">→</span>
                        </a>
                    </div>
                </div>
            </article>
        </section>

        <section class="mt-5 overflow-hidden rounded-2xl border border-executive-line bg-white shadow-panel">
            <div class="flex flex-col gap-2 border-b border-executive-line px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-sm font-extrabold text-executive-ink">
                        Business Attention
                    </h3>
                    <p class="mt-1 text-[11px] font-medium text-executive-muted">
                        Priority issues requiring management action.
                    </p>
                </div>

                <span
                    id="executiveAttentionCount"
                    class="w-fit rounded-full bg-executive-primarySoft px-3 py-1.5 text-[10px] font-extrabold text-executive-primary"
                >
                    {{ count($resolvedAttention) }} item{{ count($resolvedAttention) === 1 ? '' : 's' }}
                </span>
            </div>

            <div class="executive-scrollbar overflow-x-auto">
                <table class="min-w-[880px] w-full border-collapse text-left">
                    <thead>
                        <tr class="bg-slate-50/80 text-[9px] font-extrabold uppercase tracking-[0.1em] text-executive-muted">
                            <th class="px-5 py-3">Priority</th>
                            <th class="px-5 py-3">Issue</th>
                            <th class="px-5 py-3">Actual vs Target</th>
                            <th class="px-5 py-3">Centre</th>
                            <th class="px-5 py-3">Recommended Action</th>
                        </tr>
                    </thead>

                    <tbody id="executiveBusinessAttention" class="divide-y divide-executive-line">
                        @forelse ($resolvedAttention as $attention)
                            @php
                                $attentionStatus = $attention['severity'] ?? 'not_configured';
                                $attentionStyle = $statusStyles[$attentionStatus] ?? $defaultStatusStyle;
                            @endphp

                            <tr class="align-top transition hover:bg-slate-50/70">
                                <td class="whitespace-nowrap px-5 py-4">
                                    <span class="inline-flex rounded-full px-2 py-1 text-[9px] font-extrabold ring-1 ring-inset {{ $attentionStyle['badge'] }}">
                                        {{ $attention['severity_label'] ?? 'Attention' }}
                                    </span>
                                </td>

                                <td class="max-w-[230px] px-5 py-4">
                                    <p class="text-xs font-bold text-executive-ink">
                                        {{ $attention['title'] ?? 'KPI issue' }}
                                    </p>
                                    <p class="mt-1 text-[10px] leading-4 text-executive-muted">
                                        {{ $attention['message'] ?? 'KPI membutuhkan perhatian.' }}
                                    </p>
                                    @if (!empty($attention['source_message']))
                                        <p class="mt-1 text-[9px] leading-4 text-slate-400">
                                            {{ $attention['source_message'] }}
                                        </p>
                                    @endif
                                </td>

                                <td class="whitespace-nowrap px-5 py-4">
                                    <p class="text-xs font-extrabold text-executive-ink">
                                        {{ $attention['actual_formatted'] ?? '—' }}
                                    </p>
                                    <p class="mt-1 text-[10px] text-executive-muted">
                                        Target {{ $attention['target_formatted'] ?? '—' }}
                                    </p>
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-xs font-semibold text-executive-ink">
                                    {{ $attention['centre'] ?? 'Executive Center' }}
                                </td>

                                <td class="max-w-[280px] px-5 py-4 text-[10px] font-medium leading-4 text-slate-600">
                                    {{ $attention['recommended_action'] ?? 'Review KPI dan tetapkan tindak lanjut.' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center">
                                    <span class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="m5 12 4 4L19 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </span>
                                    <p class="mt-3 text-xs font-bold text-executive-ink">Tidak ada isu prioritas</p>
                                    <p class="mt-1 text-[10px] text-executive-muted">Semua KPI terukur berada dalam kondisi yang tidak memerlukan attention.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="mt-5 rounded-2xl border border-executive-line bg-white p-5 shadow-panel">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-sm font-extrabold text-executive-ink">
                        Data Freshness
                    </h3>
                    <p class="mt-1 text-[11px] font-medium text-executive-muted">
                        Latest recorded data used by this dashboard.
                    </p>
                </div>

                <p class="text-[10px] font-semibold text-executive-muted">
                    Dashboard generated
                    <span id="executiveGeneratedAt" class="font-bold text-executive-ink">
                        {{ !empty($resolvedBrief['generated_at']) ? \Carbon\Carbon::parse($resolvedBrief['generated_at'])->translatedFormat('d M Y, H:i') : '—' }}
                    </span>
                </p>
            </div>

            <div id="executiveDataFreshness" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
                @forelse ($resolvedFreshness as $freshness)
                    <article class="rounded-xl border border-executive-line bg-slate-50/60 px-4 py-3">
                        <div class="flex items-center justify-between gap-3">
                            <p class="truncate text-[11px] font-bold text-executive-ink">
                                {{ $freshness['source_label'] ?? 'Data Source' }}
                            </p>

                            <span class="h-2 w-2 shrink-0 rounded-full {{ ($freshness['is_available'] ?? false) ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
                        </div>

                        <p class="mt-2 text-[10px] font-medium text-executive-muted">
                            @if (!empty($freshness['last_recorded_at']))
                                {{ \Carbon\Carbon::parse($freshness['last_recorded_at'])->translatedFormat('d M Y, H:i') }}
                            @elseif ($freshness['is_available'] ?? false)
                                Available · no timestamp
                            @else
                                Source unavailable
                            @endif
                        </p>
                    </article>
                @empty
                    <div class="col-span-full rounded-xl bg-slate-50 px-4 py-6 text-center text-xs font-semibold text-executive-muted">
                        Informasi pembaruan sumber data belum tersedia.
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const root = document.getElementById('executiveDashboardRoot');
            const monthFilter = document.getElementById('executiveMonthFilter');
            const loading = document.getElementById('executiveDashboardLoading');
            const toast = document.getElementById('executiveToast');

            if (!root || !monthFilter) {
                return;
            }

            const statusStyles = {
                healthy: {
                    badge: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                    dot: 'bg-emerald-500',
                    bar: 'bg-emerald-500',
                    icon: 'bg-emerald-50 text-emerald-600',
                },
                watch: {
                    badge: 'bg-amber-50 text-amber-700 ring-amber-200',
                    dot: 'bg-amber-500',
                    bar: 'bg-amber-400',
                    icon: 'bg-amber-50 text-amber-600',
                },
                critical: {
                    badge: 'bg-rose-50 text-rose-700 ring-rose-200',
                    dot: 'bg-rose-500',
                    bar: 'bg-rose-500',
                    icon: 'bg-rose-50 text-rose-600',
                },
                unavailable: {
                    badge: 'bg-slate-100 text-slate-600 ring-slate-200',
                    dot: 'bg-slate-400',
                    bar: 'bg-slate-400',
                    icon: 'bg-slate-100 text-slate-500',
                },
                no_data: {
                    badge: 'bg-sky-50 text-sky-700 ring-sky-200',
                    dot: 'bg-sky-400',
                    bar: 'bg-sky-400',
                    icon: 'bg-sky-50 text-sky-600',
                },
                not_configured: {
                    badge: 'bg-slate-100 text-slate-600 ring-slate-200',
                    dot: 'bg-slate-300',
                    bar: 'bg-slate-300',
                    icon: 'bg-slate-100 text-slate-500',
                },
                pending: {
                    badge: 'bg-violet-50 text-violet-700 ring-violet-200',
                    dot: 'bg-violet-400',
                    bar: 'bg-violet-400',
                    icon: 'bg-violet-50 text-violet-600',
                },
            };

            const highlightIcons = {
                confirmed_revenue: '<path d="M4 7.5h16M6 4.5h12a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-11a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.8"/><path d="M16 13.5h.01M8 12h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
                total_leads: '<path d="M15 19.5v-1.2a4.3 4.3 0 0 0-4.3-4.3H6.3A4.3 4.3 0 0 0 2 18.3v1.2M8.5 10a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM18 8v6M15 11h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
                closed_deals: '<path d="M8 4h8v3a4 4 0 0 1-8 0V4Z" stroke="currentColor" stroke-width="1.8"/><path d="M8 6H4v1a4 4 0 0 0 4 4M16 6h4v1a4 4 0 0 1-4 4M12 11v5M8.5 20h7M9 16h6v4H9z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
                paid_students: '<path d="m3 9 9-5 9 5-9 5-9-5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M7 12v4.5c2.7 2 7.3 2 10 0V12M21 9v6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
                critical_kpis: '<path d="M10.3 3.4 2.2 17.5A2 2 0 0 0 3.9 20h16.2a2 2 0 0 0 1.7-2.5L13.7 3.4a2 2 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M12 8v5M12 16.5h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>',
            };

            const centreIcons = {
                growth_engine: '<path d="M4 18 10 12l4 4 6-8M15 8h5v5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
                learning_centre: '<path d="m3 9 9-5 9 5-9 5-9-5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M7 12v4.5c2.7 2 7.3 2 10 0V12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
                talent_hub: '<path d="M16 20v-1.5a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4V20M9 10.5a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM22 20v-1.5a4 4 0 0 0-3-3.9M16 2.7a4 4 0 0 1 0 7.7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
                finance_centre: '<path d="M4 7.5h16M6 4.5h12a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-11a2 2 0 0 1 2-2ZM16 13.5h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
                operations_centre: '<path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z" stroke="currentColor" stroke-width="1.8"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06-2.83 2.83-.06-.06A1.7 1.7 0 0 0 15 19.36a1.7 1.7 0 0 0-1 .64 1.7 1.7 0 0 0-.36 1.06V21h-4v-.09A1.7 1.7 0 0 0 8.6 19.4a1.7 1.7 0 0 0-1.87.34l-.06.06-2.83-2.83.06-.06A1.7 1.7 0 0 0 4.24 15a1.7 1.7 0 0 0-1.24-1H3v-4h.09A1.7 1.7 0 0 0 4.6 8.96a1.7 1.7 0 0 0-.34-1.87l-.06-.06L7.03 4.2l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.64A1.7 1.7 0 0 0 10.36 3H14v.09A1.7 1.7 0 0 0 15.04 4.6a1.7 1.7 0 0 0 1.87-.34l.06-.06 2.83 2.83-.06.06A1.7 1.7 0 0 0 19.4 9c.18.47.55.84 1.04 1H21v4h-.09A1.7 1.7 0 0 0 19.4 15Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',
            };
            const hiddenBusinessHealthKeys = new Set(['talent_hub']);

            let toastTimer = null;
            let requestController = null;

            function styleFor(status) {
                return statusStyles[status] || statusStyles.not_configured;
            }

            function escapeHtml(value) {
                return String(value ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }

            function clampPercentage(value) {
                const number = Number(value);

                if (!Number.isFinite(number)) {
                    return 0;
                }

                return Math.max(0, Math.min(100, number));
            }

            function formatNumber(value, decimals = 1) {
                const number = Number(value);

                if (!Number.isFinite(number)) {
                    return '0';
                }

                return new Intl.NumberFormat('id-ID', {
                    minimumFractionDigits: decimals,
                    maximumFractionDigits: decimals,
                }).format(number);
            }

            function formatDateTime(value) {
                if (!value) {
                    return null;
                }

                const date = new Date(value);

                if (Number.isNaN(date.getTime())) {
                    return null;
                }

                return new Intl.DateTimeFormat('id-ID', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                }).format(date);
            }

            function showToast(message, type = 'error') {
                if (!toast) {
                    return;
                }

                window.clearTimeout(toastTimer);
                toast.textContent = message;
                toast.className = [
                    'fixed right-4 top-4 z-[80] max-w-sm rounded-2xl px-4 py-3 text-sm font-semibold text-white shadow-2xl lg:right-8',
                    type === 'success' ? 'bg-emerald-600' : 'bg-rose-600',
                ].join(' ');

                toastTimer = window.setTimeout(function () {
                    toast.classList.add('hidden');
                }, 4200);
            }

            function setLoading(isLoading) {
                monthFilter.disabled = isLoading;
                root.setAttribute('aria-busy', isLoading ? 'true' : 'false');

                if (loading) {
                    loading.classList.toggle('hidden', !isLoading);
                    loading.classList.toggle('flex', isLoading);
                    loading.setAttribute('aria-hidden', isLoading ? 'false' : 'true');
                }
            }

            function renderPeriod(data) {
                const period = data.period || {};
                const summary = data.summary || {};
                const label = document.getElementById('executivePeriodLabel');
                const context = document.getElementById('executivePeriodContext');
                const liveBadge = document.getElementById('executiveLiveBadge');
                const liveBadgeText = document.getElementById('executiveLiveBadgeText');

                if (label) {
                    label.textContent = period.label || 'Selected Period';
                }

                if (context) {
                    const progress = period.is_current && period.elapsed_percentage !== null
                        ? ` · ${formatNumber(period.elapsed_percentage, 1)}% bulan berjalan`
                        : '';

                    context.textContent = `${period.actual_label || 'Periode data belum tersedia'}${progress}`;
                }

                const summaryContainer = document.getElementById('executiveSummaryBadges');

                if (summaryContainer) {
                    summaryContainer.innerHTML = `
                        <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-[10px] font-bold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                            ${escapeHtml(summary.healthy_kpis || 0)} healthy
                        </span>
                        <span class="rounded-full bg-rose-50 px-3 py-1.5 text-[10px] font-bold text-rose-700 ring-1 ring-inset ring-rose-200">
                            ${escapeHtml(summary.critical_kpis || 0)} critical
                        </span>
                    `;
                }

                if (liveBadge && liveBadgeText) {
                    const badgeState = period.is_future
                        ? {
                            text: 'Future period',
                            classes: 'bg-violet-50 text-violet-700 ring-violet-200',
                            dot: 'bg-violet-400',
                        }
                        : period.is_current
                            ? {
                                text: 'Live data',
                                classes: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                dot: 'bg-emerald-500',
                            }
                            : {
                                text: 'Closed period',
                                classes: 'bg-slate-100 text-slate-600 ring-slate-200',
                                dot: 'bg-slate-400',
                            };

                    liveBadge.className = `hidden items-center gap-2 rounded-full px-3 py-2 text-[11px] font-bold ring-1 ring-inset sm:flex ${badgeState.classes}`;
                    liveBadge.querySelector('span:first-child').className = `h-1.5 w-1.5 rounded-full ${badgeState.dot}`;
                    liveBadgeText.textContent = badgeState.text;
                }
            }

            function renderHighlights(items) {
                const container = document.getElementById('executiveHighlights');

                if (!container) {
                    return;
                }

                if (!Array.isArray(items) || items.length === 0) {
                    container.innerHTML = `
                        <div class="col-span-full rounded-2xl border border-dashed border-executive-line bg-white px-5 py-8 text-center">
                            <p class="text-sm font-bold text-executive-ink">Executive KPI belum tersedia</p>
                            <p class="mt-1 text-xs text-executive-muted">Aktifkan KPI definitions dan Monthly Targets untuk mulai monitoring.</p>
                        </div>
                    `;
                    return;
                }

                container.innerHTML = items.map(function (item) {
                    const status = item.status || 'not_configured';
                    const style = styleFor(status);
                    const trend = item.trend || {};
                    let trendHtml = `
                        <span class="${style.badge} rounded-full px-2 py-1 ring-1 ring-inset">
                            ${escapeHtml(item.status_label || 'Not configured')}
                        </span>
                    `;

                    if (trend.available && trend.is_new) {
                        trendHtml = '<span class="text-emerald-600">New vs last month</span>';
                    } else if (trend.available && trend.change_percentage !== null && trend.change_percentage !== undefined) {
                        const change = Number(trend.change_percentage);
                        const sign = change > 0 ? '+' : '';
                        const trendColor = trend.is_positive ? 'text-emerald-600' : 'text-rose-600';
                        trendHtml = `
                            <span class="${trendColor}">${sign}${formatNumber(change, 1)}%</span>
                            <span class="font-medium text-executive-muted">vs last month</span>
                        `;
                    }

                    return `
                        <article class="relative overflow-hidden rounded-2xl border border-executive-line bg-white p-4 shadow-panel">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-[11px] font-semibold text-executive-muted">
                                        ${escapeHtml(item.label || 'KPI')}
                                    </p>
                                    <p class="mt-2 truncate text-2xl font-extrabold tracking-[-0.04em] text-executive-ink">
                                        ${escapeHtml(item.value_formatted || '—')}
                                    </p>
                                </div>
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl ${style.icon}">
                                    <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        ${highlightIcons[item.code] || highlightIcons.critical_kpis}
                                    </svg>
                                </span>
                            </div>
                            <div class="mt-3 flex min-h-[20px] items-center gap-1.5 text-[10px] font-bold">
                                ${trendHtml}
                            </div>
                        </article>
                    `;
                }).join('');
            }

            function renderBusinessHealth(items) {
                const container = document.getElementById('executiveBusinessHealth');

                if (!container) {
                    return;
                }

                const visibleItems = Array.isArray(items)
                    ? items.filter((centre) => !hiddenBusinessHealthKeys.has(centre.key))
                    : [];

                if (visibleItems.length === 0) {
                    container.innerHTML = `
                        <div class="rounded-xl bg-slate-50 px-4 py-6 text-center text-xs font-semibold text-executive-muted">
                            Business Health belum dapat dihitung.
                        </div>
                    `;
                    return;
                }

                container.innerHTML = visibleItems.map(function (centre) {
                    const style = styleFor(centre.status || 'not_configured');
                    const hasHealth = centre.health_percentage !== null && centre.health_percentage !== undefined;
                    const percentage = hasHealth ? clampPercentage(centre.health_percentage) : 0;
                    const healthLabel = hasHealth ? `${formatNumber(centre.health_percentage, 0)}%` : '—';
                    const message = centre.message
                        ? `<p class="text-[10px] leading-4 text-executive-muted sm:col-start-2 sm:col-span-3">${escapeHtml(centre.message)}</p>`
                        : '';

                    return `
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-[160px_minmax(0,1fr)_52px_110px] sm:items-center sm:gap-3">
                            <div class="flex min-w-0 items-center gap-2.5">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg ${style.icon}">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        ${centreIcons[centre.key] || centreIcons.operations_centre}
                                    </svg>
                                </span>
                                <span class="truncate text-xs font-bold text-executive-ink">${escapeHtml(centre.name || 'Centre')}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full ${style.bar}" style="width: ${percentage}%"></div>
                            </div>
                            <p class="text-xs font-extrabold text-executive-ink sm:text-right">${healthLabel}</p>
                            <div class="sm:text-right">
                                <span class="inline-flex rounded-full px-2 py-1 text-[9px] font-extrabold ring-1 ring-inset ${style.badge}">
                                    ${escapeHtml(centre.status_label || 'Not configured')}
                                </span>
                            </div>
                            ${message}
                        </div>
                    `;
                }).join('');
            }

            function renderBrief(brief) {
                const container = document.getElementById('executiveBriefContent');
                const typeBadge = document.getElementById('executiveBriefType');

                if (!container) {
                    return;
                }

                const recommendations = Array.isArray(brief.recommendations)
                    ? brief.recommendations
                    : [];
                const causes = Array.isArray(brief.root_causes)
                    ? brief.root_causes.map((cause) => typeof cause === 'object'
                        ? (cause.evidence || cause.title || '')
                        : cause)
                    : [];
                const causesHtml = causes.length > 0
                    ? `
                        <div class="mt-4">
                            <p class="text-[9px] font-extrabold uppercase tracking-[0.12em] text-executive-primary">Kemungkinan penyebab utama</p>
                            <ul class="mt-2 space-y-1.5 text-[11px] font-medium leading-5 text-slate-600">
                                ${causes.map((cause) => `
                                    <li class="flex gap-2">
                                        <span class="mt-2 h-1 w-1 shrink-0 rounded-full bg-[#5B3E8E]"></span>
                                        <span>${escapeHtml(cause)}</span>
                                    </li>
                                `).join('')}
                            </ul>
                        </div>
                    `
                    : '';
                const recommendationHtml = recommendations.length > 0
                    ? `
                        <div class="mt-5 rounded-xl bg-violet-50/70 p-3.5 ring-1 ring-inset ring-violet-100">
                            <p class="text-[9px] font-extrabold uppercase tracking-[0.12em] text-executive-primary">Recommended actions</p>
                            <ul class="mt-2 space-y-1.5 text-[11px] font-semibold leading-5 text-executive-ink">
                                ${recommendations.map((recommendation, index) => `
                                    <li class="flex gap-2">
                                        <span class="text-executive-primary">${index + 1}.</span>
                                        <span>${escapeHtml(recommendation)}</span>
                                    </li>
                                `).join('')}
                            </ul>
                        </div>
                    `
                    : '';
                const priorityHtml = brief.priority
                    ? `
                        <div class="mt-4 flex items-center gap-2 text-[10px] font-bold text-executive-muted">
                            <span>Prioritas tindakan</span>
                            <span class="rounded-full bg-violet-50 px-2.5 py-1 text-executive-primary ring-1 ring-inset ring-violet-200">${escapeHtml(brief.priority)}</span>
                        </div>
                    `
                    : '';

                container.innerHTML = `
                    <h4 class="text-lg font-extrabold leading-6 tracking-[-0.025em] text-executive-ink">
                        ${escapeHtml(brief.headline || 'Executive brief belum tersedia')}
                    </h4>
                    <p class="mt-3 text-xs leading-5 text-slate-600">
                        ${escapeHtml(brief.summary || 'Data KPI belum cukup untuk membuat executive brief.')}
                    </p>
                    ${causesHtml}
                    ${recommendationHtml}
                    ${priorityHtml}
                `;

                if (typeBadge) {
                    typeBadge.textContent = brief.is_ai_generated ? 'AI generated' : 'Local fallback';
                }
            }

            function renderAttention(items) {
                const container = document.getElementById('executiveBusinessAttention');
                const counter = document.getElementById('executiveAttentionCount');

                if (!container) {
                    return;
                }

                const rows = Array.isArray(items) ? items : [];

                if (counter) {
                    counter.textContent = `${rows.length} item${rows.length === 1 ? '' : 's'}`;
                }

                if (rows.length === 0) {
                    container.innerHTML = `
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center">
                                <span class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="m5 12 4 4L19 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                                <p class="mt-3 text-xs font-bold text-executive-ink">Tidak ada isu prioritas</p>
                                <p class="mt-1 text-[10px] text-executive-muted">Semua KPI terukur berada dalam kondisi yang tidak memerlukan attention.</p>
                            </td>
                        </tr>
                    `;
                    return;
                }

                container.innerHTML = rows.map(function (item) {
                    const style = styleFor(item.severity || 'not_configured');
                    const sourceMessage = item.source_message
                        ? `<p class="mt-1 text-[9px] leading-4 text-slate-400">${escapeHtml(item.source_message)}</p>`
                        : '';

                    return `
                        <tr class="align-top transition hover:bg-slate-50/70">
                            <td class="whitespace-nowrap px-5 py-4">
                                <span class="inline-flex rounded-full px-2 py-1 text-[9px] font-extrabold ring-1 ring-inset ${style.badge}">
                                    ${escapeHtml(item.severity_label || 'Attention')}
                                </span>
                            </td>
                            <td class="max-w-[230px] px-5 py-4">
                                <p class="text-xs font-bold text-executive-ink">${escapeHtml(item.title || 'KPI issue')}</p>
                                <p class="mt-1 text-[10px] leading-4 text-executive-muted">${escapeHtml(item.message || 'KPI membutuhkan perhatian.')}</p>
                                ${sourceMessage}
                            </td>
                            <td class="whitespace-nowrap px-5 py-4">
                                <p class="text-xs font-extrabold text-executive-ink">${escapeHtml(item.actual_formatted || '—')}</p>
                                <p class="mt-1 text-[10px] text-executive-muted">Target ${escapeHtml(item.target_formatted || '—')}</p>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-xs font-semibold text-executive-ink">${escapeHtml(item.centre || 'Executive Center')}</td>
                            <td class="max-w-[280px] px-5 py-4 text-[10px] font-medium leading-4 text-slate-600">
                                ${escapeHtml(item.recommended_action || 'Review KPI dan tetapkan tindak lanjut.')}
                            </td>
                        </tr>
                    `;
                }).join('');
            }

            function renderFreshness(items, generatedAt) {
                const container = document.getElementById('executiveDataFreshness');
                const generated = document.getElementById('executiveGeneratedAt');

                if (generated) {
                    generated.textContent = formatDateTime(generatedAt) || '—';
                }

                if (!container) {
                    return;
                }

                const rows = Array.isArray(items) ? items : [];

                if (rows.length === 0) {
                    container.innerHTML = `
                        <div class="col-span-full rounded-xl bg-slate-50 px-4 py-6 text-center text-xs font-semibold text-executive-muted">
                            Informasi pembaruan sumber data belum tersedia.
                        </div>
                    `;
                    return;
                }

                container.innerHTML = rows.map(function (item) {
                    const recordedAt = formatDateTime(item.last_recorded_at);
                    const freshnessText = recordedAt
                        || (item.is_available ? 'Available · no timestamp' : 'Source unavailable');

                    return `
                        <article class="rounded-xl border border-executive-line bg-slate-50/60 px-4 py-3">
                            <div class="flex items-center justify-between gap-3">
                                <p class="truncate text-[11px] font-bold text-executive-ink">${escapeHtml(item.source_label || 'Data Source')}</p>
                                <span class="h-2 w-2 shrink-0 rounded-full ${item.is_available ? 'bg-emerald-500' : 'bg-slate-300'}"></span>
                            </div>
                            <p class="mt-2 text-[10px] font-medium text-executive-muted">${escapeHtml(freshnessText)}</p>
                        </article>
                    `;
                }).join('');
            }

            function renderDashboard(data) {
                renderPeriod(data);
                renderHighlights(data.executiveHighlights || []);
                renderBusinessHealth(data.businessHealth || []);
                renderBrief(data.executiveBrief || {});
                renderAttention(data.businessAttention || []);
                renderFreshness(
                    data.dataFreshness || [],
                    data.executiveBrief?.generated_at || null
                );
                const detailLink = document.getElementById('executiveBriefDetailLink');
                if (detailLink) {
                    const detailUrl = new URL(detailLink.href, window.location.origin);
                    detailUrl.searchParams.set('period', data.filters?.month || monthFilter.value);
                    detailLink.href = detailUrl.toString();
                }
            }

            async function loadMonth(month, pushHistory = true) {
                if (!month) {
                    return;
                }

                if (requestController) {
                    requestController.abort();
                }

                const controller = new AbortController();
                requestController = controller;
                const loadingTitle = document.getElementById('executiveLoadingTitle');
                if (loadingTitle) loadingTitle.textContent = `Loading ${new Intl.DateTimeFormat('en-US', {month:'long', year:'numeric'}).format(new Date(month + '-01T00:00:00'))} data...`;
                setLoading(true);

                try {
                    const requestUrl = new URL(root.dataset.url, window.location.origin);
                    requestUrl.searchParams.set('month', month);

                    const response = await fetch(requestUrl.toString(), {
                        method: 'GET',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        signal: controller.signal,
                        credentials: 'same-origin',
                    });

                    const payload = await response.json().catch(function () {
                        return null;
                    });

                    if (!response.ok || !payload?.success || !payload?.data) {
                        const validationMessage = payload?.errors?.month?.[0];
                        throw new Error(
                            validationMessage
                            || payload?.message
                            || 'Executive data gagal diperbarui.'
                        );
                    }

                    renderDashboard(payload.data);

                    const pageUrl = new URL(window.location.href);
                    pageUrl.searchParams.set('month', payload.data.filters?.month || month);
                    if (pushHistory) window.history.pushState({}, '', pageUrl.toString());
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        showToast(error.message || 'Executive data gagal diperbarui.');
                    }
                } finally {
                    if (requestController === controller) {
                        requestController = null;
                        setLoading(false);
                    }
                }
            }

            monthFilter.addEventListener('change', function () {
                loadMonth(monthFilter.value);
            });
            window.addEventListener('popstate', function () {
                const month = new URLSearchParams(window.location.search).get('month');
                if (month) { monthFilter.value = month; loadMonth(month, false); }
            });
        });
    </script>
@endpush
