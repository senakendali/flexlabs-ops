@extends('layouts.app-dashboard')

@section('title', 'Instructor Schedule Detail')

@section('content')
@php
    $teachingInstructor = $schedule->replacementInstructor?->name ?: $schedule->instructor?->name;
    $statusLabel = $schedule->status_label ?? ucfirst($schedule->status ?? 'scheduled');
    $deliveryModeLabel = $schedule->delivery_mode_label ?? ucfirst($schedule->delivery_mode ?? 'online');
    $scheduleDateLabel = $schedule->schedule_date_label ?? optional($schedule->schedule_date)->format('d M Y') ?? '-';
    $timeRange = $schedule->time_range ?? '-';
    $isMakeup = (bool) ($schedule->is_makeup_session ?? false);
@endphp

<div class="container-fluid px-4 py-4 instructor-schedules-show-page marketing-report-show-page">
    <div class="page-header-card mb-4">
        <div class="page-header-content d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="page-eyebrow">Academic</div>
                <h1 class="page-title mb-2">Instructor Schedule Detail</h1>
                <p class="page-subtitle mb-0">
                    Lihat detail sesi, assignment instruktur, informasi pelaksanaan, dan catatan penjadwalan dalam tampilan yang lebih rapi dan mudah dibaca.
                </p>
            </div>

            <div class="d-flex gap-2 flex-wrap page-header-actions">
                <a href="{{ route('instructor-schedules.index') }}" class="btn btn-light border">
                    <i class="bi bi-list-ul me-1"></i> List
                </a>
                <a href="{{ route('instructor-schedules.edit', $schedule) }}" class="btn btn-edit-accent">
                    <i class="bi bi-pencil-square me-1"></i> Edit Schedule
                </a>
                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteScheduleModal">
                    <i class="bi bi-trash3 me-1"></i> Delete
                </button>
            </div>
        </div>
    </div>

    <div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1090;"></div>

    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card dashboard-stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-calendar-event-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Schedule Date</div>
                        <div class="stat-value">{{ $scheduleDateLabel }}</div>
                    </div>
                </div>
                <div class="stat-description">Tanggal pelaksanaan sesi yang sedang dibuka.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card dashboard-stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-clock-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Time Range</div>
                        <div class="stat-value">{{ $timeRange }}</div>
                    </div>
                </div>
                <div class="stat-description">Rentang waktu sesi untuk aktivitas pengajaran ini.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card dashboard-stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-person-video3"></i>
                    </div>
                    <div>
                        <div class="stat-title">Instructor</div>
                        <div class="stat-value">{{ $teachingInstructor ?: '-' }}</div>
                    </div>
                </div>
                <div class="stat-description">Instruktur yang tercatat mengajar pada sesi ini.</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card dashboard-stat-card h-100">
                <div class="stat-card-top">
                    <div class="stat-icon-wrap">
                        <i class="bi bi-collection-play-fill"></i>
                    </div>
                    <div>
                        <div class="stat-title">Batch</div>
                        <div class="stat-value">{{ $schedule->batch->name ?? '-' }}</div>
                    </div>
                </div>
                <div class="stat-description">Batch yang terhubung dengan jadwal pengajaran ini.</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="content-card mb-4">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Session Overview</h5>
                        <p class="content-card-subtitle mb-0">Informasi inti sesi yang dijadwalkan untuk batch dan program terkait.</p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="info-grid">
                        <div class="info-item info-item-full">
                            <div class="info-label">Session Title</div>
                            <div class="info-value">{{ $schedule->session_title ?: '-' }}</div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Program</div>
                            <div class="info-value">{{ $schedule->program->name ?? '-' }}</div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Session / Sub Topic</div>
                            <div class="info-value">{{ $schedule->subTopic->name ?? '-' }}</div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Delivery Mode</div>
                            <div class="info-value">{{ $deliveryModeLabel }}</div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Session Type</div>
                            <div class="info-value">{{ $isMakeup ? 'Makeup Session' : 'Regular Session' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-card mb-4">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Assignment Detail</h5>
                        <p class="content-card-subtitle mb-0">Informasi pengajar utama, instruktur pengganti, batch, dan relasi reschedule.</p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Main Instructor</div>
                            <div class="info-value">{{ $schedule->instructor->name ?? '-' }}</div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Teaching Instructor</div>
                            <div class="info-value">{{ $teachingInstructor ?: '-' }}</div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Replacement Instructor</div>
                            <div class="info-value">{{ $schedule->replacementInstructor->name ?? '-' }}</div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Batch</div>
                            <div class="info-value">{{ $schedule->batch->name ?? '-' }}</div>
                        </div>

                        <div class="info-item info-item-full">
                            <div class="info-label">Rescheduled From</div>
                            <div class="info-value">
                                @if ($schedule->rescheduledFrom)
                                    {{ $schedule->rescheduledFrom->session_title }}
                                    <div class="small text-muted mt-1">
                                        {{ optional($schedule->rescheduledFrom->schedule_date)->format('d M Y') ?? '-' }}
                                        · {{ $schedule->rescheduledFrom->start_time ?? '-' }} - {{ $schedule->rescheduledFrom->end_time ?? '-' }}
                                        @if ($schedule->rescheduledFrom->batch?->name)
                                            · {{ $schedule->rescheduledFrom->batch->name }}
                                        @endif
                                    </div>
                                @else
                                    -
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Delivery Information</h5>
                        <p class="content-card-subtitle mb-0">Detail pelaksanaan sesi, meeting link, lokasi, dan catatan tambahan.</p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="text-block-group">
                        <div class="text-block-item">
                            <div class="text-block-label">Meeting Link</div>
                            <div class="text-block-value">
                                @if ($schedule->meeting_link)
                                    <a href="{{ $schedule->meeting_link }}" target="_blank" rel="noopener noreferrer">{{ $schedule->meeting_link }}</a>
                                @else
                                    -
                                @endif
                            </div>
                        </div>

                        <div class="text-block-item">
                            <div class="text-block-label">Location</div>
                            <div class="text-block-value">{{ $schedule->location ?: '-' }}</div>
                        </div>

                        <div class="text-block-item border-0 pb-0">
                            <div class="text-block-label">Notes</div>
                            <div class="text-block-value">{{ $schedule->notes ?: '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="content-card mb-4">
                <div class="content-card-header">
                    <div>
                        <h5 class="content-card-title mb-1">Quick Summary</h5>
                        <p class="content-card-subtitle mb-0">Gambaran ringkas status, mode pelaksanaan, dan waktu pencatatan jadwal.</p>
                    </div>
                </div>

                <div class="content-card-body">
                    <div class="simple-list">
                        <div class="simple-list-item">
                            <div class="simple-list-title">Current Status</div>
                            <div class="simple-list-meta">{{ $statusLabel }}</div>
                        </div>

                        <div class="simple-list-item">
                            <div class="simple-list-title">Delivery Mode</div>
                            <div class="simple-list-meta">{{ $deliveryModeLabel }}</div>
                        </div>

                        <div class="simple-list-item">
                            <div class="simple-list-title">Session Type</div>
                            <div class="simple-list-meta">{{ $isMakeup ? 'Makeup Session' : 'Regular Session' }}</div>
                        </div>

                        <div class="simple-list-item">
                            <div class="simple-list-title">Schedule Created</div>
                            <div class="simple-list-meta">{{ optional($schedule->created_at)->format('d M Y H:i') ?? '-' }}</div>
                        </div>

                        <div class="simple-list-item">
                            <div class="simple-list-title">Last Updated</div>
                            <div class="simple-list-meta">{{ optional($schedule->updated_at)->format('d M Y H:i') ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteScheduleModal" tabindex="-1" aria-labelledby="deleteScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold" id="deleteScheduleModalLabel">Delete Schedule</h5>
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
                        <div class="fw-semibold text-dark mb-1">Hapus jadwal ini?</div>
                        <div class="text-muted small">
                            Jadwal <strong>{{ $schedule->session_title }}</strong> akan dihapus dari sistem.
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteScheduleBtn">
                    <i class="bi bi-trash3 me-1"></i> Delete Schedule
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const toastContainer = document.getElementById('toastContainer');
    const deleteBtn = document.getElementById('confirmDeleteScheduleBtn');
    const deleteModalEl = document.getElementById('deleteScheduleModal');
    const deleteModal = deleteModalEl && typeof bootstrap !== 'undefined'
        ? bootstrap.Modal.getOrCreateInstance(deleteModalEl)
        : null;

    function showToast(message, type = 'success') {
        if (!toastContainer || typeof bootstrap === 'undefined') return;

        const toastId = 'toast-' + Date.now();
        const bgClass = {
            success: 'bg-success',
            danger: 'bg-danger',
            warning: 'bg-warning text-dark',
            info: 'bg-info text-dark'
        }[type] || 'bg-success';

        const closeBtnClass = (type === 'warning' || type === 'info')
            ? 'btn-close me-2 m-auto'
            : 'btn-close btn-close-white me-2 m-auto';

        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="${closeBtnClass}" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        const toastEl = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastEl, { delay: 1800 });
        toast.show();
        toastEl.addEventListener('hidden.bs.toast', function () { toastEl.remove(); });
    }

    @if (session('success'))
        showToast(@json(session('success')), 'success');
    @endif

    if (deleteBtn) {
        deleteBtn.addEventListener('click', async function () {
            const originalHtml = deleteBtn.innerHTML;
            deleteBtn.disabled = true;
            deleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Deleting...';

            try {
                const response = await fetch(@json(route('instructor-schedules.destroy', $schedule)), {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': @json(csrf_token()),
                    },
                    body: (() => {
                        const formData = new FormData();
                        formData.append('_method', 'DELETE');
                        return formData;
                    })(),
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    showToast(data.message || 'Gagal menghapus jadwal.', 'danger');
                    return;
                }

                if (deleteModal) deleteModal.hide();
                showToast(data.message || 'Jadwal berhasil dihapus.', 'success');

                setTimeout(() => {
                    window.location.href = data.redirect || @json(route('instructor-schedules.index'));
                }, 700);
            } catch (error) {
                showToast('Terjadi kesalahan saat menghapus jadwal.', 'danger');
            } finally {
                deleteBtn.disabled = false;
                deleteBtn.innerHTML = originalHtml;
            }
        });
    }
});
</script>
@endpush
