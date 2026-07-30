@extends('layouts.executive-center')

@section('title', 'KPI Scorecard')
@section('page_title')
    KPI Scorecard
@endsection
@section('meta_description', 'Track target achievement and performance across the company and each division.')
@section('page_description', 'Track target achievement and performance across the company and each division.')

@section('header_actions')
    <form id="scorecardFilters" method="GET" action="{{ route('executive-center.kpi-scorecard') }}" class="flex min-w-0 flex-col gap-3 xl:items-end">
        <p id="scorecardPeriodLabel" class="text-sm font-extrabold text-executive-ink">{{ data_get($period, 'label', 'Current Period') }}</p>
        <input type="hidden" name="division" value="{{ $filters['division'] }}">
        <label class="relative block">
            <span class="sr-only">Pilih periode bulan</span>
            <input id="scorecardPeriod" name="period" type="month" value="{{ $filters['period'] }}"
                class="h-10 min-w-[152px] rounded-xl border border-executive-line bg-white px-3 pr-9 text-xs font-bold text-executive-ink outline-none transition focus:border-executive-primary focus:ring-4 focus:ring-executive-primary/10">
            <svg class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-executive-muted" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3v3M17 3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        </label>
    </form>
@endsection

@push('styles')
<style>
    .executive-main-card { overflow: visible !important; border: 0 !important; background: transparent !important; box-shadow: none !important; }
    .executive-page-header { border: 1px solid #E5E1EE !important; border-radius: 1.25rem !important; background: #FFF !important; box-shadow: 0 16px 45px rgba(31,27,46,.07) !important; }
    .executive-content { padding: 0 !important; }
    #scorecardPage { padding-top: 20px; }
    #scorecardPeriod {
        color-scheme: light;
    }
    #scorecardPeriod::-webkit-calendar-picker-indicator {
        position: absolute;
        inset: 0;
        width: auto;
        height: auto;
        cursor: pointer;
        opacity: 0;
    }
    @media (max-width:639px) {
        .executive-page-header { border-radius: 1rem !important; }
        #scorecardPage { padding-top: 16px; }
    }
    @media print {
        #scorecardPage { padding-top: 0 !important; }
    }
</style>
@endpush

@php
    $summaryCards = [
        ['label' => 'Average Achievement', 'value' => $scorecardSummary['average_achievement'] !== null ? number_format($scorecardSummary['average_achievement'], 1, ',', '.').'%' : '—', 'helper' => ($scorecardSummary['average_change'] ?? null) !== null ? (($scorecardSummary['average_change'] >= 0 ? '↑ ' : '↓ ').number_format(abs($scorecardSummary['average_change']), 1, ',', '.').' pts') : ($scorecardSummary['scoreable_count'] ? $scorecardSummary['scoreable_count'].' scoreable KPI' : 'No scoreable KPI'), 'tone' => 'text-executive-primary', 'soft' => 'bg-violet-50', 'icon' => '<path d="M5 19V9M12 19V5M19 19v-7M3 19h18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>'],
        ['label' => 'Achieved', 'value' => $scorecardSummary['achieved_count'].' KPI', 'helper' => number_format($scorecardSummary['achieved_percentage'], 1, ',', '.').'% of scored KPI', 'tone' => 'text-emerald-700', 'soft' => 'bg-emerald-50', 'icon' => '<path d="m5 12 4 4L19 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/>'],
        ['label' => 'Needs Attention', 'value' => $scorecardSummary['attention_count'].' KPI', 'helper' => number_format($scorecardSummary['attention_percentage'], 1, ',', '.').'% of scored KPI', 'tone' => 'text-amber-700', 'soft' => 'bg-amber-50', 'icon' => '<path d="M12 8v5M12 16.5h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M10.3 3.4 2.2 17.5A2 2 0 0 0 3.9 20h16.2a2 2 0 0 0 1.7-2.5L13.7 3.4a2 2 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>'],
        ['label' => 'Critical', 'value' => $scorecardSummary['critical_count'].' KPI', 'helper' => number_format($scorecardSummary['critical_percentage'], 1, ',', '.').'% of scored KPI', 'tone' => 'text-rose-700', 'soft' => 'bg-rose-50', 'icon' => '<path d="M12 8v5M12 16.5h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/>'],
    ];
    $statusStyle = ['healthy' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'watch' => 'bg-amber-50 text-amber-700 ring-amber-200', 'critical' => 'bg-rose-50 text-rose-700 ring-rose-200', 'unavailable' => 'bg-slate-100 text-slate-600 ring-slate-200', 'no_data' => 'bg-slate-100 text-slate-600 ring-slate-200', 'not_configured' => 'bg-slate-100 text-slate-600 ring-slate-200', 'pending' => 'bg-violet-50 text-violet-700 ring-violet-200'];
    $barStyle = ['healthy' => 'bg-emerald-500', 'watch' => 'bg-amber-400', 'critical' => 'bg-rose-500'];
    $trendStyle = ['positive' => 'text-emerald-600', 'negative' => 'text-rose-600', 'neutral' => 'text-executive-muted'];
    $divisionIcons = [
        'all' => '<path d="M4 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16M8 7h2M14 7h.01M8 11h2M14 11h.01M8 15h2M14 15h.01M3 21h18M9 21v-3h4v3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
        'overall' => '<path d="M4 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16M8 7h2M14 7h.01M8 11h2M14 11h.01M8 15h2M14 15h.01M3 21h18M9 21v-3h4v3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
        'company' => '<path d="M4 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16M8 7h2M14 7h.01M8 11h2M14 11h.01M8 15h2M14 15h.01M3 21h18M9 21v-3h4v3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
        'growth' => '<path d="M4 18 10 12l4 4 6-8M15 8h5v5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
        'growth_engine' => '<path d="M4 18 10 12l4 4 6-8M15 8h5v5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
        'learning' => '<path d="m3 9 9-5 9 5-9 5-9-5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M7 12v4.5c2.7 2 7.3 2 10 0V12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'learning_centre' => '<path d="m3 9 9-5 9 5-9 5-9-5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M7 12v4.5c2.7 2 7.3 2 10 0V12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'talent' => '<path d="M16 20v-1.5a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4V20M9 10.5a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM22 20v-1.5a4 4 0 0 0-3-3.9M16 2.7a4 4 0 0 1 0 7.7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'talent_hub' => '<path d="M16 20v-1.5a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4V20M9 10.5a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM22 20v-1.5a4 4 0 0 0-3-3.9M16 2.7a4 4 0 0 1 0 7.7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'finance' => '<path d="M4 7.5h16M6 4.5h12a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-11a2 2 0 0 1 2-2ZM16 13.5h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'finance_centre' => '<path d="M4 7.5h16M6 4.5h12a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-11a2 2 0 0 1 2-2ZM16 13.5h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>',
        'operations' => '<path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z" stroke="currentColor" stroke-width="1.8"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06-2.83 2.83-.06-.06A1.7 1.7 0 0 0 15 19.36a1.7 1.7 0 0 0-1 .64 1.7 1.7 0 0 0-.36 1.06V21h-4v-.09A1.7 1.7 0 0 0 8.6 19.4a1.7 1.7 0 0 0-1.87.34l-.06.06-2.83-2.83.06-.06A1.7 1.7 0 0 0 4.24 15a1.7 1.7 0 0 0-1.24-1H3v-4h.09A1.7 1.7 0 0 0 4.6 8.96a1.7 1.7 0 0 0-.34-1.87l-.06-.06L7.03 4.2l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.64A1.7 1.7 0 0 0 10.36 3H14v.09A1.7 1.7 0 0 0 15.04 4.6a1.7 1.7 0 0 0 1.87-.34l.06-.06 2.83 2.83-.06.06A1.7 1.7 0 0 0 19.4 9c.18.47.55.84 1.04 1H21v4h-.09A1.7 1.7 0 0 0 19.4 15Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',
        'operations_centre' => '<path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z" stroke="currentColor" stroke-width="1.8"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06-2.83 2.83-.06-.06A1.7 1.7 0 0 0 15 19.36a1.7 1.7 0 0 0-1 .64 1.7 1.7 0 0 0-.36 1.06V21h-4v-.09A1.7 1.7 0 0 0 8.6 19.4a1.7 1.7 0 0 0-1.87.34l-.06.06-2.83-2.83.06-.06A1.7 1.7 0 0 0 4.24 15a1.7 1.7 0 0 0-1.24-1H3v-4h.09A1.7 1.7 0 0 0 4.6 8.96a1.7 1.7 0 0 0-.34-1.87l-.06-.06L7.03 4.2l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.64A1.7 1.7 0 0 0 10.36 3H14v.09A1.7 1.7 0 0 0 15.04 4.6a1.7 1.7 0 0 0 1.87-.34l.06-.06 2.83 2.83-.06.06A1.7 1.7 0 0 0 19.4 9c.18.47.55.84 1.04 1H21v4h-.09A1.7 1.7 0 0 0 19.4 15Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',
    ];
    $defaultDivisionIcon = '<path d="M4 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16M8 7h2M14 7h.01M8 11h2M14 11h.01M8 15h2M14 15h.01M3 21h18M9 21v-3h4v3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>';
@endphp

@section('content')
<div id="scorecardPage" class="relative min-w-0" aria-busy="false">
<div id="scorecardLoading" class="absolute inset-0 z-20 hidden min-h-[360px] items-start justify-center rounded-2xl bg-white/90 pt-20 backdrop-blur-[1px]" role="status" aria-live="polite"><div class="text-center"><svg class="mx-auto h-7 w-7 animate-spin text-executive-primary" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".2" stroke-width="3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg><p id="scorecardLoadingTitle" class="mt-4 text-sm font-extrabold text-executive-ink"></p><p class="mt-1 text-xs text-executive-muted">FlexOps is consolidating the latest KPI and business performance data.</p></div></div>
<div id="scorecardError" class="mb-5 hidden rounded-xl border border-rose-100 bg-rose-50/50 p-4 text-xs text-rose-700">Unable to update the data. Please try again. The previously loaded information is still displayed.</div>
<div id="scorecardContent" class="min-w-0 space-y-5">
    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($summaryCards as $card)
            <article class="rounded-2xl border border-executive-line bg-white p-5 shadow-panel">
                <div class="flex items-start justify-between gap-3"><p class="text-[11px] font-bold text-executive-muted">{{ $card['label'] }}</p><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $card['soft'] }} {{ $card['tone'] }}"><svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" aria-hidden="true">{!! $card['icon'] !!}</svg></span></div>
                <p class="mt-3 text-2xl font-black tracking-[-.03em] {{ $card['tone'] }}">{{ $card['value'] }}</p>
                <p class="mt-1 text-[10px] font-semibold text-executive-muted">{{ $card['helper'] }}</p>
            </article>
        @endforeach
    </section>

    @if ($scorecardSummary['unavailable_count'] > 0)
        <p class="text-[10px] font-semibold text-executive-muted">{{ $scorecardSummary['unavailable_count'] }} KPI unavailable due to missing target or data.</p>
    @endif

    <nav class="executive-scrollbar flex max-w-full gap-2 overflow-x-auto rounded-2xl border border-executive-line bg-white p-2 shadow-panel" aria-label="KPI divisions">
        @foreach ($divisions as $tab)
            @php
                $divisionIcon = $divisionIcons[$tab['key']] ?? $defaultDivisionIcon;
            @endphp

            <a href="{{ route('executive-center.kpi-scorecard', ['period' => $filters['period'], 'division' => $tab['key']]) }}"
                class="inline-flex shrink-0 items-center gap-2 rounded-xl px-4 py-2.5 text-xs font-extrabold transition {{ $filters['division'] === $tab['key'] ? 'bg-executive-primary text-white shadow-button' : 'text-executive-muted hover:bg-executive-primarySoft hover:text-executive-primary' }}"
                @if ($filters['division'] === $tab['key']) aria-current="page" @endif>
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    {!! $divisionIcon !!}
                </svg>

                <span>{{ $tab['label'] }}</span>
                <span class="text-[9px] opacity-70">{{ $tab['count'] }}</span>
            </a>
        @endforeach
    </nav>

    <section class="min-w-0 overflow-hidden rounded-2xl border border-executive-line bg-white shadow-panel">
        <header class="flex flex-col gap-2 border-b border-executive-line px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div><h2 class="text-sm font-extrabold text-executive-ink">{{ $divisionLabel }} KPI · {{ $period['label'] }}</h2><p class="mt-1 text-[10px] font-semibold text-executive-muted">Target, actual, achievement, and month-over-month trend</p></div>
            <span class="self-start rounded-full bg-executive-primarySoft px-3 py-1.5 text-[10px] font-extrabold text-executive-primary">{{ count($scorecard) }} active KPI</span>
        </header>

        @if (empty($scorecard))
            <div class="px-5 py-14 text-center"><p class="text-sm font-extrabold text-executive-ink">No KPI available</p><p class="mt-2 text-xs text-executive-muted">There are no configured KPI for this division and period.</p></div>
        @else
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full min-w-[900px] text-left">
                    <thead class="bg-slate-50/80 text-[10px] font-extrabold uppercase tracking-[.06em] text-executive-muted"><tr><th class="px-5 py-3">KPI</th><th class="px-4 py-3">Owner</th><th class="px-4 py-3">Target</th><th class="px-4 py-3">Actual</th><th class="px-4 py-3">Achievement</th><th class="px-4 py-3">Trend</th><th class="px-5 py-3">Status</th></tr></thead>
                    <tbody class="divide-y divide-executive-line">
                        @foreach ($scorecard as $kpi)
                            <tr class="odd:bg-white even:bg-slate-50/35">
                                <td class="px-5 py-4"><p class="text-xs font-extrabold text-executive-ink">{{ $kpi['label'] }}</p><p class="mt-1 max-w-xs text-[10px] leading-4 text-executive-muted">{{ $kpi['description'] }}</p></td>
                                <td class="px-4 py-4 text-xs font-semibold text-executive-muted">{{ $kpi['owner'] }}</td>
                                <td class="px-4 py-4 text-xs font-bold text-executive-ink">{{ $kpi['target_formatted'] }}</td>
                                <td class="px-4 py-4 text-xs font-bold text-executive-ink">{{ $kpi['actual_formatted'] }}</td>
                                <td class="w-44 px-4 py-4"><div class="flex items-center justify-between gap-2"><span class="text-xs font-extrabold text-executive-ink">{{ $kpi['achievement_percentage'] !== null ? number_format($kpi['achievement_percentage'], 1, ',', '.').'%' : '—' }}</span></div><div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full {{ $barStyle[$kpi['status']] ?? 'bg-slate-300' }}" style="width: {{ $kpi['progress_width'] }}%"></div></div></td>
                                <td class="px-4 py-4 text-xs font-bold {{ $trendStyle[$kpi['trend_display']['tone']] }}">{{ $kpi['trend_display']['display'] }}</td>
                                <td class="px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-[9px] font-extrabold ring-1 ring-inset {{ $statusStyle[$kpi['status']] ?? $statusStyle['unavailable'] }}">{{ $kpi['status_label'] }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="divide-y divide-executive-line md:hidden">
                @foreach ($scorecard as $kpi)
                    <article class="min-w-0 p-5"><div class="flex items-start justify-between gap-3"><div class="min-w-0"><h3 class="break-words text-sm font-extrabold text-executive-ink">{{ $kpi['label'] }}</h3><p class="mt-1 text-[10px] font-semibold text-executive-muted">{{ $kpi['owner'] }}</p></div><span class="shrink-0 rounded-full px-2.5 py-1 text-[9px] font-extrabold ring-1 ring-inset {{ $statusStyle[$kpi['status']] ?? $statusStyle['unavailable'] }}">{{ $kpi['status_label'] }}</span></div>
                        <dl class="mt-4 grid grid-cols-2 gap-3 text-xs"><div><dt class="text-[10px] text-executive-muted">Target</dt><dd class="mt-1 font-bold text-executive-ink">{{ $kpi['target_formatted'] }}</dd></div><div><dt class="text-[10px] text-executive-muted">Actual</dt><dd class="mt-1 font-bold text-executive-ink">{{ $kpi['actual_formatted'] }}</dd></div><div><dt class="text-[10px] text-executive-muted">Achievement</dt><dd class="mt-1 font-bold text-executive-ink">{{ $kpi['achievement_percentage'] !== null ? number_format($kpi['achievement_percentage'], 1, ',', '.').'%' : '—' }}</dd></div><div><dt class="text-[10px] text-executive-muted">Trend</dt><dd class="mt-1 font-bold {{ $trendStyle[$kpi['trend_display']['tone']] }}">{{ $kpi['trend_display']['display'] }}</dd></div></dl>
                        <div class="mt-4 h-1.5 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full {{ $barStyle[$kpi['status']] ?? 'bg-slate-300' }}" style="width: {{ $kpi['progress_width'] }}%"></div></div>
                        @if (! $kpi['scoreable']) <p class="mt-3 text-[10px] leading-4 text-executive-muted">Not enough data. {{ $kpi['status_reason'] }}</p> @endif
                    </article>
                @endforeach
            </div>
        @endif

        <footer class="border-t border-executive-line bg-slate-50/60 px-5 py-3 text-[10px] font-semibold text-executive-muted"><span class="font-extrabold text-executive-ink">Status rule:</span> {{ $statusLegend }}</footer>
    </section>
</div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',()=>{const form=document.getElementById('scorecardFilters'),input=document.getElementById('scorecardPeriod'),page=document.getElementById('scorecardPage'),loading=document.getElementById('scorecardLoading'),error=document.getElementById('scorecardError');let controller=null;async function load(url,push=true){controller?.abort();controller=new AbortController();input.disabled=true;page.setAttribute('aria-busy','true');error.classList.add('hidden');loading.classList.remove('hidden');loading.classList.add('flex');document.getElementById('scorecardLoadingTitle').textContent=`Loading ${new Intl.DateTimeFormat('en-US',{month:'long',year:'numeric'}).format(new Date(input.value+'-01T00:00:00'))} data...`;try{const r=await fetch(url,{headers:{'X-Requested-With':'XMLHttpRequest'},signal:controller.signal});if(!r.ok)throw new Error();const doc=new DOMParser().parseFromString(await r.text(),'text/html');document.getElementById('scorecardContent').replaceWith(doc.getElementById('scorecardContent'));document.getElementById('scorecardPeriodLabel').textContent=doc.getElementById('scorecardPeriodLabel').textContent;if(push)history.pushState({},'',url)}catch(e){if(e.name!=='AbortError')error.classList.remove('hidden')}finally{if(!controller.signal.aborted){input.disabled=false;page.setAttribute('aria-busy','false');loading.classList.add('hidden');loading.classList.remove('flex')}}}input.addEventListener('change',()=>{const q=new URLSearchParams(new FormData(form));load(`${form.action}?${q}`)});document.addEventListener('click',e=>{const link=e.target.closest('a[href*="/kpi-scorecard?"]');if(!link)return;e.preventDefault();load(link.href)});window.addEventListener('popstate',()=>load(location.href,false));});
</script>
@endpush
