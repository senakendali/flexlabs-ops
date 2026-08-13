@extends('layouts.app-dashboard')

@section('title', 'Academic Schedules')

@push('styles')
<style>
    #scheduleModal .modal-dialog {
        height: calc(100vh - 2rem);
        margin-top: 1rem;
        margin-bottom: 1rem;
    }

    #scheduleModal #scheduleForm,
    #scheduleModal .modal-content {
        display: flex;
        flex-direction: column;
        width: 100%;
        min-height: 0;
        max-height: 100%;
    }

    #scheduleModal .modal-content {
        overflow: hidden;
    }

    #scheduleModal .modal-header,
    #scheduleModal .modal-footer {
        flex: 0 0 auto;
        background: #fff;
        position: relative;
        z-index: 2;
    }

    #scheduleModal .modal-body {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        overscroll-behavior: contain;
        padding-bottom: 2rem;
    }

    @media (max-width: 767.98px) {
        #scheduleModal .modal-dialog {
            height: calc(100vh - 1rem);
            margin: .5rem;
        }
    }
</style>
@endpush

@section('content')
@php
    $typeLabels = [
        'kickoff' => 'Kickoff', 'live_session' => 'Live Session',
        'assignment_deadline' => 'Assignment Deadline', 'quiz_deadline' => 'Quiz Deadline',
        'mentoring' => 'Mentoring', 'replacement_class' => 'Replacement Class',
        'assessment' => 'Assessment', 'final_presentation' => 'Final Presentation',
        'holiday' => 'Holiday / No Class', 'other' => 'Other',
    ];
    $typeClasses = [
        'kickoff' => 'bg-primary-subtle text-primary-emphasis',
        'live_session' => 'bg-info-subtle text-info-emphasis',
        'assignment_deadline' => 'bg-warning-subtle text-warning-emphasis',
        'quiz_deadline' => 'bg-warning-subtle text-warning-emphasis',
        'mentoring' => 'bg-success-subtle text-success-emphasis',
        'replacement_class' => 'bg-info-subtle text-info-emphasis',
        'assessment' => 'bg-primary-subtle text-primary-emphasis',
        'final_presentation' => 'bg-danger-subtle text-danger-emphasis',
        'holiday' => 'bg-secondary-subtle text-secondary-emphasis',
        'other' => 'bg-secondary-subtle text-secondary-emphasis',
    ];
@endphp

<div class="container-fluid px-4 py-4">
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Academic</div>
                <h1 class="page-title mb-2">Academic Schedules</h1>
                <p class="page-subtitle mb-0">Manage markers for classes, deadlines, mentoring, presentations, and other academic activities.</p>
            </div>
            <div class="page-header-actions d-flex gap-2 flex-wrap">
                <a href="{{ route('academic.calendar.index') }}" class="btn btn-light btn-modern">
                    <i class="bi bi-calendar3 me-2"></i>View Calendar
                </a>
                <button type="button" class="btn btn-light btn-modern" onclick="openCreateModal()">
                    <i class="bi bi-plus-lg me-2"></i>Add Schedule
                </button>
            </div>
        </div>
    </div>

    <div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index:9999"></div>

    <div class="content-card">
        <div class="content-card-header align-items-start">
            <div>
                <h5 class="content-card-title mb-1">Schedule List</h5>
                <p class="content-card-subtitle mb-0">Review schedule markers by program, batch, activity type, and date.</p>
            </div>
            <form method="GET" class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                <select name="program_id" id="filterProgram" class="form-select form-select-sm" style="width:175px">
                    <option value="">All Programs</option>
                    @foreach($programs as $program)
                        <option value="{{ $program->id }}" {{ (string)request('program_id') === (string)$program->id ? 'selected' : '' }}>{{ $program->name }}</option>
                    @endforeach
                </select>
                <select name="batch_id" id="filterBatch" class="form-select form-select-sm" style="width:175px">
                    <option value="">All Batches</option>
                    @foreach($batches as $batch)
                        <option value="{{ $batch->id }}" data-program-id="{{ $batch->program_id }}" {{ (string)request('batch_id') === (string)$batch->id ? 'selected' : '' }}>{{ $batch->name }}</option>
                    @endforeach
                </select>
                <select name="schedule_type" class="form-select form-select-sm" style="width:180px">
                    <option value="">All Activities</option>
                    @foreach($typeLabels as $value => $label)
                        <option value="{{ $value }}" {{ request('schedule_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <div class="input-group input-group-sm" style="width:240px">
                    <input name="search" value="{{ request('search') }}" class="form-control" placeholder="Search schedule...">
                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                </div>
                @if(request()->hasAny(['program_id','batch_id','schedule_type','search','date_from','date_to']))
                    <a href="{{ route('academic.schedules.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                @endif
            </form>
        </div>

        <div class="content-card-body">
            @if($schedules->count())
                <div class="table-responsive dropdown-safe-table">
                    <table class="table table-hover align-middle admin-table mb-0">
                        <thead><tr>
                            <th style="width:70px">No</th><th>Schedule</th><th>Program & Batch</th><th>Date & Time</th>
                            <th>Instructor / PIC</th><th class="text-end" style="width:140px">Action</th>
                        </tr></thead>
                        <tbody>
                        @foreach($schedules as $schedule)
                            <tr>
                                <td class="text-muted">{{ ($schedules->currentPage()-1)*$schedules->perPage()+$loop->iteration }}</td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $schedule->title }}</div>
                                    <span class="badge rounded-pill border {{ $typeClasses[$schedule->schedule_type] ?? $typeClasses['other'] }}">{{ $typeLabels[$schedule->schedule_type] ?? Illuminate\Support\Str::headline($schedule->schedule_type) }}</span>
                                </td>
                                <td><div class="fw-semibold">{{ $schedule->program?->name ?? '-' }}</div><div class="small text-muted">{{ $schedule->batch?->name ?? '-' }}</div></td>
                                <td>
                                    <div class="fw-semibold text-nowrap">{{ $schedule->schedule_date?->format('d M Y') ?? '-' }}</div>
                                    <div class="small text-muted text-nowrap">{{ $schedule->is_all_day ? 'All Day' : (($schedule->start_time ? substr($schedule->start_time,0,5) : '-') . ' - ' . ($schedule->end_time ? substr($schedule->end_time,0,5) : '-')) }}</div>
                                </td>
                                <td>{{ $schedule->instructor?->name ?? '-' }}</td>
                                <td class="text-end text-nowrap">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle px-3" data-bs-toggle="dropdown" data-bs-boundary="viewport">Actions</button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li><button class="dropdown-item" onclick="viewSchedule({{ $schedule->id }})"><i class="bi bi-eye me-2"></i>View Detail</button></li>
                                            <li><button class="dropdown-item" onclick="editSchedule({{ $schedule->id }})"><i class="bi bi-pencil-square me-2"></i>Edit Schedule</button></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><button class="dropdown-item text-danger" onclick="openDeleteModal({{ $schedule->id }}, @js($schedule->title))"><i class="bi bi-trash me-2"></i>Delete</button></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @if($schedules->hasPages())<div class="mt-3">{{ $schedules->links() }}</div>@endif
            @else
                <div class="empty-state-box">
                    <div class="empty-state-icon"><i class="bi bi-calendar2-week"></i></div>
                    <h5 class="empty-state-title">No schedules found</h5>
                    <p class="empty-state-text mb-0">No academic schedule matches the selected filter.</p>
                    <div class="mt-3"><button class="btn btn-primary btn-modern" onclick="openCreateModal()"><i class="bi bi-plus-lg me-2"></i>Add Schedule</button></div>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Add/Edit Modal --}}
<div class="modal fade" id="scheduleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form id="scheduleForm" class="w-100">@csrf<input type="hidden" id="schedule_id">
            <div class="modal-content border-0 shadow">
                <div class="modal-header"><div><h5 class="modal-title fw-bold mb-1" id="scheduleModalTitle">Add Schedule</h5><div class="small text-muted">Mark an academic activity and make it visible on the calendar.</div></div><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div id="formAlert" class="alert alert-danger d-none"></div>
                    <div class="content-card mb-3"><div class="content-card-header"><div><h5 class="content-card-title mb-1">Activity Information</h5><p class="content-card-subtitle mb-0">Select its title, program, batch, and activity type.</p></div></div>
                        <div class="content-card-body"><div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Schedule Title <span class="text-danger">*</span></label><input id="title" class="form-control" maxlength="255" placeholder="e.g. Live Session 1"><div id="error_title" class="invalid-feedback"></div></div>
                            <div class="col-md-6"><label class="form-label">Activity Type <span class="text-danger">*</span></label><select id="schedule_type" class="form-select">@foreach($typeLabels as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select><div id="error_schedule_type" class="invalid-feedback"></div></div>
                            <div class="col-md-6"><label class="form-label">Program <span class="text-danger">*</span></label><select id="program_id" class="form-select"><option value="">Select Program</option>@foreach($programs as $program)<option value="{{ $program->id }}">{{ $program->name }}</option>@endforeach</select><div id="error_program_id" class="invalid-feedback"></div></div>
                            <div class="col-md-6"><label class="form-label">Batch <span class="text-danger">*</span></label><select id="batch_id" class="form-select"><option value="">Select Batch</option>@foreach($batches as $batch)<option value="{{ $batch->id }}" data-program-id="{{ $batch->program_id }}">{{ $batch->name }}</option>@endforeach</select><div id="error_batch_id" class="invalid-feedback"></div></div>
                        </div></div>
                    </div>
                    <div class="content-card mb-3"><div class="content-card-header"><div><h5 class="content-card-title mb-1">Date & Time</h5><p class="content-card-subtitle mb-0">Use All Day for holidays or markers without a specific time.</p></div></div>
                        <div class="content-card-body"><div class="row g-3 align-items-end">
                            <div class="col-md-4"><label class="form-label">Schedule Date <span class="text-danger">*</span></label><input id="schedule_date" type="date" class="form-control"><div id="error_schedule_date" class="invalid-feedback"></div></div>
                            <div class="col-md-3 time-field"><label class="form-label">Start Time <span class="text-danger">*</span></label><input id="start_time" type="time" class="form-control"><div id="error_start_time" class="invalid-feedback"></div></div>
                            <div class="col-md-3 time-field"><label class="form-label">End Time <span class="text-danger">*</span></label><input id="end_time" type="time" class="form-control"><div id="error_end_time" class="invalid-feedback"></div></div>
                            <div class="col-md-2"><div class="form-check form-switch mb-2"><input id="is_all_day" class="form-check-input" type="checkbox"><label class="form-check-label" for="is_all_day">All Day</label></div><div id="error_is_all_day" class="invalid-feedback"></div></div>
                        </div></div>
                    </div>
                    <div class="content-card"><div class="content-card-header"><div><h5 class="content-card-title mb-1">Additional Detail</h5><p class="content-card-subtitle mb-0">Instructor/PIC and notes are optional.</p></div></div>
                        <div class="content-card-body"><div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Instructor / PIC</label><select id="instructor_id" class="form-select"><option value="">Select Instructor / PIC</option>@foreach($instructors as $instructor)<option value="{{ $instructor->user_id }}">{{ $instructor->name }}</option>@endforeach</select><div id="error_instructor_id" class="invalid-feedback"></div></div>
                            <div class="col-12"><label class="form-label">Notes</label><textarea id="notes" rows="4" class="form-control" placeholder="Additional information for this schedule"></textarea><div id="error_notes" class="invalid-feedback"></div></div>
                        </div></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal"><i class="bi bi-x-circle me-2"></i>Cancel</button><button id="submitBtn" class="btn btn-primary btn-modern" type="submit"><span class="default-text"><i class="bi bi-check-circle me-2"></i>Save</span><span class="loading-text d-none"><span class="spinner-border spinner-border-sm me-2"></span>Saving...</span></button></div>
            </div>
        </form>
    </div>
</div>

{{-- Detail Modal --}}
<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content border-0 shadow">
    <div class="modal-header"><div><h5 class="modal-title fw-bold mb-1" id="detailTitle">Schedule Detail</h5><div class="small text-muted" id="detailSubtitle"></div></div><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><div id="detailLoading" class="text-center py-5"><span class="spinner-border text-primary"></span></div><div id="detailContent" class="d-none"><div class="row g-3" id="detailGrid"></div></div></div>
</div></div></div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow">
    <div class="modal-header"><div><h5 class="modal-title fw-bold mb-1">Delete Schedule</h5><div class="small text-muted">This action permanently removes the selected marker.</div></div><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><div class="alert alert-danger mb-0"><i class="bi bi-exclamation-triangle-fill me-2"></i>Delete <strong id="deleteScheduleName"></strong>?</div></div>
    <div class="modal-footer"><button class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">Cancel</button><button id="confirmDeleteBtn" class="btn btn-danger btn-modern"><span class="default-delete-text"><i class="bi bi-trash me-2"></i>Delete</span><span class="loading-delete-text d-none"><span class="spinner-border spinner-border-sm me-2"></span>Deleting...</span></button></div>
</div></div></div>
@endsection

@push('scripts')
<script>
const scheduleBaseUrl = @json(url('/academic/schedules'));
const csrfToken = @json(csrf_token());
const typeLabels = @json($typeLabels);
const detailModal = new bootstrap.Modal(document.getElementById('detailModal'));
let deleteScheduleId = null;

function showToast(message, type='success') {
    const id='toast-'+Date.now(), bg={success:'bg-success',danger:'bg-danger',warning:'bg-warning text-dark'}[type]||'bg-success';
    document.getElementById('toastContainer').insertAdjacentHTML('beforeend', `<div id="${id}" class="toast align-items-center text-white ${bg} border-0 mb-2"><div class="d-flex"><div class="toast-body">${escapeHtml(message)}</div><button class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div>`);
    const el=document.getElementById(id); new bootstrap.Toast(el,{delay:2500}).show(); el.addEventListener('hidden.bs.toast',()=>el.remove());
}
function escapeHtml(value=''){const el=document.createElement('div');el.textContent=value??'';return el.innerHTML;}
function filterOptions(select, programId){Array.from(select.options).forEach((o,i)=>{if(i===0)return;o.hidden=!!programId&&o.dataset.programId!==String(programId)});if(select.selectedOptions[0]?.hidden)select.value='';}
document.getElementById('filterProgram')?.addEventListener('change',e=>filterOptions(document.getElementById('filterBatch'),e.target.value));

async function fetchSchedule(id){const response=await fetch(`${scheduleBaseUrl}/${id}`,{headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}});const result=await response.json();if(!response.ok||!result.success)throw new Error(result.message||'Failed to load schedule.');return result.data;}
async function viewSchedule(id){
    document.getElementById('detailLoading').classList.remove('d-none');document.getElementById('detailContent').classList.add('d-none');detailModal.show();
    try {const d=await fetchSchedule(id);document.getElementById('detailTitle').textContent=d.title;document.getElementById('detailSubtitle').textContent=`${d.program?.name||'-'} · ${d.batch?.name||'-'}`;
        const time=d.is_all_day?'All Day':`${String(d.start_time||'-').slice(0,5)} - ${String(d.end_time||'-').slice(0,5)}`;
        const items=[['Activity',typeLabels[d.schedule_type]||d.schedule_type],['Date',formatDate(d.schedule_date)],['Time',time],['Instructor / PIC',d.instructor?.name||'-'],['Notes',escapeHtml(d.notes||'-')]];
        document.getElementById('detailGrid').innerHTML=items.map(([l,v],i)=>`<div class="${i===4?'col-12':'col-md-6'}"><div class="small text-muted mb-1">${l}</div><div class="fw-semibold text-dark">${v}</div></div>`).join('');
        document.getElementById('detailLoading').classList.add('d-none');document.getElementById('detailContent').classList.remove('d-none');
    } catch(e){detailModal.hide();showToast(e.message,'danger')}
}
function formatDate(value){if(!value)return '-';const raw=String(value).slice(0,10);return new Intl.DateTimeFormat('en-GB',{day:'2-digit',month:'short',year:'numeric',timeZone:'UTC'}).format(new Date(raw+'T00:00:00Z'))}
function capitalize(v=''){return String(v).replaceAll('_',' ').replace(/\b\w/g,c=>c.toUpperCase())}

const scheduleModal=new bootstrap.Modal(document.getElementById('scheduleModal')), scheduleForm=document.getElementById('scheduleForm'), submitBtn=document.getElementById('submitBtn');
const fields={id:document.getElementById('schedule_id'),title:document.getElementById('title'),program_id:document.getElementById('program_id'),batch_id:document.getElementById('batch_id'),schedule_type:document.getElementById('schedule_type'),schedule_date:document.getElementById('schedule_date'),is_all_day:document.getElementById('is_all_day'),start_time:document.getElementById('start_time'),end_time:document.getElementById('end_time'),instructor_id:document.getElementById('instructor_id'),notes:document.getElementById('notes')};
function resetForm(){scheduleForm.reset();fields.id.value='';document.getElementById('formAlert').classList.add('d-none');clearErrors();toggleAllDay();setSubmitLoading(false)}
function clearErrors(){Object.entries(fields).forEach(([k,f])=>{f.classList?.remove('is-invalid');const e=document.getElementById(`error_${k}`);if(e)e.textContent=''})}
function setErrors(errors={}){clearErrors();Object.entries(errors).forEach(([k,m])=>{fields[k]?.classList.add('is-invalid');const e=document.getElementById(`error_${k}`);if(e)e.textContent=Array.isArray(m)?m[0]:m})}
function toggleAllDay(){const all=fields.is_all_day.checked;document.querySelectorAll('.time-field').forEach(el=>el.classList.toggle('d-none',all));if(all){fields.start_time.value='';fields.end_time.value=''}}
function setSubmitLoading(v){submitBtn.disabled=v;submitBtn.querySelector('.default-text').classList.toggle('d-none',v);submitBtn.querySelector('.loading-text').classList.toggle('d-none',!v)}
function openCreateModal(){resetForm();document.getElementById('scheduleModalTitle').textContent='Add Schedule';fields.schedule_date.value=new Date().toISOString().slice(0,10);scheduleModal.show()}
async function editSchedule(id){resetForm();document.getElementById('scheduleModalTitle').textContent='Edit Schedule';try{const d=await fetchSchedule(id);Object.keys(fields).forEach(k=>{if(k==='id')fields.id.value=d.id;else if(k==='is_all_day')fields[k].checked=!!d[k];else if(fields[k])fields[k].value=d[k]??''});filterOptions(fields.batch_id,fields.program_id.value);fields.batch_id.value=d.batch_id??'';toggleAllDay();scheduleModal.show()}catch(e){showToast(e.message,'danger')}}
fields.program_id.addEventListener('change',()=>filterOptions(fields.batch_id,fields.program_id.value));fields.is_all_day.addEventListener('change',toggleAllDay);
scheduleForm.addEventListener('submit',async e=>{e.preventDefault();clearErrors();const alert=document.getElementById('formAlert');alert.classList.add('d-none');const id=fields.id.value,payload={};Object.keys(fields).forEach(k=>{if(k!=='id')payload[k]=k==='is_all_day'?fields[k].checked:(fields[k].value||null)});setSubmitLoading(true);try{const response=await fetch(id?`${scheduleBaseUrl}/${id}`:scheduleBaseUrl,{method:id?'PUT':'POST',headers:{'Content-Type':'application/json',Accept:'application/json','X-CSRF-TOKEN':csrfToken,'X-Requested-With':'XMLHttpRequest'},body:JSON.stringify(payload)});const result=await response.json();if(response.status===422){setErrors(result.errors||{});throw new Error('Please review the highlighted fields.')}if(!response.ok||!result.success)throw new Error(result.message||'Failed to save schedule.');scheduleModal.hide();showToast(result.message);setTimeout(()=>location.reload(),900)}catch(err){alert.textContent=err.message;alert.classList.remove('d-none')}finally{setSubmitLoading(false)}});

const deleteModal=new bootstrap.Modal(document.getElementById('deleteModal')),confirmDeleteBtn=document.getElementById('confirmDeleteBtn');
function openDeleteModal(id,name){deleteScheduleId=id;document.getElementById('deleteScheduleName').textContent=name;deleteModal.show()}
confirmDeleteBtn.addEventListener('click',async()=>{if(!deleteScheduleId)return;confirmDeleteBtn.disabled=true;confirmDeleteBtn.querySelector('.default-delete-text').classList.add('d-none');confirmDeleteBtn.querySelector('.loading-delete-text').classList.remove('d-none');try{const r=await fetch(`${scheduleBaseUrl}/${deleteScheduleId}`,{method:'DELETE',headers:{Accept:'application/json','X-CSRF-TOKEN':csrfToken,'X-Requested-With':'XMLHttpRequest'}}),j=await r.json();if(!r.ok||!j.success)throw new Error(j.message||'Failed to delete schedule.');deleteModal.hide();showToast(j.message);setTimeout(()=>location.reload(),900)}catch(e){showToast(e.message,'danger')}finally{confirmDeleteBtn.disabled=false;confirmDeleteBtn.querySelector('.default-delete-text').classList.remove('d-none');confirmDeleteBtn.querySelector('.loading-delete-text').classList.add('d-none');deleteScheduleId=null}});
</script>
@endpush