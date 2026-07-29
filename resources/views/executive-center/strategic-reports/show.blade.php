@extends('layouts.executive-center')

@section('title', $report->title)
@section('page_title', $report->title)
@section(
    'page_description',
    ucfirst($report->period_type)
        .' management snapshot · '
        .$report->period_start->format('d M Y')
        .' – '
        .$report->period_end->format('d M Y')
)

@section('header_actions')
    <div class="flex flex-wrap gap-2">
        <a
            href="{{ route('executive-center.strategic-reports.index') }}"
            class="rounded-xl border border-executive-line bg-white px-4 py-2.5 text-xs font-bold"
        >
            Back
        </a>

        @if(!$report->isFinalized())
            <button
                type="button"
                data-action="regenerate"
                data-url="{{ route('executive-center.strategic-reports.regenerate', $report) }}"
                class="report-action rounded-xl border border-executive-line bg-white px-4 py-2.5 text-xs font-bold"
            >
                Regenerate
            </button>

            <button
                type="button"
                data-action="finalize"
                data-url="{{ route('executive-center.strategic-reports.finalize', $report) }}"
                class="report-action rounded-xl bg-executive-primary px-4 py-2.5 text-xs font-extrabold text-white"
            >
                Finalize
            </button>
        @endif

        <a
            href="{{ route('executive-center.strategic-reports.pdf', $report) }}"
            class="rounded-xl bg-executive-primary px-4 py-2.5 text-xs font-extrabold text-white"
        >
            Export PDF
        </a>
    </div>
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

        /*
         * Hilangkan padding bawaan wrapper karena jarak dari
         * page header sudah diatur oleh layout Executive Center.
         */
        .executive-content {
            padding: 0 !important;
        }

        /*
         * Jangan menambah jarak lagi pada root report.
         */
        #strategicReport {
            margin: 0 !important;
            padding: 0 !important;
        }

        .report-card {
            border: 1px solid #E5E1EE;
            border-radius: 1rem;
            background: #FFFFFF;
            padding: 1.25rem;
            box-shadow: 0 16px 45px rgba(31, 27, 46, .07);
        }

        .report-card .grid > .rounded-xl:nth-child(4n + 1),
        .report-card > .grid > div:nth-child(4n + 1) {
            border-color: #E9E2F4;
            background: #FAF8FD;
        }

        .report-card .grid > .rounded-xl:nth-child(4n + 2),
        .report-card > .grid > div:nth-child(4n + 2) {
            border-color: #DCEEDF;
            background: #F7FBF8;
        }

        .report-card .grid > .rounded-xl:nth-child(4n + 3),
        .report-card > .grid > div:nth-child(4n + 3) {
            border-color: #F8E8B5;
            background: #FFFCF3;
        }

        .report-card .grid > .rounded-xl:nth-child(4n + 4),
        .report-card > .grid > div:nth-child(4n + 4) {
            border-color: #F4DDE3;
            background: #FFF8FA;
        }

        .report-card > .grid > div {
            border-width: 1px;
            border-radius: .75rem;
            padding: 1rem;
        }

        #strategicReport > .grid > .report-card:nth-child(1) article {
            border-color: #DCEEDF;
            background: #F7FBF8;
        }

        #strategicReport > .grid > .report-card:nth-child(2) article {
            border-color: #F4DDE3;
            background: #FFF8FA;
        }

        #strategicReport > .grid > .report-card:nth-child(3) article {
            border-color: #E9E2F4;
            background: #FAF8FD;
        }

        #strategicReport > section:nth-of-type(4) th:nth-child(2),
        #strategicReport > section:nth-of-type(4) td:nth-child(2) {
            min-width: 8.5rem;
            white-space: nowrap;
        }

        .trend-chart-card {
            min-width: 0;
            border: 1px solid #E5E1EE;
            border-radius: 1rem;
            background: #FFFFFF;
            padding: 1rem;
        }

        .trend-chart-shell {
            position: relative;
            height: 14rem;
            margin-top: 1rem;
        }

        @media (max-width: 639px) {
            .executive-page-header {
                border-radius: 1rem !important;
            }

            .trend-chart-shell {
                height: 12rem;
            }
        }
    </style>
@endpush

@php
    $statusStyle = [
        'healthy' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'watch' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'critical' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'data_limited' => 'bg-slate-100 text-slate-600 ring-slate-200',
        'unavailable' => 'bg-slate-100 text-slate-600 ring-slate-200',
        'informational' => 'bg-violet-50 text-executive-primary ring-violet-200',
    ];
@endphp

@section('content')
    <div
        id="strategicReport"
        class="relative min-w-0 space-y-5"
        data-csrf="{{ csrf_token() }}"
    >
        <div
            id="reportActionStatus"
            class="hidden rounded-xl border border-violet-100 bg-violet-50 p-4 text-xs text-executive-primary"
            role="status"
        ></div>

        {{-- Report Header --}}
        <section class="report-card">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-[10px] font-extrabold uppercase text-executive-primary">
                        Report Header
                    </p>

                    <p class="mt-2 text-xs text-executive-muted">
                        {{ ucfirst($report->period_type) }}
                        · Revision {{ $report->revision }}
                        · Last generated {{ $report->generated_at?->format('d M Y H:i') ?? '—' }}
                    </p>
                </div>

                <div class="flex gap-2">
                    <span class="rounded-md bg-violet-50 px-2.5 py-1 text-[9px] font-extrabold uppercase text-executive-primary ring-1 ring-violet-200">
                        {{ ucfirst($report->status) }}
                    </span>

                    <span class="rounded-md bg-slate-50 px-2.5 py-1 text-[9px] font-extrabold text-slate-600 ring-1 ring-slate-200">
                        Coverage {{ $report->data_coverage ?? 0 }}%
                    </span>
                </div>
            </div>
        </section>

        {{-- Executive Summary --}}
        <section class="report-card">
            <h2 class="text-sm font-extrabold text-executive-ink">
                Executive Summary
            </h2>

            <p class="mt-4 whitespace-pre-line text-sm leading-7 text-executive-muted">
                {{ $report->executive_summary }}
            </p>

            <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl border border-executive-line p-4">
                    <p class="text-[10px] font-bold text-executive-muted">
                        Overall Business Health
                    </p>

                    <p class="mt-2 text-lg font-extrabold">
                        {{ ucfirst(str_replace('_', ' ', $report->overall_business_health)) }}
                    </p>
                </div>

                <div class="rounded-xl border border-executive-line p-4">
                    <p class="text-[10px] font-bold text-executive-muted">
                        Top Achievements
                    </p>

                    <ul class="mt-2 space-y-2 text-xs text-executive-muted">
                        @forelse(array_slice($report->wins ?? [], 0, 3) as $item)
                            <li>{{ $item['title'] ?? 'Achievement' }}</li>
                        @empty
                            <li>Not Available</li>
                        @endforelse
                    </ul>
                </div>

                <div class="rounded-xl border border-executive-line p-4">
                    <p class="text-[10px] font-bold text-executive-muted">
                        Top Risks
                    </p>

                    <ul class="mt-2 space-y-2 text-xs text-executive-muted">
                        @forelse(array_slice($report->risks ?? [], 0, 3) as $item)
                            <li>{{ $item['title'] ?? 'Risk' }}</li>
                        @empty
                            <li>Not Available</li>
                        @endforelse
                    </ul>
                </div>

                <div class="rounded-xl border border-executive-line p-4">
                    <p class="text-[10px] font-bold text-executive-muted">
                        Decisions Required
                    </p>

                    <ul class="mt-2 space-y-2 text-xs text-executive-muted">
                        @forelse(array_slice($report->management_decisions ?? [], 0, 3) as $item)
                            <li>{{ $item['action'] ?? 'Decision' }}</li>
                        @empty
                            <li>Not Available</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </section>

        {{-- Strategic KPI Overview --}}
        <section class="report-card">
            <h2 class="text-sm font-extrabold">
                Strategic KPI Overview
            </h2>

            <p class="mt-1 text-[11px] text-executive-muted">
                Snapshot values calculated from FlexOps data sources.
            </p>

            <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @foreach($report->kpi_snapshot ?? [] as $kpi)
                    <article class="rounded-xl border border-executive-line p-4">
                        <div class="flex justify-between gap-2">
                            <h3 class="text-xs font-extrabold">
                                {{ $kpi['label'] }}
                            </h3>

                            <span class="rounded-md px-2 py-1 text-[9px] font-bold ring-1 ring-inset {{ $statusStyle[$kpi['status']] ?? $statusStyle['unavailable'] }}">
                                {{ $kpi['status_label'] }}
                            </span>
                        </div>

                        <p class="mt-3 text-xl font-black">
                            {{ $kpi['actual_formatted'] }}
                        </p>

                        <p class="mt-2 text-[10px] text-executive-muted">
                            Previous
                            {{ isset($kpi['previous_actual']) && $kpi['previous_actual'] !== null
                                ? number_format($kpi['previous_actual'], 1, ',', '.')
                                : '—' }}
                            · Target {{ $kpi['target_formatted'] }}
                        </p>

                        <p class="mt-1 text-[10px] text-executive-muted">
                            Source: {{ $kpi['source'] }}
                        </p>
                    </article>
                @endforeach
            </div>
        </section>

        {{-- Centre Performance --}}
        <section class="report-card">
            <h2 class="text-sm font-extrabold">
                Centre Performance
            </h2>

            <div class="mt-5 overflow-x-auto">
                <table class="w-full min-w-[720px] text-left text-xs">
                    <thead class="bg-slate-50 text-[10px] uppercase text-executive-muted">
                        <tr>
                            <th class="p-3">Centre</th>
                            <th class="p-3">Health</th>
                            <th class="p-3">KPI Summary</th>
                            <th class="p-3">Main Issue</th>
                            <th class="p-3">Availability</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-executive-line">
                        @foreach($report->centre_performance_snapshot ?? [] as $centre)
                            <tr>
                                <td class="p-3 font-extrabold">
                                    {{ $centre['name'] }}
                                </td>

                                <td class="p-3">
                                    <span class="rounded-md px-2 py-1 text-[9px] font-bold ring-1 ring-inset {{ $statusStyle[$centre['health_status']] ?? $statusStyle['data_limited'] }}">
                                        {{ $centre['status_label'] }}
                                    </span>
                                </td>

                                <td class="p-3 text-executive-muted">
                                    {{ $centre['kpi_summary'] ?? '—' }}
                                </td>

                                <td class="p-3 text-executive-muted">
                                    {{ $centre['main_issue'] ?? '—' }}
                                </td>

                                <td class="p-3 text-executive-muted">
                                    {{ ucfirst($centre['data_availability']) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        {{-- KPI Performance & Trend --}}
        <section class="report-card">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-sm font-extrabold">
                        KPI Performance &amp; Trend
                    </h2>

                    <p class="mt-1 text-[11px] text-executive-muted">
                        Historical movement for each KPI. Every metric uses its own scale to keep the trend readable.
                    </p>
                </div>

                @if(count($report->trend_snapshot ?? []) > 0)
                    <span class="rounded-md bg-violet-50 px-2.5 py-1 text-[9px] font-extrabold uppercase text-executive-primary ring-1 ring-inset ring-violet-200">
                        {{ count($report->trend_snapshot ?? []) }} KPI Tracked
                    </span>
                @endif
            </div>

            @if(count($report->trend_snapshot ?? []) > 0)
                <div class="mt-5 grid gap-4 xl:grid-cols-2">
                    @foreach($report->trend_snapshot as $trendIndex => $trend)
                        @php
                            $trendPoints = collect($trend['points'] ?? [])
                                ->filter(
                                    fn ($point) =>
                                        isset($point['value'])
                                        && is_numeric($point['value'])
                                )
                                ->values();

                            $latestTrendPoint = $trendPoints->last();

                            $previousTrendPoint = $trendPoints->count() > 1
                                ? $trendPoints->get($trendPoints->count() - 2)
                                : null;

                            $latestTrendValue = $latestTrendPoint['value'] ?? null;
                            $previousTrendValue = $previousTrendPoint['value'] ?? null;

                            $trendChange = is_numeric($latestTrendValue)
                                && is_numeric($previousTrendValue)
                                    ? (float) $latestTrendValue - (float) $previousTrendValue
                                    : null;

                            $trendChangePercent = $trendChange !== null
                                && (float) $previousTrendValue !== 0.0
                                    ? ($trendChange / abs((float) $previousTrendValue)) * 100
                                    : null;

                            $trendDirection = $trendChange === null
                                ? 'neutral'
                                : (
                                    $trendChange > 0
                                        ? 'up'
                                        : ($trendChange < 0 ? 'down' : 'neutral')
                                );
                        @endphp

                        <article class="trend-chart-card">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h3 class="truncate text-xs font-extrabold text-executive-ink">
                                        {{ $trend['label'] ?? 'KPI Trend' }}
                                    </h3>

                                    <p class="mt-1 text-[10px] text-executive-muted">
                                        {{ $trendPoints->count() }}
                                        recorded period{{ $trendPoints->count() === 1 ? '' : 's' }}
                                    </p>
                                </div>

                                @if($trendChange !== null)
                                    <span
                                        class="shrink-0 rounded-md px-2 py-1 text-[9px] font-extrabold ring-1 ring-inset
                                        {{ $trendDirection === 'up'
                                            ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                                            : (
                                                $trendDirection === 'down'
                                                    ? 'bg-rose-50 text-rose-700 ring-rose-200'
                                                    : 'bg-slate-100 text-slate-600 ring-slate-200'
                                            ) }}"
                                    >
                                        @if($trendDirection === 'up')
                                            ↑
                                        @elseif($trendDirection === 'down')
                                            ↓
                                        @else
                                            →
                                        @endif

                                        {{ $trendChangePercent !== null
                                            ? number_format(
                                                abs($trendChangePercent),
                                                1,
                                                ',',
                                                '.'
                                            ).'%'
                                            : number_format(
                                                abs($trendChange),
                                                1,
                                                ',',
                                                '.'
                                            ) }}
                                    </span>
                                @endif
                            </div>

                            <div class="mt-4 flex items-end justify-between gap-3">
                                <div>
                                    <p class="text-[9px] font-bold uppercase tracking-wide text-executive-muted">
                                        Latest Value
                                    </p>

                                    <p class="mt-1 text-xl font-black text-executive-ink">
                                        {{ $trend['latest_formatted']
                                            ?? $latestTrendPoint['formatted']
                                            ?? (
                                                is_numeric($latestTrendValue)
                                                    ? number_format(
                                                        (float) $latestTrendValue,
                                                        1,
                                                        ',',
                                                        '.'
                                                    )
                                                    : '—'
                                            ) }}
                                    </p>
                                </div>

                                <p class="text-right text-[9px] text-executive-muted">
                                    {{ $latestTrendPoint['period'] ?? 'Latest period' }}
                                </p>
                            </div>

                            <div class="trend-chart-shell">
                                <canvas
                                    class="strategic-trend-chart"
                                    data-trend-index="{{ $trendIndex }}"
                                    aria-label="{{ $trend['label'] ?? 'KPI' }} performance trend"
                                    role="img"
                                ></canvas>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="mt-5 rounded-xl border border-dashed border-executive-line bg-slate-50/60 px-5 py-10 text-center">
                    <p class="text-xs font-extrabold text-executive-ink">
                        Trend data is not available yet
                    </p>

                    <p class="mt-2 text-[10px] text-executive-muted">
                        At least two recorded periods are recommended to display KPI movement.
                    </p>
                </div>
            @endif
        </section>

        {{-- Cross-Functional Analysis --}}
        <section class="report-card">
            <h2 class="text-sm font-extrabold">
                Cross-Functional Business Analysis
            </h2>

            <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($report->cross_functional_snapshot ?? [] as $metric)
                    <div class="rounded-xl border border-executive-line p-4">
                        <p class="text-[10px] text-executive-muted">
                            {{ $metric['label'] }}
                        </p>

                        <p class="mt-2 text-lg font-extrabold">
                            {{ $metric['formatted'] }}
                        </p>

                        <span class="mt-2 inline-block text-[9px] font-bold {{ $metric['available'] ? 'text-emerald-700' : 'text-slate-500' }}">
                            {{ $metric['available'] ? 'Available' : 'Data Limited' }}
                        </span>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Wins, Risks, Opportunities --}}
        <div class="grid gap-5 lg:grid-cols-3">
            @foreach([
                ['Strategic Wins', $report->wins, 'emerald'],
                ['Strategic Risks', $report->risks, 'rose'],
                ['Strategic Opportunities', $report->opportunities, 'violet'],
            ] as [$title, $items, $tone])
                <section class="report-card">
                    <h2 class="text-sm font-extrabold">
                        {{ $title }}
                    </h2>

                    <div class="mt-4 space-y-3">
                        @forelse($items ?? [] as $item)
                            <article class="rounded-xl border border-executive-line p-4">
                                <h3 class="text-xs font-extrabold">
                                    {{ $item['title'] ?? 'Insight' }}
                                </h3>

                                <p class="mt-2 text-[11px] leading-5 text-executive-muted">
                                    {{ $item['description'] ?? $item['evidence'] ?? '' }}
                                </p>
                            </article>
                        @empty
                            <p class="text-xs text-executive-muted">
                                Not Available
                            </p>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>

        {{-- Management Decisions and Action Plan --}}
        @foreach([
            [
                'Management Decisions Required',
                $report->management_decisions,
                [
                    'Decision',
                    'Reason',
                    'Expected Impact',
                    'Centre',
                    'Priority',
                    'Status',
                ],
            ],
            [
                'Strategic Action Plan',
                $report->action_plan,
                [
                    'Action',
                    'Centre',
                    'PIC',
                    'Deadline',
                    'Expected Impact',
                    'Priority',
                    'Status',
                ],
            ],
        ] as [$title, $rows, $columns])
            <section class="report-card">
                <h2 class="text-sm font-extrabold">
                    {{ $title }}
                </h2>

                <div class="mt-5 overflow-x-auto">
                    <table class="w-full min-w-[900px] text-left text-xs">
                        <thead class="bg-slate-50 text-[10px] uppercase text-executive-muted">
                            <tr>
                                @foreach($columns as $column)
                                    <th class="p-3">
                                        {{ $column }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-executive-line">
                            @forelse($rows ?? [] as $row)
                                <tr>
                                    @foreach($columns as $column)
                                        @php
                                            $key = strtolower(
                                                str_replace(' ', '_', $column)
                                            );

                                            if ($key === 'decision') {
                                                $key = 'action';
                                            }
                                        @endphp

                                        <td class="p-3 {{ $loop->first ? 'font-extrabold' : 'text-executive-muted' }}">
                                            {{ $row[$key] ?? '—' }}
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="{{ count($columns) }}"
                                        class="p-8 text-center text-executive-muted"
                                    >
                                        Not Available
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endforeach

        {{-- Data Confidence --}}
        <section class="report-card">
            <h2 class="text-sm font-extrabold">
                Data Confidence
            </h2>

            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <div>
                    <p class="text-[10px] text-executive-muted">
                        Overall Confidence
                    </p>

                    <p class="mt-1 font-extrabold">
                        {{ ucfirst($report->data_confidence ?? 'Unavailable') }}
                    </p>
                </div>

                <div>
                    <p class="text-[10px] text-executive-muted">
                        Report Coverage
                    </p>

                    <p class="mt-1 font-extrabold">
                        {{ $report->data_coverage ?? 0 }}%
                    </p>
                </div>

                <div>
                    <p class="text-[10px] text-executive-muted">
                        Missing Sources
                    </p>

                    <p class="mt-1 font-extrabold">
                        {{ count($report->source_limitations ?? []) }}
                    </p>
                </div>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($report->data_freshness ?? [] as $source)
                    <div class="rounded-xl border border-executive-line bg-slate-50/60 p-3">
                        <p class="text-[10px] font-extrabold">
                            {{ $source['source_label'] }}
                        </p>

                        <p class="mt-1 text-[10px] text-executive-muted">
                            {{ $source['last_recorded_at'] ?? 'No timestamp' }}
                        </p>
                    </div>
                @endforeach
            </div>

            @if($report->source_limitations)
                <div class="mt-5 rounded-xl border border-amber-100 bg-amber-50/50 p-4">
                    <p class="text-[10px] font-extrabold text-amber-700">
                        Data limitations
                    </p>

                    <ul class="mt-2 space-y-1 text-[10px] text-executive-muted">
                        @foreach($report->source_limitations as $limit)
                            <li>
                                {{ $limit['source'] }}: {{ $limit['message'] }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </section>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const trends = @json($report->trend_snapshot ?? []);

            const chartColors = [
                '#5B3E8E',
                '#3B8E4D',
                '#D97706',
                '#E11D48',
                '#2563EB',
                '#64748B',
            ];

            const numberFormatter = new Intl.NumberFormat('id-ID', {
                maximumFractionDigits: 2,
            });

            if (window.Chart) {
                document
                    .querySelectorAll('.strategic-trend-chart')
                    .forEach((canvas) => {
                        const trendIndex = Number(canvas.dataset.trendIndex);
                        const trend = trends[trendIndex];

                        if (!trend) {
                            return;
                        }

                        const points = Array.isArray(trend.points)
                            ? trend.points.filter((point) => {
                                return Number.isFinite(Number(point.value));
                            })
                            : [];

                        if (!points.length) {
                            return;
                        }

                        const color =
                            chartColors[trendIndex % chartColors.length];

                        const context = canvas.getContext('2d');

                        const gradient = context.createLinearGradient(
                            0,
                            0,
                            0,
                            220
                        );

                        gradient.addColorStop(0, `${color}33`);
                        gradient.addColorStop(1, `${color}00`);

                        new Chart(context, {
                            type: 'line',

                            data: {
                                labels: points.map((point) => {
                                    return point.period ?? '—';
                                }),

                                datasets: [
                                    {
                                        label: trend.label ?? 'KPI',

                                        data: points.map((point) => {
                                            return Number(point.value);
                                        }),

                                        borderColor: color,
                                        backgroundColor: gradient,
                                        borderWidth: 2.5,
                                        fill: true,
                                        tension: 0.35,
                                        pointRadius: points.length > 12 ? 0 : 3,
                                        pointHoverRadius: 5,
                                        pointBackgroundColor: '#FFFFFF',
                                        pointBorderColor: color,
                                        pointBorderWidth: 2,
                                    },
                                ],
                            },

                            options: {
                                responsive: true,
                                maintainAspectRatio: false,

                                interaction: {
                                    mode: 'index',
                                    intersect: false,
                                },

                                layout: {
                                    padding: {
                                        top: 4,
                                        right: 4,
                                    },
                                },

                                scales: {
                                    x: {
                                        grid: {
                                            display: false,
                                        },

                                        border: {
                                            display: false,
                                        },

                                        ticks: {
                                            color: '#7B748A',

                                            font: {
                                                size: 9,
                                                weight: '600',
                                            },

                                            maxRotation: 0,
                                            autoSkip: true,
                                            maxTicksLimit: 6,
                                        },
                                    },

                                    y: {
                                        beginAtZero: false,

                                        border: {
                                            display: false,
                                        },

                                        grid: {
                                            color: '#EEEAF3',
                                            drawTicks: false,
                                        },

                                        ticks: {
                                            color: '#7B748A',
                                            padding: 8,

                                            font: {
                                                size: 9,
                                                weight: '600',
                                            },

                                            maxTicksLimit: 5,

                                            callback: (value) => {
                                                return numberFormatter.format(value);
                                            },
                                        },
                                    },
                                },

                                plugins: {
                                    legend: {
                                        display: false,
                                    },

                                    tooltip: {
                                        displayColors: false,
                                        backgroundColor: '#1F1B2E',
                                        titleColor: '#FFFFFF',
                                        bodyColor: '#FFFFFF',
                                        padding: 10,
                                        cornerRadius: 8,

                                        callbacks: {
                                            label: (context) => {
                                                const point =
                                                    points[context.dataIndex] ?? {};

                                                return point.formatted
                                                    ?? `${trend.label ?? 'Value'}: ${numberFormatter.format(context.parsed.y)}`;
                                            },
                                        },
                                    },
                                },
                            },
                        });
                    });
            }

            document
                .querySelectorAll('.report-action')
                .forEach((button) => {
                    button.addEventListener('click', async () => {
                        if (
                            button.dataset.action === 'finalize'
                            && !confirm(
                                'Finalize report ini? Snapshot tidak dapat diubah setelah finalized.'
                            )
                        ) {
                            return;
                        }

                        const status =
                            document.getElementById('reportActionStatus');

                        const csrf =
                            document.querySelector(
                                'meta[name="csrf-token"]'
                            )?.content
                            || document.getElementById('strategicReport')
                                ?.dataset.csrf
                            || '';

                        const action = new URL(
                            button.dataset.url,
                            location.href
                        );

                        status.textContent =
                            button.dataset.action === 'finalize'
                                ? 'Finalizing snapshot...'
                                : 'Regenerating report data...';

                        status.classList.remove('hidden');
                        status.classList.remove('text-rose-700');

                        document
                            .querySelectorAll('.report-action')
                            .forEach((item) => {
                                item.disabled = true;
                            });

                        try {
                            const response = await fetch(
                                `${action.pathname}${action.search}`,
                                {
                                    method: 'POST',
                                    credentials: 'same-origin',

                                    headers: {
                                        Accept: 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'X-CSRF-TOKEN': csrf,
                                    },
                                }
                            );

                            const payload = await response.json();

                            if (!response.ok) {
                                throw new Error(
                                    payload.message || 'Action failed.'
                                );
                            }

                            location.href = payload.redirect;
                        } catch (error) {
                            status.textContent = error.message;
                            status.classList.add('text-rose-700');

                            document
                                .querySelectorAll('.report-action')
                                .forEach((item) => {
                                    item.disabled = false;
                                });
                        }
                    });
                });
        });
    </script>
@endpush