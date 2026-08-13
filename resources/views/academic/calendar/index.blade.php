@extends('layouts.app-dashboard')

@section('title', 'Academic Calendar')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css">
<style>
    .academic-calendar-card { overflow:hidden; }
    .academic-calendar-card .content-card-body { padding:24px; }
    .academic-calendar-card .fc { --fc-border-color:#e7e1f0; --fc-today-bg-color:#fff8df; }
    .academic-calendar-card .fc .fc-toolbar { gap:16px; margin-bottom:22px; }
    .academic-calendar-card .fc .fc-toolbar-title { font-size:1.25rem; font-weight:800; color:#2f2440; letter-spacing:-.02em; }
    .academic-calendar-card .fc .fc-button { background:#fff; border-color:#ddd4e9; color:#5B3E8E; border-radius:9px; box-shadow:none; font-weight:600; text-transform:capitalize; padding:.52rem .82rem; }
    .academic-calendar-card .fc .fc-button:hover,
    .academic-calendar-card .fc .fc-button-active { background:#5B3E8E!important; border-color:#5B3E8E!important; color:#fff!important; }
    .academic-calendar-card .fc-theme-standard td,
    .academic-calendar-card .fc-theme-standard th { border-color:#e7e1f0; }
    .academic-calendar-card .fc .fc-col-header-cell { background:#5B3E8E; border-color:rgba(255,255,255,.2); }
    .academic-calendar-card .fc .fc-col-header-cell-cushion { display:block; padding:18px 8px; color:#fff; font-size:.78rem; font-weight:800; letter-spacing:.06em; text-decoration:none; text-transform:uppercase; }
    .academic-calendar-card .fc .fc-daygrid-day-frame { min-height:145px; padding:7px; transition:background-color .2s ease; }
    .academic-calendar-card .fc .fc-daygrid-day:hover .fc-daygrid-day-frame { background:#faf8fd; }
    .academic-calendar-card .fc .fc-daygrid-day-number { color:#41364f; font-size:.86rem; font-weight:700; padding:5px 7px; text-decoration:none; }
    .academic-calendar-card .fc .fc-day-today .fc-daygrid-day-number { display:inline-flex; align-items:center; justify-content:center; min-width:30px; height:30px; color:#fff; background:#FFBE04; border-radius:50%; }
    .academic-calendar-card .fc .fc-day-other { background:#fafafa; }
    .academic-calendar-card .fc .fc-day-other .fc-daygrid-day-number { color:#aaa3b2; }
    .academic-calendar-card .fc .fc-daygrid-event { display:block; width:100%; margin:5px 0 0; border:0!important; border-radius:8px; padding:7px 8px; cursor:pointer; box-shadow:0 3px 8px rgba(46,29,67,.16); font-size:.76rem; font-weight:700; overflow:hidden; }
    .academic-calendar-card .fc .fc-daygrid-event-harness { margin-left:0!important; margin-right:0!important; }
    .academic-calendar-card .fc .fc-event-main { width:100%; }
    .academic-calendar-card .fc .fc-event-main-frame { display:flex; align-items:center; width:100%; min-width:0; }
    .academic-calendar-card .fc .fc-event-time { flex:0 0 auto; margin-right:5px; font-weight:800; }
    .academic-calendar-card .fc .fc-event-title-container { min-width:0; }
    .academic-calendar-card .fc .fc-event-title { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .academic-calendar-card .fc .fc-daygrid-more-link { display:inline-block; margin-top:5px; padding:3px 7px; color:#5B3E8E; background:#eee8f5; border-radius:6px; font-size:.72rem; font-weight:700; text-decoration:none; }
    .academic-calendar-card .fc .fc-list { overflow:hidden; border-radius:12px; }
    .academic-calendar-card .fc .fc-list-day-cushion {
        min-height:64px;
        padding:20px 18px;
        background:#5B3E8E!important;
        color:#fff!important;
    }
    .academic-calendar-card .fc .fc-list-day-text,
    .academic-calendar-card .fc .fc-list-day-side-text {
        color:#fff!important;
        font-size:.82rem;
        font-weight:800;
        letter-spacing:.045em;
        text-decoration:none;
        text-transform:uppercase;
    }
    .academic-calendar-card .fc .fc-list-event td { padding-top:16px; padding-bottom:16px; vertical-align:middle; }
    .academic-calendar-card .fc .fc-list-event:hover td { background:#faf8fd; }
    .academic-calendar-card .fc .fc-list-event-dot { width:12px; height:12px; border-width:6px; }
    .academic-calendar-card .fc .fc-list-event-title a { color:#30253e; font-weight:700; text-decoration:none; }
    .batch-tabs {
        display:flex;
        gap:0;
        width:100%;
        overflow-x:auto;
        overflow-y:hidden;
        scrollbar-width:thin;
        border-bottom:1px solid #b9b4c0;
    }
    .batch-tab {
        flex:1 0 180px;
        min-height:58px;
        margin:0 0 -1px;
        padding:12px 18px;
        white-space:nowrap;
        background:#f5f3f7;
        border:0;
        border-top:1px solid #ded9e3;
        border-right:1px solid #ded9e3;
        border-bottom:1px solid #b9b4c0;
        border-radius:0;
        color:#625c68;
        font-size:.875rem;
        font-weight:500;
        text-align:center;
        transition:background-color .18s ease,color .18s ease;
    }
    .batch-tab:first-child { border-left:1px solid #ded9e3; }
    .batch-tab:hover { background:#eeeaf2; color:#382c46; }
    .batch-tab.active {
        position:relative;
        z-index:2;
        background:#fff;
        border-top:1px solid #6f6876;
        border-right:1px solid #6f6876;
        border-bottom-color:#fff;
        border-left:1px solid #6f6876;
        color:#241d2b;
        font-weight:800;
    }
    .batch-tab.active + .batch-tab { border-left:0; }
    .calendar-legend { display:flex; gap:12px; flex-wrap:wrap; }
    .legend-dot { width:10px; height:10px; border-radius:3px; display:inline-block; margin-right:6px; }
    @media (max-width: 767.98px) {
        .academic-calendar-card .content-card-body { padding:16px; }
        .academic-calendar-card .fc .fc-toolbar { align-items:flex-start; flex-direction:column; }
        .academic-calendar-card .fc .fc-toolbar-chunk { width:100%; }
        .academic-calendar-card .fc .fc-toolbar-chunk:nth-child(2) { order:-1; }
        .academic-calendar-card .fc .fc-daygrid-day-frame { min-height:105px; padding:3px; }
        .academic-calendar-card .fc .fc-col-header-cell-cushion { padding:13px 3px; font-size:.68rem; }
        .batch-tab { flex-basis:165px; min-height:52px; padding:10px 14px; }
    }
</style>
@endpush

@section('content')
@php
    $types = [
        'kickoff'=>'Kickoff','live_session'=>'Live Session','assignment_deadline'=>'Assignment Deadline',
        'quiz_deadline'=>'Quiz Deadline','mentoring'=>'Mentoring','replacement_class'=>'Replacement Class',
        'assessment'=>'Assessment','final_presentation'=>'Final Presentation','holiday'=>'Holiday / No Class','other'=>'Other',
    ];
@endphp
<div class="container-fluid px-4 py-4">
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div><div class="page-eyebrow">Academic</div><h1 class="page-title mb-2">Academic Calendar</h1><p class="page-subtitle mb-0">Monitor all running batches and academic activities in one monthly calendar.</p></div>
            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <a href="{{ route('academic.schedules.index') }}" class="btn btn-light btn-modern"><i class="bi bi-list-check me-2"></i>Manage Schedules</a>
                <a href="{{ route('academic.schedules.index') }}" class="btn btn-light btn-modern"><i class="bi bi-plus-lg me-2"></i>Add Schedule</a>
            </div>
        </div>
    </div>

    <div class="content-card academic-calendar-card">
        <div class="content-card-header d-block">
            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
                <div><h5 class="content-card-title mb-1">Monthly Overview</h5><p class="content-card-subtitle mb-0">Select a batch tab or use filters to focus the calendar.</p></div>
                <div class="d-flex gap-2 flex-wrap">
                    <select id="programFilter" class="form-select form-select-sm" style="width:180px"><option value="">All Programs</option>@foreach($programs as $program)<option value="{{ $program->id }}">{{ $program->name }}</option>@endforeach</select>
                    <select id="typeFilter" class="form-select form-select-sm" style="width:190px"><option value="">All Activities</option>@foreach($types as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
                </div>
            </div>
            <div class="batch-tabs" id="batchTabs">
                <button class="batch-tab active" data-batch-id="" data-program-id="">All Running Batches</button>
                @foreach($runningBatches as $batch)<button class="batch-tab" data-batch-id="{{ $batch->id }}" data-program-id="{{ $batch->program_id }}">{{ $batch->program?->name }} · {{ $batch->name }}</button>@endforeach
            </div>
        </div>
        <div class="content-card-body">
            <div id="calendarLoading" class="alert alert-light border d-none"><span class="spinner-border spinner-border-sm me-2"></span>Loading schedules...</div>
            <div id="calendarError" class="alert alert-danger d-none"></div>
            <div id="academicCalendar"></div>
            <div class="calendar-legend border-top pt-3 mt-3">
                @php $batchPalette = ['#5B3E8E','#2563EB','#0F766E','#C2410C','#7C3AED','#BE123C','#0369A1','#3F6212']; @endphp
                @forelse($runningBatches as $batch)
                    <span class="small text-muted"><span class="legend-dot" style="background:{{ $batchPalette[$batch->id % count($batchPalette)] }}"></span>{{ $batch->program?->name }} · {{ $batch->name }}</span>
                @empty
                    <span class="small text-muted">No running batches.</span>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="eventModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content border-0 shadow">
    <div class="modal-header"><div><h5 id="eventTitle" class="modal-title fw-bold mb-1">Schedule Detail</h5><div id="eventSubtitle" class="small text-muted"></div></div><button class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><div class="row g-3" id="eventDetailGrid"></div></div>
    <div class="modal-footer"><button class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">Close</button></div>
</div></div></div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
    const eventModal=new bootstrap.Modal(document.getElementById('eventModal'));
    const programFilter=document.getElementById('programFilter'),typeFilter=document.getElementById('typeFilter');
    let selectedBatchId='';
    const calendar=new FullCalendar.Calendar(document.getElementById('academicCalendar'),{
        initialView:'dayGridMonth',height:'auto',firstDay:1,dayMaxEvents:3,eventDisplay:'block',displayEventTime:true,
        headerToolbar:{left:'prev,next today',center:'title',right:'dayGridMonth,timeGridWeek,listMonth'},
        buttonText:{today:'Today',month:'Month',week:'Week',list:'List'},
        loading:isLoading=>document.getElementById('calendarLoading').classList.toggle('d-none',!isLoading),
        events:async(info,success,failure)=>{
            const params=new URLSearchParams({start:info.startStr,end:info.endStr});
            if(programFilter.value)params.set('program_id',programFilter.value);if(selectedBatchId)params.set('batch_id',selectedBatchId);if(typeFilter.value)params.set('schedule_type',typeFilter.value);
            const error=document.getElementById('calendarError');error.classList.add('d-none');
            try{const response=await fetch(`{{ route('academic.calendar.events') }}?${params}`,{headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}}),result=await response.json();if(!response.ok||!result.success)throw new Error(result.message||'Unable to load schedules.');success(result.data||[])}catch(e){error.textContent=e.message;error.classList.remove('d-none');failure(e)}
        },
        eventClick:info=>openEvent(info.event),
        eventDidMount:info=>{info.el.title=`${info.event.title} · ${info.event.extendedProps.batch_name||'-'}`}
    });
    calendar.render();
    [programFilter,typeFilter].forEach(el=>el.addEventListener('change',()=>{if(el===programFilter){selectedBatchId='';document.querySelectorAll('.batch-tab').forEach((b,i)=>b.classList.toggle('active',i===0))}calendar.refetchEvents()}));
    document.getElementById('batchTabs').addEventListener('click',e=>{const btn=e.target.closest('.batch-tab');if(!btn)return;document.querySelectorAll('.batch-tab').forEach(b=>b.classList.remove('active'));btn.classList.add('active');selectedBatchId=btn.dataset.batchId||'';programFilter.value=btn.dataset.programId||'';calendar.refetchEvents()});
    function openEvent(event){const p=event.extendedProps;document.getElementById('eventTitle').textContent=event.title;document.getElementById('eventSubtitle').textContent=`${p.program_name||'-'} · ${p.batch_name||'-'}`;const when=event.allDay?'All Day':`${formatTime(event.start)}${event.end?' - '+formatTime(event.end):''}`;const rows=[['Activity',headline(p.schedule_type)],['Date',formatDate(event.start)],['Time',when],['Instructor / PIC',p.instructor_name||'-'],['Notes',p.notes||'-']];document.getElementById('eventDetailGrid').innerHTML=rows.map(([l,v],i)=>`<div class="${i===4?'col-12':'col-md-6'}"><div class="small text-muted mb-1">${l}</div><div class="fw-semibold text-dark">${escapeHtml(v)}</div></div>`).join('');eventModal.show()}
    function formatDate(d){return new Intl.DateTimeFormat('en-GB',{day:'2-digit',month:'short',year:'numeric'}).format(d)}
    function formatTime(d){return new Intl.DateTimeFormat('en-GB',{hour:'2-digit',minute:'2-digit',hour12:false}).format(d)}
    function headline(v=''){return String(v||'-').replaceAll('_',' ').replace(/\b\w/g,c=>c.toUpperCase())}
    function escapeHtml(v=''){const d=document.createElement('div');d.textContent=v??'';return d.innerHTML}
});
</script>
@endpush