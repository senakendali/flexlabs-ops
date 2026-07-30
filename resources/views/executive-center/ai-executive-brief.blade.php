@extends('layouts.executive-center')

@section('title', 'AI Executive Brief')
@section('page_title')
    AI Executive Brief
@endsection
@section('meta_description', 'Management-ready explanation of performance, causes, risks, and next actions.')
@section('page_description', 'Management-ready explanation of performance, causes, risks, and next actions.')

@php
    $brief = $executiveBrief ?? [];
    $confidence = $brief['confidence'] ?? [];
    $periodValue = data_get($period ?? [], 'month', now()->format('Y-m'));
@endphp

@section('header_actions')
    <div class="flex min-w-0 flex-col gap-3 xl:items-end">
        <p class="text-sm font-extrabold text-executive-ink">{{ data_get($period ?? [], 'label', 'Current Period') }}</p>
        <label class="relative block">
            <span class="sr-only">Pilih periode bulan</span>
            <input id="briefPeriod" type="month" value="{{ $periodValue }}"
                class="h-10 min-w-[152px] rounded-xl border border-executive-line bg-white px-3 pr-9 text-xs font-bold text-executive-ink outline-none transition focus:border-executive-primary focus:ring-4 focus:ring-executive-primary/10 disabled:cursor-wait disabled:opacity-60"
                autocomplete="off" aria-label="Periode brief">
            <svg class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-executive-muted" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M7 3v3M17 3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
        </label>
    </div>
@endsection

@push('styles')
<style>
    .executive-main-card { overflow: visible !important; border: 0 !important; background: transparent !important; box-shadow: none !important; }
    .executive-page-header { border: 1px solid #E5E1EE !important; border-radius: 1.25rem !important; background: #FFFFFF !important; box-shadow: 0 16px 45px rgba(31, 27, 46, 0.07) !important; }
    .executive-content { padding: 0 !important; }
    #briefPage { margin: 0 !important; padding: 1.25rem 0 0 !important; }
    .brief-loading { opacity: .55; pointer-events: none; }
    #briefPage > section:first-of-type { margin-top: 0 !important; }
    @media (max-width: 639px) {
        .executive-page-header { border-radius: 1rem !important; }
        #briefPage { padding-top: 1rem !important; }
    }

    @media print {
        #briefPage { padding-top: 0 !important; }
    }
</style>
@endpush

@section('content')
<div id="briefPage" class="min-w-0 space-y-5" data-url="{{ route('executive-center.ai-executive-brief.data') }}">
    <div id="briefLoading" class="fixed inset-0 z-[70] hidden items-center justify-center bg-slate-950/10 backdrop-blur-[1px]" role="status" aria-live="polite"><div class="rounded-2xl border border-executive-line bg-white px-8 py-6 text-center shadow-panel"><svg class="mx-auto h-7 w-7 animate-spin text-executive-primary" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".2" stroke-width="3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg><p id="briefLoadingTitle" class="mt-3 text-sm font-extrabold text-executive-ink"></p><p class="mt-1 text-xs text-executive-muted">FlexOps is consolidating the latest KPI and business performance data.</p></div></div>
    <section class="rounded-2xl border border-executive-line bg-white p-5 shadow-panel">
        <div class="grid min-w-0 gap-5 xl:grid-cols-[minmax(0,1fr)_260px] xl:items-stretch">
            <div class="min-w-0">
                <div class="flex items-center gap-3">
                    <div><h2 class="text-sm font-extrabold text-executive-ink">Executive Summary</h2><p class="mt-1 text-[11px] font-medium text-executive-muted">A clear overview of business performance, key concerns, and priorities.</p></div>
                </div>
                <h3 id="briefHeadline" class="mt-5 break-words text-xl font-extrabold leading-7 tracking-[-0.025em] text-executive-ink">{{ $brief['headline'] ?? 'No executive brief available' }}</h3>
                <p id="briefSummary" class="mt-3 max-w-4xl break-words text-xs font-medium leading-6 text-executive-muted sm:text-sm">{{ $brief['executive_summary'] ?? $brief['summary'] ?? 'There is not enough business data to analyze this period.' }}</p>
                <div id="briefStatus" class="mt-5 flex min-h-6 flex-wrap items-center gap-2 text-[10px] font-semibold text-executive-muted"></div>
            </div>
            <aside class="min-w-0 rounded-xl border border-executive-line bg-executive-primarySoft2 p-4">
                <p class="text-xs font-extrabold text-executive-ink">AI Confidence</p>
                <p class="mt-3"><span id="confidenceLabel" class="text-xl font-black text-executive-primary">{{ $confidence['label'] ?? 'Unavailable' }}</span><span id="confidenceScore" class="ml-2 text-xs font-bold text-executive-muted">{{ isset($confidence['score']) ? '· '.$confidence['score'].'%' : '' }}</span></p>
                <p id="confidenceSources" class="mt-1 text-[10px] font-semibold text-executive-muted">{{ (int) ($confidence['source_count'] ?? 0) }} data sources</p>
                <p id="confidenceHelper" class="mt-3 break-words text-[10px] font-medium leading-4 text-executive-muted" title="Confidence bukan jaminan kesimpulan AI.">{{ $confidence['helper'] ?? 'Confidence merepresentasikan kelengkapan dan freshness data, bukan jaminan kesimpulan AI.' }}</p>
            </aside>
        </div>
    </section>

    <div class="grid min-w-0 grid-cols-1 gap-5 xl:grid-cols-3">
        <section class="min-w-0 rounded-2xl border border-executive-line bg-white p-5 shadow-panel xl:col-span-2">
            <div><h2 class="text-sm font-extrabold text-executive-ink">Root Cause Analysis</h2><p id="rootSubtitle" class="mt-1 text-[11px] font-medium text-executive-muted">Evidence behind the most important KPI gaps</p></div>
            <div id="rootCauses" class="mt-5 divide-y divide-executive-line"></div>
        </section>
        <section class="min-w-0 rounded-2xl border border-executive-line bg-white p-5 shadow-panel">
            <div><h2 class="text-sm font-extrabold text-executive-ink">Risk &amp; Opportunity</h2><p class="mt-1 text-[11px] font-medium text-executive-muted">What management should protect or pursue</p></div>
            <div id="riskOpportunity" class="mt-5 space-y-3"></div>
        </section>
    </div>

    <section class="min-w-0 rounded-2xl border border-executive-line bg-white p-5 shadow-panel">
        <div><h2 class="text-sm font-extrabold text-executive-ink">Recommended Decisions</h2><p class="mt-1 text-[11px] font-medium text-executive-muted">Prioritized actions for management</p></div>
        <div id="recommendedDecisions" class="mt-5 grid min-w-0 gap-3"></div>
    </section>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const page = document.getElementById('briefPage');
    const filter = document.getElementById('briefPeriod');
    const loading = document.getElementById('briefLoading');
    let controller = null;
    let historyNavigation = false;
    const escape = value => String(value ?? '').replace(/[&<>'"]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char]));
    const empty = message => `<div class="rounded-xl border border-dashed border-executive-line bg-white px-5 py-8 text-center"><p class="text-xs font-semibold text-executive-muted">${escape(message)}</p></div>`;
    const badge = (tone, label) => `<span class="inline-flex rounded-full px-2.5 py-1 text-[9px] font-extrabold uppercase tracking-[.06em] ring-1 ring-inset ${tone}">${escape(label)}</span>`;
    const priorityBadge = (tone, label) => `<span class="inline-flex shrink-0 rounded-md px-2.5 py-1 text-[10px] font-extrabold ring-1 ring-inset ${tone}">${escape(label)}</span>`;
    const generatedLabel = value => value ? new Intl.DateTimeFormat('id-ID', {day:'numeric', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit'}).format(new Date(value)) : '';
    const rootTone = severity => ({critical:'border-rose-300 bg-rose-50 text-rose-700', high:'border-rose-300 bg-rose-50 text-rose-700', warning:'border-amber-300 bg-amber-50 text-amber-700', attention:'border-amber-300 bg-amber-50 text-amber-700', medium:'border-amber-300 bg-amber-50 text-amber-700', healthy:'border-emerald-300 bg-emerald-50 text-emerald-700', positive:'border-emerald-300 bg-emerald-50 text-emerald-700', low:'border-emerald-300 bg-emerald-50 text-emerald-700'}[String(severity || '').toLowerCase()] || 'border-violet-200 bg-violet-50 text-executive-primary');

    function render(data) {
        const brief = data.executiveBrief || {};
        const confidence = brief.confidence || {};
        document.getElementById('briefHeadline').textContent = brief.headline || 'No executive brief available';
        document.getElementById('briefSummary').textContent = brief.executive_summary || brief.summary || 'There is not enough business data to analyze this period.';
        document.getElementById('confidenceLabel').textContent = confidence.label || 'Unavailable';
        document.getElementById('confidenceScore').textContent = confidence.score == null ? '' : `· ${confidence.score}%`;
        document.getElementById('confidenceSources').textContent = `${confidence.source_count || 0} data sources`;
        document.getElementById('confidenceHelper').textContent = confidence.helper || 'Confidence merepresentasikan kelengkapan dan freshness data, bukan jaminan kesimpulan AI.';
        const analysisLabel = brief.is_ai_generated ? 'AI Generated' : 'Local Analysis';
        const provider = brief.is_ai_generated && brief.provider ? `<span class="font-bold text-executive-ink">· ${escape(brief.provider)}</span>` : '';
        document.getElementById('briefStatus').innerHTML = `<div class="flex min-w-0 flex-wrap items-center gap-2">${badge(brief.is_ai_generated ? 'bg-violet-50 text-executive-primary ring-violet-200' : 'bg-amber-50 text-amber-700 ring-amber-200', analysisLabel)}${provider}</div>${brief.generated_at ? `<span class="basis-full sm:basis-auto">Generated ${escape(generatedLabel(brief.generated_at))}</span>` : ''}`;

        const roots = Array.isArray(brief.root_causes) ? brief.root_causes : [];
        document.getElementById('rootSubtitle').textContent = roots[0]?.source ? `Why ${roots[0].source} needs attention` : 'Evidence behind the most important KPI gaps';
        document.getElementById('rootCauses').innerHTML = roots.length ? roots.map((item, index) => `<article class="flex min-w-0 items-start gap-3 py-4 first:pt-0 last:pb-0"><span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full border text-sm font-extrabold ${rootTone(item.severity)}">${index + 1}</span><div class="min-w-0 flex-1"><div class="flex flex-wrap items-center gap-2"><h3 class="break-words text-xs font-extrabold text-executive-ink sm:text-sm">${escape(item.title)}</h3>${badge(item.finding_type === 'confirmed' ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-amber-50 text-amber-700 ring-amber-200', item.finding_type === 'confirmed' ? 'Confirmed by data' : 'Indication')}</div><p class="mt-2 break-words text-xs font-medium leading-5 text-executive-muted">${escape(item.evidence)}</p><p class="mt-2 break-words text-[10px] font-semibold text-executive-muted">${escape(item.source)} · ${escape(item.severity)}</p></div></article>`).join('') : empty('Root cause belum dapat ditentukan dari data periode ini.');

        const risks = Array.isArray(brief.risk_opportunity) ? brief.risk_opportunity : [];
        const riskTone = {risk:'bg-rose-50 text-rose-700 ring-rose-200', opportunity:'bg-emerald-50 text-emerald-700 ring-emerald-200', watch:'bg-amber-50 text-amber-700 ring-amber-200', neutral:'bg-violet-50 text-executive-primary ring-violet-200'};
        const riskItemTone = {risk:'border-rose-100 bg-rose-50/40 hover:border-rose-200', opportunity:'border-emerald-100 bg-emerald-50/40 hover:border-emerald-200', watch:'border-amber-100 bg-amber-50/40 hover:border-amber-200', neutral:'border-violet-100 bg-violet-50/40 hover:border-violet-200'};
        document.getElementById('riskOpportunity').innerHTML = risks.length ? risks.map(item => `<article class="min-w-0 rounded-xl border p-4 transition ${riskItemTone[item.type] || riskItemTone.neutral}"><div class="flex flex-wrap items-center justify-between gap-2">${badge(riskTone[item.type] || riskTone.neutral, item.type)}<span class="text-[9px] font-bold uppercase text-executive-muted">${escape(item.urgency)}</span></div><h3 class="mt-3 break-words text-xs font-extrabold text-executive-ink">${escape(item.title)}</h3><p class="mt-1 break-words text-xs font-medium leading-5 text-executive-muted">${escape(item.description)}</p><p class="mt-2 break-words text-[10px] font-semibold text-executive-muted">${escape(item.evidence || item.related_kpi)}</p></article>`).join('') : empty('Belum ada risk atau opportunity yang dapat disimpulkan.');

        const decisions = Array.isArray(brief.recommended_decisions) ? brief.recommended_decisions : [];
        const priorityTone = {P1:'bg-rose-50 text-rose-700 ring-rose-200', P2:'bg-amber-50 text-amber-700 ring-amber-200', P3:'bg-violet-50 text-executive-primary ring-violet-200'};
        const decisionItemTone = {P1:'border-rose-100 bg-rose-50/40 hover:border-rose-200', P2:'border-amber-100 bg-amber-50/40 hover:border-amber-200', P3:'border-violet-100 bg-violet-50/40 hover:border-violet-200'};
        document.getElementById('recommendedDecisions').innerHTML = decisions.length ? decisions.map(item => `<article class="grid min-w-0 gap-3 rounded-xl border p-4 transition sm:grid-cols-[minmax(0,1fr)_auto] ${decisionItemTone[item.priority] || decisionItemTone.P3}"><div class="min-w-0"><div class="flex min-w-0 items-start gap-2">${priorityBadge(priorityTone[item.priority] || priorityTone.P3, item.priority)}<h3 class="min-w-0 break-words text-xs font-extrabold text-executive-ink sm:text-sm">${escape(item.action)}</h3></div><p class="mt-2 break-words text-xs font-medium leading-5 text-executive-muted">${escape(item.reason)}</p><p class="mt-2 break-words text-[10px] font-semibold text-executive-muted">KPI: ${escape(item.related_kpi)} · Expected: ${escape(item.expected_impact)}</p></div><div class="text-left text-[10px] font-semibold text-executive-muted sm:text-right"><p class="font-extrabold text-executive-ink">${escape(item.pic_role)}</p><p class="mt-1">${escape(item.timeframe)}</p></div></article>`).join('') : empty('Belum ada keputusan yang dapat direkomendasikan dari data periode ini.');
    }

    render(@json(['executiveBrief' => $brief]));
    filter.addEventListener('change', async () => {
        controller?.abort(); controller = new AbortController();
        filter.disabled = true; page.classList.add('brief-loading'); page.setAttribute('aria-busy', 'true'); loading.classList.remove('hidden'); loading.classList.add('flex');
        document.getElementById('briefLoadingTitle').textContent = `Loading ${new Intl.DateTimeFormat('en-US',{month:'long',year:'numeric'}).format(new Date(filter.value+'-01T00:00:00'))} data...`;
        try {
            const response = await fetch(`${page.dataset.url}?period=${encodeURIComponent(filter.value)}`, {headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}, signal: controller.signal});
            const payload = await response.json();
            if (!response.ok || !payload.success) throw new Error(payload.message || 'Brief gagal dimuat.');
            render(payload.data); if (!historyNavigation) history.pushState({}, '', `${location.pathname}?period=${encodeURIComponent(filter.value)}`); historyNavigation = false;
        } catch (error) {
            if (error.name === 'AbortError') return;
            if (window.Swal) Swal.fire({icon:'error', title:'Brief gagal dimuat', text:error.message}); else alert(error.message);
        } finally { if (!controller.signal.aborted) { filter.disabled = false; page.classList.remove('brief-loading'); page.setAttribute('aria-busy','false'); loading.classList.add('hidden'); loading.classList.remove('flex'); } }
    });
    window.addEventListener('popstate', () => { const value = new URLSearchParams(location.search).get('period'); if (value) { historyNavigation = true; filter.value = value; filter.dispatchEvent(new Event('change')); } });
});
</script>
@endpush
