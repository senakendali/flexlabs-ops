@extends('layouts.app-dashboard')

@section('title', 'MOM Detail')

@section('content')
@php
    $meetingDateLabel = $meetingMinute->meeting_date
        ? \Illuminate\Support\Carbon::parse($meetingMinute->meeting_date)->format('d M Y')
        : '-';

    $timeLabel = ($meetingMinute->start_time ? substr((string) $meetingMinute->start_time, 0, 5) : '-')
        . ' - '
        . ($meetingMinute->end_time ? substr((string) $meetingMinute->end_time, 0, 5) : '-');

    $statusClass = match ($meetingMinute->status) {
        'draft' => 'bg-warning-subtle text-warning-emphasis',
        'published' => 'bg-info-subtle text-info-emphasis',
        'completed' => 'bg-success-subtle text-success-emphasis',
        'cancelled' => 'bg-danger-subtle text-danger-emphasis',
        default => 'bg-light text-dark',
    };

    $overviewFilled = filled($meetingMinute->title)
        && filled($meetingMinute->meeting_type)
        && filled($meetingMinute->meeting_date)
        && filled($meetingMinute->status);

    $participantsFilled = $meetingMinute->participants->isNotEmpty();
    $agendaFilled = $meetingMinute->agendas->isNotEmpty();
    $actionFilled = $meetingMinute->actionItems->isNotEmpty();
    $summaryFilled = collect([
        $meetingMinute->summary,
        $meetingMinute->notes,
    ])->filter(fn ($value) => filled($value))->isNotEmpty();

    $sectionStates = [
        'overview' => $overviewFilled ? 'completed' : 'incomplete',
        'participants' => $participantsFilled ? 'completed' : 'optional',
        'agenda' => $agendaFilled ? 'completed' : 'optional',
        'action' => $actionFilled ? 'completed' : 'optional',
        'summary' => $summaryFilled ? 'completed' : 'incomplete',
    ];

    $filledSectionsCount = collect($sectionStates)->filter(fn ($state) => $state === 'completed')->count();
    $totalSectionsCount = count($sectionStates);
    $sectionProgressPercent = $totalSectionsCount > 0
        ? round(($filledSectionsCount / $totalSectionsCount) * 100)
        : 0;
@endphp

<div class="container-fluid px-4 py-4 meeting-minute-show-page">

    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Operations / Meeting Minutes</div>
                <h1 class="page-title mb-2">{{ $meetingMinute->title }}</h1>
                <p class="page-subtitle mb-0">
                    Detail MOM dalam format read-only agar lebih nyaman dibaca untuk monitoring dan follow-up operasional.
                </p>

                <div class="d-flex flex-wrap gap-2 mt-3">
                    <span class="badge rounded-pill bg-light text-dark border">
                        <i class="bi bi-journal-text me-1"></i>{{ $meetingMinute->meeting_no }}
                    </span>

                    <span class="badge rounded-pill bg-light text-dark border">
                        <i class="bi bi-tag me-1"></i>{{ ucwords(str_replace('_', ' ', $meetingMinute->meeting_type ?? '-')) }}
                    </span>

                    <span class="badge rounded-pill {{ $statusClass }}">
                        {{ ucwords(str_replace('_', ' ', $meetingMinute->status ?? '-')) }}
                    </span>

                    @if($meetingMinute->department)
                        <span class="badge rounded-pill bg-light text-dark border">
                            <i class="bi bi-diagram-3 me-1"></i>{{ ucwords(str_replace('_', ' ', $meetingMinute->department)) }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('operation.meeting-minutes.index') }}" class="btn btn-light border">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>

                <a href="{{ route('operation.meeting-minutes.edit', $meetingMinute) }}" class="btn btn-edit-accent">
                    <i class="bi bi-pencil-square me-1"></i> Edit
                </a>

                <button type="button" class="btn btn-danger" id="openDeleteModalBtn">
                    <i class="bi bi-trash me-1"></i> Delete
                </button>
            </div>
        </div>
    </div>

    <div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1090;"></div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Participants</div>
                        <div class="stat-value">{{ number_format((int) ($stats['participants'] ?? 0)) }}</div>
                    </div>
                </div>
                <div class="stat-description">Jumlah peserta yang tercatat pada MOM ini.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-list-ul"></i>
                    </div>
                    <div>
                        <div class="stat-title">Agenda</div>
                        <div class="stat-value">{{ number_format((int) ($stats['agendas'] ?? 0)) }}</div>
                    </div>
                </div>
                <div class="stat-description">Topik pembahasan yang masuk ke MOM.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-list-task"></i>
                    </div>
                    <div>
                        <div class="stat-title">Action Items</div>
                        <div class="stat-value">{{ number_format((int) ($stats['action_items'] ?? 0)) }}</div>
                    </div>
                </div>
                <div class="stat-description">Total follow-up task dari meeting ini.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div>
                        <div class="stat-title">Pending</div>
                        <div class="stat-value">{{ number_format((int) ($stats['pending_action_items'] ?? 0)) }}</div>
                    </div>
                </div>
                <div class="stat-description">Action item yang masih perlu follow-up.</div>
            </div>
        </div>
    </div>

    <div class="content-card mb-4">
        <div class="content-card-body">
            <div class="row g-3 align-items-stretch report-summary-row">
                <div class="col-lg-8 d-flex">
                    <div class="progress-summary-wrap w-100">
                        <div class="progress-summary-top d-flex justify-content-between align-items-center gap-3 mb-2">
                            <div>
                                <div class="progress-summary-label">MOM Readiness</div>
                                <div class="progress-summary-subtitle">
                                    Ringkasan kesiapan komponen MOM berdasarkan data yang sudah tercatat.
                                </div>
                            </div>

                            <div class="progress-summary-count">
                                {{ $filledSectionsCount }} dari {{ $totalSectionsCount }} section sudah siap
                            </div>
                        </div>

                        <div class="progress progress-modern" role="progressbar" aria-label="MOM readiness progress">
                            <div class="progress-bar" style="width: {{ $sectionProgressPercent }}%"></div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 d-flex">
                    <div class="status-pill-card w-100">
                        <div class="status-pill-label">MOM Status</div>
                        <div class="status-pill-value">
                            {{ ucfirst($meetingMinute->status ?? 'draft') }}
                        </div>
                        <div class="status-pill-help">
                            {{ $meetingMinute->meeting_no }} · {{ $meetingDateLabel }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-6">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Meeting Overview</h5>
                        <p class="content-card-subtitle mb-0">
                            Informasi dasar meeting yang tercatat di MOM.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="snapshot-list">
                        <div class="snapshot-item">
                            <span class="snapshot-label">Meeting Date</span>
                            <span class="snapshot-value">{{ $meetingDateLabel }}</span>
                        </div>

                        <div class="snapshot-item">
                            <span class="snapshot-label">Time</span>
                            <span class="snapshot-value">{{ $timeLabel }}</span>
                        </div>

                        <div class="snapshot-item">
                            <span class="snapshot-label">Organizer</span>
                            <span class="snapshot-value">{{ $meetingMinute->organizer?->name ?? '-' }}</span>
                        </div>

                        <div class="snapshot-item">
                            <span class="snapshot-label">Location</span>
                            <span class="snapshot-value">{{ $meetingMinute->location ?: '-' }}</span>
                        </div>

                        <div class="snapshot-item">
                            <span class="snapshot-label">Platform</span>
                            <span class="snapshot-value">{{ $meetingMinute->platform ?: '-' }}</span>
                        </div>

                        <div class="snapshot-item">
                            <span class="snapshot-label">Related Project</span>
                            <span class="snapshot-value">{{ $meetingMinute->related_project ?: '-' }}</span>
                        </div>

                        <div class="snapshot-item">
                            <span class="snapshot-label">Created By</span>
                            <span class="snapshot-value">{{ $meetingMinute->creator?->name ?? '-' }}</span>
                        </div>

                        <div class="snapshot-item">
                            <span class="snapshot-label">Last Update</span>
                            <span class="snapshot-value">{{ optional($meetingMinute->updated_at)->format('d M Y H:i') ?: '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Summary & Notes</h5>
                        <p class="content-card-subtitle mb-0">
                            Narasi ringkasan dan catatan tambahan meeting.
                        </p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="text-block-group">
                        <div class="text-block-item">
                            <div class="text-block-label">Summary</div>
                            <div class="text-block-value">{{ $meetingMinute->summary ?: '-' }}</div>
                        </div>

                        <div class="text-block-item">
                            <div class="text-block-label">Notes</div>
                            <div class="text-block-value">{{ $meetingMinute->notes ?: '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-4">
            <div class="content-card h-100">
                <div class="content-card-header d-flex justify-content-between align-items-center gap-2">
                    <div>
                        <h5 class="content-card-title mb-1">Participants</h5>
                        <p class="content-card-subtitle mb-0">
                            Peserta meeting.
                        </p>
                    </div>
                    <span class="badge rounded-pill bg-light text-dark border">
                        {{ $meetingMinute->participants->count() }}
                    </span>
                </div>

                <div class="content-card-body">
                    @if($meetingMinute->participants->count())
                        <div class="simple-list">
                            @foreach($meetingMinute->participants as $participant)
                                <div class="simple-list-item">
                                    <div class="simple-list-title">{{ $participant->display_name }}</div>
                                    <div class="simple-list-meta">
                                        {{ ucwords(str_replace('_', ' ', $participant->role ?? '-')) }}
                                        · {{ ucwords(str_replace('_', ' ', $participant->attendance_status ?? '-')) }}
                                        @if($participant->display_email)
                                            · {{ $participant->display_email }}
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="empty-state-box small-empty-state">
                            <div class="empty-state-icon"><i class="bi bi-people"></i></div>
                            <div class="empty-state-title">No participant</div>
                            <div class="empty-state-subtitle">Belum ada peserta pada MOM ini.</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="content-card h-100">
                <div class="content-card-header d-flex justify-content-between align-items-center gap-2">
                    <div>
                        <h5 class="content-card-title mb-1">Agenda</h5>
                        <p class="content-card-subtitle mb-0">
                            Topik pembahasan meeting.
                        </p>
                    </div>
                    <span class="badge rounded-pill bg-light text-dark border">
                        {{ $meetingMinute->agendas->count() }}
                    </span>
                </div>

                <div class="content-card-body">
                    @if($meetingMinute->agendas->count())
                        <div class="simple-list">
                            @foreach($meetingMinute->agendas as $agenda)
                                <div class="simple-list-item">
                                    <div class="simple-list-title">{{ $agenda->topic ?: '-' }}</div>
                                    <div class="simple-list-meta">{{ $agenda->description ?: 'Tidak ada deskripsi.' }}</div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="empty-state-box small-empty-state">
                            <div class="empty-state-icon"><i class="bi bi-list-ul"></i></div>
                            <div class="empty-state-title">No agenda</div>
                            <div class="empty-state-subtitle">Belum ada agenda pada MOM ini.</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="content-card h-100">
                <div class="content-card-header d-flex justify-content-between align-items-center gap-2">
                    <div>
                        <h5 class="content-card-title mb-1">Action Items</h5>
                        <p class="content-card-subtitle mb-0">
                            Follow-up task hasil meeting.
                        </p>
                    </div>
                    <span class="badge rounded-pill bg-light text-dark border">
                        {{ $meetingMinute->actionItems->count() }}
                    </span>
                </div>

                <div class="content-card-body">
                    @if($meetingMinute->actionItems->count())
                        <div class="simple-list">
                            @foreach($meetingMinute->actionItems as $item)
                                @php
                                    $actionStatusClass = match($item->status) {
                                        'done' => 'bg-success-subtle text-success-emphasis',
                                        'in_progress' => 'bg-primary-subtle text-primary-emphasis',
                                        'blocked' => 'bg-danger-subtle text-danger-emphasis',
                                        'cancelled' => 'bg-secondary-subtle text-secondary-emphasis',
                                        default => 'bg-warning-subtle text-warning-emphasis',
                                    };
                                @endphp

                                <div class="simple-list-item">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div>
                                            <div class="simple-list-title">{{ $item->title ?: '-' }}</div>
                                            <div class="simple-list-meta">
                                                PIC: {{ $item->pic_display_name }}
                                                @if($item->due_date)
                                                    · Due: {{ \Illuminate\Support\Carbon::parse($item->due_date)->format('d M Y') }}
                                                @endif
                                                · {{ ucwords($item->priority ?? 'medium') }}
                                            </div>
                                        </div>

                                        <span class="badge rounded-pill {{ $actionStatusClass }}">
                                            {{ ucwords(str_replace('_', ' ', $item->status ?? 'pending')) }}
                                        </span>
                                    </div>

                                    @if($item->description)
                                        <div class="simple-list-meta mt-2">{{ $item->description }}</div>
                                    @endif

                                    <div class="mt-3">
                                        <select
                                            class="form-select form-select-sm action-status-select"
                                            data-action-id="{{ $item->id }}"
                                            data-url="{{ route('operation.meeting-minute-action-items.update-status', $item) }}"
                                        >
                                            @foreach(['pending' => 'Pending', 'in_progress' => 'In Progress', 'done' => 'Done', 'blocked' => 'Blocked', 'cancelled' => 'Cancelled'] as $value => $label)
                                                <option value="{{ $value }}" @selected($item->status === $value)>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="empty-state-box small-empty-state">
                            <div class="empty-state-icon"><i class="bi bi-list-task"></i></div>
                            <div class="empty-state-title">No action item</div>
                            <div class="empty-state-subtitle">Belum ada follow-up task pada MOM ini.</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteMomModal" tabindex="-1" aria-labelledby="deleteMomModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold" id="deleteMomModalLabel">Delete MOM</h5>
                    <p class="text-muted small mb-0 mt-1">Tindakan ini tidak bisa dibatalkan.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body pt-3">
                <div class="delete-report-modal-box">
                    <div class="delete-report-modal-icon">
                        <i class="bi bi-trash3"></i>
                    </div>
                    <div>
                        <div class="fw-semibold text-dark mb-1">Yakin mau hapus MOM ini?</div>
                        <div class="text-muted small">
                            MOM <span class="fw-semibold text-dark">{{ $meetingMinute->title }}</span> akan dihapus permanen.
                            Participants, agenda, dan action item juga ikut terhapus.
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                    Cancel
                </button>

                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <span class="default-delete-text">
                        <i class="bi bi-trash me-1"></i> Delete MOM
                    </span>
                    <span class="loading-delete-text d-none">
                        <span class="spinner-border spinner-border-sm me-2" role="status"></span>Deleting...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    
</style>
@endpush

@push('scripts')
<script>
    const momDeleteUrl = @json(route('operation.meeting-minutes.destroy', $meetingMinute));
    const momIndexUrl = @json(route('operation.meeting-minutes.index'));
    const csrfToken = @json(csrf_token());

    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('openDeleteModalBtn')?.addEventListener('click', openDeleteModal);

        document.getElementById('confirmDeleteBtn')?.addEventListener('click', deleteMom);

        document.querySelectorAll('.action-status-select').forEach(function (select) {
            select.addEventListener('change', updateActionStatus);
        });
    });

    function showToast(message, type = 'success') {
        const toastContainer = document.getElementById('toastContainer');

        if (!toastContainer || typeof bootstrap === 'undefined') return;

        const toastId = 'toast-' + Date.now();

        const bgClass = {
            success: 'bg-success',
            danger: 'bg-danger',
            warning: 'bg-warning text-dark',
            info: 'bg-info text-dark',
        }[type] || 'bg-success';

        const closeBtnClass = (type === 'warning' || type === 'info')
            ? 'btn-close me-2 m-auto'
            : 'btn-close btn-close-white me-2 m-auto';

        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0 mb-2" role="alert">
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="${closeBtnClass}" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

        toastContainer.insertAdjacentHTML('beforeend', toastHtml);

        const toastEl = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastEl, { delay: 1600 });

        toast.show();

        toastEl.addEventListener('hidden.bs.toast', function () {
            toastEl.remove();
        });
    }

    function openDeleteModal() {
        const modalEl = document.getElementById('deleteMomModal');

        if (!modalEl || typeof bootstrap === 'undefined') return;

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    async function deleteMom() {
        const btn = document.getElementById('confirmDeleteBtn');

        if (!btn) return;

        const defaultText = btn.querySelector('.default-delete-text');
        const loadingText = btn.querySelector('.loading-delete-text');

        btn.disabled = true;
        defaultText?.classList.add('d-none');
        loadingText?.classList.remove('d-none');

        try {
            const response = await fetch(momDeleteUrl, {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Gagal menghapus MOM.');
            }

            showToast(data.message || 'MOM berhasil dihapus.', 'success');

            setTimeout(function () {
                window.location.href = momIndexUrl;
            }, 800);
        } catch (error) {
            showToast(error.message || 'Terjadi kesalahan saat menghapus MOM.', 'danger');

            btn.disabled = false;
            defaultText?.classList.remove('d-none');
            loadingText?.classList.add('d-none');
        }
    }

    async function updateActionStatus(event) {
        const select = event.target;
        const url = select.dataset.url;
        const status = select.value;

        select.disabled = true;

        try {
            const response = await fetch(url, {
                method: 'PATCH',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ status }),
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Gagal update status action item.');
            }

            showToast(data.message || 'Status action item berhasil diperbarui.', 'success');
        } catch (error) {
            showToast(error.message || 'Terjadi kesalahan saat update status.', 'danger');
        } finally {
            select.disabled = false;
        }
    }
</script>
@endpush